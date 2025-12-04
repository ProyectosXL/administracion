// /novedades/js/novedades_main.js - CORREGIDO

/**
 * Configuración global y utilidades
 */
const NovedadesApp = {
    baseUrl: 'controller/novedades_controller.php',
    empleadoSeleccionado: null,
    tipoUsuario: null,
    esUsuarioRRHH: false,

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
                console.log('📤 Datos enviados como JSON:', data);
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

            if (action === 'cambiar_estado_novedad') {
                return result;
            }

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

        const contenedor = document.getElementById('alertas-container') || document.body;
        contenedor.insertAdjacentHTML('afterbegin', alerta);

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
     * Formatear fecha para mostrar - Formato: dd/mm/aaaa (español)
     * MEJORADO PARA OBJETOS DATETIME Y FECHAS YA FORMATEADAS
     */
    formatearFecha(fecha) {
        if (!fecha) return '';
        
        try {
            // Si ya está en formato dd/mm/aaaa, devolverla tal cual
            if (typeof fecha === 'string' && /^\d{2}\/\d{2}\/\d{4}/.test(fecha)) {
                return fecha.split(' ')[0]; // Remover hora si existe
            }
            
            // Manejar objetos DateTime de PHP
            if (typeof fecha === 'object' && fecha.date) {
                console.log('🔧 Procesando objeto DateTime:', fecha);
                fecha = fecha.date;
            }
            
            // Si es una cadena en formato ISO (YYYY-MM-DD), parsear directamente sin timezone
            if (typeof fecha === 'string' && /^\d{4}-\d{2}-\d{2}/.test(fecha)) {
                const partes = fecha.split(/[-T\s]/);
                const anio = partes[0];
                const mes = partes[1];
                const dia = partes[2];
                return `${dia}/${mes}/${anio}`;
            }
            
            // Para otros formatos, usar Date.parse
            let normalizada = fecha;
            if (typeof fecha === 'string') {
                normalizada = fecha.replace('T', ' ').replace(/\.\d+Z?$/, '');
            }
            
            const ts = Date.parse(normalizada);
            if (isNaN(ts)) {
                console.warn('❌ Fecha inválida después del procesamiento:', normalizada, 'Original:', fecha);
                return 'Fecha inválida';
            }
            
            const d = new Date(ts);
            const dia = String(d.getDate()).padStart(2, '0');
            const mes = String(d.getMonth() + 1).padStart(2, '0');
            const anio = d.getFullYear();
            return `${dia}/${mes}/${anio}`;
        } catch (error) {
            console.error('❌ Error formateando fecha:', error, 'Fecha original:', fecha);
            return 'Error en fecha';
        }
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
    },

    /**
     * Inicializar permisos de usuario
     */
    async inicializarPermisos() {
        try {
            const response = await this.request('get_tipo_usuario');
            this.tipoUsuario = response.tipo;
            this.esUsuarioRRHH = response.es_rrhh;
            console.log('👤 Permisos de usuario cargados:', { 
                tipo: this.tipoUsuario, 
                esRRHH: this.esUsuarioRRHH,
                descripcion: response.descripcion 
            });
        } catch (error) {
            console.error('Error cargando permisos de usuario:', error);
            this.tipoUsuario = null;
            this.esUsuarioRRHH = false;
        }
    },

    /**
     * Verificar si el usuario puede cambiar estados
     */
    puedeEditarEstados() {
        return this.esUsuarioRRHH;
    }
};

/**
 * Formatear período en formato "Mes Año (MM/YY)"
 */
NovedadesApp.formatearPeriodoDisplay = function(periodoMes, periodoAnio) {
    const meses = [
        '', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    
    if (!periodoMes || !periodoAnio) return 'Período no definido';
    
    const mesNombre = meses[parseInt(periodoMes)] || 'Mes';
    const yearCorto = periodoAnio.toString().substr(-2);
    
    return `${mesNombre} ${periodoAnio} (${periodoMes.toString().padStart(2, '0')}/${yearCorto})`;
};

/**
 * Actualizar períodos en la página
 */
NovedadesApp.actualizarPeriodosEnPagina = function() {
    document.querySelectorAll('[data-periodo-mes][data-periodo-anio]').forEach(elemento => {
        const mes = elemento.getAttribute('data-periodo-mes');
        const anio = elemento.getAttribute('data-periodo-anio');
        
        if (mes && anio) {
            elemento.textContent = this.formatearPeriodoDisplay(mes, anio);
        }
    });
    
    document.querySelectorAll('.periodo-display').forEach(elemento => {
        const mes = elemento.getAttribute('data-mes');
        const anio = elemento.getAttribute('data-anio');
        
        if (mes && anio) {
            elemento.textContent = this.formatearPeriodoDisplay(mes, anio);
        }
    });
};

/**
 * Función para manejar cambio de tipo de novedad - CORREGIDA
 */
function onTipoNovedadChange(selectElement) {
    console.log('🔄 onTipoNovedadChange llamada desde main:', selectElement.value);
    
    // Buscar la función específica en nueva_novedad_form.js
    if (typeof window.onTipoNovedadChangeSpecific === 'function') {
        window.onTipoNovedadChangeSpecific(selectElement);
    } else {
        console.warn('⚠️ Función específica onTipoNovedadChangeSpecific no encontrada');
        // Fallback básico
        basicTipoNovedadHandler(selectElement);
    }
}

/**
 * Fallback básico para cambio de tipo de novedad
 */
function basicTipoNovedadHandler(selectElement) {
    const tipoSeleccionado = parseInt(selectElement.value);
    
    // Ocultar todas las configuraciones
    const configuraciones = document.querySelectorAll('.campo-dinamico');
    configuraciones.forEach(config => {
        config.style.display = 'none';
    });
    
    if (tipoSeleccionado) {
        // Mapeo básico de tipos
        const configMap = {
            1: 'config-cambio-sucursal',
            2: 'config-nuevo-puesto',
            3: 'config-nuevo-salario',
            4: 'config-ajuste-premios',
            5: 'config-horas-extras',
            6: 'config-horas-adicionales',
            7: 'config-permisos',
            8: 'config-cortes',
            9: 'config-produccion-25',
            10: 'config-produccion-50',
            11: 'config-produccion-100'
        };
        
        const configId = configMap[tipoSeleccionado];
        if (configId) {
            const configElement = document.getElementById(configId);
            if (configElement) {
                document.getElementById('configuracion-novedad').style.display = 'block';
                configElement.style.display = 'block';
            }
        }
    }
}

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

        document.getElementById('empleado-nombre').textContent = empleado.nombre || '';
        document.getElementById('empleado-apellido').textContent = empleado.apellido || '';
        document.getElementById('empleado-legajo').textContent = empleado.legajo || '';
        
        const centroCostosTexto = empleado.desc_centro_costos 
            ? `${empleado.desc_centro_costos} (${empleado.cod_centro_costos})`
            : 'No definido';
        document.getElementById('empleado-centro-costos').textContent = centroCostosTexto;

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

    const campos = {
        'legajo': empleado.legajo,
        'nombre': empleado.nombre,
        'apellido': empleado.apellido,
        'centro_costos': empleado.cod_centro_costos
    };

    Object.keys(campos).forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.value = campos[campo] || '';
        }
    });

    const modal = bootstrap.Modal.getInstance(document.getElementById('modalBuscarEmpleado'));
    modal.hide();

    NovedadesApp.mostrarExito('Empleado seleccionado correctamente');
}

