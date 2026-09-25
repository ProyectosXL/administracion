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
