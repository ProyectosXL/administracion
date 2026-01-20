

    // Variables globales
    let numeroRegistro = 1;
    let signaturePad;

    // Funciones de alerta con SweetAlert2
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

function obtenerNumeroRegistroDesdeURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id'); // <-- Cambiado a 'id' para que coincida con tu URL
}

async function cargarDatosParaEditar(numeroRegistro) {
    if (!numeroRegistro) {
        mostrarAlerta('Error', 'No se ha especificado un número de registro para editar.');
        return;
    }

    try {
        // Hacemos la llamada al controlador para traer TODOS los datos
        const url = `/administracion/Controller/retiroController.php?accion=traerDatos&numeroRegistro=${numeroRegistro}`;
        const response = await fetch(url);
        if (!response.ok) throw new Error('Error de red al contactar el servidor.');

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'No se encontraron datos para este registro.');
        }

        const datos = result.data;

        // --- 1. Llenar el encabezado del formulario ---
        $('#numeroRegistro').val(datos.NRO_REGISTRO);
        
        // Para Select2, es importante establecer el valor y luego disparar 'change'
        // Asumiendo que 'ENTREGO_VALOR_COMPLETO' es el value que guardaste
        $('#entrego').val(datos.ENTREGO_VALOR_COMPLETO).trigger('change'); 
        $('#recibio').val(datos.RECIBIO).trigger('change');
        $('#enviaValores').val(datos.ENVIA_VALORES).trigger('change');
        $('#numeroPrecinto').val(datos.NRO_PRECINTO);
        $('#observaciones').val(datos.OBSERVACIONES);

        // --- 2. Llenar la tabla de REMITOS (¡LA PARTE CLAVE!) ---
        $('#bodyRemitos').empty(); // Limpiamos la tabla primero
        if (datos.remitos && Array.isArray(datos.remitos)) {
            datos.remitos.forEach(remito => {
                const fila = crearFilaRemito({
                    remito: remito.N_COMP,
                    destino: remito.DESTINO,
                    fecha: remito.FECHA_REM,
                    t_comp: remito.T_COMP
                });
                // Importante: Asignar el valor de bultos que ya estaba guardado
                $(fila).find('.input-bultos').val(remito.BULTOS);
                $('#bodyRemitos').append(fila);
            });
            actualizarTotalBultos(); // Actualizamos el contador total
        }

        // --- 3. Llenar la tabla de EGRESOS (¡LA OTRA PARTE CLAVE!) ---
        $('#bodyEgresos').empty(); // Limpiamos la tabla
        if (datos.egresos && Array.isArray(datos.egresos)) {
            datos.egresos.forEach(egreso => {
                const fila = crearFilaEgreso({
                    tipo: egreso.T_COMP,
                    comprobante: egreso.N_COMP,
                    fecha: egreso.FECHA_COMP
                });
                $('#bodyEgresos').append(fila);
            });
        }
        
    } catch (error) {
        console.error('Error al cargar datos para editar:', error);
        mostrarAlerta('Error Crítico', error.message);
    }
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

    // Función para generar el número de registro
    function generarNumeroRegistro() {
        return 'C' + String(numeroRegistro).padStart(11, '0');
    }

    // Inicializar el número de registro
   //document.getElementById('numeroRegistro').value = generarNumeroRegistro();

    // Función para actualizar el total de bultos
    function actualizarTotalBultos() {
        const inputs = document.querySelectorAll('.input-bultos');
        const total = Array.from(inputs).reduce((sum, input) => sum + parseInt(input.value || 0), 0);
        document.getElementById('totalBultos').textContent = total;
    }

    // Función para crear una fila de remito
