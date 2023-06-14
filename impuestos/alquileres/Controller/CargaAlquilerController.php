<?php 
    require_once "Class/Alquiler.php";
    require_once "../../controlSucursales/Class/sucursal.php";


    $alquiler = new Alquiler();
    $sucursal = new Sucursal();
    $conceptos = $alquiler->traerConceptos();
    $todosLosLocales= $sucursal->traerLocales();

    $traerPorcentajes = $alquiler->traerTodosLosPorcentajes();

    $rentabilidadNeta = $alquiler->traerRentabilidadNeta("2023-01");
    $rentabilidadBruta = $alquiler->traerRentabilidadBruta("10-2022"); //ver de unificar los formatos de fecha
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



    

?>