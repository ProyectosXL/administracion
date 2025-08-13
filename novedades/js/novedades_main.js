// /novedades/js/novedades_main.js

/**
 * Configuración global y utilidades
 */
const NovedadesApp = {
    baseUrl: 'controller/novedades_controller.php',
    empleadoSeleccionado: null,
    
    // Configuración de tipos de novedad y campos requeridos - ACTUALIZADA
    tiposNovedadConfig: {
        1: ['nueva_sucursal', 'fecha_vigencia'],          // Cambio de sucursal
        2: ['puesto', 'fecha_vigencia'],                  // Nuevo puesto
        3: ['importe'],                                   // Nuevo salario neto
        4: ['importe'],                                   // Ajuste de premios
        5: ['cantidad_horas'],                            // Horas extras
        6: ['cantidad_horas'],                            // Horas adicionales
        7: ['fecha_permiso', 'compensa'],                 // Permisos
        8: ['cantidad_cortes'],                           // Cortes
        9: ['cantidad_unidades'],                         // Producción 25%
        10: ['cantidad_unidades'],                        // Producción 50%
        11: ['cantidad_unidades']                         // Producción 100%
    },

    /**
     * Realizar petición AJAX - MEJORADO CON DEBUGGING
     */
    async request(action, data = null, method = 'GET') {
        const startTime = Date.now();
        console.log(`🚀 Iniciando petición: ${action}`, { method, data });
        
        try {
            let url = `${this.baseUrl}?action=${action}&debug=1`;
            let options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };

            if (method === 'POST' && data) {
                options.body = JSON.stringify(data);
                console.log('📤 Datos enviados:', data);
            } else if (method === 'GET' && data) {
                const params = new URLSearchParams(data);
                url += '&' + params.toString();
            }

            console.log(`📍 URL completa: ${url}`);

            const response = await fetch(url, options);
            
            console.log(`📡 Respuesta HTTP: ${response.status} ${response.statusText}`);
            
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
            }

            const responseText = await response.text();
            console.log(`📄 Texto de respuesta:`, responseText.substring(0, 200) + '...');

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ Error parseando JSON:', parseError);
                console.error('📄 Texto completo de respuesta:', responseText);
                throw new Error(`Error parseando JSON: ${parseError.message}. Respuesta: ${responseText.substring(0, 200)}...`);
            }
            
            const duration = Date.now() - startTime;
            console.log(`✅ Petición completada en ${duration}ms:`, result);

            if (!result.success) {
                throw new Error(result.message || 'Error en la petición');
            }

            return result.data;
        } catch (error) {
            const duration = Date.now() - startTime;
            console.error(`❌ Error en petición ${action} después de ${duration}ms:`, error);
            this.mostrarError(`Error en ${action}: ${error.message}`);
            throw error;
        }
    },

    /**
     * Mostrar mensaje de éxito
     */
    mostrarExito(mensaje) {
        this.mostrarAlerta(mensaje, 'success');
    },

    /**
     * Mostrar mensaje de error
     */
    mostrarError(mensaje) {
        this.mostrarAlerta(mensaje, 'danger');
    },

    /**
     * Mostrar alerta
     */
    mostrarAlerta(mensaje, tipo = 'info') {
        const alertaId = 'alerta-' + Date.now();
        const iconos = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-triangle',
            warning: 'fa-exclamation-circle',
            info: 'fa-info-circle'
        };

        const alerta = `
            <div id="${alertaId}" class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                <i class="fas ${iconos[tipo]} me-2"></i>
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Insertar en contenedor de alertas o al inicio del body
        const contenedor = document.getElementById('alertas-container') || document.body;
        contenedor.insertAdjacentHTML('afterbegin', alerta);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            const alertaElement = document.getElementById(alertaId);
            if (alertaElement) {
                alertaElement.remove();
            }
        }, 5000);
    },

    /**
     * Mostrar/ocultar loading
     */
    mostrarLoading(elemento, mostrar = true) {
        if (typeof elemento === 'string') {
            elemento = document.getElementById(elemento);
        }
        
        if (elemento) {
            if (mostrar) {
                elemento.innerHTML = `
                    <div class="text-center py-3">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <div class="mt-2">Cargando...</div>
                    </div>
                `;
            } else {
                elemento.innerHTML = '';
            }
        }
    },

    /**
     * Formatear fecha para mostrar
     */
    formatearFecha(fecha) {
        if (!fecha) return '';
        // Aceptar formatos: 'YYYY-MM-DD', 'YYYY-MM-DD HH:MM:SS'
        let normalizada = fecha;
        if (typeof fecha === 'string') {
            // Quitar fracciones y Z si vienen
            normalizada = fecha.replace('T', ' ').replace(/\.\d+Z?$/, '');
            // Si solo viene fecha agregar hora para evitar desfase por timezone
            if (/^\d{4}-\d{2}-\d{2}$/.test(normalizada)) {
                normalizada += ' 00:00:00';
            }
        }
        const ts = Date.parse(normalizada);
        if (isNaN(ts)) return 'Fecha inválida';
        const d = new Date(ts);
        return d.toLocaleDateString('es-AR');
    },

    /**
     * Formatear valor numérico según el contexto
     */
    formatearValor(valor, contexto = 'moneda') {
        if (!valor && valor !== 0) return '';
        
        switch (contexto) {
            case 'moneda':
                return new Intl.NumberFormat('es-AR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(valor);
            case 'horas':
                return `${valor} hs`;
            case 'unidades':
                return `${valor} unidades`;
            case 'cortes':
                return `${valor} cortes`;
            case 'numero':
                return valor.toString();
            default:
                return valor.toString();
        }
    },

    contextoDesdeTipo(tipo) {
        switch (tipo) {
            case 3: return 'moneda';
            case 4: return 'moneda';
            case 5: return 'horas';
            case 6: return 'horas';
            case 8: return 'cortes';
            case 9: return 'unidades';
            case 10: return 'unidades';
            case 11: return 'unidades';
            default: return 'numero';
        }
    },

    /**
     * Validar formulario
     */
    validarFormulario(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;

        const campos = form.querySelectorAll('[required]');
        let valido = true;

        campos.forEach(campo => {
            if (!campo.value.trim()) {
                campo.classList.add('is-invalid');
                valido = false;
            } else {
                campo.classList.remove('is-invalid');
            }
        });

        return valido;
    }
};

/**
 * Funciones para búsqueda de empleados
 */
async function buscarEmpleado() {
    const legajo = document.getElementById('buscar-legajo').value;
    
    if (!legajo) {
        NovedadesApp.mostrarError('Ingrese un número de legajo');
        return;
    }

    try {
        document.getElementById('resultado-empleado').style.display = 'none';
        document.getElementById('error-empleado').style.display = 'none';
        document.getElementById('btn-seleccionar-empleado').style.display = 'none';

        const empleado = await NovedadesApp.request('buscar_empleado', { legajo });

        // Mostrar datos del empleado
        document.getElementById('empleado-nombre').textContent = empleado.nombre || '';
        document.getElementById('empleado-apellido').textContent = empleado.apellido || '';
        document.getElementById('empleado-legajo').textContent = empleado.legajo || '';
        document.getElementById('empleado-sucursal').textContent = empleado.sucursal || '';

        document.getElementById('resultado-empleado').style.display = 'block';
        document.getElementById('btn-seleccionar-empleado').style.display = 'inline-block';

        NovedadesApp.empleadoSeleccionado = empleado;

    } catch (error) {
        document.getElementById('mensaje-error').textContent = error.message;
        document.getElementById('error-empleado').style.display = 'block';
    }
}

/**
 * Seleccionar empleado encontrado
 */
function seleccionarEmpleado() {
    if (!NovedadesApp.empleadoSeleccionado) return;

    const empleado = NovedadesApp.empleadoSeleccionado;

    // Llenar campos del formulario principal
    const campos = {
        'legajo': empleado.legajo,
        'nombre': empleado.nombre,
        'apellido': empleado.apellido,
        'sucursal': empleado.sucursal
    };

    Object.keys(campos).forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.value = campos[campo] || '';
        }
    });

    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalBuscarEmpleado'));
    modal.hide();

    NovedadesApp.mostrarExito('Empleado seleccionado correctamente');
}

/**
 * Probar conectividad del sistema - NUEVA FUNCIÓN
 */
async function probarSistema() {
    console.log('🔧 Iniciando pruebas del sistema...');
    
    try {
        // Probar endpoint de test
        const testResult = await NovedadesApp.request('test');
        console.log('✅ Test del controlador exitoso:', testResult);
        
        // Probar carga de tipos de novedad
        const tipos = await NovedadesApp.request('get_tipos_novedad');
        console.log('✅ Tipos de novedad cargados:', tipos.length, 'tipos');
        
        // Probar carga de sucursales
        const sucursales = await NovedadesApp.request('get_sucursales');
        console.log('✅ Sucursales cargadas:', sucursales.length, 'sucursales');
        
        return true;
    } catch (error) {
        console.error('❌ Error en pruebas del sistema:', error);
        return false;
    }
}
async function cargarDatosIniciales() {
    try {
        // Cargar sucursales con Casa Central
        const sucursales = await NovedadesApp.request('get_sucursales_con_casa_central');
        const selectSucursales = document.querySelectorAll('select[name="sucursal"], #filtro-sucursal');
        
        selectSucursales.forEach(select => {
            if (select) {
                select.innerHTML = '<option value="">Seleccione sucursal...</option>';
                sucursales.forEach(sucursal => {
                    select.innerHTML += `<option value="${sucursal.numero}">${sucursal.descripcion}</option>`;
                });
            }
        });

        // Cargar tipos de novedad
        const tipos = await NovedadesApp.request('get_tipos_novedad');
        const selectTipos = document.querySelectorAll('select[name="tipo_novedad"], #filtro-tipo, #tipo_novedad');
        
        selectTipos.forEach(select => {
            if (select) {
                select.innerHTML = '<option value="">Seleccione tipo de novedad...</option>';
                tipos.forEach(tipo => {
                    select.innerHTML += `<option value="${tipo.id}">${tipo.descripcion}</option>`;
                });
            }
        });

        console.log('Datos iniciales cargados:', { sucursales: sucursales.length, tipos: tipos.length });

    } catch (error) {
        console.error('Error cargando datos iniciales:', error);
        NovedadesApp.mostrarError('Error cargando datos del sistema');
    }
}

/**
 * Manejar cambio de tipo de novedad (función global actualizada)
 */
function onTipoNovedadChange(selectElement) {
    // Esta función ahora está implementada específicamente en nueva_novedad_form.js
    // Mantener esta referencia para compatibilidad
    if (typeof window.onTipoNovedadChangeSpecific === 'function') {
        window.onTipoNovedadChangeSpecific(selectElement);
    }
}

/**
 * Limpiar formulario
 */
function limpiarFormulario(formId = 'form-novedad') {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        
        // Ocultar campos dinámicos
        const camposDinamicos = form.querySelectorAll('.campo-dinamico');
        camposDinamicos.forEach(campo => {
            campo.style.display = 'none';
        });

        // Remover clases de validación
        const campos = form.querySelectorAll('.form-control');
        campos.forEach(campo => {
            campo.classList.remove('is-invalid', 'is-valid');
        });
    }

    NovedadesApp.empleadoSeleccionado = null;
}

/**
 * Inicializar la aplicación - MEJORADO
 */
document.addEventListener('DOMContentLoaded', async function() {
    console.log('🚀 Inicializando Sistema de Novedades RRHH...');
    
    // Primero probar conectividad
    const sistemaOK = await probarSistema();
    
    if (sistemaOK) {
        // Cargar datos iniciales solo si el sistema funciona
        cargarDatosIniciales();
    } else {
        NovedadesApp.mostrarError('Error de conectividad. Verifique que el servidor esté funcionando.');
    }
    
    // Event listeners globales
    const buscarLegajoInput = document.getElementById('buscar-legajo');
    if (buscarLegajoInput) {
        buscarLegajoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarEmpleado();
            }
        });
    }

    // Manejar cambios en tipo de novedad
    const tipoNovedadSelect = document.getElementById('tipo_novedad');
    if (tipoNovedadSelect) {
        tipoNovedadSelect.addEventListener('change', function() {
            console.log('🔄 Cambio de tipo de novedad:', this.value);
            onTipoNovedadChange(this);
        });
    }

    console.log('✅ Sistema de Novedades RRHH - Inicializado correctamente');
});