/**
 * JavaScript específico para el formulario de nueva novedad
 * Maneja los 11 tipos de novedad según especificación
 */

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
    12: { // Plus de caja
        config: 'config-plus-caja',
        campos: ['tipo_plus_caja', 'importe_plus_caja', 'fecha_vigencia_plus_caja'],
        validaciones: ['tipo_plus_caja', 'fecha_vigencia']
    },
    13: { // Plus de Sub-Encargada
        config: 'config-plus-sub-encargada',
        campos: ['tiene_importe_sub', 'fecha_vigencia_plus_sub'],
        validaciones: ['fecha_vigencia']
    },
    14: { // Plus de Encargada
        config: 'config-plus-encargada',
        campos: ['tiene_importe_enc', 'fecha_vigencia_plus_enc'],
        validaciones: ['fecha_vigencia']
    },
    15: { // Premio Local
        config: 'config-premio-local',
        campos: ['importe_premio_local', 'fecha_vigencia_premio_local', 'aplica_vendedora', 'aplica_sub_encargada'],
        validaciones: ['importe', 'fecha_vigencia']
    },
    16: { // Comisión Individual
        config: 'config-comision-individual',
        campos: ['tiene_tope_individual', 'fecha_vigencia_comision_individual'],
        validaciones: ['fecha_vigencia']
    },
    17: { // Comisión sobre Local
        config: 'config-comision-local',
        campos: ['tiene_tope_local', 'fecha_vigencia_comision_local'],
        validaciones: ['fecha_vigencia']
    },
    18: { // Premios - Ajuste General
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
                    
                    // Si es un campo de fecha de vigencia, configurarlo según el tipo de corte
                    if (campoId.includes('fecha_vigencia')) {
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

    if (tipoSeleccionado === 12) { // Plus de caja
        setTimeout(() => configurarCamposPlusCaja(), 100);
    } else if (tipoSeleccionado === 13) { // Plus Sub-Encargada
        setTimeout(() => configurarCamposPlusConImporte('sub'), 100);
    } else if (tipoSeleccionado === 14) { // Plus Encargada
        setTimeout(() => configurarCamposPlusConImporte('enc'), 100);
    } else if (tipoSeleccionado === 16) { // Comisión Individual
        setTimeout(() => configurarCamposComision('individual'), 100);
    } else if (tipoSeleccionado === 17) { // Comisión Local
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
            const importePremioLocal = parseFloat(document.getElementById('importe_premio_local').value);
            if (!importePremioLocal || importePremioLocal <= 0) {
                errores.push('El importe del premio es obligatorio');
                valido = false;
            }
            
            // Validar que al menos una aplicación esté seleccionada
            const aplicaVendedora = document.getElementById('aplica_vendedora').checked;
            const aplicaSubEncargada = document.getElementById('aplica_sub_encargada').checked;
            
            if (!aplicaVendedora && !aplicaSubEncargada) {
                errores.push('Debe seleccionar al menos a quién aplica el premio');
                valido = false;
            }
            break;

        case 16: // Comisión Individual
        case 17: // Comisión sobre Local
            const tieneTope = document.getElementById(`tiene_tope_${tipoNovedad === 16 ? 'individual' : 'local'}`).value;
            
            if (tieneTope === '1') {
                // Con tope: validar dos porcentajes
                const porcentaje1 = parseFloat(document.getElementById(`porcentaje_1_${tipoNovedad === 16 ? 'individual' : 'local'}`).value);
                const porcentaje2 = parseFloat(document.getElementById(`porcentaje_2_${tipoNovedad === 16 ? 'individual' : 'local'}`).value);
                
                if (!porcentaje1 || porcentaje1 <= 0 || porcentaje1 > 1) {
                    errores.push('El primer porcentaje debe estar entre 0.01% y 1%');
                    valido = false;
                }
                if (!porcentaje2 || porcentaje2 <= 0 || porcentaje2 > 1) {
                    errores.push('El segundo porcentaje debe estar entre 0.01% y 1%');
                    valido = false;
                }
            } else if (tieneTope === '0') {
                // Sin tope: validar un porcentaje
                const porcentajeUnico = parseFloat(document.getElementById(`porcentaje_unico_${tipoNovedad === 16 ? 'individual' : 'local'}`).value);
                
                if (!porcentajeUnico || porcentajeUnico <= 0 || porcentajeUnico > 1) {
                    errores.push('El porcentaje debe estar entre 0.01% y 1%');
                    valido = false;
                }
            } else {
                errores.push('Debe indicar si la comisión tiene tope o no');
                valido = false;
            }
            break;

        case 18: // Premios - Ajuste General
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
            datos.tipo_plus_caja = document.getElementById('tipo_plus_caja').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_plus_caja').value;
            
            // Solo incluir importe si se especifica
            const importePlusCaja = document.getElementById('importe_plus_caja');
            if (importePlusCaja && importePlusCaja.value) {
                datos.importe = parseFloat(importePlusCaja.value);
            }
            break;

        case 13: // Plus de Sub-Encargada
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
            datos.importe = parseFloat(document.getElementById('importe_premio_local').value);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_premio_local').value;
            datos.aplica_vendedora = document.getElementById('aplica_vendedora').checked;
            datos.aplica_sub_encargada = document.getElementById('aplica_sub_encargada').checked;
            break;

        case 16: // Comisión Individual
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_comision_individual').value;
            datos.tiene_tope = document.getElementById('tiene_tope_individual').value === '1';
            
            if (datos.tiene_tope) {
                datos.porcentaje_1 = parseFloat(document.getElementById('porcentaje_1_individual').value);
                datos.porcentaje_2 = parseFloat(document.getElementById('porcentaje_2_individual').value);
            } else {
                datos.porcentaje_unico = parseFloat(document.getElementById('porcentaje_unico_individual').value);
            }
            break;

        case 17: // Comisión sobre Local
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_comision_local').value;
            datos.tiene_tope = document.getElementById('tiene_tope_local').value === '1';
            
            if (datos.tiene_tope) {
                datos.porcentaje_1 = parseFloat(document.getElementById('porcentaje_1_local').value);
                datos.porcentaje_2 = parseFloat(document.getElementById('porcentaje_2_local').value);
            } else {
                datos.porcentaje_unico = parseFloat(document.getElementById('porcentaje_unico_local').value);
            }
            break;

        case 18: // Premios - Ajuste General
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
 * La fecha de vigencia es el día 28 del mes ANTERIOR al período calculado
 */
async function calcularFechaPeriodoParaVigencia(tipoNovedadId) {
    const periodo = await calcularPeriodoSegunCierre(tipoNovedadId);
    
    // Calcular el mes anterior al período
    let mesInicio = periodo.month - 1; // periodo.month ya está en 1-based
    let yearInicio = periodo.year;
    
    // Si el período es enero, el mes anterior es diciembre del año anterior
    if (mesInicio < 1) {
        mesInicio = 12;
        yearInicio--;
    }
    
    // La fecha de vigencia es siempre el día 28 del mes anterior al período
    const fechaInicioPeriodo = new Date(yearInicio, mesInicio - 1, 28); // mesInicio - 1 porque Date usa 0-based
    
    // Formatear como YYYY-MM-DD para input date
    return fechaInicioPeriodo.toISOString().split('T')[0];
}

/**
 * Calcular fecha del período siguiente para fecha de vigencia
 */
async function calcularFechaPeriodoSiguienteParaVigencia(tipoNovedadId) {
    const periodo = await calcularPeriodoSiguienteSegunCierre(tipoNovedadId);
    
    // Calcular el mes anterior al período siguiente
    let mesInicio = periodo.month - 1; // periodo.month ya está en 1-based
    let yearInicio = periodo.year;
    
    // Si el período es enero, el mes anterior es diciembre del año anterior
    if (mesInicio < 1) {
        mesInicio = 12;
        yearInicio--;
    }
    
    // La fecha de vigencia es siempre el día 28 del mes anterior al período
    const fechaInicioPeriodo = new Date(yearInicio, mesInicio - 1, 28); // mesInicio - 1 porque Date usa 0-based
    
    // Formatear como YYYY-MM-DD para input date
    return fechaInicioPeriodo.toISOString().split('T')[0];
}
/**
 * Configurar fecha de vigencia según tipo de corte
 */
async function configurarFechaVigencia(tipoNovedadId, campoFechaId) {
    const tipoData = tiposNovedadData[tipoNovedadId];
    const campoFecha = document.getElementById(campoFechaId);
    
    if (!tipoData || !campoFecha) return;
    
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
    
    if (tipoData.corte === 'Período') {
        // Si es Período, calcular fecha basada en día de cierre
        try {
            const fechaPeriodo = await calcularFechaPeriodoParaVigencia(tipoNovedadId);
            const periodo = await calcularPeriodoSegunCierre(tipoNovedadId);
            
            campoFecha.value = fechaPeriodo;
            
            // Calcular fechas del período para mostrar
            let mesInicioPeriodo = periodo.month - 1;
            let yearInicioPeriodo = periodo.year;
            
            if (mesInicioPeriodo < 1) {
                mesInicioPeriodo = 12;
                yearInicioPeriodo--;
            }
            
            const fechaInicio = `28/${mesInicioPeriodo.toString().padStart(2, '0')}/${yearInicioPeriodo}`;
            const fechaFin = `27/${periodo.month.toString().padStart(2, '0')}/${periodo.year}`;
            
            // Mensaje explicativo con información del período
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
                        <strong>Rango:</strong> ${fechaInicio} al ${fechaFin} | 
                        <strong>Cierre:</strong> ${cierreTexto}
                    </small>
                    <br>
                    <small class="text-success">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <strong>Fecha de vigencia:</strong> Inicio del período (${fechaInicio})
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
        
    } else if (tipoData.corte === 'Período siguiente') {
        // Si es Período siguiente, calcular fecha basada en período siguiente
        try {
            const fechaPeriodo = await calcularFechaPeriodoSiguienteParaVigencia(tipoNovedadId);
            const periodo = await calcularPeriodoSiguienteSegunCierre(tipoNovedadId);
            
            campoFecha.value = fechaPeriodo;
            
            // Calcular fechas del período siguiente para mostrar
            let mesInicioPeriodo = periodo.month - 1;
            let yearInicioPeriodo = periodo.year;
            
            if (mesInicioPeriodo < 1) {
                mesInicioPeriodo = 12;
                yearInicioPeriodo--;
            }
            
            const fechaInicio = `28/${mesInicioPeriodo.toString().padStart(2, '0')}/${yearInicioPeriodo}`;
            const fechaFin = `27/${periodo.month.toString().padStart(2, '0')}/${periodo.year}`;
            
            // Mensaje explicativo con información del período siguiente
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
                        <strong>Rango:</strong> ${fechaInicio} al ${fechaFin} | 
                        <strong>Cierre:</strong> ${cierreTexto}
                    </small>
                    <br>
                    <small class="text-success">
                        <i class="fas fa-calendar-plus me-1"></i>
                        <strong>Fecha de vigencia:</strong> Inicio del período siguiente (${fechaInicio})
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
        
    } else if (tipoData.corte === 'Fecha Vigencia') {
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
        
        // Almacenar la información completa globalmente
        tiposNovedadData = {};
        tipos.forEach(tipo => {
            tiposNovedadData[tipo.id] = tipo;
        });
        
        const selectTipo = document.getElementById('tipo_novedad');
        if (selectTipo) {
            selectTipo.innerHTML = '<option value="">Seleccione tipo de novedad...</option>';
            tipos.forEach(tipo => {
                if (tipo.activo == 1) { // Solo mostrar tipos activos
                    selectTipo.innerHTML += `<option value="${tipo.id}">${tipo.descripcion}</option>`;
                }
            });
        }

        console.log('Tipos de novedad cargados:', tipos.length);
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
 * Configurar campos dinámicos para comisiones
 */
function configurarCamposComision(tipoComision) {
    const sufijo = tipoComision === 'individual' ? 'individual' : 'local';
    
    // Event listener para cambio de "tiene tope"
    const tienTopeSelect = document.getElementById(`tiene_tope_${sufijo}`);
    const camposSinTope = document.getElementById(`campos_sin_tope_${sufijo}`);
    const camposConTope = document.getElementById(`campos_con_tope_${sufijo}`);
    
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
                // Si no hay selección, ocultar ambos
                if (camposSinTope) camposSinTope.style.display = 'none';
                if (camposConTope) camposConTope.style.display = 'none';
            }
        });
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