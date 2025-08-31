/**
 * JavaScript específico para el formulario de nueva novedad
 * Maneja los 11 tipos de novedad según especificación
 * Incluye funcionalidad de carga múltiple
 */

// Variables globales para el modo
let modoActual = null; // 'unica' o 'multiple'
let contadorNovedades = 0;
let novedadesData = [];

// Hacer variables disponibles globalmente
window.modoActual = modoActual;
window.contadorNovedades = contadorNovedades;
window.novedadesData = novedadesData;

// Configuración de tipos de novedad y sus campos requeridos - CORREGIDA SEGÚN ARTIFACT
const tiposNovedadConfigActualizada = {
    1: { // Cambio de centro de costos
        config: 'config-cambio-sucursal',
        campos: ['nueva_sucursal', 'fecha_vigencia_sucursal'],
        validaciones: ['nueva_sucursal', 'fecha_vigencia']
    },
    2: { // Nuevo puesto
        config: 'config-nuevo-puesto',
        campos: ['nuevo_puesto', 'fecha_vigencia_puesto', 'tipo_nuevo_puesto'],
        validaciones: ['puesto', 'fecha_vigencia', 'tipo_nuevo_puesto']
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
    },
    12: { // Plus de caja - ACTUALIZADO: usar ID real de la BD (32)
        config: 'config-plus-caja',
        campos: ['tipo_plus_caja', 'importe_plus_caja', 'fecha_vigencia_plus_caja'],
        validaciones: ['tipo_plus_caja', 'fecha_vigencia']
    },
    13: { // Plus de Sub-Encargada - ACTUALIZADO: usar ID real de la BD (33)
        config: 'config-plus-sub-encargada',
        campos: ['tiene_importe_sub', 'fecha_vigencia_plus_sub'],
        validaciones: ['fecha_vigencia']
    },
    14: { // Plus de Encargada - ACTUALIZADO: usar ID real de la BD (34)
        config: 'config-plus-encargada',
        campos: ['tiene_importe_enc', 'fecha_vigencia_plus_enc'],
        validaciones: ['fecha_vigencia']
    },
    15: { // Premio Local - ACTUALIZADO: usar ID real de la BD (35)
        config: 'config-premio-local',
        campos: ['importe_premio_local', 'fecha_vigencia_premio_local', 'aplica_vendedora', 'aplica_sub_encargada'],
        validaciones: ['importe', 'fecha_vigencia']
    },
    16: { // Comisión Individual - ACTUALIZADO: usar ID real de la BD (36)
        config: 'config-comision-individual',
        campos: ['tiene_tope_individual', 'fecha_vigencia_comision_individual', 'porcentaje_unico_individual', 'porcentaje_1_individual', 'porcentaje_2_individual'],
        validaciones: ['fecha_vigencia']
    },
    17: { // Comisión sobre Local - ACTUALIZADO: usar ID real de la BD (37)
        config: 'config-comision-local',
        campos: ['tiene_tope_local', 'fecha_vigencia_comision_local', 'porcentaje_unico_local', 'porcentaje_1_local', 'porcentaje_2_local'],
        validaciones: ['fecha_vigencia']
    },
    18: { // Premios - Ajuste General - ACTUALIZADO: usar ID real de la BD (38)
        config: 'config-premios-ajuste',
        campos: ['importe_ajuste_general', 'fecha_vigencia_ajuste'],
        validaciones: ['importe', 'fecha_vigencia']
    },
    
    // NUEVOS MAPEOS CON IDs REALES DE LA BASE DE DATOS (32-38)
    32: { // Plus de caja
        config: 'config-plus-caja',
        campos: ['tipo_plus_caja', 'importe_plus_caja', 'fecha_vigencia_plus_caja'],
        validaciones: ['tipo_plus_caja', 'fecha_vigencia']
    },
    33: { // Plus de Sub-Encargada
        config: 'config-plus-sub-encargada',
        campos: ['tiene_importe_sub', 'fecha_vigencia_plus_sub'],
        validaciones: ['fecha_vigencia']
    },
    34: { // Plus de Encargada
        config: 'config-plus-encargada',
        campos: ['tiene_importe_enc', 'fecha_vigencia_plus_enc'],
        validaciones: ['fecha_vigencia']
    },
    35: { // Premio Local
        config: 'config-premio-local',
        campos: ['importe_premio_local', 'fecha_vigencia_premio_local', 'aplica_vendedora', 'aplica_sub_encargada'],
        validaciones: ['importe', 'fecha_vigencia']
    },
    36: { // Comisión Individual
        config: 'config-comision-individual',
        campos: ['porcentaje_individual', 'fecha_vigencia_comision_individual'],
        validaciones: ['porcentaje_individual', 'fecha_vigencia']
    },
    37: { // Comisión sobre Local
        config: 'config-comision-local',
        campos: ['tiene_tope_local', 'fecha_vigencia_comision_local', 'porcentaje_unico_local', 'porcentaje_1_local', 'porcentaje_2_local'],
        validaciones: ['fecha_vigencia']
    },
    38: { // Premios - Ajuste General
        config: 'config-premios-ajuste',
        campos: ['importe_ajuste_general', 'fecha_vigencia_ajuste'],
        validaciones: ['importe', 'fecha_vigencia']
    }
};

/**
 * Manejar cambio de tipo de novedad - MEJORADO CON LOGS Y FECHAS DINÁMICAS
 */
function onTipoNovedadChangeActualizado(selectElement) {
    const tipoSeleccionado = parseInt(selectElement.value);
    
    console.log('🔍 onTipoNovedadChange llamado:', {
        valor: selectElement.value,
        tipoSeleccionado: tipoSeleccionado,
        configuracionDisponible: !!tiposNovedadConfigActualizada[tipoSeleccionado],
        configKeys: Object.keys(tiposNovedadConfigActualizada)
    });
    
    // Ocultar todas las configuraciones
    ocultarTodasLasConfiguraciones();
    
    // Limpiar todos los campos dinámicos
    limpiarCamposDinamicos();
    
    if (tipoSeleccionado && tiposNovedadConfigActualizada[tipoSeleccionado]) {
        // Mostrar configuración del tipo seleccionado
        const config = tiposNovedadConfigActualizada[tipoSeleccionado];
        const configId = config.config;
        const configElement = document.getElementById(configId);
        
        console.log('🔍 Configuración encontrada:', {
            tipoSeleccionado,
            config,
            configId,
            elementoEncontrado: !!configElement
        });
        
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
                    
                    // Si es un campo de fecha de vigencia, configurarlo según el tipo de corte
                    if (campoId.includes('fecha_vigencia')) {
                        console.log(`🕘 Configurando fecha vigencia para tipo ${tipoSeleccionado}, campo ${campoId}`);
                        setTimeout(() => {
                            configurarFechaVigencia(tipoSeleccionado, campoId);
                        }, 100); // Pequeño delay para asegurar que el DOM esté listo
                    }
                }
            });

            // Lógica específica para cambio de sucursal
            if (tipoSeleccionado === 1) {
                configurarCambioSucursal();
            }
            
            // Lógica específica para cambio de puesto
            if (tipoSeleccionado === 2) {
                configurarCambioPuesto();
            }
        }
    } else {
        // Ocultar la sección de configuración
        document.getElementById('configuracion-novedad').style.display = 'none';
    }

    if (tipoSeleccionado === 12 || tipoSeleccionado === 32) { // Plus de caja
        setTimeout(() => configurarCamposPlusCaja(), 100);
    } else if (tipoSeleccionado === 13 || tipoSeleccionado === 33) { // Plus Sub-Encargada
        setTimeout(() => configurarCamposPlusConImporte('sub'), 100);
    } else if (tipoSeleccionado === 14 || tipoSeleccionado === 34) { // Plus Encargada
        setTimeout(() => configurarCamposPlusConImporte('enc'), 100);
    } else if (tipoSeleccionado === 15 || tipoSeleccionado === 35) { // Premio Local
        setTimeout(() => configurarCamposPremioLocal(), 100);
    } else if (tipoSeleccionado === 16 || tipoSeleccionado === 36) { // Comisión Individual
        setTimeout(() => configurarCamposComision('individual'), 100);
    } else if (tipoSeleccionado === 17 || tipoSeleccionado === 37) { // Comisión Local
        setTimeout(() => configurarCamposComision('local'), 100);
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
        
        // Limpiar mensajes de fecha de vigencia
        const mensajesFecha = config.querySelectorAll('.fecha-vigencia-info');
        mensajesFecha.forEach(mensaje => mensaje.remove());
    });
}

/**
 * Configurar opciones específicas para cambio de centro de costos
 */
function configurarCambioSucursal() {
    const empleadoLegajo = document.getElementById('legajo').value;
    
    if (empleadoLegajo) {
        // Cargar información de sucursal actual del empleado
        cargarSucursalActualEmpleado(empleadoLegajo);
    }
    
    // Cargar sucursales en el select
    cargarSucursalesEnSelect();
}

/**
 * Cargar sucursal actual del empleado basada en su centro de costos
 * Solo muestra sucursal para centros de costos mapeables (LOC###)
 */
async function cargarSucursalActualEmpleado(legajo) {
    try {
        // Primero obtener la información del empleado para conseguir su centro de costos
        const empleado = await NovedadesApp.request('get_empleado_info', { legajo: legajo });
        
        if (empleado && empleado.codigo_centro_costos) {
            const codigoCentroCostos = empleado.codigo_centro_costos;
            
            // Solo intentar obtener sucursal si el código comienza con "LOC"
            if (codigoCentroCostos.startsWith('LOC')) {
                // Obtener la sucursal asociada al centro de costos usando RO_V_SUCURSALES_CON_CC
                const sucursalDescripcion = await NovedadesApp.request('get_sucursal_por_centro_costos', { 
                    codigo: codigoCentroCostos 
                });
                
                if (sucursalDescripcion) {
                    // Mostrar la sucursal actual
                    document.getElementById('sucursal_actual').value = codigoCentroCostos;
                    document.getElementById('sucursal-actual-text').textContent = sucursalDescripcion;
                    document.getElementById('sucursal-actual-info').style.display = 'block';
                } else {
                    // Si no se encuentra mapeo, ocultar sucursal actual
                    document.getElementById('sucursal-actual-info').style.display = 'none';
                }
            } else {
                // Para departamentos centrales (ADM, COM, FAB, LOG), no mostrar sucursal actual
                document.getElementById('sucursal-actual-info').style.display = 'none';
            }
            
            // Siempre cargar la lista de sucursales disponibles para seleccionar
            cargarSucursalesEnSelect();
        } else {
            console.warn('No se pudo obtener el centro de costos del empleado');
            document.getElementById('sucursal-actual-info').style.display = 'none';
        }
    } catch (error) {
        console.error('Error cargando sucursal actual del empleado:', error);
        document.getElementById('sucursal-actual-info').style.display = 'none';
    }
}

/**
 * Cargar sucursales en un select, excluyendo la actual
 */
