/**
 * LIQUIDACION SEMANAL FRANQUICIAS GA
 *
 * Pestaña "Franquicias GA" de index.php. Todo el calculo vive en los SP de
 * FRANQUICIAS_LAKERS; este archivo solo pinta y dispara acciones contra
 * api/franquicias_ga_controller.php.
 *
 * Lo llama app.js desde el handler de shown.bs.tab (#franquicias-ga-tab).
 */

const FGA_URL = 'api/franquicias_ga_controller.php';

let fgaTablaResumen = null;
let fgaTablaDetalle = null;
let fgaTablaRecibos = null;
let fgaInicializado = false;
let fgaLoteActivo = null;   // { id_lote, nro_sucurs, desc_sucursal, saldo }

// Datos crudos del ultimo JSON recibido. Los exports a Excel salen de aca y no
// del DOM: DataTables Buttons exporta el texto renderizado ("$ 4.389.424,65"),
// que Excel toma como string. Con los datos crudos SheetJS graba numeros reales.
let fgaDatosResumen = [];
let fgaDatosDetalle = { cabecera: null, filas: [], totales: null };

// Lote/sucursal para el que esta abierto el modal de mail.
let fgaMailActivo = null;   // { id_lote, nro_sucurs, desc_sucursal, periodo_desde }

/* -------------------------------------------------------------------------
   Helpers
------------------------------------------------------------------------- */

/**
 * Formatea un importe en pesos. Es una funcion comun, a proposito.
 *
 * OJO: $.fn.dataTable.render.number() NO devuelve una funcion, devuelve un
 * objeto { display: fn }. Sirve para pasarselo directo a `render:`, pero si se
 * lo invoca como funcion dentro de OTRO render tira TypeError y DataTables
 * aborta el dibujado de la tabla entera: queda sin encabezado, sin controles y
 * con las filas cortadas en la columna anterior al error.
 */
function fgaMoneda(valor) {
    const n = parseFloat(valor) || 0;
    return '$ ' + n.toLocaleString('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/** Render de DataTables para columnas de importe: formatea solo al mostrar,
 *  y devuelve el numero crudo para ordenar, filtrar y exportar. */
function fgaRenderMoneda(d, type) {
    return type === 'display' ? fgaMoneda(d) : d;
}

/**
 * Cantidad: entera si es entera, con decimales solo cuando los tiene.
 * En el lote actual todas son enteras, pero en CTA03 del canal hay ~5.800
 * renglones fraccionados sobre 5M: redondear a ciegas los ocultaria y el
 * importe dejaria de cerrar contra la cantidad que se ve.
 */
function fgaCantidad(valor) {
    const n = parseFloat(valor) || 0;
    const esEntera = Math.abs(n - Math.round(n)) < 1e-9;
    return n.toLocaleString('es-AR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: esEntera ? 0 : 4
    });
}

function fgaRenderCantidad(d, type) {
    return type === 'display' ? fgaCantidad(d) : d;
}

/* -------------------------------------------------------------------------
   Export a Excel con SheetJS
   Patron copiado de tesoreria/cajaDirectores/js/caja_reporte.js: se empujan
   numeros crudos al array, aoa_to_sheet los tipa como numericos, y el formato
   visual se fija por celda con ws[dir].z. Asi Excel recibe NUMEROS, no texto.
------------------------------------------------------------------------- */

const FGA_FMT = {
    moneda:   '"$"#,##0.00',
    cantidad: '#,##0.####',   // 1 -> "1", 1.5 -> "1,5"
    entero:   '#,##0',
    fecha:    'dd/mm/yyyy'
};

/** 'YYYY-MM-DD' -> Date local (sin corrimiento de zona horaria). */
function fgaFechaExcel(iso) {
    if (!iso) return '';
    const p = String(iso).substring(0, 10).split('-');
    return p.length === 3 ? new Date(+p[0], +p[1] - 1, +p[2]) : iso;
}

/**
 * Arma un workbook de una hoja a partir de un array de arrays.
 *   aoa       filas, la primera es el encabezado
 *   formatos  { indiceColumna: formato_z } aplicado a todas las filas de datos
 *   anchos    [ancho por columna] en caracteres
 *   filaInicioDatos  indice (0-based) de la primera fila con datos, para no
 *                    formatear titulos ni bloques de cabecera
 */
function fgaConstruirHoja(aoa, formatos, anchos, filaInicioDatos, nombreHoja) {
    const ws = XLSX.utils.aoa_to_sheet(aoa, { cellDates: true });

    if (anchos) ws['!cols'] = anchos.map(function (w) { return { wch: w }; });

    const rango = XLSX.utils.decode_range(ws['!ref']);
    for (let R = filaInicioDatos; R <= rango.e.r; ++R) {
        Object.keys(formatos).forEach(function (col) {
            const dir = XLSX.utils.encode_cell({ r: R, c: parseInt(col, 10) });
            if (ws[dir] && (ws[dir].t === 'n' || ws[dir].t === 'd')) {
                ws[dir].z = formatos[col];
            }
        });
    }

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, nombreHoja);
    return wb;
}

