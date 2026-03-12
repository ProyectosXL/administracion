<?php

/**
 * rentabilidad_rubro_controller.php
 * Controller para el reporte de Rentabilidad por Rubro
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0'); // No mostrar al cliente; los errores llegan por json

session_start();

require_once __DIR__ . '/../../../class/conexion.php';
require_once __DIR__ . '/../class/RentabilidadRubro.class.php';

// ── Conexión ──────────────────────────────────────────────────────────────────
try {
    $conexion = new Conexion();

    if (isset($_SESSION['entorno']) && $_SESSION['entorno'] === 'uy') {
        $conn = $conexion->conectar('uy');
    } else {
        $conn = $conexion->conectar('central');
    }

    if (!$conn) {
        throw new RuntimeException('No se pudo establecer conexión con la base de datos.');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── CANALES ───────────────────────────────────────────────────────────────
    case 'get_canales':
        try {
            $repo    = new RentabilidadRubro($conn);
            $canales = $repo->getCanales();
            echo json_encode(['success' => true, 'canales' => $canales]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => sqlsrv_errors()]);
        }
        break;

    // ── REPORTE ───────────────────────────────────────────────────────────────
    case 'get_reporte':
        $desde  = trim($_POST['desde'] ?? '');
        $hasta  = trim($_POST['hasta'] ?? '');
        $canal  = trim($_POST['canal'] ?? '');
        $moneda = in_array(trim($_POST['moneda'] ?? ''), ['ARS', 'USD'], true)
                    ? trim($_POST['moneda'])
                    : 'ARS';

        // Validar formato de período (M-AAAA o MM-AAAA)
        $regexPeriodo = '/^\d{1,2}-\d{4}$/';

        if (!preg_match($regexPeriodo, $desde) || !preg_match($regexPeriodo, $hasta)) {
            echo json_encode([
                'success' => false,
                'message' => 'Formato de período inválido. Use M-AAAA (ej: 1-2025).'
            ]);
            exit;
        }

        // Validar rango: desde <= hasta
        [$mesD, $anioD] = array_map('intval', explode('-', $desde));
        [$mesH, $anioH] = array_map('intval', explode('-', $hasta));

        if ($anioD > $anioH || ($anioD === $anioH && $mesD > $mesH)) {
            echo json_encode([
                'success' => false,
                'message' => 'El período Desde no puede ser mayor que el período Hasta.'
            ]);
            exit;
        }

        try {
            $repo   = new RentabilidadRubro($conn);
            $result = $repo->buildReporte($desde, $hasta, $canal, $moneda);
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => sqlsrv_errors()]);
        }
        break;

    // ── PROCESAR PERÍODO ──────────────────────────────────────────────────────
    case 'procesar_periodo':
        $fechaDesde = trim($_POST['fecha_desde'] ?? '');
        $fechaHasta = trim($_POST['fecha_hasta'] ?? '');

        // Validar formato YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido.']);
            exit;
        }

        try {
            $repo = new RentabilidadRubro($conn);
            $repo->procesarPeriodo($fechaDesde, $fechaHasta);
            echo json_encode(['success' => true, 'message' => 'Período procesado correctamente.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}
