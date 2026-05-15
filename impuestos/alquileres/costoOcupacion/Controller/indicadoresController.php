<?php
/**
 * indicadoresController.php
 * Controlador AJAX para la pestaña "Indicadores – Costos de Ocupación".
 * Devuelve JSON con todos los datos necesarios para el frontend.
 *
 * Acciones:
 *   GET/POST ?accion=fetch  → datos completos (KPIs, ranking, semáforo)
 *   GET/POST ?accion=detalle → datos de una sucursal específica
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'fetch':
        fetchIndicadores();
        break;

    case 'detalle':
        fetchDetalleSucursal();
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida',
        ]);
        break;
}

// ─────────────────────────────────────────────────────────────
// Fetch: KPIs cadena + ranking + semáforo de toda la red
// ─────────────────────────────────────────────────────────────
function fetchIndicadores(): void
{
    try {
        require_once __DIR__ . '/../../Class/Sucursal.php';
        require_once __DIR__ . '/../Class/IndicadoresService.php';
        require_once __DIR__ . '/../Class/costoOcupacionService.php';

        // Validar fechas
        $fechaDesde = $_POST['fecha_desde'] ?? '';
        $fechaHasta = $_POST['fecha_hasta'] ?? '';

        if (empty($fechaDesde) || empty($fechaHasta)) {
            // Calcular rango default (últimos 12 meses completos)
            $tmp   = new CostoOcupacionService();
            $rango = $tmp->calcularRangoDefault();
            $fechaDesde = $rango['desde'];
            $fechaHasta = $rango['hasta'];
        }

        $dDesde = DateTime::createFromFormat('Y-m-d', $fechaDesde);
        $dHasta = DateTime::createFromFormat('Y-m-d', $fechaHasta);

        if (!$dDesde || !$dHasta) {
            throw new Exception('Formato de fecha inválido. Use YYYY-MM-DD.');
        }
        if ($dDesde > $dHasta) {
            throw new Exception('La fecha desde no puede ser mayor a la fecha hasta.');
        }

        // Obtener sucursales según entorno (ARG / UY) — igual que en getReporteFecha.php
        $sucursalObj   = new Sucursal();
        $sucursales    = $sucursalObj->traerLocales(true);

        if (empty($sucursales)) {
            throw new Exception('No se encontraron sucursales para el entorno seleccionado.');
        }

        $service = new IndicadoresService();
        $data    = $service->buildIndicadores($sucursales, $fechaDesde, $fechaHasta);

        // Detectar meses sin datos (alquileres y/o ventas) — igual que costoPersonal
        $costoSvc  = new CostoOcupacionService();
        $deteccion = $costoSvc->detectarMesesSinDatos($fechaDesde, $fechaHasta);
        $data['meses_sin_datos']           = $deteccion['meses_sin_datos'];
        $data['meses_con_datos_completos'] = count($deteccion['meses_ok']);
        $data['meses_totales_periodo']     = $deteccion['total_meses'];

        echo json_encode([
            'success' => true,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        error_log('indicadoresController::fetchIndicadores — ' . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }
}

// ─────────────────────────────────────────────────────────────
// Detalle: datos completos de una sucursal (para breakeven y KPI lateral)
// ─────────────────────────────────────────────────────────────
function fetchDetalleSucursal(): void
{
    try {
        require_once __DIR__ . '/../../Class/Sucursal.php';
        require_once __DIR__ . '/../Class/IndicadoresService.php';

        $idSucursal = $_POST['id_sucursal'] ?? '';
        $fechaDesde = $_POST['fecha_desde'] ?? '';
        $fechaHasta = $_POST['fecha_hasta'] ?? '';

        if (empty($idSucursal)) {
            throw new Exception('Debe indicar id_sucursal.');
        }
        if (empty($fechaDesde) || empty($fechaHasta)) {
            throw new Exception('Debe indicar fecha_desde y fecha_hasta.');
        }

        $sucursalObj = new Sucursal();
        $infoSuc     = $sucursalObj->obtenerSucursalPorId($idSucursal);

        if (!$infoSuc) {
            throw new Exception('Sucursal no encontrada.');
        }

        // Normalizar claves igual que traerLocales()
        $suc = [
            'ID'           => $infoSuc['ID'],
            'NRO_SUCURSAL' => $infoSuc['NRO_SUCURSAL'] ?? ($infoSuc['ID'] ?? 0),
            'DESC_SUCURSAL'=> $infoSuc['SUCURSAL']     ?? ($infoSuc['DESC_SUCURSAL'] ?? ''),
        ];

        $service = new IndicadoresService();
        $detalle = $service->buildDatosSucursal($suc, $fechaDesde, $fechaHasta);

        echo json_encode([
            'success' => true,
            'data'    => $detalle,
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        error_log('indicadoresController::fetchDetalleSucursal — ' . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }
}
