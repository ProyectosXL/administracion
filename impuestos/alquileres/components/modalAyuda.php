<!-- Modal de Ayuda Contextual -->
<div class="modal fade" id="modalAyuda" tabindex="-1" role="dialog" aria-labelledby="modalAyudaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary">
                <h5 class="modal-title" id="modalAyudaLabel">
                    <i class="fas fa-question-circle"></i> Centro de Ayuda - Gestión de Alquileres
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Pestañas de navegación -->
                <ul class="nav nav-tabs nav-ayuda" id="ayudaTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="flujo-tab" data-toggle="tab" href="#flujo" role="tab" aria-controls="flujo" aria-selected="true">
                            <i class="fas fa-route"></i> Flujo del Proceso
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="faq-tab" data-toggle="tab" href="#faq" role="tab" aria-controls="faq" aria-selected="false">
                            <i class="fas fa-question"></i> Preguntas Frecuentes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="ejemplos-tab" data-toggle="tab" href="#ejemplos" role="tab" aria-controls="ejemplos" aria-selected="false">
                            <i class="fas fa-lightbulb"></i> Ejemplos Prácticos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tecnicas-tab" data-toggle="tab" href="#tecnicas" role="tab" aria-controls="tecnicas" aria-selected="false">
                            <i class="fas fa-cog"></i> Consideraciones Técnicas
                        </a>
                    </li>
                </ul>

                <!-- Contenido de las pestañas -->
                <div class="tab-content mt-4" id="ayudaTabContent">
                    
                    <!-- PESTAÑA: Flujo del Proceso -->
                    <div class="tab-pane fade show active" id="flujo" role="tabpanel" aria-labelledby="flujo-tab">
                        <div class="ayuda-intro">
                            <p class="lead">Este módulo te permite gestionar los gastos mensuales de alquiler de cada sucursal, desde la carga inicial hasta el cierre del período.</p>
                        </div>

                        <div class="accordion" id="accordionFlujo">
                            <!-- Paso 1 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingPaso1">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapsePaso1" aria-expanded="false" aria-controls="collapsePaso1">
                                            <span class="paso-numero">1</span>
                                            <i class="fas fa-pencil-alt"></i> Carga de Datos del Período
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapsePaso1" class="collapse" aria-labelledby="headingPaso1" data-parent="#accordionFlujo">
                                    <div class="card-body">
                                        <p><strong>¿Qué hacer?</strong></p>
                                        <ul>
                                            <li>Seleccioná el mes y año del período que querés gestionar</li>
                                            <li>El sistema cargará automáticamente los conceptos asociados a cada sucursal</li>
                                            <li>Completá los valores de <strong>carga manual</strong> (conceptos 1 , 2 , 3 , 8 , 10 , 11 , 12)</li>
                                            <li>Los demás conceptos se calculan automáticamente según porcentajes configurados</li>
                                        </ul>
                                        <div class="alert alert-info" role="alert">
                                            <i class="fas fa-info-circle"></i> <strong>Tip:</strong> Los valores se guardan automáticamente al escribirlos en cada celda.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Paso 2 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingPaso2">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapsePaso2" aria-expanded="false" aria-controls="collapsePaso2">
                                            <span class="paso-numero">2</span>
                                            <i class="fas fa-calculator"></i> Aplicar Ajuste por Inflación
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapsePaso2" class="collapse" aria-labelledby="headingPaso2" data-parent="#accordionFlujo">
                                    <div class="card-body">
                                        <p><strong>¿Cuándo aplicar?</strong></p>
                                        <p>Si existe un coeficiente de ajuste cargado para el período (por ejemplo, por inflación), podés aplicarlo a los conceptos manuales.</p>
                                        <p><strong>Pasos:</strong></p>
                                        <ol>
                                            <li>Verificá que el coeficiente esté cargado en el sistema</li>
                                            <li>Hacé clic en <span class="btn-example btn-info">Aplicar Ajuste</span></li>
                                            <li>El sistema multiplicará los valores de los conceptos 4, 5 y 18 por el coeficiente</li>
                                            <li>Los inputs se deshabilitarán para evitar modificaciones manuales posteriores</li>
                                        </ol>
                                        <div class="alert alert-warning" role="alert">
                                            <i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> El ajuste solo puede aplicarse una vez por período. Una vez aplicado, no podrás modificar manualmente esos valores.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Paso 3 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingPaso3">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapsePaso3" aria-expanded="false" aria-controls="collapsePaso3">
                                            <span class="paso-numero">3</span>
                                            <i class="fas fa-check-circle"></i> Validación y Procesamiento
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapsePaso3" class="collapse" aria-labelledby="headingPaso3" data-parent="#accordionFlujo">
                                    <div class="card-body">
                                        <p><strong>¿Qué hace el procesamiento?</strong></p>
                                        <p>El botón <span class="btn-example btn-success">Procesar</span> envía los datos al Informe Económico para su integración contable.</p>
                                        <p><strong>Requisitos:</strong></p>
                                        <ul>
                                            <li>El período anterior debe estar cerrado</li>
                                            <li>Todos los valores requeridos deben estar completados</li>
                                        </ul>
                                        <div class="alert alert-success" role="alert">
                                            <i class="fas fa-check"></i> <strong>¿Necesitás reprocesar?</strong> Usá el botón <span class="btn-example btn-danger">Revertir Proc.</span> para eliminar el procesamiento y volver a hacerlo.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Paso 4 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingPaso4">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapsePaso4" aria-expanded="false" aria-controls="collapsePaso4">
                                            <span class="paso-numero">4</span>
                                            <i class="fas fa-lock"></i> Cierre del Período
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapsePaso4" class="collapse" aria-labelledby="headingPaso4" data-parent="#accordionFlujo">
                                    <div class="card-body">
                                        <p><strong>¿Por qué cerrar un período?</strong></p>
                                        <p>Al cerrar, congelás los valores para asegurar la integridad de los datos. Esto evita modificaciones accidentales que puedan afectar reportes o cálculos posteriores.</p>
                                        <p><strong>¿Qué pasa al cerrar?</strong></p>
                                        <ul>
                                            <li>✅ Todos los inputs se deshabilitan</li>
                                            <li>✅ El estado del período cambia a "Cerrado"</li>
                                            <li>✅ El botón cambia a <span class="btn-example btn-primary">Abrir Período</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Paso 5 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingPaso5">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapsePaso5" aria-expanded="false" aria-controls="collapsePaso5">
                                            <span class="paso-numero">5</span>
                                            <i class="fas fa-unlock"></i> Reapertura y Verificación de Diferencias
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapsePaso5" class="collapse" aria-labelledby="headingPaso5" data-parent="#accordionFlujo">
                                    <div class="card-body">
                                        <p><strong>¿Necesitás modificar un período cerrado?</strong></p>
                                        <p>Hacé clic en <span class="btn-example btn-primary">Abrir Período</span> para habilitar las modificaciones.</p>
                                        
                                        <p class="mt-3"><strong>Al cerrar nuevamente después de modificar:</strong></p>
                                        <div class="proceso-diferencias">
                                            <div class="paso-diferencia">
                                                <div class="icono-diferencia bg-warning">1</div>
                                                <div class="texto-diferencia">
                                                    <strong>Detección automática</strong>
                                                    <p>El sistema compara los valores actuales con los guardados en la base de datos</p>
                                                </div>
                                            </div>
                                            <div class="paso-diferencia">
                                                <div class="icono-diferencia bg-info">2</div>
                                                <div class="texto-diferencia">
                                                    <strong>Resaltado visual</strong>
                                                    <p>Las celdas con diferencias se marcan con fondo amarillo</p>
                                                </div>
                                            </div>
                                            <div class="paso-diferencia">
                                                <div class="icono-diferencia bg-success">3</div>
                                                <div class="texto-diferencia">
                                                    <strong>Confirmación</strong>
                                                    <p>Decidís si actualizar los valores o cerrar sin cambios</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-primary mt-3" role="alert">
                                            <i class="fas fa-info-circle"></i> <strong>Tres opciones disponibles:</strong>
                                            <ul class="mb-0 mt-2">
                                                <li><strong>Actualizar y Cerrar:</strong> Guarda los nuevos valores y cierra el período</li>
                                                <li><strong>Cerrar sin Actualizar:</strong> Mantiene los valores originales y cierra</li>
                                                <li><strong>Cancelar:</strong> Vuelve a la edición sin cerrar</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA: FAQ -->
                    <div class="tab-pane fade" id="faq" role="tabpanel" aria-labelledby="faq-tab">
                        <div class="accordion" id="accordionFAQ">
                            
                            <!-- FAQ 1 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingFAQ1">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFAQ1" aria-expanded="false" aria-controls="collapseFAQ1">
                                            <i class="fas fa-question-circle text-info"></i> ¿Por qué no puedo modificar un importe cerrado?
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseFAQ1" class="collapse" aria-labelledby="headingFAQ1" data-parent="#accordionFAQ">
                                    <div class="card-body">
                                        <p>Cuando un período está <strong>cerrado</strong>, todos los valores se congelan para proteger la integridad de los datos. Esto evita modificaciones accidentales que podrían afectar:</p>
                                        <ul>
                                            <li>Reportes contables ya emitidos</li>
                                            <li>Cálculos de períodos posteriores</li>
                                            <li>Auditorías y trazabilidad</li>
                                        </ul>
                                        <p class="mt-2"><strong>Solución:</strong> Hacé clic en el botón <span class="btn-example btn-primary">Abrir Período</span> para habilitar las modificaciones. Recordá volver a cerrar cuando termines.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 2 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingFAQ2">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFAQ2" aria-expanded="false" aria-controls="collapseFAQ2">
                                            <i class="fas fa-question-circle text-info"></i> ¿Qué pasa si cambian los porcentajes después de cerrar?
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseFAQ2" class="collapse" aria-labelledby="headingFAQ2" data-parent="#accordionFAQ">
                                    <div class="card-body">
                                        <p>Los <strong>valores calculados automáticamente</strong> (conceptos 6, 7, 9, 13, 14, 15, 16, 17) se basan en:</p>
                                        <ul>
                                            <li>Porcentajes configurados por sucursal</li>
                                            <li>Ventas del período</li>
                                            <li>Rentabilidad neta o bruta</li>
                                        </ul>
                                        <p class="mt-2"><strong>Comportamiento:</strong></p>
                                        <ul>
                                            <li>Si el período está <strong>abierto</strong>: los valores se recalculan automáticamente al cambiar porcentajes</li>
                                            <li>Si el período está <strong>cerrado</strong>: los valores permanecen congelados hasta que lo reabras</li>
                                        </ul>
                                        <div class="alert alert-warning" role="alert">
                                            <i class="fas fa-exclamation-triangle"></i> Si modificaste porcentajes y necesitás actualizar un período cerrado, abrilo, esperá la recalculación automática y volvé a cerrarlo.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 3 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingFAQ3">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFAQ3" aria-expanded="false" aria-controls="collapseFAQ3">
                                            <i class="fas fa-question-circle text-info"></i> ¿Puedo ver o consultar períodos anteriores?
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseFAQ3" class="collapse" aria-labelledby="headingFAQ3" data-parent="#accordionFAQ">
                                    <div class="card-body">
                                        <p><strong>¡Sí!</strong> Podés navegar a cualquier período usando los selectores de mes y año en la parte superior.</p>
                                        <p>Los períodos anteriores estarán en modo <strong>solo lectura</strong> si están cerrados. Para modificarlos, primero deberás abrirlos.</p>
                                        <div class="alert alert-info" role="alert">
                                            <i class="fas fa-info-circle"></i> <strong>Consejo:</strong> Usá el botón <span class="btn-example btn-primary">Filtrar</span> después de seleccionar el mes/año para cargar los datos del período deseado.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 4 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingFAQ4">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFAQ4" aria-expanded="false" aria-controls="collapseFAQ4">
                                            <i class="fas fa-question-circle text-info"></i> ¿Qué significa "Revertir Procesamiento"?
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseFAQ4" class="collapse" aria-labelledby="headingFAQ4" data-parent="#accordionFAQ">
                                    <div class="card-body">
                                        <p>El botón <span class="btn-example btn-danger">Revertir Proc.</span> elimina el procesamiento enviado al Informe Económico.</p>
                                        <p><strong>¿Cuándo usarlo?</strong></p>
                                        <ul>
                                            <li>Detectaste un error después de procesar</li>
                                            <li>Necesitás modificar valores y volver a enviarlos</li>
                                            <li>El procesamiento falló parcialmente</li>
                                        </ul>
                                        <div class="alert alert-danger" role="alert">
                                            <i class="fas fa-exclamation-circle"></i> <strong>Atención:</strong> Esta acción NO elimina los datos de la tabla local, solo el registro en el sistema de integración. Después de revertir, deberás hacer clic nuevamente en <span class="btn-example btn-success">Procesar</span>.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 5 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingFAQ5">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFAQ5" aria-expanded="false" aria-controls="collapseFAQ5">
                                            <i class="fas fa-question-circle text-info"></i> ¿Qué sucede si no apliqué el ajuste por inflación?
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseFAQ5" class="collapse" aria-labelledby="headingFAQ5" data-parent="#accordionFAQ">
                                    <div class="card-body">
                                        <p>El ajuste es <strong>opcional</strong>. Solo debés aplicarlo si:</p>
                                        <ul>
                                            <li>Existe un coeficiente de ajuste cargado para el período</li>
                                            <li>Los contratos lo requieren (por ejemplo, actualización semestral)</li>
                                        </ul>
                                        <p class="mt-2">Si no aplicás el ajuste, los valores de los conceptos 4, 5 y 18 permanecerán como los cargaste manualmente.</p>
                                        <div class="alert alert-success" role="alert">
                                            <i class="fas fa-check"></i> <strong>Podés verificar</strong> si el ajuste ya fue aplicado: si los inputs de conceptos 4, 5, 18 están deshabilitados (en gris), significa que el ajuste ya se aplicó.
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- PESTAÑA: Ejemplos Prácticos -->
                    <div class="tab-pane fade" id="ejemplos" role="tabpanel" aria-labelledby="ejemplos-tab">
                        <div class="ejemplo-container">
                            
                            <!-- Ejemplo 1 -->
                            <div class="ejemplo-card">
                                <div class="ejemplo-header">
                                    <i class="fas fa-bookmark"></i>
                                    <h5>Ejemplo 1: Carga y Cierre de un Período Nuevo</h5>
                                </div>
                                <div class="ejemplo-body">
                                    <p><strong>Escenario:</strong> Es 5 de junio y necesitás cargar los alquileres de mayo 2025.</p>
                                    
                                    <table class="table table-sm table-bordered mt-3">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Paso</th>
                                                <th>Acción</th>
                                                <th>Resultado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-center"><span class="badge badge-primary">1</span></td>
                                                <td>Seleccioná "Mayo" y "2025" → Clic en Filtrar</td>
                                                <td>Se carga la tabla con todas las sucursales</td>
                                            </tr>
                                            <tr>
                                                <td class="text-center"><span class="badge badge-primary">2</span></td>
                                                <td>Completá los valores de conceptos 4, 5, 18</td>
                                                <td>Los valores se guardan automáticamente</td>
                                            </tr>
                                            <tr>
                                                <td class="text-center"><span class="badge badge-primary">3</span></td>
                                                <td>(Opcional) Aplicar Ajuste si hay coeficiente</td>
                                                <td>Los valores se multiplican y se deshabilitan</td>
                                            </tr>
                                            <tr>
                                                <td class="text-center"><span class="badge badge-primary">4</span></td>
                                                <td>Clic en <span class="btn-example btn-success">Procesar</span></td>
                                                <td>Datos enviados al Informe Económico</td>
                                            </tr>
                                            <tr>
                                                <td class="text-center"><span class="badge badge-primary">5</span></td>
                                                <td>Clic en <span class="btn-example btn-secondary">Cerrar Período</span></td>
                                                <td>Período bloqueado, tabla en modo solo lectura</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Ejemplo 2 -->
                            <div class="ejemplo-card">
                                <div class="ejemplo-header">
                                    <i class="fas fa-bookmark"></i>
                                    <h5>Ejemplo 2: Reapertura con Modificaciones</h5>
                                </div>
                                <div class="ejemplo-body">
                                    <p><strong>Escenario:</strong> El período 5-2025 está cerrado, pero necesitás corregir un valor de la sucursal 02.</p>
                                    
                                    <div class="ejemplo-pasos">
                                        <div class="paso-ejemplo">
                                            <div class="numero-paso">1</div>
                                            <div class="contenido-paso">
                                                <strong>Abrir el período</strong>
                                                <p>Clic en <span class="btn-example btn-primary">Abrir Período</span>. Los inputs se habilitan.</p>
                                            </div>
                                        </div>
                                        <div class="paso-ejemplo">
                                            <div class="numero-paso">2</div>
                                            <div class="contenido-paso">
                                                <strong>Realizar modificaciones</strong>
                                                <p>Cambiás el valor del concepto 5 de la sucursal 02 de <strong>$50,000</strong> a <strong>$52,000</strong>.</p>
                                            </div>
                                        </div>
                                        <div class="paso-ejemplo">
                                            <div class="numero-paso">3</div>
                                            <div class="contenido-paso">
                                                <strong>Cerrar y verificar</strong>
                                                <p>Al hacer clic en <span class="btn-example btn-secondary">Cerrar Período</span>, aparece un modal mostrando:</p>
                                                <div class="ejemplo-diferencia">
                                                    <table class="table table-sm mt-2">
                                                        <thead>
                                                            <tr>
                                                                <th>Sucursal</th>
                                                                <th>Concepto</th>
                                                                <th>Guardado</th>
                                                                <th>Actual</th>
                                                                <th>Diferencia</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr class="table-warning">
                                                                <td>02 - Sucursal Centro</td>
                                                                <td>5</td>
                                                                <td>$50,000</td>
                                                                <td>$52,000</td>
                                                                <td class="text-success">+$2,000</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="paso-ejemplo">
                                            <div class="numero-paso">4</div>
                                            <div class="contenido-paso">
                                                <strong>Decidir acción</strong>
                                                <p>Elegís <strong>"Actualizar y Cerrar"</strong> para guardar el nuevo valor y cerrar el período.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ejemplo 3 -->
                            <div class="ejemplo-card">
                                <div class="ejemplo-header">
                                    <i class="fas fa-bookmark"></i>
                                    <h5>Ejemplo 3: Reprocesamiento por Error</h5>
                                </div>
                                <div class="ejemplo-body">
                                    <p><strong>Escenario:</strong> Procesaste el período 4-2025, pero luego detectaste un error en los datos.</p>
                                    
                                    <ol class="ejemplo-lista">
                                        <li>
                                            <strong>Revertir procesamiento:</strong> Clic en <span class="btn-example btn-danger">Revertir Proc.</span>
                                            <p class="text-muted">Esto elimina el registro en el Informe Económico, pero mantiene los datos locales.</p>
                                        </li>
                                        <li>
                                            <strong>Abrir período:</strong> Si está cerrado, abrilo con <span class="btn-example btn-primary">Abrir Período</span>
                                        </li>
                                        <li>
                                            <strong>Corregir valores:</strong> Modificá los datos incorrectos.
                                        </li>
                                        <li>
                                            <strong>Procesar nuevamente:</strong> Clic en <span class="btn-example btn-success">Procesar</span>
                                        </li>
                                        <li>
                                            <strong>Cerrar:</strong> Finalmente, cerrá el período con <span class="btn-example btn-secondary">Cerrar Período</span>
                                        </li>
                                    </ol>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- PESTAÑA: Consideraciones Técnicas -->
                    <div class="tab-pane fade" id="tecnicas" role="tabpanel" aria-labelledby="tecnicas-tab">
                        <div class="tecnica-intro">
                            <p class="lead">Información técnica sobre el funcionamiento del sistema.</p>
                        </div>

                        <div class="accordion" id="accordionTecnicas">
                            
                            <!-- Técnica 1 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingTec1">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTec1" aria-expanded="false" aria-controls="collapseTec1">
                                            <i class="fas fa-save"></i> ¿Cómo se Guardan los Datos?
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseTec1" class="collapse" aria-labelledby="headingTec1" data-parent="#accordionTecnicas">
                                    <div class="card-body">
                                        <p><strong>Guardado automático:</strong></p>
                                        <ul>
                                            <li>Cada valor que ingresás se guarda <strong>automáticamente</strong> en cuanto terminás de escribirlo</li>
                                            <li>No necesitás hacer clic en ningún botón "Guardar" para los importes individuales</li>
                                            <li>Si aplicaste el ajuste por inflación, el sistema marca automáticamente esos conceptos como ajustados</li>
                                        </ul>
                                        <p class="mt-3"><strong>Estados del período:</strong></p>
                                        <table class="table table-sm table-bordered">
                                            <tr>
                                                <td><strong>Período Abierto</strong></td>
                                                <td>Podés editar todos los valores. Los cálculos automáticos se actualizan en tiempo real.</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Período Cerrado</strong></td>
                                                <td>Todos los campos están bloqueados (solo lectura). Los valores quedan congelados.</td>
                                            </tr>
                                        </table>
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Si ves que un campo está en gris y no te deja editarlo, verificá si el período está cerrado o si ese concepto se calcula automáticamente.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Técnica 2 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingTec2">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTec2" aria-expanded="false" aria-controls="collapseTec2">
                                            <i class="fas fa-sync"></i> Cálculo Automático de Conceptos
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseTec2" class="collapse" aria-labelledby="headingTec2" data-parent="#accordionTecnicas">
                                    <div class="card-body">
                                        <div class="alert alert-primary" role="alert">
                                            <i class="fas fa-calculator"></i> <strong>Sistema de Cálculo:</strong> Algunos conceptos los ingresás manualmente y otros los calcula el sistema automáticamente según las ventas y porcentajes configurados.
                                        </div>

                                        <h6 class="mt-4 mb-3"><i class="fas fa-pencil-alt text-primary"></i> Conceptos que Ingresás Manualmente</h6>
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th width="15%">ID</th>
                                                    <th>Concepto</th>
                                                    <th>Observaciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><strong>1, 2, 3</strong></td>
                                                    <td>Varios conceptos contractuales</td>
                                                    <td>Valores fijos que ingresás según el contrato de cada sucursal</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>4, 5, 18</strong></td>
                                                    <td>Alquileres base por contrato</td>
                                                    <td>Valores que podés ajustar automáticamente con el coeficiente de inflación</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>8</strong></td>
                                                    <td>Valor mínimo mensual</td>
                                                    <td>Piso contractual que se usa en los cálculos de conceptos 6 y 7</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>10, 11, 12</strong></td>
                                                    <td>Expensas y otros gastos</td>
                                                    <td>Valores variables que ingresás cada mes</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        
                                        <h6 class="mt-4 mb-3"><i class="fas fa-robot text-success"></i> Conceptos que Calcula el Sistema Automáticamente</h6>
                                        <p>Estos conceptos se calculan automáticamente cuando el período está <strong>abierto</strong>. Al cerrar el período, los valores quedan fijos.</p>
                                        
                                        <div class="card mt-3 mb-3 border-primary">
                                            <div class="card-header bg-light">
                                                <strong>Concepto 6</strong> - Porcentaje sobre Ventas Brutas
                                            </div>
                                            <div class="card-body">
                                                <p><strong>¿Cómo se calcula?</strong></p>
                                                <div class="formula-box">
                                                    <code>Paso 1: Multiplicá Venta Bruta × Porcentaje ÷ 100</code>
                                                    <br><code>Paso 2: Al resultado restale el Valor Mínimo Mensual</code>
                                                    <br><code>Si el resultado es negativo, se muestra $0</code>
                                                </div>
                                                <p class="mt-2"><strong>Ejemplo con números reales:</strong></p>
                                                <ul>
                                                    <li>Venta Bruta de Sucursal 11: $61,337,053</li>
                                                    <li>Porcentaje configurado: 6.47%</li>
                                                    <li>Valor Mínimo Mensual (concepto 8): $1,898,260</li>
                                                </ul>
                                                <div class="bg-light p-2 rounded">
                                                    <code>Paso 1: 61,337,053 × 6.47 ÷ 100 = $3,968,913.69</code><br>
                                                    <code>Paso 2: $3,968,913.69 - $1,898,260 = $2,070,653.69</code><br>
                                                    <code>✅ Resultado final: $2,070,653.69</code>
                                                </div>
                                                <div class="alert alert-info mt-2 mb-0">
                                                    <i class="fas fa-info-circle"></i> <strong>Nota importante:</strong> El sistema guarda el resultado del paso 1. La resta del valor mínimo se aplica automáticamente al mostrar los datos cuando el período está abierto.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mt-3 mb-3 border-primary">
                                            <div class="card-header bg-light">
                                                <strong>Concepto 7</strong> - Porcentaje sobre Ventas Netas
                                            </div>
                                            <div class="card-body">
                                                <p><strong>¿Cómo se calcula?</strong></p>
                                                <div class="formula-box">
                                                    <code>Paso 1: Multiplicá Venta Neta × Porcentaje ÷ 100</code>
                                                    <br><code>Paso 2: Al resultado restale el Valor Mínimo Mensual</code>
                                                    <br><code>Si el resultado es negativo, se muestra $0</code>
                                                </div>
                                                <p class="mt-2"><strong>Ejemplo con números reales:</strong></p>
                                                <ul>
                                                    <li>Venta Neta de Sucursal 2: $63,941,966</li>
                                                    <li>Porcentaje configurado: 0.8%</li>
                                                    <li>Valor Mínimo Mensual (concepto 8): $400,000</li>
                                                </ul>
                                                <div class="bg-light p-2 rounded">
                                                    <code>Paso 1: 63,941,966 × 0.8 ÷ 100 = $511,535.73</code><br>
                                                    <code>Paso 2: $511,535.73 - $400,000 = $111,535.73</code><br>
                                                    <code>✅ Resultado final: $111,535.73</code>
                                                </div>
                                                <div class="alert alert-warning mt-2 mb-0">
                                                    <i class="fas fa-exclamation-triangle"></i> <strong>Caso especial:</strong> Si el resultado del paso 1 es menor al valor mínimo, se muestra $0. Por ejemplo, si la venta es baja y da $350,000 - $400,000 = -$50,000, se muestra $0.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mt-3 mb-3 border-success">
                                            <div class="card-header bg-light">
                                                <strong>Concepto 14</strong> - Gastos Administrativos sobre Ventas Netas
                                            </div>
                                            <div class="card-body">
                                                <p><strong>¿Cómo se calcula?</strong></p>
                                                <div class="formula-box">
                                                    <code>Concepto 14 = Concepto 7 (valor mostrado) × Porcentaje ÷ 100</code>
                                                </div>
                                                <p class="mt-2"><strong>⚠️ MUY IMPORTANTE:</strong> Este concepto se calcula sobre el valor <strong>final</strong> del concepto 7 (después de restar el valor mínimo), NO sobre la venta neta directamente.</p>
                                                <p class="mt-2"><strong>Ejemplo con números reales:</strong></p>
                                                <ul>
                                                    <li>Concepto 7 mostrado (ya con el mínimo restado): $111,535.73</li>
                                                    <li>Porcentaje configurado: 10%</li>
                                                </ul>
                                                <div class="bg-light p-2 rounded">
                                                    <code>Cálculo: $111,535.73 × 10 ÷ 100 = $11,153.57</code><br>
                                                    <code>✅ Resultado final: $11,153.57</code>
                                                </div>
                                                <div class="alert alert-danger mt-2 mb-0">
                                                    <i class="fas fa-times-circle"></i> <strong>Error común:</strong> NO se calcula como (Venta Neta × 10%). Siempre se usa el valor final del concepto 7.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mt-3 mb-3 border-secondary">
                                            <div class="card-header bg-light">
                                                <strong>Concepto 15 y 16</strong> - Porcentajes sobre Venta Bruta
                                            </div>
                                            <div class="card-body">
                                                <p><strong>¿Cómo se calculan?</strong></p>
                                                <div class="formula-box">
                                                    <code>Resultado = Venta Bruta × Porcentaje ÷ 100</code>
                                                </div>
                                                <p class="mt-2"><strong>Diferencia con concepto 6:</strong> Estos conceptos <u>NO</u> restan el valor mínimo mensual. Es un cálculo directo de porcentaje sobre la venta bruta.</p>
                                                <div class="alert alert-secondary mt-2 mb-0">
                                                    <i class="fas fa-info-circle"></i> El concepto 16 se muestra como: Concepto 16 menos Concepto 9 en la pantalla.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mt-3 mb-3 border-secondary">
                                            <div class="card-header bg-light">
                                                <strong>Concepto 17</strong> - Porcentaje sobre Venta Neta
                                            </div>
                                            <div class="card-body">
                                                <p><strong>¿Cómo se calcula?</strong></p>
                                                <div class="formula-box">
                                                    <code>Resultado = Venta Neta × Porcentaje ÷ 100</code>
                                                </div>
                                                <p class="mt-2"><strong>Diferencia con concepto 7:</strong> Este concepto <u>NO</u> resta el valor mínimo mensual. Es un cálculo directo de porcentaje sobre venta neta.</p>
                                                <p class="mt-2"><strong>Ejemplo con números reales:</strong></p>
                                                <ul>
                                                    <li>Venta Neta de Sucursal 2: $63,941,966</li>
                                                    <li>Porcentaje configurado: 0.8%</li>
                                                </ul>
                                                <div class="bg-light p-2 rounded">
                                                    <code>Cálculo: 63,941,966 × 0.8 ÷ 100 = $511,535.73</code><br>
                                                    <code>✅ Resultado final: $511,535.73</code>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mt-3 mb-3 border-info">
                                            <div class="card-header bg-light">
                                                <strong>Concepto 9 y 13</strong> - Valores Fijos por Sucursal
                                            </div>
                                            <div class="card-body">
                                                <p><strong>Tipo:</strong> Son importes fijos configurados previamente para cada sucursal</p>
                                                <p>No dependen de ventas ni porcentajes. Son montos fijos establecidos por contrato que varían según cada local.</p>
                                            </div>
                                        </div>

                                        <h6 class="mt-4 mb-3"><i class="fas fa-cogs text-warning"></i> ¿Cómo Funciona Internamente?</h6>
                                        
                                        <div class="alert alert-info">
                                            <p class="mb-2"><strong>💾 Período Abierto vs Cerrado:</strong></p>
                                            <ul class="mb-0">
                                                <li><strong>Período Abierto:</strong> Los conceptos 6, 7, 14, 15, 16 y 17 se recalculan automáticamente cada vez que se modifica algo (ventas, porcentajes, valor mínimo).</li>
                                                <li><strong>Período Cerrado:</strong> Los valores quedan congelados tal como están. No se recalculan aunque cambien las ventas o porcentajes.</li>
                                            </ul>
                                        </div>

                                        <div class="alert alert-success">
                                            <p class="mb-2"><strong>✅ ¿Cuándo se Actualizan los Cálculos?</strong></p>
                                            <ol class="mb-0">
                                                <li><strong>Al abrir un período:</strong> Todos los conceptos automáticos se recalculan desde cero</li>
                                                <li><strong>Al modificar el valor mínimo (concepto 8):</strong> Se recalculan automáticamente los conceptos 6, 7 y 14</li>
                                                <li><strong>Al modificar el concepto 7:</strong> Se recalcula automáticamente el concepto 14</li>
                                                <li><strong>Al cerrar un período:</strong> Los valores actuales se guardan y quedan fijos</li>
                                            </ol>
                                        </div>

                                        <div class="alert alert-warning">
                                            <p class="mb-2"><strong>🔄 Si Cambiaron los Porcentajes:</strong></p>
                                            <p class="mb-0">Si alguien modificó los porcentajes configurados en el sistema y necesitás actualizar un período ya cerrado:</p>
                                            <ol class="mb-0 mt-2">
                                                <li>Abrí el período con el botón "Abrir Período"</li>
                                                <li>Esperá unos segundos a que el sistema recalcule automáticamente</li>
                                                <li>Volvé a cerrar el período para guardar los nuevos valores</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Técnica 3 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingTec3">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTec3" aria-expanded="false" aria-controls="collapseTec3">
                                            <i class="fas fa-search"></i> ¿Cómo Detecta el Sistema las Diferencias?
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseTec3" class="collapse" aria-labelledby="headingTec3" data-parent="#accordionTecnicas">
                                    <div class="card-body">
                                        <p><strong>¿Cuándo se comparan los valores?</strong></p>
                                        <p>Cuando intentás cerrar un período que previamente reabriste y modificaste, el sistema compara automáticamente los valores actuales con los que estaban guardados anteriormente.</p>
                                        
                                        <p class="mt-3"><strong>¿Qué pasa si hay diferencias?</strong></p>
                                        <ol>
                                            <li>El sistema te muestra un resumen de todas las diferencias encontradas</li>
                                            <li>Las celdas con cambios se resaltan con <strong>fondo amarillo</strong> y <strong>borde naranja</strong></li>
                                            <li>Podés elegir qué hacer:
                                                <ul>
                                                    <li><strong>Actualizar y Cerrar:</strong> Guarda los nuevos valores</li>
                                                    <li><strong>Cerrar sin Actualizar:</strong> Mantiene los valores originales</li>
                                                    <li><strong>Cancelar:</strong> Vuelve a la edición</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-info-circle"></i> <strong>Nota:</strong> El sistema ignora diferencias menores a $0.01 para evitar problemas por redondeos automáticos.
                                        </div>
                                        
                                        <div class="alert alert-success mt-3">
                                            <i class="fas fa-check"></i> Las diferencias se recalculan cada vez que intentás cerrar, garantizando que siempre veas la información más actualizada.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Técnica 4 -->
                            <div class="card ayuda-card">
                                <div class="card-header" id="headingTec4">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTec4" aria-expanded="false" aria-controls="collapseTec4">
                                            <i class="fas fa-paper-plane"></i> Envío al Informe Económico
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseTec4" class="collapse" aria-labelledby="headingTec4" data-parent="#accordionTecnicas">
                                    <div class="card-body">
                                        <p><strong>¿Qué hace el botón "Procesar"?</strong></p>
                                        <p>Cuando hacés clic en <span class="btn-example btn-success">Procesar</span>, el sistema envía todos los datos del período al módulo de Informe Económico para su integración contable.</p>
                                        
                                        <p class="mt-3"><strong>Información que se envía:</strong></p>
                                        <ul>
                                            <li>Todos los conceptos de todas las sucursales del período</li>
                                            <li>La fecha corresponde al último día del mes procesado</li>
                                            <li>Se marca como módulo "ALQUILERES" para identificarlo</li>
                                        </ul>
                                        
                                        <p class="mt-3"><strong>¿Qué hace "Revertir Procesamiento"?</strong></p>
                                        <p>El botón <span class="btn-example btn-danger">Revertir Proc.</span> elimina el envío al Informe Económico, permitiéndote corregir errores y volver a procesar.</p>
                                        
                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> Revertir solo elimina el envío al Informe Económico. Los datos locales de alquileres NO se borran, permanecen guardados y podés volver a procesarlos cuando quieras.
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
