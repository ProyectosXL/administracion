<?php
session_start();


if (!isset($_SESSION['numsuc'])) {
    $_SESSION['numsuc'] = '2';
}


$nroSucurs = $_SESSION['numsuc'];


function limpiarNombre($nombre) {
    return trim(str_replace(array("\r", "\n", "<br>", "<br/>", "<br />"), ' ', $nombre));
}


require_once 'Class/sucursal.php';
$guiaRetiro = new Sucursal();


if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: ID no válido o no especificado.");
}


$id = $_GET['id'];


$datosGuia = $guiaRetiro->traerDatosGuiaRetiro($id);

if (empty($datosGuia)) {
    die("Error: No se encontraron datos para el ID especificado.");
}

$datosGuia = $datosGuia[0];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Guía de Retiros</title>
    <!-- Incluir Bootstrap y estilos adicionales -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            margin: 20px;
        }
        .container {
            max-width: 600px;
        }
    </style>

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


    <div class="container">
        <div class="alert alert-primary d-flex align-items-center mb-4">
            <i class="bi bi-pencil-square me-2"></i>
            <h4 class="mb-0">Editar Guía de Retiros</h4>
        </div>
        
        <form action="actualizarGuia.php" method="POST">
           
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

           
            <div class="mb-3">
                <label for="numeroRegistro" class="form-label">
                    <i class="bi bi-hash"></i> Número de Registro
                </label>
                <input type="text" class="form-control" value="<?php echo $datosGuia['NRO_REGISTRO']; ?>" readonly>
            </div>

            
            <div class="mb-3">
                <label for="entrego" class="form-label">
                    <i class="bi bi-person-fill"></i> Entregó
                </label>
                <select class="form-select" id="entrego" name="entrego" required>
                    <option value="">Seleccione una persona</option>
            <?php
        
        $usuarios = $guiaRetiro->listarUsuarios($nroSucurs);
        
        foreach ($usuarios as $v) {
            
            $nombre = limpiarNombre($v['NOMBRE_VEN']);
            $bloque = limpiarNombre($v['BLOQUE']);
            $valor = $nombre . '++' . $bloque;

            
            $selected = ($valor === $datosGuia['ENTREGO']) ? 'selected' : '';

            
            printf(
                '<option value="%s" %s>%s</option>',
                htmlspecialchars($valor),
                $selected,
                htmlspecialchars($nombre)
            );
        }
        ?>
            </select>
        </div>


            
            <div class="mb-3">
                <label for="recibio" class="form-label">
                    <i class="bi bi-person-check-fill"></i> Recibió
                </label>
                <select class="form-select" id="recibio" name="recibio" required>
                    <option value="">Seleccione una persona</option>
                    <?php
                    
                    $fleteros = $guiaRetiro->listarFleteros();
                    foreach ($fleteros as $fletero) {
                        $nombre = $fletero['NOMBRE_APELLIDO'];
                        $selected = ($nombre === $datosGuia['RECIBIO']) ? 'selected' : '';
                        echo "<option value=\"{$nombre}\" {$selected}>{$nombre}</option>";
                    }
                    ?>
                </select>
            </div>

          
            <div class="mb-3">
                <label for="enviaValores" class="form-label">
                    <i class="bi bi-cash-coin"></i> Envía Valores
                </label>
                <select class="form-select" id="enviaValores" name="enviaValores" required>
                    <option value="SI" <?php echo ($datosGuia['ENVIA_VALORES'] == '1') ? 'selected' : ''; ?>>SI</option>
                    <option value="NO" <?php echo ($datosGuia['ENVIA_VALORES'] == '0') ? 'selected' : ''; ?>>NO</option>
                </select>
            </div>


        <div class="mb-3" id="precintoContainer" style="display: <?php echo ($datosGuia['ENVIA_VALORES'] == '1') ? 'block' : 'none'; ?>;">
            <label for="numeroPrecinto" class="form-label">
                <i class="bi bi-lock-fill"></i>
                <strong>Número de Precinto</strong>
            </label>
            <input type="text" class="form-control" id="numeroPrecinto" name="numeroPrecinto" value="<?php echo htmlspecialchars($datosGuia['NUMERO_PRECINTO'] ?? ''); ?>">
        </div>


    <div class="mb-3 mt-2" id="egresosContainer" style="display: <?php echo ($datosGuia['ENVIA_VALORES'] == '1') ? 'block' : 'none'; ?>;">
        <label for="selectEgresos" class="form-label">
            <i class="bi bi-cash"></i> Seleccionar Egresos
        </label>
        <div class="d-flex gap-2 mb-2">
            <select class="form-select" id="selectEgresos">
                <option value="">Seleccione un egreso</option>
                <?php
                try {
                    $egresos = $guiaRetiro->listarEgresosEfectivo($nroSucurs);
                    foreach ($egresos as $egreso) {
                        $valor = json_encode([
                            'comprobante' => $egreso['N_COMP'],
                            'tipo' => $egreso['COD_COMP'],
                            'fecha' => $egreso['FECHA']
                        ]);
                        echo '<option value=\'' . htmlspecialchars($valor) . '\'>' . 
                            htmlspecialchars($egreso['COD_COMP'] . ' - ' . $egreso['N_COMP'] . ' (' . $egreso['FECHA'] . ')') . 
                            '</option>';
                    }
                } catch (Exception $e) {
                    error_log("Error al cargar egresos: " . $e->getMessage());
                }
                ?>
            </select>
            <button type="button" class="btn btn-primary btn-sm" id="btnAgregarEgreso">
                <i class="bi bi-plus-lg"></i>
            </button>
    </div>

    
    <div class="table-responsive">
        <table class="table table-sm table-egresos" id="tablaEgresos">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Comprobante</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="bodyEgresos">
                
            </tbody>
        </table>
    </div>
    </div>

