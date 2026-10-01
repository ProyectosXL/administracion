let tablaDespachos = null;
let tablaOcPendientes = null;

/**
 * Cuántos días hacia atrás muestra la grilla al abrir, por fecha de carga.
 *
 * ES UN FILTRO DE PANTALLA, NO LA REGLA DE QUÉ SE VE. El servidor sigue
 * mandando todo lo que dice VisibilidadContenedor -incluidos los contenedores
 * viejos con saldo pendiente- y esto sólo esconde filas en el navegador. Por
 * eso al lado del filtro se dice siempre cuántas quedan afuera: un contenedor
 * con deuda cargado hace más de un año no puede desaparecer sin que la
 * pantalla lo diga.
 */
const DIAS_FECHA_CARGA_DEFAULT = 360;

$(document).ready(function() {
    inicializarFiltroFechaCarga();

    // Cargar despachos
    cargarDespachos();
    
    // Cargar contador de OC pendientes
    cargarContadorOcPendientes();
    
    // Event listener para botón de OC Pendientes
    $('#btnVerOcPendientes').on('click', function() {
        abrirModalOcPendientes();
    });
    
    // Limpiar modales y backdrops cuando la página se esté descargando
    $(window).on('beforeunload', function() {
        $('.modal').modal('hide');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css({
            'overflow': '',
            'padding-right': ''
        });
    });
});

function cargarDespachos() {
    $.ajax({
        url: '../controller/listarDespachos.php?tipo=gestion',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            mostrarAvisos(response.avisos || []);
            if (response.success && response.data) {
                mostrarDespachos(response.data);
            } else {
                mostrarEstadoVacio();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar despachos:', error);
            mostrarEstadoVacio();
        }
    });
}

