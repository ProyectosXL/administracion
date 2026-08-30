<?php
/**
 * Mueve una fecha del cronograma en TODAS las OCs del grupo de contenedor,
 * con validacion server-side y registro en el historial.
 *
 * Lo usan tanto el drag & drop del calendario como los inputs de fecha del
 * modal de detalle, para que ninguna edicion esquive el historial.
 */
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// conexion.php explicito: las otras clases lo requieren dentro de sus
// constructores, asi que sin esto la clase Conexion no existe todavia cuando
// este controller la instancia.
require_once __DIR__ . '/../../../class/conexion.php';
require_once __DIR__ . '/../class/CronogramaDespachos.php';
require_once __DIR__ . '/../class/CronogramaFechas.php';
require_once __DIR__ . '/../class/MotivosFecha.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$conn = null;
$enTransaccion = false;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Cuerpo inválido');
    }

    $idEncabezado = isset($data['idEncabezado']) ? (int) $data['idEncabezado'] : 0;
    $campo        = isset($data['campo']) ? trim($data['campo']) : '';
    $fechaNueva   = isset($data['fechaNueva']) ? trim($data['fechaNueva']) : '';
    $motivo       = isset($data['motivo']) ? trim($data['motivo']) : '';
    $observacion  = isset($data['observacion']) ? trim($data['observacion']) : '';

    if ($idEncabezado <= 0) {
        throw new Exception('Falta el identificador del despacho');
    }

    // 1. Whitelist del campo. Nunca se interpola un nombre de columna que
    //    venga del request.
    if (!CronogramaFechas::esCampoEditable($campo)) {
        if ($campo === 'FECHA_REC') {
            throw new Exception('La fecha de recepción se toma de Tango y no es editable');
        }
        throw new Exception('Campo no editable: ' . $campo);
    }

    // 2. Formato de fecha.
    if (!CronogramaFechas::esFechaValida($fechaNueva)) {
        throw new Exception('Fecha inválida. Se espera el formato AAAA-MM-DD');
    }

    if ($motivo !== '' && !MotivosFecha::esValido($motivo)) {
        throw new Exception('Motivo no reconocido: ' . $motivo);
    }

    if (mb_strlen($observacion) > 500) {
        throw new Exception('La observación no puede superar los 500 caracteres');
    }

    $cid  = new Conexion();
    $db   = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);
    if (!$conn) {
        throw new Exception('No se pudo establecer conexión a la base de datos');
    }

    // 3. Resolver el grupo de contenedor: el cambio impacta a todas sus OCs.
    //    Se agrupa igual que el calendario (ID_PADRE y, si no, COD_PROVEE +
    //    CONTENEDOR); si no, un badge que representa 3 OCs moveria una sola.
    $idsGrupo = CronogramaFechas::obtenerIdsDelGrupo($conn, $idEncabezado);
    if (empty($idsGrupo)) {
        throw new Exception('No se encontró el despacho ' . $idEncabezado);
    }

    // 4. Valores anteriores de todas las OCs del grupo.
    $antes = CronogramaFechas::leerFechas($conn, $idsGrupo);
    if (empty($antes) || !isset($antes[$idEncabezado])) {
        throw new Exception('No se encontró el despacho ' . $idEncabezado);
    }

    $filaBase = $antes[$idEncabezado];

    // 5. Observación obligatoria cuando se mueve algo confirmado.
    //    Se valida ACA y no solo en el front: el front es una comodidad.
    $exigeObservacion = CronogramaFechas::motivoObservacionObligatoria($campo, $filaBase);
    if ($exigeObservacion !== null && $observacion === '') {
        throw new Exception('Se requiere una observación: ' . $exigeObservacion);
    }

    // 6. Coherencia cronológica.
    $coherencia = CronogramaFechas::validarCoherencia($campo, $fechaNueva, $filaBase);
    if (!empty($coherencia['errores'])) {
        http_response_code(422);
        echo json_encode([
            'success'      => false,
            'message'      => 'El movimiento rompe el orden cronológico',
            'errores'      => $coherencia['errores'],
            'advertencias' => $coherencia['advertencias'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $usuario = isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null;

    if (sqlsrv_begin_transaction($conn) === false) {
        throw new Exception('No se pudo iniciar la transacción');
    }
    $enTransaccion = true;

    // 7. Actualizar el campo en todas las OCs del grupo.
    //    El nombre de columna sale de la whitelist, nunca del request.
    $placeholders = implode(',', array_fill(0, count($idsGrupo), '?'));
    $sqlUpdate = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                  SET $campo = ?
                  WHERE ID IN ($placeholders)";

    $stmt = sqlsrv_query($conn, $sqlUpdate, array_merge([$fechaNueva], $idsGrupo));
    if ($stmt === false) {
        throw new Exception('Error al actualizar: ' . print_r(sqlsrv_errors(), true));
    }

    // 8. Mover la distribución a mano la marca como manual, para que el
    //    recálculo automático no la vuelva a pisar.
    if ($campo === 'FECHA_DISTRI') {
        $stmt = sqlsrv_query(
            $conn,
            "UPDATE RO_T_IMPORTACIONES_ENCABEZADO SET DIST_ORIGEN = 'M'
             WHERE ID IN ($placeholders)",
            $idsGrupo
        );
        if ($stmt === false) {
            throw new Exception('Error al marcar DIST_ORIGEN: ' . print_r(sqlsrv_errors(), true));
        }
    }

    // 9. Historial: una fila por cada OC del grupo.
    foreach ($idsGrupo as $id) {
        if (!isset($antes[$id])) {
            continue;
        }
        CronogramaFechas::registrarHistorial($conn, [
            'idEncabezado'  => $id,
            'ordenCompra'   => $antes[$id]['ORDEN_COMPRA'],
            'contenedor'    => $antes[$id]['CONTENEDOR'],
            'campo'         => $campo,
            'valorAnterior' => $antes[$id][$campo],
            'valorNuevo'    => $fechaNueva,
            'motivo'        => ($motivo !== '' ? $motivo : null),
            'observacion'   => ($observacion !== '' ? $observacion : null),
            'usuario'       => $usuario,
            'origen'        => 'CRONOGRAMA',
        ]);
    }

    // 10. Cascada: mover el arribo arrastra la distribución automática.
    $recalculadas = [];
    if ($campo === 'FECHA_ARR') {
        $recalculadas = CronogramaFechas::recalcularDistribucion($conn, $idsGrupo, $fechaNueva);

        foreach ($recalculadas as $id => $nuevaDistri) {
            CronogramaFechas::registrarHistorial($conn, [
                'idEncabezado'  => $id,
                'ordenCompra'   => $antes[$id]['ORDEN_COMPRA'],
                'contenedor'    => $antes[$id]['CONTENEDOR'],
                'campo'         => 'FECHA_DISTRI',
                'valorAnterior' => $antes[$id]['FECHA_DISTRI'],
                'valorNuevo'    => $nuevaDistri,
                'motivo'        => MotivosFecha::RECALCULO_AUTOMATICO,
                'observacion'   => null,
                'usuario'       => $usuario,
                'origen'        => 'CRONOGRAMA',
            ]);
        }
    }

    if (sqlsrv_commit($conn) === false) {
        throw new Exception('Error al confirmar la transacción');
    }
    $enTransaccion = false;

    // 11. Devolver los despachos actualizados, para que el front repinte sin
    //     recargar toda la página.
    $cronograma = new CronogramaDespachos();
    $todos = $cronograma->obtenerDespachos();
    $actualizados = [];

    if (is_array($todos)) {
        foreach ($todos as $d) {
            if (in_array((int) $d['ID'], $idsGrupo, true)) {
                $d['ESTADO'] = CronogramaDespachos::determinarEstado($d);
                $actualizados[] = $d;
            }
        }
    }

    echo json_encode([
        'success'      => true,
        'message'      => 'Fecha actualizada en ' . count($idsGrupo) .
                          (count($idsGrupo) === 1 ? ' orden de compra' : ' órdenes de compra'),
        'ocsAfectadas' => count($idsGrupo),
        'recalculadas' => $recalculadas,
        'advertencias' => $coherencia['advertencias'],
        'data'         => $actualizados,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($enTransaccion && $conn) {
        sqlsrv_rollback($conn);
    }
    error_log('actualizarFechaCronograma: ' . $e->getMessage());
    if (http_response_code() === 200) {
        http_response_code(400);
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
