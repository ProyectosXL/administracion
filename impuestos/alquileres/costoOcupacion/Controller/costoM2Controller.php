<?php
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
    $sucursalService = new Sucursal();
    $sucursales = $sucursalService->traerLocales(true); // true para ordenar por nombre

    if (empty($sucursales)) {
        throw new Exception('No se encontraron sucursales');
    }

    // Filtrar sucursales por entorno
    $sucursalesFiltradas = array();
    foreach ($sucursales as $sucursal) {
        $nroSucursal = intval($sucursal['NRO_SUCURSAL']);
        
        // Lógica invertida: si es uy, >= 900, sino < 900
        if ($entorno === 'uy') {
            if ($nroSucursal >= 900) {
                $sucursalesFiltradas[] = $sucursal;
            }
        } else {
            if ($nroSucursal < 900) {
                $sucursalesFiltradas[] = $sucursal;
            }
        }
    }

    if (empty($sucursalesFiltradas)) {
        throw new Exception('No se encontraron sucursales para el entorno seleccionado');
    }

    // Inicializar servicios
    $costoService = new CostoOcupacionService();
    $superficieService = new SuperficieService();

    // Obtener números de sucursales
    $nrosSucursales = array_map(function($s) { return intval($s['NRO_SUCURSAL']); }, $sucursalesFiltradas);

    // Obtener superficies de todas las sucursales
    $superficies = $superficieService->obtenerSuperficies($nrosSucursales);

    // Procesar datos por sucursal
    $resultado = array();
    $totalExpensas = 0;
    $totalAlquiler = 0;
    $totalSuperficie = 0;
    $countConSuperficie = 0;

    foreach ($sucursalesFiltradas as $sucursal) {
        $nroSucursal = intval($sucursal['NRO_SUCURSAL']);
        $nombreSucursal = $sucursal['DESC_SUCURSAL']; // Campo correcto de traerLocales()

        // Obtener superficie
        $superficie = isset($superficies[$nroSucursal]) ? $superficies[$nroSucursal] : 0;

        if ($superficie <= 0) {
            // Sucursal sin superficie registrada, la incluimos pero con valores en 0
            $resultado[] = array(
                'nro_sucursal' => $nroSucursal,
                'nombre' => $nombreSucursal,
                'superficie' => 0,
                'expensas' => 0,
                'alquiler' => 0,
                'expensas_m2' => 0,
                'alquiler_m2' => 0,
                'sin_superficie' => true
            );
            continue;
        }

        // Obtener dataset para esta sucursal individual
        $dataset = $costoService->construirDataset($nroSucursal, $fechaDesde, $fechaHasta);
        
        // Obtener datos del dataset
        $expensas = 0;
        $alquiler = 0;

        if (isset($dataset['filas']) && is_array($dataset['filas'])) {
            foreach ($dataset['filas'] as $fila) {
                // Buscar Expensas por nombre del grupo
                if (isset($fila['concepto']) && $fila['concepto'] === 'Expensas' && isset($fila['total'])) {
                    $expensas = floatval($fila['total']);
                }
                
                // Subtotal gastos de alquiler incluye TODO (incluidas las Expensas)
                // Es la fila con is_subtotal = true
                if (isset($fila['is_subtotal']) && $fila['is_subtotal'] === true && isset($fila['total'])) {
                    $alquiler = floatval($fila['total']);
                }
            }
        }

        // Calcular costos por M²
        $expensasM2 = $superficie > 0 ? $expensas / $superficie : 0;
        $alquilerM2 = $superficie > 0 ? $alquiler / $superficie : 0;

        $resultado[] = array(
            'nro_sucursal' => $nroSucursal,
            'nombre' => $nombreSucursal,
            'superficie' => $superficie,
            'expensas' => $expensas,
            'alquiler' => $alquiler,
            'expensas_m2' => $expensasM2,
            'alquiler_m2' => $alquilerM2,
            'sin_superficie' => false
        );

        // Acumular totales solo de sucursales con superficie
        $totalExpensas += $expensas;
        $totalAlquiler += $alquiler;
        $totalSuperficie += $superficie;
        $countConSuperficie++;
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
