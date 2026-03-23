$(document).ready(function() {
    inicializarSelects();
    cargarDatosIniciales();
    generarNumeroRegistro();

    // VINCULAMOS LOS BOTONES A LA NUEVA FUNCIÓN UNIFICADA
    $('#btnGuardar').on('click', () => enviarFormulario(1)); // 1 para Borrador
    $('#btnRegistrar').on('click', () => enviarFormulario(2)); // 2 para Registrar
});

function inicializarSelects() {
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
        if (!response.ok) throw new Error('Error de red al cargar datos para ' + selector);
        const data = await response.json();
        const select = $(selector);
        select.empty().append($('<option>', { value: '', text: placeholder }));

        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
            data.data.forEach(item => {
                select.append($('<option>', {
                    value: item[valor],
                    text: item[texto]
                }));
            });
        } else {
             select.append($('<option>', { value: '', text: 'No hay opciones disponibles' }));
        }
        select.trigger('change');
    } catch (error) {
        console.error('Error al cargar ' + selector, error);
        $(selector).empty().append($('<option>', { value: '', text: 'Error al cargar opciones' }));
    }
}

function generarNumeroRegistro() {
    let codigo = $("#anterior").text();
    let nuevoCodigo = "C" + (parseInt(codigo) + 1 || 1).toString().padStart(11, '0');
    $('#numeroRegistro').val(nuevoCodigo);
}

// =======================================================
// === NUEVA LÓGICA DE VALIDACIÓN Y ENVÍO UNIFICADA ======
// =======================================================

async function validarFormulario(esBorrador = false) {
    const entrego = $('#entrego').val();
    const recibio = $('#recibio').val();
    const enviaValores = $('#enviaValores').val();

    // --- Validaciones para TODOS (Borrador y Registro) ---
    if (entrego && recibio && entrego.split('++')[0] === recibio) {
        await mostrarAlerta('Error de Validación', 'La persona que entrega no puede ser la misma que recibe.');
        return false;
    }
    
    if (enviaValores !== 'SI' && $('#bodyRemitos tr').length === 0) {
         await mostrarAlerta('Campo Requerido', 'Debe agregar al menos un remito en la tabla si no envía valores.');
         return false;
    }

    if (esBorrador) {
        return true; // Si es borrador, pasamos las validaciones básicas y es suficiente
    }

    // --- Validaciones SÓLO para Registro Final (estado = 2) ---
    // Validar campo por campo para mensajes específicos
    if (!entrego) {
        await mostrarAlerta('Campo Requerido', 'Por favor, seleccione quién <strong>Entregó</strong> en el formulario.');
        return false;
    }

    if (!recibio) {
        await mostrarAlerta('Campo Requerido', 'Por favor, seleccione quién <strong>Recibió</strong> en el formulario.');
        return false;
    }

    if (!enviaValores) {
        await mostrarAlerta('Campo Requerido', 'Por favor, seleccione la opción <strong>Envía Valores</strong> (SI o NO).');
        return false;
    }

    // Validaciones específicas cuando envía valores
    if (enviaValores === 'SI') {
        const numeroPrecinto = $('#numeroPrecinto').val().trim();
        
        if (!numeroPrecinto) {
            await mostrarAlerta('Campo Requerido', 'Falta completar el campo <strong>Número de Precinto</strong>.');
            return false;
        }
        
        if (isNaN(numeroPrecinto) || parseInt(numeroPrecinto) <= 0) {
            await mostrarAlerta('Valor Inválido', 'El <strong>Número de Precinto</strong> debe ser un número positivo válido.');
            return false;
        }
        
        if ($('#bodyEgresos tr').length === 0) {
            await mostrarAlerta('Tabla Vacía', 'Debe agregar al menos un <strong>Egreso</strong> en la tabla cuando envía valores.');
            return false;
        }
    }
    
    // Validar que haya al menos un remito o valores
    if (enviaValores === 'NO' && $('#bodyRemitos tr').length === 0) {
        await mostrarAlerta('Tabla Vacía', 'Debe agregar al menos un <strong>Remito</strong> en la tabla.');
        return false;
    }
    
    // Validar que todos los remitos tengan bultos
    const remitosConCeroBultos = $('.input-bultos').filter(function() {
        return !this.value || parseInt(this.value) <= 0;
    });
    
    if (remitosConCeroBultos.length > 0) {
        await mostrarAlerta('Valor Inválido', 'Todos los remitos deben tener al menos <strong>1 bulto</strong>. Por favor, verifique la tabla de remitos.');
        return false;
    }
    
    // Validar firma
    if (signaturePad.isEmpty()) {
        await mostrarAlerta('Campo Requerido', 'Debe agregar su <strong>Firma</strong> en el recuadro para poder registrar.');
        return false;
    }

    return true;
}

