
<?php

class Alquiler
{
    function __construct(){
        require_once __DIR__.'/../../../Class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 

    public function traerConceptos()
    {

  
        $sql = "SELECT * FROM RO_T_CONCEPTOS_ALQUILERES";

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
    public function traerConceptosPorcentaje()
    {

  
        $sql = "SELECT ID_CA,CONCEPTO FROM RO_T_CONCEPTOS_ALQUILERES WHERE ES_PORCENTAJE = 1";

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
    public function traerPorcentajeSucursal($concepto)
    {

  
        $sql = "SELECT * FROM RO_T_PORC_CONCEPTOS_ALQUILERES WHERE ID_CA = '$concepto' ORDER BY NRO_SUCURS DESC";

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
    public function insertarPorcentaje($idConcepto, $idLocal, $descLocal)
    {

 
        $sql = "INSERT INTO RO_T_PORC_CONCEPTOS_ALQUILERES(ID_CA, NRO_SUCURS, DESC_SUCURS)  
        SELECT '$idConcepto', '$idLocal', '$descLocal' 
        WHERE NOT EXISTS(SELECT 1 FROM RO_T_PORC_CONCEPTOS_ALQUILERES WHERE NRO_SUCURS = '$idLocal' AND ID_CA = '$idConcepto');";

        try{
        $stmt = sqlsrv_query($this->cid_central, $sql);
        $rowsAffected = sqlsrv_rows_affected($stmt);

        return $rowsAffected;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }
    public function actualizarPorcentaje($id, $porcentaje)
    {
        $sql = "UPDATE RO_T_PORC_CONCEPTOS_ALQUILERES SET PORCENTAJE = '$porcentaje' WHERE ID_PA = '$id'";
        try{
        $stmt = sqlsrv_query($this->cid_central, $sql);
        return true;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }
    public function eliminarPorcentaje($id)
    {
        $sql = "DELETE FROM RO_T_PORC_CONCEPTOS_ALQUILERES WHERE ID_PA = '$id'";

        try{
            
            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;
            
        } catch (\Throwable $th){
            print_r($th);
        }

    }
    public function traerTodosLosPorcentajes()
    {
        $sql = " SELECT * FROM  RO_T_PORC_CONCEPTOS_ALQUILERES ";
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
    public function traerRentabilidadNeta($periodo)
    {
        $sql = "SELECT NRO_SUCURS, VENTA FROM RO_T_RENTABILIDAD_BRUTA WHERE FECHA LIKE  '%$periodo%'";

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
    public function traerRentabilidadBruta($periodo)
    {
        $sql = "SELECT * FROM RO_V_VENTAS_BRUTAS_IE WHERE PERIODO LIKE '%$periodo%'";

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

    public function conteoDetalle($periodo)
    {
        $sql = "SELECT count(*) CONTEO FROM RO_T_DETALLE_ALQUILERES WHERE PERIODO LIKE '%$periodo%'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try{
            
           $v = sqlsrv_fetch_array($stmt);
            return $v;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

    public function traerDetalle($periodo)
    {
        $sql = "SELECT *, CAST(IMPORTE AS FLOAT) IMPORTE_PARSE FROM RO_T_DETALLE_ALQUILERES WHERE PERIODO LIKE '%$periodo%'";

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
    public function insertarDetalle($data)
    {
        $sql = "INSERT INTO RO_T_DETALLE_ALQUILERES (PERIODO,NRO_SUCURS,DESC_SUCURS,IMPORTE,ID_CA) VALUES ".$data;

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
    public function actualizarDetalle($periodo, $idSucursal, $idConcepto, $importe, $userName, $porcentaje )
    {
        $sql = "UPDATE RO_T_DETALLE_ALQUILERES SET IMPORTE = '$importe', USUARIO = '$userName', FECHA_MODIF = GETDATE(), PORCENTAJE_APLICADO = '$porcentaje' WHERE PERIODO = '$periodo' AND NRO_SUCURS = '$idSucursal' AND ID_CA = '$idConcepto'";

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

    function consultarMesesDetalle ($periodoPasado, $periodo)
    {
        
        $sql = "SELECT C.ID_CA,C.NRO_SUCURS,C.DESC_SUCURS,C.IMPORTE,C.PERIODO FROM (
            SELECT *,REVERSE(REPLACE(b.CAMPO,'-','') ) P from (
                SELECT *,
                     (CASE 
                        WHEN A.PERIODO  NOT LIKE '__-%' THEN REPLACE(A.PERIODO, '-', '0-')
                        WHEN A.PERIODO LIKE '10-%' THEN REPLACE(A.PERIODO, '10-', '01-')
                        ELSE A.PERIODO
                    END) CAMPO 
                FROM RO_T_DETALLE_ALQUILERES  A ) 
            b) 
        C where C.P BETWEEN '$periodoPasado' AND '$periodo' ;";

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

    function execSpAlquileres ($periodo) 
    {
        $sql = " EXEC RO_SP_INTEGRAL_ALQUILERES '$periodo';";
 
        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);

            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
            if(isset($rows[0][0])){

                if($rows[0][0] == 'ERROR') {
                    
                    echo 1;
                    
                }

            } else {
                
                echo 0;

            }
           
            
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function verificarProcesado ($fecha) 
    {
        $sql = "SELECT CASE
        WHEN EXISTS (
            SELECT 1
            FROM RO_T_INTEGRAL_TANGO_2
            WHERE MODULO = 'ALQUILERES'
            AND FECHA = '$fecha'
        ) THEN 1
        ELSE 0
        END AS RegistroExiste;";
        
        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
                    
            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }

    }

    function cerrarPeriodo ($periodo) 
    {
        $sql = "INSERT INTO RO_T_DETALLE_ALQUILERES_ESTADO (PERIODO, ESTADO)
        SELECT '$periodo', 1
        WHERE NOT EXISTS (
            SELECT 1
            FROM RO_T_DETALLE_ALQUILERES_ESTADO
            WHERE PERIODO = '$periodo'
        );";

        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;
            
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function abrirPeriodo ($periodo) 
    {
        $sql = "DELETE FROM RO_T_DETALLE_ALQUILERES_ESTADO WHERE PERIODO = '$periodo';";

        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;
            
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function checkCierrePeriodoAnt ($mesAnterior) 
    {
        $sql = "SELECT CASE
        WHEN EXISTS (
            SELECT 1
            FROM RO_T_DETALLE_ALQUILERES_ESTADO  
            WHERE PERIODO = '$mesAnterior'
        ) THEN 1
        ELSE 0
        END AS RegistroExiste;";
        
        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
                    
            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }

    }
    
    function traerEstado ($periodo) 
    {
        $sql = "SELECT 1
        FROM RO_T_DETALLE_ALQUILERES_ESTADO
        WHERE PERIODO = '$periodo';";
        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);
            if (sqlsrv_has_rows($stmt)) {

                return 1;

            } else {

               return 0;

            }
            
        } catch (\Throwable $th) {
            throw $th;
        }

    }
}


