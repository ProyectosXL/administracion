/**
 * editar-estimacion.js - Lógica de PCI adaptada a estructura real
 * Maneja 13 conceptos con TIPO_VALOR (P=Porcentaje, I=Importe)
 */

// Variables globales
let datosDespacho = null;
let conceptos = [];
let estimacionExistente = null;
let estaConfirmada = false;

/* Conceptos cuya alícuota guardada NO coincide con la que rige para la fecha de
   nacionalización del contenedor. Los calcula el backend -ver
   EstimacionCostos::desviosDeAlicuota()- y no el navegador, porque la
   comparación tiene que hacerse DESPUÉS del ajuste de despachante y ese ajuste
   vive en el controller. */
let desviosAlicuota = [];

/* De qué fecha salieron las alícuotas vigentes, para poder decirlo en el aviso. */
let vigenciaActual = null;

// Mapeo de IDs de conceptos según BD real
let CONCEPTOS_ID = {
    FLETE: 1,
    SEGURO: 2,
    DERECHOS: 3,
    TASA_ESTADISTICA: 4,
    IVA_GENERAL: 5,
    IVA_ADICIONAL: 6,
    IIGG: 7,
    IIBB: 8,
    SIM: 9,
    ANTIDUMPING: 10,
    DESPACHANTE: 11,
    TERMINAL: 12,
    SUMA_ASEGURADA: 13
};

// Orden de visualización (incluye conceptos calculados no guardados en BD)
let ORDEN_VISUALIZACION = [
    { tipo: 'calculado', nombre: 'FOB', esEditable: false },
    { tipo: 'concepto', id_ce: 1, nombre: 'Flete' },
    { tipo: 'concepto', id_ce: 2, nombre: 'Seguro' },
    { tipo: 'calculado', nombre: 'CIF', esEditable: false },
    { tipo: 'concepto', id_ce: 3, nombre: 'Derechos' },
    { tipo: 'concepto', id_ce: 4, nombre: 'Tasa estadistica' },
    { tipo: 'calculado', nombre: 'Base imponible', esEditable: false },
    { tipo: 'concepto', id_ce: 5, nombre: 'IVA General' },
    { tipo: 'concepto', id_ce: 6, nombre: 'IVA Adicional' },
    { tipo: 'concepto', id_ce: 7, nombre: 'IIGG' },
    { tipo: 'concepto', id_ce: 8, nombre: 'IIBB' },
    { tipo: 'concepto', id_ce: 9, nombre: 'SIM' },
    { tipo: 'concepto', id_ce: 10, nombre: 'Antidumping' },
    { tipo: 'calculado', nombre: 'Total nacionalización', esEditable: false },
    { tipo: 'concepto', id_ce: 11, nombre: 'Despachante' },
    { tipo: 'concepto', id_ce: 12, nombre: 'Terminal' },
    { tipo: 'calculado', nombre: 'Total Cashflow', esEditable: false },
    { tipo: 'concepto', id_ce: 13, nombre: 'Suma asegurada' }
];

$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const idDespacho = urlParams.get('id');
    
    if (!idDespacho) {
        mostrarError('No se especificó el ID del despacho');
        return;
    }
    
    $('#idDespacho').val(idDespacho);
    cargarDatosEstimacion(idDespacho);
});

/**
 * Cargar datos del despacho y conceptos
 */
