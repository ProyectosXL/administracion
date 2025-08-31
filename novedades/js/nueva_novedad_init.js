/**
 * Inicialización del formulario de nueva novedad
 * Maneja la configuración inicial y eventos
 */

// Compartir variables globales con el archivo principal
let modoActual, contadorNovedades, novedadesData;

/**
 * Inicialización del formulario de nueva novedad
 * Maneja la configuración inicial y eventos
 */

// Variables globales compartidas (declaradas aquí para compatibilidad)
if (typeof window.modoActual === 'undefined') {
    window.modoActual = null;
}
if (typeof window.contadorNovedades === 'undefined') {
    window.contadorNovedades = 0;
}
if (typeof window.novedadesData === 'undefined') {
    window.novedadesData = [];
}

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando formulario de nueva novedad...');
    
    // Inicializar modos de carga
    if (typeof initializarModosCarga === 'function') {
        initializarModosCarga();
    }
    
    // Configurar eventos del formulario original
    configurarFormularioOriginal();
    
    // Configurar empleado selector
    configurarEmpleadoSelector();
    
    console.log('✅ Formulario inicializado correctamente');
});

/**
 * Configurar formulario original (modo único)
 */
function configurarFormularioOriginal() {
    // Configurar select de tipo de novedad original
    const tipoNovedadSelect = document.getElementById('tipo_novedad');
    if (tipoNovedadSelect) {
        tipoNovedadSelect.addEventListener('change', function() {
            if (modoActual === 'unica' || modoActual === null) {
                // Llamar a la función original de cambio de tipo
                if (typeof onTipoNovedadChangeActualizado === 'function') {
                    onTipoNovedadChangeActualizado(this);
                } else if (typeof window.onTipoNovedadChangeSpecific === 'function') {
                    window.onTipoNovedadChangeSpecific(this);
                } else {
                    console.warn('⚠️ Función de cambio de tipo de novedad no encontrada');
                }
            }
        });
    }
    
    // Cargar tipos de novedad
    cargarTiposNovedadOriginal();
}

/**
 * Configurar selector de empleado
 */
function configurarEmpleadoSelector() {
    // Configurar Select2 para empleados si está disponible
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#empleado-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar empleado por nombre o legajo...',
            allowClear: true,
            ajax: {
                url: 'controller/novedades_controller.php?accion=buscar_empleados',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    if (data.success) {
                        return {
                            results: data.data.map(emp => ({
                                id: emp.legajo,
                                text: `${emp.legajo} - ${emp.apellido}, ${emp.nombre}`,
                                nombre: emp.nombre,
                                apellido: emp.apellido,
                                centro_costos: emp.codigo_centro_costos,
                                descripcion_centro: emp.descripcion_centro_costos
                            }))
                        };
                    }
                    return { results: [] };
                }
            }
        });
        
        // Evento de selección de empleado
        $('#empleado-select').on('select2:select', function (e) {
            const data = e.params.data;
            
            // Guardar datos del empleado
            document.getElementById('legajo').value = data.id;
            document.getElementById('centro_costos').value = data.centro_costos || '';
            document.getElementById('empleado-centro-costos').textContent = 
                `${data.centro_costos || 'N/A'} - ${data.descripcion_centro || 'Sin descripción'}`;
            
            // Guardar en NovedadesApp si está disponible
            if (window.NovedadesApp) {
                window.NovedadesApp.empleadoSeleccionado = {
                    legajo: data.id,
                    nombre: data.nombre,
                    apellido: data.apellido,
                    centro_costos: data.centro_costos,
                    descripcion_centro: data.descripcion_centro
                };
            }
            
            // Remover clases de validación
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        });
        
        // Evento de limpieza
        $('#empleado-select').on('select2:clear', function () {
            document.getElementById('legajo').value = '';
            document.getElementById('centro_costos').value = '';
            document.getElementById('empleado-centro-costos').textContent = 
                'Seleccione un empleado para ver su centro de costos';
            
            if (window.NovedadesApp) {
                window.NovedadesApp.empleadoSeleccionado = null;
            }
        });
    }
}

/**
 * Cargar tipos de novedad en el select original
 */
