
document.addEventListener('DOMContentLoaded', function() {
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
            confirmButtonColor: '#0d6efd'
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

    // Función para generar el número de registro
    function generarNumeroRegistro() {
        return 'C' + String(numeroRegistro).padStart(10, '0');
    }

    // Inicializar el número de registro
    document.getElementById('numeroRegistro').value = generarNumeroRegistro();

    // Función para actualizar el total de bultos
    function actualizarTotalBultos() {
        const inputs = document.querySelectorAll('.input-bultos');
        const total = Array.from(inputs).reduce((sum, input) => sum + parseInt(input.value || 0), 0);
        document.getElementById('totalBultos').textContent = total;
    }

    // Función para crear una fila de remito
    function crearFilaRemito(datos) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-nowrap">${datos.remito}</td>
            <td title="${datos.destino}">${datos.destino}</td>
            <td>
                <input type="number" 
                       class="form-control form-control-sm input-bultos mt-3" 
                       value="1" 
                       min="1">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm btn-quitar">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
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

        // Agregar tooltip para destinos largos en móviles
        const tdDestino = tr.querySelector('td:nth-child(2)');
        tdDestino.addEventListener('click', function() {
            if (window.innerWidth <= 576) {
                mostrarAlerta('Destino', this.textContent, 'info');
            }
        });

        return tr;
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
        }
        return true;
    }

    async function validarRemitos() {
        const remitos = document.querySelectorAll('#bodyRemitos tr');
        if (remitos.length === 0) {
            await mostrarAlerta('Error', 'Debe agregar al menos un remito');
            return false;
        }
        return true;
    }

    // Validación completa del formulario
    async function validarFormulario() {
        return await validarSelects() && 
               await validarFirma() && 
               await validarPrecinto() &&
               await validarRemitos();
    }

    // Obtener datos del formulario
    function obtenerDatosFormulario() {
        const datos = {
            numeroRegistro: document.getElementById('numeroRegistro').value,
            entrego: document.getElementById('entrego').value,
            recibio: document.getElementById('recibio').value,
            enviaValores: document.getElementById('enviaValores').value,
            observaciones: document.getElementById('observaciones').value,
            firma: signaturePad.toDataURL()
        };

        if (datos.enviaValores === 'SI') {
            datos.numeroPrecinto = document.getElementById('numeroPrecinto').value.trim();
        }

        const remitos = Array.from(document.querySelectorAll('#bodyRemitos tr')).map(tr => ({
            remito: tr.cells[0].textContent,
            destino: tr.cells[1].textContent,
            bultos: tr.querySelector('.input-bultos').value
        }));

        return {
            datos: datos,
            remitos: remitos
        };
    }

    // Reiniciar formulario
    function reiniciarFormulario() {
        document.getElementById('entregaForm').reset();
        document.getElementById('precintoContainer').style.display = 'none';
        document.getElementById('numeroPrecinto').required = false;
        document.getElementById('bodyRemitos').innerHTML = '';
        document.getElementById('totalBultos').textContent = '0';
        signaturePad.clear();
        numeroRegistro++;
        document.getElementById('numeroRegistro').value = generarNumeroRegistro();
    }

    // Manejar el envío del formulario
    document.getElementById('entregaForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!(await validarFormulario())) {
            return;
        }

        try {
            const datos = obtenerDatosFormulario();
            console.log('Datos a enviar:', datos);

            // Aquí iría el código para enviar los datos al servidor
            await mostrarAlerta('¡Éxito!', 'Formulario enviado correctamente', 'success');
            reiniciarFormulario();

        } catch (error) {
            console.error('Error:', error);
            await mostrarAlerta('Error', 'Error al procesar el formulario: ' + error.message);
        }
    });

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
});