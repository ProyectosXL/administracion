
/**
 * Gestión de Contratos de Alquiler - JavaScript Modernizado
 * Funcionalidades mejoradas con mejor UX y mensajes más claros
 */

$(document).ready(function() {
    // Configuración inicial de Select2
    $("#selectSucursal").select2({
        placeholder: "Seleccione una sucursal",
        allowClear: true,
        width: '100%'
    });
});

/**
 * Función principal para guardar el contrato
 * Valida los datos y procede con el guardado
 */
const guardar = () => {
    // Obtener valores de los campos
    const formData = getFormData();
    
    // Validar campos obligatorios
    const validation = validateForm(formData);
    if (!validation.isValid) {
        showValidationError(validation.message);
        return;
    }

    // Verificar solapamiento de contratos antes de continuar
    verificarSolapamientoContratos(formData);
};

/**
 * Obtiene y procesa los datos del formulario
 * @returns {Object} Datos del formulario procesados
 */
function getFormData() {
    const desde = document.querySelector("#desde").value;
    const hasta = document.querySelector("#hasta").value;
    const sucursal = document.querySelector("#selectSucursal").value;
    
    const [idSucursal, descSucursal] = sucursal.split("-");
    
    // Procesar importes (remover símbolos y convertir a números)
    const valorLlave = parseAmount(document.querySelector("#valorLlave").value);
    const comisiones = parseAmount(document.querySelector("#comisiones").value);
    const lanzamiento = parseAmount(document.querySelector("#lanzamiento").value);
    
    return {
        desde,
        hasta,
        idSucursal,
        descSucursal,
        valorLlave,
        comisiones,
        lanzamiento
    };
}

/**
 * Procesa un importe, removiendo símbolos y convirtiendo a número
 * @param {string} value - Valor del campo
 * @returns {number} Valor numérico
 */
function parseAmount(value) {
    if (!value || value.trim() === "") return 0;
    return parseInt(value.replace(/[$.]/g, "")) || 0;
}

/**
 * Valida los datos del formulario
 * @param {Object} formData - Datos del formulario
 * @returns {Object} Resultado de la validación
 */
function validateForm(formData) {
    if (!formData.desde) {
        return { 
            isValid: false, 
            message: "Debe seleccionar la fecha de inicio del contrato" 
        };
    }
    
    if (!formData.hasta) {
        return { 
            isValid: false, 
            message: "Debe seleccionar la fecha de finalización del contrato" 
        };
    }
    
    if (!formData.idSucursal) {
        return { 
            isValid: false, 
            message: "Debe seleccionar una sucursal" 
        };
    }
    
    // Validar que la fecha de inicio sea anterior a la de fin
    if (new Date(formData.desde) >= new Date(formData.hasta)) {
        return { 
            isValid: false, 
            message: "La fecha de inicio debe ser anterior a la fecha de finalización" 
        };
    }
    
    // Validar que las fechas no sean muy antiguas (más de 5 años atrás)
    const fechaLimite = new Date();
    fechaLimite.setFullYear(fechaLimite.getFullYear() - 5);
    
    if (new Date(formData.desde) < fechaLimite) {
        return { 
            isValid: false, 
            message: "La fecha de inicio no puede ser mayor a 5 años en el pasado" 
        };
    }
    
    // Validar que las fechas no sean muy futuras (más de 2 años)
    const fechaFuturaLimite = new Date();
    fechaFuturaLimite.setFullYear(fechaFuturaLimite.getFullYear() + 5);
    
    if (new Date(formData.hasta) > fechaFuturaLimite) {
        return { 
            isValid: false, 
            message: "La fecha de finalización no puede ser mayor a 5 años en el futuro" 
        };
    }
    
    return { isValid: true };
}

/**
 * Muestra error de validación
 * @param {string} message - Mensaje de error
 */
function showValidationError(message) {
    Swal.fire({
        icon: "error",
        title: "Datos incompletos",
        text: message,
        confirmButtonText: "Entendido",
        confirmButtonColor: "#e74c3c"
    });
}

/**
 * Muestra advertencia cuando no hay importes cargados
 * @param {Object} formData - Datos del formulario
 */
function showEmptyAmountsWarning(formData) {
    Swal.fire({
        icon: "warning",
        title: "Contrato sin importes",
        text: "Está a punto de guardar un contrato sin importes cargados. ¿Desea continuar?",
        showDenyButton: true,
        confirmButtonText: "Sí, continuar",
        denyButtonText: "No, revisar",
        confirmButtonColor: "#f39c12",
        denyButtonColor: "#95a5a6"
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar confirmación final incluso sin importes
            showSaveConfirmation(formData);
        }
        // Si cancela, no hace nada (regresa al formulario)
    });
}

