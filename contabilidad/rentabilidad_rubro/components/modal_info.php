<?php
/**
 * modal_info.php
 * Modal informativo con criterios y conceptos del reporte de Rentabilidad por Rubro
 */
?>
<div id="modalInfo" class="mi-overlay" style="display:none;"
     role="dialog" aria-modal="true" aria-labelledby="miTitulo">
    <div class="mi-card">

        <!-- ── Header ── -->
        <div class="mi-header">
            <div class="mi-header-icon">
                <i class="bi bi-info-circle-fill"></i>
            </div>
            <div class="mi-header-text">
                <h2 id="miTitulo">Acerca del informe</h2>
                <p>Criterios y conceptos del reporte de Rentabilidad por Rubro</p>
            </div>
            <button class="mi-close" id="miBtnCerrar" type="button" title="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- ── Body con acordeón ── -->
        <div class="mi-body">
            <div class="mi-accordion">

                <!-- 1. ¿Qué muestra? -->
                <div class="mi-item mi-open" data-item="1">
                    <button class="mi-item-header" aria-expanded="true" type="button">
                        <span class="mi-item-icon"><i class="bi bi-question-circle"></i></span>
                        <span class="mi-item-title">¿Qué muestra este informe?</span>
                        <i class="bi bi-chevron-down mi-item-chevron"></i>
                    </button>
                    <div class="mi-item-body">
                        <div class="mi-item-content">
                            <p>Reporte de <strong>rentabilidad bruta</strong> agrupado por dimensión (rubro, origen de producción o categoría). Permite analizar venta, costo, margen y participación relativa de cada dimensión sobre el total del período.</p>
                            <p>Los datos se calculan a partir de la facturación, remitos y ventas de locales propios consolidados en la tabla <code>RO_T_RENT_BRUTA_RUBRO</code>.</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Solapas -->
                <div class="mi-item" data-item="2">
                    <button class="mi-item-header" aria-expanded="false" type="button">
                        <span class="mi-item-icon"><i class="bi bi-grid-3x3-gap"></i></span>
                        <span class="mi-item-title">Solapas disponibles</span>
                        <i class="bi bi-chevron-down mi-item-chevron"></i>
                    </button>
                    <div class="mi-item-body" style="max-height:0;">
                        <div class="mi-item-content">
                            <div class="mi-tab-list">
                                <div class="mi-tab-item">
                                    <span class="mi-tab-badge">1</span>
                                    <div>
                                        <strong>Por Rubro</strong>
                                        <p>Vista principal. Cada columna representa un rubro de producto (ej: CALZADOS, CARTERAS DE CUERO, RELOJES). Las columnas se ordenan por participación de venta descendente.</p>
                                    </div>
                                </div>
                                <div class="mi-tab-item">
                                    <span class="mi-tab-badge">2</span>
                                    <div>
                                        <strong>Por Origen de Producción</strong>
                                        <p>Requiere seleccionar un rubro. Muestra la desagregación por origen: <em>NAC. PROPIO, NAC. TERCERO, IMPORTADO, N/A</em>.</p>
                                    </div>
                                </div>
                                <div class="mi-tab-item">
                                    <span class="mi-tab-badge">3</span>
                                    <div>
                                        <strong>Por Categoría</strong>
                                        <p>Permite filtrar por canal, rubro y color. Muestra el detalle por categoría padre (ej: BANDOLERA, CARTERA, MOCHILA dentro de CARTERAS DE CUERO).</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Filtros -->
                <div class="mi-item" data-item="3">
                    <button class="mi-item-header" aria-expanded="false" type="button">
                        <span class="mi-item-icon"><i class="bi bi-funnel"></i></span>
                        <span class="mi-item-title">Filtros</span>
                        <i class="bi bi-chevron-down mi-item-chevron"></i>
                    </button>
                    <div class="mi-item-body" style="max-height:0;">
                        <div class="mi-item-content">
                            <dl class="mi-dl">
                                <dt><i class="bi bi-calendar3"></i> Período Desde / Hasta</dt>
                                <dd>Rango de meses en formato M-AAAA (ej: <code>1-2025</code>). Si Desde = Hasta se reporta un solo mes.</dd>

                                <dt><i class="bi bi-shop"></i> Canal</dt>
                                <dd>ECOMMERCE, FRANQUICIAS, MAYORISTAS, OTROS, LOCALES PROPIOS, o sin filtrar (todos los canales).</dd>

                                <dt><i class="bi bi-tag"></i> Rubro / Color</dt>
                                <dd>Disponibles en las solapas 2 y 3 respectivamente. Son filtros en cascada: al elegir un rubro se actualizan automáticamente los colores disponibles para ese rubro.</dd>

                                <dt><i class="bi bi-currency-exchange"></i> Moneda</dt>
                                <dd>ARS o USD. Para USD se usa el TCC promedio del último día hábil de cada mes del período, obtenido de la vista <code>RO_V_DOLAR_OFICIAL_BCRA</code>.</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- 4. Conceptos de la tabla -->
                <div class="mi-item" data-item="4">
                    <button class="mi-item-header" aria-expanded="false" type="button">
                        <span class="mi-item-icon"><i class="bi bi-list-check"></i></span>
                        <span class="mi-item-title">Conceptos de la tabla</span>
                        <i class="bi bi-chevron-down mi-item-chevron"></i>
                    </button>
                    <div class="mi-item-body" style="max-height:0;">
                        <div class="mi-item-content">
                            <table class="mi-table">
                                <thead>
                                    <tr><th>Concepto</th><th>Descripción</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="mi-td-concept">VENTA</td>
                                        <td>Importe neto facturado (sin IVA) en el período seleccionado.</td>
                                    </tr>
                                    <tr>
                                        <td class="mi-td-concept">COSTO</td>
                                        <td>Costo de la mercadería vendida, calculado según tipo de artículo (importado, fabricación propia, kit, etc.).</td>
                                    </tr>
                                    <tr>
                                        <td class="mi-td-concept">RESULTADO BRUTO</td>
                                        <td>Venta − Costo.</td>
                                    </tr>
                                    <tr>
                                        <td class="mi-td-concept">MARGEN BRUTO</td>
                                        <td>Resultado Bruto / Venta. Indica qué porcentaje de la venta queda como margen antes de gastos. Se muestra como porcentaje con un decimal (ej: 42,3&nbsp;%).</td>
                                    </tr>
                                    <tr>
                                        <td class="mi-td-concept">Markup (Venta/Costo)</td>
                                        <td>Coeficiente Venta / Costo. Cuántas veces el costo está contenido en la venta.</td>
                                    </tr>
                                    <tr>
                                        <td class="mi-td-concept">% Participación venta</td>
                                        <td>Venta de la dimensión / Venta total del reporte. Indica el peso relativo de cada rubro, origen o categoría. La columna TOTAL no tiene valor (es el 100&nbsp;%).</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 5. Cards de gastos -->
                <div class="mi-item" data-item="5">
                    <button class="mi-item-header" aria-expanded="false" type="button">
                        <span class="mi-item-icon"><i class="bi bi-calculator"></i></span>
                        <span class="mi-item-title">Cards de gastos sobre venta</span>
                        <i class="bi bi-chevron-down mi-item-chevron"></i>
                    </button>
                    <div class="mi-item-body" style="max-height:0;">
                        <div class="mi-item-content">
                            <p>Las cards debajo de los KPIs principales muestran qué porcentaje de la venta total se va en cada categoría de gasto. Los gastos se obtienen del módulo de Informe Económico (<code>RO_T_RESUMEN_FINAL_IE</code> cruzada con <code>RO_T_RUBROS_CONTABLES</code>).</p>
                            <table class="mi-table" style="margin-top:12px;">
                                <thead>
                                    <tr><th>Card</th><th>Qué incluye</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="mi-td-concept">Gastos Comercialización</td>
                                        <td>Comisiones, fletes, publicidad, packaging comercial, descuentos comerciales.</td>
                                    </tr>
                                    <tr>
                                        <td class="mi-td-concept">Gastos Operativos</td>
                                        <td>Suma de Personal + Ocupación + Otros Operativos. La card muestra el total y debajo el detalle de cada subcategoría.</td>
                                    </tr>
                                    <tr>
                                        <td class="mi-td-concept">Gastos Estructura</td>
                                        <td>Gastos administrativos, honorarios, seguros, gastos generales no asignables a operación directa.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 6. Prorrateo de RECUPEROS y PRORRATEABLES -->
                <div class="mi-item" data-item="6">
                    <button class="mi-item-header" aria-expanded="false" type="button">
                        <span class="mi-item-icon"><i class="bi bi-arrows-angle-contract"></i></span>
                        <span class="mi-item-title">Prorrateo de RECUPEROS y PRORRATEABLES</span>
                        <i class="bi bi-chevron-down mi-item-chevron"></i>
                    </button>
                    <div class="mi-item-body" style="max-height:0;">
                        <div class="mi-item-content">
                            <p>Los rubros internos <strong>RECUPEROS</strong> y <strong>PRORRATEABLES</strong> no representan ventas de producto sino conceptos contables que ajustan el resultado:</p>
                            <ul class="mi-list">
                                <li><strong>RECUPEROS:</strong> recuperos de gastos imputados a la venta (ej: artículo <code>*****REC GScIVA</code>).</li>
                                <li><strong>PRORRATEABLES:</strong> diferencias de precio, descuentos por efectivo, ajustes (<code>*****DIF PRECIO</code>, <code>*****DTOEFECTIV</code>, <code>*****DIFXLIQUI</code>).</li>
                            </ul>
                            <p>Estos conceptos <strong>no se muestran como columnas</strong> en el reporte. Su importe se distribuye entre los rubros normales <em>por mes y por sucursal</em>, proporcional a la participación de venta de cada rubro en esa sucursal en ese mes.</p>
                            <div class="mi-formula">
                                <div class="mi-formula-row">
                                    <span class="mi-formula-label">Coeficiente</span>
                                    <code>coef_rubro = venta_rubro_sucursal_mes / venta_total_sucursal_mes</code>
                                </div>
                                <div class="mi-formula-row">
                                    <span class="mi-formula-label">Ajuste venta</span>
                                    <code>venta_rubro_ajustada += venta_recuperos_sucursal_mes × coef_rubro</code>
                                </div>
                            </div>
                            <p class="mi-note"><i class="bi bi-info-circle"></i> Si una sucursal tiene RECUPEROS pero no tiene venta de rubros normales en ese mes, el importe se asigna al rubro residual <strong>SIN ASIGNAR</strong> para no perderlo.</p>
                        </div>
                    </div>
                </div>

                <!-- 7. Base de cálculo del prorrateo de gastos -->
                <div class="mi-item" data-item="7">
                    <button class="mi-item-header" aria-expanded="false" type="button">
                        <span class="mi-item-icon"><i class="bi bi-percent"></i></span>
                        <span class="mi-item-title">Base de cálculo del prorrateo de gastos</span>
                        <i class="bi bi-chevron-down mi-item-chevron"></i>
                    </button>
                    <div class="mi-item-body" style="max-height:0;">
                        <div class="mi-item-content">
                            <p>Los gastos operativos, comerciales y de estructura se distribuyen entre los rubros usando una <strong>base de coeficiente</strong>: la venta sin IVA del período (códigos de rubro contable <code>1.5.</code>, <code>1.6.</code>, <code>1.7.</code>, <code>1.8.</code> de <code>RO_T_RESUMEN_FINAL_IE</code>).</p>
                            <p>Si esa consulta no devuelve datos, se utiliza la venta total del reporte como <em>fallback</em>.</p>
                            <p>La diferencia entre la base de prorrateo y la venta total visible corresponde al <strong>recupero de promociones</strong> (rubro 1.8.), y se puede ver detallada haciendo clic en el ícono <i class="bi bi-info-circle" style="color:var(--accent);"></i> del chip <em>"Base prorrateo"</em> en el encabezado de la tabla.</p>
                        </div>
                    </div>
                </div>

                <!-- 8. Procesamiento de períodos -->
                <div class="mi-item" data-item="8">
                    <button class="mi-item-header" aria-expanded="false" type="button">
                        <span class="mi-item-icon"><i class="bi bi-calendar-check"></i></span>
                        <span class="mi-item-title">Procesamiento de períodos</span>
                        <i class="bi bi-chevron-down mi-item-chevron"></i>
                    </button>
                    <div class="mi-item-body" style="max-height:0;">
                        <div class="mi-item-content">
                            <p>Si al aplicar un período no hay datos calculados en <code>RO_T_RENT_BRUTA_RUBRO</code>, se abre un modal que permite <strong>procesar los meses faltantes</strong> uno por uno.</p>
                            <p>Esto ejecuta el stored procedure <code>RO_SP_RENTABILIDAD_BRUTA_RUBRO</code>, que recalcula la tabla a partir de la facturación, remitos y ventas de locales propios del período indicado.</p>
                            <p class="mi-note mi-note-warning"><i class="bi bi-exclamation-triangle"></i> El procesamiento puede tardar varios minutos según el volumen del período. No cerrar el navegador durante la ejecución.</p>
                        </div>
                    </div>
                </div>

                <!-- 9. Conversión a USD -->
                <div class="mi-item" data-item="9">
                    <button class="mi-item-header" aria-expanded="false" type="button">
                        <span class="mi-item-icon"><i class="bi bi-currency-exchange"></i></span>
                        <span class="mi-item-title">Conversión a USD</span>
                        <i class="bi bi-chevron-down mi-item-chevron"></i>
                    </button>
                    <div class="mi-item-body" style="max-height:0;">
                        <div class="mi-item-content">
                            <p>Al elegir moneda <strong>USD</strong>, los importes monetarios (Venta, Costo, Resultado Bruto) se dividen por el TCC promedio del período (vista <code>RO_V_DOLAR_OFICIAL_BCRA</code>, último día hábil de cada mes).</p>
                            <p>Los porcentajes <strong>no se convierten</strong>, ya que son ratios cuyo valor es idéntico independientemente de la moneda: Margen Bruto, % Participación venta y las cards de estructura de gastos.</p>
                        </div>
                    </div>
                </div>

                <!-- 10. Exportar a Excel -->
                <div class="mi-item" data-item="10">
                    <button class="mi-item-header" aria-expanded="false" type="button">
                        <span class="mi-item-icon"><i class="bi bi-file-earmark-excel"></i></span>
                        <span class="mi-item-title">Exportar a Excel</span>
                        <i class="bi bi-chevron-down mi-item-chevron"></i>
                    </button>
                    <div class="mi-item-body" style="max-height:0;">
                        <div class="mi-item-content">
                            <p>El botón <strong>Excel</strong> exporta la tabla del reporte tal como se visualiza en pantalla: con los filtros aplicados, en la solapa activa y en la moneda seleccionada. El archivo tiene formato <code>.xlsx</code> y el nombre incluye el rango de período (ej: <code>Rentabilidad_PorRubro_1-2025_3-2025.xlsx</code>).</p>
                            <p>El botón se habilita automáticamente al obtener resultados. Si no hay datos, permanece deshabilitado.</p>
                        </div>
                    </div>
                </div>

            </div><!-- /.mi-accordion -->
        </div><!-- /.mi-body -->

        <!-- ── Footer ── -->
        <div class="mi-footer">
            <button class="mp-btn-primario" id="miBtnEntendido" type="button">
                <i class="bi bi-check-circle-fill"></i>
                <span>Entendido</span>
            </button>
        </div>

    </div><!-- /.mi-card -->
</div><!-- /.mi-overlay -->
