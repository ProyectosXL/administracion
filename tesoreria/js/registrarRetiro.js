
$(document).ready(function() {
    // Inicializar Select2 en el select de entrego
    $('#entrego').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccione una persona',
        allowClear: true,
        language: {
            noResults: function() {
                return "No se encontraron resultados";
            },
            searching: function() {
                return "Buscando...";
            }
        }
    });

    // Ajustar estilos específicos
    $('.select2-container--bootstrap-5 .select2-selection--single').css({
        'height': 'calc(3.5rem + 2px)',
        'padding': '1rem 0.75rem',
        'font-size': '1rem',
        'line-height': '1.5',
        'border-radius': '0.375rem'
    });
});

function generarNumeroRegistro() {
    let codigo = document.querySelector("#anterior").textContent;

    if(codigo == '0'){

        let nuevoCodigo = "C00000000001";
        console.log(nuevoCodigo); 
        document.getElementById('numeroRegistro').value = nuevoCodigo;
        return;

    }else{   
        let numero = parseInt(codigo) + 1;
        let nuevoCodigo = "C" + numero.toString().padStart(11, '0');
        document.getElementById('numeroRegistro').value = nuevoCodigo;
        return;

    }
}

generarNumeroRegistro();

const validarRemitos = async () => {
    const remitos = Array.from(document.querySelectorAll('#bodyRemitos tr')).map(tr => ({
        remito: tr.cells[0].textContent,
        destino: tr.cells[1].textContent,
        bultos: tr.querySelector('.input-bultos').value
    }));

    if (document.getElementById('enviaValores').value != 'SI' && remitos.length === 0) {
        mostrarAlerta('Error', 'Debe cargar al menos un remito');
        return false;
    }
    return true;
};

