
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Si viene entorno por URL, actualizar la sesión
if (isset($_GET['entorno']) && in_array($_GET['entorno'], ['central', 'uy'])) {
    $_SESSION['entorno'] = $_GET['entorno'];
}

// Headers para evitar caché y forzar recarga
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ ."/../Controller/listarOrden.php";

$fecha_actual = date("Y-m-d");

// Debug - mostrar qué parámetros se recibieron
$debug_info = [
    'GET_params' => $_GET,
    'filter_isset' => isset($_GET['filter']),
    'desde_isset' => isset($_GET['desde']),
    'hasta_isset' => isset($_GET['hasta'])
];

// Establecer fechas por defecto
$desde_default = date("Y-m-d", strtotime($fecha_actual . "- 3 month"));
$hasta_default = $fecha_actual;

// Obtener fechas de los parámetros GET o usar por defecto
$desde = isset($_GET['desde']) && !empty($_GET['desde']) ? $_GET['desde'] : $desde_default;
$hasta = isset($_GET['hasta']) && !empty($_GET['hasta']) ? $_GET['hasta'] : $hasta_default;

// Debug - mostrar fechas que se van a usar
$debug_info['fechas_usadas'] = [
    'desde' => $desde,
    'hasta' => $hasta
];

// Siempre llamar a la función con las fechas (filtradas o por defecto)
$listaDeOrdenes = listarPorFecha($desde, $hasta);

// Debug - mostrar cantidad de resultados
$debug_info['total_registros'] = count($listaDeOrdenes);

// Mostrar debug en desarrollo (comentar en producción)
// echo "<!-- DEBUG: " . json_encode($debug_info, JSON_PRETTY_PRINT) . " -->";

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Title Page-->
    <title>Gestión de Despachos | Comercio Exterior</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="icon" type="image/jpg" href="../images/LOGO XL 2018.jpg">
    <link rel="stylesheet" href="../css/mostrarOrden.css" class="css">
    

</head>

