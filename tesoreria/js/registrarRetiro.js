
const validarRemitos = () => {
    const remitos = Array.from(document.querySelectorAll('#bodyRemitos tr')).map(tr => ({
        remito: tr.cells[0].textContent,
        destino: tr.cells[1].textContent,
        bultos: tr.querySelector('.input-bultos').value
    }));

    if (document.getElementById('enviaValores').value != 'SI' && remitos.length === 0) {
        alert('Debe cargar al menos un remito');
        return false;
    }
    return true;
};


function generarNumeroRegistro() {
    const num = 'C' + String(numeroRegistro).padStart(11, '0');
    document.getElementById('numeroRegistro').value = num;
}


async function mostrarAlerta(titulo, texto, tipo = 'error') {
    return await Swal.fire({
        title: titulo,
        text: texto,
        icon: tipo,
        confirmButtonText: 'Aceptar',
        confirmButtonColor: tipo === 'success' ? '#198754' : '#0d6efd',
        customClass: {
            popup: 'swal2-small'
        }
    });
}


async function confirmarAccion(titulo, texto, tipo = 'question') {
    const result = await Swal.fire({
        title: titulo,
        text: texto,
        icon: tipo,
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-small'
        }
    });
    return result.isConfirmed;
}


function crearFilaRemito(datos) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="text-nowrap">${datos.remito}</td>
        <td title="${datos.destino}">${datos.destino}</td>
        <td>
            <input type="number" 
                   class="form-control form-control-sm input-bultos" 
                   value="1" 
                   min="1">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm btn-quitar">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;


    const inputBultos = tr.querySelector('.input-bultos');
    inputBultos.addEventListener('input', function () {
        if (this.value < 1) this.value = 1;
        actualizarTotalBultos();
    });


    const btnQuitar = tr.querySelector('.btn-quitar');
    btnQuitar.addEventListener('click', async function () {
        const confirmar = await confirmarAccion('¿Está seguro?', 'Se eliminará este remito', 'warning');
        if (confirmar) {
            tr.remove();
            actualizarTotalBultos();
        }
    });

    return tr;
}


function actualizarTotalBultos() {
    const inputs = document.querySelectorAll('.input-bultos');
    const total = Array.from(inputs).reduce((sum, input) => sum + parseInt(input.value || 0), 0);
    document.getElementById('totalBultos').textContent = total;
}


function initSignaturePad() {
    const canvas = document.getElementById('signature-pad');
    const ratio = Math.max(window.devicePixelRatio || 1, 1);

    const signaturePadWidth = canvas.parentElement.offsetWidth - 30;
    canvas.width = signaturePadWidth * ratio;
    canvas.height = 150 * ratio;
    canvas.style.width = `${signaturePadWidth}px`;
    canvas.style.height = '150px';

    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);

    return new SignaturePad(canvas, {
        backgroundColor: 'white',
        penColor: 'black'
    });
}
const signaturePad = initSignaturePad();


async function validarSelects() {
    const entrego = document.getElementById('entrego').value;
    const recibio = document.getElementById('recibio').value;
    const enviaValores = document.getElementById('enviaValores').value;

    if (!entrego || !recibio || !enviaValores) {
        await mostrarAlerta('Error', 'Por favor, complete todos los campos obligatorios.');
        return false;
    }

    if (entrego === recibio) {
        await mostrarAlerta('Error', 'La persona que entrega no puede ser la misma que recibe.');
        return false;
    }

    return true;
}


async function validarFirma() {
    if (signaturePad.isEmpty()) {
        await mostrarAlerta('Error', 'Por favor, proporcione una firma.');
        return false;
    }
    return true;
}


async function validarPrecinto() {
    const enviaValores = document.getElementById('enviaValores').value;
    if (enviaValores === 'SI') {
        const numeroPrecinto = document.getElementById('numeroPrecinto').value.trim();
        if (!numeroPrecinto) {
            await mostrarAlerta('Error', 'Debe ingresar el número de precinto cuando envía valores.');
            return false;
        }
        if (isNaN(numeroPrecinto) || parseInt(numeroPrecinto) <= 0) {
            await mostrarAlerta('Error', 'El número de precinto debe ser un valor numérico positivo.');
            return false;
        }
    }
    return true;
}


async function validarFormulario() {
    return await validarSelects() &&
           await validarFirma() &&
           await validarPrecinto() &&
           validarRemitos();
}


function obtenerDatosFormulario() {
    const datos = {
        numeroRegistro: document.getElementById('numeroRegistro').value,
        entrego: document.getElementById('entrego').value,
        recibio: document.getElementById('recibio').value,
        enviaValores: document.getElementById('enviaValores').value,
        observaciones: document.getElementById('observaciones').value,
        firma: signaturePad.toDataURL()
    };
    return datos;
}


function reiniciarFormulario() {
    document.getElementById('entregaForm').reset();
    document.getElementById('precintoContainer').style.display = 'none';
    document.getElementById('bodyRemitos').innerHTML = '';
    document.getElementById('totalBultos').textContent = '0';
    signaturePad.clear();
}


document.getElementById('btnAgregarRemito').addEventListener('click', function () {
    const select = document.getElementById('selectRemitos');
    const datos = JSON.parse(select.value);
    const tbody = document.getElementById('bodyRemitos');
    tbody.appendChild(crearFilaRemito(datos));
    actualizarTotalBultos();
    select.value = '';
});

document.getElementById('clear').addEventListener('click', function () {
    signaturePad.clear();
});




document.getElementById('entregaForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    if (!(await validarFormulario())) return;

    const datos = obtenerDatosFormulario();
    console.log('Datos:', datos);
    reiniciarFormulario();
});



const muestra = () => {
    let entrego = document.querySelector("#entrego").value;
    let recibio = document.querySelector("#recibio").value;

    $.ajax({
        url: 'Controller/retiroController.php?accion=registrar',
        type: 'POST',
        dataType: 'json',
        data: {
            entrego: entrego,
            recibio: recibio,
            numeroRegistro: document.getElementById('numeroRegistro').value,
            enviaValores: document.getElementById('enviaValores').value,
            numeroPrecinto: document.getElementById('numeroPrecinto').value,
            observaciones: document.getElementById('observaciones').value,
            firma: signaturePad.toDataURL(),
            egresos: Array.from(document.querySelectorAll('#bodyEgresos tr')).map(tr => ({
                tipo: tr.cells[0].textContent,
                comprobante: tr.cells[1].textContent,
                fecha: tr.cells[2].textContent
            })),
            remitos: Array.from(document.querySelectorAll('#bodyRemitos tr')).map(tr => ({
                remito: tr.cells[0].textContent,
                destino: tr.cells[1].textContent,
                bultos: tr.querySelector('.input-bultos').value
            }))
        },
        success: function (data) {
            if (data.success) {
                mostrarAlerta('¡Éxito!', data.message, 'success');
                reiniciarFormulario();
            } else {
                mostrarAlerta('Error', data.message);
            }
        },
        error: function (xhr, status, error) {
            mostrarAlerta('Error', 'Ocurrió un error inesperado. Inténtelo de nuevo.');
            console.error('Error AJAX:', error);
        }
    });
}
