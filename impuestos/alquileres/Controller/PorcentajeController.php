<?php 

switch ($_GET['accion']) {

    case 'actualizarPorcentaje':
        actualizarPorcentaje ();
        break;

    case 'insertarPorcentaje':
        insertarPorcentaje ();
        break;

    case 'eliminarPorcentaje':
        eliminarPorcentaje ();
        break;
    
    default:
        break;

}

function actualizarPorcentaje () {

    require_once '../Class/Alquiler.php';

    $alquiler = new Alquiler();

    $id = $_POST['id'];
    $porcentaje = $_POST['porcentaje'];

    $result = $alquiler->actualizarPorcentaje($id, $porcentaje);
    echo ($result);

}

function insertarPorcentaje () {

    require_once '../Class/Alquiler.php';

    $alquiler = new Alquiler();
    
    $idConcepto = $_POST['idConcepto'];
    $idLocal = $_POST['idLocal'];
    $descLocal = $_POST['descLocal'];
    
    
    $result = $alquiler->insertarPorcentaje($idConcepto, $idLocal, $descLocal);
    echo ($result);
    
    
}
function eliminarPorcentaje () {

    require_once '../Class/Alquiler.php';

    $alquiler = new Alquiler();
    
    $idPorcentaje = $_POST['id'];
    
    
    $result = $alquiler->eliminarPorcentaje($idPorcentaje);

    echo ($result);
    
}


?>