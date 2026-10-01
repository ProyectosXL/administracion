<?php
/**
 * El cableado: que cada lugar que escribe VALOR_FOB_DOLAR o FECHA_DESP_ADU
 * llame al recálculo, que el alta genere la estimación, y que el borrado y el
 * listado no vuelvan a lo de antes.
 *
 * Se prueba LEYENDO EL CÓDIGO, sin comentarios -ver codigoSinComentarios()-,
 * porque ninguna de estas cosas se puede ejercitar sin escribir en la base, y
 * estas pruebas no escriben. Es el mismo criterio que test_tablas_controles.php
 * del cashflow.
 *
 * LA LISTA DE DISPARADORES ES CERRADA: son los cuatro lugares que escriben
 * esas dos columnas fuera de los scripts SQL -relevado contra el código-, tres
 * acá y Comex::guardarFecha('NAC') en ProyectosXL/finanzas, que llama al
 * endpoint desde el navegador y se prueba allá. Si aparece un quinto, va acá.
 */

$base = __DIR__ . '/..';

seccion('el alta genera y confirma la estimación');

$alta = codigoSinComentarios($base . '/controller/insertarEncabezado.php');
chequear('insertarEncabezado.php llama a generarEstimacionAlta()', true,
    strpos($alta, '->generarEstimacionAlta($idPrincipal)') !== false);
chequear('sobre la principal del lote, y sólo si se creó', true,
    (bool) preg_match('/if \(\$idPrincipal !== null\)\s*\{\s*\$estimacionAlta = \$estimacionCostos->generarEstimacionAlta/', $alta));
chequear('la falla se informa en avisoEstimacion, no como error del alta', true,
    strpos($alta, "'avisoEstimacion' => \$avisoEstimacion") !== false);

$servicio = codigoSinComentarios($base . '/class/estimacionCostos.php');
$gen = cuerpoDe($servicio, 'generarEstimacionAlta');
chequear('la estimación nace CONFIRMADA', true, strpos($gen, ', 1, GETDATE())') !== false);
chequear('en una transacción', true,
    strpos($gen, 'sqlsrv_begin_transaction') !== false && strpos($gen, 'sqlsrv_rollback') !== false);
chequear('con la cuenta portada', true, strpos($servicio, 'CalculoEstimacion::calcular(') !== false);

seccion('los tres disparadores de Comercio Exterior llaman al recálculo');

$posAntes = strpos($alta, '$antesEstimacion = $estimacionCostos->obtenerDespacho($idDespacho)');
$posUpdate = strpos($alta, '->actualizarEncabezado($idDespacho');
$posGrupo = strpos($alta, '->actualizarEncabezadoGrupo(');
$posRecalc = strpos($alta, '->recalcularSiCambio($idDespacho, $antesEstimacion)');
chequear('edición: lee el estado previo ANTES de grabar', true,
    $posAntes !== false && $posUpdate !== false && $posAntes < $posUpdate);
chequear('edición: recalcula DESPUÉS de las dos escrituras', true,
    $posRecalc !== false && $posGrupo !== false && $posRecalc > $posGrupo);

$crono = codigoSinComentarios($base . '/cronogramaDespachos/controller/actualizarFechaCronograma.php');
chequear('cronograma: sólo cuando el campo es FECHA_DESP_ADU', true,
    strpos($crono, "if (\$campo === 'FECHA_DESP_ADU')") !== false);
chequear('cronograma: recalcula después del commit', true,
    strpos($crono, '->recalcularSiCambio($idEncabezado, $antesEstimacion)') > strpos($crono, 'sqlsrv_commit($conn)'));
chequear('cronograma: mover la ETA no dispara nada (no hay arrastre)', false,
    (bool) preg_match("/campo === 'FECHA_ARR'.{0,200}recalcularSiCambio/s", $crono));

$revertir = codigoSinComentarios($base . '/controller/revertirFechaAuto.php');
chequear('volver a auto: sólo la nacionalización', true,
    strpos($revertir, "if (\$tipo === 'NACIONALIZACION')") !== false);
chequear('volver a auto: recalcula con el estado previo', true,
    strpos($revertir, '->recalcularSiCambio($id, $antesEstimacion)') !== false);

seccion('qué dispara y qué no');

$siCambio = cuerpoDe($servicio, 'cambioDisparaRecalculo');
chequear('mira el FOB', true, strpos($siCambio, 'VALOR_FOB_DOLAR') !== false);
chequear('mira la fecha de nacionalización', true, strpos($siCambio, 'FECHA_DESP_ADU') !== false);
chequear('NO mira el despachante', false, strpos($siCambio, 'DESPACHANTE') !== false);

$recalc = cuerpoDe($servicio, 'recalcularEstimacion');
chequear('no recalcula si hay costos reales', true, strpos($recalc, "'TIENE_COSTOS'") !== false);
chequear('no crea una estimación que no existe', true, strpos($recalc, "'SIN_ESTIMACION'") !== false);
chequear('no graba si no hay diferencias', true, strpos($recalc, "'SIN_DIFERENCIAS'") !== false);
chequear('el UPDATE no toca CONFIRMADO', false,
    (bool) preg_match('/UPDATE RO_T_IMPORTACIONES_ESTIMACION_DETALLE\s+SET[^"]*CONFIRMADO/s', $recalc));
