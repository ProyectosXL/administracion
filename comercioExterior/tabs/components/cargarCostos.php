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
require_once '../../Class/Orden.php';

$encabezadoClass = new Encabezado();
$gastosClass     = new Gastos();
$ordenClass      = new Orden();

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
$valorFobDolar = isset($despacho['VALOR_FOB_DOLAR']) ? number_format($despacho['VALOR_FOB_DOLAR'], 2, ',', '.') : '0,00';
$tipoCambio = isset($despacho['TIPO_CAMBIO']) ? number_format($despacho['TIPO_CAMBIO'], 2, ',', '.') : '0,00';
$ordenCompra = $despacho['ORDEN_COMPRA'] ?? '';
$proveedor = $despacho['PROVEEDOR'] ?? '';
$contenedor = $despacho['CONTENEDOR'] ?? '';

/* ---------------------------------------------------------------------
   LOS COSTOS YA CARGADOS.

   Esta pantalla se dibujaba SIEMPRE en blanco: recorría el maestro de
   gastos y pintaba una fila vacía por cada uno, sin mirar nunca
   RO_T_IMPORTACIONES_DETALLE. Guardar funcionaba, pero volver a entrar
   mostraba el formulario limpio y cualquier "modificar un importe"
   terminaba siendo "volver a tipear los veinte".

   No hace falta nada más para que la edición posterior funcione: el
   guardado ya borra el detalle del grupo e inserta el que manda la
   pantalla (OrdenDeCompraController -> deleteDetalleGrupo +
   insertDetalleReplicado), así que guardar diez veces deja siempre una
   fila por gasto. Lo único que faltaba era traer lo guardado.

   Se indexa por GASTOS y no por ID porque es lo que une las dos tablas:
   el detalle guarda el NOMBRE del gasto, no el ID del maestro.
   --------------------------------------------------------------------- */
$detalleGuardado = [];
foreach ($ordenClass->traerPorOrdenCompra($idDespacho) as $fila) {
    $nombreGasto = isset($fila['GASTOS']) ? trim((string) $fila['GASTOS']) : '';
    if ($nombreGasto === '') {
        continue;
    }
    $detalleGuardado[mb_strtoupper($nombreGasto)] = $fila;
}

$hayDetalleGuardado = count($detalleGuardado) > 0;

/* FILAS GUARDADAS QUE YA NO ESTÁN EN EL MAESTRO DE GASTOS.
   El formulario dibuja una fila por gasto del maestro, así que un costo
   guardado con un nombre que el maestro ya no tiene no se puede mostrar —y
   el guardado, que borra el detalle y lo reinserta desde la pantalla, lo
   perdería sin decir nada.

   Ya pasaba antes de traer los valores; lo que cambia es que ahora se avisa.
   En la base al 19/09/2026 son 27 filas en 5 contenedores viejos (ID_MG 20,
   131, 195, 369 y 497), de 6162: nombres anteriores al maestro actual, como
   "MONTECON" o "GASTOS DESPACHANTE PRACCA".

   La comparación se hace acá y no en SQL a propósito: las dos tablas tienen
   collations distintas -Latin1_General_BIN contra Modern_Spanish_CI_AI- y
   un JOIN por nombre falla con "cannot resolve the collation conflict", que
   es el mismo problema que Orden::traerOrdenPorFecha resuelve con un COLLATE
   explícito. */
$gastosDelMaestro = [];
foreach ($todosLosGastos as $g) {
    $gastosDelMaestro[mb_strtoupper(trim($g['GASTOS']))] = true;
}

$costosHuerfanos = [];
foreach ($detalleGuardado as $clave => $fila) {
    if (!isset($gastosDelMaestro[$clave])) {
        $costosHuerfanos[] = trim((string) $fila['GASTOS']);
    }
}

/**
 * Formatea un número al formato que espera cargarCostos.js: miles con
 * punto y decimales con coma ("1.234,56"), que es lo que produce el
 * toLocaleString('es-ES') del lado del navegador. Si se devolviera con
 * punto decimal, sacarParseo() lo leería como separador de miles al
 * guardar y 1.234,56 se convertiría en 123456.
 */
function formatoCampo($valor) {
    if ($valor === null || $valor === '') {
        return '';
    }
    $num = floatval(str_replace(',', '.', (string) $valor));
    return number_format($num, 2, ',', '.');
}

/**
 * El % sobre FOB va con PUNTO decimal y sufijo '%', que es exactamente lo
 * que escribe iniciarCalculo() con toFixed(2) y lo que el guardado espera
 * al hacer replace('%',''). Con coma, el INSERT recibiría "8,50" y SQL
 * Server lo tomaría como dos columnas.
 */
