
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
        $sql = "SELECT *, CAST(IMPORTE AS FLOAT) IMPORTE_PARSE FROM RO_T_DETALLE_ALQUILERES WHERE PERIODO LIKE '%$periodo%'";

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
        $sql = "UPDATE RO_T_DETALLE_ALQUILERES SET IMPORTE = '$importe', USUARIO = '$userName', FECHA_MODIF = GETDATE(), PORCENTAJE_APLICADO = '$porcentaje' WHERE PERIODO = '$periodo' AND NRO_SUCURS = '$idSucursal' AND ID_CA = '$idConcepto'";

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
        $sql = " EXEC RO_SP_INTEGRAL_ALQUILERES '$periodo';";
 
        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);

            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
            if(isset($rows[0][0])){

                if($rows[0][0] == 'ERROR') {
                    
                    echo 1;
                    
                }

            } else {
                
                echo 0;

            }
           
            
        } catch (\Throwable $th) {
            throw $th;
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

    /**
     * Verifica si existe solapamiento de contratos para una sucursal en un rango de fechas
     * @param string $sucursal - Número de sucursal
     * @param string $fechaDesde - Fecha de inicio del nuevo contrato (YYYY-MM-DD)
     * @param string $fechaHasta - Fecha de fin del nuevo contrato (YYYY-MM-DD)
     * @return array - Array con información sobre el solapamiento
     */
    public function verificarSolapamientoContrato($sucursal, $fechaDesde, $fechaHasta) {
        
        $sql = "
            SELECT TOP 1
                ID,
                NRO_SUCURS,
                DESC_SUCURS,
                VIG_DESDE,
                VIG_HASTA,
                ID_CA,
                IMPORTE,
                ID_CA_2,
                IMPORTE_2,
                ID_CA_3,
                IMPORTE_3
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE NRO_SUCURS = '$sucursal'
            AND (
                -- Caso 1: El nuevo contrato empieza durante un contrato existente
                ('$fechaDesde' BETWEEN VIG_DESDE AND VIG_HASTA)
                OR
                -- Caso 2: El nuevo contrato termina durante un contrato existente  
                ('$fechaHasta' BETWEEN VIG_DESDE AND VIG_HASTA)
                OR
                -- Caso 3: El nuevo contrato engloba completamente a uno existente
                ('$fechaDesde' <= VIG_DESDE AND '$fechaHasta' >= VIG_HASTA)
                OR
                -- Caso 4: Un contrato existente engloba completamente al nuevo
                (VIG_DESDE <= '$fechaDesde' AND VIG_HASTA >= '$fechaHasta')
            )
            ORDER BY VIG_DESDE DESC
        ";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if (!$stmt) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $contratoExistente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($contratoExistente) {
                // Formatear las fechas para la respuesta
                $vigDesde = $contratoExistente['VIG_DESDE'];
                $vigHasta = $contratoExistente['VIG_HASTA'];
                
                // Convertir objetos DateTime a string si es necesario
                if ($vigDesde instanceof DateTime) {
                    $vigDesde = $vigDesde->format('Y-m-d');
                }
                if ($vigHasta instanceof DateTime) {
                    $vigHasta = $vigHasta->format('Y-m-d');
                }

                return [
                    'solapamiento' => true,
                    'contrato_existente' => [
                        'ID' => $contratoExistente['ID'],
                        'NRO_SUCURS' => $contratoExistente['NRO_SUCURS'],
                        'DESC_SUCURS' => $contratoExistente['DESC_SUCURS'],
                        'VIG_DESDE' => $vigDesde,
                        'VIG_HASTA' => $vigHasta,
                        'IMPORTE_1' => $contratoExistente['IMPORTE'],
                        'IMPORTE_2' => $contratoExistente['IMPORTE_2'],
                        'IMPORTE_3' => $contratoExistente['IMPORTE_3']
                    ],
                    'mensaje' => 'Ya existe un contrato para esta sucursal que se solapa con el período seleccionado'
                ];
            } else {
                return [
                    'solapamiento' => false,
                    'mensaje' => 'No hay solapamiento de contratos'
                ];
            }

        } catch (\Throwable $th) {
            error_log("Error en verificarSolapamientoContrato: " . $th->getMessage());
            
            // En caso de error, devolver que no hay solapamiento para permitir continuar
            return [
                'solapamiento' => false,
                'error' => true,
                'mensaje' => 'Error al verificar solapamiento: ' . $th->getMessage()
            ];
        }
    }

    function traerContratoAlquiler ($fecha = null) {

        if($fecha != null ){

            $sql = "
            DECLARE @periodo VARCHAR(7) = '$fecha';
            
            WITH CTE AS (
                SELECT
                    *,
                    ROW_NUMBER() OVER(PARTITION BY NRO_SUCURS ORDER BY ID DESC) AS rn
                FROM RO_T_CONTRATOS_ALQUILERES
                WHERE CONVERT(VARCHAR(7), VIG_DESDE, 120) <= @periodo
                  AND CONVERT(VARCHAR(7), VIG_HASTA, 120) >= @periodo
            )
            
            SELECT * FROM CTE WHERE rn = 1;
            ";
  
        }else{
            $sql = "SELECT *
            FROM RO_T_CONTRATOS_ALQUILERES";
        }

        try {
        
            $stmt = sqlsrv_query($this->cid_central, $sql);
    
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;
        } catch (\Throwable $th) {
            throw $th; 
        }
    }

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

    
    
    function traerContratoVigente(){

        $sql="
        DECLARE @fecha_actual DATE = GETDATE(); -- Obtiene la fecha actual
        
        WITH CTE AS (
            SELECT
                *,
                ROW_NUMBER() OVER(PARTITION BY NRO_SUCURS ORDER BY ID DESC) AS rn
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE VIG_DESDE <= @fecha_actual
              AND VIG_HASTA >= @fecha_actual
        )
        
        SELECT * FROM CTE WHERE rn IN (1)";

        try {
        
            $stmt = sqlsrv_query($this->cid_central, $sql);
    
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;
        } catch (\Throwable $th) {
            throw $th; 
        }

    }

    function traerContratoAnterior () {

        $sql=" DECLARE @fecha_actual DATE = GETDATE(); -- Obtiene la fecha actual

        WITH CTE AS (
            SELECT
                *,
                ROW_NUMBER() OVER(PARTITION BY NRO_SUCURS ORDER BY ABS(DATEDIFF(DAY, VIG_HASTA, @fecha_actual))) AS rn
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE VIG_HASTA <= @fecha_actual
        )
        
        SELECT * FROM CTE WHERE rn IN (1); ";

        try {
        
            $stmt = sqlsrv_query($this->cid_central, $sql);
    
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;
        } catch (\Throwable $th) {
            throw $th; 
        }

    }

    /**
     * Trae los contratos futuros (que aún no han comenzado)
     * @return array - Array de contratos futuros
     */
    function traerContratosFuturos() {
        $sql = "
            DECLARE @fecha_actual DATE = GETDATE();
            
            WITH CTE AS (
                SELECT
                    *,
                    ROW_NUMBER() OVER(PARTITION BY NRO_SUCURS ORDER BY VIG_DESDE ASC) AS rn
                FROM RO_T_CONTRATOS_ALQUILERES
                WHERE VIG_DESDE > @fecha_actual
            )
            
            SELECT * FROM CTE WHERE rn = 1
            ORDER BY VIG_DESDE ASC
        ";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if (!$stmt) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $rows = array();
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }

            return $rows;
            
        } catch (\Throwable $th) {
            error_log("Error en traerContratosFuturos: " . $th->getMessage());
            // En caso de error, devolver array vacío para evitar crashes
            return array();
        }
    }

    /**
     * Guarda un nuevo contrato de alquiler
     * @param string $nroSucursal - Número de sucursal
     * @param string $descSucursal - Descripción de la sucursal  
     * @param int $valorLlave - Valor llave
     * @param int $comisiones - Comisiones
     * @param int $lanzamiento - FPC Lanzamiento
     * @param string $vigDesde - Fecha de inicio (YYYY-MM-DD)
     * @param string $vigHasta - Fecha de fin (YYYY-MM-DD)
     * @return bool - True si se guardó correctamente, false en caso contrario
     */
    public function guardarContratoAlquiler($nroSucursal, $descSucursal, $valorLlave, $comisiones, $lanzamiento, $vigDesde, $vigHasta) {
        
        $sql = "
            INSERT INTO RO_T_CONTRATOS_ALQUILERES (
                NRO_SUCURS, 
                DESC_SUCURS, 
                VIG_DESDE, 
                VIG_HASTA, 
                ID_CA, 
                IMPORTE, 
                ID_CA_2, 
                IMPORTE_2, 
                ID_CA_3, 
                IMPORTE_3,
                FECHA_CARGA
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE()
            )
        ";

        try {
            $params = [
                $nroSucursal,
                $descSucursal, 
                $vigDesde,
                $vigHasta,
                4, // ID_CA para Valor Llave
                $valorLlave,
                5, // ID_CA_2 para Comisiones  
                $comisiones,
                18, // ID_CA_3 para FPC Lanzamiento
                $lanzamiento
            ];

            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                throw new \Exception("Error al preparar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            return true;

        } catch (\Throwable $th) {
            error_log("Error en guardarContratoAlquiler: " . $th->getMessage());
            return false;
        }
    }

    /**
     * Método adicional para obtener contratos activos de una sucursal
     * @param string $sucursal - Número de sucursal
     * @return array - Contratos activos
     */
    public function obtenerContratosActivosSucursal($sucursal) {
        
        $sql = "
            SELECT 
                ID,
                NRO_SUCURS,
                DESC_SUCURS,
                VIG_DESDE,
                VIG_HASTA,
                ID_CA,
                IMPORTE,
                ID_CA_2,
                IMPORTE_2,
                ID_CA_3,
                IMPORTE_3,
                FECHA_CARGA
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE NRO_SUCURS = '$sucursal'
            AND VIG_HASTA >= GETDATE()
            ORDER BY VIG_DESDE DESC
        ";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if (!$stmt) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $contratos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                
                // Formatear fechas si son objetos DateTime
                if ($row['VIG_DESDE'] instanceof DateTime) {
                    $row['VIG_DESDE'] = $row['VIG_DESDE']->format('Y-m-d');
                }
                if ($row['VIG_HASTA'] instanceof DateTime) {
                    $row['VIG_HASTA'] = $row['VIG_HASTA']->format('Y-m-d');
                }
                if ($row['FECHA_CARGA'] instanceof DateTime) {
                    $row['FECHA_CARGA'] = $row['FECHA_CARGA']->format('Y-m-d H:i:s');
                }
                
                $contratos[] = $row;
            }

            return $contratos;

        } catch (\Throwable $th) {
            error_log("Error en obtenerContratosActivosSucursal: " . $th->getMessage());
            return array();
        }
    }

    /**
     * Obtiene un contrato por su ID
     * @param int $contratoId - ID del contrato
     * @return array|false - Datos del contrato o false si no existe
     */
    public function obtenerContratoPorId($contratoId) {
        $sql = "
            SELECT 
                ID,
                NRO_SUCURS,
                DESC_SUCURS,
                VIG_DESDE,
                VIG_HASTA,
                ID_CA,
                IMPORTE,
                ID_CA_2,
                IMPORTE_2,
                ID_CA_3,
                IMPORTE_3,
                FECHA_CARGA
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE ID = ?
        ";

        try {
            $stmt = sqlsrv_prepare($this->cid_central, $sql, [$contratoId]);
            
            if (!$stmt) {
                throw new \Exception("Error al preparar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $contrato = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($contrato) {
                // Formatear fechas si son objetos DateTime
                if ($contrato['VIG_DESDE'] instanceof DateTime) {
                    $contrato['VIG_DESDE'] = $contrato['VIG_DESDE']->format('Y-m-d');
                }
                if ($contrato['VIG_HASTA'] instanceof DateTime) {
                    $contrato['VIG_HASTA'] = $contrato['VIG_HASTA']->format('Y-m-d');
                }
                if ($contrato['FECHA_CARGA'] instanceof DateTime) {
                    $contrato['FECHA_CARGA'] = $contrato['FECHA_CARGA']->format('Y-m-d H:i:s');
                }
                
                return $contrato;
            }
            
            return false;

        } catch (\Throwable $th) {
            error_log("Error en obtenerContratoPorId: " . $th->getMessage());
            return false;
        }
    }

    /**
     * Verifica si existe solapamiento de contratos excluyendo el contrato actual (para edición)
     * @param string $sucursal - Número de sucursal
     * @param string $fechaDesde - Fecha de inicio del contrato (YYYY-MM-DD)
     * @param string $fechaHasta - Fecha de fin del contrato (YYYY-MM-DD)
     * @param int $contratoId - ID del contrato que se está editando (se excluye de la verificación)
     * @return array - Array con información sobre el solapamiento
     */
    public function verificarSolapamientoContratoEdicion($sucursal, $fechaDesde, $fechaHasta, $contratoId) {
        
        $sql = "
            SELECT TOP 1
                ID,
                NRO_SUCURS,
                DESC_SUCURS,
                VIG_DESDE,
                VIG_HASTA,
                ID_CA,
                IMPORTE,
                ID_CA_2,
                IMPORTE_2,
                ID_CA_3,
                IMPORTE_3
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE NRO_SUCURS = '$sucursal'
            AND ID != $contratoId
            AND (
                -- Caso 1: El contrato editado empieza durante un contrato existente
                ('$fechaDesde' BETWEEN VIG_DESDE AND VIG_HASTA)
                OR
                -- Caso 2: El contrato editado termina durante un contrato existente  
                ('$fechaHasta' BETWEEN VIG_DESDE AND VIG_HASTA)
                OR
                -- Caso 3: El contrato editado engloba completamente a uno existente
                ('$fechaDesde' <= VIG_DESDE AND '$fechaHasta' >= VIG_HASTA)
                OR
                -- Caso 4: Un contrato existente engloba completamente al editado
                (VIG_DESDE <= '$fechaDesde' AND VIG_HASTA >= '$fechaHasta')
            )
            ORDER BY VIG_DESDE DESC
        ";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if (!$stmt) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $contratoExistente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($contratoExistente) {
                // Formatear las fechas para la respuesta
                $vigDesde = $contratoExistente['VIG_DESDE'];
                $vigHasta = $contratoExistente['VIG_HASTA'];
                
                // Convertir objetos DateTime a string si es necesario
                if ($vigDesde instanceof DateTime) {
                    $vigDesde = $vigDesde->format('Y-m-d');
                }
                if ($vigHasta instanceof DateTime) {
                    $vigHasta = $vigHasta->format('Y-m-d');
                }

                return [
                    'solapamiento' => true,
                    'contrato_existente' => [
                        'ID' => $contratoExistente['ID'],
                        'NRO_SUCURS' => $contratoExistente['NRO_SUCURS'],
                        'DESC_SUCURS' => $contratoExistente['DESC_SUCURS'],
                        'VIG_DESDE' => $vigDesde,
                        'VIG_HASTA' => $vigHasta,
                        'IMPORTE_1' => $contratoExistente['IMPORTE'],
                        'IMPORTE_2' => $contratoExistente['IMPORTE_2'],
                        'IMPORTE_3' => $contratoExistente['IMPORTE_3']
                    ],
                    'mensaje' => "Se solapa con contrato ID {$contratoExistente['ID']} ({$vigDesde} a {$vigHasta})"
                ];
            } else {
                return [
                    'solapamiento' => false,
                    'mensaje' => 'No hay solapamiento de contratos'
                ];
            }

        } catch (\Throwable $th) {
            error_log("Error en verificarSolapamientoContratoEdicion: " . $th->getMessage());
            
            // En caso de error, devolver que no hay solapamiento para permitir continuar
            return [
                'solapamiento' => false,
                'error' => true,
                'mensaje' => 'Error al verificar solapamiento: ' . $th->getMessage()
            ];
        }
    }

    /**
     * Actualiza un contrato existente
     * @param int $contratoId - ID del contrato a actualizar
     * @param string $vigDesde - Nueva fecha de inicio (YYYY-MM-DD)
     * @param string $vigHasta - Nueva fecha de fin (YYYY-MM-DD)
     * @param float $valorLlave - Nuevo valor llave
     * @param float $comisiones - Nuevas comisiones
     * @param float $lanzamiento - Nuevo FPC Lanzamiento
     * @return bool - True si se actualizó correctamente, false en caso contrario
     */
    public function actualizarContrato($contratoId, $vigDesde, $vigHasta, $valorLlave, $comisiones, $lanzamiento) {
        
        $sql = "
            UPDATE RO_T_CONTRATOS_ALQUILERES SET
                VIG_DESDE = ?,
                VIG_HASTA = ?,
                IMPORTE = ?,
                IMPORTE_2 = ?,
                IMPORTE_3 = ?,
                FECHA_MODIF = GETDATE()
            WHERE ID = ?
        ";

        try {
            $params = [
                $vigDesde,
                $vigHasta,
                $valorLlave,
                $comisiones,
                $lanzamiento,
                $contratoId
            ];

            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                $errors = sqlsrv_errors();
                error_log("Error al preparar consulta actualizar contrato: " . print_r($errors, true));
                throw new \Exception("Error al preparar la consulta: " . print_r($errors, true));
            }

            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                $errors = sqlsrv_errors();
                error_log("Error al ejecutar consulta actualizar contrato: " . print_r($errors, true));
                throw new \Exception("Error al ejecutar la consulta: " . print_r($errors, true));
            }

            $rowsAffected = sqlsrv_rows_affected($stmt);
            return $rowsAffected > 0;

        } catch (\Throwable $th) {
            error_log("Error en actualizarContrato: " . $th->getMessage());
            return false;
        }
    }
}