function cargarDatosEstimacion(idDespacho) {
    mostrarLoading(true);
    
    $.ajax({
        url: '../../controller/cargarEstimacion.php',
        method: 'GET',
        data: { id: idDespacho },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                datosDespacho = response.data.despacho;
                conceptos = response.data.conceptos;
                estimacionExistente = response.data.estimacion;
                estaConfirmada = response.data.confirmada;
                desviosAlicuota = response.data.desvios || [];
                vigenciaActual = response.data.vigencia || null;

                // Map names case-insensitively
                const mapName = (name) => {
                    const n = name.toLowerCase().trim();
                    if (n === 'flete') return 'FLETE';
                    if (n === 'seguro') return 'SEGURO';
                    if (n === 'derechos') return 'DERECHOS';
                    if (n === 'tasa estadistica' || n === 'tasa estadística') return 'TASA_ESTADISTICA';
                    if (n === 'iva general') return 'IVA_GENERAL';
                    if (n === 'iva adicional') return 'IVA_ADICIONAL';
                    if (n === 'iigg') return 'IIGG';
                    if (n === 'iibb') return 'IIBB';
                    if (n === 'sim') return 'SIM';
                    if (n === 'antidumping') return 'ANTIDUMPING';
                    if (n === 'despachante') return 'DESPACHANTE';
                    if (n === 'terminal') return 'TERMINAL';
                    if (n === 'suma asegurada') return 'SUMA_ASEGURADA';
                    return null;
                };

                // Si estamos en Uruguay, debemos remapear dinámicamente CONCEPTOS_ID y ORDEN_VISUALIZACION
                const esUruguay = $('#entorno').val() === 'uy' || (conceptos.length > 0 && conceptos.some(c => c.ID_CE > 13));
                
                if (esUruguay) {
                    conceptos.forEach(c => {
                        const key = mapName(c.CONCEPTO);
                        if (key) {
                            CONCEPTOS_ID[key] = c.ID_CE;
                        }
                    });

                    // Construir ORDEN_VISUALIZACION específico para UY
                    const ordenUY = [
                        { tipo: 'calculado', nombre: 'FOB', esEditable: false },
                        { tipo: 'concepto', id_ce: CONCEPTOS_ID.FLETE, nombre: 'Flete' },
                        { tipo: 'concepto', id_ce: CONCEPTOS_ID.SEGURO, nombre: 'Seguro' },
                        { tipo: 'calculado', nombre: 'CIF', esEditable: false },
                        { tipo: 'calculado', nombre: 'Base imponible', esEditable: false }
                    ];

                    // Agregar todos los conceptos que no sean Flete, Seguro, Despachante, Terminal ni Suma Asegurada
                    conceptos.forEach(c => {
                        const key = mapName(c.CONCEPTO);
                        if (key !== 'FLETE' && key !== 'SEGURO' && key !== 'DESPACHANTE' && key !== 'TERMINAL' && key !== 'SUMA_ASEGURADA') {
                            ordenUY.push({ tipo: 'concepto', id_ce: c.ID_CE, nombre: c.CONCEPTO });
                        }
                    });

                    // Agregar los campos calculados y conceptos restantes en el orden solicitado por el usuario
                    ordenUY.push({ tipo: 'calculado', nombre: 'Total nacionalización', esEditable: false });
                    ordenUY.push({ tipo: 'concepto', id_ce: CONCEPTOS_ID.DESPACHANTE, nombre: 'Despachante' });
                    ordenUY.push({ tipo: 'concepto', id_ce: CONCEPTOS_ID.TERMINAL, nombre: 'Terminal' });
                    ordenUY.push({ tipo: 'calculado', nombre: 'Total Cashflow', esEditable: false });
                    if (CONCEPTOS_ID.SUMA_ASEGURADA) {
                        ordenUY.push({ tipo: 'concepto', id_ce: CONCEPTOS_ID.SUMA_ASEGURADA, nombre: 'Suma asegurada' });
                    }

                    ORDEN_VISUALIZACION = ordenUY;
                } else {
                    // Buscar nuevos conceptos no mapeados en ORDEN_VISUALIZACION e insertarlos antes de Total Cashflow
                    const idsFijos = ORDEN_VISUALIZACION.filter(item => item.tipo === 'concepto').map(item => item.id_ce);
                    let idxTotalCashflow = ORDEN_VISUALIZACION.findIndex(item => item.tipo === 'calculado' && item.nombre === 'Total Cashflow');
                    
                    conceptos.forEach(c => {
                        if (!idsFijos.includes(c.ID_CE)) {
                            const nuevoItem = { tipo: 'concepto', id_ce: c.ID_CE, nombre: c.CONCEPTO };
                            if (idxTotalCashflow !== -1) {
                                ORDEN_VISUALIZACION.splice(idxTotalCashflow, 0, nuevoItem);
                                idxTotalCashflow++; // Incrementar índice para mantener orden correcto
                            } else {
                                ORDEN_VISUALIZACION.push(nuevoItem);
                            }
                        }
                    });
                }
                
                // DEBUG: Ver valores del concepto DESPACHANTE
                console.log('=== DEBUG DESPACHANTE ===');
                console.log('Despachante del despacho:', datosDespacho.DESPACHANTE);
                console.log('TODOS los conceptos:', conceptos);
                const conceptoDespachante = conceptos.find(c => c.CONCEPTO === 'DESPACHANTE' || c.CONCEPTO === 'Despachante');
                if (conceptoDespachante) {
                    console.log('Concepto DESPACHANTE encontrado:', conceptoDespachante);
                } else {
                    console.log('Concepto DESPACHANTE NO encontrado');
                }
                if (estimacionExistente) {
                    console.log('TODA la estimacion existente:', estimacionExistente);
                    const estimacionDespachante = estimacionExistente.find(e => e.CONCEPTO === 'DESPACHANTE' || e.CONCEPTO === 'Despachante');
                    if (estimacionDespachante) {
                        console.log('Estimacion DESPACHANTE encontrada:', estimacionDespachante);
                    } else {
                        console.log('Estimacion DESPACHANTE NO encontrada');
                    }
                }
                console.log('=========================');
                
                $('#estaConfirmada').val(estaConfirmada ? '1' : '0');
                
                mostrarInformacionDespacho();
                generarFormularioConceptos();
                mostrarOrigenAlicuotas(response.data.vigencia);
                mostrarDesviosDeAlicuota();

                /* Confirmado ya NO bloquea el formulario. Los costos de
                   nacionalización se terminan de conocer después de confirmar
                   -llega el despacho, llega la factura del despachante- y
                   corregir un número obligaba a borrar el contenedor entero.
                   Lo que se mantiene es el aviso: editar acá cambia un dato
                   que el cashflow de Finanzas ya está leyendo. */
                if (estaConfirmada) {
                    marcarConfirmadoEditable();
                }
            } else {
                mostrarError(response.message || 'Error al cargar los datos');
            }
            mostrarLoading(false);
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            mostrarError('Error al conectar con el servidor');
            mostrarLoading(false);
        }
    });
}

/**
 * Mostrar información del despacho
 */
function mostrarInformacionDespacho() {
    $('#despacheInfo').text(`Despacho #${datosDespacho.ID} - ${datosDespacho.PROVEEDOR}`);
    $('#infoProveedor').text(datosDespacho.PROVEEDOR);
    $('#infoContenedor').text(datosDespacho.CONTENEDOR);
    $('#infoMaterial').text(datosDespacho.MATERIAL);
    $('#infoOrdenCompra').text(datosDespacho.ORDEN_COMPRA);
    $('#valorFOB').val(datosDespacho.VALOR_FOB_DOLAR);

    // Mostrar OCs vinculadas si hay hijas en el mismo contenedor
    const cantOcs = parseInt(datosDespacho.CANT_OCS) || 1;
    if (cantOcs > 1 && datosDespacho.OCS_VINCULADAS) {
        $('#infoOcsVinculadas').text(datosDespacho.OCS_VINCULADAS);
        $('#infoOcsVinculadasContainer').show();
    }

    if (estaConfirmada) {
        // "editable" en el badge y no sólo en los campos: el usuario venía de
        // una versión donde confirmado significaba que la pantalla era de
        // sólo lectura, y los inputs habilitados solos se leen como un error.
        $('#estadoBadge').html('<i class="bi bi-check-circle-fill"></i> Confirmado · editable')
            .removeClass('badge-warning').addClass('badge-success')
            .attr('title', 'Los importes se pueden seguir corrigiendo; el contenedor sigue confirmado');
    }
}

/**
 * Generar formulario con todos los conceptos en el orden correcto
 */
