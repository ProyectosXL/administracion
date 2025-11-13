
const validarFlujo = (row, accion) => {
    const cells = row.querySelectorAll('td');
    const recibidoCell = cells[8];
    const controladoCell = cells[9];
    const cargadoCell = cells[10];

    const estaRecibido = recibidoCell.querySelector('.bi-check-circle-fill') !== null;
    const estaControlado = controladoCell.querySelector('.bi-check-circle-fill') !== null;
    const estaCargado = cargadoCell.querySelector('.bi-check-circle-fill') !== null;

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

const marcarRecibido = async (e) => {
    try {
        // Desmarcar el checkbox si la validación falla
        if (!validarFlujo(e.closest('tr'), 'recibido')) {
            e.checked = false;
            return;
        }

        const row = e.closest('tr');
        const cells = row.querySelectorAll('td');

        // Convertir fecha de dd/mm/yyyy a yyyy-mm-dd
        const fechaParts = cells[0].textContent.trim().split('/');
        const fechaFormateada = `${fechaParts[2]}-${fechaParts[1]}-${fechaParts[0]}`;
        
        const data = {
            fecha: fechaFormateada,
            nroSucursal: cells[1].textContent.trim(),
            tipoComprobante: cells[3].textContent.trim(),
            nroComprobante: cells[4].getAttribute('data-ncomp-original') || cells[4].textContent.trim(),
            monto: cells[5].textContent.replace(/[$.]/g, '').trim(),
            codCuenta: row.querySelector('[data-cod-cuenta]').getAttribute('data-cod-cuenta'),
            descripcionCuenta: row.querySelector('[data-desc-cuenta]').getAttribute('data-desc-cuenta'),
            observaciones: cells[11].querySelector('textarea')?.value || ''
        };

        console.log('Datos enviados para marcar como recibido:', data);

        $.ajax({
            type: 'POST',
            url: 'Controller/ControlEgresosController.php?accion=marcarRecibido',
            data: data,
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                cells[8].innerHTML = '<i class="bi bi-check-circle-fill text-success fs-4"></i>';
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Marcado como recibido correctamente',
                    showConfirmButton: false,
                    timer: 3000
                });
            },
            error: function(xhr, status, error) {
                e.checked = false; // Desmarcar el checkbox si hay error
                console.error('Error completo:', {xhr, status, error, responseText: xhr.responseText});
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Error', 
                    text: 'Error al marcar como recibido',
                    footer: xhr.responseText || ''
                });
            }
        });
    } catch (error) {
        e.checked = false; // Desmarcar el checkbox si hay error
        console.error('Error al marcar como recibido:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al marcar como recibido' });
    }
};

const marcarControlado = (e) => {
    // Validar el flujo antes de proceder
    if (!validarFlujo(e.closest('tr'), 'controlado')) {
        e.checked = false;
        return;
    }

    const row = e.closest('tr');
    const cells = row.querySelectorAll('td');
    
    // Convertir fecha de dd/mm/yyyy a yyyy-mm-dd
    const fechaParts = cells[0].textContent.trim().split('/');
    const fechaFormateada = `${fechaParts[2]}-${fechaParts[1]}-${fechaParts[0]}`;
    
    const data = {
        fecha: fechaFormateada,
        nroSucursal: cells[1].textContent.trim(),
        tipoComprobante: cells[3].textContent.trim(),
        nroComprobante: cells[4].getAttribute('data-ncomp-original') || cells[4].textContent.trim(),
        codCuenta: row.querySelector('[data-cod-cuenta]').getAttribute('data-cod-cuenta'),
        descripcionCuenta: row.querySelector('[data-desc-cuenta]').getAttribute('data-desc-cuenta'),
        monto: cells[5].textContent.replace(/[$.]/g, '').trim(),
        observaciones: cells[11].querySelector('textarea')?.value || ''
    };

    console.log('Datos enviados:', data);

    $.ajax({
        type: 'POST',
        url: 'Controller/ControlEgresosController.php?accion=controlTesoreria',
        data: data,
        success: function(response) {
            console.log('Respuesta completa del servidor:', response);
            
            if(response.success) {
                cells[9].innerHTML = '<i class="bi bi-check-circle-fill text-success fs-4"></i>';
                Swal.fire({ icon: 'success', title: 'Éxito', text: 'Marcado como controlado correctamente' });
            } else {
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Error', 
                    text: response.message,
                    footer: response.debug ? JSON.stringify(response.debug) : ''
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error completo:', {xhr, status, error, responseText: xhr.responseText});
            Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'Error al comunicarse con el servidor' });
        }
    });
};

