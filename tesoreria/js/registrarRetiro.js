$(document).ready(function() {
    inicializarSelects();
    cargarDatosIniciales();
    generarNumeroRegistro();
});

function inicializarSelects() {
    // Función genérica para inicializar Select2
    const initSelect2 = (selector, placeholder) => {
        $(selector).select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: placeholder,
            allowClear: true,
            language: {
                noResults: () => "No se encontraron resultados",
                searching: () => "Buscando..."
            }
        });
    };

    initSelect2('#entrego', 'Seleccione una persona');
    initSelect2('#recibio', 'Seleccione una persona');
    initSelect2('#selectRemitos', 'Seleccione un remito');
    initSelect2('#selectEgresos', 'Seleccione un egreso');

    // Estilos adicionales que tenías (se mantienen)
    $('.select2-container--bootstrap-5 .select2-selection--single').css({
        'height': 'calc(3.5rem + 2px)', 'padding': '1rem 0.75rem',
        'font-size': '1rem', 'line-height': '1.5', 'border-radius': '0.375rem'
    });
}

async function cargarDatosIniciales() {
    const nroSucurs = $("#numSucurs").text();

    // Cargamos todos los datos en paralelo para mayor eficiencia
    await Promise.all([
        cargarSelect('Controller/retiroController.php?accion=listarUsuarios', '#entrego', 'Seleccione una persona', 'NOMBRE_VEN', 'VALOR_COMPLETO'),
        cargarSelect('Controller/retiroController.php?accion=listarFleteros', '#recibio', 'Seleccione una persona', 'NOMBRE_APELLIDO', 'NOMBRE_APELLIDO'),
        cargarSelect('Controller/retiroController.php?accion=listarRemitos&nroSucurs=' + nroSucurs, '#selectRemitos', 'Seleccione un remito', 'DISPLAY', 'VALOR_JSON'),
        cargarSelect('Controller/retiroController.php?accion=listarEgresos&nroSucurs=' + nroSucurs, '#selectEgresos', 'Seleccione un egreso', 'DISPLAY', 'VALOR_JSON')
    ]);
}

async function cargarSelect(url, selector, placeholder, texto, valor) {
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Error de red al cargar datos para ' + selector);
        }
        const data = await response.json();
        const select = $(selector);
        
        select.empty(); // Limpiar opciones anteriores
        select.append($('<option>', { value: '', text: placeholder }));

        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
            data.data.forEach(item => {
                select.append($('<option>', {
                    value: item[valor],
                    text: item[texto]
                }));
            });
        } else {
             select.empty().append($('<option>', { value: '', text: 'No hay opciones disponibles' }));
        }
        select.trigger('change'); // Notificar a Select2 del cambio
    } catch (error) {
        console.error('Error al cargar ' + selector, error);
        $(selector).empty().append($('<option>', { value: '', text: 'Error al cargar opciones' }));
    }
}

function generarNumeroRegistro() {
    let codigo = document.querySelector("#anterior").textContent;
    let nuevoCodigo;
    if (codigo === '0' || !codigo) {
        nuevoCodigo = "C00000000001";
    } else {
        let numero = parseInt(codigo) + 1;
        nuevoCodigo = "C" + numero.toString().padStart(11, '0');
    }
    document.getElementById('numeroRegistro').value = nuevoCodigo;
}

const validarRemitos = async () => {
    const remitos = document.querySelectorAll('#bodyRemitos tr');

    if (document.getElementById('enviaValores').value !== 'SI' && remitos.length === 0) {
        mostrarAlerta('Error', 'Debe cargar al menos un remito');
        return false;
    }
    return true;
};