<body>
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-archive"></i>
                Gestión de Despachos
            </h1>
            <p class="page-subtitle">Administra y consulta los despachos de importación registrados en el sistema</p>
        </div>

        <!-- Filters Card -->
        <div class="filters-card">
            <form class="filter-form" method="GET" action="">
                <div class="filter-group">
                    <label class="filter-label" for="desde">
                        <i class="bi bi-calendar-date"></i> Fecha Desde
                    </label>
                    <input type="date" class="form-control-modern" id="desde" name="desde" value="<?= htmlspecialchars($desde) ?>" required>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label" for="hasta">
                        <i class="bi bi-calendar-check"></i> Fecha Hasta
                    </label>
                    <input type="date" class="form-control-modern" id="hasta" name="hasta" value="<?= htmlspecialchars($hasta) ?>" required>
                </div>
                
                <div class="filter-group">
                    <button type="submit" name="filter" value="1" class="btn-modern btn-primary-modern">
                        <i class="bi bi-funnel"></i>
                        Filtrar
                    </button>
                </div>
                
                <div class="filter-group">
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn-modern" style="background: var(--secondary-color); color: white; text-decoration: none;">
                        <i class="bi bi-arrow-clockwise"></i>
                        Limpiar
                    </a>
                </div>
                
                <div class="filter-group">
                    <button type="button" class="btn-modern btn-success-modern" id="btnExport">
                        <i class="bi bi-file-earmark-excel"></i>
                        Exportar Excel
                    </button>
                </div>
            </form>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="bi bi-table"></i>
                    Despachos de Importación
                    <span class="badge bg-secondary"><?= count($listaDeOrdenes) ?> registros</span>
                </h2>
            </div>
            
            <div class="table-container">
                <table class="table table-hover" id="tableDinamic" data-page-length="25">
                    <thead>
                        <tr>
                            <th>Fecha Despacho</th>
                            <th>Fecha Ingreso</th>
                            <th>Contenedor</th>
                            <th>Despacho N°</th>
                            <th>Cod. Proveedor.</th>
                            <th>Proveedor</th>
                            <th>N° Ord. Compra</th>
                            <th>Costo Nac.</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($listaDeOrdenes as $orden): ?>
                            <tr>
                                <!-- Agregar data-sort para ordenamiento correcto -->
                                <td data-sort="<?= $orden['FECHA_DESP_ADU']->format('Y-m-d') ?>">
                                    <?= $orden['FECHA_DESP_ADU']->format('d/m/Y') ?>
                                </td>
                                <td data-sort="<?= $orden['FECHA_MOV']->format('Y-m-d') ?>">
                                    <?= $orden['FECHA_MOV']->format('d/m/Y') ?>
                                </td>
                                <td>
                                    <span class="fw-medium"><?= $orden['CONTENEDOR'] ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark"><?= $orden['DESPACHO'] ?></span>
                                </td>
                                <td>
                                    <code class="small"><?= $orden['COD_PROVEE'] ?></code>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($orden['PROVEEDOR']) ?>">
                                        <?= $orden['PROVEEDOR'] ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-medium"><?= $orden['ORDEN_COMPRA'] ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $costoNac = number_format($orden['COSTO_NAC'], 2);
                                    $badgeClass = $costoNac > 10 ? 'status-warning' : 'status-success';
                                    ?>
                                    <span class="status-badge <?= $badgeClass ?>">
                                        <?= $costoNac ?>%
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <button class="action-btn btn-edit" 
                                                title="Editar despacho"
                                                onclick="verDetalle('<?= $orden['ID'] ?>','<?= addslashes($orden['PROVEEDOR']) ?>','<?= $orden['ORDEN_COMPRA'] ?>','<?= $orden['COD_PROVEE'] ?>','<?= $orden['VALOR_FOB_PESO'] ?>')">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="action-btn btn-download" 
                                                title="Descargar PDF"
                                                onclick="imprimir('<?= $orden['ID'] ?>')">
                                            <i class="bi bi-download"></i>
                                        </button>
                                        <button class="action-btn btn-delete" 
                                                title="Eliminar despacho"
                                                onclick="eliminarDespacho('<?= $orden['ID'] ?>', '<?= addslashes($orden['DESPACHO']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/jquery/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
    <script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Funciones de navegación
        const verDetalle = (id, prov, orden, codProv, valorFobPeso) => {
            window.location = `components/editarOrden.php?idEncabezado=${id}&proveedor=${encodeURIComponent(prov)}&ordenDeCompra=${encodeURIComponent(orden)}&codProveedor=${codProv}&valorFobPeso=${valorFobPeso}`;
        }

        const imprimir = (id) => {
            window.location = `components/imprimir.php?idEncabezado=${id}`;
        }

        const eliminarDespacho = (id, numeroDespacho) => {
            Swal.fire({
                title: '¿Estás seguro?',
                text: `Se eliminará permanentemente el despacho N° ${numeroDespacho}. Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading
                    Swal.fire({
                        title: 'Eliminando...',
                        text: 'Por favor espera mientras se elimina el despacho',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Realizar la eliminación
                    $.ajax({
                        url: '../controller/eliminarDespacho.php',
                        type: 'POST',
                        data: {
                            id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Eliminado',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonColor: '#059669'
                                }).then(() => {
                                    // Recargar la página para actualizar la tabla
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message,
                                    icon: 'error',
                                    confirmButtonColor: '#dc2626'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                title: 'Error de conexión',
                                text: 'No se pudo conectar con el servidor. Por favor, inténtalo nuevamente.',
                                icon: 'error',
                                confirmButtonColor: '#dc2626'
                            });
                            console.error('Error AJAX:', error);
                        }
                    });
                }
            });
        }

        // Configuración de DataTables
        $(document).ready(function() {
            $('#tableDinamic').DataTable({
                responsive: true,
                fixedHeader: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                language: {
                    lengthMenu: "Mostrar _MENU_ registros por página",
                    zeroRecords: "No se encontraron registros",
                    info: "Mostrando página _PAGE_ de _PAGES_ (_TOTAL_ registros total)",
                    infoEmpty: "No hay registros disponibles",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    search: "Buscar:",
                    searchPlaceholder: "Buscar en cualquier campo...",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                },
                order: [[0, 'desc']], // Ordenar por Fecha Despacho descendente
                columnDefs: [
                    {
                        targets: [0, 1], // Columnas de fecha (Fecha Despacho y Fecha Ingreso)
                        type: "date",
                        render: function(data, type, row) {
                            // Para ordenamiento y búsqueda, usar el data-sort
                            if (type === 'sort' || type === 'type') {
                                return $(data).parent().attr('data-sort') || data;
                            }
                            // Para mostrar, usar el texto visible
                            return data;
                        }
                    },
                    {
                        targets: -1, // Última columna (Acciones)
                        orderable: false,
                        searchable: false,
                        className: "text-center"
                    }
                ],
                // Configuración alternativa para fechas
                createdRow: function(row, data, dataIndex) {
                    // Aplicar el atributo data-sort a las celdas de fecha
                    $('td:eq(0)', row).attr('data-order', $('td:eq(0)', row).attr('data-sort'));
                    $('td:eq(1)', row).attr('data-order', $('td:eq(1)', row).attr('data-sort'));
                },
                initComplete: function() {
                    // Animación de entrada
                    $('.table-card').css('opacity', '0').animate({opacity: 1}, 500);
                    
                    console.log('DataTable inicializado con ordenamiento por fecha descendente');
                }
            });

            // Exportar a Excel
            $("#btnExport").click(function() {
                $("#tableDinamic").table2excel({
                    exclude: ".no-export",
                    name: "Despachos_Importacion",
                    filename: `Despachos_${new Date().toISOString().split('T')[0]}`,
                    fileext: ".xlsx"
                });
                
                // Mostrar notificación
                Swal.fire({
                    title: 'Exportación exitosa',
                    text: 'El archivo Excel se ha descargado correctamente',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            });

            // Efectos visuales adicionales
            $('.action-btn').hover(
                function() {
                    $(this).addClass('shadow');
                },
                function() {
                    $(this).removeClass('shadow');
                }
            );
        });

        // Loading state para botones
        $('.btn-modern').click(function(e) {
            const btn = $(this);
            
            // Si es el botón de exportar, no hacer nada especial
            if (btn.attr('id') === 'btnExport') {
                return;
            }
            
            // Si es un botón de tipo submit, dejar que el formulario se envíe normalmente
            if (btn.attr('type') === 'submit') {
                return;
            }
            
            // Para otros botones, aplicar el estado de loading
            const originalText = btn.html();
            btn.prop('disabled', true);
            btn.html('<span class="loading"></span> Procesando...');
            
            setTimeout(() => {
                btn.prop('disabled', false);
                btn.html(originalText);
            }, 1000);
        });
    </script>
    
</body>

</html>