
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
        
        // Inicializar entorno por defecto si no existe
        if(!isset($_SESSION['entorno'])){
            $_SESSION['entorno'] = 'central';
        }
        
        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){

            $this->cid_central = $cid->conectar('uy');

        }

    } 

    public function traerConceptos()
    {

  
        $sql = "SELECT * FROM RO_T_CONCEPTOS_ALQUILERES";

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
    
    public function traerConceptosPorcentaje()
    {

  
        $sql = "SELECT ID_CA,CONCEPTO FROM RO_T_CONCEPTOS_ALQUILERES WHERE ES_PORCENTAJE = 1";

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
    public function traerPorcentajeSucursal($concepto)
    {

  
        $sql = "SELECT * FROM RO_T_PORC_CONCEPTOS_ALQUILERES WHERE ID_CA = '$concepto' ORDER BY NRO_SUCURS DESC";

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
    public function insertarPorcentaje($idConcepto, $idLocal, $descLocal)
    {

 
        $sql = "INSERT INTO RO_T_PORC_CONCEPTOS_ALQUILERES(ID_CA, NRO_SUCURS, DESC_SUCURS)  
        SELECT '$idConcepto', '$idLocal', '$descLocal' 
        WHERE NOT EXISTS(SELECT 1 FROM RO_T_PORC_CONCEPTOS_ALQUILERES WHERE NRO_SUCURS = '$idLocal' AND ID_CA = '$idConcepto');";

        try{
        $stmt = sqlsrv_query($this->cid_central, $sql);
        $rowsAffected = sqlsrv_rows_affected($stmt);

        return $rowsAffected;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }
    public function actualizarPorcentaje($id, $porcentaje)
    {
        $sql = "UPDATE RO_T_PORC_CONCEPTOS_ALQUILERES SET PORCENTAJE = '$porcentaje' WHERE ID_PA = '$id'";
        try{
        $stmt = sqlsrv_query($this->cid_central, $sql);
        return true;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }
    public function eliminarPorcentaje($id)
    {
        $sql = "DELETE FROM RO_T_PORC_CONCEPTOS_ALQUILERES WHERE ID_PA = '$id'";

        try{
            
            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;
            
        } catch (\Throwable $th){
            print_r($th);
        }

    }
    public function traerTodosLosPorcentajes()
    {
        $sql = " SELECT * FROM  RO_T_PORC_CONCEPTOS_ALQUILERES ";
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
    public function traerRentabilidadNeta($periodo)
    {
        $sql = "SELECT NRO_SUCURS, VENTA FROM RO_T_RENTABILIDAD_BRUTA WHERE FECHA LIKE  '%$periodo%'";

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
    public function traerRentabilidadBruta($periodo)
    {
        $sql = "SELECT * FROM RO_V_VENTAS_BRUTAS_IE WHERE PERIODO LIKE '%$periodo%'";

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

    public function conteoDetalle($periodo)
    {
        $sql = "SELECT count(*) CONTEO FROM RO_T_DETALLE_ALQUILERES WHERE PERIODO LIKE '%$periodo%'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try{
            
           $v = sqlsrv_fetch_array($stmt);
            return $v;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

    public function traerDetalle($periodo)
    {
        $sql = "SELECT *, CAST(IMPORTE AS FLOAT) IMPORTE_PARSE, 
                ISNULL(AJUSTADO, 0) AS AJUSTADO 
                FROM RO_T_DETALLE_ALQUILERES 
                WHERE PERIODO LIKE '%$periodo%'";

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
    public function insertarDetalle($data)
    {
        $sql = "INSERT INTO RO_T_DETALLE_ALQUILERES (PERIODO,NRO_SUCURS,DESC_SUCURS,IMPORTE,ID_CA) VALUES ".$data;

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
    public function actualizarDetalle($periodo, $idSucursal, $idConcepto, $importe, $userName, $porcentaje )
    {
        $userNameClause = $userName ? ", USUARIO = '$userName'" : "";
        $sql = "UPDATE RO_T_DETALLE_ALQUILERES 
                SET IMPORTE = '$importe'" . $userNameClause . ", 
                    FECHA_MODIF = GETDATE(), 
                    PORCENTAJE_APLICADO = '$porcentaje' 
                WHERE PERIODO = '$periodo' 
                  AND NRO_SUCURS = '$idSucursal' 
                  AND ID_CA = '$idConcepto'";

        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                throw new \Exception("Error en UPDATE: " . print_r(sqlsrv_errors(), true));
            }
            
            return true;
        
        } catch (\Throwable $th){
            throw $th;
        }

    }

    function consultarMesesDetalle ($periodoPasado, $periodo)
    {
        
        $sql = "SELECT C.ID_CA,C.NRO_SUCURS,C.DESC_SUCURS,C.IMPORTE,C.PERIODO FROM (
            SELECT *,REVERSE(REPLACE(b.CAMPO,'-','') ) P from (
                SELECT *,
                     (CASE 
                        WHEN A.PERIODO  NOT LIKE '__-%' THEN REPLACE(A.PERIODO, '-', '0-')
                        WHEN A.PERIODO LIKE '10-%' THEN REPLACE(A.PERIODO, '10-', '01-')
                        ELSE A.PERIODO
                    END) CAMPO 
                FROM RO_T_DETALLE_ALQUILERES  A ) 
            b) 
        C where C.P BETWEEN '$periodoPasado' AND '$periodo' ;";
    

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

    function execSpAlquileres ($periodo) 
    {
        // Calculamos la fecha del último día del mes para verificación
        $partes = explode('-', $periodo);
        if (count($partes) == 2) {
            $mes = str_pad($partes[0], 2, '0', STR_PAD_LEFT);
            $anio = $partes[1];
            $fechaUltimoDia = date('Y-m-d', strtotime("last day of $anio-$mes"));
        } else {
            $fechaUltimoDia = null;
        }
        
        // Verificamos usando la misma lógica que el SP
        // El SP verifica contra PERIODO (que se graba como "4-2025") y FECHA (último día del mes)
        if ($fechaUltimoDia) {
            $verificarSql = "SELECT CASE
                WHEN EXISTS (
                    SELECT 1 FROM RO_T_INTEGRAL_TANGO_2 
                    WHERE MODULO = 'ALQUILERES' 
                    AND (FECHA = '$fechaUltimoDia' OR PERIODO = '$periodo')
                ) THEN 1
                ELSE 0
            END AS RegistroExiste;";
            
            try {
                $stmtVerif = sqlsrv_query($this->cid_central, $verificarSql);
                if ($stmtVerif) {
                    $resultVerif = sqlsrv_fetch_array($stmtVerif);
                    if ($resultVerif && $resultVerif['RegistroExiste'] == 1) {
                        echo json_encode([
                            'status' => 'error',
                            'code' => 1,
                            'message' => 'El período ya se encuentra procesado',
                            'periodo' => $periodo,
                            'fecha_verificada' => $fechaUltimoDia
                        ]);
                        return;
                    }
                }
            } catch (\Throwable $th) {
                // Si hay error en la verificación, continúa con el SP para obtener el mensaje apropiado
                error_log("Error en verificación previa: " . $th->getMessage());
            }
        }
        
        // Enviamos el período original al SP (el SP maneja internamente el formato)
        $sql = " EXEC RO_SP_INTEGRAL_ALQUILERES '$periodo';";
 
        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if (!$stmt) {
                $errors = sqlsrv_errors();
                echo json_encode([
                    'status' => 'error',
                    'code' => 3,
                    'message' => 'Error al ejecutar el stored procedure',
                    'sql_errors' => $errors,
                    'periodo' => $periodo
                ]);
                return;
            }

            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
            
            // Si no hay resultados, significa que se procesó correctamente sin insertar registros
            if(empty($rows)) {
                echo json_encode([
                    'status' => 'success', 
                    'code' => 0,
                    'message' => 'Procesado correctamente',
                    'periodo' => $periodo,
                    'fecha_proceso' => $fechaUltimoDia ?? 'No calculada',
                    'nota' => 'El SP se ejecutó sin errores y sin devolver registros'
                ]);
                return;
            }
            
            if(isset($rows[0][0])){
                if($rows[0][0] == 'ERROR') {
                    echo json_encode([
                        'status' => 'error',
                        'code' => 1,
                        'message' => 'El período ya se encuentra procesado (confirmado por SP)',
                        'raw_response' => $rows[0][0],
                        'periodo' => $periodo,
                        'detalle' => 'El stored procedure indica que ya existe un registro para este período en RO_T_INTEGRAL_TANGO_2'
                    ]);
                    return;
                }
            }
            
            // Si llegamos aquí, el procesamiento fue exitoso
            echo json_encode([
                'status' => 'success', 
                'code' => 0,
                'message' => 'Procesado correctamente',
                'periodo' => $periodo,
                'registros_insertados' => count($rows),
                'fecha_proceso' => $fechaUltimoDia ?? 'No calculada'
            ]);
           
            
        } catch (\Throwable $th) {
            echo json_encode([
                'status' => 'error',
                'code' => 2, 
                'message' => 'Error en la base de datos: ' . $th->getMessage(),
                'periodo' => $periodo
            ]);
        }

    }

    /**
     * Verifica si un período ya está procesado usando la misma lógica que el SP
     */
    function verificarProcesadoPorPeriodo($periodo) {
        // Calculamos la fecha del último día del mes
        $partes = explode('-', $periodo);
        if (count($partes) == 2) {
            $mes = str_pad($partes[0], 2, '0', STR_PAD_LEFT);
            $anio = $partes[1];
            $fechaUltimoDia = date('Y-m-d', strtotime("last day of $anio-$mes"));
        } else {
            return false;
        }
        
        // Verificamos usando el período original (como se graba en la tabla: "4-2025")
        $sql = "SELECT 
            CASE WHEN EXISTS (
                SELECT 1 FROM RO_T_INTEGRAL_TANGO_2 
                WHERE MODULO = 'ALQUILERES' 
                AND (FECHA = '$fechaUltimoDia' OR PERIODO = '$periodo')
            ) THEN 1 ELSE 0 END AS Procesado,
            '$fechaUltimoDia' as FechaCalculada,
            '$periodo' as PeriodoOriginal";
            
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            if ($stmt) {
                return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            }
            return false;
        } catch (\Throwable $th) {
            error_log("Error en verificarProcesadoPorPeriodo: " . $th->getMessage());
            return false;
        }
    }

    function verificarProcesado ($fecha) 
    {
        $sql = "SELECT CASE
        WHEN EXISTS (
            SELECT 1
            FROM RO_T_INTEGRAL_TANGO_2
            WHERE MODULO = 'ALQUILERES'
            AND FECHA = '$fecha'
        ) THEN 1
        ELSE 0
        END AS RegistroExiste;";
        
        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
                    
            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }

    }

    function cerrarPeriodo ($periodo) 
    {
        $sql = "INSERT INTO RO_T_DETALLE_ALQUILERES_ESTADO (PERIODO, ESTADO)
        SELECT '$periodo', 1
        WHERE NOT EXISTS (
            SELECT 1
            FROM RO_T_DETALLE_ALQUILERES_ESTADO
            WHERE PERIODO = '$periodo'
        );";

        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;
            
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function abrirPeriodo ($periodo) 
    {
        $sql = "DELETE FROM RO_T_DETALLE_ALQUILERES_ESTADO WHERE PERIODO = '$periodo';";

        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;
            
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function checkCierrePeriodoAnt ($mesAnterior) 
    {
        $sql = "SELECT CASE
        WHEN EXISTS (
            SELECT 1
            FROM RO_T_DETALLE_ALQUILERES_ESTADO  
            WHERE PERIODO = '$mesAnterior'
        ) THEN 1
        ELSE 0
        END AS RegistroExiste;";
        
        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
                    
            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }

    }
    
    function traerEstado ($periodo) 
    {
        $sql = "SELECT 1
        FROM RO_T_DETALLE_ALQUILERES_ESTADO
        WHERE PERIODO = '$periodo';";
        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);
            if (sqlsrv_has_rows($stmt)) {

                return 1;

            } else {

               return 0;

            }
            
        } catch (\Throwable $th) {
            throw $th;
        }

    }
    
    // FUNCIONES DESHABILITADAS - No se usan más
    /*
    function traerSucursalesOcultas ($periodo) {

        $sql = "SELECT * FROM SJ_ALQUILERES_OCULTOS_POR_PERIODO WHERE PERIODO = '$periodo';";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            if ($stmt === false) {
                throw new \Exception("Error en la consulta SQL: " . print_r(sqlsrv_errors(), true));
            }
            
            $rows = array();
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
            return $rows;
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function ocultarSucursal ($periodo, $sucursal) {

        
        $sql = "IF EXISTS (SELECT 1 FROM SJ_ALQUILERES_OCULTOS_POR_PERIODO WHERE PERIODO = '$periodo')
        BEGIN
            -- El período existe, realizar una actualización (UPDATE)
            UPDATE SJ_ALQUILERES_OCULTOS_POR_PERIODO
            SET JSON_LOCALES = '$sucursal'
            WHERE PERIODO = '$periodo';
        END
        ELSE
        BEGIN
            -- El período no existe, realizar una inserción (INSERT)
            INSERT INTO SJ_ALQUILERES_OCULTOS_POR_PERIODO (PERIODO, JSON_LOCALES)
            VALUES ('$periodo', '$sucursal');
        END";

   
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
           
            return true;

        } catch (\Throwable $th) {
            throw $th;
        }

    }
    */

    public function traerCoeficiente ($periodo ) {

        $sql="SELECT COEFICIENTE FROM RO_T_COEFICIENTES_AJUSTE  WHERE PERIODO = '$periodo'";
 

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
        
            if (sqlsrv_fetch($stmt) === true) {
      
                $coeficiente = sqlsrv_get_field($stmt, 0);
                return $coeficiente;
            } else{
                return 0;
            }

        } catch (\Throwable $th) {
            throw $th; 
        }
    }

    public function aplicarAjuste ($nroSucursal, $concepto,$importe, $periodo ) {

        $sql = "UPDATE RO_T_DETALLE_ALQUILERES SET IMPORTE = '$importe', FECHA_MODIF = GETDATE() ,FECHA_AJUSTE = GETDATE(), AJUSTADO = 1 WHERE PERIODO = '$periodo' AND NRO_SUCURS = '$nroSucursal' AND ID_CA = '$concepto'";

        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;
            
        } catch (\Throwable $th){
            print_r($th);
        }

    }

    public function comprobarAjuste ($nroSucursal, $concepto, $periodo) {

        $sql = "SELECT AJUSTADO FROM RO_T_DETALLE_ALQUILERES WHERE PERIODO = '$periodo' AND NRO_SUCURS = '$nroSucursal' AND ID_CA = '$concepto'";

        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
            if (sqlsrv_fetch($stmt) === true) {
                $ajustado = sqlsrv_get_field($stmt, 0);
                return $ajustado;
            } else{
                return 0;
            }
            
        } catch (\Throwable $th){
            print_r($th);
        }
    }

    public function comprobarAjusteGeneral ($periodo) {
        // Verificar si hay registros sin ajustar de los conceptos 4, 5 y 18 que tengan importe diferente de 0
        $sql = "SELECT COUNT(*) as REGISTROS_SIN_AJUSTAR 
                FROM RO_T_DETALLE_ALQUILERES 
                WHERE PERIODO = '$periodo' 
                AND AJUSTADO IS NULL 
                AND ID_CA IN (4, 5, 18) 
                AND IMPORTE != 0";

        try{
            $stmt = sqlsrv_query($this->cid_central, $sql);
            if (sqlsrv_fetch($stmt) === true) {
                $count = sqlsrv_get_field($stmt, 0);
                // Si count > 0, hay registros sin ajustar, retornar 1 (error)
                // Si count = 0, todo está ajustado, retornar 0 (ok)
                return ($count > 0) ? 1 : 0;
            } else{
                return 1; // Error en la consulta, asumir que no está ajustado
            }
            
        } catch (\Throwable $th){
            error_log("Error en comprobarAjusteGeneral: " . $th->getMessage());
            return 1; // Error, asumir que no está ajustado
        }
    }

    public function revertirProcesamiento($periodo) {
        
        // Calcular la fecha del último día del mes
        $partes = explode('-', $periodo);
        if (count($partes) == 2) {
            $mes = str_pad($partes[0], 2, '0', STR_PAD_LEFT);
            $anio = $partes[1];
            $fechaUltimoDia = date('Y-m-d', strtotime("last day of $anio-$mes"));
        } else {
            throw new \Exception("Formato de período inválido");
        }

        // Eliminar registros de la tabla RO_T_INTEGRAL_TANGO_2
        $sql = "DELETE FROM RO_T_INTEGRAL_TANGO_2 
                WHERE MODULO = 'ALQUILERES' 
                AND (FECHA = '$fechaUltimoDia' OR PERIODO = '$periodo')";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new \Exception("Error al eliminar registros: " . print_r($errors, true));
            }
            
            $rowsAffected = sqlsrv_rows_affected($stmt);
            
            return [
                'rowsAffected' => $rowsAffected,
                'fecha' => $fechaUltimoDia,
                'periodo' => $periodo
            ];
            
        } catch (\Throwable $th) {
            throw $th;
        }
    }

}


