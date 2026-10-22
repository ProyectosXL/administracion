/**
 * remitos.js
 * Pestaña Remitos pendientes de facturar: filtros, resumen en pantalla y
 * descarga del Excel.
 */
(function ($) {
    'use strict';

    var raiz = null;

    Reportes.registrar('remitos_pendientes', {
        init: function (el) {
            raiz = el;
        }
    });
})(jQuery);
