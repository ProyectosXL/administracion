<?php

class Sucursal
{
    private $cid;
    private $cid_central;
    private $cid_locales;
    private $conexion; 
    private $cid_uy;
    
    function __construct()
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/administracion/class/conexion.php';
        $this->cid = new Conexion();

        $this->cid_central = $this->cid->conectar('central');
        $this->cid_locales =($this->cid->env == 'DEV') ? $this->cid->conectar('central') : $this->cid->conectar('locales');
        $this->cid_uy = $this->cid->conectar('uy');

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
            
            $this->conexion = $this->cid->conectar('uy');
        }else{
            $this->conexion = $this->cid->conectar('central');

        }

    } 
    
    public function traerLocales($orderByName = null)
    {

        if((isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy')){

            $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS  WHERE CANAL = 'EXTERIOR' ";

        }else{

            $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE CANAL = 'PROPIOS' AND HABILITADO = 1
                    UNION ALL
                SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE NRO_SUCURSAL = '16'";

            
        }

        if($orderByName == true){

            $sql = $sql."ORDER BY DESC_SUCURSAL";

        }else{

            $sql = $sql."ORDER BY NRO_SUCURSAL"; 
            
        }    


        $stmt = sqlsrv_query($this->conexion, $sql);

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