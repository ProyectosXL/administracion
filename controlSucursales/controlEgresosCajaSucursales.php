<?php
    require_once "Class/sucursal.php";
    $selectSucursal = isset($_GET['selectSucursal']) ?  $_GET['selectSucursal'] : '2-UNICENTER';
        
    $selectSucursal = explode("-",$selectSucursal);

    $fecha_actual = date("Y-m-d");
 
    if(isset($_GET['desde']) && $_GET['desde'] != "" ){
        $desde = $_GET['desde'];
    }else{
        $desde = date("Y-m-d",strtotime($fecha_actual."- 1 week"));
    }

    if(isset($_GET['hasta']) && $_GET['hasta'] != "" ){
        $hasta = $_GET['hasta'];
    }else{
        $hasta = date("Y-m-d",strtotime($fecha_actual."- 1 day"));
    }

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
  
    if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
        $checked = 'checked';
    }else{
        $checked = '';
    }
        
    $checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $dataOnValue = ($checkedValue === 'suc_uy') ? 'UY' : 'ARG';
    $dataOffValue = ($checkedValue === 'suc_uy') ? 'ARG' : 'UY';
    $imageOn = ($checkedValue === 'central') ? '../assets/images/bandera_con_sol__55757_std.jpg' : '../assets/images/UY.png';
    $imageOff = ($checkedValue === 'central') ? '../assets/images/UY.png' : '../assets/images/bandera_con_sol__55757_std.jpg';
    

    $sucursal = new Sucursal();
    $todosLosLocales= $sucursal->traerLocales(true);

    $data = null;

    if($desde != null && $hasta != null){
        
        $data = $sucursal->traerGastosCajaSucursales($desde,$hasta,$selectSucursal[0]);
    
    }