/**
 * Envía los datos al servidor
 * @param {Object} formData - Datos del formulario
 */
const enviarData = (formData) => {
    // Mostrar indicador de carga
    Swal.fire({
        title: 'Guardando contrato',
        text: 'Por favor espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'Controller/ContratoController.php?accion=guardarContratoAlquiler',
        type: 'POST',
        data: {
            desde: formData.desde,
            hasta: formData.hasta,
            idSucursal: formData.idSucursal,
            descSucursal: formData.descSucursal,
            valorLlave: formData.valorLlave,
            comisiones: formData.comisiones,
            lanzamiento: formData.lanzamiento
        },
        success: function(response) {
            console.log('Respuesta del servidor:', response); // Para debugging
            handleSaveResponse(response, formData);
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', xhr, status, error); // Para debugging
            handleSaveError(error);
        }
    });
};

/**
 * Verifica si existe solapamiento de contratos para la sucursal y fechas seleccionadas
 * @param {Object} formData - Datos del formulario
 */
function verificarSolapamientoContratos(formData) {
    // Mostrar indicador de carga para la verificación
    Swal.fire({
        title: 'Verificando contratos existentes',
        text: 'Por favor espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'Controller/ContratoController.php?accion=verificarSolapamientoContrato',
        type: 'POST',
        dataType: 'json',
        headers: { 'Accept': 'application/json' },
        data: {
            idSucursal: formData.idSucursal,
            desde: formData.desde,
            hasta: formData.hasta
        },
        success: function(response, textStatus, jqXHR) {
            handleSolapamientoResponse(response, formData, jqXHR);
        },
        error: function(xhr, status, error) {
            console.error('Error al verificar solapamiento:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de verificación',
                text: 'No se pudo verificar los contratos existentes. Intente nuevamente.',
                confirmButtonText: "Entendido",
                confirmButtonColor: "#e74c3c"
            });
        }
    });
}

/**
 * Maneja la respuesta de verificación de solapamiento
 * @param {*} response - Respuesta del servidor
 * @param {Object} formData - Datos del formulario
 */
function handleSolapamientoResponse(response, formData, jqXHR) {
    try {
        console.debug('Solapamiento response:', response);
        
        // Utilidad local para verificar objeto
        const isObj = (v) => v !== null && typeof v === 'object';

        // Determinar la respuesta correcta
        let resultado = null;
        
        if (isObj(response)) {
            resultado = response;
        } else if (jqXHR && isObj(jqXHR.responseJSON)) {
            resultado = jqXHR.responseJSON;
        } else if (typeof response === 'string') {
            try { 
                resultado = JSON.parse(response); 
            } catch (e) {
                console.error('Error parsing response:', e);
            }
        }

        // Verificar que tenemos un objeto válido con la estructura esperada
        if (!isObj(resultado) || typeof resultado.solapamiento !== 'boolean') {
            throw new Error('Respuesta inválida del servidor');
        }
        
        console.log('Resultado procesado:', resultado);
        console.log('¿Hay solapamiento?', resultado.solapamiento);
        
        if (resultado.solapamiento === true) {
            // Hay solapamiento, mostrar detalles del conflicto
            const contratoExistente = resultado.contrato_existente || {};
            
            Swal.fire({
                icon: 'warning',
                title: 'Conflicto de contratos detectado',
                html: `
                    <div style="text-align: left; margin: 20px 0;">
                        <p><strong>Ya existe un contrato para esta sucursal que se solapa con el período seleccionado:</strong></p>
                        <br>
                        <p><strong>Contrato existente:</strong></p>
                        <p>• Sucursal: ${contratoExistente.DESC_SUCURS || 'N/A'}</p>
                        <p>• Vigencia: ${formatearFecha(contratoExistente.VIG_DESDE)} - ${formatearFecha(contratoExistente.VIG_HASTA)}</p>
                        <br>
                        <p><strong>Período que intenta cargar:</strong></p>
                        <p>• Desde: ${formatearFecha(formData.desde)} - Hasta: ${formatearFecha(formData.hasta)}</p>
                        <br>
                        <p style="color: #e74c3c;"><strong>Los períodos no pueden solaparse.</strong></p>
                    </div>
                `,
                confirmButtonText: "Entendido",
                confirmButtonColor: "#e74c3c",
                width: '600px'
            });
        } else {
            // No hay solapamiento, proceder con la validación de importes
            console.log('No hay solapamiento, procediendo...');
            proceedWithAmountValidation(formData);
        }
        
    } catch (error) {
        console.error('Error al parsear respuesta de solapamiento:', error);
        console.error('Response original:', response);
        
        Swal.fire({
            icon: 'error',
            title: 'Error de procesamiento',
            text: 'Hubo un error al procesar la verificación de solapamiento. Intente nuevamente.',
            confirmButtonText: "Entendido",
            confirmButtonColor: "#e74c3c"
        });
    }
}

