<?php
/**
 * ParametrosController.php
 * Pestaña Parámetros: listar, historial y guardar.
 *
 *   POST Controller/ParametrosController.php?action=...
 *   responde { success, message, ... }
 *
 * Lo que pide cada acción está en AuthReportes (LECTURAS / ESCRITURAS); acá
 * sólo se orquesta. El usuario de la auditoría es el que devuelve autorizar():
 * el front no manda ninguna columna de auditoría, y si las mandara se
 * ignorarían.
 */
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

require_once __DIR__ . '/autorizacion.php';
require_once __DIR__ . '/../Class/Parametros.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
$usuario = autorizar('Parametros', $action);

$clave = isset($_POST['clave']) ? trim((string) $_POST['clave']) : '';

try {
    $parametros = new Parametros();

    switch ($action) {
        case 'listar':
            $respuesta = ['success' => true, 'parametros' => $parametros->listar()];
            break;

        case 'historial':
            $respuesta = ['success' => true, 'clave' => $clave, 'historial' => $parametros->historial($clave)];
            break;

        case 'guardar':
            $respuesta = $parametros->guardar($clave, $_POST['valor'] ?? '', $usuario);
            break;

        default:
            // No se llega: autorizar() ya rechazó lo que no está declarado
            http_response_code(400);
            $respuesta = ['success' => false, 'message' => 'Acción no válida.'];
    }
} catch (Throwable $e) {
    error_log('Reportes/ParametrosController ' . $action . ': ' . $e->getMessage());
    $respuesta = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
