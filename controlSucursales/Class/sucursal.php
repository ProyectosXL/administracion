<?php

class Sucursal
{
    private $cid;
    public $cid_central; // Público para acceso desde controladores
    private $cid_locales;
    private $conexion; 
    public $cid_uy; // Público para acceso desde controladores
    
    function __construct()
    {
        require_once __DIR__.'/../../class/conexion.php';
        $this->cid = new Conexion();

        $this->cid_central = $this->cid->conectar('central');
        $this->cid_locales =($this->cid->env == 'DEV') ? $this->cid->conectar('central') : $this->cid->conectar('locales');
        $this->cid_uy = $this->cid->conectar('uy');

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
            $this->cid_locales =  $this->cid->conectar('suc_uy');
        }

        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
            $this->conexion = $this->cid->conectar('suc_uy');
        }else{
            $this->conexion = $this->cid->conectar('central');
        }
    } 

    public function traerTodosLosMediosDePago()
    {
        try {
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') {
                $sql = "SELECT DISTINCT(MEDIO_PAGO) MEDIO_PAGO 
                        FROM RO_T_VENTA_DIARIA_SUCURSALES_UY
                        ORDER BY MEDIO_PAGO";
                $cid = $this->cid_locales;
            } else {
                $sql = "SELECT DISTINCT(MEDIO_PAGO) MEDIO_PAGO 
                        FROM ".$this->cid->prefix."RO_T_VENTA_DIARIA_SUCURSALES 
                        WHERE MEDIO_PAGO IS NOT NULL
                        ORDER BY MEDIO_PAGO";
                $cid = $this->cid_locales;
            }
            
            $stmt = sqlsrv_query($cid, $sql);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error en la consulta SQL: " . print_r($errors, true));
            }

            $rows = array();
            while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $v;
            }

            return $rows;
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    public function traerImportesTotales($nroSucursal, $fecha)
    {
        $sql = "SELECT * FROM  ".$this->cid->prefix."RO_T_VENTA_DIARIA_SUCURSALES where nro_sucursal = '$nroSucursal' and FECHA = '$fecha';";
        $stmt = sqlsrv_query($this->cid_locales, $sql);

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

    public function traerImportesTotalesPorPeriodo ($nroSucursal, $desde, $hasta, $medioDePago  )
    {
        $tabla = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? "RO_T_VENTA_DIARIA_SUCURSALES_UY" : "RO_T_VENTA_DIARIA_SUCURSALES";

        $sql = "SELECT * FROM  ".$this->cid->prefix.$tabla." where nro_sucursal = '$nroSucursal' 
        AND FECHA BETWEEN '$desde' AND '$hasta' 
        AND MEDIO_PAGO = '$medioDePago' 
        ORDER BY FECHA";
        
        $stmt = sqlsrv_query($this->cid_locales, $sql);

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

    public function traerLocales($orderByName = null)
    {
        if((isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') || (isset($_SESSION['entorno']) &&  $_SESSION['entorno'] == 'uy')){
            $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS  WHERE CANAL = 'EXTERIOR' ";
        }else{
            $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE CANAL = 'PROPIOS' AND HABILITADO = 1";
        }

        if($orderByName == true){
            $sql = $sql."ORDER BY DESC_SUCURSAL";
        }else{
            $sql = $sql."ORDER BY NRO_SUCURSAL"; 
        }    

        $cid = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? $this->cid_uy : $this->cid_central;
        $stmt = sqlsrv_query($cid, $sql);

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

    public function actualizarValor ($id, $importeControl, $verificado = null, $observaciones = null)
    {
        $importeControl = str_replace(' ', '', $importeControl);
        $tabla = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? "RO_T_VENTA_DIARIA_SUCURSALES_UY" : "RO_T_VENTA_DIARIA_SUCURSALES";

        $sql = "UPDATE ".$this->cid->prefix.$tabla." SET IMPORTE_\$_FISICO = '$importeControl', VERIFICADO = $verificado, FECHA_MODIF = GETDATE(), OBSERVACIONES = '$observaciones' WHERE ID = $id";

        try{
            $stmt = sqlsrv_query($this->cid_locales, $sql);
            return $stmt;
        } catch (\Throwable $th){
            print_r($th);
        }
    }

    public function autorizarEgreso ($fecha, $nroSucursal, $tipoComp, $comprobante, $codCuenta, $descCuenta, $monto, $leyenda, $fechaDeHoy)
    {
        try {
            // Verificamos si existe el registro
            $sqlCheck = "SELECT COUNT(*) as count FROM RO_T_GASTOS_CAJA_SUCURSALES WHERE N_COMP = ? AND NRO_SUCURSAL = ? AND TIPO_COMP = ?";
            $params = array($comprobante, $nroSucursal, $tipoComp);
            $stmt = sqlsrv_query($this->conexion, $sqlCheck, $params);
            
            if ($stmt === false) {
                throw new Exception("Error checking record existence");
            }
            
            $row = sqlsrv_fetch_array($stmt);
            
            if ($row['count'] > 0) {
                // El registro existe, actualizamos
                $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET AUTORIZADO = 1, FECHA_AUTORIZADO = ? WHERE N_COMP = ? AND NRO_SUCURSAL = ? AND TIPO_COMP = ?";
                $params = array($fechaDeHoy, $comprobante, $nroSucursal, $tipoComp);
            } else {
                // El registro no existe, lo insertamos
                $sql = "INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES (FECHA, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, LEYENDA, FACTURA, CONTROL, AUTORIZADO, FECHA_AUTORIZADO) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 1, ?)";
                $params = array($fecha, $nroSucursal, $tipoComp, $comprobante, $codCuenta, $descCuenta, $monto, $leyenda, $fechaDeHoy);
            }
            
            $stmt = sqlsrv_query($this->conexion, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("SQL Error en autorizarEgreso: " . print_r($errors, true));
                throw new Exception("Error executing SQL");
            }
            
            return $stmt;
            
        } catch (\Throwable $th){
            error_log("Exception en autorizarEgreso: " . $th->getMessage());
            throw $th;
        }
    }

    public function traerVerificados ($fecha)
    {
        $sql = "SELECT nro_sucursal ,MIN(VERIFICADO) AS STATUS
        FROM  ".$this->cid->prefix."RO_T_VENTA_DIARIA_SUCURSALES
        WHERE FECHA = '$fecha' GROUP BY NRO_SUCURSAL ";
   
        $stmt = sqlsrv_query($this->cid_locales, $sql);
        
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

    public function traerControlMensual ($desde, $hasta)
    {
        try {
            $sql = "EXEC ".$this->cid->prefix."RO_SP_CONTROL_MENSUAL_VENTA_SUCURSALES '$desde', '$hasta' ;";
            $stmt = sqlsrv_query($this->cid_locales, $sql);

            $v = [];
            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {
                $v[] = $row;
            }
            return $v;
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function marcarFacturado ($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control) 
    {
        try {
            $conexion = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? $this->cid_uy : $this->conexion;
            
            // Verificamos si existe el registro
            $sqlCheck = "SELECT COUNT(*) as count FROM RO_T_GASTOS_CAJA_SUCURSALES WHERE N_COMP = ? AND NRO_SUCURSAL = ? AND TIPO_COMP = ?";
            $params = array($nroComprobante, $nroSucursal, $tipoComprobante);
            $stmt = sqlsrv_query($conexion, $sqlCheck, $params);
            
            if ($stmt === false) {
                throw new Exception("Error checking record existence");
            }
            
            $row = sqlsrv_fetch_array($stmt);
            
            if ($row['count'] > 0) {
                // El registro existe, actualizamos
                $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET FACTURA = ? WHERE N_COMP = ? AND NRO_SUCURSAL = ? AND TIPO_COMP = ?";
                $params = array($factura, $nroComprobante, $nroSucursal, $tipoComprobante);
            } else {
            
                $fechaSQL = DateTime::createFromFormat('d/m/Y', $fecha)->format('Y-m-d');
                $sql = "INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES (FECHA, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, LEYENDA, FACTURA, CONTROL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = array($fechaSQL, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control);
            }
            
            $stmt = sqlsrv_query($conexion, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("SQL Error en marcarFacturado: " . print_r($errors, true));
                throw new Exception("Error executing SQL");
            }
            
            return $stmt;
            
        } catch (\Throwable $th){
            error_log("Exception en marcarFacturado: " . $th->getMessage());
            throw $th;
        }
    }

    public function uncheckFactura ($nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $monto)
    {
        try {
            $conexion = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? $this->cid_uy : $this->conexion;
            
            $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET FACTURA = 0 WHERE N_COMP = ? AND NRO_SUCURSAL = ? AND TIPO_COMP = ? AND COD_CUENTA = ? AND MONTO = ?";
            $params = array($nroComprobante, $nroSucursal, $tipoComprobante, $codCuenta, $monto);
            
            $stmt = sqlsrv_query($conexion, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("SQL Error en uncheckFactura: " . print_r($errors, true));
                return false;
            }
            
            return true;
        } catch (\Throwable $th){
            error_log("Exception en uncheckFactura: " . $th->getMessage());
            return false;
        }
    }

    public function marcarControlado ($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control, $observaciones) 
    {
        try {
            $conexion = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? $this->cid_uy : $this->conexion;
            
            // Verificamos si existe el registro
            $sqlCheck = "SELECT COUNT(*) as count FROM RO_T_GASTOS_CAJA_SUCURSALES WHERE N_COMP = ? AND NRO_SUCURSAL = ? AND TIPO_COMP = ? AND COD_CUENTA = ?";
            $params = array($nroComprobante, $nroSucursal, $tipoComprobante, $codCuenta);
            $stmt = sqlsrv_query($conexion, $sqlCheck, $params);
            
            if ($stmt === false) {
                throw new Exception("Error checking record existence");
            }
            
            $row = sqlsrv_fetch_array($stmt);
            
            if ($row['count'] > 0) {
                // El registro existe, actualizamos
                $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET CONTROL = ?, FECHA_CONTROL = GETDATE(), OBSERVACIONES = ? WHERE N_COMP = ? AND NRO_SUCURSAL = ? AND TIPO_COMP = ? AND COD_CUENTA = ?";
                $params = array($control, $observaciones, $nroComprobante, $nroSucursal, $tipoComprobante, $codCuenta);
            } else {
                $fechaSQL = DateTime::createFromFormat('d/m/Y', $fecha)->format('Y-m-d');
                // El registro no existe, lo insertamos
                $sql = "INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES (FECHA, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, LEYENDA, FACTURA, CONTROL, FECHA_CONTROL, USUARIO, OBSERVACIONES) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), '', ?)";
                $params = array($fechaSQL, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control, $observaciones);
            }
            
            $stmt = sqlsrv_query($conexion, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("SQL Error en marcarControlado: " . print_r($errors, true));
                throw new Exception("Error executing SQL");
            }
            
            return $stmt;
            
        } catch (\Throwable $th){
            error_log("Exception en marcarControlado: " . $th->getMessage());
            throw $th;
        }
    }

    public function uncheckControl ($nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $monto) {
        try {
            $conexion = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? $this->cid_uy : $this->conexion;
            
            $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET CONTROL = 0 WHERE N_COMP = ? AND NRO_SUCURSAL = ? AND TIPO_COMP = ? AND COD_CUENTA = ? AND MONTO = ?";
            $params = array($nroComprobante, $nroSucursal, $tipoComprobante, $codCuenta, $monto);
            
            $stmt = sqlsrv_query($conexion, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("SQL Error en uncheckControl: " . print_r($errors, true));
                return false;
            }
            
            return true;
        } catch (\Throwable $th){
            error_log("Exception en uncheckControl: " . $th->getMessage());
            return false;
        }
    }

    public function marcarRecibido($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $observaciones) 
    {
        error_log("=== DEBUG marcarRecibido ===");
        error_log("fecha: '$fecha'");
        error_log("nroSucursal: '$nroSucursal'");
        error_log("tipoComprobante: '$tipoComprobante'");
        error_log("nroComprobante: '$nroComprobante'");
        error_log("codCuenta: '$codCuenta'");
        error_log("descripcionCuenta: '$descripcionCuenta'");
        error_log("monto: '$monto'");
        error_log("observaciones: '$observaciones'");

        try {
            // Primero verificamos si existe el registro
            $sqlCheck = "SELECT COUNT(*) as count FROM RO_T_GASTOS_CAJA_SUCURSALES 
                        WHERE N_COMP = ? AND NRO_SUCURSAL = ? AND TIPO_COMP = ?";
            
            $params = array($nroComprobante, $nroSucursal, $tipoComprobante);
            $stmt = sqlsrv_query($this->cid_central, $sqlCheck, $params);
            
            if ($stmt === false) {
                throw new Exception("Error checking record existence");
            }
            
            $row = sqlsrv_fetch_array($stmt);
            
            if ($row['count'] > 0) {
                // El registro existe, actualizamos
                $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES 
                        SET RECIBIDO = 1,
                            FECHA_RECIBIDO = GETDATE(),
                            OBSERVACIONES = ?
                        WHERE N_COMP = ? 
                        AND NRO_SUCURSAL = ? 
                        AND TIPO_COMP = ?";
                
                $params = array($observaciones, $nroComprobante, $nroSucursal, $tipoComprobante);
            } else {
                // El registro no existe, lo insertamos
                $sql = "INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES 
                        (FECHA, FECHA_RECIBIDO, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, RECIBIDO, OBSERVACIONES) 
                        VALUES (?, GETDATE(), ?, ?, ?, ?, ?, ?, 1, ?)";
                
                // DEBUG: Verificar parámetros antes de insertar
                error_log("INSERT - codCuenta: '$codCuenta', descripcionCuenta: '$descripcionCuenta'");
                
                $params = array($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $observaciones);
            }
            
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("SQL Error en marcarRecibido: " . print_r($errors, true));
                throw new Exception("Error executing SQL");
            }
            
            $rowsAffected = sqlsrv_rows_affected($stmt);
            error_log("Filas afectadas en marcarRecibido: " . $rowsAffected);
            
            return $rowsAffected > 0;
            
        } catch (\Throwable $th) {
            error_log("Exception en marcarRecibido: " . $th->getMessage());
            error_log($th->getTraceAsString());
            throw $th;
        }
    }

    public function controlTesoreria($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto)
{
    // Debug: Log de los parámetros recibidos
    error_log("=== DEBUG controlTesoreria ===");
    error_log("fecha: '" . $fecha . "'");
    error_log("nroSucursal: '" . $nroSucursal . "'");
    error_log("tipoComprobante: '" . $tipoComprobante . "'");
    error_log("nroComprobante: '" . $nroComprobante . "' (length: " . strlen($nroComprobante) . ")");
    error_log("codCuenta: '" . $codCuenta . "'");
    error_log("descripcionCuenta: '" . $descripcionCuenta . "'");
    error_log("monto: '" . $monto . "'");
    
    // Primero verificamos si existe el registro
    $sqlCheck = "SELECT COUNT(*) as count FROM RO_T_GASTOS_CAJA_SUCURSALES 
                WHERE N_COMP = ? AND NRO_SUCURSAL = ? AND TIPO_COMP = ?";
    
    try {
        $params = array($nroComprobante, $nroSucursal, $tipoComprobante);
        $stmt = sqlsrv_query($this->cid_central, $sqlCheck, $params);
        
        if ($stmt === false) {
            throw new Exception("Error checking record existence");
        }
        
        $row = sqlsrv_fetch_array($stmt);
        
        if ($row['count'] > 0) {
            // El registro existe, actualizamos
            $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES 
                    SET CTROL_TESORERIA = 1, 
                        FECHA_CTROL_TESOR = GETDATE()
                    WHERE N_COMP = ? 
                    AND NRO_SUCURSAL = ? 
                    AND TIPO_COMP = ?";
            
            $params = array($nroComprobante, $nroSucursal, $tipoComprobante);
        } else {
            // El registro no existe, lo insertamos
            $sql = "INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES 
                    (FECHA, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, CTROL_TESORERIA, FECHA_CTROL_TESOR) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, GETDATE())";
            
            $params = array($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto);
        }
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if($stmt === false) {
            $errors = sqlsrv_errors();
            error_log("SQL Error: " . print_r($errors, true));
            return false;
        }
        
        $rowsAffected = sqlsrv_rows_affected($stmt);
        error_log("Filas afectadas: " . $rowsAffected);
        
        return $rowsAffected > 0;
        
    } catch (\Throwable $th){
        error_log("Exception: " . print_r($th, true));
        return false;
    }
}
    public function guardarObservaciones ($observaciones, $nroSucursal, $nroComprobante)
    {
        try {
            $conexion = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? $this->cid_uy : $this->conexion;
            
            $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET OBSERVACIONES = ? WHERE N_COMP = ? AND NRO_SUCURSAL = ?";
            $params = array($observaciones, $nroComprobante, $nroSucursal);
            
            $stmt = sqlsrv_query($conexion, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("SQL Error en guardarObservaciones: " . print_r($errors, true));
                return false;
            }
            
            return true;
        } catch (\Throwable $th){
            error_log("Exception en guardarObservaciones: " . $th->getMessage());
            return false;
        }
    }
   
    public function traerGastosTesoreria ($desde, $hasta) 
    {
        $sql = "EXEC RO_SP_CARGA_GASTOS_CAJA_SUCURSALES '$desde', '$hasta'";
        
        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);

            $v = [];
            sqlsrv_next_result($stmt);
            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {
                $v[] = $row;
            }
            return $v;
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function controlarGastosTesoreria ($nroSucursal, $data, $periodo) 
    {
        $sql = "INSERT INTO SJ_CONTROL_GASTOS_TESORERIA (NRO_SUCURSAL,DATA,CHECKEADO,PERIODO) VALUES ($nroSucursal,'$data','1','$periodo')";

        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function traerGastosTesoreriaCheckeados ($periodo) 
    {
        $sql = "SELECT NRO_SUCURSAL FROM  SJ_CONTROL_GASTOS_TESORERIA WHERE PERIODO = '$periodo'";

        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);

            $v = [];
            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {
                $v[] = $row;
            }
            return $v;
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }
   
    public function traerDatosControlRecepcion ($desde, $hasta, $estado)
    {
        $sql = "SELECT A.*, CASE WHEN C.N_COMP IS NULL THEN 0 ELSE 1 END DESPACHADO, C.FECHA_DESP, A.N_COMP, B.RECIBIDO, B.CTROL_TESORERIA, C.PRECINTO, B.OBSERVACIONES,
                CASE WHEN V.id IS NULL THEN 0 ELSE 1 END AS VINCULADO
        FROM [LAKERBIS].locales_lakers.dbo.RO_V_GASTOS_CAJA_SUCURSALES A
        LEFT JOIN RO_T_GASTOS_CAJA_SUCURSALES B
            ON RTRIM(LTRIM(A.N_COMP)) = RTRIM(LTRIM(B.N_COMP)) COLLATE Latin1_General_BIN AND A.COD_COMP = B.TIPO_COMP COLLATE Latin1_General_BIN AND A.NRO_SUCURS = B.NRO_SUCURSAL
        LEFT JOIN (SELECT FECHA_REG AS FECHA_DESP, B.N_COMP, B.T_COMP, B.NRO_SUCURS, A.PRECINTO
                FROM RO_ENC_GUIA_RETIROS_SUC A
                INNER JOIN RO_EGRESOS_GUIA_RETIROS_SUC B ON A.NRO_REGISTRO = B.NRO_REGISTRO AND A.NRO_SUCURS = B.NRO_SUCURS) C
            ON RTRIM(LTRIM(A.N_COMP)) = RTRIM(LTRIM(C.N_COMP)) COLLATE Latin1_General_BIN AND A.COD_COMP = C.T_COMP COLLATE Latin1_General_BIN AND A.NRO_SUCURS = C.NRO_SUCURS
        LEFT JOIN RO_T_RECIBOS_VINCULADOS V
            ON A.COD_COMP = V.original_cod_comp COLLATE Latin1_General_BIN
            AND RTRIM(LTRIM(A.N_COMP)) = RTRIM(LTRIM(V.original_n_comp)) COLLATE Latin1_General_BIN
        WHERE A.COD_CTA = '100100' AND A.COD_COMP IN ('RAF','REV')
            AND A.FECHA BETWEEN '$desde' AND '$hasta'
        ORDER BY A.FECHA DESC";

        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
            if ($stmt === false) {
                error_log("SQL query failed in traerDatosControlRecepcion: " . print_r(sqlsrv_errors(), true));
                error_log("Failing SQL: " . $sql);
                return [];
            }

            $v = [];
            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {
                $v[] = $row;
            }
            
            // Aplicar filtro después de obtener los datos
            if($estado != "todos" && !empty($v)){
                $dataFiltrada = [];
                foreach($v as $gasto){
                    $incluir = false;
                    
                    if($estado == "pendiente_recibir"){
                        $incluir = ($gasto['RECIBIDO'] != 1);
                    } elseif($estado == "pendiente_control"){
                        $incluir = ($gasto['RECIBIDO'] == 1 && $gasto['CTROL_TESORERIA'] != 1);
                    } elseif($estado == "pendiente_cargar"){
                        $incluir = ($gasto['RECIBIDO'] == 1 && $gasto['CTROL_TESORERIA'] == 1 && $gasto['VINCULADO'] != 1);
                    }
                    
                    if($incluir){
                        $dataFiltrada[] = $gasto;
                    }
                }
                $v = $dataFiltrada;
            }
            
            return $v;
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }
   
    public function contabilizar ($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $monto, $contabilizado) 
    {
        try {
            $conexion = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? $this->cid_uy : $this->cid_central;
            
            $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET CONTABILIZADA = ? WHERE FECHA = ? AND N_COMP = ? AND NRO_SUCURSAL = ? AND TIPO_COMP = ? AND COD_CUENTA = ? AND MONTO = ?";
            $params = array($contabilizado, $fecha, $nroComprobante, $nroSucursal, $tipoComprobante, $codCuenta, $monto);
            
            $stmt = sqlsrv_query($conexion, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("SQL Error en contabilizar: " . print_r($errors, true));
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Excepción capturada en contabilizar: ' . $e->getMessage());
            return false;
        }
    }

    // Método actualizado con compatibilidad de nomenclatura nueva/vieja y validación de año
    public function traerGastosAutorizarSucursales($desde, $hasta, $nroSucursal, $estado) {
        
        $sqlWhere = "";
        if($estado == 1){
            $sqlWhere = "AND (B.AUTORIZADO != 1 or B.AUTORIZADO IS NULL)";
        }else if($estado == 2){
            $sqlWhere = "AND B.AUTORIZADO = 1";
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $prefix = "LOCALES_LAKERS";

        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
            $cid = $this->cid_uy;
            $prefix = "SUCURSALES_URUGUAY";
        }else{
            $cid = $this->cid_central;
        }

        // ACTUALIZADA: Consulta modificada para incluir la validación de año con COD_COMP
        $sql = "SELECT A.*, B.AUTORIZADO, B.FECHA_AUTORIZADO, (CASE WHEN C.FECHA_GUARDADO IS NOT NULL THEN 1 ELSE 0 END) guardado
        FROM LAKERBIS.$prefix.DBO.RO_V_GASTOS_CAJA_SUCURSALES A
        LEFT JOIN RO_T_GASTOS_CAJA_SUCURSALES B on REPLACE(A.N_COMP, ' ', '') = REPLACE (B.N_COMP, ' ', '') collate Latin1_General_BIN
        AND A.NRO_SUCURS = B.NRO_SUCURSAL AND A.COD_COMP = B.TIPO_COMP collate Latin1_General_BIN AND A.COD_CTA = B.COD_CUENTA
        AND A.COD_CTA = B.COD_CUENTA
        LEFT JOIN SJ_EGRESOS_DE_CAJA_GUARDADO C on A.N_COMP = C.N_COMP collate Latin1_General_BIN 
        AND A.NRO_SUCURS = C.NRO_SUCURSAL 
        AND A.COD_CTA = C.COD_CTA
        AND (
            -- Nueva modalidad con COD_COMP
            (A.COD_COMP = C.COD_COMP COLLATE Latin1_General_BIN AND C.COD_COMP IS NOT NULL AND C.COD_COMP != '') 
            OR 
            -- Modalidad vieja sin COD_COMP, solo si es del mismo año
            (
                (C.COD_COMP IS NULL OR C.COD_COMP = '') 
                AND YEAR(A.FECHA) = YEAR(C.FECHA_GUARDADO)
            )
        )
        AND C.NRO_SUCURSAL LIKE '$nroSucursal'
        WHERE A.FECHA BETWEEN '$desde' AND '$hasta' AND A.NRO_SUCURS LIKE '$nroSucursal' AND A.COD_CTA LIKE '5%'
        $sqlWhere
        ORDER BY FECHA ASC
        ";  

        try{
            $stmt = sqlsrv_query($cid, $sql);

            $v = [];
            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {
                $v[] = $row;
            }
            return $v;
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    // Método para traer gastos de caja con compatibilidad de nomenclatura
    public function traerGastosCajaSucursales ($desde, $hasta, $sucursal, $facturado = null)
    {
        try {
            $prefix = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? "[LAKERBIS].SUCURSALES_URUGUAY.dbo." : "[LAKERBIS].locales_lakers.dbo.";

            // SQL actualizada con lógica de compatibilidad para fotos
            $sql = "SELECT a.*,b.FACTURA, b.CONTROL , b.RECIBIDO, b.FECHA_RECIBIDO,b.CONTABILIZADA,b.AUTORIZADO,b.FECHA_AUTORIZADO,
            (CASE WHEN c.FECHA_GUARDADO IS NOT NULL THEN 1 ELSE 0 END) guardado
            FROM ".$prefix."RO_V_GASTOS_CAJA_SUCURSALES a 
            LEFT JOIN RO_T_GASTOS_CAJA_SUCURSALES b on REPLACE(a.N_COMP, ' ', '') = REPLACE (b.N_COMP, ' ', '') collate Latin1_General_BIN 
            AND A.NRO_SUCURS = B.NRO_SUCURSAL AND A.COD_COMP = B.TIPO_COMP collate Latin1_General_BIN AND A.COD_CTA = B.COD_CUENTA 
            AND A.COD_CTA = B.COD_CUENTA 
            LEFT JOIN SJ_EGRESOS_DE_CAJA_GUARDADO c on a.N_COMP = c.N_COMP collate Latin1_General_BIN 
            AND A.NRO_SUCURS = C.NRO_SUCURSAL 
            AND A.COD_CTA = C.COD_CTA 
            AND (
                -- Nueva modalidad con COD_COMP
                (A.COD_COMP = C.COD_COMP COLLATE Latin1_General_BIN AND C.COD_COMP IS NOT NULL AND C.COD_COMP != '') 
                OR 
                -- Modalidad vieja sin COD_COMP, solo si es del mismo año
                (
                    (C.COD_COMP IS NULL OR C.COD_COMP = '') 
                    AND YEAR(A.FECHA) = YEAR(C.FECHA_GUARDADO)
                )
            )
            AND c.NRO_SUCURSAL = '$sucursal'
            WHERE a.FECHA BETWEEN '$desde' AND '$hasta' AND a.NRO_SUCURS = $sucursal";
            
            if($facturado == true){
                $sql = $sql." AND b.FACTURA = 1";
            }

            $sql = $sql." ORDER BY FECHA ASC;";

            $conexion = $this->conexion;

            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
                $conexion = $this->cid_uy;
            }

            $stmt = sqlsrv_query($conexion , $sql);

            $v = [];
            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {
                $v[] = $row;
            }

            return $v;
          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function traerRecibosParaVincular($searchTerm = '')
    {
        $sql = "
            SELECT
                CAST(s.FECHA AS DATE) AS FECHA,
                s.COD_COMP,
                s.N_COMP,
				CASE WHEN s.COD_CTA = '100901' THEN CAST(s.CANT_MONE * S.COTIZ_MONE AS FLOAT)
				     ELSE CAST(s.CANT_MONE AS FLOAT) END AS CANT_MONE,
                s.LEYENDA
            FROM SBA05 s
            LEFT JOIN RO_T_RECIBOS_VINCULADOS v
                ON s.COD_COMP = v.vinculado_cod_comp collate Latin1_General_BIN
                AND s.N_COMP = v.vinculado_n_comp collate Latin1_General_BIN
            WHERE
                s.COD_CTA IN ('100101','100901')
                AND s.FECHA >= GETDATE() - 15
                AND s.D_H = 'D'
                AND v.id IS NULL 
        ";

        $params = [];
        if (!empty($searchTerm)) {
            $sql .= " AND (s.N_COMP LIKE ? OR s.LEYENDA LIKE ?)";
            $searchTermWithWildcards = '%' . $searchTerm . '%';
            $params[] = $searchTermWithWildcards;
            $params[] = $searchTermWithWildcards;
        }

        $sql .= " ORDER BY s.FECHA DESC";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            if ($stmt === false) {
                // Manejo de errores de SQL Server
                $errors = sqlsrv_errors();
                error_log("Error en la consulta SQL de traerRecibosParaVincular: " . print_r($errors, true));
                return []; // Retornar un array vacío en caso de error
            }

            $v = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $v[] = $row;
            }
            return $v;
        } catch (Exception $e) {
            error_log('Excepción capturada en traerRecibosParaVincular: ' . $e->getMessage());
            return []; // Retornar un array vacío en caso de excepción
        }
    }

    public function vincularReciboDb($original_cod_comp, $original_n_comp, $vinculado_cod_comp, $vinculado_n_comp, $usuario)
    {
        $sql = "
            INSERT INTO RO_T_RECIBOS_VINCULADOS
                (original_cod_comp, original_n_comp, vinculado_cod_comp, vinculado_n_comp, usuario)
            VALUES
                (?, ?, ?, ?, ?)
        ";

        $params = array($original_cod_comp, $original_n_comp, $vinculado_cod_comp, $vinculado_n_comp, $usuario);

        try {
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            if (!$stmt) {
                $errors = sqlsrv_errors();
                error_log("Error en la preparación de la consulta de vincularReciboDb: " . print_r($errors, true));
                return false;
            }

            if (sqlsrv_execute($stmt)) {
                return true;
            } else {
                $errors = sqlsrv_errors();
                error_log("Error en la ejecución de la consulta de vincularReciboDb: " . print_r($errors, true));
                return false;
            }
        } catch (Exception $e) {
            error_log('Excepción capturada en vincularReciboDb: ' . $e->getMessage());
            return false;
        }
    }
}