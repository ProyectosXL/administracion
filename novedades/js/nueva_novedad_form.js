/**
 * JavaScript específico para el formulario de nueva novedad
 * Maneja los 11 tipos de novedad según especificación
 */

// Configuración de tipos de novedad y sus campos requeridos - CORREGIDA SEGÚN ARTIFACT
const tiposNovedadConfig = {
    1: { // Cambio de sucursal
        config: 'config-cambio-sucursal',
        campos: ['nueva_sucursal', 'fecha_vigencia_sucursal'],
        validaciones: ['nueva_sucursal', 'fecha_vigencia']
    },
    2: { // Nuevo puesto
        config: 'config-nuevo-puesto',
        campos: ['nuevo_puesto', 'fecha_vigencia_puesto'],
        validaciones: ['puesto', 'fecha_vigencia']
    },
    3: { // Nuevo salario neto
        config: 'config-nuevo-salario',
        campos: ['importe_salario', 'fecha_vigencia_salario'], // AGREGADA FECHA
        validaciones: ['importe', 'fecha_vigencia']
    },
    4: { // Ajuste de premios
        config: 'config-ajuste-premios',
        campos: ['importe_premios', 'fecha_vigencia_premios'], // AGREGADA FECHA
        validaciones: ['importe', 'fecha_vigencia']
    },
    5: { // Horas extras
        config: 'config-horas-extras',
        campos: ['cantidad_horas_extras', 'fecha_vigencia_horas_extras'], // AGREGADA FECHA
        validaciones: ['cantidad_horas', 'fecha_vigencia']
    },
    6: { // Horas adicionales
        config: 'config-horas-adicionales',
        campos: ['cantidad_horas_adicionales', 'fecha_vigencia_horas_adicionales'], // AGREGADA FECHA
        validaciones: ['cantidad_horas', 'fecha_vigencia']
    },
    7: { // Permisos
        config: 'config-permisos',
        campos: ['fecha_permiso', 'compensa'],
        validaciones: ['fecha_permiso', 'compensa']
    },
    8: { // Cortes
        config: 'config-cortes',
        campos: ['cantidad_cortes', 'fecha_vigencia_cortes'], // AGREGADA FECHA
        validaciones: ['cantidad_cortes', 'fecha_vigencia']
    },
    9: { // Producción 25%
        config: 'config-produccion-25',
        campos: ['cantidad_unidades_25', 'fecha_vigencia_produccion_25'], // AGREGADA FECHA
        validaciones: ['cantidad_unidades', 'fecha_vigencia']
    },
    10: { // Producción 50%
        config: 'config-produccion-50',
        campos: ['cantidad_unidades_50', 'fecha_vigencia_produccion_50'], // AGREGADA FECHA
        validaciones: ['cantidad_unidades', 'fecha_vigencia']
    },
    11: { // Producción 100%
        config: 'config-produccion-100',
        campos: ['cantidad_unidades_100', 'fecha_vigencia_produccion_100'], // AGREGADA FECHA
        validaciones: ['cantidad_unidades', 'fecha_vigencia']
    }
};

/**
 * Manejar cambio de tipo de novedad - MEJORADO CON LOGS
 */