/**
 * Probar conectividad del sistema
 */
async function probarSistema() {
    console.log('🔧 Iniciando pruebas del sistema...');
    
    try {
        const testResult = await NovedadesApp.request('test');
        console.log('✅ Test del controlador exitoso:', testResult);
        
        const tipos = await NovedadesApp.request('get_tipos_novedad');
        console.log('✅ Tipos de novedad cargados:', tipos.length, 'tipos');
        
        const centrosCostos = await NovedadesApp.request('get_centros_costos');
        console.log('✅ Centros de costos cargados:', centrosCostos.length, 'centros');
        
        return true;
    } catch (error) {
        console.error('❌ Error en pruebas del sistema:', error);
        return false;
    }
}

/**
 * Cargar datos iniciales del sistema
 */
async function cargarDatosIniciales() {
    try {
        // Cargar centros de costos para filtros
        const centrosCostos = await NovedadesApp.request('get_centros_costos');
        const selectCentrosCostos = document.querySelectorAll('#filtro-centro-costos');
        
        selectCentrosCostos.forEach(select => {
            if (select) {
                select.innerHTML = '<option value="">Todos los centros</option>';
                centrosCostos.forEach(centro => {
                    select.innerHTML += `<option value="${centro.codigo}">${centro.descripcion}</option>`;
                });
            }
        });

        // Cargar tipos de novedad
        const tipos = await NovedadesApp.request('get_tipos_novedad');
        
        // Filtrar duplicados por descripción (para eliminar duplicados específicos)
        const tiposUnicos = tipos.filter((tipo, index, arr) => 
            arr.findIndex(t => t.descripcion === tipo.descripcion) === index
        );
        const selectTipos = document.querySelectorAll('select[name="tipo_novedad"], #filtro-tipo, #tipo_novedad');
        
        selectTipos.forEach(select => {
            if (select) {
                select.innerHTML = '<option value="">Seleccione tipo de novedad...</option>';
                tiposUnicos.forEach(tipo => {
                    select.innerHTML += `<option value="${tipo.id}">${tipo.descripcion}</option>`;
                });
            }
        });

        console.log('Datos iniciales cargados:', { centrosCostos: centrosCostos.length, tipos: tipos.length });

    } catch (error) {
        console.error('Error cargando datos iniciales:', error);
        NovedadesApp.mostrarError('Error cargando datos del sistema');
    }
}

/**
 * Limpiar formulario
 */
function limpiarFormulario(formId = 'form-novedad') {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        
        const camposDinamicos = form.querySelectorAll('.campo-dinamico');
        camposDinamicos.forEach(campo => {
            campo.style.display = 'none';
        });

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
    
    // Inicializar permisos de usuario
    await NovedadesApp.inicializarPermisos();
    
    // Probar conectividad
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

    // Configurar el listener para tipo de novedad solo si el elemento existe
    const tipoNovedadSelect = document.getElementById('tipo_novedad');
    if (tipoNovedadSelect) {
        console.log('🎯 Configurando listener para tipo_novedad');
        tipoNovedadSelect.addEventListener('change', function() {
            console.log('🔄 Cambio de tipo de novedad detectado:', this.value);
            onTipoNovedadChange(this);
        });
    } else {
        console.log('ℹ️ Elemento tipo_novedad no encontrado en esta página');
    }

    console.log('✅ Sistema de Novedades RRHH - Inicializado correctamente');
});

/**
 * Exponer funciones globalmente para compatibilidad
 */
window.NovedadesApp = NovedadesApp;
window.onTipoNovedadChange = onTipoNovedadChange;
window.buscarEmpleado = buscarEmpleado;
window.seleccionarEmpleado = seleccionarEmpleado;
window.limpiarFormulario = limpiarFormulario;
// Al final del archivo, agregar:
window.onTipoNovedadChangeSpecific = onTipoNovedadChange;