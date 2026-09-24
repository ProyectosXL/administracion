/**
 * Mayoristas.js - Flujo de Cobranzas Mayoristas
 * XL Extra Large
 */

$(document).ready(function() {
    let tablaPendientes = null;
    let tablaHistorial = null;
    let clienteSeleccionado = null;

    const formatoMoneda = (val) => {
        const num = Math.ceil(parseFloat(val || 0));
        return num.toLocaleString('es-AR', { style: 'currency', currency: 'ARS', minimumFractionDigits: 0, maximumFractionDigits: 0 });
    };

    const formatoFecha = (fechaStr) => {
        if (!fechaStr) return '-';
        const strLimpio = String(fechaStr).trim().split(' ')[0].split('T')[0];
        const parts = strLimpio.split('-');
        if (parts.length === 3 && parts[0].length === 4) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return strLimpio;
    };

    const fechaHoyLegible = () => {
        const hoy = new Date();
        const d = String(hoy.getDate()).padStart(2, '0');
        const m = String(hoy.getMonth() + 1).padStart(2, '0');
        const y = hoy.getFullYear();
        const h = String(hoy.getHours()).padStart(2, '0');
        const min = String(hoy.getMinutes()).padStart(2, '0');
        return `${d}/${m}/${y} ${h}:${min}`;
    };

    // =========================================================================
    // 1. CARGA DE TABLA DE COBRANZAS PENDIENTES
    // =========================================================================
    function cargarCobranzasPendientes() {
        const codVended = $('#filtro-vendedor-pendientes').val() || 'TODOS';

        $('#loading-pendientes').removeClass('d-none');

        $.ajax({
            url: `api/mayoristas_controller.php?action=listar_pendientes&cod_vended=${codVended}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                $('#loading-pendientes').addClass('d-none');
                if (res.success) {
                    renderizarTablaPendientes(res.data);
                    actualizarKpisPendientes(res.data);
                } else {
                    alert('Error al cargar pendientes: ' + (res.message || 'Error desconocido'));
                }
            },
            error: function(err) {
                $('#loading-pendientes').addClass('d-none');
                console.error("Error al cargar pendientes", err);
            }
        });
    }

    function actualizarKpisPendientes(data) {
        let totalGeneral = 0;
        let totalFacturas = 0;
        let totalRemitos = 0;
        let clientesConDeuda = data.length;

        data.forEach(item => {
            totalGeneral += (item.TOTAL_GENERAL || 0);
            totalFacturas += (item.TOTAL_FACTURA || 0);
            totalRemitos += (item.TOTAL_REMITO || 0);
        });

        $('#kpi-total-pendiente').text(formatoMoneda(totalGeneral));
        $('#kpi-total-facturas').text(formatoMoneda(totalFacturas));
        $('#kpi-total-remitos').text(formatoMoneda(totalRemitos));
        $('#kpi-clientes-pendientes').text(clientesConDeuda);
    }

    function renderizarTablaPendientes(data) {
        if ($.fn.DataTable.isDataTable('#tabla-mayoristas-pendientes')) {
            $('#tabla-mayoristas-pendientes').DataTable().destroy();
        }

        tablaPendientes = $('#tabla-mayoristas-pendientes').DataTable({
            data: data,
            responsive: true,
            scrollY: '55vh',
            scrollCollapse: true,
            paging: true,
            pageLength: 100,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            columns: [
                {
                    data: 'RAZON_SOCI',
                    title: 'Cliente',
                    render: function(data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return data;
                        }
                        return `<span class="badge bg-secondary font-monospace">${row.COD_CLIENT}</span><br><strong>${data}</strong>`;
                    }
                },
                {
                    data: 'COD_VENDED',
                    title: 'Vendedor',
                    render: function(data) {
                        let badgeClass = 'bg-primary';
                        let nombre = 'VALERIA VILLARREAL';
                        if (data === 'Z4') { badgeClass = 'bg-info text-dark'; nombre = 'SERGIO LOPEZ COBOS'; }
                        if (data === 'Z5') { badgeClass = 'bg-success'; nombre = 'CRISTIAN NACKE'; }
                        return `<span class="badge ${badgeClass}">${data}</span> <small class="text-muted d-block">${nombre}</small>`;
                    }
                },
                {
                    data: 'TOTAL_FACTURA',
                    title: 'Camino 1: Factura',
                    className: 'text-end',
                    render: function(data, type, row) {
                        const cant = row.CANT_FACTURAS || 0;
                        const monto = formatoMoneda(data);
                        return `<div><strong class="text-success">${monto}</strong></div><small class="text-muted">${cant} comp.</small>`;
                    }
                },
                {
                    data: 'TOTAL_REMITO',
                    title: 'Camino 2: Remito',
                    className: 'text-end',
                    render: function(data, type, row) {
                        const cant = row.CANT_REMITOS || 0;
                        const monto = formatoMoneda(data);
                        return `<div><strong class="text-purple" style="color: #6f42c1;">${monto}</strong></div><small class="text-muted">${cant} rem.</small>`;
                    }
                },
                {
                    data: 'TOTAL_GENERAL',
                    title: 'Total Pendiente',
                    className: 'text-end',
                    render: function(data) {
                        return `<span class="h6 fw-bold text-dark">${formatoMoneda(data)}</span>`;
                    }
                },
                {
                    data: 'TELEFONO_WPP',
                    title: 'WhatsApp / Contacto',
                    render: function(data, type, row) {
                        if (data && data.trim() !== '') {
                            return `<div class="d-flex align-items-center gap-1">
                                        <i class="fa-brands fa-whatsapp text-success fs-5"></i>
                                        <div>
                                            <span class="fw-bold">${data}</span>
                                            ${row.CONTACTO_NOMBRE ? `<small class="text-muted d-block">${row.CONTACTO_NOMBRE}</small>` : ''}
                                        </div>
                                    </div>`;
                        } else {
                            return `<span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i>Sin Teléfono</span>`;
                        }
                    }
                },
                {
                    data: 'ULTIMO_ENVIO',
                    title: 'Estado Cobranza',
                    render: function(data) {
                        if (data && data.ESTADO) {
                            if (data.ESTADO === 'ABONADO') {
                                return `<span class="badge bg-success"><i class="fa-solid fa-check-double me-1"></i>Abonado</span>
                                        <small class="d-block text-muted mt-1">${data.FECHA_ENVIO}</small>`;
                            } else {
                                return `<span class="badge bg-info text-dark"><i class="fa-brands fa-whatsapp me-1 text-success"></i>Enviado</span>
                                        <small class="d-block text-muted mt-1">${data.FECHA_ENVIO}</small>`;
                            }
                        }
                        return `<span class="badge bg-light text-muted border"><i class="fa-solid fa-clock me-1"></i>Sin Enviar</span>`;
                    }
                },
                {
                    data: null,
                    title: 'Acciones',
                    orderable: false,
                    className: 'text-center',
                    render: function(data, type, row) {
                        const rowJson = encodeURIComponent(JSON.stringify(row));
                        const btnCobrarClass = (row.ULTIMO_ENVIO && row.ULTIMO_ENVIO.ESTADO === 'ENVIADO') ? 'btn-outline-success' : 'btn-success';
                        const textoCobrar = (row.ULTIMO_ENVIO && row.ULTIMO_ENVIO.ESTADO === 'ENVIADO') ? 'Reenviar' : 'Cobrar';
                        return `
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-primary btn-ver-detalle" data-cliente="${rowJson}" title="Ver Desglose de Facturas y Remitos">
                                    <i class="fa-solid fa-eye me-1"></i> Desglosar
                                
                                </button>
                                <button class="btn ${btnCobrarClass} btn-generar-wpp" data-cliente="${rowJson}" title="Generar Cobranza WhatsApp">
                                    <i class="fa-brands fa-whatsapp me-1"></i> ${textoCobrar}
                                </button>
                                <button class="btn btn-outline-secondary btn-editar-contacto" data-cliente="${rowJson}" title="Gestionar Teléfono / Contacto">
                                    <i class="fa-solid fa-address-book"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, 'asc']]
        });
    }

    // =========================================================================
    // MODAL DE DESGLOSE DE PENDIENTES (FACTURAS Y REMITOS)
    // =========================================================================
    $(document).on('click', '.btn-ver-detalle', function() {
        const clienteData = JSON.parse(decodeURIComponent($(this).data('cliente')));
        $('#modal-desglose-cliente-nombre').text(`${clienteData.RAZON_SOCI} (${clienteData.COD_CLIENT})`);
        $('#modal-desglose-vendedor').text(`Vendedor: ${clienteData.COD_VENDED}`);
        $('#modal-desglose-total-fac').text(formatoMoneda(clienteData.TOTAL_FACTURA || 0));
        $('#modal-desglose-total-rem').text(formatoMoneda(clienteData.TOTAL_REMITO || 0));
        $('#modal-desglose-total-gral').text(formatoMoneda(clienteData.TOTAL_GENERAL || 0));

        // 1. Renderizar Facturas / NCR / REC
        let htmlFac = '';
        if (clienteData.FACTURAS && clienteData.FACTURAS.length > 0) {
            clienteData.FACTURAS.forEach(f => {
                const esNC = (f.IMPORTE < 0 || (f.T_COMP && (f.T_COMP.includes('NC') || f.T_COMP.includes('NCR'))));
                const esREC = (f.T_COMP === 'REC');
                let badgeColor = 'bg-secondary';
                if (esNC) badgeColor = 'bg-danger text-white';
                else if (esREC) badgeColor = 'bg-info text-dark';
                
                const textColor = f.IMPORTE < 0 ? 'text-danger' : (esREC ? 'text-info fw-bold' : 'text-success');
                htmlFac += `
                    <tr>
                        <td><span class="badge ${badgeColor} font-monospace">${f.T_COMP}</span></td>
                        <td><strong class="font-monospace">${f.N_COMP}</strong></td>
                        <td>${formatoFecha(f.FECHA_EMIS)}</td>
                        <td>${formatoFecha(f.FECHA_PROB_COBRO)}</td>
                        <td class="text-end fw-bold ${textColor}">${formatoMoneda(f.IMPORTE)}</td>
                    </tr>
                `;
            });
        } else {
            htmlFac = '<tr><td colspan="5" class="text-center text-muted py-3">No posee comprobantes pendientes en este momento.</td></tr>';
        }
        $('#cuerpo-tabla-desglose-facturas').html(htmlFac);

        // 2. Renderizar Remitos
        let htmlRem = '';
        if (clienteData.REMITOS && clienteData.REMITOS.length > 0) {
            clienteData.REMITOS.forEach(r => {
                htmlRem += `
                    <tr>
                        <td><span class="badge bg-purple text-white" style="background-color: #6f42c1;">REM</span></td>
                        <td><strong class="font-monospace">${r.N_COMP}</strong></td>
                        <td>${formatoFecha(r.FECHA_EMIS)}</td>
                        <td class="text-end fw-bold" style="color: #6f42c1;">${formatoMoneda(r.IMPORTE)}</td>
                    </tr>
                `;
            });
        } else {
            htmlRem = '<tr><td colspan="4" class="text-center text-muted py-3">No posee remitos pendientes en este momento.</td></tr>';
        }
        $('#cuerpo-tabla-desglose-remitos').html(htmlRem);

        // Botón para pasar directo a WhatsApp desde el modal de desglose
        const rowJson = encodeURIComponent(JSON.stringify(clienteData));
        $('#btn-desglose-ir-wpp').data('cliente', rowJson);

        $('#modalDesgloseCliente').modal('show');
    });

    // =========================================================================
    // 2. MODAL Y GENERADOR DE MENSAJES WHATSAPP (CON SELECCIÓN PARCIALIZADA)
    // =========================================================================
    function inicializarModalWpp(clienteData) {
        clienteSeleccionado = clienteData;

        $('#modal-wpp-cliente-nombre').text(`${clienteData.RAZON_SOCI} (${clienteData.COD_CLIENT})`);
        $('#modal-wpp-vendedor').text(`Vendedor: ${clienteData.COD_VENDED}`);
        $('#modal-wpp-telefono').val(clienteData.TELEFONO_WPP || '');
        $('#modal-wpp-contacto-nombre').val(clienteData.CONTACTO_NOMBRE || '');

        // Renderizar Lista de Facturas / NCR / REC
        let htmlFac = '';
        if (clienteData.FACTURAS && clienteData.FACTURAS.length > 0) {
            $('#cont-wpp-sel-facturas').removeClass('d-none');
            $('#chk-switch-todas-fac').prop('checked', true).prop('disabled', false);
            clienteData.FACTURAS.forEach((f, idx) => {
                const facJson = encodeURIComponent(JSON.stringify(f));
                const esNC = (f.IMPORTE < 0 || (f.T_COMP && (f.T_COMP.includes('NC') || f.T_COMP.includes('NCR'))));
                const esREC = (f.T_COMP === 'REC');
                let textClass = 'text-success';
                if (f.IMPORTE < 0 || esNC) textClass = 'text-danger';
                else if (esREC) textClass = 'text-info';

                htmlFac += `
                    <div class="form-check small py-1 border-bottom border-light">
                        <input class="form-check-input chk-wpp-factura" type="checkbox" value="${facJson}" id="chk-wpp-fac-${idx}" data-importe="${f.IMPORTE || 0}" checked>
                        <label class="form-check-label d-flex justify-content-between align-items-center" for="chk-wpp-fac-${idx}">
                            <span><strong class="font-monospace">${f.T_COMP || 'FAC'} ${f.N_COMP}</strong> <small class="text-muted d-block">${formatoFecha(f.FECHA_EMIS)}</small></span>
                            <span class="fw-bold ${textClass} font-monospace ms-2">${formatoMoneda(f.IMPORTE)}</span>
                        </label>
                    </div>
                `;
            });
        } else {
            $('#cont-wpp-sel-facturas').addClass('d-none');
            $('#chk-switch-todas-fac').prop('checked', false).prop('disabled', true);
            htmlFac = '<div class="text-muted small py-2 text-center">Sin facturas / comprobantes pendientes</div>';
        }
        $('#lista-wpp-facturas').html(htmlFac);

        // Renderizar Lista de Remitos
        let htmlRem = '';
        if (clienteData.REMITOS && clienteData.REMITOS.length > 0) {
            $('#cont-wpp-sel-remitos').removeClass('d-none');
            $('#chk-switch-todos-rem').prop('checked', true).prop('disabled', false);
            clienteData.REMITOS.forEach((r, idx) => {
                const remJson = encodeURIComponent(JSON.stringify(r));
                htmlRem += `
                    <div class="form-check small py-1 border-bottom border-light">
                        <input class="form-check-input chk-wpp-remito" type="checkbox" value="${remJson}" id="chk-wpp-rem-${idx}" data-importe="${r.IMPORTE || 0}" checked>
                        <label class="form-check-label d-flex justify-content-between align-items-center" for="chk-wpp-rem-${idx}">
                            <span><strong class="font-monospace">REM ${r.N_COMP}</strong> <small class="text-muted d-block">${formatoFecha(r.FECHA_EMIS)}</small></span>
                            <span class="fw-bold font-monospace ms-2" style="color: #6f42c1;">${formatoMoneda(r.IMPORTE)}</span>
                        </label>
                    </div>
                `;
            });
        } else {
            $('#cont-wpp-sel-remitos').addClass('d-none');
            $('#chk-switch-todos-rem').prop('checked', false).prop('disabled', true);
            htmlRem = '<div class="text-muted small py-2 text-center">Sin remitos pendientes</div>';
        }
        $('#lista-wpp-remitos').html(htmlRem);

        // Si solo tiene facturas o solo remitos, ajustar clases de columnas
        if ((!clienteData.FACTURAS || clienteData.FACTURAS.length === 0) && (clienteData.REMITOS && clienteData.REMITOS.length > 0)) {
            $('#cont-wpp-sel-remitos').removeClass('col-md-6').addClass('col-12');
        } else if ((clienteData.FACTURAS && clienteData.FACTURAS.length > 0) && (!clienteData.REMITOS || clienteData.REMITOS.length === 0)) {
            $('#cont-wpp-sel-facturas').removeClass('col-md-6 border-end').addClass('col-12');
        } else {
            $('#cont-wpp-sel-facturas').removeClass('col-12').addClass('col-md-6 border-end');
            $('#cont-wpp-sel-remitos').removeClass('col-12').addClass('col-md-6');
        }

        recalcularSeleccionComprobantesWpp(true);
        $('#modalGenerarWpp').modal('show');
    }

    // Obtener comprobantes seleccionados actualmente
    function obtenerComprobantesSeleccionados() {
        const facturas = [];
        let totalFac = 0;
        $('.chk-wpp-factura:checked').each(function() {
            try {
                const f = JSON.parse(decodeURIComponent($(this).val()));
                facturas.push(f);
                totalFac += parseFloat($(this).data('importe') || 0);
            } catch (e) {}
        });

        const remitos = [];
        let totalRem = 0;
        $('.chk-wpp-remito:checked').each(function() {
            try {
                const r = JSON.parse(decodeURIComponent($(this).val()));
                remitos.push(r);
                totalRem += parseFloat($(this).data('importe') || 0);
            } catch (e) {}
        });

        return {
            facturas: facturas,
            remitos: remitos,
            totalFacturas: totalFac,
            totalRemitos: totalRem,
            totalGeneral: totalFac + totalRem
        };
    }

    function recalcularSeleccionComprobantesWpp(autoAjustarTipoMensaje = false) {
        const seleccion = obtenerComprobantesSeleccionados();

        $('#badge-total-fac-wpp').text(formatoMoneda(seleccion.totalFacturas));
        $('#badge-total-rem-wpp').text(formatoMoneda(seleccion.totalRemitos));
        $('#wpp-total-seleccionado-gral').text(formatoMoneda(seleccion.totalGeneral));

        // Auto-seleccionar tipo de mensaje según selección
        if (autoAjustarTipoMensaje) {
            if (seleccion.totalRemitos > 0 && seleccion.totalFacturas > 0) {
                $('#tipoMensaje1').prop('checked', true);
            } else if (seleccion.totalFacturas > 0) {
                $('#tipoMensaje2').prop('checked', true);
            } else if (seleccion.totalRemitos > 0) {
                $('#tipoMensaje1').prop('checked', true);
            } else {
                $('#tipoMensaje1').prop('checked', true);
            }
        }

        actualizarVistaPreviaMensaje();
    }

    // Switches y botones de selección rápida
    $(document).on('change', '#chk-switch-todas-fac', function() {
        const isChecked = $(this).is(':checked');
        $('.chk-wpp-factura').prop('checked', isChecked);
        recalcularSeleccionComprobantesWpp(true);
    });

    $(document).on('change', '#chk-switch-todos-rem', function() {
        const isChecked = $(this).is(':checked');
        $('.chk-wpp-remito').prop('checked', isChecked);
        recalcularSeleccionComprobantesWpp(true);
    });

    $(document).on('change', '.chk-wpp-factura', function() {
        const total = $('.chk-wpp-factura').length;
        const checked = $('.chk-wpp-factura:checked').length;
        $('#chk-switch-todas-fac').prop('checked', checked > 0);
        recalcularSeleccionComprobantesWpp(false);
    });

    $(document).on('change', '.chk-wpp-remito', function() {
        const total = $('.chk-wpp-remito').length;
        const checked = $('.chk-wpp-remito:checked').length;
        $('#chk-switch-todos-rem').prop('checked', checked > 0);
        recalcularSeleccionComprobantesWpp(false);
    });

    $('#btn-wpp-seleccionar-todos').on('click', function() {
        $('.chk-wpp-factura').prop('checked', true);
        $('.chk-wpp-remito').prop('checked', true);
        $('#chk-switch-todas-fac').prop('checked', true);
        $('#chk-switch-todos-rem').prop('checked', true);
        recalcularSeleccionComprobantesWpp(true);
    });

    $('#btn-wpp-deseleccionar-todos').on('click', function() {
        $('.chk-wpp-factura').prop('checked', false);
        $('.chk-wpp-remito').prop('checked', false);
        $('#chk-switch-todas-fac').prop('checked', false);
        $('#chk-switch-todos-rem').prop('checked', false);
        recalcularSeleccionComprobantesWpp(true);
    });

    $(document).on('click', '#btn-desglose-ir-wpp', function() {
        $('#modalDesgloseCliente').modal('hide');
        const clienteData = JSON.parse(decodeURIComponent($(this).data('cliente')));
        inicializarModalWpp(clienteData);
    });

    $(document).on('click', '.btn-generar-wpp', function() {
        const clienteData = JSON.parse(decodeURIComponent($(this).data('cliente')));
        inicializarModalWpp(clienteData);
    });

    $('input[name="tipoMensaje"]').on('change', function() {
        actualizarVistaPreviaMensaje();
    });

    function construirTextoMensaje(tipo, cliente, seleccion) {
        const razonSocial = cliente.RAZON_SOCI || '';
        const totalFac = seleccion ? seleccion.totalFacturas : (cliente.TOTAL_FACTURA || 0);
        const totalRem = seleccion ? seleccion.totalRemitos : (cliente.TOTAL_REMITO || 0);
        const totalGral = seleccion ? seleccion.totalGeneral : ((cliente.TOTAL_FACTURA || 0) + (cliente.TOTAL_REMITO || 0));
        const montoFac = formatoMoneda(totalFac);
        const montoRem = formatoMoneda(totalRem);
        const montoGral = formatoMoneda(totalGral);
        const fechaActual = fechaHoyLegible();

        // Si se seleccionaron ambos o si es tipo 1 con ambos montos
        if (totalFac > 0 && totalRem > 0) {
            return `🙂 ¡HOLA! ${razonSocial}

🔈🔉🔊 Tienen mercadería preparada

🟢 Factura ${montoFac}
🟣 Remito ${montoRem}

Condiciones de pago
Facturas
1-Transferencia a los 10 días FF Descuento 5%
2-Echeqs a 60 días FF SIN DESCUENTO
3-Echeqs a 15 días FF Descuento 6% (emisión del pago inmediata)
4-Echeqs a 30 días FF Descuento 4% (emisión del pago inmediata)

Remitos
💵Efectivo. 
⚠️ Deposito
📝Valores, a 60 días fecha remito máximo. (propios)

🔖 Horarios de entrega
📌De lunes a viernes de 8.30 a 12.30 y de 14 a 15.30 horas.

🌎 Dirección
📍Uruguay 4415, Victoria, San Fernando.
      Pcia. De Buenos Aires. CP 1644
${fechaActual}`;
        } else if (totalRem > 0 && totalFac === 0) {
            // Solo Remitos seleccionados
            return `🙂 ¡HOLA! ${razonSocial}

🔈🔉🔊 Tienen mercadería preparada

🟣 Remito ${montoRem}

Condición de pago
Remitos
💵Efectivo. 
⚠️ Deposito
📝Valores, a 60 días fecha remito máximo. (propios)

🔖 Horarios de entrega
📌De lunes a viernes de 8.30 a 12.30 y de 14 a 15.30 horas.

🌎 Dirección
📍Uruguay 4415, Victoria, San Fernando.
      Pcia. De Buenos Aires. CP 1644
${fechaActual}`;
        } else {
            // Solo Facturas seleccionadas
            return `🙂 ¡HOLA! ${razonSocial}

🔈🔉🔊 Tienen mercadería preparada

🟢 Factura ${montoFac}

Condición de pago
1-Transferencia a los 10 días FF Descuento 5%
2-Echeqs a 60 días FF SIN DESCUENTO
3-Echeqs a 15 días FF Descuento 6% (emisión del pago inmediata)
4-Echeqs a 30 días FF Descuento 4% (emisión del pago inmediata)

🔖 Horarios de entrega
📌De lunes a viernes de 8.30 a 12.30 y de 14 a 15.30 horas.

🌎 Dirección
📍Uruguay 4415, Victoria, San Fernando.
  Pcia. De Buenos Aires. CP 1644
${fechaActual}`;
        }
    }

    function actualizarVistaPreviaMensaje() {
        if (!clienteSeleccionado) return;
        const tipo = parseInt($('input[name="tipoMensaje"]:checked').val() || 1);
        const seleccion = obtenerComprobantesSeleccionados();
        const texto = construirTextoMensaje(tipo, clienteSeleccionado, seleccion);
        $('#modal-wpp-preview').val(texto);
    }

    // Botón Enviar WhatsApp
    $('#btn-confirmar-envio-wpp').on('click', function() {
        if (!clienteSeleccionado) return;

        const seleccion = obtenerComprobantesSeleccionados();
        if (seleccion.totalGeneral <= 0 && seleccion.facturas.length === 0 && seleccion.remitos.length === 0) {
            alert('Debe seleccionar al menos una factura o remito para generar la cobranza.');
            return;
        }

        const telefono = $('#modal-wpp-telefono').val().trim();
        const contactoNombre = $('#modal-wpp-contacto-nombre').val().trim();
        const mensajeTexto = $('#modal-wpp-preview').val();
        const tipoMensaje = parseInt($('input[name="tipoMensaje"]:checked').val() || 1);

        if (!telefono) {
            alert('Por favor complete el número de WhatsApp de destino.');
            $('#modal-wpp-telefono').focus();
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Registrando...');

        // 1. Guardar o actualizar el teléfono y contacto en la base de datos
        $.ajax({
            url: 'api/mayoristas_controller.php?action=guardar_contacto',
            type: 'POST',
            data: {
                cod_client: clienteSeleccionado.COD_CLIENT,
                razon_soci: clienteSeleccionado.RAZON_SOCI,
                telefono_wpp: telefono,
                contacto_nombre: contactoNombre
            },
            dataType: 'json'
        });

        // 2. Registrar en el historial de cobranzas solo los comprobantes seleccionados
        const comprobantesSnapshot = {
            FACTURAS: seleccion.facturas,
            REMITOS: seleccion.remitos
        };

        $.ajax({
            url: 'api/mayoristas_controller.php?action=registrar_envio',
            type: 'POST',
            data: {
                cod_client: clienteSeleccionado.COD_CLIENT,
                razon_soci: clienteSeleccionado.RAZON_SOCI,
                cod_vended: clienteSeleccionado.COD_VENDED,
                tipo_mensaje: tipoMensaje,
                monto_factura: seleccion.totalFacturas,
                monto_remito: seleccion.totalRemitos,
                telefono_destino: telefono,
                mensaje_enviado: mensajeTexto,
                comprobantes_json: JSON.stringify(comprobantesSnapshot)
            },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fa-brands fa-whatsapp me-1"></i> Abrir WhatsApp y Registrar');
                $('#modalGenerarWpp').modal('hide');

                // 3. Abrir WhatsApp Web / App en nueva pestaña
                const telLimpio = telefono.replace(/[^0-9]/g, '');
                const urlWpp = `https://api.whatsapp.com/send?phone=${telLimpio}&text=${encodeURIComponent(mensajeTexto)}`;
                window.open(urlWpp, '_blank');

                // Refrescar vistas
                cargarCobranzasPendientes();
                cargarHistorialCobranzas();
            },
            error: function(err) {
                btn.prop('disabled', false).html('<i class="fa-brands fa-whatsapp me-1"></i> Abrir WhatsApp y Registrar');
                alert('Error al registrar la cobranza: ' + err.statusText);
            }
        });
    });

    // =========================================================================
    // 3. MODAL DE GESTIÓN DE CONTACTOS WHATSAPP
    // =========================================================================
    $(document).on('click', '.btn-editar-contacto', function() {
        const clienteData = JSON.parse(decodeURIComponent($(this).data('cliente')));
        $('#contacto-cod-client').val(clienteData.COD_CLIENT);
        $('#contacto-razon-soci').val(clienteData.RAZON_SOCI);
        $('#contacto-telefono').val(clienteData.TELEFONO_WPP || '');
        $('#contacto-nombre').val(clienteData.CONTACTO_NOMBRE || '');
        $('#contacto-modal-titulo').text(`Contacto: ${clienteData.RAZON_SOCI}`);
        $('#modalContacto').modal('show');
    });

    $('#btn-guardar-contacto').on('click', function() {
        const codClient = $('#contacto-cod-client').val().trim();
        const razonSoci = $('#contacto-razon-soci').val().trim();
        const telefono = $('#contacto-telefono').val().trim();
        const nombre = $('#contacto-nombre').val().trim();

        if (!telefono) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Atención', 'Ingrese un número de teléfono válido.', 'warning');
            } else {
                alert('Ingrese un número de teléfono válido.');
            }
            $('#contacto-telefono').focus();
            return;
        }

        const btnGuardar = $(this);
        btnGuardar.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: 'api/mayoristas_controller.php?action=guardar_contacto',
            type: 'POST',
            data: {
                cod_client: codClient,
                razon_soci: razonSoci,
                telefono_wpp: telefono,
                contacto_nombre: nombre
            },
            dataType: 'json',
            success: function(res) {
                btnGuardar.prop('disabled', false).html('Guardar Contacto');
                if (res.success) {
                    $('#modalContacto').modal('hide');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Contacto Guardado',
                            text: 'El número y nombre de contacto fueron actualizados.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    cargarCobranzasPendientes();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', res.message, 'error');
                    } else {
                        alert('Error: ' + res.message);
                    }
                }
            },
            error: function(err) {
                btnGuardar.prop('disabled', false).html('Guardar Contacto');
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'No se pudo guardar el contacto en la base de datos.', 'error');
                } else {
                    alert('Error en el servidor al guardar el contacto.');
                }
            }
        });
    });

    // =========================================================================
    // 4. HISTORIAL DE COBRANZAS Y VINCULACIÓN DE RECIBOS
    // =========================================================================
    function cargarHistorialCobranzas() {
        const codVended = $('#filtro-vendedor-historial').val() || 'TODOS';
        const estado = $('#filtro-estado-historial').val() || 'TODOS';

        $('#loading-historial').removeClass('d-none');

        $.ajax({
            url: `api/mayoristas_controller.php?action=listar_historial&cod_vended=${codVended}&estado=${estado}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                $('#loading-historial').addClass('d-none');
                if (res.success) {
                    renderizarTablaHistorial(res.data);
                }
            },
            error: function(err) {
                $('#loading-historial').addClass('d-none');
                console.error("Error cargando historial", err);
            }
        });
    }

    function renderizarTablaHistorial(data) {
        if ($.fn.DataTable.isDataTable('#tabla-mayoristas-historial')) {
            $('#tabla-mayoristas-historial').DataTable().destroy();
        }

        tablaHistorial = $('#tabla-mayoristas-historial').DataTable({
            data: data,
            responsive: true,
            scrollY: '55vh',
            scrollCollapse: true,
            paging: true,
            pageLength: 100,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            columns: [
                {
                    data: 'ID',
                    title: '# ID',
                    render: function(data) {
                        return `<span class="fw-bold">#${data}</span>`;
                    }
                },
                {
                    data: 'FECHA_ENVIO',
                    title: 'Fecha Envío',
                    render: function(data) {
                        return `<small>${data}</small>`;
                    }
                },
                {
                    data: 'RAZON_SOCI',
                    title: 'Cliente',
                    render: function(data, type, row) {
                        return `<strong>${data}</strong><br><small class="text-muted font-monospace">${row.COD_CLIENT}</small>`;
                    }
                },
                {
                    data: 'COD_VENDED',
                    title: 'Vendedor',
                    render: function(data) {
                        return `<span class="badge bg-light text-dark border">${data}</span>`;
                    }
                },
                {
                    data: 'TOTAL_PROPUESTO',
                    title: 'Total Enviado',
                    className: 'text-end',
                    render: function(data, type, row) {
                        return `<span class="fw-bold">${formatoMoneda(data)}</span>
                                <small class="text-muted d-block">Fac: ${formatoMoneda(row.MONTO_FACTURA)} | Rem: ${formatoMoneda(row.MONTO_REMITO)}</small>`;
                    }
                },
                {
                    data: 'ESTADO',
                    title: 'Estado',
                    render: function(data) {
                        if (data === 'PAGADO' || data === 'ABONADO') {
                            return `<span class="badge bg-success"><i class="fa-solid fa-check-double me-1"></i>Pagada</span>`;
                        }
                        return `<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>Enviada</span>`;
                    }
                },
                {
                    data: 'FECHA_PAGO',
                    title: 'Fecha de Pago',
                    render: function(data, type, row) {
                        if (data) {
                            return `<span class="fw-bold text-success">${formatoFecha(data)}</span>`;
                        }
                        return `<span class="text-muted small">Pendiente</span>`;
                    }
                },
                {
                    data: 'DIAS_A_PAGO',
                    title: 'Días a Pago',
                    className: 'text-center',
                    render: function(data) {
                        if (data !== null && data !== undefined) {
                            let badge = 'bg-success';
                            if (data > 15) badge = 'bg-warning text-dark';
                            if (data > 30) badge = 'bg-danger';
                            return `<span class="badge ${badge}">${data} días</span>`;
                        }
                        return `<span class="text-muted">-</span>`;
                    }
                },
                {
                    data: 'OBSERVACIONES_GESTION',
                    title: 'Observaciones / Gestión',
                    render: function(data) {
                        if (data && data.trim() !== '') {
                            return `<small class="text-dark">${data}</small>`;
                        }
                        return `<span class="text-muted small">-</span>`;
                    }
                },
                {
                    data: null,
                    title: 'Acción',
                    orderable: false,
                    className: 'text-center',
                    render: function(data, type, row) {
                        const rowJson = encodeURIComponent(JSON.stringify(row));
                        const isPagado = row.ESTADO === 'PAGADO' || row.ESTADO === 'ABONADO';
                        const btnColor = isPagado ? 'btn-outline-success' : 'btn-outline-primary';
                        const btnIcon = isPagado ? 'fa-solid fa-pen-to-square' : 'fa-solid fa-circle-check';
                        const btnTitle = isPagado ? 'Modificar Estado / Gestión' : 'Marcar como Pagada / Gestionar';
                        
                        return `
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-info btn-ver-detalle-historial" data-cobranza="${rowJson}" title="Ver Detalle de Facturas y Remitos Enviados">
                                    <i class="fa-solid fa-list-ul"></i>
                                </button>
                                <button class="btn ${btnColor} btn-gestionar-estado" data-cobranza="${rowJson}" title="${btnTitle}">
                                    <i class="${btnIcon}"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-eliminar-cobranza" data-id="${row.ID}" data-cliente="${row.RAZON_SOCI}" title="Eliminar Cobranza">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, 'desc']]
        });
    }

    // Modal de Detalle Histórico de Comprobantes Enviados
    $(document).on('click', '.btn-ver-detalle-historial', function() {
        const cob = JSON.parse(decodeURIComponent($(this).data('cobranza')));
        
        $('#hist-modal-cliente').text(`${cob.RAZON_SOCI} (${cob.COD_CLIENT})`);
        $('#hist-modal-meta').text(`Propuesta #${cob.ID} | Vendedor: ${cob.COD_VENDED} | Enviado: ${cob.FECHA_ENVIO}`);
        $('#hist-modal-total-fac').text(formatoMoneda(cob.MONTO_FACTURA || 0));
        $('#hist-modal-total-rem').text(formatoMoneda(cob.MONTO_REMITO || 0));
        $('#hist-modal-total-gral').text(formatoMoneda(cob.TOTAL_PROPUESTO || 0));
        $('#hist-modal-mensaje-texto').val(cob.MENSAJE_ENVIADO || 'Sin registro de texto.');

        // Renderizar comprobantes guardados en COMPROBANTES_JSON
        let comprobantes = null;
        if (cob.COMPROBANTES_JSON) {
            try {
                comprobantes = typeof cob.COMPROBANTES_JSON === 'string' ? JSON.parse(cob.COMPROBANTES_JSON) : cob.COMPROBANTES_JSON;
            } catch (e) {
                console.error("Error parseando comprobantes_json", e);
            }
        }

        let htmlContainer = '';
        
        if (comprobantes && ((comprobantes.FACTURAS && comprobantes.FACTURAS.length > 0) || (comprobantes.REMITOS && comprobantes.REMITOS.length > 0))) {
            // Tabla Facturas / NCR
            if (comprobantes.FACTURAS && comprobantes.FACTURAS.length > 0) {
                htmlContainer += `
                    <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Facturas / Notas de Crédito Incluidas</h6>
                    <div class="table-responsive border rounded mb-4">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Comprobante</th>
                                    <th>Fecha Emisión</th>
                                    <th>Fecha Prob. Cobro</th>
                                    <th class="text-end">Importe Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                comprobantes.FACTURAS.forEach(f => {
                    const esNC = (f.IMPORTE < 0 || (f.T_COMP && (f.T_COMP.includes('NC') || f.T_COMP.includes('NCR'))));
                    const badgeColor = esNC ? 'bg-danger text-white' : 'bg-secondary';
                    const textColor = esNC ? 'text-danger' : 'text-success';
                    htmlContainer += `
                        <tr>
                            <td><span class="badge ${badgeColor} font-monospace">${f.T_COMP || 'FAC'}</span></td>
                            <td><strong class="font-monospace">${f.N_COMP}</strong></td>
                            <td>${formatoFecha(f.FECHA_EMIS)}</td>
                            <td>${formatoFecha(f.FECHA_PROB_COBRO)}</td>
                            <td class="text-end fw-bold ${textColor}">${formatoMoneda(f.IMPORTE)}</td>
                        </tr>
                    `;
                });
                htmlContainer += `</tbody></table></div>`;
            }

            // Tabla Remitos
            if (comprobantes.REMITOS && comprobantes.REMITOS.length > 0) {
                htmlContainer += `
                    <h6 class="fw-bold mb-2" style="color: #6f42c1;"><i class="fa-solid fa-truck-ramp-box me-1"></i> Remitos Incluidos</h6>
                    <div class="table-responsive border rounded">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo</th>
                                    <th>N° Remito</th>
                                    <th>Fecha Emisión / Mov.</th>
                                    <th class="text-end">Importe Remito</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                comprobantes.REMITOS.forEach(r => {
                    htmlContainer += `
                        <tr>
                            <td><span class="badge bg-purple text-white" style="background-color: #6f42c1;">REM</span></td>
                            <td><strong class="font-monospace">${r.N_COMP}</strong></td>
                            <td>${formatoFecha(r.FECHA_EMIS)}</td>
                            <td class="text-end fw-bold" style="color: #6f42c1;">${formatoMoneda(r.IMPORTE)}</td>
                        </tr>
                    `;
                });
                htmlContainer += `</tbody></table></div>`;
            }
        } else {
            // Si es un registro previo sin JSON individual, mostrar resumen de totales y mensaje
            htmlContainer = `
                <div class="alert alert-info py-3">
                    <i class="fa-solid fa-circle-info me-2"></i> Esta propuesta fue registrada con montos globales de <strong>Facturas: ${formatoMoneda(cob.MONTO_FACTURA)}</strong> y <strong>Remitos: ${formatoMoneda(cob.MONTO_REMITO)}</strong>.
                    <br>Puede consultar el texto completo enviado al cliente en la pestaña <em>"Mensaje de WhatsApp Enviado"</em>.
                </div>
            `;
        }

        $('#hist-detalle-comprobantes-container').html(htmlContainer);
        $('#hist-comprobantes-tab').tab('show');
        $('#modalDetalleHistorial').modal('show');
    });

    // Handler para eliminar cobranza del historial
    $(document).on('click', '.btn-eliminar-cobranza', function() {
        const idCobranza = $(this).data('id');
        const nombreCliente = $(this).data('cliente');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Eliminar Cobranza?',
                text: `Se eliminará la cobranza #${idCobranza} de ${nombreCliente} y todo su registro asociado.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    ejecutarEliminacionCobranza(idCobranza);
                }
            });
        } else {
            if (confirm(`¿Está seguro de eliminar la cobranza #${idCobranza} de ${nombreCliente}?`)) {
                ejecutarEliminacionCobranza(idCobranza);
            }
        }
    });

    function ejecutarEliminacionCobranza(idCobranza) {
        $.ajax({
            url: 'api/mayoristas_controller.php?action=eliminar_cobranza',
            type: 'POST',
            data: { id_cobranza: idCobranza },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('¡Eliminado!', res.message, 'success');
                    }
                    cargarHistorialCobranzas();
                    cargarCobranzasPendientes();
                    cargarReportesMayoristas();
                } else {
                    alert('Error: ' + res.message);
                }
            },
            error: function(err) {
                alert('Error al eliminar la cobranza del servidor.');
            }
        });
    }

    // Modal de Gestión de Estado (Enviado / Pagado)
    $(document).on('click', '.btn-gestionar-estado', function() {
        const cob = JSON.parse(decodeURIComponent($(this).data('cobranza')));
        $('#gestion-id-cobranza').val(cob.ID);
        $('#gestion-cliente-info').text(`${cob.RAZON_SOCI} (${cob.COD_CLIENT})`);
        $('#gestion-monto-info').text(`Total Enviado: ${formatoMoneda(cob.TOTAL_PROPUESTO)} | Propuesta #${cob.ID} (${cob.FECHA_ENVIO})`);
        
        const isPagado = cob.ESTADO === 'PAGADO' || cob.ESTADO === 'ABONADO';
        if (isPagado) {
            $('#radio-estado-pagado').prop('checked', true);
            $('#seccion-datos-pago').show();
        } else {
            $('#radio-estado-pagado').prop('checked', true); // Sugerir marcar como Pagado
            $('#seccion-datos-pago').show();
        }

        const montoSugerido = Math.ceil(parseFloat(cob.MONTO_PAGO_RECIBO || cob.TOTAL_PROPUESTO || 0));
        $('#gestion-fecha-pago').val(cob.FECHA_PAGO || new Date().toISOString().split('T')[0]);
        $('#gestion-monto-pago').val(montoSugerido > 0 ? montoSugerido : '');
        $('#gestion-observaciones').val(cob.OBSERVACIONES_GESTION || '');

        $('#modalGestionarEstado').modal('show');
    });

    $('input[name="radioEstadoGestion"]').on('change', function() {
        if ($(this).val() === 'PAGADO') {
            $('#seccion-datos-pago').slideDown(150);
        } else {
            $('#seccion-datos-pago').slideUp(150);
        }
    });

    $('#btn-guardar-estado-gestion').on('click', function() {
        const idCobranza = $('#gestion-id-cobranza').val();
        const nuevoEstado = $('input[name="radioEstadoGestion"]:checked').val() || 'PAGADO';
        const fechaPago = $('#gestion-fecha-pago').val();
        const montoPago = parseFloat($('#gestion-monto-pago').val() || 0);
        const observaciones = $('#gestion-observaciones').val().trim();

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: 'api/mayoristas_controller.php?action=marcar_gestion',
            type: 'POST',
            data: {
                id_cobranza: idCobranza,
                nuevo_estado: nuevoEstado,
                fecha_pago: fechaPago,
                monto_pago: montoPago,
                observaciones: observaciones
            },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Guardar Gestión');
                if (res.success) {
                    $('#modalGestionarEstado').modal('hide');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Gestión Guardada!',
                            text: res.message,
                            timer: 1600,
                            showConfirmButton: false
                        });
                    }
                    cargarHistorialCobranzas();
                    cargarCobranzasPendientes();
                    cargarReportesMayoristas();
                } else {
                    alert('Error: ' + res.message);
                }
            },
            error: function(err) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Guardar Gestión');
                alert('Error en el servidor al guardar la gestión.');
            }
        });
    });

    // =========================================================================
    // 5. REPORTES Y RENDIMIENTO
    // =========================================================================
    function cargarReportesMayoristas() {
        $.ajax({
            url: 'api/mayoristas_controller.php?action=obtener_reportes',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#reporte-kpi-enviados').text(res.kpis.total_enviados);
                    $('#reporte-kpi-abonados').text(res.kpis.total_abonados);
                    $('#reporte-kpi-monto-cobrado').text(formatoMoneda(res.kpis.monto_total_cobrado));
                    $('#reporte-kpi-promedio-dias').text(`${res.kpis.promedio_dias_pago} días`);

                    renderizarTablaReporteVendedores(res.vendedores);
                }
            }
        });
    }

    let reporteVendedoresData = [];

    function renderizarTablaReporteVendedores(vendedores) {
        reporteVendedoresData = vendedores || [];
        let html = '';
        if (vendedores && vendedores.length > 0) {
            vendedores.forEach((v, index) => {
                const tasaCobro = v.CANT_ENVIOS > 0 ? ((v.CANT_ABONADOS / v.CANT_ENVIOS) * 100).toFixed(1) : 0;
                const collapseId = `collapse-vendedor-${index}`;
                const cantClientes = (v.CLIENTES && v.CLIENTES.length) ? v.CLIENTES.length : 0;

                // Fila Principal del Vendedor
                html += `
                    <tr class="table-group-header align-middle" style="cursor: pointer; background-color: #f8fafc;" data-bs-toggle="collapse" data-bs-target="#${collapseId}">
                        <td class="text-center text-primary fw-bold">
                            <i class="fa-solid fa-chevron-right toggle-icon transition-transform"></i>
                        </td>
                        <td>
                            <span class="badge bg-primary me-1">${v.COD_VENDED}</span> 
                            <strong>${v.NOMBRE_VENDEDOR}</strong>
                            <small class="text-muted ms-1">(${cantClientes} propuestas)</small>
                        </td>
                        <td class="text-center fw-bold">${v.CANT_ENVIOS}</td>
                        <td class="text-center text-success fw-bold">${v.CANT_ABONADOS}</td>
                        <td class="text-center"><span class="badge bg-info text-dark">${tasaCobro}%</span></td>
                        <td class="text-end fw-bold">${formatoMoneda(v.MONTO_PROPUESTO)}</td>
                        <td class="text-end"><strong class="text-success">${formatoMoneda(v.MONTO_COBRADO)}</strong></td>
                        <td class="text-center"><span class="badge bg-secondary">${v.PROMEDIO_DIAS} días</span></td>
                    </tr>
                `;

                // Fila Colapsable con el Detalle de Clientes
                let htmlClientes = '';
                if (v.CLIENTES && v.CLIENTES.length > 0) {
                    v.CLIENTES.forEach(c => {
                        const isPagado = c.ESTADO === 'PAGADO' || c.ESTADO === 'ABONADO';
                        const estadoBadge = isPagado 
                            ? `<span class="badge bg-success"><i class="fa-solid fa-check-double me-1"></i>Pagada</span>` 
                            : `<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>Enviada</span>`;
                        
                        const reciboText = c.NRO_RECIBO ? `<span class="font-monospace text-dark fw-bold">${c.NRO_RECIBO}</span> <small class="text-muted d-block">(${c.FECHA_PAGO || '-'})</small>` : '<span class="text-muted">-</span>';
                        const montoReal = c.MONTO_PAGO_RECIBO !== null ? `<span class="text-success fw-bold">${formatoMoneda(c.MONTO_PAGO_RECIBO)}</span>` : '<span class="text-muted">-</span>';
                        const diasPago = c.DIAS_A_PAGO !== null ? `<span class="badge bg-light text-dark border">${c.DIAS_A_PAGO} días</span>` : '<span class="text-muted">-</span>';
                        
                        let desvioText = '<span class="text-muted">-</span>';
                        if (c.DIFERENCIA_MONTO !== null) {
                            const colorDesv = c.DIFERENCIA_MONTO >= 0 ? 'text-success' : 'text-danger';
                            const sign = c.DIFERENCIA_MONTO > 0 ? '+' : '';
                            desvioText = `<span class="${colorDesv} fw-bold">${sign}${formatoMoneda(c.DIFERENCIA_MONTO)}</span> <small class="text-muted d-block">(${sign}${(c.DIFERENCIA_PORC || 0).toFixed(1)}%)</small>`;
                        }

                        const cobJson = encodeURIComponent(JSON.stringify({
                            ID: c.ID,
                            COD_CLIENT: c.COD_CLIENT,
                            RAZON_SOCI: c.RAZON_SOCI,
                            COD_VENDED: c.COD_VENDED || v.COD_VENDED,
                            ESTADO: isPagado ? 'PAGADO' : 'ENVIADO',
                            FECHA_ENVIO: c.FECHA_ENVIO,
                            FECHA_PAGO: c.FECHA_PAGO,
                            TOTAL_PROPUESTO: c.TOTAL_PROPUESTO,
                            MONTO_FACTURA: c.MONTO_FACTURA,
                            MONTO_REMITO: c.MONTO_REMITO,
                            MONTO_PAGO_RECIBO: c.MONTO_PAGO_RECIBO,
                            OBSERVACIONES_GESTION: c.OBSERVACIONES_GESTION || ''
                        }));

                        const btnColor = isPagado ? 'btn-outline-success' : 'btn-outline-primary';
                        const btnIcon = isPagado ? 'fa-solid fa-pen-to-square' : 'fa-solid fa-circle-check';
                        const btnTitle = isPagado ? 'Modificar Estado / Pago' : 'Cambiar Estado a Pagado';

                        htmlClientes += `
                            <tr class="align-middle">
                                <td class="ps-3">
                                    <span class="badge bg-secondary font-monospace">${c.COD_CLIENT}</span>
                                    <strong class="ms-1 text-dark">${c.RAZON_SOCI}</strong>
                                    <small class="text-muted d-block mt-0" style="font-size: 0.78rem;">Propuesta #${c.ID} | Envío: ${c.FECHA_ENVIO}</small>
                                </td>
                                <td class="text-end fw-bold text-dark">${formatoMoneda(c.TOTAL_PROPUESTO)}</td>
                                <td class="text-center">${estadoBadge}</td>
                                <td>${reciboText}</td>
                                <td class="text-end">${montoReal}</td>
                                <td class="text-center">${diasPago}</td>
                                <td class="text-end">${desvioText}</td>
                                <td class="text-center pe-3">
                                    <button class="btn btn-sm ${btnColor} btn-gestionar-estado py-1 px-2" data-cobranza="${cobJson}" title="${btnTitle}">
                                        <i class="${btnIcon} me-1"></i> ${isPagado ? 'Modificar' : 'Gestionar'}
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    htmlClientes = '<tr><td colspan="8" class="text-center text-muted py-3">No hay cobranzas registradas para este vendedor.</td></tr>';
                }

                html += `
                    <tr class="p-0 border-0">
                        <td colspan="8" class="p-0 border-0">
                            <div class="collapse" id="${collapseId}">
                                <div class="p-3 bg-light border-start border-end border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="small fw-bold text-primary text-uppercase mb-0">
                                            <i class="fa-solid fa-users me-1"></i>Detalle de Clientes y Cobranzas de ${v.NOMBRE_VENDEDOR} (${cantClientes})
                                        </h6>
                                        <span class="badge bg-white text-secondary border">Scroll disponible con encabezados fijos</span>
                                    </div>
                                    <div class="table-responsive bg-white rounded border shadow-sm reporte-detalle-scroll">
                                        <table class="table table-sm table-hover mb-0 align-middle">
                                            <thead class="table-light">
                                                <tr class="text-muted small">
                                                    <th class="ps-3">Cliente / Razón Social</th>
                                                    <th class="text-end">Monto Propuesto</th>
                                                    <th class="text-center">Estado</th>
                                                    <th>Recibo / Fecha</th>
                                                    <th class="text-end">Monto Cobrado</th>
                                                    <th class="text-center">Días a Pago</th>
                                                    <th class="text-end">Desvío</th>
                                                    <th class="text-center pe-3" style="width: 110px;">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${htmlClientes}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="8" class="text-center text-muted py-4">Aún no hay cobranzas gestionadas registradas.</td></tr>';
        }
        $('#cuerpo-tabla-reporte-vendedores').html(html);

        // Rotar icono en acordeon al expandir/contraer
        $('.collapse').on('show.bs.collapse', function () {
            $(this).closest('tr').prev('.table-group-header').find('.toggle-icon').addClass('rotate-90');
        }).on('hide.bs.collapse', function () {
            $(this).closest('tr').prev('.table-group-header').find('.toggle-icon').removeClass('rotate-90');
        });
    }

    // =========================================================================
    // EVENTOS Y FILTROS
    // =========================================================================
    $('#filtro-vendedor-pendientes').on('change', function() {
        cargarCobranzasPendientes();
    });

    $('#btn-refrescar-pendientes').on('click', function() {
        cargarCobranzasPendientes();
    });

    $('#filtro-vendedor-historial, #filtro-estado-historial').on('change', function() {
        cargarHistorialCobranzas();
    });

    $('#btn-refrescar-historial').on('click', function() {
        cargarHistorialCobranzas();
    });

    $('#btn-refrescar-reportes').on('click', function() {
        cargarReportesMayoristas();
    });

    let todosExpandidos = false;
    $('#btn-toggle-todos-reportes').on('click', function() {
        todosExpandidos = !todosExpandidos;
        if (todosExpandidos) {
            $('#cuerpo-tabla-reporte-vendedores .collapse').collapse('show');
        } else {
            $('#cuerpo-tabla-reporte-vendedores .collapse').collapse('hide');
        }
    });

    // Pestañas
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('data-bs-target');
        if (target === '#tab-pendientes') {
            if (tablaPendientes) {
                tablaPendientes.columns.adjust().draw();
            }
        } else if (target === '#tab-historial') {
            cargarHistorialCobranzas();
            if (tablaHistorial) {
                tablaHistorial.columns.adjust().draw();
            }
        } else if (target === '#tab-reportes') {
            cargarReportesMayoristas();
        }
    });

    // Helper para formatear columnas de moneda en hojas de SheetJS
    function aplicarFormatoMonedaSheet(ws, colIndicesMoneda, numFilas) {
        const fmtMoneda = '"$"#,##0;[Red]("$"#,##0);"-"';
        for (let r = 1; r <= numFilas; r++) {
            colIndicesMoneda.forEach(c => {
                const cellRef = XLSX.utils.encode_cell({ r: r, c: c });
                if (ws[cellRef] && typeof ws[cellRef].v === 'number') {
                    ws[cellRef].v = Math.ceil(ws[cellRef].v);
                    ws[cellRef].z = fmtMoneda;
                }
            });
        }
    }

    // Helper para ajustar anchos automáticos de columnas
    function autoAjustarColumnas(ws, dataArray) {
        if (!dataArray || dataArray.length === 0) return;
        const keys = Object.keys(dataArray[0]);
        const colWidths = keys.map(key => {
            let maxLen = key.length;
            dataArray.forEach(row => {
                const val = row[key] !== null && row[key] !== undefined ? String(row[key]) : '';
                if (val.length > maxLen) maxLen = val.length;
            });
            return { wch: Math.min(Math.max(maxLen + 3, 12), 40) };
        });
        ws['!cols'] = colWidths;
    }

    // =========================================================================
    // EXPORTACIÓN A EXCEL EN LAS TRES SOLAPAS CON FORMATO MONEDA
    // =========================================================================

    // 1. Exportar Solapa 1: Pendientes
    $('#btn-exportar-pendientes-excel').on('click', function() {
        if (!tablaPendientes) return;
        const datos = tablaPendientes.rows({ search: 'applied' }).data().toArray();
        if (datos.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Atención', 'No hay datos disponibles para exportar con los filtros actuales.', 'info');
            } else {
                alert('No hay datos para exportar.');
            }
            return;
        }

        const dataExcel = datos.map(row => ({
            'Código Cliente': row.COD_CLIENT,
            'Razón Social': row.RAZON_SOCI,
            'Vendedor': row.COD_VENDED,
            'Camino 1: Factura': Math.ceil(parseFloat(row.TOTAL_FACTURA || 0)),
            'Cant. Facturas': row.CANT_FACTURAS || 0,
            'Camino 2: Remito': Math.ceil(parseFloat(row.TOTAL_REMITO || 0)),
            'Cant. Remitos': row.CANT_REMITOS || 0,
            'Total Pendiente': Math.ceil(parseFloat(row.TOTAL_GENERAL || 0)),
            'Teléfono WhatsApp': row.TELEFONO_WPP || '',
            'Nombre Contacto': row.CONTACTO_NOMBRE || '',
            'Estado Cobranza': (row.ULTIMO_ENVIO && row.ULTIMO_ENVIO.ESTADO) ? row.ULTIMO_ENVIO.ESTADO : 'SIN ENVIAR',
            'Último Envío': (row.ULTIMO_ENVIO && row.ULTIMO_ENVIO.FECHA_ENVIO) ? row.ULTIMO_ENVIO.FECHA_ENVIO : ''
        }));

        const worksheet = XLSX.utils.json_to_sheet(dataExcel);
        // Columnas D (3), F (5) y H (7) son monedas
        aplicarFormatoMonedaSheet(worksheet, [3, 5, 7], dataExcel.length);
        autoAjustarColumnas(worksheet, dataExcel);

        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Cobranzas Pendientes");
        const fechaStr = new Date().toISOString().split('T')[0];
        XLSX.writeFile(workbook, `Cobranzas_Mayoristas_Pendientes_${fechaStr}.xlsx`);
    });

    // 2. Exportar Solapa 2: Historial
    $('#btn-exportar-historial-excel').on('click', function() {
        if (!tablaHistorial) return;
        const datos = tablaHistorial.rows({ search: 'applied' }).data().toArray();
        if (datos.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Atención', 'No hay registros en el historial para exportar.', 'info');
            } else {
                alert('No hay datos para exportar.');
            }
            return;
        }

        const dataExcel = datos.map(row => ({
            'ID Propuesta': row.ID,
            'Fecha Envío': row.FECHA_ENVIO,
            'Código Cliente': row.COD_CLIENT,
            'Razón Social': row.RAZON_SOCI,
            'Vendedor': row.COD_VENDED,
            'Usuario Envío': row.NOMBRE_USUARIO || '',
            'Total Propuesto': Math.ceil(parseFloat(row.TOTAL_PROPUESTO || 0)),
            'Monto Factura': Math.ceil(parseFloat(row.MONTO_FACTURA || 0)),
            'Monto Remito': Math.ceil(parseFloat(row.MONTO_REMITO || 0)),
            'Teléfono Destino': row.TELEFONO_DESTINO || '',
            'Estado': row.ESTADO,
            'N° Recibo': row.NRO_RECIBO || '',
            'Fecha Pago': row.FECHA_PAGO || '',
            'Monto Abonado': row.MONTO_PAGO_RECIBO !== null ? Math.ceil(parseFloat(row.MONTO_PAGO_RECIBO)) : '',
            'Diferencia Monto': row.DIFERENCIA_MONTO !== null ? Math.ceil(parseFloat(row.DIFERENCIA_MONTO)) : '',
            'Diferencia (%)': row.DIFERENCIA_PORC !== null ? (row.DIFERENCIA_PORC / 100) : '',
            'Días a Pago': row.DIAS_A_PAGO !== null ? row.DIAS_A_PAGO : '',
            'Observaciones': row.OBSERVACIONES_GESTION || ''
        }));

        const worksheet = XLSX.utils.json_to_sheet(dataExcel);
        // Columnas G (6), H (7), I (8), N (13) y O (14) son monedas
        aplicarFormatoMonedaSheet(worksheet, [6, 7, 8, 13, 14], dataExcel.length);

        // Formato Porcentaje para columna P (15)
        for (let r = 1; r <= dataExcel.length; r++) {
            const cellRef = XLSX.utils.encode_cell({ r: r, c: 15 });
            if (worksheet[cellRef] && typeof worksheet[cellRef].v === 'number') {
                worksheet[cellRef].z = '0.0%';
            }
        }

        autoAjustarColumnas(worksheet, dataExcel);

        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Historial Cobranzas");
        const fechaStr = new Date().toISOString().split('T')[0];
        XLSX.writeFile(workbook, `Cobranzas_Mayoristas_Historial_${fechaStr}.xlsx`);
    });

    // 3. Exportar Solapa 3: Reportes y Desempeño
    $('#btn-exportar-reportes-excel').on('click', function() {
        if (!reporteVendedoresData || reporteVendedoresData.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Atención', 'No hay métricas de desempeño para exportar.', 'info');
            } else {
                alert('No hay datos de reportes para exportar.');
            }
            return;
        }

        const workbook = XLSX.utils.book_new();

        // Hoja 1: Resumen por Vendedor
        const resumenVendedores = reporteVendedoresData.map(v => {
            const tasaCobro = v.CANT_ENVIOS > 0 ? (v.CANT_ABONADOS / v.CANT_ENVIOS) : 0;
            return {
                'Código Vendedor': v.COD_VENDED,
                'Vendedor': v.NOMBRE_VENDEDOR,
                'Envíos Realizados': v.CANT_ENVIOS,
                'Cobranzas Abonadas': v.CANT_ABONADOS,
                'Efectividad': tasaCobro,
                'Monto Propuesto': Math.ceil(parseFloat(v.MONTO_PROPUESTO || 0)),
                'Monto Cobrado': Math.ceil(parseFloat(v.MONTO_COBRADO || 0)),
                'Promedio Días a Pago': v.PROMEDIO_DIAS
            };
        });
        const wsResumen = XLSX.utils.json_to_sheet(resumenVendedores);
        // Columnas F (5) y G (6) son monedas
        aplicarFormatoMonedaSheet(wsResumen, [5, 6], resumenVendedores.length);
        // Columna E (4) es porcentaje
        for (let r = 1; r <= resumenVendedores.length; r++) {
            const cellRef = XLSX.utils.encode_cell({ r: r, c: 4 });
            if (wsResumen[cellRef] && typeof wsResumen[cellRef].v === 'number') {
                wsResumen[cellRef].z = '0.0%';
            }
        }
        autoAjustarColumnas(wsResumen, resumenVendedores);
        XLSX.utils.book_append_sheet(workbook, wsResumen, "Resumen Vendedores");

        // Hoja 2: Detalle Desglosado por Cliente
        const detalleClientes = [];
        reporteVendedoresData.forEach(v => {
            if (v.CLIENTES && v.CLIENTES.length > 0) {
                v.CLIENTES.forEach(c => {
                    detalleClientes.push({
                        'Vendedor': `${v.COD_VENDED} - ${v.NOMBRE_VENDEDOR}`,
                        'Código Cliente': c.COD_CLIENT,
                        'Razón Social': c.RAZON_SOCI,
                        'Propuesta ID': c.ID,
                        'Fecha Envío': c.FECHA_ENVIO,
                        'Monto Propuesto': Math.ceil(parseFloat(c.TOTAL_PROPUESTO || 0)),
                        'Monto Facturas': Math.ceil(parseFloat(c.MONTO_FACTURA || 0)),
                        'Monto Remitos': Math.ceil(parseFloat(c.MONTO_REMITO || 0)),
                        'Estado': c.ESTADO,
                        'N° Recibo': c.NRO_RECIBO || '',
                        'Fecha Pago': c.FECHA_PAGO || '',
                        'Monto Cobrado': c.MONTO_PAGO_RECIBO !== null ? Math.ceil(parseFloat(c.MONTO_PAGO_RECIBO)) : '',
                        'Días a Pago': c.DIAS_A_PAGO !== null ? c.DIAS_A_PAGO : '',
                        'Diferencia Monto': c.DIFERENCIA_MONTO !== null ? Math.ceil(parseFloat(c.DIFERENCIA_MONTO)) : '',
                        'Diferencia (%)': c.DIFERENCIA_PORC !== null ? (c.DIFERENCIA_PORC / 100) : ''
                    });
                });
            }
        });

        if (detalleClientes.length > 0) {
            const wsDetalle = XLSX.utils.json_to_sheet(detalleClientes);
            // Columnas F (5), G (6), H (7), L (11) y N (13) son monedas
            aplicarFormatoMonedaSheet(wsDetalle, [5, 6, 7, 11, 13], detalleClientes.length);
            // Columna O (14) es porcentaje
            for (let r = 1; r <= detalleClientes.length; r++) {
                const cellRef = XLSX.utils.encode_cell({ r: r, c: 14 });
                if (wsDetalle[cellRef] && typeof wsDetalle[cellRef].v === 'number') {
                    wsDetalle[cellRef].z = '0.0%';
                }
            }
            autoAjustarColumnas(wsDetalle, detalleClientes);
            XLSX.utils.book_append_sheet(workbook, wsDetalle, "Detalle Clientes");
        }

        const fechaStr = new Date().toISOString().split('T')[0];
        XLSX.writeFile(workbook, `Cobranzas_Mayoristas_Reporte_Vendedores_${fechaStr}.xlsx`);
    });

    // Inicializar
    cargarCobranzasPendientes();
});
