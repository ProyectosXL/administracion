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
 */
require_once __DIR__ . '/AuthReportes.php';

class Menu {

    private static $pestanas = [
        ['tab' => 'valorizacion_stock', 'nombre' => 'Valorización de stock', 'icono' => 'bi-box-seam',
         'js' => 'valorizacion.js'],
        ['tab' => 'parametros', 'nombre' => 'Parámetros', 'icono' => 'bi-sliders',
         'js' => 'parametros.js']
    ];

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
