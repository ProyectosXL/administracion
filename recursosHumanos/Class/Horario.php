<?php

class Horario
{
    
    private $cid;
    private $cid_central;


    
    function __construct()
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/administracion/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');
      

    } 


    private function retornarArray($sqlEnviado, $db = 'central'){

        $sql = $sqlEnviado;

        $cid_central = $this->cid->conectar($db);

        $stmt = sqlsrv_query( $cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        return $rows;  

    }

    public function traerVendedores($nroLegajo = null){

        $sql = "SELECT COD_VENDED BLOQUE, B.NOMBRE,B.APELLIDO,B.TAREA_HABITUAL, B.HABILITADO,B.NRO_LEGAJO, LOCALIDAD,
        GVA23_CAMPOS_ADICIONALES.XML_CA.value('CA_1118_NUM_SUCURSAL', 'VARCHAR(6)') NRO_SUCURS FROM GVA23 A
        OUTER APPLY A.CAMPOS_ADICIONALES.nodes('CAMPOS_ADICIONALES') as GVA23_CAMPOS_ADICIONALES(XML_CA)
        INNER JOIN RO_T_LEGAJOS_PERSONAL B ON A.COD_VENDED = B.COD_VENDEDOR COLLATE Latin1_General_BIN
        ";

        if($nroLegajo != null){
            $sql .= " WHERE B.NRO_LEGAJO = $nroLegajo";
        }

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        return $rows;  

    }



}