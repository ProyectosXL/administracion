/**
 * valorizacion.js
 * Pestaña Valorización de stock: filtros, resumen en pantalla y descarga del
 * Excel.
 *
 * CAMBIAR UN FILTRO NO CONSULTA. Una consulta en modo actual tarda unos
 * segundos y recorre el cierre entero: se dispara con el botón y nada más.
 *
 * LA DESCARGA USA LOS FILTROS DE LA ÚLTIMA CONSULTA, los que devolvió el
 * servidor ya validados, y no los que estén en pantalla en ese momento: el
 * Excel tiene que ser el detalle de lo que se está viendo. Si se cambió algún
 * filtro sin consultar, se avisa.
 *
 * EL EXCEL SE ARMA ACÁ, con SheetJS, desde el JSON de
 * ValorizacionController?action=detalle. El SQL no viaja ni se ve.
 */
(function ($) {
    'use strict';

    var raiz = null;
    var sucursales = [];     // [{sector, sucursal}] de la fecha elegida
    var ultima = null;       // la respuesta de la última consulta

    var COLOR = '#7066e0';

    /* ---------------------------------------------------------------- */
    /* Filtros                                                           */
    /* ---------------------------------------------------------------- */

    function filtrosEnPantalla() {
        return {
            fecha: $('#raValFecha').val(),
            modo: $('input[name="raValModo"]:checked').val(),
            sector: $('#raValSector').val(),
            sucursales: sucursalesElegidas(),
            articulo: $.trim($('#raValArticulo').val())
        };
    }

    function sucursalesElegidas() {
        return $('#raValSucLista input:checked').map(function () {
            return this.value;
        }).get();
    }

    function cargarFechas() {
        Reportes.post('Valorizacion', 'fechas').then(function (r) {
            var $sel = $('#raValFecha').empty();

            if (!r.fechas.length) {
                $sel.append('<option value="">Sin cierres</option>');
                return;
            }

            // De la más reciente a la más vieja, la última preseleccionada
            r.fechas.forEach(function (f, i) {
                $sel.append($('<option>').val(f).text(f).prop('selected', i === 0));
            });

            $sel.prop('disabled', false);
            cargarSucursales();
        }).catch(function (err) {
            $('#raValFecha').html('<option value="">Error</option>');
            Reportes.error('No se pudieron leer las fechas de cierre', err);
        });
    }

    function cargarSucursales() {
        var fecha = $('#raValFecha').val();
        var antes = sucursalesElegidas();

        $('#raValConsultar').prop('disabled', true);

        Reportes.post('Valorizacion', 'sucursales', { fecha: fecha }).then(function (r) {
            sucursales = r.sucursales || [];
            dibujarSucursales(antes);
            $('#raValConsultar').prop('disabled', false);
        }).catch(function (err) {
            Reportes.error('No se pudieron leer las sucursales', err);
        });
    }

    /**
     * La lista de sucursales, filtrada por el sector. Conserva tildadas las
     * que siguen siendo válidas: cambiar de sector no tiene por qué perder la
     * elección.
     */
    function dibujarSucursales(tildadas) {
        var sector = $('#raValSector').val();
        tildadas = tildadas || sucursalesElegidas();

        var html = sucursales.filter(function (s) {
            return sector === '' || s.sector === sector;
        }).map(function (s, i) {
            var id = 'raValSuc' + i;

            return '<div class="form-check">'
                + '<input class="form-check-input" type="checkbox" id="' + id + '" value="' + Reportes.esc(s.sucursal) + '"'
                + (tildadas.indexOf(s.sucursal) >= 0 ? ' checked' : '') + '>'
                + '<label class="form-check-label" for="' + id + '">' + Reportes.esc(s.sucursal)
                + (sector === '' ? ' <span class="text-muted small">' + Reportes.esc(s.sector.toLowerCase()) + '</span>' : '')
                + '</label></div>';
        }).join('');

        $('#raValSucLista').html(html || '<div class="small text-muted">Sin sucursales para este sector.</div>');
        rotularSucursales();
    }

    function rotularSucursales() {
        var n = sucursalesElegidas();

        $('#raValSucursalBtn').text(n.length === 0 ? 'Todas' : (n.length === 1 ? n[0] : n.length + ' sucursales'));
    }

    /** Avisa que lo que se ve ya no corresponde a los filtros de la pantalla */
    function marcarCambio() {
        if (ultima) {
            $('#raValCambio').prop('hidden', false);
        }
    }

    /* ---------------------------------------------------------------- */
    /* Consulta y resumen                                                */
    /* ---------------------------------------------------------------- */

    function consultar() {
        var $btn = $('#raValConsultar');
        var html = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Consultando…');

        Reportes.post('Valorizacion', 'consultar', filtrosEnPantalla()).then(function (r) {
            ultima = r;
            $('#raValCambio').prop('hidden', true);
            dibujar(r);
        }).catch(function (err) {
            Reportes.error('No se pudo consultar', err);
        }).then(function () {
            $btn.prop('disabled', false).html(html);
        });
    }

    function unidades(v) {
        return Reportes.numero(v, Number.isInteger(v) ? 0 : 2);
    }

    function dibujar(r) {
        var hay = r.kpis.filas > 0;

        $('#raValInicio').prop('hidden', true);
        $('#raValVacio').prop('hidden', hay);
        $('#raValResultado').prop('hidden', !hay);
        $('#raValExcel').prop('disabled', !hay);

        if (!hay) {
            return;
        }

        $('#raValLeyenda').html('<strong>' + Reportes.esc(r.leyenda) + '</strong>'
            + (r.factorTexto ? ' · factor de nacionalización estimado ' + Reportes.esc(r.factorTexto) : '')
            + '<br>' + r.filtrosTexto.slice(2).map(Reportes.esc).join(' · '));

        var kpis = [
            kpi('Stock total (unidades)', unidades(r.kpis.stock)),
            kpi('Valorización total', '$ ' + Reportes.numero(r.kpis.valorizacion)),
            kpi('Cantidad de artículos', Reportes.numero(r.kpis.articulos, 0),
                Reportes.numero(r.kpis.filas, 0) + ' filas por sucursal')
        ];

        if (r.filtros.modo === 'actual') {
            kpis.push(kpi('Sin costo', Reportes.numero(r.kpis.sinCosto, 0),
                'filas sin costo de última compra: valorizadas en 0', r.kpis.sinCosto > 0));
            kpis.push(kpi('Nacionalización estimada', Reportes.numero(r.kpis.nacEstimada, 0),
                'filas con el factor ' + r.factorTexto, r.kpis.nacEstimada > 0));
        }

        $('#raValKpis').html(kpis.join(''));
        $('#raValResumen tbody').html(filasResumen(r.sectores, r.total));
    }

    function kpi(etiqueta, valor, nota, aviso) {
        return '<div class="ra-kpi' + (aviso ? ' aviso' : '') + '">'
            + '<div class="ra-kpi-label">' + Reportes.esc(etiqueta) + '</div>'
            + '<div class="ra-kpi-valor">' + Reportes.esc(valor) + '</div>'
            + (nota ? '<div class="ra-kpi-nota">' + Reportes.esc(nota) + '</div>' : '')
            + '</div>';
    }

    function filasResumen(sectores, total) {
        var html = '';

        sectores.forEach(function (s) {
            s.sucursales.forEach(function (x) {
                html += '<tr><td>' + Reportes.esc(s.sector) + '</td><td>' + Reportes.esc(x.sucursal) + '</td>'
                    + '<td class="num">' + unidades(x.stock) + '</td>'
                    + '<td class="num">' + Reportes.numero(x.valorizacion) + '</td></tr>';
            });

            html += '<tr class="subtotal"><td colspan="2">Subtotal ' + Reportes.esc(s.sector) + '</td>'
                + '<td class="num">' + unidades(s.stock) + '</td>'
                + '<td class="num">' + Reportes.numero(s.valorizacion) + '</td></tr>';
        });

        return html + '<tr class="total"><td colspan="2">Total general</td>'
            + '<td class="num">' + unidades(total.stock) + '</td>'
            + '<td class="num">' + Reportes.numero(total.valorizacion) + '</td></tr>';
    }

    /* ---------------------------------------------------------------- */
    /* Excel                                                             */
    /* ---------------------------------------------------------------- */

    function descargar() {
        if (!ultima) {
            return;
        }

        if (typeof XLSX === 'undefined') {
            Reportes.error('No se puede generar el Excel', 'No se cargó la librería SheetJS (xlsx).');
            return;
        }

        Swal.fire({
            title: 'Generando el Excel…',
            text: 'Se trae el detalle por artículo con los filtros de la consulta.',
            allowOutsideClick: false,
            didOpen: function () { Swal.showLoading(); }
        });

        Reportes.post('Valorizacion', 'detalle', ultima.filtros).then(function (r) {
            var nombre = generarExcel(r);

            Swal.fire({ icon: 'success', title: 'Excel generado', text: nombre + ' (' + Reportes.numero(r.filas.length, 0)
                + ' filas)', confirmButtonColor: COLOR });
        }).catch(function (err) {
            Reportes.error('No se pudo generar el Excel', err);
        });
    }

    /** 'AAAA-MM-DD' al número de serie de Excel, sin pasar por la zona horaria */
    function serieExcel(iso) {
        var p = iso.split('-');

        return (Date.UTC(+p[0], +p[1] - 1, +p[2]) - Date.UTC(1899, 11, 30)) / 86400000;
    }

    /** 'AAAA-MM-DD HH:MM:SS' a 'DD/MM/AAAA HH:MM' */
    function fechaHora(s) {
        var m = String(s || '').match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/);

        return m ? m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] + ':' + m[5] : s;
    }

    /** Las filas de encabezado de las dos hojas */
    function encabezado(r) {
        var filas = [['Valorización de stock — LAKERS SA'], [r.leyenda]];

        r.filtrosTexto.forEach(function (t) {
            filas.push([t]);
        });

        if (r.filtros.modo === 'actual') {
            filas.push(['Factor de nacionalización estimado: ' + r.factorTexto]);
        }

        filas.push(['Generado por ' + r.usuario + ' el ' + fechaHora(r.generado)]);
        filas.push([]);

        return filas;
    }

    /** Formato numérico a un rango de celdas de una columna */
    function formatear(ws, col, desde, hasta, formato) {
        for (var f = desde; f <= hasta; f++) {
            var celda = ws[XLSX.utils.encode_cell({ r: f, c: col })];

            if (celda && celda.t === 'n') {
                celda.z = typeof formato === 'function' ? formato(celda.v) : formato;
            }
        }
    }

    function formatoStock(v) {
        return Number.isInteger(v) ? '#,##0' : '#,##0.00';
    }

    function hojaDetalle(r) {
        var aoa = encabezado(r);
        var fila0 = aoa.length;
        var c = {};
        var serie = serieExcel(r.filtros.fecha);

        r.columnas.forEach(function (nombre, i) { c[nombre] = i; });

        aoa.push(['Fecha de cierre', 'Sector', 'Sucursal', 'Artículo', 'Descripción', 'Stock', 'Costo unitario',
            'Valorización', 'Origen del costo']);

        r.filas.forEach(function (f) {
            aoa.push([serie, f[c.sector], f[c.sucursal], f[c.articulo], f[c.descripcion], f[c.stock],
                f[c.costoUnitario], f[c.valorizacion], f[c.origen]]);
        });

        var ws = XLSX.utils.aoa_to_sheet(aoa);
        var ultimaFila = aoa.length - 1;

        formatear(ws, 0, fila0 + 1, ultimaFila, 'dd/mm/yyyy');
        formatear(ws, 5, fila0 + 1, ultimaFila, formatoStock);
        formatear(ws, 6, fila0 + 1, ultimaFila, '#,##0.00');
        formatear(ws, 7, fila0 + 1, ultimaFila, '#,##0.00');

        ws['!autofilter'] = { ref: XLSX.utils.encode_range({ s: { r: fila0, c: 0 }, e: { r: ultimaFila, c: 8 } }) };
        ws['!cols'] = [{ wch: 13 }, { wch: 10 }, { wch: 24 }, { wch: 18 }, { wch: 40 }, { wch: 10 }, { wch: 15 },
            { wch: 18 }, { wch: 40 }];

        return ws;
    }

    function hojaResumen(r) {
        var aoa = encabezado(r);
        var fila0 = aoa.length;

        aoa.push(['Sector', 'Sucursal', 'Stock', 'Valorización']);

        r.resumen.sectores.forEach(function (s) {
            s.sucursales.forEach(function (x) {
                aoa.push([s.sector, x.sucursal, x.stock, x.valorizacion]);
            });
            aoa.push(['Subtotal ' + s.sector, '', s.stock, s.valorizacion]);
        });

        aoa.push(['Total general', '', r.resumen.total.stock, r.resumen.total.valorizacion]);

        var ws = XLSX.utils.aoa_to_sheet(aoa);

        formatear(ws, 2, fila0 + 1, aoa.length - 1, formatoStock);
        formatear(ws, 3, fila0 + 1, aoa.length - 1, '#,##0.00');
        ws['!cols'] = [{ wch: 20 }, { wch: 26 }, { wch: 12 }, { wch: 20 }];

        return ws;
    }

    /** Arma y descarga el libro. Devuelve el nombre del archivo. */
    function generarExcel(r) {
        var wb = XLSX.utils.book_new();

        XLSX.utils.book_append_sheet(wb, hojaDetalle(r), 'Detalle');
        XLSX.utils.book_append_sheet(wb, hojaResumen(r), 'Resumen');

        var nombre = 'Valorizacion_Stock_' + r.filtros.fecha.replace(/-/g, '') + '_'
            + (r.filtros.modo === 'actual' ? 'Actual' : 'Historico') + '.xlsx';

        XLSX.writeFile(wb, nombre, { compression: true });

        return nombre;
    }

    /* ---------------------------------------------------------------- */

    Reportes.registrar('valorizacion_stock', {
        init: function (el) {
            raiz = el;

            $(raiz)
                .on('change', '#raValFecha', function () { cargarSucursales(); marcarCambio(); })
                .on('change', '#raValSector', function () { dibujarSucursales(); marcarCambio(); })
                .on('change', 'input[name="raValModo"]', marcarCambio)
                .on('change', '#raValSucLista input', function () { rotularSucursales(); marcarCambio(); })
                .on('input', '#raValArticulo', marcarCambio)
                .on('keydown', '#raValArticulo', function (e) {
                    if (e.key === 'Enter' && !$('#raValConsultar').prop('disabled')) {
                        consultar();
                    }
                })
                .on('click', '#raValSucTodas', function () {
                    $('#raValSucLista input').prop('checked', true);
                    rotularSucursales();
                    marcarCambio();
                })
                .on('click', '#raValSucNinguna', function () {
                    $('#raValSucLista input').prop('checked', false);
                    rotularSucursales();
                    marcarCambio();
                })
                .on('click', '#raValConsultar', consultar)
                .on('click', '#raValExcel', descargar);

            cargarFechas();
        }
    });
})(jQuery);
