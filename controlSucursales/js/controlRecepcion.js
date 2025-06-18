
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
                alert('Error al marcar como recibido');
            }
        });
    } catch (error) {
        console.error('Error al marcar como recibido:', error);
        alert('Ocurrió un error al marcar como recibido');
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
                alert('Error al marcar como controlado');
                console.log('Datos enviados:', data); // Para debugging
            }
        });
    } catch (error) {
        console.error('Error al marcar como controlado:', error);
        alert('Ocurrió un error al marcar como controlado');
    }
};

const guardarObservaciones = async (btn) => {
    try {
        const row = btn.closest('tr');
        const cells = row.querySelectorAll('td');
        const textarea = cells[10].querySelector('textarea');
        
        if (!textarea.value.trim()) {
            alert('Por favor, ingrese una observación');
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
                alert('Error al guardar las observaciones');
            }
        });
    } catch (error) {
        console.error('Error al guardar observaciones:', error);
        alert('Ocurrió un error al guardar las observaciones');
    }
};

// Event listener para el botón de filtrar
document.getElementById('btnFiltrarControlRecepcion').addEventListener('click', (e) => {
    e.preventDefault();
    const desde = document.getElementById('desde').value;
    const hasta = document.getElementById('hasta').value;
    const estado = document.getElementById('selectEstado').value;

    if (!desde || !hasta) {
        alert('Por favor, seleccione fechas válidas');
        return;
    }

    window.location.href = `?desde=${desde}&hasta=${hasta}&selectEstado=${estado}`;
});