<?php
/**
 * Menu
 * Las pestañas de Reportes de Administración, como dato.
 *
 * PARA AGREGAR UN REPORTE
 * -----------------------
 * Una entrada acá, su archivo en Tabs/ y su JS en Js/. index.php dibuja las
 * solapas desde esta lista y carga sólo los JS de las que el usuario ve;
 * TabController sirve sólo las que están acá, y cada una pide su propio
 * permiso: ra.tab.<tab>, que va en sql/reportes_permisos.sql (la prueba
 * tests/test_auth_reportes.php falla si falta).
 *
 * El orden de la lista es el de las solapas: la primera que el usuario puede
 * ver es la que se abre.
 *
 * 'js' son los archivos de Js/ que la pestaña carga, en orden: los compartidos
 * (como excel.js) van antes que el propio.
 *
 * 'libs' son las librerías externas que la pestaña necesita, además de las
 * comunes (jQuery, Bootstrap, SweetAlert2). index.php las carga una sola vez
 * aunque las pidan varias pestañas.
 */
require_once __DIR__ . '/AuthReportes.php';

class Menu {

    private static $pestanas = [
        ['tab' => 'valorizacion_stock', 'nombre' => 'Valorización de stock', 'icono' => 'bi-box-seam',
         'js' => ['excel.js', 'valorizacion.js'],
         // SheetJS, la misma versión que novedades y el Informe Económico
         'libs' => ['https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js']],
        ['tab' => 'remitos_pendientes', 'nombre' => 'Remitos pendientes de facturar', 'icono' => 'bi-receipt',
         'js' => ['remitos.js'], 'libs' => []],
        ['tab' => 'parametros', 'nombre' => 'Parámetros', 'icono' => 'bi-sliders',
         'js' => ['parametros.js'], 'libs' => []]
    ];

    /**
     * Las librerías externas de un juego de pestañas, sin repetir.
     *
     * @param array $pestanas
     * @return string[]
     */
    public static function libs(array $pestanas) {
        $libs = [];

        foreach ($pestanas as $p) {
            foreach ($p['libs'] ?? [] as $url) {
                $libs[$url] = true;
            }
        }

        return array_keys($libs);
    }

    /**
     * Los JS propios de un juego de pestañas, en orden y sin repetir: un
     * archivo compartido se carga una sola vez aunque lo pidan varias.
     *
     * @param array $pestanas
     * @return string[] Nombres de archivo de Js/
     */
    public static function scripts(array $pestanas) {
        $js = [];

        foreach ($pestanas as $p) {
            foreach ($p['js'] as $archivo) {
                $js[$archivo] = true;
            }
        }

        return array_keys($js);
    }

    /** Todas, sin filtrar: es un hecho del código (lo usan las pruebas) */
    public static function todas() {
        return self::$pestanas;
    }

    /** Las que puede ver el usuario de la sesión */
    public static function permitidas() {
        return array_values(array_filter(self::$pestanas, function ($p) {
            return AuthReportes::puede($p['tab']);
        }));
    }

    /** La definición de una pestaña, o null si no existe */
    public static function buscar($tab) {
        foreach (self::$pestanas as $p) {
            if ($p['tab'] === $tab) {
                return $p;
            }
        }

        return null;
    }
}