function mostrarDespachos(despachos) {
    const tbody = $('#tablaDespachos tbody');
    tbody.empty();
    
    if (despachos.length === 0) {
        mostrarEstadoVacio();
        return;
    }
    
    // Destruir tooltips existentes antes de actualizar
    const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    existingTooltips.forEach(el => {
        const tooltip = bootstrap.Tooltip.getInstance(el);
        if (tooltip) tooltip.dispose();
    });
    
    despachos.forEach(function(despacho) {
        const esHija = despacho.ID_PADRE !== null && despacho.ID_PADRE !== undefined;
        const ocsVinculadas = despacho.OCS_VINCULADAS || '';
        const ordenPadre    = despacho.ORDEN_COMPRA_PADRE || '';

        // Celda de OC: badge "+N" para principales con hijas, badge "Vinculada" para hijas
        let ocDisplay = despacho.ORDEN_COMPRA || '-';
        if (!esHija && ocsVinculadas) {
            const cantHijas = ocsVinculadas.split(',').length;
            ocDisplay = `
                <div class="d-flex align-items-center gap-2">
                    <span>${despacho.ORDEN_COMPRA}</span>
                    <span class="badge bg-info text-white"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          data-bs-title="OCs vinculadas: ${ocsVinculadas}">
                        <i class="bi bi-link-45deg"></i> +${cantHijas}
                    </span>
                </div>`;
        } else if (esHija) {
            ocDisplay = `
                <div class="d-flex align-items-center gap-2">
                    <span>${despacho.ORDEN_COMPRA}</span>
                    <span class="badge bg-secondary text-white"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          data-bs-title="Vinculada al contenedor de ${ordenPadre}">
                        <i class="bi bi-link-45deg"></i> Vinculada
                    </span>
                </div>`;
        }

        // Botón "Gestionar costos": en hijas muestra SweetAlert antes de redirigir
        let btnCostos;
        if (esHija) {
            btnCostos = `
                <button onclick="gestionarCostosVinculada(${despacho.ID}, '${ordenPadre}', ${despacho.ID_PADRE})"
                        class="btn-action btn-costos"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        data-bs-title="Gestionar costos (OC principal)">
                    <i class="bi bi-calculator"></i>
                </button>`;
        } else {
            btnCostos = `
                <a href="components/cargarCostos.php?id=${despacho.ID}"
                   class="btn-action btn-costos"
                   data-bs-toggle="tooltip"
                   data-bs-placement="top"
                   data-bs-title="Gestionar costos">
                    <i class="bi bi-calculator"></i>
                </a>`;
        }

        // data-fecha-mov: la fecha cruda 'Y-m-d' para el filtro por fecha de
        // carga. data-order: para que la columna ordene por fecha y no por el
        // texto dd/mm/aaaa.
        const row = `
            <tr data-fecha-mov="${despacho.FECHA_MOV || ''}" data-tiene-costos="${despacho.TIENE_COSTOS ? 1 : 0}">
                <td><strong>#${despacho.ID}</strong></td>
                <td data-order="${despacho.FECHA_MOV || ''}">${formatearFecha(despacho.FECHA_MOV)}</td>
                <td>${despacho.PROVEEDOR || '-'}</td>
                <td>${celdaContenedor(despacho)}</td>
                <td>${despacho.MATERIAL || '-'}</td>
                <td>${ocDisplay}</td>
                <td>
                    <div class="d-flex gap-1" style="flex-wrap: nowrap;">
                        <a href="components/editarDespacho.php?id=${despacho.ID}"
                           class="btn-action btn-editar"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           data-bs-title="Completar despacho">
                            <i class="bi bi-pencil"></i>
                        </a>
                        ${btnCostos}
                        <button onclick="eliminarDespacho(${despacho.ID}, '${despacho.CONTENEDOR}')"
                                class="btn-action btn-eliminar"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-title="Eliminar despacho">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Reinicializar tooltips después de agregar el contenido
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    
    // Destruir DataTable existente si existe
    if ($.fn.DataTable.isDataTable('#tablaDespachos')) {
        $('#tablaDespachos').DataTable().destroy();
    }
    
    // Inicializar DataTable con los datos cargados
    tablaDespachos = $('#tablaDespachos').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
        },
        order: [[0, 'desc']], // Ordenar por ID descendente
        pageLength: 15,
        lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, "Todos"]],
        responsive: true,
        autoWidth: false,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });

    actualizarInfoFechaCarga();
}

/* ==========================================================================
   FILTRO POR FECHA DE CARGA

   Mira FECHA_MOV -la fecha en que se cargó el despacho, la columna "Fecha"
   de la grilla- y arranca en los últimos DIAS_FECHA_CARGA_DEFAULT días.

   SE FILTRA EN EL NAVEGADOR, con una búsqueda propia de DataTables, y no en
   el servidor: el listado ya está entero en la página y la regla de qué
   contenedores existen para esta pantalla es del servidor
   (VisibilidadContenedor). Así el filtro se combina solo con el buscador y
   el paginado, y cambiar las fechas no es un pedido nuevo.
   ========================================================================== */

/** 'Y-m-d' de una fecha, en hora local (toISOString() daría la de UTC). */
function fechaIso(d) {
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${mm}-${dd}`;
}

function rangoFechaCargaDefault() {
    const hoy = new Date();
    const desde = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - DIAS_FECHA_CARGA_DEFAULT);
    $('#fechaCargaDesde').val(fechaIso(desde));
    $('#fechaCargaHasta').val(fechaIso(hoy));
}

/**
 * Si una fecha 'Y-m-d' cae en el rango elegido. Un extremo vacío es abierto.
 * Una fila sin FECHA_MOV sólo se ve con el rango entero abierto: no hay cómo
 * afirmar que esté adentro.
 */
function dentroDeFechaCarga(fechaMov) {
    const desde = $('#fechaCargaDesde').val();
    const hasta = $('#fechaCargaHasta').val();

    if (!desde && !hasta) return true;
    if (!fechaMov) return false;
    if (desde && fechaMov < desde) return false;
    if (hasta && fechaMov > hasta) return false;
    return true;
}

function inicializarFiltroFechaCarga() {
    rangoFechaCargaDefault();

    // Sólo para esta tabla: el modal de OC pendientes también es un DataTable
    // y esta búsqueda es global a todas.
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'tablaDespachos') return true;
        const tr = settings.aoData[dataIndex].nTr;
        return dentroDeFechaCarga(tr ? tr.dataset.fechaMov : '');
    });

    const redibujar = function() {
        if (tablaDespachos) tablaDespachos.draw();
        actualizarInfoFechaCarga();
    };

    $('#fechaCargaDesde, #fechaCargaHasta').on('change', redibujar);
    $('#btnFechaCargaDefault').on('click', function() {
        rangoFechaCargaDefault();
        redibujar();
    });
    $('#btnFechaCargaTodas').on('click', function() {
        $('#fechaCargaDesde, #fechaCargaHasta').val('');
        redibujar();
    });
}

/**
 * Cuántos despachos deja afuera el filtro de fechas, y cuántos de ésos tienen
 * los costos cargados -o sea, están en la grilla por saldo pendiente-. Se dice
 * siempre: una tabla que esconde filas sin decirlo se lee como que no existen.
 */
