
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

    public function obtenerContratos($sucursal = '', $vigente = '')
    {
        $sql = "SELECT * FROM RO_T_CONTRATOS_ALQUILER_FRANQUICIAS WHERE 1=1";
        $params = array();

        if (!empty($sucursal)) {
            $sql .= " AND NRO_SUCURS = ?";
            $params[] = $sucursal;
        }

        $fechaActual = date('Y-m-d');
        if ($vigente === 'actual') {
            $sql .= " AND ? BETWEEN VIG_DESDE AND VIG_HASTA";
            $params[] = $fechaActual;
        }

        $stmt = sqlsrv_query($this->cid_central, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception("Error en la consulta SQL: " . print_r($errors, true));
        }

        $rows = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    
    public function insertarContratoAlquiler($nroSucursal, $vigDesde, $vigHasta, $contratoComercial, $contratoLocacion, $habilitacion)
    {
        $fechaActual = date('Y-m-d');
        $estado = ($fechaActual >= $vigDesde && $fechaActual <= $vigHasta) ? 1 : 0;

        $sql = "INSERT INTO RO_T_CONTRATOS_ALQUILER_FRANQUICIAS 
                (FECHA_CARGA, NRO_SUCURS, DESC_SUCURS, VIG_DESDE, VIG_HASTA, ESTADO, CONTRATO_COMERCIAL, CONTRATO_LOCACION, HABILITACION) 
                VALUES (GETDATE(), ?, ?, ?, ?, ?, ?, ?, ?)";

        $descSucursal = $this->obtenerDescripcionSucursal($nroSucursal);

        $params = array($nroSucursal, $descSucursal, $vigDesde, $vigHasta, $estado, $contratoComercial, $contratoLocacion, $habilitacion);

        $stmt = sqlsrv_query($this->cid_central, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception("Error al insertar el contrato: " . print_r($errors, true));
        }

        sqlsrv_free_stmt($stmt);

        return true;
    }

    private function obtenerDescripcionSucursal($nroSucursal)
    {
        $sql = "SELECT DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE NRO_SUCURSAL = ?";
        $params = array($nroSucursal);

        $stmt = sqlsrv_query($this->cid_central, $sql, $params);

        if ($stmt === false) {
            throw new Exception("Error al obtener la descripción de la sucursal.");
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        return $row['DESC_SUCURSAL'] ?? '';
    }

    public function subirArchivoContrato($contratoId, $tipoArchivo, $archivo)
    {
        $carpetaDestino = __DIR__ . '/../archivos/';
        $nombreArchivo = $this->generarNombreArchivo($archivo['name']);
        $rutaCompleta = $carpetaDestino . $nombreArchivo;

        if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            $columna = $this->obtenerColumnaArchivo($tipoArchivo);

            $sql = "UPDATE RO_T_CONTRATOS_ALQUILER_FRANQUICIAS SET $columna = ? WHERE ID = ?";
            $params = array($nombreArchivo, $contratoId);

            $stmt = sqlsrv_query($this->cid_central, $sql, $params);

            if ($stmt === false) {
                throw new Exception("Error al actualizar el contrato: " . print_r(sqlsrv_errors(), true));
            }

            sqlsrv_free_stmt($stmt);
            return true;
        } else {
            throw new Exception('Error al mover el archivo subido');
        }
    }

    private function generarNombreArchivo($nombreOriginal)
    {
        $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        $timestamp = date('YmdHis');
        return 'contrato_' . $timestamp . '.' . $extension;
    }

    private function obtenerColumnaArchivo($tipoArchivo)
    {
        switch ($tipoArchivo) {
            case 'comercial':
                return 'CONTRATO_COMERCIAL';
            case 'locacion':
                return 'CONTRATO_LOCACION';
            case 'habilitacion':
                return 'HABILITACION';
            default:
                throw new Exception('Tipo de archivo no válido');
        }
    }

}