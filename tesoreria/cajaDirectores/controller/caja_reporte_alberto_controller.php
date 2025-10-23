<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../Class/Egreso.php';

try {
    $accion = $_GET['accion'] ?? '';
    
    switch ($accion) {
        case 'saldo':
            $egreso = new Egreso();
            
            // Para Reporte Alberto:
            // - Gastos: egresos con COD_COMP='GAS'
            // - Egresos: egresos con proveedor='OGROLL' y COD_COMP != 'GAS'
            $totalGastos = $egreso->obtenerTotalGastos();
            $totalEgresos = $egreso->obtenerTotalProveedorSinGastos('OGROLL');
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
            
            // Obtener gastos (egresos con COD_COMP='GAS')
            $gastos = $egreso->obtenerGastos($filtros);
            
            // Obtener egresos del proveedor OGROLL (por defecto ya excluye COD_COMP='GAS')
            $filtros['proveedor'] = 'OGROLL';
            $egresos = $egreso->obtenerTodos($filtros);
            
            // Combinar y ordenar movimientos
            $movimientos = [];
            
            // Agregar gastos (ahora desde egresos con COD_COMP='GAS')
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
                    'tipo_gasto' => $gasto['tipo_gasto'] ?? null,
                    'tiene_foto' => $gasto['tiene_foto'] ?? 0
                ];
            }
            
            // Agregar egresos del proveedor OGROLL (sin gastos)
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
