<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../Class/Egreso.php';
    require_once __DIR__ . '/../Class/Director.php';
    require_once __DIR__ . '/../Class/Proveedor.php';
    require_once __DIR__ . '/../Class/Database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar clases: ' . $e->getMessage()
    ]);
    exit;
}

try {
    $accion = $_GET['accion'] ?? $_POST['accion'] ?? '';
    
    // Log para debugging
    error_log("=== PAGO SERVICIOS CONTROLLER ===");
    error_log("Acción: " . $accion);
    
    switch ($accion) {
        case 'obtener_directores':
            // Obtener lista de directores usando la clase Director
            $director = new Director();
            $directoresNombres = $director->obtenerDirectores();
            
            // Convertir a formato con ID incremental (ya que no tenemos acceso directo a IDs)
            $directores = [];
            foreach ($directoresNombres as $index => $nombre) {
                $directores[] = [
                    'id' => $index + 1, // ID incremental temporal
                    'nombre' => $nombre
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $directores
            ]);
            break;
            
        case 'buscar_proveedores':
            // Búsqueda de proveedores para Select2
            $proveedor = new Proveedor();
            $termino = $_GET['q'] ?? '';
            $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;
            
            $proveedores = $proveedor->buscar($termino, $limite);
            
            // Formatear para Select2
            $resultados = [
                'results' => []
            ];
            
            foreach ($proveedores as $prov) {
                $resultados['results'][] = [
                    'id' => $prov['nombre'], // Usamos el nombre como ID
                    'text' => $prov['text'],
                    'nombre' => $prov['nombre'],
                    'cuit' => $prov['cuit'],
                    'cbu' => $prov['cbu'],
                    'descripcion_cbu' => $prov['descripcion_cbu'],
                    'tiene_cbu' => $prov['tiene_cbu']
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
            
        case 'crear':
            // Validar datos requeridos
            $errores = [];
            if (empty($_POST['id_director'])) $errores[] = 'id_director';
            if (empty($_POST['nombre_director'])) $errores[] = 'nombre_director';
            if (empty($_POST['motivo'])) $errores[] = 'motivo';
            if (empty($_POST['fecha_vencimiento'])) $errores[] = 'fecha_vencimiento';
            if (!isset($_POST['importe']) || trim($_POST['importe']) === '') $errores[] = 'importe';
            if (empty($_POST['foto'])) $errores[] = 'foto';
            
            if (!empty($errores)) {
                throw new Exception('Faltan datos requeridos: ' . implode(', ', $errores));
            }
            
            // Validar proveedor si es "Pago de seguros"
            if ($_POST['motivo'] === 'Pago de seguros') {
                if (empty($_POST['nom_provee'])) {
                    throw new Exception('El proveedor es obligatorio para pago de seguros');
                }
            }
            
            // Validar CBU si se proporciona
            if (!empty($_POST['cbu'])) {
                if (!Proveedor::validarCBU($_POST['cbu'])) {
                    throw new Exception('El CBU debe tener exactamente 22 dígitos');
                }
            }
            
            // Generar número de comprobante
            $db = Database::getInstance()->getAppsConnection();
            $sqlMaxComp = "SELECT ISNULL(MAX(CAST(N_COMP AS BIGINT)), 0) + 1 as siguiente 
                          FROM egresos 
                          WHERE COD_COMP = 'EGR'";
            $stmtMaxComp = sqlsrv_query($db, $sqlMaxComp);
            
            if ($stmtMaxComp === false) {
                throw new Exception("Error al obtener número de comprobante: " . print_r(sqlsrv_errors(), true));
            }
            
            $rowMaxComp = sqlsrv_fetch_array($stmtMaxComp, SQLSRV_FETCH_ASSOC);
            $nComp = str_pad($rowMaxComp['siguiente'], 11, '0', STR_PAD_LEFT);
            sqlsrv_free_stmt($stmtMaxComp);
            
            error_log("Número de comprobante generado: " . $nComp);
            
            // Limpiar importe
            $importeLimpio = str_replace('.', '', $_POST['importe']);
            $importeLimpio = str_replace(',', '.', $importeLimpio);
            $importe = floatval($importeLimpio);
            
            // Procesar foto (base64 a imagen comprimida)
            $egreso = new Egreso();
            $fotoBase64 = $_POST['foto'];
            
            // Preparar datos para INSERT en egresos
            $proveedor = null;
            if ($_POST['motivo'] === 'Pago de seguros') {
                $proveedor = $_POST['nom_provee'];
            }
            
            // INSERT en tabla egresos
            $sqlEgreso = "INSERT INTO egresos (
                            nombre_director,
                            COD_COMP,
                            N_COMP,
                            motivo,
                            fecha,
                            recibido,
                            observaciones,
                            importe,
                            foto,
                            centro_costo,
                            proveedor,
                            tipo_gasto,
                            fecha_carga
                        ) VALUES (
                            ?, 'EGR', ?, ?, ?, 1, ?, ?, ?, NULL, ?, 'Servicios', GETDATE()
                        );
                        SELECT SCOPE_IDENTITY() AS id_egreso;";
            
            $params = [
                $_POST['nombre_director'],
                $nComp,
                $_POST['motivo'],
                $_POST['fecha_vencimiento'],
                $_POST['observaciones'] ?? '',
                $importe,
                $fotoBase64,
                $proveedor
            ];
            
            error_log("Ejecutando INSERT en egresos...");
            error_log("Params: " . print_r($params, true));
            
            $stmtEgreso = sqlsrv_query($db, $sqlEgreso, $params);
            
            if ($stmtEgreso === false) {
                $errors = sqlsrv_errors();
                error_log("Error SQL en INSERT egresos: " . print_r($errors, true));
                throw new Exception("Error al insertar egreso: " . print_r($errors, true));
            }
            
            // Obtener ID del egreso insertado
            sqlsrv_next_result($stmtEgreso);
            $rowEgreso = sqlsrv_fetch_array($stmtEgreso, SQLSRV_FETCH_ASSOC);
            $idEgreso = $rowEgreso['id_egreso'];
            sqlsrv_free_stmt($stmtEgreso);
            
            error_log("Egreso insertado con ID: " . $idEgreso);
            
            // INSERT en tabla FT_T_PROVEEDORES solo si es "Pago de seguros"
            if ($_POST['motivo'] === 'Pago de seguros') {
                error_log("Insertando en FT_T_PROVEEDORES...");
                
                $sqlProveedor = "INSERT INTO FT_T_PROVEEDORES (
                                    id_egresos,
                                    NOM_PROVEE,
                                    CBU,
                                    DESCRIPCION_CBU
                                ) VALUES (?, ?, ?, ?)";
                
                $cbu = isset($_POST['cbu']) ? trim($_POST['cbu']) : '';
                $descripcionCbu = isset($_POST['descripcion_cbu']) ? trim($_POST['descripcion_cbu']) : '';
                
                $paramsProveedor = [
                    $idEgreso,
                    $_POST['nom_provee'],
                    $cbu,
                    $descripcionCbu
                ];
                
                error_log("Params proveedor: " . print_r($paramsProveedor, true));
                
                $stmtProveedor = sqlsrv_query($db, $sqlProveedor, $paramsProveedor);
                
                if ($stmtProveedor === false) {
                    $errors = sqlsrv_errors();
                    error_log("Error SQL en INSERT FT_T_PROVEEDORES: " . print_r($errors, true));
                    throw new Exception("Error al insertar proveedor: " . print_r($errors, true));
                }
                
                sqlsrv_free_stmt($stmtProveedor);
                error_log("Proveedor insertado exitosamente");
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Pago registrado exitosamente',
                'id_egreso' => $idEgreso,
                'n_comp' => $nComp
            ]);
            break;
            
        case 'listar_ultimos':
            // Obtener últimos pagos de servicios (motivos: Pago de seguros, Pago de patentes, Pago de expensas, Pago de tarjetas, Transf. Haberes, Otros)
            $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
            
            $db = Database::getInstance()->getAppsConnection();
            // SQL Server no permite parámetros con TOP, usar directamente el valor
            $sql = "SELECT TOP {$limite}
                        e.id,
                        e.nombre_director,
                        e.motivo,
                        e.fecha,
                        e.importe,
                        e.observaciones,
                        e.fecha_carga,
                        e.foto,
                        CASE WHEN e.foto IS NOT NULL THEN 1 ELSE 0 END as tiene_foto,
                        p.NOM_PROVEE as proveedor_nom,
                        p.CBU as proveedor_cbu,
                        p.DESCRIPCION_CBU as proveedor_descripcion_cbu
                    FROM egresos e
                    LEFT JOIN FT_T_PROVEEDORES p ON e.id = p.id_egresos
                    WHERE e.COD_COMP = 'EGR'
                      AND e.motivo IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas', 'Pago de tarjetas', 'Transf. Haberes', 'Otros')
                    ORDER BY e.fecha_carga DESC, e.id DESC";
            
            $stmt = sqlsrv_query($db, $sql);
            
            if ($stmt === false) {
                throw new Exception("Error al listar pagos: " . print_r(sqlsrv_errors(), true));
            }
            
            $pagos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Formatear fecha
                if ($row['fecha'] instanceof DateTime) {
                    $row['fecha'] = $row['fecha']->format('Y-m-d');
                }
                if ($row['fecha_carga'] instanceof DateTime) {
                    $row['fecha_carga'] = $row['fecha_carga']->format('Y-m-d H:i:s');
                }
                
                // Detectar tipo de archivo si tiene foto
                if ($row['tiene_foto'] && !empty($row['foto'])) {
                    $fotoCompleta = $row['foto'];
                    $row['tipo_archivo'] = 'image/jpeg'; // Por defecto
                    
                    // Si el dato ya incluye el prefijo data:mime;base64, extraerlo
                    if (strpos($fotoCompleta, 'data:') === 0) {
                        // Formato: data:application/pdf;base64,JVBERi0...
                        preg_match('/^data:([^;]+);base64,/', $fotoCompleta, $matches);
                        if ($matches) {
                            $row['tipo_archivo'] = $matches[1];
                        }
                    } else {
                        // Es base64 puro, detectar por contenido
                        $foto = $row['foto'];
                        
                        // Los PDFs en base64 empiezan con "JVBERi0"
                        if (strpos($foto, 'JVBERi0') === 0) {
                            $row['tipo_archivo'] = 'application/pdf';
                        }
                        // Las imágenes JPEG empiezan con "/9j/"
                        else if (strpos($foto, '/9j/') === 0) {
                            $row['tipo_archivo'] = 'image/jpeg';
                        }
                        // Las imágenes PNG empiezan con "iVBORw0KGgo"
                        else if (strpos($foto, 'iVBORw0KGgo') === 0) {
                            $row['tipo_archivo'] = 'image/png';
                        }
                    }
                }
                
                // No enviar el base64 de la foto en el listado (solo el tipo)
                unset($row['foto']);
                
                $pagos[] = $row;
            }
            
            sqlsrv_free_stmt($stmt);
            
            echo json_encode([
                'success' => true,
                'data' => $pagos
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida: ' . $accion);
    }
    
} catch (Exception $e) {
    error_log("Error en pago_servicios_controller: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
