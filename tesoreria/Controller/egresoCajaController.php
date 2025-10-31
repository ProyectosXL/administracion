<?php
// Configurar el manejo de errores para evitar que aparezcan en la respuesta JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../Class/gasto.php';

// CORRECCIÓN: Usamos $_REQUEST para que la acción pueda venir por GET o POST
$accion = isset($_REQUEST['accion']) ? $_REQUEST['accion'] : '';

// Función para enviar respuesta JSON limpia
function enviarRespuestaJSON($data) {
    header('Content-Type: application/json');
    ob_clean(); // Limpiar cualquier salida previa
    echo json_encode($data);
    exit;
}

switch ($accion) {
    case 'subirFotos':
        subirFotos();
        break;
    case 'obtenerFotos':
        obtenerFotos();
        break;
    case 'eliminarFoto':
        eliminarFoto();
        break;
    case 'verificarEstado':
        verificarEstado();
        break;
    case 'guardarRegistro':
        guardarRegistro();
        break;
    case 'obtenerFotosYEstado':
        obtenerFotosYEstado();
        break;

    // ===== NUEVO CASE AÑADIDO =====
    case 'eliminarGasto':
        eliminarGasto();
        break;
    // ===============================

    default:
        enviarRespuestaJSON(['error' => 'Acción no reconocida']);
        break;
}

function subirFotos() {
    try {
        if (!isset($_POST['codComp']) || !isset($_POST['nComp']) || !isset($_POST['codCta'])) {
            enviarRespuestaJSON(['success' => false, 'error' => 'Faltan parámetros requeridos']);
            return;
        }

        if (!isset($_FILES['fotos']) || empty($_FILES['fotos']['tmp_name'])) {
            enviarRespuestaJSON(['success' => false, 'error' => 'No se recibieron archivos']);
            return;
        }

        $gasto = new Gasto();
        $codComp = $_POST['codComp'];
        $nComp = $_POST['nComp'];
        $codCta = $_POST['codCta'];
        $fotos = $_FILES['fotos'];

        $resultado = $gasto->subirFotos($codComp, $nComp, $codCta, $fotos);
        
        enviarRespuestaJSON($resultado);

    } catch (Exception $e) {
        error_log("Error en subirFotos controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => 'Error interno del servidor: ' . $e->getMessage(),
            'archivos_subidos' => 0
        ]);
    }
}

function obtenerFotos() {
    try {
        if (!isset($_GET['codComp']) || !isset($_GET['nComp']) || !isset($_GET['codCta'])) {
            enviarRespuestaJSON(['error' => 'Faltan parámetros requeridos']);
            return;
        }

        $gasto = new Gasto();
        $codComp = $_GET['codComp'];
        $nComp = $_GET['nComp'];
        $codCta = $_GET['codCta'];

        $fotos = $gasto->obtenerFotos($codComp, $nComp, $codCta);
        enviarRespuestaJSON($fotos);

    } catch (Exception $e) {
        error_log("Error en obtenerFotos controller: " . $e->getMessage());
        enviarRespuestaJSON(['error' => $e->getMessage()]);
    }
}

function eliminarFoto() {
    try {
        if (!isset($_POST['foto']) || !isset($_POST['codComp']) || !isset($_POST['nComp'])) {
            enviarRespuestaJSON(['success' => false, 'error' => 'Faltan parámetros requeridos']);
            return;
        }

        $gasto = new Gasto();
        $foto = $_POST['foto'];
        $codComp = $_POST['codComp'];
        $nComp = $_POST['nComp'];
        
        $codCta = isset($_POST['codCta']) ? $_POST['codCta'] : null;
        if (!$codCta) {
            $partes = explode('_', $foto);
            $codCta = (count($partes) >= 3) ? $partes[2] : null;
        }

        $resultado = $gasto->eliminarFoto($foto, $codComp, $nComp);
        
        if ($resultado['success'] && $codCta) {
            $estado = $gasto->verificarEstado($codComp, $nComp, $codCta);
            $resultado['fotosRestantes'] = $estado['numeroFotos'];
            $resultado['tieneFotos'] = $estado['tieneFotos'];
        }
        
        enviarRespuestaJSON($resultado);

    } catch (Exception $e) {
        error_log("Error en eliminarFoto controller: " . $e->getMessage());
        enviarRespuestaJSON(['success' => false, 'error' => $e->getMessage()]);
    }
}

