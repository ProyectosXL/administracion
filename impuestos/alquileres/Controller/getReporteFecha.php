<?php
/**
 * getReporteFecha.php
 * Controlador para obtener datos del reporte a fecha
 * Transpone la vista: sucursales en columnas, conceptos en filas
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once "../Class/Sucursal.php";
    require_once "../Class/costoOcupacionService.php";
    
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
    
    $service = new CostoOcupacionService();
    $sucursalObj = new Sucursal();
    
    // Obtener todas las sucursales activas
    $todasLasSucursales = $sucursalObj->traerLocales(true);
    
    // Filtrar sucursales según el entorno (Argentina o Uruguay)
    $entorno = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $sucursalesFiltradas = [];
    
    foreach ($todasLasSucursales as $sucursal) {
        // Lógica de filtrado según entorno
        if ($entorno === 'central') {
            // Argentina: todas las sucursales normales
            if (isset($sucursal['NRO_SUCURSAL']) && $sucursal['NRO_SUCURSAL'] < 900) {
                $sucursalesFiltradas[] = [
                    'id' => $sucursal['ID'],
                    'numero' => $sucursal['NRO_SUCURSAL'],
                    'nombre' => isset($sucursal['DESC_SUCURSAL']) ? $sucursal['DESC_SUCURSAL'] : 'Sucursal ' . $sucursal['NRO_SUCURSAL']
                ];
            }
        } else {
            // Uruguay: solo sucursales de Uruguay
            if (isset($sucursal['NRO_SUCURSAL']) && $sucursal['NRO_SUCURSAL'] >= 900) {
                $sucursalesFiltradas[] = [
                    'id' => $sucursal['ID'],
                    'numero' => $sucursal['NRO_SUCURSAL'],
                    'nombre' => isset($sucursal['DESC_SUCURSAL']) ? $sucursal['DESC_SUCURSAL'] : 'Sucursal ' . $sucursal['NRO_SUCURSAL']
                ];
            }
        }
    }
    
    // Array para almacenar datos por sucursal
    $datosPorSucursal = [];
    
    // Obtener dataset de cada sucursal
    foreach ($sucursalesFiltradas as $sucursal) {
        $dataset = $service->construirDataset($sucursal['id'], $fechaDesde, $fechaHasta);
        $datosPorSucursal[$sucursal['id']] = $dataset;
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
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'entorno' => $entorno
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    
    // Log del error para debugging
    error_log("Error en getReporteFecha: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
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
