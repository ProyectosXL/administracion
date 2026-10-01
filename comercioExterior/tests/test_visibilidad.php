<?php
/**
 * Cuándo un contenedor deja de verse en Gestión de Despachos.
 *
 * QUÉ FIJA. La regla vive dos veces en este repo -en PHP, para probarla, y en
 * SQL, para filtrar la lista- y una tercera en ProyectosXL/finanzas. Acá se
 * prueba la función pura caso por caso, y después, contra la base y en sólo
 * lectura, que el SQL diga lo mismo que la función fila por fila.
 *
 * EL BORDE DEL CENTAVO es la parte delicada: Pagos::estadoSaldo() resta en
 * double y el SQL castea a FLOAT por eso. Con DECIMAL los dos lados caerían en
 * estados distintos justo en 0,01.
 */

require_once __DIR__ . '/../class/VisibilidadContenedor.php';

seccion('el estado del saldo es el de Pagos::obtenerResumen()');

chequear('sin pagos: PENDIENTE', 'PENDIENTE', Pagos::estadoSaldo(1000, 0));
chequear('pagado exacto: CANCELADO', 'CANCELADO', Pagos::estadoSaldo(1000, 1000));
chequear('medio centavo de menos sigue CANCELADO', 'CANCELADO', Pagos::estadoSaldo(1000, 999.995));
chequear('medio centavo de más sigue CANCELADO', 'CANCELADO', Pagos::estadoSaldo(1000, 1000.005));
chequear('dos centavos de más: SOBREPAGO', 'SOBREPAGO', Pagos::estadoSaldo(1000, 1000.02));
chequear('sin FOB: SIN_FOB aunque haya pagos', 'SIN_FOB', Pagos::estadoSaldo(0, 500));
chequear('FOB null: SIN_FOB', 'SIN_FOB', Pagos::estadoSaldo(null, 0));
// 100,01 - 100,00 en double es 0,010000000000005, que pasa la tolerancia
chequear('el borde del centavo en double: 100,01 contra 100 es PENDIENTE', 'PENDIENTE',
    Pagos::estadoSaldo(100.01, 100.00));
chequear('la tolerancia es la del cashflow', 0.01, Pagos::TOLERANCIA);

seccion('(B): qué estados cubren el FOB');

chequear('CANCELADO cubre', true, VisibilidadContenedor::fobCubierto('CANCELADO'));
chequear('SOBREPAGO cubre', true, VisibilidadContenedor::fobCubierto('SOBREPAGO'));
chequear('PENDIENTE no', false, VisibilidadContenedor::fobCubierto('PENDIENTE'));
chequear('SIN_FOB no: no hay contra qué medir', false, VisibilidadContenedor::fobCubierto('SIN_FOB'));

seccion('NOT (A AND B): sólo se va con costos Y el FOB cubierto');

$casos = [
    // tieneCostos, estado, visible
    [false, 'PENDIENTE', true,  'sin costos y con saldo'],
    [false, 'CANCELADO', true,  'sin costos y pagado: sigue, como antes'],
    [false, 'SOBREPAGO', true,  'sin costos y con sobrepago'],
    [false, 'SIN_FOB',   true,  'sin costos ni FOB'],
    [true,  'PENDIENTE', true,  'con costos y saldo pendiente: ESTO es lo nuevo'],
    [true,  'SIN_FOB',   true,  'con costos y sin FOB: no está cubierto'],
    [true,  'CANCELADO', false, 'con costos y pagado: se va'],
    [true,  'SOBREPAGO', false, 'con costos y sobrepago: se va'],
];
foreach ($casos as $c) {
    chequear($c[3], $c[2], VisibilidadContenedor::sigueVisible($c[0], $c[1]));
}

seccion('la ventana de 6 meses sólo aplica a lo que no tiene saldo');

chequear('viejo y con saldo: se ve igual', true,
    VisibilidadContenedor::visibleEnGestion(true, 'PENDIENTE', false));
chequear('viejo, sin costos y con saldo: se ve igual', true,
    VisibilidadContenedor::visibleEnGestion(false, 'PENDIENTE', false));
chequear('viejo y pagado sin costos: la ventana lo esconde, como antes', false,
    VisibilidadContenedor::visibleEnGestion(false, 'CANCELADO', false));
chequear('viejo y sin FOB: la ventana lo esconde', false,
    VisibilidadContenedor::visibleEnGestion(true, 'SIN_FOB', false));
chequear('reciente y pagado sin costos: se ve', true,
    VisibilidadContenedor::visibleEnGestion(false, 'CANCELADO', true));
chequear('reciente con costos y pagado: no se ve, ni dentro de la ventana', false,
    VisibilidadContenedor::visibleEnGestion(true, 'CANCELADO', true));

seccion('el SQL no se escribe con el corte viejo');

