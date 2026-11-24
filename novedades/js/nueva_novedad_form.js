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

// Configuración de tipos de novedad activos y sus campos requeridos
const tiposNovedadConfigActualizada = {
    1: { // Cambio de sucursal / centro de costos
        config: 'config-cambio-sucursal',
        campos: ['nueva_sucursal', 'fecha_vigencia_sucursal'],
        validaciones: ['nueva_sucursal', 'fecha_vigencia']
    },
    2: { // Nuevo puesto
        config: 'config-nuevo-puesto',
        campos: ['nuevo_puesto', 'fecha_vigencia_puesto', 'tipo_nuevo_puesto'],
        validaciones: ['puesto', 'fecha_vigencia', 'tipo_nuevo_puesto']
    },
    4: { // Ajuste de premios (importe no obligatorio)
        config: 'config-ajuste-premios',
        campos: ['fecha_vigencia_premios'],
        validaciones: ['fecha_vigencia']
    },
    5: { // Horas extras
        config: 'config-horas-extras',
        campos: ['cantidad_horas_extras', 'fecha_vigencia_horas_extras'],
        validaciones: ['cantidad_horas', 'fecha_vigencia']
    },
    6: { // Horas adicionales
        config: 'config-horas-adicionales',
        campos: ['cantidad_horas_adicionales', 'fecha_vigencia_horas_adicionales'],
        validaciones: ['cantidad_horas', 'fecha_vigencia']
    },
    7: { // Permisos
        config: 'config-permisos',
        campos: ['fecha_permiso', 'compensa'],
        validaciones: ['fecha_permiso', 'compensa']
    },
    32: { // Plus de caja (sin importe, vencimiento opcional)
        config: 'config-plus-caja',
        campos: ['tipo_plus_caja', 'fecha_vigencia_plus_caja'],
        validaciones: ['tipo_plus_caja', 'fecha_vigencia']
    },
    33: { // Plus de Sub-Encargada (SI/NO, sin importe, vencimiento opcional)
        config: 'config-plus-sub-encargada',
        campos: ['aplica_plus_sub', 'fecha_vigencia_plus_sub'],
        validaciones: ['aplica_plus_sub', 'fecha_vigencia']
    },
    34: { // Plus de Encargada (SI/NO, sin importe, vencimiento opcional)
        config: 'config-plus-encargada',
        campos: ['aplica_plus_enc', 'fecha_vigencia_plus_enc'],
        validaciones: ['aplica_plus_enc', 'fecha_vigencia']
    },
    35: { // Premio Local (escala global por defecto, todos los importes obligatorios)
        config: 'config-premio-local',
        campos: [
            'fecha_vigencia_premio_local',
            'premio_1ro_enc_sub',
            'premio_1ro_vend',
            'premio_cumple_enc_sub',
            'premio_cumple_vend',
            'premio_no_llega_enc_sub',
            'premio_no_llega_vend'
        ],
        validaciones: ['fecha_vigencia', 'premio_local_escala']
    },
    36: { // Comisión Individual (para Supervisoras - múltiples sucursales PROPIOS)
        config: 'config-comision-individual',
        campos: ['fecha_vigencia_comision_individual'],
        validaciones: ['fecha_vigencia', 'comision_sucursales']
    },
    37: { // Comisión sobre Local
        config: 'config-comision-local',
        campos: ['tiene_tope_local', 'fecha_vigencia_comision_local'],
        validaciones: ['fecha_vigencia', 'comision_local']
    },
    38: { // Premios - Ajuste General (importe no obligatorio)
        config: 'config-premios-ajuste',
        campos: ['fecha_vigencia_ajuste'],
        validaciones: ['fecha_vigencia']
    },
    53: { // Reemplazo
        config: 'config-reemplazo',
        campos: ['puesto_reemplazo', 'fecha_vigencia_reemplazo', 'tipo_reemplazo'],
        validaciones: ['puesto', 'fecha_vigencia', 'tipo_reemplazo']
    },
    54: { // Aumento Salarial
        config: 'config-aumento-salarial',
        campos: ['tipo_aumento', 'porcentaje_aumento', 'monto_aumento', 'fecha_vigencia_aumento'],
        validaciones: ['tipo_aumento', 'fecha_vigencia']
    }
};

/**
 * Alternar visibilidad de la sección de empleado (Premio Local único es global)
 */
function actualizarVisibilidadEmpleadoPorTipo(tipoSeleccionado) {
    const empSec = document.getElementById('datos-empleado-section');
    const ayudaGlobal = document.getElementById('empleado-ayuda-global');
    const legajoInput = document.getElementById('legajo');
    const esGlobal = (tipoSeleccionado === 35);

    if (empSec) empSec.style.display = esGlobal ? 'none' : 'block';
    if (ayudaGlobal) ayudaGlobal.style.display = esGlobal ? 'block' : 'none';
    if (legajoInput) {
        if (esGlobal) {
            legajoInput.removeAttribute('required');
        } else {
            legajoInput.setAttribute('required', 'required');
        }
    }
}

/**
 * Manejar cambio de tipo de novedad
 */
