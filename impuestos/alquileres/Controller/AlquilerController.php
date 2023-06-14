<?php 

$accion = isset($_GET['accion']) ? $_GET['accion'] : "";

switch ($accion) {

    case 'insertarDetalle':
        insertarDetalle(); 
        break;

    case 'actualizarDetalle':
        actualizarDetalle(); 
        break;

    default:
      
        break;

}

function insertarDetalle () {

    require_once '../Class/Alquiler.php';

    $alquiler = new Alquiler();

    $data = $_POST['values'];

    $result = $alquiler->insertarDetalle($data);

    return true;


}

function actualizarDetalle () {

    require_once '../Class/Alquiler.php';
    
    $alquiler = new Alquiler();
    
    $periodo = $_POST['periodo'];
    $sucursal = $_POST['sucursal'];
    $concepto = $_POST['concepto'];
    $importe = $_POST['importe'];
    $userName = $_POST['userName'];
    
    
    $result = $alquiler->actualizarDetalle($periodo, $sucursal, $concepto, $importe, $userName);
    
    if($concepto == 8) {
     
        $importe9 = $_POST['importe9'];
        $importe13 = $_POST['importe13'];
    
        $alquiler->actualizarDetalle($periodo, $sucursal, 9, $importe9, $userName);
        $alquiler->actualizarDetalle($periodo, $sucursal, 13, $importe13, $userName);
    
    }
    
    return true;
      
    
}

function cargarAlquieres ($fecha, $periodo) {

    require_once "Class/Alquiler.php";
    require_once "../../controlSucursales/Class/sucursal.php";


    $alquiler = new Alquiler();
    $sucursal = new Sucursal();
    $conceptos = $alquiler->traerConceptos();
    $todosLosLocales= $sucursal->traerLocales();

    $traerPorcentajes = $alquiler->traerTodosLosPorcentajes();
    
    $rentabilidadNeta = $alquiler->traerRentabilidadNeta($fecha);
    $rentabilidadBruta = $alquiler->traerRentabilidadBruta($periodo); 

    $newArray = [];

    foreach ($todosLosLocales as $k => $v) {
        
        $newArray[$v['NRO_SUCURSAL']] = [];
        
        foreach ($conceptos as $key => $value) {    

                $total = 0;

                if($value['carga_manual'] != 1){
                    
                        if( in_array($value['ID_CA'], ["9", "13"]) ) {

                            foreach ($traerPorcentajes as  $porcentaje) {
                   
                                if($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {
                               
                                    $total = $porcentaje['PORCENTAJE'];
                                }
                            }   
                        }

                        if( in_array($value['ID_CA'], ["6", "15", "16"]) ) {

                            foreach ($rentabilidadBruta as $rentabilidad) {

                                if($rentabilidad['NRO_SUCURSAL'] == $v['NRO_SUCURSAL']) {
                                    foreach ($traerPorcentajes as  $porcentaje) {
                                        if($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURSAL']) {
                                            $total = ( ( $rentabilidad['IMPORTE'] * $porcentaje['PORCENTAJE'] ) / 100 ) ;
                                        }
                                    }   
                                }
                                
                            }

                        }

                        if( in_array($value['ID_CA'], ["7", "14", "17"]) ) {

                            foreach ($rentabilidadNeta  as $rentabilidad) {

                                if($rentabilidad['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                                    foreach ($traerPorcentajes as  $porcentaje) {
                                        if($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURS']) {
                                            $total = $rentabilidad['VENTA'] * $porcentaje['PORCENTAJE'] / 100;
                                        }
                                    }   

                                }

                            }
                        }
    

                }

                $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = $total;      
            
            }
        }

    return $newArray;

}

function traerDetalleAlquiler ($periodo) {

    require_once "Class/Alquiler.php";
    require_once "../../controlSucursales/Class/sucursal.php";


    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $traerPorcentajes = $alquiler->traerTodosLosPorcentajes();

    $conceptos = $alquiler->traerConceptos();
    $todosLosLocales= $sucursal->traerLocales();

    $detalle = $alquiler->traerDetalle($periodo);

    $newArray = [];

    
    foreach ($todosLosLocales as $k => $v) {
        
        $newArray[$v['NRO_SUCURSAL']] = [];

        foreach ($conceptos as $key => $value) {  
            
            $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = 0;

            if(in_array($value['ID_CA'], ["9", "13"])) {

                foreach ($traerPorcentajes as $porcentaje ) {

                    if($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {
                        $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = $porcentaje['PORCENTAJE'];
                    }

                }

            }else{
        
                foreach ($detalle as $det) {

                    if($det['NRO_SUCURS'] == $v['NRO_SUCURSAL'] && $det['ID_CA'] == $value['ID_CA']) {
                        $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = (int)$det['IMPORTE'];
                    }
                    
                }  
            }
    
        }
    }
    return $newArray;
}

function traerLocales () {
    
    require_once "../../controlSucursales/Class/sucursal.php";

    $sucursal = new Sucursal();
    $todosLosLocales= $sucursal->traerLocales();

    return $todosLosLocales;
}

function traerConceptos () {
    
    require_once "Class/Alquiler.php";

    $alquiler = new Alquiler();

    $conceptos = $alquiler->traerConceptos();

    return $conceptos;
}
?>