function generarFormularioConceptos() {
    const tbody = $('#tablaConceptos');
    tbody.empty();
    
    // Generar filas según ORDEN_VISUALIZACION
    ORDEN_VISUALIZACION.forEach((item, index) => {
        let fila;
        
        if (item.tipo === 'calculado') {
            // Fila de valor calculado (FOB, CIF, Base Imponible, etc.)
            fila = generarFilaCalculada(item.nombre);
        } else {
            // Fila de concepto de BD
            const concepto = conceptos.find(c => c.ID_CE === item.id_ce);
            if (!concepto) return; // Si no existe el concepto, saltar
            
            const valorExistente = estimacionExistente ? 
                estimacionExistente.find(e => e.ID_CE == concepto.ID_CE) : null;
            
            const param1 = valorExistente ? valorExistente.VALOR_DEFAULT_1 : concepto.VALOR_DEFAULT_1;
            const param2 = valorExistente ? valorExistente.VALOR_DEFAULT_2 : concepto.VALOR_DEFAULT_2;
            const confirmado = valorExistente ? valorExistente.CONFIRMADO : 0;

            // Determinar importe inicial:
            // - Si hay IMPORTE guardado (confirmado o borrador): usar ese valor
            // - Si no hay estimación previa: usar parámetro como valor inicial
            let importe = 0;
            let tieneOverride = false;

            if (valorExistente && valorExistente.IMPORTE !== null) {
                // Usar IMPORTE guardado en BD (borrador o confirmado)
                const importeGuardado = parseFloat(valorExistente.IMPORTE);
                importe = !isNaN(importeGuardado) ? importeGuardado : 0;
                // Para tipo P: marcar override solo si el importe guardado es mayor a cero
                tieneOverride = (concepto.TIPO_VALOR === 'P' && importe > 0.01) || (confirmado === 1);
            } else {
                // Sin estimación previa: usar parámetro como valor inicial
                if (param1 !== null && param1 !== undefined) {
                    const param1Num = parseFloat(param1);
                    importe = !isNaN(param1Num) ? param1Num : 0;
                }
            }

            fila = generarFilaConcepto(concepto, param1, param2, importe, confirmado, index, tieneOverride);
        }
        
        tbody.append(fila);
    });
    
    // Calcular importes iniciales
    calcularTodosLosConceptos();
}

/**
 * Generar fila para valores calculados no editables (FOB, CIF, etc.)
 */
function generarFilaCalculada(nombre) {
    return `
        <tr class="fila-calculada" data-concepto-calculado="${nombre}">
            <td class="concepto-nombre">
                <strong>${nombre}</strong>
            </td>
            <td class="text-center">
                <span class="badge bg-warning">Calculado</span>
            </td>
            <td class="text-center">
                <span class="parametro-badge">-</span>
            </td>
            <td class="text-center">
                <span class="parametro-badge">-</span>
            </td>
            <td>
                <input type="text" 
                       class="form-control-plaintext importe-calculado text-end fw-bold" 
                       value="0,00" 
                       readonly
                       style="background-color: #f8f9fa;">
            </td>
        </tr>
    `;
}

/**
 * Generar HTML para una fila de concepto
 */
function generarFilaConcepto(concepto, param1, param2, importe, confirmado, index, tieneOverride = false) {
    const tipoValor = concepto.TIPO_VALOR; // 'P' = Porcentaje, 'I' = Importe
    const tipoTexto = tipoValor === 'P' ? 'Porcentaje' : 'Importe Fijo';
    const param1Display = param1 !== null ? formatearParametro(param1, tipoValor) : '-';
    const param2Display = param2 !== null ? formatearParametro(param2, tipoValor) : '-';
    
    // Confirmado YA NO implica readonly: los importes se siguen editando
    // después de confirmar. La clase 'confirmado' queda porque es la marca
    // visual de que este contenedor ya pasó por confirmación, que es
    // información distinta de "no se puede tocar".
    const esConfirmado = confirmado === 1;
    const readonlyAttr = '';
    const confirmadoClass = esConfirmado ? 'confirmado' : '';

    // Para tipo P: envolver el input con el ícono de override.
    // Un importe confirmado siempre es un override -es un valor que alguien
    // fijó- así que el recálculo automático no debe pisarlo al abrir.
    const overrideActivo = tieneOverride;
    let celdaImporte;
    if (tipoValor === 'P') {
        celdaImporte = `
            <div class="input-override-wrapper">
                <input type="text"
                       class="form-control importe-editable text-end ${overrideActivo ? 'override-activo' : ''}"
                       value="${formatearMoneda(importe)}"
                       data-valor="${importe}"
                       data-override="${overrideActivo ? 'true' : 'false'}"
                       onkeyup="handleImporteChange(this)"
                       onblur="formatearCampoMoneda(this)">
                <button type="button"
                        class="btn btn-reset-override ${overrideActivo ? '' : 'd-none'}"
                        onclick="resetearOverride(this)"
                        title="Valor editado manualmente. Haga clic para recalcular automáticamente">
                    <i class="bi bi-pencil-fill"></i>
                </button>
            </div>`;
    } else {
        celdaImporte = `
            <input type="text"
                   class="form-control importe-editable text-end ${confirmadoClass}"
                   value="${formatearMoneda(importe)}"
                   data-valor="${importe}"
                   data-override="${tieneOverride ? 'true' : 'false'}"
                   ${readonlyAttr}
                   onkeyup="handleImporteChange(this)"
                   onblur="formatearCampoMoneda(this)">`;
    }

    return `
        <tr data-concepto-id="${concepto.ID_CE}"
            data-concepto-nombre="${concepto.CONCEPTO}"
            data-tipo="${tipoValor}"
            data-confirmado="${confirmado || 0}">
            <td class="concepto-nombre">
                <strong>${concepto.CONCEPTO}</strong>
            </td>
            <td class="text-center">
                <span class="badge bg-${tipoValor === 'P' ? 'info' : 'secondary'}">${tipoTexto}</span>
            </td>
            <td class="text-center">
                <span class="parametro-badge">${param1Display}</span>
                <input type="hidden" class="param1" value="${param1 || 0}">
            </td>
            <td class="text-center">
                <span class="parametro-badge">${param2Display}</span>
                <input type="hidden" class="param2" value="${param2 || 0}">
            </td>
            <td>${celdaImporte}</td>
        </tr>
    `;
}

/**
 * Manejar cambio en un importe editable
 */
