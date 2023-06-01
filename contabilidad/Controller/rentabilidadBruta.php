<?php 

switch ($_GET['accion']) {
    case 'traerRentabilidad':
        traerRentabilidad();
        break;

    case 'actualizarValor':
        actualizarValor();
        break;

    case 'controlarRentabilidad':
        controlarRentabilidad();
        break;
    
    default:
       
        break;
}



function traerRentabilidad(){

    require_once "../Class/gasto.php";
    $gasto = new Gasto();

    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    
    $result = $gasto -> traerRentabilidadBruta($desde, $hasta);
    
    echo json_encode($result);
    
}


function actualizarValor(){

    require_once "../Class/gasto.php";
    $gasto = new Gasto();

    $id = $_POST['id'];
    $nuevoValor = $_POST['nuevoValor'];
    
    $result = $gasto -> actualizarValor($id, $nuevoValor);
    
    echo json_encode($result);

}



function controlarRentabilidad(){

    require_once "../Class/gasto.php";
    $gasto = new Gasto();

    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    
    $result = $gasto -> marcarRentabilidadControlada($desde, $hasta);
    
    echo json_encode($result);

}

?>



