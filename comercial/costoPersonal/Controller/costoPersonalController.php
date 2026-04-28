<?php
/**
 * costoPersonalController.php
 * Controlador AJAX — Pestaña "Reporte por Sucursal".
 * Devuelve el dataset completo de una sucursal (costos + ventas + %).
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';

try {
    require_once CP_BASE_PATH . '/Class/CostoPersonalService.php';

    $idSucursal = $_POST['idSucursal'] ?? '';
    $fechaDesde = $_POST['fechaDesde'] ?? '';
    $fechaHasta = $_POST['fechaHasta'] ?? '';

    if (empty($idSucursal)) throw new Exception('Debe indicar idSucursal.');

    $service = new CostoPersonalService();

    if (empty($fechaDesde) || empty($fechaHasta)) {
        $rango = $service->calcularRangoDefault();
        $fechaDesde = $rango['desde'];
        $fechaHasta = $rango['hasta'];
    }

    $dDesde = DateTime::createFromFormat('Y-m-d', $fechaDesde);
    $dHasta = DateTime::createFromFormat('Y-m-d', $fechaHasta);
    if (!$dDesde || !$dHasta) throw new Exception('Formato de fecha inválido.');
    if ($dDesde > $dHasta)    throw new Exception('La fecha desde no puede ser mayor a la fecha hasta.');

    $dataset = $service->construirDataset((int) $idSucursal, $fechaDesde, $fechaHasta);

    // Datos de evolución (últimos 6 meses vs YoY) para modal de gráfico
    $evolucion = $service->obtenerDatosEvolucion((int) $idSucursal, $dataset['meses']);

    echo json_encode([
        'success'   => true,
        'data'      => $dataset,
        'evolucion' => $evolucion,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('costoPersonalController — ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
