<?php

class Sucursal
{
    private $cid;
    private $cid_central;
    private $cid_locales;

    
    function __construct()
    {

        require_once __DIR__.'/../../class/conexion.php';
        $this->cid = new Conexion();

        $this->cid_central = $this->cid->conectar('central');
        $this->cid_locales =($this->cid->env == 'DEV') ? $this->cid->conectar('central') : $this->cid->conectar('locales');

    } 

    public function traerTodosLosMediosDePago()
    {
  
        $sql = "SELECT * FROM RO_T_MEDIOS_DE_PAGO where ACTIVO = '1'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

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
    public function traerLocales()
    {

        $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE CANAL = 'PROPIOS' AND HABILITADO = 1
                    UNION ALL
                SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE NRO_SUCURSAL = '16'
                ORDER BY NRO_SUCURSAL";

        $stmt = sqlsrv_query($this->cid_central, $sql);

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

        $sql = "UPDATE ".$this->cid->prefix."RO_T_VENTA_DIARIA_SUCURSALES SET IMPORTE_\$_FISICO = '$importeControl', VERIFICADO = $verificado, FECHA_MODIF = GETDATE(), OBSERVACIONES = '$observaciones' WHERE ID = $id";

        try{
            
            $stmt = sqlsrv_query($this->cid_locales, $sql);
       
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

    public function traerGastosCajaSucursales ($desde, $hasta, $sucursal)
    {

        try {

            $sql = "SELECT a.*,b.FACTURA, b.CONTROL 
            FROM [LAKERBIS].locales_lakers.dbo.RO_V_GASTOS_CAJA_SUCURSALES a
            left join RO_T_GASTOS_CAJA_SUCURSALES b on  REPLACE(a.N_COMP, ' ', '') = REPLACE (b.N_COMP, ' ', '')  collate Latin1_General_BIN 
            WHERE a.FECHA BETWEEN '$desde' AND '$hasta' AND a.NRO_SUCURS = $sucursal 
            ORDER BY FECHA ASC; 
            ";
            
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

    public function marcarFacturado ($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control) 
    {

        $sql = "
        IF EXISTS (SELECT 1 FROM RO_T_GASTOS_CAJA_SUCURSALES WHERE N_COMP = $nroComprobante)
        BEGIN
            UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET FACTURA = $factura WHERE N_COMP  = $nroComprobante
        END
        ELSE
        BEGIN
            INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES (FECHA, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, LEYENDA, FACTURA, CONTROL) VALUES ('$fecha',$nroSucursal,'$tipoComprobante','$nroComprobante','$codCuenta','$descripcionCuenta',$monto,'$leyenda',$factura,$control)
        END
        ";

        try{
            
            $stmt = sqlsrv_query($this->cid_central, $sql);
       
            return $stmt;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

    public function marcarControlado ($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control) 
    {

        $sql = "
        IF EXISTS (SELECT 1 FROM RO_T_GASTOS_CAJA_SUCURSALES WHERE N_COMP = $nroComprobante)
        BEGIN
            UPDATE RO_T_GASTOS_CAJA_SUCURSALES SET CONTROL = $control ,FECHA_CONTROL = GETDATE() WHERE N_COMP  = $nroComprobante
        END
        ELSE
        BEGIN
            INSERT INTO RO_T_GASTOS_CAJA_SUCURSALES (FECHA, NRO_SUCURSAL, TIPO_COMP, N_COMP, COD_CUENTA, CUENTA, MONTO, LEYENDA, FACTURA, CONTROL, FECHA_CONTROL, USUARIO) VALUES ('$fecha',$nroSucursal,'$tipoComprobante','$nroComprobante','$codCuenta','$descripcionCuenta',$monto,'$leyenda',$factura,$control,GETDATE(),'')
        END
        ";

        try{
            
            $stmt = sqlsrv_query($this->cid_central, $sql);
       
            return $stmt;
        
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
   

}