async function cargarSucursalesEnSelect() {
    try {
        const sucursales = await NovedadesApp.request('get_sucursales');
        const nuevaSucursalSelect = document.getElementById('nueva_sucursal');
        const sucursalActual = document.getElementById('sucursal_actual').value;
        
        nuevaSucursalSelect.innerHTML = '<option value="">Seleccione nueva sucursal...</option>';
        
        sucursales.forEach(sucursal => {
            if (sucursal.numero !== sucursalActual) {
                nuevaSucursalSelect.innerHTML += `<option value="${sucursal.numero}">${sucursal.descripcion}</option>`;
            }
        });
    } catch (error) {
        console.error('Error cargando sucursales:', error);
    }
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
    
    if (!validarConfiguracionTipoActualizado(tipoNovedad)) {
        console.error('❌ Validación de configuración falló');
        return;
    }
    
    console.log('✅ Validación de configuración exitosa');

    // Recopilar datos del formulario (ahora es async)
    const datos = await recopilarDatosFormularioActualizado(tipoNovedad);
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
    
    const camposBasicos = ['empleado-select', 'tipo_novedad'];
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
 * Validar configuración específica según tipo de novedad - ACTUALIZADO
 */
function validarConfiguracionTipoActualizado(tipoNovedad) {
    if (!tiposNovedadConfigActualizada[tipoNovedad]) {
        NovedadesApp.mostrarError('Tipo de novedad no válido');
        return false;
    }

    const config = tiposNovedadConfigActualizada[tipoNovedad];
    const errores = [];
    let valido = true;

    // Validar campos requeridos
    config.campos.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (campo) {
            let estaVacio = false;
            
            if (campo.type === 'date') {
                estaVacio = !campo.value || campo.value === '';
            } else if (campo.type === 'checkbox') {
                // Los checkboxes no son obligatorios por defecto
                estaVacio = false;
            } else {
                estaVacio = !campo.value.trim();
            }
            
            if (estaVacio && config.validaciones.includes(campoId.replace(/_(plus_caja|sub|enc|premio_local|individual|local|ajuste).*/, ''))) {
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
            const sucursalActual = document.getElementById('sucursal_actual').value;
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
            // Validar límite máximo (decimal(15,2) permite hasta 999,999,999,999.99)
            if (importe > 999999999999.99) {
                errores.push('El importe no puede ser mayor a $999,999,999,999.99');
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

        case 12: // Plus de caja
        case 32: // Plus de caja (ID real BD)
            const tipoPlusCaja = document.getElementById('tipo_plus_caja').value;
            if (!tipoPlusCaja) {
                errores.push('Debe seleccionar el tipo de plus de caja');
                valido = false;
            }
            
            // Solo validar importe si el tipo lo requiere (premio requiere importe, recibo puede ser opcional)
            if (tipoPlusCaja === 'premio') {
                const importePlusCaja = parseFloat(document.getElementById('importe_plus_caja').value);
                if (!importePlusCaja || importePlusCaja <= 0) {
                    errores.push('El importe es obligatorio para premios de caja');
                    valido = false;
                }
            }
            break;

        case 13: // Plus de Sub-Encargada
        case 33: // Plus de Sub-Encargada (ID real BD)
            const tieneImporteSub = document.getElementById('tiene_importe_sub').value;
            if (tieneImporteSub === '1') {
                const importeSub = parseFloat(document.getElementById('importe_sub_encargada').value);
                if (!importeSub || importeSub <= 0) {
                    errores.push('Debe especificar el importe cuando selecciona "Sí"');
                    valido = false;
                }
            }
            break;

        case 14: // Plus de Encargada
        case 34: // Plus de Encargada (ID real BD)
            const tieneImporteEnc = document.getElementById('tiene_importe_enc').value;
            if (tieneImporteEnc === '1') {
                const importeEnc = parseFloat(document.getElementById('importe_encargada').value);
                if (!importeEnc || importeEnc <= 0) {
                    errores.push('Debe especificar el importe cuando selecciona "Sí"');
                    valido = false;
                }
            }
            break;

        case 15: // Premio Local
        case 35: // Premio Local (ID real BD)
            const importePremioLocal = parseFloat(document.getElementById('importe_premio_local').value);
            if (!importePremioLocal || importePremioLocal <= 0) {
                errores.push('El importe del premio es obligatorio');
                valido = false;
            }
            
            // Validar que al menos una aplicación esté seleccionada (pero no ambas)
            const aplicaVendedora = document.getElementById('aplica_vendedora').checked;
            const aplicaSubEncargada = document.getElementById('aplica_sub_encargada').checked;
            
            if (!aplicaVendedora && !aplicaSubEncargada) {
                errores.push('Debe seleccionar al menos a quién aplica el premio');
                valido = false;
            }
            
            // Validar que no se seleccionen ambas (excluyente)
            if (aplicaVendedora && aplicaSubEncargada) {
                errores.push('Solo puede seleccionar una opción: Vendedora O Sub-Encargada, no ambas');
                valido = false;
            }
            break;

        case 16: // Comisión Individual
        case 36: // Comisión Individual (ID real BD)
            // COMISIÓN INDIVIDUAL: Solo validar un porcentaje simple
            const porcentajeIndividual = parseFloat(document.getElementById('porcentaje_individual').value);
            if (!porcentajeIndividual || porcentajeIndividual <= 0) {
                errores.push('El porcentaje de comisión individual es requerido y debe ser mayor a 0');
                valido = false;
            }
            break;

        case 17: // Comisión sobre Local
        case 37: // Comisión sobre Local (ID real BD)
            // COMISIÓN SOBRE LOCAL: Validar estructura con tope
            const tieneTope = document.getElementById('tiene_tope_local').value;
            
            if (tieneTope === '1') {
                // Con tope: validar dos porcentajes
                const porcentaje1 = parseFloat(document.getElementById('porcentaje_1_local').value);
                const porcentaje2 = parseFloat(document.getElementById('porcentaje_2_local').value);
                
                if (!porcentaje1 || porcentaje1 <= 0) {
                    errores.push('El primer porcentaje es requerido y debe ser mayor a 0');
                    valido = false;
                }
                if (!porcentaje2 || porcentaje2 <= 0) {
                    errores.push('El segundo porcentaje es requerido y debe ser mayor a 0');
                    valido = false;
                }
            } else if (tieneTope === '0') {
                // Sin tope: validar un porcentaje
                const porcentajeUnico = parseFloat(document.getElementById('porcentaje_unico_local').value);
                
                if (!porcentajeUnico || porcentajeUnico <= 0) {
                    errores.push('El porcentaje es requerido y debe ser mayor a 0');
                    valido = false;
                }
            } else {
                errores.push('Debe indicar si la comisión tiene tope o no');
                valido = false;
            }
            break;

        case 18: // Premios - Ajuste General
        case 38: // Premios - Ajuste General (ID real BD)
            const importeAjuste = parseFloat(document.getElementById('importe_ajuste_general').value);
            if (!importeAjuste || importeAjuste <= 0) {
                errores.push('El importe del ajuste es obligatorio');
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
async function recopilarDatosFormularioActualizado(tipoNovedad) {
    console.log('📦 Iniciando recopilación de datos...');
    
    // Obtener datos del empleado
    const legajo = document.getElementById('legajo').value;
    
    let nombreEmpleado = '';
    let apellidoEmpleado = '';
    
    // Método 1: Obtener desde NovedadesApp si está disponible
    const empleadoSeleccionado = window.NovedadesApp ? window.NovedadesApp.empleadoSeleccionado : null;
    console.log('👤 Empleado desde NovedadesApp:', empleadoSeleccionado);
    
    if (empleadoSeleccionado && empleadoSeleccionado.nombre && empleadoSeleccionado.apellido) {
        nombreEmpleado = empleadoSeleccionado.nombre;
        apellidoEmpleado = empleadoSeleccionado.apellido;
        console.log('✅ Usando datos almacenados en NovedadesApp');
    } else {
        // Método 2: Obtener del Select2 directamente
        try {
            const empleadoSelect = $('#empleado-select');
            if (empleadoSelect.length && empleadoSelect.val()) {
                const selectedData = empleadoSelect.select2('data')[0];
                console.log('👤 Datos desde Select2:', selectedData);
                
                if (selectedData) {
                    // Si viene con nombre y apellido separados
                    if (selectedData.nombre && selectedData.apellido) {
                        nombreEmpleado = selectedData.nombre;
                        apellidoEmpleado = selectedData.apellido;
                    } else if (selectedData.text) {
                        // Extraer nombre y apellido del texto "NOMBRE APELLIDO (LEGAJO)"
                        const match = selectedData.text.match(/^(.+?)\s+(.+?)\s*\(\d+\)$/);
                        if (match) {
                            nombreEmpleado = match[1].trim();
                            apellidoEmpleado = match[2].trim();
                        }
                    }
                    console.log('✅ Extraído del Select2:', { nombreEmpleado, apellidoEmpleado });
                }
            }
        } catch (e) {
            console.warn('⚠️ Error obteniendo datos del Select2:', e);
        }
        
        // Método 3: Buscar por legajo como último recurso
        if ((!nombreEmpleado || !apellidoEmpleado) && legajo) {
            console.log('🔍 Buscando empleado por legajo:', legajo);
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
    
    // Validar que tenemos los datos obligatorios
    if (!nombreEmpleado || !apellidoEmpleado) {
        throw new Error('No se pudo obtener el nombre y apellido del empleado seleccionado');
    }
    
    // Datos básicos - OBLIGATORIOS
    const datos = {
        legajo: legajo,
        nombre: nombreEmpleado,
        apellido: apellidoEmpleado,
        tipo_novedad: tipoNovedad,
        observaciones: document.getElementById('observaciones') ? document.getElementById('observaciones').value : ''
    };
    
    console.log('📋 Datos básicos recopilados:', datos);

    // Agregar campos específicos según el tipo
    switch (tipoNovedad) {
        case 1: // Cambio de sucursal
            datos.nueva_sucursal = document.getElementById('nueva_sucursal').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_sucursal').value;
            // Agregar sucursal actual para las observaciones (usar número, no descripción)
            const empleadoSeleccionado = window.NovedadesApp?.empleadoSeleccionado;
            if (empleadoSeleccionado && empleadoSeleccionado.sucursal_numero) {
                datos.sucursal_actual = empleadoSeleccionado.sucursal_numero;
            }
            break;

        case 2: // Nuevo puesto
            datos.puesto = document.getElementById('nuevo_puesto').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_puesto').value;
            
            // Obtener tipo de puesto (permanente/temporario) - MEJORADO
            let tipoPuesto = 'permanente'; // default seguro
            
            // Verificar radio buttons
            const radioPermanente = document.getElementById('tipo_puesto_permanente');
            const radioTemporario = document.getElementById('tipo_puesto_temporario');
            
            if (radioTemporario && radioTemporario.checked) {
                tipoPuesto = 'temporario';
            } else if (radioPermanente && radioPermanente.checked) {
                tipoPuesto = 'permanente';
            }
            // Si ninguno está seleccionado, queda 'permanente' por defecto
            
            datos.tipo_nuevo_puesto = tipoPuesto;
            
            // Si es temporario, agregar fecha de fin
            if (tipoPuesto === 'temporario') {
                const fechaHasta = document.getElementById('fecha_vigencia_hasta_puesto');
                datos.fecha_vigencia_hasta = fechaHasta ? fechaHasta.value : '';
            } else {
                // Para permanente, asegurar que fecha_vigencia_hasta sea null
                datos.fecha_vigencia_hasta = null;
            }
            
            console.log('🔍 NUEVO PUESTO - Datos recopilados:', {
                puesto: datos.puesto,
                fecha_vigencia: datos.fecha_vigencia,
                tipo_nuevo_puesto: datos.tipo_nuevo_puesto,
                fecha_vigencia_hasta: datos.fecha_vigencia_hasta || 'N/A',
                radioPermanenteChecked: radioPermanente ? radioPermanente.checked : 'no encontrado',
                radioTemporarioChecked: radioTemporario ? radioTemporario.checked : 'no encontrado'
            });
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

        case 12: // Plus de caja
        case 32: // Plus de caja (ID real BD)
            datos.tipo_plus_caja = document.getElementById('tipo_plus_caja').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_plus_caja').value;
            
            // Solo incluir importe si se especifica
            const importePlusCaja = document.getElementById('importe_plus_caja');
            if (importePlusCaja && importePlusCaja.value) {
                datos.importe = parseFloat(importePlusCaja.value);
            }
            break;

        case 13: // Plus de Sub-Encargada
        case 33: // Plus de Sub-Encargada (ID real BD)
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_plus_sub').value;
            datos.tiene_importe = document.getElementById('tiene_importe_sub').value;
            
            if (datos.tiene_importe === '1') {
                const importeSub = document.getElementById('importe_sub_encargada');
                if (importeSub && importeSub.value) {
                    datos.importe = parseFloat(importeSub.value);
                }
            }
            break;

        case 14: // Plus de Encargada
        case 34: // Plus de Encargada (ID real BD)
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_plus_enc').value;
            datos.tiene_importe = document.getElementById('tiene_importe_enc').value;
            
            if (datos.tiene_importe === '1') {
                const importeEnc = document.getElementById('importe_encargada');
                if (importeEnc && importeEnc.value) {
                    datos.importe = parseFloat(importeEnc.value);
                }
            }
            break;

        case 15: // Premio Local
        case 35: // Premio Local (ID real BD)
            datos.importe = parseFloat(document.getElementById('importe_premio_local').value);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_premio_local').value;
            datos.aplica_vendedora = document.getElementById('aplica_vendedora').checked;
            datos.aplica_sub_encargada = document.getElementById('aplica_sub_encargada').checked;
            break;

        case 16: // Comisión Individual
        case 16: // Comisión Individual
        case 36: // Comisión Individual (ID real BD)
            // COMISIÓN INDIVIDUAL: Solo un porcentaje simple
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_comision_individual').value;
            datos.porcentaje_individual = parseFloat(document.getElementById('porcentaje_individual').value);
            datos.tipo_comision = 'individual';
            break;

        case 17: // Comisión sobre Local
        case 37: // Comisión sobre Local (ID real BD)
            // COMISIÓN SOBRE LOCAL: Con estructura de tope
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_comision_local').value;
            datos.tiene_tope = document.getElementById('tiene_tope_local').value === '1';
            
            if (datos.tiene_tope) {
                // Con tope: dos porcentajes
                datos.porcentaje_1 = parseFloat(document.getElementById('porcentaje_1_local').value);
                datos.porcentaje_2 = parseFloat(document.getElementById('porcentaje_2_local').value);
            } else {
                // Sin tope: un porcentaje
                datos.porcentaje_unico = parseFloat(document.getElementById('porcentaje_unico_local').value);
            }
            datos.tipo_comision = 'local';
            break;

        case 18: // Premios - Ajuste General
        case 38: // Premios - Ajuste General (ID real BD)
            datos.importe = parseFloat(document.getElementById('importe_ajuste_general').value);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_ajuste').value;
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
 * Cargar tipos de novedad - NUEVO CON INFORMACIÓN DE CORTE
 */
let tiposNovedadData = {}; // Variable global para almacenar la información completa de tipos

/**
 * Calcular el primer día hábil del mes usando API de días feriados
 */
async function calcularPrimerDiaHabil(year, month) {
    try {
        // API para obtener feriados de Argentina
        const response = await fetch(`https://nolaborables.com.ar/API/v2/feriados/${year}`);
        const feriados = await response.json();
        
        // Crear array de fechas de feriados para el mes específico
        const feriadosDelMes = feriados
            .filter(feriado => {
                const fechaFeriado = new Date(feriado.fecha);
                return fechaFeriado.getMonth() === month && fechaFeriado.getFullYear() === year;
            })
            .map(feriado => new Date(feriado.fecha).getDate());
        
        // Buscar el primer día hábil
        for (let dia = 1; dia <= 31; dia++) {
            const fecha = new Date(year, month, dia);
            
            // Verificar que la fecha sea válida para el mes
            if (fecha.getMonth() !== month) break;
            
            const diaSemana = fecha.getDay(); // 0 = domingo, 6 = sábado
            
            // Si no es fin de semana y no es feriado
            if (diaSemana !== 0 && diaSemana !== 6 && !feriadosDelMes.includes(dia)) {
                return dia;
            }
        }
        
        // Fallback: si no se encuentra, retornar día 1
        return 1;
        
    } catch (error) {
        console.warn('Error obteniendo feriados, usando día 1 como fallback:', error);
        // Fallback simple: buscar primer día que no sea fin de semana
        for (let dia = 1; dia <= 7; dia++) {
            const fecha = new Date(year, month, dia);
            const diaSemana = fecha.getDay();
            if (diaSemana !== 0 && diaSemana !== 6) {
                return dia;
            }
        }
        return 1;
    }
}

/**
 * Obtener día de cierre efectivo (incluyendo cálculo de primer día hábil)
 */
async function obtenerDiaCierreEfectivo(valorCierre, year, month) {
    if (valorCierre === '1er día hábil') {
        return await calcularPrimerDiaHabil(year, month);
    }
    
    // Si es un número, convertir a entero
    const diaNumerico = parseInt(valorCierre);
    if (!isNaN(diaNumerico) && diaNumerico >= 1 && diaNumerico <= 31) {
        return diaNumerico;
    }
    
    // Fallback
    return 28;
}

/**
 * Calcular período siguiente basado en el día de cierre del tipo de novedad
 */
async function calcularPeriodoSiguienteSegunCierre(tipoNovedadId, fechaReferencia = null) {
    const tipoData = tiposNovedadData[tipoNovedadId];
    if (!tipoData || !tipoData.cierre) {
        // Fallback: usar día 28
        return calcularPeriodoConDiaCierre(28, fechaReferencia, false, true);
    }
    
    const hoy = fechaReferencia ? new Date(fechaReferencia) : new Date();
    const year = hoy.getFullYear();
    const month = hoy.getMonth(); // 0-based
    
    // Obtener día de cierre efectivo
    const diaCierre = await obtenerDiaCierreEfectivo(tipoData.cierre, year, month);
    
    // Determinar si es "1er día hábil" para aplicar lógica especial
    const esPrimerDiaHabil = tipoData.cierre === '1er día hábil';
    
    return calcularPeriodoConDiaCierre(diaCierre, fechaReferencia, esPrimerDiaHabil, true);
}
async function calcularPeriodoSegunCierre(tipoNovedadId, fechaReferencia = null) {
    const tipoData = tiposNovedadData[tipoNovedadId];
    if (!tipoData || !tipoData.cierre) {
        // Fallback: usar día 28
        return calcularPeriodoConDiaCierre(28, fechaReferencia, false);
    }
    
    const hoy = fechaReferencia ? new Date(fechaReferencia) : new Date();
    const year = hoy.getFullYear();
    const month = hoy.getMonth(); // 0-based
    
    // Obtener día de cierre efectivo
    const diaCierre = await obtenerDiaCierreEfectivo(tipoData.cierre, year, month);
    
    // Determinar si es "1er día hábil" para aplicar lógica especial
    const esPrimerDiaHabil = tipoData.cierre === '1er día hábil';
    
    return calcularPeriodoConDiaCierre(diaCierre, fechaReferencia, esPrimerDiaHabil);
}

/**
 * Calcular período con un día de cierre específico
 */
function calcularPeriodoConDiaCierre(diaCierre, fechaReferencia = null, esPrimerDiaHabil = false, esPeriodoSiguiente = false) {
    const hoy = fechaReferencia ? new Date(fechaReferencia) : new Date();
    const diaActual = hoy.getDate();
    
    let yearPeriodo = hoy.getFullYear();
    let mesPeriodo = hoy.getMonth(); // 0-based
    
    if (esPrimerDiaHabil) {
        // LÓGICA ESPECIAL PARA "1er día hábil":
        // Si día actual <= 1er día hábil → período del mes ANTERIOR
        // Si día actual > 1er día hábil → período del mes ACTUAL
        if (diaActual <= diaCierre) {
            mesPeriodo--;
            
            // Si se va antes de enero, decrementar año
            if (mesPeriodo < 0) {
                mesPeriodo = 11; // diciembre
                yearPeriodo--;
            }
        }
        // Si diaActual > diaCierre, se queda en el mes actual (no se modifica)
        
    } else {
        // LÓGICA NORMAL PARA DÍAS NUMÉRICOS:
        // Si día actual > día de cierre → período del mes SIGUIENTE
        if (diaActual > diaCierre) {
            mesPeriodo++;
            
            // Si se pasa de diciembre, incrementar año
            if (mesPeriodo > 11) {
                mesPeriodo = 0;
                yearPeriodo++;
            }
        }
        // Si diaActual <= diaCierre, se queda en el mes actual (no se modifica)
    }
    
    // Si se solicita período siguiente, avanzar un mes más
    if (esPeriodoSiguiente) {
        mesPeriodo++;
        if (mesPeriodo > 11) {
            mesPeriodo = 0;
            yearPeriodo++;
        }
    }
    
    return {
        year: yearPeriodo,
        month: mesPeriodo + 1, // Convertir a 1-based para la BD
        diaCierre: diaCierre,
        fechaPeriodo: new Date(yearPeriodo, mesPeriodo, diaCierre),
        esPrimerDiaHabil: esPrimerDiaHabil,
        esPeriodoSiguiente: esPeriodoSiguiente,
        logicaAplicada: esPrimerDiaHabil 
            ? (diaActual <= diaCierre ? 'mes anterior' : 'mes actual')
            : (diaActual > diaCierre ? 'mes siguiente' : 'mes actual'),
        logicaFinal: esPeriodoSiguiente ? 'período siguiente aplicado' : 'período normal'
    };
}

/**
 * Calcular fecha del período para fecha de vigencia
 * La fecha de vigencia es el día 1 del mes del período calculado
 */
async function calcularFechaPeriodoParaVigencia(tipoNovedadId) {
    const periodo = await calcularPeriodoSegunCierre(tipoNovedadId);
    
    // La fecha de vigencia es el primer día del mes del período
    const fechaInicioPeriodo = new Date(periodo.year, periodo.month - 1, 1); // periodo.month - 1 porque Date usa 0-based
    
    // Formatear como YYYY-MM-DD para input date
    return fechaInicioPeriodo.toISOString().split('T')[0];
}

/**
 * Calcular fecha del período siguiente para fecha de vigencia
 */
async function calcularFechaPeriodoSiguienteParaVigencia(tipoNovedadId) {
    const periodo = await calcularPeriodoSiguienteSegunCierre(tipoNovedadId);
    
    // La fecha de vigencia es el primer día del mes del período siguiente
    const fechaInicioPeriodo = new Date(periodo.year, periodo.month - 1, 1); // periodo.month - 1 porque Date usa 0-based
    
    // Formatear como YYYY-MM-DD para input date
    return fechaInicioPeriodo.toISOString().split('T')[0];
}
/**
 * Configurar fecha de vigencia según tipo de corte
 */
async function configurarFechaVigencia(tipoNovedadId, campoFechaId) {
    const tipoData = tiposNovedadData[tipoNovedadId];
    const campoFecha = document.getElementById(campoFechaId);
    
    console.log('🔍 configurarFechaVigencia llamado:', {
        tipoNovedadId,
        campoFechaId,
        tipoDataDisponible: !!tipoData,
        campoFechaEncontrado: !!campoFecha,
        tipoData: tipoData
    });
    
    if (!tipoData || !campoFecha) {
        console.error('❌ Datos faltantes:', { tipoData: !!tipoData, campoFecha: !!campoFecha });
        return;
    }
    
    // Prevenir configuraciones múltiples simultáneas
    if (campoFecha.hasAttribute('data-configurando')) {
        console.log('⏳ Configuración en proceso, saltando...');
        return;
    }
    campoFecha.setAttribute('data-configurando', 'true');
    
    try {
        // Buscar contenedor del campo para agregar mensaje
        const contenedorCampo = campoFecha.closest('.mb-3') || campoFecha.parentElement;
        
        // Limpiar TODOS los mensajes anteriores de forma más agresiva
        const todoElDocumento = document;
        const todosMensajesGlobales = todoElDocumento.querySelectorAll('.fecha-vigencia-info');
        todosMensajesGlobales.forEach(mensaje => {
            if (contenedorCampo.contains(mensaje) || mensaje.closest('.form-group') === contenedorCampo) {
                mensaje.remove();
            }
        });
    
    if (tipoData.corte === 'Período' || tipoData.corte === 'Periodo') {
        // Si es Período, calcular fecha basada en día de cierre
        try {
            const fechaPeriodo = await calcularFechaPeriodoParaVigencia(tipoNovedadId);
            const periodo = await calcularPeriodoSegunCierre(tipoNovedadId);
            
            campoFecha.value = fechaPeriodo;
            
            // Calcular fechas del período mensual para mostrar
            const fechaInicio = `01/${periodo.month.toString().padStart(2, '0')}/${periodo.year}`;
            const ultimoDiaDelMes = new Date(periodo.year, periodo.month, 0).getDate();
            const fechaFin = `${ultimoDiaDelMes.toString().padStart(2, '0')}/${periodo.month.toString().padStart(2, '0')}/${periodo.year}`;
            
            // Mensaje explicativo con información del período mensual
            let cierreTexto;
            
            if (tipoData.cierre === '1er día hábil') {
                cierreTexto = `primer día hábil (día ${periodo.diaCierre})`;
            } else {
                cierreTexto = `día ${tipoData.cierre}`;
            }
                
            const mensajeInfo = document.createElement('div');
            mensajeInfo.className = 'fecha-vigencia-info mt-2';
            mensajeInfo.innerHTML = `
                <div class="alert alert-info alert-sm py-2">
                    <i class="fas fa-calendar-check me-2"></i>
                    <strong>Período:</strong> 
                    ${periodo.month.toString().padStart(2, '0')}/${periodo.year}
                    <br>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Rango mensual:</strong> ${fechaInicio} al ${fechaFin} | 
                        <strong>Cierre:</strong> ${cierreTexto}
                    </small>
                    <br>
                    <small class="text-success">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <strong>Fecha de vigencia:</strong> Primer día del mes (${fechaInicio})
                    </small>
                </div>
            `;
            contenedorCampo.appendChild(mensajeInfo);
            
        } catch (error) {
            console.error('Error calculando período:', error);
            
            // Fallback al cálculo simple
            const hoy = new Date();
            const fechaFallback = new Date(hoy.getFullYear(), hoy.getMonth(), 28);
            campoFecha.value = fechaFallback.toISOString().split('T')[0];
            
            const mensajeInfo = document.createElement('div');
            mensajeInfo.className = 'fecha-vigencia-info mt-2';
            mensajeInfo.innerHTML = `
                <div class="alert alert-warning alert-sm py-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Fecha estimada:</strong> Se estableció una fecha aproximada. Verifique y ajuste si es necesario.
                </div>
            `;
            contenedorCampo.appendChild(mensajeInfo);
        }
        
    } else if (tipoData.corte === 'Período siguiente' || tipoData.corte === 'Periodo siguiente') {
        // Si es Período siguiente, calcular fecha basada en período siguiente
        try {
            const fechaPeriodo = await calcularFechaPeriodoSiguienteParaVigencia(tipoNovedadId);
            const periodo = await calcularPeriodoSiguienteSegunCierre(tipoNovedadId);
            
            campoFecha.value = fechaPeriodo;
            
            // Calcular fechas del período siguiente mensual para mostrar
            const fechaInicio = `01/${periodo.month.toString().padStart(2, '0')}/${periodo.year}`;
            const ultimoDiaDelMes = new Date(periodo.year, periodo.month, 0).getDate();
            const fechaFin = `${ultimoDiaDelMes.toString().padStart(2, '0')}/${periodo.month.toString().padStart(2, '0')}/${periodo.year}`;
            
            // Mensaje explicativo con información del período siguiente mensual
            let cierreTexto;
            
            if (tipoData.cierre === '1er día hábil') {
                cierreTexto = `primer día hábil (día ${periodo.diaCierre})`;
            } else {
                cierreTexto = `día ${tipoData.cierre}`;
            }
                
            const mensajeInfo = document.createElement('div');
            mensajeInfo.className = 'fecha-vigencia-info mt-2';
            mensajeInfo.innerHTML = `
                <div class="alert alert-warning alert-sm py-2">
                    <i class="fas fa-forward me-2"></i>
                    <strong>Período Siguiente:</strong> 
                    ${periodo.month.toString().padStart(2, '0')}/${periodo.year}
                    <br>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Rango mensual:</strong> ${fechaInicio} al ${fechaFin} | 
                        <strong>Cierre:</strong> ${cierreTexto}
                    </small>
                    <br>
                    <small class="text-success">
                        <i class="fas fa-calendar-plus me-1"></i>
                        <strong>Fecha de vigencia:</strong> Primer día del mes (${fechaInicio})
                    </small>
                </div>
            `;
            contenedorCampo.appendChild(mensajeInfo);
            
        } catch (error) {
            console.error('Error calculando período siguiente:', error);
            
            // Fallback
            const hoy = new Date();
            const mesProximo = hoy.getMonth() + 1;
            const yearProximo = mesProximo > 11 ? hoy.getFullYear() + 1 : hoy.getFullYear();
            const mesAjustado = mesProximo > 11 ? 0 : mesProximo;
            const fechaFallback = new Date(yearProximo, mesAjustado, 28);
            campoFecha.value = fechaFallback.toISOString().split('T')[0];
            
            const mensajeInfo = document.createElement('div');
            mensajeInfo.className = 'fecha-vigencia-info mt-2';
            mensajeInfo.innerHTML = `
                <div class="alert alert-warning alert-sm py-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Fecha estimada:</strong> Se estableció una fecha aproximada del período siguiente. Verifique y ajuste si es necesario.
                </div>
            `;
            contenedorCampo.appendChild(mensajeInfo);
        }
        
    } else if (tipoData.corte === 'Fecha Vigencia' || tipoData.corte === 'Fecha de Vigencia') {
        // Si es Fecha Vigencia, limpiar campo y permitir selección libre
        campoFecha.value = '';
        
        // Agregar mensaje informativo
        const mensajeInfo = document.createElement('div');
        mensajeInfo.className = 'fecha-vigencia-info mt-2';
        mensajeInfo.innerHTML = `
            <div class="alert alert-primary alert-sm py-2">
                <i class="fas fa-calendar-alt me-2"></i>
                <strong>Fecha de vigencia libre:</strong> Seleccione la fecha específica de entrada en vigencia.
            </div>
        `;
        contenedorCampo.appendChild(mensajeInfo);
    }
    
    } finally {
        // Remover flag de configuración en proceso
        campoFecha.removeAttribute('data-configurando');
    }
}

async function cargarTiposNovedad() {
    try {
        const tipos = await NovedadesApp.request('get_tipos_novedad');
        
        // Filtrar duplicados por descripción (para eliminar duplicados específicos)
        const tiposUnicos = tipos.filter((tipo, index, arr) => 
            arr.findIndex(t => t.descripcion === tipo.descripcion) === index
        );
        
        // Almacenar la información completa globalmente
        tiposNovedadData = {};
        tiposUnicos.forEach(tipo => {
            tiposNovedadData[tipo.id] = tipo;
        });
        
        const selectTipo = document.getElementById('tipo_novedad');
        if (selectTipo) {
            selectTipo.innerHTML = '<option value="">Seleccione tipo de novedad...</option>';
            tiposUnicos.forEach(tipo => {
                if (tipo.activo == 1) { // Solo mostrar tipos activos
                    selectTipo.innerHTML += `<option value="${tipo.id}">${tipo.descripcion}</option>`;
                }
            });
        }

        console.log('Tipos de novedad cargados:', tiposUnicos.length);
        console.log('Datos de tipos:', tiposNovedadData);

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
                    
                    // Si está activo el tipo "Cambio de Puesto", actualizar puesto actual
                    const tipoSelect = document.getElementById('tipo_novedad');
                    if (tipoSelect && tipoSelect.value === '2') {
                        configurarCambioPuesto();
                    }
                    
                    // Si está activo el tipo "Cambio de Sucursal", actualizar sucursal actual
                    if (tipoSelect && tipoSelect.value === '1') {
                        cargarSucursalActualEmpleado(legajo);
                    }
                    
                } catch (error) {
                    console.log('Empleado no encontrado para autocompletado:', legajo);
                }
            }
        }, 1000);
    });
}

/**
 * Configurar campos para Premio Local con checkboxes excluyentes
 */
function configurarCamposPremioLocal() {
    const checkVendedora = document.getElementById('aplica_vendedora');
    const checkSubEncargada = document.getElementById('aplica_sub_encargada');
    
    if (checkVendedora && checkSubEncargada) {
        // Función para manejar exclusividad
        function manejarExclusividad(checkActual, checkOtro) {
            checkActual.addEventListener('change', function() {
                if (this.checked) {
                    checkOtro.checked = false;
                }
            });
        }
        
        // Aplicar exclusividad a ambos checkboxes
        manejarExclusividad(checkVendedora, checkSubEncargada);
        manejarExclusividad(checkSubEncargada, checkVendedora);
        
        console.log('✅ Premio Local configurado con checkboxes excluyentes');
    }
}

/**
 * Configurar campos dinámicos para comisiones
 */
function configurarCamposComision(tipoComision) {
    if (tipoComision === 'individual') {
        // COMISIÓN INDIVIDUAL: No tiene campos dinámicos, solo validación simple
        console.log('✅ Comisión Individual configurada (sin campos dinámicos)');
        return;
    }
    
    if (tipoComision === 'local') {
        // COMISIÓN SOBRE LOCAL: Configurar campos con tope/sin tope
        const tienTopeSelect = document.getElementById('tiene_tope_local');
        const camposSinTope = document.getElementById('campos_sin_tope_local');
        const camposConTope = document.getElementById('campos_con_tope_local');
        
        if (tienTopeSelect) {
            tienTopeSelect.addEventListener('change', function() {
                const tieneTope = this.value === '1';
                
                if (tieneTope) {
                    // Mostrar campos con tope, ocultar sin tope
                    if (camposConTope) {
                        camposConTope.style.display = 'block';
                        camposConTope.querySelectorAll('input').forEach(input => {
                            input.setAttribute('required', 'required');
                        });
                    }
                    if (camposSinTope) {
                        camposSinTope.style.display = 'none';
                        camposSinTope.querySelectorAll('input').forEach(input => {
                            input.removeAttribute('required');
                            input.value = '';
                        });
                    }
                } else if (this.value === '0') {
                    // Mostrar campos sin tope, ocultar con tope
                    if (camposSinTope) {
                        camposSinTope.style.display = 'block';
                        camposSinTope.querySelectorAll('input').forEach(input => {
                            input.setAttribute('required', 'required');
                        });
                    }
                    if (camposConTope) {
                        camposConTope.style.display = 'none';
                        camposConTope.querySelectorAll('input').forEach(input => {
                            input.removeAttribute('required');
                            input.value = '';
                        });
                    }
                } else {
                    // No seleccionado: ocultar ambos
                    if (camposSinTope) {
                        camposSinTope.style.display = 'none';
                        camposSinTope.querySelectorAll('input').forEach(input => {
                            input.removeAttribute('required');
                            input.value = '';
                        });
                    }
                    if (camposConTope) {
                        camposConTope.style.display = 'none';
                        camposConTope.querySelectorAll('input').forEach(input => {
                            input.removeAttribute('required');
                            input.value = '';
                        });
                    }
                }
                
                console.log(`Comisión sobre Local: Tope = ${tieneTope}`);
            });
            
            console.log('✅ Comisión sobre Local configurada con campos dinámicos');
        } else {
            console.error('❌ No se encontró el select tiene_tope_local');
        }
    }
}

/**
 * Configurar campos para plus con importe opcional
 */
function configurarCamposPlusConImporte(tipo) {
    const sufijo = tipo === 'sub' ? 'sub' : 'enc';
    const tieneImporteSelect = document.getElementById(`tiene_importe_${sufijo}`);
    const campoImporte = document.getElementById(`campo_importe_${sufijo === 'sub' ? 'sub_encargada' : 'encargada'}`);
    
    if (tieneImporteSelect && campoImporte) {
        tieneImporteSelect.addEventListener('change', function() {
            if (this.value === '1') {
                // Mostrar campo de importe
                campoImporte.style.display = 'block';
                const inputImporte = campoImporte.querySelector('input[type="number"]');
                if (inputImporte) {
                    inputImporte.setAttribute('required', 'required');
                }
            } else {
                // Ocultar campo de importe
                campoImporte.style.display = 'none';
                const inputImporte = campoImporte.querySelector('input[type="number"]');
                if (inputImporte) {
                    inputImporte.removeAttribute('required');
                    inputImporte.value = '';
                }
            }
        });
    }
}

/**
 * Configurar campos para plus de caja
 */
function configurarCamposPlusCaja() {
    const tipoSelect = document.getElementById('tipo_plus_caja');
    const campoImporte = document.getElementById('campo_importe_plus_caja');
    
    if (tipoSelect && campoImporte) {
        tipoSelect.addEventListener('change', function() {
            if (this.value === 'premio') {
                // Para premios, el importe es obligatorio
                campoImporte.style.display = 'block';
                const inputImporte = campoImporte.querySelector('input[type="number"]');
                if (inputImporte) {
                    inputImporte.setAttribute('required', 'required');
                }
            } else if (this.value === 'recibo') {
                // Para recibos, el importe es opcional
                campoImporte.style.display = 'block';
                const inputImporte = campoImporte.querySelector('input[type="number"]');
                if (inputImporte) {
                    inputImporte.removeAttribute('required');
                }
            } else {
                // Sin selección, ocultar importe
                campoImporte.style.display = 'none';
                const inputImporte = campoImporte.querySelector('input[type="number"]');
                if (inputImporte) {
                    inputImporte.removeAttribute('required');
                    inputImporte.value = '';
                }
            }
        });
    }
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
    
    // Configurar cambio de tipo de novedad - REMOVIDO: ya se configura en main.js
    // const tipoNovedadSelect = document.getElementById('tipo_novedad');
    // if (tipoNovedadSelect) {
    //     tipoNovedadSelect.addEventListener('change', function() {
    //         console.log('Tipo de novedad seleccionado:', this.value);
    //         onTipoNovedadChangeActualizado(this);
    //     });
    // }
    
    // Configurar validación en tiempo real
    configurarValidacionTiempoReal();
    
    // Configurar autocompletado de empleado
    configurarAutocompletadoEmpleado();
    
    // Configurar eventos específicos
    configurarEventosEspecificos();
    
    console.log('Formulario Nueva Novedad - Inicializado correctamente');
});

/**
 * Configurar cambio de puesto - Mostrar puesto actual
 */
async function configurarCambioPuesto() {
    const legajoInput = document.getElementById('legajo');
    
    if (!legajoInput || !legajoInput.value) {
        // Si no hay empleado seleccionado, ocultar el puesto actual
        const puestoActualInfo = document.getElementById('puesto-actual-info');
        if (puestoActualInfo) {
            puestoActualInfo.style.display = 'none';
        }
        return;
    }
    
    try {
        const legajo = legajoInput.value;
        console.log('🔍 Obteniendo puesto actual para legajo:', legajo);
        
        const empleado = await NovedadesApp.request('buscar_empleado', { legajo: legajo });
        console.log('👤 Datos empleado recibidos:', empleado);
        
        const puestoActualInfo = document.getElementById('puesto-actual-info');
        const puestoActualTexto = document.getElementById('puesto-actual-texto');
        
        if (puestoActualInfo && puestoActualTexto) {
            if (empleado.puesto_actual && empleado.puesto_actual.trim()) {
                puestoActualTexto.textContent = empleado.puesto_actual.trim();
                puestoActualInfo.style.display = 'block';
                console.log('✅ Mostrando puesto actual:', empleado.puesto_actual.trim());
            } else {
                puestoActualTexto.textContent = 'No especificado';
                puestoActualInfo.style.display = 'block';
                console.log('⚠️ Puesto actual no especificado');
            }
        }
        
        // Configurar event listeners para tipo de puesto (Permanente/Temporario)
        configurarTipoPuesto();
        
    } catch (error) {
        console.error('❌ Error obteniendo puesto actual:', error);
        const puestoActualInfo = document.getElementById('puesto-actual-info');
        if (puestoActualInfo) {
            puestoActualInfo.style.display = 'none';
        }
    }
}

/**
 * Configurar lógica de tipo de puesto (Permanente/Temporario)
 */
function configurarTipoPuesto() {
    const radioPermanente = document.getElementById('tipo_puesto_permanente');
    const radioTemporario = document.getElementById('tipo_puesto_temporario');
    const campoFechaFin = document.getElementById('campo_fecha_fin');
    const fechaHastaCampo = document.getElementById('fecha_vigencia_hasta_puesto');
    
    // Event listener para cambio de tipo
    function manejarCambioTipoPuesto() {
        if (radioTemporario && radioTemporario.checked) {
            // Mostrar campo de fecha de fin para temporario
            if (campoFechaFin) {
                campoFechaFin.style.display = 'block';
                campoFechaFin.classList.add('fade-in');
            }
            if (fechaHastaCampo) {
                fechaHastaCampo.setAttribute('required', 'required');
            }
            console.log('🕐 Cambio temporario seleccionado - mostrando fecha de fin');
        } else {
            // Ocultar campo de fecha de fin para permanente
            if (campoFechaFin) {
                campoFechaFin.style.display = 'none';
                campoFechaFin.classList.remove('fade-in');
            }
            if (fechaHastaCampo) {
                fechaHastaCampo.removeAttribute('required');
                fechaHastaCampo.value = ''; // Limpiar valor
            }
            console.log('✅ Cambio permanente seleccionado - ocultando fecha de fin');
        }
    }
    
    // Agregar event listeners
    if (radioPermanente) {
        radioPermanente.addEventListener('change', manejarCambioTipoPuesto);
    }
    if (radioTemporario) {
        radioTemporario.addEventListener('change', manejarCambioTipoPuesto);
    }
    
    // Configurar estado inicial (permanente por defecto)
    if (radioPermanente) {
        radioPermanente.checked = true;
        manejarCambioTipoPuesto();
    }
}

/**
 * Obtener sucursal asociada a un centro de costos
 */
async function obtenerSucursalPorCentroCostos(codigoCentroCostos) {
    try {
        const response = await NovedadesApp.request('get_sucursal_por_centro_costos', { codigo: codigoCentroCostos });
        return response;
    } catch (error) {
        console.error('Error obteniendo sucursal por centro de costos:', error);
        return null;
    }
}

// Exponer funciones globalmente para compatibilidad
window.onTipoNovedadChangeSpecific = onTipoNovedadChangeActualizado;

/**
 * FUNCIONES PARA MANEJO DE MODO MÚLTIPLE
 */

/**
 * Inicializar eventos de modo de carga
 */
function initializarModosCarga() {
    const botonesModoCarga = document.querySelectorAll('.modo-carga-btn');
    
    botonesModoCarga.forEach(boton => {
        boton.addEventListener('click', function() {
            const modo = this.getAttribute('data-modo');
            seleccionarModoCarga(modo);
        });
    });
    
    // Event listener para agregar nueva novedad
    const btnAgregarNovedad = document.getElementById('agregar-novedad-btn');
    if (btnAgregarNovedad) {
        btnAgregarNovedad.addEventListener('click', agregarNuevaNovedadCard);
    }
}

/**
 * Seleccionar modo de carga
 */
function seleccionarModoCarga(modo) {
    console.log('🔄 Seleccionando modo de carga:', modo);
    
    window.modoActual = modo;
    
    // Actualizar indicador de modo
    actualizarIndicadorModo(modo);
    
    // Ocultar sección de selección de modo
    document.getElementById('modo-carga-section').style.display = 'none';
    
    // Mostrar formulario
    document.getElementById('form-novedad').style.display = 'block';
    
    // Mostrar botón de cambiar modo
    document.getElementById('btn-cambiar-modo-top').style.display = 'inline-block';
    
    if (modo === 'unica') {
        configurarModoUnico();
    } else if (modo === 'multiple') {
        configurarModoMultiple();
    }
}

/**
 * Actualizar indicador de modo activo
 */
function actualizarIndicadorModo(modo) {
    const indicator = document.getElementById('modo-activo-indicator');
    const text = document.getElementById('modo-activo-text');
    
    if (indicator && text) {
        if (modo === 'unica') {
            text.textContent = 'Modo: Novedad Única';
            indicator.style.backgroundColor = 'var(--primary-color)';
        } else if (modo === 'multiple') {
            text.textContent = 'Modo: Novedades Múltiples';
            indicator.style.backgroundColor = 'var(--success-color)';
        }
        
        indicator.style.display = 'block';
    }
}

/**
 * Actualizar indicador de modo activo
 */
function actualizarIndicadorModo(modo) {
    const indicator = document.getElementById('modo-activo-indicator');
    const text = document.getElementById('modo-activo-text');
    
    if (indicator && text) {
        if (modo === 'unica') {
            text.textContent = 'Modo: Novedad Única';
            indicator.style.backgroundColor = '#0d6efd';
        } else if (modo === 'multiple') {
            text.textContent = 'Modo: Novedades Múltiples';
            indicator.style.backgroundColor = '#198754';
        }
        indicator.style.display = 'block';
    }
}

/**
 * Volver a la selección de modo
 */
function volverSeleccionModo() {
    // Mostrar confirmación si hay datos
    const tieneNovedad = document.getElementById('tipo_novedad')?.value;
    const tieneNovedadesMultiples = document.querySelectorAll('.novedad-card').length > 0;
    
    if (tieneNovedad || tieneNovedadesMultiples) {
        if (!confirm('¿Está seguro de que desea cambiar el modo? Se perderán todos los datos ingresados.')) {
            return;
        }
    }
    
    // Limpiar formulario
    if (typeof limpiarFormularioSinConfirmacion === 'function') {
        limpiarFormularioSinConfirmacion();
    } else {
        document.getElementById('form-novedad').reset();
        if (typeof $ !== 'undefined' && $('#empleado-select').length) {
            $('#empleado-select').val(null).trigger('change');
        }
    }
    
    // Limpiar novedades múltiples
    window.novedadesData = [];
    window.contadorNovedades = 0;
    const container = document.getElementById('novedades-multiples-container');
    if (container) {
        container.innerHTML = '';
    }
    
    // Ocultar formulario y mostrar selección de modo
    document.getElementById('form-novedad').style.display = 'none';
    document.getElementById('modo-carga-section').style.display = 'block';
    
    // Ocultar botón de cambiar modo
    document.getElementById('btn-cambiar-modo-top').style.display = 'none';
    
    // Ocultar indicador de modo
    const indicator = document.getElementById('modo-activo-indicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
    
    // Resetear modo actual
    window.modoActual = null;
    
    console.log('🔄 Volviendo a selección de modo');
}

/**
 * Configurar modo único (funcionalidad original)
 */
function configurarModoUnico() {
    console.log('📝 Configurando modo único');
    
    // Mostrar elementos del modo único
    document.getElementById('tipo-novedad-unica').style.display = 'block';
    document.getElementById('configuracion-novedad').style.display = 'none';
    document.getElementById('observaciones-section').style.display = 'block'; // Mostrar observaciones
    
    // Ocultar elementos del modo múltiple
    document.getElementById('configuracion-global-section').style.display = 'none';
    document.getElementById('novedades-multiples-container').style.display = 'none';
    
    // Inicializar funcionalidad original
    if (typeof inicializarFormularioOriginal === 'function') {
        inicializarFormularioOriginal();
    }
}

/**
 * Configurar modo múltiple
 */
function configurarModoMultiple() {
    console.log('📚 Configurando modo múltiple');
    
    // Inicializar contador de novedades
    window.contadorNovedades = 0;
    window.novedadesData = [];
    
    // Ocultar elementos del modo único
    document.getElementById('tipo-novedad-unica').style.display = 'none';
    document.getElementById('configuracion-novedad').style.display = 'none';
    document.getElementById('observaciones-section').style.display = 'none'; // Ocultar observaciones generales
    
    // Mostrar elementos del modo múltiple
    document.getElementById('configuracion-global-section').style.display = 'block';
    document.getElementById('novedades-multiples-container').style.display = 'block';
    
    // Limpiar contenedor de cards
    const container = document.getElementById('novedades-cards-container');
    if (container) {
        container.innerHTML = '';
    }
    
    // Inicializar configuración global
    inicializarConfiguracionGlobal();
    
    // Agregar primera card de novedad
    agregarNuevaNovedadCard();
}

/**
 * Inicializar configuración global para modo múltiple
 */
function inicializarConfiguracionGlobal() {
    // Cargar opciones de período
    cargarOpcionesPeriodo();
    
    // Configurar fecha de vigencia por defecto
    const fechaVigenciaGlobal = document.getElementById('fecha_vigencia_global');
    if (fechaVigenciaGlobal) {
        // Establecer fecha mínima como hoy
        const hoy = new Date().toISOString().split('T')[0];
        fechaVigenciaGlobal.min = hoy;
    }
}

/**
 * Cargar opciones de período (mes y año separados con rangos amplios)
 */
function cargarOpcionesPeriodo() {
    const selectMes = document.getElementById('periodo_mes_global');
    const selectAnio = document.getElementById('periodo_anio_global');
    
    if (!selectMes || !selectAnio) return;
    
    // Nombres de meses
    const meses = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    
    // Fecha actual
    const ahora = new Date();
    const mesActual = ahora.getMonth(); // 0-based
    const anioActual = ahora.getFullYear();
    
    // Limpiar opciones
    selectMes.innerHTML = '<option value="">Seleccionar mes...</option>';
    selectAnio.innerHTML = '<option value="">Seleccionar año...</option>';
    
    // Cargar todos los meses (1-12)
    for (let mes = 1; mes <= 12; mes++) {
        const optionMes = document.createElement('option');
        optionMes.value = mes; // 1-based para la BD
        optionMes.textContent = meses[mes - 1]; // 0-based para el array
        selectMes.appendChild(optionMes);
    }
    
    // Cargar años con rango amplio (año anterior hasta 2 años adelante)
    for (let anio = anioActual - 1; anio <= anioActual + 2; anio++) {
        const optionAnio = document.createElement('option');
        optionAnio.value = anio;
        optionAnio.textContent = anio;
        selectAnio.appendChild(optionAnio);
    }
    
    // Establecer valores por defecto: mes siguiente y año actual
    const mesSiguiente = mesActual + 2; // +1 para siguiente, +1 para base-1
    if (mesSiguiente > 12) {
        selectMes.value = mesSiguiente - 12; // Enero del siguiente año
        selectAnio.value = anioActual + 1;
    } else {
        selectMes.value = mesSiguiente;
        selectAnio.value = anioActual;
    }
}

/**
 * Agregar nueva card de novedad
 */
function agregarNuevaNovedadCard() {
    window.contadorNovedades++;
    
    const cardId = `novedad-card-${window.contadorNovedades}`;
    const container = document.getElementById('novedades-cards-container');
    
    const cardHtml = `
        <div class="card mb-3 novedad-card" id="${cardId}" data-novedad-id="${window.contadorNovedades}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-file-alt me-2"></i>
                    Novedad #${window.contadorNovedades}
                </h6>
                ${window.contadorNovedades > 1 ? `
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarNovedadCard('${cardId}')">
                    <i class="fas fa-times"></i>
                </button>
                ` : ''}
            </div>
            <div class="card-body">
                <!-- Tipo de novedad -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="tipo_novedad_${window.contadorNovedades}" class="form-label">
                            Tipo de novedad <span class="required">*</span>
                        </label>
                        <select class="form-select tipo-novedad-multiple" id="tipo_novedad_${window.contadorNovedades}" 
                                name="tipo_novedad_${window.contadorNovedades}" data-card-id="${cardId}" required>
                            <option value="">Seleccione tipo de novedad...</option>
                        </select>
                        <div class="invalid-feedback">
                            El tipo de novedad es obligatorio
                        </div>
                    </div>
                </div>
                
                <!-- Configuración específica (se genera dinámicamente) -->
                <div id="config-${window.contadorNovedades}" class="configuracion-novedad-multiple">
                    <!-- La configuración específica aparecerá aquí -->
                </div>
                
                <!-- Observaciones específicas de esta novedad -->
                <div class="row">
                    <div class="col-12">
                        <label for="observaciones_${window.contadorNovedades}" class="form-label">
                            Observaciones específicas
                        </label>
                        <textarea class="form-control" id="observaciones_${window.contadorNovedades}" 
                                  name="observaciones_${window.contadorNovedades}" rows="2" 
                                  placeholder="Observaciones específicas para esta novedad..."></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', cardHtml);
    
    // Cargar tipos de novedad en el nuevo select
    cargarTiposNovedadEnSelect(`tipo_novedad_${window.contadorNovedades}`);
    
    // Agregar event listener para el cambio de tipo
    const selectTipoNovedad = document.getElementById(`tipo_novedad_${window.contadorNovedades}`);
    if (selectTipoNovedad) {
        selectTipoNovedad.addEventListener('change', function() {
            onTipoNovedadChangeMultiple(this, window.contadorNovedades);
        });
    }
    
    console.log(`✅ Agregada card de novedad #${window.contadorNovedades}`);
}

/**
 * Eliminar card de novedad
 */
function eliminarNovedadCard(cardId) {
    const card = document.getElementById(cardId);
    if (card) {
        // Remover del array de datos
        const novedadId = parseInt(card.getAttribute('data-novedad-id'));
        window.novedadesData = window.novedadesData.filter(n => n.id !== novedadId);
        
        // Remover del DOM
        card.remove();
        
        console.log(`🗑️ Eliminada card ${cardId}`);
    }
}

/**
 * Manejar cambio de tipo de novedad en modo múltiple
 */
function onTipoNovedadChangeMultiple(selectElement, novedadId) {
    const tipoSeleccionado = parseInt(selectElement.value);
    const configContainer = document.getElementById(`config-${novedadId}`);
    
    console.log('🔍 Cambio tipo novedad múltiple:', {
        novedadId,
        tipoSeleccionado,
        configContainer: !!configContainer
    });
    
    if (!configContainer) return;
    
    // Limpiar configuración anterior
    configContainer.innerHTML = '';
    
    if (tipoSeleccionado && tiposNovedadConfigActualizada[tipoSeleccionado]) {
        // Generar configuración específica
        const configHtml = generarConfiguracionEspecificaMultiple(tipoSeleccionado, novedadId);
        configContainer.innerHTML = configHtml;
        
        // Aplicar configuraciones específicas según el tipo
        setTimeout(() => {
            aplicarConfiguracionesEspecificasMultiple(tipoSeleccionado, novedadId);
        }, 100);
    }
}

/**
 * Generar HTML de configuración específica para modo múltiple
 */
function generarConfiguracionEspecificaMultiple(tipoNovedad, novedadId) {
    // Aquí generamos el HTML específico según el tipo de novedad
    // Por simplicidad, vamos a reutilizar las configuraciones existentes pero con IDs únicos
    
    const config = tiposNovedadConfigActualizada[tipoNovedad];
    if (!config) return '';
    
    // Mapear cada tipo de novedad a su HTML específico
    switch (tipoNovedad) {
        case 1: // Cambio de sucursal
            return generarConfigCambioSucursalMultiple(novedadId);
        case 2: // Nuevo puesto
            return generarConfigNuevoPuestoMultiple(novedadId);
        case 3: // Nuevo salario
            return generarConfigNuevoSalarioMultiple(novedadId);
        case 4: // Ajuste premios
            return generarConfigAjustePremiosMultiple(novedadId);
        case 5: // Horas extras
            return generarConfigHorasExtrasMultiple(novedadId);
        case 6: // Horas adicionales
            return generarConfigHorasAdicionalesMultiple(novedadId);
        case 7: // Permisos
            return generarConfigPermisosMultiple(novedadId);
        case 8: // Cortes
            return generarConfigCortesMultiple(novedadId);
        case 9: // Producción 25%
        case 10: // Producción 50%
        case 11: // Producción 100%
            return generarConfigProduccionMultiple(tipoNovedad, novedadId);
        case 12: // Plus de caja
        case 32: // Plus de caja (ID real BD)
            return generarConfigPlusCajaMultiple(novedadId);
        case 13: // Plus de Sub-Encargada
        case 33: // Plus de Sub-Encargada (ID real BD)
            return generarConfigPlusSubEncargadaMultiple(novedadId);
        case 14: // Plus de Encargada
        case 34: // Plus de Encargada (ID real BD)
            return generarConfigPlusEncargadaMultiple(novedadId);
        case 15: // Premio Local
        case 35: // Premio Local (ID real BD)
            return generarConfigPremioLocalMultiple(novedadId);
        case 16: // Comisión Individual
        case 36: // Comisión Individual (ID real BD)
            return generarConfigComisionIndividualMultiple(novedadId);
        case 17: // Comisión sobre Local
        case 37: // Comisión sobre Local (ID real BD)
            return generarConfigComisionLocalMultiple(novedadId);
        case 18: // Premios - Ajuste General
        case 38: // Premios - Ajuste General (ID real BD)
            return generarConfigPremiosAjusteGeneralMultiple(novedadId);
        default:
            return `<div class="alert alert-warning">Tipo de novedad ${tipoNovedad} no configurado para modo múltiple</div>`;
    }
}

/**
 * Cargar tipos de novedad en un select específico
 */
async function cargarTiposNovedadEnSelect(selectId) {
    try {
        const tipos = await NovedadesApp.request('get_tipos_novedad');
        const select = document.getElementById(selectId);
        
        if (select && tipos) {
            select.innerHTML = '<option value="">Seleccione tipo de novedad...</option>';
            tipos.forEach(tipo => {
                select.innerHTML += `<option value="${tipo.id}">${tipo.descripcion}</option>`;
            });
        }
    } catch (error) {
        console.error('Error cargando tipos de novedad:', error);
    }
}

// Funciones de generación de configuración específica (ejemplos básicos)
function generarConfigCambioSucursalMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-6">
                <label for="nueva_sucursal_${novedadId}" class="form-label">
                    Nueva Sucursal <span class="required">*</span>
                </label>
                <select class="form-select" id="nueva_sucursal_${novedadId}" name="nueva_sucursal_${novedadId}" required>
                    <option value="">Seleccione nueva sucursal...</option>
                </select>
                <div class="invalid-feedback">La nueva sucursal es obligatoria</div>
            </div>
        </div>
    `;
}

function generarConfigNuevoSalarioMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-6">
                <label for="importe_salario_${novedadId}" class="form-label">
                    Nuevo Salario Neto <span class="required">*</span>
                </label>
                <input type="number" class="form-control" id="importe_salario_${novedadId}" 
                       name="importe_salario_${novedadId}" step="0.01" min="0" required>
                <div class="invalid-feedback">El importe es obligatorio</div>
            </div>
        </div>
    `;
}

function generarConfigPermisosMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-6">
                <label for="fecha_permiso_${novedadId}" class="form-label">
                    Fecha del Permiso <span class="required">*</span>
                </label>
                <input type="date" class="form-control" id="fecha_permiso_${novedadId}" 
                       name="fecha_permiso_${novedadId}" required>
                <div class="invalid-feedback">La fecha del permiso es obligatoria</div>
            </div>
            <div class="col-md-6">
                <label for="compensa_${novedadId}" class="form-label">
                    ¿Compensa? <span class="required">*</span>
                </label>
                <select class="form-select" id="compensa_${novedadId}" name="compensa_${novedadId}" required>
                    <option value="">Seleccione...</option>
                    <option value="1">SI</option>
                    <option value="0">NO</option>
                </select>
                <div class="invalid-feedback">Debe indicar si compensa</div>
            </div>
        </div>
    `;
}

// Funciones auxiliares para otros tipos (completadas)
function generarConfigNuevoPuestoMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-6">
                <label for="nuevo_puesto_${novedadId}" class="form-label">
                    Nuevo Puesto <span class="required">*</span>
                </label>
                <select class="form-select" id="nuevo_puesto_${novedadId}" name="nuevo_puesto_${novedadId}" required>
                    <option value="">Seleccione nuevo puesto...</option>
                </select>
                <div class="invalid-feedback">El nuevo puesto es obligatorio</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tipo de Cambio <span class="required">*</span></label>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_nuevo_puesto_${novedadId}" 
                                   id="tipo_puesto_permanente_${novedadId}" value="permanente" checked>
                            <label class="form-check-label" for="tipo_puesto_permanente_${novedadId}">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Permanente</strong>
                                <br><small class="text-muted">Cambio definitivo de puesto</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_nuevo_puesto_${novedadId}" 
                                   id="tipo_puesto_temporario_${novedadId}" value="temporario">
                            <label class="form-check-label" for="tipo_puesto_temporario_${novedadId}">
                                <i class="fas fa-clock text-warning me-2"></i>
                                <strong>Temporario</strong>
                                <br><small class="text-muted">Cambio temporal con fecha de fin</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fecha de fin (solo para temporario) -->
        <div class="row mt-3" id="campo_fecha_fin_${novedadId}" style="display: none;">
            <div class="col-md-6">
                <label for="fecha_vigencia_hasta_puesto_${novedadId}" class="form-label">
                    Fecha de fin <span class="required">*</span>
                </label>
                <input type="date" class="form-control" id="fecha_vigencia_hasta_puesto_${novedadId}" name="fecha_vigencia_hasta_${novedadId}">
                <div class="invalid-feedback">
                    La fecha de fin es obligatoria para cambios temporarios
                </div>
            </div>
        </div>
    `;
}

function generarConfigAjustePremiosMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-6">
                <label for="importe_premios_${novedadId}" class="form-label">
                    Importe <span class="required">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="importe_premios_${novedadId}" 
                           name="importe_premios_${novedadId}" step="0.01" min="0" required>
                </div>
                <div class="invalid-feedback">El importe es obligatorio</div>
            </div>
        </div>
    `;
}

function generarConfigHorasExtrasMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-6">
                <label for="cantidad_horas_extras_${novedadId}" class="form-label">
                    Cantidad de horas <span class="required">*</span>
                </label>
                <div class="input-group">
                    <input type="number" class="form-control" id="cantidad_horas_extras_${novedadId}" 
                           name="cantidad_horas_extras_${novedadId}" min="1" required>
                    <span class="input-group-text">hs</span>
                </div>
                <div class="invalid-feedback">La cantidad de horas es obligatoria</div>
            </div>
        </div>
    `;
}

function generarConfigHorasAdicionalesMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-6">
                <label for="cantidad_horas_adicionales_${novedadId}" class="form-label">
                    Cantidad de horas <span class="required">*</span>
                </label>
                <div class="input-group">
                    <input type="number" class="form-control" id="cantidad_horas_adicionales_${novedadId}" 
                           name="cantidad_horas_adicionales_${novedadId}" min="1" required>
                    <span class="input-group-text">hs</span>
                </div>
                <div class="invalid-feedback">La cantidad de horas es obligatoria</div>
            </div>
        </div>
    `;
}

function generarConfigCortesMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-6">
                <label for="cantidad_cortes_${novedadId}" class="form-label">
                    Cantidad de cortes <span class="required">*</span>
                </label>
                <input type="number" class="form-control" id="cantidad_cortes_${novedadId}" 
                       name="cantidad_cortes_${novedadId}" min="1" required>
                <div class="invalid-feedback">La cantidad de cortes es obligatoria</div>
            </div>
        </div>
    `;
}

function generarConfigProduccionMultiple(tipoNovedad, novedadId) {
    const porcentaje = tipoNovedad === 9 ? '25' : tipoNovedad === 10 ? '50' : '100';
    return `
        <div class="row">
            <div class="col-md-6">
                <label for="cantidad_unidades_${porcentaje}_${novedadId}" class="form-label">
                    Cantidad de unidades producidas <span class="required">*</span>
                </label>
                <div class="input-group">
                    <input type="number" class="form-control" id="cantidad_unidades_${porcentaje}_${novedadId}" 
                           name="cantidad_unidades_${porcentaje}_${novedadId}" min="1" required>
                    <span class="input-group-text">un.</span>
                </div>
                <div class="invalid-feedback">La cantidad de unidades es obligatoria</div>
                <small class="form-text text-muted">Producción con ${porcentaje}% de eficiencia</small>
            </div>
        </div>
    `;
}

// Funciones para tipos más complejos
function generarConfigPlusCajaMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-4">
                <label for="tipo_plus_caja_${novedadId}" class="form-label">
                    Tipo de Plus <span class="required">*</span>
                </label>
                <select class="form-select" id="tipo_plus_caja_${novedadId}" name="tipo_plus_caja_${novedadId}" required>
                    <option value="">Seleccione tipo...</option>
                    <option value="premio">Premio (requiere importe)</option>
                    <option value="recibo">Recibo (opcional importe)</option>
                </select>
                <div class="invalid-feedback">El tipo de plus es obligatorio</div>
            </div>
            <div class="col-md-4" id="campo_importe_plus_caja_${novedadId}">
                <label for="importe_plus_caja_${novedadId}" class="form-label">
                    Importe <span class="required">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="importe_plus_caja_${novedadId}" 
                           name="importe_plus_caja_${novedadId}" step="0.01" min="0">
                </div>
                <div class="invalid-feedback">El importe es obligatorio para premios</div>
            </div>
        </div>
    `;
}

function generarConfigPlusSubEncargadaMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-4">
                <label for="tiene_importe_sub_${novedadId}" class="form-label">
                    ¿Tiene importe específico? <span class="required">*</span>
                </label>
                <select class="form-select" id="tiene_importe_sub_${novedadId}" name="tiene_importe_sub_${novedadId}" required>
                    <option value="">Seleccione...</option>
                    <option value="1">Sí (especificar importe)</option>
                    <option value="0">No</option>
                </select>
                <div class="invalid-feedback">Debe seleccionar una opción</div>
            </div>
            <div class="col-md-4" id="campo_importe_sub_encargada_${novedadId}" style="display: none;">
                <label for="importe_sub_encargada_${novedadId}" class="form-label">
                    Importe Específico <span class="required">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="importe_sub_encargada_${novedadId}" 
                           name="importe_sub_encargada_${novedadId}" step="0.01" min="0">
                </div>
                <div class="invalid-feedback">El importe es obligatorio</div>
            </div>
        </div>
    `;
}

function generarConfigPlusEncargadaMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-4">
                <label for="tiene_importe_enc_${novedadId}" class="form-label">
                    ¿Tiene importe específico? <span class="required">*</span>
                </label>
                <select class="form-select" id="tiene_importe_enc_${novedadId}" name="tiene_importe_enc_${novedadId}" required>
                    <option value="">Seleccione...</option>
                    <option value="1">Sí (especificar importe)</option>
                    <option value="0">No</option>
                </select>
                <div class="invalid-feedback">Debe seleccionar una opción</div>
            </div>
            <div class="col-md-4" id="campo_importe_encargada_${novedadId}" style="display: none;">
                <label for="importe_encargada_${novedadId}" class="form-label">
                    Importe Específico <span class="required">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="importe_encargada_${novedadId}" 
                           name="importe_encargada_${novedadId}" step="0.01" min="0">
                </div>
                <div class="invalid-feedback">El importe es obligatorio</div>
            </div>
        </div>
    `;
}

function generarConfigPremioLocalMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-4">
                <label for="importe_premio_local_${novedadId}" class="form-label">
                    Importe <span class="required">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="importe_premio_local_${novedadId}" 
                           name="importe_premio_local_${novedadId}" step="0.01" min="0" required>
                </div>
                <div class="invalid-feedback">El importe es obligatorio</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Aplicable a <span class="required">*</span></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="aplica_vendedora_${novedadId}" 
                           name="aplica_vendedora_${novedadId}" value="1">
                    <label class="form-check-label" for="aplica_vendedora_${novedadId}">
                        Vendedora
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="aplica_sub_encargada_${novedadId}" 
                           name="aplica_sub_encargada_${novedadId}" value="1">
                    <label class="form-check-label" for="aplica_sub_encargada_${novedadId}">
                        Sub-Encargada
                    </label>
                </div>
                <div class="invalid-feedback">Debe seleccionar al menos una aplicación</div>
            </div>
        </div>
    `;
}

function generarConfigComisionIndividualMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-6">
                <label for="porcentaje_individual_${novedadId}" class="form-label">
                    Porcentaje de Comisión <span class="required">*</span>
                </label>
                <div class="input-group">
                    <input type="number" class="form-control" id="porcentaje_individual_${novedadId}" 
                           name="porcentaje_individual_${novedadId}" step="0.01" min="0.01" max="99.99" required>
                    <span class="input-group-text">%</span>
                </div>
                <div class="invalid-feedback">El porcentaje es obligatorio</div>
                <small class="form-text text-muted">Ejemplo: 2.50 para 2.50%</small>
            </div>
        </div>
    `;
}

function generarConfigComisionLocalMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-4">
                <label for="tiene_tope_local_${novedadId}" class="form-label">
                    Estructura de Comisión <span class="required">*</span>
                </label>
                <select class="form-select" id="tiene_tope_local_${novedadId}" name="tiene_tope_local_${novedadId}" required>
                    <option value="">Seleccione...</option>
                    <option value="0">Sin tope (porcentaje único)</option>
                    <option value="1">Con tope (dos porcentajes)</option>
                </select>
                <div class="invalid-feedback">Debe seleccionar la estructura</div>
            </div>
        </div>
        <div class="row mt-3" id="campos_sin_tope_local_${novedadId}" style="display: none;">
            <div class="col-md-4">
                <label for="porcentaje_unico_local_${novedadId}" class="form-label">
                    Porcentaje Único <span class="required">*</span>
                </label>
                <div class="input-group">
                    <input type="number" class="form-control" id="porcentaje_unico_local_${novedadId}" 
                           name="porcentaje_unico_local_${novedadId}" step="0.01" min="0.01" max="99.99">
                    <span class="input-group-text">%</span>
                </div>
                <div class="invalid-feedback">El porcentaje es obligatorio</div>
            </div>
        </div>
        <div class="row mt-3" id="campos_con_tope_local_${novedadId}" style="display: none;">
            <div class="col-md-3">
                <label for="porcentaje_1_local_${novedadId}" class="form-label">
                    Primer Porcentaje <span class="required">*</span>
                </label>
                <div class="input-group">
                    <input type="number" class="form-control" id="porcentaje_1_local_${novedadId}" 
                           name="porcentaje_1_local_${novedadId}" step="0.01" min="0.01" max="99.99">
                    <span class="input-group-text">%</span>
                </div>
                <div class="invalid-feedback">El porcentaje es obligatorio</div>
            </div>
            <div class="col-md-3">
                <label for="porcentaje_2_local_${novedadId}" class="form-label">
                    Segundo Porcentaje <span class="required">*</span>
                </label>
                <div class="input-group">
                    <input type="number" class="form-control" id="porcentaje_2_local_${novedadId}" 
                           name="porcentaje_2_local_${novedadId}" step="0.01" min="0.01" max="99.99">
                    <span class="input-group-text">%</span>
                </div>
                <div class="invalid-feedback">El porcentaje es obligatorio</div>
            </div>
        </div>
    `;
}

