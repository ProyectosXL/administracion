<?php
/**
 * Alta de una vigencia de alícuota, y baja lógica de una existente.
 *
 * EL PADRÓN SE SINCRONIZA, NO SE DUPLICA.
 * RO_T_CONCEPTOS_ESTIMACION_COMEX.VALOR_DEFAULT_1/2 sigue siendo lo que leen
 * la pantalla de Parámetros y cualquier consulta que no sepa de vigencias, así
 * que después de guardar se lo deja en el valor que rige HOY. No es una
 * segunda fuente de verdad: la verdad de "qué alícuota aplica a una operación"
 * está siempre en la tabla de vigencias y se resuelve contra la fecha de
 * nacionalización. El padrón es el espejo del vigente hoy, y existe para que
 * nada de lo que ya lo lee cambie de comportamiento.
 *
 * Por eso el padrón NO se toca cuando se carga una vigencia que todavía no
 * empezó o una que ya terminó: en los dos casos lo que rige hoy es otra cosa.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    require_once __DIR__ . '/../../../class/conexion.php';
    require_once __DIR__ . '/../../class/AlicuotasVigencia.php';

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Cuerpo de la solicitud inválido');
    }

    $cid  = new Conexion();
    $db   = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);

    if (!$conn) {
        throw new Exception('No se pudo establecer conexión a la base de datos');
    }

    $usuario = $_SESSION['usuario_dns'] ?? null;
    $accion  = isset($data['accion']) ? $data['accion'] : 'agregar';

    // ------------------------------------------------------------------
    // Baja lógica
    // ------------------------------------------------------------------
    if ($accion === 'retirar') {
        if (!isset($data['id'])) {
            throw new Exception('Falta el ID de la vigencia');
        }

        $resultado = AlicuotasVigencia::retirar($conn, intval($data['id']), $usuario);

        if ($resultado['success']) {
            $resultado['padron'] = sincronizarPadron($conn, intval($data['id_ce'] ?? 0));
        }

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ------------------------------------------------------------------
    // Alta
    // ------------------------------------------------------------------
    if (!isset($data['id_ce']) || !isset($data['vigencia_desde'])) {
        throw new Exception('Faltan datos: concepto y "vigente desde" son obligatorios');
    }

    $idCe = intval($data['id_ce']);

    $valor1 = (isset($data['valor_1']) && $data['valor_1'] !== '' && $data['valor_1'] !== null)
        ? floatval($data['valor_1']) : null;
    $valor2 = (isset($data['valor_2']) && $data['valor_2'] !== '' && $data['valor_2'] !== null)
        ? floatval($data['valor_2']) : null;

    if ($valor1 === null) {
        throw new Exception('El valor de la alícuota es obligatorio');
    }

    // Vacío significa "sigue vigente", no "hoy".
    $hasta = (isset($data['vigencia_hasta']) && trim((string) $data['vigencia_hasta']) !== '')
        ? $data['vigencia_hasta'] : null;

    $resultado = AlicuotasVigencia::agregar(
        $conn, $idCe, $valor1, $valor2,
        $data['vigencia_desde'], $hasta,
        $data['observacion'] ?? null,
        $usuario
    );

    if ($resultado['success']) {
        $resultado['padron'] = sincronizarPadron($conn, $idCe);
    }

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Error en guardarVigencia.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/**
 * Deja VALOR_DEFAULT_1/2 del padrón igual a la vigencia que rige hoy.
 *
 * Si hoy no rige ninguna -todas las cargadas son futuras, o quedaron todas
 * cerradas- el padrón se deja como está: es el último valor conocido y
 * borrarlo dejaría sin alícuota a todo lo que todavía lo lee.
 *
 * @return string qué hizo, para que la pantalla lo pueda decir
 */
function sincronizarPadron($conn, $idCe)
{
    if ($idCe <= 0) {
        return 'sin cambios';
    }

    $vigente = AlicuotasVigencia::vigenteHoy($conn, $idCe);

    if ($vigente === null) {
        return 'sin cambios: hoy no rige ninguna vigencia cargada';
    }

    $sql = "UPDATE RO_T_CONCEPTOS_ESTIMACION_COMEX
            SET VALOR_DEFAULT_1 = ?, VALOR_DEFAULT_2 = ?, ULT_ACTUA = GETDATE()
            WHERE ID_CE = ?";

    $stmt = sqlsrv_query($conn, $sql,
        array($vigente['VALOR_1'], $vigente['VALOR_2'], $idCe));

    if ($stmt === false) {
        error_log('[guardarVigencia::sincronizarPadron] ' . print_r(sqlsrv_errors(), true));
        return 'no se pudo sincronizar el padrón';
    }

    return 'padrón sincronizado con la vigencia de hoy';
}
