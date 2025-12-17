/**
 * Control de Gastos - Gestión de Acciones (Amortizar, Prorratear, Procesar)
 * Maneja los estados y las reversiones de cada proceso
 */

const AccionesControl = {
    
    // Estados de cada proceso
    estados: {
        amortizado: false,
        prorrateado: false,
        procesado: false
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
        $.ajax({
            url: 'Controller/controlGastosController.php?accion=verificarAmortizado',
            method: 'POST',
            data: { desde: desde, hasta: hasta },
            success: (data) => {
                this.estados.amortizado = data.trim() === 'true';
                this.actualizarBotonAmortizar();
            }
        });
    },

    // Verificar si ya se prorrateó
    verificarEstadoProrrateo: function(desde, hasta) {
        $.ajax({
            url: 'Controller/controlGastosController.php?accion=verificarProrrateado',
            method: 'POST',
            data: { desde: desde, hasta: hasta },
            success: (data) => {
                this.estados.prorrateado = data.trim() === 'true';
                this.actualizarBotonProrratear();
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

        if (this.estados.amortizado) {
            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Revertir Amort. <span class="estado-indicador estado-ejecutado">✓</span>';
            btn.className = 'btn btn-revertir dropdown-toggle';
            dropdownMenu.innerHTML = `
                <a class="dropdown-item revertir-item" href="#" onclick="AccionesControl.revertirAmortizacion(); return false;">
                    <i class="bi bi-arrow-counterclockwise"></i> Revertir Amortización
                </a>
            `;
        } else {
            btn.innerHTML = '<i class="bi bi-calendar2-week"></i> Amortizar';
            btn.className = 'btn btn-ejecutar dropdown-toggle';
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

    // Ejecutar Prorrateo (llama a la función existente)
    ejecutarProrrateo: function() {
        prorratearGastos();
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
                                location.reload();
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
                                location.reload();
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
