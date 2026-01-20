<?php

include 'Class/ventas.php';

$ventas= new Ventas();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");


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


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de ventas </title>

    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Toggle -->
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/resumenVentas.css">
    
    <style>
        /* Estilos dinámicos para toggle (dependen de variables PHP) */
        .toggle-on {
            background-image: url('<?= $imageOn ?>');
            background-size: contain;
            background-repeat: no-repeat;
            height: 40px;
            width: 40px;
            border-radius: 8px;
        }

        .toggle-off {
            background-image: url('<?= $imageOff ?>');
            background-size: contain;
            background-repeat: no-repeat;
            height: 40px;
            width: 40px;
            border-radius: 8px;
        }
    </style>

</head>

<body>
    <div class="container-fluid">
        <div class="modern-card">
            <div class="page-title">
                <a href="http://192.168.0.13:8000/" class="btn-home" title="Volver al menú">
                    <img src="../image/home-button.png" alt="Home">
                </a>
                <i class="bi bi-credit-card"></i>
                <span>Ventas por medio de pago</span>
                <?php if (isset($_GET['desde'])): ?>
                    <span class="date-range"> (<?= $desde ?> a <?= $hasta ?>)</span>
                <?php endif; ?>
            </div>
            
            <form class="filters-form" method="GET">
                <div>
                    <label>Desde:</label>
                    <input type="date" class="form-control" name="desde" value="<?= $desde ?>">
                </div>
                
                <div>
                    <label>Hasta:</label>
                    <input type="date" class="form-control" name="hasta" value="<?= $hasta ?>">
                </div>
                
                <button type="submit" name="submit" class="btn-modern btn-search" id="search">
                    <i class="bi bi-search"></i> Buscar
                </button>
                
                <button type="button" class="btn-modern btn-export" id="btnExport">
                    <i class="bi bi-file-earmark-excel"></i> Exportar
                </button>
                
                <div class="toggle-wrapper ml-auto">
                    <input type="checkbox" checked data-toggle="toggle" 
                           data-on="<?= $dataOnValue ?>" 
                           data-off="<?= $dataOffValue ?>" 
                           class="custom-toggle" 
                           onchange="cambiarEntorno(this)" 
                           id="checkEntorno">
                </div>
            </form>
        </div>

        <!-- spinner -->
        <div id="boxLoading"></div>
           
    <?php

    if (isset($_GET['desde'])) {
        $todasLasVentas = json_decode($ventas->traerVentas($desde,$hasta));

    ?>

        <div class="table-wrapper">
            <table class="display" id="tableVentas">
                <thead>
                    <tr>
                        <th>NRO. SUC</th>
                        <th>SUCURSAL</th>
                        <th>TARJETA</th>
                        <th>CUENTA DNI</th>
                        <th>TOTAL TARJETAS</th>
                        <th>MERCADO PAGO QR</th>
                        <th>MERCADO PAGO</th>
                        <th>MODO QR</th>
                        <th>PROMO BANCO</th>
                        <th>EFECTIVO</th>
                        <th>BONUS SHOPPING</th>
                        <th>DOLARES</th>
                        <th>EUROS</th>
                        <th>TOTAL VENTAS</th>
                    </tr>
                </thead>

                <tbody id="table">
                    <?php
                    foreach ($todasLasVentas as $valor => $key) {
                    ?>
                        <tr>
                            <td><?= $key->NRO_SUCURSAL ?></td>
                            <td><?= $key->DESC_SUCURSAL ?></td>
                            <td class="tdTarjeta" data-value="<?= $key->TARJETA ?>">$<?= number_format($key->TARJETA, 2, '.', ',') ?></td>
                            <td class="tdCuentaDni" data-value="<?= $key->CUENTA_DNI ?>">$<?= number_format($key->CUENTA_DNI, 2, '.', ',') ?></td>
                            <td class="tdTotalTarjetas col-total-tarjetas" data-value="<?= $key->TOTAL_TARJETAS ?>">$<?= number_format($key->TOTAL_TARJETAS, 2, '.', ',') ?></td>
                            <td class="tdMercadoPagoQr" data-value="<?= $key->MERCADO_PAGO_QR ?>">$<?= number_format($key->MERCADO_PAGO_QR, 2, '.', ',') ?></td>
                            <td class="tdMercadoPago" data-value="<?= $key->MERCADO_PAGO ?>">$<?= number_format($key->MERCADO_PAGO, 2, '.', ',') ?></td>
                            <td class="tdModoQr" data-value="<?= $key->MODO_QR ?>">$<?= number_format($key->MODO_QR, 2, '.', ',') ?></td>
                            <td class="tdPromoBanco valor-negativo" data-value="<?= $key->PROMO_BANCO ?>">
                                <?php if ($key->PROMO_BANCO < 0): ?>
                                    <span style="color: #ef4444;">($<?= number_format(abs($key->PROMO_BANCO), 2, '.', ',') ?>)</span>
                                <?php else: ?>
                                    $<?= number_format($key->PROMO_BANCO, 2, '.', ',') ?>
                                <?php endif; ?>
                            </td>
                            <td class="tdEfectivo" data-value="<?= $key->EFECTIVO ?>">$<?= number_format($key->EFECTIVO, 2, '.', ',') ?></td>
                            <td class="tdBonusShopping" data-value="<?= $key->BONUS_SHOPPING ?>">$<?= number_format($key->BONUS_SHOPPING, 2, '.', ',') ?></td>
                            <td class="tdDolares" data-value="<?= $key->DOLARES ?>">$<?= number_format($key->DOLARES, 2, '.', ',') ?></td>
                            <td class="tdEuros" data-value="<?= $key->EUROS ?>">$<?= number_format($key->EUROS, 2, '.', ',') ?></td>
                            <td class="tdTotalVentas col-total-ventas" data-value="<?= $key->VENTAS ?>">$<?= number_format($key->VENTAS, 2, '.', ',') ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
                
                <tfoot>
                    <tr>
                        <td colspan="2">TOTALES</td>
                        <td id="totalTarjeta"></td>
                        <td id="totalCuentaDni"></td>
                        <td id="totalTarjetas" class="col-total-tarjetas"></td>
                        <td id="totalMercadoPagoQr"></td>
                        <td id="totalMercadoPago"></td>
                        <td id="totalModoQr"></td>
                        <td id="totalPromoBanco"></td>
                        <td id="totalEfectivo"></td>
                        <td id="totalBonusShopping"></td>
                        <td id="totalDolares"></td>
                        <td id="totalEuros"></td>
                        <td id="totalVentas" class="col-total-ventas"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php
    }
    ?>
    </div>
    
    <!-- jQuery (usar versión completa, no slim) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <!-- Plugin to export Excel -->
    <script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
    
    <!-- Bootstrap Toggle -->
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
    
    <!-- Script específico de resumen de ventas (debe cargarse ANTES de main.js para sobrescribir funciones) -->
    <script src="js/resumenVentas.js" charset="utf-8"></script>
    
    <!-- Script principal (main.js mantiene otras funcionalidades) -->
    <script src="js/main.js" charset="utf-8"></script>

</body>

</html>