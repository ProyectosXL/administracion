<?php

class Gasto
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();


        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }


        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
            $this->cid_central  = $cid->conectar('uy');
        }else{
            $this->cid_central = $cid->conectar('central');

        }

    } 

      

    public function traerGastos($desde, $hasta, $estado, $codRubro, $codCuenta = null){

    if($estado == '0'){
           // Pendiente control // 
            $sql = "SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE  AMORTIZADO IS NULL AND FECHA BETWEEN '$desde' AND '$hasta' AND PRORRATEADO IS NULL AND (CONTROLADO = 0 OR CONTROLADO IS NULL) AND EXCLUIR = 0 AND COD_CUENTA LIKE '$codCuenta'
                        UNION ALL
                    SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO = 1 AND AMORTIZAR IS NULL AND PERIODO = CAST(DATEPART(MONTH, '$hasta') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$hasta') AS VARCHAR) AND PRORRATEADO IS NULL AND (CONTROLADO = 0 OR CONTROLADO IS NULL) AND EXCLUIR = 0 AND COD_CUENTA LIKE '$codCuenta'" ;
    
    }elseif($estado == '1'){
            // Para amortizar //
            $sql="SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO IS NULL AND FECHA BETWEEN '$desde' AND '$hasta' 
                  AND PRORRATEADO IS NULL AND CONTROLADO IS NOT NULL AND EXCLUIR = 0 AND AMORTIZAR > 0 AND AMORTIZADO IS NULL
                  AND COD_CUENTA LIKE '$codCuenta'
            ";
    }elseif($estado == '2'){
            // Gastos excluidos // 
            $sql ="SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE FECHA BETWEEN '$desde' AND '$hasta' AND EXCLUIR = 1
             AND COD_CUENTA LIKE '$codCuenta'";

    }elseif($estado == '3'){
            // Gastos sin asignar // 
            $sql ="SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE FECHA BETWEEN '$desde' AND '$hasta' AND EXCLUIR = 0 AND (COD_RUBRO IS NULL OR COD_PRORRATEO IS NULL AND AMORTIZADO IS NULL)
             AND COD_CUENTA LIKE '$codCuenta'";

    }else{

            $sql = "SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE (AMORTIZADO IS NULL OR AMORTIZADO = 0) AND FECHA BETWEEN '$desde' AND '$hasta' --AND PRORRATEADO IS NULL
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
        $sql = "SELECT a.*,b.COD_CUENTA,b.DESC_CUENTA,c.RUBRO_CONTABLE, D.DESC_PRORRATEO from RO_T_RELACION_CUENTA_RUBRO_CONTABLE a
        INNER JOIN CUENTA b ON b.COD_CUENTA = a.COD_CUENTA
        INNER JOIN RO_T_RUBROS_CONTABLES c ON c.COD_RUBRO = a.COD_RUBRO
        LEFT JOIN RO_T_METODOS_PRORRATEO D ON A.COD_PRORRATEO = D.COD_PRORRATEO
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
        WHERE AMORTIZAR > 0 
        AND AMORTIZADO IS NULL 
        AND FECHA BETWEEN '$desde' AND '$hasta'";
        
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

    // Método actualizado para mantener la relación codCuenta-sector sin posibilidad de cambio
    public function actualizarRelacionCuentaRubroContable($id, $codCuenta, $sector, $codRubro, $codProrrateo) {
        
        // Actualizamos solo los campos permitidos: COD_RUBRO y COD_PRORRATEO
        $sql = "UPDATE RO_T_RELACION_CUENTA_RUBRO_CONTABLE 
                SET COD_RUBRO = '$codRubro', 
                    COD_PRORRATEO = '$codProrrateo' 
                WHERE ID = $id";
        
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        return 1;
    }

    public function eliminarRelacionCuentaRubroContable($id) {
        $sql = "DELETE FROM RO_T_RELACION_CUENTA_RUBRO_CONTABLE WHERE ID = $id";
        
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        return 1;
    }

    public function revertir($desde, $hasta) {
        $sql = "EXEC RO_SP_REVERTIR_DATAWAREHOUSE_IE '$desde', '$hasta'";
        $stmt = sqlsrv_query($this->cid_central, $sql);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $errorMessage = "Error al ejecutar el procedimiento almacenado: ";
            if ($errors) {
                foreach ($errors as $error) {
                    $errorMessage .= $error['message'] . " ";
                }
            }
            return array('success' => false, 'message' => $errorMessage);
        }

        return array('success' => true, 'message' => 'Proceso de reversión ejecutado correctamente');
    }

    public function validarModulos($periodo) {
        // Validar que el paso 7 esté ejecutado
        $sqlPaso7 = "SELECT PASO_7 FROM RO_T_CONTROL_INFORME_ECONOMICO WHERE PERIODO = '$periodo'";
        $stmtPaso7 = sqlsrv_query($this->cid_central, $sqlPaso7);

        if ($stmtPaso7 === false) {
            $errors = sqlsrv_errors();
            $errorMessage = "Error al consultar el paso 7: ";
            if ($errors) {
                foreach ($errors as $error) {
                    $errorMessage .= $error['message'] . " ";
                }
            }
            return array('success' => false, 'message' => $errorMessage);
        }

        $rowPaso7 = sqlsrv_fetch_array($stmtPaso7, SQLSRV_FETCH_ASSOC);
        
        if (!$rowPaso7) {
            return array('success' => false, 'message' => "No se encontró el período $periodo en la tabla de control");
        }

        if ($rowPaso7['PASO_7'] == 1) {
            // El paso 7 está ejecutado, continuar con la validación de módulos
        } else {
            return array('success' => false, 'message' => "El paso 7 no ha sido ejecutado para el período $periodo");
        }

        // Validar módulos
        $sql = "SELECT DISTINCT (MODULO) AS MODULOS FROM RO_T_INTEGRAL_TANGO_2
                WHERE MODULO NOT IN ('ALQUILERES','CUENTAS2','VENTAS')";
        $stmt = sqlsrv_query($this->cid_central, $sql);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $errorMessage = "Error al consultar módulos: ";
            if ($errors) {
                foreach ($errors as $error) {
                    $errorMessage .= $error['message'] . " ";
                }
            }
            return array('success' => false, 'message' => $errorMessage, 'modulos' => array());
        }

        $modulos = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $modulos[] = $row['MODULOS'];
        }

        $modulosRequeridos = array('CONTABILIDAD', 'TESORERIA', 'COMPRAS');
        
        $modulosFaltantes = array_diff($modulosRequeridos, $modulos);
        
        if (empty($modulosFaltantes)) {
            return array('success' => true, 'message' => 'Paso 7 ejecutado correctamente y todos los módulos están presentes', 'modulos' => $modulos);
        } else {
            $modulosFaltantesStr = implode(', ', $modulosFaltantes);
            return array('success' => false, 'message' => "Faltan los siguientes módulos: $modulosFaltantesStr", 'modulos' => $modulos, 'modulosFaltantes' => $modulosFaltantes);
        }
    }

     /**
     * Verifica si existen registros amortizados para el período
     * @param string $desde Fecha inicio
     * @param string $hasta Fecha fin
     * @return string 'true' o 'false'
     */
    public function verificarAmortizado($desde, $hasta) {
        
        $periodo = (int)date('n', strtotime($hasta)) . '-' . date('Y', strtotime($hasta));
        
        // Verificar si hay registros YA AMORTIZADOS en RO_T_INTEGRAL_TANGO_2 para el período
        $sql = "SELECT 
                CASE 
                    WHEN EXISTS (
                        SELECT 1 
                        FROM RO_T_INTEGRAL_TANGO_2 
                        WHERE AMORTIZADO = 1 
                        AND FECHA BETWEEN '$desde' AND '$hasta'
                    ) THEN 'true'
                    ELSE 'false'
                END AS resultado";
        
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            return 'false';
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['resultado'];
    }

    /**
     * Verifica si existen gastos con monto de amortización en el período
     * (amortizados o pendientes)
     * @param string $desde Fecha inicio
     * @param string $hasta Fecha fin
     * @return string 'true' o 'false'
     */
    public function existenGastosParaAmortizar($desde, $hasta) {
        
        // Verificar si hay registros con AMORTIZAR > 0 en el período
        $sql = "SELECT 
                CASE 
                    WHEN EXISTS (
                        SELECT 1 
                        FROM RO_T_INTEGRAL_TANGO_2 
                        WHERE AMORTIZAR > 0 
                        AND FECHA BETWEEN '$desde' AND '$hasta'
                    ) THEN 'true'
                    ELSE 'false'
                END AS resultado";
        
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            return 'false';
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['resultado'];
    }

    /**
     * Verifica si existen registros prorrateados para el período
     * @param string $desde Fecha inicio
     * @param string $hasta Fecha fin
     * @return string 'true' o 'false'
     */
    public function verificarProrrateado($desde, $hasta) {
        
        $periodo = (int)date('n', strtotime($hasta)) . '-' . date('Y', strtotime($hasta));
        
        $sql = "SELECT 
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM RO_T_INTEGRAL_PRORRATEOS 
                        WHERE PERIODO = '$periodo'
                    ) THEN 'true'
                    ELSE 'false'
                END AS resultado";
        
        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        if ($stmt === false) {
            return 'false';
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['resultado'];
    }

    /**
     * Revierte las amortizaciones del período ejecutando el SP correspondiente
     * @param string $desde Fecha inicio
     * @param string $hasta Fecha fin
     * @return array Resultado de la operación
     */
    public function revertirAmortizacion($desde, $hasta) {
        
        $sql = "EXEC RO_SP_REVERTIR_AMORTIZACIONES '$desde', '$hasta'";
        $stmt = sqlsrv_query($this->cid_central, $sql);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $errorMessage = "Error al ejecutar el procedimiento: ";
            if ($errors) {
                foreach ($errors as $error) {
                    $errorMessage .= $error['message'] . " ";
                }
            }
            return array('success' => false, 'message' => $errorMessage);
        }

        return array('success' => true, 'message' => 'Amortización revertida correctamente');
    }

    /**
     * Revierte los prorrateos del período ejecutando el SP correspondiente
     * @param string $desde Fecha inicio
     * @param string $hasta Fecha fin
     * @return array Resultado de la operación
     */
    public function revertirProrrateo($desde, $hasta) {
        
        $sql = "EXEC RO_SP_REVERTIR_PRORRATEOS '$desde', '$hasta'";
        $stmt = sqlsrv_query($this->cid_central, $sql);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $errorMessage = "Error al ejecutar el procedimiento: ";
            if ($errors) {
                foreach ($errors as $error) {
                    $errorMessage .= $error['message'] . " ";
                }
            }
            return array('success' => false, 'message' => $errorMessage);
        }

        return array('success' => true, 'message' => 'Prorrateo revertido correctamente');
    }

}  