function obtenerDatosFormulario() {
    const datos = {
        numeroRegistro: $('#numeroRegistro').val(),
        entrego: ($('#entrego').val() || '').split('++')[0],
        recibio: $('#recibio').val(),
        enviaValores: $('#enviaValores').val(),
        observaciones: $('#observaciones').val(),
    };

    if (datos.enviaValores === 'SI') {
        datos.numeroPrecinto = $('#numeroPrecinto').val().trim();
        datos.egresos = Array.from(document.querySelectorAll('#bodyEgresos tr')).map(tr => ({
            tipo: tr.cells[0].textContent,
            comprobante: tr.cells[1].textContent,
            fecha: tr.cells[2].textContent
        }));
    }

    const remitos = Array.from(document.querySelectorAll('#bodyRemitos tr')).map(tr => ({
        remito: tr.cells[0].textContent,
        destino: tr.cells[1].textContent,
        bultos: $(tr).find('.input-bultos').val(),
        fecha: tr.cells[4].textContent,
        t_comp: tr.cells[5].textContent
    }));

    return { datos, remitos };
}

async function enviarFormulario(estado) {
    const esBorrador = (estado === 1);
    const accionTexto = esBorrador ? 'guardar el borrador' : 'registrar la guía';
    const nroSucursal = $("#numSucurs").text();

    // =================================================================
    // === PASO 1: VERIFICAR LA CONEXIÓN ANTES DE HACER NADA MÁS ===
    // =================================================================
    
    // Mostramos un mensaje de espera al usuario
    Swal.fire({
        title: 'Verificando conexión con el local...',
        text: 'Por favor, espere.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const url = `Controller/retiroController.php?accion=verificarConexionLocal&nroSucurs=${nroSucursal}`;
        const response = await fetch(url);
        const result = await response.json();

        if (!response.ok || !result.success) {
            Swal.close(); // Cerramos el mensaje de espera
            // Mostramos el error devuelto por el servidor y detenemos todo.
            await mostrarAlerta('Error de Conexión', result.message || 'No se pudo conectar con la base de datos del local. No se puede continuar.');
            return; 
        }
    } catch (error) {
        Swal.close(); // Cerramos el mensaje de espera
        await mostrarAlerta('Error de Red', 'No se pudo comunicar con el servidor para verificar la conexión. Revise su conexión a internet.');
        return; // Detenemos todo.
    }
    
    Swal.close(); // Si la conexión fue exitosa, cerramos el mensaje de espera.

    // =================================================================
    // === PASO 2: SI LA CONEXIÓN FUE EXITOSA, CONTINUAR CON EL PROCESO NORMAL ===
    // =================================================================
    
    if (!await validarFormulario(esBorrador)) {
        return;
    }
    
    const confirmar = await confirmarAccion(`¿Confirmar ${accionTexto}?`, 'Esta acción guardará los datos en el sistema.', 'question');
    if (!confirmar) return;

    try {
        const { datos, remitos } = obtenerDatosFormulario();
        let firmaPath = null;

        const dataPayload = {
            datos: datos,
            remitos: remitos,
            nroSucursal: nroSucursal,
            estado: estado,
            firma: firmaPath
        };

        if (!esBorrador && !signaturePad.isEmpty()) {
            const firmaBase64 = signaturePad.toDataURL('image/jpeg', 0.8);
            const responseFirma = await fetch('Controller/upload_image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ firma: firmaBase64, nro_registro: datos.numeroRegistro, sucursal: nroSucursal })
            });
            const respuestaFirma = await responseFirma.json();
            if (!responseFirma.ok || !respuestaFirma.filePath) {
                throw new Error(respuestaFirma.error || 'Error al subir la firma.');
            }
            dataPayload.firma = respuestaFirma.filePath;
        }

        $.ajax({
            url: 'Controller/retiroController.php?accion=registrar',
            type: 'POST',
            data: dataPayload, // Envío como FormData
            success: function(response) {
                if (response.success) {
                    Swal.fire({ title: '¡Éxito!', text: response.message, icon: 'success' })
                       .then(() => window.location.href = 'listarRetiros.php');
                } else {
                    mostrarAlerta('Error', response.message);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : `Error al ${accionTexto}.`;
                mostrarAlerta('Error', errorMsg);
            }
        });

    } catch (error) {
        await mostrarAlerta('Error', 'Error en el proceso de envío: ' + error.message);
    }
}

// ===============================================
// === RESTO DE FUNCIONES (AUXILIARES) ===========
// ===============================================

async function mostrarAlerta(titulo, texto, tipo = 'error') {
    // Detectar si el texto contiene HTML
    const contieneHTML = /<[^>]*>/g.test(texto);
    
    return await Swal.fire({
        title: titulo,
        [contieneHTML ? 'html' : 'text']: texto,
        icon: tipo,
        confirmButtonText: 'Aceptar',
        confirmButtonColor: tipo === 'success' ? '#198754' : '#0d6efd',
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
        cancelButtonText: 'Cancelar'
    });
    return result.isConfirmed;
}

function crearFilaRemito(datos) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="text-nowrap">${datos.remito}</td><td title="${datos.destino}">${datos.destino}</td><td><input type="number" class="form-control form-control-sm input-bultos" value="1" min="0"></td><td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-quitar"><i class="bi bi-trash"></i></button></td><td hidden>${datos.fecha}</td><td hidden>${datos.t_comp}</td>`;
    $(tr).find('.input-bultos').on('input', function() { if (this.value < 1) this.value = 0; actualizarTotalBultos(); });
    $(tr).find('.btn-quitar').on('click', async function() {
        if (await confirmarAccion('¿Está seguro?', 'Se eliminará este remito', 'warning')) {
            $(this).closest('tr').remove();
            actualizarTotalBultos();
        }
    });
    return tr;
}