?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Control Egresos de Caja Sucursales</title>

        <!-- INCLUDE CSS FILES -->
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
        ?>
        
        <!-- CSS específico para Control Egresos -->
        <link rel="stylesheet" href="css/controlEgresosCaja.css">
        
        <style>
            .toggle-on {
                background-image: url('<?= $imageOn ?>');
                background-size: contain;
                background-repeat: no-repeat;
                height: 40px;
                width: 40px;
            }

            .toggle-off {
                background-image: url('<?= $imageOff ?>');
                background-size: contain;
                background-repeat: no-repeat;
                height: 40px;
                width: 40px;
            }
        </style>
    </head>

    <body>

        <div class="controlEgresos_wrapper">
            <div class="container-fluid">
                <div class="controlEgresos_main-card">
                    
                    <!-- Header -->
                    <div class="controlEgresos_header">
                        <div class="controlEgresos_header-content">
                            <div class="controlEgresos_title-section">
                                <h3>
                                    <i class="bi bi-cash-stack"></i>
                                    Control Egresos de Caja - <?= $selectSucursal[1] ?>
                                </h3>
                            </div>
                            <div class="controlEgresos_country-selector">
                                <div class="controlEgresos_country-info">
                                    <span class="controlEgresos_country-label">País actual:</span>
                                    <div class="controlEgresos_country-display">
                                        <img src="<?= ($checkedValue === 'central') ? '../assets/images/bandera_con_sol__55757_std.jpg' : '../assets/images/UY.png' ?>" 
                                             alt="<?= ($checkedValue === 'central') ? 'Argentina' : 'Uruguay' ?>" 
                                             class="controlEgresos_flag">
                                        <span class="controlEgresos_country-name"><?= ($checkedValue === 'central') ? 'ARGENTINA' : 'URUGUAY' ?></span>
                                    </div>
                                </div>
                                <select class="controlEgresos_country-select" onchange="cambiarEntorno(this)">
                                    <option value="ARG" <?= ($checkedValue === 'central') ? 'selected' : '' ?>>🇦🇷 Argentina</option>
                                    <option value="URY" <?= ($checkedValue === 'suc_uy') ? 'selected' : '' ?>>🇺🇾 Uruguay</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="controlEgresos_filters">
                        <form class="form-inline" action="#" method="get">

                            <div class="controlEgresos_filter-row">

                                <div class="controlEgresos_filter-group">
                                    <label class="controlEgresos_filter-label">Desde:</label>
                                    <input type="date" class="controlEgresos_filter-input" id="desde" name="desde" value="<?= $desde ?>">
                                </div>

                                <div class="controlEgresos_filter-group">
                                    <label class="controlEgresos_filter-label">Hasta:</label>
                                    <input type="date" class="controlEgresos_filter-input" id="hasta" name="hasta" value="<?= $hasta ?>">
                                </div>
                                
                                <div class="controlEgresos_filter-group controlEgresos_filter-select">
                                    <label class="controlEgresos_filter-label">Sucursal:</label>
                                    <select name="selectSucursal" id="selectSucursal" class="controlEgresos_filter-input controlEgresos_select2">
                                        <?php foreach ($todosLosLocales as $key => $value) { ?>
                                            <option value="<?php echo ($value['NRO_SUCURSAL']."-".$value['DESC_SUCURSAL']) ?>" 
                                                    <?php if ($selectSucursal[0] == $value['NRO_SUCURSAL']) echo "selected"; ?>>
                                                <?= $value['DESC_SUCURSAL'] ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="controlEgresos_filter-group">
                                    <button class="controlEgresos_filter-btn" id="btnSubmit" type="submit">
                                        <i class="bi bi-funnel-fill"></i>
                                        Filtrar
                                    </button>
                                </div>



                            </div>

                            <div class="controlEgresos_filter-summary">
                                <i class="bi bi-funnel"></i>
                                <strong>Filtros aplicados:</strong> 
                                Desde <?= date('d/m/Y', strtotime($desde)) ?> hasta <?= date('d/m/Y', strtotime($hasta)) ?> 
                                - Sucursal: <?= $selectSucursal[1] ?>
                            </div>

                        </form>
                    </div>
                    
                    <!-- Modal de imágenes -->
                    <div id="carruselImagenes" class="modal fade controlEgresos_modal" tabindex="-1" aria-hidden="true"></div>

                    <!-- Contenedor de tabla -->
                    <div class="controlEgresos_table-container">
                        <?php if($data != null && count($data) > 0) { ?>
                            <table class="controlEgresos_table" id="myTable" cellspacing="0" data-page-length="100">
                                <thead>
                                    <tr>
                                        <th>FECHA</th>
                                        <th>SUCURSAL</th>
                                        <th>TIPO COMP.</th>
                                        <th>COMPROBANTE</th>
                                        <th>COD.CUENTA</th>
                                        <th>CUENTA</th>
                                        <th>MONTO</th>
                                        <th>LEYENDA</th>
                                        <th>VER</th>
                                        <th data-toggle="tooltip" data-placement="top" title="AUTORIZADO"><i class="bi bi-shield-check"></i></th>
                                        <th data-toggle="tooltip" data-placement="top" title="RECIBIDO"><i class="bi bi-inbox"></i></th>
                                        <th data-toggle="tooltip" data-placement="top" title="FACTURA"><i class="bi bi-receipt"></i></th>
                                        <th data-toggle="tooltip" data-placement="top" title="CONTROL"><i class="bi bi-clipboard-check"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    foreach ($data as $key => $gasto) {
                                        $fecha = ( $gasto['FECHA_RECIBIDO'] != null ) ? $gasto['FECHA_RECIBIDO']->format("Y-m-d") : "";
                                        $fechaRecibido = "";
                                        if($gasto['RECIBIDO'] == 1){
                                            $fechaRecibido = "data-toggle='tooltip' data-placement='top' title='FECHA RECIBIDO: $fecha'";
                                        }
                                        
                                        // Clases dinámicas para el estado de la fila
                                        $rowClass = "";
                                        if($gasto['AUTORIZADO'] == 1) $rowClass .= " row-autorizado";
                                        if($gasto['RECIBIDO'] != 1) $rowClass .= " row-pendiente";
                                    ?>
                
                                        <tr class="<?= $rowClass ?>">
                                            <td class="controlEgresos_fecha"><?= $gasto['FECHA']->format("d/m/Y") ?></td>
                                            
                                            <td>
                                                <span class="controlEgresos_sucursal"><?= $gasto['NRO_SUCURS'] ?></span>
                                            </td>
                                            
                                            <td><?= $gasto['COD_COMP'] ?></td>
                                            
                                            <td class="controlEgresos_comprobante" 
                                                data-toggle="tooltip" data-placement="top" 
                                                title="USUARIO: <?= $gasto['USUARIO'] ?? 'N/A' ?>">
                                                <?= $gasto['N_COMP'] ?>
                                            </td>
                                            
                                            <td>
                                                <span class="controlEgresos_cuenta-codigo"><?= $gasto['COD_CTA'] ?></span>
                                            </td>
                                            
                                            <td><?= $gasto['DESC_CUENTA'] ?></td>
                                            
                                            <td>
                                                <?php 
                                                if($gasto['MONTO'] < 0){
                                                    $monto = $gasto['MONTO'] * -1;
                                                    $valor = "- $". number_format($monto, 0, '.','.');
                                                    echo "<span class='controlEgresos_monto-negativo'>$valor</span>";
                                                } else {
                                                    $valor = "$". number_format($gasto['MONTO'], 0, '.','.');
                                                    echo "<span class='controlEgresos_monto-positivo'>$valor</span>";
                                                }
                                                ?>
                                            </td>
                                            
                                            <td>
                                                <span class="controlEgresos_leyenda" 
                                                      data-toggle="tooltip" data-placement="top" 
                                                      title="<?= htmlspecialchars($gasto['LEYENDA']) ?>">
                                                    <?= $gasto['LEYENDA'] ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php if($gasto['guardado'] == 1) { ?>
                                                    <button class="controlEgresos_btn-ver" onclick="mostrarImagen(this)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                <?php } ?>
                                            </td>
                                 
                                            <td>
                                                <?php if($gasto['AUTORIZADO'] == 1) { ?>
                                                    <i class="bi bi-check-circle-fill controlEgresos_icon-autorizado" 
                                                       data-toggle="tooltip" data-placement="top" 
                                                       title="AUTORIZADO: <?= $gasto['FECHA_AUTORIZADO']->format('d/m/Y') ?>"></i>
                                                <?php } ?>
                                            </td>
                                     
                                            <td <?= $fechaRecibido ?>>
                                                <?php if($gasto['RECIBIDO'] == 1) { ?>
                                                    <i class="bi bi-check-circle-fill" style="color: #198754 !important; font-size: 1.3rem;"></i>
                                                <?php } ?>
                                            </td>
                                            
                                            <td>
                                                <input type="checkbox" class="controlEgresos_checkbox" 
                                                       id="checkFactura" onchange="checkFactura(this)" 
                                                       <?= ($gasto['FACTURA'] == 1) ? "checked" : "" ?>>
                                            </td>
                                            
                                            <td>
                                                <input type="checkbox" class="controlEgresos_checkbox" 
                                                       id="checkControl" onchange="checkControl(this)" 
                                                       <?= ($gasto['CONTROL'] == 1) ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                        
                                    <?php 
                                    }
                                ?>   
                                </tbody>
                            </table>
                        <?php } else { ?>
                            <div class="controlEgresos_no-data">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem;"></i>
                                <h5>No hay datos para mostrar</h5>
                                <p>No se encontraron registros para los filtros seleccionados.</p>
                                <small>Intenta cambiar los parámetros de búsqueda o el rango de fechas.</small>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

    </body>

</html>

<!-- INCLUDE JS FILES -->
<?php
    require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php';
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
<script src="js/controlEgresosSucursales.js"></script> 
<script>
    $(document).ready(function() {
        // Inicializar Select2 con estilos personalizados
        $("#selectSucursal").select2({
            theme: 'bootstrap4',
            width: '100%'
        });
        
        // Ajustar estilos del Select2
        setTimeout(function() {
            $(".select2-selection.select2-selection--single").css({
                'height': '44px',
                'border': '2px solid #dee2e6',
                'border-radius': '8px'
            });
            $("#select2-selectSucursal-container").css('margin-top', '8px');
        }, 100);

        // Configurar toggle con estilos personalizados
        setTimeout(function() {
            if (document.querySelector(".toggle")) {
                const toggle = document.querySelector(".toggle");
                const toggleOn = document.querySelector(".toggle-on");
                const toggleOff = document.querySelector(".toggle-off");
                
                toggle.style.width = "80px";
                toggle.style.height = "40px";
                toggle.style.borderRadius = "20px";
                
                if (toggleOn) {
                    toggleOn.style.fontSize = "0";
                    toggleOn.style.width = "35px";
                    toggleOn.style.height = "35px";
                    toggleOn.style.borderRadius = "50%";
                }
                
                if (toggleOff) {
                    toggleOff.style.fontSize = "0";
                    toggleOff.style.width = "35px";
                    toggleOff.style.height = "35px";
                    toggleOff.style.borderRadius = "50%";
                }
            }
        }, 200);

        // Inicializar DataTable con configuración personalizada
        if ($("#myTable").length) {
            // Verificar si DataTable ya está inicializada y destruirla si es necesario
            if ($.fn.DataTable.isDataTable('#myTable')) {
                $('#myTable').DataTable().destroy();
            }
            
            $('#myTable').DataTable({
                "bLengthChange": false,
                "bInfo": true,
                "aaSorting": [[0, "desc"]], // Ordenar por fecha descendente
                "pageLength": 50,
                "responsive": false, // Deshabilitado para mostrar todas las columnas
                "scrollX": true, // Scroll horizontal si es necesario
                "autoWidth": false, // Control manual del ancho
                "dom": 'rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "columnDefs": [
                    {
                        "targets": "_all", 
                        "className": "text-center",
                    },
                    {
                        "targets": [7], // Columna de leyenda
                        "className": "text-left",
                    }
                ],
                "language": {
                    "search": "Buscar:",
                    "searchPlaceholder": "Filtrar registros...",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    },
                    "emptyTable": "No hay datos disponibles en la tabla",
                    "zeroRecords": "No se encontraron registros coincidentes"
                }
            });
        }

        // Inicializar tooltips
        $('[data-toggle="tooltip"]').tooltip({
            container: 'body',
            html: true
        });

        // Añadir efecto de loading al enviar formulario
        $('form').on('submit', function() {
            var btn = $(this).find('button[type="submit"]');
            btn.html('<i class="bi bi-hourglass-split"></i> Cargando...');
            btn.prop('disabled', true);
        });
    });


</script>
