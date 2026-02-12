<?php
header('Content-Type: application/json');

// El try principal envuelve TODA la lógica del archivo para capturar cualquier error.
try {
    require_once __DIR__ . '/../Class/Egreso.php';
    require_once __DIR__ . '/../Class/Director.php';
    require_once __DIR__ . '/../Class/Proveedor.php';
    require_once __DIR__ . '/../Class/MotivoPagoServicio.php';

    $accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

    switch ($accion) {

        case 'obtener_directores':
            $director = new Director();
            $directoresNombres = $director->obtenerDirectores();
            $directores = [];
            foreach ($directoresNombres as $index => $nombre) {
                $directores[] = [
                    'id' => $index + 1,
                    'nombre' => $nombre
                ];
            }
            echo json_encode(['success' => true, 'data' => $directores]);
            break;

        case 'obtener_motivos':
            $motivo = new MotivoPagoServicio();
            $motivos = $motivo->obtenerMotivosActivos();
            echo json_encode(['success' => true, 'data' => $motivos]);
            break;

        case 'buscar_proveedores':
            $proveedor = new Proveedor();
            $termino = $_GET['q'] ?? '';
            $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 50;
            $proveedores = $proveedor->buscar($termino, $limite);

            $resultados = ['results' => []];
            foreach ($proveedores as $prov) {
                $resultados['results'][] = [
                    'id' => $prov['nombre'],
                    'text' => $prov['text'],
                    'nombre' => $prov['nombre'],
                    'cuit' => $prov['cuit'],
                    'cbu' => $prov['cbu'],
                    'descripcion_cbu' => $prov['descripcion_cbu'],
                    'tiene_cbu' => $prov['tiene_cbu']
                ];
            }
            $resultados['results'][] = [
                'id' => 'MANUAL',
                'text' => '🔧 No encontrado - Cargar a mano',
                'es_manual' => true
            ];
            echo json_encode($resultados);
            break;

        case 'crear':
            $errores = [];
            if (empty($_POST['id_director']))
                $errores[] = 'id_director';
            if (empty($_POST['nombre_director']))
                $errores[] = 'nombre_director';
            if (empty($_POST['motivo']))
                $errores[] = 'motivo';
            if (empty($_POST['fecha_vencimiento']))
                $errores[] = 'fecha_vencimiento';
            if (!isset($_POST['importe']) || trim($_POST['importe']) === '')
                $errores[] = 'importe';

            // Manejo de múltiples fotos
            $fotos = $_POST['fotos'] ?? [];
            if (empty($fotos) && !empty($_POST['foto'])) {
                $fotos[] = $_POST['foto'];
            }

            if (empty($fotos))
                $errores[] = 'foto'; // Al menos una foto requerida

            if (!empty($errores)) {
                throw new Exception('Faltan datos requeridos: ' . implode(', ', $errores));
            }
            if ($_POST['motivo'] === 'Pago de seguros' && empty($_POST['nom_provee'])) {
                throw new Exception('El proveedor es obligatorio para pago de seguros');
            }
            if (!empty($_POST['cbu']) && !Proveedor::validarCBU($_POST['cbu'])) {
                throw new Exception('El CBU debe tener exactamente 22 dígitos');
            }

            require_once __DIR__ . '/../../../class/conexion.php';
            $conexion = new Conexion();
            $db = $conexion->conectar('apps');
            $sqlMaxComp = "SELECT ISNULL(MAX(CAST(N_COMP AS BIGINT)), 0) + 1 as siguiente FROM egresos WHERE COD_COMP = 'EGR'";
            $stmtMaxComp = sqlsrv_query($db, $sqlMaxComp);
            if ($stmtMaxComp === false)
                throw new Exception("Error al obtener N° de comprobante.");
            $rowMaxComp = sqlsrv_fetch_array($stmtMaxComp, SQLSRV_FETCH_ASSOC);
            $nComp = str_pad($rowMaxComp['siguiente'], 11, '0', STR_PAD_LEFT);
            sqlsrv_free_stmt($stmtMaxComp);

            $importeLimpio = str_replace(['.', ','], ['', '.'], $_POST['importe']);
            $importe = floatval($importeLimpio);

            $importe = floatval($importeLimpio);

            $egreso = new Egreso();
            $mainPhoto = $fotos[0] ?? null;
            $fotoBase64 = $egreso->comprimirImagen($mainPhoto);

            $proveedor = ($_POST['motivo'] === 'Pago de seguros') ? $_POST['nom_provee'] : null;

            $sqlEgreso = "INSERT INTO egresos (nombre_director, COD_COMP, N_COMP, motivo, fecha, recibido, observaciones, importe, foto, proveedor, tipo_gasto, fecha_carga) VALUES (?, 'EGR', ?, ?, ?, 1, ?, ?, ?, ?, 'Servicios', GETDATE()); SELECT SCOPE_IDENTITY() AS id_egreso;";
            $params = [$_POST['nombre_director'], $nComp, $_POST['motivo'], $_POST['fecha_vencimiento'], $_POST['observaciones'] ?? '', $importe, $fotoBase64, $proveedor];
            $stmtEgreso = sqlsrv_query($db, $sqlEgreso, $params);
            if ($stmtEgreso === false)
                throw new Exception("Error al insertar egreso: " . print_r(sqlsrv_errors(), true));
            sqlsrv_next_result($stmtEgreso);
            $rowEgreso = sqlsrv_fetch_array($stmtEgreso, SQLSRV_FETCH_ASSOC);
            $idEgreso = $rowEgreso['id_egreso'];
            sqlsrv_free_stmt($stmtEgreso);

            if ($_POST['motivo'] === 'Pago de seguros') {
                $sqlProveedor = "INSERT INTO FT_T_PROVEEDORES (id_egresos, NOM_PROVEE, CBU, DESCRIPCION_CBU) VALUES (?, ?, ?, ?)";
                $paramsProveedor = [$idEgreso, $_POST['nom_provee'], $_POST['cbu'] ?? '', $_POST['descripcion_cbu'] ?? ''];
                $stmtProveedor = sqlsrv_query($db, $sqlProveedor, $paramsProveedor);
                if ($stmtProveedor === false)
                    throw new Exception("Error al insertar proveedor: " . print_r(sqlsrv_errors(), true));
                sqlsrv_free_stmt($stmtProveedor);
            }

            // Guardar todas las fotos en la tabla de archivos
            if (!empty($fotos)) {
                $egreso->guardarArchivos($idEgreso, $fotos);
            }

            echo json_encode(['success' => true, 'message' => 'Pago registrado exitosamente', 'id_egreso' => $idEgreso, 'n_comp' => $nComp]);
            break;

        case 'listar_pagos_filtrados':
            $egreso = new Egreso();
            $filtros = [];
            if (!empty($_GET['director']))
                $filtros['nombre_director'] = $_GET['director'];
            if (!empty($_GET['motivo']))
                $filtros['motivo'] = $_GET['motivo'];
            if (!empty($_GET['fecha_desde']))
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            if (!empty($_GET['fecha_hasta']))
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            $filtros['tipo_gasto_especifico'] = 'Servicios';

            $pagos = $egreso->obtenerTodos($filtros);

            $pagosFormateados = [];
            foreach ($pagos as $pago) {
                if ($pago['fecha'] instanceof DateTime)
                    $pago['fecha'] = $pago['fecha']->format('Y-m-d');
                if ($pago['fecha_carga'] instanceof DateTime)
                    $pago['fecha_carga'] = $pago['fecha_carga']->format('Y-m-d H:i:s');
                if ($pago['tiene_foto']) {
                    $fotoData = $egreso->obtenerFoto($pago['id']);
                    $pago['tipo_archivo'] = $fotoData['tipo'] ?? 'image/jpeg';
                }
                unset($pago['foto']);
                $pagosFormateados[] = $pago;
            }

            echo json_encode(['success' => true, 'data' => $pagosFormateados]);
            break;

        case 'obtener_pago_detalle':
            $id = $_GET['id'] ?? null;
            if (empty($id))
                throw new Exception('ID de pago no proporcionado');
            $egreso = new Egreso();
            $pago = $egreso->obtenerPorId((int) $id);
            if (!$pago)
                throw new Exception('Pago no encontrado');
            echo json_encode(['success' => true, 'data' => $pago]);
            break;

        case 'actualizar_pago':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST')
                throw new Exception('Método no permitido');
            $id = $_POST['id'] ?? null;
            if (empty($id))
                throw new Exception('ID de pago es requerido para actualizar');

            $importeLimpio = str_replace(['.', ','], ['', '.'], $_POST['importe']);
            $_POST['importe'] = floatval($importeLimpio);

            $fotoBase64 = null;
            // Manejo de nuevas fotos en edición
            $nuevasFotos = $_POST['fotos'] ?? [];
            if (empty($nuevasFotos) && !empty($_POST['foto'])) {
                $nuevasFotos[] = $_POST['foto'];
            }

            if (!empty($nuevasFotos)) {
                $egresoFoto = new Egreso();
                // Actualizamos la foto principal con la primera de las nuevas
                $fotoBase64 = $egresoFoto->comprimirImagen($nuevasFotos[0]);

                // Y guardamos todas en la tabla adjunta
                // Nota: Esto agrega las nuevas fotos, no borra las anteriores de la tabla adjunta.
                // Si quisiéramos borrar, deberíamos hacerlo explícitamente.
                // Por ahora, solo AGREGAMOS (según requerimiento de "sumar más de una foto")
                // Pero necesitamos el ID, que está en variable $id
                $egresoFoto->guardarArchivos((int) $id, $nuevasFotos);
            }

            // Solo si se envió una foto actualizamos la columna 'foto' principal
            if ($fotoBase64) {
                $_POST['foto'] = $fotoBase64;
            } else {
                unset($_POST['foto']); // No tocar la foto principal si no se envió una nueva
            }

            $_POST['proveedor'] = ($_POST['motivo'] === 'Pago de seguros') ? $_POST['nom_provee'] : null;

            $egreso = new Egreso();
            $resultado = $egreso->actualizar((int) $id, $_POST);

            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Pago actualizado exitosamente.']);
            } else {
                throw new Exception('No se pudo actualizar el pago.');
            }
            break;

        case 'obtener_foto':
            $id = $_GET['id'] ?? null;
            if (empty($id)) {
                throw new Exception('ID de pago no proporcionado');
            }
            $egreso = new Egreso();
            $fotoData = $egreso->obtenerFoto((int) $id);

            if ($fotoData) {
                echo json_encode([
                    'success' => true,
                    'foto' => $fotoData['foto'],
                    'tipo' => $fotoData['tipo']
                ]);
            } else {
                throw new Exception('Archivo no encontrado o egreso no existe.');
            }
            break;

        case 'obtener_archivos':
            $id = $_GET['id'] ?? null;
            if (empty($id)) {
                throw new Exception('ID de pago no proporcionado');
            }
            $egreso = new Egreso();
            $archivos = $egreso->obtenerArchivos((int) $id);
            echo json_encode(['success' => true, 'archivos' => $archivos]);
            break;

        case 'eliminar_pago':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST')
                throw new Exception('Método no permitido');
            $id = $_POST['id'] ?? null;
            if (empty($id))
                throw new Exception('ID de pago no proporcionado');
            $egreso = new Egreso();
            $resultado = $egreso->eliminar((int) $id);
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'El pago ha sido eliminado exitosamente.']);
            } else {
                throw new Exception('No se pudo eliminar el pago o el pago no existe.');
            }
            break;

        default:
            if (!empty($accion)) {
                throw new Exception('Acción no válida: ' . htmlspecialchars($accion));
            }
            // Si la acción está vacía, se devuelve un JSON vacío para evitar errores en el frontend.
            echo json_encode(['success' => true, 'data' => []]);
            break;

    } // Fin del switch

} catch (Throwable $e) { // 'Throwable' atrapa errores fatales y excepciones

    // Escribir el error en un log para que podamos verlo
    $errorMessage = "[" . date("Y-m-d H:i:s") . "] FATAL ERROR en pago_servicios_controller.php: " . PHP_EOL;
    $errorMessage .= "Acción: " . ($accion ?? 'No definida') . PHP_EOL;
    $errorMessage .= "Mensaje: " . $e->getMessage() . PHP_EOL;
    $errorMessage .= "Archivo: " . $e->getFile() . PHP_EOL;
    $errorMessage .= "Línea: " . $e->getLine() . PHP_EOL;
    $errorMessage .= "--------------------------------------------------" . PHP_EOL;
    file_put_contents(__DIR__ . '/_debug_log.txt', $errorMessage, FILE_APPEND);

    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Ocurrió un error crítico en el servidor. El administrador ha sido notificado.'
    ]);
}