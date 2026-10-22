/**
 * remitos.js
 * Pestaña Remitos pendientes de facturar: filtros, resumen en pantalla y
 * descarga del Excel.
 *
 * LOS RANGOS DEL PERÍODO LOS CALCULA EL SERVIDOR (RemitosController?action=
 * periodos), con su "hoy" de Argentina: la regla está en un solo lugar, con su
 * prueba. Elegir una opción completa Desde y Hasta; tocar una fecha a mano
 * pasa el selector a "Personalizado".
 *
 * CAMBIAR UN FILTRO NO CONSULTA, igual que en la valorización: una consulta
 * recorre un año de remitos y tarda unos segundos. Se dispara con el botón.
 *
 * La lista de clientes es la de los que tienen remitos en el período y el
 * tipo elegidos, y se vuelve a pedir cuando cambian. Ninguno tildado es todos.
 */
(function ($) {
    'use strict';

    var raiz = null;
    var hoy = null;
    var periodos = [];
    var clientes = [];       // [{cliente, razonSocial}] del período y tipo
    var tildados = [];       // códigos elegidos, sobreviven a recargar la lista
    var pedidoClientes = 0;  // descarta respuestas viejas si cambian rápido
    var ultima = null;       // la respuesta de la última consulta

    var PERSONALIZADO = 'personalizado';

    /* ---------------------------------------------------------------- */
    /* Filtros                                                           */
    /* ---------------------------------------------------------------- */

    function filtrosEnPantalla() {
        return {
            desde: $('#raRemDesde').val(),
            hasta: $('#raRemHasta').val(),
            tipo: $('#raRemTipo').val(),
            clientes: tildados.slice()
        };
    }

    function cargarPeriodos() {
        Reportes.post('Remitos', 'periodos').then(function (r) {
            hoy = r.hoy;
            periodos = r.periodos;

            var $sel = $('#raRemPeriodo').empty();

            periodos.forEach(function (p, i) {
                $sel.append($('<option>').val(p.opcion).text(p.nombre).prop('selected', i === 0));
            });

            // Un Hasta posterior a hoy no se puede elegir
            $('#raRemDesde, #raRemHasta').attr('max', hoy).prop('disabled', false);
            $sel.prop('disabled', false);
            aplicarPeriodo();
        }).catch(function (err) {
            $('#raRemPeriodo').html('<option value="">Error</option>');
            Reportes.error('No se pudieron leer los períodos', err);
        });
    }

    /** Completa Desde y Hasta con la opción elegida */
    function aplicarPeriodo() {
        var opcion = $('#raRemPeriodo').val();
        var p = periodos.filter(function (x) { return x.opcion === opcion; })[0];

        if (p && p.desde) {
            $('#raRemDesde').val(p.desde);
            $('#raRemHasta').val(p.hasta);
        }

        fechasCambiaron();
    }

    /**
     * Lo que el servidor rechazaría, dicho antes. El servidor valida igual: esto
     * es para no consultar en vano.
     */
    function errorDeFechas() {
        var d = $('#raRemDesde').val();
        var h = $('#raRemHasta').val();

        if (!d || !h) {
            return 'Elegí las fechas Desde y Hasta del período.';
        }

        if (h > hoy) {
            return 'La fecha Hasta no puede ser posterior a hoy.';
        }

        if (d > h) {
            return 'La fecha Desde no puede ser posterior a la fecha Hasta.';
        }

        return '';
    }

    function fechasCambiaron() {
        var error = errorDeFechas();

        $('#raRemError').text(error).prop('hidden', !error);
        $('#raRemHasta').toggleClass('is-invalid', !!error && $('#raRemHasta').val() > hoy);
        $('#raRemDesde').toggleClass('is-invalid', !!error && $('#raRemDesde').val() > $('#raRemHasta').val());
        $('#raRemConsultar').prop('disabled', !!error);

        if (!error) {
            cargarClientes();
        }
    }

    function cargarClientes() {
        var n = ++pedidoClientes;
        var f = filtrosEnPantalla();

        $('#raRemCliLista').html('<div class="small text-muted"><span class="spinner-border spinner-border-sm"></span> Cargando…</div>');

        Reportes.post('Remitos', 'clientes', { desde: f.desde, hasta: f.hasta, tipo: f.tipo }).then(function (r) {
            if (n !== pedidoClientes) {
                return;
            }

            clientes = r.clientes || [];

            // Los elegidos que ya no tienen remitos en el período se sueltan:
            // si no, la consulta los rechazaría
            var codigos = clientes.map(function (c) { return c.cliente; });

            tildados = tildados.filter(function (c) { return codigos.indexOf(c) >= 0; });
            dibujarClientes();
        }).catch(function (err) {
            if (n === pedidoClientes) {
                $('#raRemCliLista').html('<div class="small text-danger">' + Reportes.esc(err.message) + '</div>');
            }
        });
    }

    /** La lista, filtrada por el texto buscado. Los tildados van primero. */
    function dibujarClientes() {
        var texto = $.trim($('#raRemClienteBuscar').val()).toLowerCase();

        var visibles = clientes.filter(function (c) {
            return !texto || tildados.indexOf(c.cliente) >= 0
                || (c.cliente + ' ' + c.razonSocial).toLowerCase().indexOf(texto) >= 0;
        }).sort(function (a, b) {
            return (tildados.indexOf(b.cliente) >= 0) - (tildados.indexOf(a.cliente) >= 0)
                || a.cliente.localeCompare(b.cliente);
        });

        var html = visibles.map(function (c, i) {
            var id = 'raRemCli' + i;

            return '<div class="form-check">'
                + '<input class="form-check-input" type="checkbox" id="' + id + '" value="' + Reportes.esc(c.cliente) + '"'
                + (tildados.indexOf(c.cliente) >= 0 ? ' checked' : '') + '>'
                + '<label class="form-check-label" for="' + id + '"><strong>' + Reportes.esc(c.cliente) + '</strong> '
                + Reportes.esc(c.razonSocial) + '</label></div>';
        }).join('');

        $('#raRemCliLista').html(html || '<div class="small text-muted">'
            + (clientes.length ? 'Ningún cliente coincide con la búsqueda.' : 'Sin remitos en el período.') + '</div>');
        rotularClientes();
    }

    function rotularClientes() {
        var n = tildados.length;

        $('#raRemClienteBtn').text(n === 0 ? 'Todos' : (n === 1 ? tildados[0] : n + ' clientes'));
    }

    /** Avisa que lo que se ve ya no corresponde a los filtros de la pantalla */
    function marcarCambio() {
        if (ultima) {
            $('#raRemCambio').prop('hidden', false);
        }
    }

    /* ---------------------------------------------------------------- */
    /* Consulta y resumen                                                */
    /* ---------------------------------------------------------------- */

    function consultar() {
        var $btn = $('#raRemConsultar');
        var html = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Consultando…');

        Reportes.post('Remitos', 'consultar', filtrosEnPantalla()).then(function (r) {
            ultima = r;
            $('#raRemCambio').prop('hidden', true);
            dibujar(r);
        }).catch(function (err) {
            Reportes.error('No se pudo consultar', err);
        }).then(function () {
            $btn.prop('disabled', !!errorDeFechas()).html(html);
        });
    }

    /** Importes con miles y 2 decimales, como en el Excel */
    function importe(v) {
        return Reportes.numero(v, 2);
    }

    function cantidad(v) {
        return Reportes.numero(v, 0);
    }

    /** "1 remito", "3 remitos" */
    function remitos(n) {
        return cantidad(n) + (n === 1 ? ' remito' : ' remitos');
    }

    /** 'AAAA-MM-DD' a 'DD/MM/AAAA' */
    function fecha(iso) {
        var p = String(iso || '').split('-');

        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
    }

    function dibujar(r) {
        var k = r.kpis;
        var hay = k.pendientes > 0;

        $('#raRemInicio').prop('hidden', true);
        $('#raRemVacio').prop('hidden', hay);
        $('#raRemResultado').prop('hidden', !hay);
        $('#raRemExcel').prop('disabled', !hay);
        $('#raRemVacioTexto').text(r.leyenda + ': ninguno. ' + r.filtrosTexto.slice(2).join(' · '));

        if (!hay) {
            return;
        }

        $('#raRemLeyenda').html('<strong>' + Reportes.esc(r.leyenda) + '</strong>'
            + '<br>' + r.filtrosTexto.slice(2).map(Reportes.esc).join(' · '));

        var corte = fecha(r.filtros.hasta);

        $('#raRemKpis').html([
            kpi('Pendientes al ' + corte, remitos(k.pendientes), '$ ' + importe(k.importePendiente) + ' neto'),
            kpi('Ya facturados después', remitos(k.facturadosDespues),
                '$ ' + importe(k.importeFacturadoDespues) + ' · de los pendientes al ' + corte),
            kpi('Siguen sin factura hoy', remitos(k.sinFactura),
                '$ ' + importe(k.importeSinFactura), k.sinFactura > 0),
            kpi('Clientes con pendientes', cantidad(k.clientes))
        ].join(''));

        $('#raRemTipos tbody').html(filasTipos(r.tipos, r.total));
        $('#raRemClientes tbody').html(filasClientes(r.clientes));
    }

    function kpi(etiqueta, valor, nota, aviso) {
        return '<div class="ra-kpi' + (aviso ? ' aviso' : '') + '">'
            + '<div class="ra-kpi-label">' + Reportes.esc(etiqueta) + '</div>'
            + '<div class="ra-kpi-valor">' + Reportes.esc(valor) + '</div>'
            + (nota ? '<div class="ra-kpi-nota">' + Reportes.esc(nota) + '</div>' : '')
            + '</div>';
    }

    function filasTipos(tipos, total) {
        return tipos.map(function (t) {
            return '<tr><td>' + Reportes.esc(t.tipo) + '</td>'
                + '<td class="num">' + cantidad(t.cantidad) + '</td>'
                + '<td class="num">' + importe(t.importe) + '</td></tr>';
        }).join('') + '<tr class="total"><td>Total</td>'
            + '<td class="num">' + cantidad(total.cantidad) + '</td>'
            + '<td class="num">' + importe(total.importe) + '</td></tr>';
    }

    function filasClientes(lista) {
        return lista.map(function (c) {
            return '<tr><td>' + Reportes.esc(c.cliente) + '</td><td>' + Reportes.esc(c.razonSocial) + '</td>'
                + '<td>' + Reportes.esc(c.tipo) + '</td>'
                + '<td class="num">' + cantidad(c.cantidad) + '</td>'
                + '<td class="num">' + importe(c.importe) + '</td>'
                + '<td class="num">' + cantidad(c.facturadosDespues) + '</td>'
                + '<td class="num">' + fecha(c.masViejo) + '</td></tr>';
        }).join('');
    }

    /* ---------------------------------------------------------------- */
    /* Excel                                                             */
    /* ---------------------------------------------------------------- */

    var Excel = window.ExcelReportes;

    /** Importes con miles y 2 decimales. Es formato: la celda es el número. */
    var FORMATO_IMPORTE = '#,##0.00';
    var FORMATO_FECHA = 'dd/mm/yyyy';

    /**
     * La descarga usa los filtros de la última consulta, los que devolvió el
     * servidor ya validados: el Excel es el detalle de lo que se está viendo.
     */
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
            text: 'Se trae el detalle por remito con los filtros de la consulta.',
            allowOutsideClick: false,
            didOpen: function () { Swal.showLoading(); }
        });

        Reportes.post('Remitos', 'detalle', ultima.filtros).then(function (r) {
            var nombre = generarExcel(r);

            Swal.fire({ icon: 'success', title: 'Excel generado', text: nombre + ' (' + remitos(r.filas.length) + ')',
                confirmButtonColor: '#7066e0' });
        }).catch(function (err) {
            Reportes.error('No se pudo generar el Excel', err);
        });
    }

    /** Las filas de encabezado de las dos hojas: período, corte, filtros, usuario y hora */
    function encabezado(r) {
        return Excel.encabezado('Remitos pendientes de facturar — LAKERS SA', [r.leyenda].concat(r.filtrosTexto),
            r.usuario, r.generado);
    }

    /** Una fecha 'AAAA-MM-DD' como fecha de Excel, o la celda vacía */
    function serie(iso) {
        return iso ? Excel.serie(iso) : null;
    }

    function hojaDetalle(r) {
        var aoa = encabezado(r);
        var fila0 = aoa.length;
        var c = {};

        r.columnas.forEach(function (nombre, i) { c[nombre] = i; });

        aoa.push(['Fecha remito', 'Tipo de cliente', 'Código cliente', 'Razón social', 'Remito', 'Importe neto',
            'Días al corte', 'Facturado después: Nº factura', 'Facturado después: fecha', 'Cómo se identificó']);

        r.filas.forEach(function (f) {
            aoa.push([serie(f[c.fecha]), f[c.tipo], f[c.cliente], f[c.razonSocial], f[c.remito], f[c.importe],
                f[c.diasAlCorte], f[c.facturaNro], serie(f[c.facturaFecha]), f[c.como]]);
        });

        var ws = XLSX.utils.aoa_to_sheet(aoa);
        var ultimaFila = aoa.length - 1;

        Excel.formatear(ws, 0, fila0 + 1, ultimaFila, FORMATO_FECHA);
        Excel.formatear(ws, 5, fila0 + 1, ultimaFila, FORMATO_IMPORTE);
        Excel.formatear(ws, 6, fila0 + 1, ultimaFila, '0');
        Excel.formatear(ws, 8, fila0 + 1, ultimaFila, FORMATO_FECHA);

        Excel.autofiltro(ws, fila0, ultimaFila, 9);
        ws['!cols'] = [{ wch: 12 }, { wch: 17 }, { wch: 10 }, { wch: 40 }, { wch: 16 }, { wch: 16 }, { wch: 9 },
            { wch: 18 }, { wch: 13 }, { wch: 20 }];

        return ws;
    }

    /** Las dos tablas de la pantalla, una debajo de la otra */
    function hojaResumen(r) {
        var aoa = encabezado(r);
        var res = r.resumen;

        aoa.push(['Resumen por tipo de cliente']);
        aoa.push(['Tipo de cliente', 'Remitos', 'Importe neto']);

        var tipos0 = aoa.length;

        res.tipos.forEach(function (t) {
            aoa.push([t.tipo, t.cantidad, t.importe]);
        });

        aoa.push(['Total', res.total.cantidad, res.total.importe]);

        var tiposN = aoa.length - 1;

        aoa.push([]);
        aoa.push(['Resumen por cliente']);
        aoa.push(['Código', 'Razón social', 'Tipo', 'Remitos', 'Importe pendiente', 'Ya facturados después',
            'Remito más viejo']);

        var clientes0 = aoa.length;

        res.clientes.forEach(function (x) {
            aoa.push([x.cliente, x.razonSocial, x.tipo, x.cantidad, x.importe, x.facturadosDespues, serie(x.masViejo)]);
        });

        var ws = XLSX.utils.aoa_to_sheet(aoa);
        var ultimaFila = aoa.length - 1;

        Excel.formatear(ws, 2, tipos0, tiposN, FORMATO_IMPORTE);
        Excel.formatear(ws, 4, clientes0, ultimaFila, FORMATO_IMPORTE);
        Excel.formatear(ws, 6, clientes0, ultimaFila, FORMATO_FECHA);
        ws['!cols'] = [{ wch: 18 }, { wch: 40 }, { wch: 17 }, { wch: 9 }, { wch: 18 }, { wch: 12 }, { wch: 14 }];

        return ws;
    }

    /** Arma y descarga el libro. Devuelve el nombre del archivo. */
    function generarExcel(r) {
        var wb = XLSX.utils.book_new();

        XLSX.utils.book_append_sheet(wb, hojaDetalle(r), 'Detalle');
        XLSX.utils.book_append_sheet(wb, hojaResumen(r), 'Resumen');

        var nombre = 'Remitos_Pendientes_' + r.filtros.desde.replace(/-/g, '') + '_'
            + r.filtros.hasta.replace(/-/g, '') + '.xlsx';

        XLSX.writeFile(wb, nombre, { compression: true });

        return nombre;
    }

    /* ---------------------------------------------------------------- */

    Reportes.registrar('remitos_pendientes', {
        init: function (el) {
            raiz = el;

            $(raiz)
                .on('change', '#raRemPeriodo', function () { aplicarPeriodo(); marcarCambio(); })
                .on('change', '#raRemDesde, #raRemHasta', function () {
                    // Una fecha tocada a mano ya no es la opción elegida
                    $('#raRemPeriodo').val(PERSONALIZADO);
                    fechasCambiaron();
                    marcarCambio();
                })
                .on('change', '#raRemTipo', function () {
                    if (!errorDeFechas()) {
                        cargarClientes();
                    }
                    marcarCambio();
                })
                .on('input', '#raRemClienteBuscar', dibujarClientes)
                .on('change', '#raRemCliLista input', function () {
                    var c = this.value;

                    tildados = tildados.filter(function (x) { return x !== c; });

                    if (this.checked) {
                        tildados.push(c);
                    }

                    tildados.sort();
                    rotularClientes();
                    marcarCambio();
                })
                .on('click', '#raRemCliNinguno', function () {
                    tildados = [];
                    dibujarClientes();
                    marcarCambio();
                })
                .on('click', '#raRemConsultar', consultar)
                .on('click', '#raRemExcel', descargar);

            cargarPeriodos();
        }
    });
})(jQuery);
