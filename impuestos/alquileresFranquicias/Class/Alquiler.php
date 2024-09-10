
<?php

class Alquiler
{
    private $cid_central;

    function __construct(){
        require_once __DIR__.'/../../../Class/conexion.php';

        $cid = new Conexion();

        $this->cid_central = $cid->conectar('central');

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    } 

    public function traerFranquicias()
    {
        $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS 
                WHERE CANAL = 'FRANQUICIAS' AND HABILITADO = 1 AND NRO_SUC_MADRE IS NULL";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception("Error en la consulta SQL: " . print_r($errors, true));
        }

        try {
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $v;
            }
    
            return $rows;
        
        } catch (\Throwable $th){
            throw new Exception("Error al procesar los resultados: " . $th->getMessage());
        } finally {
            sqlsrv_free_stmt($stmt);
        }
    }

    
    public function insertarContratoAlquiler($nroSucursal, $descSucursal, $vigDesde, $vigHasta)
    {
        // Determinar si el contrato está activo
        $fechaActual = date('Y-m-d');
        $estado = ($fechaActual >= $vigDesde && $fechaActual <= $vigHasta) ? 1 : 0;

        $sql = "INSERT INTO RO_T_CONTRATOS_ALQUILER_FRANQUICIAS 
                (FECHA_CARGA, NRO_SUCURS, DESC_SUCURS, VIG_DESDE, VIG_HASTA, ESTADO) 
                VALUES (GETDATE(), ?, ?, ?, ?, ?)";

        $params = array($nroSucursal, $descSucursal, $vigDesde, $vigHasta, $estado);

        $stmt = sqlsrv_query($this->cid_central, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception("Error al insertar el contrato: " . print_r($errors, true));
        }

        sqlsrv_free_stmt($stmt);

        return true;
    }

}