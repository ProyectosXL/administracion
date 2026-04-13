/**
 * arqueoCaja.js - Lógica para la pestaña Arqueo vs Caja
 */

$(document).ready(function () {
    $('#btn-consultar-arqueoCaja').on('click', function () {
        consultarArqueoCaja();
    });

    $('#btn-exportar-arqueoCaja').on('click', function () {
        exportarExcelArqueo();
    });
});

/**
 * Realiza la consulta AJAX al controlador y renderiza la tabla
 */
function consultarArqueoCaja() {
    const fecha = $('#fecha-arqueoCaja').val();

    if (!fecha) {
        alert('Por favor, ingrese una fecha.');
        return;
    }

    const btnConsultar = $('#btn-consultar-arqueoCaja');
    btnConsultar.prop('disabled', true);
    mostrarLoading();

    $.ajax({
        url: 'Controller/arqueoCajaController.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'obtener_datos',
            fecha: fecha
        },
        success: function (response) {
            if (!response.success) {
                alert('Error: ' + response.message);
                return;
            }
            renderizarTablaArqueo(response.data);
        },
        error: function (xhr, status, error) {
            console.error('Error en AJAX arqueoCaja:', xhr.responseText);
            alert('Ocurrió un error inesperado al consultar los datos.');
        },
        complete: function () {
            ocultarLoading();
            btnConsultar.prop('disabled', false);
        }
    });
}

/**
 * Renderiza las filas de la tabla con los datos recibidos
 * @param {Array} data - Array de objetos con los datos de la vista
 */
function renderizarTablaArqueo(data) {
    const tbody = $('#tabla-arqueo-caja-body');
    tbody.empty();

    if (!data || data.length === 0) {
        tbody.append(
            '<tr><td colspan="7" class="text-center text-muted">No se encontraron registros para la fecha seleccionada.</td></tr>'
        );
    } else {
        data.forEach(function (row) {
            const diferencia = row.DIFERENCIA !== null ? row.DIFERENCIA : 0;
            const claseDiferencia = diferencia !== 0 ? 'text-danger fw-bold' : 'text-success';

            const camposNulos = row.SALDO_CAJA === null || row.SALDO_CIERRE === null || row.DIFERENCIA === null;
            const claseFilaWarning = camposNulos ? 'table-warning' : '';

            const celSaldoCaja    = row.SALDO_CAJA    !== null ? `<span class="text-end d-block">${formatCurrency(row.SALDO_CAJA)}</span>`    : '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i>Sin dato</span>';
            const celSaldoCierre  = row.SALDO_CIERRE  !== null ? `<span class="text-end d-block">${formatCurrency(row.SALDO_CIERRE)}</span>`  : '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i>Sin dato</span>';
            const celDiferencia   = row.DIFERENCIA    !== null ? `<span class="text-end d-block ${claseDiferencia}">${formatCurrency(row.DIFERENCIA)}</span>` : '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i>Sin dato</span>';

            const tr = `
                <tr class="${claseFilaWarning}">
                    <td>${row.FECHA ?? ''}</td>
                    <td>${row.NRO_SUCURSAL ?? ''}</td>
                    <td>${row.DESC_SUCURSAL ?? ''}</td>
                    <td>${row.COD_CTA_CUENTA_TESORERIA ?? ''}</td>
                    <td>${celSaldoCaja}</td>
                    <td>${celSaldoCierre}</td>
                    <td>${celDiferencia}</td>
                </tr>
            `;
            tbody.append(tr);
        });
    }

    // Mostrar tabla y botón exportar, ocultar mensaje inicial
    $('#wrapper-tabla-arqueoCaja').show();
    $('#msg-sin-datos-arqueoCaja').hide();
    $('#btn-exportar-arqueoCaja').show();

    // Limpiar búsqueda rápida
    $('#textBox-arqueoCaja').val('');
}

/**
 * Filtro de búsqueda rápida sobre la tabla
 */
function busquedaRapidaArqueo() {
    const input = document.getElementById('textBox-arqueoCaja');
    const filter = input.value.toUpperCase();
    const tbody = document.getElementById('tabla-arqueo-caja-body');
    const rows = tbody.getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        let visible = false;
        const cells = rows[i].getElementsByTagName('td');
        for (let j = 0; j < cells.length; j++) {
            if (cells[j] && cells[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                visible = true;
                break;
            }
        }
        rows[i].style.display = visible ? '' : 'none';
    }
}

/**
 * Exporta la tabla a Excel
 */
function exportarExcelArqueo() {
    if ($('#tabla-arqueo-caja-body tr').length === 0) {
        alert('No hay datos para exportar.');
        return;
    }

    const fecha = $('#fecha-arqueoCaja').val() || new Date().toISOString().slice(0, 10);

    $('#tabla-arqueo-caja').table2excel({
        exclude: '.noExport',
        name: 'Arqueo vs Caja',
        filename: 'Arqueo_vs_Caja_' + fecha,
        fileext: '.xlsx'
    });
}
