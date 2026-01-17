/* ===================================
   RESUMEN DE VENTAS - JAVASCRIPT
   =================================== */

let dataTable;

/**
 * Inicialización al cargar el documento
 */
$(document).ready(function() {
    console.log('Iniciando aplicación de resumen de ventas...');
    
    // Verificar que la tabla existe
    if ($('#tableVentas').length === 0) {
        console.error('Tabla #tableVentas no encontrada');
        return;
    }
    
    // Inicializar DataTables
    initializeDataTable();
    
    // Configurar exportación a Excel
    setupExcelExport();
    
    // Ajustar estilos del toggle
    adjustToggleStyles();
    
    // Configurar spinner de búsqueda
    setupSearchSpinner();
    
    console.log('Aplicación inicializada correctamente');
});

/**
 * Inicializa DataTables con configuración en español
 */
function initializeDataTable() {
    dataTable = $('#tableVentas').DataTable({
        paging: false,
        ordering: true,
        searching: true,
        info: true,
        autoWidth: false,
        language: {
            search: "Buscar:",
            info: "Mostrando _TOTAL_ sucursales",
            infoEmpty: "No hay registros disponibles",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "No hay datos disponibles en la tabla",
            loadingRecords: "Cargando...",
            processing: "Procesando..."
        },
        order: [[0, 'asc']], // Ordenar por número de sucursal por defecto
        columnDefs: [
            { targets: [0, 1], orderable: true, searchable: true },
            { targets: '_all', orderable: true, searchable: true }
        ],
        initComplete: function() {
            console.log('DataTables inicializado completamente');
            // Calcular totales después de que DataTables termine de cargar
            setTimeout(function() {
                console.log('Ejecutando cálculo de totales desde initComplete');
                calcularTotales();
            }, 300);
            
            // Segundo cálculo de respaldo
            setTimeout(function() {
                console.log('Ejecutando segundo cálculo de totales (respaldo)');
                calcularTotales();
            }, 1000);
        },
        drawCallback: function() {
            console.log('drawCallback ejecutado');
            // Recalcular totales después de filtrar
            calcularTotales();
        }
    });
}

/**
 * Calcula los totales de todas las columnas numéricas
 * Solo suma las filas visibles después de aplicar filtros
 */