function onTipoNovedadChange(selectElement) {
    const tipoSeleccionado = parseInt(selectElement.value);
    
    console.log('onTipoNovedadChange called:', {
        valor: selectElement.value,
        tipoSeleccionado: tipoSeleccionado,
        configuracionDisponible: !!tiposNovedadConfig[tipoSeleccionado]
    });
    
    // Ocultar todas las configuraciones
    ocultarTodasLasConfiguraciones();
    
    // Limpiar todos los campos dinámicos
    limpiarCamposDinamicos();
    
    if (tipoSeleccionado && tiposNovedadConfig[tipoSeleccionado]) {
        // Mostrar configuración del tipo seleccionado
        const config = tiposNovedadConfig[tipoSeleccionado];
        const configId = config.config;
        const configElement = document.getElementById(configId);
        
        console.log('Mostrando configuración:', {
            configId: configId,
            elementoEncontrado: !!configElement
        });
        
        if (configElement) {
            // Mostrar la sección de configuración
            document.getElementById('configuracion-novedad').style.display = 'block';
            configElement.style.display = 'block';
            configElement.classList.add('fade-in');
            
            // Hacer requeridos los campos necesarios
            const campos = config.campos;
            campos.forEach(campoId => {
                const campo = document.getElementById(campoId);
                if (campo) {
                    campo.setAttribute('required', 'required');
                }
            });

            // Lógica específica para cambio de sucursal
            if (tipoSeleccionado === 1) {
                configurarCambioSucursal();
            }
        }
    } else {
        // Ocultar la sección de configuración
        document.getElementById('configuracion-novedad').style.display = 'none';
    }
}

/**
 * Ocultar todas las configuraciones de novedad
 */
function ocultarTodasLasConfiguraciones() {
    const configuraciones = document.querySelectorAll('.campo-dinamico');
    configuraciones.forEach(config => {
        config.style.display = 'none';
        config.classList.remove('fade-in');
        
        // Remover atributo required de todos los campos
        const inputs = config.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.removeAttribute('required');
        });
    });
}

/**
 * Limpiar campos dinámicos
 */
function limpiarCamposDinamicos() {
    const configuraciones = document.querySelectorAll('.campo-dinamico');
    configuraciones.forEach(config => {
        const inputs = config.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.value = '';
            input.classList.remove('is-invalid', 'is-valid');
        });
    });
}

/**
 * Configurar opciones específicas para cambio de sucursal
 */
function configurarCambioSucursal() {
    const sucursalActual = document.getElementById('sucursal').value;
    const nuevaSucursalSelect = document.getElementById('nueva_sucursal');
    
    // Filtrar opciones para no mostrar la sucursal actual
    Array.from(nuevaSucursalSelect.options).forEach(option => {
        if (option.value === sucursalActual && sucursalActual !== '') {
            option.style.display = 'none';
            option.disabled = true;
        } else {
            option.style.display = 'block';
            option.disabled = false;
        }
    });
}

/**
 * Manejar envío del formulario - CON DEBUGGING MEJORADO
 */
async function enviarFormulario(event) {
    event.preventDefault();
    
    console.log('🚀 Iniciando envío del formulario...');
    
    // Validar datos básicos
    if (!validarDatosBasicos()) {
        console.error('❌ Validación básica falló');
        NovedadesApp.mostrarError('Por favor, complete todos los campos obligatorios');
        return;
    }
    
    console.log('✅ Validación básica exitosa');

    // Validar configuración específica del tipo de novedad
    const tipoNovedad = parseInt(document.getElementById('tipo_novedad').value);
    console.log('🔢 Tipo de novedad:', tipoNovedad);
    
    if (!validarConfiguracionTipo(tipoNovedad)) {
        console.error('❌ Validación de configuración falló');
        return;
    }
    
    console.log('✅ Validación de configuración exitosa');

    // Recopilar datos del formulario (ahora es async)
    const datos = await recopilarDatosFormulario(tipoNovedad);
    console.log('📦 Datos recopilados:', datos);

    // Mostrar loading en botón
    const btnGuardar = document.getElementById('btn-guardar');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';

    try {
        // Enviar datos al servidor
        console.log('📤 Enviando datos al servidor...');
        
        // Log específico para permisos
        if (tipoNovedad === 7) {
            console.log('🔍 DATOS PERMISOS ANTES DEL ENVÍO:', {
                tipo_novedad: datos.tipo_novedad,
                fecha_permiso: datos.fecha_permiso,
                compensa: datos.compensa,
                datos_completos: datos
            });
        }
        
        await NovedadesApp.request('crear_novedad', datos, 'POST');
        
        // Mostrar modal de confirmación
        const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
        modal.show();
        
        // Limpiar formulario automáticamente después del éxito
        if (typeof window.limpiarFormularioSinConfirmacion === 'function') {
            window.limpiarFormularioSinConfirmacion();
        } else {
            // Fallback: limpiar manualmente
            console.log('🧹 Limpiando formulario (fallback)...');
            document.getElementById('form-novedad').reset();
            if (typeof $ !== 'undefined' && $('#empleado-select').length) {
                $('#empleado-select').val(null).trigger('change');
            }
            if (typeof NovedadesApp !== 'undefined') {
                NovedadesApp.empleadoSeleccionado = null;
            }
        }

    } catch (error) {
        console.error('Error al guardar novedad:', error);
        
    } finally {
        // Restaurar botón
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = textoOriginal;
    }
}

