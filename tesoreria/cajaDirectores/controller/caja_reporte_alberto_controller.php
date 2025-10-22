<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../class/Ingreso.php';
require_once __DIR__ . '/../class/Egreso.php';

try {
    $accion = $_GET['accion'] ?? '';
    
    switch ($accion) {
        case 'saldo':
            $ingreso = new Ingreso();
            $egreso = new Egreso();
            
            // Para Reporte Alberto: Gastos (ingresos con COD_COMP='GAS')
            $totalGastos = $ingreso->obtenerTotalGastos();
            // Egresos del proveedor OGROLL
            $totalEgresos = $egreso->obtenerTotalProveedor('OGROLL');
            // Saldo = Egresos - Gastos (inverso al reporte normal)
            $saldo = $totalEgresos - $totalGastos;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_gastos' => $totalGastos,
                    'total_egresos' => $totalEgresos,
                    'saldo' => $saldo
                ]
            ]);
            break;
            
        case 'movimientos':
            $ingreso = new Ingreso();
            $egreso = new Egreso();
            
            $filtros = [];
            
            if (!empty($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            }
            
            if (!empty($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            }
            
            if (empty($filtros['fecha_desde']) || empty($filtros['fecha_hasta'])) {
                throw new Exception('Fechas desde y hasta son requeridas');
            }
            
            // Obtener gastos (ingresos con COD_COMP='GAS')
            $gastos = $ingreso->obtenerGastos($filtros['fecha_desde'], $filtros['fecha_hasta']);
            // Obtener egresos del proveedor OGROLL
            $filtros['proveedor'] = 'OGROLL';
            $egresos = $egreso->obtenerTodos($filtros);
            
            // Combinar y ordenar movimientos
            $movimientos = [];
            
            // Agregar gastos (antes "ingresos")
            foreach ($gastos as $gasto) {
                $fecha = is_object($gasto['fecha']) ? $gasto['fecha']->format('Y-m-d') : $gasto['fecha'];
                
                $codComp = $gasto['COD_COMP'] ?? '';
                $nComp = $gasto['N_COMP'] ?? '';
                
                $movimientos[] = [
                    'tipo' => 'GASTO',
                    'fecha' => $fecha,
                    'cod_comp' => $codComp,
                    'n_comp' => $nComp,
                    'concepto' => $gasto['observaciones'] ?? 'Gasto',
                    'importe' => $gasto['importe'],
                    'id' => $gasto['id'],
                    'tipo_gasto' => null // Gastos no tienen tipo_gasto
                ];
            }
            
            // Agregar egresos del proveedor OGROLL
            foreach ($egresos as $egr) {
                $fecha = is_object($egr['fecha']) ? $egr['fecha']->format('Y-m-d') : $egr['fecha'];
                
                // Solo mostrar observaciones, sin concatenar con motivo
                $concepto = $egr['observaciones'] ?? '';
                
                $movimientos[] = [
                    'tipo' => 'EGRESO',
                    'fecha' => $fecha,
                    'cod_comp' => $egr['COD_COMP'],
                    'n_comp' => $egr['N_COMP'],
                    'concepto' => $concepto,
                    'importe' => $egr['importe'],
                    'id' => $egr['id'],
                    'tipo_gasto' => $egr['tipo_gasto'] ?? null,
                    'tiene_foto' => $egr['tiene_foto'] ?? 0
                ];
            }
            
            // Ordenar por fecha descendente
            usort($movimientos, function($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });
            
            echo json_encode([
                'success' => true,
                'data' => $movimientos
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
