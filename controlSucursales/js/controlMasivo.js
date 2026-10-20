/* ===================================
   CONTROL MASIVO DE COBRANZA - JAVASCRIPT
   =================================== */

let sortColumn = null;
let sortDirection = 'asc';

// Antes se leía el medio de pago con value.split("-")[1], que siempre daba undefined,
// así que la validación de PROMO BANCO (valor negativo) nunca se ejecutó.
// Se deja desactivada para no cambiar el comportamiento.
const VALIDAR_PROMO_BANCO = false;

$(document).ready(function () {
    $("#selectSucursal").select2({ width: '200px' });

    $('#formFiltros').on('submit', function() {
        $("#boxLoading").addClass("loading");
    });

    if ($('#tablaControl').length === 0) {
        return;
    }

    $('#tablaControl [title]').tooltip({ container: 'body' });

    setupSearch();
    setupSorting();
    setupExcelExport();
    calcularTotales();
});

/* ===================================
   FORMATO Y CÁLCULOS
   =================================== */

const parseNumber = (number) => {
    number = parseInt(number);

    return number.toLocaleString('de-DE', {
        style: 'decimal',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });
}

/**
 * "- $1.234" / "$1.234" / "$-1.234" -> número entero (NaN si no se puede leer)
 */
const aNumero = (texto) => {
    const valor = String(texto).replace(/[$.\s]/g, "");

    if (valor.includes("-")) {
        return parseInt(valor.replace(/-/g, "")) * -1;
    }
    return parseInt(valor);
}

const formatearMonto = (numero) => {
    return (numero < 0) ? "- $" + parseNumber(numero * -1) : "$" + parseNumber(numero);
}

const inputControl = (tr) => tr.querySelector('.input-control');
const inputObservacion = (tr) => tr.querySelector('.input-observacion');

/**
 * Valor que se envía al controlador: el texto del input sin "$" ni "." (igual que antes)
 */
const importeControlFila = (tr) => inputControl(tr).value.replace(/[$.]/g, "");

const diferenciaFila = (tr) => aNumero(tr.dataset.sistema) - aNumero(inputControl(tr).value);

/**
 * Recalcula la diferencia de cada fila y los totales (footer y resumen)
 */
const calcularTotales = () => {
    let totalEnSistema = 0;
    let totalFisico = 0;
    let totalDiferencia = 0;

    document.querySelectorAll('#tablaControl tbody tr').forEach((tr) => {
        const diferencia = diferenciaFila(tr);
        const tdDiferencia = tr.querySelector('.td-diferencia');

        tdDiferencia.textContent = formatearMonto(diferencia);
        tdDiferencia.classList.toggle('con-diferencia', diferencia !== 0);

        totalEnSistema += aNumero(tr.dataset.sistema);
        totalFisico += aNumero(inputControl(tr).value);
        totalDiferencia += diferencia;
    });

    $('#totalEnSistema, #statSistema').text(formatearMonto(totalEnSistema));
    $('#totalFisico, #statControl').text(formatearMonto(totalFisico));
    $('#totalDiferencia, #statDiferencia').text(formatearMonto(totalDiferencia));
}

/**
 * Da formato al $ CONTROL cargado y recalcula las diferencias
 */
const calcularDiferecias = (input) => {
    if (VALIDAR_PROMO_BANCO && document.querySelector('#medioPago').value == "PROMO BANCO") {
        const valorFisico = input.value.replace(/[$.]/g, "");
        const btnControlar = document.querySelector('#controlar');

        if (!valorFisico.includes("-") && valorFisico != 0) {
            Swal.fire({
                icon: 'warning',
                title: 'El valor debe ser negativo',
                text: 'Por favor ingresá un valor negativo'
            });
            if (btnControlar) btnControlar.disabled = true;
            return;
        }
        if (btnControlar) btnControlar.disabled = false;
    }

    // Se quitan también los espacios para que "- $500" editado no quede como $NaN
    const valor = input.value.replace(/[$.\s]/g, "");
    input.value = (valor < 0) ? "- $" + parseNumber(valor * -1) : "$" + parseNumber(valor);

    calcularTotales();
}

/* ===================================
   GUARDAR / CONTROLAR
   =================================== */

/**
 * Datos de todas las filas (incluye las ocultas por el buscador y las ya verificadas, como antes)
 */