function handleImporteChange(input) {
    const valor = parseNumero($(input).val());
    $(input).data('valor', valor);

    // Si es tipo P y el usuario lo edita manualmente, marcar como override
    // para que el recálculo automático no sobreescriba el valor ingresado
    const $row = $(input).closest('tr');
    if ($row.data('tipo') === 'P') {
        $(input).attr('data-override', 'true').addClass('override-activo');
        $row.find('.btn-reset-override').removeClass('d-none');
    }

    // Recalcular todos los conceptos
    calcularTodosLosConceptos();
}

/**
 * Restaurar el cálculo automático de un concepto tipo P,
 * quitando el override manual y recalculando desde los parámetros.
 */
function resetearOverride(btn) {
    const $row = $(btn).closest('tr');
    const $input = $row.find('.importe-editable');

    $input.attr('data-override', 'false').removeClass('override-activo');
    $(btn).addClass('d-none');

    // Recalcular para que el valor vuelva al automático
    calcularTodosLosConceptos();
}

/**
 * Calcular todos los conceptos según la lógica de negocio.
 * Para conceptos tipo P con override manual, se usa el valor guardado en lugar del calculado.
 */
function calcularTodosLosConceptos() {
    const fob = parseFloat($('#valorFOB').val()) || 0;
    const esUruguay = $('#entorno').val() === 'uy' || (conceptos.length > 0 && conceptos.some(c => c.ID_CE > 13));

    if (esUruguay) {
        // --- CÁLCULO URUGUAY ---
        // 1. FOB (valor fijo del despacho)
        setValorCalculado('FOB', fob);

        // 2. Flete (ID_CE de Flete en UY)
        const flete = getConceptoValorEditable(CONCEPTOS_ID.FLETE);

        // 3. Seguro (ID_CE de Seguro en UY)
        let seguro;
        const seguroOverride = getInputOverride(CONCEPTOS_ID.SEGURO);
        if (seguroOverride !== null) {
            seguro = seguroOverride;
        } else {
            const seguroParam = getConceptoParam1(CONCEPTOS_ID.SEGURO);
            const conceptoSeguroObj = conceptos.find(c => c.ID_CE === CONCEPTOS_ID.SEGURO);
            if (conceptoSeguroObj && conceptoSeguroObj.TIPO_VALOR === 'P') {
                seguro = (fob + flete) * seguroParam;
            } else {
                seguro = seguroParam; // Importe Fijo (ej: 40.00 en UY)
            }
            setConceptoValorCalculado(CONCEPTOS_ID.SEGURO, seguro);
        }

        // 4. CIF = FOB + Flete + Seguro
        const cif = fob + flete + seguro;
        setValorCalculado('CIF', cif);

        // En Uruguay, los porcentajes por defecto se calculan sobre el CIF.
        // Recorremos todos los conceptos (excepto Flete y Seguro que ya están calculados)
        let totalNac = 0;
        let despachante = 0;
        let terminal = 0;

        conceptos.forEach(c => {
            if (c.ID_CE === CONCEPTOS_ID.FLETE || c.ID_CE === CONCEPTOS_ID.SEGURO) {
                return;
            }

            const idCe = c.ID_CE;
            const override = getInputOverride(idCe);
            let valor = 0;

            if (override !== null) {
                valor = override;
            } else {
                const param1 = getConceptoParam1(idCe);
                if (c.TIPO_VALOR === 'P') {
                    // Porcentual: base es CIF (o el id_ref_concepto si se especificó)
                    const idRef = c.ID_REF_CONCEPTO;
                    let baseCalculo = cif;
                    if (idRef) {
                        baseCalculo = getConceptoValorActual(idRef);
                    }
                    valor = baseCalculo * param1;
                } else {
                    // Importe Fijo
                    valor = param1;
                }
                setConceptoValorCalculado(idCe, valor);
            }

            // Acumular
            if (idCe === CONCEPTOS_ID.DESPACHANTE) {
                despachante = valor;
            } else if (idCe === CONCEPTOS_ID.TERMINAL) {
                terminal = valor;
            } else if (idCe === CONCEPTOS_ID.SUMA_ASEGURADA) {
                // Suma asegurada no suma al total de nacionalización o cashflow en la estructura estándar
            } else {
                totalNac += valor;
            }
        });

        // Setear los totales calculados
        setValorCalculado('Base imponible', cif);
        setValorCalculado('Total nacionalización', totalNac);

        const totalCashflow = totalNac + despachante + terminal;
        setValorCalculado('Total Cashflow', totalCashflow);

        // Suma Asegurada (si corresponde)
        const sumaAseguradaOverride = getInputOverride(CONCEPTOS_ID.SUMA_ASEGURADA);
        if (sumaAseguradaOverride === null && CONCEPTOS_ID.SUMA_ASEGURADA) {
            const sumaParam1 = getConceptoParam1(CONCEPTOS_ID.SUMA_ASEGURADA);
            const sumaParam2 = getConceptoParam2(CONCEPTOS_ID.SUMA_ASEGURADA);
            const sumaAsegurada = ((fob + flete) * (1 + sumaParam1)) * (1 + sumaParam2);
            setConceptoValorCalculado(CONCEPTOS_ID.SUMA_ASEGURADA, sumaAsegurada);
        }
        return;
    }

    // --- CÁLCULO ARGENTINA ---
    // 1. FOB (valor fijo del despacho)
    setValorCalculado('FOB', fob);

    // 2. Flete (ID_CE=1): Importe fijo editable
    const flete = getConceptoValorEditable(CONCEPTOS_ID.FLETE);

    // 3. Seguro (ID_CE=2): (FOB + Flete) * Parámetro1  [override posible]
    let seguro;
    const seguroOverride = getInputOverride(CONCEPTOS_ID.SEGURO);
    if (seguroOverride !== null) {
        seguro = seguroOverride;
    } else {
        const seguroParam = getConceptoParam1(CONCEPTOS_ID.SEGURO);
        seguro = (fob + flete) * seguroParam;
        setConceptoValorCalculado(CONCEPTOS_ID.SEGURO, seguro);
    }

    // 4. CIF (calculado, no guardado): FOB + Flete + Seguro
    const cif = fob + flete + seguro;
    setValorCalculado('CIF', cif);

    // 5. Derechos (ID_CE=3): CIF * Parámetro1  [override posible]
    let derechos;
    const derechosOverride = getInputOverride(CONCEPTOS_ID.DERECHOS);
    if (derechosOverride !== null) {
        derechos = derechosOverride;
    } else {
        const derechosParam = getConceptoParam1(CONCEPTOS_ID.DERECHOS);
        derechos = cif * derechosParam;
        setConceptoValorCalculado(CONCEPTOS_ID.DERECHOS, derechos);
    }

    // 6. Tasa Estadística (ID_CE=4): CIF * Parámetro1  [override posible]
    let tasaEstadistica;
    const tasaOverride = getInputOverride(CONCEPTOS_ID.TASA_ESTADISTICA);
    if (tasaOverride !== null) {
        tasaEstadistica = tasaOverride;
    } else {
        const tasaParam = getConceptoParam1(CONCEPTOS_ID.TASA_ESTADISTICA);
        tasaEstadistica = cif * tasaParam;
        setConceptoValorCalculado(CONCEPTOS_ID.TASA_ESTADISTICA, tasaEstadistica);
    }

    // 7. Base Imponible (calculada, no guardada): CIF + Derechos + Tasa
    const baseImponible = cif + derechos + tasaEstadistica;
    setValorCalculado('Base imponible', baseImponible);

    // 8. IVA General (ID_CE=5): Base Imponible * Parámetro1  [override posible]
    let ivaGeneral;
    const ivaGeneralOverride = getInputOverride(CONCEPTOS_ID.IVA_GENERAL);
    if (ivaGeneralOverride !== null) {
        ivaGeneral = ivaGeneralOverride;
    } else {
        const ivaGeneralParam = getConceptoParam1(CONCEPTOS_ID.IVA_GENERAL);
        ivaGeneral = baseImponible * ivaGeneralParam;
        setConceptoValorCalculado(CONCEPTOS_ID.IVA_GENERAL, ivaGeneral);
    }

    // 9. IVA Adicional (ID_CE=6): Base Imponible * Parámetro1  [override posible]
    let ivaAdicional;
    const ivaAdicionalOverride = getInputOverride(CONCEPTOS_ID.IVA_ADICIONAL);
    if (ivaAdicionalOverride !== null) {
        ivaAdicional = ivaAdicionalOverride;
    } else {
        const ivaAdicionalParam = getConceptoParam1(CONCEPTOS_ID.IVA_ADICIONAL);
        ivaAdicional = baseImponible * ivaAdicionalParam;
        setConceptoValorCalculado(CONCEPTOS_ID.IVA_ADICIONAL, ivaAdicional);
    }

    // 10. IIGG (ID_CE=7): Base Imponible * Parámetro1  [override posible]
    let iigg;
    const iiggOverride = getInputOverride(CONCEPTOS_ID.IIGG);
    if (iiggOverride !== null) {
        iigg = iiggOverride;
    } else {
        const iiggParam = getConceptoParam1(CONCEPTOS_ID.IIGG);
        iigg = baseImponible * iiggParam;
        setConceptoValorCalculado(CONCEPTOS_ID.IIGG, iigg);
    }

    // 11. IIBB (ID_CE=8): Base Imponible * Parámetro1  [override posible]
    let iibb;
    const iibbOverride = getInputOverride(CONCEPTOS_ID.IIBB);
    if (iibbOverride !== null) {
        iibb = iibbOverride;
    } else {
        const iibbParam = getConceptoParam1(CONCEPTOS_ID.IIBB);
        iibb = baseImponible * iibbParam;
        setConceptoValorCalculado(CONCEPTOS_ID.IIBB, iibb);
    }

    // 12. SIM (ID_CE=9): Importe fijo editable
    const sim = getConceptoValorEditable(CONCEPTOS_ID.SIM);

    // 13. Antidumping (ID_CE=10): Valor manual editable
    const antidumping = getConceptoValorEditable(CONCEPTOS_ID.ANTIDUMPING);

    // 14. Total Nacionalización (calculado): seguro + derechos + tasa + IVA + IIGG + IIBB + SIM + antidumping
    const totalNac = seguro + derechos + tasaEstadistica + ivaGeneral +
                     ivaAdicional + iigg + iibb + sim + antidumping;
    setValorCalculado('Total nacionalización', totalNac);

    // 15. Despachante (ID_CE=11): ((FOB * 0.01) + Param1) * 1.21  [override posible]
    let despachante;
    const despachanteOverride = getInputOverride(CONCEPTOS_ID.DESPACHANTE);
    if (despachanteOverride !== null) {
        despachante = despachanteOverride;
    } else {
        const despachanteParam1 = getConceptoParam1(CONCEPTOS_ID.DESPACHANTE);
        despachante = ((fob * 0.01) + despachanteParam1) * 1.21;
        setConceptoValorCalculado(CONCEPTOS_ID.DESPACHANTE, despachante);
    }

    // 16. Terminal (ID_CE=12): Importe fijo editable
    const terminal = getConceptoValorEditable(CONCEPTOS_ID.TERMINAL);

    // Calcular conceptos nuevos y acumularlos en totalCashflow
    let acumuladoConceptosNuevos = 0;
    const idsFijos = Object.values(CONCEPTOS_ID);
    
    conceptos.forEach(c => {
        if (!idsFijos.includes(c.ID_CE)) {
            const idCe = c.ID_CE;
            
            // Verificar si el usuario ingresó un override manual
            const override = getInputOverride(idCe);
            let valor = 0;
            
            if (override !== null) {
                valor = override;
            } else {
                // Calcular valor automático
                const param1 = getConceptoParam1(idCe);
                
                if (c.TIPO_VALOR === 'P') {
                    // Es un porcentaje. Verificar si tiene ID_REF_CONCEPTO
                    const idRef = c.ID_REF_CONCEPTO;
                    let baseCalculo = cif; // Base por defecto es CIF
                    
                    if (idRef) {
                        baseCalculo = getConceptoValorActual(idRef);
                    }
                    valor = baseCalculo * param1;
                } else {
                    // Importe Fijo
                    valor = param1;
                }
                
                setConceptoValorCalculado(idCe, valor);
            }
            
            acumuladoConceptosNuevos += valor;
        }
    });

    // 17. Total Cashflow (calculado): Total Nac + Despachante + Terminal + Conceptos Nuevos
    const totalCashflow = totalNac + despachante + terminal + acumuladoConceptosNuevos;
    setValorCalculado('Total Cashflow', totalCashflow);

    // 18. Suma Asegurada (ID_CE=13): ((FOB + Flete) * (1 + Param1)) * (1 + Param2)  [override posible]
    const sumaAseguradaOverride = getInputOverride(CONCEPTOS_ID.SUMA_ASEGURADA);
    if (sumaAseguradaOverride === null) {
        const sumaParam1 = getConceptoParam1(CONCEPTOS_ID.SUMA_ASEGURADA);
        const sumaParam2 = getConceptoParam2(CONCEPTOS_ID.SUMA_ASEGURADA);
        const sumaAsegurada = ((fob + flete) * (1 + sumaParam1)) * (1 + sumaParam2);
        setConceptoValorCalculado(CONCEPTOS_ID.SUMA_ASEGURADA, sumaAsegurada);
    }
}