function formatoPorcentaje($valor) {
    if ($valor === null || $valor === '') {
        return '';
    }
    return number_format(floatval(str_replace(',', '.', (string) $valor)), 2, '.', '') . '%';
}
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
                    <!-- El FOB en dólares, que es el editable y del que sale
                         el de pesos. Se muestra al lado para que, si alguien
                         lo corrigió, se vea acá de dónde salió el % sobre FOB
                         de cada fila. -->
                    <div class="provider-code">U$S <?= $valorFobDolar ?> × TC <?= $tipoCambio ?></div>
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

            <?php if ($hayDetalleGuardado) { ?>
            <!-- Que la pantalla diga que está mostrando lo guardado, y no una
                 carga nueva en blanco, es la mitad del arreglo: los mismos
                 campos llenos podrían leerse como valores tipeados y todavía
                 sin guardar. -->
            <div class="alert alert-info py-2 px-3 mb-0" style="margin: 0 1rem;">
                <i class="bi bi-clock-history"></i>
                Se muestran los <strong><?= count($detalleGuardado) ?></strong> costos ya registrados
                para este contenedor. Modificá lo que haga falta y guardá: se reemplazan los valores,
                no se agregan filas nuevas.
            </div>
            <?php } ?>

            <?php if (!empty($costosHuerfanos)) { ?>
            <!-- Se avisa antes de guardar, no después: guardar reemplaza el
                 detalle entero por lo que está en pantalla, y estos conceptos
                 no están en pantalla porque ya no existen en el maestro. -->
            <div class="alert alert-warning py-2 px-3 mb-0" style="margin: 0.5rem 1rem 0;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Este contenedor tiene <strong><?= count($costosHuerfanos) ?></strong> costo(s)
                guardado(s) con conceptos que ya no están en el maestro de gastos:
                <strong><?= htmlspecialchars(implode(', ', $costosHuerfanos)) ?></strong>.
                No se pueden mostrar acá y <strong>se van a perder si guardás</strong>.
                Si hacen falta, hay que darlos de alta en el maestro de gastos primero.
            </div>
            <?php } ?>

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
                        // Lo que ya estaba guardado para este gasto, si hay algo.
                        $guardado = $detalleGuardado[mb_strtoupper(trim($key['GASTOS']))] ?? null;

                        $vImporteUsd = $guardado ? formatoCampo($guardado['IMPORTE_U$S']) : '';
                        $vImportePeso = $guardado ? formatoCampo($guardado['IMPORTE_$']) : '';
                        $vPorcentaje = $guardado ? formatoPorcentaje($guardado['PORCENTAJE']) : '';
                        $vObserva    = $guardado ? (string) $guardado['OBSERVACIONES'] : '';

                        /* El tipo de cambio guardado gana sobre el valor por
                           defecto. Sin esto, reabrir una carga hecha con otro
                           tipo de cambio la recalcularía sola contra el del
                           encabezado y cambiaría importes que nadie tocó. */
                        if ($guardado && $guardado['TIPO_CAMBIO'] !== null && $guardado['TIPO_CAMBIO'] !== '') {
                            $vTipoCambio = formatoCampo($guardado['TIPO_CAMBIO']);
                        } elseif (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') {
                            $vTipoCambio = '0';
                        } else {
                            $vTipoCambio = ($valor <= 6) ? $tipoCambio : '0';
                        }
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
                                   value="<?= htmlspecialchars($vImporteUsd) ?>"
                                   onkeyup="iniciarCalculo(this)"
                                   onchange='convertirNumeros(this)'
                                   onclick='limpiarInput(this)'>
                        </td>
                        <td>
                            <input class="input-field decimales currencyInput tipoCambio"
                                   type="text"
                                   onkeyup="iniciarCalculo(this)"
                                   id="tipoCambio"
                                   value="<?= htmlspecialchars($vTipoCambio) ?>"
                                   onchange='convertirNumeros(this)'
                                   onclick='limpiarInput(this)'>
                        </td>
                        <td>
                            <input class="input-field decimales currencyInput importe skip"
                                   id="valorFobPeso"
                                   name="inputNum[]"
                                   value="<?= htmlspecialchars($vImportePeso) ?>"
                                   readonly>
                        </td>
                        <td>
                            <input class="input-field skip" value="<?= htmlspecialchars($vPorcentaje) ?>" readonly>
                        </td>
                        <td>
                            <input class="input-field skip" value="<?= htmlspecialchars($vObserva) ?>">
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