// Función para crear una fila de remito (CORREGIDA)
function crearFilaRemito(datos) {
    const tr = document.createElement('tr');
    
    // CORRECCIÓN CLAVE: Asignar los dataset al tr para que obtenerDatosFormulario los pueda leer
    tr.dataset.fecha = datos.fecha;
    tr.dataset.tcomp = datos.t_comp || 'REM'; // Valor por defecto si no viene

    tr.innerHTML = `
        <td class="text-nowrap">${datos.remito}</td>
        <td title="${datos.destino}">${datos.destino}</td>
        <td>
            <input type="number" 
                   class="form-control form-control-sm input-bultos" 
                   value="${datos.bultos || 1}" 
                   min="1">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm btn-quitar">
                <i class="bi bi-trash"></i>
            </button>
        </td>
        <td hidden>${datos.fecha}</td>
        <td hidden>${datos.t_comp || 'REM'}</td>
    `;

    // Evento para el input de bultos
    const inputBultos = tr.querySelector('.input-bultos');
    inputBultos.addEventListener('input', function() {
        if (this.value < 1) this.value = 1;
        actualizarTotalBultos();
    });

    // Evento para el botón de quitar
    const btnQuitar = tr.querySelector('.btn-quitar');
    btnQuitar.addEventListener('click', async function() {
        const confirmar = await confirmarAccion('¿Está seguro?', 'Se eliminará este remito', 'warning');
        if (confirmar) {
            tr.remove();
            actualizarTotalBultos();
        }
    });

    // Agregar tooltip para destinos largos
    const tdDestino = tr.querySelector('td:nth-child(2)');
    tdDestino.addEventListener('click', function() {
        if (window.innerWidth <= 576) {
            mostrarAlerta('Destino', this.textContent, 'info');
        }
    });

    return tr;
}

    // Función para eliminar un remito específico por su número
    function eliminarRemito(remito) {
        const row = Array.from(document.querySelectorAll('#bodyRemitos tr')).find(tr => {
            return tr.querySelector('td').textContent.trim() === remito;
        });

        if (row) {
            row.remove(); // Eliminar la fila de la tabla
            actualizarTotalBultos(); // Actualizar el total de bultos si es necesario
        }
    }

    async function eliminarEgreso(comprobante) {
        const confirmar = await confirmarAccion('¿Está seguro?', 'Se eliminará este egreso', 'warning');
            if (confirmar) {
        
    
            const row = Array.from(document.querySelectorAll('#bodyEgresos tr')).find(tr => {
                return tr.querySelectorAll('td')[1].textContent.trim() === comprobante.trim();
            });

            if (row) {
                row.remove();
            }
        }
    }

    // Configuración del pad de firma
    function initSignaturePad() {
        var canvas = document.getElementById('signature-pad');
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        
        var signaturePadWidth = canvas.parentElement.offsetWidth - 30;
        
        canvas.width = signaturePadWidth * ratio;
        canvas.height = (150 * ratio);
        canvas.style.width = signaturePadWidth + 'px';
        canvas.style.height = '150px';
        
        var ctx = canvas.getContext('2d');
        ctx.scale(ratio, ratio);
        
        return new SignaturePad(canvas, {
            backgroundColor: 'white',
            penColor: 'black',
            velocityFilterWeight: 0.7,
            minWidth: 0.5,
            maxWidth: 2.5,
            throttle: 16
        });
    }

    // Inicializar SignaturePad
    signaturePad = initSignaturePad();

    // Evento para agregar remito
    document.getElementById('btnAgregarRemito').addEventListener('click', async function() {
        const select = document.getElementById('selectRemitos');
        if (!select.value) {
            await mostrarAlerta('Error', 'Por favor, seleccione un remito');
            return;
        }

        const datos = JSON.parse(select.value);
        const tbody = document.getElementById('bodyRemitos');
        
        const remitosExistentes = tbody.querySelectorAll('tr td:first-child');
        for (let td of remitosExistentes) {
            if (td.textContent === datos.remito) {
                await mostrarAlerta('Error', 'Este remito ya ha sido agregado');
                return;
            }
        }

        tbody.appendChild(crearFilaRemito(datos));
        actualizarTotalBultos();
        select.value = '';
    });

    // Manejar el redimensionamiento de la ventana
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            var canvas = document.getElementById('signature-pad');
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            var newWidth = canvas.parentElement.offsetWidth - 30;
            
            var signatureData = signaturePad.toData();
            
            canvas.width = newWidth * ratio;
            canvas.height = 150 * ratio;
            canvas.style.width = newWidth + 'px';
            canvas.style.height = '150px';
            
            var ctx = canvas.getContext('2d');
            ctx.scale(ratio, ratio);
            signaturePad.clear();
            if (signatureData) {
                signaturePad.fromData(signatureData);
            }
        }, 200);
    });

    // Manejar la visibilidad del número de precinto
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

    // Botón para limpiar la firma
    document.getElementById('clear').addEventListener('click', async function() {
        const confirmar = await confirmarAccion('¿Está seguro?', 'Se limpiará la firma actual', 'warning');
        if (confirmar) {
            signaturePad.clear();
        }
    });

    // Funciones de validación
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
    
            const egresos = document.querySelectorAll('#bodyEgresos tr');
            if (egresos.length === 0) {
                await mostrarAlerta('Error', 'Debe agregar al menos un egreso cuando envía valores.');
                return false;
            }
        }
        return true;
    }
    

    async function validarFormulario() {
        const remitos = document.querySelectorAll('#bodyRemitos tr');
        if (remitos.length === 0) {
            await mostrarAlerta('Error', 'Debe cargar al menos un remito.');
            return false;
        }
    
        return (
            await validarSelects() &&
            await validarFirma() &&
            await validarPrecinto() && 
            validarEgresos()
        );
    }
    
    // Reiniciar formulario
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

    
    // Prevenir el zoom en dispositivos móviles
    document.addEventListener('touchstart', function(e) {
        if (e.touches.length > 1) {
            e.preventDefault();
        }
    }, { passive: false });

    // Deshabilitar el scroll en firma
    document.getElementById('signature-pad').addEventListener('touchmove', function(e) {
        e.preventDefault();
    }, { passive: false });

    // Función para crear una fila de egreso
