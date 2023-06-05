
<?php

class Sucursal
{
    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 

    public function traerTodosLosMediosDePago()
    {

  
        $sql = "SELECT * FROM RO_T_MEDIOS_DE_PAGO where ACTIVO = '1'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try{
            
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

    public function traerImportesTotales($nroSucursal,$fecha){
 

        $sql = "SELECT * FROM  [LAKERBIS].LOCALES_LAKERS.DBO.RO_T_VENTA_DIARIA_SUCURSALES where nro_sucursal = '$nroSucursal' and FECHA = '$fecha';";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try{
            
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;
        
        } catch (\Throwable $th){
            print_r($th);
        }


    }
    public function traerLocales(){

        $sql = " SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE CANAL = 'PROPIOS' AND HABILITADO = 1 order by NRO_SUCURSAL";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try{
            
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;
        
        } catch (\Throwable $th){
            print_r($th);
        }


    }

    public function actualizarValor ($id,$importeControl,$verificado = null,$observaciones = null){

        $sql = " UPDATE [LAKERBIS].LOCALES_LAKERS.DBO.RO_T_VENTA_DIARIA_SUCURSALES SET IMPORTE_\$_FISICO = '$importeControl', VERIFICADO = $verificado, FECHA_MODIF = GETDATE(), OBSERVACIONES = '$observaciones' WHERE ID = $id";

        try{
            
            $stmt = sqlsrv_query($this->cid_central, $sql);
       
            return $stmt;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }
    public function traerVerificados ($fecha){

        $sql = " SELECT nro_sucursal ,MIN(VERIFICADO) AS STATUS
        FROM  [LAKERBIS].LOCALES_LAKERS.DBO.RO_T_VENTA_DIARIA_SUCURSALES
        WHERE FECHA = '$fecha' GROUP BY NRO_SUCURSAL ";
   
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        try{
            
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;
        
        } catch (\Throwable $th){
            print_r($th);
        }
    }

}


