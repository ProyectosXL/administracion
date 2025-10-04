<?php
/**
 * Controlador para Comparación de Sucursales
 * Maneja las peticiones AJAX para comparar costos entre sucursales
 */

// Headers para respuesta JSON
header('Content-Type: application/json; charset=utf-8');

// Capturar errores de PHP
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../Class/costoOcupacionService.php';
require_once __DIR__ . '/../Class/Sucursal.php';

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener acción
    $action = $_POST['action'] ?? '';
    
    if ($action === 'comparar') {
        // Validar parámetros requeridos
        $idSucursal1 = $_POST['id_sucursal_1'] ?? '';
        $idSucursal2 = $_POST['id_sucursal_2'] ?? '';
        $fechaDesde = $_POST['fecha_desde'] ?? '';
        $fechaHasta = $_POST['fecha_hasta'] ?? '';
        
        if (empty($idSucursal1) || empty($idSucursal2)) {
            throw new Exception('Debe seleccionar ambas sucursales');
        }
        
        if ($idSucursal1 === $idSucursal2) {
            throw new Exception('Debe seleccionar sucursales diferentes');
        }
        
        // Crear instancias de servicios
        $service = new CostoOcupacionService();
        $sucursalObj = new Sucursal();
        
        // Obtener datos de ambas sucursales
        $dataset1 = $service->construirDataset($idSucursal1, $fechaDesde, $fechaHasta);
        $dataset2 = $service->construirDataset($idSucursal2, $fechaDesde, $fechaHasta);
        
        // Obtener nombres de sucursales
        $infoSucursal1 = $sucursalObj->obtenerSucursalPorId($idSucursal1);
        $infoSucursal2 = $sucursalObj->obtenerSucursalPorId($idSucursal2);
        
        // Construir comparación
        $comparacion = construirComparacion($dataset1, $dataset2, $infoSucursal1, $infoSucursal2);
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'data' => $comparacion
        ]);
        
    } else {
        throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} catch (Throwable $e) {
    // Capturar errores fatales
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * Construye los datos de comparación entre dos sucursales
 */
function construirComparacion($dataset1, $dataset2, $infoSucursal1, $infoSucursal2) {
    // Obtener costo de ocupación de cada sucursal
    $costoOcupacion1 = null;
    $costoOcupacion2 = null;
    
    // Buscar la fila de % Costo de Ocupación (múltiples variantes)
    $patronesCosto = ['Costo de Ocupación', 'Costo Ocupación', '% Costo de Ocupación', '% Costo Ocupación'];
    
    foreach ($dataset1['filas'] as $fila) {
        if ($fila['is_percentage']) {
            foreach ($patronesCosto as $patron) {
                if (stripos($fila['concepto'], $patron) !== false) {
                    $costoOcupacion1 = $fila['total'];
                    break 2; // Salir de ambos loops
                }
            }
        }
    }
    
    foreach ($dataset2['filas'] as $fila) {
        if ($fila['is_percentage']) {
            foreach ($patronesCosto as $patron) {
                if (stripos($fila['concepto'], $patron) !== false) {
                    $costoOcupacion2 = $fila['total'];
                    break 2; // Salir de ambos loops
                }
            }
        }
    }
    
    // Debug: Log para verificar qué datos se están procesando
    error_log("Debug CompararSucursales - Dataset1 filas: " . count($dataset1['filas'] ?? []));
    error_log("Debug CompararSucursales - Dataset2 filas: " . count($dataset2['filas'] ?? []));
    error_log("Debug CompararSucursales - Costo Ocupación 1: " . ($costoOcupacion1 ?? 'null'));
    error_log("Debug CompararSucursales - Costo Ocupación 2: " . ($costoOcupacion2 ?? 'null'));
    
    // Si no se encontró el costo de ocupación específico, buscar cualquier fila de porcentaje como fallback
    if ($costoOcupacion1 === null) {
        foreach ($dataset1['filas'] as $fila) {
            if ($fila['is_percentage'] && isset($fila['total']) && $fila['total'] !== null) {
                $costoOcupacion1 = $fila['total'];
                error_log("Debug: Usando fallback para sucursal 1: " . $fila['concepto'] . " = " . $fila['total']);
                break;
            }
        }
    }
    
    if ($costoOcupacion2 === null) {
        foreach ($dataset2['filas'] as $fila) {
            if ($fila['is_percentage'] && isset($fila['total']) && $fila['total'] !== null) {
                $costoOcupacion2 = $fila['total'];
                error_log("Debug: Usando fallback para sucursal 2: " . $fila['concepto'] . " = " . $fila['total']);
                break;
            }
        }
    }
    
    // Calcular diferencia
    $diferencia = null;
    if ($costoOcupacion1 !== null && $costoOcupacion2 !== null) {
        $diferencia = $costoOcupacion2 - $costoOcupacion1;
    }
    
    // Construir filas de comparación (excluyendo Venta Bruta y Venta Neta)
    $filasComparacion = [];
    
    // Usar las filas del primer dataset como base
    foreach ($dataset1['filas'] as $index => $fila1) {
        // Excluir filas de ventas
        if (strpos($fila1['concepto'], 'Venta Bruta') !== false || 
            strpos($fila1['concepto'], 'Venta Neta') !== false) {
            continue;
        }
        
        $fila2 = $dataset2['filas'][$index] ?? null;
        
        if ($fila2) {
            $filasComparacion[] = [
                'concepto' => $fila1['concepto'],
                'valor_sucursal_1' => $fila1['total'],
                'valor_sucursal_2' => $fila2['total'],
                'is_subtotal' => $fila1['is_subtotal'] ?? false,
                'is_percentage' => $fila1['is_percentage'] ?? false
            ];
        }
    }
    
    return [
        'sucursal1' => [
            'id' => $infoSucursal1['ID'],
            'nombre' => $infoSucursal1['SUCURSAL']
        ],
        'sucursal2' => [
            'id' => $infoSucursal2['ID'],
            'nombre' => $infoSucursal2['SUCURSAL']
        ],
        'resumen' => [
            'nombre_sucursal_1' => $infoSucursal1['SUCURSAL'],
            'nombre_sucursal_2' => $infoSucursal2['SUCURSAL'],
            'costo_ocupacion_1' => $costoOcupacion1,
            'costo_ocupacion_2' => $costoOcupacion2,
            'diferencia' => $diferencia
        ],
        'filas' => $filasComparacion
    ];
}
?>