async function cargarTiposNovedadOriginal() {
    try {
        const response = await fetch('controller/novedades_controller.php?accion=get_tipos_novedad');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('tipo_novedad');
            if (select) {
                select.innerHTML = '<option value="">Seleccione tipo de novedad...</option>';
                data.data.forEach(tipo => {
                    select.innerHTML += `<option value="${tipo.id}">${tipo.descripcion}</option>`;
                });
            }
        }
    } catch (error) {
        console.error('Error cargando tipos de novedad:', error);
    }
}

/**
 * Función para limpiar formulario manual
 */
function limpiarFormularioManual() {
    if (confirm('¿Está seguro que desea limpiar el formulario? Se perderán todos los datos ingresados.')) {
        if (window.modoActual === 'multiple') {
            if (typeof limpiarFormularioMultiple === 'function') {
                limpiarFormularioMultiple();
            } else if (typeof window.limpiarFormularioMultiple === 'function') {
                window.limpiarFormularioMultiple();
            }
        } else {
            limpiarFormularioUnico();
        }
    }
}

/**
 * Limpiar formulario en modo único
 */
function limpiarFormularioUnico() {
    document.getElementById('form-novedad').reset();
    
    // Limpiar Select2
    if (typeof $ !== 'undefined' && $('#empleado-select').length) {
        $('#empleado-select').val(null).trigger('change');
    }
    
    // Limpiar datos del empleado
    document.getElementById('legajo').value = '';
    document.getElementById('centro_costos').value = '';
    document.getElementById('empleado-centro-costos').textContent = 
        'Seleccione un empleado para ver su centro de costos';
    
    // Ocultar configuraciones
    if (typeof ocultarTodasLasConfiguraciones === 'function') {
        ocultarTodasLasConfiguraciones();
    }
    
    // Limpiar NovedadesApp
    if (window.NovedadesApp) {
        window.NovedadesApp.empleadoSeleccionado = null;
    }
    
    // Remover clases de validación
    document.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
        el.classList.remove('is-invalid', 'is-valid');
    });
}

/**
 * Función para volver (modo compatibilidad)
 */
function volverAtras() {
    if (window.modoActual === null) {
        window.history.back();
    } else {
        // Si estamos en un modo específico, volver a la selección de modo
        if (confirm('¿Desea volver a la selección de modo? Se perderán los datos ingresados.')) {
            if (window.modoActual === 'multiple') {
                if (typeof limpiarFormularioMultiple === 'function') {
                    limpiarFormularioMultiple();
                } else if (typeof window.limpiarFormularioMultiple === 'function') {
                    window.limpiarFormularioMultiple();
                }
            } else {
                limpiarFormularioUnico();
            }
        }
    }
}

// Funciones de compatibilidad con el sistema existente
window.limpiarFormularioManual = limpiarFormularioManual;
window.volverAtras = volverAtras;
window.limpiarFormularioSinConfirmacion = function() {
    if (window.modoActual === 'multiple') {
        if (typeof limpiarFormularioMultiple === 'function') {
            limpiarFormularioMultiple();
        } else if (typeof window.limpiarFormularioMultiple === 'function') {
            window.limpiarFormularioMultiple();
        }
    } else {
        limpiarFormularioUnico();
    }
};

/**
 * Inicializar NovedadesApp si no existe
 */
if (!window.NovedadesApp) {
    window.NovedadesApp = {
        empleadoSeleccionado: null,
        
        // Función request básica
        request: async function(accion, datos = {}, metodo = 'GET') {
            const url = `controller/novedades_controller.php?accion=${accion}`;
            
            const opciones = {
                method: metodo,
                headers: {
                    'Content-Type': 'application/json',
                }
            };
            
            if (metodo === 'POST') {
                opciones.body = JSON.stringify(datos);
            }
            
            const response = await fetch(url, opciones);
            const resultado = await response.json();
            
            if (!resultado.success) {
                throw new Error(resultado.message || 'Error en la petición');
            }
            
            return resultado.data;
        },
        
        // Función para mostrar errores
        mostrarError: function(mensaje) {
            console.error('Error:', mensaje);
            alert(mensaje); // Fallback simple
        }
    };
}
