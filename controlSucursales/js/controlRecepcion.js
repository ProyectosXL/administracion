/* ===================================
   CONTROL RECEPCIÓN EFECTIVO - JAVASCRIPT
   =================================== */

let sortColumn = null;
let sortDirection = 'asc';

const ICONO_OK = '<i class="bi bi-check-circle-fill icon-ok"></i>';

const estaMarcado = (row, columna) => row.querySelector(`.${columna} .bi-check-circle-fill`) !== null;

const validarFlujo = (row, accion) => {
    const estaRecibido = estaMarcado(row, 'col-recibido');
    const estaControlado = estaMarcado(row, 'col-controlado');

    switch(accion) {
        case 'recibido':
            // Recibido siempre se puede marcar si no está marcado
            return true;
        case 'controlado':
            if (!estaRecibido) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción no permitida',
                    text: 'Primero debe marcar como recibido'
                });
                return false;
            }
            return true;
        case 'cargado':
            if (!estaRecibido || !estaControlado) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción no permitida',
                    text: 'Debe marcar como recibido y controlado antes de cargar'
                });
                return false;
            }
            return true;
        default:
            return false;
    }
};

/**
 * Datos del comprobante tomados de los atributos data-* de la fila
 */
const datosComprobante = (row) => {
    const d = row.dataset;
    return {
        fecha: d.fechaIso,
        nroSucursal: d.sucursal,
        tipoComprobante: d.tipo,
        nroComprobante: d.ncomp,
        monto: d.monto,
        codCuenta: d.codCuenta,
        descripcionCuenta: d.descCuenta,
        observaciones: row.querySelector('.obs-input')?.value || ''
    };
}

/**
 * Reemplaza el checkbox por el ícono de OK y actualiza el resumen
 */
const marcarCelda = (row, columna, contador) => {
    const celda = row.querySelector(`.${columna}`);
    celda.innerHTML = ICONO_OK;
    celda.setAttribute('data-sort', '1');
    actualizarContador(contador, columna);
}

const actualizarContador = (idContador, columna) => {
    const contador = document.getElementById(idContador);
    if (contador) {
        contador.textContent = document.querySelectorAll(`#tablaControlRecepcion .${columna} .bi-check-circle-fill`).length;
    }
}

const marcarRecibido = (e) => {
    const row = e.closest('tr');

    if (!validarFlujo(row, 'recibido')) {
        e.checked = false;
        return;
    }

    e.disabled = true;

    $.ajax({
        type: 'POST',
        url: 'Controller/ControlEgresosController.php?accion=marcarRecibido',
        data: datosComprobante(row),
        success: function() {
            marcarCelda(row, 'col-recibido', 'statRecibidos');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Marcado como recibido correctamente',
                showConfirmButton: false,
                timer: 3000
            });
        },
        error: function(xhr) {
            e.checked = false;
            e.disabled = false;
            console.error('Error al marcar como recibido:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al marcar como recibido',
                footer: xhr.responseText || ''
            });
        }
    });
};

const marcarControlado = (e) => {
    const row = e.closest('tr');

    if (!validarFlujo(row, 'controlado')) {
        e.checked = false;
        return;
    }

    e.disabled = true;

    $.ajax({
        type: 'POST',
        url: 'Controller/ControlEgresosController.php?accion=controlTesoreria',
        data: datosComprobante(row),
        success: function(response) {
            if (response.success) {
                marcarCelda(row, 'col-controlado', 'statControlados');
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Marcado como controlado correctamente',
                    showConfirmButton: false,
                    timer: 3000
                });
            } else {
                e.checked = false;
                e.disabled = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    footer: response.debug ? JSON.stringify(response.debug) : ''
                });
            }
        },
        error: function(xhr) {
            e.checked = false;
            e.disabled = false;
            console.error('Error al marcar como controlado:', xhr.responseText);
            Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'Error al comunicarse con el servidor' });
        }
    });
};