/**
 * Validar datos básicos del formulario - ACTUALIZADO PARA CAMPO ÚNICO CON DEBUGGING
 */
function validarDatosBasicos() {
    console.log('🔍 Iniciando validación básica...');
    
    const camposBasicos = ['empleado-select', 'sucursal', 'tipo_novedad'];
    let valido = true;

    camposBasicos.forEach(campoId => {
        const campo = document.getElementById(campoId);
        console.log(`📋 Validando campo ${campoId}:`, campo ? campo.value : 'CAMPO NO ENCONTRADO');
        
        if (!campo || !campo.value.trim()) {
            console.log(`❌ Campo ${campoId} inválido`);
            if (campo) {
                campo.classList.add('is-invalid');
            }
            valido = false;
        } else {
            console.log(`✅ Campo ${campoId} válido`);
            campo.classList.remove('is-invalid');
            campo.classList.add('is-valid');
        }
    });
    
    // Validar que el legajo esté completado (campo hidden)
    const legajoHidden = document.getElementById('legajo');
    console.log('🆔 Legajo hidden:', legajoHidden ? legajoHidden.value : 'CAMPO NO ENCONTRADO');
    
    if (!legajoHidden || !legajoHidden.value) {
        console.log('❌ Legajo hidden inválido');
        // Si no hay legajo, marcar el select de empleado como inválido
        const empleadoSelect = document.getElementById('empleado-select');
        if (empleadoSelect) {
            empleadoSelect.classList.add('is-invalid');
        }
        valido = false;
    } else {
        console.log('✅ Legajo hidden válido');
    }

    // Validar empleado seleccionado en memoria
    const empleadoSeleccionado = window.NovedadesApp ? window.NovedadesApp.empleadoSeleccionado : null;
    console.log('👤 Empleado en memoria:', empleadoSeleccionado);

    console.log(`📊 Resultado validación básica: ${valido ? 'VÁLIDO' : 'INVÁLIDO'}`);
    return valido;
}

/**
 * Validar configuración específica según tipo de novedad
 */