function onTipoNovedadChangeActualizado(selectElement) {
    const tipoSeleccionado = parseInt(selectElement.value);

    // Mostrar u ocultar selección de empleado según si el tipo es global (Premio Local)
    actualizarVisibilidadEmpleadoPorTipo(tipoSeleccionado);

    // Toggle del botón de información (con verificación)
    try {
        if (typeof window.toggleInfoButton === 'function') {
            window.toggleInfoButton(selectElement, 'unica');
        } else if (typeof toggleInfoButton === 'function') {
            toggleInfoButton(selectElement, 'unica');
        }
    } catch (error) {
        console.warn('Error al ejecutar toggleInfoButton:', error);
    }

    // Ocultar todas las configuraciones
    ocultarTodasLasConfiguraciones();

    // Limpiar todos los campos dinámicos
    limpiarCamposDinamicos();

    if (tipoSeleccionado && tiposNovedadConfigActualizada[tipoSeleccionado]) {
        const config = tiposNovedadConfigActualizada[tipoSeleccionado];
        const configId = config.config;
        const configElement = document.getElementById(configId);

        if (configElement) {
            document.getElementById('configuracion-novedad').style.display = 'block';
            configElement.style.display = 'block';
            configElement.classList.add('fade-in');

            config.campos.forEach(campoId => {
                const campo = document.getElementById(campoId);
                if (campo) {
                    campo.setAttribute('required', 'required');

                    if (campoId.includes('fecha_vigencia') && !campoId.includes('hasta')) {
                        setTimeout(() => {
                            configurarFechaVigencia(tipoSeleccionado, campoId);
                        }, 100);
                    }
                }
            });

            if (tipoSeleccionado === 1) {
                configurarCambioSucursal();
            } else if (tipoSeleccionado === 2) {
                configurarCambioPuesto();
            }
        }

        if (window.modoActual === 'unica' || (!window.modoActual && modoActual === 'unica')) {
            mostrarPeriodoAplicacion(tipoSeleccionado);
        }
    } else {
        document.getElementById('configuracion-novedad').style.display = 'none';
        if (window.modoActual === 'unica' || (!window.modoActual && modoActual === 'unica')) {
            ocultarPeriodoAplicacion();
        }
    }

    if (tipoSeleccionado === 36) {
        setTimeout(() => inicializarComisionSucursales('unica'), 100);
    } else if (tipoSeleccionado === 37) {
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
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else {
                input.value = '';
            }
            input.classList.remove('is-invalid', 'is-valid');
        });

        const tbodyComInd = config.querySelector('#tbody_comision_ind_unica');
        if (tbodyComInd) {
            tbodyComInd.innerHTML = '<tr class="fila-vacia-comision"><td colspan="5" class="text-center text-muted py-3">Agregue al menos una sucursal PROPIOS</td></tr>';
        }

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
        const empleado = await NovedadesApp.request('get_empleado_info', { legajo: legajo });
        
        if (empleado && empleado.codigo_centro_costos) {
            const codigoCentroCostos = empleado.codigo_centro_costos;
            
            if (codigoCentroCostos.startsWith('LOC')) {
                const sucursalDescripcion = await NovedadesApp.request('get_sucursal_por_centro_costos', { 
                    codigo: codigoCentroCostos 
                });
                
                if (sucursalDescripcion) {
                    document.getElementById('sucursal_actual').value = codigoCentroCostos;
                    document.getElementById('sucursal-actual-text').textContent = sucursalDescripcion;
                    document.getElementById('sucursal-actual-info').style.display = 'block';
                } else {
                    document.getElementById('sucursal-actual-info').style.display = 'none';
                }
            } else {
                document.getElementById('sucursal-actual-info').style.display = 'none';
            }
            
            cargarSucursalesEnSelect();
        } else {
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
        const sucursales = await NovedadesApp.request('get_sucursales_con_casa_central');
        const nuevaSucursalSelect = document.getElementById('nueva_sucursal');
        const sucursalActual = (document.getElementById('sucursal_actual')?.value || '').trim();
        const sucursalActualNum = sucursalActual.replace(/^LOC0*/i, '');

        if (!nuevaSucursalSelect) return;
        nuevaSucursalSelect.innerHTML = '<option value="">Seleccione nueva sucursal...</option>';

        sucursales.forEach(sucursal => {
            const num = String(sucursal.numero || '').trim();
            if (num !== sucursalActual && num !== sucursalActualNum) {
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

    // Validar período de aplicación (solo en modo único)
    const modoActivo = window.modoActual || modoActual;
    if (modoActivo === 'unica') {
        const validacionPeriodo = validarPeriodoAplicacionFormulario();
        if (!validacionPeriodo.valido) {
            console.error('❌ Validación de período falló:', validacionPeriodo.error);
            NovedadesApp.mostrarError(validacionPeriodo.error);
            return;
        }
        console.log('✅ Validación de período exitosa');
    }

    // Recopilar datos del formulario (ahora es async)
    const datos = await recopilarDatosFormularioActualizado(tipoNovedad);
    console.log('📦 Datos recopilados:', datos);
    
    // Log específico para período
    if (datos.periodo_mes && datos.periodo_anio) {
        console.log('🔍 PERÍODO EN DATOS FINALES:', {
            periodo_mes: datos.periodo_mes,
            periodo_anio: datos.periodo_anio,
            tipo_mes: typeof datos.periodo_mes,
            tipo_anio: typeof datos.periodo_anio
        });
    } else {
        console.warn('⚠️ NO SE ENCONTRÓ PERÍODO EN LOS DATOS FINALES');
    }

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
    const tipoEl = document.getElementById('tipo_novedad');
    const tipoNovedad = parseInt(tipoEl ? tipoEl.value : '0', 10);
    const esGlobalPremioLocal = (tipoNovedad === 35);

    const camposBasicos = esGlobalPremioLocal ? ['tipo_novedad'] : ['empleado-select', 'tipo_novedad'];
    let valido = true;

    camposBasicos.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (!campo || !campo.value.trim()) {
            if (campo) campo.classList.add('is-invalid');
            valido = false;
        } else {
            campo.classList.remove('is-invalid');
            campo.classList.add('is-valid');
        }
    });

    if (!esGlobalPremioLocal) {
        const legajoHidden = document.getElementById('legajo');
        if (!legajoHidden || !legajoHidden.value) {
            const empleadoSelect = document.getElementById('empleado-select');
            if (empleadoSelect) empleadoSelect.classList.add('is-invalid');
            valido = false;
        }
    }

    return valido;
}

/**
 * Validar fecha de vencimiento opcional (>= fecha vigencia)
 */
function validarRangoFechaOpcional(fechaVigencia, fechaHastaInput, errores) {
    if (!fechaHastaInput || !fechaHastaInput.value) {
        if (fechaHastaInput) fechaHastaInput.classList.remove('is-invalid');
        return true;
    }
    if (fechaVigencia && fechaHastaInput.value < fechaVigencia) {
        errores.push('La fecha de vencimiento no puede ser anterior a la fecha de vigencia');
        fechaHastaInput.classList.add('is-invalid');
        return false;
    }
    fechaHastaInput.classList.remove('is-invalid');
    fechaHastaInput.classList.add('is-valid');
    return true;
}

/**
 * Validar configuración específica según tipo de novedad
 */
function validarConfiguracionTipoActualizado(tipoNovedad) {
    if (!tiposNovedadConfigActualizada[tipoNovedad]) {
        NovedadesApp.mostrarError('Tipo de novedad no válido');
        return false;
    }

    const config = tiposNovedadConfigActualizada[tipoNovedad];
    const errores = [];
    let valido = true;

    // Validar campos requeridos declarados en la config
    config.campos.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (campo && campo.hasAttribute('required')) {
            const vacio = (campo.type === 'checkbox') ? false : !campo.value.trim();
            if (vacio) {
                campo.classList.add('is-invalid');
                valido = false;
            } else {
                campo.classList.remove('is-invalid');
                campo.classList.add('is-valid');
            }
        }
    });

    switch (tipoNovedad) {
        case 1: { // Cambio de sucursal
            const sucursalActual = (document.getElementById('sucursal_actual')?.value || '').trim();
            const sucursalActualNum = sucursalActual.replace(/^LOC0*/i, '');
            const nuevaSucursal = (document.getElementById('nueva_sucursal')?.value || '').trim();
            if (!nuevaSucursal) {
                errores.push('Debe seleccionar la nueva sucursal');
                document.getElementById('nueva_sucursal')?.classList.add('is-invalid');
                valido = false;
            } else if (sucursalActual && (nuevaSucursal === sucursalActual || nuevaSucursal === sucursalActualNum)) {
                errores.push('La nueva sucursal debe ser diferente a la actual');
                document.getElementById('nueva_sucursal')?.classList.add('is-invalid');
                valido = false;
            }
            const fvSuc = document.getElementById('fecha_vigencia_sucursal')?.value || '';
            if (!fvSuc) {
                errores.push('La fecha de vigencia es obligatoria');
                valido = false;
            }
            if (!validarRangoFechaOpcional(fvSuc, document.getElementById('fecha_vigencia_hasta_sucursal'), errores)) {
                valido = false;
            }
            break;
        }

        case 2: { // Nuevo puesto
            const nuevoPuesto = document.getElementById('nuevo_puesto')?.value;
            const fvPuesto = document.getElementById('fecha_vigencia_puesto')?.value;
            const radioTemp = document.getElementById('tipo_puesto_temporario');
            if (!nuevoPuesto) {
                errores.push('Debe seleccionar el nuevo puesto');
                valido = false;
            }
            if (!fvPuesto) {
                errores.push('La fecha de vigencia es obligatoria');
                valido = false;
            }
            if (radioTemp && radioTemp.checked) {
                const fHastaPuesto = document.getElementById('fecha_vigencia_hasta_puesto');
                if (!fHastaPuesto || !fHastaPuesto.value) {
                    errores.push('La fecha de fin es obligatoria para cambios temporarios');
                    fHastaPuesto?.classList.add('is-invalid');
                    valido = false;
                } else if (fvPuesto && fHastaPuesto.value < fvPuesto) {
                    errores.push('La fecha de fin no puede ser anterior a la fecha de vigencia');
                    fHastaPuesto.classList.add('is-invalid');
                    valido = false;
                }
            }
            break;
        }

        case 4: { // Ajuste de premios (importe opcional)
            const impRaw = document.getElementById('importe_premios')?.value?.trim() || '';
            if (impRaw !== '') {
                const importe = parseFloat(impRaw);
                if (isNaN(importe) || importe <= 0) {
                    errores.push('Si ingresa un importe, debe ser mayor a cero');
                    document.getElementById('importe_premios')?.classList.add('is-invalid');
                    valido = false;
                } else if (importe > 999999999999.99) {
                    errores.push('El importe no puede ser mayor a $999,999,999,999.99');
                    valido = false;
                }
            }
            if (!document.getElementById('fecha_vigencia_premios')?.value) {
                errores.push('La fecha de vigencia es obligatoria');
                valido = false;
            }
            break;
        }

        case 5: // Horas extras
        case 6: { // Horas adicionales
            const idCant = tipoNovedad === 5 ? 'cantidad_horas_extras' : 'cantidad_horas_adicionales';
            const idFec = tipoNovedad === 5 ? 'fecha_vigencia_horas_extras' : 'fecha_vigencia_horas_adicionales';
            const cantidadHoras = parseInt(document.getElementById(idCant)?.value || '0', 10);
            if (!cantidadHoras || cantidadHoras <= 0) {
                errores.push('La cantidad de horas debe ser mayor a cero');
                valido = false;
            }
            if (!document.getElementById(idFec)?.value) {
                errores.push('La fecha de vigencia es obligatoria');
                valido = false;
            }
            break;
        }

        case 7: { // Permisos
            const fechaPermisoValue = document.getElementById('fecha_permiso')?.value;
            const compensaValue = document.getElementById('compensa')?.value;
            if (!fechaPermisoValue) {
                errores.push('La fecha del permiso es obligatoria');
                document.getElementById('fecha_permiso')?.classList.add('is-invalid');
                valido = false;
            }
            if (!compensaValue) {
                errores.push('Debe indicar si compensa o no');
                document.getElementById('compensa')?.classList.add('is-invalid');
                valido = false;
            }
            break;
        }

        case 32: { // Plus de caja (sin importe, vencimiento opcional)
            const tipoPlusCaja = document.getElementById('tipo_plus_caja')?.value;
            const fvCaja = document.getElementById('fecha_vigencia_plus_caja')?.value;
            if (!tipoPlusCaja) {
                errores.push('Debe seleccionar el tipo de plus de caja');
                valido = false;
            }
            if (!fvCaja) {
                errores.push('La fecha de vigencia es obligatoria');
                valido = false;
            }
            if (!validarRangoFechaOpcional(fvCaja, document.getElementById('fecha_vigencia_hasta_plus_caja'), errores)) {
                valido = false;
            }
            break;
        }

        case 33: { // Plus de Sub-Encargada (SI/NO, vencimiento opcional)
            const aplicaSub = document.getElementById('aplica_plus_sub')?.value;
            const fvSub = document.getElementById('fecha_vigencia_plus_sub')?.value;
            if (aplicaSub !== '1' && aplicaSub !== '0') {
                errores.push('Debe indicar si aplica o no el Plus de Sub-Encargada');
                document.getElementById('aplica_plus_sub')?.classList.add('is-invalid');
                valido = false;
            }
            if (!fvSub) {
                errores.push('La fecha de vigencia es obligatoria');
                valido = false;
            }
            if (!validarRangoFechaOpcional(fvSub, document.getElementById('fecha_vigencia_hasta_plus_sub'), errores)) {
                valido = false;
            }
            break;
        }

        case 34: { // Plus de Encargada (SI/NO, vencimiento opcional)
            const aplicaEnc = document.getElementById('aplica_plus_enc')?.value;
            const fvEnc = document.getElementById('fecha_vigencia_plus_enc')?.value;
            if (aplicaEnc !== '1' && aplicaEnc !== '0') {
                errores.push('Debe indicar si aplica o no el Plus de Encargada');
                document.getElementById('aplica_plus_enc')?.classList.add('is-invalid');
                valido = false;
            }
            if (!fvEnc) {
                errores.push('La fecha de vigencia es obligatoria');
                valido = false;
            }
            if (!validarRangoFechaOpcional(fvEnc, document.getElementById('fecha_vigencia_hasta_plus_enc'), errores)) {
                valido = false;
            }
            break;
        }

        case 35: { // Premio Local (escala global, los 6 importes obligatorios)
            const fvPremio = document.getElementById('fecha_vigencia_premio_local')?.value;
            if (!fvPremio) {
                errores.push('La fecha de vigencia es obligatoria');
                valido = false;
            }
            const camposEscala = [
                { id: 'premio_1ro_enc_sub', label: '1ro en el ranking - Encargada/Sub' },
                { id: 'premio_1ro_vend', label: '1ro en el ranking - Vendedora' },
                { id: 'premio_cumple_enc_sub', label: 'Cumple objetivo - Encargada/Sub' },
                { id: 'premio_cumple_vend', label: 'Cumple objetivo - Vendedora' },
                { id: 'premio_no_llega_enc_sub', label: '1ro que no llega - Encargada/Sub' },
                { id: 'premio_no_llega_vend', label: '1ro que no llega - Vendedora' }
            ];
            camposEscala.forEach(item => {
                const el = document.getElementById(item.id);
                const val = parseFloat(el?.value || '0');
                if (!val || val <= 0) {
                    errores.push(`El importe "${item.label}" es obligatorio y debe ser mayor a 0`);
                    el?.classList.add('is-invalid');
                    valido = false;
                } else {
                    el.classList.remove('is-invalid');
                    el.classList.add('is-valid');
                }
            });
            break;
        }

        case 36: { // Comisión Individual (para Supervisoras - múltiples sucursales PROPIOS)
            const fvComInd = document.getElementById('fecha_vigencia_comision_individual')?.value;
            if (!fvComInd) {
                errores.push('La fecha de vigencia es obligatoria');
                valido = false;
            }
            const resCom = recopilarSucursalesComision('unica', true);
            if (!resCom.valido) {
                resCom.errores.forEach(e => errores.push(e));
                valido = false;
            }
            break;
        }

        case 37: { // Comisión sobre Local
            const fvComLoc = document.getElementById('fecha_vigencia_comision_local')?.value;
            if (!fvComLoc) {
                errores.push('La fecha de vigencia es obligatoria');
                valido = false;
            }
            const tieneTope = document.getElementById('tiene_tope_local')?.value;
            if (tieneTope === '1') {
                const p1 = parseFloat(document.getElementById('porcentaje_1_local')?.value || '0');
                const p2 = parseFloat(document.getElementById('porcentaje_2_local')?.value || '0');
                if (!p1 || p1 <= 0 || p1 > 100) {
                    errores.push('El 1er porcentaje (antes del tope) debe ser mayor a 0 y menor o igual a 100');
                    valido = false;
                }
                if (!p2 || p2 <= 0 || p2 > 100) {
                    errores.push('El 2do porcentaje (luego del tope) debe ser mayor a 0 y menor o igual a 100');
                    valido = false;
                }
            } else if (tieneTope === '0') {
                const pu = parseFloat(document.getElementById('porcentaje_unico_local')?.value || '0');
                if (!pu || pu <= 0 || pu > 100) {
                    errores.push('El porcentaje debe ser mayor a 0 y menor o igual a 100');
                    valido = false;
                }
            } else {
                errores.push('Debe indicar si la comisión tiene tope o no');
                valido = false;
            }
            break;
        }

        case 38: { // Premios - Ajuste General (importe opcional)
            const impAjRaw = document.getElementById('importe_ajuste_general')?.value?.trim() || '';
            if (impAjRaw !== '') {
                const impAj = parseFloat(impAjRaw);
                if (isNaN(impAj) || impAj <= 0) {
                    errores.push('Si ingresa un importe en Ajuste General, debe ser mayor a 0');
                    document.getElementById('importe_ajuste_general')?.classList.add('is-invalid');
                    valido = false;
                }
            }
            if (!document.getElementById('fecha_vigencia_ajuste')?.value) {
                errores.push('La fecha de vigencia es obligatoria');
                valido = false;
            }
            break;
        }

        case 53: { // Reemplazo
            const puestoReemplazo = document.getElementById('puesto_reemplazo');
            const fechaVigenciaReemplazo = document.getElementById('fecha_vigencia_reemplazo');
            const tipoReemplazoPermanente = document.getElementById('tipo_reemplazo_permanente');
            const tipoReemplazoTemporario = document.getElementById('tipo_reemplazo_temporario');

            if (!puestoReemplazo || !puestoReemplazo.value) {
                errores.push('El puesto de reemplazo es obligatorio');
                valido = false;
            }
            if (!fechaVigenciaReemplazo || !fechaVigenciaReemplazo.value) {
                errores.push('La fecha de vigencia del reemplazo es obligatoria');
                valido = false;
            }
            if ((!tipoReemplazoPermanente || !tipoReemplazoPermanente.checked) &&
                (!tipoReemplazoTemporario || !tipoReemplazoTemporario.checked)) {
                errores.push('Debe seleccionar el tipo de reemplazo');
                valido = false;
            }
            if (tipoReemplazoTemporario && tipoReemplazoTemporario.checked) {
                const fechaFinReemplazo = document.getElementById('fecha_vigencia_hasta_reemplazo');
                if (!fechaFinReemplazo || !fechaFinReemplazo.value) {
                    errores.push('La fecha de fin es obligatoria para reemplazos temporarios');
                    valido = false;
                }
            }
            break;
        }

        case 54: { // Aumento Salarial
            const fechaVigenciaAumento = document.getElementById('fecha_vigencia_aumento');
            const tipoAumentoPorcentaje = document.getElementById('tipo_aumento_porcentaje');
            const tipoAumentoMonto = document.getElementById('tipo_aumento_monto');

            if (!fechaVigenciaAumento || !fechaVigenciaAumento.value) {
                errores.push('La fecha de vigencia del aumento es obligatoria');
                valido = false;
            }
            if ((!tipoAumentoPorcentaje || !tipoAumentoPorcentaje.checked) &&
                (!tipoAumentoMonto || !tipoAumentoMonto.checked)) {
                errores.push('Debe seleccionar el tipo de aumento');
                valido = false;
            }
            if (tipoAumentoPorcentaje && tipoAumentoPorcentaje.checked) {
                const porcentajeAumento = parseFloat(document.getElementById('porcentaje_aumento').value);
                if (!porcentajeAumento || porcentajeAumento <= 0) {
                    errores.push('El porcentaje de aumento debe ser mayor a 0');
                    valido = false;
                }
            } else if (tipoAumentoMonto && tipoAumentoMonto.checked) {
                const montoAumento = parseFloat(document.getElementById('monto_aumento').value);
                if (!montoAumento || montoAumento <= 0) {
                    errores.push('El monto de aumento debe ser mayor a 0');
                    valido = false;
                }
            }
            break;
        }
    }

    if (errores.length > 0) {
        NovedadesApp.mostrarError(errores.join('<br>'));
    }

    return valido;
}