const guardarObservaciones = (btn) => {
    const row = btn.closest('tr');
    const textarea = row.querySelector('.obs-input');

    if (!textarea.value.trim()) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'warning',
            title: 'Por favor, ingrese una observación',
            showConfirmButton: false,
            timer: 3000
        });
        return;
    }

    btn.disabled = true;

    $.ajax({
        type: 'POST',
        url: 'Controller/ControlEgresosController.php?accion=guardarObservaciones',
        data: {
            nroSucursal: row.dataset.sucursal,
            nroComprobante: row.dataset.ncomp,
            observaciones: textarea.value
        },
        success: function() {
            btn.remove();
            textarea.disabled = true;
        },
        error: function(xhr, status, error) {
            btn.disabled = false;
            console.error('Error:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al guardar las observaciones' });
        }
    });
};

/* ===================================
   VINCULAR RECIBO
   =================================== */

let currentRowData = null;
let debounceTimer;

const vincularRecibo = (btn) => {
    const row = btn.closest('tr');
    const d = row.dataset;

    currentRowData = {
        row: row,
        fecha: d.fecha,
        nroSucursal: d.sucursal,
        codComp: d.tipo,
        nComp: d.ncomp,
        monto: parseFloat(d.monto),
        codCta: d.codCuenta,
        montoFormateado: d.montoFormateado
    };

    const infoDiv = document.getElementById('infoComprobanteSeleccionado');
    infoDiv.innerHTML = `
        <strong>Comprobante a vincular:</strong><br>
        Sucursal: ${currentRowData.nroSucursal} - ${d.descSucursal}<br>
        Fecha: ${currentRowData.fecha} | Comp: ${currentRowData.codComp}-${currentRowData.nComp} | Monto: ${currentRowData.montoFormateado}
    `;
    infoDiv.style.display = 'block';

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVincularRecibo')).show();

    // Limpiar búsqueda anterior y cargar resultados iniciales
    document.getElementById('searchInput').value = '';
    buscarRecibos();
};

const buscarRecibos = async (searchTerm = '') => {
    try {
        const response = await fetch(`Controller/ControlEgresosController.php?accion=traerRecibosParaVincular&search=${encodeURIComponent(searchTerm)}`);
        const recibos = await response.json();

        const tablaBody = document.querySelector('#tablaRecibosVincular tbody');
        tablaBody.innerHTML = ''; // Limpiar tabla

        if (recibos.length === 0) {
            tablaBody.innerHTML = '<tr><td colspan="7" class="text-center">No se encontraron recibos.</td></tr>';
            return;
        }

        recibos.forEach(recibo => {
            const tr = document.createElement('tr');
            // Formatear la fecha
            const fecha = new Date(recibo.FECHA.date);
            const formattedDate = fecha.toLocaleDateString('es-ES');

            const formattedAmount = recibo.CANT_MONE.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

            let monedaBadge;
            if (recibo.COD_CTA === '100901') {
                monedaBadge = '<span class="badge bg-success">USD</span>';
            } else if (recibo.COD_CTA === '100101') {
                monedaBadge = '<span class="badge bg-secondary">ARS</span>';
            } else {
                monedaBadge = `<span class="badge bg-light text-dark border">${recibo.COD_CTA}</span>`;
            }

            tr.innerHTML = `
                <td>${formattedDate}</td>
                <td>${recibo.COD_COMP}</td>
                <td>${recibo.N_COMP}</td>
                <td>${formattedAmount}</td>
                <td>${monedaBadge}</td>
                <td>${recibo.LEYENDA}</td>
                <td>
                    <button class="btn btn-success btn-sm" onclick="seleccionarRecibo('${recibo.COD_COMP}', '${recibo.N_COMP}', ${recibo.CANT_MONE})">
                        <i class="bi bi-check-circle"></i>
                    </button>
                </td>
            `;
            tablaBody.appendChild(tr);
        });

    } catch (error) {
        console.error('Error al buscar recibos:', error);
        const tablaBody = document.querySelector('#tablaRecibosVincular tbody');
        tablaBody.innerHTML = '<tr><td colspan="7" class="text-center">Error al cargar los recibos.</td></tr>';
    }
};

