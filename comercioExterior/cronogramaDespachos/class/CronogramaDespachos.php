<?php
class CronogramaDespachos {
    private $cid_central;

    function __construct() {
        require_once __DIR__ . '/../../../class/conexion.php';
        require_once __DIR__ . '/CronogramaFechas.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($db);
    }

    /**
     * Obtiene todos los despachos para el cronograma
     */
    public function obtenerDespachos() {
        try {
            $sql = "SELECT A.ID, A.ID_PADRE, A.ORDEN_COMPRA, A.COD_PROVEE, A.PROVEEDOR, A.CONTENEDOR,
                    CAST(C.FECHA_INGRESO AS DATE) FECHA_ING_OC, A.FECHA_EST_EMB,
                    A.FECHA_EMB, A.FECHA_ARR, A.FECHA_DESP_ADU,
                    COALESCE(CAST(A.FECHA_RECIBIDO AS DATE), D.FECHA_REC) AS FECHA_REC, A.ETA_CONFIRMADA,
                    A.FECHA_DISTRI, A.DIST_ORIGEN,
                    -- Clave de agrupacion para emitir un solo badge por embarque.
                    -- Prioridad 1: ID_PADRE, el vinculo explicito que se carga
                    -- desde gestion de despachos.
                    -- Prioridad 2: COD_PROVEE + CONTENEDOR. CONTENEDOR no es un
                    -- contenedor fisico sino un codigo de oleada de temporada
                    -- (INV01-26 lo comparten 13 proveedores distintos), asi que
                    -- agrupar solo por CONTENEDOR juntaria embarques ajenos.
                    -- Con el proveedor adentro, el grupo es 'mismo proveedor,
                    -- misma oleada', que si es un embarque partido en varias OCs.
                    CASE
                        WHEN NULLIF(LTRIM(RTRIM(A.CONTENEDOR)), '') IS NULL
                            THEN COALESCE(A.ID_PADRE, A.ID)
                        ELSE MIN(COALESCE(A.ID_PADRE, A.ID)) OVER (
                                PARTITION BY A.COD_PROVEE, LTRIM(RTRIM(A.CONTENEDOR)))
                    END AS ID_GRUPO
                    FROM RO_T_IMPORTACIONES_ENCABEZADO A
                    LEFT JOIN CPA35 C ON A.ORDEN_COMPRA = C.N_ORDEN_CO
                    LEFT JOIN
                    (
                        SELECT MAX(CAST(FECHA_MOV AS DATE)) FECHA_REC, N_ORDEN_CO
                        FROM STA20
                        WHERE TCOMP_IN_S = 'RP' AND FECHA_MOV >= GETDATE()-360
                        GROUP BY N_ORDEN_CO
                    ) D ON A.ORDEN_COMPRA = D.N_ORDEN_CO
                    WHERE A.FECHA_MOV >= GETDATE()-360
                    ORDER BY A.FECHA_MOV DESC";
            
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error en la consulta: " . print_r($errors, true));
            }
            
            // Se leen una sola vez para derivar FECHA_DISTRI de todas las filas.
            $parametros = CronogramaFechas::obtenerParametros($this->cid_central);

            $despachos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Formatear fechas
                if ($row['FECHA_ING_OC'] && is_object($row['FECHA_ING_OC'])) {
                    $row['FECHA_ING_OC'] = $row['FECHA_ING_OC']->format('Y-m-d');
                }
                if ($row['FECHA_EST_EMB'] && is_object($row['FECHA_EST_EMB'])) {
                    $row['FECHA_EST_EMB'] = $row['FECHA_EST_EMB']->format('Y-m-d');
                }
                if ($row['FECHA_EMB'] && is_object($row['FECHA_EMB'])) {
                    $row['FECHA_EMB'] = $row['FECHA_EMB']->format('Y-m-d');
                }
                if ($row['FECHA_ARR'] && is_object($row['FECHA_ARR'])) {
                    $row['FECHA_ARR'] = $row['FECHA_ARR']->format('Y-m-d');
                }
                if ($row['FECHA_DESP_ADU'] && is_object($row['FECHA_DESP_ADU'])) {
                    $row['FECHA_DESP_ADU'] = $row['FECHA_DESP_ADU']->format('Y-m-d');
                }
                if ($row['FECHA_REC'] && is_object($row['FECHA_REC'])) {
                    $row['FECHA_REC'] = $row['FECHA_REC']->format('Y-m-d');
                }
                if ($row['FECHA_DISTRI'] && is_object($row['FECHA_DISTRI'])) {
                    $row['FECHA_DISTRI'] = $row['FECHA_DISTRI']->format('Y-m-d');
                }

                // Convertir ETA_CONFIRMADA a entero (manejar NULL como 0)
                if (isset($row['ETA_CONFIRMADA']) && $row['ETA_CONFIRMADA'] !== null) {
                    $row['ETA_CONFIRMADA'] = (int)$row['ETA_CONFIRMADA'];
                } else {
                    $row['ETA_CONFIRMADA'] = 0;
                }

                // El front compara y agrupa por estos IDs, asi que tienen que
                // viajar como enteros y no como strings.
                $row['ID']       = (int)$row['ID'];
                $row['ID_GRUPO'] = (int)$row['ID_GRUPO'];
                $row['ID_PADRE'] = ($row['ID_PADRE'] === null) ? null : (int)$row['ID_PADRE'];

                // Origen de FECHA_DISTRI: A automatica, M manual, C confirmada.
                $row['DIST_ORIGEN'] = ($row['DIST_ORIGEN'] === null)
                    ? 'A' : strtoupper(trim($row['DIST_ORIGEN']));

                // Con DIST_ORIGEN = 'A', la columna es solo una cache y la
                // formula es la verdad: la recepcion llega por Tango, no por
                // esta aplicacion, asi que no hay donde engancharse para
                // recalcular al recibir. Derivarla en la lectura mantiene el
                // calendario correcto sin escribir en cada carga.
                $row['FECHA_DISTRI'] = CronogramaFechas::derivarDistribucion($row, $parametros);

                /* LA CADENA ESTIMADA VIAJA CALCULADA DESDE ACA.
                   calcularFechasEstimadas() la armaba en el navegador sumando
                   dias por su cuenta; la carga inicial hacia lo mismo con otros
                   numeros. Aunque los dias salgan ahora de la misma tabla, dos
                   implementaciones de la misma suma se separan tarde o temprano
                   por cualquier detalle. Calculandola una sola vez, la unica
                   forma de que las dos pantallas difieran es que difieran los
                   parametros.

                   Se resuelve por fila y no de a una consulta por fila:
                   obtenerParametros() cachea por request, asi que las 400 y
                   pico de filas del cronograma cuestan un solo SELECT.

                   EL ARRIBO EN FIRME ENTRA A LA CADENA. Con ETA_CONFIRMADA = 1
                   la nacionalizacion estimada cuelga del arribo real y no del
                   proyectado, que es lo que el JS no hacia: dibujaba la
                   nacionalizacion sobre embarque + 45 aunque la naviera ya
                   hubiera confirmado otra fecha. */
                $arriboFirme = ((int) $row['ETA_CONFIRMADA'] === 1 && !empty($row['FECHA_ARR']))
                    ? $row['FECHA_ARR']
                    : null;

                $cadena = CronogramaFechas::cadenaDeFechas(
                    $this->cid_central,
                    CronogramaFechas::fechaBaseEmbarque($row),
                    $arriboFirme,
                    $row['FECHA_REC']
                );

                $row['FECHAS_EST'] = $cadena['fechas'];

                $despachos[] = $row;
            }
            
