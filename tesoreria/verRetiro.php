<?php
session_start();

if (!isset($_SESSION['numsuc'])) {
    $_SESSION['numsuc'] = '2';
}

$nroSucurs = $_SESSION['numsuc'];

// Función para limpiar y formatear el nombre
function limpiarNombre($nombre) {
    return trim(str_replace(array("\r", "\n", "<br>", "<br/>", "<br />"), ' ', $nombre));
}

require_once 'Class/sucursal.php';
require_once 'Class/gasto.php';
$guiaRetiro = new Sucursal();
$gasto = new Gasto();

$id = $_GET['id'];
$datosGuia = $guiaRetiro->traerDatosGuiaRetiro($id, $nroSucurs);
$datosGuia = $datosGuia[0];

$remitos = $guiaRetiro->listarRemitosPorGuia($id, $nroSucurs); 
$totalBultos = 0;
foreach ($remitos as $key => $remito) {
    $totalBultos += $remito['BULTOS'];
    
}

$egresos = $gasto->listarEgresosPorGuia($id, $nroSucurs);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Entrega</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .firma-container {
            background-color: white;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #ced4da;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
                    <div class="container" style="max-width:600px;">
                        <div class="alert alert-primary d-flex align-items-center mb-4" style="font-size: 22px;" role="alert">
                            <i class="bi bi-clipboard-check alert-icon"></i>
                            <div>
                                <h4 class="alert-heading mb-0">Guia Retiros de Sucursal</h4>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <form id="entregaForm">
                                    <div class="mb-3">
                                        <label for="numeroRegistro" class="form-label">
                                            <i class="bi bi-hash"></i> Número de Registro
                                        </label>
                                        <input type="text" class="form-control" id="numeroRegistro" value="<?php echo $datosGuia['NRO_REGISTRO']; ?>" readonly>
                                    </div>

                                    <div class="mb-3">
                    <label for="entrego" class="form-label">
                        <i class="bi bi-person-fill"></i> Entregó
                    </label>
                    <input type="text" class="form-control" id="entrego" value="<?php echo htmlspecialchars($datosGuia['ENTREGO']); ?>" readonly>
                </div>


                    <div class="mb-3">
                        <label for="recibio" class="form-label">
                            <i class="bi bi-person-check-fill"></i> Recibió
                        </label>
                        <input type="text" class="form-control" id="recibio" value="<?php echo htmlspecialchars($datosGuia['RECIBIO']); ?>" readonly>
                    </div>


                
                <div class="mb-3">
                    <label for="enviaValores" class="form-label">
                        <i class="bi bi-cash-coin"></i>
                        Envía Valores
                    </label>
                    <select class="form-select" id="enviaValores" name="enviaValores" disabled>
                        <option value="SI" <?php echo ($datosGuia['ENVIA_VALORES'] == '1') ? 'selected' : ''; ?>>SI</option>
                        <option value="NO" <?php echo ($datosGuia['ENVIA_VALORES'] == '0') ? 'selected' : ''; ?>>NO</option>
                    </select>
                </div>

            
            <?php if ($datosGuia['ENVIA_VALORES'] == '1'): ?>
                <div class="mb-3" id="precintoContainer">
                    <label for="numeroPrecinto" class="form-label">
                        <i class="bi bi-lock-fill"></i>
                        <strong>Número de Precinto</strong>
                    </label>
                    <input type="text" class="form-control" id="numeroPrecinto" name="numeroPrecinto"
                        value="<?php echo htmlspecialchars($datosGuia['PRECINTO'] ?? ''); ?>" readonly>
                </div>


                <div class="mb-3 mt-2" id="egresosContainer">
                
                <div class="table-responsive">
                    <table class="table table-sm table-egresos" id="tablaEgresos">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Comprobante</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody id="bodyEgresos">
    <?php if (!empty($egresos)): ?>
        <?php foreach ($egresos as $egreso): ?>
            <tr>
                <td><?php echo htmlspecialchars($egreso['T_COMP']); ?></td>
                <td><?php echo htmlspecialchars($egreso['N_COMP']); ?></td>
                <td><?php echo $egreso['FECHA_COMP']->format("d/m/Y");; ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="3">No hay egresos asociados.</td>
        </tr>
    <?php endif; ?>
</tbody>

                        </table>
                    </div>
                </div>
                <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Remito</th>
                                    <th>Destino</th>
                                    <th>Bultos</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($remitos as $remito){ ?>
                                    <tr>
                                        <td><?php echo $remito['REMITO']; ?></td>
                                        <td><?php echo $remito['DESTINO']; ?></td>
                                        <td><?php echo $remito['BULTOS']; ?></td>
                                        <td></td>
                                    </tr>
                                <?php }; ?>
                            </tbody>                                           

                    <tfoot>
                        <tr class="total">
                            <td colspan="2" class="text-end pe-2"><strong>Total:</strong></td>
                            <td id="totalBultos" class="fw-bold"><?php echo $totalBultos; ?></td>
                            <th></th>
                        </tr>                        
                    </tfoot>
                    </table>
                    </div>


                <div class="mb-3">
                        <label for="observaciones" class="form-label">
                             <i class="bi bi-chat-left-text-fill"></i> Observaciones
                        </label>
                    <textarea class="form-control" id="observaciones" readonly><?php echo htmlspecialchars($datosGuia['OBSERVACIONES']); ?></textarea>
                    </div>

                    <div class="mb-3">
    <label for="firma" class="form-label"><i class="bi bi-pen"></i> Firma</label>
    <div class="firma-container">
        <?php if (!empty($datosGuia['FIRMA'])): ?>
            <?php 
                // Convertir la ruta absoluta en una ruta relativa para el navegador
                $urlFirma = str_replace('C:/xampp/htdocs', '', $datosGuia['FIRMA']);
            ?>
            <img src="<?php echo htmlspecialchars($urlFirma); ?>" alt="Firma" class="img-fluid">
        <?php else: ?>
            <p class="text-muted">No hay firma registrada.</p>
        <?php endif; ?>
    </div>
</div>


                    
                </form>
            </div>
        </div>
    </div>
</body>

</html>