function actualizarInfoFechaCarga() {
    const $info = $('#filtroFechaCargaInfo');
    if (!tablaDespachos) {
        $info.text('');
        return;
    }

    let total = 0;
    let afuera = 0;
    let afueraConCostos = 0;

    tablaDespachos.rows().every(function() {
        const tr = this.node();
        total++;
        if (!dentroDeFechaCarga(tr.dataset.fechaMov)) {
            afuera++;
            if (tr.dataset.tieneCostos === '1') afueraConCostos++;
        }
    });

    if (afuera === 0) {
        $info.text(`Se ven los ${total} despachos.`);
    } else {
        $info.text(`${afuera} de ${total} despachos fuera del rango de fechas` +
            (afueraConCostos > 0
                ? ` (${afueraConCostos} con los costos ya cargados).`
                : '.'));
    }
}

/**
 * El contenedor, con la etiqueta "Costos cargados" si corresponde.
 *
 * POR QUÉ HACE FALTA LA ETIQUETA. Hasta feature/comex-visibilidad-saldo,
 * cargar los costos de nacionalización sacaba el contenedor de esta grilla.
 * Ahora sale cuando tiene los costos Y el FOB pagado -ver
 * class/VisibilidadContenedor.php-, así que un contenedor con los costos ya
 * cargados puede seguir acá por el saldo. Sin la marca se confunde con uno al
 * que todavía le faltan los costos, y alguien los cargaría dos veces.
 *
 * TIENE_COSTOS lo decide el servidor, a nivel grupo: una hija lleva la
 * etiqueta si el detalle está cargado en cualquier OC del contenedor.
 */
function celdaContenedor(despacho) {
    const contenedor = despacho.CONTENEDOR || '-';
    if (!despacho.TIENE_COSTOS) {
        return contenedor;
    }

    const motivo = despacho.ESTADO_PAGO === 'SIN_FOB'
        ? 'no tiene FOB cargado, así que no hay contra qué medir los pagos'
        : 'al proveedor del exterior todavía le falta cobrar parte del FOB';

    return `
        <div class="d-flex align-items-center gap-2">
            <span>${contenedor}</span>
            <span class="badge bg-success text-white"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  data-bs-title="Ya tiene los costos de nacionalización cargados. Sigue en la grilla porque ${motivo}.">
                <i class="bi bi-receipt"></i> Costos cargados
            </span>
        </div>`;
}

/**
 * Lo que el servidor no pudo leer y cambia qué contenedores se ven.
 * Se pinta arriba de la tabla y no se cierra: es un aviso sobre los datos.
 */
function mostrarAvisos(avisos) {
    const $destino = $('#avisosGestion');
    if (!$destino.length) return;

    if (!avisos.length) {
        $destino.hide().empty();
        return;
    }

    const html = avisos.map(a =>
        `<div><i class="bi bi-exclamation-triangle-fill"></i> ${$('<div>').text(a).html()}</div>`
    ).join('');

    $destino.html(html).show();
}

function formatearFecha(fecha) {
    if (!fecha) return '-';

    /* 'Y-m-d' se arma a mano: new Date('2026-09-30') la toma como medianoche
       UTC y en Argentina se mostraba como 29/09. Con el filtro por fecha de
       carga, ese día de diferencia hacía que una fila pareciera fuera del
       rango que sí cumple. */
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(fecha));
    if (m) return `${m[3]}/${m[2]}/${m[1]}`;

    const d = new Date(fecha);
    return d.toLocaleDateString('es-AR', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

function mostrarEstadoVacio() {
    const tbody = $('#tablaDespachos tbody');
    tbody.html(`
        <tr>
            <td colspan="7">
                <div class="empty-state">
                    <i class="bi bi-check-circle"></i>
                    <h3>¡No hay despachos pendientes!</h3>
                    <p>Todos los despachos han sido completados.</p>
                </div>
            </td>
        </tr>
    `);
}

/**
 * Borra un despacho, avisando antes qué más se va a borrar.
 *
 * PRIMERO SE PREGUNTA AL SERVIDOR qué cuelga del despacho: costos cargados,
 * estimación, pagos e historial de fechas. La pantalla no tiene esos datos, y
 * un aviso armado con lo que sí tiene diría de menos justo antes de un borrado
 * que no se puede deshacer. Ver Orden::infoEliminacion().
 *
 * TRES CAMINOS:
 *  - principal con OCs vinculadas: se rechaza y no se borra nada;
 *  - con algo más que el despacho: aviso con el detalle, Continuar / Cancelar;
 *  - sin nada colgado: la confirmación de siempre.
 *
 * El borrado en sí va en una transacción del lado del servidor: o se borra
 * todo, o no se borra nada.
 */
function eliminarDespacho(id, contenedor) {
    $.ajax({
        url: '../controller/consultarEliminacionDespacho.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (!response.success) {
                Swal.fire({
                    title: 'No se puede eliminar',
                    text: response.message || 'No se pudo leer el despacho',
                    icon: 'error',
                    confirmButtonColor: '#7066e0'
                });
                return;
            }

            const info = response.data;

            if (info.bloqueado) {
                Swal.fire({
                    title: 'No se puede eliminar este despacho',
                    text: info.motivoBloqueo,
                    icon: 'error',
                    confirmButtonColor: '#7066e0'
                });
                return;
            }

            confirmarEliminacion(id, contenedor, info);
        },
        error: function() {
            Swal.fire({
                title: 'Error',
                text: 'No se pudo consultar qué se borraría con el despacho. No se borró nada.',
                icon: 'error',
                confirmButtonColor: '#7066e0'
            });
        }
    });
}