function calcularTotales() {
    console.log('=== INICIANDO CÁLCULO DE TOTALES ===');
    
    let totales = {
        tarjeta: 0,
        cuentaDni: 0,
        totalTarjetas: 0,
        mercadoPagoQr: 0,
        mercadoPago: 0,
        modoQr: 0,
        promoBanco: 0,
        efectivo: 0,
        bonusShopping: 0,
        dolares: 0,
        euros: 0,
        totalVentas: 0
    };

    // Obtener filas visibles del tbody usando DataTables API
    let rows;
    if (dataTable) {
        // Usar la API de DataTables para obtener solo filas visibles
        rows = dataTable.rows({ search: 'applied' }).nodes();
        console.log('Usando DataTables API - Filas visibles:', rows.length);
    } else {
        // Fallback si DataTables no está inicializado
        rows = $('#tableVentas tbody tr:visible');
        console.log('Usando jQuery - Filas visibles:', rows.length);
    }
    
    // Iterar sobre cada fila visible
    $(rows).each(function(index) {
        const $row = $(this);
        
        // Obtener valores usando data-value
        const tarjeta = parseFloat($row.find('.tdTarjeta').attr('data-value')) || 0;
        const cuentaDni = parseFloat($row.find('.tdCuentaDni').attr('data-value')) || 0;
        const totalTarjetas = parseFloat($row.find('.tdTotalTarjetas').attr('data-value')) || 0;
        const mercadoPagoQr = parseFloat($row.find('.tdMercadoPagoQr').attr('data-value')) || 0;
        const mercadoPago = parseFloat($row.find('.tdMercadoPago').attr('data-value')) || 0;
        const modoQr = parseFloat($row.find('.tdModoQr').attr('data-value')) || 0;
        const promoBanco = parseFloat($row.find('.tdPromoBanco').attr('data-value')) || 0;
        const efectivo = parseFloat($row.find('.tdEfectivo').attr('data-value')) || 0;
        const bonusShopping = parseFloat($row.find('.tdBonusShopping').attr('data-value')) || 0;
        const dolares = parseFloat($row.find('.tdDolares').attr('data-value')) || 0;
        const euros = parseFloat($row.find('.tdEuros').attr('data-value')) || 0;
        const totalVentasRow = parseFloat($row.find('.tdTotalVentas').attr('data-value')) || 0;
        
        // Si el data-value de totalVentas está vacío, calcularlo sumando todos los medios de pago
        let totalVentasCalculado = totalVentasRow;
        if (!$row.find('.tdTotalVentas').attr('data-value') || totalVentasRow === 0) {
            // Total = Total Tarjetas (ya incluye Tarjeta + Cuenta DNI) + MP QR + MP + Modo QR + Promo Banco + Efectivo + Bonus + Dólares + Euros
            totalVentasCalculado = totalTarjetas + mercadoPagoQr + mercadoPago + modoQr + promoBanco + efectivo + bonusShopping + dolares + euros;
            
            // Actualizar el data-value y el HTML de la celda
            $row.find('.tdTotalVentas').attr('data-value', totalVentasCalculado);
            $row.find('.tdTotalVentas').html('$' + formatNumber(totalVentasCalculado));
        }
        
        // Debug detallado para cada fila
        if (index < 3) {
            console.log(`==================== FILA ${index} ====================`);
            console.log('  Total Ventas (data-value):', totalVentasRow);
            console.log('  Total Ventas CALCULADO:', totalVentasCalculado);
            console.log('  --- Desglose ---');
            console.log('  Total Tarjetas:', totalTarjetas);
            console.log('  Mercado Pago QR:', mercadoPagoQr);
            console.log('  Mercado Pago:', mercadoPago);
            console.log('  Modo QR:', modoQr);
            console.log('  Promo Banco:', promoBanco);
            console.log('  Efectivo:', efectivo);
            console.log('  Bonus Shopping:', bonusShopping);
            console.log('  Dólares:', dolares);
            console.log('  Euros:', euros);
            console.log('  SUMA VERIFICACIÓN:', totalTarjetas + mercadoPagoQr + mercadoPago + modoQr + promoBanco + efectivo + bonusShopping + dolares + euros);
            console.log('=============================================');
        }
        
        // Sumar a los totales
        totales.tarjeta += tarjeta;
        totales.cuentaDni += cuentaDni;
        totales.totalTarjetas += totalTarjetas;
        totales.mercadoPagoQr += mercadoPagoQr;
        totales.mercadoPago += mercadoPago;
        totales.modoQr += modoQr;
        totales.promoBanco += promoBanco; // Mantiene el signo negativo
        totales.efectivo += efectivo;
        totales.bonusShopping += bonusShopping;
        totales.dolares += dolares;
        totales.euros += euros;
        totales.totalVentas += totalVentasCalculado;
    });

    console.log('Totales calculados:', totales);
    console.log('Total Ventas específicamente:', totales.totalVentas);

    // Actualizar los totales en el footer con verificación
    actualizarFooter('#totalTarjeta', totales.tarjeta);
    actualizarFooter('#totalCuentaDni', totales.cuentaDni);
    actualizarFooter('#totalTarjetas', totales.totalTarjetas);
    actualizarFooter('#totalMercadoPagoQr', totales.mercadoPagoQr);
    actualizarFooter('#totalMercadoPago', totales.mercadoPago);
    actualizarFooter('#totalModoQr', totales.modoQr);
    actualizarFooterNegativo('#totalPromoBanco', totales.promoBanco);
    actualizarFooter('#totalEfectivo', totales.efectivo);
    actualizarFooter('#totalBonusShopping', totales.bonusShopping);
    actualizarFooter('#totalDolares', totales.dolares);
    actualizarFooter('#totalEuros', totales.euros);
    actualizarFooter('#totalVentas', totales.totalVentas);
    
    console.log('=== TOTALES ACTUALIZADOS EN DOM ===');
}

/**
 * Actualiza un campo del footer con formato de moneda
 */
function actualizarFooter(selector, valor) {
    const elemento = $(selector);
    if (elemento.length === 0) {
        console.error('Elemento no encontrado:', selector);
        return;
    }
    
    const valorFormateado = formatearMoneda(valor);
    elemento.html(valorFormateado);
    
    if (selector === '#totalVentas') {
        console.log('>>> Actualizando #totalVentas <<<');
        console.log('   Valor recibido:', valor);
        console.log('   Valor formateado:', valorFormateado);
        console.log('   Elemento encontrado:', elemento.length);
        console.log('   HTML final:', elemento.html());
        console.log('   Texto visible:', elemento.text());
    }
}

/**
 * Actualiza un campo del footer con formato negativo
 */
