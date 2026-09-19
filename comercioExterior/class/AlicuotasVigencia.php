<?php
/**
 * Vigencia de las alicuotas de importacion.
 *
 * QUE RESUELVE
 * RO_T_CONCEPTOS_ESTIMACION_COMEX tiene una fila por concepto y se editaba
 * con un UPDATE: al cambiar Derechos del 20% al 22% el valor anterior
 * desaparecia, y con el la unica forma de saber que alicuota regia cuando se
 * nacionalizo un contenedor de hace ocho meses.
 *
 * RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA (script 09) es ese historico: cada
 * edicion cierra la vigencia anterior e inserta una nueva, y la alicuota que
 * aplica a una operacion se resuelve contra su FECHA DE NACIONALIZACION
 * -RO_T_IMPORTACIONES_ENCABEZADO.FECHA_DESP_ADU-, no contra la fecha de hoy.
 *
 * LA REGLA, CON LOS DOS EXTREMOS ADENTRO
 *     VIGENCIA_DESDE <= fecha
 *     AND (VIGENCIA_HASTA IS NULL OR VIGENCIA_HASTA >= fecha)
 * Una operacion nacionalizada exactamente el VIGENCIA_DESDE, o exactamente el
 * VIGENCIA_HASTA, usa esa alicuota. VIGENCIA_HASTA en NULL es "sigue vigente".
 *
 * SI EL SCRIPT NO SE CORRIO, NADA SE ROMPE
 * disponible() responde false y todos los metodos de lectura devuelven vacio;
 * quien los llama cae al VALOR_DEFAULT_1 / VALOR_DEFAULT_2 del padron, que es
 * exactamente lo que la aplicacion hace hoy. La pantalla de Parametros lo
 * avisa y deja el ABM de vigencias deshabilitado, mismo criterio que
 * Comex::tieneCotizEdit() en la aplicacion de Finanzas.
 *
 * SIN VIGENCIA QUE CUBRA LA FECHA TAMBIEN SE CAE AL PADRON
 * Es el caso de una operacion anterior a la primera vigencia registrada. No
 * hay alicuota historica que buscar -nadie la registro nunca- y el padron es
 * la mejor respuesta disponible. Que la fecha quede descubierta lo informa
 * resolver() en su tercer elemento, para que la pantalla pueda decirlo.
 */
class AlicuotasVigencia
{
    const TABLA = 'RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA';

    /** Cache por conexion: la existencia de la tabla no cambia en un request. */
    private static $existeTabla = [];

    /**
     * La tabla del script 09 esta creada en esta base.
     *
     * Se pregunta una vez por conexion y no una vez por concepto: resolver()
     * corre trece veces por estimacion y preguntarlo en cada una serian trece
     * consultas para responder siempre lo mismo.
     */
    public static function disponible($conn)
    {
        if ($conn === false || $conn === null) {
            return false;
        }

        $clave = self::claveConexion($conn);
        if (isset(self::$existeTabla[$clave])) {
            return self::$existeTabla[$clave];
        }

        $stmt = sqlsrv_query($conn, "SELECT OBJECT_ID('dbo." . self::TABLA . "', 'U') AS T");
        $existe = false;

        if ($stmt !== false) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $existe = $row && $row['T'] !== null;
        }

