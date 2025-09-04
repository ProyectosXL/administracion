
<?php
session_start();
require_once 'Class/sucursal.php';
require_once 'Class/gasto.php';

$sucursal = new Sucursal();
$gasto = new Gasto();

$ultimoRegistro = $sucursal->ultimoRegistro($_SESSION['numsuc']);

if (!isset($_SESSION['numsuc'])) {
    $_SESSION['numsuc'] = '2';
}

$nroSucurs = $_SESSION['numsuc'];

// Función para limpiar y formatear el nombre
function limpiarNombre($nombre) {
    return trim(str_replace(array("\r", "\n", "<br>", "<br/>", "<br />"), ' ', $nombre));
}

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
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>

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
    <div class="container" style="max-width:600px;">
        <!-- Header con título -->
        <div class="alert alert-primary d-flex align-items-center mb-4" style="font-size: 22px;" role="alert">
            <i class="bi bi-clipboard-check alert-icon"></i>
            <div>
                <h4 class="alert-heading mb-0">Guia Retiros de Sucursal</h4>
            </div>
        </div>
        <div hidden id="numSucurs"><?= $nroSucurs ?></div>
        <!-- Card principal -->
        <div class="card">
            <div class="card-body">
                    <!-- Número de Registro -->
                    <div class="mb-3">
                        <label for="numeroRegistro" class="form-label">
                            <i class="bi bi-hash"></i>
                            Número de Registro
                        </label>
                        <div hidden id="anterior"><?= $ultimoRegistro ?></div>
                        <input type="text" class="form-control" id="numeroRegistro" readonly>
                    </div>

                    <!-- Select Entregó -->
                    <div class="mb-3">
                        <label for="entrego" class="form-label">
                            <i class="bi bi-person-fill"></i>
                            Entregó
                        </label>
                        <select class="form-select" id="entrego" name="entrego" required>
                            <option value="">Seleccione una persona</option>
                            <?php
                            try {
                                require_once 'Class/sucursal.php';
                                $data = new Sucursal();
                                $usuarios = $data->listarUsuarios();

                                if (!empty($usuarios)) {
                                    foreach ($usuarios as $v) {
                                        $nombre = limpiarNombre($v['NOMBRE_VEN']);
                                        $bloque = limpiarNombre($v['BLOQUE']);
                                        $valor = htmlspecialchars($nombre . '++' . $bloque);
                                        $nombreMostrar = htmlspecialchars($nombre);
                                        printf('<option value="%s">%s</option>', $valor, $nombreMostrar);
                                    }
                                }
                            } catch (Exception $e) {
                                error_log("Error al cargar usuarios: " . $e->getMessage());
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                    <label for="recibio" class="form-label">
                        <i class="bi bi-person-check-fill"></i>
                        Recibió
                    </label>
                    <select class="form-select" id="recibio" name="recibio" required>
                        <option value="">Seleccione una persona</option>
                        <?php
                        try {
                            $fleteros = $data->listarFleteros();
                            if (!empty($fleteros)) {
                                foreach ($fleteros as $f) {
                                    $nombre = htmlspecialchars(trim($f['NOMBRE_APELLIDO']));
                                    echo "<option value=\"{$nombre}\">{$nombre}</option>";
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Error al cargar fleteros: " . $e->getMessage());
                        }
                        ?>
                    </select>
                </div>

                <!-- Nuevo select para Envía Valores -->
                <div class="mb-3">
                    <label for="enviaValores" class="form-label">
                        <i class="bi bi-cash-coin"></i>
                        Envía Valores
                    </label>
                    <select class="form-select" id="enviaValores" name="enviaValores" required>
                        <option value="">Seleccione una opción</option>
                        <option value="SI">SI</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <!-- Campo para Número de Precinto (inicialmente oculto) -->
                <div class="mb-3" id="precintoContainer" style="display: none;">
                    <label for="numeroPrecinto" class="form-label">
                        <i class="bi bi-lock-fill"></i>
                        <strong>Número de Precinto</strong>
                    </label>
                    <input type="number" class="form-control" id="numeroPrecinto" name="numeroPrecinto">
                    <!-- Campo para Egresos (dentro del precintoContainer) -->
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

                        <!-- Tabla de egresos seleccionados -->
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
                                    <!-- Aquí se agregarán los egresos dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Agregar después del campo de observaciones y antes de la firma -->
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
                                $remitos = $data->listarRemitos($nroSucurs);
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

                    <!-- Tabla de remitos seleccionados -->
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
                                <!-- Aquí se agregarán los remitos dinámicamente -->
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

                    <!-- Observaciones -->
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">
                            <i class="bi bi-chat-left-text-fill"></i>
                            Observaciones
                        </label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>

                    <!-- Firma -->
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
                    </div>

                    <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm flex-grow-1" id="btnGuardar" onclick="guardarFormulario()">
                        <i class="bi bi-file-earmark-check me-2"></i>
                        Guardar
                    </button>
                    <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnRegistrar" onclick="registrar()">
                        <i class="bi bi-send-check me-2"></i>
                        Registrar
                    </button>
                    </div>
    
            </div>
        </div>
    </div>
</body>
</html>
    <script src="assets/jquery/jquery.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="js/registrarRetiro.js"></script>
    <!--  <script src="js/cargarFormulario.js"></script>  -->

</html>