/**
 * Recopilar datos del formulario según el tipo de novedad
 */
async function recopilarDatosFormularioActualizado(tipoNovedad) {
    let datos;

    if (tipoNovedad === 35) {
        // Premio Local global (no requiere empleado)
        datos = {
            legajo: 0,
            nombre: 'TODOS',
            apellido: 'LOCALES',
            tipo_novedad: 35,
            es_global: 1,
            observaciones: document.getElementById('observaciones') ? document.getElementById('observaciones').value : ''
        };
    } else {
        const legajo = document.getElementById('legajo').value;
        let nombreEmpleado = '';
        let apellidoEmpleado = '';

        const empleadoSeleccionado = window.NovedadesApp ? window.NovedadesApp.empleadoSeleccionado : null;
        if (empleadoSeleccionado && empleadoSeleccionado.nombre && empleadoSeleccionado.apellido) {
            nombreEmpleado = empleadoSeleccionado.nombre;
            apellidoEmpleado = empleadoSeleccionado.apellido;
        } else {
            try {
                const empleadoSelect = $('#empleado-select');
                if (empleadoSelect.length && empleadoSelect.val()) {
                    const selectedData = empleadoSelect.select2('data')[0];
                    if (selectedData) {
                        if (selectedData.nombre && selectedData.apellido) {
                            nombreEmpleado = selectedData.nombre;
                            apellidoEmpleado = selectedData.apellido;
                        } else if (selectedData.text) {
                            const match = selectedData.text.match(/^(.+?)\s+(.+?)\s*\(\d+\)$/);
                            if (match) {
                                nombreEmpleado = match[1].trim();
                                apellidoEmpleado = match[2].trim();
                            }
                        }
                    }
                }
            } catch (e) {
                console.warn('Error obteniendo datos del Select2:', e);
            }

            if ((!nombreEmpleado || !apellidoEmpleado) && legajo) {
                try {
                    const empleadoData = await NovedadesApp.request('buscar_empleado', { legajo: legajo }, 'GET');
                    if (empleadoData && empleadoData.nombre && empleadoData.apellido) {
                        nombreEmpleado = empleadoData.nombre;
                        apellidoEmpleado = empleadoData.apellido;
                    }
                } catch (error) {
                    console.warn('Error buscando empleado por legajo:', error);
                }
            }
        }

        if (!nombreEmpleado || !apellidoEmpleado) {
            throw new Error('No se pudo obtener el nombre y apellido del empleado seleccionado');
        }

        datos = {
            legajo: legajo,
            nombre: nombreEmpleado,
            apellido: apellidoEmpleado,
            tipo_novedad: tipoNovedad,
            observaciones: document.getElementById('observaciones') ? document.getElementById('observaciones').value : ''
        };
    }

    switch (tipoNovedad) {
        case 1: { // Cambio de sucursal
            datos.nueva_sucursal = document.getElementById('nueva_sucursal').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_sucursal').value;
            const fhSuc = document.getElementById('fecha_vigencia_hasta_sucursal')?.value?.trim();
            datos.fecha_vigencia_hasta = fhSuc || null;
            const empSel = window.NovedadesApp?.empleadoSeleccionado;
            if (empSel && empSel.sucursal_numero) {
                datos.sucursal_actual = empSel.sucursal_numero;
            }
            break;
        }

        case 2: { // Nuevo puesto
            datos.puesto = document.getElementById('nuevo_puesto').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_puesto').value;
            const radioTemporario = document.getElementById('tipo_puesto_temporario');
            const tipoPuesto = (radioTemporario && radioTemporario.checked) ? 'temporario' : 'permanente';
            datos.tipo_nuevo_puesto = tipoPuesto;
            datos.fecha_vigencia_hasta = (tipoPuesto === 'temporario')
                ? (document.getElementById('fecha_vigencia_hasta_puesto')?.value || '')
                : null;
            break;
        }

        case 4: { // Ajuste de premios (importe opcional)
            const impRaw = document.getElementById('importe_premios')?.value?.trim() || '';
            datos.importe = impRaw !== '' ? parseFloat(impRaw) : null;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_premios').value;
            break;
        }

        case 5: // Horas extras
            datos.cantidad_horas = parseInt(document.getElementById('cantidad_horas_extras').value, 10);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_horas_extras').value;
            break;

        case 6: // Horas adicionales
            datos.cantidad_horas = parseInt(document.getElementById('cantidad_horas_adicionales').value, 10);
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_horas_adicionales').value;
            break;

        case 7: { // Permisos
            const fechaPermisoElement = document.getElementById('fecha_permiso');
            const compensaElement = document.getElementById('compensa');
            datos.fecha_permiso = fechaPermisoElement ? fechaPermisoElement.value : '';
            datos.compensa = compensaElement ? compensaElement.value === '1' : false;
            break;
        }

        case 32: { // Plus de caja (sin importe, vencimiento opcional)
            datos.tipo_plus_caja = document.getElementById('tipo_plus_caja').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_plus_caja').value;
            const fhCaja = document.getElementById('fecha_vigencia_hasta_plus_caja')?.value?.trim();
            datos.fecha_vigencia_hasta = fhCaja || null;
            break;
        }

        case 33: { // Plus de Sub-Encargada (SI/NO, vencimiento opcional)
            datos.aplica_plus = document.getElementById('aplica_plus_sub').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_plus_sub').value;
            const fhSub = document.getElementById('fecha_vigencia_hasta_plus_sub')?.value?.trim();
            datos.fecha_vigencia_hasta = fhSub || null;
            break;
        }

        case 34: { // Plus de Encargada (SI/NO, vencimiento opcional)
            datos.aplica_plus = document.getElementById('aplica_plus_enc').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_plus_enc').value;
            const fhEnc = document.getElementById('fecha_vigencia_hasta_plus_enc')?.value?.trim();
            datos.fecha_vigencia_hasta = fhEnc || null;
            break;
        }

        case 35: { // Premio Local (escala global)
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_premio_local').value;
            datos.escala_premios = [
                {
                    nivel_codigo: 'PRIMER_RANKING',
                    importe_enc_sub: parseFloat(document.getElementById('premio_1ro_enc_sub').value),
                    importe_vend: parseFloat(document.getElementById('premio_1ro_vend').value)
                },
                {
                    nivel_codigo: 'CUMPLE_OBJETIVO',
                    importe_enc_sub: parseFloat(document.getElementById('premio_cumple_enc_sub').value),
                    importe_vend: parseFloat(document.getElementById('premio_cumple_vend').value)
                },
                {
                    nivel_codigo: 'PRIMER_NO_LLEGA',
                    importe_enc_sub: parseFloat(document.getElementById('premio_no_llega_enc_sub').value),
                    importe_vend: parseFloat(document.getElementById('premio_no_llega_vend').value)
                }
            ];
            break;
        }

        case 36: { // Comisión Individual (múltiples sucursales PROPIOS)
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_comision_individual').value;
            datos.comision_sucursales = recopilarSucursalesComision('unica', false).items;
            datos.tipo_comision = 'individual';
            break;
        }

        case 37: { // Comisión sobre Local
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_comision_local').value;
            datos.tiene_tope = document.getElementById('tiene_tope_local').value === '1';
            if (datos.tiene_tope) {
                datos.porcentaje_1 = parseFloat(document.getElementById('porcentaje_1_local').value);
                datos.porcentaje_2 = parseFloat(document.getElementById('porcentaje_2_local').value);
            } else {
                datos.porcentaje_unico = parseFloat(document.getElementById('porcentaje_unico_local').value);
            }
            datos.tipo_comision = 'local';
            break;
        }

        case 38: { // Premios - Ajuste General (importe opcional)
            const impAjRaw = document.getElementById('importe_ajuste_general')?.value?.trim() || '';
            datos.importe = impAjRaw !== '' ? parseFloat(impAjRaw) : null;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_ajuste').value;
            break;
        }

        case 53: { // Reemplazo
            datos.puesto = document.getElementById('puesto_reemplazo').value;
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_reemplazo').value;
            const radioTempReemp = document.getElementById('tipo_reemplazo_temporario');
            const tipoReemplazo = (radioTempReemp && radioTempReemp.checked) ? 'temporario' : 'permanente';
            datos.tipo_reemplazo = tipoReemplazo;
            datos.fecha_vigencia_hasta = (tipoReemplazo === 'temporario')
                ? (document.getElementById('fecha_vigencia_hasta_reemplazo')?.value || '')
                : null;
            break;
        }

        case 54: { // Aumento Salarial
            datos.fecha_vigencia = document.getElementById('fecha_vigencia_aumento').value;
            const radioPorcentaje = document.getElementById('tipo_aumento_porcentaje');
            const radioMonto = document.getElementById('tipo_aumento_monto');
            if (radioMonto && radioMonto.checked) {
                datos.tipo_aumento = 'monto';
                datos.valor_numerico = parseFloat(document.getElementById('monto_aumento').value);
                datos.porcentaje_1 = null;
            } else {
                datos.tipo_aumento = 'porcentaje';
                datos.porcentaje_1 = parseFloat(document.getElementById('porcentaje_aumento').value);
                datos.valor_numerico = null;
            }
            break;
        }
    }

    const modoActivo = window.modoActual || modoActual;
    if (modoActivo === 'unica') {
        const validacionPeriodo = validarPeriodoAplicacionFormulario();
        if (!validacionPeriodo.valido) {
            throw new Error(validacionPeriodo.error || 'Error en el período de aplicación');
        }
        if (validacionPeriodo.periodo) {
            datos.periodo_mes = validacionPeriodo.periodo.mes;
            datos.periodo_anio = validacionPeriodo.periodo.año;
        }
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
    const tipoData = window.tiposNovedadData ? window.tiposNovedadData[tipoNovedadId] : null;
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
    const tipoData = window.tiposNovedadData ? window.tiposNovedadData[tipoNovedadId] : null;
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
    const tipoData = window.tiposNovedadData ? window.tiposNovedadData[tipoNovedadId] : null;
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
                    <strong>Período sugerido:</strong> 
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
                        <strong>Fecha de vigencia sugerida:</strong> Primer día del mes (${fechaInicio})
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
        
        // Filtrar solo tipos activos y ordenar alfabéticamente - CORREGIDO FILTRO
        const tiposActivos = tiposUnicos
            .filter(tipo => {
                // Manejar diferentes formatos del campo activo
                const esActivo = tipo.activo == 1 || tipo.activo === '1' || tipo.activo === true || tipo.activo === 'true';
                return esActivo;
            })
            .sort((a, b) => a.descripcion.localeCompare(b.descripcion, 'es', { sensitivity: 'accent' }));
        
        // Almacenar la información completa globalmente
        window.tiposNovedadData = {};
        tiposUnicos.forEach(tipo => {
            window.tiposNovedadData[tipo.id] = tipo;
        });
        
        // FALLBACK: Si no hay tipos activos, usar todos los tipos disponibles
        let tiposParaUsar = tiposActivos;
        if (tiposActivos.length === 0 && tiposUnicos.length > 0) {
            console.warn('⚠️ No se encontraron tipos activos en modo único, usando todos los tipos disponibles');
            tiposParaUsar = tiposUnicos.sort((a, b) => a.descripcion.localeCompare(b.descripcion, 'es', { sensitivity: 'accent' }));
        }

        const selectTipo = document.getElementById('tipo_novedad');
        if (selectTipo) {
            selectTipo.innerHTML = '<option value="">Buscar tipo de novedad...</option>';
            tiposParaUsar.forEach(tipo => {
                selectTipo.innerHTML += `<option value="${tipo.id}">${tipo.descripcion}</option>`;
            });
            
            // Inicializar Select2 con búsqueda
            inicializarSelect2TiposNovedad(selectTipo);
        }

        console.log('Tipos de novedad cargados (ordenados alfabéticamente):', tiposParaUsar.length);
        console.log('Datos de tipos:', tiposNovedadData);

    } catch (error) {
        console.error('Error cargando tipos de novedad:', error);
        NovedadesApp.mostrarError('Error cargando tipos de novedad');
    }
}

/**
 * Inicializar Select2 para selector de tipos de novedad
 */