function fgaWorkbookResumen() {
    const datos = fgaDatosResumen || [];
    const aoa = [[
        'Lote', 'Período Desde', 'Período Hasta', 'Sucursal', 'Franquicia',
        'Comprobantes', 'Renglones', 'Rezagados', 'Sin precio L30',
        'Importe', 'Cobrado', 'Saldo', 'Estado', 'Último envío', 'Enviado a'
    ]];

    let tImporte = 0, tCobrado = 0, tSaldo = 0, tComp = 0;
    datos.forEach(function (r) {
        aoa.push([
            r.ID_LOTE,
            fgaFechaExcel(r.PERIODO_DESDE),
            fgaFechaExcel(r.PERIODO_HASTA),
            r.NRO_SUCURS,
            r.DESC_SUCURSAL,
            r.CANT_COMPROBANTES,
            r.CANT_RENGLONES,
            r.CANT_REZAGADOS,
            r.CANT_SIN_PRECIO,
            r.IMPORTE_TOTAL,
            r.IMPORTE_COBRADO,
            r.SALDO,
            r.ESTADO_SUCURSAL,
            r.ULTIMO_ENVIO_FECHA ? r.ULTIMO_ENVIO_FECHA.substring(0, 16) : '',
            r.ULTIMO_ENVIO_DEST || ''
        ]);
        tImporte += r.IMPORTE_TOTAL; tCobrado += r.IMPORTE_COBRADO;
        tSaldo += r.SALDO; tComp += r.CANT_COMPROBANTES;
    });

    aoa.push([]);
    aoa.push(['', '', '', '', 'TOTAL', tComp, '', '', '', tImporte, tCobrado, tSaldo, '', '', '']);

    return fgaConstruirHoja(
        aoa,
        { 1: FGA_FMT.fecha, 2: FGA_FMT.fecha, 5: FGA_FMT.entero, 6: FGA_FMT.entero,
          7: FGA_FMT.entero, 8: FGA_FMT.entero, 9: FGA_FMT.moneda, 10: FGA_FMT.moneda, 11: FGA_FMT.moneda },
        [7, 13, 13, 9, 28, 13, 11, 11, 13, 16, 16, 16, 12, 17, 30],
        1,
        'Resumen'
    );
}

function fgaExportarResumenExcel() {
    if (!fgaDatosResumen || fgaDatosResumen.length === 0) {
        Swal.fire('Sin datos', 'No hay liquidaciones para exportar con los filtros actuales.', 'info');
        return;
    }
    const wb = fgaWorkbookResumen();
    XLSX.writeFile(wb, 'Liquidacion_Franquicias_GA_Resumen_' + $('#fga-desde').val() + '_' + $('#fga-hasta').val() + '.xlsx');
}

/**
 * Recibe { cabecera, filas } explicitamente y no lee fgaDatosDetalle: el mail
 * adjunta el detalle del lote/sucursal que se esta enviando, que no tiene por
 * que ser el ultimo que se abrio en el modal de detalle.
 */
function fgaWorkbookDetalle(datos) {
    const c = datos.cabecera || {};
    const filas = datos.filas || [];
    const franquicia = filas.length ? (filas[0].NRO_SUCURS + ' - ' + filas[0].DESC_SUCURSAL) : '';

    // Bloque de cabecera (como caja_reporte.js) y despues la tabla.
    const aoa = [
        ['LIQUIDACIÓN SEMANAL FRANQUICIAS GA - DETALLE DE COMPROBANTES'],
        ['Lote', '#' + (c.ID_LOTE || '')],
        ['Período', fgaFechaExcel(c.PERIODO_DESDE), 'al', fgaFechaExcel(c.PERIODO_HASTA)],
        ['Franquicia', franquicia],
        ['Estado del lote', c.ESTADO_LOTE || ''],
        [],
        ['Rezagado', 'Fecha', 'Tipo', 'Número', 'Artículo', 'Cantidad', 'Precio L30', 'Importe', 'Sin precio L30']
    ];
    const filaInicioDatos = aoa.length;

    let tImporte = 0;
    filas.forEach(function (r) {
        aoa.push([
            r.ES_REZAGADO ? 'Sí' : '',
            fgaFechaExcel(r.FECHA_EMIS),
            r.T_COMP,
            r.N_COMP,
            r.COD_ARTICU,
            r.CANTIDAD,
            r.PRECIO_UNITARIO,
            r.IMPORTE,
            r.SIN_PRECIO_LISTA ? 'Sí' : ''
        ]);
        tImporte += r.IMPORTE;
    });

    /*  El total A COBRAR esta redondeado a pesos enteros y la suma de los
        renglones no, asi que el pie tiene que mostrar los tres numeros. Este
        Excel es el adjunto que recibe la franquicia: si mostrara solo la suma
        cruda, no cerraria contra el importe del mail ni contra el recibo.
        El redondeo se toma de datos.totales, que lo calcula el servidor; aca
        no se replica la cuenta.                                             */
    const t = datos.totales || {};
    const ajuste = typeof t.ajuste_redondeo === 'number' ? t.ajuste_redondeo : 0;
    const aCobrar = typeof t.importe === 'number' ? t.importe : tImporte;

    aoa.push([]);
    aoa.push(['', '', '', '', 'TOTAL (' + filas.length + ' renglones)', '', '', tImporte, '']);
    if (Math.abs(ajuste) >= 0.005) {
        aoa.push(['', '', '', '', 'Redondeo a pesos enteros', '', '', ajuste, '']);
        aoa.push(['', '', '', '', 'TOTAL A COBRAR', '', '', aCobrar, '']);
    }

    // Las fechas del bloque de cabecera tambien van con formato de fecha.
    const wb = fgaConstruirHoja(
        aoa,
        { 1: FGA_FMT.fecha, 5: FGA_FMT.cantidad, 6: FGA_FMT.moneda, 7: FGA_FMT.moneda },
        [10, 12, 6, 17, 20, 10, 15, 16, 14],
        filaInicioDatos,
        'Detalle'
    );
    const ws = wb.Sheets['Detalle'];
    ['B3', 'D3'].forEach(function (dir) { if (ws[dir] && ws[dir].t === 'd') ws[dir].z = FGA_FMT.fecha; });
    return wb;
}

function fgaNombreArchivoDetalle(datos) {
    const c = datos.cabecera || {};
    const filas = datos.filas || [];
    const suc = filas.length ? filas[0].NRO_SUCURS : 'todas';
    return 'Liquidacion_Franquicias_GA_Lote' + (c.ID_LOTE || '') + '_Suc' + suc + '_' + (c.PERIODO_DESDE || '') + '.xlsx';
}

function fgaExportarDetalleExcel() {
    if (!fgaDatosDetalle.filas || fgaDatosDetalle.filas.length === 0) {
        Swal.fire('Sin datos', 'El lote no tiene renglones para exportar.', 'info');
        return;
    }
    XLSX.writeFile(fgaWorkbookDetalle(fgaDatosDetalle), fgaNombreArchivoDetalle(fgaDatosDetalle));
}

