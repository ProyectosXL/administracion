/**
 * ventaVsCobranza.js - Lógica para la pestaña de Venta vs Cobranza
 */

$(document).ready(function() {
    // Solo ejecutar si estamos en la pestaña de venta vs cobranza
    if ($('#tabla-venta-vs-cobranza').length === 0) {
        return;
    }

    // Event listener para exportar
    $('#btn-exportar-vvc').on('click', function() {
        exportarExcelVVC();
    });

    console.log('Módulo Venta vs Cobranza cargado correctamente');
});

/**
 * Búsqueda rápida en la tabla
 */
function busquedaRapida() {
    const input = document.getElementById("textBox-vvc");
    const filter = input.value.toUpperCase();
    const table = document.getElementById("tabla-vvc-body");
    const tr = table.getElementsByTagName("tr");

    for (let i = 0; i < tr.length; i++) {
        let visible = false;
        const td = tr[i].getElementsByTagName("td");

        for (let j = 0; j < td.length; j++) {
            if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                visible = true;
                break;
            }
        }

        if (visible) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

/**
 * Confirmar exclusión de venta vs cobranza
 * @param {HTMLInputElement} checkbox - El checkbox que disparó el evento
 */
function confirmarVentaVsCobranza(checkbox) {
    checkbox.disabled = true;

    const row = checkbox.closest('tr');
    const cells = row.querySelectorAll('td');
    const nroSucursal = cells[1].textContent.trim();
    const nroComprobante = cells[4].textContent.trim();

    $.ajax({
        url: "Controller/ventaVsCobranzaController.php?accion=confirmarVentaVsCobranza",
        type: "POST",
        data: {
            nroSucursal: nroSucursal,
            nroComprobante: nroComprobante
        },
        success: function (response) {
            console.log('Venta vs Cobranza confirmada:', response);
            // Recargar manteniendo la pestaña activa
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('tab', 'ventaVsCobranza');
            window.location.href = currentUrl.toString();
        },
        error: function(xhr, status, error) {
            console.error('Error al confirmar:', error);
            checkbox.disabled = false;
            alert('Error al confirmar. Por favor, intente nuevamente.');
        }
    });
}

/**
 * Controlar saltos de línea en textarea
 * @param {HTMLTextAreaElement} textarea - El textarea que disparó el evento
 */
function checkLineBreak(textarea) {
    const maxLength = 26;
    let lineBreakAdded = textarea.dataset.lineBreakAdded === 'true';

    if (textarea.value.length >= maxLength && !lineBreakAdded) {
        textarea.value = textarea.value.replace(new RegExp('(.{' + maxLength + '})', 'g'), '$1\n');
        textarea.dataset.lineBreakAdded = 'true';
    }

    if (textarea.value.length < maxLength) {
        textarea.dataset.lineBreakAdded = 'false';
    }
}

/**
 * Exportar tabla a Excel
 */
function exportarExcelVVC() {
    if ($('#tabla-venta-vs-cobranza').length === 0) {
        alert('No hay datos para exportar');
        return;
    }

    $('#tabla-venta-vs-cobranza').table2excel({
        exclude: ".noExport",
        name: "Venta vs Cobranza",
        filename: "Venta_vs_Cobranza_" + new Date().toISOString().slice(0, 10),
        fileext: ".xlsx"
    });
}
