
<?php
require_once __DIR__ . "/Controller/listarOrden.php";
$ordenCompra = $_GET['idEncabezado']; 
$orden = listarPorOrdenCompra($ordenCompra);

// Función para formatear números con separador de miles
function formatearImporte($numero) {
    if (is_null($numero) || $numero === '' || $numero === 0) {
        return '0,00';
    }
    return number_format((float)$numero, 2, ',', '.');
}

// Función para obtener valor numérico limpio CORREGIDA
function obtenerValorNumerico($valor) {
    if (is_null($valor) || $valor === '') {
        return 0;
    }
    
    // Si ya es un número, devolverlo directamente
    if (is_numeric($valor)) {
        return (float)$valor;
    }
    
    // Si es string, detectar el formato
    if (is_string($valor)) {
        $valor = trim($valor);
        
        // Contar puntos y comas para determinar el formato
        $puntos = substr_count($valor, '.');
        $comas = substr_count($valor, ',');
        
        // Caso 1: Formato como 53384128.15 (punto decimal, sin separadores de miles)
        if ($puntos == 1 && $comas == 0) {
            return (float)$valor;
        }
        
        // Caso 2: Formato como 53.384.128,15 (puntos como miles, coma decimal)
        if ($puntos > 1 && $comas == 1) {
            $limpio = str_replace('.', '', $valor); // Remover puntos (miles)
            $limpio = str_replace(',', '.', $limpio); // Coma a punto decimal
            return (float)$limpio;
        }
        
        // Caso 3: Formato como 53,384,128.15 (comas como miles, punto decimal)
        if ($comas > 1 && $puntos == 1) {
            $limpio = str_replace(',', '', $valor); // Remover comas (miles)
            return (float)$limpio;
        }
        
        // Caso 4: Solo comas (formato español sin miles)
        if ($comas == 1 && $puntos == 0) {
            $limpio = str_replace(',', '.', $valor);
            return (float)$limpio;
        }
        
        // Caso 5: Solo puntos (formato inglés sin miles)
        if ($puntos == 1 && $comas == 0) {
            return (float)$valor;
        }
        
        // Fallback: remover todo formato y convertir
        $limpio = preg_replace('/[^0-9.]/', '', $valor);
        return (float)$limpio;
    }
    
    return (float)$valor;
}

// Debug para verificar la conversión
$valorOriginal = $_GET['valorFobPeso'];
$valorNumerico = obtenerValorNumerico($valorOriginal);
$valorFormateado = formatearImporte($valorOriginal);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <!-- Icons font CSS-->
    <link href="assets/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">
    <link href="assets/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    <!-- Font special for pages-->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <!-- Vendor CSS-->
    <link href="assets/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="assets/datepicker/daterangepicker.css" rel="stylesheet" media="all">

    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">
    <!-- Main CSS-->
    <link href="css/style.css" rel="stylesheet" media="all">
    <title>Carga Costos</title>