function generarConfigPremiosAjusteGeneralMultiple(novedadId) {
    return `
        <div class="row">
            <div class="col-md-6">
                <label for="importe_ajuste_general_${novedadId}" class="form-label">
                    Importe del Ajuste <span class="required">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="importe_ajuste_general_${novedadId}" 
                           name="importe_ajuste_general_${novedadId}" step="0.01" min="0" required>
                </div>
                <div class="invalid-feedback">El importe es obligatorio</div>
            </div>
        </div>
    `;
}

/**
 * Aplicar configuraciones específicas después de generar HTML
 */
function aplicarConfiguracionesEspecificasMultiple(tipoNovedad, novedadId) {
    // Aquí aplicamos lógica específica como cargar opciones, configurar fechas, etc.
    switch (tipoNovedad) {
        case 1: // Cambio de sucursal
            cargarSucursalesEnSelectMultiple(`nueva_sucursal_${novedadId}`);
            break;
        case 2: // Nuevo puesto
            cargarPuestosEnSelectMultiple(`nuevo_puesto_${novedadId}`);
            configurarTipoPuestoMultiple(novedadId);
            break;
        case 12: // Plus de caja
        case 32:
            configurarPlusCajaMultiple(novedadId);
            break;
        case 13: // Plus Sub-Encargada
        case 33:
            configurarPlusSubEncargadaMultiple(novedadId);
            break;
        case 14: // Plus Encargada
        case 34:
            configurarPlusEncargadaMultiple(novedadId);
            break;
        case 17: // Comisión sobre Local
        case 37:
            configurarComisionLocalMultiple(novedadId);
            break;
    }
}