function actualizarFooterNegativo(selector, valor) {
    const elemento = $(selector);
    if (elemento.length === 0) {
        console.error('Elemento no encontrado:', selector);
        return;
    }
    
    const valorFormateado = formatearMonedaNegativa(valor);
    elemento.html(valorFormateado);
}

/**
 * Formatea un valor como moneda
 */
function formatearMoneda(valor) {
    if (isNaN(valor) || valor === undefined || valor === null) {
        console.warn('Valor inválido para formatear:', valor);
        valor = 0;
    }
    return '$' + formatNumber(Math.abs(valor));
}

/**
 * Formatea un valor negativo entre paréntesis y en rojo
 */
function formatearMonedaNegativa(valor) {
    if (isNaN(valor) || valor === undefined || valor === null) {
        console.warn('Valor inválido para formatear como negativo:', valor);
        valor = 0;
    }
    
    if (valor < 0) {
        return '<span style="color: #ef4444;">($' + formatNumber(Math.abs(valor)) + ')</span>';
    } else {
        return '$' + formatNumber(valor);
    }
}

/**
 * Formatea un número con separador de miles y decimales
 * @param {number} number - Número a formatear
 * @returns {string} - Número formateado
 */
function formatNumber(number) {
    if (isNaN(number) || number === undefined || number === null) {
        console.warn('Número inválido para formatear:', number);
        return '0.00';
    }
    
    return number.toLocaleString('de-DE', {
        style: 'decimal',
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
    });
}

/**
 * Configura la exportación a Excel
 */
function setupExcelExport() {
    $("#btnExport").click(function(e) {
        e.preventDefault();
        
        // Recalcular totales antes de exportar
        console.log('Recalculando totales antes de exportar...');
        calcularTotales();
        
        // Obtener las fechas del formulario para el nombre del archivo
        const desde = $('input[name="desde"]').val() || 'sin_fecha';
        const hasta = $('input[name="hasta"]').val() || 'sin_fecha';
        
        console.log('Exportando a Excel...');
        
        $("#tableVentas").table2excel({
            exclude: ".noExport",
            name: "Ventas por medio de pago",
            filename: `Ventas_por_medio_de_pago_${desde}_${hasta}`,
            fileext: ".xlsx"
        });
    });
}

/**
 * Ajusta los estilos del toggle switch
 */
function adjustToggleStyles() {
    setTimeout(function() {
        const toggle = $(".toggle");
        
        if (toggle.length > 0) {
            console.log('Toggle encontrado, estilos aplicados desde CSS');
        }
        
        console.log('Estilos del toggle ajustados');
    }, 200);
}

/**
 * Configura el spinner de carga para el botón de búsqueda
 */
function setupSearchSpinner() {
    // Asegurar que el evento esté configurado incluso si la tabla no existe aún
    $(document).on('click', '#search', function(e) {
        console.log('Botón de búsqueda presionado');
        $("#boxLoading").addClass("loading");
    });
    
    // Configurar también en el formulario submit como backup
    $('.filters-form-improved').on('submit', function() {
        console.log('Formulario enviado');
        $("#boxLoading").addClass("loading");
    });
}

/**
 * Función heredada de main.js para cambiar el entorno
 * @param {HTMLElement} t - Elemento toggle
 */
const cambiarEntorno = (t) => {
    let entorno = 0;
    let nuevoEntorno = '';
    let nuevaBandera = '';

    if(t.getAttribute("data-off") == "ARG" ){
        entorno = 0;
        nuevoEntorno = 'ARG';
        nuevaBandera = 'https://flagcdn.com/w80/ar.png';
    } else {
        entorno = 1;
        nuevoEntorno = 'UY';
        nuevaBandera = 'https://flagcdn.com/w80/uy.png';
    }

    console.log('Cambiando entorno a:', nuevoEntorno);

    // Actualizar visualmente antes de recargar
    $('#textoEntorno').text(nuevoEntorno);
    $('#banderaEntorno').attr('src', nuevaBandera);

    $.ajax({
        url: "Controller/cambiarEntorno.php",
        method: "POST",
        data : {entorno: entorno},
        success: function (data) {
            console.log('Entorno cambiado exitosamente');
            location.reload();
        },
        error: function(xhr, status, error) {
            console.error('Error al cambiar entorno:', error);
        }
    });
}

// Respaldo: calcular totales cuando la ventana termine de cargar completamente
window.addEventListener('load', function() {
    console.log('Window load event - verificando totales...');
    setTimeout(function() {
        if ($('#tableVentas').length > 0) {
            console.log('Ejecutando cálculo de totales desde window.load');
            calcularTotales();
        }
    }, 500);
});