function crearFilaEgreso(datos) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="text-nowrap">${datos.tipo}</td><td class="text-nowrap">${datos.comprobante}</td><td>${datos.fecha}</td><td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-quitar"><i class="bi bi-trash"></i></button></td>`;
    $(tr).find('.btn-quitar').on('click', async function() {
        if (await confirmarAccion('¿Está seguro?', 'Se eliminará este egreso', 'warning')) {
            $(this).closest('tr').remove();
        }
    });
    return tr;
}

function actualizarTotalBultos() {
    const total = Array.from(document.querySelectorAll('.input-bultos')).reduce((sum, input) => sum + parseInt(input.value || 0), 0);
    $('#totalBultos').text(total);
}

const signaturePad = new SignaturePad(document.getElementById('signature-pad'), { backgroundColor: 'white', penColor: 'black' });
(function resizeCanvas() {
    const canvas = document.getElementById('signature-pad');
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const width = canvas.parentElement.offsetWidth - 30;
    canvas.width = width * ratio;
    canvas.height = 150 * ratio;
    canvas.style.width = `${width}px`;
    canvas.style.height = '150px';
    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);
    signaturePad.clear();
})();

$('#clear').on('click', () => signaturePad.clear());

$('#enviaValores').on('change', function() {
    $('#precintoContainer').toggle(this.value === 'SI');
}).trigger('change');

// Agregar remito automáticamente al seleccionarlo
$('#selectRemitos').on('change', async function() {
    const select = $(this);
    const valorSeleccionado = select.val();
    
    if (!valorSeleccionado) return; // Si no hay selección, no hacer nada
    
    try {
        const datos = JSON.parse(valorSeleccionado);
        
        // Verificar si ya existe el remito en la tabla
        const yaExiste = $('#bodyRemitos td:first-child').filter(function() { 
            return $(this).text().trim() === datos.remito; 
        }).length > 0;
        
        if (yaExiste) {
            await mostrarAlerta('Error', 'Este remito ya ha sido agregado');
            select.val('').trigger('change');
            return;
        }
        
        // Agregar el remito a la tabla
        $('#bodyRemitos').append(crearFilaRemito(datos));
        actualizarTotalBultos();
        
        // Limpiar la selección
        select.val('').trigger('change');
    } catch (error) {
        console.error('Error al procesar el remito:', error);
    }
});

// Agregar egreso automáticamente al seleccionarlo
$('#selectEgresos').on('change', async function() {
    const select = $(this);
    const valorSeleccionado = select.val();
    
    if (!valorSeleccionado) return; // Si no hay selección, no hacer nada
    
    try {
        const datos = JSON.parse(valorSeleccionado);
        
        // Verificar si ya existe el egreso en la tabla
        const yaExiste = $('#bodyEgresos td:nth-child(2)').filter(function() { 
            return $(this).text() === datos.comprobante; 
        }).length > 0;
        
        if (yaExiste) {
            await mostrarAlerta('Error', 'Este comprobante ya ha sido agregado');
            select.val('').trigger('change');
            return;
        }
        
        // Agregar el egreso a la tabla
        $('#bodyEgresos').append(crearFilaEgreso(datos));
        
        // Limpiar la selección
        select.val('').trigger('change');
    } catch (error) {
        console.error('Error al procesar el egreso:', error);
    }
});