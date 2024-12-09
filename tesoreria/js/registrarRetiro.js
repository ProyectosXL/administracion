
function generarNumeroRegistro() {
    let codigo = document.querySelector("#anterior").textContent;

    let parteNumerica = parseInt(codigo.slice(1), 10);

    parteNumerica += 1;

    let nuevoCodigo = "C" + String(parteNumerica).padStart(11, '0');

    console.log(nuevoCodigo); 

    document.getElementById('numeroRegistro').value = nuevoCodigo;

}

generarNumeroRegistro();

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
    document.getElementById('numeroPrecinto').required = false;
    document.getElementById('bodyRemitos').innerHTML = '';
    document.getElementById('bodyEgresos').innerHTML = '';
    document.getElementById('totalBultos').textContent = '0';
    signaturePad.clear();
    numeroRegistro++;
    document.getElementById('numeroRegistro').value = generarNumeroRegistro();
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



// Manejar el envío del formulario
const registrar = async () => {
    

    const datos = obtenerDatosFormulario();
        
    let firmaBase64 = signaturePad.toDataURL('image/jpeg', 0.8);
    
    const response = await fetch('Controller/upload_image.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            firma: firmaBase64,
            nro_registro: datos.numeroRegistro,
            sucursal: document.querySelector("#numSucurs").textContent}),
        });
        
        const respuestaDatos = await response.json();
        
        let firma = (respuestaDatos.filePath);

    if (!(await validarFormulario())) {
        return;
    }
    
    try {
        const confirmar = await confirmarAccion(
            '¿Confirmar registro?',
            'Esta acción no se puede deshacer',
            'question'
        );
        
        if (!confirmar) return;
        
     

            $.ajax({
                url: 'Controller/retiroController.php?accion=registrar',
                type: 'POST',
                dataType: 'json',
                data: {
                    datos: datos,
                    firma: firma
                }
            });
            // LLAMADO AJAX  
            
            
            // try {
                //     const response = await fetch(`Controller/retiroController.php?accion=traerDatos&numeroRegistro=${numeroRegistro}`);
                
                //     if (!response.ok) {
                //         throw new Error("Error al traer los datos del formulario.");
                //     }
            
                //     const datos = await response.json();
            
                //     if (!datos.success) {
                //         throw new Error(datos.message || "Error desconocido al traer los datos.");
                //     }
            
                    
                //     document.getElementById('entrego').value = datos.data.entrego || '';
                //     document.getElementById('recibio').value = datos.data.recibio || '';
                //     document.getElementById('enviaValores').value = datos.data.enviaValores || '';
                //     document.getElementById('observaciones').value = datos.data.observaciones || '';
                //     document.getElementById('numeroRegistro').value = datos.data.numeroRegistro || '';
            
                    
                //     if (datos.data.firma) {
                //         const image = new Image();
                //         image.onload = () => {
                //             const ctx = document.getElementById('signature-pad').getContext('2d');
                //             ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height); 
                //             ctx.drawImage(image, 0, 0); 
                //         };
                //         image.src = datos.data.firma;
                //     }
            
                //     console.log("Datos cargados exitosamente:", datos.data);
                // } catch (error) {
                //     console.error("Error al traer los datos del formulario:", error);
                //     await mostrarAlerta('Error', 'No se pudieron cargar los datos del formulario: ' + error.message);
                // }
        
            
            
            
            
            // LLAMADO 



            
            // Aquí iría el código para registrar los datos
            await mostrarAlerta('¡Éxito!', 'Formulario registrado correctamente', 'success');
            reiniciarFormulario();
    
        } catch (error) {
            console.error('Error:', error);
            await mostrarAlerta('Error', 'Error al registrar el formulario: ' + error.message);
        }
      
    };




    
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