/**
 * Cargar sucursales en select múltiple
 */
async function cargarSucursalesEnSelectMultiple(selectId) {
    try {
        const sucursales = await NovedadesApp.request('get_sucursales_con_casa_central');
        const select = document.getElementById(selectId);
        
        if (select && sucursales) {
            select.innerHTML = '<option value="">Seleccione nueva sucursal...</option>';
            sucursales.forEach(sucursal => {
                select.innerHTML += `<option value="${sucursal.numero}">${sucursal.descripcion}</option>`;
            });
            console.log(`✅ Sucursales cargadas en ${selectId}:`, sucursales.length);
        }
    } catch (error) {
        console.error(`Error cargando sucursales en ${selectId}:`, error);
    }
}

/**
 * Cargar puestos en select múltiple
 */
async function cargarPuestosEnSelectMultiple(selectId) {
    try {
        const puestos = await NovedadesApp.request('get_puestos');
        const select = document.getElementById(selectId);
        
        if (select && puestos) {
            select.innerHTML = '<option value="">Seleccione nuevo puesto...</option>';
            puestos.forEach(puesto => {
                select.innerHTML += `<option value="${puesto.nombre_puesto}">${puesto.nombre_puesto}</option>`;
            });
            console.log(`✅ Puestos cargados en ${selectId}:`, puestos.length);
        }
    } catch (error) {
        console.error(`Error cargando puestos en ${selectId}:`, error);
    }
}

