/**
 * reportes.js
 * Lo común del módulo: las solapas, la carga de cada pestaña por AJAX y el
 * pedido a los controllers.
 *
 * CADA PESTAÑA SE REGISTRA SOLA. Su JS llama a Reportes.registrar('tab', {init})
 * y este archivo no sabe qué pestañas existen: las solapas las dibuja el PHP
 * desde Class/Menu.php. Así sumar un reporte no toca este archivo.
 *
 * El HTML de una pestaña se pide UNA vez, la primera que se abre, y queda en su
 * panel: volver a una pestaña no pierde los filtros ni lo que ya se consultó.
 */
(function ($) {
    'use strict';

    var tabs = {};
    var cargadas = {};

    var Reportes = {};

    /**
     * Registra una pestaña. init(raiz) se llama una vez, cuando su HTML llega.
     *
     * @param {string} tab
     * @param {{init: function(Element)}} def
     */
    Reportes.registrar = function (tab, def) {
        tabs[tab] = def;
    };

    /** Abre una pestaña: muestra su panel y, la primera vez, pide su HTML */
    Reportes.abrir = function (tab) {
        $('.ra-tab').removeClass('active').filter('[data-tab="' + tab + '"]').addClass('active');
        $('.ra-panel').attr('hidden', true);

        var $panel = $('#raPanel-' + tab).removeAttr('hidden');

        if (cargadas[tab]) {
            return;
        }

        cargadas[tab] = true;
        $panel.html('<div class="ra-cargando"><div class="spinner-border spinner-border-sm"></div> Cargando…</div>');

        $.ajax({ url: 'Controller/TabController.php', method: 'POST', data: { tab: tab }, dataType: 'html' })
            .done(function (html) {
                $panel.html(html);

                if (tabs[tab] && typeof tabs[tab].init === 'function') {
                    tabs[tab].init($panel.children().get(0));
                }
            })
            .fail(function (xhr) {
                // El 401 y el 403 traen su propio HTML explicando qué pasa
                $panel.html(xhr.responseText || '<div class="ra-estado"><h3>No se pudo cargar la pestaña</h3></div>');
                cargadas[tab] = false;
            });
    };

    /**
     * POST a un controller del módulo. Resuelve con la respuesta si
     * success = true y rechaza con un mensaje en cualquier otro caso: un
     * error de validación, un 401, un 403 o una caída del servidor.
     *
     * @param {string} controller 'Parametros', 'Valorizacion'
     * @param {string} action
     * @param {object} [data]
     * @returns {Promise<object>}
     */
    Reportes.post = function (controller, action, data) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: 'Controller/' + controller + 'Controller.php?action=' + encodeURIComponent(action),
                method: 'POST',
                data: data || {},
                dataType: 'json'
            }).done(function (r) {
                if (r && r.success) {
                    resolve(r);
                } else {
                    reject(new Error((r && r.message) || 'El servidor no devolvió una respuesta válida.'));
                }
            }).fail(function (xhr) {
                var r = xhr.responseJSON;
                var msg = (r && r.message) || ('Error de comunicación con el servidor (' + xhr.status + ').');

                reject(new Error(msg));
            });
        });
    };

    /** Muestra un error con SweetAlert2 */
    Reportes.error = function (titulo, err) {
        Swal.fire({
            icon: 'error',
            title: titulo,
            text: (err && err.message) ? err.message : String(err || ''),
            confirmButtonColor: '#7066e0'
        });
    };

    /**
     * Un número con formato argentino: punto de miles y coma decimal.
     *
     * @param {number} v
     * @param {number} [dec=2]
     * @returns {string}
     */
    Reportes.numero = function (v, dec) {
        dec = (dec === undefined) ? 2 : dec;

        return Number(v || 0).toLocaleString('es-AR', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    };

    /** Escapa texto para meterlo en HTML */
    Reportes.esc = function (s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    };

    window.Reportes = Reportes;

    $(function () {
        $('#raTabs').on('click', '.ra-tab', function () {
            Reportes.abrir($(this).data('tab'));
        });

        var $primera = $('.ra-tab.active').first();

        if ($primera.length) {
            Reportes.abrir($primera.data('tab'));
        }
    });
})(jQuery);