const armarData = () => {
    const data = [];

    document.querySelectorAll('#tablaControl tbody tr').forEach((tr) => {
        data.push({
            id: tr.dataset.id,
            importeControl: importeControlFila(tr),
            observaciones: inputObservacion(tr).value
        });
    });

    return data;
}

const enviarControlDiario = (verificado, data) => {
    $("#boxLoading").addClass("loading");

    return $.ajax({
        url: 'Controller/ControlDiario.php?verificado=' + verificado,
        type: 'POST',
        data: {
            data
        }
    }).fail(function () {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo guardar',
            text: 'Ocurrió un error al guardar los importes. Intentá nuevamente.'
        });
    }).always(function () {
        $("#boxLoading").removeClass("loading");
    });
}

const guardar = () => {
    enviarControlDiario(0, armarData()).done(function () {
        Swal.fire({
            icon: 'success',
            title: 'Guardado',
            text: 'Se guardó correctamente'
        }).then(() => {
            location.reload();
        });
    });
}

const controlar = () => {
    const filas = document.querySelectorAll('#tablaControl tbody tr');
    let error = false;
    let inputsCargados = true;

    filas.forEach((tr) => {
        const importeControl = importeControlFila(tr);

        if (importeControl == "" || importeControl == 0) inputsCargados = false;
        if (diferenciaFila(tr) !== 0) error = true;
    });

    if (!inputsCargados) {
        Swal.fire({
            icon: 'warning',
            title: 'Hay valores sin cargar',
            text: 'Por favor completá todos los valores'
        });
        return;
    }

    const data = armarData();

    if (error) {
        Swal.fire({
            icon: 'warning',
            title: '¿Querés realizar el control con diferencias?',
            showDenyButton: true,
            confirmButtonText: 'Confirmar',
            denyButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                confirmarControl(data);
            } else if (result.isDenied) {
                Swal.fire('El control fue cancelado', '', 'info');
            }
        });
    } else {
        confirmarControl(data);
    }
}

const confirmarControl = (data) => {
    enviarControlDiario(1, data).done(function () {
        Swal.fire('¡Controlado!', '', 'success').then(() => {
            location.reload();
        });
    });
}

/* ===================================
   DETALLE DEL DÍA
   =================================== */

const SIN_MEDIO = 'SIN MEDIO DE PAGO';
let detalleActual = [];

const escapeHtml = (texto) => $('<div>').text(texto == null ? '' : String(texto)).html();

const formatearFecha = (fechaIso) => fechaIso.split('-').reverse().join('/');

const datosPantalla = () => {
    const tabla = document.querySelector('#tablaControl');
    return {
        sucursal: tabla.dataset.sucursal,
        descSucursal: tabla.dataset.descSucursal,
        medioPago: tabla.dataset.medioPago,
        desde: tabla.dataset.desde,
        hasta: tabla.dataset.hasta
    };
}

const verDetalleDia = (fecha) => {
    const pantalla = datosPantalla();

    $('#modalDetalleDiaLabel').text('Detalle del ' + formatearFecha(fecha) + ' · ' + pantalla.descSucursal);
    $('#detalleCargando').show();
    $('#detalleContenido').hide();
    $('#modalDetalleDia').modal('show');

    $.ajax({
        url: 'Controller/DetalleCobranzaController.php',
        type: 'POST',
        dataType: 'json',
        data: { action: 'detalle', sucursal: pantalla.sucursal, fecha }
    }).done(function (resp) {
        if (!resp.success) {
            $('#modalDetalleDia').modal('hide');
            Swal.fire({ icon: 'error', title: 'No se pudo traer el detalle', text: resp.message });
            return;
        }
        renderDetalle(resp.detalle, resp.tabla, pantalla.medioPago);
        $('#detalleCargando').hide();
        $('#detalleContenido').show();
    }).fail(function () {
        $('#modalDetalleDia').modal('hide');
        Swal.fire({ icon: 'error', title: 'No se pudo traer el detalle', text: 'Intentá nuevamente.' });
    });
}

