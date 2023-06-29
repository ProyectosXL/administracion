
<?php

class Categoria
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 

    public function traerRubros () {
        
        $sql = "SELECT * FROM RO_T_RUBROS_CODIFICACION";

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        try{

            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }     

    }

    public function traerVistaRubroCategoriaCodificacion ($sigla) {
        
        $sql = "SELECT * FROM RO_V_RUBROS_CATEGORIAS_CODIFICACION WHERE RUBRO like '%$sigla%' ORDER BY CATEGORIA ASC";

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        try{

            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }     

    }

    public function insertarNuevaCategoria ($codCategoria, $descCategoria, $siglaRubro) {
        
        $sql = "INSERT INTO SJ_CATEGORIAS (CATEGORIA, DESC_CATEGORIA, RUBRO) VALUES ($codCategoria, '$descCategoria', '$siglaRubro')";
        
        try{
            
            $stmt = sqlsrv_query( $this->cid_central, $sql );
            return true;

        } catch (\Throwable $th){
            print_r($th);
        }     

    }

    public function actualizarDescripcionCategoria ($descCategoria, $siglaRubro, $codCategoria) {
        
        $sql = "UPDATE SJ_CATEGORIAS SET DESC_CATEGORIA = '$descCategoria' WHERE CATEGORIA = '$codCategoria' AND RUBRO = '$siglaRubro'";

        try{
            
            $stmt = sqlsrv_query( $this->cid_central, $sql );
            return true;

        } catch (\Throwable $th){
            print_r($th);
        }     

    }

}