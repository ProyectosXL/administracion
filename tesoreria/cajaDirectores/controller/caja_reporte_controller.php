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
            
            $totalIngresos = $ingreso->obtenerTotalRecibido();
            $totalEgresos = $egreso->obtenerTotal();
            $saldo = $totalIngresos - $totalEgresos;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_ingresos' => $totalIngresos,
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
            
            // Obtener ingresos combinados de todas las fuentes
            $ingresos = $ingreso->obtenerIngresosCombinados($filtros['fecha_desde'], $filtros['fecha_hasta']);
            $egresos = $egreso->obtenerTodos($filtros);
            
            // Combinar y ordenar movimientos
            $movimientos = [];
            
            foreach ($ingresos as $ing) {
                // Convertir fechas DateTime a string si es necesario
                $fecha = is_object($ing['fecha']) ? $ing['fecha']->format('Y-m-d') : $ing['fecha'];
                
                // Para TESORERÍA, mostrar los campos COMP correctamente
                $codComp = $ing['COD_COMP'] ?? '';
                $nComp = $ing['N_COMP'] ?? '';
                
                $movimientos[] = [
                    'tipo' => 'INGRESO',
                    'fecha' => $fecha,
                    'cod_comp' => $codComp,
                    'n_comp' => $nComp,
                    'concepto' => $ing['observaciones'] ?? 'Ingreso de caja',
                    'importe' => $ing['importe'],
                    'recibido' => $ing['recibido'],
                    'id' => $ing['id'],
                    'origen' => $ing['origen'] ?? 'MANUAL',
                    'ID_SBA05' => $ing['ID_SBA05'] ?? null, // Necesario para TESORERÍA
                    'tiene_foto' => 0 // Los ingresos no tienen foto
                ];
            }
            
            foreach ($egresos as $egr) {
                // Convertir fechas DateTime a string si es necesario
                $fecha = is_object($egr['fecha']) ? $egr['fecha']->format('Y-m-d') : $egr['fecha'];
                
                $concepto = $egr['motivo'];
                if ($egr['motivo'] === 'RETIROS' && !empty($egr['nombre_director'])) {
                    $concepto .= ' - ' . $egr['nombre_director'];
                }
                if (!empty($egr['observaciones'])) {
                    $concepto .= ' (' . $egr['observaciones'] . ')';
                }
                
                $movimientos[] = [
                    'tipo' => 'EGRESO',
                    'fecha' => $fecha,
                    'cod_comp' => $egr['COD_COMP'],
                    'n_comp' => $egr['N_COMP'],
                    'concepto' => $concepto,
                    'importe' => $egr['importe'],
                    'recibido' => $egr['recibido'],
                    'id' => $egr['id'],
                    'origen' => 'MANUAL', // Los egresos siempre son manuales
                    'tiene_foto' => $egr['tiene_foto'] ?? 0 // Incluir info de foto
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