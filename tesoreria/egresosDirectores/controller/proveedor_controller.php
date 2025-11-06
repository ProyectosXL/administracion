<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../Class/Proveedor.php';

try {
    $proveedor = new Proveedor();
    $accion = $_GET['accion'] ?? '';
    
    switch ($accion) {
        case 'buscar':
            // Búsqueda para Select2
            $termino = $_GET['q'] ?? ''; // Select2 usa 'q' como parámetro de búsqueda
            $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;
            
            $proveedores = $proveedor->buscar($termino, $limite);
            
            // Agregar opción "No encontrado - Cargar a mano" al final
            $resultados = [
                'results' => []
            ];
            
            // Agregar proveedores encontrados
            foreach ($proveedores as $prov) {
                $resultados['results'][] = [
                    'id' => $prov['nombre'], // Usamos el nombre como ID
                    'text' => $prov['text'],
                    'nombre' => $prov['nombre'],
                    'cuit' => $prov['cuit'],
                    'cbu' => $prov['cbu'],
                    'descripcion_cbu' => $prov['descripcion_cbu'],
                    'tiene_cbu' => !empty($prov['cbu'])
                ];
            }
            
            // Agregar opción manual al final
            $resultados['results'][] = [
                'id' => 'MANUAL',
                'text' => '🔧 No encontrado - Cargar a mano',
                'nombre' => '',
                'cuit' => '',
                'cbu' => '',
                'descripcion_cbu' => '',
                'tiene_cbu' => false,
                'es_manual' => true
            ];
            
            echo json_encode($resultados);
            break;
            
        case 'obtener':
            // Obtener proveedor específico por nombre
            if (empty($_GET['nombre'])) {
                throw new Exception('Nombre de proveedor requerido');
            }
            
            $datos = $proveedor->obtenerPorNombre($_GET['nombre']);
            
            if ($datos) {
                echo json_encode([
                    'success' => true,
                    'data' => $datos
                ]);
            } else {
                throw new Exception('Proveedor no encontrado');
            }
            break;
            
        case 'listar':
            // Listar todos los proveedores (con límite)
            $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 100;
            $proveedores = $proveedor->obtenerTodos($limite);
            
            echo json_encode([
                'success' => true,
                'data' => $proveedores,
                'total' => count($proveedores)
            ]);
            break;
            
        case 'validar_cbu':
            // Validar formato de CBU
            if (empty($_GET['cbu'])) {
                throw new Exception('CBU requerido');
            }
            
            $cbu = $_GET['cbu'];
            $esValido = Proveedor::validarCBU($cbu);
            
            echo json_encode([
                'success' => true,
                'valido' => $esValido,
                'mensaje' => $esValido ? 'CBU válido' : 'CBU debe tener 22 dígitos'
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
