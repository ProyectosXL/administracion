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

    /** Etiquetas legibles, para mensajes de error y para el historial. */
    const ETIQUETAS_CAMPOS = [
        'FECHA_EST_EMB'  => 'Embarque estimado',
        'FECHA_EMB'      => 'Embarque real',
        'FECHA_ARR'      => 'Arribo',
        'FECHA_DESP_ADU' => 'Despacho de aduana',
        'FECHA_REC'      => 'Recepción',
        'FECHA_DISTRI'   => 'Distribución',
    ];

    /**
     * Orden cronologico del flujo. Lo usa la validacion de coherencia para
     * saber que va antes y que va despues de que.
     */
    const ORDEN_FLUJO = [
        'FECHA_EST_EMB',
        'FECHA_EMB',
        'FECHA_ARR',
        'FECHA_DESP_ADU',
        'FECHA_REC',
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

    public static function etiqueta($campo)
    {
        return isset(self::ETIQUETAS_CAMPOS[$campo]) ? self::ETIQUETAS_CAMPOS[$campo] : $campo;
    }

    /** Valida el formato y que la fecha sea real (rechaza 2026-02-31). */
    public static function esFechaValida($fecha)
    {
        if (!is_string($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    /**
     * Una fecha es "real" (en firme) o una estimacion.
     *
     * Importa para la validacion de coherencia: chocar contra una fecha real
     * bloquea el movimiento, chocar contra una estimacion solo advierte.
     */
    public static function esFechaReal($campo, array $fila)
    {
        switch ($campo) {
            case 'FECHA_EST_EMB':
                return false;  // es una estimacion por definicion
            case 'FECHA_ARR':
                return (int) $fila['ETA_CONFIRMADA'] === 1;
            case 'FECHA_DISTRI':
                return $fila['DIST_ORIGEN'] === 'C';
            default:
                // FECHA_EMB, FECHA_DESP_ADU y FECHA_REC son hechos cargados.
                return !empty($fila[$campo]);
        }
    }

    /**
     * Coherencia cronologica del movimiento propuesto.
     *
     * Se compara la fecha nueva contra todas las demas del mismo encabezado,
     * segun ORDEN_FLUJO. Un choque contra una fecha REAL ya persistida
     * bloquea; contra una estimacion solo advierte, porque las estimaciones
     * se mueven solas al recalcularse.
     *
     * @return array ['errores' => [...], 'advertencias' => [...]]
     */
    public static function validarCoherencia($campo, $fechaNueva, array $fila)
    {
        $errores = [];
        $advertencias = [];

        $posicion = array_search($campo, self::ORDEN_FLUJO, true);
        if ($posicion === false) {
            return ['errores' => ['Campo fuera del flujo: ' . $campo], 'advertencias' => []];
        }

        foreach (self::ORDEN_FLUJO as $i => $otro) {
            if ($otro === $campo || empty($fila[$otro])) {
                continue;
            }

            $valorOtro = substr((string) $fila[$otro], 0, 10);
            $anterior  = ($i < $posicion);

            $invierte = $anterior
                ? ($valorOtro > $fechaNueva)   // algo previo quedaria despues
                : ($valorOtro < $fechaNueva);  // algo posterior quedaria antes

            if (!$invierte) {
                continue;
            }

            $etiquetaOtro = self::etiqueta($otro);
            $etiquetaEste = self::etiqueta($campo);

            $mensaje = $anterior
                ? "{$etiquetaEste} quedaría antes de {$etiquetaOtro} ({$valorOtro})"
                : "{$etiquetaEste} quedaría después de {$etiquetaOtro} ({$valorOtro})";

            if (self::esFechaReal($otro, $fila)) {
                $errores[] = $mensaje . ', que es una fecha en firme';
            } else {
                $advertencias[] = $mensaje . ', que es una estimación';
            }
        }

        return ['errores' => $errores, 'advertencias' => $advertencias];
    }

    /**
     * Reglas de arrastre: que campos exigen observacion obligatoria.
     *
     * @return string|null motivo por el que la observacion es obligatoria
     */
    public static function motivoObservacionObligatoria($campo, array $fila)
    {
        if ($campo === 'FECHA_ARR' && (int) $fila['ETA_CONFIRMADA'] === 1) {
            return 'El arribo está confirmado (ETA confirmada)';
        }
        if ($campo === 'FECHA_DISTRI' && $fila['DIST_ORIGEN'] === 'C') {
            return 'La fecha de distribución está confirmada';
        }
        return null;
    }

    /**
     * Inserta una fila en el historial de cambios de fecha.
     *
     * USUARIO sale de $_SESSION['usuario_dns'], que hoy no se puebla en este
     * modulo: comercioExterior no tiene autenticacion y esa variable la setea
     * setearDnsBaseName(), que solo se usa en otros flujos. Queda en NULL
     * hasta que haya un usuario de aplicacion de verdad.
     */
    public static function registrarHistorial($conn, array $datos)
    {
        $sql = "INSERT INTO RO_T_IMPORTACIONES_FECHAS_HIST
                (ID_ENCABEZADO, ORDEN_COMPRA, CONTENEDOR, CAMPO,
                 VALOR_ANTERIOR, VALOR_NUEVO, MOTIVO, OBSERVACION, USUARIO, ORIGEN)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            (int) $datos['idEncabezado'],
            isset($datos['ordenCompra']) ? $datos['ordenCompra'] : null,
            isset($datos['contenedor'])  ? $datos['contenedor']  : null,
            $datos['campo'],
            !empty($datos['valorAnterior']) ? $datos['valorAnterior'] : null,
            !empty($datos['valorNuevo'])    ? $datos['valorNuevo']    : null,
            isset($datos['motivo'])      ? $datos['motivo']      : null,
            isset($datos['observacion']) ? $datos['observacion'] : null,
            isset($datos['usuario'])     ? $datos['usuario']     : null,
            $datos['origen'],
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            error_log('CronogramaFechas::registrarHistorial: ' . print_r(sqlsrv_errors(), true));
            return false;
        }
        return true;
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
            'DIAS_DESP_REC' => 2,   // recepcion estimada = arribo + 9
            'DIAS_ARR_DIST' => 10,  // distribucion estimada = arribo + 10
            'DIAS_REC_DIST' => 1,   // con recepcion REAL, al dia siguiente
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

    /** Suma dias corridos a una fecha 'Y-m-d' o DateTime. */
    private static function sumarDias($fecha, $dias)
    {
        if (empty($fecha)) {
            return null;
        }

        $d = ($fecha instanceof DateTime)
            ? clone $fecha
            : DateTime::createFromFormat('Y-m-d', substr((string) $fecha, 0, 10));

        if (!$d) {
            return null;
        }

        $d->modify('+' . (int) $dias . ' days');
        return $d->format('Y-m-d');
    }

    /**
     * Fecha de distribucion.
     *
     * La distribucion es la salida del deposito central hacia los locales, o
     * sea que va DESPUES de la recepcion. De ahi las dos ramas:
     *
     *   - Con recepcion REAL: recepcion + DIAS_REC_DIST. En el deposito se
     *     distribuye casi siempre al dia siguiente de recibir, asi que en
     *     cuanto Tango confirma la recepcion esa es la referencia buena.
     *   - Sin recepcion: arribo + DIAS_ARR_DIST, que con los valores por
     *     defecto cae un dia despues de la recepcion estimada (arribo + 9).
     *
     * Se calcula igual sobre un arribo confirmado que sobre uno estimado, y
     * tambien cuando la fecha ya paso: la proyeccion sigue al arribo en vez
     * de congelarse.
     *
     * @return string|null 'Y-m-d'
     */
    public static function calcularDistribucion($fechaArribo, $diasArrDist, $fechaRecepcion = null, $diasRecDist = 1)
    {
        if (!empty($fechaRecepcion)) {
            return self::sumarDias($fechaRecepcion, $diasRecDist);
        }
        return self::sumarDias($fechaArribo, $diasArrDist);
    }

    /**
     * Fecha de distribucion que hay que MOSTRAR para una fila.
     *
     * Con DIST_ORIGEN = 'A' la columna FECHA_DISTRI es apenas una cache: la
     * verdad es la formula. Hace falta derivarla en cada lectura porque la
     * recepcion no entra por esta aplicacion sino por Tango (STA20,
     * comprobantes 'RP'), y no hay ningun punto donde engancharse para
     * recalcular cuando eso pasa. Sin esto, un contenedor que se recibe
     * manana se queda para siempre con la proyeccion vieja de arribo + 10 en
     * lugar de moverse a recepcion + 1.
     *
     * Con 'M' o 'C' se devuelve el valor guardado tal cual: son decisiones
     * humanas y ninguna formula las pisa.
     *
     * Que filas entran en el calculo, y por que:
     *   - no recibidas y con arribo  -> se proyecta: sirve para planificar,
     *     y cubre tanto lo del backfill como los contenedores nuevos.
     *   - recibidas y CON fecha ya cargada -> se reancla a recepcion + 1:
     *     son las que venian siguiendose y acaban de recibirse.
     *   - recibidas y SIN fecha -> queda null. Es el historico viejo:
     *     inventarle una distribucion pasada que nadie confirmo solo
     *     ensuciaria el calendario.
     *
     * @param  array $fila        con DIST_ORIGEN, FECHA_DISTRI, FECHA_ARR, FECHA_REC
     * @param  array $parametros  de obtenerParametros()
     * @return string|null 'Y-m-d'
     */
    public static function derivarDistribucion(array $fila, array $parametros)
    {
        $origen = isset($fila['DIST_ORIGEN']) ? $fila['DIST_ORIGEN'] : 'A';
        $guardada = isset($fila['FECHA_DISTRI']) ? $fila['FECHA_DISTRI'] : null;

        if ($origen !== 'A') {
            return $guardada;
        }

        $arribo    = isset($fila['FECHA_ARR']) ? $fila['FECHA_ARR'] : null;
        $recepcion = isset($fila['FECHA_REC']) ? $fila['FECHA_REC'] : null;

        if (!empty($recepcion)) {
            // Ya recibido: solo se sigue proyectando si venia con fecha.
            return empty($guardada)
                ? null
                : self::calcularDistribucion($arribo, $parametros['DIAS_ARR_DIST'],
                                             $recepcion, $parametros['DIAS_REC_DIST']);
        }

        if (empty($arribo)) {
            return $guardada;
        }

        return self::calcularDistribucion($arribo, $parametros['DIAS_ARR_DIST']);
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

        $parametros = self::obtenerParametros($conn);
        if ($diasArrDist === null) {
            $diasArrDist = $parametros['DIAS_ARR_DIST'];
        }
        $diasRecDist = $parametros['DIAS_REC_DIST'];

        // Cada OC puede tener su propia recepcion, asi que la fecha no es
        // necesariamente la misma para todo el grupo: se resuelve por fila.
        $actuales = self::leerFechas($conn, $ids);
        if (empty($actuales)) {
            return [];
        }

        $afectados = [];
        foreach ($actuales as $id => $fila) {
            // 'M' y 'C' no se tocan: si abastecimiento la movio a mano o la
            // confirmo, el recalculo no debe pisarla.
            if ($fila['DIST_ORIGEN'] !== 'A') {
                continue;
            }

            $nueva = self::calcularDistribucion(
                $fechaArribo, $diasArrDist, $fila['FECHA_REC'], $diasRecDist
            );

            if ($nueva !== null && $nueva !== $fila['FECHA_DISTRI']) {
                $afectados[$id] = $nueva;
            }
        }

        if (empty($afectados)) {
            return [];
        }

        // Un UPDATE por valor distinto. Son pocas filas (las OCs de un grupo)
        // y con la doble condicion DIST_ORIGEN = 'A' se evita cualquier
        // carrera contra una confirmacion simultanea.
        foreach ($afectados as $id => $nueva) {
            $stmt = sqlsrv_query(
                $conn,
                "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                 SET FECHA_DISTRI = ?
                 WHERE ID = ? AND DIST_ORIGEN = 'A'",
                [$nueva, $id]
            );

            if ($stmt === false) {
                error_log('CronogramaFechas::recalcularDistribucion: ' . print_r(sqlsrv_errors(), true));
                unset($afectados[$id]);
            }
        }

        return $afectados;
    }

    /**
     * IDs de todas las OCs del grupo de contenedor al que pertenece un ID.
     *
     * NO se usa Encabezado::obtenerIdsDelGrupo() aca, que agrupa solo por
     * ID_PADRE: esa columna esta vacia en el 100% de las filas de las dos
     * bases, asi que devolveria siempre una sola OC. El badge del calendario,
     * en cambio, agrupa por ID_PADRE y ademas por COD_PROVEE + CONTENEDOR
     * (ver el ID_GRUPO de CronogramaDespachos::obtenerDespachos). Si la
     * escritura no agrupara igual que la lectura, arrastrar un badge que
     * representa 3 OCs moveria una sola.
     *
     * La ventana de 360 dias es la misma que la de la query del cronograma,
     * para que el grupo sea exactamente el que se ve en pantalla.
     */
    public static function obtenerIdsDelGrupo($conn, $idEncabezado)
    {
        $sql = "WITH V AS (
                    SELECT ID, ID_PADRE, COD_PROVEE, LTRIM(RTRIM(CONTENEDOR)) AS CONT
                    FROM RO_T_IMPORTACIONES_ENCABEZADO
                    WHERE FECHA_MOV >= GETDATE()-360
                ),
                BASE AS (SELECT * FROM V WHERE ID = ?)
                SELECT DISTINCT V.ID
                FROM V CROSS JOIN BASE
                WHERE V.ID = BASE.ID
                   OR V.ID = COALESCE(BASE.ID_PADRE, BASE.ID)
                   OR V.ID_PADRE = COALESCE(BASE.ID_PADRE, BASE.ID)
                   OR (NULLIF(BASE.CONT, '') IS NOT NULL
                       AND V.COD_PROVEE = BASE.COD_PROVEE
                       AND V.CONT = BASE.CONT)";

        $stmt = sqlsrv_query($conn, $sql, [(int) $idEncabezado]);
        if ($stmt === false) {
            error_log('CronogramaFechas::obtenerIdsDelGrupo: ' . print_r(sqlsrv_errors(), true));
            return [(int) $idEncabezado];
        }

        $ids = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $ids[] = (int) $row['ID'];
        }

        return empty($ids) ? [] : $ids;
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
