<?php

class Alquiler
{
    private $cid_central;

    function __construct()
    {
        require_once __DIR__ . '/../../../Class/conexion.php';

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $cid = new Conexion();
        $database = 'central';

        if (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') {
            $database = 'uy';
        }

        $this->cid_central = $cid->conectar($database);

        if ($this->cid_central === false) {
            error_log("Error al conectar con la base de datos {$database} en Alquiler");
        }
    }

    public function traerConceptos()
    {


        $sql = "SELECT * FROM RO_T_CONCEPTOS_ALQUILERES";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th) {
            print_r($th);
        }

    }

    public function traerConceptosPorcentaje()
    {


        $sql = "SELECT ID_CA,CONCEPTO FROM RO_T_CONCEPTOS_ALQUILERES WHERE ES_PORCENTAJE = 1";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }

    }
    public function traerPorcentajeSucursal($concepto)
    {


        $sql = "SELECT * FROM RO_T_PORC_CONCEPTOS_ALQUILERES WHERE ID_CA = '$concepto' ORDER BY NRO_SUCURS DESC";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }

    }
    public function insertarPorcentaje($idConcepto, $idLocal, $descLocal)
    {


        $sql = "INSERT INTO RO_T_PORC_CONCEPTOS_ALQUILERES(ID_CA, NRO_SUCURS, DESC_SUCURS)  
        SELECT '$idConcepto', '$idLocal', '$descLocal' 
        WHERE NOT EXISTS(SELECT 1 FROM RO_T_PORC_CONCEPTOS_ALQUILERES WHERE NRO_SUCURS = '$idLocal' AND ID_CA = '$idConcepto');";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            $rowsAffected = sqlsrv_rows_affected($stmt);

            return $rowsAffected;

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }

    }
    public function actualizarPorcentaje($id, $porcentaje)
    {
        $sql = "UPDATE RO_T_PORC_CONCEPTOS_ALQUILERES SET PORCENTAJE = '$porcentaje' WHERE ID_PA = '$id'";
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }

    }
    public function eliminarPorcentaje($id)
    {
        $sql = "DELETE FROM RO_T_PORC_CONCEPTOS_ALQUILERES WHERE ID_PA = '$id'";

        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }

    }
    public function traerTodosLosPorcentajes()
    {
        $sql = " SELECT * FROM  RO_T_PORC_CONCEPTOS_ALQUILERES ";
        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }

    }
    public function traerRentabilidadNeta($periodo)
    {
        $sql = "SELECT NRO_SUCURS, VENTA FROM RO_T_RENTABILIDAD_BRUTA WHERE FECHA LIKE  '%$periodo%'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }

    }
    public function traerRentabilidadBruta($periodo)
    {
        $sql = "SELECT * FROM RO_V_VENTAS_BRUTAS_IE WHERE PERIODO LIKE '%$periodo%'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }

    }

    public function conteoDetalle($periodo)
    {
        // Usar = en lugar de LIKE para búsqueda exacta
        $sql = "SELECT count(*) CONTEO FROM RO_T_DETALLE_ALQUILERES WHERE PERIODO = '$periodo'";

        // DEBUG: Log de la consulta
        error_log("🔍 conteoDetalle SQL: " . $sql);

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("❌ Error en conteoDetalle SQL: " . print_r($errors, true));
                return array('CONTEO' => 0);
            }

            $v = sqlsrv_fetch_array($stmt);
            error_log("✅ conteoDetalle encontró {$v['CONTEO']} registros para periodo '{$periodo}'");
            return $v;

        } catch (\Throwable $th) {
            error_log("❌ Exception en conteoDetalle: " . $th->getMessage());
            print_r($th);
            return array('CONTEO' => 0);
        }

    }

    public function traerDetalle($periodo)
    {
        // Usar = en lugar de LIKE para búsqueda exacta
        $sql = "SELECT *, CAST(IMPORTE AS FLOAT) IMPORTE_PARSE, 
                ISNULL(AJUSTADO, 0) AS AJUSTADO 
                FROM RO_T_DETALLE_ALQUILERES 
                WHERE PERIODO = '$periodo'";

        // DEBUG: Log de la consulta
        error_log("🔍 traerDetalle SQL: " . $sql);

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("❌ Error en traerDetalle SQL: " . print_r($errors, true));
                return array();
            }

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }

            error_log("✅ traerDetalle encontró " . count($rows) . " registros para periodo '{$periodo}'");

            return $rows;

        } catch (\Throwable $th) {
            error_log("❌ Exception en traerDetalle: " . $th->getMessage());
            print_r($th);
            return array();
        }

    }
    public function insertarDetalle($data)
    {
        $sql = "INSERT INTO RO_T_DETALLE_ALQUILERES (PERIODO,NRO_SUCURS,DESC_SUCURS,IMPORTE,ID_CA) VALUES " . $data;

        // DEBUG: Log de la query
        error_log("🔧 SQL insertarDetalle - Longitud query: " . strlen($sql));
        error_log("🔧 SQL (primeros 300 caracteres): " . substr($sql, 0, 300));

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("❌ Error en insertarDetalle SQL: " . print_r($errors, true));
                throw new \Exception("Error al insertar: " . print_r($errors, true));
            }

            $rowsAffected = sqlsrv_rows_affected($stmt);
            error_log("✅ Filas insertadas correctamente: " . $rowsAffected);

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th) {
            error_log("❌ Exception en insertarDetalle: " . $th->getMessage());
            print_r($th);
            throw $th;
        }

    }
    public function actualizarDetalle($periodo, $idSucursal, $idConcepto, $importe, $userName, $porcentaje)
    {
        $userNameClause = $userName ? ", USUARIO = '$userName'" : "";
        $sql = "UPDATE RO_T_DETALLE_ALQUILERES 
                SET IMPORTE = '$importe'" . $userNameClause . ", 
                    FECHA_MODIF = GETDATE(), 
                    PORCENTAJE_APLICADO = '$porcentaje' 
                WHERE PERIODO = '$periodo' 
                  AND NRO_SUCURS = '$idSucursal' 
                  AND ID_CA = '$idConcepto'";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                throw new \Exception("Error en UPDATE: " . print_r(sqlsrv_errors(), true));
            }

            return true;

        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function consultarMesesDetalle($periodoPasado, $periodo)
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

        try {

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }



    }

    function execSpAlquileres($periodo)
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

        // LOG: Información del entorno actual
        $entornoActual = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
        $nombreEntorno = ($entornoActual === 'uy') ? 'URUGUAY' : 'ARGENTINA';
        error_log("🔍 execSpAlquileres - Entorno: {$nombreEntorno}, Periodo: {$periodo}, Fecha: {$fechaUltimoDia}");

        // NOTA: Eliminamos la verificación previa porque:
        // 1. La tabla RO_T_INTEGRAL_TANGO_2 puede contener datos de ambos entornos
        // 2. La conexión $this->cid_central ya está configurada para el entorno correcto
        // 3. El Stored Procedure RO_SP_INTEGRAL_ALQUILERES debe manejar la validación internamente
        // 4. Esto evita falsos positivos al cambiar entre entornos

        // Enviamos el período original al SP (el SP maneja internamente el formato y validación)
        $sql = " EXEC RO_SP_INTEGRAL_ALQUILERES '$periodo';";

        error_log("📤 Ejecutando SP: {$sql}");

        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);

            if (!$stmt) {
                $errors = sqlsrv_errors();
                error_log("❌ Error al ejecutar SP: " . print_r($errors, true));
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

            // LOG: Ver qué devolvió el SP
            error_log("📥 Respuesta del SP - Total filas: " . count($rows));
            if (count($rows) > 0) {
                error_log("📋 Primera fila del SP: " . print_r($rows[0], true));
            }

            // Si no hay resultados, significa que se procesó correctamente sin insertar registros
            if (empty($rows)) {
                error_log("✅ SP sin resultados - Procesado correctamente");
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

            if (isset($rows[0][0])) {
                error_log("🔍 Verificando rows[0][0]: '{$rows[0][0]}'");

                if ($rows[0][0] == 'ERROR') {
                    error_log("❌ SP devolvió ERROR - período ya procesado según el SP");

                    // Verificar manualmente si realmente existe
                    $verificarManual = "SELECT COUNT(*) as Total FROM RO_T_INTEGRAL_TANGO_2 
                                       WHERE MODULO = 'ALQUILERES' 
                                       AND (FECHA = '$fechaUltimoDia' OR PERIODO = '$periodo')";
                    $stmtVerif = sqlsrv_query($this->cid_central, $verificarManual);
                    $resultVerif = sqlsrv_fetch_array($stmtVerif);

                    $totalEncontrado = intval($resultVerif['Total'] ?? 0);

                    error_log("🔎 Verificación manual - Registros encontrados: {$totalEncontrado}");

                    // Si la verificación manual confirma que NO hay registros, hay un problema con el SP
                    if ($totalEncontrado == 0) {
                        error_log("⚠️ INCONSISTENCIA DETECTADA:");
                        error_log("  - El SP devuelve ERROR (indica que ya existe)");
                        error_log("  - Pero la verificación manual NO encuentra registros");
                        error_log("  - Periodo enviado al SP: '{$periodo}'");
                        error_log("  - Fecha calculada: '{$fechaUltimoDia}'");

                        // Verificar qué registros existen con condiciones similares
                        $debugSql = "SELECT TOP 5 * FROM RO_T_INTEGRAL_TANGO_2 
                                    WHERE MODULO = 'ALQUILERES' 
                                    ORDER BY FECHA DESC";
                        $stmtDebug = sqlsrv_query($this->cid_central, $debugSql);

                        error_log("🔎 Últimos 5 registros de ALQUILERES en RO_T_INTEGRAL_TANGO_2:");
                        $debugCount = 0;
                        while ($rowDebug = sqlsrv_fetch_array($stmtDebug, SQLSRV_FETCH_ASSOC)) {
                            $fechaDebug = $rowDebug['FECHA'];
                            if ($fechaDebug instanceof DateTime) {
                                $fechaDebug = $fechaDebug->format('Y-m-d');
                            }
                            error_log("  #{$debugCount}: FECHA={$fechaDebug}, PERIODO={$rowDebug['PERIODO']}, NUM_SUCURSAL={$rowDebug['NUM_SUCURSAL']}");
                            $debugCount++;
                        }

                        echo json_encode([
                            'status' => 'error',
                            'code' => 5,
                            'message' => 'Error: El SP indica que el período ya está procesado, pero no se encontraron registros en la base de datos',
                            'periodo' => $periodo,
                            'fecha_verificada' => $fechaUltimoDia,
                            'entorno' => $nombreEntorno,
                            'verificacion_manual' => $totalEncontrado,
                            'detalle' => 'Posible causa: El SP puede estar usando un formato de fecha/período diferente o validando contra otra tabla. Revisa los logs del servidor para ver los últimos registros existentes.',
                            'sugerencia' => 'Verifica que no haya registros previos con fechas similares o períodos en formato diferente (ej: "03-2024" vs "3-2024")'
                        ]);
                        return;
                    }

                    // Si realmente hay registros, entonces sí está procesado
                    echo json_encode([
                        'status' => 'error',
                        'code' => 1,
                        'message' => 'El período ya se encuentra procesado',
                        'raw_response' => $rows[0][0],
                        'periodo' => $periodo,
                        'fecha_verificada' => $fechaUltimoDia,
                        'entorno' => $nombreEntorno,
                        'verificacion_manual' => $totalEncontrado,
                        'detalle' => "Verificación confirmó {$totalEncontrado} registro(s) existente(s) en RO_T_INTEGRAL_TANGO_2"
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
    function verificarProcesadoPorPeriodo($periodo)
    {
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

    function verificarProcesado($fecha)
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

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }

    }

    function cerrarPeriodo($periodo)
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

    function abrirPeriodo($periodo)
    {
        $sql = "DELETE FROM RO_T_DETALLE_ALQUILERES_ESTADO WHERE PERIODO = '$periodo';";

        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);
            return true;

        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function checkCierrePeriodoAnt($mesAnterior)
    {
        $sql = "SELECT CASE
        WHEN EXISTS (
            SELECT 1
            FROM RO_T_DETALLE_ALQUILERES_ESTADO  
            WHERE PERIODO = '$mesAnterior'
        ) THEN 1
        ELSE 0
        END AS RegistroExiste;";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }


            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }

    }

    function traerEstado($periodo)
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

    public function traerCoeficiente($periodo)
    {

        // LOG: Período original recibido
        error_log("DEBUG traerCoeficiente - Período original: " . $periodo);

        // Normalizar el período: separar mes y año, convertir mes a entero, y reconstruir
        // Esto asegura que "07-2025" se convierta en "7-2025" para coincidir con la BD
        $partes = explode('-', $periodo);
        if (count($partes) == 2) {
            $mes = (int) $partes[0]; // Elimina ceros a la izquierda
            $anio = $partes[1];
            $periodo = $mes . '-' . $anio;
        }

        error_log("DEBUG traerCoeficiente - Período normalizado: " . $periodo);

        $sql = "SELECT COEFICIENTE FROM RO_T_COEFICIENTES_AJUSTE  WHERE PERIODO = '$periodo'";
        error_log("DEBUG traerCoeficiente - SQL: " . $sql);

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if (sqlsrv_fetch($stmt) === true) {

                $coeficiente = sqlsrv_get_field($stmt, 0);
                error_log("DEBUG traerCoeficiente - Coeficiente encontrado: " . $coeficiente);
                return $coeficiente;
            } else {
                error_log("DEBUG traerCoeficiente - NO se encontró coeficiente (sqlsrv_fetch retornó false)");

                // Verificar si hay error en la consulta
                $errors = sqlsrv_errors();
                if ($errors) {
                    error_log("DEBUG traerCoeficiente - Errores SQL: " . print_r($errors, true));
                }

                return 0;
            }

        } catch (\Throwable $th) {
            error_log("DEBUG traerCoeficiente - Exception: " . $th->getMessage());
            throw $th;
        }
    }

    public function aplicarAjuste($nroSucursal, $concepto, $importe, $periodo)
    {

        // Primero verificar si ya está ajustado
        $sqlCheck = "SELECT AJUSTADO, IMPORTE FROM RO_T_DETALLE_ALQUILERES WHERE PERIODO = '$periodo' AND NRO_SUCURS = '$nroSucursal' AND ID_CA = '$concepto'";

        try {
            $stmtCheck = sqlsrv_query($this->cid_central, $sqlCheck);

            if (sqlsrv_fetch($stmtCheck) === true) {
                $ajustado = sqlsrv_get_field($stmtCheck, 0);
                $importeActual = sqlsrv_get_field($stmtCheck, 1);

                // Si ya está ajustado, no hacer nada
                if ($ajustado == 1) {
                    error_log("DEBUG aplicarAjuste - Registro ya ajustado: Sucursal=$nroSucursal, Concepto=$concepto, Periodo=$periodo");
                    return false; // Ya está ajustado
                }
            }

            // Si no está ajustado, proceder con el UPDATE
            $sql = "UPDATE RO_T_DETALLE_ALQUILERES SET IMPORTE = '$importe', FECHA_MODIF = GETDATE() ,FECHA_AJUSTE = GETDATE(), AJUSTADO = 1 WHERE PERIODO = '$periodo' AND NRO_SUCURS = '$nroSucursal' AND ID_CA = '$concepto' AND (AJUSTADO IS NULL OR AJUSTADO = 0)";

            $stmt = sqlsrv_query($this->cid_central, $sql);

            // Verificar cuántas filas se afectaron
            $rowsAffected = sqlsrv_rows_affected($stmt);
            error_log("DEBUG aplicarAjuste - Filas afectadas: $rowsAffected para Sucursal=$nroSucursal, Concepto=$concepto");

            return ($rowsAffected > 0);

        } catch (\Throwable $th) {
            error_log("DEBUG aplicarAjuste - Exception: " . $th->getMessage());
            print_r($th);
            return false;
        }

    }

    public function comprobarAjuste($nroSucursal, $concepto, $periodo)
    {

        $sql = "SELECT AJUSTADO FROM RO_T_DETALLE_ALQUILERES WHERE PERIODO = '$periodo' AND NRO_SUCURS = '$nroSucursal' AND ID_CA = '$concepto'";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            if (sqlsrv_fetch($stmt) === true) {
                $ajustado = sqlsrv_get_field($stmt, 0);
                return $ajustado;
            } else {
                return 0;
            }

        } catch (\Throwable $th) {
            error_log("Error en Alquiler: " . $th->getMessage());
        }
    }

    public function comprobarAjusteGeneral($periodo)
    {
        // Verificar si hay registros sin ajustar de los conceptos 4, 5 y 18 que tengan importe diferente de 0
        $sql = "SELECT COUNT(*) as REGISTROS_SIN_AJUSTAR 
                FROM RO_T_DETALLE_ALQUILERES 
                WHERE PERIODO = '$periodo' 
                AND AJUSTADO IS NULL 
                AND ID_CA IN (4, 5, 18) 
                AND IMPORTE != 0";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            if (sqlsrv_fetch($stmt) === true) {
                $count = sqlsrv_get_field($stmt, 0);
                // Si count > 0, hay registros sin ajustar, retornar 1 (error)
                // Si count = 0, todo está ajustado, retornar 0 (ok)
                return ($count > 0) ? 1 : 0;
            } else {
                return 1; // Error en la consulta, asumir que no está ajustado
            }

        } catch (\Throwable $th) {
            error_log("Error en comprobarAjusteGeneral: " . $th->getMessage());
            return 1; // Error, asumir que no está ajustado
        }
    }

    public function revertirProcesamiento($periodo)
    {

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

    /**
     * Elimina todos los registros de una sucursal específica para un período
     * Esto permite excluir sucursales sin costos del procesamiento
     */
    public function eliminarSucursalDelPeriodo($periodo, $nroSucursal)
    {

        $sql = "DELETE FROM RO_T_DETALLE_ALQUILERES 
                WHERE PERIODO = '$periodo' 
                AND NRO_SUCURS = '$nroSucursal'";

        try {
            error_log("🗑️ Eliminando sucursal $nroSucursal del período $periodo");

            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new \Exception("Error al eliminar sucursal: " . print_r($errors, true));
            }

            $rowsAffected = sqlsrv_rows_affected($stmt);

            error_log("✅ Eliminados $rowsAffected registros de sucursal $nroSucursal");

            return [
                'rowsAffected' => $rowsAffected,
                'sucursal' => $nroSucursal,
                'periodo' => $periodo
            ];

        } catch (\Throwable $th) {
            error_log("❌ Error al eliminar sucursal: " . $th->getMessage());
            throw $th;
        }
    }

    /**
     * Obtiene las sucursales que tienen total = 0 en un período
     */
    public function obtenerSucursalesSinCostos($periodo)
    {

        $sql = "SELECT 
                    NRO_SUCURS,
                    DESC_SUCURS,
                    SUM(CAST(IMPORTE AS FLOAT)) AS TOTAL
                FROM RO_T_DETALLE_ALQUILERES
                WHERE PERIODO = '$periodo'
                GROUP BY NRO_SUCURS, DESC_SUCURS
                HAVING SUM(CAST(IMPORTE AS FLOAT)) = 0
                ORDER BY NRO_SUCURS";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new \Exception("Error al obtener sucursales sin costos: " . print_r($errors, true));
            }

            $rows = array();
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }

            return $rows;

        } catch (\Throwable $th) {
            error_log("❌ Error al obtener sucursales sin costos: " . $th->getMessage());
            throw $th;
        }
    }

}


