<?php
/**
 * getReporteFecha.php
 * Controlador para obtener datos del reporte a fecha
 * Transpone la vista: sucursales en columnas, conceptos en filas
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once "../../Class/Sucursal.php";
    require_once __DIR__ . '/../Class/costoOcupacionService.php';
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Validar parámetros
    if (!isset($_POST['fechaDesde']) || !isset($_POST['fechaHasta'])) {
        throw new Exception('Faltan parámetros obligatorios');
    }
    
    $fechaDesde = $_POST['fechaDesde'];
    $fechaHasta = $_POST['fechaHasta'];
    
    // Validar formato de fechas
    $dateDesde = DateTime::createFromFormat('Y-m-d', $fechaDesde);
    $dateHasta = DateTime::createFromFormat('Y-m-d', $fechaHasta);
    
    if (!$dateDesde || !$dateHasta) {
        throw new Exception('Formato de fecha inválido');
    }
    
    // Validar que fechaDesde sea menor o igual a fechaHasta
    if (strtotime($fechaDesde) > strtotime($fechaHasta)) {
        throw new Exception('La fecha desde no puede ser mayor a la fecha hasta');
    }
    
    // Calcular período anterior (YoY - mismo rango pero 12 meses antes)
    $dateDesdeAnterior = clone $dateDesde;
    $dateDesdeAnterior->modify('-12 months');
    $fechaDesdeAnterior = $dateDesdeAnterior->format('Y-m-d');
    
    $dateHastaAnterior = clone $dateHasta;
    $dateHastaAnterior->modify('-12 months');
    $fechaHastaAnterior = $dateHastaAnterior->format('Y-m-d');
    
    $service = new CostoOcupacionService();
    $sucursalObj = new Sucursal();
    
    // Obtener todas las sucursales activas
    $todasLasSucursales = $sucursalObj->traerLocales(true);
    
    // Debug: Log de sucursales obtenidas
    error_log("Total sucursales obtenidas: " . count($todasLasSucursales));
    if (count($todasLasSucursales) > 0) {
        error_log("Primera sucursal: " . print_r($todasLasSucursales[0], true));
    }
    
    // Filtrar sucursales según el entorno (Argentina o Uruguay)
    $entorno = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    error_log("Entorno actual: " . $entorno);
    $sucursalesFiltradas = [];
    
    foreach ($todasLasSucursales as $sucursal) {
        // Lógica de filtrado según entorno
        if ($entorno === 'uy') {
            // Uruguay: solo sucursales de Uruguay (>= 900)
            if (isset($sucursal['NRO_SUCURSAL']) && $sucursal['NRO_SUCURSAL'] >= 900) {
                $sucursalesFiltradas[] = [
                    'id' => $sucursal['ID'],
                    'numero' => $sucursal['NRO_SUCURSAL'],
                    'nombre' => isset($sucursal['DESC_SUCURSAL']) ? $sucursal['DESC_SUCURSAL'] : 'Sucursal ' . $sucursal['NRO_SUCURSAL']
                ];
            }
        } else {
            // Argentina: todas las sucursales normales (< 900)
            // Esto incluye 'central', 'sistemas' y cualquier otro entorno que no sea Uruguay
            if (isset($sucursal['NRO_SUCURSAL']) && $sucursal['NRO_SUCURSAL'] < 900) {
                $sucursalesFiltradas[] = [
                    'id' => $sucursal['ID'],
                    'numero' => $sucursal['NRO_SUCURSAL'],
                    'nombre' => isset($sucursal['DESC_SUCURSAL']) ? $sucursal['DESC_SUCURSAL'] : 'Sucursal ' . $sucursal['NRO_SUCURSAL']
                ];
            }
        }
    }
    
    // Debug: Log de sucursales filtradas
    error_log("Sucursales filtradas: " . count($sucursalesFiltradas));
    
    // Array para almacenar datos por sucursal (período actual)
    $datosPorSucursal = [];
    
    // Obtener dataset de cada sucursal (período actual)
    foreach ($sucursalesFiltradas as $sucursal) {
        $dataset = $service->construirDataset($sucursal['id'], $fechaDesde, $fechaHasta);
        $datosPorSucursal[$sucursal['id']] = $dataset;
    }
    
    // Obtener % Costo de Ocupación del período anterior (YoY) para cada sucursal
    $porcentajesAnteriores = [];
    foreach ($sucursalesFiltradas as $sucursal) {
        $datasetAnterior = $service->construirDatasetSimplificado(
            $sucursal['id'], 
            $fechaDesdeAnterior, 
            $fechaHastaAnterior
        );
        
        if (isset($datasetAnterior['porcentaje_costo_ocupacion']) && 
            $datasetAnterior['porcentaje_costo_ocupacion'] !== null) {
            $porcentajesAnteriores[$sucursal['id']] = floatval($datasetAnterior['porcentaje_costo_ocupacion']);
        } else {
            $porcentajesAnteriores[$sucursal['id']] = null;
        }
    }
    
    // Construir estructura transpuesta: conceptos en filas, sucursales en columnas
    $conceptos = [];
    
    // Usar la primera sucursal como referencia para obtener los conceptos
    if (count($datosPorSucursal) > 0) {
        $primerDataset = reset($datosPorSucursal);
        
        foreach ($primerDataset['filas'] as $fila) {
            $conceptoNombre = $fila['concepto'];
            
            // Crear estructura del concepto
            $concepto = [
                'nombre' => $conceptoNombre,
                'valores' => [],
                'is_subtotal' => isset($fila['is_subtotal']) && $fila['is_subtotal'],
                'is_metric' => isset($fila['is_metric']) && $fila['is_metric'],
                'is_percentage' => isset($fila['is_percentage']) && $fila['is_percentage']
            ];
            
            // Obtener valores de cada sucursal para este concepto
            foreach ($sucursalesFiltradas as $sucursal) {
                if (isset($datosPorSucursal[$sucursal['id']])) {
                    $dataset = $datosPorSucursal[$sucursal['id']];
                    
                    // Buscar este concepto en el dataset de la sucursal
                    $valorTotal = 0;
                    foreach ($dataset['filas'] as $filaData) {
                        if ($filaData['concepto'] === $conceptoNombre) {
                            $valorTotal = $filaData['total'];
                            break;
                        }
                    }
                    
                    $concepto['valores'][$sucursal['id']] = $valorTotal;
                } else {
                    $concepto['valores'][$sucursal['id']] = 0;
                }
            }
            
            $conceptos[] = $concepto;
        }
    }
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'sucursales' => $sucursalesFiltradas,
            'conceptos' => $conceptos,
            'porcentajesAnteriores' => $porcentajesAnteriores,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'fechaDesdeAnterior' => $fechaDesdeAnterior,
            'fechaHastaAnterior' => $fechaHastaAnterior,
            'entorno' => $entorno
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    
    // Log del error para debugging
    error_log("Error en getReporteFecha: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("Línea: " . $e->getLine());
    error_log("Archivo: " . $e->getFile());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    http_response_code(500);
    
    error_log("Fatal error en getReporteFecha: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error fatal: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