const renderDetalle = (detalle, tabla, medioSeleccionado) => {
    detalleActual = detalle.map((d) => ({ ...d, MEDIO_PAGO: d.MEDIO_PAGO || SIN_MEDIO }));

    // Por medio de pago: calculado vs grabado
    const medios = {};
    const medio = (nombre) => medios[nombre] || (medios[nombre] = { calculado: 0, comprobantes: 0, enTabla: null, verificado: false });

    detalleActual.forEach((d) => {
        const m = medio(d.MEDIO_PAGO);
        m.calculado += d.IMPORTE;
        m.comprobantes++;
    });
    tabla.forEach((t) => {
        const m = medio(t.MEDIO_PAGO);
        m.enTabla = (m.enTabla || 0) + (t.IMPORTE_SISTEMA || 0);
        m.verificado = m.verificado || t.VERIFICADO;
    });

    const nombresMedios = Object.keys(medios).sort((a, b) => (a === SIN_MEDIO) - (b === SIN_MEDIO) || a.localeCompare(b));
    let filasMedio = '';
    let totalCalculado = 0;
    let totalTabla = 0;
    let hayDiferencias = false;

    nombresMedios.forEach((nombre) => {
        const m = medios[nombre];
        const calculado = Math.round(m.calculado);
        const enTabla = m.enTabla === null ? null : Math.round(m.enTabla);
        const diferencia = (enTabla || 0) - calculado;
        let estado;

        if (nombre === SIN_MEDIO) {
            estado = '<span class="badge-estado badge-error">La cuenta no tiene medio de pago: no se graba</span>';
        } else if (enTabla === null) {
            estado = '<span class="badge-estado badge-error">Sin fila en la tabla</span>';
        } else if (diferencia !== 0 && m.verificado) {
            estado = '<span class="badge-estado badge-warn">Difiere (verificado, no se reprocesa)</span>';
        } else if (diferencia !== 0) {
            estado = '<span class="badge-estado badge-warn">Difiere: reprocesar</span>';
        } else {
            estado = '<span class="badge-estado badge-ok">OK' + (m.verificado ? ' · verificado' : '') + '</span>';
        }
        if (diferencia !== 0 || enTabla === null) hayDiferencias = true;

        totalCalculado += calculado;
        totalTabla += enTabla || 0;

        filasMedio += `
            <tr class="${nombre === medioSeleccionado ? 'fila-seleccionada' : ''}">
                <td><a href="#" class="link-medio" data-medio="${escapeHtml(nombre)}">${escapeHtml(nombre)}</a></td>
                <td class="td-num">${m.comprobantes}</td>
                <td class="td-num">${formatearMonto(calculado)}</td>
                <td class="td-num">${enTabla === null ? '—' : formatearMonto(enTabla)}</td>
                <td class="td-num ${diferencia !== 0 ? 'con-diferencia' : ''}">${formatearMonto(diferencia)}</td>
                <td>${estado}</td>
            </tr>`;
    });

    filasMedio += `
        <tr class="fila-total">
            <td>TOTAL</td>
            <td></td>
            <td class="td-num">${formatearMonto(totalCalculado)}</td>
            <td class="td-num">${formatearMonto(totalTabla)}</td>
            <td class="td-num">${formatearMonto(totalTabla - totalCalculado)}</td>
            <td></td>
        </tr>`;
    $('#detallePorMedio tbody').html(filasMedio);

    $('#detalleAlertas').html(hayDiferencias
        ? '<div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle"></i> Hay medios de pago donde lo calculado no coincide con lo grabado.</div>'
        : '');

    // Por cuenta de tesorería
    const cuentas = {};
    detalleActual.forEach((d) => {
        const clave = d.MEDIO_PAGO + '|' + (d.COD_CTA || '');
        const c = cuentas[clave] || (cuentas[clave] = { medio: d.MEDIO_PAGO, cuenta: d.COD_CTA, desc: d.DESC_CTA, importe: 0, comprobantes: 0 });
        c.importe += d.IMPORTE;
        c.comprobantes++;
    });

    const filasCuenta = Object.values(cuentas).map((c) => `
        <tr class="${c.medio === medioSeleccionado ? 'fila-seleccionada' : ''}">
            <td>${escapeHtml(c.medio)}</td>
            <td>${escapeHtml(c.cuenta || '—')}</td>
            <td>${escapeHtml(c.desc)}</td>
            <td class="td-num">${c.comprobantes}</td>
            <td class="td-num">${formatearMonto(Math.round(c.importe))}</td>
        </tr>`).join('');
    $('#detallePorCuenta tbody').html(filasCuenta || '<tr><td colspan="5" class="text-center">Sin movimientos</td></tr>');

    // Comprobantes, filtrados por medio (arranca en el medio de la pantalla si tiene movimientos)
    const opciones = ['<option value="">Todos los medios</option>']
        .concat(nombresMedios.filter((n) => medios[n].comprobantes > 0).map((n) => `<option value="${escapeHtml(n)}">${escapeHtml(n)}</option>`));
    $('#detalleFiltroMedio').html(opciones.join(''))
        .val(medios[medioSeleccionado] && medios[medioSeleccionado].comprobantes > 0 ? medioSeleccionado : '');
    renderComprobantes();
}

