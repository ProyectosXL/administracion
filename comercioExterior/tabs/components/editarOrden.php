
<?php
require_once __DIR__ . "/../../Controller/listarOrden.php";
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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="icon" type="image/jpg" href="../../images/LOGO XL 2018.jpg">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/editarOrden.css">
    
    <title>Editar Costos de Nacionalización</title>
</head>
<body>
    <div class="container-wrapper">
        <!-- Header Card -->
        <div class="header-card">
            <div class="header-title">
                <i class="bi bi-box-seam-fill"></i>
                <h1>Editar Costos de Nacionalización</h1>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">
                        <i class="bi bi-box-seam"></i>
                        Orden de Compra
                    </div>
                    <div class="info-value"><?= htmlspecialchars($_GET['ordenDeCompra']) ?></div>
                    <div id="nroOrden" hidden><?= htmlspecialchars($_GET['ordenDeCompra']) ?></div>
                </div>

                <div class="info-item provider">
                    <div class="info-label">
                        <i class="bi bi-building"></i>
                        Proveedor
                    </div>
                    <div class="provider-name" title="<?= htmlspecialchars($_GET['proveedor']) ?>">
                        <?= htmlspecialchars($_GET['proveedor']) ?>
                    </div>
                    <div class="provider-code">
                        <i class="bi bi-code-square" style="font-size: 0.7rem;"></i>
                        <?= htmlspecialchars($_GET['codProveedor']) ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="bi bi-cash-dollar"></i>
                        Valor F.O.B.
                    </div>
                    <div class="info-value" id="valorPesosFob" attr-value="<?= $valorNumerico ?>">
                        $ <?= $valorFormateado ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="bi bi-cash-coin"></i>
                        Total Gastos
                    </div>
                    <div class="info-value" id="totalGastosDetalle">$ 0,00</div>
                </div>

                <div class="info-item highlighted">
                    <div class="info-label">
                        <i class="bi bi-percent"></i>
                        Costo de Nac.
                    </div>
                    <div class="info-value"><span id="porcentaje">0,00</span></div>
                </div>
            </div>

            <div id="idEncabezado" attr-value="<?= htmlspecialchars($_GET['idEncabezado']) ?>" hidden></div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-title">
                <i class="bi bi-table"></i>
                Detalle de Costos de Nacionalización
            </div>

            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 220px;">Gastos</th>
                        <th style="width: 130px;">Importe U$S</th>
                        <th style="width: 130px;">Tipo Cambio</th>
                        <th style="width: 150px;">Importe $</th>
                        <th style="width: 110px;">% F.O.B. $</th>
                        <th style="width: 180px;">Observaciones</th>
                        <th style="width: 70px;">Acción</th>
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
                        <td class="cell-id" id="id" attr-value="<?=htmlspecialchars($key['ID'])?>">
                            <span class="badge-info"><?= ($valor+1) ?></span>
                        </td>
                        <td>
                            <input type="text" class="input-field" value="<?= htmlspecialchars($key['GASTOS']) ?>">
                        </td>
                        <td>
                            <input type="text" class="input-field decimales currencyInput" id="valorFobDolar" 
                                   onkeyup="iniciarCalculo(this)" 
                                   value="<?= formatearImporte($key['IMPORTE_U$S']) ?>" 
                                   onclick='window.limpiarInput(this)'>
                        </td>
                        <td>
                            <input type="text" class="input-field decimales currencyInput tipoCambio" 
                                   id="tipoCambio" 
                                   onkeyup="iniciarCalculo(this)" 
                                   value="<?= formatearImporte($key['TIPO_CAMBIO']) ?>" 
                                   onclick='window.limpiarInput(this)'>
                        </td>
                        <td>
                            <input type="text" class="input-field decimales currencyInput importe" 
                                   id="valorFobPeso" name="inputNum[]" readonly 
                                   value="<?= formatearImporte($key['IMPORTE_$']) ?>">
                        </td>
                        <td>
                            <input type="text" class="input-field" value="<?= $porcentajeParseado ?>%" readonly>
                        </td>
                        <td>
                            <input type="text" class="input-field" value="<?= htmlspecialchars($key['OBSERVACIONES']) ?>">
                        </td>
                        <td class="action-cell">
                            <button type="button" class="btn-delete" onclick="borrarGasto(this)" title="Eliminar gasto">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php
                    }   
                    ?>
                    <tr class="total-row">
                        <td colspan="3"></td>
                        <td style="text-align: right; font-weight: 600;">TOTAL</td>
                        <td id="totalGastosDetalleR" value="0">$ 0,00</td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>

            <div class="btn-group-actions">
                <button type="button" class="btn-modern btn-primary-modern" id="btnAgregarDetalle">
                    <i class="bi bi-plus-circle"></i>
                    Agregar Gasto
                </button>
                <button type="button" class="btn-modern btn-success-modern" id="btnUpdateDetalle">
                    <i class="bi bi-check-circle"></i>
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/jquery/jquery.min.js"></script>
    <script src="../../js/utils-formateo.js"></script>
    <script src="../../js/costosEditar.js"></script>
    <script src="../../js/editar.js"></script>

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