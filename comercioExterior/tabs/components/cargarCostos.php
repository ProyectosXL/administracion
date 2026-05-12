<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Validar que se recibió el ID del despacho
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Error: ID de despacho no especificado');
}

$idDespacho = intval($_GET['id']);

// Cargar datos del despacho
require_once '../../Class/encabezado.php';
require_once '../../Class/maestroGastos.php';

$encabezadoClass = new Encabezado();
$gastosClass     = new Gastos();

// Guardia: si el ID pertenece a una OC hija, redirigir al principal con aviso
$idPrincipal = $encabezadoClass->resolverIdPrincipal($idDespacho);
if ($idPrincipal !== $idDespacho) {
    echo '<!DOCTYPE html><html><head>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head><body>
    <script>
    Swal.fire({
        title: "OC vinculada a contenedor",
        text: "Esta OC es hija de otra. Te redirigimos a la OC principal para gestionar los costos.",
        icon: "info",
        confirmButtonText: "Continuar",
        confirmButtonColor: "#7066e0",
        allowOutsideClick: false
    }).then(function() {
        window.location.href = "cargarCostos.php?id=' . $idPrincipal . '";
    });
    </script></body></html>';
    exit;
}

$despacho = $encabezadoClass->obtenerDespachoPorId($idDespacho);
$todosLosGastos = $gastosClass->traerGastos();

if (!$despacho) {
    die('Error: Despacho no encontrado');
}

// Formatear valores
$valorFobPeso = isset($despacho['VALOR_FOB_PESO']) ? number_format($despacho['VALOR_FOB_PESO'], 2, ',', '.') : '0,00';
$tipoCambio = isset($despacho['TIPO_CAMBIO']) ? number_format($despacho['TIPO_CAMBIO'], 2, ',', '.') : '0,00';
$ordenCompra = $despacho['ORDEN_COMPRA'] ?? '';
$proveedor = $despacho['PROVEEDOR'] ?? '';
$contenedor = $despacho['CONTENEDOR'] ?? '';
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
    <link rel="stylesheet" href="../../css/gestionarCostos.css">
    
    <title>Gestionar Costos - <?= $proveedor ?></title>
</head>
<body>
    <div class="container-wrapper">
        <!-- Header Card -->
        <div class="header-card">
            <div class="header-title">
                <i class="bi bi-box-seam-fill"></i>
                <h1>Gestionar Costos de Nacionalización</h1>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">
                        <i class="bi bi-box-seam"></i>
                        Orden de Compra
                    </div>
                    <div class="info-value"><?= htmlspecialchars($ordenCompra) ?></div>
                    <div id="nroOrdenCompra" hidden><?= htmlspecialchars($ordenCompra) ?></div>
                </div>

                <div class="info-item provider">
                    <div class="info-label">
                        <i class="bi bi-building"></i>
                        Proveedor
                    </div>
                    <div class="provider-name" title="<?= htmlspecialchars($proveedor) ?>">
                        <?= htmlspecialchars($proveedor) ?>
                    </div>
                    <div class="provider-code">
                        <i class="bi bi-archive" style="font-size: 0.7rem;"></i>
                        <?= htmlspecialchars($contenedor) ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="bi bi-cash-dollar"></i>
                        Valor F.O.B.
                    </div>
                    <div class="info-value" id="valorPesosFob" attr-value="<?= $valorFobPeso ?>">
                        $ <?= $valorFobPeso ?>
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

            <div id="idEncabezado" attr-value="<?= $idDespacho ?>" hidden></div>
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
                    </tr>
                </thead>
                <tbody id="table">
                    <?php
                    foreach ($todosLosGastos as $valor => $key) {
                    ?>
                    <tr id="trBody">
                        <td class="cell-id" id="id">
                            <span class="badge-info"><?= $key['ID_MG'] ?></span>
                        </td>
                        <td><?= htmlspecialchars($key['GASTOS']) ?></td>
                        <td>
                            <input class="input-field decimales currencyInput" 
                                   type="text" 
                                   id="valorFobDolar" 
                                   onkeyup="iniciarCalculo(this)" 
                                   onchange='convertirNumeros(this)' 
                                   onclick='limpiarInput(this)'>
                        </td>
                        <?php if (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') { ?>
                            <td>
                                <input class="input-field decimales currencyInput tipoCambio" 
                                       type="text" 
                                       onkeyup="iniciarCalculo(this)" 
                                       id="tipoCambio" 
                                       value="0" 
                                       onchange='convertirNumeros(this)' 
                                       onclick='limpiarInput(this)'>
                            </td>
                        <?php } else { ?>
                            <td>
                                <input class="input-field decimales currencyInput tipoCambio" 
                                       type="text" 
                                       onkeyup="iniciarCalculo(this)" 
                                       id="tipoCambio" 
                                       onchange='convertirNumeros(this)' 
                                       value="<?= ($valor <= 6) ? $tipoCambio : "0" ?>" 
                                       onclick='limpiarInput(this)'>
                            </td>
                        <?php } ?>
                        <td>
                            <input class="input-field decimales currencyInput importe skip" 
                                   id="valorFobPeso" 
                                   name="inputNum[]" 
                                   readonly>
                        </td>
                        <td>
                            <input class="input-field skip" readonly>
                        </td>
                        <td>
                            <input class="input-field skip">
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <!-- Fila de Totales -->
                    <tr class="total-row">
                        <td colspan="3"></td>
                        <td style="text-align: right; font-weight: 600;">TOTAL</td>
                        <td id="totalGastosDetalleR" value="0">$ 0,00</td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>

            <div class="btn-group-actions">
                <a href="/administracion/comercioExterior/index.php" target="_top" class="btn-modern btn-secondary-modern">
                    <i class="bi bi-arrow-left"></i>
                    Volver
                </a>
                <button type="button" class="btn-modern btn-primary-modern" id="btnSaveDetalle">
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
    <script src="../../js/cargarCostos.js"></script>

    <!-- Script para navegación con flechas -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Obtener todas las celdas de entrada en la tabla
            var inputs = document.querySelectorAll("input.currencyInput");

            // Agregar evento de teclado a cada celda de entrada
            inputs.forEach(function(input) {
                input.addEventListener("keydown", function(event) {
                    // Verificar si se presionó la tecla de flecha hacia abajo
                    if (event.key === "ArrowDown") {
                        moveFocus(event, 1);
                    }
                    // Verificar si se presionó la tecla de flecha hacia arriba
                    else if (event.key === "ArrowUp") {
                        moveFocus(event, -1);
                    }
                });
            });

            function moveFocus(event, direction) {
                // Obtener el índice de la celda actual
                var currentIndex = Array.prototype.indexOf.call(inputs, event.target);

                // Calcular el índice de la siguiente celda de entrada
                var nextIndex = currentIndex + direction;

                // Verificar si el índice está dentro del rango de celdas de entrada
                if (nextIndex >= 0 && nextIndex < inputs.length) {
                    // Si la siguiente celda de entrada es la que deseas omitir, salta a la siguiente
                    if (inputs[nextIndex].classList.contains('skip')) {
                        nextIndex += direction;
                    }
                    // Enfocar la siguiente celda de entrada
                    inputs[nextIndex].focus();
                } else {
                    // Si está fuera de rango, no hacer nada (mantener el foco en la celda actual)
                    event.preventDefault();
                }
            }
        });
    </script>
</body>
</html>