$sqlCostos = VisibilidadContenedor::sqlTieneCostos('X.ID');
chequear('(A) es un EXISTS', true, strpos($sqlCostos, 'EXISTS') === 0);
chequear('(A) mira el grupo entero', true, strpos($sqlCostos, 'COALESCE(GV.ID_PADRE, GV.ID) = X.ID') !== false);
chequear('el estado castea a FLOAT antes de restar', true,
    substr_count(VisibilidadContenedor::sqlEstadoPago('F', 'P'), 'AS FLOAT') >= 2);

/* ======================================================================
   CONTRA LA BASE, SÓLO LECTURA
   ====================================================================== */
seccion('contra la base: el SQL dice lo mismo que la función pura');

if (!Pruebas::hayBase()) {
    Pruebas::saltear('no hay conexión a la base');
} else {
    require_once __DIR__ . '/../../class/conexion.php';
    $cx = new Conexion();
    $central = $cx->conectar('central');

    /* El estado en SQL sobre valores escritos a mano, incluidos los bordes:
       si el CAST a FLOAT se fuera, el 100,01 contra 100 daría CANCELADO acá y
       PENDIENTE en PHP. */
    $valores = [[1000, 0], [1000, 1000], [1000, 999.995], [1000, 1000.005], [1000, 1000.02],
                [0, 500], [100.01, 100.00], [77408, 10000], [24750, 24750], [0.01, 0]];
    $filasSql = [];
    foreach ($valores as $v) {
        $filasSql[] = 'SELECT CAST(' . $v[0] . ' AS DECIMAL(18,2)) F, CAST(' . $v[1] . ' AS DECIMAL(18,3)) P';
    }
    $stmt = sqlsrv_query($central, 'SELECT V.F, V.P, '
        . VisibilidadContenedor::sqlEstadoPago('V.F', 'V.P') . ' AS E FROM ('
        . implode(' UNION ALL ', $filasSql) . ') V');

    $iguales = 0;
    $distintos = [];
    while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $php = Pagos::estadoSaldo($r['F'], $r['P']);
        if ($php === $r['E']) {
            $iguales++;
        } else {
            $distintos[] = $r['F'] . '/' . $r['P'] . ': SQL ' . $r['E'] . ', PHP ' . $php;
        }
    }
    chequear('el estado en SQL y en PHP coincide en los ' . count($valores) . ' bordes', [], $distintos);

    foreach (['central', 'uy'] as $entorno) {
        require_once __DIR__ . '/../class/estimacionCostos.php';
        $conn = $cx->conectar($entorno);

        $hayPagos = VisibilidadContenedor::hayTablaPagos($conn);
        $pagado = $hayPagos
            ? '(SELECT ISNULL(SUM(PG.MONTO), 0) FROM ' . VisibilidadContenedor::TABLA_PAGOS
              . ' PG WHERE PG.ID_ENCABEZADO = COALESCE(E.ID_PADRE, E.ID))'
            : 'CAST(0 AS DECIMAL(18,2))';

        /* El padrón entero, con los datos crudos que la regla necesita, para
           aplicarle la función pura y comparar contra lo que lista el SQL. */
        $stmt = sqlsrv_query($conn,
            "SELECT E.ID,
                    CASE WHEN " . VisibilidadContenedor::sqlTieneCostos('COALESCE(E.ID_PADRE, E.ID)')
                    . " THEN 1 ELSE 0 END AS TIENE_COSTOS,
                    ISNULL(P.VALOR_FOB_DOLAR, E.VALOR_FOB_DOLAR) AS FOB,
                    " . $pagado . " AS PAGADO,
                    CASE WHEN ISNULL(P.FECHA_MOV, E.FECHA_MOV) >= DATEADD(MONTH, -6, GETDATE())
                         THEN 1 ELSE 0 END AS EN_VENTANA
             FROM RO_T_IMPORTACIONES_ENCABEZADO E
             LEFT JOIN RO_T_IMPORTACIONES_ENCABEZADO P ON P.ID = COALESCE(E.ID_PADRE, E.ID)");

        $esperados = [];
        $todas = 0;
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $todas++;
            if (VisibilidadContenedor::visibleEnGestion(
                    intval($r['TIENE_COSTOS']) === 1,
                    Pagos::estadoSaldo($r['FOB'], $r['PAGADO']),
                    intval($r['EN_VENTANA']) === 1)) {
                $esperados[] = intval($r['ID']);
            }
        }

        $listado = (new EstimacionCostos($entorno))->listarDespachosTodosConPadre();
        $obtenidos = array_map('intval', array_column($listado, 'ID'));
        sort($esperados);
        sort($obtenidos);

        chequear($entorno . ': Gestión lista exactamente lo que dice la regla pura ('
            . count($esperados) . ' de ' . $todas . ')', $esperados, $obtenidos);
        chequear($entorno . ': sin filas repetidas (el EXISTS no multiplica)',
            count($obtenidos), count(array_unique($obtenidos)));

        $conCostosYCubierto = array_filter($listado, function ($f) {
            return $f['TIENE_COSTOS'] && VisibilidadContenedor::fobCubierto($f['ESTADO_PAGO']);
        });
        chequear($entorno . ': ninguna fila listada tiene costos y el FOB cubierto', 0,
            count($conCostosYCubierto));
    }
}
