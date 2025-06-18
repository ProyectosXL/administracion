
// reporteAnticipos.js - Versión que muestra último período con datos
$(document).ready(function() {
    let table = null;
    
    // Cargar períodos y configurar último período con datos
    function cargarPeriodos() {
        $.ajax({
            url: 'Controller/getPeriodos.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Períodos cargados:', response);
                
                if (Array.isArray(response) && response.length > 0) {
                    const select = $('#periodFilter');
                    select.empty();
                    select.append('<option value="">Todos los períodos</option>');
                    
                    response.forEach(item => {
                        const option = new Option(item.PERIODO, item.PERIODO);
                        select.append(option);
                    });
                    
                    // Seleccionar el primer período (más reciente) que tiene datos
                    // Los períodos vienen ordenados descendente desde getPeriodos.php
                    if (response.length > 0) {
                        const ultimoPeriodoConDatos = response[0].PERIODO;
                        select.val(ultimoPeriodoConDatos);
                        console.log('Seleccionado último período con datos:', ultimoPeriodoConDatos);
                    }
                    
                    // Inicializar tabla después de cargar períodos
                    inicializarTabla();
                } else {
                    console.error('No se recibieron períodos válidos:', response);
                    // Aún así inicializar la tabla
                    inicializarTabla();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar períodos:', error, xhr.responseText);
                // Inicializar tabla incluso si hay error
                inicializarTabla();
            }
        });
    }

    // Inicializar DataTable
    function inicializarTabla() {
        if (table) {
            table.destroy();
        }
        
        table = $('#reportTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'Controller/getAnticipos.php',
                type: 'POST',
                data: function(d) {
                    const periodoSeleccionado = $('#periodFilter').val();
                    console.log('Enviando período:', periodoSeleccionado);
                    return {
                        ...d,
                        periodo: periodoSeleccionado || ''
                    };
                },
                error: function(xhr, error, thrown) {
                    console.error('Error en DataTable AJAX:', error, thrown, xhr.responseText);
                }
            },
            columns: [
                { data: 'NRO_LEGAJO' },
                { data: 'APELLIDO_Y_NOMBRE' },
                { 
                    data: 'DNI',
                    render: function(data, type) {
                        if (type === 'display' || type === 'filter') {
                            return data ? data.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '';
                        }
                        return data;
                    }
                },
                { data: 'PERIODO' },
                { 
                    data: 'IMPORTE', 
                    render: function(data, type, row) {
                        if (type === 'display' || type === 'filter') {
                            return data ? '$ ' + parseFloat(data).toLocaleString('es-AR', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }) : '$ 0';
                        } 
                        else if (type === 'export') {
                            return data ? parseFloat(data).toString() : '0';
                        }
                        return parseFloat(data || 0);
                    }
                },
                { data: 'FECHA_CARGA' },
                { data: 'DESC_DEPARTAMENTO' }
            ],        
                    
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-2"></i>Excel',
                    className: 'btn btn-success',
                    title: function() {
                        const periodo = $('#periodFilter').val();
                        return periodo ? `Reporte de Anticipos - ${periodo}` : 'Reporte de Anticipos';
                    },
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                if (column === 4) {
                                    return data.replace(/[$\s.,]/g, '');
                                }
                                else if (column === 2) {
                                    return data.replace(/\./g, '');
                                }
                                return data;
                            }
                        }
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf me-2"></i>PDF',
                    className: 'btn btn-danger',
                    title: function() {
                        const periodo = $('#periodFilter').val();
                        return periodo ? `Reporte de Anticipos - ${periodo}` : 'Reporte de Anticipos';
                    },
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-ES.json'
            },        
            pageLength: 200,
            ordering: true,
            order: [[5, 'desc']],
            responsive: true
        });
    }
    
    // Event listeners
    $(document).on('change', '#periodFilter', function() {
        const periodoSeleccionado = $(this).val();
        console.log('Cambio de período a:', periodoSeleccionado);
        
        // Actualizar el título de la página/card
        actualizarTituloPagina(periodoSeleccionado);
        
        if (table) {
            table.ajax.reload();
        }
    });
        
    $(document).on('keyup', '#searchBox', function() {
        if (table) {
            table.search(this.value).draw();
        }
    });

    // Función para actualizar el título de la página
    function actualizarTituloPagina(periodo) {
        const titulo = $('.card-header h5');
        if (periodo) {
            titulo.html(`<i class="fas fa-chart-bar me-2"></i>Reporte de Anticipos - ${periodo}`);
        } else {
            titulo.html(`<i class="fas fa-chart-bar me-2"></i>Reporte de Anticipos`);
        }
    }

    // Administración de períodos
    $('#periodosModal').on('show.bs.modal', function() {
        console.log('Abriendo modal de períodos');
        cargarYears();
    });

    function cargarYears() {
        $.ajax({
            url: 'Controller/getYears.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Años cargados:', response);
                
                const select = $('#yearSelect');
                select.empty();
                
                if (Array.isArray(response) && response.length > 0) {
                    response.forEach(year => {
                        select.append(new Option(year, year));
                    });
                } else {
                    // Si no hay años, agregar algunos años por defecto
                    const currentYear = new Date().getFullYear();
                    for (let i = currentYear - 2; i <= currentYear + 2; i++) {
                        select.append(new Option(i, i));
                    }
                }
                
                // Seleccionar año actual por defecto
                const currentYear = new Date().getFullYear();
                select.val(currentYear);
                cargarPeriodosPorAno(currentYear);
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar años:', error, xhr.responseText);
                
                // Fallback: agregar años por defecto
                const select = $('#yearSelect');
                select.empty();
                const currentYear = new Date().getFullYear();
                for (let i = currentYear - 2; i <= currentYear + 2; i++) {
                    select.append(new Option(i, i));
                }
                select.val(currentYear);
                cargarPeriodosPorAno(currentYear);
            }
        });
    }

    $('#yearSelect').on('change', function() {
        const year = $(this).val();
        console.log('Cambiando a año:', year);
        cargarPeriodosPorAno(year);
    });

    function cargarPeriodosPorAno(year) {
        $.ajax({
            url: 'Controller/getPeriodosByYear.php',
            method: 'POST',
            data: { year: year },
            dataType: 'json',
            success: function(response) {
                console.log('Períodos del año cargados:', response);
                mostrarPeriodos(response, year);
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar períodos del año:', error, xhr.responseText);
                // Mostrar períodos vacíos en caso de error
                mostrarPeriodos([], year);
            }
        });
    }

    // Función mejorada para mostrar períodos con indicadores visuales
    function mostrarPeriodos(periodos, year) {
        const container = $('#periodosContainer');
        container.empty();

        const meses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];

        const currentMonth = new Date().getMonth() + 1;
        const currentYear = new Date().getFullYear();

        for (let i = 1; i <= 12; i++) {
            const periodo = periodos.find(p => p.PERIODO === `${i}-${year}`) || {};
            const esVigente = i === currentMonth && year == currentYear;
            
            const periodoHtml = `
                <div class="periodo-row ${esVigente ? 'periodo-vigente' : ''}" data-periodo="${i}-${year}">
                    <div class="row">
                        <div class="col-md-2">
                            <h6 class="periodo-title" id="titulo-${i}-${year}">
                                ${meses[i-1]} ${year} ${esVigente ? '(Vigente)' : ''}
                                <span class="status-indicator empty" id="status-${i}-${year}"></span>
                            </h6>
                            <small class="text-muted">Período: ${i}-${year}</small>
                            <div class="error-messages" id="errors-${i}-${year}" style="display: none;">
                                <small class="text-danger"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Anticipo</label>
                            <input type="date" class="form-control fecha-anticipo" 
                                   value="${periodo.FECHA_ANTICIPO || ''}" 
                                   data-original="${periodo.FECHA_ANTICIPO || ''}" />
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vigencia Desde</label>
                            <input type="datetime-local" class="form-control vig-desde" 
                                   value="${formatDateTime(periodo.VIG_DESDE) || ''}"
                                   data-original="${formatDateTime(periodo.VIG_DESDE) || ''}" />
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vigencia Hasta</label>
                            <input type="datetime-local" class="form-control vig-hasta" 
                                   value="${formatDateTime(periodo.VIG_HASTA) || ''}"
                                   data-original="${formatDateTime(periodo.VIG_HASTA) || ''}" />
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            `;
            container.append(periodoHtml);
        }

        // Agregar eventos de validación
        agregarValidacionesVisuales();
        
        // Validar todos los períodos inicialmente
        validarTodosLosPeriodos();
    }

    function formatDateTime(dateTimeString) {
        if (!dateTimeString) return '';
        try {
            const date = new Date(dateTimeString);
            return date.toISOString().slice(0, 16);
        } catch (e) {
            console.error('Error al formatear fecha:', dateTimeString, e);
            return '';
        }
    }

    // Función de validación visual mejorada
    function agregarValidacionesVisuales() {
        $('.fecha-anticipo, .vig-desde, .vig-hasta').off('change.validation input.validation')
            .on('change.validation input.validation', function() {
                const row = $(this).closest('.periodo-row');
                const periodo = row.data('periodo');
                validarPeriodo(periodo);
                validarTodosLosPeriodos();
            });
    }

    // Función para validar un período específico
    function validarPeriodo(periodo) {
        const row = $(`.periodo-row[data-periodo="${periodo}"]`);
        const [mes, año] = periodo.split('-');
        
        const fechaAnticipo = row.find('.fecha-anticipo').val();
        const vigDesde = row.find('.vig-desde').val();
        const vigHasta = row.find('.vig-hasta').val();
        
        // Verificar si hay cambios
        const fechaAnticipoOriginal = row.find('.fecha-anticipo').data('original');
        const vigDesdeOriginal = row.find('.vig-desde').data('original');
        const vigHastaOriginal = row.find('.vig-hasta').data('original');
        
        const hasChanges = (fechaAnticipo !== fechaAnticipoOriginal) || 
                          (vigDesde !== vigDesdeOriginal) || 
                          (vigHasta !== vigHastaOriginal);

        // Limpiar estados previos
        row.removeClass('has-errors has-valid has-changes');
        row.find('.form-control').removeClass('is-invalid is-valid');
        row.find('.invalid-feedback').text('');
        
        const titulo = row.find('.periodo-title');
        const statusIndicator = row.find('.status-indicator');
        const errorsContainer = row.find('.error-messages');
        
        titulo.removeClass('error valid warning');
        statusIndicator.removeClass('error valid warning empty');
        errorsContainer.hide().find('small').text('');

        let errors = [];
        let hasError = false;

        // Si no hay datos, marcar como vacío
        if (!fechaAnticipo && !vigDesde && !vigHasta) {
            statusIndicator.addClass('empty');
            titulo.removeClass('error valid warning');
            return { valid: true, empty: true, errors: [] };
        }

        // Validar campos completos
        if ((fechaAnticipo || vigDesde || vigHasta) && (!fechaAnticipo || !vigDesde || !vigHasta)) {
            errors.push('Debe completar todos los campos o dejarlos vacíos');
            hasError = true;
        }

        if (fechaAnticipo && vigDesde && vigHasta) {
            const dateAnticipo = new Date(fechaAnticipo + 'T23:59:59');
            const dateDesde = new Date(vigDesde);
            const dateHasta = new Date(vigHasta);

            // Validar fechas válidas
            if (isNaN(dateAnticipo.getTime()) || isNaN(dateDesde.getTime()) || isNaN(dateHasta.getTime())) {
                errors.push('Una o más fechas tienen formato inválido');
                hasError = true;
            } else {
                // Validar que las fechas de vigencia correspondan al mes
                if (dateDesde.getMonth() + 1 != mes || dateDesde.getFullYear() != año) {
                    errors.push(`La fecha de vigencia desde debe ser del mes ${mes}/${año}`);
                    row.find('.vig-desde').addClass('is-invalid');
                    row.find('.vig-desde').siblings('.invalid-feedback').text('Mes incorrecto');
                    hasError = true;
                }

                if (dateHasta.getMonth() + 1 != mes || dateHasta.getFullYear() != año) {
                    errors.push(`La fecha de vigencia hasta debe ser del mes ${mes}/${año}`);
                    row.find('.vig-hasta').addClass('is-invalid');
                    row.find('.vig-hasta').siblings('.invalid-feedback').text('Mes incorrecto');
                    hasError = true;
                }

                // Validar que vigencia hasta no sea inferior a vigencia desde
                if (dateHasta <= dateDesde) {
                    errors.push('La fecha hasta debe ser posterior a la fecha desde');
                    row.find('.vig-hasta').addClass('is-invalid');
                    row.find('.vig-hasta').siblings('.invalid-feedback').text('Debe ser posterior a fecha desde');
                    hasError = true;
                }

                // Validar que las fechas de vigencia no sean superiores a la fecha del anticipo
                if (dateDesde > dateAnticipo) {
                    errors.push('La fecha de vigencia desde no puede ser superior a la fecha del anticipo');
                    row.find('.vig-desde').addClass('is-invalid');
                    row.find('.vig-desde').siblings('.invalid-feedback').text('No puede ser superior al anticipo');
                    hasError = true;
                }

                if (dateHasta > dateAnticipo) {
                    errors.push('La fecha de vigencia hasta no puede ser superior a la fecha del anticipo');
                    row.find('.vig-hasta').addClass('is-invalid');
                    row.find('.vig-hasta').siblings('.invalid-feedback').text('No puede ser superior al anticipo');
                    hasError = true;
                }
            }
        }

        // Aplicar estilos según el estado
        if (hasError) {
            row.addClass('has-errors shake-error');
            titulo.addClass('error');
            statusIndicator.addClass('error');
            
            // Mostrar errores
            errorsContainer.show().find('small').html(errors.join('<br>'));
            
            // Remover animación después de un tiempo
            setTimeout(() => row.removeClass('shake-error'), 500);
            
        } else if (hasChanges) {
            row.addClass('has-changes');
            titulo.addClass('warning');
            statusIndicator.addClass('warning');
            
            // Marcar campos como válidos
            row.find('.form-control').filter(function() {
                return $(this).val() !== '';
            }).addClass('is-valid');
            
        } else if (fechaAnticipo || vigDesde || vigHasta) {
            row.addClass('has-valid');
            titulo.addClass('valid');
            statusIndicator.addClass('valid');
            
            // Marcar campos como válidos
            row.find('.form-control').filter(function() {
                return $(this).val() !== '';
            }).addClass('is-valid');
        }

        return { 
            valid: !hasError, 
            empty: !fechaAnticipo && !vigDesde && !vigHasta,
            hasChanges: hasChanges,
            errors: errors 
        };
    }

    // Función para validar todos los períodos
    function validarTodosLosPeriodos() {
        let totalErrors = 0;
        let totalChanges = 0;
        let totalValid = 0;

        $('.periodo-row').each(function() {
            const periodo = $(this).data('periodo');
            const result = validarPeriodo(periodo);
            
            if (!result.empty) {
                if (!result.valid) {
                    totalErrors++;
                } else if (result.hasChanges) {
                    totalChanges++;
                } else {
                    totalValid++;
                }
            }
        });

        // Actualizar el botón de guardar según el estado
        const saveButton = $('#guardarPeriodos');
        if (totalErrors > 0) {
            saveButton.prop('disabled', true)
                      .removeClass('btn-success')
                      .addClass('btn-danger')
                      .html(`<i class="fas fa-exclamation-triangle me-2"></i>Hay ${totalErrors} período(s) con errores`);
        } else if (totalChanges > 0) {
            saveButton.prop('disabled', false)
                      .removeClass('btn-danger')
                      .addClass('btn-success')
                      .html(`<i class="fas fa-save me-2"></i>Guardar Cambios (${totalChanges} período(s))`);
        } else {
            saveButton.prop('disabled', true)
                      .removeClass('btn-danger btn-success')
                      .addClass('btn-secondary')
                      .html(`<i class="fas fa-save me-2"></i>Sin cambios para guardar`);
        }

        console.log(`Validación: ${totalErrors} errores, ${totalChanges} cambios, ${totalValid} válidos`);
    }

    // Función mejorada para guardar períodos
    function guardarPeriodosConValidacion() {
        // Validar todo antes de guardar
        validarTodosLosPeriodos();
        
        const periodos = [];
        let hasErrors = false;

        $('.periodo-row').each(function() {
            const row = $(this);
            const periodo = row.data('periodo');
            const fechaAnticipo = row.find('.fecha-anticipo').val();
            const vigDesde = row.find('.vig-desde').val();
            const vigHasta = row.find('.vig-hasta').val();

            // Solo incluir períodos que tengan datos
            if (fechaAnticipo && vigDesde && vigHasta) {
                const result = validarPeriodo(periodo);
                
                if (!result.valid) {
                    hasErrors = true;
                    return false;
                }

                periodos.push({
                    periodo: periodo,
                    fecha_anticipo: fechaAnticipo,
                    vig_desde: vigDesde,
                    vig_hasta: vigHasta
                });
            }
        });

        if (hasErrors) {
            Swal.fire({
                icon: 'error',
                title: 'Errores de validación',
                text: 'Corrija los errores marcados antes de guardar',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        if (periodos.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Sin cambios',
                text: 'No hay períodos para guardar',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        console.log('Períodos a guardar:', periodos);

        // Guardar períodos
        $.ajax({
            url: 'Controller/savePeriodos.php',
            method: 'POST',
            data: { periodos: JSON.stringify(periodos) },
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta guardar períodos:', response);
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: `${response.count} período(s) guardado(s) correctamente`,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#periodosModal').modal('hide');
                        cargarPeriodos(); // Recargar períodos del selector
                    });
                } else {
                    mostrarError(response.message || 'Error desconocido al guardar');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al guardar períodos:', error, xhr.responseText);
                let errorMessage = 'Error al guardar los períodos';
                
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                } catch (e) {
                    errorMessage += ': ' + error;
                }
                
                mostrarError(errorMessage);
            }
        });

        return true;
    }

    // Event listener del botón guardar
    $('#guardarPeriodos').off('click').on('click', function() {
        if (!$(this).prop('disabled')) {
            guardarPeriodosConValidacion();
        }
    });

    function mostrarError(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje
        });
    }

    // Iniciar la aplicación
    cargarPeriodos();
});