// Los render de DataTables reciben (data, type, row). Para 'sort', 'filter' y
// 'export' hay que devolver el valor crudo: si se devuelve el HTML, la columna
// ordena por el nombre de la clase CSS del badge en vez de por el estado.
function fgaBadgeEstado(estado, type) {
    if (type !== 'display') return estado;

    let clase = 'secondary';
    if (estado === 'COBRADO') clase = 'success';
    else if (estado === 'PARCIAL') clase = 'info';
    else if (estado === 'PENDIENTE') clase = 'warning text-dark';
    else if (estado === 'ANULADO') clase = 'dark';
    else if (estado === 'GENERADO') clase = 'primary';
    return '<span class="badge bg-' + clase + '">' + estado + '</span>';
}

function fgaError(xhr, mensajePorDefecto) {
    let msg = mensajePorDefecto;
    try {
        const r = JSON.parse(xhr.responseText);
        if (r && r.message) msg = r.message;
    } catch (e) { /* respuesta no-JSON: queda el mensaje por defecto */ }
    Swal.fire('Error', msg, 'error');
}

/* -------------------------------------------------------------------------
   Init de la pestaña
------------------------------------------------------------------------- */
function initFranquiciasGA() {
    if (fgaInicializado) {
        fgaCargarResumen();
        return;
    }

    $.ajax({
        url: FGA_URL + '?action=init',
        type: 'GET',
        dataType: 'json',
        success: function (r) {
            if (!r.success) {
                Swal.fire('Atención', r.message || 'No se pudo inicializar la pestaña.', 'warning');
                return;
            }

            // Periodo por defecto: ultimo lunes-domingo cerrado, calculado en
            // el servidor con el MISMO criterio que el SP y que el job.
            $('#fga-desde').val(r.periodo_desde);
            $('#fga-hasta').val(r.periodo_hasta);

            const $suc = $('#fga-sucursal').empty().append('<option value="">Todas</option>');
            r.sucursales.forEach(function (s) {
                $suc.append('<option value="' + s.NRO_SUCURSAL + '">' +
                    s.NRO_SUCURSAL + ' - ' + s.DESC_SUCURSAL + '</option>');
            });

            const $est = $('#fga-estado').empty().append('<option value="">Todos</option>');
            r.estados_lote.forEach(function (e) {
                $est.append('<option value="' + e + '">' + e + '</option>');
            });

            fgaAvisarSucursalesSinAlta(r.sucursales_sin_alta);

            fgaInicializado = true;
            fgaCargarResumen();
        },
        error: function (xhr) {
            fgaError(xhr, 'No se pudo conectar con el servidor.');
        }
    });
}

/**
 * Una franquicia del canal sin fecha de alta en RO_T_FRANQ_GA_SUCURSAL_INICIO
 * NO se liquida. Sin este aviso, el sintoma seria una grilla a la que le falta
 * una fila, sin ninguna explicacion a la vista.
 */
function fgaAvisarSucursalesSinAlta(sinAlta) {
    const $caja = $('#fga-aviso-sin-alta');

    if (!sinAlta || sinAlta.length === 0) {
        $caja.addClass('d-none').empty();
        return;
    }

    const lista = sinAlta.map(function (s) {
        return s.NRO_SUCURSAL + ' - ' + s.DESC_SUCURSAL;
    }).join(', ');

    $caja.removeClass('d-none').html(
        '<i class="fa-solid fa-triangle-exclamation me-1"></i> ' +
        '<strong>' + sinAlta.length + ' franquicia(s) del canal sin fecha de alta: </strong>' + lista + '. ' +
        'No se están liquidando. Hay que cargarlas en <code>RO_T_FRANQ_GA_SUCURSAL_INICIO</code> ' +
        'indicando desde qué fecha corresponde liquidarlas.'
    );
}