function inicializarSelect2TiposNovedad(selectElement) {
    if (!selectElement || typeof $ === 'undefined') {
        console.warn('⚠️ No se puede inicializar Select2 para tipos de novedad:', !selectElement ? 'elemento no encontrado' : 'jQuery no disponible');
        return;
    }
    
    try {
        const $select = $(selectElement);
        
        // Destruir Select2 existente si existe
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
        
        // Configurar Select2 con búsqueda
        $select.select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar tipo de novedad...',
            allowClear: true,
            width: '100%',
            language: {
                inputTooShort: function () {
                    return 'Escriba para buscar tipos de novedad';
                },
                noResults: function () {
                    return 'No se encontraron tipos de novedad';
                },
                searching: function () {
                    return 'Buscando tipos de novedad...';
                }
            },
            // Las opciones ya están cargadas y ordenadas alfabéticamente
            // Select2 mantendrá este orden durante la búsqueda
            sorter: function(data) {
                // Mantener el orden alfabético durante la búsqueda
                return data.sort(function(a, b) {
                    if (a.text && b.text) {
                        return a.text.localeCompare(b.text, 'es', { sensitivity: 'accent' });
                    }
                    return 0;
                });
            }
        });
        
        // Event listener para cuando se selecciona un tipo
        $select.on('select2:select', function (e) {
            const data = e.params.data;
            console.log('🎯 Tipo de novedad seleccionado:', {
                selectId: selectElement.id,
                data: data,
                esModoUnico: selectElement.id === 'tipo_novedad',
                esModoMultiple: selectElement.id.startsWith('tipo_novedad_')
            });
            
            // Remover clases de error
            $(this).removeClass('is-invalid').addClass('is-valid');
            
            // Llamar a la función de cambio de tipo según el contexto
            if (selectElement.id === 'tipo_novedad') {
                // Modo único
                console.log('🔄 Ejecutando onTipoNovedadChangeActualizado para modo único');
                onTipoNovedadChangeActualizado(selectElement);
            } else if (selectElement.id.startsWith('tipo_novedad_')) {
                // Modo múltiple - extraer el ID de la novedad
                const match = selectElement.id.match(/tipo_novedad_(\d+)/);
                console.log('🔍 Match para modo múltiple:', match);
                
                if (match && typeof onTipoNovedadChangeMultiple === 'function') {
                    const novedadId = parseInt(match[1]);
                    console.log('🔄 Ejecutando onTipoNovedadChangeMultiple para novedad:', novedadId);
                    onTipoNovedadChangeMultiple(selectElement, novedadId);
                    
                    // También llamar a toggleInfoButton si existe
                    if (typeof window.toggleInfoButton === 'function') {
                        window.toggleInfoButton(selectElement, novedadId);
                    } else if (typeof toggleInfoButton === 'function') {
                        toggleInfoButton(selectElement, novedadId);
                    }
                } else {
                    console.error('❌ No se pudo ejecutar onTipoNovedadChangeMultiple:', {
                        match: match,
                        functionExists: typeof onTipoNovedadChangeMultiple === 'function'
                    });
                }
            }
        });
        
        // Event listener para cuando se limpia la selección
        $select.on('select2:clear', function (e) {
            $(this).removeClass('is-valid is-invalid');
            
            // Limpiar configuraciones si es modo único
            if (selectElement.id === 'tipo_novedad') {
                ocultarTodasLasConfiguraciones();
                document.getElementById('configuracion-novedad').style.display = 'none';
            }
        });
        
        console.log('✅ Select2 inicializado para tipos de novedad:', selectElement.id);
        
    } catch (error) {
        console.error('❌ Error inicializando Select2 para tipos de novedad:', error);
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
        if (typeof $ !== 'undefined') {
            // Limpiar select de empleados
            if ($('#empleado-select').length) {
                $('#empleado-select').val(null).trigger('change');
            }
            
            // Limpiar select de tipos de novedad (modo único)
            if ($('#tipo_novedad').length && $('#tipo_novedad').hasClass('select2-hidden-accessible')) {
                $('#tipo_novedad').val(null).trigger('change');
            }
            
            // Limpiar selects de tipos de novedad (modo múltiple)
            $('.tipo-novedad-multiple').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).val(null).trigger('change');
                }
            });
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

window.sucursalesPropiosCache = window.sucursalesPropiosCache || null;
window.sucursalesConCasaCentralCache = window.sucursalesConCasaCentralCache || null;

/**
 * Obtener sucursales PROPIOS habilitadas (con caché)
 */
async function obtenerSucursalesPropios() {
    if (Array.isArray(window.sucursalesPropiosCache)) {
        return window.sucursalesPropiosCache;
    }
    const lista = await NovedadesApp.request('get_sucursales');
    window.sucursalesPropiosCache = Array.isArray(lista) ? lista : [];
    return window.sucursalesPropiosCache;
}

/**
 * Obtener todas las sucursales + Casa Central (con caché)
 */
async function obtenerSucursalesConCasaCentral() {
    if (Array.isArray(window.sucursalesConCasaCentralCache)) {
        return window.sucursalesConCasaCentralCache;
    }
    const lista = await NovedadesApp.request('get_sucursales_con_casa_central');
    window.sucursalesConCasaCentralCache = Array.isArray(lista) ? lista : [];
    return window.sucursalesConCasaCentralCache;
}

/**
 * Inicializar selector y botones de Comisión Individual por Sucursales (PROPIOS)
 * Reutilizable entre carga única ('unica') y checklist ('prueba', 'confirmacion')
 */
async function inicializarComisionSucursales(contexto) {
    const selectId = contexto === 'unica' ? 'comision_ind_sucursal_select' : `cl_${contexto}_com_ind_sucursal_select`;
    const btnId = contexto === 'unica' ? 'btn_agregar_suc_com_ind_unica' : `cl_${contexto}_btn_agregar_suc`;
    const selectEl = document.getElementById(selectId);
    const btnEl = document.getElementById(btnId);

    if (selectEl) {
        try {
            const sucursales = await obtenerSucursalesPropios();
            selectEl.innerHTML = '<option value="">Seleccionar sucursal PROPIOS...</option>';
            sucursales.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.numero;
                opt.textContent = s.descripcion;
                opt.setAttribute('data-nombre', s.nombre || s.descripcion);
                selectEl.appendChild(opt);
            });
        } catch (err) {
            console.error('Error cargando sucursales PROPIOS:', err);
        }
    }

    if (btnEl) {
        btnEl.onclick = function() {
            agregarSucursalComision(contexto);
        };
    }
}

/**
 * Agregar fila de sucursal a la tabla de Comisión Individual
 */