<script>
    document.getElementById('enviaValores').addEventListener('change', function() {
        const isEnviaValoresYes = this.value === 'SI';
        document.getElementById('precintoContainer').style.display = isEnviaValoresYes ? 'block' : 'none';
        document.getElementById('egresosContainer').style.display = isEnviaValoresYes ? 'block' : 'none';
        document.getElementById('remitosContainer').style.display = 'block';
    });
</script>

            
        <div class="mb-3">
            <label for="selectRemitos" class="form-label">
                <i class="bi bi-file-earmark-text"></i>
                Seleccionar Remitos
            </label>
            <div class="d-flex gap-2 mb-2">
                <select class="form-select" id="selectRemitos">
                    <option value="">Seleccione un remito</option>
                    <?php
                    try {
                        $remitos = $guiaRetiro->listarRemitos($nroSucurs);
                        foreach ($remitos as $remito) {
                            $valor = json_encode([
                                'remito' => $remito['REMITO'],
                                'destino' => $remito['DESTINO'],
                                'fecha' => $remito['FECHA']
                            ]);
                            echo '<option value=\'' . htmlspecialchars($valor) . '\'>' . 
                                htmlspecialchars($remito['REMITO'] . ' - ' . $remito['DESTINO'] . ' (' . $remito['FECHA'] . ')') . 
                                '</option>';
                        }
                    } catch (Exception $e) {
                        error_log("Error al cargar remitos: " . $e->getMessage());
                    }
                    ?>
                </select>
                <button type="button" class="btn btn-primary btn-sm" id="btnAgregarRemito">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>

        
        <div class="table-responsive">
            <table class="table table-sm table-remitos" id="tablaRemitos">
                <thead>
                    <tr>
                        <th>Remito</th>
                        <th>Destino</th>
                        <th>Bultos</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="bodyRemitos">
                    
                </tbody>
                <tfoot>
                    <tr class="total">
                        <td colspan="2" class="text-end pe-2"><strong>Total:</strong></td>
                        <td id="totalBultos" class="fw-bold">0</td>
                        <td></td>
                    </tr>
                </tfoot>
                </table>
            </div>
        </div>

<?php if (isset($datosGuia['ENVIA_VALORES']) && $datosGuia['ENVIA_VALORES'] == '1'): ?>
    
    <div class="mb-3 mt-2" id="egresosContainer">
        <label for="selectEgresos" class="form-label">
            <i class="bi bi-cash"></i>
            Seleccionar Egresos
        </label>
        <div class="d-flex gap-2 mb-2">
            <select class="form-select" id="selectEgresos">
                <option value="">Seleccione un egreso</option>
                <?php
                try {
                    $egresos = $guiaRetiro->listarEgresosEfectivo($nroSucurs);
                    foreach ($egresos as $egreso) {
                        $valor = json_encode([
                            'comprobante' => $egreso['N_COMP'],
                            'tipo' => $egreso['COD_COMP'],
                            'fecha' => $egreso['FECHA']
                        ]);
                        echo '<option value=\'' . htmlspecialchars($valor) . '\'>' . 
                            htmlspecialchars($egreso['COD_COMP'] . ' - ' . $egreso['N_COMP'] . ' (' . $egreso['FECHA'] . ')') . 
                            '</option>';
                    }
                } catch (Exception $e) {
                    error_log("Error al cargar egresos: " . $e->getMessage());
                }
                ?>
            </select>
            <button type="button" class="btn btn-primary btn-sm" id="btnAgregarEgreso">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>

        
        <div class="table-responsive">
            <table class="table table-sm table-egresos" id="tablaEgresos">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Comprobante</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="bodyEgresos">
                    
                </tbody>
            </table>
            </div>
        </div>
<?php endif; ?>

            
            <div class="mb-3">
                <label for="observaciones" class="form-label">
                    <i class="bi bi-chat-left-text-fill"></i> Observaciones
                </label>
                <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($datosGuia['OBSERVACIONES']); ?></textarea>
            </div>

            
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-pen-fill"></i>
                          Firma
                </label>
                   <div class="firma-container">
                        <canvas id="signature-pad"></canvas>
                    </div>
                       <button type="button" id="clear" class="btn btn-secondary btn-sm mt-2">
                           <i class="bi bi-eraser-fill"></i>
                           Limpiar Firma
                    </button>
                    <input type="hidden" id="firma" name="firma">

            </div>

            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save2-fill"></i> Actualizar Registro
                </button>
            </div>

            
                </form>
            </div>


</body>

</html>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="js/editarRetiro.js"></script>

</html>
