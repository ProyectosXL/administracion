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
        $sql="IF EXISTS (SELECT 1 FROM RO_T_GASTOS_CAJA_SUCURSALES WHERE N_COMP = '$comprobante'  AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComp')
        BEGIN
            UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET AUTORIZADO = 1, FECHA_AUTORIZADO = '$fechaDeHoy' WHERE N_COMP  = '$comprobante' AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComp'
        END
        ELSE
        BEGIN
            INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES (FECHA, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, LEYENDA, FACTURA, CONTROL, AUTORIZADO, FECHA_AUTORIZADO) 
            VALUES ('$fecha', $nroSucursal, '$tipoComp', '$comprobante', '$codCuenta', '$descCuenta', $monto, '$leyenda', '0', '0', '1', '$fechaDeHoy')
        END";

        try{
            $stmt = sqlsrv_query($this->conexion, $sql);
            return $stmt;
        } catch (\Throwable $th){
            print_r($th);
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
        $sql = "
        IF EXISTS (SELECT 1 FROM RO_T_GASTOS_CAJA_SUCURSALES WHERE N_COMP = '$nroComprobante'  AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComprobante')
        BEGIN
            UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET FACTURA = $factura WHERE N_COMP  = '$nroComprobante' AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComprobante'
        END
        ELSE
        BEGIN
            INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES (FECHA, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, LEYENDA, FACTURA, CONTROL) 
            VALUES ('$fecha', $nroSucursal, '$tipoComprobante', '$nroComprobante', '$codCuenta', '$descripcionCuenta', $monto, '$leyenda', $factura, $control)
        END
        ";

        try{
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
                $stmt = sqlsrv_query($this->cid_uy, $sql);
            }else{
                $stmt = sqlsrv_query($this->conexion, $sql);
            }
            return $stmt;
        } catch (\Throwable $th){
            print_r($th);
        }
    }

    public function uncheckFactura ($nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $monto)
    {
        $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET FACTURA = 0 
        WHERE N_COMP  = '$nroComprobante' 
        AND NRO_SUCURSAL = '$nroSucursal' 
        AND TIPO_COMP = '$tipoComprobante' 
        AND COD_CUENTA = '$codCuenta' 
        AND MONTO = $monto";

        try{
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
                $stmt = sqlsrv_query($this->cid_uy, $sql);
            }else{
                $stmt = sqlsrv_query($this->conexion, $sql);
            }
            return true;
        } catch (\Throwable $th){
            print_r($th);
        }
    }

    public function marcarControlado ($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control, $observaciones) 
    {
        $sql = "
        IF EXISTS (SELECT 1 FROM RO_T_GASTOS_CAJA_SUCURSALES WHERE N_COMP = '$nroComprobante'  AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComprobante' AND COD_CUENTA = '$codCuenta')
        BEGIN
            UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET CONTROL = $control ,FECHA_CONTROL = GETDATE(), OBSERVACIONES = '$observaciones' WHERE N_COMP  = '$nroComprobante' AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComprobante' AND COD_CUENTA = '$codCuenta'
        END
        ELSE
        BEGIN
            INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES (FECHA, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, LEYENDA, FACTURA, CONTROL, FECHA_CONTROL, USUARIO, OBSERVACIONES) 
            VALUES ('$fecha', $nroSucursal, '$tipoComprobante', '$nroComprobante', '$codCuenta', '$descripcionCuenta', $monto, '$leyenda', $factura, $control, GETDATE(), '', '$observaciones')
        END
        ";

        try{
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
                $stmt = sqlsrv_query($this->cid_uy, $sql);
            }else{
                $stmt = sqlsrv_query($this->conexion, $sql);
            }
            return $stmt;
        } catch (\Throwable $th){
            print_r($th);
        }
    }

    public function uncheckControl ($nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $monto) {
        $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET CONTROL = 0
        WHERE N_COMP  = '$nroComprobante' 
        AND NRO_SUCURSAL = '$nroSucursal' 
        AND TIPO_COMP = '$tipoComprobante' 
        AND COD_CUENTA = '$codCuenta' 
        AND MONTO = $monto";

        try{
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
                $stmt = sqlsrv_query($this->cid_uy, $sql);
            }else{
                $stmt = sqlsrv_query($this->conexion, $sql);
            }
            return true;
        } catch (\Throwable $th){
            print_r($th);
        }
    }

    public function marcarRecibido ($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $observaciones) 
    {
        $sql = "
        IF EXISTS (SELECT 1 FROM RO_T_GASTOS_CAJA_SUCURSALES WHERE N_COMP = '$nroComprobante'  AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComprobante')
        BEGIN
            UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET RECIBIDO = 1,FECHA_RECIBIDO = '$fecha', OBSERVACIONES = '$observaciones'  WHERE N_COMP  = $nroComprobante AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComprobante'
        END
        ELSE
        BEGIN
            INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES (FECHA, FECHA_RECIBIDO, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, RECIBIDO, OBSERVACIONES) VALUES ('$fecha',GETDATE(),$nroSucursal,'$tipoComprobante','$nroComprobante','$codCuenta','$descripcionCuenta','$monto','1', '$observaciones')
        END
        ";

        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;
        } catch (\Throwable $th){
            print_r($th);
        }
    }

    public function controlTesoreria($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto)
    {
        $sql = "UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET CTROL_TESORERIA = 1, FECHA_CTROL_TESOR = GETDATE() WHERE N_COMP = '$nroComprobante' 
        AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComprobante' AND COD_CUENTA = '$codCuenta' AND MONTO = $monto AND FECHA = '$fecha'";
 
        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;
        } catch (\Throwable $th){
            print_r($th);
        }
    } 

    public function guardarObservaciones ($observaciones, $nroSucursal, $nroComprobante)
    {
        $sql = " UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET OBSERVACIONES = '$observaciones' WHERE N_COMP = '$nroComprobante' AND NRO_SUCURSAL = '$nroSucursal'";
   
        try{
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
                $stmt = sqlsrv_query($this->cid_uy, $sql);
            }else{
                $stmt = sqlsrv_query($this->conexion, $sql);
            }
            return true;
        } catch (\Throwable $th){
            print_r($th);
        }
    }
   
    public function traerGastosTesoreria ($desde, $hasta) 
    {
        $sql = "EXEC RO_SP_CARGA_GASTOS_CAJA_SUCURSALES '$desde', '$hasta'";
        
        try{
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);

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
        $sql = "
            SELECT
                A.*,
                CASE WHEN C.N_COMP IS NULL THEN 0 ELSE 1 END AS DESPACHADO,
                C.FECHA_DESP,
                A.N_COMP,
                B.RECIBIDO,
                B.CTROL_TESORERIA,
                C.PRECINTO,
                B.OBSERVACIONES,
                CASE WHEN V.id IS NULL THEN 0 ELSE 1 END AS VINCULADO
            FROM [LAKERBIS].locales_lakers.dbo.RO_V_GASTOS_CAJA_SUCURSALES A
            LEFT JOIN RO_T_GASTOS_CAJA_SUCURSALES B
                ON A.N_COMP = B.N_COMP COLLATE Latin1_General_BIN
                AND A.COD_COMP = B.TIPO_COMP COLLATE Latin1_General_BIN
                AND A.NRO_SUCURS = B.NRO_SUCURSAL
                AND B.RECIBIDO LIKE ?
            LEFT JOIN (
                SELECT FECHA_REG AS FECHA_DESP, B.N_COMP, B.T_COMP, B.NRO_SUCURS, A.PRECINTO
                FROM RO_ENC_GUIA_RETIROS_SUC A
                INNER JOIN RO_EGRESOS_GUIA_RETIROS_SUC B ON A.NRO_REGISTRO = B.NRO_REGISTRO AND A.NRO_SUCURS = B.NRO_SUCURS
            ) C
                ON A.N_COMP = C.N_COMP COLLATE Latin1_General_BIN
                AND A.COD_COMP = C.T_COMP COLLATE Latin1_General_BIN
                AND A.NRO_SUCURS = C.NRO_SUCURS
            LEFT JOIN RO_T_RECIBOS_VINCULADOS V
                ON A.COD_COMP = V.original_cod_comp
                AND A.N_COMP = V.original_n_comp
            WHERE
                A.COD_CTA = '100100'
                AND A.FECHA BETWEEN ? AND ?
        ";

        $params = [$estado, $desde, $hasta];

        if($estado == "0"){
            $sql .= " AND (B.RECIBIDO IS NULL)";
        }

        $sql .= " ORDER BY A.FECHA DESC";
        
        try{
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);

            $v = [];
            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {
                $v[] = $row;
            }
            return $v;
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }
   
    public function contabilizar ($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $monto, $contabilizado) 
    {
        $sql ="UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET CONTABILIZADA = '$contabilizado'
        WHERE FECHA = '$fecha' 
        AND N_COMP = '$nroComprobante' 
        AND NRO_SUCURSAL = '$nroSucursal' 
        AND TIPO_COMP = '$tipoComprobante'
        AND COD_CUENTA = '$codCuenta' 
        AND MONTO = $monto";

        try{
            $conexion = $this->cid_central;

            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
                $conexion = $this->cid_uy;
            }

            $stmt = sqlsrv_query($conexion, $sql);
            return true;
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
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
                CAST(s.CANT_MONE AS FLOAT) AS CANT_MONE,
                s.LEYENDA
            FROM SBA05 s
            LEFT JOIN RO_T_RECIBOS_VINCULADOS v
                ON s.COD_COMP = v.vinculado_cod_comp collate Latin1_General_BIN
                AND s.N_COMP = v.vinculado_n_comp collate Latin1_General_BIN
            WHERE
                s.COD_CTA = '100101'
                AND s.FECHA >= GETDATE() - 30
                AND s.D_H = 'D'
                AND v.id IS NULL -- Excluir recibos ya vinculados
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