/**
 * Obtener el valor numérico actual de un concepto
 */
function getConceptoValorActual(idCe) {
    const $row = $(`tr[data-concepto-id="${idCe}"]`);
    if ($row.length > 0) {
        const $input = $row.find('.importe-editable');
        return parseFloat($input.data('valor')) || 0;
    }
    
    // Si se hace referencia por concepto o texto
    if (idCe == 'FOB') return parseFloat($('#valorFOB').val()) || 0;
    if (idCe == 'CIF') {
        const fob = parseFloat($('#valorFOB').val()) || 0;
        const flete = getConceptoValorEditable(CONCEPTOS_ID.FLETE);
        const seguro = getConceptoValorEditable(CONCEPTOS_ID.SEGURO);
        return fob + flete + seguro;
    }
    return 0;
}

/**
 * Obtener valor editable de un concepto por ID
 */
function getConceptoValorEditable(idCe) {
    const $row = $(`tr[data-concepto-id="${idCe}"]`);
    const valor = $row.find('.importe-editable').data('valor');
    return parseFloat(valor) || 0;
}

/**
 * Obtener parámetro 1 de un concepto por ID
 */
function getConceptoParam1(idCe) {
    const $row = $(`tr[data-concepto-id="${idCe}"]`);
    return parseFloat($row.find('.param1').val()) || 0;
}