/* -------------------------------------------------------------------------
   Grilla principal: resumen por lote y franquicia
------------------------------------------------------------------------- */
function fgaCargarResumen() {
    const filtros = {
        action: 'resumen',
        desde: $('#fga-desde').val(),
        hasta: $('#fga-hasta').val(),
        nro_sucursal: $('#fga-sucursal').val(),
        estado: $('#fga-estado').val()
    };

    $.ajax({
        url: FGA_URL,
        data: filtros,
        type: 'GET',
        dataType: 'json',
        success: function (r) {
            if (!r.success) {
                Swal.fire('Atención', r.message || 'No se pudo obtener el resumen.', 'warning');
                return;
            }

            fgaPintarKpis(r.totales, r.data.length);
            fgaDatosResumen = r.data;

            if ($.fn.DataTable.isDataTable('#tabla-franq-ga-resumen')) {
                $('#tabla-franq-ga-resumen').DataTable().destroy();
                $('#tabla-franq-ga-resumen').empty();
            }

            fgaTablaResumen = $('#tabla-franq-ga-resumen').DataTable({
                data: r.data,
                columns: [
                    {
                        data: 'ID_LOTE', title: 'Lote', className: 'text-center fw-bold',
                        render: function (d, type) { return type === 'display' ? '#' + d : d; }
                    },
                    {
                        data: 'PERIODO_DESDE', title: 'Período', className: 'text-center',
                        render: function (d, type, row) {
                            if (type !== 'display') return d;   // ordena por la fecha, no por el HTML
                            return d + '<br><small class="text-muted">al ' + row.PERIODO_HASTA + '</small>';
                        }
                    },
                    { data: 'NRO_SUCURS', title: 'Suc.', className: 'text-center' },
                    { data: 'DESC_SUCURSAL', title: 'Franquicia' },
                    { data: 'CANT_COMPROBANTES', title: 'Comprob.', className: 'text-center' },
                    {
                        data: 'CANT_REZAGADOS', title: 'Rezag.', className: 'text-center',
                        render: function (d, type) {
                            if (type !== 'display') return d;
                            if (!d) return '<span class="text-muted">-</span>';
                            return '<span class="badge bg-warning text-dark" title="Comprobantes de períodos anteriores incluidos en este lote">' + d + '</span>';
                        }
                    },
                    {
                        /*  Importe A COBRAR: redondeado a pesos enteros por
                            franquicia, que es por lo que se emite el recibo.
                            El title deja a mano la suma cruda del detalle
                            para quien tenga que conciliar los centavos.    */
                        data: 'IMPORTE_TOTAL', title: 'Importe', className: 'text-end fw-bold',
                        render: function (d, type, row) {
                            if (type !== 'display') return d;
                            const txt = fgaMoneda(d);
                            return Math.abs(row.AJUSTE_REDONDEO || 0) >= 0.005
                                ? '<span title="Redondeado a pesos enteros. Suma del detalle: ' +
                                  fgaMoneda(row.IMPORTE_EXACTO) + '" class="border-bottom border-secondary-subtle">' +
                                  txt + '</span>'
                                : txt;
                        }
                    },
                    { data: 'IMPORTE_COBRADO', title: 'Cobrado', className: 'text-end', render: fgaRenderMoneda },
                    {
                        data: 'SALDO', title: 'Saldo', className: 'text-end fw-bold',
                        render: function (d, type) {
                            if (type !== 'display') return d;
                            const clase = d > 0 ? 'text-danger' : 'text-success';
                            return '<span class="' + clase + '">' + fgaMoneda(d) + '</span>';
                        }
                    },
                    {
                        data: 'ESTADO_SUCURSAL', title: 'Estado', className: 'text-center',
                        render: fgaBadgeEstado
                    },
                    {
                        data: 'ULTIMO_ENVIO_FECHA', title: 'Envío', className: 'text-center',
                        render: function (d, type, row) {
                            if (type !== 'display') return d || '';
                            if (!d) return '<span class="badge bg-light text-muted border">Sin enviar</span>';
                            const f = d.substring(8, 10) + '/' + d.substring(5, 7) + ' ' + d.substring(11, 16);
                            return '<span class="badge bg-success" title="Enviado a ' + (row.ULTIMO_ENVIO_DEST || '') +
                                ' por ' + (row.ULTIMO_ENVIO_USUARIO || 'n/d') +
                                (row.CANT_ENVIOS > 1 ? ' (' + row.CANT_ENVIOS + ' envíos)' : '') + '">' +
                                '<i class="fa-solid fa-envelope-circle-check me-1"></i>' + f + '</span>';
                        }
                    },
                    {
                        data: null, title: 'Acciones', orderable: false, className: 'text-center',
                        render: function (row) {
                            const deshabilitado = row.ESTADO_LOTE === 'ANULADO' ? ' disabled' : '';
                            return '<div class="btn-group">' +
                                '<button class="btn btn-outline-info btn-sm fga-btn-detalle" ' +
                                'data-id-lote="' + row.ID_LOTE + '" data-suc="' + row.NRO_SUCURS + '" ' +
                                'title="Ver comprobantes del lote"><i class="fa-solid fa-list-ul"></i></button>' +
                                '<button class="btn btn-outline-success btn-sm fga-btn-recibos"' + deshabilitado + ' ' +
                                'data-id-lote="' + row.ID_LOTE + '" data-suc="' + row.NRO_SUCURS + '" ' +
                                'data-desc="' + row.DESC_SUCURSAL + '" ' +
                                'title="Vincular recibos de cobranza"><i class="fa-solid fa-link"></i></button>' +
                                '<button class="btn btn-outline-primary btn-sm fga-btn-mail"' + deshabilitado + ' ' +
                                'data-id-lote="' + row.ID_LOTE + '" data-suc="' + row.NRO_SUCURS + '" ' +
                                'data-desc="' + row.DESC_SUCURSAL + '" data-desde="' + row.PERIODO_DESDE + '" ' +
                                'title="Enviar la liquidación por mail a la franquicia"><i class="fa-solid fa-envelope"></i></button>' +
                                '</div>';
                        }
                    }
                ],
                order: [[1, 'desc'], [2, 'asc']],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                responsive: true,
                autoWidth: false,
                dom: 'Bfrtip',
                buttons: [
                    {
                        // Export propio con SheetJS (ver fgaWorkbookResumen). No se usa
                        // extend: 'excel' porque exporta el texto renderizado y Excel lo
                        // recibe como string.
                        text: '<i class="fa-solid fa-file-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        action: function () { fgaExportarResumenExcel(); }
                    }
                ]
            });
        },
        error: function (xhr) {
            fgaError(xhr, 'No se pudo obtener el resumen de liquidación.');
        }
    });
}

function fgaPintarKpis(t, filas) {
    $('#fga-kpi-importe').text(fgaMoneda(t.importe));
    $('#fga-kpi-cobrado').text(fgaMoneda(t.cobrado));
    $('#fga-kpi-saldo').text(fgaMoneda(t.saldo));
    $('#fga-kpi-comprobantes').text((t.comprobantes || 0).toLocaleString('es-AR') +
        ' en ' + filas + (filas === 1 ? ' liquidación' : ' liquidaciones'));
}

