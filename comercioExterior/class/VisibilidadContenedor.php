<?php
require_once __DIR__ . '/Pagos.php';

/**
 * Cuándo un contenedor deja de verse en Gestión de Despachos.
 *
 * LA REGLA
 * --------
 * Un contenedor deja de mostrarse sólo cuando cumple LAS DOS condiciones:
 *
 *   (A) TIENE COSTOS DE NACIONALIZACIÓN CONFIRMADOS: hay filas en
 *       RO_T_IMPORTACIONES_DETALLE para cualquier OC del grupo. La estimación
 *       de PCI (RO_T_IMPORTACIONES_ESTIMACION_DETALLE) NO cuenta: es una
 *       proyección, no el costo.
 *   (B) LOS PAGOS CUBREN EL FOB: Pagos::estadoSaldo() da CANCELADO o
 *       SOBREPAGO. El sobrepago cuenta como cubierto; SIN_FOB no.
 *
 * Hasta esta rama alcanzaba con (A): cargar los costos sacaba el contenedor
 * de la grilla aunque al proveedor del exterior todavía se le debiera plata, y
 * la única pantalla donde se cargan esos pagos es justamente ésta. Un
 * contenedor con el FOB pagado y sin costos sigue apareciendo, como siempre.
 *
 * SE EVALÚA POR GRUPO, COALESCE(ID_PADRE, ID): la principal y sus hijas
 * aparecen o desaparecen juntas. (A) es verdadera si hay detalle para
 * cualquier OC del grupo -el detalle se replica a todas, ver
 * OrdenDeCompra::insertDetalleReplicado()- y (B) usa el FOB y los pagos de la
 * principal, como Pagos::obtenerResumen().
 *
 * LA VENTANA DE 6 MESES DE GESTIÓN se aplica sólo a lo que no tiene saldo
 * pendiente: un contenedor con plata por pagar se muestra sin importar su
 * antigüedad, porque esconderlo es esconder la deuda. También va por grupo:
 * la fecha que cuenta es el FECHA_MOV de la principal.
 *
 *     visible = NOT (A AND B) AND (estado = PENDIENTE OR dentro de 6 meses)
 *
 * UNA FUNCIÓN PURA Y UN FRAGMENTO SQL, Y LOS DOS ACÁ
 * --------------------------------------------------
 * La lista se filtra en SQL -no se traen 400 filas para tirar la mitad- y la
 * regla se prueba en PHP sin base. Son dos escrituras de la misma regla en el
 * mismo archivo, y tests/test_visibilidad.php compara una contra la otra fila
 * por fila contra la base.
 *
 * EL ESTADO SE CALCULA EN FLOAT EN LOS DOS LADOS. Pagos::estadoSaldo() resta
 * en double; el SQL castea a FLOAT antes de restar para caer del mismo lado
 * del borde del centavo. Ver el docblock de estadoSaldo().
 *
 * ESTA REGLA ESTÁ DUPLICADA EN ProyectosXL/finanzas, en
 * Comex::sigueEnProveedores() y Comex::sqlSigueVisible(), para la pestaña
 * Proveedores del Exterior y para ComprasProyectadasDatos::cargado(). El
 * encabezado de Pagos.php y el de Comex.php son las dos mitades del pacto: si
 * cambia de un lado, se cambia del otro. Allá no hay ventana de tiempo.
 */
class VisibilidadContenedor
{
    const TABLA_DETALLE = 'RO_T_IMPORTACIONES_DETALLE';
    const TABLA_MAESTRO = 'RO_T_IMPORTACIONES_ENCABEZADO';
    const TABLA_PAGOS   = 'RO_T_IMPORTACIONES_ENCABEZADO_PAGOS';

    /** Los meses de la ventana de Gestión, para lo que ya no tiene saldo */
    const MESES_VENTANA = 6;

    /* ======================================================================
       LA REGLA, PURA
       ====================================================================== */

    /** (B): el FOB está cubierto. SIN_FOB no lo está: no hay contra qué medir */
    public static function fobCubierto($estado)
    {
        return $estado === 'CANCELADO' || $estado === 'SOBREPAGO';
    }

    /** NOT (A AND B): la parte de la regla que comparten las dos aplicaciones */
    public static function sigueVisible($tieneCostos, $estado)
    {
        return !($tieneCostos && self::fobCubierto($estado));
    }

