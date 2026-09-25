/* ===================================
   CARGA GASTOS TESORERÍA - JAVASCRIPT
   =================================== */

let sortColumn = null;
let sortDirection = 'asc';

$(document).ready(function() {
    setupSpinnerFiltros();

    if ($('#tablaGastosTesoreria').length === 0) {
        return;
    }

    setupSearch();
    setupSorting();
    setupExcelExport();
    calcularTotales();
});

/**
 * Muestra el spinner al aplicar filtros
 */
function setupSpinnerFiltros() {
    $('#formFiltros').on('submit', function() {
        $("#boxLoading").addClass("loading");
    });
}

/**
 * Buscador sobre las filas de la tabla
 */
function setupSearch() {
    $('#searchInput').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();

        $('#tablaGastosTesoreria tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.indexOf(searchTerm) !== -1);
        });

        calcularTotales();
    });
}

/**
 * Ordenamiento al hacer click en los encabezados
 */
function setupSorting() {
    $('#tablaGastosTesoreria thead th').each(function(index) {
        $(this).on('click', function() {
            sortTable(index);
        });
    });
}

function sortTable(columnIndex) {
    const tbody = $('#tablaGastosTesoreria tbody');
    const rows = tbody.find('tr').toArray();

    if (sortColumn === columnIndex) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn = columnIndex;
        sortDirection = 'asc';
    }

    rows.sort(function(a, b) {
        const aCell = $($(a).find('td')[columnIndex]);
        const bCell = $($(b).find('td')[columnIndex]);

        const aVal = aCell.attr('data-value') !== undefined ? aCell.attr('data-value') : aCell.text().trim();
        const bVal = bCell.attr('data-value') !== undefined ? bCell.attr('data-value') : bCell.text().trim();

        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);

        let resultado;
        if (!isNaN(aNum) && !isNaN(bNum)) {
            resultado = aNum - bNum;
        } else {
            resultado = aVal.localeCompare(bVal, 'es');
        }

        return sortDirection === 'asc' ? resultado : -resultado;
    });

    $('#tablaGastosTesoreria thead th').removeClass('sorting_asc sorting_desc');
    $('#tablaGastosTesoreria thead th').eq(columnIndex).addClass(sortDirection === 'asc' ? 'sorting_asc' : 'sorting_desc');

    tbody.append(rows);
}

/**
 * Suma cada cuenta de las filas visibles y actualiza footer y resumen
 */
function calcularTotales() {
    const totales = {};
    let totalGeneral = 0;

    $('#tablaGastosTesoreria tbody tr:visible').each(function() {
        $(this).find('.td-cuenta').each(function() {
            const col = $(this).data('col');
            const valor = parseFloat($(this).attr('data-value')) || 0;
            totales[col] = (totales[col] || 0) + valor;
        });
        totalGeneral += parseFloat($(this).find('.td-total').attr('data-value')) || 0;
    });

    $('#tablaGastosTesoreria tfoot .total-cuenta').each(function() {
        const col = $(this).data('col');
        $(this).text(formatearMoneda(totales[col] || 0));
    });

    $('#totalGeneral').text(formatearMoneda(totalGeneral));
    $('#statTotal').text(formatearMoneda(totalGeneral));
}

function formatearMoneda(valor) {
    if (isNaN(valor)) valor = 0;
    return '$' + parseNumber(valor);
}

const parseNumber = (number) => {
    return Math.round(number).toLocaleString('de-DE', {
        style: 'decimal',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });
}

/**
 * Exportación a Excel (excluye la columna CARGADO)
 */
function setupExcelExport() {
    $("#btnExport").click(function(e) {
        e.preventDefault();

        const periodo = $('#periodoArchivo').text() || 'sin_periodo';

        $("#tablaGastosTesoreria").table2excel({
            exclude: ".noExport",
            name: "Gastos Tesoreria",
            filename: `Gastos_Tesoreria_${periodo}`,
            fileext: ".xlsx"
        });
    });
}

/**
 * Arma el detalle por cuenta de una fila (se guarda como foto del período)
 */
const armarDataFila = (tr) => {
    const data = {};

    $('#tablaGastosTesoreria thead .th-cuenta').each(function() {
        const col = $(this).data('col');
        const valor = parseFloat($(tr).find(`.td-cuenta[data-col="${col}"]`).attr('data-value')) || 0;
        data[$(this).text().trim()] = String(Math.round(valor));
    });

    return data;
}

/**
 * Registra una sucursal como cargada en el período
 */
const enviarControl = (input) => {
    const tr = input.closest('tr');
    const nroSucursal = tr.dataset.sucursal;
    const periodo = document.querySelector("#periodo").textContent;

    input.disabled = true;

    return $.ajax({
        type: "POST",
        url: "Controller/gastosTesoreriaController.php?accion=checkControl",
        data: {
            nroSucursal: nroSucursal,
            periodo: periodo,
            data: armarDataFila(tr)
        }
    }).done(function() {
        input.checked = true;
        tr.classList.add('row-cargada');
        $(tr).find('.check-cargado span').text('Cargado');
        actualizarCantidadCargadas();
    }).fail(function() {
        input.checked = false;
        input.disabled = false;
    });
}

const checkControl = (input) => {
    if (!input.checked) {
        return;
    }

    enviarControl(input).fail(function() {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo guardar',
            text: 'Ocurrió un error al marcar la sucursal como cargada. Intentá nuevamente.'
        });
    });
}

/**
 * Marca como cargadas todas las sucursales pendientes (omite las ya cargadas)
 */
const checkMasivo = () => {
    const pendientes = Array.from(document.querySelectorAll('#tablaGastosTesoreria .checkControl'))
        .filter(input => !input.disabled && !input.checked);

    if (pendientes.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin pendientes',
            text: 'Todas las sucursales ya están marcadas como cargadas en este período.'
        });
        return;
    }

    Swal.fire({
        icon: 'question',
        title: '¿Marcar todas como cargadas?',
        html: `Se van a marcar <strong>${pendientes.length}</strong> sucursales pendientes.<br>Esta acción no se puede deshacer.`,
        showCancelButton: true,
        confirmButtonText: 'Sí, marcar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#10b981'
    }).then(async (result) => {
        if (!result.isConfirmed) {
            return;
        }

        $("#boxLoading").addClass("loading");

        let errores = 0;
        for (const input of pendientes) {
            try {
                await enviarControl(input);
            } catch (e) {
                errores++;
            }
        }

        $("#boxLoading").removeClass("loading");

        if (errores > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Proceso con errores',
                text: `${errores} sucursal(es) no se pudieron marcar. Revisalas e intentá nuevamente.`
            });
        } else {
            Swal.fire({
                icon: 'success',
                title: 'Listo',
                text: 'Todas las sucursales pendientes quedaron marcadas como cargadas.',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}

const actualizarCantidadCargadas = () => {
    $('#statCargadas').text($('#tablaGastosTesoreria .checkControl:checked').length);
}

/**
 * Cambia el entorno (Argentina/Uruguay)
 */
function cambiarEntornoCustom(container) {
    const activeFlag = $(container).find('.toggle-flag.active');
    const nuevoEntorno = (activeFlag.data('entorno') === 'central') ? 1 : 0;

    $("#boxLoading").addClass("loading");

    $.ajax({
        url: "Controller/cambiarEntorno.php",
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
                text: 'No se pudo cambiar el entorno. Por favor intente nuevamente.'
            });
        }
    });
}
