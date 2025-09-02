
const marcarRecibido = async (e) => {
    try {
        const row = e.closest('tr');
        const cells = row.querySelectorAll('td');
        const data = {
            fecha: cells[0].textContent,
            nroSucursal: cells[1].textContent,
            tipoComprobante: cells[3].textContent,
            nroComprobante: cells[4].textContent,
            monto: cells[5].textContent.replace(/[$.]/g, ''),
            codCuenta: cells[12].textContent,      // Nueva columna oculta
            descripcionCuenta: cells[13].textContent, // Nueva columna oculta
            observaciones: cells[10].querySelector('textarea')?.value || ''
        };

        $.ajax({
            type: 'POST',
            url: 'Controller/ControlEgresosController.php?accion=marcarRecibido',
            data: data,
            success: function(response) {
                cells[8].innerHTML = '<i class="bi bi-check-circle-fill text-success fs-4"></i>';
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error al marcar como recibido' });
            }
        });
    } catch (error) {
        console.error('Error al marcar como recibido:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al marcar como recibido' });
    }
};

const marcarControlado = async (e) => {
    try {
        const row = e.closest('tr');
        const cells = row.querySelectorAll('td');
        
        const data = {
            fecha: cells[0].textContent,
            nroSucursal: cells[1].textContent,
            tipoComprobante: cells[3].textContent,
            nroComprobante: cells[4].textContent,
            monto: cells[5].textContent.replace(/[$.]/g, ''),
            codCuenta: cells[12].textContent,      // Nueva columna oculta
            descripcionCuenta: cells[13].textContent, // Nueva columna oculta
            observaciones: cells[10].querySelector('textarea')?.value || ''
        };

        $.ajax({
            type: 'POST',
            url: 'Controller/ControlEgresosController.php?accion=controlTesoreria',
            data: data,
            success: function(response) {
                cells[9].innerHTML = '<i class="bi bi-check-circle-fill text-success fs-4"></i>';
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error al marcar como controlado' });
                console.log('Datos enviados:', data); // Para debugging
            }
        });
    } catch (error) {
        console.error('Error al marcar como controlado:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al marcar como controlado' });
    }
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
        codCta: cells[12].textContent.trim(),
    };

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