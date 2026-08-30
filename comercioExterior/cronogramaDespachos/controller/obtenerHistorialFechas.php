<?php
/**
 * Historial de cambios de fecha de un grupo de contenedor.
 * Se carga por demanda al abrir el modal de detalle.
 */
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../class/conexion.php';
require_once __DIR__ . '/../class/CronogramaFechas.php';
require_once __DIR__ . '/../class/MotivosFecha.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

try {
    $idEncabezado = isset($_GET['idEncabezado']) ? (int) $_GET['idEncabezado'] : 0;
    if ($idEncabezado <= 0) {
        throw new Exception('Falta el identificador del despacho');
    }

    $cid  = new Conexion();
    $db   = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);
    if (!$conn) {
        throw new Exception('No se pudo establecer conexión a la base de datos');
    }

    // Se trae el historial de TODAS las OCs del grupo: un movimiento impacta
    // al grupo entero, asi que ver solo una OC daria una historia parcial.
    $idsGrupo = CronogramaFechas::obtenerIdsDelGrupo($conn, $idEncabezado);
    if (empty($idsGrupo)) {
        echo json_encode(['success' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($idsGrupo), '?'));
    $sql = "SELECT TOP 200
                   ID, ID_ENCABEZADO, ORDEN_COMPRA, CAMPO,
                   VALOR_ANTERIOR, VALOR_NUEVO, MOTIVO, OBSERVACION,
                   USUARIO, ORIGEN, FECHA_ALTA
            FROM RO_T_IMPORTACIONES_FECHAS_HIST
            WHERE ID_ENCABEZADO IN ($placeholders)
            ORDER BY FECHA_ALTA DESC, ID DESC";

    $stmt = sqlsrv_query($conn, $sql, $idsGrupo);
    if ($stmt === false) {
        throw new Exception('Error al consultar el historial: ' . print_r(sqlsrv_errors(), true));
    }

    $historial = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $historial[] = [
            'id'            => (int) $row['ID'],
            'idEncabezado'  => (int) $row['ID_ENCABEZADO'],
            'ordenCompra'   => trim((string) $row['ORDEN_COMPRA']),
            'campo'         => $row['CAMPO'],
            'campoLabel'    => CronogramaFechas::etiqueta($row['CAMPO']),
            'valorAnterior' => ($row['VALOR_ANTERIOR'] instanceof DateTime)
                                ? $row['VALOR_ANTERIOR']->format('Y-m-d') : null,
            'valorNuevo'    => ($row['VALOR_NUEVO'] instanceof DateTime)
                                ? $row['VALOR_NUEVO']->format('Y-m-d') : null,
            'motivo'        => $row['MOTIVO'],
            'motivoLabel'   => $row['MOTIVO'] ? MotivosFecha::label($row['MOTIVO']) : null,
            'observacion'   => $row['OBSERVACION'],
            'usuario'       => $row['USUARIO'],
            'origen'        => $row['ORIGEN'],
            'fechaAlta'     => ($row['FECHA_ALTA'] instanceof DateTime)
                                ? $row['FECHA_ALTA']->format('Y-m-d H:i') : null,
        ];
    }

    echo json_encode([
        'success'   => true,
        'ocsGrupo'  => count($idsGrupo),
        'data'      => $historial,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('obtenerHistorialFechas: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