/* -------------------------------------------------------------------------
   Modal de detalle
------------------------------------------------------------------------- */
function fgaAbrirDetalle(idLote, nroSucursal) {
    $('#fga-detalle-titulo').text('Lote #' + idLote);
    $('#fga-detalle-resumen').html('<div class="text-center p-3"><div class="spinner-border text-primary"></div></div>');

    const modal = new bootstrap.Modal(document.getElementById('modalFranqGaDetalle'));
    modal.show();

    $.ajax({
        url: FGA_URL,
        data: { action: 'detalle', id_lote: idLote, nro_sucursal: nroSucursal },
        type: 'GET',
        dataType: 'json',
        success: function (r) {
            if (!r.success) {
                Swal.fire('Atención', r.message || 'No se pudo obtener el detalle.', 'warning');
                return;
            }

            const c = r.cabecera || {};
            $('#fga-detalle-titulo').text('Lote #' + idLote + '  ·  ' + (c.PERIODO_DESDE || '') + ' al ' + (c.PERIODO_HASTA || ''));

            /*  La tarjeta muestra el importe A COBRAR (redondeado a pesos
                enteros, igual que la columna Importe de la grilla) y debajo,
                en chico, la suma cruda de los renglones cuando difiere. Sin
                esa segunda linea el total de la tarjeta no cierra contra la
                columna Importe de la tabla de abajo y parece un error.      */
            const ajusteDet = r.totales.ajuste_redondeo || 0;
            const notaRedondeo = Math.abs(ajusteDet) >= 0.005
                ? '<div class="text-xs text-muted" title="El importe a cobrar se redondea a pesos enteros">' +
                  'suma del detalle ' + fgaMoneda(r.totales.importe_exacto) +
                  ' · redondeo ' + (ajusteDet > 0 ? '+' : '−') + fgaMoneda(Math.abs(ajusteDet)) +
                  '</div>'
                : '';

            $('#fga-detalle-resumen').html(
                '<div class="row g-2">' +
                '<div class="col-md-3"><div class="border rounded p-2 bg-light"><div class="text-xs text-uppercase text-muted">Total liquidado</div>' +
                '<div class="h5 mb-0 fw-bold">' + fgaMoneda(r.totales.importe) + '</div>' + notaRedondeo + '</div></div>' +
                '<div class="col-md-3"><div class="border rounded p-2 bg-light"><div class="text-xs text-uppercase text-muted">Renglones</div>' +
                '<div class="h5 mb-0 fw-bold">' + r.totales.renglones + '</div></div></div>' +
                '<div class="col-md-3"><div class="border rounded p-2 bg-light"><div class="text-xs text-uppercase text-muted">Rezagados</div>' +
                '<div class="h5 mb-0 fw-bold text-warning">' + r.totales.rezagados + '</div></div></div>' +
                '<div class="col-md-3"><div class="border rounded p-2 bg-light"><div class="text-xs text-uppercase text-muted">Sin precio Lista 30</div>' +
                '<div class="h5 mb-0 fw-bold ' + (r.totales.sin_precio ? 'text-danger' : 'text-muted') + '">' + r.totales.sin_precio + '</div></div></div>' +
                '</div>'
            );

            fgaDatosDetalle = { cabecera: r.cabecera, filas: r.data, totales: r.totales };

            if ($.fn.DataTable.isDataTable('#tabla-franq-ga-detalle')) {
                $('#tabla-franq-ga-detalle').DataTable().destroy();
                $('#tabla-franq-ga-detalle').empty();
            }

            fgaTablaDetalle = $('#tabla-franq-ga-detalle').DataTable({
                data: r.data,
                columns: [
                    {
                        data: 'ES_REZAGADO', title: '', className: 'text-center', orderable: false,
                        render: function (d, type) {
                            if (type !== 'display') return d;
                            return d
                                ? '<i class="fa-solid fa-clock-rotate-left text-warning" title="Rezagado: comprobante de un período anterior"></i>'
                                : '';
                        }
                    },
                    { data: 'FECHA_EMIS', title: 'Fecha', className: 'text-center' },
                    { data: 'T_COMP', title: 'Tipo', className: 'text-center' },
                    { data: 'N_COMP', title: 'Número' },
                    { data: 'COD_ARTICU', title: 'Artículo' },
                    { data: 'CANTIDAD', title: 'Cant.', className: 'text-end', render: fgaRenderCantidad },
                    {
                        data: 'PRECIO_UNITARIO', title: 'Precio L30', className: 'text-end',
                        render: function (d, type, row) {
                            const txt = fgaMoneda(d);
                            if (type !== 'display') return d;
                            return row.SIN_PRECIO_LISTA
                                ? '<span class="text-danger" title="El artículo no tenía precio en la Lista 30 al generar el lote">' + txt + ' <i class="fa-solid fa-triangle-exclamation"></i></span>'
                                : txt;
                        }
                    },
                    {
                        data: 'IMPORTE', title: 'Importe', className: 'text-end fw-bold',
                        render: function (d, type) {
                            if (type !== 'display') return d;
                            return d < 0
                                ? '<span class="text-danger">' + fgaMoneda(d) + '</span>'
                                : fgaMoneda(d);
                        }
                    }
                ],
                // Los rezagados ya vienen primero desde el SP; se respeta ese orden.
                order: [],
                pageLength: 25,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                responsive: true,
                autoWidth: false,
                dom: 'Bfrtip',
                buttons: [
                    {
                        text: '<i class="fa-solid fa-file-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        action: function () { fgaExportarDetalleExcel(); }
                    }
                ],
                createdRow: function (fila, data) {
                    // Diferenciacion visual de los rezagados pedida para la pantalla.
                    if (data.ES_REZAGADO) $(fila).addClass('table-warning');
                    if (data.SIN_PRECIO_LISTA) $(fila).addClass('fst-italic');
                }
            });
        },
        error: function (xhr) {
            fgaError(xhr, 'No se pudo obtener el detalle del lote.');
        }
    });
}

/* -------------------------------------------------------------------------
   Modal de recibos
------------------------------------------------------------------------- */
function fgaAbrirRecibos(idLote, nroSucursal, descSucursal) {
    fgaLoteActivo = { id_lote: idLote, nro_sucurs: nroSucursal, desc_sucursal: descSucursal };

    $('#fga-recibos-titulo').text('Lote #' + idLote + '  ·  ' + descSucursal);

    const modal = new bootstrap.Modal(document.getElementById('modalFranqGaRecibos'));
    modal.show();

    fgaCargarRecibos();
}