// Función para crear una fila de egreso (CORREGIDA)
function crearFilaEgreso(datos) {
    const tr = document.createElement('tr');
    
    // CORRECCIÓN CLAVE: Asignar los dataset para que luego se puedan leer
    tr.dataset.tipo = datos.tipo;
    tr.dataset.comprobante = datos.comprobante;
    tr.dataset.fecha = datos.fecha;

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

    // Evento para agregar egreso
    document.getElementById('btnAgregarEgreso').addEventListener('click', async function() {
        const select = document.getElementById('selectEgresos');
        if (!select.value) {
            await mostrarAlerta('Error', 'Por favor, seleccione un egreso');
            return;
        }

        const datos = JSON.parse(select.value);
        const tbody = document.getElementById('bodyEgresos');
        
        // Verificar si el egreso ya está agregado
        const egresosExistentes = tbody.querySelectorAll('tr td:first-child');
        for (let td of egresosExistentes) {
            if (td.textContent === datos.comprobante) {
                await mostrarAlerta('Error', 'Este egreso ya ha sido agregado');
                return;
            }
        }

        tbody.appendChild(crearFilaEgreso(datos));
        select.value = ''; // Limpiar la selección
    });

    // Función para manejar el guardado 
    async function guardarFormulario() {
        if (!(await validarFormulario())) {
            return false;
        }
    
        try {
            const datos = obtenerDatosFormulario();
            console.log('Datos a guardar:', datos);
    
            // Aquí iría el código para guardar los datos
            await mostrarAlerta('¡Éxito!', 'Datos guardados correctamente', 'success');
            return true;
        } catch (error) {
            console.error('Error:', error);
            await mostrarAlerta('Error', 'Error al guardar los datos: ' + error.message);
            return false;
        }
    }

  
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

const validarEgresos = () => {
  
    if(document.getElementById('enviaValores').value == 'SI'){
        let egresos = Array.from(document.querySelectorAll('#bodyEgresos tr')).map(tr => ({
            tipo: tr.cells[0].textContent,
            comprobante: tr.cells[1].textContent,
            fecha: tr.cells[2].textContent
        }));
       
        if(egresos.length == 0){
            mostrarAlerta('Error', 'Debe cargar al menos un egreso.');
            console.log("aca1");
            return false;
        }else{
            console.log("aca");
            return true;
        }

    }else{
        return true
    }
}


const registrar = async () => {
    

    const datos = obtenerDatosFormulario();

    let firmaBase64 = signaturePad.toDataURL('image/jpeg', 0.8);
    let nroSucursal = document.querySelector("#numSucurs").textContent;
    
    const response = await fetch('/administracion/tesoreria/Controller/upload_image.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            firma: firmaBase64,
            nro_registro: datos.datos.numeroRegistro,
            sucursal: document.querySelector("#numSucurs").textContent}),
        });
        
        const respuestaDatos = await response.json();
        
        let firma = (respuestaDatos.filePath);
        console.log("antes");

    if (!(await validarFormulario())) {
        return;
    }
    console.log("paso");
    
    try {
        const confirmar = await confirmarAccion(
            '¿Confirmar registro?',
            'Esta acción no se puede deshacer',
            'question'
        );
        
        if (!confirmar) return;
        
     

            $.ajax({
                url: '/administracion/tesoreria/Controller/retiroController.php?accion=actualizar',
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
                        console.log(result)
                        if (result.isConfirmed) {
                            window.location.href = 'listarRetiros.php';
                        }
                    });
    
    
                }
            })
            
     
            // Aquí iría el código para registrar los datos
    
        } catch (error) {
            console.error('Error:', error);
            await mostrarAlerta('Error', 'Error al registrar el formulario: ' + error.message);
        }
      
};



// Función para obtener datos (MEJORADA)
function obtenerDatosFormulario() {
    // Protección para la firma
    let firmaData = '';
    try {
        firmaData = signaturePad.toDataURL();
    } catch(e) {
        console.warn("Error leyendo firma o firma vacía");
    }

    const datos = {
        numeroRegistro: document.getElementById('numeroRegistro').value,
        entrego: (document.getElementById('entrego').value).split('++')[0],
        recibio: document.getElementById('recibio').value,
        enviaValores: document.getElementById('enviaValores').value,
        observaciones: document.getElementById('observaciones').value,
        firma: firmaData
    };

    if (datos.enviaValores === 'SI') {
        datos.numeroPrecinto = document.getElementById('numeroPrecinto').value.trim();
        
        datos.egresos = Array.from(document.querySelectorAll('#bodyEgresos tr')).map(tr => ({
            tipo: tr.dataset.tipo || tr.cells[0].textContent, // Fallback por si falla el dataset
            comprobante: tr.dataset.comprobante || tr.cells[1].textContent,
            fecha: tr.dataset.fecha || tr.cells[2].textContent
        }));
    }

    const remitos = Array.from(document.querySelectorAll('#bodyRemitos tr')).map(tr => ({
        remito: tr.cells[0].textContent,
        destino: tr.cells[1].textContent,
        bultos: tr.querySelector('.input-bultos').value,
        // IMPORTANTE: Si el dataset falla, intenta buscar en la celda oculta (indice 4)
        fecha: tr.dataset.fecha || (tr.cells[4] ? tr.cells[4].textContent : ''),
        t_comp: tr.dataset.tcomp || (tr.cells[5] ? tr.cells[5].textContent : 'REM')
    }));

    return {
        datos: datos,
        remitos: remitos
    };
}

