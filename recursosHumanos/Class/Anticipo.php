
<?php

class Anticipo
{
    
    private $cid;
    private $cid_central;


    
    function __construct()
    {
        date_default_timezone_set('America/Argentina/Buenos_Aires'); 
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

    public function traerEmpleadosGrupo($nroSucurs){

        $sql = "SELECT * FROM RO_T_LEGAJOS_PERSONAL WHERE NUM_SUCURSAL = $nroSucurs
        ";

        if($nroLegajo != null){
            $sql .= " WHERE NRO_LEGAJO = $nroLegajo";
        }

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        return $rows;  

    }

    public function traerEmpleadosIndividual($search = ''){
        $sql = "SELECT TOP 10 NRO_LEGAJO, NRO_DOCUMENTO, APELLIDO_Y_NOMBRE 
                FROM [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_V_LEGAJO 
                WHERE APELLIDO_Y_NOMBRE LIKE ? 
                ORDER BY APELLIDO_Y_NOMBRE";
        
        $params = array("%$search%");
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if($stmt === false) {
            return array();
        }
    
        $rows = array();
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }
    
        return $rows;
    }

    public function validarDNI($dni, $legajo) {
        $sql = "SELECT COUNT(*) as total 
                FROM [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_V_LEGAJO 
                WHERE NRO_DOCUMENTO = ? AND NRO_LEGAJO = ?";
        
        $params = array($dni, $legajo);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            return false;
        }
    
        $row = sqlsrv_fetch_array($stmt);
        return $row['total'] > 0;
    }

    public function obtenerValor($sql, $params = array()) {
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error SQL: " . print_r($errors, true));
                throw new Exception("Error en la consulta SQL");
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
            return $row ? $row[0] : null;
        } catch (Exception $e) {
            error_log("Error en obtenerValor: " . $e->getMessage());
            throw $e;
        }
    }

    public function beginTransaction() {
        if (sqlsrv_begin_transaction($this->cid_central) === false) {
            throw new Exception("Error al iniciar la transacción");
        }
    }

    public function commit() {
        if (sqlsrv_commit($this->cid_central) === false) {
            throw new Exception("Error al confirmar la transacción");
        }
    }

    public function rollback() {
        if (sqlsrv_rollback($this->cid_central) === false) {
            throw new Exception("Error al revertir la transacción");
        }
    }

    public function ejecutar($sql, $params = array()) {
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        if ($stmt === false) {
            throw new Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    public function obtenerPeriodoActual()
    {
        $sql = "SELECT PERIODO, FECHA_ANTICIPO, VIG_DESDE, VIG_HASTA 
                FROM RO_T_FECHA_ANTICIPOS 
                WHERE GETDATE() BETWEEN VIG_DESDE AND VIG_HASTA";
        
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error al consultar el período");
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

}