<?php
session_start();
header('Content-Type: application/json');
require_once '../Class/sucursal.php';
require_once '../Class/gasto.php';

$accion = $_GET['accion'] ?? '';

$sucursal = new Sucursal();
$gasto = new Gasto();

// Función auxiliar para limpiar nombres (reutilizada de la vista)
function limpiarNombre($nombre) {
    return trim(str_replace(array("\r", "\n", "<br>", "<br/>", "<br />"), ' ', $nombre));
}

switch ($accion) {
    // --- ACCIONES NUEVAS PARA CARGA DINÁMICA (AJAX) ---
    case 'listarUsuarios':
        try {
            $usuarios = $sucursal->listarUsuarios();
            $data = [];
            foreach ($usuarios as $v) {
                $nombre = limpiarNombre($v['NOMBRE_VEN']);
                $bloque = limpiarNombre($v['BLOQUE']);
                $data[] = [
                    'NOMBRE_VEN' => htmlspecialchars($nombre),
                    'VALOR_COMPLETO' => htmlspecialchars($nombre . '++' . $bloque)
                ];
            }
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
        break;

    case 'listarFleteros':
        try {
            $fleteros = $sucursal->listarFleteros();
            $data = [];
            foreach ($fleteros as $f) {
                $data[] = ['NOMBRE_APELLIDO' => htmlspecialchars(trim($f['NOMBRE_APELLIDO']))];
            }
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
        break;

    case 'listarRemitos':
        try {
            $nroSucurs = $_GET['nroSucurs'] ?? $_SESSION['numsuc'] ?? '2';
            $remitos = $sucursal->listarRemitos($nroSucurs);
            $data = [];
            foreach ($remitos as $remito) {
                $valor = json_encode([
                    'remito' => $remito['REMITO'], 'destino' => $remito['DESTINO'],
                    'fecha' => $remito['FECHA'], 't_comp' => $remito['T_COMP']
                ]);
                $display = htmlspecialchars($remito['REMITO'] . ' - ' . $remito['DESTINO'] . ' (' . $remito['FECHA'] . ')');
                $data[] = ['DISPLAY' => $display, 'VALOR_JSON' => $valor];
            }
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
        break;

    case 'listarEgresos':
        try {
            $nroSucurs = $_GET['nroSucurs'] ?? $_SESSION['numsuc'] ?? '2';
            $egresos = $gasto->listarEgresosEfectivo($nroSucurs);
            $data = [];
            foreach ($egresos as $egreso) {
                $valor = json_encode([
                    'comprobante' => $egreso['N_COMP'], 'tipo' => $egreso['COD_COMP'], 'fecha' => $egreso['FECHA']
                ]);
                $display = htmlspecialchars($egreso['COD_COMP'] . ' - ' . $egreso['N_COMP'] . ' (' . $egreso['FECHA'] . ')');
                $data[] = ['DISPLAY' => $display, 'VALOR_JSON' => $valor];
            }
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
        break;
    
    // --- ACCIONES ORIGINALES ---
    case 'registrar':
        registrarRetiro();
        break;

    case 'actualizar':
        actualizarRetiro();
        break;

    case 'traerDatos': 
        $numeroRegistro = $_GET['numeroRegistro'] ?? '';
        if (empty($numeroRegistro)) {
            echo json_encode(['success' => false, 'message' => 'Número de registro no proporcionado.']);
            exit;
        }

        $datos = $sucursal->traerDatosGuiaRetiro($numeroRegistro);

        if (!empty($datos)) {
            echo json_encode(['success' => true, 'data' => $datos[0]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontraron datos para el número de registro proporcionado.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function registrarRetiro() {
    global $sucursal, $gasto;

    // IMPORTANTE: Debes implementar un método en tu clase `Sucursal` o `Conexion` 
    // para obtener la conexión a la DB y así poder manejar la transacción.
    // Ejemplo: $conn = $sucursal->getConexion();
    $conn = $sucursal->getConexion(); // Asumiendo que este método existe
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error crítico de conexión a la base de datos.']);
        return;
    }

    if (sqlsrv_begin_transaction($conn) === false) {
        http_response_code(500);
        // Usar die() puede cortar la ejecución abruptamente, mejor es un echo + return
        echo json_encode(['success' => false, 'message' => 'No se pudo iniciar la transacción. '. print_r(sqlsrv_errors(), true)]);
        return;
    }

    try {
        $datos = $_POST['datos'] ?? null;
        $firma = $_POST['firma'] ?? null;
        $remitos = $_POST['remitos'] ?? [];
        $nroSucursal = $_POST['nroSucursal'] ?? null;
        $estado = $_POST['estado'] ?? null;
   
        // Validación más estricta de los datos necesarios
        if ($estado != 1 && (empty($datos) || ($estado == 2 && empty($firma)))) {
            throw new Exception('Faltan datos esenciales para completar el registro.');
        }

        $existingRecord = $sucursal->checkExistingRetiro($datos['numeroRegistro'], $nroSucursal);
        if ($existingRecord) {
            throw new Exception('Ya existe un registro con este número para esta sucursal.');
        }

        $resultado = $sucursal->insertarEncabezadoGuiaRetiro($datos, $nroSucursal, $firma, $estado);
        if (!$resultado['success']) {
            throw new Exception('Error al guardar el encabezado del registro: ' . ($resultado['message'] ?? 'Error desconocido'));
        }

        if (!empty($remitos)) {
            $sucursal->limpiarRemitos($datos['numeroRegistro'], $nroSucursal);
            // ¡IMPORTANTE! Debes crear este método `insertarMultiplesRemitos` en tu clase `Sucursal`.
            $sucursal->insertarMultiplesRemitos($datos['numeroRegistro'], $remitos, $nroSucursal);
        }

        if (isset($datos['egresos']) && !empty($datos['egresos'])) {
            $gasto->limpiarEgresos($datos['numeroRegistro'], $nroSucursal);
            // ¡IMPORTANTE! Debes añadir el método `insertarMultiplesEgresos` en tu clase `Gasto`.
            $gasto->insertarMultiplesEgresos($datos['numeroRegistro'], $datos['egresos'], $nroSucursal);
        }

        // Si todo salió bien, confirmamos la transacción
        sqlsrv_commit($conn);
        $message = ($estado == 1) ? 'Borrador guardado correctamente' : 'Registro guardado correctamente';
        echo json_encode(['success' => true, 'message' => $message]);

    } catch (Exception $e) {
        // Si algo falla, revertimos todos los cambios
        sqlsrv_rollback($conn);
        error_log("Error en registrarRetiro: " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(['success' => false, 'message' => 'Se produjo un error y la operación fue revertida: ' . $e->getMessage()]);
    }
}

function actualizarRetiro() {
    // Tomamos el código original que proporcionaste, ya que no solicitaste refactorizarlo,
    // pero se mantiene la recomendación de usar transacciones aquí también para mayor seguridad.
    global $sucursal, $gasto;

    try {
        $datos = $_POST['datos'] ?? null;
        $firma = $_POST['firma'] ?? null;
        $remitos = $_POST['remitos'] ?? [];
        $nroSucursal = $_POST['nroSucursal'] ?? null;
        $estado = $_POST['estado'] ?? null;
        
        if ($estado != 1 && (empty($datos) || ($estado == 2 && empty($firma)))) {
            echo json_encode(['success' => false, 'message' => 'Datos no proporcionados.']);
            exit;
        }

        $resultado = $sucursal->actualizarEncabezadoGuiaRetiro($datos, $nroSucursal, $firma, $estado);

        if (!empty($remitos)) {
            $sucursal->limpiarRemitos($datos['numeroRegistro'], $nroSucursal);
            // RECOMENDACIÓN: Usar también un método de inserción múltiple aquí.
            foreach ($remitos as $remito) {    
                $sucursal->insertarRemitos($datos['numeroRegistro'], $remito['fecha'], $remito['remito'], $remito['destino'], $remito['bultos'], $nroSucursal);
            }
        }

        if (isset($datos['egresos']) && !empty($datos['egresos'])) {
            $gasto->limpiarEgresos($datos['numeroRegistro'], $nroSucursal);
            // RECOMENDACIÓN: Usar también un método de inserción múltiple aquí.
            foreach ($datos['egresos'] as $egreso) {
                $gasto->insertarEgresos($datos['numeroRegistro'], $egreso['fecha'], $egreso['tipo'], $egreso['comprobante'], $nroSucursal);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);

    } catch (Exception $e) {
        error_log("Error en actualizarRetiro: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
    }
}