function fgaCargarRecibos() {
    if (!fgaLoteActivo) return;

    $('#fga-recibos-vinculados').html('<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>');

    $.ajax({
        url: FGA_URL,
        data: {
            action: 'recibos',
            id_lote: fgaLoteActivo.id_lote,
            nro_sucursal: fgaLoteActivo.nro_sucurs
        },
        type: 'GET',
        dataType: 'json',
        success: function (r) {
            if (!r.success) {
                Swal.fire('Atención', r.message || 'No se pudieron obtener los recibos.', 'warning');
                return;
            }

            fgaLoteActivo.saldo = r.saldo_pendiente;

            $('#fga-recibos-importe').text(fgaMoneda(r.importe_lote));
            $('#fga-recibos-cobrado').text(fgaMoneda(r.importe_cobrado));
            $('#fga-recibos-saldo')
                .text(fgaMoneda(r.saldo_pendiente))
                .removeClass('text-danger text-success')
                .addClass(r.saldo_pendiente > 0 ? 'text-danger' : 'text-success');

            fgaPintarVinculados(r.vinculados);

            if ($.fn.DataTable.isDataTable('#tabla-franq-ga-recibos')) {
                $('#tabla-franq-ga-recibos').DataTable().destroy();
                $('#tabla-franq-ga-recibos').empty();
            }

            fgaTablaRecibos = $('#tabla-franq-ga-recibos').DataTable({
                data: r.candidatos,
                columns: [
                    {
                        data: 'MATCH_EXACTO', title: '', className: 'text-center', orderable: false,
                        render: function (d, type) {
                            if (type !== 'display') return d;
                            return d
                                ? '<i class="fa-solid fa-circle-check text-success" title="El importe coincide exactamente con el saldo pendiente"></i>'
                                : '';
                        }
                    },
                    { data: 'FECHA_EMIS_RECIBO', title: 'Fecha', className: 'text-center' },
                    { data: 'N_COMP_RECIBO', title: 'Recibo' },
                    { data: 'COD_CLIENT', title: 'Cliente', className: 'text-center' },
                    { data: 'IMPORTE_RECIBO', title: 'Importe', className: 'text-end fw-bold', render: fgaRenderMoneda },
                    {
                        data: 'DIFERENCIA', title: 'Dif. vs saldo', className: 'text-end',
                        render: function (d, type) {
                            if (type !== 'display') return d;
                            return d === 0
                                ? '<span class="text-success fw-bold">exacto</span>'
                                : '<span class="text-muted">' + fgaMoneda(d) + '</span>';
                        }
                    },
                    { data: 'LEYENDA', title: 'Leyenda' },
                    {
                        data: null, title: '', orderable: false, className: 'text-center',
                        render: function (row) {
                            return '<button class="btn btn-success btn-sm fga-btn-vincular" ' +
                                'data-n-comp="' + row.N_COMP_RECIBO + '" ' +
                                'data-suc="' + row.NRO_SUCURS + '" ' +
                                'data-importe="' + row.IMPORTE_RECIBO + '" ' +
                                'data-exacto="' + row.MATCH_EXACTO + '">' +
                                '<i class="fa-solid fa-link me-1"></i>Vincular</button>';
                        }
                    }
                ],
                // El SP ya los devuelve por proximidad al saldo; no se reordena.
                order: [],
                pageLength: 10,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                responsive: true,
                autoWidth: false,
                createdRow: function (fila, data) {
                    if (data.MATCH_EXACTO) $(fila).addClass('table-success');
                }
            });
        },
        error: function (xhr) {
            fgaError(xhr, 'No se pudieron obtener los recibos candidatos.');
        }
    });
}

function fgaPintarVinculados(vinculados) {
    if (!vinculados || vinculados.length === 0) {
        $('#fga-recibos-vinculados').html(
            '<p class="text-muted small mb-0 fst-italic">Todavía no hay recibos vinculados a este lote.</p>'
        );
        return;
    }

    let html = '<ul class="list-group list-group-flush">';
    vinculados.forEach(function (v) {
        html += '<li class="list-group-item d-flex justify-content-between align-items-center px-2 py-2">' +
            '<div>' +
            '<strong>' + v.N_COMP_RECIBO + '</strong> ' +
            '<span class="badge bg-light text-dark border ms-1">' + v.TIPO_MATCH + '</span><br>' +
            '<small class="text-muted">' + (v.FECHA_EMIS_RECIBO || '-') +
            ' · vinculado por ' + (v.USUARIO_VINCULA || 'n/d') + ' el ' + (v.FECHA_VINCULO || '-') + '</small>' +
            '</div>' +
            '<div class="text-end">' +
            '<span class="fw-bold me-2">' + fgaMoneda(v.IMPORTE_RECIBO) + '</span>' +
            '<button class="btn btn-outline-danger btn-sm fga-btn-desvincular" data-id-vinculo="' + v.ID_VINCULO + '" ' +
            'data-n-comp="' + v.N_COMP_RECIBO + '" title="Desvincular"><i class="fa-solid fa-link-slash"></i></button>' +
            '</div></li>';
    });
    html += '</ul>';

    $('#fga-recibos-vinculados').html(html);
}

