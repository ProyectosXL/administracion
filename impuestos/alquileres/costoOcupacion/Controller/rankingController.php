<?php
/**
 * rankingController.php
 * Controlador para obtener datos del ranking YoY de % Costo de Ocupación
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once "../../Class/Sucursal.php";
    require_once "../Class/costoOcupacionService.php";
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Validar parámetros
    if (!isset($_POST['fechaDesde']) || !isset($_POST['fechaHasta'])) {
        throw new Exception('Faltan parámetros obligatorios');
    }
    
    $fechaDesdeActual = $_POST['fechaDesde'];
    $fechaHastaActual = $_POST['fechaHasta'];
    
    // Validar formato de fechas
    $dateDesdeActual = DateTime::createFromFormat('Y-m-d', $fechaDesdeActual);
    $dateHastaActual = DateTime::createFromFormat('Y-m-d', $fechaHastaActual);
    
    if (!$dateDesdeActual || !$dateHastaActual) {
        throw new Exception('Formato de fecha inválido');
    }
    
    // Calcular período anterior (YoY - mismo rango pero 12 meses antes)
    $dateDesdeAnterior = clone $dateDesdeActual;
    $dateDesdeAnterior->modify('-12 months');
    $fechaDesdeAnterior = $dateDesdeAnterior->format('Y-m-d');
    
    $dateHastaAnterior = clone $dateHastaActual;
    $dateHastaAnterior->modify('-12 months');
    $fechaHastaAnterior = $dateHastaAnterior->format('Y-m-d');
    
    $service = new CostoOcupacionService();
    $sucursalObj = new Sucursal();
    
    // Obtener todas las sucursales activas
    $todasLasSucursales = $sucursalObj->traerLocales(true);
    
    // Filtrar sucursales según el entorno
    $entorno = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $sucursales = [];
    
    foreach ($todasLasSucursales as $sucursal) {
        if ($entorno === 'uy') {
            // Uruguay: sucursales >= 900
            if (isset($sucursal['NRO_SUCURSAL']) && $sucursal['NRO_SUCURSAL'] >= 900) {
                $sucursales[] = $sucursal;
            }
        } else {
            // Argentina: sucursales < 900 (incluye 'central', 'sistemas', etc.)
            if (isset($sucursal['NRO_SUCURSAL']) && $sucursal['NRO_SUCURSAL'] < 900) {
                $sucursales[] = $sucursal;
            }
        }
    }
    
    // Calcular % Costo de Ocupación para cada sucursal en ambos períodos
    $ranking = [];
    
    foreach ($sucursales as $sucursal) {
        $idSucursal = $sucursal['ID'];
        $numeroSucursal = $sucursal['NRO_SUCURSAL'];
        $nombreSucursal = $sucursal['DESC_SUCURSAL'] ?? 'Sucursal ' . $numeroSucursal;
        
        // Período actual
        $datasetActual = $service->construirDatasetSimplificado(
            $idSucursal, 
            $fechaDesdeActual, 
            $fechaHastaActual
        );
        
        // Período anterior (YoY)
        $datasetAnterior = $service->construirDatasetSimplificado(
            $idSucursal, 
            $fechaDesdeAnterior, 
            $fechaHastaAnterior
        );
        
        // Calcular % Costo de Ocupación Actual
        $porcentajeActual = null;
        if (isset($datasetActual['porcentaje_costo_ocupacion']) && 
            $datasetActual['porcentaje_costo_ocupacion'] !== null) {
            $porcentajeActual = floatval($datasetActual['porcentaje_costo_ocupacion']);
        }
        
        // Calcular % Costo de Ocupación Anterior
        $porcentajeAnterior = null;
        $tieneDatosAnteriores = false;
        if (isset($datasetAnterior['porcentaje_costo_ocupacion']) && 
            $datasetAnterior['porcentaje_costo_ocupacion'] !== null) {
            $porcentajeAnterior = floatval($datasetAnterior['porcentaje_costo_ocupacion']);
            $tieneDatosAnteriores = true;
        }
        
        // Calcular diferencias
        $diferenciaPP = null; // Puntos porcentuales
        $diferenciaRelativa = null; // % de cambio
        
        if ($porcentajeActual !== null && $porcentajeAnterior !== null && $porcentajeAnterior != 0) {
            $diferenciaPP = $porcentajeActual - $porcentajeAnterior;
            $diferenciaRelativa = (($porcentajeActual - $porcentajeAnterior) / $porcentajeAnterior) * 100;
        }
        
        // Solo agregar al ranking si tiene porcentaje actual válido
        if ($porcentajeActual !== null) {
            $ranking[] = [
                'id' => $idSucursal,
                'numero' => $numeroSucursal,
                'nombre' => $nombreSucursal,
                'porcentaje_actual' => $porcentajeActual,
                'porcentaje_anterior' => $porcentajeAnterior,
                'tiene_datos_anteriores' => $tieneDatosAnteriores,
                'diferencia_pp' => $diferenciaPP,
                'diferencia_relativa' => $diferenciaRelativa
            ];
        }
    }
    
    // Ordenar por porcentaje actual de mayor a menor (peores primero)
    usort($ranking, function($a, $b) {
        $diff = $b['porcentaje_actual'] - $a['porcentaje_actual'];
        if (abs($diff) < 0.01) {
            // En caso de empate, ordenar alfabéticamente
            return strcmp($a['nombre'], $b['nombre']);
        }
        return $diff > 0 ? 1 : -1;
    });
    
    // Verificar si hay sucursales sin datos anteriores
    $haySinDatosAnteriores = false;
    foreach ($ranking as $item) {
        if (!$item['tiene_datos_anteriores']) {
            $haySinDatosAnteriores = true;
            break;
        }
    }
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'ranking' => $ranking,
            'periodo_actual' => [
                'desde' => $fechaDesdeActual,
                'hasta' => $fechaHastaActual
            ],
            'periodo_anterior' => [
                'desde' => $fechaDesdeAnterior,
                'hasta' => $fechaHastaAnterior
            ],
            'tiene_datos_incompletos' => $haySinDatosAnteriores,
            'total_sucursales' => count($ranking)
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    
    error_log("Error en rankingController: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    http_response_code(500);
    
    error_log("Fatal error en rankingController: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error fatal: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
