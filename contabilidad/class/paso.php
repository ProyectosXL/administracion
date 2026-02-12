<?php

class Paso
{

    private function ejecutarQuery($sqlEnviado)
    {
        try {
            require_once __DIR__.'/../../class/conexion.php';

            $cid = new Conexion();
 
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
    
    
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $cid_central  = $cid->conectar('uy');
            }else{
                $cid_central = $cid->conectar('central');
    
            }
    
            $sql = $sqlEnviado;

            $stmt = sqlsrv_query($cid_central, $sql);
            sqlsrv_execute($stmt);
        }  catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function consultarPasos($periodo){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }


        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
            $cid_central  = $cid->conectar('uy');
        }else{
            $cid_central = $cid->conectar('central');

        }

        $sql = "SELECT * FROM RO_T_CONTROL_INFORME_ECONOMICO WHERE PERIODO = '$periodo'";
        $stmt = sqlsrv_query($cid_central, $sql);
        $salida=array();

        while ($row = sqlsrv_fetch_array($stmt)) {
            $salida[] = $row;
        }


        return ($salida);

    }

    public function ejecutarPaso1($desde, $hasta)
    {
 
        try {
            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
    
    
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $cid_central  = $cid->conectar('uy');
            }else{
                $cid_central = $cid->conectar('central');
    
            }
            $sql = "EXEC RO_SP_VENTAS_BRUTAS '$desde', '$hasta'";

            $stmt = sqlsrv_query($cid_central, $sql);

            return true;
          
        } catch (Exception $e) {
            return 'Excepción capturada: '.$e->getMessage();
        }
    }



    public function ejecutarPaso2($desde, $hasta)
    {  
        try {

            require_once __DIR__.'/../../class/conexion.php';
            
            $cid = new Conexion();

            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
    
    
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $cid_central  = $cid->conectar('uy');
            }else{
                $cid_central = $cid->conectar('locales');
    
            }
            $cid_conexion =  $cid_central;
            
            $prefix = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? '[LAKERBIS].SUCURSALES_URUGUAY.dbo.' : '';

            $sql = "EXEC ".$prefix."RO_SP_VENTAS_VS_COBRANZA_TOTALES '$desde', '$hasta' ;";

            ini_set('max_execution_time', 300);
            
            $stmt = sqlsrv_query($cid_conexion, $sql);

            $next_result = sqlsrv_next_result($stmt);

            $v = [];

            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {

                $v[] = $row;

            }

            return $v;
          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }


    public function ejecutarPaso3($desde, $hasta)
    {  
        // 1. SISTEMA DE LOGS DEPURACIÓN (Se guardará en la carpeta Class)
        $archivoLog = __DIR__ . '/log_paso3.txt';
        file_put_contents($archivoLog, date('H:i:s') . " - INICIO PASO 3 ($desde al $hasta)\n", FILE_APPEND);

        try {
            // 2. AUMENTAR TIEMPO DE EJECUCIÓN (Faltaba esto)
            ini_set('max_execution_time', 600); // 10 Minutos
            ini_set('memory_limit', '512M');

            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $cid_central  = $cid->conectar('uy');
            }else{
                $cid_central = $cid->conectar('central');
            }

            $sql = "SET NOCOUNT ON; EXEC RO_SP_ARTICULOS_SIN_COSTO_NAC '$desde', '$hasta';";
            
            file_put_contents($archivoLog, date('H:i:s') . " - Ejecutando Consulta SQL...\n", FILE_APPEND);

            // 3. AUMENTAR TIMEOUT DEL DRIVER SQL
            $options = array("QueryTimeout" => 600); 
            $stmt = sqlsrv_query($cid_central, $sql, array(), $options);
   
            if ($stmt === false) {
                $errores = print_r(sqlsrv_errors(), true);
                file_put_contents($archivoLog, date('H:i:s') . " - ERROR SQL: $errores\n", FILE_APPEND);
                die(json_encode(["error" => $errores]));
            }

            file_put_contents($archivoLog, date('H:i:s') . " - Consulta enviada. Procesando resultados...\n", FILE_APPEND);

            $v = [];
            $contadorResultSets = 0;
            
            // Bucle optimizado con logs
            do {
                $contadorResultSets++;
                $filasEnEsteSet = 0;
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $v[] = $row;
                    $filasEnEsteSet++;
                }
                
                // Solo logueamos si encontramos datos para no llenar el disco con basura
                if($filasEnEsteSet > 0) {
                    file_put_contents($archivoLog, date('H:i:s') . " - Set #$contadorResultSets: $filasEnEsteSet filas encontradas.\n", FILE_APPEND);
                }

            } while (sqlsrv_next_result($stmt));
            
            file_put_contents($archivoLog, date('H:i:s') . " - FIN EXITOSO. Total filas retornadas: " . count($v) . "\n", FILE_APPEND);
            
            return $v;
        
        } catch (Exception $e) {
            $msg = $e->getMessage();
            file_put_contents($archivoLog, date('H:i:s') . " - EXCEPCIÓN: $msg\n", FILE_APPEND);
            echo 'Excepción capturada: ',  $msg, "\n";
        }
    }

  
    public function ejecutarPaso4($desde, $hasta)
    {  
        try {
            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
    
    
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $cid_central  = $cid->conectar('uy');
            }else{
                $cid_central = $cid->conectar('central');
    
            }

            $sql = " EXEC RO_SP_ARTICULOS_SIN_PRECIO_COSTO '$desde', '$hasta';";

            ini_set('max_execution_time', 300);

            $stmt = sqlsrv_query($cid_central, $sql);

            $next_result = sqlsrv_next_result($stmt);

            $v = [];

            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {

                $v[] = $row;

            }

            return $v;

        
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }


    public function ejecutarPaso5($desde, $hasta)
    {  
        try {

            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
    
    
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $cid_central  = $cid->conectar('uy');
            }else{
                $cid_central = $cid->conectar('central');
    
            }

            $sql = "EXEC RO_SP_RENTABILIDAD_BRUTA '$desde', '$hasta' ;";

            ini_set('max_execution_time', 300);

            $stmt = sqlsrv_query($cid_central, $sql);

            $next_result = sqlsrv_next_result($stmt);

            $v = [];

            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {

                $v[] = $row;

            }

            return $v;
       
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function ejecutarPaso6($desde, $hasta)
    {  
        try {

            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
    
    
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $cid_central  = $cid->conectar('uy');
            }else{
                $cid_central = $cid->conectar('central');
    
            }

            $sql = "EXEC RO_SP_INSERTAR_MET_PRORRATEO_TODOS '$desde', '$hasta' ;";

            ini_set('max_execution_time', 300);

            $stmt = sqlsrv_query($cid_central, $sql);

            $next_result = sqlsrv_next_result($stmt);

            $v = [];

            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {

                $v[] = $row;

            }

            return $v;

          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function ejecutarPaso7($desde, $hasta)
    {  
        try {

            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
    
    
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $cid_central  = $cid->conectar('uy');
            }else{
                $cid_central = $cid->conectar('central');
    
            }

            $sql = "EXEC RO_SP_INTEGRAL '$desde', '$hasta' ;";

            ini_set('max_execution_time', 300);

            $stmt = sqlsrv_query($cid_central, $sql);

            $next_result = sqlsrv_next_result($stmt);

            $v = [];

            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {

                $v[] = $row;

            }

            return true;
          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }
    
    public function ejecutarPaso8($desde, $hasta)
    {  
        try {

            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
    
    
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $cid_central  = $cid->conectar('uy');
            }else{
                $cid_central = $cid->conectar('central');
    
            }


            $sql = "EXEC RO_SP_APLICAR_COEF_AJUSTE '$desde', '$hasta' ;";

            ini_set('max_execution_time', 300);

            $stmt = sqlsrv_query($cid_central, $sql);

            $next_result = sqlsrv_next_result($stmt);

            $v = [];

            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {

                $v[] = $row;

            }

            return true;
          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }


    public function marcarPasoEjecutado ($paso_ejecutado, $periodo) {
        try {

            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();

            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
    
    
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $cid_central  = $cid->conectar('uy');
            }else{
                $cid_central = $cid->conectar('central');
    
            }


            // Usar MERGE para evitar duplicados
            $sql = "MERGE INTO RO_T_CONTROL_INFORME_ECONOMICO AS target
                    USING (SELECT '$periodo' AS PERIODO) AS source
                    ON target.PERIODO = source.PERIODO
                    WHEN MATCHED THEN
                        UPDATE SET PASO_$paso_ejecutado = 1
                    WHEN NOT MATCHED THEN
                        INSERT (PERIODO, PASO_$paso_ejecutado)
                        VALUES ('$periodo', 1);";

            $stmt = sqlsrv_query($cid_central, $sql);

            return true;

          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
        
    }

    public function aceptarConDiferencias ($paso_ejecutado, $periodo, $desde, $hasta) {
        
        try {

            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
    
    
            if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                $cid_central  = $cid->conectar('uy');
            }else{
                $cid_central = $cid->conectar('central');
    
            }

            $sql ="UPDATE RO_T_CONTROL_INFORME_ECONOMICO SET PASO_".$paso_ejecutado." = 1 WHERE PERIODO = '$periodo'";

            $stmt = sqlsrv_query($cid_central, $sql);

            $sql2="EXEC [XL-LAKERBIS].[LOCALES_LAKERS].DBO.RO_SP_RESUMEN_VENTA_SUCURSALES_POR_TIPO_PAGO_NUEVO '$desde', '$hasta'";

            $stmt = sqlsrv_query($cid_central, $sql2);


            return true;

          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
        
    }


}




?>