            return $despachos;
            
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Rubros y cantidades pedidas de cada orden de compra.
     *
     * Va aparte y no como join en obtenerDespachos(): una OC tiene varios
     * rubros, asi que el join multiplicaria las filas del cronograma y
     * romperia el conteo de eventos del calendario.
     *
     * @param  array $ordenesCompra numeros de OC tal como vienen del encabezado
     * @return array ['<OC>' => [['rubro' => ..., 'cantidad' => float], ...], ...]
     */
    public function obtenerRubrosPorOC(array $ordenesCompra) {
        // Los valores viajan verbatim a proposito. ORDEN_COMPRA y
        // CPA35.N_ORDEN_CO son varchar(14) con collation Latin1_General_BIN, o
        // sea comparacion binaria, y en la base el 100% de los valores tiene
        // espacios a la izquierda (' 0000100014381'). Un trim los dejaria sin
        // matchear.
        $ocs = [];
        foreach ($ordenesCompra as $oc) {
            if ($oc !== null && $oc !== '') {
                $ocs[$oc] = true;
            }
        }
        $ocs = array_keys($ocs);

        if (empty($ocs)) {
            return [];
        }

        try {
            $resultado = [];

            // sqlsrv no expande arrays en un IN, y el limite de parametros por
            // sentencia es 2100. Se procesa en lotes para no chocarlo cuando el
            // cronograma crezca.
            foreach (array_chunk($ocs, 500) as $lote) {
                $placeholders = implode(',', array_fill(0, count($lote), '?'));

                // Sin COLLATE en el join de COD_ARTICU: CPA36.COD_ARTICU y
                // SOF_RUBROS_TANGO.COD_ARTICU son varchar(15) Latin1_General_BIN
                // en los dos entornos, o sea que ya coinciden.
                $sql = "SELECT A.N_ORDEN_CO, C.RUBRO, SUM(B.CAN_PEDIDA) AS CAN_PEDIDA
                        FROM CPA35 A
                        INNER JOIN CPA36 B ON A.N_ORDEN_CO = B.N_ORDEN_CO
                        LEFT JOIN SOF_RUBROS_TANGO C ON B.COD_ARTICU = C.COD_ARTICU
                        WHERE A.N_ORDEN_CO IN ($placeholders)
                        GROUP BY A.N_ORDEN_CO, C.RUBRO
                        ORDER BY A.N_ORDEN_CO, SUM(B.CAN_PEDIDA) DESC";

                $stmt = sqlsrv_query($this->cid_central, $sql, $lote);

                if ($stmt === false) {
                    error_log('obtenerRubrosPorOC: ' . print_r(sqlsrv_errors(), true));
                    continue;
                }

                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $oc = $row['N_ORDEN_CO'];
                    if (!isset($resultado[$oc])) {
                        $resultado[$oc] = [];
                    }
                    // Articulo sin rubro mapeado en Tango.
                    $rubro = ($row['RUBRO'] === null || trim($row['RUBRO']) === '')
                        ? 'SIN RUBRO'
                        : trim($row['RUBRO']);

                    $resultado[$oc][] = [
                        'rubro'    => $rubro,
                        'cantidad' => (float)$row['CAN_PEDIDA'],
                    ];
                }
            }

            return $resultado;

        } catch (Exception $e) {
            error_log('obtenerRubrosPorOC: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Alias de 2 letras por proveedor: ['<COD_PROVEE>' => '<ALIAS>', ...]
     */
    public function obtenerAliasProveedores() {
        $sql = "SELECT COD_PROVEE, ALIAS
                FROM RO_T_IMPORTACIONES_PROVEEDOR_ALIAS
                WHERE ACTIVO = 1";

        $stmt = sqlsrv_query($this->cid_central, $sql);
        if ($stmt === false) {
            error_log('obtenerAliasProveedores: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $alias = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $alias[$row['COD_PROVEE']] = strtoupper(trim($row['ALIAS']));
        }
        return $alias;
    }

    /**
     * Icono por rubro: ['<RUBRO>' => '<bi-clase|emoji>', ...]
     */
    public function obtenerIconosRubro() {
        $sql = "SELECT RUBRO, ICONO
                FROM RO_T_IMPORTACIONES_RUBRO_ICONO
                WHERE ACTIVO = 1
                ORDER BY ORDEN";

        $stmt = sqlsrv_query($this->cid_central, $sql);
        if ($stmt === false) {
            error_log('obtenerIconosRubro: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $iconos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $iconos[trim($row['RUBRO'])] = trim($row['ICONO']);
        }
        return $iconos;
    }

    /**
     * Parametros de dias del cronograma, tal cual estan en la tabla.
     *
     * DELEGA EN CronogramaFechas y no repite la consulta. Este metodo tenia su
     * propia copia de los defaults -45/7/2/10/1- identica a la que tenia
     * CronogramaFechas::obtenerParametros() e identica a la del objeto
     * parametrosDias de cronograma.js. Tres copias del mismo numero es como se
     * llega a que una pantalla diga arribo + 2 y la otra arribo + 7.
     *
     * Ya no hay defaults en ningun lado: si falta una clave, el front lo dice.
     * Ver el comentario de CronogramaFechas::obtenerParametros().
     */
    public function obtenerParametros() {
        require_once __DIR__ . '/CronogramaFechas.php';
        return CronogramaFechas::obtenerParametros($this->cid_central);
    }

    /**
     * Determina el estado actual del despacho
     */
    public static function determinarEstado($despacho) {
        if (!empty($despacho['FECHA_REC'])) {
            return 'recibido';
        } else if (!empty($despacho['FECHA_DESP_ADU'])) {
            return 'despachado';
        } else if (!empty($despacho['FECHA_ARR'])) {
            return 'arribado';
        } else if (!empty($despacho['FECHA_EMB'])) {
            return 'embarcado';
        } else {
            // Si solo existe FECHA_EST_EMB, está en origen
            return 'origen';
        }
    }
}
?>