const guardarObservaciones = async (btn) => {
    try {
        const row = btn.closest('tr');
        const cells = row.querySelectorAll('td');
        const textarea = cells[10].querySelector('textarea');
        
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

        const data = {
            nroSucursal: cells[1].textContent,
            nroComprobante: cells[4].textContent,
            observaciones: textarea.value
        };

        $.ajax({
            type: 'POST',
            url: 'Controller/ControlEgresosController.php?accion=guardarObservaciones',
            data: data,
            success: function(response) {
                btn.style.display = 'none';
                textarea.disabled = true;
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error al guardar las observaciones' });
            }
        });
    } catch (error) {
        console.error('Error al guardar observaciones:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al guardar las observaciones' });
    }
};

// Event listener para el botón de filtrar
document.getElementById('btnFiltrarControlRecepcion').addEventListener('click', (e) => {
    e.preventDefault();
    const desde = document.getElementById('desde').value;
    const hasta = document.getElementById('hasta').value;
    const estado = document.getElementById('selectEstado').value;

    if (!desde || !hasta) {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Por favor, seleccione fechas válidas' });
        return;
    }

    window.location.href = `?desde=${desde}&hasta=${hasta}&selectEstado=${estado}`;
});

let currentRowData = null;
let debounceTimer;

const vincularRecibo = (btn) => {
    const row = btn.closest('tr');
    const cells = row.querySelectorAll('td');
    currentRowData = {
        fecha: cells[0].textContent.trim(),
        nroSucursal: cells[1].textContent.trim(),
        codComp: cells[3].textContent.trim(),
        nComp: cells[4].textContent, // No usar trim() aquí
        monto: parseFloat(cells[5].textContent.replace(/[$.]/g, '').replace(',', '.')),
        codCta: row.querySelector('[data-cod-cuenta]').getAttribute('data-cod-cuenta'),
        montoFormateado: cells[5].textContent.trim()
    };

    // Obtener el nombre de la sucursal desde la tercera columna de la fila
    const nombreSucursal = cells[2].textContent.trim();

    const infoDiv = document.getElementById('infoComprobanteSeleccionado');
    infoDiv.innerHTML = `
        <strong>Comprobante a vincular:</strong><br>
        Sucursal: ${currentRowData.nroSucursal} - ${nombreSucursal}<br>
        Fecha: ${currentRowData.fecha} | Comp: ${currentRowData.codComp}-${currentRowData.nComp} | Monto: ${currentRowData.montoFormateado}
    `;
    
    infoDiv.style.display = 'block';

    const modal = new bootstrap.Modal(document.getElementById('modalVincularRecibo'));
    modal.show();

    const searchInput = document.getElementById('searchInput');
    searchInput.value = ''; // Limpiar búsqueda anterior

    // Cargar resultados iniciales (sin término de búsqueda)
    buscarRecibos();

    searchInput.addEventListener('keyup', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            buscarRecibos(searchInput.value);
        }, 500); // Espera 500ms después de que el usuario deja de escribir
    });
};

const buscarRecibos = async (searchTerm = '') => {
    try {
        const response = await fetch(`Controller/ControlEgresosController.php?accion=traerRecibosParaVincular&search=${encodeURIComponent(searchTerm)}`);
        const recibos = await response.json();

        const tablaBody = document.querySelector('#tablaRecibosVincular tbody');
        tablaBody.innerHTML = ''; // Limpiar tabla

        if (recibos.length === 0) {
            tablaBody.innerHTML = '<tr><td colspan="6" class="text-center">No se encontraron recibos.</td></tr>';
            return;
        }

        recibos.forEach(recibo => {
            const tr = document.createElement('tr');
            // Formatear la fecha
            const fecha = new Date(recibo.FECHA.date);
            const formattedDate = fecha.toLocaleDateString('es-ES');

            const formattedAmount = recibo.CANT_MONE.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

            tr.innerHTML = `
                <td>${formattedDate}</td>
                <td>${recibo.COD_COMP}</td>
                <td>${recibo.N_COMP}</td>
                <td>${formattedAmount}</td>
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
        tablaBody.innerHTML = '<tr><td colspan="6" class="text-center">Error al cargar los recibos.</td></tr>';
    }
};

const seleccionarRecibo = async (codCompVinculado, nCompVinculado, montoVinculado) => {
    // Validar el flujo antes de proceder
    if (!validarFlujo(document.querySelector(`td[data-ncomp='${currentRowData.nComp}']`).closest('tr'), 'cargado')) {
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
                    const rowElement = document.querySelector(`td[data-ncomp='${currentRowData.nComp}']`).closest('tr');
                    if (rowElement) {
                        const vincularBtn = rowElement.querySelector('.btn-info');
                        if(vincularBtn) vincularBtn.style.display = 'none';

                        const cargadoCell = rowElement.children[10]; // Asumiendo que es la 11a columna
                        cargadoCell.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-4"></i>';
                    }
                } else {
                    Swal.fire('Error', result.message || 'Ocurrió un error al vincular.', 'error');
                }
            } catch (error) {
                console.error('Error al vincular el recibo:', error);
                Swal.fire('Error', 'Ocurrió un error de comunicación al intentar vincular el recibo.', 'error');
            } finally {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalVincularRecibo'));
                modal.hide();
            }
        }
    });
};