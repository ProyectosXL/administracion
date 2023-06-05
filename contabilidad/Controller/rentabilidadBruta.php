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
    $observacion = $_POST['observacion'];


    $result = $gasto->actualizarValor($id, $nuevoValor, $observacion);
    
    echo json_encode($result);

}



function controlarRentabilidad(){

    require_once "../Class/gasto.php";
    require_once "../Class/paso.php";
    $gasto = new Gasto();
    $paso = new Paso();
    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    $periodo = $_POST['periodo'];

    $result = $gasto -> marcarRentabilidadControlada($desde, $hasta);
    $paso->marcarControlPaso3($periodo);
    echo json_encode($result);

}

?>