/** Escapa texto para meterlo en el html de un SweetAlert */
function escaparHtml(texto) {
    return $('<div>').text(texto === null || texto === undefined ? '' : String(texto)).html();
}

/**
 * El aviso previo, armado con lo que informó el servidor.
 */
function confirmarEliminacion(id, contenedor, info) {
    const titulo = `<strong>${escaparHtml(contenedor || ('ID: ' + id))}</strong>`;

    if (!info.requiereAviso) {
        Swal.fire({
            title: '¿Eliminar despacho?',
            html: `<p>Estás a punto de eliminar el despacho:</p>${titulo}` +
                  `<p class="mt-2">Esta acción no se puede deshacer.</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) enviarEliminacion(id);
        });
        return;
    }

    const items = [];
    if (info.costos.filas > 0) {
        items.push('Los <strong>costos de nacionalización</strong> cargados');
    }
    if (info.estimacion.filas > 0) {
        items.push('La <strong>estimación de PCI</strong>' +
                   (info.estimacion.confirmada ? ' (confirmada)' : ' (en borrador)'));
    }
    if (info.pagos.cantidad > 0) {
        const monto = Number(info.pagos.totalUsd).toLocaleString('es-AR',
            { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        items.push(`<strong>${info.pagos.cantidad} ${info.pagos.cantidad === 1 ? 'pago' : 'pagos'}</strong>` +
                   ` al proveedor del exterior, por <strong>U$S ${monto}</strong>`);
    }
    if (info.historial.filas > 0) {
        items.push(`El <strong>historial de fechas</strong> (${info.historial.filas} ` +
                   `${info.historial.filas === 1 ? 'cambio' : 'cambios'})`);
    }

    let html = `<p>Junto con el despacho ${titulo} se va a borrar:</p>` +
               `<ul class="text-start">${items.map(i => `<li>${i}</li>`).join('')}</ul>`;

    if (info.esHija) {
        html += `<p class="text-start small">Es una OC vinculada: la estimación y los pagos ` +
                `del contenedor están en la OC principal <strong>${escaparHtml(info.ordenPrincipal)}</strong> ` +
                `y <strong>no</strong> se borran.</p>`;
    }

    (info.avisos || []).forEach(a => {
        html += `<p class="text-start small text-muted">${escaparHtml(a)}</p>`;
    });

    html += '<p class="mt-2">Esta acción no se puede deshacer.</p>';

    Swal.fire({
        title: 'Este despacho tiene datos cargados',
        html: html,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Continuar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) enviarEliminacion(id);
    });
}

function enviarEliminacion(id) {
    $.ajax({
        url: '../controller/eliminarDespacho.php',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: '¡Eliminado!',
                    text: response.message || 'El despacho ha sido eliminado correctamente.',
                    icon: 'success',
                    timer: 2500,
                    showConfirmButton: false,
                    willClose: () => {
                        // Recargar la página para actualizar la tabla
                        location.reload();
                    }
                });
            } else {
                Swal.fire({
                    title: response.bloqueado ? 'No se puede eliminar este despacho' : 'No se pudo eliminar',
                    text: response.message || 'No se pudo eliminar el despacho',
                    icon: 'error',
                    confirmButtonColor: '#7066e0'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Ocurrió un error al eliminar el despacho. Si el servidor no respondió, ' +
                      'el borrado se hace en una transacción: o se borró todo, o nada.',
                icon: 'error',
                confirmButtonColor: '#7066e0'
            });
        }
    });
}

// ========== FUNCIONES PARA ÓRDENES DE COMPRA PENDIENTES ==========

/**
 * Carga el contador de órdenes de compra pendientes (badge en el botón)
 */
function cargarContadorOcPendientes() {
    $.ajax({
        url: '../controller/traerOrdenesPendientesController.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && Array.isArray(response) && response.length > 0) {
                $('#badgeOcPendientes').text(response.length).show();
            } else {
                $('#badgeOcPendientes').hide();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar contador de OC pendientes:', error);
            $('#badgeOcPendientes').hide();
        }
    });
}

/**
 * Abre el modal y carga las órdenes de compra pendientes
 */
function abrirModalOcPendientes() {
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalOcPendientes'));
    modal.show();
    
    // Mostrar loading
    const tbody = $('#tablaOcPendientes tbody');
    tbody.html(`
        <tr>
            <td colspan="5" class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando órdenes de compra pendientes...</p>
            </td>
        </tr>
    `);
    
    // Cargar datos
    $.ajax({
        url: '../controller/traerOrdenesPendientesController.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && Array.isArray(response)) {
                mostrarOcPendientes(response);
            } else {
                mostrarOcPendientesVacio();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar OC pendientes:', error);
            tbody.html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <p class="mt-2">Error al cargar las órdenes de compra pendientes</p>
                    </td>
                </tr>
            `);
        }
    });
}