function verificarEstado() {
    try {
        if (!isset($_GET['codComp']) || !isset($_GET['nComp']) || !isset($_GET['codCta'])) {
            enviarRespuestaJSON(['error' => 'Faltan parámetros requeridos']);
            return;
        }

        $gasto = new Gasto();
        $codComp = $_GET['codComp'];
        $nComp = $_GET['nComp'];
        $codCta = $_GET['codCta'];
        
        $resultado = $gasto->verificarEstado($codComp, $nComp, $codCta);
        enviarRespuestaJSON($resultado);

    } catch (Exception $e) {
        error_log("Error en verificarEstado controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'tieneFotos' => false,
            'estaGuardado' => false,
            'numeroFotos' => 0,
            'error' => $e->getMessage()
        ]);
    }
}

function guardarRegistro() {
    try {
        if (!isset($_POST['codComp']) || !isset($_POST['nComp']) || !isset($_POST['codCta'])) {
            enviarRespuestaJSON(['success' => false, 'error' => 'Faltan parámetros requeridos']);
            return;
        }

        $gasto = new Gasto();
        $codComp = $_POST['codComp'];
        $nComp = $_POST['nComp'];
        $codCta = $_POST['codCta'];
        
        $estado = $gasto->verificarEstado($codComp, $nComp, $codCta);
        
        if (!$estado['tieneFotos']) {
            enviarRespuestaJSON([
                'success' => false,
                'error' => 'No se pueden guardar registros sin fotos válidas'
            ]);
            return;
        }
        
        $resultado = $gasto->guardarRegistro($codComp, $nComp, $codCta);
        
        if (!$resultado['success'] && isset($resultado['error'])) {
            error_log("Error al guardar registro: " . print_r($resultado['error'], true));
        }
        
        enviarRespuestaJSON($resultado);

    } catch (Exception $e) {
        error_log("Error en guardarRegistro controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => 'Error interno del servidor: ' . $e->getMessage()
        ]);
    }
}

function obtenerFotosYEstado() {
    try {
        if (!isset($_GET['codComp']) || !isset($_GET['nComp']) || !isset($_GET['codCta'])) {
            enviarRespuestaJSON(['error' => 'Faltan parámetros requeridos']);
            return;
        }

        $gasto = new Gasto();
        $codComp = $_GET['codComp'];
        $nComp = $_GET['nComp'];
        $codCta = $_GET['codCta'];

        $archivos = $gasto->obtenerFotos($codComp, $nComp, $codCta);
        $estaGuardado = $gasto->verificarRegistroGuardado($codComp, $nComp, $codCta);

        enviarRespuestaJSON([
            'archivos' => $archivos,
            'estaGuardado' => $estaGuardado
        ]);

    } catch (Exception $e) {
        error_log("Error en obtenerFotosYEstado controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'archivos' => [],
            'estaGuardado' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// ===== NUEVA FUNCIÓN AÑADIDA =====
function eliminarGasto() {
    try {
        // Los datos vienen por POST desde el JS
        if (!isset($_POST['codComp']) || !isset($_POST['nComp']) || !isset($_POST['codCta'])) {
            enviarRespuestaJSON(['success' => false, 'error' => 'Faltan parámetros para eliminar el gasto.']);
            return;
        }

        $gasto = new Gasto();
        $codComp = $_POST['codComp'];
        $nComp = $_POST['nComp'];
        $codCta = $_POST['codCta'];

        // Llamamos al método de la clase Gasto que borra las fotos y el registro
        $resultado = $gasto->eliminarGasto($codComp, $nComp, $codCta);

        enviarRespuestaJSON($resultado);

    } catch (Exception $e) {
        error_log("Error en eliminarGasto controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => 'Error interno del servidor al eliminar: ' . $e->getMessage()
        ]);
    }
}
// ===================================
?>