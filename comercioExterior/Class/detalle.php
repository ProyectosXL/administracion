<?php

class Detalle{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    }
     
    public function insertarDetalle($datosDetalle){   
        
        $sql = "INSERT INTO RO_T_IMPORTACIONES_DETALLE(ID_MG, IMPORTE_U\$S, TIPO_CAMBIO, IMPORTE_$, PORCENTAJE, OBSERVACIONES, FECHA_MOD)
            VALUES ('".$datosDetalle['idEncabezado']."','".$datosDetalle['importeEnDolares']."','".$datosDetalle['tipoCambio']."','".$datosDetalle['importeEnPesos']."','".$datosDetalle['porcentaje']."','',GETDATE())
        ;";

        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);

        } catch (Exception $e) {

            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
    
        }

    }  

}