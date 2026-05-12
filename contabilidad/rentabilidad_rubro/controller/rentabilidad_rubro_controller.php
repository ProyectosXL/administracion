<?php

/**
 * rentabilidad_rubro_controller.php
 * Controller para el reporte de Rentabilidad por Rubro
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();

require_once __DIR__ . '/../../../class/conexion.php';
require_once __DIR__ . '/../class/RentabilidadRubro.class.php';

// ── Conexión ──────────────────────────────────────────────────────────────────
try {
    $conexion = new Conexion();
    $conn = (isset($_SESSION['entorno']) && $_SESSION['entorno'] === 'uy')
        ? $conexion->conectar('uy')
        : $conexion->conectar('central');

    if (!$conn) throw new RuntimeException('No se pudo establecer conexión con la base de datos.');
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Helper: validar y parsear período ────────────────────────────────────────
function validarPeriodo(string $p): bool
{
    if (!preg_match('/^\d{1,2}-\d{4}$/', $p)) return false;
    [$m, $a] = array_map('intval', explode('-', $p));
    return $m >= 1 && $m <= 12 && $a >= 2000 && $a <= 2100;
}

function periodoMayor(string $a, string $b): bool
{
    [$mA, $aA] = array_map('intval', explode('-', $a));
    [$mB, $aB] = array_map('intval', explode('-', $b));
    return $aA > $aB || ($aA === $aB && $mA > $mB);
}

function validarRangosPeriodo(string $desde, string $hasta): ?string
{
    if (!validarPeriodo($desde) || !validarPeriodo($hasta)) {
        return 'Formato de período inválido. Use M-AAAA (ej: 1-2025).';
    }
    if (periodoMayor($desde, $hasta)) {
        return 'El período Desde no puede ser mayor que el período Hasta.';
    }
    return null;
}

switch ($action) {

    // ── CANALES ───────────────────────────────────────────────────────────────
    case 'get_canales':
        try {
            $repo = new RentabilidadRubro($conn);
            echo json_encode(['success' => true, 'canales' => $repo->getCanales()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => sqlsrv_errors()]);
        }
        break;

    // ── RUBROS ────────────────────────────────────────────────────────────────
    case 'get_rubros':
        try {
            $repo = new RentabilidadRubro($conn);
            echo json_encode(['success' => true, 'rubros' => $repo->getRubros()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ── COLORES POR RUBRO ─────────────────────────────────────────────────────
    case 'get_colores':
        $rubro = trim($_POST['rubro'] ?? '');
        if ($rubro === '') {
            echo json_encode(['success' => false, 'message' => 'Se requiere el parámetro rubro.']);
            break;
        }
        try {
            $repo = new RentabilidadRubro($conn);
            echo json_encode(['success' => true, 'colores' => $repo->getColoresPorRubro($rubro)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ── REPORTE POR RUBRO ─────────────────────────────────────────────────────
    case 'get_reporte':
        $desde  = trim($_POST['desde']  ?? '');
        $hasta  = trim($_POST['hasta']  ?? '');
        $canal  = trim($_POST['canal']  ?? '');
        $moneda = in_array(trim($_POST['moneda'] ?? ''), ['ARS', 'USD'], true)
                    ? trim($_POST['moneda']) : 'ARS';

        $err = validarRangosPeriodo($desde, $hasta);
        if ($err !== null) { echo json_encode(['success' => false, 'message' => $err]); break; }

        try {
            $repo = new RentabilidadRubro($conn);
            echo json_encode($repo->buildReporte($desde, $hasta, $canal, $moneda),
                JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => sqlsrv_errors()]);
        }
        break;

    // ── REPORTE POR ORIGEN DE PRODUCCIÓN ──────────────────────────────────────
    case 'get_reporte_origen':
        $desde  = trim($_POST['desde']  ?? '');
        $hasta  = trim($_POST['hasta']  ?? '');
        $canal  = trim($_POST['canal']  ?? '');
        $rubro  = trim($_POST['rubro']  ?? '');
        $moneda = in_array(trim($_POST['moneda'] ?? ''), ['ARS', 'USD'], true)
                    ? trim($_POST['moneda']) : 'ARS';

        $err = validarRangosPeriodo($desde, $hasta);
        if ($err !== null) { echo json_encode(['success' => false, 'message' => $err]); break; }

        try {
            $repo = new RentabilidadRubro($conn);
            echo json_encode($repo->buildReportePorOrigen($desde, $hasta, $canal, $rubro, $moneda),
                JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => sqlsrv_errors()]);
        }
        break;

    // ── REPORTE POR CATEGORÍA ─────────────────────────────────────────────────
    case 'get_reporte_categoria':
        $desde  = trim($_POST['desde']  ?? '');
        $hasta  = trim($_POST['hasta']  ?? '');
        $canal  = trim($_POST['canal']  ?? '');
        $rubro  = trim($_POST['rubro']  ?? '');
        $color  = trim($_POST['color']  ?? '');
        $moneda = in_array(trim($_POST['moneda'] ?? ''), ['ARS', 'USD'], true)
                    ? trim($_POST['moneda']) : 'ARS';

        $err = validarRangosPeriodo($desde, $hasta);
        if ($err !== null) { echo json_encode(['success' => false, 'message' => $err]); break; }

        try {
            $repo = new RentabilidadRubro($conn);
            echo json_encode($repo->buildReportePorCategoria($desde, $hasta, $canal, $rubro, $color, $moneda),
                JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => sqlsrv_errors()]);
        }
        break;

    // ── PROCESAR PERÍODO ──────────────────────────────────────────────────────
    case 'procesar_periodo':
        $fechaDesde = trim($_POST['fecha_desde'] ?? '');
        $fechaHasta = trim($_POST['fecha_hasta'] ?? '');

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