        self::$existeTabla[$clave] = $existe;
        return $existe;
    }

    private static function claveConexion($conn)
    {
        return is_resource($conn) ? (string) $conn : spl_object_hash((object) $conn);
    }

    /**
     * Normaliza a 'Y-m-d' lo que llegue: DateTime de sqlsrv, 'd/m/Y' del
     * formulario o 'Y-m-d ...' de la base. Devuelve null si no es una fecha.
     */
    public static function normalizarFecha($fecha)
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }
        if ($fecha instanceof DateTime) {
            return $fecha->format('Y-m-d');
        }

        $texto = trim((string) $fecha);

        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $texto, $m)) {
            $texto = $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        if (!preg_match('/^(\d{4}-\d{2}-\d{2})/', $texto, $m)) {
            return null;
        }

        $d = DateTime::createFromFormat('Y-m-d', $m[1]);
        return ($d && $d->format('Y-m-d') === $m[1]) ? $m[1] : null;
    }

    /**
     * Las alicuotas vigentes a una fecha, por concepto.
     *
     * @param  resource    $conn
     * @param  string|null $fecha Fecha de nacionalizacion (cualquier formato
     *                            que entienda normalizarFecha).
     * @return array ['valores' => [ID_CE => ['VALOR_1'=>..,'VALOR_2'=>..,
     *                                        'VIGENCIA_DESDE'=>..,'VIGENCIA_HASTA'=>..]],
     *                'fecha'   => 'Y-m-d'|null,
     *                'aplicada'=> bool  false cuando no se pudo resolver nada
     *                                   y quien llama debe usar el padron]
     */
    public static function resolver($conn, $fecha)
    {
        $vacio = ['valores' => [], 'fecha' => null, 'aplicada' => false];

        $fechaSql = self::normalizarFecha($fecha);
        if ($fechaSql === null || !self::disponible($conn)) {
            return $vacio;
        }

        /* ROW_NUMBER y no MAX(VIGENCIA_DESDE): hacen falta los dos valores de
           la fila ganadora, no solo su fecha. El desempate por ID DESC es el
           que decide entre dos vigencias que arrancan el mismo dia -una
           correccion de un dedazo-: gana la insertada despues. */
        $sql = "SELECT ID_CE, VALOR_1, VALOR_2, VIGENCIA_DESDE, VIGENCIA_HASTA
                FROM (
                    SELECT V.ID_CE, V.VALOR_1, V.VALOR_2,
                           V.VIGENCIA_DESDE, V.VIGENCIA_HASTA,
                           ROW_NUMBER() OVER (
                               PARTITION BY V.ID_CE
                               ORDER BY V.VIGENCIA_DESDE DESC, V.ID DESC
                           ) AS RN
                    FROM " . self::TABLA . " V
                    WHERE V.ACTIVO = 1
                      AND V.VIGENCIA_DESDE <= ?
                      AND (V.VIGENCIA_HASTA IS NULL OR V.VIGENCIA_HASTA >= ?)
                ) T
                WHERE T.RN = 1";

        $stmt = sqlsrv_query($conn, $sql, array($fechaSql, $fechaSql));
        if ($stmt === false) {
            error_log('[AlicuotasVigencia::resolver] ' . print_r(sqlsrv_errors(), true));
            return $vacio;
        }

        $valores = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $valores[intval($row['ID_CE'])] = [
                'VALOR_1'        => $row['VALOR_1'],
                'VALOR_2'        => $row['VALOR_2'],
                'VIGENCIA_DESDE' => self::normalizarFecha($row['VIGENCIA_DESDE']),
                'VIGENCIA_HASTA' => self::normalizarFecha($row['VIGENCIA_HASTA']),
            ];
        }

        return [
            'valores'  => $valores,
            'fecha'    => $fechaSql,
            'aplicada' => count($valores) > 0,
        ];
    }

    /**
     * Pisa VALOR_DEFAULT_1 / VALOR_DEFAULT_2 de una lista de conceptos con la
     * vigencia que corresponde a la fecha.
     *
     * Los conceptos que no tienen vigencia que cubra esa fecha quedan con el
     * valor del padron y se marcan con VIGENCIA_APLICADA = 0, que es lo que
     * permite a la pantalla decir cual valor de donde salio en vez de mostrar
     * trece numeros indistinguibles.
     *
     * @param  array $conceptos Filas con al menos ID_CE, VALOR_DEFAULT_1 y _2
     * @return array Las mismas filas, con VIGENCIA_* agregadas
     */
    public static function aplicarAConceptos(array $conceptos, array $resolucion)
    {
        $valores = isset($resolucion['valores']) ? $resolucion['valores'] : [];

        foreach ($conceptos as &$c) {
            $idCe = isset($c['ID_CE']) ? intval($c['ID_CE']) : 0;

            if (isset($valores[$idCe])) {
                $c['VALOR_DEFAULT_1']   = $valores[$idCe]['VALOR_1'];
                $c['VALOR_DEFAULT_2']   = $valores[$idCe]['VALOR_2'];
                $c['VIGENCIA_APLICADA'] = 1;
                $c['VIGENCIA_DESDE']    = $valores[$idCe]['VIGENCIA_DESDE'];
                $c['VIGENCIA_HASTA']    = $valores[$idCe]['VIGENCIA_HASTA'];
            } else {
                $c['VIGENCIA_APLICADA'] = 0;
                $c['VIGENCIA_DESDE']    = null;
                $c['VIGENCIA_HASTA']    = null;
            }
        }
        unset($c);

        return $conceptos;
    }

    /**
     * Todas las vigencias de un concepto, de la mas nueva a la mas vieja.
     * Incluye las retiradas (ACTIVO = 0): el historial completo es el punto
     * de la tabla.
     */
    public static function historial($conn, $idCe)
    {
        if (!self::disponible($conn)) {
            return [];
        }

        $sql = "SELECT ID, ID_CE, VALOR_1, VALOR_2, VIGENCIA_DESDE, VIGENCIA_HASTA,
                       ACTIVO, OBSERVACION, FECHA_ALTA, USUARIO
                FROM " . self::TABLA . "
                WHERE ID_CE = ?
                ORDER BY VIGENCIA_DESDE DESC, ID DESC";

        $stmt = sqlsrv_query($conn, $sql, array(intval($idCe)));
        if ($stmt === false) {
            error_log('[AlicuotasVigencia::historial] ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $filas = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['VIGENCIA_DESDE'] = self::normalizarFecha($row['VIGENCIA_DESDE']);
            $row['VIGENCIA_HASTA'] = self::normalizarFecha($row['VIGENCIA_HASTA']);
            $row['FECHA_ALTA']     = is_object($row['FECHA_ALTA'])
                ? $row['FECHA_ALTA']->format('Y-m-d H:i:s') : $row['FECHA_ALTA'];
            $row['ACTIVO']         = intval($row['ACTIVO']);
            $filas[] = $row;
        }

        return $filas;
    }

    /**
     * La vigencia activa de un concepto a la fecha de hoy, o null.
     * La usa el ABM para saber cual cerrar al insertar una nueva.
     */
    public static function vigenteHoy($conn, $idCe, $hoy = null)
    {
        $hoy = ($hoy === null) ? date('Y-m-d') : self::normalizarFecha($hoy);
        $res = self::resolver($conn, $hoy);

        return isset($res['valores'][intval($idCe)]) ? $res['valores'][intval($idCe)] : null;
    }

    /**
     * Devuelve el motivo por el que el periodo propuesto se superpone con
     * otro ya cargado, o null si no se superpone con ninguno.
     *
     * Dos periodos se solapan cuando cada uno empieza antes de que termine el
     * otro. Un extremo abierto se trata como '9999-12-31', que es la forma de
     * escribir "no termina" sin partir la comparacion en cuatro casos.
     *
     * Esto NO es un CHECK de la tabla porque depende de las otras filas del
     * mismo concepto, que un CHECK de columna no puede mirar. Mismo criterio
     * que la suma de alicuotas de RO_T_CASHFLOW_COBEL_ALICUOTA en Finanzas.
     *
     * @param int|null $excluirId Vigencia a ignorar (al editarse a si misma)
     */
    public static function validarSuperposicion($conn, $idCe, $desde, $hasta, $excluirId = null)
    {
        if (!self::disponible($conn)) {
            return null;
        }

        $desde = self::normalizarFecha($desde);
        $hasta = self::normalizarFecha($hasta);

        if ($desde === null) {
            return 'La fecha "vigente desde" es obligatoria.';
        }
        if ($hasta !== null && $hasta < $desde) {
            return 'La fecha "vigente hasta" no puede ser anterior a "vigente desde".';
        }

        $sql = "SELECT TOP 1 ID, VIGENCIA_DESDE, VIGENCIA_HASTA
                FROM " . self::TABLA . "
                WHERE ID_CE = ?
                  AND ACTIVO = 1
                  AND (? IS NULL OR ID <> ?)
                  AND VIGENCIA_DESDE <= ?
                  AND ISNULL(VIGENCIA_HASTA, '9999-12-31') >= ?
                ORDER BY VIGENCIA_DESDE";

        $hastaSql  = ($hasta === null) ? '9999-12-31' : $hasta;
        $excluir   = ($excluirId === null) ? null : intval($excluirId);

        $stmt = sqlsrv_query($conn, $sql,
            array(intval($idCe), $excluir, $excluir, $hastaSql, $desde));

        if ($stmt === false) {
            error_log('[AlicuotasVigencia::validarSuperposicion] ' . print_r(sqlsrv_errors(), true));
            return null;
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $d = self::normalizarFecha($row['VIGENCIA_DESDE']);
        $h = self::normalizarFecha($row['VIGENCIA_HASTA']);

        return 'El periodo se superpone con la vigencia #' . $row['ID'] .
               ' (' . $d . ' a ' . ($h === null ? 'sin cierre' : $h) . ').';
    }

    /**
     * Da de alta una vigencia nueva, cerrando la anterior si hace falta.
     *
     * POR QUE CIERRA LA ANTERIOR EN VEZ DE PEDIR QUE LA CIERREN
     * El caso normal -"desde el 1/7 la alicuota pasa a 12%"- deja la vigencia
     * abierta anterior solapada con la nueva desde el 1/7 en adelante. Pedirle
     * al usuario que primero edite la vieja y despues cargue la nueva haria
     * que el paso intermedio quedara con un periodo mal cerrado si se
     * interrumpe. Cerrarla en el dia anterior al nuevo DESDE deja los dos
     * periodos consecutivos sin hueco ni superposicion, que es lo que la
     * pantalla pide.
     *
     * Solo se cierra automaticamente la vigencia ABIERTA (VIGENCIA_HASTA IS
     * NULL) que arranca ANTES del nuevo DESDE. Cualquier otra superposicion
     * -periodos ya cerrados, o una vigencia que arranca despues- se rechaza:
     * ahi no hay una unica forma obvia de acomodar las fechas y adivinarla
     * seria reescribir un periodo que alguien definio a proposito.
     *
     * @return array ['success'=>bool, 'message'=>string, 'id'=>int|null]
     */
    public static function agregar($conn, $idCe, $valor1, $valor2, $desde, $hasta = null,
                                   $observacion = null, $usuario = null)
    {
        if (!self::disponible($conn)) {
            return [
                'success' => false,
                'message' => 'Falta correr el script comercioExterior/sql/09_alicuotas_vigencia.sql en esta base.',
                'id'      => null,
            ];
        }

        $idCe  = intval($idCe);
        $desde = self::normalizarFecha($desde);
        $hasta = self::normalizarFecha($hasta);

        if ($idCe <= 0) {
            return ['success' => false, 'message' => 'Concepto invalido.', 'id' => null];
        }
        if ($desde === null) {
            return ['success' => false, 'message' => 'La fecha "vigente desde" es obligatoria.', 'id' => null];
        }

        // 1. Cerrar la vigencia abierta anterior, si la hay.
        $cerradas = self::cerrarAbiertaAnterior($conn, $idCe, $desde);
        if ($cerradas === false) {
            return ['success' => false, 'message' => 'No se pudo cerrar la vigencia anterior.', 'id' => null];
        }

        // 2. Con la anterior ya cerrada, lo que quede superpuesto es un
        //    conflicto real y no el caso normal.
        $choque = self::validarSuperposicion($conn, $idCe, $desde, $hasta);
        if ($choque !== null) {
            return ['success' => false, 'message' => $choque, 'id' => null];
        }

        $sql = "INSERT INTO " . self::TABLA . "
                    (ID_CE, VALOR_1, VALOR_2, VIGENCIA_DESDE, VIGENCIA_HASTA,
                     ACTIVO, OBSERVACION, FECHA_ALTA, USUARIO)
                VALUES (?, ?, ?, ?, ?, 1, ?, GETDATE(), ?)";

        $params = array(
            $idCe,
            ($valor1 === '' || $valor1 === null) ? null : floatval($valor1),
            ($valor2 === '' || $valor2 === null) ? null : floatval($valor2),
            $desde,
            $hasta,
            ($observacion === '' ? null : $observacion),
            ($usuario === '' ? null : $usuario),
        );

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            error_log('[AlicuotasVigencia::agregar] ' . print_r(sqlsrv_errors(), true));
            return ['success' => false, 'message' => 'Error al guardar la vigencia.', 'id' => null];
        }

        $id = null;
        $stmtId = sqlsrv_query($conn, 'SELECT CAST(SCOPE_IDENTITY() AS INT) AS ID');
        if ($stmtId !== false) {
            $row = sqlsrv_fetch_array($stmtId, SQLSRV_FETCH_ASSOC);
            $id = $row ? $row['ID'] : null;
        }

        return [
            'success'  => true,
            'message'  => $cerradas > 0
                ? 'Vigencia registrada. La anterior quedo cerrada el dia previo.'
                : 'Vigencia registrada.',
            'id'       => $id,
            'cerradas' => $cerradas,
        ];
    }

    /**
     * Cierra la vigencia abierta del concepto en el dia anterior a $desde.
     * Devuelve cuantas cerro, o false si la consulta fallo.
     */
    private static function cerrarAbiertaAnterior($conn, $idCe, $desde)
    {
        $sql = "UPDATE " . self::TABLA . "
                SET VIGENCIA_HASTA = DATEADD(DAY, -1, CAST(? AS DATE))
                WHERE ID_CE = ?
                  AND ACTIVO = 1
                  AND VIGENCIA_HASTA IS NULL
                  AND VIGENCIA_DESDE < CAST(? AS DATE)";

        $stmt = sqlsrv_query($conn, $sql, array($desde, intval($idCe), $desde));
        if ($stmt === false) {
            error_log('[AlicuotasVigencia::cerrarAbiertaAnterior] ' . print_r(sqlsrv_errors(), true));
            return false;
        }

        return sqlsrv_rows_affected($stmt);
    }

    /**
     * Retira una vigencia sin borrarla.
     *
     * No hay bajas fisicas: una estimacion historica calculada con esa
     * alicuota quedaria apuntando a algo que ya no se puede explicar.
     */
    public static function retirar($conn, $id, $usuario = null)
    {
        if (!self::disponible($conn)) {
            return ['success' => false, 'message' => 'Falta correr el script 09_alicuotas_vigencia.sql.'];
        }

        $sql = "UPDATE " . self::TABLA . "
                SET ACTIVO = 0, USUARIO = COALESCE(?, USUARIO)
                WHERE ID = ? AND ACTIVO = 1";

        $stmt = sqlsrv_query($conn, $sql, array(($usuario === '' ? null : $usuario), intval($id)));
        if ($stmt === false) {
            error_log('[AlicuotasVigencia::retirar] ' . print_r(sqlsrv_errors(), true));
            return ['success' => false, 'message' => 'Error al retirar la vigencia.'];
        }

        if (sqlsrv_rows_affected($stmt) === 0) {
            return ['success' => false, 'message' => 'La vigencia no existe o ya estaba retirada.'];
        }

        return ['success' => true, 'message' => 'Vigencia retirada.'];
    }
}
