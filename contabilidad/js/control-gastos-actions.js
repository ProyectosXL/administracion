/**
 * Control de Gastos - Gestión de Acciones (Amortizar, Prorratear, Procesar)
 * Maneja los estados y las reversiones de cada proceso
 */

const AccionesControl = {
    
    // Estados de cada proceso
    estados: {
        amortizado: false,
        prorrateado: false,
        procesado: false,
        sinGastos: false,  // Nuevo estado para meses sin gastos para amortizar
        pendientesProrrateo: false  // Quedan gastos sin prorratear que bloquean el cierre
    },

    // Inicializar el módulo
    init: function() {
        this.verificarEstados();
    },

    // Verificar el estado de cada proceso
    verificarEstados: function() {
        const desde = document.getElementById('desde').value;
        const hasta = document.getElementById('hasta').value;
        const periodo = document.getElementById('periodo').getAttribute('attr-periodo');

        // Verificar estado de amortización
        this.verificarEstadoAmortizacion(desde, hasta);
        
        // Verificar estado de prorrateo
        this.verificarEstadoProrrateo(desde, hasta);
        
        // Verificar estado de procesamiento
        this.verificarEstadoProcesamiento(periodo);
    },

    // Verificar si ya se amortizó
    verificarEstadoAmortizacion: function(desde, hasta) {
        // Primero verificar si existen gastos para amortizar
        $.ajax({
            url: 'Controller/controlGastosController.php?accion=existenGastosParaAmortizar',
            method: 'POST',
            data: { desde: desde, hasta: hasta },
            success: (data) => {
                const existenGastos = data.trim() === 'true';
                
                if (!existenGastos) {
                    // No hay gastos para amortizar en este mes
                    this.estados.amortizado = false;
                    this.estados.sinGastos = true;
                    this.actualizarBotonAmortizar();
                } else {
                    // Hay gastos, verificar si ya están amortizados
                    this.estados.sinGastos = false;
                    $.ajax({
                        url: 'Controller/controlGastosController.php?accion=verificarAmortizado',
                        method: 'POST',
                        data: { desde: desde, hasta: hasta },
                        success: (data) => {
                            this.estados.amortizado = data.trim() === 'true';
                            this.actualizarBotonAmortizar();
                        }
                    });
                }
            }
        });
    },

    // Verificar si ya se prorrateó y si aún quedan gastos pendientes de prorratear
    verificarEstadoProrrateo: function(desde, hasta) {
        $.ajax({
            url: 'Controller/controlGastosController.php?accion=verificarProrrateado',
            method: 'POST',
            data: { desde: desde, hasta: hasta },
            success: (data) => {
                this.estados.prorrateado = data.trim() === 'true';

                // Aunque el período ya tenga prorrateos, pueden haberse controlado gastos
                // despues de esa corrida. El SP es incremental, asi que hay que poder
                // volver a ejecutarlo para levantar solo lo que quedo pendiente.
                $.ajax({
                    url: 'Controller/controlGastosController.php?accion=hayPendientesProrrateo',
                    method: 'POST',
                    data: { desde: desde, hasta: hasta },
                    success: (pend) => {
                        this.estados.pendientesProrrateo = pend.trim() === 'true';
                        this.actualizarBotonProrratear();
                    },
                    error: () => {
                        this.actualizarBotonProrratear();
                    }
                });
            }
        });
    },

    // Verificar si ya se procesó
    verificarEstadoProcesamiento: function(periodo) {
        $.ajax({
            url: 'Controller/controlGastosController.php?accion=existeResumen',
            method: 'POST',
            data: { periodo: periodo },
            success: (data) => {
                this.estados.procesado = data.trim() === 'true';
                this.actualizarBotonProcesar();
            }
        });
    },

    // Actualizar el botón de Amortizar según el estado
    actualizarBotonAmortizar: function() {
        const btn = document.getElementById('btnAmortizarDropdown');
        const dropdownMenu = document.getElementById('menuAmortizar');
        
        if (!btn || !dropdownMenu) return;

        if (this.estados.sinGastos) {
            // No hay gastos para amortizar en este mes
            btn.innerHTML = '<i class="bi bi-calendar2-week"></i> Amortizar <span class="estado-indicador estado-vacio">∅</span>';
            btn.className = 'btn btn-secondary dropdown-toggle';
            btn.disabled = true;
            dropdownMenu.innerHTML = `
                <a class="dropdown-item disabled" href="#">
                    <i class="bi bi-info-circle"></i> No hay gastos para amortizar este mes
                </a>
            `;
        } else if (this.estados.amortizado) {
            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Revertir Amort. <span class="estado-indicador estado-ejecutado">✓</span>';
            btn.className = 'btn btn-revertir dropdown-toggle';
            btn.disabled = false;
            dropdownMenu.innerHTML = `
                <a class="dropdown-item revertir-item" href="#" onclick="AccionesControl.revertirAmortizacion(); return false;">
                    <i class="bi bi-arrow-counterclockwise"></i> Revertir Amortización
                </a>
            `;
        } else {
            btn.innerHTML = '<i class="bi bi-calendar2-week"></i> Amortizar';
            btn.className = 'btn btn-ejecutar dropdown-toggle';
            btn.disabled = false;
            dropdownMenu.innerHTML = `
                <a class="dropdown-item ejecutar-item" href="#" onclick="AccionesControl.ejecutarAmortizacion(); return false;">
                    <i class="bi bi-play-fill"></i> Ejecutar Amortización
                </a>
            `;
        }
    },

    // Actualizar el botón de Prorratear según el estado
    actualizarBotonProrratear: function() {
        const btn = document.getElementById('btnProrratearDropdown');
        const dropdownMenu = document.getElementById('menuProrratear');
        
        if (!btn || !dropdownMenu) return;

        if (this.estados.prorrateado) {

            if (this.estados.pendientesProrrateo) {
                // Ya se prorrateó, pero quedaron gastos sin prorratear que bloquean el cierre
                btn.innerHTML = '<i class="bi bi-file-text"></i> Prorratear <span class="estado-indicador estado-pendiente">!</span>';
                btn.className = 'btn btn-ejecutar dropdown-toggle';
                dropdownMenu.innerHTML = `
                    <a class="dropdown-item ejecutar-item" href="#" onclick="AccionesControl.ejecutarProrrateo(); return false;">
                        <i class="bi bi-play-fill"></i> Prorratear pendientes
                    </a>
                    <a class="dropdown-item revertir-item" href="#" onclick="AccionesControl.revertirProrrateo(); return false;">
                        <i class="bi bi-arrow-counterclockwise"></i> Revertir Prorrateo
                    </a>
                `;
                return;
            }

            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Revertir Prorr. <span class="estado-indicador estado-ejecutado">✓</span>';
            btn.className = 'btn btn-revertir dropdown-toggle';
            dropdownMenu.innerHTML = `
                <a class="dropdown-item revertir-item" href="#" onclick="AccionesControl.revertirProrrateo(); return false;">
                    <i class="bi bi-arrow-counterclockwise"></i> Revertir Prorrateo
                </a>
            `;
        } else {
            btn.innerHTML = '<i class="bi bi-file-text"></i> Prorratear';
            btn.className = 'btn btn-ejecutar dropdown-toggle';
            dropdownMenu.innerHTML = `
                <a class="dropdown-item ejecutar-item" href="#" onclick="AccionesControl.ejecutarProrrateo(); return false;">
                    <i class="bi bi-play-fill"></i> Ejecutar Prorrateo
                </a>
            `;
        }
    },

    // Actualizar el botón de Procesar según el estado
    actualizarBotonProcesar: function() {
        const btn = document.getElementById('btnProcesarDropdown');
        const dropdownMenu = document.getElementById('menuProcesar');
        
        if (!btn || !dropdownMenu) return;

        if (this.estados.procesado) {
            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Revertir Proc. <span class="estado-indicador estado-ejecutado">✓</span>';
            btn.className = 'btn btn-revertir dropdown-toggle';
            dropdownMenu.innerHTML = `
                <a class="dropdown-item revertir-item" href="#" onclick="AccionesControl.revertirProcesamiento(); return false;">
                    <i class="bi bi-arrow-counterclockwise"></i> Revertir Proceso
                </a>
            `;
        } else {
            btn.innerHTML = '<i class="bi bi-check2-square"></i> Procesar';
            btn.className = 'btn btn-ejecutar dropdown-toggle';
            dropdownMenu.innerHTML = `
                <a class="dropdown-item ejecutar-item" href="#" onclick="AccionesControl.ejecutarProcesamiento(); return false;">
                    <i class="bi bi-play-fill"></i> Ejecutar Proceso
                </a>
            `;
        }
    },

    // ==================== EJECUTAR ACCIONES ====================

    // Ejecutar Amortización (llama a la función existente)
    ejecutarAmortizacion: function() {
        amortizarGastos();
    },

    // Ejecutar Prorrateo (llama a la función existente o usa el sistema asíncrono)
    ejecutarProrrateo: function() {
        // Verificar si existe la función de prorrateo asíncrono
        if (typeof prorratearGastosAsync !== 'undefined') {
            prorratearGastosAsync();
        } else {
            prorratearGastos();
        }
    },
    
    // Sistema de polling para verificar estado del proceso de prorrateo
    verificarEstadoProceso: function(idProceso, intervalo = 2000) {
        const spinner = document.getElementById('boxLoading');
        
        const checkStatus = () => {
            $.ajax({
                url: 'controller/ProrrateoController.php',
                method: 'POST',
                data: {
                    accion: 'consultar_estado',
                    id_proceso: idProceso
                },
                dataType: 'json',
                success: (response) => {
                    if (!response.success) {
                        clearInterval(pollingInterval);
                        spinner.classList.remove('loading');
                        this.limpiarMensajeProgreso();
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Error al consultar el estado',
                            icon: 'error'
                        });
                        return;
                    }
                    
                    const { estado, progreso, mensaje, total_registros, registros_procesados } = response;
                    
                    // Actualizar mensaje de progreso si existe un elemento para ello
                    this.actualizarMensajeProgreso(mensaje, progreso, registros_procesados, total_registros);
                    
                    if (estado === 'COMPLETADO') {
                        clearInterval(pollingInterval);
                        spinner.classList.remove('loading');
                        this.limpiarMensajeProgreso();
                        
                        Swal.fire({
                            title: '¡Éxito!',
                            text: mensaje || 'Prorrateo completado exitosamente',
                            icon: 'success',
                            timer: 3000
                        }).then(() => {
                            // Preservar filtros en la recarga
                            const urlParams = new URLSearchParams(window.location.search);
                            window.location.href = window.location.pathname + '?' + urlParams.toString();
                        });
                    } else if (estado === 'ERROR') {
                        clearInterval(pollingInterval);
                        spinner.classList.remove('loading');
                        this.limpiarMensajeProgreso();
                        
                        Swal.fire({
                            title: 'Error en el proceso',
                            text: response.detalles_error || mensaje || 'Ocurrió un error durante el prorrateo',
                            icon: 'error'
                        });
                    }
                },
                error: () => {
                    clearInterval(pollingInterval);
                    spinner.classList.remove('loading');
                    this.limpiarMensajeProgreso();
                    Swal.fire({
                        title: 'Error de conexión',
                        text: 'No se pudo verificar el estado del proceso',
                        icon: 'error'
                    });
                }
            });
        };
        
        // Iniciar polling
        const pollingInterval = setInterval(checkStatus, intervalo);
        
        // Primera verificación inmediata
        checkStatus();
    },
    
    // Limpiar mensaje de progreso
    limpiarMensajeProgreso: function() {
        const progressDiv = document.getElementById('prorrateo-progress');
        if (progressDiv) {
            progressDiv.remove();
        }
    },
    
    // Actualizar mensaje de progreso en la UI
    actualizarMensajeProgreso: function(mensaje, progreso, procesados, total) {
        // Buscar o crear elemento de progreso
        let progressDiv = document.getElementById('prorrateo-progress');
        
        if (!progressDiv) {
            const spinner = document.getElementById('boxLoading');
            if (spinner) {
                progressDiv = document.createElement('div');
                progressDiv.id = 'prorrateo-progress';
                progressDiv.style.cssText = `
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    z-index: 10000;
                    min-width: 400px;
                    text-align: center;
                `;
                document.body.appendChild(progressDiv);
            }
        }
        
        if (progressDiv) {
            let registrosInfo = '';
            if (total > 0) {
                registrosInfo = `<div style="margin-top: 10px; color: #666;">${procesados} / ${total} registros</div>`;
            }
            
            progressDiv.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <i class="bi bi-hourglass-split" style="font-size: 48px; color: #007bff;"></i>
                </div>
                <h4 style="margin-bottom: 15px;">Procesando Prorrateo</h4>
                <div style="margin-bottom: 10px; color: #555;">${mensaje}</div>
                <div class="progress" style="height: 25px; margin-top: 15px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: ${progreso}%"
                         aria-valuenow="${progreso}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        ${progreso}%
                    </div>
                </div>
                ${registrosInfo}
                <div style="margin-top: 15px; font-size: 12px; color: #999;">
                    Este proceso puede tardar varios minutos...
                </div>
            `;
        }
    },

    // Ejecutar Procesamiento (llama a la función existente)
    ejecutarProcesamiento: function() {
        procesar();
    },

    // ==================== REVERTIR ACCIONES ====================

    // Revertir Amortización
    revertirAmortizacion: function() {
        const desde = document.getElementById('desde').value;
        const hasta = document.getElementById('hasta').value;

        Swal.fire({
            title: '¿Revertir Amortización?',
            text: 'Esta acción revertirá todas las amortizaciones del período seleccionado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, revertir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const spinner = document.getElementById('boxLoading');
                spinner.className += ' loading';

                $.ajax({
                    url: 'Controller/controlGastosController.php?accion=revertirAmortizacion',
                    method: 'POST',
                    data: { desde: desde, hasta: hasta },
                    dataType: 'json',
                    success: (response) => {
                        spinner.classList.remove('loading');
                        if (response.success) {
                            Swal.fire({
                                title: '¡Éxito!',
                                text: 'La amortización fue revertida correctamente.',
                                icon: 'success'
                            }).then(() => {
                                // Preservar filtros en la recarga
                                const urlParams = new URLSearchParams(window.location.search);
                                window.location.href = window.location.pathname + '?' + urlParams.toString();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message || 'No se pudo revertir la amortización.',
                                icon: 'error'
                            });
                        }
                    },
                    error: () => {
                        spinner.classList.remove('loading');
                        Swal.fire({
                            title: 'Error de conexión',
                            text: 'No se pudo conectar con el servidor.',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    },

    // Revertir Prorrateo
    revertirProrrateo: function() {
        const desde = document.getElementById('desde').value;
        const hasta = document.getElementById('hasta').value;

        Swal.fire({
            title: '¿Revertir Prorrateo?',
            text: 'Esta acción revertirá todos los prorrateos del período seleccionado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, revertir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const spinner = document.getElementById('boxLoading');
                spinner.className += ' loading';

                $.ajax({
                    url: 'Controller/controlGastosController.php?accion=revertirProrrateo',
                    method: 'POST',
                    data: { desde: desde, hasta: hasta },
                    dataType: 'json',
                    success: (response) => {
                        spinner.classList.remove('loading');
                        if (response.success) {
                            Swal.fire({
                                title: '¡Éxito!',
                                text: 'El prorrateo fue revertido correctamente.',
                                icon: 'success'
                            }).then(() => {
                                // Preservar filtros en la recarga
                                const urlParams = new URLSearchParams(window.location.search);
                                window.location.href = window.location.pathname + '?' + urlParams.toString();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message || 'No se pudo revertir el prorrateo.',
                                icon: 'error'
                            });
                        }
                    },
                    error: () => {
                        spinner.classList.remove('loading');
                        Swal.fire({
                            title: 'Error de conexión',
                            text: 'No se pudo conectar con el servidor.',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    },

    // Revertir Procesamiento (usa la función existente)
    revertirProcesamiento: function() {
        revertir();
    },

    // Refrescar estados después de una acción
    refrescarEstados: function() {
        this.verificarEstados();
    }
};

// Inicializar cuando el documento esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            AccionesControl.init();
        }, 500);
    });
} else {
    setTimeout(() => {
        AccionesControl.init();
    }, 500);
}
