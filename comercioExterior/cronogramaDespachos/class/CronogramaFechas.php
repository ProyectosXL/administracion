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
     * Campos cuyos cambios se REGISTRAN EN EL HISTORIAL.
     *
     * Es un superconjunto de CAMPOS_EDITABLES y no la misma lista, porque las
     * dos constantes contestan preguntas distintas:
     *
     *   CAMPOS_EDITABLES  que puede escribir el CRONOGRAMA. Habilita el drag &
     *                     drop y entra en la validacion de coherencia contra
     *                     ORDEN_FLUJO.
     *   CAMPOS_HISTORIAL  de que cambios queremos rastro.
     *
     * FECHA_EST_PAGO esta aca y NO en la otra: no vive en el flujo logistico
     * -no tiene lugar en ORDEN_FLUJO, que va del embarque a la distribucion- y
     * el cronograma no la dibuja ni la arrastra. Meterla en CAMPOS_EDITABLES
     * para conseguir el historial le habilitaria de paso el drag & drop y la
     * haria chocar con validarCoherencia(), que no sabe donde ubicarla.
     */
    const CAMPOS_HISTORIAL = [
        'FECHA_EST_EMB',
        'FECHA_EMB',
        'FECHA_ARR',
        'FECHA_DESP_ADU',
        'FECHA_DISTRI',
        'FECHA_EST_PAGO',
    ];

    /** Etiquetas legibles, para mensajes de error y para el historial. */
    const ETIQUETAS_CAMPOS = [
        'FECHA_EST_EMB'  => 'Embarque estimado',
        'FECHA_EMB'      => 'Embarque real',
        'FECHA_ARR'      => 'Arribo',
        'FECHA_DESP_ADU' => 'Despacho de aduana',
        'FECHA_REC'      => 'Recepción',
        'FECHA_DISTRI'   => 'Distribución',
        'FECHA_EST_PAGO' => 'Fecha estimada de pago',
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
     * Claves que hacen falta para calcular la cadena completa de fechas.
     *
     * SON TODAS LAS QUE EXISTEN. DIAS_ARR_DIST se retiro: la distribucion
     * cuelga de la recepcion en los dos caminos -este y derivarDistribucion()-
     * y ya no hay ningun parametro que describa un tramo salteando eslabones.
     * Ver el comentario de calcularDistribucion().
     */
    const CLAVES_CADENA = [
        'DIAS_EMB_ARR',
        'DIAS_EMB_PAGO',
        'DIAS_ARR_DESP',
        'DIAS_DESP_REC',
        'DIAS_REC_DIST',
    ];

    /**
     * Parametros de dias, TAL CUAL estan en la tabla.
     *
     * ACA YA NO HAY DEFAULTS, y es el punto del cambio. Este metodo devolvia
     * ['DIAS_EMB_ARR' => 45, 'DIAS_ARR_DESP' => 7, ...] como "red de
     * seguridad por si la tabla no fue sembrada", y lo mismo hacian
     * CronogramaDespachos::obtenerParametros() y el objeto parametrosDias de
     * cronograma.js. Esa red fue justamente lo que dejo que durante meses el
     * navegador calculara arribo + 2 mientras la tabla decia arribo + 7 sin
     * que nadie se enterara: cuando el valor de respaldo es plausible, un
     * parametro faltante no se ve.
     *
     * Sin la clave, el calculo devuelve null y quien llama tiene que decirlo.
     * Es ruidoso a proposito.
     *
     * @return array [CLAVE => (int) valor]
     */
    /**
     * Cache por request.
     *
     * HACE FALTA desde que el cronograma calcula la cadena fila por fila: sin
     * esto, las ~400 filas de central serian ~400 SELECT identicos en una sola
     * carga del calendario. Se invalida solo al terminar el request, que es
     * exactamente lo que se quiere: un guardado que cambia un parametro entra
     * por otro request y lo lee de nuevo.
     *
     * @var array|null
     */
    private static $cacheParametros = null;

    public static function obtenerParametros($conn)
    {
        if (self::$cacheParametros !== null) {
            return self::$cacheParametros;
        }

        $parametros = [];

        $stmt = sqlsrv_query($conn, "SELECT CLAVE, VALOR FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA");
        if ($stmt === false) {
            /* NO se cachea el fallo: si la consulta falló por algo transitorio,
               cachear el array vacío dejaría a todo el request sin parámetros. */
            error_log('CronogramaFechas::obtenerParametros: ' . print_r(sqlsrv_errors(), true));
            return $parametros;
        }

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $parametros[trim($row['CLAVE'])] = (int) $row['VALOR'];
        }

        self::$cacheParametros = $parametros;
        return $parametros;
    }

    /**
     * Olvida los parametros cacheados.
     *
     * NINGUN ENDPOINT LA NECESITA: dentro de un request los parametros no
     * cambian, y el ABM los actualiza en un request que despues no los vuelve
     * a leer. Existe para los scripts de verificacion, que cambian un valor y
     * comprueban que las fechas se mueven sin tener que levantar otro proceso.
     * Si alguna vez un mismo request escribe y despues lee, este es el llamado
     * que falta.
     */
    public static function olvidarParametros()
    {
        self::$cacheParametros = null;
    }

    /**
     * Claves de CLAVES_CADENA que la base no tiene.
     *
     * Lo usan los endpoints para contestar "falta correr el script 12" en vez
     * de devolver una fecha inventada.
     *
     * @return array lista de claves faltantes, vacia si esta todo
     */
    public static function parametrosFaltantes(array $parametros)
    {
        $faltan = [];
        foreach (self::CLAVES_CADENA as $clave) {
            if (!isset($parametros[$clave])) {
                $faltan[] = $clave;
            }
        }
        return $faltan;
    }

    /* ====================================================================
       LA CADENA DE FECHAS DERIVADAS

       ESTE ES EL UNICO LUGAR DONDE SE CALCULAN. Antes vivia repartida entre
       tres implementaciones que no coincidian:

         js/cargaInicial.js         arribo = embarque + 45
                                    pago   = embarque + 5
                                    nacionalizacion = arribo + 2
         cronograma.js              arribo = embarque + DIAS_EMB_ARR
                                    nacionalizacion = arribo + DIAS_ARR_DESP (7)
         Encabezado::DIAS_EMB_EST_PAGO   pago = embarque + 5, con un comentario
                                    que decia "si se cambia uno hay que
                                    cambiar el otro"

       Con los mismos datos, gestion de despachos y el cronograma mostraban
       nacionalizaciones distintas para el mismo contenedor.

       ESTOS PARAMETROS TAMBIEN MUEVEN EL CASHFLOW DE ProyectosXL/finanzas,
       que lee FECHA_EST_PAGO (pestana Proveedores Exterior) y FECHA_DESP_ADU
       (pestana Crono Nacionalizacion) de este mismo maestro, y que va a leer
       RO_T_IMPORTACIONES_PARAM_CRONOGRAMA para proyectar contenedores que
       todavia no existen como fila. Tocar un parametro mueve plata de mes en
       el tablero de Finanzas, no solo fechas en esta pantalla.
       ==================================================================== */

    /**
     * Cadena completa de fechas derivadas de una fecha de embarque.
     *
     *     arribo          = embarque        + DIAS_EMB_ARR
     *     pago            = embarque        + DIAS_EMB_PAGO
     *     nacionalizacion = arribo          + DIAS_ARR_DESP
     *     recepcion       = nacionalizacion + DIAS_DESP_REC
     *     distribucion    = recepcion       + DIAS_REC_DIST
     *
     * EL PAGO CUELGA DEL EMBARQUE Y NO DEL ARRIBO: se le paga al proveedor
     * del exterior contra embarque, no contra llegada. Por eso es la unica
     * rama que no pasa por el arribo.
     *
     * LOS HECHOS LE GANAN A LAS PROYECCIONES, y por eso hay dos parametros de
     * entrada mas:
     *
     *   $fechaArriboFijada   el arribo en firme: o la ETA que confirmo la
     *                        naviera (ETA_CONFIRMADA = 1) o una fecha que
     *                        alguien corrigio a mano. Cuando existe, la
     *                        nacionalizacion cuelga de ELLA y no del arribo
     *                        proyectado. Sin esto, un contenedor cuya ETA se
     *                        atraso dos semanas seguiria mostrando la
     *                        nacionalizacion vieja, calculada sobre un arribo
     *                        que ya se sabe que no va a pasar.
     *   $fechaRecepcion      la recepcion REAL, que entra por Tango (STA20,
     *                        comprobantes 'RP'). Cuando existe, la
     *                        distribucion sale de esa.
     *
     * SON DIAS CORRIDOS, sin corrimiento a dia habil. El corrimiento lo sigue
     * haciendo la pantalla de gestion de despachos -validarCampoFechaHabil()
     * en cargaInicial.js- sobre el campo ya cargado, avisando al usuario. No
     * se subio acá a proposito: meterlo cambiaria de golpe todas las fechas
     * que dibuja el cronograma, que hoy no corre ninguna, y esta entrega
     * cambia la nacionalizacion y nada mas. Es la unica diferencia que puede
     * quedar entre lo que muestra el cronograma y lo que guarda gestion de
     * despachos, y es visible: la pantalla avisa cada vez que corre una fecha.
     *
     * @param  resource    $conn
     * @param  string|null $fechaEmbarque      'Y-m-d'. El ETD real si existe y
     *                                         si no el estimado; usar
     *                                         fechaBaseEmbarque() para resolverlo.
     * @param  string|null $fechaArriboFijada  'Y-m-d' del arribo en firme, si lo hay
     * @param  string|null $fechaRecepcion     'Y-m-d' de la recepcion REAL, si la hay
     * @return array [
     *     'fechas'      => ['arribo'|'pago'|'nacionalizacion'|'recepcion'|'distribucion' => 'Y-m-d'|null],
     *     'parametros'  => [CLAVE => int],
     *     'faltan'      => [claves sin valor en la base],
     * ]
     */
    public static function cadenaDeFechas($conn, $fechaEmbarque, $fechaArriboFijada = null, $fechaRecepcion = null)
    {
        $parametros = self::obtenerParametros($conn);
        $faltan     = self::parametrosFaltantes($parametros);

        $fechas = [
            'arribo'          => null,
            'pago'            => null,
            'nacionalizacion' => null,
            'recepcion'       => null,
            'distribucion'    => null,
        ];

        /* Sin parametros no se calcula nada: se devuelve la cadena en null en
           vez de media cadena. Una fecha calculada con algunos eslabones y
           otros no seria peor que ninguna, porque no se distingue de una buena. */
        if (!empty($faltan)) {
            return ['fechas' => $fechas, 'parametros' => $parametros, 'faltan' => $faltan];
        }

        /* El arribo fijado entra aunque no haya embarque: un contenedor al que
           le confirmaron la ETA pero al que todavia nadie le cargo el ETD
           igual tiene nacionalizacion, recepcion y distribucion calculables. */
        $arribo = !empty($fechaArriboFijada)
            ? substr((string) $fechaArriboFijada, 0, 10)
            : self::sumarDias($fechaEmbarque, $parametros['DIAS_EMB_ARR']);

        $fechas['arribo'] = $arribo;
        $fechas['pago']   = self::sumarDias($fechaEmbarque, $parametros['DIAS_EMB_PAGO']);

        $fechas['nacionalizacion'] = self::sumarDias($arribo, $parametros['DIAS_ARR_DESP']);

        /* La recepcion REAL pisa a la estimada. Se expone la que vale para
           que el que llama muestre una sola. */
        $fechas['recepcion'] = !empty($fechaRecepcion)
            ? substr((string) $fechaRecepcion, 0, 10)
            : self::sumarDias($fechas['nacionalizacion'], $parametros['DIAS_DESP_REC']);

        $fechas['distribucion'] = self::sumarDias($fechas['recepcion'], $parametros['DIAS_REC_DIST']);

        return ['fechas' => $fechas, 'parametros' => $parametros, 'faltan' => $faltan];
    }

    /**
     * La fecha de embarque que manda: el ETD real si existe, y si no el
     * estimado.
     *
     * Es la regla que ya aplicaban obtenerFechaBase() en cargaInicial.js y
     * calcularFechasEstimadas() en cronograma.js, cada una por su cuenta.
     *
     * @param  array $fila con FECHA_EMB y FECHA_EST_EMB
     * @return string|null 'Y-m-d'
     */
    public static function fechaBaseEmbarque(array $fila)
    {
        if (!empty($fila['FECHA_EMB'])) {
            return substr((string) $fila['FECHA_EMB'], 0, 10);
        }
        if (!empty($fila['FECHA_EST_EMB'])) {
            return substr((string) $fila['FECHA_EST_EMB'], 0, 10);
        }
        return null;
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
     * Fecha de distribucion: SIEMPRE la recepcion + DIAS_REC_DIST.
     *
     * La distribucion es la salida del deposito central hacia los locales, o
     * sea que va DESPUES de la recepcion, y en el deposito se distribuye casi
     * siempre al dia siguiente de recibir. Lo unico que cambia es de donde
     * sale la recepcion:
     *
     *   - con recepcion REAL (Tango, STA20 comprobantes 'RP'): esa;
     *   - sin ella: la estimada de la cadena, arribo + DIAS_ARR_DESP +
     *     DIAS_DESP_REC.
     *
     * DIAS_ARR_DIST SE FUE, y era la segunda rama de esta funcion. Valia 10
     * porque con la cadena vieja -nacionalizacion en arribo + 7, recepcion
     * estimada en arribo + 9- caia justo un dia despues de la recepcion. Con
     * la nacionalizacion en arribo + 5 la recepcion estimada es arribo + 7,
     * asi que arribo + 10 dejo de ser "el dia siguiente" de nada: eran dos
     * dias de aire que nadie habia decidido.
     *
     * POR QUE SE ELIMINO EN VEZ DE BAJARLO A 8. Un 8 daria hoy el mismo
     * resultado, pero volveria a quedar desactualizado en silencio la proxima
     * vez que se toque DIAS_ARR_DESP o DIAS_DESP_REC desde el ABM. Es
     * exactamente el modo de falla que tenian el 45/7/2 repartidos: un numero
     * plausible que describe una cadena que ya cambio. Derivandola, no hay
     * nada que mantener sincronizado.
     *
     * Se calcula igual sobre un arribo confirmado que sobre uno estimado, y
     * tambien cuando la fecha ya paso: la proyeccion sigue al arribo en vez
     * de congelarse.
     *
     * @param  string|null $fechaArribo    'Y-m-d'
     * @param  array       $parametros     de obtenerParametros()
     * @param  string|null $fechaRecepcion 'Y-m-d' de la recepcion REAL, si la hay
     * @return string|null 'Y-m-d'
     */
    public static function calcularDistribucion($fechaArribo, array $parametros, $fechaRecepcion = null)
    {
        foreach (['DIAS_ARR_DESP', 'DIAS_DESP_REC', 'DIAS_REC_DIST'] as $clave) {
            if (!isset($parametros[$clave])) {
                error_log('CronogramaFechas::calcularDistribucion: falta ' . $clave);
                return null;
            }
        }

        if (!empty($fechaRecepcion)) {
            return self::sumarDias($fechaRecepcion, $parametros['DIAS_REC_DIST']);
        }

        /* La recepcion estimada NO se pide a cadenaDeFechas() para no arrastrar
           la conexion hasta aca: esta funcion se llama por fila y ya recibe los
           parametros resueltos. Son los mismos dos eslabones. */
        $nacionalizacion = self::sumarDias($fechaArribo, $parametros['DIAS_ARR_DESP']);
        $recepcion       = self::sumarDias($nacionalizacion, $parametros['DIAS_DESP_REC']);

        return self::sumarDias($recepcion, $parametros['DIAS_REC_DIST']);
    }

    /**
     * Fecha de distribucion que hay que MOSTRAR para una fila.
     *
     * Con DIST_ORIGEN = 'A' la columna FECHA_DISTRI es apenas una cache: la
     * verdad es la formula. Hace falta derivarla en cada lectura porque la
     * recepcion no entra por esta aplicacion sino por Tango (STA20,
     * comprobantes 'RP'), y no hay ningun punto donde engancharse para
     * recalcular cuando eso pasa. Sin esto, un contenedor que se recibe
     * manana se queda para siempre con la proyeccion vieja en lugar de
     * moverse a recepcion + DIAS_REC_DIST.
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
                : self::calcularDistribucion($arribo, $parametros, $recepcion);
        }

        if (empty($arribo)) {
            return $guardada;
        }

        /* Si faltan parametros, calcularDistribucion() devuelve null y loguea.
           Se devuelve lo guardado en vez de ese null: mostrar la ultima
           proyeccion buena es mejor que vaciar la columna. */
        $derivada = self::calcularDistribucion($arribo, $parametros);

        return ($derivada === null) ? $guardada : $derivada;
    }

    /**
     * Aplica el recalculo de FECHA_DISTRI a las OCs indicadas tras un cambio
     * de FECHA_ARR.
     *
     * Solo toca las filas con DIST_ORIGEN = 'A' (automatica). 'M' (movida a
     * mano) y 'C' (confirmada) quedan intactas: sin esa distincion el
     * recalculo pisaria lo que abastecimiento ajusto a proposito.
     *
     * EL PARAMETRO $diasArrDist SE FUE junto con DIAS_ARR_DIST. Nadie se lo
     * pasaba: los tres llamadores -actualizarFechaCronograma.php y las dos
     * ramas de encabezado.php- lo dejaban en null para que se leyera de la
     * tabla.
     *
     * @param  resource $conn
     * @param  array    $ids          IDs de encabezado a recalcular
     * @param  string   $fechaArribo  nueva fecha de arribo 'Y-m-d'
     * @return array    [idEncabezado => nuevaFechaDistri] de las filas tocadas
     */
    public static function recalcularDistribucion($conn, array $ids, $fechaArribo)
    {
        if (empty($ids)) {
            return [];
        }

        $parametros = self::obtenerParametros($conn);

        /* Sin parametros NO se recalcula nada. Antes obtenerParametros()
           devolvia defaults y esto nunca podia fallar; ahora puede, y la
           respuesta correcta es no tocar las fechas guardadas. */
        foreach (['DIAS_ARR_DESP', 'DIAS_DESP_REC', 'DIAS_REC_DIST'] as $clave) {
            if (!isset($parametros[$clave])) {
                error_log('CronogramaFechas::recalcularDistribucion: falta ' . $clave . ', no se recalcula');
                return [];
            }
        }

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

            $nueva = self::calcularDistribucion($fechaArribo, $parametros, $fila['FECHA_REC']);

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

        /* FECHA_EST_PAGO entra aca aunque el cronograma no la dibuje: es lo que
           le permite a registrarCambiosDeFecha() compararla y dejar rastro. No
           aparece en ORDEN_FLUJO ni en CAMPOS_EDITABLES, asi que ni la
           validacion de coherencia ni el drag & drop la ven. */
        $sql = "SELECT ID, ORDEN_COMPRA, CONTENEDOR, DIST_ORIGEN, ETA_CONFIRMADA,
                       FECHA_EST_EMB, FECHA_EMB, FECHA_ARR, FECHA_DESP_ADU, FECHA_DISTRI,
                       FECHA_EST_PAGO,
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
