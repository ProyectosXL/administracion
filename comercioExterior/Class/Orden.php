<?php
class Orden{

    function __construct() {

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

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

}