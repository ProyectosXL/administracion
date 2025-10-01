<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../Class/Ingreso.php';

try {
    $ingreso = new Ingreso();
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    switch ($accion) {
        case 'crear':
            // Validar datos requeridos
            if (empty($_POST['fecha']) || empty($_POST['importe'])) {
                throw new Exception('Faltan datos obligatorios');
            }
            
            // Limpiar y validar importe
            $importe = str_replace(['.', ','], ['', '.'], $_POST['importe']);
            $importe = (float)$importe;
            
            if ($importe <= 0) {
                throw new Exception('El importe debe ser mayor a cero');
            }
            
            $datos = [
                'fecha' => $_POST['fecha'],
                'importe' => $importe,
                'observaciones' => $_POST['observaciones'] ?? '',
                'recibido' => 0,
                'origen' => 'MANUAL'
            ];
            
            $resultado = $ingreso->crear($datos);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ingreso registrado correctamente'
                ]);
            } else {
                throw new Exception('Error al registrar el ingreso');
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
            
            if (isset($_GET['recibido'])) {
                $filtros['recibido'] = (int)$_GET['recibido'];
            }
            
            $ingresos = $ingreso->obtenerTodos($filtros);
            
            // Convertir fechas DateTime a string para JSON
            foreach ($ingresos as &$ing) {
                if (is_object($ing['fecha'])) {
                    $ing['fecha'] = $ing['fecha']->format('Y-m-d');
                }
                if (isset($ing['fecha_solo']) && is_object($ing['fecha_solo'])) {
                    $ing['fecha_solo'] = $ing['fecha_solo']->format('Y-m-d');
                }
                if (isset($ing['fecha_carga']) && is_object($ing['fecha_carga'])) {
                    $ing['fecha_carga'] = $ing['fecha_carga']->format('Y-m-d H:i:s');
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $ingresos
            ]);
            break;
            
        case 'listar_combinados':
            if (empty($_GET['fecha_desde']) || empty($_GET['fecha_hasta'])) {
                throw new Exception('Fechas desde y hasta son requeridas');
            }
            
            $desde = $_GET['fecha_desde'];
            $hasta = $_GET['fecha_hasta'];
            
            $ingresos = $ingreso->obtenerIngresosCombinados($desde, $hasta);
            
            // Convertir fechas DateTime a string para JSON
            foreach ($ingresos as &$ing) {
                if (is_object($ing['fecha'])) {
                    $ing['fecha'] = $ing['fecha']->format('Y-m-d');
                }
                if (isset($ing['fecha_carga']) && is_object($ing['fecha_carga'])) {
                    $ing['fecha_carga'] = $ing['fecha_carga']->format('Y-m-d H:i:s');
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $ingresos
            ]);
            break;
            
        case 'marcar_recibido':
            if (empty($_POST['id'])) {
                throw new Exception('ID de ingreso no especificado');
            }
            
            $resultado = $ingreso->marcarRecibido((int)$_POST['id']);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ingreso marcado como recibido'
                ]);
            } else {
                throw new Exception('Error al marcar el ingreso');
            }
            break;
            
        case 'marcar_recibido_599':
            $required = ['id_sba05', 'fecha', 'cod_comp', 'n_comp', 'observaciones', 'importe'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Campo requerido: {$field}");
                }
            }
            
            $resultado = $ingreso->marcarRecibido599(
                $_POST['id_sba05'],
                $_POST['fecha'],
                $_POST['cod_comp'],
                $_POST['n_comp'],
                $_POST['observaciones'],
                (float)$_POST['importe']
            );
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ingreso 599 marcado como recibido'
                ]);
            } else {
                throw new Exception('Error al marcar el ingreso 599');
            }
            break;
            
        case 'marcar_recibido_tesoreria':
            $required = ['id_tesoreria', 'fecha', 'observaciones', 'importe'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Campo requerido: {$field}");
                }
            }
            
            $resultado = $ingreso->marcarRecibidoTesoreria(
                $_POST['id_tesoreria'],
                $_POST['fecha'],
                $_POST['observaciones'],
                (float)$_POST['importe']
            );
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ingreso de TESORERÍA marcado como recibido'
                ]);
            } else {
                throw new Exception('Error al marcar el ingreso de TESORERÍA');
            }
            break;
            
        case 'total_recibido':
            $total = $ingreso->obtenerTotalRecibido();
            
            echo json_encode([
                'success' => true,
                'total' => $total
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