    /**
     * La regla completa de Gestión de Despachos.
     *
     * @param bool   $tieneCostos     (A), a nivel grupo
     * @param string $estado          Pagos::estadoSaldo() de la principal
     * @param bool   $dentroDeVentana FECHA_MOV de la principal en los últimos 6 meses
     */
    public static function visibleEnGestion($tieneCostos, $estado, $dentroDeVentana)
    {
        return self::sigueVisible($tieneCostos, $estado)
            && ($estado === 'PENDIENTE' || $dentroDeVentana);
    }

    /* ======================================================================
       LA MISMA REGLA, EN SQL
       ====================================================================== */

    /**
     * (A) para el grupo cuya principal es $grupoExpr.
     *
     * EXISTS Y NO LEFT JOIN. El corte viejo era LEFT JOIN DETALLE ... WHERE
     * ID_MG IS NULL, que no multiplicaba filas porque se quedaba sólo con las
     * que NO tenían detalle. Ahora pasan contenedores con detalle, y el mismo
     * JOIN daría una fila por cada línea de costo.
     */
    public static function sqlTieneCostos($grupoExpr)
    {
        return "EXISTS (SELECT 1
                        FROM " . self::TABLA_DETALLE . " DV
                        INNER JOIN " . self::TABLA_MAESTRO . " GV ON GV.ID = DV.ID_MG
                        WHERE COALESCE(GV.ID_PADRE, GV.ID) = " . $grupoExpr . ")";
    }

    /**
     * Pagos::estadoSaldo() escrito en SQL. FLOAT a propósito: ver el
     * encabezado.
     *
     * @param string $fobExpr    FOB de la principal
     * @param string $pagadoExpr SUM(MONTO) de la principal, nunca NULL
     */
    public static function sqlEstadoPago($fobExpr, $pagadoExpr)
    {
        $fob   = "CAST(ISNULL(" . $fobExpr . ", 0) AS FLOAT)";
        $saldo = "(" . $fob . " - CAST(" . $pagadoExpr . " AS FLOAT))";

        return "CASE
                    WHEN " . $fob . " <= 0 THEN 'SIN_FOB'
                    WHEN " . $saldo . " < -" . Pagos::TOLERANCIA . " THEN 'SOBREPAGO'
                    WHEN ABS" . $saldo . " <= " . Pagos::TOLERANCIA . " THEN 'CANCELADO'
                    ELSE 'PENDIENTE'
                END";
    }

    /**
     * visibleEnGestion() sobre las columnas ya calculadas de un SELECT.
     *
     * @param string $tieneCostosCol columna BIT/INT con (A)
     * @param string $estadoCol      columna con el estado de sqlEstadoPago()
     * @param string $fechaMovCol    FECHA_MOV de la principal
     */
    public static function sqlVisibleEnGestion($tieneCostosCol, $estadoCol, $fechaMovCol)
    {
        return "NOT (" . $tieneCostosCol . " = 1 AND " . $estadoCol . " IN ('CANCELADO', 'SOBREPAGO'))
                AND (" . $estadoCol . " = 'PENDIENTE'
                     OR " . $fechaMovCol . " >= DATEADD(MONTH, -" . self::MESES_VENTANA . ", GETDATE()))";
    }

    /**
     * Si existe la tabla de pagos en esta base.
     *
     * Se pregunta antes de nombrarla, mismo criterio que
     * Comex::tienePagosComex() en el cashflow: nombrar una tabla ausente
     * rompe la pantalla entera con "Invalid object name".
     */
    public static function hayTablaPagos($conn)
    {
        $stmt = sqlsrv_query($conn, "SELECT OBJECT_ID('dbo." . self::TABLA_PAGOS . "', 'U') AS T");
        if ($stmt === false) {
            return false;
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        return $row && $row['T'] !== null;
    }

    /**
     * El aviso de que no se pueden leer los pagos, o null.
     *
     * Sin la tabla nada está pagado, así que todo contenedor con FOB es
     * PENDIENTE: la grilla muestra DE MÁS -contenedores ya pagados y con
     * costos que tendrían que haber salido- y lo dice.
     */
    public static function avisoSinPagos($hayTabla)
    {
        if ($hayTabla) {
            return null;
        }

        return 'No se encuentra la tabla ' . self::TABLA_PAGOS . ' en esta base, así que no se '
            . 'puede saber qué se le pagó a cada proveedor del exterior: todos los contenedores '
            . 'con FOB se tratan como pendientes de pago, y la grilla muestra también los que ya '
            . 'tienen costos cargados y podrían estar pagados.';
    }
}