/**
 * Configurar tipo de puesto (permanente/temporario)
 */
function configurarTipoPuestoMultiple(novedadId) {
    const radioTemporario = document.getElementById(`tipo_puesto_temporario_${novedadId}`);
    const radioPermanente = document.getElementById(`tipo_puesto_permanente_${novedadId}`);
    const campoFechaFin = document.getElementById(`campo_fecha_fin_${novedadId}`);
    
    if (radioTemporario && radioPermanente && campoFechaFin) {
        // Event listener para mostrar/ocultar fecha fin
        [radioTemporario, radioPermanente].forEach(radio => {
            radio.addEventListener('change', function() {
                if (radioTemporario.checked) {
                    campoFechaFin.style.display = 'block';
                    const fechaInput = document.getElementById(`fecha_vigencia_hasta_puesto_${novedadId}`);
                    if (fechaInput) fechaInput.required = true;
                } else {
                    campoFechaFin.style.display = 'none';
                    const fechaInput = document.getElementById(`fecha_vigencia_hasta_puesto_${novedadId}`);
                    if (fechaInput) {
                        fechaInput.required = false;
                        fechaInput.value = '';
                    }
                }
            });
        });
    }
}

/**
 * Configurar Plus de Caja en modo múltiple
 */
function configurarPlusCajaMultiple(novedadId) {
    const tipoSelect = document.getElementById(`tipo_plus_caja_${novedadId}`);
    const campoImporte = document.getElementById(`campo_importe_plus_caja_${novedadId}`);
    const inputImporte = document.getElementById(`importe_plus_caja_${novedadId}`);
    
    if (tipoSelect && campoImporte && inputImporte) {
        tipoSelect.addEventListener('change', function() {
            if (this.value === 'premio') {
                campoImporte.style.display = 'block';
                inputImporte.required = true;
                const label = campoImporte.querySelector('label');
                if (label) label.innerHTML = 'Importe <span class="required">*</span>';
            } else if (this.value === 'recibo') {
                campoImporte.style.display = 'block';
                inputImporte.required = false;
                const label = campoImporte.querySelector('label');
                if (label) label.innerHTML = 'Importe (opcional)';
            } else {
                campoImporte.style.display = 'none';
                inputImporte.required = false;
                inputImporte.value = '';
            }
        });
    }
}

/**
 * Configurar Plus de Sub-Encargada en modo múltiple
 */
/**
 * Configurar Comisión sobre Local en modo múltiple
 */
function configurarComisionLocalMultiple(novedadId) {
    const tieneTopeSelect = document.getElementById(`tiene_tope_local_${novedadId}`);
    const campoSinTope = document.getElementById(`campos_sin_tope_local_${novedadId}`);
    const campoConTope = document.getElementById(`campos_con_tope_local_${novedadId}`);
    
    if (tieneTopeSelect && campoSinTope && campoConTope) {
        tieneTopeSelect.addEventListener('change', function() {
            if (this.value === '0') {
                // Sin tope
                campoSinTope.style.display = 'block';
                campoConTope.style.display = 'none';
                const inputUnico = document.getElementById(`porcentaje_unico_local_${novedadId}`);
                const input1 = document.getElementById(`porcentaje_1_local_${novedadId}`);
                const input2 = document.getElementById(`porcentaje_2_local_${novedadId}`);
                
                if (inputUnico) inputUnico.required = true;
                if (input1) input1.required = false;
                if (input2) input2.required = false;
            } else if (this.value === '1') {
                // Con tope
                campoSinTope.style.display = 'none';
                campoConTope.style.display = 'block';
                const inputUnico = document.getElementById(`porcentaje_unico_local_${novedadId}`);
                const input1 = document.getElementById(`porcentaje_1_local_${novedadId}`);
                const input2 = document.getElementById(`porcentaje_2_local_${novedadId}`);
                
                if (inputUnico) inputUnico.required = false;
                if (input1) input1.required = true;
                if (input2) input2.required = true;
            } else {
                // No seleccionado
                campoSinTope.style.display = 'none';
                campoConTope.style.display = 'none';
            }
        });
    }
}

/**
 * Configuraciones específicas para tipos complejos en modo múltiple
 */
function configurarPlusCajaMultiple(novedadId) {
    const selectTipo = document.getElementById(`tipo_plus_caja_${novedadId}`);
    const campoImporte = document.getElementById(`campo_importe_plus_caja_${novedadId}`);
    
    if (selectTipo && campoImporte) {
        selectTipo.addEventListener('change', function() {
            const inputImporte = document.getElementById(`importe_plus_caja_${novedadId}`);
            if (this.value === 'premio') {
                campoImporte.style.display = 'block';
                if (inputImporte) inputImporte.setAttribute('required', 'required');
            } else {
                campoImporte.style.display = 'none';
                if (inputImporte) inputImporte.removeAttribute('required');
            }
        });
    }
}

function configurarPlusSubEncargadaMultiple(novedadId) {
    const selectTieneImporte = document.getElementById(`tiene_importe_sub_${novedadId}`);
    const campoImporte = document.getElementById(`campo_importe_sub_encargada_${novedadId}`);
    
    if (selectTieneImporte && campoImporte) {
        selectTieneImporte.addEventListener('change', function() {
            const inputImporte = document.getElementById(`importe_sub_encargada_${novedadId}`);
            if (this.value === '1') {
                campoImporte.style.display = 'block';
                if (inputImporte) inputImporte.setAttribute('required', 'required');
            } else {
                campoImporte.style.display = 'none';
                if (inputImporte) inputImporte.removeAttribute('required');
            }
        });
    }
}

function configurarPlusEncargadaMultiple(novedadId) {
    const selectTieneImporte = document.getElementById(`tiene_importe_enc_${novedadId}`);
    const campoImporte = document.getElementById(`campo_importe_encargada_${novedadId}`);
    
    if (selectTieneImporte && campoImporte) {
        selectTieneImporte.addEventListener('change', function() {
            const inputImporte = document.getElementById(`importe_encargada_${novedadId}`);
            if (this.value === '1') {
                campoImporte.style.display = 'block';
                if (inputImporte) inputImporte.setAttribute('required', 'required');
            } else {
                campoImporte.style.display = 'none';
                if (inputImporte) inputImporte.removeAttribute('required');
            }
        });
    }
}

function configurarComisionLocalMultiple(novedadId) {
    const selectTieneTope = document.getElementById(`tiene_tope_local_${novedadId}`);
    const camposSinTope = document.getElementById(`campos_sin_tope_local_${novedadId}`);
    const camposConTope = document.getElementById(`campos_con_tope_local_${novedadId}`);
    
    if (selectTieneTope) {
        selectTieneTope.addEventListener('change', function() {
            if (this.value === '0') { // Sin tope
                if (camposSinTope) camposSinTope.style.display = 'block';
                if (camposConTope) camposConTope.style.display = 'none';
            } else if (this.value === '1') { // Con tope
                if (camposSinTope) camposSinTope.style.display = 'none';
                if (camposConTope) camposConTope.style.display = 'block';
            } else {
                if (camposSinTope) camposSinTope.style.display = 'none';
                if (camposConTope) camposConTope.style.display = 'none';
            }
        });
    }
}

function configurarTipoPuestoMultiple(novedadId) {
    const radioPermanente = document.getElementById(`tipo_puesto_permanente_${novedadId}`);
    const radioTemporario = document.getElementById(`tipo_puesto_temporario_${novedadId}`);
    const campoFechaFin = document.getElementById(`campo_fecha_fin_${novedadId}`);
    const fechaHastaCampo = document.getElementById(`fecha_vigencia_hasta_puesto_${novedadId}`);
    
    function manejarCambioTipoPuesto() {
        if (radioTemporario && radioTemporario.checked) {
            // Mostrar campo de fecha de fin para temporario
            if (campoFechaFin) {
                campoFechaFin.style.display = 'block';
            }
            if (fechaHastaCampo) {
                fechaHastaCampo.setAttribute('required', 'required');
            }
        } else {
            // Ocultar campo de fecha de fin para permanente
            if (campoFechaFin) {
                campoFechaFin.style.display = 'none';
            }
            if (fechaHastaCampo) {
                fechaHastaCampo.removeAttribute('required');
                fechaHastaCampo.value = '';
            }
        }
    }
    
    // Agregar event listeners
    if (radioPermanente) {
        radioPermanente.addEventListener('change', manejarCambioTipoPuesto);
    }
    if (radioTemporario) {
        radioTemporario.addEventListener('change', manejarCambioTipoPuesto);
    }
    
    // Configurar estado inicial (permanente por defecto)
    if (radioPermanente) {
        radioPermanente.checked = true;
        manejarCambioTipoPuesto();
    }
}