/**
 * Procede con la validación de importes después de verificar solapamiento
 * @param {Object} formData - Datos del formulario
 */
function proceedWithAmountValidation(formData) {
    // Verificar si no hay importes cargados
    if (formData.valorLlave === 0 && formData.comisiones === 0 && formData.lanzamiento === 0) {
        showEmptyAmountsWarning(formData);
        return;
    }
    
    // Mostrar confirmación antes de guardar
    showSaveConfirmation(formData);
}

/**
 * Muestra confirmación antes de guardar el contrato
 * @param {Object} formData - Datos del formulario
 */
function showSaveConfirmation(formData) {
    const importesTexto = [];
    if (formData.valorLlave > 0) importesTexto.push(`Valor Llave: ${formData.valorLlave.toLocaleString()}`);
    if (formData.comisiones > 0) importesTexto.push(`Comisiones: ${formData.comisiones.toLocaleString()}`);
    if (formData.lanzamiento > 0) importesTexto.push(`FPC Lanzamiento: ${formData.lanzamiento.toLocaleString()}`);

    const importesInfo = importesTexto.length > 0 
        ? `<br><strong>Importes:</strong><br>• ${importesTexto.join('<br>• ')}<br>` 
        : '<br><em>Sin importes cargados</em><br>';

    Swal.fire({
        icon: 'question',
        title: '¿Confirma el guardado del contrato?',
        html: `
            <div style="text-align: left; margin: 15px 0;">
                <p><strong>Sucursal:</strong> ${formData.descSucursal}</p>
                <p><strong>Período:</strong> ${formatearFecha(formData.desde)} - ${formatearFecha(formData.hasta)}</p>
                ${importesInfo}
            </div>
        `,
        showDenyButton: true,
        confirmButtonText: "Sí, guardar contrato",
        denyButtonText: "No, revisar datos",
        confirmButtonColor: "#27ae60",
        denyButtonColor: "#95a5a6",
        width: '500px'
    }).then((result) => {
        if (result.isConfirmed) {
            enviarData(formData);
        }
        // Si cancela, no hace nada (regresa al formulario)
    });
}

/**
 * Formatea una fecha para mostrar en formato legible
 * @param {string} fecha - Fecha en formato YYYY-MM-DD
 * @returns {string} Fecha formateada
 */
function formatearFecha(fecha) {
    const fechaObj = new Date(fecha + 'T00:00:00');
    return fechaObj.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Maneja la respuesta del servidor al guardar
 * @param {*} response - Respuesta del servidor
 * @param {Object} formData - Datos enviados
 */
function handleSaveResponse(response, formData) {
    // Limpiar espacios en blanco y convertir a string para comparación
    const cleanResponse = String(response).trim();
    
    console.log('Respuesta limpia:', cleanResponse); // Para debugging
    
    // Verificar diferentes variaciones de "true" que puede devolver PHP
    if (cleanResponse === 'true' || cleanResponse === '1' || cleanResponse === 'TRUE' || cleanResponse === true) {
        Swal.fire({
            icon: 'success',
            title: '¡Contrato guardado exitosamente!',
            text: `Se ha registrado el contrato para ${formData.descSucursal}`,
            confirmButtonText: 'Continuar',
            confirmButtonColor: '#27ae60',
            timer: 3000,
            timerProgressBar: true
        }).then(() => {
            // Limpiar formulario después del guardado exitoso
            clearForm();
        });
    } else if (cleanResponse === 'false' || cleanResponse === '0' || cleanResponse === 'FALSE' || cleanResponse === false) {
        // Error conocido del servidor (probablemente solapamiento)
        Swal.fire({
            icon: 'error',
            title: 'No se pudo guardar el contrato',
            text: 'Ya existe un contrato que se solapa con el período seleccionado para esta sucursal.',
            confirmButtonText: "Entendido",
            confirmButtonColor: "#e74c3c",
            footer: '<small>Verifique las fechas e intente nuevamente</small>'
        });
    } else {
        // Respuesta inesperada del servidor
        console.warn('Respuesta inesperada del servidor:', response);
        Swal.fire({
            icon: 'warning',
            title: 'Respuesta inesperada del servidor',
            text: `El servidor devolvió: "${cleanResponse}". Por favor, verifique si el contrato se guardó correctamente.`,
            confirmButtonText: "Entendido",
            confirmButtonColor: "#f39c12",
            footer: '<small>Consulte con el administrador del sistema si el problema persiste</small>'
        });
    }
}

/**
 * Maneja errores de conexión o servidor
 * @param {string} error - Error ocurrido
 */
function handleSaveError(error) {
    console.error('Error al guardar:', error);
    Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo conectar con el servidor. Verifique su conexión e intente nuevamente.',
        confirmButtonText: "Reintentar",
        confirmButtonColor: "#e74c3c"
    });
}

