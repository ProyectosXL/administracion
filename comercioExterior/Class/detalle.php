<?php

class Detalle{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    }
     
    public function insertarDetalle($datosDetalle,$idCabezera){   
        foreach ($datosDetalle as $dato) {

            $sql = "INSERT INTO RO_T_IMPORTACIONES_DETALLE(ID_MG, IMPORTE_U\$S, TIPO_CAMBIO, IMPORTE_$, PORCENTAJE, OBSERVACIONES, FECHA_MOD,GASTOS)
                VALUES ('".$idCabezera."','".$dato['importeEnDolares']."','".$dato['tipoCambio']."','".$dato['importeEnPesos']."','".$dato['sobreFob']."','".$dato['observaciones']."',GETDATE(),'".$dato['Gastos']."')
            ;";

            try {
                
                $stmt = sqlsrv_query($this->cid_central, $sql);

            } catch (Exception $e) {
    
                echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        
            }
        }


    }  
    public function editarDetalle($datosDetalle){   
        foreach ($datosDetalle as $dato) {

            $sql = " UPDATE RO_T_IMPORTACIONES_DETALLE SET 
                [IMPORTE_U\$S] = '".$dato['importeEnDolares']."', 
                TIPO_CAMBIO ='".$dato['tipoCambio']."', 
                IMPORTE_$ = '".$dato['importeEnPesos']."' , 
                PORCENTAJE = '".$dato['sobreFob']."', 
                OBSERVACIONES = '".$dato['observaciones']."', 
                FECHA_MOD = GETDATE(),
                GASTOS = '".$dato['Gastos']."'
                WHERE ID_MG ='".$dato['idEncabezado']."' AND ID ='".$dato['idDetalle']."'";

            try {
                
                $stmt = sqlsrv_query($this->cid_central, $sql);
                
            } catch (Exception $e) {
                
                echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        
            }
        }
        // var_dump($datosDetalle);
        // die();



    }  

}