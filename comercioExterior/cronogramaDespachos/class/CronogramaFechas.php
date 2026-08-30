<?php
/**
 * Reglas de fechas del cronograma que necesitan los dos caminos de escritura:
 * el drag & drop del cronograma y la edicion desde gestion de despachos.
 *
 * La columna de distribucion es FECHA_DISTRI (no FECHA_DIST): en central ya
 * existia con ese nombre y en uy la creo el script 05.
 */
class CronogramaFechas
{
    /** Campos de fecha que el cronograma puede escribir. */
    const CAMPOS_EDITABLES = [
        'FECHA_EST_EMB',
        'FECHA_EMB',
        'FECHA_ARR',
        'FECHA_DESP_ADU',
        'FECHA_DISTRI',
    ];

    /**
     * FECHA_REC no entra: sale de Tango (STA20, comprobantes 'RP') y el
     * cronograma no la escribe.
     */
    public static function esCampoEditable($campo)
    {
        return in_array($campo, self::CAMPOS_EDITABLES, true);
    }

    /**
     * Parametros de dias, con los defaults que antes estaban hardcodeados en
     * el JS como red de seguridad.
     */
    public static function obtenerParametros($conn)
    {
        $parametros = [
            'DIAS_EMB_ARR'  => 45,
            'DIAS_ARR_DESP' => 7,
            'DIAS_DESP_REC' => 3,
            'DIAS_ARR_DIST' => 10,
        ];

        $stmt = sqlsrv_query($conn, "SELECT CLAVE, VALOR FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA");
        if ($stmt === false) {
            error_log('CronogramaFechas::obtenerParametros: ' . print_r(sqlsrv_errors(), true));
            return $parametros;
        }

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $parametros[trim($row['CLAVE'])] = (int) $row['VALOR'];
        }
        return $parametros;
    }

    /**
     * Fecha de distribucion derivada del arribo.
     *
     * Se calcula igual sobre un arribo confirmado que sobre uno estimado, y
     * tambien cuando la fecha original ya paso: el objetivo es que la
     * proyeccion siga al arribo, no que se congele.
     *
     * @return string|null 'Y-m-d'
     */
    public static function calcularDistribucion($fechaArribo, $diasArrDist)
    {
        if (empty($fechaArribo)) {
            return null;
        }

        $fecha = ($fechaArribo instanceof DateTime)
            ? clone $fechaArribo
            : DateTime::createFromFormat('Y-m-d', substr((string) $fechaArribo, 0, 10));

        if (!$fecha) {
            return null;
        }

        $fecha->modify('+' . (int) $diasArrDist . ' days');
        return $fecha->format('Y-m-d');
    }

    /**
     * Aplica el recalculo de FECHA_DISTRI a las OCs indicadas tras un cambio
     * de FECHA_ARR.
     *
     * Solo toca las filas con DIST_ORIGEN = 'A' (automatica). 'M' (movida a
     * mano) y 'C' (confirmada) quedan intactas: sin esa distincion el
     * recalculo pisaria lo que abastecimiento ajusto a proposito.
     *
     * @param  resource $conn
     * @param  array    $ids          IDs de encabezado a recalcular
     * @param  string   $fechaArribo  nueva fecha de arribo 'Y-m-d'
     * @param  int|null $diasArrDist  si no viene, se lee de parametros
     * @return array    [idEncabezado => nuevaFechaDistri] de las filas tocadas
     */
    public static function recalcularDistribucion($conn, array $ids, $fechaArribo, $diasArrDist = null)
    {
        if (empty($ids)) {
            return [];
        }

        if ($diasArrDist === null) {
            $parametros = self::obtenerParametros($conn);
            $diasArrDist = $parametros['DIAS_ARR_DIST'];
        }

        $nueva = self::calcularDistribucion($fechaArribo, $diasArrDist);
        if ($nueva === null) {
            return [];
        }

        $ids = array_values(array_map('intval', $ids));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Se leen primero las filas que van a cambiar, para poder devolverlas
        // y que el historial registre una linea por cada una.
        $sqlLeer = "SELECT ID FROM RO_T_IMPORTACIONES_ENCABEZADO
                    WHERE ID IN ($placeholders)
                      AND DIST_ORIGEN = 'A'
                      AND (FECHA_DISTRI IS NULL OR FECHA_DISTRI <> ?)";

        $stmt = sqlsrv_query($conn, $sqlLeer, array_merge($ids, [$nueva]));
        if ($stmt === false) {
            error_log('CronogramaFechas::recalcularDistribucion (lectura): ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $afectados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $afectados[(int) $row['ID']] = $nueva;
        }

        if (empty($afectados)) {
            return [];
        }

        $idsAfectados = array_keys($afectados);
        $ph = implode(',', array_fill(0, count($idsAfectados), '?'));

        $sqlUpdate = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                      SET FECHA_DISTRI = ?
                      WHERE ID IN ($ph) AND DIST_ORIGEN = 'A'";

        $stmt = sqlsrv_query($conn, $sqlUpdate, array_merge([$nueva], $idsAfectados));
        if ($stmt === false) {
            error_log('CronogramaFechas::recalcularDistribucion (update): ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        return $afectados;
    }

    /**
     * Lee los valores actuales de los campos de fecha de varias OCs.
     * Lo usan el historial y la validacion de coherencia.
     *
     * @return array [idEncabezado => ['FECHA_ARR' => 'Y-m-d'|null, ...]]
     */
    public static function leerFechas($conn, array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $ids = array_values(array_map('intval', $ids));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT ID, ORDEN_COMPRA, CONTENEDOR, DIST_ORIGEN, ETA_CONFIRMADA,
                       FECHA_EST_EMB, FECHA_EMB, FECHA_ARR, FECHA_DESP_ADU, FECHA_DISTRI,
                       CAST(FECHA_RECIBIDO AS DATE) AS FECHA_REC
                FROM RO_T_IMPORTACIONES_ENCABEZADO
                WHERE ID IN ($placeholders)";

        $stmt = sqlsrv_query($conn, $sql, $ids);
        if ($stmt === false) {
            error_log('CronogramaFechas::leerFechas: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $salida = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($row as $campo => $valor) {
                if ($valor instanceof DateTime) {
                    $row[$campo] = $valor->format('Y-m-d');
                }
            }
            $salida[(int) $row['ID']] = $row;
        }
        return $salida;
    }
}