function validarConfiguracionTipo(tipoNovedad) {
    if (!tiposNovedadConfig[tipoNovedad]) {
        NovedadesApp.mostrarError('Tipo de novedad no válido');
        return false;
    }

    const config = tiposNovedadConfig[tipoNovedad];
    const errores = [];
    let valido = true;

    // Validar campos requeridos
    config.campos.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (campo) {
            let estaVacio = false;
            
            // Verificar si el campo está vacío según su tipo
            if (campo.type === 'date') {
                estaVacio = !campo.value || campo.value === '';
            } else {
                estaVacio = !campo.value.trim();
            }
            
            if (estaVacio) {
                campo.classList.add('is-invalid');
                valido = false;
            } else {
                campo.classList.remove('is-invalid');
                campo.classList.add('is-valid');
            }
        }
    });

    // Validaciones específicas por tipo
    switch (tipoNovedad) {
        case 1: // Cambio de sucursal
            const sucursalActual = document.getElementById('sucursal').value;
            const nuevaSucursal = document.getElementById('nueva_sucursal').value;
            if (sucursalActual === nuevaSucursal) {
                errores.push('La nueva sucursal debe ser diferente a la actual');
                document.getElementById('nueva_sucursal').classList.add('is-invalid');
                valido = false;
            }
            break;

        case 3: // Nuevo salario neto
        case 4: // Ajuste de premios
            const importe = parseFloat(document.getElementById(tipoNovedad === 3 ? 'importe_salario' : 'importe_premios').value);
            if (importe <= 0) {
                errores.push('El importe debe ser mayor a cero');
                valido = false;
            }
            break;

        case 5: // Horas extras
        case 6: // Horas adicionales
            const cantidadHoras = parseInt(document.getElementById(tipoNovedad === 5 ? 'cantidad_horas_extras' : 'cantidad_horas_adicionales').value);
            if (cantidadHoras <= 0) {
                errores.push('La cantidad de horas debe ser mayor a cero');
                valido = false;
            }
            break;

        case 7: // Permisos
            console.log('🔍 Iniciando validación de PERMISOS...');
            const fechaPermisoValue = document.getElementById('fecha_permiso').value;
            const compensaValue = document.getElementById('compensa').value;
            
            console.log('🔍 Valores obtenidos:', {
                fecha_permiso: fechaPermisoValue,
                compensa: compensaValue
            });
            
            // Solo validar que la fecha esté presente - SIN RESTRICCIONES DE FECHA FUTURA
            if (!fechaPermisoValue) {
                console.log('❌ Fecha de permiso vacía');
                errores.push('La fecha del permiso es obligatoria');
                document.getElementById('fecha_permiso').classList.add('is-invalid');
                valido = false;
            } else {
                console.log('✅ Fecha de permiso presente:', fechaPermisoValue);
                document.getElementById('fecha_permiso').classList.remove('is-invalid');
                document.getElementById('fecha_permiso').classList.add('is-valid');
            }
            
            // Validar compensa
            if (!compensaValue) {
                console.log('❌ Campo compensa vacío');
                errores.push('Debe indicar si compensa o no');
                document.getElementById('compensa').classList.add('is-invalid');
                valido = false;
            } else {
                console.log('✅ Campo compensa presente:', compensaValue);
                document.getElementById('compensa').classList.remove('is-invalid');
                document.getElementById('compensa').classList.add('is-valid');
            }
            
            console.log('🔍 Fin validación PERMISOS - válido:', valido);
            break;

        case 8: // Cortes
            const cantidadCortes = parseInt(document.getElementById('cantidad_cortes').value);
            if (cantidadCortes <= 0) {
                errores.push('La cantidad de cortes debe ser mayor a cero');
                valido = false;
            }
            break;

        case 9: // Producción 25%
        case 10: // Producción 50%
        case 11: // Producción 100%
            const cantidadUnidades = parseInt(document.getElementById(`cantidad_unidades_${tipoNovedad === 9 ? '25' : tipoNovedad === 10 ? '50' : '100'}`).value);
            if (cantidadUnidades <= 0) {
                errores.push('La cantidad de unidades debe ser mayor a cero');
                valido = false;
            }
            break;
    }

    if (errores.length > 0) {
        NovedadesApp.mostrarError(errores.join('<br>'));
    }

    return valido;
}

/**
 * Recopilar datos del formulario según el tipo de novedad - CON DEBUGGING Y FALLBACK MEJORADO
 */
