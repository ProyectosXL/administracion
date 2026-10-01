<?php
/**
 * La cuenta del servidor contra las estimaciones que guardó la pantalla.
 * SÓLO LECTURA: reporta, no corrige.
 *
 * QUÉ SE COMPARA. Para cada estimación guardada se rehace la cuenta con
 * CalculoEstimacion usando LOS PARÁMETROS Y LOS IMPORTES EDITABLES QUE TIENE
 * GUARDADOS -no la vigencia de hoy-, o sea lo que la pantalla tenía delante
 * cuando alguien apretó Guardar. Si nadie pisó un importe calculado, las dos
 * cuentas tienen que dar lo mismo al centavo.
 *
 * POR QUÉ SE REPORTA Y NO FALLA. La base no distingue un importe que escribió
 * una persona de uno que calculó el sistema -IMPORTE_MANUAL existe en central
 * pero nadie lo escribe y vale 0 en todas las filas-, así que una diferencia
 * puede ser un override legítimo. Lo que sí sería un problema es que FALLARAN
 * TODAS, y eso es lo único que se chequea: si la mayoría no coincide, la
 * copia del servidor se separó del JS.
 *
 * Medido el 01/10/2026: 102 de 112 coinciden en central; las 10 que no tienen
 * overrides visibles -Seguro cargado en 400 o 500, IVA Adicional o IIGG en
 * cero- y su efecto en cascada. uy tiene una sola estimación, y coincide.
 */

require_once __DIR__ . '/../class/estimacionCostos.php';

seccion('contra la base: rehacer las estimaciones guardadas');

if (!Pruebas::hayBase()) {
    Pruebas::saltear('no hay conexión a la base');
} else {
    foreach (['central', 'uy'] as $entorno) {
        $ec = new EstimacionCostos($entorno);
        $conn = (new Conexion())->conectar($entorno);

        $stmt = sqlsrv_query($conn, 'SELECT DISTINCT ID_MG FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE');
        $padron = $ec->obtenerConceptos(null);

        $coinciden = 0;
        $difieren = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $idMg = intval($row['ID_MG']);
            $despacho = $ec->obtenerDespacho($idMg);
            $guardada = $ec->obtenerEstimacion($idMg);
            if (!$despacho || !$guardada) {
                continue;
            }

            $porId = [];
            foreach ($guardada as $g) {
                $porId[intval($g['ID_CE'])] = $g;
            }

            // Los parámetros con los que se guardó, no los de hoy.
            $conceptos = $padron;
            foreach ($conceptos as &$c) {
                if (isset($porId[intval($c['ID_CE'])])) {
                    $c['VALOR_DEFAULT_1'] = $porId[intval($c['ID_CE'])]['VALOR_DEFAULT_1'];
                    $c['VALOR_DEFAULT_2'] = $porId[intval($c['ID_CE'])]['VALOR_DEFAULT_2'];
                }
            }
            unset($c);

            $editables = [];
            foreach (CalculoEstimacion::idsEditables($conceptos, $entorno) as $id) {
                if (isset($porId[$id]) && $porId[$id]['IMPORTE'] !== null) {
                    $editables[$id] = $porId[$id]['IMPORTE'];
                }
            }

            $r = CalculoEstimacion::calcular([
                'entorno' => $entorno, 'fob' => $despacho['VALOR_FOB_DOLAR'],
                'conceptos' => $conceptos, 'editables' => $editables,
            ]);

            $distintos = [];
            foreach ($r['filas'] as $f) {
                if (!isset($porId[$f['id_ce']])) {
                    continue;
                }
                $guardado = floatval($porId[$f['id_ce']]['IMPORTE']);
                if (abs($f['importe'] - $guardado) > 0.005 + 1e-9) {
                    $distintos[] = $porId[$f['id_ce']]['CONCEPTO'] . ' ' . number_format($guardado, 2, ',', '.')
                        . ' guardado / ' . number_format($f['importe'], 2, ',', '.') . ' calculado';
                }
            }

            if ($distintos) {
                $difieren[$idMg] = $distintos;
            } else {
                $coinciden++;
            }
        }

        $total = $coinciden + count($difieren);
        Pruebas::informar($entorno . ': ' . $coinciden . ' de ' . $total
            . ' estimaciones coinciden al centavo con la cuenta del servidor');

        foreach ($difieren as $idMg => $distintos) {
            Pruebas::informar('  #' . $idMg . ': ' . implode(' · ', array_slice($distintos, 0, 3))
                . (count($distintos) > 3 ? ' · y ' . (count($distintos) - 3) . ' más' : ''));
        }

        if ($total > 0) {
            chequear($entorno . ': la mayoría coincide (si no, la copia se separó del JS)', true,
                $coinciden > $total / 2);
        }
    }
}
