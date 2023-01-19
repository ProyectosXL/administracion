<?php

class Detalle{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    }
     
    public function insertarDetalle($datosDetalle,$idCabezera){   
        
        $this->deleteDetalle($idCabezera);
        foreach ($datosDetalle as $dato) {


            $sql = "INSERT INTO RO_T_IMPORTACIONES_DETALLE(ID_MG, IMPORTE_U\$S, TIPO_CAMBIO, IMPORTE_$, PORCENTAJE, OBSERVACIONES, FECHA_MOD,GASTOS)
                VALUES ('".$idCabezera."','".$dato['importeEnDolares']."','".$dato['tipoCambio']."','".$dato['importeEnPesos']."','".$dato['sobreFob']."','".$dato['observaciones']."',GETDATE(),'".$dato['gastos']."')
            ;";

    
            try {
                
                $stmt = sqlsrv_query($this->cid_central, $sql);

            } catch (Exception $e) {
    
                echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        
            }
        }


    }  
    public function editarDetalle($datosDetalle){   

        $idCabezera = $datosDetalle[0]['idEncabezado'];
        $this->insertarDetalle($datosDetalle,$idCabezera);

    }  

    public function deleteDetalle($idEncabezado){

        $sql = "DELETE RO_T_IMPORTACIONES_DETALLE WHERE ID_MG ='".$idEncabezado."'";

        try {
                
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
        } catch (Exception $e) {
            
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
    
        }
    }
}