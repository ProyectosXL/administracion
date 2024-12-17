
<?php
require_once '../Class/gasto.php';

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

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
    default:
        echo json_encode(['error' => 'Acción no reconocida']);
        break;
}

function subirFotos() {
    $gasto = new Gasto();
    $codComp = $_POST['codComp'];
    $nComp = $_POST['nComp'];
    $codCta = $_POST['codCta'];
    $fotos = $_FILES['fotos'];

    $resultado = $gasto->subirFotos($codComp, $nComp, $codCta, $fotos);
    echo json_encode($resultado);
}

function obtenerFotos() {
    $gasto = new Gasto();
    $codComp = $_GET['codComp'];
    $nComp = $_GET['nComp'];
    $codCta = $_GET['codCta'];

    $fotos = $gasto->obtenerFotos($codComp, $nComp, $codCta);
    echo json_encode($fotos);
}

function eliminarFoto() {
    $gasto = new Gasto();
    $foto = $_POST['foto'];
    $codComp = $_POST['codComp'];
    $nComp = $_POST['nComp'];

    $resultado = $gasto->eliminarFoto($foto, $codComp, $nComp);
    echo json_encode($resultado);
}

function verificarEstado() {
    $gasto = new Gasto();
    $codComp = $_GET['codComp'];
    $nComp = $_GET['nComp'];
    $codCta = $_GET['codCta'];
    
    $resultado = $gasto->verificarEstado($codComp, $nComp, $codCta);
    echo json_encode($resultado);
}


function guardarRegistro() {
    $gasto = new Gasto();
    $codComp = $_POST['codComp'];
    $nComp = $_POST['nComp'];
    $codCta = $_POST['codCta'];
    
    $resultado = $gasto->guardarRegistro($codComp, $nComp, $codCta);
    
    if (!$resultado['success'] && isset($resultado['error'])) {
        error_log("Error al guardar registro: " . print_r($resultado['error'], true));
    }
    
    echo json_encode($resultado);
}

function obtenerFotosYEstado() {
    $gasto = new Gasto();
    $codComp = $_GET['codComp'];
    $nComp = $_GET['nComp'];
    $codCta = $_GET['codCta'];

    $archivos = $gasto->obtenerFotos($codComp, $nComp, $codCta);
    $estaGuardado = $gasto->verificarRegistroGuardado($codComp, $nComp, $codCta);

    echo json_encode([
        'archivos' => $archivos,
        'estaGuardado' => $estaGuardado
    ]);
}

?>