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
    
    case 'verificarConexionLocal':
        verificarConexion();
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

    $conn = $sucursal->getConexion();
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error crítico de conexión a la base de datos.']);
        return;
    }
    if (sqlsrv_begin_transaction($conn) === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo iniciar la transacción: '. print_r(sqlsrv_errors(), true)]);
        return;
    }

    try {
        $datos = $_POST['datos'] ?? null;
        $firma = $_POST['firma'] ?? null;
        $remitos = $_POST['remitos'] ?? [];
        $nroSucursal = $_POST['nroSucursal'] ?? null;
        $estado = $_POST['estado'] ?? null;

        if ($estado == 2) {
            if (empty($datos) || empty($firma)) {
                throw new Exception('Faltan datos esenciales (encabezado o firma) para completar el registro.');
            }
            if (isset($datos['enviaValores']) && $datos['enviaValores'] === 'SI') {
                if (!isset($datos['egresos']) || empty($datos['egresos'])) {
                    throw new Exception('Para registrar una guía con "Envío de Valores", es obligatorio agregar al menos un egreso (RAF).');
                }
            }
        }

        $existingRecord = $sucursal->checkExistingRetiro($datos['numeroRegistro'], $nroSucursal);
        if ($existingRecord) {
            throw new Exception('Ya existe un registro con este número para esta sucursal. Por favor, refresque la página.');
        }
        
        $resultado = $sucursal->insertarEncabezadoGuiaRetiro($datos, $nroSucursal, $firma, $estado);
        if (!$resultado['success']) {
            
            throw new Exception('Error al guardar el encabezado del registro: ' . ($resultado['message'] ?? 'Error desconocido'));
        }

        
        if (!empty($remitos)) {
            
            $sucursal->insertarMultiplesRemitos($datos['numeroRegistro'], $remitos, $nroSucursal);
        }

        
        if (isset($datos['egresos']) && !empty($datos['egresos'])) {
            
            $gasto->insertarMultiplesEgresos($datos['numeroRegistro'], $datos['egresos'], $nroSucursal);
        }

        
        sqlsrv_commit($conn);

        
        $message = ($estado == 1) ? 'Borrador guardado correctamente' : 'Guía registrada correctamente';
        echo json_encode(['success' => true, 'message' => $message]);

    } catch (Exception $e) {
        
        sqlsrv_rollback($conn);
        
        
        error_log("Error en registrarRetiro: " . $e->getMessage());
        
        
        http_response_code(500); 
        echo json_encode(['success' => false, 'message' => 'Se produjo un error y la operación fue revertida: ' . $e->getMessage()]);
    }
}

function verificarConexion() {
    require_once '../../Class/conexion.php';
    
    $nroSucursal = $_GET['nroSucurs'] ?? $_SESSION['numsuc'] ?? null;

    if (!$nroSucursal) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se especificó un número de sucursal.']);
        return;
    }

    try {
        $conexion = new Conexion();
        
        if ($conexion->setearDnsBaseName($nroSucursal)) {
            $conn_local = $conexion->conectar('');
            
            if ($conn_local) {
                sqlsrv_close($conn_local);
                echo json_encode(['success' => true, 'message' => 'Conexión con el local establecida.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la base de datos del local. Verifique que el local esté online y accesible en la red.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró la configuración de conexión para la sucursal ' . $nroSucursal]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log("Error en verificarConexion: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno en el servidor al verificar la conexión.']);
    }
}

function actualizarRetiro() {
    global $sucursal, $gasto;

    $conn = $sucursal->getConexion();
    if (!$conn || sqlsrv_begin_transaction($conn) === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: No se pudo iniciar la transacción.']);
        return;
    }

    try {
        // 1. Extraer todos los datos del POST
        $datos = $_POST['datos'] ?? null;
        $firma = $_POST['firma'] ?? null;
        $remitos = $_POST['remitos'] ?? [];
        $nroSucursal = $_POST['nroSucursal'] ?? null;
        $estado = $_POST['estado'] ?? null;
        
        // 2. Extraer los egresos que vienen DENTRO del array 'datos'
        $egresos = $datos['egresos'] ?? []; 
        
        if (empty($datos) || empty($nroSucursal) || empty($estado)) {
            throw new Exception('Faltan datos esenciales para la actualización.');
        }

        // 3. Actualizar el encabezado (esta función ya sabe buscar el precinto dentro de $datos)
        $sucursal->actualizarEncabezadoGuiaRetiro($datos, $nroSucursal, $firma, $estado);

        // 4. Limpiar los detalles antiguos para reemplazarlos
        $sucursal->limpiarRemitos($datos['numeroRegistro'], $nroSucursal);
        $gasto->limpiarEgresos($datos['numeroRegistro'], $nroSucursal);
        
        // 5. Insertar los nuevos detalles que llegaron del formulario
        if (!empty($remitos)) {
            $sucursal->insertarMultiplesRemitos($datos['numeroRegistro'], $remitos, $nroSucursal);
        }

        if (!empty($egresos)) {
            $gasto->insertarMultiplesEgresos($datos['numeroRegistro'], $egresos, $nroSucursal);
        }

        // 6. Si todo salió bien, confirmar los cambios
        sqlsrv_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);

    } catch (Exception $e) {
        // Si algo falló en cualquier punto, deshacer todo
        sqlsrv_rollback($conn);
        error_log("Error en actualizarRetiro: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar y la operación fue revertida: ' . $e->getMessage()]);
    }
}