/**
 * Obtener parámetro 2 de un concepto por ID
 */
function getConceptoParam2(idCe) {
    const $row = $(`tr[data-concepto-id="${idCe}"]`);
    const param2 = $row.find('.param2').val();
    return param2 ? parseFloat(param2) : 1; // Default 1 si no existe
}

/**
 * Obtener valor override de un concepto tipo P (si fue editado manualmente o cargado desde BD).
 * Retorna el valor numérico si tiene override, o null si debe calcularse normalmente.
 */
function getInputOverride(idCe) {
    const $input = $(`tr[data-concepto-id="${idCe}"]`).find('.importe-editable');
    if ($input.attr('data-override') === 'true') {
        return parseFloat($input.data('valor')) || 0;
    }
    return null;
}

/**
 * Establecer valor calculado de un concepto
 */
function setConceptoValorCalculado(idCe, valor) {
    const $row = $(`tr[data-concepto-id="${idCe}"]`);
    const $input = $row.find('.importe-editable');
    const override = $input.attr('data-override') === 'true';

    // NO actualizar si:
    // 1. El usuario está editando el campo actualmente
    // 2. El usuario (o la carga inicial desde BD) marcó un override manual
    //
    // El corte por CONFIRMADO que había acá se fue junto con el bloqueo del
    // formulario, y no hace falta: una fila confirmada nace con override en
    // true -el importe guardado es un valor que alguien fijó- así que el
    // recálculo automático sigue sin pisarla al abrir. La diferencia es que
    // ahora el botón de "volver al automático" funciona también sobre un
    // contenedor confirmado, que antes quedaba muerto.
    if ($input.is(':focus') || override) {
        return;
    }

    // Actualizar el valor calculado en modo borrador
    $input.val(formatearMoneda(valor)).data('valor', valor);
}

/**
 * Establecer valor de fila calculada (FOB, CIF, Base Imponible, etc.)
 */
function setValorCalculado(nombre, valor) {
    const $row = $(`tr[data-concepto-calculado="${nombre}"]`);
    $row.find('.importe-calculado').val(formatearMoneda(valor));
}

/**
 * Guardar estimación
 */
function guardarEstimacion() {
    /* Un contenedor confirmado se guarda igual. Acá había un return que lo
       impedía; lo que queda en su lugar es una confirmación, porque estos
       importes son los que el cashflow de Finanzas lee como gastos de
       nacionalización y cambiarlos mueve ese tablero. */
    if (estaConfirmada) {
        Swal.fire({
            title: 'El contenedor está confirmado',
            html: 'Vas a modificar los importes de una estimación ya confirmada.<br>' +
                  'Sigue confirmada después de guardar, y el cambio se ve también en el ' +
                  'cashflow de Finanzas.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Guardar cambios',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#7066e0'
        }).then((r) => {
            if (r.isConfirmed) enviarEstimacion();
        });
        return;
    }

    enviarEstimacion();
}

/**
 * El envío en sí, separado de la confirmación para que los dos caminos
 * -borrador y confirmado- manden exactamente lo mismo.
 */
function enviarEstimacion() {
    const conceptosData = [];
    
    // Solo guardar conceptos de BD (no los calculados como FOB, CIF, etc.)
    $('#tablaConceptos tr[data-concepto-id]').each(function() {
        const $row = $(this);
        const idCe = $row.data('concepto-id');
        const param1 = parseFloat($row.find('.param1').val()) || null;
        const param2 = parseFloat($row.find('.param2').val()) || null;
        const importe = $row.find('.importe-editable').data('valor') || 0;
        
        conceptosData.push({
            id_ce: idCe,
            valor_default_1: param1,
            valor_default_2: param2,
            importe: parseFloat(importe)
        });
    });
    
    const data = {
        id_mg: parseInt($('#idDespacho').val()),
        conceptos: conceptosData
    };
    
    Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: '../../controller/guardarEstimacion.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: '¡Guardado!',
                    text: response.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    // Volver a la pestaña de proyección de costos
                    window.location.href = '../proyeccionCostos.php';
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Ocurrió un error al guardar la estimación',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    });
}

