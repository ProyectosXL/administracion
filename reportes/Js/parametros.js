/**
 * parametros.js
 * Pestaña Parámetros: la grilla, la edición con confirmación y el historial.
 *
 * LA VALIDACIÓN ES DEL SERVIDOR (Parametros::validarValor). El JS no repite el
 * rango: manda lo que se escribió y muestra el mensaje que vuelve. Así la regla
 * vive en un solo lugar y el mensaje que ve el usuario es el de la regla.
 */
(function ($) {
    'use strict';

    var raiz = null;
    var parametros = [];

    function cargar() {
        var $tbody = $('#raTablaParametros tbody');

        Reportes.post('Parametros', 'listar').then(function (r) {
            parametros = r.parametros || [];
            dibujar();
        }).catch(function (err) {
            $tbody.html('<tr><td colspan="4" class="text-danger small">' + Reportes.esc(err.message) + '</td></tr>');
        });
    }

    function dibujar() {
        var $tbody = $('#raTablaParametros tbody');

        if (!parametros.length) {
            $tbody.html('<tr><td colspan="4" class="text-muted small">No hay parámetros cargados. '
                + 'Verificá que se haya corrido sql/reportes_parametros.sql en la base central.</td></tr>');
            return;
        }

        var html = parametros.map(function (p, i) {
            var editar = p.editable
                ? Permisos.siEdita(raiz, '<button type="button" class="btn btn-sm btn-ra ra-editar" data-i="' + i + '">'
                    + '<i class="bi bi-pencil"></i> Modificar</button>')
                : '';

            return '<tr>'
                + '<td><div class="fw-semibold">' + Reportes.esc(p.nombre) + '</div>'
                + '<div class="small text-muted">' + Reportes.esc(p.descripcion || '') + '</div>'
                + '<div class="small text-muted"><i class="bi bi-info-circle"></i> ' + Reportes.esc(p.regla) + '</div></td>'
                + '<td class="num fs-5 fw-semibold">' + Reportes.esc(p.valorTexto) + '</td>'
                + '<td class="small">' + Reportes.esc(p.usuario || '—') + '<br><span class="text-muted">'
                + Reportes.esc(p.fecha || '') + (p.modificado ? '' : ' (alta)') + '</span></td>'
                + '<td class="text-end text-nowrap">'
                + '<button type="button" class="btn btn-sm btn-outline-secondary ra-historial me-1" data-i="' + i + '">'
                + '<i class="bi bi-clock-history"></i> Historial</button>'
                + editar
                + '</td></tr>';
        }).join('');

        $tbody.html(html);
    }

    function editar(p) {
        Swal.fire({
            title: p.nombre,
            html: '<div class="small text-muted mb-2">' + Reportes.esc(p.regla) + '</div>',
            input: 'text',
            inputValue: p.valorTexto,
            inputAttributes: { inputmode: 'decimal', autocomplete: 'off' },
            showCancelButton: true,
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#7066e0',
            inputValidator: function (v) {
                return String(v || '').trim() === '' ? 'Ingresá un valor.' : undefined;
            }
        }).then(function (r) {
            if (!r.isConfirmed) {
                return;
            }

            var nuevo = String(r.value).trim();

            return Swal.fire({
                icon: 'question',
                title: '¿Guardar el cambio?',
                html: Reportes.esc(p.nombre) + ':<br><strong>' + Reportes.esc(p.valorTexto) + '</strong> → <strong>'
                    + Reportes.esc(nuevo) + '</strong><br><span class="small text-muted">Queda registrado con tu usuario '
                    + 'y la fecha, y se aplica en la próxima consulta.</span>',
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#7066e0'
            }).then(function (c) {
                if (c.isConfirmed) {
                    guardar(p, nuevo);
                }
            });
        });
    }

    function guardar(p, valor) {
        Reportes.post('Parametros', 'guardar', { clave: p.clave, valor: valor }).then(function (r) {
            Swal.fire({ icon: r.cambio ? 'success' : 'info', title: r.cambio ? 'Guardado' : 'Sin cambios',
                text: r.message, confirmButtonColor: '#7066e0' });
            cargar();
        }).catch(function (err) {
            Reportes.error('No se guardó', err);
        });
    }

    function historial(p) {
        Reportes.post('Parametros', 'historial', { clave: p.clave }).then(function (r) {
            var filas = r.historial || [];
            var cuerpo = filas.length
                ? filas.map(function (h) {
                    return '<tr><td>' + Reportes.esc(h.fecha) + '</td><td>' + Reportes.esc(h.usuario) + '</td>'
                        + '<td class="num">' + Reportes.esc(h.anterior === null ? '—' : h.anterior) + '</td>'
                        + '<td class="num fw-semibold">' + Reportes.esc(h.nuevo) + '</td></tr>';
                }).join('')
                : '<tr><td colspan="4" class="text-muted">Todavía no se modificó: conserva el valor del alta.</td></tr>';

            Swal.fire({
                title: 'Historial',
                html: '<div class="small text-muted mb-2">' + Reportes.esc(p.nombre) + '</div>'
                    + '<table class="table table-sm ra-tabla text-start"><thead><tr><th>Fecha</th><th>Usuario</th>'
                    + '<th class="num">Anterior</th><th class="num">Nuevo</th></tr></thead><tbody>' + cuerpo
                    + '</tbody></table>',
                width: 640,
                confirmButtonText: 'Cerrar',
                confirmButtonColor: '#7066e0'
            });
        }).catch(function (err) {
            Reportes.error('No se pudo leer el historial', err);
        });
    }

    Reportes.registrar('parametros', {
        init: function (el) {
            raiz = el;

            $(raiz).on('click', '.ra-editar', function () {
                editar(parametros[$(this).data('i')]);
            }).on('click', '.ra-historial', function () {
                historial(parametros[$(this).data('i')]);
            });

            cargar();
        }
    });
})(jQuery);
