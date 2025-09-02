/**
 * Funciones para manejo de período seleccionable
 * /novedades/js/periodo_seleccionable.js
 */

/**
 * Generar opciones de período (mes/año) para el select
 * Desde el mes anterior hasta 11 meses en adelante (total: 13 opciones)
 */
function generarOpcionesPeriodo() {
    const fechaActual = new Date();
    const opciones = [];
    
    // Empezar desde el mes anterior
    for (let i = -1; i <= 11; i++) {
        const fecha = new Date(fechaActual.getFullYear(), fechaActual.getMonth() + i, 1);
        const mes = fecha.getMonth() + 1; // getMonth() devuelve 0-11, necesitamos 1-12
        const año = fecha.getFullYear();
        
        opciones.push({
            value: `${mes}-${año}`,
            text: `${obtenerNombreMes(mes)} ${año}`,
            mes: mes,
            año: año,
            esPeriodoActual: i === 0 // Marcar si es el período actual
        });
    }
    
    return opciones;
}

/**
 * Obtener el período sugerido según el tipo de novedad y fecha de corte
 */
function obtenerPeriodoSugerido(tipoNovedadId, fechaReferencia = null) {
    const fecha = fechaReferencia ? new Date(fechaReferencia) : new Date();
    
    // Por defecto, usar el período actual (mes/año actual)
    const mes = fecha.getMonth() + 1;
    const año = fecha.getFullYear();
    
    return {
        mes: mes,
        año: año,
        value: `${mes}-${año}`,
        text: `${obtenerNombreMes(mes)} ${año}`
    };
}

/**
 * Crear el HTML del select de período
 */
function crearSelectPeriodo(valorPorDefecto = null) {
    const opciones = generarOpcionesPeriodo();
    const periodoSugerido = valorPorDefecto || obtenerPeriodoSugerido();
    
    let html = `
        <div class="form-group mb-3" id="periodo-aplicacion-group">
            <label for="periodo_aplicacion" class="form-label">
                <i class="fas fa-calendar-alt me-2"></i>
                Período de Aplicación
            </label>
            <select class="form-select" id="periodo_aplicacion" name="periodo_aplicacion" required>
    `;
    
    opciones.forEach(opcion => {
        const selected = (valorPorDefecto && opcion.value === valorPorDefecto.value) || 
                        (!valorPorDefecto && opcion.esPeriodoActual) ? 'selected' : '';
        html += `<option value="${opcion.value}" ${selected}>${opcion.text}</option>`;
    });
    
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
 * Insertar el select de período en el formulario
 */
function insertarSelectPeriodo(contenedorSelector, valorPorDefecto = null) {
    const contenedor = document.querySelector(contenedorSelector);
    if (!contenedor) {
        console.error('No se encontró el contenedor para el select de período:', contenedorSelector);
        return;
    }
    
    // Verificar si ya existe el select
    const selectExistente = document.getElementById('periodo_aplicacion');
    if (selectExistente) {
        selectExistente.closest('#periodo-aplicacion-group').remove();
    }
    
    contenedor.insertAdjacentHTML('beforeend', crearSelectPeriodo(valorPorDefecto));
    
    // Aplicar Select2 si está disponible
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#periodo_aplicacion').select2({
            theme: 'bootstrap-5',
            placeholder: 'Seleccione período...',
            allowClear: false
        });
    }
}

/**
 * Obtener el período seleccionado como objeto
 */
function obtenerPeriodoSeleccionado() {
    const select = document.getElementById('periodo_aplicacion');
    if (!select) {
        console.error('Select de período no encontrado');
        return null;
    }
    
    const valor = select.value;
    if (!valor) {
        return null;
    }
    
    const [mes, año] = valor.split('-').map(Number);
    return {
        mes: mes,
        año: año,
        value: valor,
        text: select.options[select.selectedIndex].text
    };
}

/**
 * Validar que el período seleccionado esté en el rango permitido
 */
function validarPeriodoSeleccionado() {
    const periodoSeleccionado = obtenerPeriodoSeleccionado();
    if (!periodoSeleccionado) {
        return {
            valido: false,
            error: 'Debe seleccionar un período de aplicación'
        };
    }
    
    const opciones = generarOpcionesPeriodo();
    const valoresPermitidos = opciones.map(op => op.value);
    
    if (!valoresPermitidos.includes(periodoSeleccionado.value)) {
        return {
            valido: false,
            error: 'El período seleccionado no está en el rango permitido'
        };
    }
    
    return {
        valido: true,
        periodo: periodoSeleccionado
    };
}

/**
 * Obtener nombre del mes
 */
function obtenerNombreMes(numeroMes) {
    const meses = [
        '', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    return meses[numeroMes] || 'Mes inválido';
}

/**
 * Actualizar el período cuando cambia el tipo de novedad
 */
function actualizarPeriodoSegunTipo(tipoNovedadId) {
    const periodoSugerido = obtenerPeriodoSugerido(tipoNovedadId);
    const select = document.getElementById('periodo_aplicacion');
    
    if (select) {
        // Buscar la opción correspondiente al período sugerido
        const opciones = Array.from(select.options);
        const opcionSugerida = opciones.find(opt => opt.value === periodoSugerido.value);
        
        if (opcionSugerida) {
            select.value = periodoSugerido.value;
            
            // Actualizar Select2 si está activo
            if (typeof $ !== 'undefined' && $.fn.select2 && $(select).hasClass('select2-hidden-accessible')) {
                $(select).trigger('change');
            }
        }
    }
}

// Hacer las funciones disponibles globalmente
window.generarOpcionesPeriodo = generarOpcionesPeriodo;
window.obtenerPeriodoSugerido = obtenerPeriodoSugerido;
window.crearSelectPeriodo = crearSelectPeriodo;
window.insertarSelectPeriodo = insertarSelectPeriodo;
window.obtenerPeriodoSeleccionado = obtenerPeriodoSeleccionado;
window.validarPeriodoSeleccionado = validarPeriodoSeleccionado;
window.actualizarPeriodoSegunTipo = actualizarPeriodoSegunTipo;
window.obtenerNombreMes = obtenerNombreMes;
