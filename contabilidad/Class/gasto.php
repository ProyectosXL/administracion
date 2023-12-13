<?php

class Gasto
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 

      

    public function traerGastos($desde, $hasta, $estado, $codRubro, $codCuenta = null){

    if($estado == '0'){

            $sql = "SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE  AMORTIZADO IS NULL AND FECHA BETWEEN '$desde' AND '$hasta' AND PRORRATEADO IS NULL AND (CONTROLADO = 0 OR CONTROLADO IS NULL) AND EXCLUIR = 0 AND COD_CUENTA LIKE '$codCuenta'
                        UNION ALL
                    SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO = 1 AND AMORTIZAR IS NULL AND PERIODO = CAST(DATEPART(MONTH, '$hasta') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$hasta') AS VARCHAR) AND PRORRATEADO IS NULL AND (CONTROLADO = 0 OR CONTROLADO IS NULL) AND EXCLUIR = 0 AND COD_CUENTA LIKE '$codCuenta'" ;
    
    }elseif($estado == '1'){

            $sql="SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO IS NULL AND FECHA BETWEEN '$desde' AND '$hasta' 
                  AND PRORRATEADO IS NULL AND CONTROLADO IS NOT NULL AND EXCLUIR = 0 AND AMORTIZAR > 0 AND AMORTIZADO IS NULL
                  AND COD_CUENTA LIKE '$codCuenta'
            ";
    }elseif($estado == '2'){

            $sql ="SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE FECHA BETWEEN '$desde' AND '$hasta' AND EXCLUIR = 1
             AND COD_CUENTA LIKE '$codCuenta'";

    }elseif($estado == '3'){

            $sql ="SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE FECHA BETWEEN '$desde' AND '$hasta' AND EXCLUIR = 0 AND (COD_RUBRO IS NULL OR COD_PRORRATEO IS NULL AND AMORTIZADO IS NULL)
             AND COD_CUENTA LIKE '$codCuenta'";

    }else{

            $sql = "SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE (AMORTIZADO IS NULL OR AMORTIZADO = 0) AND FECHA BETWEEN '$desde' AND '$hasta' AND PRORRATEADO IS NULL
                    AND COD_RUBRO LIKE '$codRubro'
                    AND COD_CUENTA LIKE '$codCuenta'
                    UNION ALL SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO = 1 AND AMORTIZAR IS NULL 
                    AND PERIODO BETWEEN CAST(DATEPART(MONTH, '$desde') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$desde') AS VARCHAR) AND CAST(DATEPART(MONTH, '$hasta') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$hasta') AS VARCHAR) 
                    --AND PRORRATEADO IS NULL 
                    AND COD_RUBRO LIKE '$codRubro' 
                    AND COD_CUENTA LIKE '$codCuenta'
                    --ORDER BY ID
                ";

    }

        $stmt = sqlsrv_query( $this->cid_central, $sql );
    
        try{
            
            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
    
            $myJSON = json_encode($rows);
    
            return $myJSON;

        } catch (\Throwable $th){
            print_r($th);
        }

    }

    public function traerGastosConsulta($desde, $hasta, $codRubro, $columna, $codCuenta){

        $sql ="SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE (AMORTIZADO IS NULL OR AMORTIZADO = 0) AND FECHA BETWEEN '$desde' AND '$hasta' --AND PRORRATEADO IS NULL
             AND COD_RUBRO LIKE '$codRubro' AND ( DESC_LEYENDA LIKE '%$columna%' OR RAZON_SOCIAL LIKE '%$columna%' OR N_COMP LIKE '%$columna%')AND COD_CUENTA LIKE '$codCuenta'
                UNION ALL 
             SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO = 1 AND AMORTIZAR IS NULL 
             AND PERIODO BETWEEN CAST(DATEPART(MONTH, '$desde') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$desde') AS VARCHAR) AND CAST(DATEPART(MONTH, '$hasta') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$hasta') AS VARCHAR) 
             AND PRORRATEADO IS NULL AND COD_RUBRO LIKE '$codRubro'  AND ( DESC_LEYENDA  LIKE '%$columna%' OR RAZON_SOCIAL LIKE '%$columna%' OR N_COMP LIKE '%$columna%')
             AND COD_CUENTA LIKE '$codCuenta'
        ";
        $stmt = sqlsrv_query( $this->cid_central, $sql );

            
        try{
            
            $rows = array();

            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }

            $myJSON = json_encode($rows);

            return $myJSON;

        } catch (\Throwable $th){
            print_r($th);
        }


    }

    public function traerGastos2($desde, $hasta){

        $sql = "SELECT * FROM RO_T_INTEGRAL_CUENTAS_2 WHERE FECHA BETWEEN '$desde' AND '$hasta'
        ";


        $stmt = sqlsrv_query( $this->cid_central, $sql );

        try{

            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
    
            $myJSON = json_encode($rows);
    
            return $myJSON;

        } catch (\Throwable $th){
            print_r($th);
        }
    }

    public function updateGasto($id, $numSucursal, $codAuxiliar, $descAuxiliar, $sector){

        $sql = "UPDATE RO_T_INTEGRAL_TANGO_2 SET NUM_SUCURSAL = '$numSucursal', COD_AUXILIAR = '$codAuxiliar', DESC_AUXILIAR = '$descAuxiliar', SECTOR = '$sector' WHERE ID = '$id'";

        $stmt = sqlsrv_query( $this->cid_central, $sql );
  
        try {
            sqlsrv_execute($stmt);
      
        } catch (Exception $e) {
            print_r($e);
        }


    }

    public function cambiarValorSaldo($id, $saldo){

        $sql = "UPDATE RO_T_INTEGRAL_TANGO_2 SET saldo = '$saldo' WHERE ID = '$id'";

        $stmt = sqlsrv_prepare( $this->cid_central, $sql );
  
        try {
            sqlsrv_execute($stmt);
      
        } catch (Exception $e) {
            print_r($e);
        }
        

    }


    public function traerCodRubro ($codCuenta,$sector){

        $sql ="SELECT * from RO_T_RELACION_CUENTA_RUBRO_CONTABLE  where COD_CUENTA = '$codCuenta' AND SECTOR = '$sector'";

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        try{

            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }
    }

    public function eliminarGasto($id){

        $sql="DELETE FROM  RO_T_INTEGRAL_CUENTAS_2 WHERE ID_CTA_2 = '$id'";
        $sql2 = "DELETE FROM RO_T_INTEGRAL_TANGO_2 WHERE ID_CTA_2 = '$id'";

        try {

            $stmt = sqlsrv_query( $this->cid_central, $sql );
            $stmt2 = sqlsrv_query( $this->cid_central, $sql2 );
            return true;
            
        } catch (\Throwable $th) {
            throw $th;
        }
       
    }

    public function traerRentabilidadBruta ($desde, $hasta){
        $sql = "SELECT * FROM RO_T_RENTABILIDAD_BRUTA WHERE FECHA BETWEEN '$desde' AND '$hasta' ORDER BY NRO_SUCURS";

        $stmt = sqlsrv_query( $this->cid_central, $sql );
        try{

            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }        

    }
    public function actualizarValor ($id, $nuevoValor, $observacion){

        $sql = "UPDATE RO_T_RENTABILIDAD_BRUTA  SET VENTA = '$nuevoValor' ,OBSERVACION_MOD = '$observacion', FECHA_MOD = GETDATE() WHERE ID = $id";


        try{

            $stmt = sqlsrv_query( $this->cid_central, $sql );
            return true;

        } catch (\Throwable $th){
            print_r($th);
        }        

    }

    public function marcarRentabilidadControlada ($desde, $hasta){

        $sql = "UPDATE RO_T_RENTABILIDAD_BRUTA  SET CONTROLADO = '1' WHERE FECHA BETWEEN '2023-01-01' AND '2023-02-28'";

        try{

            $stmt = sqlsrv_query( $this->cid_central, $sql );
            return true;

        } catch (\Throwable $th){
            print_r($th);
        }        

    }

    public function validarCoeficiente($periodo) {
        $sql = "SELECT * FROM RO_T_COEFICIENTES_AJUSTE WHERE PERIODO = '$periodo'";

        $stmt = sqlsrv_query( $this->cid_central, $sql );
        try{

            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }        


    }
    
    public function insertarCoeficiente($periodo,$valor) {

        $sql = "INSERT INTO RO_T_COEFICIENTES_AJUSTE (PERIODO, COEFICIENTE) values('$periodo', $valor)";    


        try{
            $stmt = sqlsrv_query( $this->cid_central, $sql );
            return true;

        } catch (\Throwable $th){
            print_r($th);
        }        


    }
    
    public function traerRelacionCuentaRubroContable() {
        $sql = "SELECT a.*,b.COD_CUENTA,b.DESC_CUENTA,c.RUBRO_CONTABLE from RO_T_RELACION_CUENTA_RUBRO_CONTABLE a
        INNER JOIN CUENTA b ON b.COD_CUENTA = a.COD_CUENTA
        INNER JOIN RO_T_RUBROS_CONTABLES c ON c.COD_RUBRO = a.COD_RUBRO
        ORDER BY a.ID DESC";

        $stmt = sqlsrv_query( $this->cid_central, $sql );
        try{

            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }        


    }

    public function insertarRelacionCuentaRubroContable ($codCuenta, $sector, $codRubro, $codProrrateo) {

        $sql = "INSERT INTO RO_T_RELACION_CUENTA_RUBRO_CONTABLE(COD_CUENTA, SECTOR, COD_RUBRO, COD_PRORRATEO)
        SELECT '$codCuenta', '$sector', '$codRubro', '$codProrrateo'
        WHERE NOT EXISTS(SELECT 1 FROM RO_T_RELACION_CUENTA_RUBRO_CONTABLE WHERE COD_CUENTA = '$codCuenta' AND SECTOR = '$sector')";

        try{

            $stmt = sqlsrv_query( $this->cid_central, $sql );
            $rowsAffected = sqlsrv_rows_affected($stmt);

            return $rowsAffected;

        }catch (\Throwable $th){

            print_r($th);

        }


    }

    public function validarPendienteDeAsignar ($desde, $hasta) {
       
       $sql = "SELECT 
        CASE 
            WHEN COUNT(*) > 0 THEN 'true'
            ELSE 'false'
        END AS hay_registros_pendientes
        FROM RO_T_INTEGRAL_TANGO_2
        WHERE FECHA BETWEEN '$desde' AND '$hasta'
        AND EXCLUIR = 0 
        AND (COD_RUBRO IS NULL OR COD_PRORRATEO IS NULL AND AMORTIZADO IS NULL) 
        AND COD_CUENTA LIKE '%'";
  
        $stmt = sqlsrv_query( $this->cid_central, $sql );

        
        // Manejar el resultado
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Obtener el valor
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $hayRegistrosPendientes = $row['hay_registros_pendientes'];

        return $hayRegistrosPendientes;

    }

    public function validarPendienteControl ($desde, $hasta) {
       
       $sql = " SELECT 
       CASE 
           WHEN EXISTS (
               SELECT 1
               FROM RO_T_INTEGRAL_TANGO_2
               WHERE 
                   AMORTIZADO IS NULL AND
                   FECHA BETWEEN '$desde' AND '$hasta' AND
                   PRORRATEADO IS NULL AND
                   (CONTROLADO = 0 OR CONTROLADO IS NULL) AND
                   EXCLUIR = 0 AND
                   COD_CUENTA LIKE '%'
           )
           OR EXISTS (
               SELECT 1
               FROM RO_T_INTEGRAL_TANGO_2
               WHERE 
                   AMORTIZADO = 1 AND
                   AMORTIZAR IS NULL AND
                   PERIODO = CAST(DATEPART(MONTH, '$hasta') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$hasta') AS VARCHAR) AND
                   PRORRATEADO IS NULL AND
                   CONTROLADO = 0 AND
                   EXCLUIR = 0 AND
                   COD_CUENTA LIKE '%'
           )
           THEN 'true'
           ELSE 'false'
        END AS Resultado;
        ";
  
        $stmt = sqlsrv_query( $this->cid_central, $sql );

        
        // Manejar el resultado
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Obtener el valor
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $hayRegistrosPendientes = $row['Resultado'];

        return $hayRegistrosPendientes;

    }


    function validarPendienteAmortizar ($desde, $hasta) {

        $sql="SELECT 
        CASE 
            WHEN COUNT(*) > 0 THEN 'true'
            ELSE 'false'
        END AS hay_registros_pendientes
        FROM RO_T_INTEGRAL_TANGO_2 
        WHERE FECHA BETWEEN '$desde' AND '$hasta' AND AMORTIZAR IS NOT NULL AND AMORTIZAR <> 0 AND AMORTIZADO IS NULL";

        /* WHERE AMORTIZADO IS NULL 
        AND FECHA BETWEEN '$desde' AND '$hasta' 
        AND PRORRATEADO IS NULL 
        AND CONTROLADO IS NOT NULL 
        AND EXCLUIR = 0 
        AND AMORTIZAR > 0 
        AND AMORTIZADO IS NULL 
        AND COD_CUENTA LIKE '%'"; */

        
        $stmt = sqlsrv_query( $this->cid_central, $sql );

                
        // Manejar el resultado
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Obtener el valor
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $hayRegistrosPendientes = $row['hay_registros_pendientes'];

        return $hayRegistrosPendientes;

    }
    

    function existeResumen ($periodo){

        $sql = "SELECT 
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM RO_T_RESUMEN_FINAL_IE WHERE PERIODO = '$periodo' AND COD_RUBRO NOT IN ('1.1.','1.2.','1.6.','1.8.')
            ) THEN 'true'
            ELSE 'false'
        END AS resultado;";

        $result = sqlsrv_query($this->cid_central, $sql);

        if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
        }
      
       
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $respuesta = $row['resultado'];

        return $respuesta;
                    
    }

    function traerResumen ($periodo ) {

        $sql = "SELECT PERIODO, NRO_SUCURSAL, DESC_SUCURSAL, COD_RUBRO, RUBRO_CONTABLE, CAST(IMPORTE AS DECIMAL(15,2)) IMPORTE FROM RO_T_RESUMEN_FINAL_IE
        WHERE PERIODO = '$periodo'";

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        try{

            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }        
    }
}  