function agregarSucursalComision(contexto) {
    const selectId = contexto === 'unica' ? 'comision_ind_sucursal_select' : `cl_${contexto}_com_ind_sucursal_select`;
    const tbodyId = contexto === 'unica' ? 'tbody_comision_ind_unica' : `cl_${contexto}_tbody_com_ind`;
    const selectEl = document.getElementById(selectId);
    const tbodyEl = document.getElementById(tbodyId);

    if (!selectEl || !tbodyEl) return;

    const nro = (selectEl.value || '').trim();
    if (!nro) {
        NovedadesApp.mostrarError('Seleccione una sucursal PROPIOS para agregar');
        return;
    }

    if (tbodyEl.querySelector(`tr[data-nro-sucursal="${CSS.escape(nro)}"]`)) {
        NovedadesApp.mostrarError('Esa sucursal ya fue agregada a la lista');
        return;
    }

    const opt = selectEl.options[selectEl.selectedIndex];
    const nombre = opt ? (opt.getAttribute('data-nombre') || opt.textContent) : nro;

    const filaVacia = tbodyEl.querySelector('.fila-vacia-comision');
    if (filaVacia) filaVacia.remove();

    const tr = document.createElement('tr');
    tr.setAttribute('data-nro-sucursal', nro);
    tr.setAttribute('data-nombre-sucursal', nombre);
    tr.innerHTML = `
        <td class="align-middle fw-semibold">${nombre}</td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" class="form-control com-ind-pct1" step="0.01" min="0.01" max="100" placeholder="Ej: 1.50" required>
                <span class="input-group-text">%</span>
            </div>
        </td>
        <td class="text-center align-middle">
            <div class="form-check form-switch d-inline-block">
                <input class="form-check-input com-ind-tope-chk" type="checkbox" onchange="toggleTopeFilaComision(this)">
            </div>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" class="form-control com-ind-pct2" step="0.01" min="0.01" max="100" placeholder="Sin tope" disabled>
                <span class="input-group-text">%</span>
            </div>
        </td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarFilaComisionSucursal(this, '${tbodyId}')">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbodyEl.appendChild(tr);
    selectEl.value = '';
}

function toggleTopeFilaComision(checkbox) {
    const tr = checkbox.closest('tr');
    if (!tr) return;
    const pct2 = tr.querySelector('.com-ind-pct2');
    if (!pct2) return;
    if (checkbox.checked) {
        pct2.disabled = false;
        pct2.required = true;
        pct2.placeholder = 'Ej: 0.75';
    } else {
        pct2.disabled = true;
        pct2.required = false;
        pct2.value = '';
        pct2.placeholder = 'Sin tope';
        pct2.classList.remove('is-invalid', 'is-valid');
    }
}

function eliminarFilaComisionSucursal(btn, tbodyId) {
    const tr = btn.closest('tr');
    if (tr) tr.remove();
    const tbody = document.getElementById(tbodyId);
    if (tbody && tbody.querySelectorAll('tr[data-nro-sucursal]').length === 0) {
        tbody.innerHTML = '<tr class="fila-vacia-comision"><td colspan="5" class="text-center text-muted py-3">Agregue al menos una sucursal PROPIOS</td></tr>';
    }
}

function recopilarSucursalesComision(contexto, validar = true) {
    const tbodyId = contexto === 'unica' ? 'tbody_comision_ind_unica' : `cl_${contexto}_tbody_com_ind`;
    const tbody = document.getElementById(tbodyId);
    const items = [];
    const errores = [];

    if (!tbody) {
        return { valido: false, items: [], errores: ['No se encontró la tabla de sucursales de comisión'] };
    }

    const filas = tbody.querySelectorAll('tr[data-nro-sucursal]');
    if (filas.length === 0) {
        if (validar) errores.push('Debe agregar al menos una sucursal PROPIOS en Comisión Individual');
        return { valido: false, items: [], errores };
    }

    filas.forEach(tr => {
        const nro = tr.getAttribute('data-nro-sucursal');
        const nombre = tr.getAttribute('data-nombre-sucursal');
        const pct1El = tr.querySelector('.com-ind-pct1');
        const chkEl = tr.querySelector('.com-ind-tope-chk');
        const pct2El = tr.querySelector('.com-ind-pct2');

        const pct1 = parseFloat(pct1El?.value || '0');
        const tieneTope = chkEl && chkEl.checked ? 1 : 0;
        const pct2 = tieneTope ? parseFloat(pct2El?.value || '0') : null;

        if (validar) {
            if (!pct1 || pct1 <= 0 || pct1 > 100) {
                errores.push(`Ingrese un porcentaje válido (0.01 - 100) para ${nombre}`);
                pct1El?.classList.add('is-invalid');
            } else {
                pct1El?.classList.remove('is-invalid');
            }
            if (tieneTope && (!pct2 || pct2 <= 0 || pct2 > 100)) {
                errores.push(`Ingrese un porcentaje post-tope válido (0.01 - 100) para ${nombre}`);
                pct2El?.classList.add('is-invalid');
            } else {
                pct2El?.classList.remove('is-invalid');
            }
        }

        items.push({
            nro_sucursal: nro,
            nombre_sucursal: nombre,
            porcentaje_1: pct1,
            tiene_tope: tieneTope,
            porcentaje_2: pct2
        });
    });

    return {
        valido: errores.length === 0,
        items,
        errores
    };
}

/**
 * Configurar campos dinámicos para comisiones (Comisión sobre Local)
 */
function configurarCamposComision(tipoComision) {
    if (tipoComision === 'individual') {
        inicializarComisionSucursales('unica');
        return;
    }

    if (tipoComision === 'local') {
        const tienTopeSelect = document.getElementById('tiene_tope_local');
        const camposSinTope = document.getElementById('campos_sin_tope_local');
        const camposConTope = document.getElementById('campos_con_tope_local');

        if (tienTopeSelect) {
            tienTopeSelect.onchange = function() {
                const tieneTope = this.value === '1';

                if (tieneTope) {
                    if (camposConTope) {
                        camposConTope.style.display = 'block';
                        camposConTope.querySelectorAll('input').forEach(i => i.setAttribute('required', 'required'));
                    }
                    if (camposSinTope) {
                        camposSinTope.style.display = 'none';
                        camposSinTope.querySelectorAll('input').forEach(i => { i.removeAttribute('required'); i.value = ''; });
                    }
                } else if (this.value === '0') {
                    if (camposSinTope) {
                        camposSinTope.style.display = 'block';
                        camposSinTope.querySelectorAll('input').forEach(i => i.setAttribute('required', 'required'));
                    }
                    if (camposConTope) {
                        camposConTope.style.display = 'none';
                        camposConTope.querySelectorAll('input').forEach(i => { i.removeAttribute('required'); i.value = ''; });
                    }
                } else {
                    if (camposSinTope) {
                        camposSinTope.style.display = 'none';
                        camposSinTope.querySelectorAll('input').forEach(i => { i.removeAttribute('required'); i.value = ''; });
                    }
                    if (camposConTope) {
                        camposConTope.style.display = 'none';
                        camposConTope.querySelectorAll('input').forEach(i => { i.removeAttribute('required'); i.value = ''; });
                    }
                }
            };
        }
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
    const sucursalSelect = document.getElementById('sucursal');
    if (sucursalSelect) {
        sucursalSelect.addEventListener('change', function() {
            const tipoNovedad = parseInt(document.getElementById('tipo_novedad')?.value || '0', 10);
            if (tipoNovedad === 1) {
                configurarCambioSucursal();
            }
        });
    }

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
 * Inicialización específica del formulario
 */
document.addEventListener('DOMContentLoaded', function() {
    cargarSucursales();
    cargarPuestos();
    cargarTiposNovedad();
    configurarValidacionTiempoReal();
    configurarAutocompletadoEmpleado();
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
 * FUNCIONES PARA MANEJO DE MODO ÚNICO Y MODO MÚLTIPLE (CHECKLIST PREDETERMINADO)
 */

function initializarModosCarga() {
    const botonesModoCarga = document.querySelectorAll('.modo-carga-btn');
    botonesModoCarga.forEach(boton => {
        boton.addEventListener('click', function() {
            const modo = this.getAttribute('data-modo');
            seleccionarModoCarga(modo);
        });
    });
}

function seleccionarModoCarga(modo) {
    window.modoActual = modo;
    actualizarIndicadorModo(modo);

    const modoSection = document.getElementById('modo-carga-section');
    if (modoSection) modoSection.style.display = 'none';

    const formNovedad = document.getElementById('form-novedad');
    if (formNovedad) formNovedad.style.display = 'block';

    const btnCambiarModo = document.getElementById('btn-cambiar-modo-top');
    if (btnCambiarModo) btnCambiarModo.style.display = 'inline-block';

    if (modo === 'unica') {
        configurarModoUnico();
    } else if (modo === 'multiple') {
        configurarModoMultiple();
    }
}

function actualizarIndicadorModo(modo) {
    const indicator = document.getElementById('modo-activo-indicator');
    const text = document.getElementById('modo-activo-text');
    if (indicator && text) {
        if (modo === 'unica') {
            text.textContent = 'Modo: Novedad Única';
            indicator.style.backgroundColor = '#0d6efd';
        } else if (modo === 'multiple') {
            text.textContent = 'Modo: Checklist Múltiple';
            indicator.style.backgroundColor = '#198754';
        }
        indicator.style.display = 'block';
    }
}

function volverSeleccionModo() {
    if (typeof limpiarFormularioSinConfirmacion === 'function') {
        limpiarFormularioSinConfirmacion();
    } else {
        document.getElementById('form-novedad')?.reset();
        if (typeof $ !== 'undefined' && $('#empleado-select').length) {
            $('#empleado-select').val(null).trigger('change');
        }
    }

    const multiplesContainer = document.getElementById('novedades-multiples-container');
    if (multiplesContainer) multiplesContainer.style.display = 'none';

    const configGlobal = document.getElementById('configuracion-global-section');
    if (configGlobal) configGlobal.style.display = 'none';

    const form = document.getElementById('form-novedad');
    if (form) form.style.display = 'none';

    const modoSection = document.getElementById('modo-carga-section');
    if (modoSection) modoSection.style.display = 'block';

    const btnCambiar = document.getElementById('btn-cambiar-modo-top');
    if (btnCambiar) btnCambiar.style.display = 'none';

    const indicator = document.getElementById('modo-activo-indicator');
    if (indicator) indicator.style.display = 'none';

    const empSec = document.getElementById('datos-empleado-section');
    if (empSec) empSec.style.display = 'block';
    const ayudaGlobal = document.getElementById('empleado-ayuda-global');
    if (ayudaGlobal) ayudaGlobal.style.display = 'none';

    window.modoActual = null;
}

function configurarModoUnico() {
    const tipoUnica = document.getElementById('tipo-novedad-unica');
    const configNovedad = document.getElementById('configuracion-novedad');
    const obsSec = document.getElementById('observaciones-section');
    const configGlobal = document.getElementById('configuracion-global-section');
    const multCont = document.getElementById('novedades-multiples-container');

    if (tipoUnica) tipoUnica.style.display = 'block';
    if (configNovedad) configNovedad.style.display = 'none';
    if (obsSec) obsSec.style.display = 'block';
    if (configGlobal) configGlobal.style.display = 'none';
    if (multCont) multCont.style.display = 'none';

    setTimeout(() => mostrarPeriodoAplicacion(), 150);

    if (typeof inicializarFormularioOriginal === 'function') {
        inicializarFormularioOriginal();
    }
}

function configurarModoMultiple() {
    const tipoUnica = document.getElementById('tipo-novedad-unica');
    const configNovedad = document.getElementById('configuracion-novedad');
    const obsSec = document.getElementById('observaciones-section');
    if (tipoUnica) tipoUnica.style.display = 'none';
    if (configNovedad) configNovedad.style.display = 'none';
    if (obsSec) obsSec.style.display = 'none';

    // Asegurar que la sección de empleado esté visible y obligatoria en modo checklist
    actualizarVisibilidadEmpleadoPorTipo(0);

    if (typeof ocultarPeriodoAplicacion === 'function') {
        ocultarPeriodoAplicacion();
    }

    const configGlobal = document.getElementById('configuracion-global-section');
    const multiplesContainer = document.getElementById('novedades-multiples-container');
    if (configGlobal) configGlobal.style.display = 'block';
    if (multiplesContainer) multiplesContainer.style.display = 'block';

    cargarOpcionesPeriodo();
    inicializarChecklistMultiple();
}

function cargarOpcionesPeriodo() {
    const selectMes = document.getElementById('periodo_mes_global');
    const selectAnio = document.getElementById('periodo_anio_global');
    if (!selectMes || !selectAnio) return;

    const meses = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    const ahora = new Date();
    const mesActual = ahora.getMonth() + 1;
    const anioActual = ahora.getFullYear();

    selectMes.innerHTML = '<option value="">Seleccionar mes...</option>';
    for (let mes = 1; mes <= 12; mes++) {
        const opt = document.createElement('option');
        opt.value = mes;
        opt.textContent = meses[mes - 1];
        selectMes.appendChild(opt);
    }

    selectAnio.innerHTML = '<option value="">Seleccionar año...</option>';
    for (let anio = anioActual - 1; anio <= anioActual + 2; anio++) {
        const opt = document.createElement('option');
        opt.value = anio;
        opt.textContent = anio;
        selectAnio.appendChild(opt);
    }

    selectMes.value = String(mesActual);
    selectAnio.value = String(anioActual);
}

/**
 * Inicializar Checklist Predeterminado (Propuesta -> Durante la Prueba -> Confirmación)
 */
async function inicializarChecklistMultiple() {
    const bodyPrueba = document.getElementById('cl_bloque_prueba_body');
    const bodyConf = document.getElementById('cl_bloque_confirmacion_body');

    if (bodyPrueba) bodyPrueba.innerHTML = generarHtmlBloqueChecklist('prueba');
    if (bodyConf) bodyConf.innerHTML = generarHtmlBloqueChecklist('confirmacion');

    // Sincronizar visibilidad de bloques con switches de etapa
    const chkPrueba = document.getElementById('cl_incluir_prueba');
    const chkConf = document.getElementById('cl_incluir_confirmacion');

    if (chkPrueba && bodyPrueba) {
        chkPrueba.checked = true;
        bodyPrueba.style.display = 'block';
        chkPrueba.onchange = function() {
            bodyPrueba.style.display = this.checked ? 'block' : 'none';
            actualizarHintFechaFinChecklist();
        };
    }

    if (chkConf && bodyConf) {
        chkConf.checked = false;
        bodyConf.style.display = 'none';
        chkConf.onchange = function() {
            bodyConf.style.display = this.checked ? 'block' : 'none';
            sugerirFechaConfirmacion();
        };
    }

    // Cargar sucursales para Local destino
    try {
        const sucursales = await obtenerSucursalesConCasaCentral();
        const selectDest = document.getElementById('cl_local_destino');
        if (selectDest) {
            selectDest.innerHTML = '<option value="">Sin cambio de sucursal</option>';
            sucursales.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.numero;
                opt.textContent = s.descripcion;
                selectDest.appendChild(opt);
            });
        }
    } catch (e) {
        console.error('Error cargando sucursales para checklist:', e);
    }

    // Cargar puestos desde Tango (TAREA_HABITUAL) para Puesto a prueba y Puesto confirmación
    try {
        const puestos = await NovedadesApp.request('get_puestos');
        const poblarPuestos = (el, placeholder) => {
            if (!el) return;
            el.innerHTML = `<option value="">${placeholder}</option>`;
            (puestos || []).forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.nombre_puesto;
                opt.textContent = p.nombre_puesto;
                el.appendChild(opt);
            });
        };
        const selPrueba = document.getElementById('cl_puesto_prueba');
        const selConf = document.getElementById('cl_confirmacion_puesto');
        poblarPuestos(selPrueba, 'No aplica / Sin período a prueba');
        poblarPuestos(selConf, 'Seleccionar puesto de confirmación...');

        if (selPrueba) {
            selPrueba.onchange = function() {
                actualizarHintFechaFinChecklist();
                if (selConf && !selConf.value && this.value) {
                    selConf.value = this.value;
                }
            };
        }
    } catch (e) {
        console.error('Error cargando puestos para checklist:', e);
    }

    // Fecha fin -> sugerir día siguiente como fecha de confirmación
    const fechaFinEl = document.getElementById('cl_fecha_fin');
    if (fechaFinEl) {
        fechaFinEl.onchange = sugerirFechaConfirmacion;
    }

    // Poblar selects de sucursales PROPIOS para Comisiones Individuales dentro de cada etapa
    inicializarComisionSucursales('prueba');
    inicializarComisionSucursales('confirmacion');

    // Sync local origen y posición actual si ya hay un empleado seleccionado
    const emp = window.NovedadesApp?.empleadoSeleccionado;
    if (emp) {
        const ccDesc = emp.desc_centro_costos || emp.descripcion_centro || '';
        const ccCod = emp.cod_centro_costos || emp.centro_costos || '';
        const clOrig = document.getElementById('cl_local_origen');
        const clPos = document.getElementById('cl_posicion_actual');
        if (clOrig && ccDesc) clOrig.value = `${ccDesc} (${ccCod})`;
        if (clPos) clPos.value = emp.puesto_actual || '';
    }
}

function sugerirFechaConfirmacion() {
    const fechaFinEl = document.getElementById('cl_fecha_fin');
    const fechaConfEl = document.getElementById('cl_confirmacion_fecha');
    if (!fechaConfEl || fechaConfEl.value) return;
    if (fechaFinEl && fechaFinEl.value) {
        const d = new Date(fechaFinEl.value + 'T00:00:00');
        if (!isNaN(d.getTime())) {
            d.setDate(d.getDate() + 1);
            fechaConfEl.value = d.toISOString().split('T')[0];
        }
    }
}

function actualizarHintFechaFinChecklist() {
    const puesto = document.getElementById('cl_puesto_prueba')?.value || '';
    const reemplazoChk = document.getElementById('cl_prueba_reemplazo_chk');
    const hint = document.getElementById('cl_fecha_fin_hint');
    const req = Boolean(puesto || (reemplazoChk && reemplazoChk.checked));
    if (hint) {
        hint.innerHTML = req
            ? '<span class="text-danger fw-semibold">* Obligatoria (período a prueba / reemplazo)</span>'
            : '(obligatoria si tiene puesto a prueba o reemplazo)';
    }
}

/**
 * Generar HTML parametrizado de una etapa del checklist ('prueba' o 'confirmacion')
 */
function generarHtmlBloqueChecklist(etapa) {
    const esPrueba = (etapa === 'prueba');
    const p = `cl_${etapa}`;

    const cabeceraEtapa = esPrueba
        ? `<div class="alert alert-warning py-2 small mb-3">
                <i class="fas fa-calendar-alt me-1"></i>
                Las novedades marcadas en esta etapa tomarán la <strong>Fecha de inicio</strong> de la Propuesta (y Fecha de finalización para Reemplazo).
           </div>`
        : `<div class="row mb-3">
                <div class="col-md-4">
                    <label for="cl_confirmacion_fecha" class="form-label fw-semibold">
                        Fecha de vigencia (Confirmación) <span class="required">*</span>
                    </label>
                    <input type="date" class="form-control" id="cl_confirmacion_fecha">
                    <div class="invalid-feedback">Indique la fecha de confirmación</div>
                </div>
           </div>`;

    const bloquePuestoEtapa = esPrueba
        ? `<div class="col-md-6">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="${p}_reemplazo_chk" onchange="toggleItemChecklist('${p}_reemplazo'); actualizarHintFechaFinChecklist();">
                        <label class="form-check-label fw-bold" for="${p}_reemplazo_chk">Reemplazo (Temporario)</label>
                    </div>
                    <div id="${p}_reemplazo_fields" style="display:none;">
                        <small class="text-muted d-block">
                            Usará el <strong>Puesto a prueba</strong>, <strong>Fecha de inicio</strong> y <strong>Fecha de finalización</strong> definidos en la Propuesta (tipo Temporario).
                        </small>
                    </div>
                </div>
           </div>`
        : `<div class="col-md-6">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="${p}_puesto_chk" onchange="toggleItemChecklist('${p}_puesto')">
                        <label class="form-check-label fw-bold" for="${p}_puesto_chk">Nuevo Puesto (Efectivización)</label>
                    </div>
                    <div id="${p}_puesto_fields" style="display:none;">
                        <div class="mb-2">
                            <label class="form-label small mb-1">Puesto definitivo <span class="required">*</span></label>
                            <select class="form-select form-select-sm" id="cl_confirmacion_puesto">
                                <option value="">Seleccionar puesto...</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small mb-1">Convenio <span class="required">*</span></label>
                            <select class="form-select form-select-sm" id="cl_confirmacion_convenio">
                                <option value="">Seleccionar convenio...</option>
                                <option value="Comercio">Comercio</option>
                                <option value="FC">FC (Fuera de Convenio)</option>
                            </select>
                        </div>
                    </div>
                </div>
           </div>`;

    return `
        ${cabeceraEtapa}
        <div class="row g-3">
            <!-- Cambio de Sucursal -->
            <div class="col-md-6">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="${p}_sucursal_chk" onchange="toggleItemChecklist('${p}_sucursal')">
                        <label class="form-check-label fw-bold" for="${p}_sucursal_chk">Cambio de Sucursal</label>
                    </div>
                    <div id="${p}_sucursal_fields" style="display:none;">
                        <small class="text-muted d-block mb-2">Usará el <strong>Local destino</strong> indicado en Propuesta.</small>
                        <label class="form-label small mb-1">Fecha de vencimiento (opcional)</label>
                        <input type="date" class="form-control form-control-sm" id="${p}_sucursal_hasta">
                        <small class="text-muted">Si se deja vacía, se considera sin vencimiento.</small>
                    </div>
                </div>
            </div>

            ${bloquePuestoEtapa}

            <!-- Plus Encargada -->
            <div class="col-md-4">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="${p}_plus_enc_chk" onchange="toggleItemChecklist('${p}_plus_enc')">
                        <label class="form-check-label fw-bold" for="${p}_plus_enc_chk">Plus Encargada</label>
                    </div>
                    <div id="${p}_plus_enc_fields" style="display:none;">
                        <select class="form-select form-select-sm mb-2" id="${p}_plus_enc_val">
                            <option value="">Seleccionar SI / NO...</option>
                            <option value="1">SI</option>
                            <option value="0">NO</option>
                        </select>
                        <label class="form-label small mb-1">Fecha vencimiento (opc.)</label>
                        <input type="date" class="form-control form-control-sm" id="${p}_plus_enc_hasta">
                        <small class="text-muted">Si se deja vacía, es sin vencimiento.</small>
                    </div>
                </div>
            </div>

            <!-- Plus Sub-Encargada -->
            <div class="col-md-4">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="${p}_plus_sub_chk" onchange="toggleItemChecklist('${p}_plus_sub')">
                        <label class="form-check-label fw-bold" for="${p}_plus_sub_chk">Plus Sub-Encargada</label>
                    </div>
                    <div id="${p}_plus_sub_fields" style="display:none;">
                        <select class="form-select form-select-sm mb-2" id="${p}_plus_sub_val">
                            <option value="">Seleccionar SI / NO...</option>
                            <option value="1">SI</option>
                            <option value="0">NO</option>
                        </select>
                        <small class="text-info d-block mb-1"><i class="fas fa-info-circle me-1"></i>Equivale al Plus de Caja.</small>
                        <label class="form-label small mb-1">Fecha vencimiento (opc.)</label>
                        <input type="date" class="form-control form-control-sm" id="${p}_plus_sub_hasta">
                        <small class="text-muted">Si se deja vacía, es sin vencimiento.</small>
                    </div>
                </div>
            </div>

            <!-- Plus de Caja -->
            <div class="col-md-4">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="${p}_plus_caja_chk" onchange="toggleItemChecklist('${p}_plus_caja')">
                        <label class="form-check-label fw-bold" for="${p}_plus_caja_chk">Plus de Caja</label>
                    </div>
                    <div id="${p}_plus_caja_fields" style="display:none;">
                        <select class="form-select form-select-sm mb-2" id="${p}_plus_caja_val">
                            <option value="">Seleccionar tipo...</option>
                            <option value="recibo">Recibo</option>
                            <option value="premio">Premio</option>
                        </select>
                        <label class="form-label small mb-1">Fecha vencimiento (opc.)</label>
                        <input type="date" class="form-control form-control-sm" id="${p}_plus_caja_hasta">
                        <small class="text-muted">Si se deja vacía, es sin vencimiento.</small>
                    </div>
                </div>
            </div>

            <!-- Comisiones Individual -->
            <div class="col-12">
                <div class="border rounded p-3 bg-light-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="${p}_com_ind_chk" onchange="toggleItemChecklist('${p}_com_ind')">
                            <label class="form-check-label fw-bold" for="${p}_com_ind_chk">Comisiones Individual</label>
                        </div>
                        <small class="text-muted">Porcentaje simple o por sucursales PROPIOS (Supervisoras)</small>
                    </div>
                    <div id="${p}_com_ind_fields" style="display:none;">
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Modalidad</label>
                                <select class="form-select form-select-sm" id="${p}_com_ind_modo" onchange="toggleModoComIndChecklist('${etapa}')">
                                    <option value="simple">Porcentaje único (Vendedora / Encargada)</option>
                                    <option value="sucursales">Por sucursales PROPIOS (Supervisora)</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="${p}_com_ind_simple_wrap">
                                <label class="form-label small mb-1">Porcentaje (%)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" id="${p}_com_ind_pct" step="0.01" min="0.01" max="100" placeholder="Ej: 1.00">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        <div id="${p}_com_ind_sucursales_wrap" style="display:none;">
                            <div class="input-group input-group-sm mb-2">
                                <select class="form-select" id="${p}_com_ind_sucursal_select">
                                    <option value="">Seleccionar sucursal PROPIOS...</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="${p}_btn_agregar_suc">
                                    <i class="fas fa-plus me-1"></i>Agregar sucursal
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered bg-white mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sucursal (PROPIOS)</th>
                                            <th style="width:130px;">% Comisión</th>
                                            <th style="width:90px;" class="text-center">¿Tope?</th>
                                            <th style="width:130px;">% Post-Tope</th>
                                            <th style="width:60px;" class="text-center">Quitar</th>
                                        </tr>
                                    </thead>
                                    <tbody id="${p}_tbody_com_ind">
                                        <tr class="fila-vacia-comision">
                                            <td colspan="5" class="text-center text-muted py-2">Agregue al menos una sucursal PROPIOS</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comisiones Local -->
            <div class="col-md-6">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="${p}_com_local_chk" onchange="toggleItemChecklist('${p}_com_local')">
                        <label class="form-check-label fw-bold" for="${p}_com_local_chk">Comisiones Local</label>
                    </div>
                    <div id="${p}_com_local_fields" style="display:none;">
                        <select class="form-select form-select-sm mb-2" id="${p}_com_local_tope" onchange="toggleTopeComLocalChecklist('${etapa}')">
                            <option value="">Seleccionar si tiene tope...</option>
                            <option value="1">Con Tope</option>
                            <option value="0">Sin Tope</option>
                        </select>
                        <div id="${p}_com_local_contope_wrap" style="display:none;">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">% Antes tope</label>
                                    <input type="number" class="form-control form-control-sm" id="${p}_com_local_p1" step="0.05" min="0.05" max="100" placeholder="0.30 - 1.00">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">% Post-tope</label>
                                    <input type="number" class="form-control form-control-sm" id="${p}_com_local_p2" step="0.05" min="0.05" max="100" placeholder="0.15 - 0.50">
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-info-circle me-1"></i>Guía orientativa: 0,30%-1,00% y 0,15%-0,50% · Rango de 0,05%.
                            </small>
                        </div>
                        <div id="${p}_com_local_sintope_wrap" style="display:none;">
                            <label class="form-label small mb-1">% Único sobre local</label>
                            <input type="number" class="form-control form-control-sm" id="${p}_com_local_pu" step="0.05" min="0.05" max="100" placeholder="Ej: 0.50">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Premios Obj Loc -->
            <div class="col-md-6">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="${p}_premio_loc_chk" onchange="toggleItemChecklist('${p}_premio_loc')">
                        <label class="form-check-label fw-bold" for="${p}_premio_loc_chk">Premios Obj Loc</label>
                    </div>
                    <div id="${p}_premio_loc_fields" style="display:none;">
                        <small class="text-muted d-block mb-2">Categoría aplicable para el empleado:</small>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="${p}_premio_loc_rol" id="${p}_premio_vend" value="Vendedora">
                            <label class="form-check-label" for="${p}_premio_vend">Vendedora</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="${p}_premio_loc_rol" id="${p}_premio_enc" value="Encargada">
                            <label class="form-check-label" for="${p}_premio_enc">Encargada</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Premio Ajuste Gral -->
            <div class="col-md-6">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="${p}_ajuste_gral_chk" onchange="toggleItemChecklist('${p}_ajuste_gral')">
                        <label class="form-check-label fw-bold" for="${p}_ajuste_gral_chk">Premio Ajuste Gral.</label>
                    </div>
                    <div id="${p}_ajuste_gral_fields" style="display:none;">
                        <select class="form-select form-select-sm mb-2" id="${p}_ajuste_gral_val">
                            <option value="">Seleccionar SI / NO...</option>
                            <option value="1">SI</option>
                            <option value="0">NO</option>
                        </select>
                        <label class="form-label small mb-1">Importe (opcional)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="${p}_ajuste_gral_imp" step="0.01" min="0" placeholder="Opcional">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sueldo Básico -->
            <div class="col-md-6">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="${p}_sueldo_basico_chk" onchange="toggleItemChecklist('${p}_sueldo_basico')">
                        <label class="form-check-label fw-bold" for="${p}_sueldo_basico_chk">Sueldo Básico (Escala)</label>
                    </div>
                    <div id="${p}_sueldo_basico_fields" style="display:none;">
                        <small class="text-muted d-block mb-2">Escala salarial aplicable:</small>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="${p}_sueldo_basico_esc" id="${p}_basico_vend" value="Vendedora">
                            <label class="form-check-label" for="${p}_basico_vend">Vendedora</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="${p}_sueldo_basico_esc" id="${p}_basico_enc" value="Encargada">
                            <label class="form-check-label" for="${p}_basico_enc">Encargada</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function toggleItemChecklist(baseId) {
    const chk = document.getElementById(`${baseId}_chk`);
    const fields = document.getElementById(`${baseId}_fields`);
    if (fields) {
        fields.style.display = (chk && chk.checked) ? 'block' : 'none';
    }
}

function toggleModoComIndChecklist(etapa) {
    const modo = document.getElementById(`cl_${etapa}_com_ind_modo`)?.value || 'simple';
    const simpleWrap = document.getElementById(`cl_${etapa}_com_ind_simple_wrap`);
    const sucWrap = document.getElementById(`cl_${etapa}_com_ind_sucursales_wrap`);
    if (simpleWrap) simpleWrap.style.display = modo === 'simple' ? 'block' : 'none';
    if (sucWrap) sucWrap.style.display = modo === 'sucursales' ? 'block' : 'none';
}

function toggleTopeComLocalChecklist(etapa) {
    const val = document.getElementById(`cl_${etapa}_com_local_tope`)?.value || '';
    const conWrap = document.getElementById(`cl_${etapa}_com_local_contope_wrap`);
    const sinWrap = document.getElementById(`cl_${etapa}_com_local_sintope_wrap`);
    if (conWrap) conWrap.style.display = val === '1' ? 'block' : 'none';
    if (sinWrap) sinWrap.style.display = val === '0' ? 'block' : 'none';
}

function resetearChecklistMultiple() {
    if (window.modoActual === 'multiple') {
        inicializarChecklistMultiple();
    }
}

/**
 * Recopilar y validar todas las novedades marcadas en una etapa del checklist
 */
function recopilarEtapaChecklist(etapa, ctx) {
    const p = `cl_${etapa}`;
    const labelEtapa = etapa === 'prueba' ? 'Durante la Prueba' : 'Confirmación';
    const fechaVigencia = etapa === 'prueba'
        ? (document.getElementById('cl_fecha_inicio')?.value || '')
        : (document.getElementById('cl_confirmacion_fecha')?.value || '');

    const errores = [];
    const novedades = [];

    if (!fechaVigencia) {
        errores.push(`La fecha de vigencia para "${labelEtapa}" es obligatoria`);
        return { errores, novedades };
    }

    const baseNovedad = (tipoNovedad) => ({
        legajo: ctx.legajo,
        nombre: ctx.nombre,
        apellido: ctx.apellido,
        tipo_novedad: tipoNovedad,
        fecha_vigencia: fechaVigencia,
        periodo_mes: ctx.periodoMes,
        periodo_anio: ctx.periodoAnio,
        etapa_multiple: etapa,
        observaciones: ctx.observaciones
    });

    // 1. Cambio de Sucursal
    if (document.getElementById(`${p}_sucursal_chk`)?.checked) {
        const dest = (document.getElementById('cl_local_destino')?.value || '').trim();
        const fh = (document.getElementById(`${p}_sucursal_hasta`)?.value || '').trim() || null;
        if (!dest) {
            errores.push(`[${labelEtapa}] Seleccione el "Local destino" en la Propuesta para Cambio de Sucursal`);
        } else if (fh && fh < fechaVigencia) {
            errores.push(`[${labelEtapa}] La fecha de vencimiento de Cambio de Sucursal no puede ser anterior a la vigencia`);
        } else {
            const nov = baseNovedad(1);
            nov.nueva_sucursal = dest;
            nov.sucursal_actual = ctx.sucursalNumero || '';
            nov.fecha_vigencia_hasta = fh;
            novedades.push(nov);
        }
    }

    // 53. Reemplazo (solo Prueba)
    if (etapa === 'prueba' && document.getElementById(`${p}_reemplazo_chk`)?.checked) {
        const puestoPrueba = (document.getElementById('cl_puesto_prueba')?.value || '').trim();
        const fechaFin = (document.getElementById('cl_fecha_fin')?.value || '').trim();
        if (!puestoPrueba) {
            errores.push(`[${labelEtapa}] Seleccione el "Puesto a prueba" en la Propuesta para Reemplazo`);
        }
        if (!fechaFin) {
            errores.push(`[${labelEtapa}] Complete la "Fecha de finalización" en la Propuesta para Reemplazo`);
        } else if (fechaFin < fechaVigencia) {
            errores.push(`[${labelEtapa}] La fecha de finalización no puede ser anterior a la fecha de inicio`);
        }
        if (puestoPrueba && fechaFin && fechaFin >= fechaVigencia) {
            const nov = baseNovedad(53);
            nov.puesto = puestoPrueba;
            nov.tipo_reemplazo = 'temporario';
            nov.fecha_vigencia_hasta = fechaFin;
            novedades.push(nov);
        }
    }

    // 2. Nuevo Puesto (solo Confirmación)
    if (etapa === 'confirmacion' && document.getElementById(`${p}_puesto_chk`)?.checked) {
        const puestoConf = (document.getElementById('cl_confirmacion_puesto')?.value || '').trim();
        const convenioConf = (document.getElementById('cl_confirmacion_convenio')?.value || '').trim();
        if (!puestoConf) errores.push(`[${labelEtapa}] Seleccione el Nuevo Puesto`);
        if (!convenioConf) errores.push(`[${labelEtapa}] Seleccione el Convenio (Comercio / FC)`);
        if (puestoConf && convenioConf) {
            const nov = baseNovedad(2);
            nov.puesto = puestoConf;
            nov.tipo_nuevo_puesto = 'permanente';
            nov.convenio = convenioConf;
            novedades.push(nov);
        }
    }

    // 34. Plus Encargada (SI/NO)
    if (document.getElementById(`${p}_plus_enc_chk`)?.checked) {
        const val = document.getElementById(`${p}_plus_enc_val`)?.value;
        const fh = (document.getElementById(`${p}_plus_enc_hasta`)?.value || '').trim() || null;
        if (val !== '1' && val !== '0') {
            errores.push(`[${labelEtapa}] Indique SI o NO en Plus Encargada`);
        } else if (fh && fh < fechaVigencia) {
            errores.push(`[${labelEtapa}] Fecha vencimiento inválida en Plus Encargada`);
        } else {
            const nov = baseNovedad(34);
            nov.aplica_plus = val;
            nov.fecha_vigencia_hasta = fh;
            novedades.push(nov);
        }
    }

    // 33. Plus Sub-Encargada (SI/NO)
    if (document.getElementById(`${p}_plus_sub_chk`)?.checked) {
        const val = document.getElementById(`${p}_plus_sub_val`)?.value;
        const fh = (document.getElementById(`${p}_plus_sub_hasta`)?.value || '').trim() || null;
        if (val !== '1' && val !== '0') {
            errores.push(`[${labelEtapa}] Indique SI o NO en Plus Sub-Encargada`);
        } else if (fh && fh < fechaVigencia) {
            errores.push(`[${labelEtapa}] Fecha vencimiento inválida en Plus Sub-Encargada`);
        } else {
            const nov = baseNovedad(33);
            nov.aplica_plus = val;
            nov.fecha_vigencia_hasta = fh;
            novedades.push(nov);
        }
    }

    // 32. Plus de Caja (Recibo / Premio, sin importe)
    if (document.getElementById(`${p}_plus_caja_chk`)?.checked) {
        const val = document.getElementById(`${p}_plus_caja_val`)?.value;
        const fh = (document.getElementById(`${p}_plus_caja_hasta`)?.value || '').trim() || null;
        if (!val) {
            errores.push(`[${labelEtapa}] Seleccione Recibo o Premio en Plus de Caja`);
        } else if (fh && fh < fechaVigencia) {
            errores.push(`[${labelEtapa}] Fecha vencimiento inválida en Plus de Caja`);
        } else {
            const nov = baseNovedad(32);
            nov.tipo_plus_caja = val;
            nov.fecha_vigencia_hasta = fh;
            novedades.push(nov);
        }
    }

    // 36. Comisiones Individual
    if (document.getElementById(`${p}_com_ind_chk`)?.checked) {
        const modo = document.getElementById(`${p}_com_ind_modo`)?.value || 'simple';
        const nov = baseNovedad(36);
        nov.tipo_comision = 'individual';
        if (modo === 'simple') {
            const pct = parseFloat(document.getElementById(`${p}_com_ind_pct`)?.value || '0');
            if (!pct || pct <= 0 || pct > 100) {
                errores.push(`[${labelEtapa}] Ingrese un porcentaje válido en Comisiones Individual`);
            } else {
                nov.porcentaje_individual = pct;
                novedades.push(nov);
            }
        } else {
            const res = recopilarSucursalesComision(etapa, true);
            if (!res.valido) {
                res.errores.forEach(e => errores.push(`[${labelEtapa}] ${e}`));
            } else {
                nov.comision_sucursales = res.items;
                novedades.push(nov);
            }
        }
    }

    // 37. Comisiones Local
    if (document.getElementById(`${p}_com_local_chk`)?.checked) {
        const top = document.getElementById(`${p}_com_local_tope`)?.value;
        const nov = baseNovedad(37);
        nov.tipo_comision = 'local';
        if (top === '1') {
            const p1 = parseFloat(document.getElementById(`${p}_com_local_p1`)?.value || '0');
            const p2 = parseFloat(document.getElementById(`${p}_com_local_p2`)?.value || '0');
            if (!p1 || p1 <= 0 || p1 > 100 || !p2 || p2 <= 0 || p2 > 100) {
                errores.push(`[${labelEtapa}] Ingrese ambos porcentajes (Con Tope) en Comisiones Local`);
            } else {
                nov.tiene_tope = true;
                nov.porcentaje_1 = p1;
                nov.porcentaje_2 = p2;
                novedades.push(nov);
            }
        } else if (top === '0') {
            const pu = parseFloat(document.getElementById(`${p}_com_local_pu`)?.value || '0');
            if (!pu || pu <= 0 || pu > 100) {
                errores.push(`[${labelEtapa}] Ingrese el porcentaje único en Comisiones Local`);
            } else {
                nov.tiene_tope = false;
                nov.porcentaje_unico = pu;
                novedades.push(nov);
            }
        } else {
            errores.push(`[${labelEtapa}] Seleccione Con Tope o Sin Tope en Comisiones Local`);
        }
    }

    // 35. Premios Obj Loc (por empleado: Vendedora / Encargada)
    if (document.getElementById(`${p}_premio_loc_chk`)?.checked) {
        const rolEl = document.querySelector(`input[name="${p}_premio_loc_rol"]:checked`);
        if (!rolEl) {
            errores.push(`[${labelEtapa}] Seleccione Vendedora o Encargada en Premios Obj Loc`);
        } else {
            const nov = baseNovedad(35);
            nov.es_global = 0;
            nov.aplica_vendedora = rolEl.value === 'Vendedora' ? 1 : 0;
            nov.aplica_encargada = rolEl.value === 'Encargada' ? 1 : 0;
            novedades.push(nov);
        }
    }

    // 38. Premio Ajuste General (SI/NO + importe opcional)
    if (document.getElementById(`${p}_ajuste_gral_chk`)?.checked) {
        const val = document.getElementById(`${p}_ajuste_gral_val`)?.value;
        const impRaw = (document.getElementById(`${p}_ajuste_gral_imp`)?.value || '').trim();
        if (val !== '1' && val !== '0') {
            errores.push(`[${labelEtapa}] Indique SI o NO en Premio Ajuste Gral.`);
        } else {
            const nov = baseNovedad(38);
            nov.aplica_plus = val;
            if (impRaw !== '') {
                const imp = parseFloat(impRaw);
                if (isNaN(imp) || imp <= 0) {
                    errores.push(`[${labelEtapa}] Si indica importe en Premio Ajuste Gral., debe ser mayor a 0`);
                } else {
                    nov.importe = imp;
                }
            }
            if (errores.length === 0 || nov.aplica_plus !== undefined) {
                novedades.push(nov);
            }
        }
    }

    // 54. Sueldo Básico (escala Vendedora / Encargada)
    if (document.getElementById(`${p}_sueldo_basico_chk`)?.checked) {
        const escEl = document.querySelector(`input[name="${p}_sueldo_basico_esc"]:checked`);
        if (!escEl) {
            errores.push(`[${labelEtapa}] Seleccione Vendedora o Encargada en Sueldo Básico`);
        } else {
            const nov = baseNovedad(54);
            nov.tipo_aumento = 'basico';
            nov.escala_sueldo_basico = escEl.value;
            novedades.push(nov);
        }
    }

    return { errores, novedades };
}

async function enviarFormularioMultiple(event) {
    event.preventDefault();
    if (window.modoActual === 'unica') {
        return await enviarFormulario(event);
    } else if (window.modoActual === 'multiple') {
        return await enviarFormularioModoMultiple();
    }
}

async function enviarFormularioModoMultiple() {
    // 1. Validar empleado y período global
    const legajo = (document.getElementById('legajo')?.value || '').trim();
    const periodoMes = parseInt(document.getElementById('periodo_mes_global')?.value || '0', 10);
    const periodoAnio = parseInt(document.getElementById('periodo_anio_global')?.value || '0', 10);
    const emp = window.NovedadesApp?.empleadoSeleccionado;

    const errores = [];
    if (!legajo || !emp || !emp.nombre || !emp.apellido) {
        errores.push('Debe seleccionar un empleado');
        document.getElementById('empleado-select')?.classList.add('is-invalid');
    }
    if (!periodoMes || !periodoAnio) {
        errores.push('Debe seleccionar el período (mes y año)');
    }

    const incPrueba = document.getElementById('cl_incluir_prueba')?.checked;
    const incConf = document.getElementById('cl_incluir_confirmacion')?.checked;
    if (!incPrueba && !incConf) {
        errores.push('Debe habilitar al menos una etapa (Durante la Prueba o Confirmación)');
    }

    const ctx = {
        legajo,
        nombre: emp?.nombre || '',
        apellido: emp?.apellido || '',
        sucursalNumero: emp?.sucursal_numero || '',
        periodoMes,
        periodoAnio,
        observaciones: (document.getElementById('cl_observaciones')?.value || '').trim()
    };

    const novedades = [];
    if (incPrueba) {
        const resP = recopilarEtapaChecklist('prueba', ctx);
        errores.push(...resP.errores);
        novedades.push(...resP.novedades);
    }
    if (incConf) {
        const resC = recopilarEtapaChecklist('confirmacion', ctx);
        errores.push(...resC.errores);
        novedades.push(...resC.novedades);
    }

    if (errores.length > 0) {
        NovedadesApp.mostrarError(errores.join('<br>'));
        return;
    }

    if (novedades.length === 0) {
        NovedadesApp.mostrarError('Marque al menos un concepto del checklist para generar novedades');
        return;
    }

    const btnGuardar = document.getElementById('btn-guardar');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando lote...';

    try {
        await NovedadesApp.request('crear_novedades_lote', { novedades }, 'POST');

        const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
        modal.show();
        limpiarFormularioMultiple();
    } catch (error) {
        console.error('Error al guardar lote de novedades:', error);
        NovedadesApp.mostrarError(error.message || 'Error al guardar las novedades');
    } finally {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = textoOriginal;
    }
}

function limpiarFormularioMultiple() {
    window.contadorNovedades = 0;
    window.novedadesData = [];
    window.modoActual = null;

    const indicator = document.getElementById('modo-activo-indicator');
    if (indicator) indicator.style.display = 'none';

    const multCont = document.getElementById('novedades-multiples-container');
    if (multCont) multCont.style.display = 'none';

    const cfgGlobal = document.getElementById('configuracion-global-section');
    if (cfgGlobal) cfgGlobal.style.display = 'none';

    const modoSection = document.getElementById('modo-carga-section');
    if (modoSection) modoSection.style.display = 'block';

    const form = document.getElementById('form-novedad');
    if (form) {
        form.style.display = 'none';
        form.reset();
    }

    if (typeof $ !== 'undefined' && $('#empleado-select').length) {
        $('#empleado-select').val(null).trigger('change');
    }

    if (typeof NovedadesApp !== 'undefined') {
        NovedadesApp.empleadoSeleccionado = null;
    }
}

/**
 * Funciones para manejo del período de aplicación
 */

/**
 * Mostrar el período de aplicación para modo único
 */
function mostrarPeriodoAplicacion(tipoNovedadId = null) {
    console.log('📅 Mostrando período de aplicación para tipo:', tipoNovedadId);
    
    // Solo mostrar en modo único (revisar tanto modoActual como window.modoActual)
    const modoActivo = window.modoActual || modoActual;
    if (modoActivo && modoActivo !== 'unica') {
        console.log('📅 No se muestra período personalizado - modo múltiple usa configuración global');
        return;
    }
    
    const seccionPeriodo = document.getElementById('periodo-aplicacion-section');
    const contenedorPeriodo = document.getElementById('periodo-aplicacion-container');
    
    if (!seccionPeriodo || !contenedorPeriodo) {
        console.error('📅 No se encontraron elementos del período de aplicación');
        console.error('seccionPeriodo:', seccionPeriodo);
        console.error('contenedorPeriodo:', contenedorPeriodo);
        return;
    }
    
    // Mostrar la sección
    seccionPeriodo.style.display = 'block';
    
    // Insertar el select de período directamente
    if (typeof crearSelectPeriodo !== 'undefined') {
        // Limpiar contenedor
        contenedorPeriodo.innerHTML = '';
        
        // Obtener período sugerido según el tipo de novedad
        const periodoSugerido = tipoNovedadId && typeof obtenerPeriodoSugerido !== 'undefined' ? 
            obtenerPeriodoSugerido(tipoNovedadId) : 
            null;
        
        // Insertar el select usando la función del archivo periodo_seleccionable.js
        contenedorPeriodo.innerHTML = crearSelectPeriodo(periodoSugerido);
        
        // Aplicar Select2 si está disponible
        setTimeout(() => {
            if (typeof $ !== 'undefined' && $.fn.select2) {
                const selectElement = $('#periodo_aplicacion');
                if (selectElement.length) {
                    selectElement.select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Seleccione período...',
                        allowClear: false
                    });
                }
            }
        }, 100);
        
        console.log('📅 Período de aplicación insertado correctamente');
    } else {
        console.error('📅 Función crearSelectPeriodo no disponible, usando fallback');
        // Fallback: crear select básico
        contenedorPeriodo.innerHTML = crearSelectPeriodoBasico();
    }
}