</head>
<body>
    <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo" >
        <div class="wrapper wrapper--w680" style="margin-left: 12rem;">
            <div class="card card-1" style="width: 1200px; justify-content:center; text-align:center">
                <div class="card-heading"></div>
                <div class="card-body">
                    <div class="alert alert-primary">
                        <div class="row justify-content-md-center mb-2">
                            <div class="col-md-auto"><h3 class="mb-1" style="font-weight: bold;"><i class="bi bi-box-seam-fill"></i> <?= $_GET['proveedor'].'-'.$_GET['ordenDeCompra']?></h3></div>
                            <div id="nroOrden" hidden><?= $_GET['ordenDeCompra'] ?></div>
                        </div>
                        <div class="row justify-content-md-center">
                            <div class="col-md-auto"><i class="bi bi-airplane-fill icon"></i><h5 class="mb-1"><label style="font-weight: bold;">Nº Orden Proveedor</label><?= ' '.$_GET['codProveedor']?></h5></div>
                            <!-- VALOR FOB CORREGIDO -->
                            <div class="col-md-auto">
                                <i class="bi bi-cash icon"></i>
                                <h5 class="mb-1" id="valorPesosFob" attr-value="<?= $valorNumerico ?>">
                                    <label style="font-weight: bold;">Valor F.O.B. $: </label><?= $valorFormateado ?>
                                </h5>
                            </div>
                            <div id="idEncabezado" attr-value="<?= $_GET['idEncabezado'] ?>" hidden></div>
                            <div class="col-md-auto"><i class="bi bi-cash-coin icon"></i><h5 class="mb-1"><label  id="totalGastosDetalle" style="font-weight: bold;">Gastos $:</label></h5></div>
                            <div class="col-md-auto"><i class="bi bi-percent icon"></i><h5 class="mb-1"><label style="font-weight: bold;">Costos nac.: </label> <span id="porcentaje"></span></h5></div>
                        </div>
                    </div>
                    <h2 class="title"><i class="bi bi-folder-check"></i> Detalle Costos de Nacionalizacion</h2>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="col-">ID</th>
                                    <th class="col-3">GASTOS</th>
                                    <th class="col-">IMPORTE U$S</th>
                                    <th class="col-">TIPO CAMBIO</th>
                                    <th class="col-">IMPORTE $</th>
                                    <th class="col-">% SOBRE F.O.B. $</th>
                                    <th class="col-">OBSERVACIONES</th>
                                </tr>
                            </thead>
                            <tbody id="table">

                                <?php
                                foreach($orden as $valor => $key){
                                    $importePesoNumerico = obtenerValorNumerico($key['IMPORTE_$']);
                                    
                                    $porcentaje = $valorNumerico > 0 ? ($importePesoNumerico / $valorNumerico) * 100 : 0;
                                    $porcentajeParseado = number_format((float)$porcentaje, 2, ',', '.'); 
                                ?>
                                <tr>
                                    <td id="id" attr-value="<?=$key['ID']?>"><?=  ($valor+1)?></td>
                                    <td><input style="text-align:center" type="text"  value = "<?=  $key['GASTOS']?>"></input></td>
                                    <td><input class="decimales currencyInput" style="text-align:center" type="text" id="valorFobDolar" onkeyup="iniciarCalculo(this)" value = "<?= formatearImporte($key['IMPORTE_U$S']) ?>" onclick='window.limpiarInput(this)'></input></td>
                                    <td><input class="decimales currencyInput tipoCambio" style="text-align:center" type="text"  onkeyup="iniciarCalculo(this)" id="tipoCambio" value="<?= formatearImporte($key['TIPO_CAMBIO']) ?>" onclick='window.limpiarInput(this)'></input></td>
                                    <td><input class="decimales currencyInput importe" style="text-align:center" type="text" id="valorFobPeso" name="inputNum[]" readonly value="<?= formatearImporte($key['IMPORTE_$']) ?>"></input></td>
                                    <td><input style="text-align:center"  value="<?= $porcentajeParseado ?>%" readonly></input></td>
                                    <td><input value="<?=$key['OBSERVACIONES']?>"></input></td>
                                    <td><button type="button" class="btn btn-danger" onclick="borrarGasto(this)">X</button></td>
                                </tr>
                            <?php
                            }   
                            ?>
                                <tr class="alert alert-primary" style="font-weight: bold;">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>TOTAL</td>
                                    <td id="totalGastosDetalleR" value="0"></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <div><button class="btn btn-primary m-r" id="btnAgregarDetalle" >Agregar Gasto <i class="bi bi-cloud-download"></i></button> <button class="btn btn-primary" id="btnUpdateDetalle">Guardar <i class="bi bi-cloud-download"></i></button></div>
                </div>
            </div>
        </div>
    </div>

    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Jquery JS-->
    <script src="assets/jquery/jquery.min.js"></script>

    <!-- Utilities script - DEBE CARGARSE PRIMERO -->
    <script src="js/utils-formateo.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.min.js" integrity="sha384-IDwe1+LCz02ROU9k972gdyvl+AESN10+x7tBKgc9I5HFtuNz0wWnPclzo6p9vxnk" crossorigin="anonymous"></script>
    <script src="js/costosEditar.js"></script>
    <script src="js/editar.js"></script>

    <!-- DEBUG: Mostrar valores para verificación -->
    <script>
        console.log('DEBUG PHP - Valor FOB original:', '<?= $valorOriginal ?>');
        console.log('DEBUG PHP - Valor FOB numérico corregido:', <?= $valorNumerico ?>);
        console.log('DEBUG PHP - Valor FOB formateado:', '<?= $valorFormateado ?>');
        console.log('DEBUG PHP - Tipo del valor original:', typeof '<?= $valorOriginal ?>');
        console.log('DEBUG PHP - Es numérico?:', <?= is_numeric($valorOriginal) ? 'true' : 'false' ?>);
    </script>

</body>
</html>