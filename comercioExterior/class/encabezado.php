<?php

class Encabezado
{
    private $cid_central;

    /**
     * VALOR_FOB_PESO derivado del FOB en dólares y el tipo de cambio.
     *
     * Es la MISMA regla que recalcularFobPesos() aplica en el navegador, y
     * está acá porque el Valor F.O.B. U$S pasó a ser editable después del
     * alta: desde que ese número se puede corregir, el FOB en pesos no puede
     * seguir saliendo de lo que el cliente informa. Si el formulario mandaba
     * el campo vacío -por ejemplo porque el tipo de cambio no estaba cargado-
     * el UPDATE salteaba la columna y VALOR_FOB_PESO quedaba con el valor del
     * FOB viejo, que es el que después usan el % sobre FOB de los costos de
     * nacionalización y la impresión.
     *
     * URUGUAY: FOB_PESO = FOB_DOLAR, sin multiplicar. Es lo que hace la
     * pantalla y lo que hay en la base: en las 72 filas de uy las dos
     * columnas son iguales y el TIPO_CAMBIO -que ronda 40- no se aplica.
     *
     * @return float|null null cuando no hay con qué calcularlo; en ese caso
     *                    quien llama NO debe tocar la columna, porque escribir
     *                    un cero sería afirmar que el contenedor no vale nada.
     */
    public static function calcularFobPeso($fobDolar, $tipoCambio, $esUruguay = false)
    {
        $fob = floatval(str_replace(',', '.', (string) $fobDolar));

        if ($fob <= 0) {
            return null;
        }

        if ($esUruguay) {
            return round($fob, 2);
        }

        $tc = floatval(str_replace(',', '.', (string) $tipoCambio));

        if ($tc <= 0) {
            return null;
        }

        return round($fob * $tc, 2);
    }

    function __construct()
    {

        require_once __DIR__ . '/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($db);

    }

    /* ====================================================================
       LAS FECHAS FIJADAS A MANO

       Tres de las fechas del maestro las calcula el sistema a partir de la de
       embarque, y para las tres hace falta poder decir "esta no, esta la puso
       una persona". Si no, el recalculo automatico las pisa.

       El script 10 resolvio el caso de FECHA_EST_PAGO con un BIT en el
       maestro. El script 11 extendio el mismo patron a las otras dos:

           fecha              bit               quien / cuando
           ---------------------------------------------------------------
           FECHA_EST_PAGO     FECHA_PAGO_CONF   FECHA_PAGO_CONF_USUARIO/_FECHA
           FECHA_ARR          ETA_CONFIRMADA    ETA_CONF_USUARIO/_FECHA
           FECHA_DESP_ADU     FECHA_DESP_CONF   FECHA_DESP_CONF_USUARIO/_FECHA

       EL ARRIBO NO ESTRENA BIT: ETA_CONFIRMADA ya existia y ya significaba
       esto -se enciende al editar la ETA a mano, ver el datepicker
       .js-datepicker-arribo en cargaInicial.js- ademas de que ya la leen el
       cronograma y el cashflow. Agregarle un FECHA_ARR_CONF al lado seria
       tener dos columnas afirmando el mismo hecho.

       Por que un BIT y no un flag deducido de RO_T_IMPORTACIONES_FECHAS_HIST:
       el recalculo automatico y la edicion manual llegan aca por el MISMO POST
       del formulario y dejan en el historial una fila indistinguible. Ver el
       encabezado de sql/10_fecha_pago_manual.sql.

       LOS DIAS YA NO ESTAN ACA. Habia una constante DIAS_EMB_EST_PAGO = 5 con
       el comentario "esta duplicada con el JS, si se cambia uno hay que
       cambiar el otro". La cadena entera vive ahora en
       CronogramaFechas::cadenaDeFechas(), que la lee de
       RO_T_IMPORTACIONES_PARAM_CRONOGRAMA, y este archivo no tiene ni un
       numero de dias escrito.
       ==================================================================== */

    /**
     * Las tres fechas que el sistema calcula y que se pueden fijar a mano.
     *
     * Cada entrada dice que columna guarda la fecha, que columna dice si esta
     * fijada, de donde sale el valor automatico dentro de la cadena, y como se
     * llama el campo en el historial. Tener el mapa explicito es lo que
     * permite que marcarFechaFijada() y revertirFechaAuto() sean una sola
     * implementacion en vez de tres copias que se van separando.
     *
     * 'cadena' es la clave con la que la fecha viene dentro de lo que devuelve
     * CronogramaFechas::cadenaDeFechas().
     */
    const FECHAS_FIJABLES = [
        'PAGO' => [
            'columna'  => 'FECHA_EST_PAGO',
            'conf'     => 'FECHA_PAGO_CONF',
            'usuario'  => 'FECHA_PAGO_CONF_USUARIO',
            'fecha'    => 'FECHA_PAGO_CONF_FECHA',
            'cadena'   => 'pago',
            'etiqueta' => 'fecha estimada de pago',
        ],
        'ARRIBO' => [
            'columna'  => 'FECHA_ARR',
            'conf'     => 'ETA_CONFIRMADA',
            'usuario'  => 'ETA_CONF_USUARIO',
            'fecha'    => 'ETA_CONF_FECHA',
            'cadena'   => 'arribo',
            'etiqueta' => 'fecha de arribo (ETA)',
        ],
        'NACIONALIZACION' => [
            'columna'  => 'FECHA_DESP_ADU',
            'conf'     => 'FECHA_DESP_CONF',
            'usuario'  => 'FECHA_DESP_CONF_USUARIO',
            'fecha'    => 'FECHA_DESP_CONF_FECHA',
            'cadena'   => 'nacionalizacion',
            'etiqueta' => 'fecha de nacionalización',
        ],
    ];

    /** @var array Cache por request de que columnas existen en esta base */
    private $columnasConf = [];