/**
 * Cargar puestos en select del modo múltiple
 */
async function cargarPuestosEnSelectMultiple(selectId) {
    try {
        const puestos = await NovedadesApp.request('get_puestos');
        const select = document.getElementById(selectId);
        
        if (select && puestos) {
            select.innerHTML = '<option value="">Seleccione nuevo puesto...</option>';
            puestos.forEach(puesto => {
                // Usar nombre_puesto como value para mantener consistencia con modo simple
                select.innerHTML += `<option value="${puesto.nombre_puesto}">${puesto.nombre_puesto}</option>`;
            });
            console.log(`✅ Puestos cargados en ${selectId}:`, puestos.length);
        }
    } catch (error) {
        console.error('Error cargando puestos:', error);
    }
}

/**
 * Cargar sucursales en select del modo múltiple
 */
async function cargarSucursalesEnSelectMultiple(selectId) {
    try {
        const sucursales = await NovedadesApp.request('get_sucursales_con_casa_central');
        const select = document.getElementById(selectId);
        
        if (select && sucursales) {
            select.innerHTML = '<option value="">Seleccione nueva sucursal...</option>';
            sucursales.forEach(sucursal => {
                select.innerHTML += `<option value="${sucursal.numero}">${sucursal.descripcion}</option>`;
            });
            console.log(`✅ Sucursales cargadas en ${selectId}:`, sucursales.length);
        }
    } catch (error) {
        console.error(`Error cargando sucursales en ${selectId}:`, error);
    }
}

/**
 * Modificar función de envío para manejar modo múltiple
 */
async function enviarFormularioMultiple(event) {
    event.preventDefault();
    
    console.log('🚀 Iniciando envío del formulario en modo:', window.modoActual);
    
    if (window.modoActual === 'unica') {
        // Usar la función original
        return await enviarFormulario(event);
    } else if (window.modoActual === 'multiple') {
        return await enviarFormularioModoMultiple();
    }
}

/**
 * Enviar formulario en modo múltiple
 */
async function enviarFormularioModoMultiple() {
    console.log('📚 Enviando formulario en modo múltiple');
    
    // Validar datos globales
    if (!validarDatosGlobales()) {
        NovedadesApp.mostrarError('Complete los datos globales obligatorios');
        return;
    }
    
    // Recopilar y validar todas las novedades
    const novedades = recopilarNovedadesMultiples();
    
    if (novedades.length === 0) {
        NovedadesApp.mostrarError('Debe agregar al menos una novedad');
        return;
    }
    
    // Mostrar loading
    const btnGuardar = document.getElementById('btn-guardar');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    
    try {
        // Enviar cada novedad
        for (const novedad of novedades) {
            console.log('🚀 Enviando novedad con período:', {
                tipo_novedad: novedad.tipo_novedad,
                periodo_mes: novedad.periodo_mes,
                periodo_anio: novedad.periodo_anio,
                legajo: novedad.legajo
            });
            await NovedadesApp.request('crear_novedad', novedad, 'POST');
        }
        
        // Mostrar modal de confirmación
        const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
        modal.show();
        
        // Limpiar formulario
        limpiarFormularioMultiple();
        
    } catch (error) {
        console.error('Error al guardar novedades:', error);
        NovedadesApp.mostrarError('Error al guardar las novedades');
    } finally {
        // Restaurar botón
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = textoOriginal;
    }
}

/**
 * Validar datos globales del modo múltiple
 */
function validarDatosGlobales() {
    const campos = ['empleado-select', 'periodo_mes_global', 'periodo_anio_global', 'fecha_vigencia_global'];
    let valido = true;
    
    campos.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (!campo || !campo.value.trim()) {
            if (campo) campo.classList.add('is-invalid');
            valido = false;
        } else {
            campo.classList.remove('is-invalid');
            campo.classList.add('is-valid');
        }
    });
    
    return valido;
}

/**
 * Recopilar todas las novedades del modo múltiple
 */
function recopilarNovedadesMultiples() {
    const novedades = [];
    const cards = document.querySelectorAll('.novedad-card');
    
    // Datos globales
    const datosGlobales = {
        legajo: document.getElementById('legajo').value,
        nombre: window.NovedadesApp?.empleadoSeleccionado?.nombre || '',
        apellido: window.NovedadesApp?.empleadoSeleccionado?.apellido || '',
        periodo_mes: document.getElementById('periodo_mes_global').value,
        periodo_anio: document.getElementById('periodo_anio_global').value,
        fecha_vigencia: document.getElementById('fecha_vigencia_global').value
    };
    
    // Log para debugging del período
    console.log('🗓️ Datos globales recopilados:', {
        periodo_mes: datosGlobales.periodo_mes,
        periodo_anio: datosGlobales.periodo_anio,
        fecha_vigencia: datosGlobales.fecha_vigencia
    });
    
    cards.forEach(card => {
        const novedadId = card.getAttribute('data-novedad-id');
        const tipoNovedad = document.getElementById(`tipo_novedad_${novedadId}`)?.value;
        
        if (tipoNovedad) {
            const novedad = {
                ...datosGlobales,
                tipo_novedad: parseInt(tipoNovedad),
                observaciones: document.getElementById(`observaciones_${novedadId}`)?.value || ''
            };
            
            // Agregar campos específicos según el tipo
            agregarCamposEspecificosNovedad(novedad, tipoNovedad, novedadId);
            
            novedades.push(novedad);
        }
    });
    
    return novedades;
}

/**
 * Agregar campos específicos según el tipo de novedad
 */
function agregarCamposEspecificosNovedad(novedad, tipoNovedad, novedadId) {
    switch (parseInt(tipoNovedad)) {
        case 1: // Cambio de sucursal
            novedad.nueva_sucursal = document.getElementById(`nueva_sucursal_${novedadId}`)?.value;
            // También agregar campo compatible
            novedad.sucursal_nueva_id = novedad.nueva_sucursal;
            // Agregar sucursal actual para las observaciones (usar número, no descripción)
            const empleadoSeleccionado = window.NovedadesApp?.empleadoSeleccionado;
            if (empleadoSeleccionado && empleadoSeleccionado.sucursal_numero) {
                novedad.sucursal_actual = empleadoSeleccionado.sucursal_numero;
            }
            break;
        case 2: // Nuevo puesto
            novedad.puesto = document.getElementById(`nuevo_puesto_${novedadId}`)?.value;
            novedad.tipo_nuevo_puesto = document.querySelector(`input[name="tipo_nuevo_puesto_${novedadId}"]:checked`)?.value;
            if (novedad.tipo_nuevo_puesto === 'temporario') {
                novedad.fecha_vigencia_hasta = document.getElementById(`fecha_vigencia_hasta_puesto_${novedadId}`)?.value;
            }
            break;
        case 3: // Nuevo salario
            novedad.importe = document.getElementById(`importe_salario_${novedadId}`)?.value;
            break;
        case 4: // Ajuste premios
            novedad.importe = document.getElementById(`importe_premios_${novedadId}`)?.value;
            break;
        case 5: // Horas extras
            novedad.cantidad_horas = document.getElementById(`cantidad_horas_extras_${novedadId}`)?.value;
            break;
        case 6: // Horas adicionales
            novedad.cantidad_horas = document.getElementById(`cantidad_horas_adicionales_${novedadId}`)?.value;
            break;
        case 7: // Permisos
            novedad.fecha_permiso = document.getElementById(`fecha_permiso_${novedadId}`)?.value;
            const compensaValue = document.getElementById(`compensa_${novedadId}`)?.value;
            // Convertir igual que en modo simple
            novedad.compensa = compensaValue === '1';
            break;
        case 8: // Cortes
            novedad.cantidad_cortes = document.getElementById(`cantidad_cortes_${novedadId}`)?.value;
            break;
        case 9: // Producción 25%
            novedad.cantidad_unidades = document.getElementById(`cantidad_unidades_25_${novedadId}`)?.value;
            break;
        case 10: // Producción 50%
            novedad.cantidad_unidades = document.getElementById(`cantidad_unidades_50_${novedadId}`)?.value;
            break;
        case 11: // Producción 100%
            novedad.cantidad_unidades = document.getElementById(`cantidad_unidades_100_${novedadId}`)?.value;
            break;
        case 12: // Plus de caja
        case 32:
            novedad.tipo_plus_caja = document.getElementById(`tipo_plus_caja_${novedadId}`)?.value;
            const importePlusCaja = document.getElementById(`importe_plus_caja_${novedadId}`)?.value;
            if (importePlusCaja) {
                novedad.importe = importePlusCaja;
            }
            break;
        case 13: // Plus de Sub-Encargada
        case 33:
            const tieneImporteSub = document.getElementById(`tiene_importe_sub_${novedadId}`)?.value;
            // Normalizar el nombre del campo para que coincida con el backend
            novedad.tiene_importe = tieneImporteSub;
            // Solo agregar importe si tiene_importe es '1'
            if (tieneImporteSub === '1') {
                const importeSubEnc = document.getElementById(`importe_sub_encargada_${novedadId}`)?.value;
                if (importeSubEnc) {
                    novedad.importe = importeSubEnc;
                }
            }
            break;
        case 14: // Plus de Encargada
        case 34:
            const tieneImporteEnc = document.getElementById(`tiene_importe_enc_${novedadId}`)?.value;
            // Normalizar el nombre del campo para que coincida con el backend
            novedad.tiene_importe = tieneImporteEnc;
            // Solo agregar importe si tiene_importe es '1'
            if (tieneImporteEnc === '1') {
                const importeEnc = document.getElementById(`importe_encargada_${novedadId}`)?.value;
                if (importeEnc) {
                    novedad.importe = importeEnc;
                }
            }
            break;
        case 15: // Premio Local
        case 35:
            novedad.importe = document.getElementById(`importe_premio_local_${novedadId}`)?.value;
            novedad.aplica_vendedora = document.getElementById(`aplica_vendedora_${novedadId}`)?.checked ? 1 : 0;
            novedad.aplica_sub_encargada = document.getElementById(`aplica_sub_encargada_${novedadId}`)?.checked ? 1 : 0;
            break;
        case 16: // Comisión Individual
        case 36:
            novedad.porcentaje_individual = document.getElementById(`porcentaje_individual_${novedadId}`)?.value;
            break;
        case 17: // Comisión sobre Local
        case 37:
            const tieneTopeLocal = document.getElementById(`tiene_tope_local_${novedadId}`)?.value;
            // Normalizar el nombre del campo para que coincida con el backend
            novedad.tiene_tope = tieneTopeLocal;
            
            if (tieneTopeLocal === '0') {
                const porcentajeUnico = document.getElementById(`porcentaje_unico_local_${novedadId}`)?.value;
                // Normalizar el nombre del campo para que coincida con el backend
                novedad.porcentaje_unico = porcentajeUnico;
            } else if (tieneTopeLocal === '1') {
                const porcentaje1 = document.getElementById(`porcentaje_1_local_${novedadId}`)?.value;
                const porcentaje2 = document.getElementById(`porcentaje_2_local_${novedadId}`)?.value;
                // Normalizar los nombres de los campos para que coincidan con el backend
                novedad.porcentaje_1 = porcentaje1;
                novedad.porcentaje_2 = porcentaje2;
            }
            break;
        case 18: // Premios - Ajuste General
        case 38:
            novedad.importe = document.getElementById(`importe_ajuste_general_${novedadId}`)?.value;
            break;
        // Agregar más casos según necesidad
    }
}

/**
 * Limpiar formulario en modo múltiple
 */
function limpiarFormularioMultiple() {
    // Resetear variables globales
    window.contadorNovedades = 0;
    window.novedadesData = [];
    window.modoActual = null;
    
    // Ocultar indicador de modo
    const indicator = document.getElementById('modo-activo-indicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
    
    // Limpiar contenedor de cards
    document.getElementById('novedades-cards-container').innerHTML = '';
    
    // Mostrar sección de selección de modo
    document.getElementById('modo-carga-section').style.display = 'block';
    document.getElementById('form-novedad').style.display = 'none';
    
    // Limpiar formulario
    document.getElementById('form-novedad').reset();
    
    if (typeof $ !== 'undefined' && $('#empleado-select').length) {
        $('#empleado-select').val(null).trigger('change');
    }
    
    if (typeof NovedadesApp !== 'undefined') {
        NovedadesApp.empleadoSeleccionado = null;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializarModosCarga();
    
    // Modificar el event listener del formulario
    const form = document.getElementById('form-novedad');
    if (form) {
        form.removeEventListener('submit', enviarFormulario); // Remover el original si existe
        form.addEventListener('submit', enviarFormularioMultiple);
    }
});

// Hacer funciones disponibles globalmente
window.initializarModosCarga = initializarModosCarga;
window.seleccionarModoCarga = seleccionarModoCarga;
window.agregarNuevaNovedadCard = agregarNuevaNovedadCard;
window.eliminarNovedadCard = eliminarNovedadCard;
window.limpiarFormularioMultiple = limpiarFormularioMultiple;
window.enviarFormularioMultiple = enviarFormularioMultiple;