/**
 * Un contenedor confirmado se sigue editando, y la pantalla lo dice.
 *
 * Reemplaza a bloquearFormulario(), que ponía los importes en readonly y
 * deshabilitaba los dos botones de guardar. Los campos quedan editables y el
 * único cambio es el texto del botón: "Guardar cambios" en vez de "Guardar",
 * porque acá no se está creando una estimación sino corrigiendo una que ya
 * existe y que el cashflow de Finanzas ya está leyendo.
 */
function marcarConfirmadoEditable() {
    $('.importe-editable').prop('readonly', false);
    $('#btnGuardar, #btnGuardarBottom')
        .prop('disabled', false)
        .html('<i class="bi bi-save"></i> Guardar cambios')
        .attr('title', 'El contenedor está confirmado: al guardar sigue confirmado, con los importes nuevos');
}

/**
 * Dice de dónde salieron las alícuotas de la pantalla.
 *
 * Sin este cartel, un importe calculado con la alícuota que regía el día de
 * la nacionalización y uno calculado con la del padrón se ven exactamente
 * igual, y la diferencia es justamente lo que el módulo de vigencias vino a
 * hacer visible.
 */
function mostrarOrigenAlicuotas(vigencia) {
    const $destino = $('#avisoVigencia');
    if (!$destino.length || !vigencia) return;

    if (!vigencia.disponible) {
        $destino
            .html('<i class="bi bi-info-circle"></i> Las alícuotas salen del padrón. ' +
                  'Para que se resuelvan por fecha de nacionalización falta correr ' +
                  '<code>comercioExterior/sql/09_alicuotas_vigencia.sql</code> en esta base.')
            .attr('class', 'alert alert-secondary py-2 px-3 mb-3')
            .show();
        return;
    }

    if (!vigencia.fecha) {
        $destino
            .html('<i class="bi bi-exclamation-triangle"></i> El contenedor no tiene ' +
                  '<strong>fecha de nacionalización</strong> cargada, así que las alícuotas ' +
                  'salen del padrón. Al cargarla, se recalculan con las que regían ese día.')
            .attr('class', 'alert alert-warning py-2 px-3 mb-3')
            .show();
        return;
    }

    const fecha = vigencia.fecha.split('-').reverse().join('/');

    if (vigencia.aplicada) {
        $destino
            .html('<i class="bi bi-calendar-check"></i> Alícuotas vigentes al <strong>' + fecha +
                  '</strong> (fecha de nacionalización), no las de hoy.')
            .attr('class', 'alert alert-info py-2 px-3 mb-3')
            .show();
    } else {
        $destino
            .html('<i class="bi bi-exclamation-triangle"></i> No hay ninguna vigencia cargada que ' +
                  'cubra el <strong>' + fecha + '</strong>, así que las alícuotas salen del padrón.')
            .attr('class', 'alert alert-warning py-2 px-3 mb-3')
            .show();
    }
}

/* ==========================================================================
   ALÍCUOTAS QUE NO COINCIDEN CON LA VIGENTE

   EL VALOR GUARDADO NO SE PISA SOLO. Al abrir una estimación, el detalle
   guardado le gana a la alícuota que acaba de resolver AlicuotasVigencia, y eso
   sigue así: un importe que alguien revisó no cambia por abrir la pantalla.

   Lo que cambia es que ahora la pantalla LO DICE. Hasta acá una estimación
   calculada con una alícuota que ya no rige se veía igual que una al día, y la
   diferencia es justamente lo que el módulo de vigencias vino a hacer visible.

   RECALCULAR ES UNA DECISIÓN, con su botón y su confirmación, y sólo sobre
   estimaciones SIN CONFIRMAR: congelar los números es el punto de confirmar.
   ========================================================================== */

/**
 * Marca las filas desviadas y muestra el aviso con el botón de recalcular.
 *
 * EL AVISO SE MUESTRA TAMBIÉN EN LAS CONFIRMADAS, aunque ahí no haya botón.
 * Es el caso que motivó todo esto -la OC 0000100015881 está confirmada y tiene
 * el IVA Adicional en cero- y esconder el aviso justo donde el número está mal
 * dejaría a la pantalla sin decir nada sobre el único síntoma visible. Lo que
 * no se ofrece es recalcular: eso se resuelve corrigiendo el importe a mano, o
 * desde la base.
 */
function mostrarDesviosDeAlicuota() {
    const $destino = $('#avisoDesvios');
    if (!$destino.length) return;

    // Limpiar una corrida anterior: la función se vuelve a llamar al recalcular.
    $('tr[data-concepto-id]').removeClass('alicuota-desviada');
    $('.alicuota-desviada-nota').remove();

    if (!desviosAlicuota.length) {
        $destino.hide().empty();
        return;
    }

    /* La marca va en la FILA y no sólo en el aviso: con trece conceptos, un
       cartel que dice "2 conceptos" obliga a buscar cuáles. */
    desviosAlicuota.forEach(d => {
        const $fila = $(`tr[data-concepto-id="${d.ID_CE}"]`);
        $fila.addClass('alicuota-desviada');

        const guardado = (d.GUARDADO === null)
            ? 'sin alícuota'
            : formatearParametro(d.GUARDADO, d.TIPO_VALOR);
        const vigente = (d.VIGENTE === null)
            ? 'sin alícuota'
            : formatearParametro(d.VIGENTE, d.TIPO_VALOR);

        $fila.find('td').eq(2).append(
            `<div class="alicuota-desviada-nota" title="La estimación guardó ${guardado}; ` +
            `para la fecha de nacionalización rige ${vigente}.">` +
            `<i class="bi bi-exclamation-triangle-fill"></i> vigente: ${vigente}</div>`
        );
    });

    const cuantos = desviosAlicuota.length;
    const plural = cuantos === 1 ? 'concepto tiene' : 'conceptos tienen';

    /* De qué fecha sale la alícuota vigente. Sin esto el aviso afirma que hay
       una diferencia sin decir contra qué, y "la vigente" es ambiguo: la de hoy
       y la de la fecha de nacionalización no son la misma. */
    const fecha = (vigenciaActual && vigenciaActual.fecha)
        ? vigenciaActual.fecha.split('-').reverse().join('/')
        : null;

    const contra = fecha
        ? `la que regía al <strong>${fecha}</strong> (fecha de nacionalización)`
        : 'la del padrón (el contenedor no tiene fecha de nacionalización cargada)';

    let html = `<i class="bi bi-exclamation-triangle-fill"></i> ${cuantos} ${plural} ` +
               `guardada una alícuota distinta de ${contra}. ` +
               `El valor guardado no se cambió: están marcados en la tabla.`;

    if (estaConfirmada) {
        /* CONFIRMADA: no hay botón. Se dice por qué, porque un aviso que señala
           un problema y no ofrece nada se lee como un error de la pantalla. */
        html += ` <span class="text-muted">La estimación está confirmada, así que ` +
                `no se recalcula: si el número está mal, corregí el importe a mano.</span>`;
    } else {
        html += ` <button type="button" class="btn btn-sm btn-warning ms-2" ` +
                `onclick="recalcularConAlicuotaVigente()">` +
                `<i class="bi bi-arrow-repeat"></i> Recalcular con la alícuota vigente</button>`;
    }

    $destino.html(html).attr('class', 'alert alert-warning py-2 px-3 mb-3').show();
}