    /**
     * Si el maestro ya tiene una columna.
     *
     * SE PREGUNTA en vez de darlo por hecho, mismo criterio que
     * AlicuotasVigencia::disponible() y que Comex::tieneCotizEdit() en la
     * aplicacion de Finanzas: sin los scripts 10 y 11 la pantalla sigue
     * andando -las fechas se recalculan siempre- y lo unico que no se puede es
     * fijarlas. Los repos se despliegan juntos, pero no se puede asumir que el
     * DDL corrio antes que el codigo.
     *
     * @return bool
     */
    private function tieneColumna($columna)
    {
        if (isset($this->columnasConf[$columna])) {
            return $this->columnasConf[$columna];
        }

        $this->columnasConf[$columna] = false;

        /* El nombre NO se interpola libre: sale de FECHAS_FIJABLES, que es una
           constante de esta clase, y ademas va como parametro de COL_LENGTH. */
        $stmt = sqlsrv_query(
            $this->cid_central,
            "SELECT COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO', ?) AS C",
            [$columna]
        );

        if ($stmt === false) {
            error_log('tieneColumna(' . $columna . '): ' . print_r(sqlsrv_errors(), true));
            return false;
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        $this->columnasConf[$columna] = ($row && $row['C'] !== null);

        return $this->columnasConf[$columna];
    }

    /**
     * Si se puede fijar a mano la fecha de este tipo en esta base.
     *
     * Pregunta por las tres columnas y no solo por el BIT: el badge de la
     * pantalla muestra quien y cuando, y con el BIT sin sus dos acompanantes
     * quedaria a medias.
     */
    public function puedeFijar($tipo)
    {
        if (!isset(self::FECHAS_FIJABLES[$tipo])) {
            return false;
        }

        $mapa = self::FECHAS_FIJABLES[$tipo];

        return $this->tieneColumna($mapa['conf'])
            && $this->tieneColumna($mapa['usuario'])
            && $this->tieneColumna($mapa['fecha']);
    }

    /**
     * Compatibilidad: lo que preguntaba el codigo del script 10.
     *
     * Se conserva el nombre porque lo usan actualizarFechaPagoController.php y
     * las lecturas de obtenerDespachoPorId(); por dentro es puedeFijar('PAGO').
     */
    public function tieneFechaPagoConf()
    {
        return $this->puedeFijar('PAGO');
    }

    /**
     * Normaliza lo que el front afirma sobre que fechas fijo el usuario.
     *
     * ACEPTA UN BOOL SUELTO porque la firma vieja de actualizarEncabezadoGrupo()
     * recibia solo el marcador del pago, y este metodo se llama desde codigo
     * que puede no haberse actualizado todavia. Un bool suelto significa lo
     * que significaba antes: "el usuario fijo la fecha de pago".
     *
     * TODO LO QUE NO ESTE EN FECHAS_FIJABLES SE DESCARTA: la lista de tipos la
     * decide esta clase, no el POST.
     *
     * @return array [TIPO => bool] con las tres claves siempre definidas
     */
    private static function normalizarMarcasManuales($marcas)
    {
        if (!is_array($marcas)) {
            $marcas = ['PAGO' => (bool) $marcas];
        }

        $salida = [];
        foreach (array_keys(self::FECHAS_FIJABLES) as $tipo) {
            $salida[$tipo] = !empty($marcas[$tipo]);
        }
        return $salida;
    }

    /**
     * Marca como fijada a mano una fecha de las OCs indicadas.
     *
     * HACEN FALTA LAS DOS COSAS, y por motivos distintos:
     *
     *   1. Que el FRONT diga que lo toco el usuario. Sin esto, cualquier
     *      recalculo automatico -mover el ETD recalcula la cadena entera y la
     *      manda distinta- quedaria marcado como manual y el contenedor no
     *      volveria a recalcular nunca mas. El marcador es lo unico que separa
     *      los dos casos, porque los dos llegan por el mismo POST.
     *
     *   2. Que el valor REALMENTE difiera del maestro. Es la comparacion que
     *      manda, igual que en Comex::guardarFecha(): el cliente no decide
     *      solo. Tipear la misma fecha que ya estaba no es una edicion, y
     *      marcarla dejaria una fila de auditoria que afirma algo que no paso.
     *
     * NO APAGA EL BIT. Volver a automatico es una decision explicita y tiene su
     * propio endpoint -controller/revertirFechaAuto.php-. Que un guardado
     * cualquiera pudiera apagarlo devolveria el problema original: la fecha
     * fijada se perderia sin que nadie lo pida.
     *
     * EL BIT Y EL QUIEN/CUANDO SE ESCRIBEN POR SEPARADO a proposito. Para el
     * arribo, el BIT -ETA_CONFIRMADA- existe desde antes que sus dos columnas
     * de auditoria, asi que en una base donde el script 11 todavia no corrio
     * hay que poder seguir marcando la ETA como confirmada, que es lo que la
     * aplicacion ya hacia. Lo que falta ahi es el dato de quien, no la marca.
     *
     * @param string $tipo      clave de FECHAS_FIJABLES
     * @param array  $ids       OCs a marcar
     * @param array  $antes     Estado previo, de leerFechasParaHistorial()
     * @param bool   $marcador  Lo que el front afirma sobre esta edicion
     * @return int Cuantas OCs se marcaron
     */
    private function marcarFechaFijada($tipo, $ids, $antes, $marcador)
    {
        if (!$marcador || empty($ids) || !isset(self::FECHAS_FIJABLES[$tipo])) {
            return 0;
        }

        $mapa = self::FECHAS_FIJABLES[$tipo];

        if (!$this->tieneColumna($mapa['conf'])) {
            return 0;
        }

        $this->cargarCronogramaFechas();
        $despues = CronogramaFechas::leerFechas($this->cid_central, $ids);

        // Solo las que efectivamente cambiaron de valor.
        $aMarcar = [];
        foreach ($despues as $id => $fila) {
            if (!isset($antes[$id])) continue;

            if ((string) $antes[$id][$mapa['columna']] !== (string) $fila[$mapa['columna']]) {
                $aMarcar[] = (int) $id;
            }
        }

        if (empty($aMarcar)) {
            return 0;
        }

        $usuario      = isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null;
        $placeholders = implode(',', array_fill(0, count($aMarcar), '?'));

        /* Los nombres de columna salen de FECHAS_FIJABLES, que es una
           constante de esta clase: no hay entrada del usuario en el SQL. */
        $sets   = [$mapa['conf'] . ' = 1'];
        $params = [];

        if ($this->tieneColumna($mapa['usuario']) && $this->tieneColumna($mapa['fecha'])) {
            $sets[]   = $mapa['usuario'] . ' = ?';
            $sets[]   = $mapa['fecha'] . ' = GETDATE()';
            $params[] = $usuario;
        }

        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                   SET " . implode(', ', $sets) . "
                 WHERE ID IN ($placeholders)";

        $stmt = sqlsrv_query($this->cid_central, $sql, array_merge($params, $aMarcar));

        if ($stmt === false) {
            error_log('marcarFechaFijada(' . $tipo . '): ' . print_r(sqlsrv_errors(), true));
            return 0;
        }

        error_log('marcarFechaFijada: ' . count($aMarcar) . ' OCs con '
                  . $mapa['columna'] . ' fijada a mano');

        return count($aMarcar);
    }

    /**
     * Vuelve una fecha al calculo automatico.
     *
     * LA FORMULA YA NO SE DUPLICA ACA. Este metodo repetia "+5 dias" con un
     * comentario que lo reconocia como deuda: "la formula canonica vive en
     * recalcularFechaEstimadaPago() de js/cargaInicial.js y este metodo la
     * repite porque el endpoint tiene que devolver la fecha ya resuelta.
     * Moverla al backend -y que el JS deje de calcularla- es lo que cierra
     * esta duplicacion". Eso es lo que se hizo: la fecha sale de
     * CronogramaFechas::cadenaDeFechas() y el JS ya no calcula nada.
     *
     * DE DONDE SALE CADA FECHA:
     *   pago              del embarque (FECHA_EMB, con fallback a FECHA_EST_EMB)
     *   arribo            del embarque
     *   nacionalizacion   del arribo que quede vigente
     *
     * OJO CON LA NACIONALIZACION: se recalcula sobre el FECHA_ARR guardado, no
     * sobre el arribo proyectado. Si a un contenedor le confirmaron la ETA,
     * volver la nacionalizacion a automatico tiene que devolver "esa ETA + 5",
     * y no "embarque + 45 + 5", que es una fecha que ya se sabe que no va a
     * pasar.
     *
     * @param int    $id    OC cuyo grupo se revierte
     * @param string $tipo  clave de FECHAS_FIJABLES
     * @return array ['fecha' => 'Y-m-d'|null, 'ocs' => int, 'aviso' => string|null]
     */
    public function revertirFechaAuto($id, $tipo = 'PAGO')
    {
        if (!isset(self::FECHAS_FIJABLES[$tipo])) {
            throw new Exception('Tipo de fecha desconocido: ' . $tipo);
        }

        $mapa = self::FECHAS_FIJABLES[$tipo];

        if (!$this->tieneColumna($mapa['conf'])) {
            throw new Exception(
                'Todavía no se puede volver a automático: falta la columna '
                . $mapa['conf'] . ' en esta base. Corré los scripts '
                . 'comercioExterior/sql/10_fecha_pago_manual.sql y '
                . '11_fechas_fijadas_arribo_nacionalizacion.sql.'
            );
        }

        $idPrincipal = $this->resolverIdPrincipal($id);
        if (!$idPrincipal) {
            throw new Exception('No se encontró el despacho ' . intval($id));
        }

        /* Se revierte TODO EL GRUPO, no la OC sola: el guardado replica estas
           fechas a todas las OCs del contenedor -ver
           actualizarEncabezadoGrupo()- y dejar media docena de hermanas
           fijadas haria que la proxima edicion volviera a pisar la que se
           acaba de liberar. */
        $idsGrupo = $this->obtenerIdsDelGrupo($idPrincipal);
        if (empty($idsGrupo)) {
            throw new Exception('No se encontró el despacho ' . intval($id));
        }

        $antes = $this->leerFechasParaHistorial($idsGrupo);
        if (empty($antes) || !isset($antes[$idPrincipal])) {
            throw new Exception('No se encontró el despacho ' . intval($id));
        }

        $this->cargarCronogramaFechas();

        $fila = $antes[$idPrincipal];
        $base = CronogramaFechas::fechaBaseEmbarque($fila);

        /* El arribo vigente entra a la cadena SALVO cuando lo que se esta
           revirtiendo es el arribo mismo: ahi hay que recalcularlo desde el
           embarque, que es justo lo que se esta pidiendo. */
        $arriboVigente = ($tipo === 'ARRIBO') ? null : $fila['FECHA_ARR'];

        $cadena = CronogramaFechas::cadenaDeFechas(
            $this->cid_central, $base, $arriboVigente, $fila['FECHA_REC']
        );

        if (!empty($cadena['faltan'])) {
            throw new Exception(
                'No se puede recalcular: faltan parámetros ('
                . implode(', ', $cadena['faltan']) . '). Corré '
                . 'comercioExterior/sql/12_parametros_fechas_derivadas.sql en esta base.'
            );
        }

        $aviso      = null;
        $fechaNueva = $cadena['fechas'][$mapa['cadena']];

        if ($fechaNueva === null) {
            $aviso = 'La fecha quedó en automático, pero no se pudo recalcular porque '
                   . 'el contenedor no tiene fecha de embarque ni estimada de embarque. '
                   . 'Se mantiene la fecha que tenía.';
        }

        $placeholders = implode(',', array_fill(0, count($idsGrupo), '?'));

        if (sqlsrv_begin_transaction($this->cid_central) === false) {
            throw new Exception('No se pudo abrir la transacción para revertir la fecha');
        }

        try {
            /* El BIT y la fecha se escriben JUNTOS: si se apagara el BIT y
               fallara el recalculo, el contenedor quedaria diciendo "esta fecha
               es automatica" al lado de una fecha que nadie calculo. */
            $sets = [$mapa['conf'] . ' = 0'];

            if ($this->tieneColumna($mapa['usuario']) && $this->tieneColumna($mapa['fecha'])) {
                $sets[] = $mapa['usuario'] . ' = NULL';
                $sets[] = $mapa['fecha'] . ' = NULL';
            }

            if ($fechaNueva !== null) {
                $sets[] = $mapa['columna'] . ' = ?';
            }

            $sqlConf = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                           SET " . implode(', ', $sets) . "
                         WHERE ID IN ($placeholders)";

            $params = ($fechaNueva !== null)
                ? array_merge([$fechaNueva], $idsGrupo)
                : $idsGrupo;

            $stmt = sqlsrv_query($this->cid_central, $sqlConf, $params);
            if ($stmt === false) {
                throw new Exception('Error al revertir: ' . print_r(sqlsrv_errors(), true));
            }

            sqlsrv_commit($this->cid_central);
        } catch (Throwable $e) {
            sqlsrv_rollback($this->cid_central);
            throw $e;
        }

        /* Volver el arribo a automatico mueve la distribucion, que cuelga de
           el. La resuelve recalcularDistribucion(), que ya respeta
           DIST_ORIGEN y no pisa lo movido a mano. */
        if ($tipo === 'ARRIBO' && $fechaNueva !== null) {
            $this->recalcularDistribucion($idsGrupo, $fechaNueva, $antes);
        }

        /* El historial va DESPUES del commit y con su propio motivo: es el
           mismo criterio que recalcularDistribucion(), donde la cascada
           automatica se registra aparte de lo que edito el usuario.

           EL DETALLE SALE DE LOS PARAMETROS que se acaban de usar, no de un
           literal: si manana DIAS_ARR_DESP pasa a 6, el historial viejo sigue
           diciendo la verdad sobre con que numero se calculo esa fila. */
        if ($fechaNueva !== null) {
            $usuario = isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null;
            $dias    = $cadena['parametros'];

            if ($tipo === 'NACIONALIZACION') {
                $detalle = 'arribo + ' . $dias['DIAS_ARR_DESP'] . ' días';
            } elseif ($tipo === 'ARRIBO') {
                $detalle = 'embarque + ' . $dias['DIAS_EMB_ARR'] . ' días';
            } else {
                $detalle = 'embarque + ' . $dias['DIAS_EMB_PAGO'] . ' días';
            }

            foreach ($idsGrupo as $idOc) {
                if (!isset($antes[$idOc])) continue;
                if ((string) $antes[$idOc][$mapa['columna']] === (string) $fechaNueva) continue;

                CronogramaFechas::registrarHistorial($this->cid_central, [
                    'idEncabezado'  => $idOc,
                    'ordenCompra'   => $antes[$idOc]['ORDEN_COMPRA'],
                    'contenedor'    => $antes[$idOc]['CONTENEDOR'],
                    'campo'         => $mapa['columna'],
                    'valorAnterior' => $antes[$idOc][$mapa['columna']],
                    'valorNuevo'    => $fechaNueva,
                    'motivo'        => MotivosFecha::RECALCULO_AUTOMATICO,
                    'observacion'   => 'Se volvió al cálculo automático (' . $detalle . ')',
                    'usuario'       => $usuario,
                    'origen'        => 'GESTION_DESPACHOS',
                ]);
            }
        }

        return [
            'fecha' => $fechaNueva,
            'ocs'   => count($idsGrupo),
            'aviso' => $aviso,
        ];
    }

    /**
     * Compatibilidad con el endpoint que creo el script 10.
     * Ver revertirFechaAuto(), que es donde esta la implementacion.
     */
    public function revertirFechaPagoAuto($id)
    {
        return $this->revertirFechaAuto($id, 'PAGO');
    }


    public function insertarEncabezado($datosDeCabezera)
    {

        $codProv = substr($datosDeCabezera['cod_proveedor'], 0, 6);

        // Sección 1 - Datos Iniciales (campos obligatorios)
        $sql = "INSERT INTO RO_T_IMPORTACIONES_ENCABEZADO(
            FECHA_MOV, COD_PROVEE, PROVEEDOR, CONTENEDOR, MATERIAL, ORIGEN, 
            VALOR_FOB_DOLAR, FECHA_EST_EMB, ORDEN_COMPRA, OCM, DESPACHANTE";

        $values = "VALUES (
            GETDATE(),
            '" . $codProv . "',
            '" . $datosDeCabezera['proveedor'] . "',
            '" . $datosDeCabezera['contenedor'] . "',
            '" . $datosDeCabezera['material'] . "',
            '" . $datosDeCabezera['origen'] . "',
            '" . $datosDeCabezera['valorFobDolar'] . "',
            '" . $datosDeCabezera['fechaEstEmb'] . "',
            '" . $datosDeCabezera['ordenCompra'] . "',
            '" . $datosDeCabezera['ocm'] . "',
            '" . (isset($datosDeCabezera['despachante']) ? $datosDeCabezera['despachante'] : 'Laffitte') . "'";

        // Campos calculados automáticamente que se guardan desde la Sección 1
        if (isset($datosDeCabezera['fechaArr']) && !empty($datosDeCabezera['fechaArr'])) {
            $sql .= ", FECHA_ARR";
            $values .= ", '" . $datosDeCabezera['fechaArr'] . "'";
        }

        if (isset($datosDeCabezera['fechaPago']) && !empty($datosDeCabezera['fechaPago'])) {
            $sql .= ", FECHA_PAGO";
            $values .= ", '" . $datosDeCabezera['fechaPago'] . "'";
        }

        if (isset($datosDeCabezera['fechaDespAdu']) && !empty($datosDeCabezera['fechaDespAdu'])) {
            $sql .= ", FECHA_DESP_ADU";
            $values .= ", '" . $datosDeCabezera['fechaDespAdu'] . "'";
        }

        // Campos opcionales de secciones 2 y 3 (solo si vienen en el array)
        // FECHA_EMB es el ETD (Estimated Time of Departure - fecha de salida real)
        if (isset($datosDeCabezera['fechaEmb']) && !empty($datosDeCabezera['fechaEmb'])) {
            $sql .= ", FECHA_EMB";
            $values .= ", '" . $datosDeCabezera['fechaEmb'] . "'";
        }

        if (isset($datosDeCabezera['numeroBl']) && !empty($datosDeCabezera['numeroBl'])) {
            $sql .= ", NUMERO_BL";
            $values .= ", '" . $datosDeCabezera['numeroBl'] . "'";
        }

        if (isset($datosDeCabezera['facturaProveedor']) && !empty($datosDeCabezera['facturaProveedor'])) {
            $sql .= ", FACTURA";
            $values .= ", '" . $datosDeCabezera['facturaProveedor'] . "'";
        }

        if (isset($datosDeCabezera['tipoCambio']) && !empty($datosDeCabezera['tipoCambio'])) {
            $sql .= ", TIPO_CAMBIO";
            $values .= ", '" . $datosDeCabezera['tipoCambio'] . "'";
        }

        if (isset($datosDeCabezera['valorFobPeso']) && !empty($datosDeCabezera['valorFobPeso'])) {
            $sql .= ", VALOR_FOB_PESO";
            $values .= ", '" . (float) ($datosDeCabezera['valorFobPeso'] + 0.15) . "'";
        }

        if (isset($datosDeCabezera['formaPago']) && !empty($datosDeCabezera['formaPago'])) {
            $sql .= ", FORMA_PAGO";
            $values .= ", '" . $datosDeCabezera['formaPago'] . "'";
        }

        if (isset($datosDeCabezera['despacho']) && !empty($datosDeCabezera['despacho'])) {
            $sql .= ", DESPACHO";
            $values .= ", '" . $datosDeCabezera['despacho'] . "'";
        }

        if (isset($datosDeCabezera['fechaEstPago']) && !empty($datosDeCabezera['fechaEstPago'])) {
            $sql .= ", FECHA_EST_PAGO";
            $values .= ", '" . $datosDeCabezera['fechaEstPago'] . "'";
        }

        if (isset($datosDeCabezera['puertoOrigen']) && !empty($datosDeCabezera['puertoOrigen'])) {
            $sql .= ", PUERTO_ORIGEN";
            $values .= ", '" . $datosDeCabezera['puertoOrigen'] . "'";
        }

        if (isset($datosDeCabezera['terminal']) && !empty($datosDeCabezera['terminal'])) {
            $sql .= ", TERMINAL";
            $values .= ", '" . $datosDeCabezera['terminal'] . "'";
        }

        // ETA Confirmada (bit - 0 o 1)
        if (isset($datosDeCabezera['etaConfirmada'])) {
            $sql .= ", ETA_CONFIRMADA";
            $values .= ", " . $datosDeCabezera['etaConfirmada'];
        }

        $sql .= ") " . $values . ");";

        // Log del SQL para debugging
        error_log("SQL a ejecutar: " . $sql);

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en INSERT: " . print_r($errors, true));
                throw new Exception("Error al insertar: " . $errors[0]['message']);
            }

            $queryIdentity = "SELECT @@IDENTITY as ID";
            $stmtIdentity = sqlsrv_query($this->cid_central, $queryIdentity);

            if ($stmtIdentity === false) {
                error_log("Error al obtener ID: " . print_r(sqlsrv_errors(), true));
                return null;
            }

            $row = sqlsrv_fetch_array($stmtIdentity, SQLSRV_FETCH_ASSOC);
            $id = $row ? $row['ID'] : null;

            error_log("ID insertado: " . $id);
            return $id;

        } catch (Exception $e) {
            error_log('Excepción en insertarEncabezado: ' . $e->getMessage());
            throw $e;
        }

    }

    public function traerOrdenManual()
    {
        $sql = "SELECT MAX(cast (RIGHT(ORDEN_COMPRA,'8')as INT)+1 ) AS nroOrden FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE OCM = 1;";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

            $rows = $v;
        }
        return ($rows);

    }

    public function listarTodosLosDespachos()
    {
        $sql = "SELECT TOP 100
                A.ID,
                FECHA_MOV,
                COD_PROVEE,
                PROVEEDOR,
                CONTENEDOR,
                MATERIAL,
                ORDEN_COMPRA,
                FECHA_EST_EMB,
                FECHA_EMB,
                NUMERO_BL,
                FACTURA,
                A.TIPO_CAMBIO,
                VALOR_FOB_DOLAR
            FROM RO_T_IMPORTACIONES_ENCABEZADO A
            LEFT JOIN RO_T_IMPORTACIONES_DETALLE B ON A.ID = B.ID_MG
            WHERE DESPACHO IS NULL OR B.ID_MG IS NULL
            AND A.FECHA_MOV >= GETDATE()-90
            ORDER BY ID DESC";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                error_log("Error en listarTodosLosDespachos: " . print_r(sqlsrv_errors(), true));
                return [];
            }

            $despachos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir objetos DateTime a strings
                if (isset($row['FECHA_MOV']) && is_object($row['FECHA_MOV'])) {
                    $row['FECHA_MOV'] = $row['FECHA_MOV']->format('Y-m-d');
                }
                if (isset($row['FECHA_EST_EMB']) && is_object($row['FECHA_EST_EMB'])) {
                    $row['FECHA_EST_EMB'] = $row['FECHA_EST_EMB']->format('Y-m-d');
                }
                if (isset($row['FECHA_EMB']) && is_object($row['FECHA_EMB'])) {
                    $row['FECHA_EMB'] = $row['FECHA_EMB']->format('Y-m-d');
                }

                $despachos[] = $row;
            }

            return $despachos;

        } catch (Exception $e) {
            error_log('Error en listarTodosLosDespachos: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerDespachoPorId($id)
    {
        $sql = "SELECT * FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = ?";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql, array($id));

            if ($stmt === false) {
                error_log("Error en obtenerDespachoPorId: " . print_r(sqlsrv_errors(), true));
                return null;
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($row) {
                // Convertir objetos DateTime a strings en formato DD/MM/YYYY
                $dateFields = [
                    'FECHA_MOV',
                    'FECHA_EST_EMB',
                    'FECHA_EMB',
                    'FECHA_ARR',
                    'FECHA_PAGO',
                    'FECHA_DESP_ADU',
                    'FECHA_EST_PAGO'
                ];

                foreach ($dateFields as $field) {
                    if (isset($row[$field]) && is_object($row[$field])) {
                        $row[$field] = $row[$field]->format('d/m/Y');
                    }
                }

                /* LOS TRES BITS DE "FIJADA A MANO" llegan por el SELECT * en
                   cuanto corrieron los scripts 10 y 11, y no llegan si no
                   corrieron. Se normalizan a 0/1 acá, una sola vez: un BIT de
                   SQL Server puede venir como '0', que en el JSON seria un
                   string verdadero y dejaria toda la base marcada como manual.

                   LAS CLAVES SE DEFINEN SIEMPRE, asi que el front no tiene que
                   preguntar si el DDL corrio: sin la columna valen 0, que en el
                   caso del pago y de la nacionalizacion es "se recalcula
                   sola" -el comportamiento viejo- y en el del arribo es "ETA
                   estimada", que es lo que ETA_CONFIRMADA ya significaba. */
                foreach (self::FECHAS_FIJABLES as $mapa) {
                    $row[$mapa['conf']] =
                        (isset($row[$mapa['conf']]) && (string) $row[$mapa['conf']] === '1') ? 1 : 0;

                    if (isset($row[$mapa['fecha']]) && is_object($row[$mapa['fecha']])) {
                        $row[$mapa['fecha']] = $row[$mapa['fecha']]->format('d/m/Y H:i');
                    }
                    if (!array_key_exists($mapa['usuario'], $row)) {
                        $row[$mapa['usuario']] = null;
                    }
                    if (!array_key_exists($mapa['fecha'], $row)) {
                        $row[$mapa['fecha']] = null;
                    }
                }

                return $row;
            }

            return null;

        } catch (Exception $e) {
            error_log('Error en obtenerDespachoPorId: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Marca una OC como hija de otra (vinculación por contenedor compartido).
     * Solo actualiza si la fila todavía no tiene padre (idempotente).
     */
    public function vincularComoPadre($idHijo, $idPadre) {
        $idHijo  = intval($idHijo);
        $idPadre = intval($idPadre);

        if ($idHijo <= 0 || $idPadre <= 0 || $idHijo === $idPadre) {
            return false;
        }

        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                SET ID_PADRE = ?
                WHERE ID = ? AND ID_PADRE IS NULL";

        $stmt = sqlsrv_query($this->cid_central, $sql, array($idPadre, $idHijo));

        if ($stmt === false) {
            error_log('[vincularComoPadre] Error: ' . print_r(sqlsrv_errors(), true));
            return false;
        }

        return true;
    }

    /**
     * Devuelve el ID de la OC principal del grupo.
     * Si el ID dado ya es principal (ID_PADRE IS NULL), retorna el mismo.
     * Si es hijo, retorna el padre.
     */
    public function resolverIdPrincipal($idMg) {
        $idMg = intval($idMg);
        $sql = "SELECT COALESCE(ID_PADRE, ID) AS ID_PRINCIPAL
                FROM RO_T_IMPORTACIONES_ENCABEZADO
                WHERE ID = ?";

        $stmt = sqlsrv_query($this->cid_central, $sql, array($idMg));
        if ($stmt === false) return $idMg;

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? intval($row['ID_PRINCIPAL']) : $idMg;
    }

    /**
     * Devuelve array con todos los IDs del grupo (principal + hijas).
     * Si el ID dado es hijo, primero resuelve al padre.
     */
    public function obtenerIdsDelGrupo($idMg) {
        $idPrincipal = $this->resolverIdPrincipal($idMg);

        $ids = [$idPrincipal];

        $sql = "SELECT ID FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID_PADRE = ?";
        $stmt = sqlsrv_query($this->cid_central, $sql, array($idPrincipal));

        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $ids[] = intval($row['ID']);
            }
        }

        return $ids;
    }

    /**
     * Devuelve array con los datos de todas las OCs del grupo.
     * Cada elemento: ['ID' => int, 'ORDEN_COMPRA' => string, 'ID_PADRE' => int|null]
     * El principal aparece primero.
     */
    public function obtenerOrdenesDelGrupo($idMg) {
        $idPrincipal = $this->resolverIdPrincipal($idMg);

        $sql = "SELECT ID, ORDEN_COMPRA, ID_PADRE
                FROM RO_T_IMPORTACIONES_ENCABEZADO
                WHERE ID = ? OR ID_PADRE = ?
                ORDER BY (CASE WHEN ID_PADRE IS NULL THEN 0 ELSE 1 END), ID";

        $stmt = sqlsrv_query($this->cid_central, $sql, array($idPrincipal, $idPrincipal));
        if ($stmt === false) return [];

        $resultado = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultado[] = $row;
        }
        return $resultado;
    }

    /**
     * Recalcula FECHA_DISTRI de las OCs indicadas cuando se mueve FECHA_ARR.
     *
     * Delega en CronogramaFechas, que es la misma regla que usa el drag & drop
     * del cronograma: solo se tocan las filas con DIST_ORIGEN = 'A'; las
     * movidas a mano ('M') o confirmadas ('C') quedan intactas.
     */
    private function recalcularDistribucion($ids, $fechaArribo, $antes = null) {
        $this->cargarCronogramaFechas();

        $tocadas = CronogramaFechas::recalcularDistribucion(
            $this->cid_central, $ids, $fechaArribo
        );

        if (empty($tocadas)) {
            return [];
        }

        error_log('recalcularDistribucion: ' . count($tocadas) .
                  ' OCs con FECHA_DISTRI recalculada a ' . reset($tocadas));

        // La cascada se registra aparte, con su propio motivo.
        if ($antes !== null) {
            $parametros = CronogramaFechas::obtenerParametros($this->cid_central);
            foreach ($tocadas as $id => $nueva) {
                if (!isset($antes[$id])) continue;
                CronogramaFechas::registrarHistorial($this->cid_central, [
                    'idEncabezado'  => $id,
                    'ordenCompra'   => $antes[$id]['ORDEN_COMPRA'],
                    'contenedor'    => $antes[$id]['CONTENEDOR'],
                    'campo'         => 'FECHA_DISTRI',
                    'valorAnterior' => CronogramaFechas::derivarDistribucion($antes[$id], $parametros),
                    'valorNuevo'    => $nueva,
                    'motivo'        => MotivosFecha::RECALCULO_AUTOMATICO,
                    'observacion'   => null,
                    'usuario'       => isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null,
                    'origen'        => 'GESTION_DESPACHOS',
                ]);
            }
        }

        return $tocadas;
    }

    private function cargarCronogramaFechas() {
        require_once __DIR__ . '/../cronogramaDespachos/class/CronogramaFechas.php';
        require_once __DIR__ . '/../cronogramaDespachos/class/MotivosFecha.php';
    }

    /**
     * Fechas de las OCs indicadas, para comparar antes y despues de un UPDATE.
     */
    private function leerFechasParaHistorial($ids) {
        $this->cargarCronogramaFechas();
        return CronogramaFechas::leerFechas($this->cid_central, $ids);
    }

    /**
     * Compara el estado previo contra el actual y registra una fila de
     * historial por cada campo de fecha que cambio.
     *
     * Es lo que evita que el historial quede con agujeros: sin esto, toda
     * edicion hecha desde gestion de despachos no dejaria rastro y el
     * historial solo mostraria lo movido desde el cronograma.
     *
     * MOTIVO y OBSERVACION quedan en NULL a proposito: el formulario de
     * gestion de despachos no los pide, y sumarle campos obligatorios no
     * entra en esta tanda.
     */
    private function registrarCambiosDeFecha($ids, $antes) {
        if (empty($antes)) return;

        $this->cargarCronogramaFechas();
        $despues = CronogramaFechas::leerFechas($this->cid_central, $ids);
        $usuario = isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null;

        foreach ($despues as $id => $filaDespues) {
            if (!isset($antes[$id])) continue;

            /* CAMPOS_HISTORIAL y no CAMPOS_EDITABLES: la segunda es lo que el
               cronograma puede arrastrar, y FECHA_EST_PAGO no es eso -no entra
               en ORDEN_FLUJO ni se dibuja en el calendario- pero si queremos
               rastro de sus cambios. Ver CronogramaFechas. */
            foreach (CronogramaFechas::CAMPOS_HISTORIAL as $campo) {
                $previo = $antes[$id][$campo];
                $actual = $filaDespues[$campo];

                if ((string) $previo === (string) $actual) {
                    continue;
                }

                CronogramaFechas::registrarHistorial($this->cid_central, [
                    'idEncabezado'  => $id,
                    'ordenCompra'   => $antes[$id]['ORDEN_COMPRA'],
                    'contenedor'    => $antes[$id]['CONTENEDOR'],
                    'campo'         => $campo,
                    'valorAnterior' => $previo,
                    'valorNuevo'    => $actual,
                    'motivo'        => null,
                    'observacion'   => null,
                    'usuario'       => $usuario,
                    'origen'        => 'GESTION_DESPACHOS',
                ]);
            }
        }
    }

    /**
     * Actualiza campos comunes de embarque/despacho en TODAS las OCs del grupo.
     * No toca ORDEN_COMPRA, OCM ni ID.
     *
     * @param int   $idEncabezado  ID de cualquier OC del grupo
     * @param array $datos         ['COLUMNA' => valor, ...]
     * @param array|bool $marcasManuales  que fechas afirma el front que fijo el
     *                  usuario en esta edicion: ['PAGO' => bool, 'ARRIBO' =>
     *                  bool, 'NACIONALIZACION' => bool]. Se acepta un bool
     *                  suelto por compatibilidad con la firma vieja, que
     *                  recibia solo el marcador del pago.
     * @return bool
     */
    public function actualizarEncabezadoGrupo($idEncabezado, $datos, $marcasManuales = []) {
        $idPrincipal = $this->resolverIdPrincipal($idEncabezado);
        if (!$idPrincipal) return false;

        $idsGrupo = $this->obtenerIdsDelGrupo($idPrincipal);
        if (empty($idsGrupo)) return false;

        $columnasPermitidas = [
            'FECHA_MOV', 'FECHA_EMB', 'FECHA_OC',
            'FACTURA', 'NUMERO_BL',
            'TIPO_CAMBIO', 'VALOR_FOB_DOLAR', 'VALOR_FOB_PESO',
            'FECHA_ARR', 'FECHA_DESP_ADU',
            'FECHA_EST_PAGO', 'FECHA_EST_EMB',
            'DESPACHANTE', 'PUERTO_ORIGEN', 'TERMINAL', 'ETA_CONFIRMADA',
            'CONTENEDOR', 'DESPACHO', 'MATERIAL', 'ORIGEN', 'FORMA_PAGO',
            'COD_PROVEE', 'PROVEEDOR',
        ];

        $campos     = [];
        $parametros = [];

        foreach ($datos as $col => $valor) {
            if (in_array($col, $columnasPermitidas, true)) {
                $campos[]     = "$col = ?";
                $parametros[] = ($valor === '' ? null : $valor);
            }
        }

        if (empty($campos)) return false;

        $placeholders = implode(',', array_fill(0, count($idsGrupo), '?'));
        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                SET "    . implode(', ', $campos) . "
                WHERE ID IN ($placeholders)";

        $parametros = array_merge($parametros, $idsGrupo);

        // Estado previo, para poder registrar en el historial que cambio.
        $antes = $this->leerFechasParaHistorial($idsGrupo);

        $stmt = sqlsrv_query($this->cid_central, $sql, $parametros);
        if ($stmt === false) {
            error_log('actualizarEncabezadoGrupo: ' . print_r(sqlsrv_errors(), true));
            return false;
        }

        $this->registrarCambiosDeFecha($idsGrupo, $antes);

        /* La marca de "fijada a mano" se propaga al grupo entero porque la
           fecha tambien se propaga: arriba, FECHA_EST_PAGO, FECHA_ARR y
           FECHA_DESP_ADU estan las tres en columnasPermitidas. Si el BIT
           quedara solo en la OC principal, la proxima edicion recalcularia el
           de las hermanas y las pisaria. */
        $marcas = self::normalizarMarcasManuales($marcasManuales);
        foreach ($marcas as $tipo => $fijada) {
            $this->marcarFechaFijada($tipo, $idsGrupo, $antes, $fijada);
        }

        // Si se movio el arribo, la distribucion automatica lo sigue.
        if (array_key_exists('FECHA_ARR', $datos) && !empty($datos['FECHA_ARR'])) {
            $this->recalcularDistribucion($idsGrupo, $datos['FECHA_ARR'], $antes);
        }

        $afectados = sqlsrv_rows_affected($stmt);
        error_log("actualizarEncabezadoGrupo: idRecibido=$idEncabezado, " .
                  "idPrincipal=$idPrincipal, ocsGrupo=" . count($idsGrupo) .
                  ", filasAfectadas=$afectados");

        return true;
    }

    public function actualizarEncabezado($id, $datosDeCabezera)
    {
        $id = intval($id);
        $codProv = substr(str_replace("'", "''", $datosDeCabezera['cod_proveedor']), 0, 6);

        // Construir UPDATE dinámico
        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO SET ";
        $updates = [];

        // Función auxiliar para formatear números de forma segura para SQL
        $fmtNum = function ($val) {
            return str_replace(',', '.', (string) $val);
        };

        // Función auxiliar para escapar strings
        $esc = function ($val) {
            return str_replace("'", "''", (string) $val);
        };

        // Campos que siempre se actualizan
        $updates[] = "COD_PROVEE = '" . $esc($codProv) . "'";
        $updates[] = "PROVEEDOR = '" . $esc($datosDeCabezera['proveedor']) . "'";
        $updates[] = "CONTENEDOR = '" . $esc($datosDeCabezera['contenedor']) . "'";
        $updates[] = "MATERIAL = '" . $esc($datosDeCabezera['material']) . "'";
        $updates[] = "ORIGEN = '" . $esc($datosDeCabezera['origen']) . "'";
        $updates[] = "VALOR_FOB_DOLAR = '" . $fmtNum($datosDeCabezera['valorFobDolar']) . "'";
        $updates[] = "FECHA_EST_EMB = '" . $esc($datosDeCabezera['fechaEstEmb']) . "'";
        $updates[] = "ORDEN_COMPRA = '" . $esc($datosDeCabezera['ordenCompra']) . "'";
        $updates[] = "OCM = '" . $esc($datosDeCabezera['ocm']) . "'";

        // Despachante (con valor por defecto si no viene)
        if (isset($datosDeCabezera['despachante']) && !empty($datosDeCabezera['despachante'])) {
            $updates[] = "DESPACHANTE = '" . $esc($datosDeCabezera['despachante']) . "'";
        }

        // Campos opcionales - actualizar siempre si están en el array (incluso si vacíos)
        if (isset($datosDeCabezera['fechaArr'])) {
            if (!empty($datosDeCabezera['fechaArr'])) {
                $updates[] = "FECHA_ARR = '" . $esc($datosDeCabezera['fechaArr']) . "'";
            } else {
                $updates[] = "FECHA_ARR = NULL";
            }
        }

        if (isset($datosDeCabezera['fechaPago']) && !empty($datosDeCabezera['fechaPago'])) {
            $updates[] = "FECHA_PAGO = '" . $esc($datosDeCabezera['fechaPago']) . "'";
        }

        if (isset($datosDeCabezera['fechaDespAdu']) && !empty($datosDeCabezera['fechaDespAdu'])) {
            $updates[] = "FECHA_DESP_ADU = '" . $esc($datosDeCabezera['fechaDespAdu']) . "'";
        }

        if (isset($datosDeCabezera['fechaEmb']) && !empty($datosDeCabezera['fechaEmb'])) {
            $updates[] = "FECHA_EMB = '" . $esc($datosDeCabezera['fechaEmb']) . "'";
        }

        if (isset($datosDeCabezera['numeroBl']) && !empty($datosDeCabezera['numeroBl'])) {
            $updates[] = "NUMERO_BL = '" . $esc($datosDeCabezera['numeroBl']) . "'";
        }

        if (isset($datosDeCabezera['facturaProveedor']) && !empty($datosDeCabezera['facturaProveedor'])) {
            $updates[] = "FACTURA = '" . $esc($datosDeCabezera['facturaProveedor']) . "'";
        }

        if (isset($datosDeCabezera['tipoCambio']) && !empty($datosDeCabezera['tipoCambio'])) {
            $updates[] = "TIPO_CAMBIO = '" . $fmtNum($datosDeCabezera['tipoCambio']) . "'";
        }

        if (isset($datosDeCabezera['valorFobPeso']) && !empty($datosDeCabezera['valorFobPeso'])) {
            $updates[] = "VALOR_FOB_PESO = '" . $fmtNum($datosDeCabezera['valorFobPeso']) . "'";
        }

        if (isset($datosDeCabezera['formaPago']) && !empty($datosDeCabezera['formaPago'])) {
            $updates[] = "FORMA_PAGO = '" . $esc($datosDeCabezera['formaPago']) . "'";
        }

        if (isset($datosDeCabezera['despacho']) && !empty($datosDeCabezera['despacho'])) {
            $updates[] = "DESPACHO = '" . $esc($datosDeCabezera['despacho']) . "'";
        }

        if (isset($datosDeCabezera['fechaEstPago']) && !empty($datosDeCabezera['fechaEstPago'])) {
            $updates[] = "FECHA_EST_PAGO = '" . $esc($datosDeCabezera['fechaEstPago']) . "'";
        }

        if (isset($datosDeCabezera['puertoOrigen']) && !empty($datosDeCabezera['puertoOrigen'])) {
            $updates[] = "PUERTO_ORIGEN = '" . $esc($datosDeCabezera['puertoOrigen']) . "'";
        }

        if (isset($datosDeCabezera['terminal']) && !empty($datosDeCabezera['terminal'])) {
            $updates[] = "TERMINAL = '" . $esc($datosDeCabezera['terminal']) . "'";
        }

        // ETA Confirmada (bit - 0 o 1)
        if (isset($datosDeCabezera['etaConfirmada'])) {
            $updates[] = "ETA_CONFIRMADA = " . intval($datosDeCabezera['etaConfirmada']);
        }

        if (empty($updates))
            return false;

        $sql .= implode(", ", $updates);
        $sql .= " WHERE ID = " . $id;

        error_log("SQL UPDATE: " . $sql);

        // Estado previo, para el historial.
        $antes = $this->leerFechasParaHistorial([$id]);

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en actualizarEncabezado: " . print_r($errors, true));
                return false;
            }

            $this->registrarCambiosDeFecha([$id], $antes);

            /* Si el usuario movio una de las tres fechas calculadas, queda
               fijada y el recalculo deja de pisarla. Hacen falta las dos cosas
               -el marcador del front Y que el valor haya cambiado de verdad-;
               ver marcarFechaFijada(). */
            $marcas = self::normalizarMarcasManuales([
                'PAGO'            => !empty($datosDeCabezera['fechaEstPagoManual']),
                'ARRIBO'          => !empty($datosDeCabezera['fechaArrManual']),
                'NACIONALIZACION' => !empty($datosDeCabezera['fechaDespAduManual']),
            ]);

            foreach ($marcas as $tipo => $fijada) {
                $this->marcarFechaFijada($tipo, [$id], $antes, $fijada);
            }

            // Igual que en actualizarEncabezadoGrupo: mover el arribo arrastra
            // la distribucion automatica. Aca alcanza con esta OC, porque este
            // metodo actualiza una sola fila.
            if (isset($datosDeCabezera['fechaArr']) && !empty($datosDeCabezera['fechaArr'])) {
                $this->recalcularDistribucion([$id], $datosDeCabezera['fechaArr'], $antes);
            }

            return $id;

        } catch (Exception $e) {
            error_log('Error en actualizarEncabezado: ' . $e->getMessage());
            return false;
        }
    }

}