/**
 * Limpia el formulario después de un guardado exitoso
 */
function clearForm() {
    document.querySelector("#desde").value = "";
    document.querySelector("#hasta").value = "";
    document.querySelector("#valorLlave").value = "";
    document.querySelector("#comisiones").value = "";
    document.querySelector("#lanzamiento").value = "";
    
    // Resetear Select2
    $("#selectSucursal").val(null).trigger('change');
    
    // Enfocar en el primer campo
    document.querySelector("#selectSucursal").focus();
}

/**
 * Formatea números como moneda con separadores de miles
 * @param {HTMLElement} input - Elemento input
 */
const parseNumber = (input) => {
    if (!input.value || input.value.trim() === "") {
        return;
    }
    
    try {
        // Remover símbolos existentes y obtener solo números
        const cleanValue = input.value.replace(/[^0-9]/g, "");
        
        if (!cleanValue) {
            input.value = "";
            return;
        }
        
        const numero = parseInt(cleanValue);
        
        // Formatear con separadores de miles
        const formattedNumber = numero.toLocaleString('de-DE', {
            style: 'decimal',
            maximumFractionDigits: 0,
            minimumFractionDigits: 0
        });
        
        input.value = "$" + formattedNumber;
        
    } catch (error) {
        console.error('Error al formatear número:', error);
        // En caso de error, mantener el valor original sin el símbolo $
        input.value = input.value.replace(/[^0-9]/g, "");
    }
};

/**
 * Cambia el entorno (ARG/UY)
 * @param {HTMLElement} toggle - Elemento toggle
 */
const cambiarEntorno = (toggle) => {
    // Determinar el entorno basado en si el toggle está marcado
    // checked = true significa Argentina (central), false significa Uruguay (uy)
    const entorno = toggle.checked ? 0 : 1;
    
    console.log('Toggle checked:', toggle.checked, 'Entorno:', entorno);
    
    // Mostrar indicador de carga
    Swal.fire({
        title: 'Cambiando entorno',
        text: 'Por favor espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: "../Controller/cambiarEntorno.php",
        method: "POST",
        data: { entorno: entorno },
        dataType: 'json',
        success: function(data) {
            console.log('Respuesta cambio entorno:', data);
            if (data.success) {
                // Recargar página después de cambiar entorno
                setTimeout(() => {
                    location.reload();
                }, 100);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cambiar entorno',
                    text: data.message || 'Error desconocido',
                    confirmButtonText: "Entendido",
                    confirmButtonColor: "#e74c3c"
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cambiar entorno:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error al cambiar entorno',
                text: 'No se pudo cambiar el entorno. Intente nuevamente.',
                confirmButtonText: "Entendido",
                confirmButtonColor: "#e74c3c"
            });
        }
    });
};

// Eventos adicionales para mejorar la UX
$(document).ready(function() {
    // Autocompletar fecha actual en campo "desde" si está vacío
    const desdeInput = document.querySelector("#desde");
    if (desdeInput && !desdeInput.value) {
        const today = new Date().toISOString().split('T')[0];
        desdeInput.value = today;
    }
    
    // Validación en tiempo real para fechas (con debounce)
    const hastaInput = document.querySelector("#hasta");
    if (hastaInput) {
        let timeoutId;
        hastaInput.addEventListener('input', function() {
            // Limpiar timeout anterior
            clearTimeout(timeoutId);
            
            // Solo validar después de 1 segundo sin escribir
            timeoutId = setTimeout(() => {
                const desde = document.querySelector("#desde").value;
                if (desde && this.value && new Date(desde) >= new Date(this.value)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Fechas inválidas',
                        text: 'La fecha de finalización debe ser posterior a la fecha de inicio',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            }, 1000); // Esperar 1 segundo después de dejar de escribir
        });
        
        // Validación más suave en blur (cuando pierde el foco)
        hastaInput.addEventListener('blur', function() {
            const desde = document.querySelector("#desde").value;
            if (desde && this.value && new Date(desde) >= new Date(this.value)) {
                this.focus();
            }
        });
    }
});
