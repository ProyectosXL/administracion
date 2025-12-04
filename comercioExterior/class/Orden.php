<?php
class Orden{

    function __construct() {

        require_once __DIR__ . '/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($db);

    }

    public function listarOrden($idEncabezado = null){
        
        $date = date('Y-m-d', strtotime("-90 days"));
        $sql = "SELECT ID,FECHA_MOV,COD_PROVEE,PROVEEDOR,DESPACHO,ORDEN_COMPRA,VALOR_FOB_PESO FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE FECHA_MOV > '$date' ORDER BY FECHA_MOV DESC";
        if($idEncabezado != null){
            $sql = " SELECT * FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = $idEncabezado ";
        }
     
        try{
        $stmt = sqlsrv_query( $this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }
        return ($rows);
        

        } catch (\Throwable $th) {

        print_r($th);

        }
    }

    public function traerPorOrdenCompra($id) {
        $sql = "SELECT * FROM RO_T_IMPORTACIONES_DETALLE WHERE ID_MG =".$id.";";
        try{
            $stmt = sqlsrv_query( $this->cid_central, $sql );
            $rows = array();
            
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }

            foreach ($rows as $key => &$value) {
                foreach ($value as $k => &$v) {
                    if((gettype($v)== 'string') && substr($v, 0, 1) == '.'){
                        $v = '0'.$v;
                    }
                }
            }

            // print_r($rows);
            // die();

            return ($rows);
            
    
            } catch (\Throwable $th) {
    
            print_r($th);
    
            }
    }
    public function traerOrdenPorFecha($desde, $hasta) {

        $sql = "SELECT ID, FECHA_MOV, A.FECHA_DESP_ADU,  CONTENEDOR, COD_PROVEE, PROVEEDOR, DESPACHO, A.ORDEN_COMPRA, VALOR_FOB_PESO, (COSTO_NAC*100) COSTO_NAC FROM RO_T_IMPORTACIONES_ENCABEZADO A
                LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                WHERE A.FECHA_DESP_ADU BETWEEN '$desde' AND '$hasta' 
                ORDER BY A.FECHA_DESP_ADU DESC;";
        
        try{
            $stmt = sqlsrv_query( $this->cid_central, $sql );
    
            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
            return ($rows);
            
    
            } catch (\Throwable $th) {
    
            print_r($th);
    
            }
    }

    public function EjecutarSp ($nroOrden){

        try {

            if(strlen(trim($nroOrden)) == 13){
                $nroOrden = ' '.trim($nroOrden);
            }

            $sql = "EXEC RO_SP_INSERTAR_COSTO_NACIONALIZACION '$nroOrden';";
 


            ini_set('max_execution_time', 300);

            $stmt = sqlsrv_query($this->cid_central, $sql);

            $next_result = sqlsrv_next_result($stmt);

            return true;
          
        } catch (Exception $e) {
            return 'Excepción capturada: '.$e->getMessage();
        }

    }

    public function updateCostoNacionalizacion($nroOrden){

        if(strlen(trim($nroOrden)) == 13){
            $nroOrden = ' '.trim($nroOrden);
        }

        try {

            $sql = "DELETE FROM  RO_COSTOS_NACIONALIZACION WHERE N_ORDEN_CO  =  '$nroOrden';";

            $stmt = sqlsrv_query($this->cid_central, $sql);


            $sql = "EXEC RO_SP_INSERTAR_COSTO_NACIONALIZACION '$nroOrden';";


            ini_set('max_execution_time', 300);

            $stmt = sqlsrv_query($this->cid_central, $sql);

            $next_result = sqlsrv_next_result($stmt);

            return true;
          
        } catch (Exception $e) {
            return 'Excepción capturada: '.$e->getMessage();
        }

    }

    public function eliminarDespacho($id) {
        try {
            // Primero eliminar los detalles
            $sqlDetalle = "DELETE FROM RO_T_IMPORTACIONES_DETALLE WHERE ID_MG = ?";
            $stmtDetalle = sqlsrv_prepare($this->cid_central, $sqlDetalle, array(&$id));
            
            if (!sqlsrv_execute($stmtDetalle)) {
                throw new Exception("Error al eliminar detalles del despacho");
            }
            
            // Luego eliminar el encabezado
            $sqlEncabezado = "DELETE FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = ?";
            $stmtEncabezado = sqlsrv_prepare($this->cid_central, $sqlEncabezado, array(&$id));
            
            if (!sqlsrv_execute($stmtEncabezado)) {
                throw new Exception("Error al eliminar encabezado del despacho");
            }
            
            return true;
            
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

}