/**
 * Recalcula los conceptos desviados con la alícuota vigente.
 *
 * QUÉ HACE, EXACTAMENTE: le pone a cada fila desviada el parámetro que resolvió
 * la vigencia y le SACA EL OVERRIDE, para que calcularTodosLosConceptos() la
 * vuelva a calcular. Sin sacar el override no pasaría nada visible: un importe
 * guardado mayor a cero se interpreta como valor fijado a mano y el recálculo
 * automático lo saltea -ver setConceptoValorCalculado()-.
 *
 * Y ES POR ESO QUE ESTO ES UN BOTÓN Y NO ALGO AUTOMÁTICO. Hoy la pantalla no
 * puede distinguir "este importe lo escribió una persona" de "este importe lo
 * calculó el sistema y quedó guardado": lo infiere de `importe > 0.01` en
 * generarFormularioConceptos(), que da verdadero en los dos casos. Separar las
 * dos cosas de verdad pide una columna en
 * RO_T_IMPORTACIONES_ESTIMACION_DETALLE y está propuesta, no hecha. Mientras
 * esa marca no exista, la única forma segura de recalcular es que lo pida
 * alguien que está mirando la pantalla.
 *
 * NO GUARDA. Deja la pantalla con los números nuevos y el usuario decide si
 * guarda, igual que cualquier otra edición.
 */
function recalcularConAlicuotaVigente() {
    if (estaConfirmada || !desviosAlicuota.length) return;

    const cuantos = desviosAlicuota.length;
    const nombres = desviosAlicuota.map(d => d.CONCEPTO).join(', ');

    Swal.fire({
        title: '¿Recalcular con la alícuota vigente?',
        html: `Se van a recalcular <b>${cuantos}</b> concepto(s) con la alícuota que rige ` +
              `para la fecha de nacionalización:<br><br><b>${nombres}</b><br><br>` +
              `Si alguno de esos importes lo habías escrito a mano, se pierde. ` +
              `Los cambios no se guardan hasta que aprietes Guardar.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, recalcular',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ff9800'
    }).then((res) => {
        if (!res.isConfirmed) return;

        desviosAlicuota.forEach(d => {
            const $fila = $(`tr[data-concepto-id="${d.ID_CE}"]`);
            if (!$fila.length) return;

            const vigente = (d.VIGENTE === null) ? 0 : d.VIGENTE;

            // El parámetro que usa el cálculo, y el badge que lo muestra.
            $fila.find('.param1').val(vigente);
            $fila.find('td').eq(2).find('.parametro-badge')
                .text(d.VIGENTE === null ? '-' : formatearParametro(vigente, d.TIPO_VALOR));

            /* Sacar el override es lo que hace que el recálculo se vea. Sólo
               sobre las filas desviadas: las demás conservan lo que tuvieran. */
            const $input = $fila.find('.importe-editable');
            $input.attr('data-override', 'false').removeClass('override-activo');
            $fila.find('.btn-reset-override').addClass('d-none');

            /* Y también el estado en memoria, para que la comparación no vuelva
               a marcar como desviado algo que ya se recalculó. */
            if (estimacionExistente) {
                const g = estimacionExistente.find(e => e.ID_CE == d.ID_CE);
                if (g) g.VALOR_DEFAULT_1 = vigente;
            }
        });

        desviosAlicuota = [];

        calcularTodosLosConceptos();
        mostrarDesviosDeAlicuota();

        Swal.fire({
            title: 'Recalculado',
            text: 'Revisá los importes y apretá Guardar para que queden en la base.',
            icon: 'success',
            timer: 2600,
            showConfirmButton: false
        });
    });
}

/**
 * Utilidades de formato
 */
function formatearMoneda(valor) {
    return parseFloat(valor).toLocaleString('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatearParametro(valor, tipo) {
    if (tipo === 'P') {
        // Porcentaje: mostrar como % con 2 decimales
        return (parseFloat(valor) * 100).toFixed(2) + '%';
    } else {
        // Importe: mostrar con formato moneda
        return formatearMoneda(valor);
    }
}

function formatearNumero(valor, decimales = 6) {
    return parseFloat(valor).toLocaleString('es-AR', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales
    });
}

function parseNumero(valor) {
    if (typeof valor === 'number') return valor;
    return parseFloat(valor.toString().replace(/\./g, '').replace(',', '.')) || 0;
}

function formatearCampoMoneda(input) {
    const valor = parseNumero($(input).val());
    $(input).val(formatearMoneda(valor)).data('valor', valor);
}

function mostrarLoading(mostrar) {
    if (mostrar) {
        $('#loadingOverlay').fadeIn();
    } else {
        $('#loadingOverlay').fadeOut();
    }
}

function mostrarError(mensaje) {
    Swal.fire({
        title: 'Error',
        text: mensaje,
        icon: 'error',
        confirmButtonColor: '#dc3545'
    }).then(() => {
        window.location.href = '../proyeccionCostos.php';
    });
}
