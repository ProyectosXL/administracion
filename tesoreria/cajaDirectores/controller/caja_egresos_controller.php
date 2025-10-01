<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../Class/Egreso.php';

try {
    $egreso = new Egreso();
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
                Egreso::MOTIVO_RETIROS
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
                'observaciones' => $_POST['observaciones'] ?? ''
            ];
            
            // Si es retiro de socio, agregar director
            if ($_POST['motivo'] === Egreso::MOTIVO_RETIROS) {
                if (empty($_POST['nombre_director'])) {
                    throw new Exception('Debe seleccionar un director para retiros de socios');
                }
                $datos['nombre_director'] = $_POST['nombre_director'];
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