const validarEgresos = async () => {
    
    if(document.getElementById('enviaValores').value == 'SI'){
        let egresos = Array.from(document.querySelectorAll('#bodyEgresos tr')).map(tr => ({
            tipo: tr.cells[0].textContent,
            comprobante: tr.cells[1].textContent,
            fecha: tr.cells[2].textContent
        }));
       
        if(egresos.length == 0){
            mostrarAlerta('Error', 'Debe cargar al menos un egreso');
            return false;
        }else{
            return true;
        }

    }else{
        return true
    }
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
                   min="0">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm btn-quitar">
                <i class="bi bi-trash"></i>
            </button>
        </td>
        <td hidden>${datos.fecha}</td>
        <td hidden>${datos.t_comp}</td>
    `;


    const inputBultos = tr.querySelector('.input-bultos');
    inputBultos.addEventListener('input', function () {
        if (this.value < 1) this.value = 0;
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
        console.log("entro")
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
    console.log("validar precinto")

    return true;
}


async function validarFormulario() {
    return await validarSelects() &&
           await validarFirma() &&
           await validarPrecinto() &&
           await validarRemitos() &&
           await validarEgresos();
           
}


function obtenerDatosFormulario() {
    const datos = {
        numeroRegistro: document.getElementById('numeroRegistro').value,
        entrego: (document.getElementById('entrego').value).split('++')[0],
        recibio: document.getElementById('recibio').value,
        enviaValores: document.getElementById('enviaValores').value,
        observaciones: document.getElementById('observaciones').value,
        firma: signaturePad.toDataURL()
    };
    if (datos.enviaValores === 'SI') {
        datos.numeroPrecinto = document.getElementById('numeroPrecinto').value.trim();
        datos.egresos = Array.from(document.querySelectorAll('#bodyEgresos tr')).map(tr => ({
            tipo: tr.cells[0].textContent,
            comprobante: tr.cells[1].textContent,
            fecha: tr.cells[2].textContent
        }));
    }

    const remitos = Array.from(document.querySelectorAll('#bodyRemitos tr')).map(tr => ({
        remito: tr.cells[0].textContent,
        destino: tr.cells[1].textContent,
        bultos: tr.querySelector('.input-bultos').value,
        fecha : tr.cells[4].textContent,
        t_comp : tr.cells[5].textContent
    }));

    return {
        datos: datos,
        remitos: remitos
    };
}

document.getElementById('enviaValores').addEventListener('change', function() {
    const precintoContainer = document.getElementById('precintoContainer');
    const numeroPrecinto = document.getElementById('numeroPrecinto');
    
    if (this.value === 'SI') {
        precintoContainer.style.display = 'block';
        numeroPrecinto.required = true;
    } else {
        precintoContainer.style.display = 'none';
        numeroPrecinto.required = false;
        numeroPrecinto.value = '';
    }
});

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

document.getElementById('btnAgregarRemito').addEventListener('click', async function () {
    const select = document.getElementById('selectRemitos');
    if (!select.value) {
        await mostrarAlerta('Error', 'Por favor, seleccione un remito');
        return;
    }

    const datos = JSON.parse(select.value);
    const tbody = document.getElementById('bodyRemitos');

    const remitosExistentes = tbody.querySelectorAll('tr td:nth-child(1)');
    for (let td of remitosExistentes) {
        if (td.textContent.trim() === datos.remito) {
            await mostrarAlerta('Error', 'Este remito ya ha sido agregado');
            return;
        }
    }

    tbody.appendChild(crearFilaRemito(datos));
    actualizarTotalBultos();
    select.value = '';
});


document.getElementById('clear').addEventListener('click', function () {
    signaturePad.clear();
});



const registrar = async () => {
    // Validar formulario primero
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

        const datos = obtenerDatosFormulario();

        let firmaBase64 = signaturePad.toDataURL('image/jpeg', 0.8);
        let nroSucursal = document.querySelector("#numSucurs").textContent;
        
        const response = await fetch('Controller/upload_image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                firma: firmaBase64,
                nro_registro: datos.datos.numeroRegistro,
                sucursal: document.querySelector("#numSucurs").textContent}),
            });
            
            const respuestaDatos = await response.json();
            
            let firma = (respuestaDatos.filePath);
        
     

            $.ajax({
                url: 'Controller/retiroController.php?accion=registrar',
                type: 'POST',
                dataType: 'json',
                data: {
                    datos: datos.datos,
                    remitos: datos.remitos,
                    nroSucursal: nroSucursal,
                    firma: firma,
                    estado: 2
                },
                success: function (data) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Formulario registrado correctamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#198754',
                        customClass: {
                            popup: 'swal2-small'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'listarRetiros.php';
                        }
                    });
                },
                error: function (xhr, status, error) {
                    console.error('Error AJAX:', error);
                    console.error('Response:', xhr.responseText);
                    mostrarAlerta('Error', 'Error al registrar: ' + error);
                }
            });
            
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

document.getElementById('btnAgregarEgreso').addEventListener('click', async function() {
    const select = document.getElementById('selectEgresos');
    if (!select.value) {
        await mostrarAlerta('Error', 'Por favor, seleccione un egreso');
        return;
    }

    const datos = JSON.parse(select.value);
    const tbody = document.getElementById('bodyEgresos');
    
    // Verificar si el egreso ya está agregado
    const egresosExistentes = tbody.querySelectorAll('tr td:nth-child(2)');
    for (let td of egresosExistentes) {
        if (td.textContent === datos.comprobante) {
            await mostrarAlerta('Error', 'Este comprobante ya ha sido agregado');
            return;
        }
    }

    tbody.appendChild(crearFilaEgreso(datos));
    select.value = ''; // Limpiar la selección
});

function crearFilaEgreso(datos) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="text-nowrap">${datos.tipo}</td>
        <td class="text-nowrap">${datos.comprobante}</td>
        <td>${datos.fecha}</td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm btn-quitar">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    // Evento para el botón de quitar
    const btnQuitar = tr.querySelector('.btn-quitar');
    btnQuitar.addEventListener('click', async function() {
        const confirmar = await confirmarAccion('¿Está seguro?', 'Se eliminará este egreso', 'warning');
        if (confirmar) {
            tr.remove();
        }
    });

    return tr;
}


async function guardarFormulario() {
    let nroSucursal = document.querySelector("#numSucurs").textContent;

    const remitos = document.querySelectorAll('#bodyRemitos tr');
    if (!remitos.length) {
        await mostrarAlerta('Error', 'Debe cargar al menos un remito.');
        return false; 
    }

    try {
        const datos = obtenerDatosFormulario();
        console.log('Datos a guardar:', datos);

        $.ajax({
            url: 'Controller/retiroController.php?accion=registrar',
            type: 'POST',
            dataType: 'json',
            data: {
                datos: datos.datos,
                remitos: datos.remitos,
                nroSucursal: nroSucursal,
                estado: 1 
            },
            success: function (data) {
                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Formulario guardado como borrador correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#198754',
                    customClass: {
                        popup: 'swal2-small'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location = 'listarRetiros.php';
                    }
                });
            },
            error: function (xhr, status, error) {
                console.error('Error AJAX:', error);
                console.error('Response:', xhr.responseText);
                mostrarAlerta('Error', 'Error al guardar: ' + error);
            }
        });

    } catch (error) {
        console.error('Error:', error);
        await mostrarAlerta('Error', 'Error al guardar los datos: ' + error.message);
        return false;
    }
}