async function recopilarDatosFormulario(tipoNovedad) {
    console.log('📦 Iniciando recopilación de datos...');
    
    // Obtener datos del empleado desde NovedadesApp (almacenado cuando se selecciona)
    const empleadoSeleccionado = window.NovedadesApp ? window.NovedadesApp.empleadoSeleccionado : null;
    console.log('👤 Empleado desde NovedadesApp:', empleadoSeleccionado);
    
    // Fallback: intentar obtener datos del Select2 directamente
    let nombreEmpleado = '';
    let apellidoEmpleado = '';
    const legajo = document.getElementById('legajo').value;
    
    if (empleadoSeleccionado && empleadoSeleccionado.nombre && empleadoSeleccionado.apellido) {
        nombreEmpleado = empleadoSeleccionado.nombre;
        apellidoEmpleado = empleadoSeleccionado.apellido;
        console.log('✅ Usando datos almacenados en NovedadesApp');
    } else {
        // Fallback 1: obtener del Select2 si está disponible
        try {
            const empleadoSelect = $('#empleado-select');
            if (empleadoSelect.length && empleadoSelect.val()) {
                const selectedData = empleadoSelect.select2('data')[0];
                console.log('👤 Datos desde Select2:', selectedData);
                if (selectedData && selectedData.nombre && selectedData.apellido) {
                    nombreEmpleado = selectedData.nombre;
                    apellidoEmpleado = selectedData.apellido;
                    console.log('✅ Usando datos del Select2');
                }
            }
        } catch (e) {
            console.warn('⚠️ Error obteniendo datos del Select2:', e);
        }
        
        // Fallback 2: buscar empleado por legajo si aún no tenemos datos
        if ((!nombreEmpleado || !apellidoEmpleado) && legajo) {
            console.log('� Buscando empleado por legajo:', legajo);
            try {
                const empleadoData = await NovedadesApp.request('buscar_empleado', { legajo: legajo }, 'GET');
                if (empleadoData && empleadoData.nombre && empleadoData.apellido) {
                    nombreEmpleado = empleadoData.nombre;
                    apellidoEmpleado = empleadoData.apellido;
                    console.log('✅ Empleado encontrado por legajo:', empleadoData);
                }
            } catch (error) {
                console.warn('⚠️ Error buscando empleado por legajo:', error);
            }
        }
    }
    
    console.log('📝 Nombre final:', nombreEmpleado);
    console.log('📝 Apellido final:', apellidoEmpleado);
    
    // Datos básicos
    const datos = {
        legajo: legajo,
        nombre: nombreEmpleado,
        apellido: apellidoEmpleado,
        sucursal: document.getElementById('sucursal').value,
        tipo_novedad: tipoNovedad,
        observaciones: document.getElementById('observaciones') ? document.getElementById('observaciones').value : ''
    };
    
    console.log('📋 Datos básicos recopilados:', datos);

    // Agregar campos específicos según el tipo
    switch (tipoNovedad) {
        case 1: // Cambio de sucursal
            datos.nueva_sucursal = document.getElementById('nueva_sucursal').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_sucursal').value;
            break;

        case 2: // Nuevo puesto
            datos.puesto = document.getElementById('nuevo_puesto').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_puesto').value;
            break;

        case 3: // Nuevo salario neto
            datos.importe = parseFloat(document.getElementById('importe_salario').value);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_salario').value;
            break;

        case 4: // Ajuste de premios
            datos.importe = parseFloat(document.getElementById('importe_premios').value);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_premios').value;
            break;

        case 5: // Horas extras
            datos.cantidad_horas = parseInt(document.getElementById('cantidad_horas_extras').value);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_horas_extras').value;
            break;

        case 6: // Horas adicionales
            datos.cantidad_horas = parseInt(document.getElementById('cantidad_horas_adicionales').value);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_horas_adicionales').value;
            break;

        case 7: // Permisos
            const fechaPermisoElement = document.getElementById('fecha_permiso');
            const compensaElement = document.getElementById('compensa');
            
            datos.fecha_permiso = fechaPermisoElement ? fechaPermisoElement.value : '';
            datos.compensa = compensaElement ? compensaElement.value === '1' : false;
            
            console.log('🔍 PERMISOS - Datos recopilados:', {
                fecha_permiso: datos.fecha_permiso,
                compensa: datos.compensa,
                elemento_fecha_exists: !!fechaPermisoElement,
                elemento_compensa_exists: !!compensaElement,
                fecha_elemento_value: fechaPermisoElement ? fechaPermisoElement.value : 'NO EXISTE',
                compensa_elemento_value: compensaElement ? compensaElement.value : 'NO EXISTE'
            });
            break;

        case 8: // Cortes
            datos.cantidad_cortes = parseInt(document.getElementById('cantidad_cortes').value);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_cortes').value;
            break;

        case 9: // Producción 25%
            datos.cantidad_unidades = parseInt(document.getElementById('cantidad_unidades_25').value);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_produccion_25').value;
            break;

        case 10: // Producción 50%
            datos.cantidad_unidades = parseInt(document.getElementById('cantidad_unidades_50').value);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_produccion_50').value;
            break;

        case 11: // Producción 100%
            datos.cantidad_unidades = parseInt(document.getElementById('cantidad_unidades_100').value);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_produccion_100').value;
            break;
    }

    return datos;
}

