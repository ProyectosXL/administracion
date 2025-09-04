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
require_once 'Class/gasto.php';
$guiaRetiro = new Sucursal();
$gasto = new Gasto();


if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: ID no válido o no especificado.");
}


$id = $_GET['id'];


$datosGuia = $guiaRetiro->traerDatosGuiaRetiro($id, $nroSucurs);

if (empty($datosGuia)) {
    die("Error: No se encontraron datos para el ID especificado.");
}

$datosGuia = $datosGuia[0];


$remitosCargados = $guiaRetiro->listarRemitosPorGuia($id, $nroSucurs);
$egresosCargados = $gasto->listarEgresosPorGuia($id, $nroSucurs);
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        body {
            margin: 20px;
        }
        .container {
            max-width: 600px;
        }
        
        .firma-container {
            background-color: white;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #ced4da;
            margin-bottom: 10px;
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            min-height: 38px !important;
            height: 38px !important;
            padding: 0.375rem 0.75rem !important;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.375rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding: 0 !important;
            line-height: 1.5 !important;
        }

        .select2-container--bootstrap-5 .select2-selection__arrow {
            height: 36px !important;
        }

        /* Ajustar el dropdown */
        .select2-results__option {
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
        }

        /* Ajustes responsivos */
        @media (max-width: 768px) {
            .select2-container--bootstrap-5 .select2-selection--single {
                min-height: 35px !important;
                height: 35px !important;
                font-size: 0.875rem;
            }
        }

</style>
</head>
<body>


    <div class="container">
        <div class="alert alert-primary d-flex align-items-center mb-4">
            <i class="bi bi-pencil-square me-2"></i>
            <h4 class="mb-0">Editar Guía de Retiros</h4>
        </div>
           
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

            <div hidden id="numSucurs"><?= $nroSucurs ?></div>
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
                <select class="form-select" id="entrego" name="entrego" required>
                    <option value="">Seleccione una persona</option>
            <?php
        
        $usuarios = $guiaRetiro->listarUsuarios($nroSucurs);
        
        foreach ($usuarios as $v) {
            
            $nombre = limpiarNombre($v['NOMBRE_VEN']);
            $bloque = limpiarNombre($v['BLOQUE']);
            $valor = $nombre . '++' . $bloque;

            
            $selected = ($nombre === $datosGuia['ENTREGO']) ? 'selected' : '';

            
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
            <input type="text" class="form-control" id="numeroPrecinto" name="numeroPrecinto" value="<?php echo htmlspecialchars($datosGuia['PRECINTO'] ?? ''); ?>">
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
                    $egresos = $gasto->listarEgresosEfectivo($nroSucurs);
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
                <?php foreach ($egresosCargados as $egreso): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($egreso['T_COMP']); ?></td>
                        <td><?php echo htmlspecialchars($egreso['N_COMP']); ?></td>
                        <td><?php echo htmlspecialchars($egreso['FECHA_COMP']->format('d/m/Y')); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm btn-quitar" onclick="eliminarEgreso('<?php echo htmlspecialchars($egreso['N_COMP']); ?>')">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
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
                                'fecha' => $remito['FECHA'],
                                't_comp' => $remito['T_COMP'],
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
                    <?php foreach ($remitosCargados as $remito): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($remito['REMITO']); ?></td>
                            <td><?php echo htmlspecialchars($remito['DESTINO']); ?></td>
                            <td><input type="number" class="form-control form-control-sm input-bultos" value="<?= $remito['BULTOS'] ?>" min="1"></td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarRemito('<?php echo htmlspecialchars($remito['REMITO']); ?>')">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </td>
                            <td hidden><?= $remito['FECHA'] ?></td>
                        </tr>
                    <?php endforeach; ?>
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
                <button type="button" class="btn btn-primary"  onclick="registrar()">
                    <i class="bi bi-save2-fill"></i> Actualizar Registro
                </button>
            </div>

            
           
            </div>


</body>

</html>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="js/editarRetiro.js"></script>

</html>