const seleccionarRecibo = async (codCompVinculado, nCompVinculado, montoVinculado) => {
    // Validar el flujo antes de proceder
    if (!validarFlujo(currentRowData.row, 'cargado')) {
        return;
    }

    if (currentRowData.monto !== montoVinculado) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'Los montos no coinciden',
            showConfirmButton: false,
            timer: 3000
        });
        return;
    }

    Swal.fire({
        title: '¿Confirmar Vinculación?',
        text: `¿Está seguro de que desea vincular el comprobante ${currentRowData.nComp} con el recibo ${nCompVinculado}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, vincular',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const data = new FormData();
            data.append('original_cod_comp', currentRowData.codComp);
            data.append('original_n_comp', currentRowData.nComp);
            data.append('vinculado_cod_comp', codCompVinculado);
            data.append('vinculado_n_comp', nCompVinculado);
            data.append('nro_sucursal', currentRowData.nroSucursal);

            try {
                const response = await fetch('Controller/ControlEgresosController.php?accion=vincularRecibo', {
                    method: 'POST',
                    body: data
                });
                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Vinculado correctamente',
                        showConfirmButton: false,
                        timer: 2000
                    });

                    // Actualizar UI
                    const rowElement = currentRowData.row;
                    if (rowElement) {
                        rowElement.querySelector('.btn-vincular')?.remove();
                        rowElement.classList.add('row-completa');
                        marcarCelda(rowElement, 'col-cargado', 'statCargados');
                    }
                } else {
                    Swal.fire('Error', result.message || 'Ocurrió un error al vincular.', 'error');
                }
            } catch (error) {
                console.error('Error al vincular el recibo:', error);
                Swal.fire('Error', 'Ocurrió un error de comunicación al intentar vincular el recibo.', 'error');
            } finally {
                bootstrap.Modal.getInstance(document.getElementById('modalVincularRecibo'))?.hide();
            }
        }
    });
};

/* ===================================
   INICIALIZACIÓN
   =================================== */

$(document).ready(function () {
    $('#formFiltros').on('submit', function() {
        $("#boxLoading").addClass("loading");
    });

    // Búsqueda del modal de vincular (se registra una sola vez)
    $('#searchInput').on('keyup', function() {
        clearTimeout(debounceTimer);
        const termino = this.value;
        debounceTimer = setTimeout(() => buscarRecibos(termino), 500);
    });

    if ($('#tablaControlRecepcion').length === 0) {
        return;
    }

    document.querySelectorAll('#tablaControlRecepcion [title]').forEach(el => {
        new bootstrap.Tooltip(el, { container: 'body' });
    });

    setupSearch();
    setupSorting();
});

/**
 * Buscador sobre las filas de la tabla
 */
function setupSearch() {
    $('#buscarTabla').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();

        $('#tablaControlRecepcion tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.indexOf(searchTerm) !== -1);
        });
    });
}

/**
 * Ordenamiento al hacer click en los encabezados
 */
function setupSorting() {
    $('#tablaControlRecepcion thead th').each(function(index) {
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
    return $td.attr('data-sort') !== undefined ? $td.attr('data-sort') : $td.text().trim();
}

function sortTable(columnIndex) {
    const tbody = $('#tablaControlRecepcion tbody');
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

    $('#tablaControlRecepcion thead th').removeClass('sorting_asc sorting_desc');
    $('#tablaControlRecepcion thead th').eq(columnIndex).addClass(sortDirection === 'asc' ? 'sorting_asc' : 'sorting_desc');

    tbody.append(rows);
}