/* -------------------------------------------------------------------------
   Modal de mail: previsualizacion + confirmacion
   Mismo patron que el modal de WhatsApp de mayoristas.js: se muestra lo que se
   va a mandar, el operador puede ajustar destinatario y asunto, y recien
   despues de confirmar se envia.
------------------------------------------------------------------------- */
function fgaAbrirMail(idLote, nroSucursal, descSucursal, periodoDesde) {
    fgaMailActivo = {
        id_lote: idLote,
        nro_sucurs: nroSucursal,
        desc_sucursal: descSucursal,
        periodo_desde: periodoDesde,
        detalle: null,       // { cabecera, filas } para armar el adjunto
        listo: false
    };

    $('#fga-mail-titulo').text('Lote #' + idLote + '  ·  ' + descSucursal);
    $('#fga-mail-destinatario').val('').removeClass('is-invalid');
    $('#fga-mail-asunto').val('');
    $('#fga-mail-preview').attr('srcdoc', '<p style="font-family:sans-serif;color:#888;padding:20px">Cargando vista previa...</p>');
    $('#fga-mail-adjunto-info').text('Preparando adjunto...');
    $('#fga-mail-aviso-enviado').addClass('d-none').empty();
    $('#fga-mail-sin-mail').addClass('d-none');
    $('#fga-btn-enviar-mail').prop('disabled', true);

    const modal = new bootstrap.Modal(document.getElementById('modalFranqGaMail'));
    modal.show();

    // Preview del mail y detalle para el adjunto, en paralelo.
    const pPreview = $.ajax({
        url: FGA_URL, type: 'GET', dataType: 'json',
        data: { action: 'preview_mail', id_lote: idLote, nro_sucursal: nroSucursal }
    });
    const pDetalle = $.ajax({
        url: FGA_URL, type: 'GET', dataType: 'json',
        data: { action: 'detalle', id_lote: idLote, nro_sucursal: nroSucursal }
    });

    $.when(pPreview, pDetalle).done(function (rp, rd) {
        const p = rp[0], d = rd[0];

        if (!p.success) {
            Swal.fire('Atención', p.message || 'No se pudo armar el mail.', 'warning');
            modal.hide();
            return;
        }
        if (!d.success) {
            Swal.fire('Atención', d.message || 'No se pudo obtener el detalle para el adjunto.', 'warning');
            modal.hide();
            return;
        }

        fgaMailActivo.detalle = { cabecera: d.cabecera, filas: d.data, totales: d.totales };

        $('#fga-mail-asunto').val(p.asunto);
        // srcdoc muestra el HTML tal cual lo devolvio el servidor: la vista previa
        // es el mail real, no una copia del template en JS.
        $('#fga-mail-preview').attr('srcdoc', p.cuerpo_html);

        if (p.destinatario) {
            $('#fga-mail-destinatario').val(p.destinatario);
            $('#fga-mail-sin-mail').addClass('d-none');
        } else {
            // 58 de 179 franquicias del canal no tienen MAIL cargado: no es un caso raro.
            $('#fga-mail-sin-mail').removeClass('d-none');
            $('#fga-mail-destinatario').addClass('is-invalid');
        }

        const nombre = fgaNombreArchivoDetalle(fgaMailActivo.detalle);
        $('#fga-mail-adjunto-info').html(
            '<i class="fa-solid fa-file-excel text-success me-1"></i><strong>' + nombre + '</strong>' +
            ' <span class="text-muted">(' + d.data.length + ' renglones, ' + fgaMoneda(d.totales.importe) + ')</span>'
        );

        if (p.ultimo_envio) {
            const u = p.ultimo_envio;
            const f = u.FECHA ? (u.FECHA.substring(8, 10) + '/' + u.FECHA.substring(5, 7) + '/' + u.FECHA.substring(0, 4) + ' ' + u.FECHA.substring(11, 16)) : '';
            $('#fga-mail-aviso-enviado').removeClass('d-none').html(
                '<i class="fa-solid fa-triangle-exclamation me-1"></i> ' +
                '<strong>Esta liquidación ya fue enviada</strong> el ' + f + ' a <strong>' + u.DEST + '</strong>' +
                (u.USUARIO ? ' por ' + u.USUARIO : '') +
                (p.cant_envios > 1 ? ' (' + p.cant_envios + ' envíos en total)' : '') +
                '. Si continuás, se va a enviar de nuevo.'
            );
        }

        fgaMailActivo.listo = true;
        fgaValidarDestinatario();
    }).fail(function (xhr) {
        fgaError(xhr, 'No se pudo preparar el mail.');
        modal.hide();
    });
}

function fgaValidarDestinatario() {
    const v = $('#fga-mail-destinatario').val().trim();
    const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    $('#fga-mail-destinatario').toggleClass('is-invalid', !ok);
    $('#fga-btn-enviar-mail').prop('disabled', !(ok && fgaMailActivo && fgaMailActivo.listo));
    return ok;
}

