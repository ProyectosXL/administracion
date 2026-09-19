<?php
/**
 * El historial de vigencias de un concepto.
 *
 * Devuelve TODAS, incluidas las retiradas (ACTIVO = 0): el historial completo
 * es el motivo por el que existe la tabla. Cuál rige hoy lo dice 'vigenteHoy'.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

try {
    require_once __DIR__ . '/../../../class/conexion.php';
    require_once __DIR__ . '/../../class/AlicuotasVigencia.php';

    if (!isset($_GET['id_ce']) || $_GET['id_ce'] === '') {
        throw new Exception('Concepto no proporcionado');
    }

    $idCe = intval($_GET['id_ce']);

    $cid  = new Conexion();
    $db   = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);

    if (!$conn) {
        throw new Exception('No se pudo establecer conexión a la base de datos');
    }

    /* Si el script 09 no se corrió, no es un error: la aplicación funciona
       igual usando el padrón. La pantalla lo dice y deja el ABM apagado, que
       es el mismo criterio que usa Finanzas cuando falta una tabla suya. */
    $disponible = AlicuotasVigencia::disponible($conn);

    echo json_encode([
        'success'     => true,
        'disponible'  => $disponible,
        'script'      => 'comercioExterior/sql/09_alicuotas_vigencia.sql',
        'id_ce'       => $idCe,
        'vigencias'   => $disponible ? AlicuotasVigencia::historial($conn, $idCe) : [],
        'vigenteHoy'  => $disponible ? AlicuotasVigencia::vigenteHoy($conn, $idCe) : null,
        'hoy'         => date('Y-m-d')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Error en listarVigencias.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
