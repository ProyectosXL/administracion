<?php
    require_once "Class/Alquiler.php";
    require_once "Class/Sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $conceptos = $alquiler->traerConceptosPorcentaje();
    $locales = $sucursal->traerLocales();
    
    $conceptoFiltrado = isset($_GET['conceptos']) ? $_GET['conceptos'] : "6-Porc. S/ventas brutas";

    $idConcepto = explode("-", $conceptoFiltrado)[0];
    $descConcepto = explode("-", $conceptoFiltrado)[1];
    $porcentajePorSucursal = $alquiler->traerPorcentajeSucursal($idConcepto);

    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $checked = ($checkedValue === 'central') ? 'checked' : '';
    $dataOnValue = 'ARG';
    $dataOffValue = 'UY';
    $imagenBandera = ($checkedValue === 'central') ? 
        '../../assets/images/bandera_con_sol__55757_std.jpg' : 
        '../../assets/images/UY.png';
    $nombrePais = ($checkedValue === 'central') ? 'Argentina' : 'Uruguay';

    ?>


    <!DOCTYPE html>
    <html lang="en">

    <style>

        /* Toggle styles */
        .toggle-on, .toggle-off {
            font-size: 12px !important;
            font-weight: bold !important;
            color: white !important;
            text-shadow: none !important;
            line-height: 30px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-size: auto !important;
            background-image: none !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            height: auto !important;
            width: auto !important;
            min-height: 34px !important;
            min-width: 45px !important;
        }

        .toggle.btn {
            height: 38px !important;
            min-width: 90px !important;
            border-radius: 6px !important;
            padding: 0 !important;
        }

        .toggle-on {
            background-color: #007bff !important;
            border-color: #007bff !important;
        }
        
        .toggle-off {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
        }

        .flag-indicator {
            width: 40px;
            height: 30px;
            margin-left: 10px;
            border-radius: 4px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border: 2px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .environment-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .country-label {
            color: white;
            font-size: 14px;
            font-weight: bold;
            margin-left: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

    </style>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestion de Conceptos</title>
        
        <!-- INCLUDES CSS -->
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
        ?>
        <link rel="stylesheet" href="css/porcGastosAlquileres.css">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">

    </head>

    <body>

        <div class="alert alert-secondary">
            <div class="page-wrapper p-b-100 pt-2 font-robo">
                <div class="wrapper wrapper--w680">
                    <div class="card card-1">
                        <div id="username" hidden><?= $_SESSION['username'] ?></div>
                        
                        <!-- Header -->
                        <div class="row" style="margin-left:50px; padding: 1.5rem;">
                            <a href="http://192.168.0.13:8000/" style="display:inline-block;">
                                <img src="../../image/home-button.png" style="width:50px;height:45px;transition: transform 0.3s;" title="Menú Principal">
                            </a>
                            <h3 class="ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                class="bi bi-graph-up-arrow" viewBox="0 0 16 16" style="vertical-align: middle; margin-right: 6px;">
                                <path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0zm15 10.5a.5.5 0 0 1-.5.5H12l-3-3-3 3L2.5 8.5a.5.5 0 0 1 .707-.707L6 10.586l3-3 3 3h2.5a.5.5 0 0 1 .5.5z"/>
                            </svg>
                            Gestión de Porcentajes - <?= $descConcepto ?>
                            </h3>

                            <div class="environment-controls">
                                <input type="checkbox" <?= $checked ?> data-toggle="toggle" 
                                       data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" 
                                       class="custom-toggle"
                                       id="checkEntorno">
                                <div class="flag-indicator" style="background-image: url('<?= $imagenBandera ?>');" 
                                     title="<?= $nombrePais ?>"></div>
                                <span class="country-label"><?= $nombrePais ?></span>
                            </div>
                        </div>

                        <!-- Formulario de Filtros -->
                        <form action="">
                            <div class="container-fluid">
                                <div class="row">
                                    <!-- Selector de Conceptos -->
                                    <div>
                                        <div class="row">
                                            <div>Concepto:</div>
                                            <div>
                                                <select name="conceptos" id="conceptos">
                                                    <?php 
                                                    foreach ($conceptos as  $value) {
                                                    ?>
                                                        <option value="<?= $value['ID_CA'] ?>-<?= $value['CONCEPTO'] ?>" <?php  if($idConcepto == $value['ID_CA']){ echo "selected"; }?>><?= $value['CONCEPTO'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div>
                                                <button class="btn btn-primary submit">Filtrar <i class="bi bi-funnel-fill"></i></button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selector de Sucursales -->
                                    <div>
                                        <div class="row">
                                            <div>Sucursal:</div>
                                            <div>
                                                <select name="locales" id="locales"  class="form-select">
                                                    <?php 
                                                    foreach ($locales as  $value) {
                                                    ?>
                                                        <option value="<?= $value['NRO_SUCURSAL'] ?>"><?= $value['DESC_SUCURSAL'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div>
                                                <button class="btn btn-success" type="button" onclick="agregar()">Agregar <i class="bi bi-plus-square"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Tabla de Porcentajes -->
                        <div class="row" style="margin:0;">
                            <div class="table-responsive" id="tableIndex">
                                <table class="table table-hover table-condensed table-striped text-center" cellspacing="0" data-page-length="100">
                                    <thead class="thead-dark">
                                        <th scope="col">SUCURSAL</th>
                                        <th scope="col">NOMBRE</th>
                                        <th scope="col">PORCENTAJE</th>
                                        <th scope="col">ACCIÓN</th>
                                    </thead>

                                    <tbody id="tableVb">
                                        <?php foreach ($porcentajePorSucursal as $key => $value) { ?>
                                            <tr>
                                                <td hidden><?= $value['ID_PA'] ?></td>
                                                <td><?= $value['NRO_SUCURS'] ?></td>
                                                <td><?= $value['DESC_SUCURS'] ?></td>
                                                <td><input type="text" value="<?= $value['PORCENTAJE'] ?>" onchange="actualizarPorcentaje(this)"></td>
                                                <td>
                                                    <button type="button" class="btn btn-danger" onclick="eliminarPorcentaje(this)" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php';
        ?>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <script>
        $(document).ready(function() {
            console.log('Estado inicial del entorno:', '<?= $_SESSION['entorno'] ?>');
            console.log('Checked value:', '<?= $checked ?>');
            
            // Ajustar estilos del toggle
            setTimeout(() => {
                const toggle = document.querySelector(".toggle");
                if (toggle) {
                    toggle.style.width = "90px";
                    toggle.style.height = "38px";
                }
                
                const toggleOn = document.querySelector(".toggle-on");
                const toggleOff = document.querySelector(".toggle-off");
                
                if (toggleOn) {
                    toggleOn.style.backgroundImage = "none";
                    toggleOn.style.backgroundSize = "auto";
                    toggleOn.style.width = "auto";
                    toggleOn.style.height = "auto";
                    toggleOn.style.minWidth = "45px";
                    toggleOn.style.minHeight = "34px";
                    toggleOn.style.fontSize = "12px";
                    toggleOn.style.fontWeight = "bold";
                    toggleOn.style.color = "white";
                    toggleOn.style.display = "flex";
                    toggleOn.style.alignItems = "center";
                    toggleOn.style.justifyContent = "center";
                    toggleOn.style.textShadow = "none";
                }
                if (toggleOff) {
                    toggleOff.style.backgroundImage = "none";
                    toggleOff.style.backgroundSize = "auto";
                    toggleOff.style.width = "auto";
                    toggleOff.style.height = "auto";
                    toggleOff.style.minWidth = "45px";
                    toggleOff.style.minHeight = "34px";
                    toggleOff.style.fontSize = "12px";
                    toggleOff.style.fontWeight = "bold";
                    toggleOff.style.color = "white";
                    toggleOff.style.display = "flex";
                    toggleOff.style.alignItems = "center";
                    toggleOff.style.justifyContent = "center";
                    toggleOff.style.textShadow = "none";
                }
                
                // Agregar evento de cambio usando jQuery después de que el toggle esté inicializado
                $('#checkEntorno').off('change').on('change', function() {
                    const isChecked = $(this).prop('checked');
                    console.log('Toggle changed via jQuery, checked:', isChecked);
                    cambiarEntorno(this);
                });
                
            }, 100);
        });

        // Función de cambio de entorno
        function cambiarEntorno(toggle) {
            console.log('Toggle clicked, checked:', toggle.checked);
            
            // checked = true → Argentina (0)
            // checked = false → Uruguay (1)
            const entorno = toggle.checked ? 0 : 1;
            
            console.log('Enviando entorno:', entorno);
            
            Swal.fire({
                title: 'Cambiando entorno',
                text: 'Por favor espere...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "Controller/cambiarEntorno.php",
                method: "POST",
                data: { entorno: entorno },
                dataType: 'json',
                success: function(data) {
                    console.log('Respuesta del servidor:', data);
                    if (data.success) {
                        setTimeout(() => {
                            location.reload();
                        }, 100);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cambiar entorno',
                            text: data.message || 'Error desconocido',
                            confirmButtonText: "Entendido",
                            confirmButtonColor: "#e74c3c"
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al cambiar entorno',
                        text: 'No se pudo cambiar el entorno. Intente nuevamente.',
                        confirmButtonText: "Entendido",
                        confirmButtonColor: "#e74c3c"
                    });
                }
            });
        }
        </script>
        <script src="js/cargaDePorcentaje.js"></script>
    </body>

    </html>
