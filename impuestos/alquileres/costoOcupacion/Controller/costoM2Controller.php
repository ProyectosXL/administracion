<?php
// Limpiar cualquier caché de código PHP (OPcache)
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Enable error reporting for debugging
ini_set('display_errors', 0); // Don't display errors in output
error_reporting(E_ALL);

session_start();

// Headers agresivos para prevenir TODO tipo de caché
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Fecha en el pasado
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// Log de inicio para verificar que se ejecuta la versión correcta
error_log("========== costoM2Controller INICIADO - VERSION DETALLADA ==========");

try {
    // Check if files exist before requiring
    $costoServicePath = __DIR__ . '/../Class/costoOcupacionService.php';
    $superficieServicePath = __DIR__ . '/../Class/superficieService.php';
    $sucursalPath = __DIR__ . '/../../Class/Sucursal.php';
    
    if (!file_exists($costoServicePath)) {
        throw new Exception("No se encontró costoOcupacionService.php en: $costoServicePath");
    }
    if (!file_exists($superficieServicePath)) {
        throw new Exception("No se encontró superficieService.php en: $superficieServicePath");
    }
    if (!file_exists($sucursalPath)) {
        throw new Exception("No se encontró Sucursal.php en: $sucursalPath");
    }
    
    require_once $costoServicePath;
    require_once $superficieServicePath;
    require_once $sucursalPath;

    // Validar parámetros
    if (!isset($_POST['fecha_desde']) || !isset($_POST['fecha_hasta'])) {
        throw new Exception('Fechas no proporcionadas');
    }

    $fechaDesde = $_POST['fecha_desde'];
    $fechaHasta = $_POST['fecha_hasta'];

    // Validar formato de fechas
    $dateDesde = DateTime::createFromFormat('Y-m-d', $fechaDesde);
    $dateHasta = DateTime::createFromFormat('Y-m-d', $fechaHasta);
    
    if (!$dateDesde || !$dateHasta) {
        throw new Exception('Formato de fecha inválido');
    }

    if ($dateDesde > $dateHasta) {
        throw new Exception('La fecha desde no puede ser mayor a la fecha hasta');
    }

    // Obtener entorno
    $entorno = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';

    // Obtener sucursales
    // La clase Sucursal ya filtra según el entorno (Uruguay o Argentina)
    $sucursalService = new Sucursal();
    $sucursalesFiltradas = $sucursalService->traerLocales(true); // true para ordenar por nombre

    if (empty($sucursalesFiltradas)) {
        throw new Exception('No se encontraron sucursales');
    }
    
    error_log("costoM2Controller - Entorno: {$entorno}, Sucursales obtenidas: " . count($sucursalesFiltradas));
    
    // Log de las sucursales antes de procesarlas
    foreach ($sucursalesFiltradas as $idx => $suc) {
        error_log("costoM2Controller - Sucursal #{$idx}: ID={$suc['ID']}, NRO={$suc['NRO_SUCURSAL']}, NOMBRE={$suc['DESC_SUCURSAL']}");
    }

    // Inicializar servicios
    $costoService = new CostoOcupacionService();
    $superficieService = new SuperficieService();

    // Obtener números de sucursales
    $nrosSucursales = array_map(function($s) { return intval($s['NRO_SUCURSAL']); }, $sucursalesFiltradas);
    error_log("costoM2Controller - Números de sucursales para buscar superficies: " . implode(", ", $nrosSucursales));

    // Obtener superficies de todas las sucursales
    $superficies = $superficieService->obtenerSuperficies($nrosSucursales);
    error_log("costoM2Controller - Superficies obtenidas: " . count($superficies));
    foreach ($superficies as $nro => $sup) {
        error_log("costoM2Controller - Superficie[{$nro}] = {$sup}");
    }

    // Procesar datos por sucursal
    $resultado = array();
    $totalExpensas = 0;
    $totalAlquiler = 0;
    $totalSuperficie = 0;
    $countConSuperficie = 0;
    
    error_log("costoM2Controller - INICIANDO PROCESAMIENTO DE SUCURSALES con fechas: {$fechaDesde} a {$fechaHasta}");

    foreach ($sucursalesFiltradas as $sucursal) {
        $idSucursal = $sucursal['ID']; // ID de la sucursal (lo que espera construirDataset)
        $nroSucursal = intval($sucursal['NRO_SUCURSAL']);
        $nombreSucursal = $sucursal['DESC_SUCURSAL']; // Campo correcto de traerLocales()

        // Obtener superficie
        $superficie = isset($superficies[$nroSucursal]) ? $superficies[$nroSucursal] : 0;
        $sinSuperficie = ($superficie <= 0);
        
        error_log("costoM2Controller - Procesando sucursal {$nroSucursal}: Superficie={$superficie}, SinSuperficie=" . ($sinSuperficie ? "SI" : "NO"));

        // Obtener dataset para esta sucursal individual
        // IMPORTANTE: construirDataset espera el ID, no el número de sucursal
        $dataset = $costoService->construirDataset($idSucursal, $fechaDesde, $fechaHasta);
        
        $numFilas = isset($dataset['filas']) ? count($dataset['filas']) : 0;
        error_log("costoM2Controller - Sucursal {$nroSucursal} (ID: {$idSucursal}): {$numFilas} filas obtenidas");
        
        // Log detallado del dataset para debugging
        if ($numFilas > 0) {
            error_log("costoM2Controller - Conceptos disponibles para sucursal {$nroSucursal}:");
            foreach ($dataset['filas'] as $fila) {
                $concepto = isset($fila['concepto']) ? $fila['concepto'] : 'N/A';
                $total = isset($fila['total']) ? $fila['total'] : 0;
                $isSubtotal = isset($fila['is_subtotal']) ? 'SI' : 'NO';
                error_log("  - {$concepto}: Total={$total}, IsSubtotal={$isSubtotal}");
            }
        } else {
            error_log("costoM2Controller - ADVERTENCIA: No se obtuvieron filas para sucursal {$nroSucursal} (ID: {$idSucursal})");
            error_log("costoM2Controller - Período: {$fechaDesde} a {$fechaHasta}");
        }
        
        // Obtener datos del dataset
        $expensas = 0;
        $alquiler = 0;

        if (isset($dataset['filas']) && is_array($dataset['filas'])) {
            foreach ($dataset['filas'] as $fila) {
                // Buscar Expensas por nombre del grupo
                if (isset($fila['concepto']) && $fila['concepto'] === 'Expensas' && isset($fila['total'])) {
                    $expensas = floatval($fila['total']);
                    error_log("costoM2Controller - Sucursal {$nroSucursal}: Expensas encontradas = {$expensas}");
                }
                
                // Subtotal gastos de alquiler incluye TODO (incluidas las Expensas)
                // Es la fila con is_subtotal = true
                if (isset($fila['is_subtotal']) && $fila['is_subtotal'] === true && isset($fila['total'])) {
                    $alquiler = floatval($fila['total']);
                    error_log("costoM2Controller - Sucursal {$nroSucursal}: Alquiler (subtotal) encontrado = {$alquiler}");
                }
            }
        }
        
        error_log("costoM2Controller - Sucursal {$nroSucursal}: Expensas={$expensas}, Alquiler={$alquiler}, Superficie={$superficie}");

        // Calcular costos por M²
        $expensasM2 = ($superficie > 0 && $expensas > 0) ? $expensas / $superficie : 0;
        $alquilerM2 = ($superficie > 0 && $alquiler > 0) ? $alquiler / $superficie : 0;

        $resultado[] = array(
            'nro_sucursal' => $nroSucursal,
            'nombre' => $nombreSucursal,
            'superficie' => $superficie,
            'expensas' => $expensas,
            'alquiler' => $alquiler,
            'expensas_m2' => $expensasM2,
            'alquiler_m2' => $alquilerM2,
            'sin_superficie' => $sinSuperficie
        );

        // Acumular totales solo de sucursales con superficie
        if (!$sinSuperficie) {
            $totalExpensas += $expensas;
            $totalAlquiler += $alquiler;
            $totalSuperficie += $superficie;
            $countConSuperficie++;
        }
    }

    // Calcular promedios
    $promedioExpensasM2 = $totalSuperficie > 0 ? $totalExpensas / $totalSuperficie : 0;
    $promedioAlquilerM2 = $totalSuperficie > 0 ? $totalAlquiler / $totalSuperficie : 0;

    // Ordenar por nombre de sucursal
    usort($resultado, function($a, $b) {
        return strcmp($a['nombre'], $b['nombre']);
    });

    echo json_encode(array(
        'success' => true,
        'data' => $resultado,
        'promedios' => array(
            'expensas_m2' => $promedioExpensasM2,
            'alquiler_m2' => $promedioAlquilerM2,
            'total_expensas' => $totalExpensas,
            'total_alquiler' => $totalAlquiler,
            'total_superficie' => $totalSuperficie,
            'count_con_superficie' => $countConSuperficie
        ),
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta
    ), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ), JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Error PHP: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ), JSON_UNESCAPED_UNICODE);
}
