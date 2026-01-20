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
    
    // Esperar un momento para que DataTables termine de renderizar
    setTimeout(function() {
        // Calcular totales iniciales
        calcularTotales();
    }, 100);
    
    // Configurar exportación a Excel
    setupExcelExport();
    
    // Ajustar estilos del toggle
    adjustToggleStyles();
    
    // Configurar spinner de búsqueda
    setupSearchSpinner();
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
        drawCallback: function() {
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
    console.log('Calculando totales...');
    
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

    // Obtener filas visibles del tbody
    let filas = $('#tableVentas tbody tr:visible');
    
    console.log('Filas visibles:', filas.length);
    
    // Iterar sobre cada fila visible
    filas.each(function() {
        const $row = $(this);
        
        // Usar data-value para obtener los valores numéricos
        totales.tarjeta += parseFloat($row.find('.tdTarjeta').attr('data-value') || 0);
        totales.cuentaDni += parseFloat($row.find('.tdCuentaDni').attr('data-value') || 0);
        totales.totalTarjetas += parseFloat($row.find('.tdTotalTarjetas').attr('data-value') || 0);
        totales.mercadoPagoQr += parseFloat($row.find('.tdMercadoPagoQr').attr('data-value') || 0);
        totales.mercadoPago += parseFloat($row.find('.tdMercadoPago').attr('data-value') || 0);
        totales.modoQr += parseFloat($row.find('.tdModoQr').attr('data-value') || 0);
        totales.promoBanco += parseFloat($row.find('.tdPromoBanco').attr('data-value') || 0);
        totales.efectivo += parseFloat($row.find('.tdEfectivo').attr('data-value') || 0);
        totales.bonusShopping += parseFloat($row.find('.tdBonusShopping').attr('data-value') || 0);
        totales.dolares += parseFloat($row.find('.tdDolares').attr('data-value') || 0);
        totales.euros += parseFloat($row.find('.tdEuros').attr('data-value') || 0);
        totales.totalVentas += parseFloat($row.find('.tdTotalVentas').attr('data-value') || 0);
    });

    console.log('Totales calculados:', totales);

    // Actualizar los totales en el footer
    $('#totalTarjeta').html(formatearMoneda(totales.tarjeta));
    $('#totalCuentaDni').html(formatearMoneda(totales.cuentaDni));
    $('#totalTarjetas').html(formatearMoneda(totales.totalTarjetas));
    $('#totalMercadoPagoQr').html(formatearMoneda(totales.mercadoPagoQr));
    $('#totalMercadoPago').html(formatearMoneda(totales.mercadoPago));
    $('#totalModoQr').html(formatearMoneda(totales.modoQr));
    $('#totalPromoBanco').html(formatearMonedaNegativa(totales.promoBanco));
    $('#totalEfectivo').html(formatearMoneda(totales.efectivo));
    $('#totalBonusShopping').html(formatearMoneda(totales.bonusShopping));
    $('#totalDolares').html(formatearMoneda(totales.dolares));
    $('#totalEuros').html(formatearMoneda(totales.euros));
    $('#totalVentas').html(formatearMoneda(totales.totalVentas));
    
    console.log('Totales actualizados en DOM');
}

/**
 * Formatea un valor como moneda
 */
function formatearMoneda(valor) {
    if (isNaN(valor)) valor = 0;
    return '$' + formatNumber(Math.abs(valor));
}

/**
 * Formatea un valor negativo entre paréntesis y en rojo
 */
function formatearMonedaNegativa(valor) {
    if (isNaN(valor)) valor = 0;
    
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
    if (isNaN(number)) return '0.00';
    
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
        
        // Obtener las fechas del formulario para el nombre del archivo
        const desde = $('input[name="desde"]').val() || 'sin_fecha';
        const hasta = $('input[name="hasta"]').val() || 'sin_fecha';
        
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
    if (document.querySelector(".toggle")) {
        const toggle = document.querySelector(".toggle");
        const toggleOn = document.querySelector(".toggle-on");
        const toggleOff = document.querySelector(".toggle-off");
        const toggleBtn = document.querySelector('.toggle.btn.btn-primary');
        
        if (toggle) toggle.style.width = "50px";
        if (toggleOn) toggleOn.style.fontSize = "0";
        if (toggleOff) toggleOff.style.fontSize = "0";
        if (toggleBtn) toggleBtn.style.height = '42px';
    }
}

/**
 * Configura el spinner de carga para el botón de búsqueda
 */
function setupSearchSpinner() {
    $('#search').on('click', function() {
        $("#boxLoading").addClass("loading");
    });
}

/**
 * Función heredada de main.js para cambiar el entorno
 * @param {HTMLElement} t - Elemento toggle
 */
const cambiarEntorno = (t) => {
    let entorno = 0;

    if(t.getAttribute("data-off") == "ARG" ){
        entorno = 0;
    } else {
        entorno = 1;
    }

    $.ajax({
        url: "Controller/cambiarEntorno.php",
        method: "POST",
        data : {entorno: entorno},
        success: function (data) {
            location.reload();
        }
    });
}
