<?php

class Vendedor
{
    
    private $cid;
    private $cid_central;

    
    function __construct()
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/administracion/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');

    } 


    private function retornarArray($sqlEnviado){

        $sql = $sqlEnviado;

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        return $rows;  

    }

    public function traertSucursales(){

        $sql = " 
        
        SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM [LAKERBIS].locales_lakers.dbo.SUCURSALES_LAKERS WHERE CANAL IN ('PROPIOS','FRANQUICIAS') 
        AND NRO_SUC_MADRE IS NULL AND HABILITADO = 1
        ";

        $rows = $this->retornarArray($sql);

        return $rows;

    }  

    public function crearGrupoEnc($nombreGrupo){

        $sql = "INSERT INTO FU_GRUPOS_VENDEDORES (NOMBRE, CREATED_AT, UPDATED_AT ) VALUES ('$nombreGrupo', GETDATE(), GETDATE())";

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        if( $stmt === false ) {
            die( print_r( sqlsrv_errors(), true));
        }

        return true;
    }
      

    public function traerGrupos () {
        
        $sql = "SELECT * FROM FU_GRUPOS_VENDEDORES";

        $rows = $this->retornarArray($sql);

        return $rows;

    }


    public function crearGrupoDet($localesPorGrupo){

        $sql = "INSERT INTO FU_LOCALES_POR_GRUPOS_VENDEDORES (NRO_SUCURSAL, NOMBRE_GRUPO,  CREATED_AT, UPDATED_AT ) VALUES $localesPorGrupo";
  
        $stmt = sqlsrv_query( $this->cid_central, $sql );

        if( $stmt === false ) {
            die( print_r( sqlsrv_errors(), true));
        }

        return true;
    }
      

    public function traerVendedores () {
        
        $sql = "SELECT COD_VENDED, NOMBRE_VEN, INHABILITA FROM GVA23
        WHERE INHABILITA = 0";

        $rows = $this->retornarArray($sql);

        return $rows;
        
    }

    public function borrarGrupo ($grupo) {

        $sql = "DELETE FROM FU_GRUPOS_VENDEDORES WHERE NOMBRE = '$grupo'";

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        if( $stmt === false ) {
            die( print_r( sqlsrv_errors(), true));
        }

        $sql2 = "DELETE FROM FU_LOCALES_POR_GRUPOS_VENDEDORES WHERE NOMBRE_GRUPO = '$grupo'";

        $stmt2 = sqlsrv_query( $this->cid_central, $sql2 );

        if( $stmt2 === false ) {
            die( print_r( sqlsrv_errors(), true));
        }

        return true;

    }

    public function traerLocalesPorGrupo ($nombre) {

        $sql = "SELECT * FROM FU_LOCALES_POR_GRUPOS_VENDEDORES where NOMBRE_GRUPO in ('$nombre')";
   

        $rows = $this->retornarArray($sql);

        return $rows;

    }

    public function editarGrupo ($nombreGrupo, $sucursales){

        $sql ="DELETE FROM FU_LOCALES_POR_GRUPOS_VENDEDORES WHERE NOMBRE_GRUPO = '$nombreGrupo'";

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        if( $stmt === false ) {
            die( print_r( sqlsrv_errors(), true));
        }

        $sql = "INSERT INTO FU_LOCALES_POR_GRUPOS_VENDEDORES (NRO_SUCURSAL, NOMBRE_GRUPO,  CREATED_AT, UPDATED_AT ) VALUES $sucursales";

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        if( $stmt === false ) {
            die( print_r( sqlsrv_errors(), true));
        }

        return true;

    }

    
    public function localConexion($num_suc){
    
        $sql_buscar_local = "SELECT TOP 1 * FROM ACTOR WHERE NUMERO_ACTOR = $num_suc";
    
        $query_buscar_local = sqlsrv_prepare($cid_tangonet_bis_1, $sql_buscar_local);
    
        sqlsrv_execute($query_buscar_local);
    
        $datos = array();
    
        while($v=sqlsrv_fetch_array($query_buscar_local)){
    
            $datos = array
            (
    
                "SERVIDOR" => $v['SERVIDOR_ACTOR'],
                "Database" => $v['BASE_ACTOR'],
                "NOMBRE" => $v['NOMBRE_ACTOR'],
                "MAIL" => $v['MAIL_ACTOR']
    
            );
    
    
        }
    }
}    

$conexion_tangonet_bis_1 = array( "Database"=>"TangoNet_Bis_1", "UID"=>"sa", "PWD"=>"Axoft");
$cid_tangonet_bis_1 = sqlsrv_connect($servidor_lakerbis, $conexion_tangonet_bis_1);