/**
 * Cargar sucursales en los selects - CORREGIDO
 */
async function cargarSucursales() {
    try {
        const sucursales = await NovedadesApp.request('get_sucursales_con_casa_central');
        
        // Cargar en select principal
        const selectSucursal = document.getElementById('sucursal');
        if (selectSucursal) {
            selectSucursal.innerHTML = '<option value="">Seleccione sucursal...</option>';
            sucursales.forEach(sucursal => {
                selectSucursal.innerHTML += `<option value="${sucursal.numero}">${sucursal.descripcion}</option>`;
            });
        }

        // Cargar en select de nueva sucursal
        const selectNuevaSucursal = document.getElementById('nueva_sucursal');
        if (selectNuevaSucursal) {
            selectNuevaSucursal.innerHTML = '<option value="">Seleccione nueva sucursal...</option>';
            sucursales.forEach(sucursal => {
                selectNuevaSucursal.innerHTML += `<option value="${sucursal.numero}">${sucursal.descripcion}</option>`;
            });
        }

        console.log('Sucursales cargadas (incluyendo Casa Central):', sucursales.length);

    } catch (error) {
        console.error('Error cargando sucursales:', error);
        NovedadesApp.mostrarError('Error cargando sucursales');
    }
}

/**
 * Cargar puestos disponibles - CORREGIDO
 */
async function cargarPuestos() {
    try {
        const puestos = await NovedadesApp.request('get_puestos');
        
        const selectPuesto = document.getElementById('nuevo_puesto');
        if (selectPuesto) {
            selectPuesto.innerHTML = '<option value="">Seleccione puesto...</option>';
            puestos.forEach(puesto => {
                selectPuesto.innerHTML += `<option value="${puesto.nombre_puesto}">${puesto.nombre_puesto}</option>`;
            });
        }

        console.log('Puestos cargados:', puestos.length);

    } catch (error) {
        console.error('Error cargando puestos:', error);
        NovedadesApp.mostrarError('Error cargando puestos');
    }
}

/**
 * Cargar tipos de novedad - NUEVO
 */
async function cargarTiposNovedad() {
    try {
        const tipos = await NovedadesApp.request('get_tipos_novedad');
        
        const selectTipo = document.getElementById('tipo_novedad');
        if (selectTipo) {
            selectTipo.innerHTML = '<option value="">Seleccione tipo de novedad...</option>';
            tipos.forEach(tipo => {
                selectTipo.innerHTML += `<option value="${tipo.id}">${tipo.descripcion}</option>`;
            });
        }

        console.log('Tipos de novedad cargados:', tipos.length);

    } catch (error) {
        console.error('Error cargando tipos de novedad:', error);
        NovedadesApp.mostrarError('Error cargando tipos de novedad');
    }
}

/**
 * Crear nueva novedad (desde modal de confirmación)
 */
function crearNuevaNovedad() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmacion'));
    modal.hide();
    limpiarFormulario();
    window.scrollTo(0, 0);
}

/**
 * Limpiar formulario completo
 */