/**
 * Ocultar el período de aplicación
 */
function ocultarPeriodoAplicacion() {
    console.log('📅 Ocultando período de aplicación');
    
    const seccionPeriodo = document.getElementById('periodo-aplicacion-section');
    if (seccionPeriodo) {
        seccionPeriodo.style.display = 'none';
    }
}

/**
 * Crear select de período básico como fallback
 */
function crearSelectPeriodoBasico() {
    const fechaActual = new Date();
    const mesActual = fechaActual.getMonth() + 1;
    const añoActual = fechaActual.getFullYear();
    
    let html = `
        <div class="form-group mb-3" id="periodo-aplicacion-group">
            <label for="periodo_aplicacion" class="form-label">
                <i class="fas fa-calendar-alt me-2"></i>
                Período de Aplicación
            </label>
            <select class="form-select" id="periodo_aplicacion" name="periodo_aplicacion" required>
    `;
    
    // Generar opciones de -1 a +11 meses
    for (let i = -1; i <= 11; i++) {
        const fecha = new Date(añoActual, mesActual - 1 + i, 1);
        const mes = fecha.getMonth() + 1;
        const año = fecha.getFullYear();
        const value = `${mes}-${año}`;
        const selected = i === 0 ? 'selected' : '';
        const nombreMes = obtenerNombreMesBasico(mes);
        
        html += `<option value="${value}" ${selected}>${nombreMes} ${año}</option>`;
    }
    
    html += `
            </select>
            <div class="form-text">
                <i class="fas fa-info-circle me-1"></i>
                Por defecto se aplica el período en curso, antes de la fecha de corte, pero puede modificarse.
            </div>
        </div>
    `;
    
    return html;
}

