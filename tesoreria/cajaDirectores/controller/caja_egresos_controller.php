<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../class/Egreso.php';

try {
    // Intentar crear instancia de Egreso con manejo de errores mejorado
    try {
        $egreso = new Egreso();
    } catch (Exception $e) {
        error_log("Error crítico al crear instancia de Egreso: " . $e->getMessage());
        throw new Exception("Error al inicializar el módulo de egresos: " . $e->getMessage());
    }
    
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    switch ($accion) {
        case 'crear':
            // Validar datos requeridos
            if (empty($_POST['fecha']) || empty($_POST['importe']) || empty($_POST['motivo'])) {
                throw new Exception('Faltan datos obligatorios');
            }
            
            // Validar motivo
            $motivosValidos = [
                Egreso::MOTIVO_SUELDOS,
                Egreso::MOTIVO_PROVEEDORES,
                Egreso::MOTIVO_RETIROS,
                Egreso::MOTIVO_COMPENSACION_IVA,
                Egreso::MOTIVO_AJUSTE
            ];
            
            if (!in_array($_POST['motivo'], $motivosValidos)) {
                throw new Exception('Motivo no válido');
            }
            
            // Limpiar y validar importe
            $importe = str_replace(['.', ','], ['', '.'], $_POST['importe']);
            $importe = (float)$importe;
            
            if ($importe <= 0) {
                throw new Exception('El importe debe ser mayor a cero');
            }
            
            $datos = [
                'fecha' => $_POST['fecha'],
                'motivo' => $_POST['motivo'],
                'importe' => $importe,
                'observaciones' => $_POST['observaciones'] ?? '',
                'foto' => $_POST['foto'] ?? null
            ];
            
            // Si es retiro de socio, agregar director
            if ($_POST['motivo'] === Egreso::MOTIVO_RETIROS) {
                if (empty($_POST['nombre_director'])) {
                    throw new Exception('Debe seleccionar un director para retiros de socios');
                }
                $datos['nombre_director'] = $_POST['nombre_director'];
            }
            
            // Si es compensación IVA, agregar director
            if ($_POST['motivo'] === Egreso::MOTIVO_COMPENSACION_IVA) {
                if (empty($_POST['nombre_director'])) {
                    throw new Exception('Debe seleccionar un director para compensación IVA');
                }
                $datos['nombre_director'] = $_POST['nombre_director'];
            }
            
            // Si es para sueldos, agregar centro de costo
            if ($_POST['motivo'] === Egreso::MOTIVO_SUELDOS) {
                if (!empty($_POST['centro_costo'])) {
                    $datos['centro_costo'] = $_POST['centro_costo'];
                }
            }
            
            // Si es pago a proveedores, agregar proveedor y tipo de gasto
            if ($_POST['motivo'] === Egreso::MOTIVO_PROVEEDORES) {
                if (!empty($_POST['proveedor'])) {
                    $datos['proveedor'] = $_POST['proveedor'];
                }
                if (!empty($_POST['tipo_gasto'])) {
                    $datos['tipo_gasto'] = $_POST['tipo_gasto'];
                }
            }
            
            $resultado = $egreso->crear($datos);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Egreso registrado correctamente'
                ]);
            } else {
                throw new Exception('Error al registrar el egreso');
            }
            break;
            
        case 'listar':
            $filtros = [];
            
            if (!empty($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            }
            
            if (!empty($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            }
            
            if (!empty($_GET['motivo'])) {
                $filtros['motivo'] = $_GET['motivo'];
            }
            
            $egresos = $egreso->obtenerTodos($filtros);
            
            // Convertir fechas DateTime a string para JSON
            foreach ($egresos as &$egr) {
                if (is_object($egr['fecha'])) {
                    $egr['fecha'] = $egr['fecha']->format('Y-m-d');
                }
                if (isset($egr['fecha_solo']) && is_object($egr['fecha_solo'])) {
                    $egr['fecha_solo'] = $egr['fecha_solo']->format('Y-m-d');
                }
                if (isset($egr['fecha_carga']) && is_object($egr['fecha_carga'])) {
                    $egr['fecha_carga'] = $egr['fecha_carga']->format('Y-m-d H:i:s');
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $egresos
            ]);
            break;
            
        case 'total':
            $total = $egreso->obtenerTotal();
            
            echo json_encode([
                'success' => true,
                'total' => $total
            ]);
            break;
            
        case 'directores':
            $directores = $egreso->obtenerDirectores();
            
            echo json_encode([
                'success' => true,
                'data' => $directores
            ]);
            break;
            
        case 'centros_costo':
            $centrosCosto = $egreso->obtenerCentrosCosto();
            
            echo json_encode([
                'success' => true,
                'data' => $centrosCosto
            ]);
            break;
            
        case 'proveedores':
            $proveedores = $egreso->obtenerProveedores();
            
            echo json_encode([
                'success' => true,
                'data' => $proveedores
            ]);
            break;
            
        case 'obtener_foto':
            if (empty($_GET['id'])) {
                throw new Exception('ID de egreso requerido');
            }
            
            $resultado = $egreso->obtenerFoto($_GET['id']);
            
            if (!$resultado) {
                throw new Exception('No se encontró el archivo');
            }
            
            echo json_encode([
                'success' => true,
                'foto' => $resultado['foto'],
                'tipo' => $resultado['tipo']
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