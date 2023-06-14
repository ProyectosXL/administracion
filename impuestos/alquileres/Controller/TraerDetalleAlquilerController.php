<?php 
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

            if($value['ID_CA'] == 9 || $value['ID_CA'] == 13 ) {

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


?>