const renderComprobantes = () => {
    const filtro = $('#detalleFiltroMedio').val();
    const filas = detalleActual.filter((d) => !filtro || d.MEDIO_PAGO === filtro);
    let total = 0;

    const html = filas.map((d) => {
        total += d.IMPORTE;
        return `
            <tr>
                <td>${escapeHtml(d.T_COMP)} ${escapeHtml(d.N_COMP)}</td>
                <td>${escapeHtml(d.MEDIO_PAGO)}</td>
                <td>${escapeHtml(d.COD_CTA || '—')}${d.DESC_CTA ? ' · ' + escapeHtml(d.DESC_CTA) : ''}</td>
                <td class="td-num">${d.COTIZACION_TC ? '$' + d.COTIZACION_TC.toLocaleString('de-DE') : ''}</td>
                <td class="td-num">${formatearMonto(Math.round(d.IMPORTE))}</td>
            </tr>`;
    }).join('');

    $('#detalleComprobantes tbody').html(html || '<tr><td colspan="5" class="text-center">Sin comprobantes</td></tr>');
    $('#detalleTotalComprobantes').text(formatearMonto(Math.round(total)));
}

$(document).on('change', '#detalleFiltroMedio', renderComprobantes);

$(document).on('click', '#detallePorMedio .link-medio', function (e) {
    e.preventDefault();
    const medio = $(this).data('medio');
    if ($('#detalleFiltroMedio option').filter(function () { return this.value === medio; }).length) {
        $('#detalleFiltroMedio').val(medio);
        renderComprobantes();
        document.querySelector('#detalleComprobantes').scrollIntoView({ behavior: 'smooth' });
    }
});

/* ===================================
   REPROCESAR
   =================================== */

const reprocesar = () => {
    const pantalla = datosPantalla();

    Swal.fire({
        icon: 'question',
        title: '¿Reprocesar el $ sistema?',
        html: `Se recalcula el <strong>$ sistema</strong> de <strong>${escapeHtml(pantalla.descSucursal)}</strong>
               del ${formatearFecha(pantalla.desde)} al ${formatearFecha(pantalla.hasta)}, en <strong>todos los medios de pago</strong>.<br><br>
               <small>Los días verificados no se modifican y no se crean días que falten.
               El $ control y las observaciones no se tocan.</small>`,
        showCancelButton: true,
        confirmButtonText: 'Reprocesar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;

        $("#boxLoading").addClass("loading");

        $.ajax({
            url: 'Controller/DetalleCobranzaController.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'reprocesar', sucursal: pantalla.sucursal, desde: pantalla.desde, hasta: pantalla.hasta }
        }).done(function (resp) {
            $("#boxLoading").removeClass("loading");

            if (!resp.success) {
                Swal.fire({ icon: 'error', title: 'No se pudo reprocesar', text: resp.message });
                return;
            }

            let html = 'No hubo cambios: el $ sistema ya estaba actualizado.';
            if (resp.cambios.length) {
                html = `<p>Se actualizaron ${resp.cambios.length} registro(s):</p>
                    <table class="tabla-detalle tabla-cambios">
                        <thead><tr><th>Fecha</th><th>Medio</th><th class="th-num">Antes</th><th class="th-num">Después</th></tr></thead>
                        <tbody>${resp.cambios.map((c) => `
                            <tr>
                                <td>${formatearFecha(c.FECHA)}</td>
                                <td>${escapeHtml(c.MEDIO_PAGO)}</td>
                                <td class="td-num">${c.ANTES === null ? '—' : formatearMonto(c.ANTES)}</td>
                                <td class="td-num">${c.DESPUES === null ? '—' : formatearMonto(c.DESPUES)}</td>
                            </tr>`).join('')}
                        </tbody>
                    </table>`;
            }

            Swal.fire({ icon: 'success', title: 'Reprocesado', html, width: 700 }).then(() => {
                location.reload();
            });
        }).fail(function () {
            $("#boxLoading").removeClass("loading");
            Swal.fire({ icon: 'error', title: 'No se pudo reprocesar', text: 'Ocurrió un error al ejecutar el SP. Intentá nuevamente.' });
        });
    });
}