chequear('y sí FECHA_MOD', true, strpos($recalc, 'FECHA_MOD = GETDATE()') !== false);

seccion('el endpoint del cashflow');

$endpoint = codigoSinComentarios($base . '/controller/recalcularEstimacion.php');
chequear('valida el entorno contra la lista cerrada', true,
    strpos($endpoint, "in_array(\$entorno, ['central', 'uy'], true)") !== false);
chequear('construye con el entorno explícito, no el de la sesión', true,
    strpos($endpoint, 'new EstimacionCostos($entorno)') !== false);
chequear('no abre la sesión', false, strpos($endpoint, 'session_start') !== false);
chequear('valida que el ID sea numérico', true, strpos($endpoint, 'ctype_digit($id)') !== false);
chequear('y que exista', true, strpos($endpoint, '->obtenerDespacho(intval($id))') !== false);
chequear('sólo recalcula: no llama a ninguna otra escritura', 0,
    preg_match_all('/->(guardarEstimacion|confirmarEstimacion|generarEstimacionAlta|eliminar\w*)\(/', $endpoint));

seccion('el listado de Gestión ya no usa el corte viejo');

$gestion = cuerpoDe($servicio, 'listarDespachosTodosConPadre');
chequear('no hay LEFT JOIN al detalle', false, stripos($gestion, 'LEFT JOIN RO_T_IMPORTACIONES_DETALLE') !== false);
chequear('usa la regla de VisibilidadContenedor', true,
    strpos($gestion, 'VisibilidadContenedor::sqlVisibleEnGestion(') !== false
    && strpos($gestion, 'VisibilidadContenedor::sqlTieneCostos(') !== false);
chequear('devuelve TIENE_COSTOS para la etiqueta', true, strpos($gestion, 'AS TIENE_COSTOS') !== false);

$pci = cuerpoDe($servicio, 'listarDespachosConEstado');
chequear('PCI no cambia su criterio', true,
    strpos($pci, 'F.ID_MG IS NULL') !== false && strpos($pci, 'DATEADD(MONTH, -6, GETDATE())') !== false);

$js = codigoSinComentarios($base . '/js/gestionDespachos.js');
chequear('la grilla dibuja la etiqueta Costos cargados', true,
    strpos($js, 'despacho.TIENE_COSTOS') !== false && strpos($js, 'Costos cargados') !== false);

seccion('el filtro por fecha de carga');

chequear('arranca en los últimos 360 días', true,
    strpos($js, 'const DIAS_FECHA_CARGA_DEFAULT = 360;') !== false);
chequear('filtra por FECHA_MOV, la fecha cruda de la fila', true,
    strpos($js, 'data-fecha-mov="${despacho.FECHA_MOV') !== false
    && strpos($js, 'dentroDeFechaCarga(tr ? tr.dataset.fechaMov') !== false);
chequear('la búsqueda sólo aplica a la tabla de despachos', true,
    strpos($js, "settings.nTable.id !== 'tablaDespachos'") !== false);
chequear('dice cuántos deja afuera', true, strpos($js, 'fuera del rango de fechas') !== false);
chequear('la pantalla tiene los dos campos y los dos botones', 4,
    preg_match_all('/id="(fechaCargaDesde|fechaCargaHasta|btnFechaCargaDefault|btnFechaCargaTodas)"/',
        file_get_contents($base . '/tabs/gestionDespachos.php')));
chequear('el servidor no filtra por esa fecha: la regla sigue siendo la de VisibilidadContenedor', false,
    stripos($gestion, '360') !== false);

seccion('el borrado');

$orden = codigoSinComentarios($base . '/class/Orden.php');
$borrar = cuerpoDe($orden, 'eliminarDespacho');
chequear('va en una transacción', true,
    strpos($borrar, 'sqlsrv_begin_transaction') !== false && strpos($borrar, 'sqlsrv_rollback') !== false
    && strpos($borrar, 'sqlsrv_commit') !== false);
chequear('borra el historial de fechas', true, strpos($borrar, 'self::TABLA_HISTORIAL') !== false);
chequear('borra los pagos explícitamente', true, strpos($borrar, 'self::TABLA_PAGOS') !== false);
chequear('rechaza una principal con hijas', true, strpos($borrar, "\$info['bloqueado']") !== false);
chequear('el encabezado es lo último que se borra', true,
    strrpos($borrar, 'DELETE FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE') > strrpos($borrar, 'DELETE FROM RO_T_IMPORTACIONES_DETALLE'));
chequear('el aviso previo lo informa el servidor', true,
    strpos($js, 'consultarEliminacionDespacho.php') !== false);

seccion('la cuenta del JS no se tocó');

$jsEstimacion = codigoSinComentarios($base . '/js/editar-estimacion.js');
chequear('la condición de esUruguay sigue siendo la de siempre', 2,
    substr_count($jsEstimacion, "\$('#entorno').val() === 'uy' || (conceptos.length > 0 && conceptos.some(c => c.ID_CE > 13))"));
chequear('getConceptoParam2() sigue con su default', true,
    strpos($jsEstimacion, 'return param2 ? parseFloat(param2) : 1;') !== false);
chequear('y el JS nombra a su copia del servidor', true,
    strpos(file_get_contents($base . '/js/editar-estimacion.js'), 'class/CalculoEstimacion.php') !== false);
