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
        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
    
            $sql = "SELECT DISTINCT(MEDIO_PAGO) MEDIO_PAGO FROM RO_T_VENTA_DIARIA_SUCURSALES_UY
            ORDER BY MEDIO_PAGO";
            $cid = $this->cid_locales; 
        }else{

            $sql = "SELECT * FROM RO_T_MEDIOS_DE_PAGO where ACTIVO = '1'";

            $cid = $this->cid_central;

        }

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

            $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE CANAL = 'PROPIOS' AND HABILITADO = 1
                    UNION ALL
                SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE NRO_SUCURSAL = '16'";
    
            
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

    public function traerGastosCajaSucursales ($desde, $hasta, $sucursal, $facturado = null)
    {
        
        try {
            
            $prefix = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? "[LAKERBIS].SUCURSALES_URUGUAY.dbo." : "[LAKERBIS].locales_lakers.dbo.";

         
            $sql = "SELECT a.*,b.FACTURA, b.CONTROL , b.RECIBIDO, b.FECHA_RECIBIDO,b.CONTABILIZADA,b.AUTORIZADO,b.FECHA_AUTORIZADO,
            (case when c.FECHA_GUARDADO is not null then 1 else 0 end) guardado
            FROM ".$prefix."RO_V_GASTOS_CAJA_SUCURSALES a 
            left join RO_T_GASTOS_CAJA_SUCURSALES b on REPLACE(a.N_COMP, ' ', '') = REPLACE (b.N_COMP, ' ', '') collate Latin1_General_BIN 
            AND A.NRO_SUCURS = B.NRO_SUCURSAL AND A.COD_COMP = B.TIPO_COMP collate Latin1_General_BIN AND A.COD_CTA = B.COD_CUENTA 
            AND A.COD_CTA = B.COD_CUENTA 

            left join SJ_EGRESOS_DE_CAJA_GUARDADO c on a.N_COMP = c.N_COMP collate Latin1_General_BIN AND A.NRO_SUCURS = C.NRO_SUCURSAL AND A.COD_CTA = C.COD_CTA 
            and c.NRO_SUCURSAL = '$sucursal'
            WHERE a.FECHA BETWEEN '$desde' AND '$hasta' AND a.NRO_SUCURS = $sucursal 
            "
            ;
            if($facturado == true){

                $sql = $sql."AND b.FACTURA = 1";
                
            }

            $sql = $sql."ORDER BY FECHA ASC;";

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
            
            $stmt = sqlsrv_query($this->conexion, $sql);
       
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
            
            $stmt = sqlsrv_query($this->conexion, $sql);
       
            return true;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

    public function marcarControlado ($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control) 
    {

        $sql = "
        IF EXISTS (SELECT 1 FROM RO_T_GASTOS_CAJA_SUCURSALES WHERE N_COMP = '$nroComprobante'  AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComprobante' AND COD_CUENTA = '$codCuenta')
        BEGIN
            UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET CONTROL = $control ,FECHA_CONTROL = GETDATE() WHERE N_COMP  = '$nroComprobante' AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComprobante' AND COD_CUENTA = '$codCuenta'
        END
        ELSE
        BEGIN
            INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES (FECHA, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, LEYENDA, FACTURA, CONTROL, FECHA_CONTROL, USUARIO) 
            VALUES ('$fecha', $nroSucursal, '$tipoComprobante', '$nroComprobante', '$codCuenta', '$descripcionCuenta', $monto, '$leyenda', $factura, $control, GETDATE(), '')
        END
        ";

        try{
            
            $stmt = sqlsrv_query($this->conexion, $sql);
       
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
                
                $stmt = sqlsrv_query($this->conexion, $sql);
        
                return true;
            
            } catch (\Throwable $th){
                print_r($th);
            }
    }

    public function marcarRecibido ($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto) 
    {

        $sql = "
        IF EXISTS (SELECT 1 FROM RO_T_GASTOS_CAJA_SUCURSALES WHERE N_COMP = '$nroComprobante'  AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComprobante')
        BEGIN
            UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET RECIBIDO = 1,FECHA_RECIBIDO = '$fecha'  WHERE N_COMP  = $nroComprobante AND NRO_SUCURSAL = '$nroSucursal' AND TIPO_COMP = '$tipoComprobante'
        END
        ELSE
        BEGIN
            INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES (FECHA, FECHA_RECIBIDO, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, RECIBIDO) VALUES ('$fecha',GETDATE(),$nroSucursal,'$tipoComprobante','$nroComprobante','$codCuenta','$descripcionCuenta','$monto','1')
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

        $sql = "SELECT A.*, B.RECIBIDO, B.CTROL_TESORERIA
        FROM [LAKERBIS].locales_lakers.dbo.RO_V_GASTOS_CAJA_SUCURSALES A 
        LEFT JOIN RO_T_GASTOS_CAJA_SUCURSALES B 
            ON A.N_COMP = B.N_COMP COLLATE Latin1_General_BIN AND A.COD_COMP = B.TIPO_COMP COLLATE Latin1_General_BIN AND A.NRO_SUCURS = B.NRO_SUCURSAL  
            AND B.RECIBIDO LIKE '%$estado%'
        WHERE COD_CTA = '100100' 
            AND A.FECHA BETWEEN '$desde' AND '$hasta'";
        if($estado == "0"){

            $sql = $sql."AND (B.RECIBIDO IS NULL)";
        }


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
            
            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;

          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }


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

        $sql = "SELECT A.*, B.AUTORIZADO, B.FECHA_AUTORIZADO, (CASE WHEN C.FECHA_GUARDADO IS NOT NULL THEN 1 ELSE 0 END) guardado
        FROM LAKERBIS.$prefix.DBO.RO_V_GASTOS_CAJA_SUCURSALES A
        LEFT JOIN RO_T_GASTOS_CAJA_SUCURSALES B on REPLACE(A.N_COMP, ' ', '') = REPLACE (B.N_COMP, ' ', '') collate Latin1_General_BIN
        AND A.NRO_SUCURS = B.NRO_SUCURSAL AND A.COD_COMP = B.TIPO_COMP collate Latin1_General_BIN AND A.COD_CTA = B.COD_CUENTA
        AND A.COD_CTA = B.COD_CUENTA
        LEFT JOIN SJ_EGRESOS_DE_CAJA_GUARDADO C on A.N_COMP = C.N_COMP collate Latin1_General_BIN AND A.NRO_SUCURS = C.NRO_SUCURSAL AND A.COD_CTA = C.COD_CTA
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
}