/**
 * Muestra las órdenes de compra pendientes en la tabla del modal
 */
function mostrarOcPendientes(ordenes) {
    const tbody = $('#tablaOcPendientes tbody');
    tbody.empty();
    
    if (ordenes.length === 0) {
        mostrarOcPendientesVacio();
        return;
    }
    
    ordenes.forEach(function(orden) {
        const row = `
            <tr>
                <td><strong>${orden.COD_PROVEE || '-'}</strong></td>
                <td>${orden.PROVEEDOR || '-'}</td>
                <td><strong>${orden.N_ORDEN_CO || '-'}</strong></td>
                <td>${formatearFecha(orden.FECHA_INGRESO)}</td>
                <td>
                    <button class="btn btn-sm btn-primary" 
                            onclick="crearDespachoDesdeOc('${orden.COD_PROVEE}', '${orden.N_ORDEN_CO}')"
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            data-bs-title="Crear despacho con esta OC">
                        <i class="bi bi-plus-circle"></i>
                        Crear Despacho
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Reinicializar tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    
    // Destruir DataTable existente si existe
    if ($.fn.DataTable.isDataTable('#tablaOcPendientes')) {
        $('#tablaOcPendientes').DataTable().destroy();
    }
    
    // Inicializar DataTable sin paginado
    tablaOcPendientes = $('#tablaOcPendientes').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
        },
        order: [[3, 'desc']], // Ordenar por fecha ingreso descendente
        paging: false, // Sin paginado - mostrar todo
        searching: true, // Mantener búsqueda
        info: false, // Sin información de "Mostrando X de Y registros"
        responsive: true,
        autoWidth: false,
        dom: '<"row"<"col-sm-12"f>>rt' // Solo búsqueda y tabla
    });
}

/**
 * Muestra mensaje cuando no hay órdenes de compra pendientes
 */
function mostrarOcPendientesVacio() {
    const tbody = $('#tablaOcPendientes tbody');
    tbody.html(`
        <tr>
            <td colspan="5" class="text-center">
                <div class="empty-state">
                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">¡No hay órdenes de compra pendientes!</h5>
                    <p class="text-muted">Todas las órdenes del último año y medio tienen despacho asignado.</p>
                </div>
            </td>
        </tr>
    `);
}

/**
 * Intercepta el click en "Gestionar costos" de una OC hija.
 * Muestra un SweetAlert informativo y redirige a la principal si confirman.
 */
function gestionarCostosVinculada(idHija, ordenPadre, idPadre) {
    Swal.fire({
        title: 'OC vinculada a contenedor',
        html: `Esta OC está vinculada a la OC <strong>${ordenPadre}</strong>. ` +
              `Los costos de nacionalización se gestionan desde la OC principal. ¿Vamos allí?`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Sí, ir a la principal',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#7066e0'
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = 'components/cargarCostos.php?id=' + idPadre;
        }
    });
}

/**
 * Redirige a la página de carga inicial con proveedor y OC preseleccionados
 */
function crearDespachoDesdeOc(codProvee, nOrdenCo) {
    // Guardar los datos en sessionStorage
    sessionStorage.setItem('ocPendiente_proveedor', codProvee);
    sessionStorage.setItem('ocPendiente_ordenCompra', nOrdenCo);
    
    // Limpiar cualquier backdrop o modal abierto antes de navegar
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css({
        'overflow': '',
        'padding-right': ''
    });
    
    // Navegar directamente sin intentar cerrar el modal
    // El cambio de página limpiará automáticamente el modal
    window.location.href = 'cargaInicial.php';
}