const validarEgresos = async () => {
    if (document.getElementById('enviaValores').value === 'SI') {
        const egresos = document.querySelectorAll('#bodyEgresos tr');
        if (egresos.length === 0) {
            mostrarAlerta('Error', 'Debe cargar al menos un egreso');
            return false;
        }
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
    
    const nombreEntrego = entrego.split('++')[0];
    if (nombreEntrego === recibio) {
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
           await validarRemitos() &&
           await validarEgresos();
}

function obtenerDatosFormulario() {
    const datos = {
        numeroRegistro: document.getElementById('numeroRegistro').value,
        entrego: (document.getElementById('entrego').value).split('++')[0],
        recibio: document.getElementById('recibio').value,
        enviaValores: document.getElementById('enviaValores').value,
        observaciones: document.getElementById('observaciones').value
        // La firma se manejará por separado en el envío
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
    } else {
        precintoContainer.style.display = 'none';
        numeroPrecinto.value = '';
    }
});

document.getElementById('btnAgregarRemito').addEventListener('click', async function () {
    const select = document.getElementById('selectRemitos');
    if (!select.value) {
        await mostrarAlerta('Error', 'Por favor, seleccione un remito');
        return;
    }

    const datos = JSON.parse(select.value);
    const tbody = document.getElementById('bodyRemitos');

    const remitosExistentes = tbody.querySelectorAll('tr td:first-child');
    for (let td of remitosExistentes) {
        if (td.textContent.trim() === datos.remito) {
            await mostrarAlerta('Error', 'Este remito ya ha sido agregado');
            return;
        }
    }

    tbody.appendChild(crearFilaRemito(datos));
    actualizarTotalBultos();
    $('#selectRemitos').val('').trigger('change');
});

document.getElementById('clear').addEventListener('click', function () {
    signaturePad.clear();
});

const registrar = async () => {
    if (!(await validarFormulario())) {
        return;
    }
    
    try {
        const confirmar = await confirmarAccion('¿Confirmar registro?', 'Esta acción no se puede deshacer.', 'question');
        if (!confirmar) return;

        const { datos, remitos } = obtenerDatosFormulario();
        const nroSucursal = document.querySelector("#numSucurs").textContent;
        const firmaBase64 = signaturePad.toDataURL('image/jpeg', 0.8);
        
        const response = await fetch('Controller/upload_image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                firma: firmaBase64,
                nro_registro: datos.numeroRegistro,
                sucursal: nroSucursal
            })
        });
            
        const respuestaDatos = await response.json();
        if (!response.ok || !respuestaDatos.filePath) {
            throw new Error(respuestaDatos.error || 'Error al subir la firma');
        }
        const firmaPath = respuestaDatos.filePath;
     
        $.ajax({
            url: 'Controller/retiroController.php?accion=registrar',
            type: 'POST',
            dataType: 'json',
            data: {
                datos: datos,
                remitos: remitos,
                nroSucursal: nroSucursal,
                firma: firmaPath,
                estado: 2
            },
            success: function (data) {
                if(data.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message || 'Formulario registrado correctamente',
                        icon: 'success'
                    }).then(() => window.location.href = 'listarRetiros.php');
                } else {
                    mostrarAlerta('Error', data.message || 'Ocurrió un error al registrar.');
                }
            },
            error: function (xhr, status, error) {
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Error al registrar: ' + error;
                mostrarAlerta('Error', errorMsg);
            }
        });
            
    } catch (error) {
        await mostrarAlerta('Error', 'Error al procesar el registro: ' + error.message);
    }
};

document.getElementById('btnAgregarEgreso').addEventListener('click', async function() {
    const select = document.getElementById('selectEgresos');
    if (!select.value) {
        await mostrarAlerta('Error', 'Por favor, seleccione un egreso');
        return;
    }

    const datos = JSON.parse(select.value);
    const tbody = document.getElementById('bodyEgresos');
    
    const egresosExistentes = tbody.querySelectorAll('tr td:nth-child(2)');
    for (let td of egresosExistentes) {
        if (td.textContent === datos.comprobante) {
            await mostrarAlerta('Error', 'Este comprobante ya ha sido agregado');
            return;
        }
    }

    tbody.appendChild(crearFilaEgreso(datos));
    $('#selectEgresos').val('').trigger('change');
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
    const remitosTr = document.querySelectorAll('#bodyRemitos tr');
    if (remitosTr.length === 0) {
        await mostrarAlerta('Error', 'Debe cargar al menos un remito.');
        return;
    }

    try {
        const { datos, remitos } = obtenerDatosFormulario();
        const nroSucursal = document.querySelector("#numSucurs").textContent;

        $.ajax({
            url: 'Controller/retiroController.php?accion=registrar',
            type: 'POST',
            dataType: 'json',
            data: {
                datos: datos,
                remitos: remitos,
                nroSucursal: nroSucursal,
                estado: 1 
            },
            success: function (data) {
                if(data.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message || 'Formulario guardado como borrador',
                        icon: 'success'
                    }).then(() => window.location.href = 'listarRetiros.php');
                } else {
                     mostrarAlerta('Error', data.message || 'Ocurrió un error al guardar.');
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Error al guardar.';
                mostrarAlerta('Error', errorMsg);
            }
        });
    } catch (error) {
        await mostrarAlerta('Error', 'Error al procesar el guardado: ' + error.message);
    }
}