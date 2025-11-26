<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../Class/Egreso.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar clase Egreso: ' . $e->getMessage()
    ]);
    exit;
}

try {
    $accion = $_GET['accion'] ?? $_POST['accion'] ?? '';
    
    // Log para debugging
    error_log("Accion: " . $accion);
    error_log("POST data: " . print_r($_POST, true));
    
    $egreso = new Egreso();
    
    switch ($accion) {
        case 'crear':
            // Validar datos requeridos
            $errores = [];
            if (empty($_POST['fecha'])) $errores[] = 'fecha';
            if (empty($_POST['tipo_gasto'])) $errores[] = 'tipo_gasto';
            if (empty($_POST['centro_costo'])) $errores[] = 'centro_costo';
            if (!isset($_POST['importe']) || trim($_POST['importe']) === '') $errores[] = 'importe';
            
            if (!empty($errores)) {
                throw new Exception('Faltan datos requeridos: ' . implode(', ', $errores));
            }
            
            // Preparar datos del gasto
            // Limpiar importe: remover puntos de miles y reemplazar coma decimal por punto
            $importeLimpio = str_replace('.', '', $_POST['importe']); // Remover separador de miles
            $importeLimpio = str_replace(',', '.', $importeLimpio); // Convertir coma decimal a punto
            
            // Capturar checkbox es_factura
            $esFactura = isset($_POST['es_factura']) ? (int)$_POST['es_factura'] : 0;
            
            $datos = [
                'fecha' => $_POST['fecha'],
                'motivo' => 'PROVEEDORES', // Fijo
                'proveedor' => 'OGROLL', // Fijo
                'tipo_gasto' => $_POST['tipo_gasto'],
                'centro_costo' => $_POST['centro_costo'],
                'importe' => floatval($importeLimpio),
                'observaciones' => $_POST['observaciones'] ?? '',
                'foto' => $_POST['foto'] ?? null,
                'cod_comp' => 'GAS', // Fijo para gastos
                'es_factura' => $esFactura
            ];
            
            $resultado = $egreso->crearGasto($datos);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Gasto registrado exitosamente'
                ]);
            } else {
                throw new Exception('No se pudo registrar el gasto');
            }
            break;
            
        case 'listar':
            // Obtener solo gastos (COD_COMP = 'GAS')
            $gastos = $egreso->obtenerGastos();
            
            echo json_encode([
                'success' => true,
                'data' => $gastos
            ]);
            break;
            
        case 'centros_costo':
            // Obtener centros de costo
            $centrosCosto = $egreso->obtenerCentrosCosto();
            
            echo json_encode([
                'success' => true,
                'data' => $centrosCosto
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