/* ===================================
   EXPORTAR A EXCEL
   =================================== */

/**
 * Se exporta una copia de la tabla con los inputs reemplazados por su valor actual
 * (table2excel no exporta inputs)
 */
function setupExcelExport() {
    $("#btnExport").click(function (e) {
        e.preventDefault();

        const originales = $('#tablaControl input');
        const copia = $('#tablaControl').clone();

        copia.find('input').each(function (i) {
            $(this).replaceWith(document.createTextNode(originales.eq(i).val()));
        });

        copia.table2excel({
            exclude: ".noExport",
            name: "Control Masivo de Cobranza",
            filename: "Control Masivo de Cobranza",
            fileext: ".xlsx"
        });
    });
}

/* ===================================
   BUSCADOR Y ORDENAMIENTO
   =================================== */

/**
 * Buscador sobre las filas de la tabla (incluye lo escrito en los inputs)
 */
function setupSearch() {
    $('#searchInput').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();

        $('#tablaControl tbody tr').each(function() {
            const valoresInputs = $(this).find('input').map(function () { return $(this).val(); }).get().join(' ');
            const rowText = ($(this).text() + ' ' + valoresInputs).toLowerCase();
            $(this).toggle(rowText.indexOf(searchTerm) !== -1);
        });
    });
}

/**
 * Ordenamiento al hacer click en los encabezados
 */
function setupSorting() {
    $('#tablaControl thead th').each(function(index) {
        if ($(this).hasClass('no-sort')) {
            return;
        }
        $(this).on('click', function() {
            sortTable(index);
        });
    });
}

function valorOrden(td) {
    const $td = $(td);

    if ($td.attr('data-sort') !== undefined) {
        return $td.attr('data-sort');
    }

    const texto = $td.find('input').length ? $td.find('input').val() : $td.text().trim();

    // $ CONTROL y DIFERENCIA se ordenan por número
    if ($td.hasClass('td-num')) {
        const numero = aNumero(texto);
        return isNaN(numero) ? '' : String(numero);
    }
    return texto;
}

function sortTable(columnIndex) {
    const tbody = $('#tablaControl tbody');
    const rows = tbody.find('tr').toArray();

    if (sortColumn === columnIndex) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn = columnIndex;
        sortDirection = 'asc';
    }

    rows.sort(function(a, b) {
        const aVal = valorOrden($(a).find('td')[columnIndex]);
        const bVal = valorOrden($(b).find('td')[columnIndex]);

        const aNum = Number(aVal);
        const bNum = Number(bVal);

        let resultado;
        if (aVal !== '' && bVal !== '' && !isNaN(aNum) && !isNaN(bNum)) {
            resultado = aNum - bNum;
        } else {
            resultado = aVal.localeCompare(bVal, 'es', { numeric: true });
        }

        return sortDirection === 'asc' ? resultado : -resultado;
    });

    $('#tablaControl thead th').removeClass('sorting_asc sorting_desc');
    $('#tablaControl thead th').eq(columnIndex).addClass(sortDirection === 'asc' ? 'sorting_asc' : 'sorting_desc');

    tbody.append(rows);
}

/* ===================================
   ENTORNO
   =================================== */

/**
 * Cambia el entorno (Argentina/Uruguay).
 * Esta pantalla usa cambiarEntornoTasky.php ('uy' -> tabla RO_T_VENTA_DIARIA_SUCURSALES_UY),
 * no cambiarEntorno.php ('suc_uy'), que no cambia la tabla que se consulta acá.
 * Igual que el toggle anterior: desde 'uy' vuelve a Argentina; desde cualquier otro entorno pasa a 'uy'.
 */
function cambiarEntornoCustom(container) {
    const nuevoEntorno = ($(container).data('entorno-actual') === 'uy') ? 0 : 1;

    $("#boxLoading").addClass("loading");

    $.ajax({
        url: "Controller/cambiarEntornoTasky.php",
        method: "POST",
        data: { entorno: nuevoEntorno },
        success: function () {
            location.reload();
        },
        error: function() {
            $("#boxLoading").removeClass("loading");
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo cambiar el entorno. Por favor intentá nuevamente.'
            });
        }
    });
}