/**
 * Obtener nombre del mes básico
 */
function obtenerNombreMesBasico(numeroMes) {
    const meses = [
        '', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    return meses[numeroMes] || 'Mes inválido';
}

/**
 * Validar período de aplicación antes de enviar formulario
 */
function validarPeriodoAplicacionFormulario() {
    // Solo validar en modo único
    const modoActivo = window.modoActual || modoActual;
    if (modoActivo !== 'unica') {
        return { valido: true };
    }
    
    // Intentar usar la función avanzada primero
    if (typeof validarPeriodoSeleccionado !== 'undefined') {
        return validarPeriodoSeleccionado();
    }
    
    // Fallback: validación básica
    const select = document.getElementById('periodo_aplicacion');
    if (!select) {
        return { valido: false, error: 'No se encontró el campo de período' };
    }
    
    const valor = select.value;
    if (!valor) {
        return { valido: false, error: 'Debe seleccionar un período de aplicación' };
    }
    
    const [mes, año] = valor.split('-').map(Number);
    if (!mes || !año || mes < 1 || mes > 12) {
        return { valido: false, error: 'Formato de período inválido' };
    }
    
    return {
        valido: true,
        periodo: { mes: mes, año: año, value: valor }
    };
}


// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializarModosCarga();

    const form = document.getElementById('form-novedad');
    if (form) {
        form.removeEventListener('submit', enviarFormulario);
        form.addEventListener('submit', enviarFormularioMultiple);
    }
});

