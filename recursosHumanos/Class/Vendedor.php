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
        
        SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM [LAKERBIS].locales_lakers.dbo.SUCURSALES_LAKERS WHERE CANAL IN ('PROPIOS') 
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
    
        require_once $_SERVER['DOCUMENT_ROOT'].'/administracion/Class/Conexion.php';
        $cid = new Conexion();
        $cidTango = $cid->conectar('tangoBis');

        $sql_buscar_local = "SELECT TOP 1 * FROM ACTOR WHERE NUMERO_ACTOR = $num_suc";
    
      
        $stmt = sqlsrv_query( $cidTango, $sql_buscar_local );

        
        if( $stmt === false ) {
            die( print_r( sqlsrv_errors(), true));
        }

        $datos = array();
    
        while($v=sqlsrv_fetch_array($stmt)){
    
            $_SESSION['conexion_dns'] = $v['SERVIDOR_ACTOR'];
            $_SESSION['base_nombre'] = $v['BASE_ACTOR'];
    
        }
        return true;
    }

    public function habilitarVendedorPorSucursal($cod, $nombre){

        require_once $_SERVER['DOCUMENT_ROOT'].'/administracion/Class/Conexion.php';

        $cid = new Conexion();

        $cidLocal = $cid->conectar('');


        $sqlInsertaVended = 
        "
        IF NOT EXISTS (SELECT * FROM GVA23 WHERE COD_VENDED = '".$cod."')
        BEGIN
            INSERT INTO GVA23 (COD_VENDED, NOMBRE_VEN, PORC_COMIS, INHABILITA, TIPO_DOC, COD_GVA23)
            VALUES('".$cod."', '".$nombre."', 1, 0, 99, '".$cod."')
        END
        ELSE 
        BEGIN
            UPDATE GVA23 SET INHABILITA = 0 WHERE COD_VENDED = '".$cod."'
        END
        ";

        $stmt = sqlsrv_query( $cidLocal, $sqlInsertaVended );

        if( $stmt === false ) {
            die( print_r( sqlsrv_errors(), true));
        }

        return true;


    }

    public function inhabilitarVendedorPorSucursal($cod, $nombre){

        require_once $_SERVER['DOCUMENT_ROOT'].'/administracion/Class/Conexion.php';

        $cid = new Conexion();

        $cidLocal = $cid->conectar('');


        $sqlInsertaVended = 
        "
        IF EXISTS (SELECT * FROM GVA23 WHERE COD_VENDED = '".$cod."')
        BEGIN
            UPDATE GVA23 SET INHABILITA = 1 WHERE COD_VENDED = '".$cod."'
        END

        ";

        
        $stmt = sqlsrv_query( $cidLocal, $sqlInsertaVended );

        if( $stmt === false ) {
            die( print_r( sqlsrv_errors(), true));
        }

        return true;


    }
}    
