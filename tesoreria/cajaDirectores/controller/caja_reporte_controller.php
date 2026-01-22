<?php
// Aumentar tiempo de ejecución para consultas pesadas
set_time_limit(180); // 3 minutos
ini_set('max_execution_time', '180');

header('Content-Type: application/json');
require_once __DIR__ . '/../class/Ingreso.php';
require_once __DIR__ . '/../class/Egreso.php';
require_once __DIR__ . '/../class/Config.php';
require_once __DIR__ . '/../../../class/conexion.php';

try {
    $accion = $_GET['accion'] ?? '';
    
    switch ($accion) {
        case 'fecha_inicio_app':
            // Devolver la fecha de inicio de la aplicación
            echo json_encode([
                'success' => true,
                'fecha_inicio' => Config::getFechaInicioApp()
            ]);
            break;
            
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
            try {
                $ingreso = new Ingreso();
            } catch (Exception $e) {
                error_log("Error creando instancia de Ingreso: " . $e->getMessage());
                throw new Exception("Error al inicializar módulo de ingresos: " . $e->getMessage());
            }
            
            try {
                $egreso = new Egreso();
            } catch (Exception $e) {
                error_log("Error creando instancia de Egreso: " . $e->getMessage());
                throw new Exception("Error al inicializar módulo de egresos: " . $e->getMessage());
            }
            
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
            
            // Aplicar filtro de fecha de inicio de la app
            $fechaInicioApp = Config::getFechaInicioApp();
            if (strtotime($filtros['fecha_desde']) < strtotime($fechaInicioApp)) {
                $filtros['fecha_desde'] = $fechaInicioApp;
            }
            
            // Obtener ingresos combinados de todas las fuentes
            try {
                $ingresos = $ingreso->obtenerIngresosCombinados($filtros['fecha_desde'], $filtros['fecha_hasta']);
            } catch (Exception $e) {
                error_log("Error obteniendo ingresos combinados: " . $e->getMessage());
                throw new Exception("Error al obtener ingresos: " . $e->getMessage());
            }
            
            try {
                $egresos = $egreso->obtenerTodos($filtros);
            } catch (Exception $e) {
                error_log("Error obteniendo egresos: " . $e->getMessage());
                throw new Exception("Error al obtener egresos: " . $e->getMessage());
            }
            
            // Reutilizar la conexión existente de Ingreso para evitar crear más conexiones
            $conexion = $ingreso->getConexion();
            $dbCentral = $conexion->conectar('central');
            
            if ($dbCentral === false) {
                throw new Exception("Error al conectar con la base de datos central");
            }
            
            // Combinar y ordenar movimientos
            $movimientos = [];
            
            foreach ($ingresos as $ing) {
                // Convertir fechas DateTime a string si es necesario
                $fecha = is_object($ing['fecha']) ? $ing['fecha']->format('Y-m-d') : $ing['fecha'];
                
                // Obtener fecha_carga completa para ordenamiento
                $fechaCarga = '';
                if (isset($ing['fecha_carga'])) {
                    $fechaCarga = is_object($ing['fecha_carga']) ? $ing['fecha_carga']->format('Y-m-d H:i:s') : $ing['fecha_carga'];
                }
                
                // Para TESORERÍA, mostrar los campos COMP correctamente
                $codComp = $ing['COD_COMP'] ?? '';
                $nComp = $ing['N_COMP'] ?? '';
                
                $movimientos[] = [
                    'tipo' => 'INGRESO',
                    'fecha' => $fecha,
                    'fecha_carga' => $fechaCarga,
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
                // Para pagos de servicios (Pago de seguros, patentes, expensas, tarjetas, haberes, otros), usar fecha_carga en lugar de fecha
                $esPagoServicio = in_array($egr['motivo'], ['Pago de seguros', 'Pago de patentes', 'Pago de expensas', 'Pago de tarjetas', 'Transf. Haberes', 'Otros']);
                
                // Obtener fecha_carga completa para ordenamiento
                $fechaCarga = '';
                if (isset($egr['fecha_carga'])) {
                    $fechaCarga = is_object($egr['fecha_carga']) ? $egr['fecha_carga']->format('Y-m-d H:i:s') : $egr['fecha_carga'];
                }
                
                if ($esPagoServicio && !empty($egr['fecha_carga'])) {
                    // Usar fecha_carga casteada a formato YYYY-MM-DD
                    if (is_object($egr['fecha_carga'])) {
                        $fecha = $egr['fecha_carga']->format('Y-m-d');
                    } else {
                        // Si es string, extraer solo la parte de fecha (antes del espacio)
                        $fecha = explode(' ', $egr['fecha_carga'])[0];
                    }
                } else {
                    // Para otros egresos, usar el campo fecha normal
                    $fecha = is_object($egr['fecha']) ? $egr['fecha']->format('Y-m-d') : $egr['fecha'];
                }
                
                $concepto = $egr['motivo'];
                
                // RETIROS: motivo - director (observaciones)
                if ($egr['motivo'] === 'RETIROS' && !empty($egr['nombre_director'])) {
                    $concepto .= ' - ' . $egr['nombre_director'];
                }
                
                // COMPENSACION_IVA: Compensación IVA - director (observaciones)
                if ($egr['motivo'] === 'COMPENSACION_IVA' && !empty($egr['nombre_director'])) {
                    $concepto = 'Compensación IVA - ' . $egr['nombre_director'];
                }
                
                // SUELDOS: motivo - centro_costo (observaciones)
                if ($egr['motivo'] === 'SUELDOS' && !empty($egr['centro_costo'])) {
                    // Buscar el nombre del centro de costo en la base de datos CENTRAL
                    $sqlCentro = "SELECT CENTRO_COSTO FROM RO_T_CENTRO_DE_COSTOS WHERE COD_AUXILIAR = ?";
                    $stmtCentro = sqlsrv_query($dbCentral, $sqlCentro, [$egr['centro_costo']]);
                    if ($stmtCentro && $rowCentro = sqlsrv_fetch_array($stmtCentro, SQLSRV_FETCH_ASSOC)) {
                        $concepto .= ' - ' . $rowCentro['CENTRO_COSTO'];
                    }
                    if ($stmtCentro) sqlsrv_free_stmt($stmtCentro);
                }
                
                // PROVEEDORES: motivo - proveedor - tipo_gasto (si hay) (observaciones)
                if ($egr['motivo'] === 'PROVEEDORES' && !empty($egr['proveedor'])) {
                    // Buscar el nombre del proveedor en la base de datos CENTRAL
                    $sqlProv = "SELECT NOM_PROVEE FROM RO_V_PROVEEDORES_EGRE_DIRECTORES WHERE COD_PROVEE = ?";
                    $stmtProv = sqlsrv_query($dbCentral, $sqlProv, [$egr['proveedor']]);
                    if ($stmtProv && $rowProv = sqlsrv_fetch_array($stmtProv, SQLSRV_FETCH_ASSOC)) {
                        $concepto .= ' - ' . $rowProv['NOM_PROVEE'];
                    }
                    if ($stmtProv) sqlsrv_free_stmt($stmtProv);
                    
                    // Agregar tipo de gasto si existe
                    if (!empty($egr['tipo_gasto'])) {
                        $concepto .= ' - ' . $egr['tipo_gasto'];
                    }
                }
                
                // PAGO DE SEGUROS: motivo - proveedor (observaciones)
                if ($egr['motivo'] === 'Pago de seguros' && !empty($egr['proveedor_nom'])) {
                    $concepto .= ' - ' . $egr['proveedor_nom'];
                }
                
                // Agregar observaciones al final
                if (!empty($egr['observaciones'])) {
                    $concepto .= ' (' . $egr['observaciones'] . ')';
                }
                
                $movimientos[] = [
                    'tipo' => 'EGRESO',
                    'fecha' => $fecha,
                    'fecha_carga' => $fechaCarga,
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
            
            // Ordenar por fecha_carga descendente (más reciente primero), luego por fecha si no hay fecha_carga
            usort($movimientos, function($a, $b) {
                $fechaA = !empty($a['fecha_carga']) ? $a['fecha_carga'] : $a['fecha'] . ' 00:00:00';
                $fechaB = !empty($b['fecha_carga']) ? $b['fecha_carga'] : $b['fecha'] . ' 00:00:00';
                return strtotime($fechaB) - strtotime($fechaA);
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
    
    // Log detallado con información de contexto
    $errorDetails = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'action' => $_GET['accion'] ?? 'N/A',
        'get_params' => $_GET,
        'trace' => $e->getTraceAsString()
    ];
    
    error_log("Error en caja_reporte_controller.php: " . json_encode($errorDetails));
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_detail' => $e->getFile() . ':' . $e->getLine()
    ]);
}