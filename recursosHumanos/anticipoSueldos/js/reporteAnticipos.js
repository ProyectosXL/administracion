
// reporteAnticipos.js
$(document).ready(function() {
    // Cargar períodos
    $.ajax({
        url: 'Controller/getPeriodos.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (Array.isArray(response)) {
                const select = $('#periodFilter');
                response.forEach(item => {
                    select.append(`<option value="${item.PERIODO}">${item.PERIODO}</option>`);
                });
            } else {
                console.error('Respuesta inesperada:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar períodos:', error);
            console.error('Respuesta:', xhr.responseText);
        }
    });

    // Inicializar DataTable
    const table = $('#reportTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'Controller/getAnticipos.php',
            type: 'POST',
            data: function(d) {
                return {
                    ...d,
                    periodo: $('#periodFilter').val()
                };
            }
        },
        columns: [
            { data: 'NRO_LEGAJO' },
            { data: 'APELLIDO_Y_NOMBRE' },
            { 
                data: 'DNI',
                render: function(data) {
                    return data.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }
            },
            { data: 'PERIODO' },
            { 
                data: 'IMPORTE', 
                render: function(data, type, row) {
                    let importe = parseFloat(data).toFixed(0);
                    
                    if (type === 'display') {
                        return importe.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                    } else if (type === 'export' || type === 'excel' || type === 'pdf') {
                        return importe.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    }
                    return importe;
                }
            },
            { data: 'FECHA_CARGA' }
        ],        
                
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel me-2"></i>Excel',
                className: 'btn btn-success',
                title: 'Reporte de Anticipos',
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            if (column === 4) {
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
                title: 'Reporte de Anticipos',
                exportOptions: {
                    columns: ':visible'
                }
            }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-ES.json'
        },        
        pageLength: 10,
        ordering: true,
        order: [[5, 'desc']],
        responsive: true
        });
        
        $('#periodFilter').on('change', function() {
            table.ajax.reload();
        });
        
        $('#searchBox').on('keyup', function() {
            table.search(this.value).draw();
        });
        });
        