function limpiarFormulario(formId = 'form-novedad') {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        
        // Limpiar Select2 si existe
        if (typeof $ !== 'undefined' && $('#empleado-select').length) {
            $('#empleado-select').val(null).trigger('change');
        }
        
        // Ocultar configuraciones
        document.getElementById('configuracion-novedad').style.display = 'none';
        ocultarTodasLasConfiguraciones();

        // Remover clases de validación
        const campos = form.querySelectorAll('.form-control, .form-select');
        campos.forEach(campo => {
            campo.classList.remove('is-invalid', 'is-valid');
        });
    }

    NovedadesApp.empleadoSeleccionado = null;
}

/**
 * Configurar autocompletado de empleado
 */
function configurarAutocompletadoEmpleado() {
    const legajoInput = document.getElementById('legajo');
    let timeoutId;
    
    legajoInput.addEventListener('input', function() {
        const legajo = this.value.trim();
        
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        
        if (!legajo) {
            document.getElementById('nombre').value = '';
            document.getElementById('apellido').value = '';
            return;
        }
        
        timeoutId = setTimeout(async () => {
            if (legajo.length >= 2) {
                try {
                    const empleado = await NovedadesApp.request('buscar_empleado', { legajo });
                    
                    document.getElementById('nombre').value = empleado.nombre || '';
                    document.getElementById('apellido').value = empleado.apellido || '';
                    
                    const sucursalSelect = document.getElementById('sucursal');
                    if (empleado.sucursal && sucursalSelect) {
                        sucursalSelect.value = empleado.sucursal;
                    }
                    
                    NovedadesApp.mostrarExito('Empleado encontrado automáticamente');
                    
                } catch (error) {
                    console.log('Empleado no encontrado para autocompletado:', legajo);
                }
            }
        }, 1000);
    });
}

/**
 * Validación en tiempo real
 */
function configurarValidacionTiempoReal() {
    const campos = document.querySelectorAll('#form-novedad input, #form-novedad select, #form-novedad textarea');
    
    campos.forEach(campo => {
        campo.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
                if (this.value.trim()) {
                    this.classList.add('is-valid');
                }
            }
        });
        
        campo.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
}

/**
 * Configurar eventos específicos
 */
function configurarEventosEspecificos() {
    // Manejar cambio de sucursal actual para cambio de sucursal
    const sucursalSelect = document.getElementById('sucursal');
    sucursalSelect.addEventListener('change', function() {
        const tipoNovedad = parseInt(document.getElementById('tipo_novedad').value);
        if (tipoNovedad === 1) {
            configurarCambioSucursal();
        }
    });

    // Manejar cambios en campos numéricos
    const camposNumericos = document.querySelectorAll('input[type="number"]');
    camposNumericos.forEach(campo => {
        campo.addEventListener('input', function() {
            if (parseFloat(this.value) < 0) {
                this.value = '';
            }
        });
    });
}

/**
 * Inicialización específica del formulario - ACTUALIZADA
 */
document.addEventListener('DOMContentLoaded', function() {
    // Configurar envío del formulario
    const form = document.getElementById('form-novedad');
    if (form) {
        form.addEventListener('submit', enviarFormulario);
    }
    
    // Cargar datos iniciales
    cargarSucursales();
    cargarPuestos();
    cargarTiposNovedad(); // NUEVO: Cargar tipos de novedad específicamente
    
    // Configurar cambio de tipo de novedad
    const tipoNovedadSelect = document.getElementById('tipo_novedad');
    if (tipoNovedadSelect) {
        tipoNovedadSelect.addEventListener('change', function() {
            console.log('Tipo de novedad seleccionado:', this.value);
            onTipoNovedadChange(this);
        });
    }
    
    // Configurar validación en tiempo real
    configurarValidacionTiempoReal();
    
    // Configurar autocompletado de empleado
    configurarAutocompletadoEmpleado();
    
    // Configurar eventos específicos
    configurarEventosEspecificos();
    
    console.log('Formulario Nueva Novedad - Inicializado correctamente');
});