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
            // Log de datos recibidos
            error_log("=== INICIO CREACION GASTO ===");
            error_log("POST recibido: " . json_encode($_POST));
            error_log("FILES recibido: " . json_encode(array_keys($_FILES)));
            
            // Validar datos requeridos
            $errores = [];
            if (empty($_POST['fecha'])) $errores[] = 'fecha';
            if (empty($_POST['tipo_gasto'])) $errores[] = 'tipo_gasto';
            if (!isset($_POST['importe']) || trim($_POST['importe']) === '') {
                $errores[] = 'importe';
                error_log("ERROR: Importe vacío o no definido. Valor recibido: " . var_export($_POST['importe'] ?? 'NO DEFINIDO', true));
            }
            
            if (!empty($errores)) {
                error_log("Errores de validación básica: " . implode(', ', $errores));
                throw new Exception('Faltan datos requeridos: ' . implode(', ', $errores));
            }
            
            // Validar distribución
            if (empty($_POST['distribucion'])) {
                error_log("Error: No se recibió distribución");
                throw new Exception('Falta la distribución de centros de costo');
            }
            
            // Decodificar distribución
            $distribucion = json_decode($_POST['distribucion'], true);
            error_log("Distribución decodificada: " . json_encode($distribucion));
            
            if (!is_array($distribucion) || count($distribucion) === 0) {
                error_log("Error: Distribución inválida o vacía");
                throw new Exception('La distribución de centros de costo es inválida');
            }
            
            // Validar suma de porcentajes
            $totalPorcentaje = array_sum(array_column($distribucion, 'porcentaje'));
            error_log("Total porcentaje calculado: " . $totalPorcentaje);
            
            if (abs($totalPorcentaje - 100) > 0.01) {
                error_log("Error: Porcentajes no suman 100%");
                throw new Exception('La suma de porcentajes debe ser 100%. Actual: ' . $totalPorcentaje . '%');
            }
            
            // Validar centros únicos
            $centros = array_column($distribucion, 'centro_costo');
            if (count($centros) !== count(array_unique($centros))) {
                error_log("Error: Centros de costo duplicados");
                throw new Exception('No puede haber centros de costo duplicados');
            }
            
            // Limpiar importe: remover puntos de miles y reemplazar coma decimal por punto
            error_log("Importe recibido (raw): " . var_export($_POST['importe'], true));
            $importeLimpio = str_replace('.', '', $_POST['importe']); // Remover separador de miles
            $importeLimpio = str_replace(',', '.', $importeLimpio); // Convertir coma decimal a punto
            $importeTotal = floatval($importeLimpio);
            error_log("Importe limpio: " . $importeLimpio);
            error_log("Importe total calculado: " . $importeTotal);
            
            if ($importeTotal <= 0) {
                error_log("ERROR: Importe inválido (menor o igual a 0)");
                throw new Exception('El importe debe ser mayor a 0. Valor recibido: ' . $_POST['importe']);
            }
            
            // Capturar checkbox es_factura
            $esFactura = isset($_POST['es_factura']) ? (int)$_POST['es_factura'] : 0;
            
            // Preparar datos comunes del gasto
            $datosComunes = [
                'fecha' => $_POST['fecha'],
                'motivo' => 'PROVEEDORES', // Fijo
                'proveedor' => 'OGROLL', // Fijo
                'tipo_gasto' => $_POST['tipo_gasto'],
                'observaciones' => $_POST['observaciones'] ?? '',
                'foto' => $_POST['foto'] ?? null,
                'tipo_archivo' => $_POST['tipo_archivo'] ?? 'image/jpeg',
                'cod_comp' => 'GAS', // Fijo para gastos
                'es_factura' => $esFactura
            ];
            
            error_log("Llamando a crearGastoConDistribucion...");
            
            // Crear gasto con distribución
            $resultado = $egreso->crearGastoConDistribucion($datosComunes, $distribucion, $importeTotal);
            
            error_log("Resultado de creación: " . ($resultado ? 'true' : 'false'));
            
            if ($resultado) {
                error_log("=== GASTO CREADO EXITOSAMENTE ===");
                echo json_encode([
                    'success' => true,
                    'message' => 'Gasto registrado exitosamente con ' . count($distribucion) . ' centro(s) de costo'
                ]);
            } else {
                error_log("Error: No se pudo registrar el gasto");
                throw new Exception('No se pudo registrar el gasto');
            }
            break;
            
        case 'listar':
            // Obtener solo gastos (COD_COMP = 'GAS')
            $gastos = $egreso->obtenerGastos();
            
            // Enriquecer con nombres de centros de costo
            $conexion = $egreso->getConexion();
            $dbCentral = $conexion->conectar('central');
            
            if ($dbCentral === false) {
                throw new Exception('Error al conectar con la base de datos central');
            }
            
            foreach ($gastos as &$gasto) {
                if (!empty($gasto['centro_costo'])) {
                    $sqlNombre = "SELECT CENTRO_COSTO FROM RO_T_CENTRO_DE_COSTOS WHERE COD_AUXILIAR = ?";
                    $stmtNombre = sqlsrv_query($dbCentral, $sqlNombre, [$gasto['centro_costo']]);
                    if ($stmtNombre && $rowNombre = sqlsrv_fetch_array($stmtNombre, SQLSRV_FETCH_ASSOC)) {
                        $gasto['centro_costo_nombre'] = trim($rowNombre['CENTRO_COSTO']);
                    } else {
                        $gasto['centro_costo_nombre'] = $gasto['centro_costo']; // Fallback al código
                    }
                    if ($stmtNombre) sqlsrv_free_stmt($stmtNombre);
                }
            }
            
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