window.initializarModosCarga = initializarModosCarga;
window.seleccionarModoCarga = seleccionarModoCarga;
window.resetearChecklistMultiple = resetearChecklistMultiple;

/**
 * Funcionalidad del botón de información de tipo de novedad
 */
const tiposNovedadInfo = {
    1: {
        nombre: "Cambio de Sucursal / Centro de Costos",
        descripcion: "Registra el traslado a otra sucursal con fecha de vigencia y fecha de vencimiento opcional."
    },
    2: {
        nombre: "Nuevo Puesto",
        descripcion: "Modifica el puesto habitual del empleado (permanente o temporario). Los puestos se obtienen desde Tango."
    },
    4: {
        nombre: "Ajuste de Premios",
        descripcion: "Registra un ajuste de premios en el período indicado (el importe puede dejarse vacío)."
    },
    5: {
        nombre: "Horas Extras",
        descripcion: "Informa cantidad de horas extras trabajadas con su fecha de vigencia."
    },
    6: {
        nombre: "Horas Adicionales",
        descripcion: "Informa cantidad de horas adicionales trabajadas con su fecha de vigencia."
    },
    7: {
        nombre: "Permisos",
        descripcion: "Registra un día de permiso especificando si compensa o no."
    },
    32: {
        nombre: "Plus de Caja",
        descripcion: "Asigna el tipo de Plus de Caja (Recibo o Premio), sin importe y con fecha de vencimiento opcional."
    },
    33: {
        nombre: "Plus de Sub-Encargada",
        descripcion: "Indica SI o NO aplica Plus de Sub-Encargada (equivale al Plus de Caja), con vencimiento opcional."
    },
    34: {
        nombre: "Plus de Encargada",
        descripcion: "Indica SI o NO aplica Plus de Encargada, con fecha de vencimiento opcional."
    },
    35: {
        nombre: "Premio Local",
        descripcion: "Define la escala general de premios para todos los locales en 3 niveles (1ro en ranking, Cumple objetivo, 1ro que no llega) para Encargada/Sub y Vendedora."
    },
    36: {
        nombre: "Comisión Individual",
        descripcion: "Configura comisiones individuales para Supervisoras sobre múltiples sucursales PROPIOS con porcentaje y tope opcional por sucursal."
    },
    37: {
        nombre: "Comisión sobre Local",
        descripcion: "Configura comisión sobre ventas del local, con o sin tope."
    },
    38: {
        nombre: "Premios - Ajuste General",
        descripcion: "Registra un ajuste general de premios (importe no obligatorio)."
    },
    53: {
        nombre: "Reemplazo",
        descripcion: "Registra reemplazo de puesto (permanente o temporario con fecha fin)."
    },
    54: {
        nombre: "Aumento Salarial",
        descripcion: "Registra un aumento salarial por porcentaje o monto fijo."
    }
};

function toggleInfoButton(selectElement, mode) {
    try {
        if (!selectElement) return;
        const selectedValue = selectElement.value;
        const infoBtn = document.getElementById(`infoBtn-${mode}`);
        if (!infoBtn) return;

        if (selectedValue && selectedValue !== '') {
            infoBtn.classList.add('active');
            infoBtn.style.position = 'absolute';
            infoBtn.style.top = '8px';
            infoBtn.style.right = '8px';
            infoBtn.style.width = '30px';
            infoBtn.style.height = '30px';
            infoBtn.style.borderRadius = '50%';
            infoBtn.style.border = 'none';
            infoBtn.style.background = 'linear-gradient(135deg, #0d6efd, #4285f4)';
            infoBtn.style.color = 'white';
            infoBtn.style.display = 'flex';
            infoBtn.style.alignItems = 'center';
            infoBtn.style.justifyContent = 'center';
            infoBtn.style.fontSize = '0.8rem';
            infoBtn.style.cursor = 'pointer';
            infoBtn.style.zIndex = '100';
            infoBtn.style.boxShadow = '0 3px 10px rgba(13, 110, 253, 0.4)';
            infoBtn.style.fontWeight = '600';
            infoBtn.style.visibility = 'visible';
            infoBtn.style.opacity = '1';

            const parentContainer = selectElement.closest('.position-relative') ||
                selectElement.closest('.col-md-8') ||
                selectElement.parentElement;
            if (parentContainer) {
                parentContainer.style.position = 'relative';
            }
        } else {
            infoBtn.classList.remove('active');
            infoBtn.style.display = 'none';
            infoBtn.style.visibility = 'hidden';
            infoBtn.style.opacity = '0';
        }
    } catch (error) {
        console.error('Error en toggleInfoButton:', error);
    }
}

function mostrarInfoTipoNovedad(mode) {
    let tipoSeleccionado;
    if (mode === 'unica') {
        const selectUnica = document.getElementById('tipo_novedad');
        tipoSeleccionado = selectUnica ? selectUnica.value : null;
    } else {
        const selectMultiple = document.getElementById(`tipo_novedad_${mode}`);
        tipoSeleccionado = selectMultiple ? selectMultiple.value : null;
    }

    if (!tipoSeleccionado || tipoSeleccionado === '') {
        if (typeof NovedadesApp !== 'undefined' && NovedadesApp.mostrarError) {
            NovedadesApp.mostrarError('Por favor, seleccione un tipo de novedad primero.');
        }
        return;
    }

    const infoTipo = tiposNovedadInfo[tipoSeleccionado];
    const tipoNombre = document.getElementById('tipoNovedadNombre');
    const tipoDescripcion = document.getElementById('tipoNovedadDescripcion');

    if (tipoNombre) {
        tipoNombre.textContent = infoTipo ? infoTipo.nombre : `Tipo de Novedad #${tipoSeleccionado}`;
    }

    if (tipoDescripcion) {
        tipoDescripcion.innerHTML = `
            <div class="p-3 bg-light rounded border">
                <p class="mb-0">${infoTipo ? infoTipo.descripcion : 'Sin descripción adicional.'}</p>
            </div>
        `;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalTipoNovedadInfo'));
    modal.show();
}

window.mostrarInfoTipoNovedad = mostrarInfoTipoNovedad;
window.toggleInfoButton = toggleInfoButton;
window.limpiarFormularioMultiple = limpiarFormularioMultiple;
window.enviarFormularioMultiple = enviarFormularioMultiple;
window.mostrarPeriodoAplicacion = mostrarPeriodoAplicacion;
window.ocultarPeriodoAplicacion = ocultarPeriodoAplicacion;
window.validarPeriodoAplicacionFormulario = validarPeriodoAplicacionFormulario;
window.crearSelectPeriodoBasico = crearSelectPeriodoBasico;
window.obtenerNombreMesBasico = obtenerNombreMesBasico;
