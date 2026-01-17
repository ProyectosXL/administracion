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
// Usar banderas de mejor calidad
$imageOn = ($checkedValue === 'central') ? 'https://flagcdn.com/w80/ar.png' : 'https://flagcdn.com/w80/uy.png';
$imageOff = ($checkedValue === 'central') ? 'https://flagcdn.com/w80/uy.png' : 'https://flagcdn.com/w80/ar.png';


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

</head>

<body>
    <div class="container-fluid">
        <!-- Card único con header, título y filtros -->
        <div class="modern-card unified-header-card">
            <!-- Primera fila: Home, Título y Toggle -->
            <div class="header-row">
                <div class="left-section">
                    <a href="http://192.168.0.13:8000/" class="btn-home" title="Volver al menú">
                        <img src="../image/home-button.png" alt="Home">
                    </a>
                    <div class="page-title">
                        <i class="bi bi-credit-card"></i>
                        <span>Ventas por medio de pago</span>
                        <?php if (isset($_GET['desde'])): ?>
                            <span class="date-range">(<?= $desde ?> a <?= $hasta ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="country-toggle-container">
                    <div class="entorno-label">
                        <span>Entorno:</span>
                        <img src="<?= $imageOn ?>" alt="<?= $dataOnValue ?>" class="bandera-icon" id="banderaEntorno">
                        <span class="entorno-text" id="textoEntorno"><?= $dataOnValue ?></span>
                    </div>
                    <input type="checkbox" <?= $checked ?> data-toggle="toggle" 
                           data-on="<?= $dataOnValue ?>" 
                           data-off="<?= $dataOffValue ?>" 
                           data-onstyle="info"
                           data-offstyle="info"
                           data-width="70"
                           data-height="38"
                           class="custom-toggle" 
                           onchange="cambiarEntorno(this)" 
                           id="checkEntorno">
                </div>
            </div>
            
            <!-- Segunda fila: Filtros -->
            <div class="filters-row-separator"></div>
            <form class="filters-form-improved" method="GET">
                <div class="filters-row">
                    <div class="filter-field">
                        <label>Desde:</label>
                        <input type="date" class="form-control" name="desde" value="<?= $desde ?>">
                    </div>
                    
                    <div class="filter-field">
                        <label>Hasta:</label>
                        <input type="date" class="form-control" name="hasta" value="<?= $hasta ?>">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" name="submit" class="btn-modern btn-search" id="search">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        
                        <button type="button" class="btn-modern btn-export" id="btnExport">
                            <i class="bi bi-file-earmark-excel"></i> Exportar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- spinner -->
        <div id="boxLoading">
            <div class="spinner-circle"></div>
            <div class="spinner-text">Cargando...</div>
        </div>
           
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
                        <td id="totalTarjeta">$0.00</td>
                        <td id="totalCuentaDni">$0.00</td>
                        <td id="totalTarjetas" class="col-total-tarjetas">$0.00</td>
                        <td id="totalMercadoPagoQr">$0.00</td>
                        <td id="totalMercadoPago">$0.00</td>
                        <td id="totalModoQr">$0.00</td>
                        <td id="totalPromoBanco">$0.00</td>
                        <td id="totalEfectivo">$0.00</td>
                        <td id="totalBonusShopping">$0.00</td>
                        <td id="totalDolares">$0.00</td>
                        <td id="totalEuros">$0.00</td>
                        <td id="totalVentas" class="col-total-ventas">$0.00</td>
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

</body>

</html>