function fgaEnviarMail() {
    if (!fgaMailActivo || !fgaMailActivo.listo || !fgaValidarDestinatario()) return;

    const destinatario = $('#fga-mail-destinatario').val().trim();
    const asunto       = $('#fga-mail-asunto').val().trim();

    Swal.fire({
        title: '¿Enviar la liquidación?',
        html: '<div class="text-start small">' +
            '<div><strong>Para:</strong> ' + destinatario + '</div>' +
            '<div><strong>Asunto:</strong> ' + $('<div>').text(asunto).html() + '</div>' +
            '<div><strong>Adjunto:</strong> ' + fgaNombreArchivoDetalle(fgaMailActivo.detalle) + '</div>' +
            '</div>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#198754'
    }).then(function (res) {
        if (!res.isConfirmed) return;

        // El .xlsx se arma aca con el MISMO generador que el boton Excel del
        // detalle, asi el adjunto es identico a lo que se descarga.
        let adjuntoB64 = '';
        try {
            adjuntoB64 = XLSX.write(fgaWorkbookDetalle(fgaMailActivo.detalle), { bookType: 'xlsx', type: 'base64' });
        } catch (e) {
            Swal.fire('Error', 'No se pudo generar el adjunto Excel: ' + e.message, 'error');
            return;
        }

        Swal.fire({
            title: 'Enviando...',
            html: 'Conectando con el servidor de correo. Esto puede tardar unos segundos.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function () { Swal.showLoading(); }
        });

        $.ajax({
            url: FGA_URL + '?action=enviar_mail',
            type: 'POST',
            dataType: 'json',
            data: {
                id_lote: fgaMailActivo.id_lote,
                nro_sucursal: fgaMailActivo.nro_sucurs,
                destinatario: destinatario,
                asunto: asunto,
                adjunto_base64: adjuntoB64,
                adjunto_nombre: fgaNombreArchivoDetalle(fgaMailActivo.detalle)
            },
            success: function (r) {
                if (!r.success) {
                    // El envio es sincrono: si llego aca con success=false, el mail
                    // realmente NO salio. No se disfraza.
                    Swal.fire('No se envió', r.message || 'El servidor de correo rechazó el envío.', 'error');
                    return;
                }
                Swal.fire('¡Enviado!', r.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalFranqGaMail')).hide();
                fgaCargarResumen();
            },
            error: function (xhr) {
                fgaError(xhr, 'No se pudo enviar el mail.');
            }
        });
    });
}

/* -------------------------------------------------------------------------
   Eventos
------------------------------------------------------------------------- */
$(function () {

    $('#fga-btn-filtrar').on('click', fgaCargarResumen);

    $('#fga-btn-limpiar').on('click', function () {
        $('#fga-sucursal').val('');
        $('#fga-estado').val('');
        // Las fechas vuelven al periodo por defecto que dio el servidor.
        $.getJSON(FGA_URL + '?action=init', function (r) {
            if (r.success) {
                $('#fga-desde').val(r.periodo_desde);
                $('#fga-hasta').val(r.periodo_hasta);
            }
            fgaCargarResumen();
        });
    });

    $('#franquicias-ga').on('click', '.fga-btn-detalle', function () {
        fgaAbrirDetalle($(this).data('id-lote'), $(this).data('suc'));
    });

    $('#franquicias-ga').on('click', '.fga-btn-recibos', function () {
        fgaAbrirRecibos($(this).data('id-lote'), $(this).data('suc'), $(this).data('desc'));
    });

    // --- Mail de liquidacion ---
    $('#franquicias-ga').on('click', '.fga-btn-mail', function () {
        fgaAbrirMail($(this).data('id-lote'), $(this).data('suc'), $(this).data('desc'), $(this).data('desde'));
    });

    $('#fga-mail-destinatario').on('input change', fgaValidarDestinatario);

    $('#fga-btn-enviar-mail').on('click', fgaEnviarMail);

    // --- Generar lote manual (reproceso si fallo el job) ---
    $('#fga-btn-generar').on('click', function () {
        const desde = $('#fga-desde').val();
        const hasta = $('#fga-hasta').val();

        Swal.fire({
            title: 'Generar lote manual',
            html: '<p class="mb-2">Se va a generar la liquidación del período:</p>' +
                '<p class="fw-bold mb-3">' + desde + ' al ' + hasta + '</p>' +
                '<p class="small text-muted mb-0">Si ya existe un lote para ese período no se duplica nada: ' +
                'el proceso lo detecta y avisa. Los comprobantes ya liquidados en lotes anteriores quedan excluidos.</p>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0d6efd'
        }).then(function (res) {
            if (!res.isConfirmed) return;

            $.ajax({
                url: FGA_URL + '?action=generar_lote',
                type: 'POST',
                dataType: 'json',
                data: { desde: desde, hasta: hasta },
                success: function (r) {
                    if (!r.success) {
                        Swal.fire('Error', r.message || 'No se pudo generar el lote.', 'error');
                        return;
                    }
                    const d = r.data;
                    Swal.fire({
                        icon: r.ya_existia ? 'info' : 'success',
                        title: r.ya_existia ? 'El lote ya existía' : '¡Lote generado!',
                        html: r.message +
                            '<hr class="my-2"><div class="small text-start">' +
                            '<div><strong>Importe:</strong> ' + fgaMoneda(d.IMPORTE_TOTAL) + '</div>' +
                            '<div><strong>Comprobantes:</strong> ' + d.CANT_COMPROBANTES + ' (' + d.CANT_RENGLONES + ' renglones)</div>' +
                            '<div><strong>Rezagados:</strong> ' + d.CANT_REZAGADOS + '</div>' +
                            '<div><strong>Sin precio Lista 30:</strong> ' + d.CANT_SIN_PRECIO + '</div>' +
                            '</div>'
                    });
                    fgaCargarResumen();
                },
                error: function (xhr) {
                    fgaError(xhr, 'No se pudo generar el lote.');
                }
            });
        });
    });

    // --- Vincular recibo (confirmacion explicita) ---
    $('#modalFranqGaRecibos').on('click', '.fga-btn-vincular', function () {
        const nComp   = $(this).data('n-comp');
        const suc     = $(this).data('suc');
        const importe = parseFloat($(this).data('importe'));
        const exacto  = $(this).data('exacto') === 1 || $(this).data('exacto') === '1';

        const saldo = fgaLoteActivo ? fgaLoteActivo.saldo : 0;
        const nuevoSaldo = saldo - importe;

        let aviso = '';
        if (!exacto) {
            aviso = '<div class="alert alert-warning small text-start mb-0 mt-2">' +
                '<i class="fa-solid fa-triangle-exclamation me-1"></i> ' +
                'El importe <strong>no coincide</strong> con el saldo pendiente. ' +
                'Después de vincular el saldo queda en <strong>' + fgaMoneda(nuevoSaldo) + '</strong>.' +
                '</div>';
        }

        Swal.fire({
            title: '¿Vincular este recibo?',
            html: '<div class="text-start small">' +
                '<div><strong>Recibo:</strong> ' + nComp + '</div>' +
                '<div><strong>Importe:</strong> ' + fgaMoneda(importe) + '</div>' +
                '<div><strong>Saldo actual del lote:</strong> ' + fgaMoneda(saldo) + '</div>' +
                '</div>' + aviso,
            icon: exacto ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, vincular',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#198754'
        }).then(function (res) {
            if (!res.isConfirmed) return;

            $.ajax({
                url: FGA_URL + '?action=vincular_recibo',
                type: 'POST',
                dataType: 'json',
                data: {
                    id_lote: fgaLoteActivo.id_lote,
                    nro_sucursal: suc,
                    n_comp_recibo: nComp,
                    tipo_match: exacto ? 'AUTO' : 'MANUAL'
                },
                success: function (r) {
                    if (!r.success) {
                        Swal.fire('Error', r.message || 'No se pudo vincular el recibo.', 'error');
                        return;
                    }
                    Swal.fire('¡Listo!', r.message, 'success');
                    fgaCargarRecibos();
                    fgaCargarResumen();
                },
                error: function (xhr) {
                    fgaError(xhr, 'No se pudo vincular el recibo.');
                }
            });
        });
    });

    // --- Desvincular recibo ---
    $('#modalFranqGaRecibos').on('click', '.fga-btn-desvincular', function () {
        const idVinculo = $(this).data('id-vinculo');
        const nComp     = $(this).data('n-comp');

        Swal.fire({
            title: '¿Desvincular el recibo?',
            html: 'Se va a quitar la imputación del recibo <strong>' + nComp + '</strong> a este lote.' +
                '<p class="small text-muted mt-2 mb-0">El recibo de Tango no se modifica: vuelve a quedar disponible ' +
                'para vincular y el saldo del lote se recalcula.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, desvincular',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then(function (res) {
            if (!res.isConfirmed) return;

            $.ajax({
                url: FGA_URL + '?action=desvincular_recibo',
                type: 'POST',
                dataType: 'json',
                data: { id_vinculo: idVinculo },
                success: function (r) {
                    if (!r.success) {
                        Swal.fire('Error', r.message || 'No se pudo desvincular.', 'error');
                        return;
                    }
                    Swal.fire('Desvinculado', r.message, 'success');
                    fgaCargarRecibos();
                    fgaCargarResumen();
                },
                error: function (xhr) {
                    fgaError(xhr, 'No se pudo desvincular el recibo.');
                }
            });
        });
    });
});
