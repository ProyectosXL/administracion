<?php
// ===== DEBUG ULTRA EXTREMO =====
error_log('[GLOBAL DEBUG] === INICIO ABSOLUTO caja_ingresos_controller.php ===');
error_log('[GLOBAL DEBUG] Timestamp: ' . date('Y-m-d H:i:s'));
error_log('[GLOBAL DEBUG] Método HTTP: ' . $_SERVER['REQUEST_METHOD']);
error_log('[GLOBAL DEBUG] Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'NO_DEFINIDO'));
error_log('[GLOBAL DEBUG] User-Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'NO_DEFINIDO'));
error_log('[GLOBAL DEBUG] Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'NO_DEFINIDO'));
error_log('[GLOBAL DEBUG] Query String: ' . ($_SERVER['QUERY_STRING'] ?? 'NO_DEFINIDO'));

// Capturar entrada RAW
$raw_input = file_get_contents('php://input');
error_log('[GLOBAL DEBUG] php://input RAW (' . strlen($raw_input) . ' chars): ' . $raw_input);

// Análisis de $_POST
error_log('[GLOBAL DEBUG] $_POST count: ' . count($_POST));
error_log('[GLOBAL DEBUG] $_POST keys: ' . implode(', ', array_keys($_POST)));
error_log('[GLOBAL DEBUG] $_POST complete: ' . print_r($_POST, true));

// Análisis de $_GET
error_log('[GLOBAL DEBUG] $_GET count: ' . count($_GET));
error_log('[GLOBAL DEBUG] $_GET complete: ' . print_r($_GET, true));

// Análisis de $_REQUEST
error_log('[GLOBAL DEBUG] $_REQUEST count: ' . count($_REQUEST));
error_log('[GLOBAL DEBUG] $_REQUEST complete: ' . print_r($_REQUEST, true));

header('Content-Type: application/json');
require_once __DIR__ . '/../class/Ingreso.php';

try {
    $ingreso = new Ingreso();
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    error_log('[GLOBAL DEBUG] Acción detectada: "' . $accion . '"');
    error_log('[GLOBAL DEBUG] === FIN DEBUG GLOBAL - INICIANDO SWITCH ===');
    
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
            
        case 'marcar_recibido_tesoreria':
            // Debug EXTREMO: Capturar TODO lo que llega
            error_log('[ULTRA DEBUG] === INICIO marcar_recibido_tesoreria ===');
            error_log('[ULTRA DEBUG] Método HTTP: ' . $_SERVER['REQUEST_METHOD']);
            error_log('[ULTRA DEBUG] Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'NO_DEFINIDO'));
            error_log('[ULTRA DEBUG] POST raw: ' . print_r($_POST, true));
            error_log('[ULTRA DEBUG] REQUEST raw: ' . print_r($_REQUEST, true));
            error_log('[ULTRA DEBUG] php://input: ' . file_get_contents('php://input'));
            
            // Obtener valores con múltiples fuentes de respaldo
            $id_sba05 = $_POST['id_sba05'] ?? $_REQUEST['id_sba05'] ?? '';
            $fecha = $_POST['fecha'] ?? $_REQUEST['fecha'] ?? '';
            $cod_comp = $_POST['cod_comp'] ?? $_REQUEST['cod_comp'] ?? '';
            $n_comp = $_POST['n_comp'] ?? $_REQUEST['n_comp'] ?? '';
            $observaciones = $_POST['observaciones'] ?? $_REQUEST['observaciones'] ?? '';
            $importe = $_POST['importe'] ?? $_REQUEST['importe'] ?? 0;
            
            // Log EXTREMO de cada campo con análisis profundo
            error_log('[ULTRA DEBUG] ANÁLISIS DE CAMPOS:');
            error_log('  id_sba05: "' . $id_sba05 . '" (tipo: ' . gettype($id_sba05) . ', longitud: ' . strlen($id_sba05) . ', empty: ' . (empty($id_sba05) ? 'true' : 'false') . ')');
            error_log('  fecha: "' . $fecha . '" (tipo: ' . gettype($fecha) . ', longitud: ' . strlen($fecha) . ', empty: ' . (empty($fecha) ? 'true' : 'false') . ')');
            error_log('  cod_comp: "' . $cod_comp . '" (tipo: ' . gettype($cod_comp) . ')');
            error_log('  n_comp: "' . $n_comp . '" (tipo: ' . gettype($n_comp) . ')');
            error_log('  observaciones: "' . $observaciones . '" (tipo: ' . gettype($observaciones) . ')');
            error_log('  importe: "' . $importe . '" (tipo: ' . gettype($importe) . ')');
            
            // Análisis de caracteres ocultos en campos críticos
            if (!empty($id_sba05)) {
                error_log('[ULTRA DEBUG] id_sba05 análisis de caracteres:');
                for ($i = 0; $i < strlen($id_sba05); $i++) {
                    $char = $id_sba05[$i];
                    error_log('  Posición ' . $i . ': "' . $char . '" (ASCII: ' . ord($char) . ')');
                }
            }
            
            // Limpiar posibles caracteres ocultos
            $id_sba05 = trim($id_sba05);
            $fecha = trim($fecha);
            $observaciones = trim($observaciones);
            
            error_log('[ULTRA DEBUG] Después de trim:');
            error_log('  id_sba05: "' . $id_sba05 . '" (longitud: ' . strlen($id_sba05) . ')');
            error_log('  fecha: "' . $fecha . '" (longitud: ' . strlen($fecha) . ')');
            
            // Validación ULTRA detallada
            if (empty($id_sba05) || $id_sba05 === '' || $id_sba05 === 'undefined' || $id_sba05 === 'null') {
                $error = 'Campo requerido: id_sba05';
                error_log('[CRITICAL ERROR] ' . $error);
                error_log('[CRITICAL ERROR] Valor exacto recibido: "' . $id_sba05 . '"');
                error_log('[CRITICAL ERROR] Análisis completo: empty=' . (empty($id_sba05) ? 'true' : 'false') . 
                         ', equals_empty_string=' . ($id_sba05 === '' ? 'true' : 'false') . 
                         ', equals_undefined=' . ($id_sba05 === 'undefined' ? 'true' : 'false') . 
                         ', equals_null=' . ($id_sba05 === 'null' ? 'true' : 'false'));
                error_log('[CRITICAL ERROR] Todas las claves de $_POST: ' . implode(', ', array_keys($_POST)));
                error_log('[CRITICAL ERROR] ¿Existe $_POST[id_sba05]? ' . (isset($_POST['id_sba05']) ? 'SI' : 'NO'));
                throw new Exception($error);
            }
            
            if (empty($fecha) || $fecha === '' || $fecha === 'undefined' || $fecha === 'null') {
                $error = 'Campo requerido: fecha';
                error_log('[CRITICAL ERROR] ' . $error);
                error_log('[CRITICAL ERROR] Valor exacto de fecha: "' . $fecha . '"');
                throw new Exception($error);
            }
            
            if (empty($observaciones) || $observaciones === '' || $observaciones === 'undefined' || $observaciones === 'null') {
                $error = 'Campo requerido: observaciones';
                error_log('[CRITICAL ERROR] ' . $error);
                error_log('[CRITICAL ERROR] Valor exacto de observaciones: "' . $observaciones . '"');
                throw new Exception($error);
            }
            
            error_log('[ULTRA DEBUG] ✅ Todas las validaciones pasadas. Campos válidos:');
            error_log('  ✅ id_sba05: "' . $id_sba05 . '"');
            error_log('  ✅ fecha: "' . $fecha . '"');
            error_log('  ✅ observaciones: "' . $observaciones . '"');
            error_log('[ULTRA DEBUG] Llamando a marcarRecibidoTesoreria...');
            
            $resultado = $ingreso->marcarRecibidoTesoreria(
                $id_sba05,
                $fecha,
                $cod_comp,
                $n_comp,
                $observaciones,
                (float)$importe
            );
            
            if ($resultado) {
                error_log('[ULTRA DEBUG] ✅ marcarRecibidoTesoreria exitoso');
                echo json_encode([
                    'success' => true,
                    'message' => 'Ingreso TESORERÍA marcado como recibido'
                ]);
            } else {
                error_log('[ULTRA DEBUG] ❌ marcarRecibidoTesoreria falló');
                throw new Exception('Error al marcar el ingreso TESORERÍA');
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