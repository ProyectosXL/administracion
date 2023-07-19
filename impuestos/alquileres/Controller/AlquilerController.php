<?php 

$accion = isset($_GET['accion']) ? $_GET['accion'] : "";

switch ($accion) {

    case 'insertarDetalle':
        insertarDetalle(); 
        break;

    case 'actualizarDetalle':
        actualizarDetalle(); 
        break;

    case 'procesar':
        execSpAlquileres(); 
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

                                            if(in_array($v['NRO_SUCURSAL'],["02","16","60","79","81"]) && $value['ID_CA'] == "14"){

                                                $total = ($newArray[$v['NRO_SUCURSAL']]["Porc. S/ventas netas"] - $newArray[$v['NRO_SUCURSAL']]["Valor minimo mensual"]) * $porcentaje['PORCENTAJE'] / 100;
        
                                            }
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

function traerDetalleAlquiler ($fecha,$periodo) {

    require_once "Class/Alquiler.php";
    require_once "../../controlSucursales/Class/sucursal.php";


    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $traerPorcentajes = $alquiler->traerTodosLosPorcentajes();

    $conceptos = $alquiler->traerConceptos();
    $todosLosLocales= $sucursal->traerLocales();

    $rentabilidadNeta = $alquiler->traerRentabilidadNeta($fecha);
    $rentabilidadBruta = $alquiler->traerRentabilidadBruta($periodo); 


    $detalle = $alquiler->traerDetalle($periodo);

    $newArray = [];

    
    foreach ($todosLosLocales as $k => $v) {

        $newArray[$v['NRO_SUCURSAL']] = [];

        foreach ($conceptos as $key => $value) {  
            $total = 0;

            $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = 0;

            if($value['carga_manual'] != 1){

                if( in_array($value['ID_CA'], ["9", "13"]) ) {

                    foreach ($traerPorcentajes as $porcentaje ) {

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

                                    if(in_array($v['NRO_SUCURSAL'],["02","16","60","79","81"]) && $value['ID_CA'] == "14"){
                                        // $total = ($newArray[$v['NRO_SUCURSAL']]["Porc. S/ventas netas"] - $newArray[$v['NRO_SUCURSAL']]["Valor minimo mensual"]) * $porcentaje['PORCENTAJE'] / 100;
                                        $total = $porcentaje['PORCENTAJE'] ;
                                    }
                                }
                            }   

                        }

                    }
                }

                $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = $total;  

            }else{

                foreach ($detalle as $det) {

                    if($det['NRO_SUCURS'] == $v['NRO_SUCURSAL'] && $det['ID_CA'] == $value['ID_CA']) {

                        
                        $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = $det['IMPORTE_PARSE'];

                        break;
                
      
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

function consultarMesesDetalle ($periodoPasado, $periodo) {
    
    require_once "Class/Alquiler.php";
    require_once "../../controlSucursales/Class/sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $conceptos = $alquiler->traerConceptos();
    $detalles = $alquiler->consultarMesesDetalle($periodoPasado, $periodo);
    
    $locales = $sucursal->traerLocales();

    $newArray = [];
    $periodoActual = "";
    $sucursalActual = "";
    foreach ($conceptos as $concepto) {

        $newArray[$concepto['ID_CA']] = [] ;
        $total = 0;
        foreach ($detalles as $key => $detalle) {

            if($detalle['ID_CA'] == $concepto['ID_CA']){

                if($detalle['PERIODO'] != $periodoActual){
                    $total = 0;
                }

                $newArray[$concepto['ID_CA']][$detalle['PERIODO']] = [];
    
                $total += (int)$detalle['IMPORTE'];
                $newArray[$concepto['ID_CA']][$detalle['PERIODO']]['TOTAL'] = "";
                $newArray[$concepto['ID_CA']][$detalle['PERIODO']]['TOTAL'] = $total;
 
                $periodoActual = $detalle['PERIODO'];

            }

        }

 

    }


    return $newArray;

}

function traerArrayPeriodo () {
    $fechaActual = date('Y-m-d'); // Obtiene la fecha actual en el formato "Año-Mes-Día"
    $fechaHaceUnAnio = date('Y-m-d', strtotime('-1 year', strtotime($fechaActual)));

    $hoy = new DateTime($fechaActual);
    $haceUnAnio = new DateTime($fechaHaceUnAnio);

    $fecha = $hoy;

    $interval = $hoy->diff($haceUnAnio);
    $interval = $interval->format('%a');

    $dates = [];

    for($i = $interval; $i > 0; $i--){

    $fecha = $fecha->sub(new DateInterval('P1D'));
    $periodo = substr($fecha->format('Y-m-d'), 0, 7);

    if(!in_array($periodo, $dates)){
        $dates[] = $periodo;
    }
    }
    foreach ($dates as $key => &$value) {
        $valor = explode("-", $value);
        $value = (int)$valor[1]."-".$valor[0];
        
    }

    return ($dates);
}

function traerDetalleHaceUnAño ($periodoPasado, $now) {
    
    require_once "Class/Alquiler.php";
    require_once "../../controlSucursales/Class/sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $detalles = $alquiler->consultarMesesDetalle ($periodoPasado, $now);

    return $detalles;
}


function execSpAlquileres () {

    require_once "../Class/Alquiler.php";
    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];

    $detalles = $alquiler->execSpAlquileres($periodo);

    return $detalles;

}
?>