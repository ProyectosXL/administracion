
<?php
require_once 'Class/Alquiler.php';

$alquiler = new Alquiler();
$franquicias = $alquiler->traerFranquicias();
$sucursal = isset($_GET['sucursal']) ? $_GET['sucursal'] : '';
$vigente = isset($_GET['contratoVigente']) ? $_GET['contratoVigente'] : 'actual';
$contratos = $alquiler->obtenerContratos($sucursal, $vigente);
$contratosPendientes = $alquiler->traerFranquiciasSinContrato();
$contratosPorVencer = $alquiler->traerContratosPorVencer();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../image/icono.jpg" type="image/jpg">
    <title>Contratos Alquiler Franquicias</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="css/detalleContratos.css" class="rel">
   
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="alert alert-primary" role="alert">
            <h3 class="mb-0">
                <i class="fas fa-file-contract me-2"></i>
                Contratos Alquiler Franquicias
            </h3>
        </div>
        <form action="">
            <div class="row mb-3 align-items-end">
                <div class="col-md-4">
                    <label for="sucursal" class="form-label">Sucursal</label>
                    <select id="sucursal" name="sucursal" class="form-select">
                        <option value="">Todas las sucursales</option>
                        <?php foreach ($franquicias as $franquicia): ?>
                            <option value="<?php echo htmlspecialchars($franquicia['NRO_SUCURSAL']); ?>">
                                <?php echo htmlspecialchars($franquicia['DESC_SUCURSAL']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="contratoVigente" class="form-label">Vigencia</label>
                    <select id="contratoVigente" name="contratoVigente" class="form-select">
                        <option value="">Todos</option>
                        <option value="actual" <?php echo $vigente === 'actual' ? 'selected' : ''; ?>>Actual</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button id="btnBuscar" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Buscar
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btnContratosPendientes" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#contratosPendientesModal">
                        <i class="fas fa-exclamation-triangle me-2"></i>Contratos pendientes
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btnProntoVencimiento" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#prontoVencimientoModal">
                        <i class="fas fa-clock me-2"></i>Pronto vencimiento
                    </button>
                </div>
            </div>
        </form>
                        
        <div class="table-container">
            <table id="contratosTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Fecha Carga</th>
                        <th>Nro. Sucursal</th>
                        <th>Sucursal</th>
                        <th>Vigencia Desde</th>
                        <th>Vigencia Hasta</th>
                        <th>Vigente</th>
                        <th>Contrato Comercial</th>
                        <th>Contrato Locación</th>
                        <th>Habilitación Local</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contratos as $contrato): ?>
                        <tr>
                            <td><?= $contrato['FECHA_CARGA']->format('Y-m-d') ?></td>
                            <td><?php echo htmlspecialchars($contrato['NRO_SUCURS']); ?></td>
                            <td><?php echo htmlspecialchars($contrato['DESC_SUCURS']); ?></td>
                            <td><?= $contrato['VIG_DESDE']->format('Y-m-d') ?></td>
                            <td><?= $contrato['VIG_HASTA']->format('Y-m-d') ?></td>
                            <td class="vigente-cell" style="text-align:center"></td>
                            <td class="btn-center" >
                                <?php if (!empty($contrato['CONTRATO_COMERCIAL'])): ?>
                                    <button class="btn btn-warning btn-sm ver-archivo" data-archivo="<?php echo htmlspecialchars($contrato['CONTRATO_COMERCIAL']); ?>">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-primary btn-sm subir-archivo" data-tipo="comercial" data-id="<?php echo $contrato['ID']; ?>">
                                        <i class="fas fa-upload"></i> Subir
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td class="btn-center">
                                <?php if (!empty($contrato['CONTRATO_LOCACION'])): ?>
                                    <button class="btn btn-warning btn-sm ver-archivo" data-archivo="<?php echo htmlspecialchars($contrato['CONTRATO_LOCACION']); ?>">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-primary btn-sm subir-archivo" data-tipo="locacion" data-id="<?php echo $contrato['ID']; ?>">
                                        <i class="fas fa-upload"></i> Subir
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td class="btn-center">
                                <?php if (!empty($contrato['HABILITACION'])): ?>
                                    <button class="btn btn-warning btn-sm ver-archivo" data-archivo="<?php echo htmlspecialchars($contrato['HABILITACION']); ?>">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-primary btn-sm subir-archivo" data-tipo="habilitacion" data-id="<?php echo $contrato['ID']; ?>">
                                        <i class="fas fa-upload"></i> Subir
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para subir archivo -->
        <div class="modal fade" id="subirArchivoModal" tabindex="-1" aria-labelledby="subirArchivoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="subirArchivoModalLabel">Subir Archivo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="subirArchivoForm">
                            <input type="hidden" id="contratoId" name="contratoId">
                            <input type="hidden" id="tipoArchivo" name="tipoArchivo">
                            <div class="mb-3">
                                <label for="archivo" class="form-label">Seleccionar archivo</label>
                                <input type="file" class="form-control" id="archivo" name="archivo" accept=".pdf" required>
                            </div>
                            <div id="nombreArchivo"></div>
                        </form>
                        <button id="verPdfBtn" class="btn btn-secondary mt-2" style="display: none;">
                            <i class="fas fa-eye"></i> Ver PDF
                        </button>
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarArchivo">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Contratos Pendientes -->
        <div class="modal fade" id="contratosPendientesModal" tabindex="-1" aria-labelledby="contratosPendientesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="contratosPendientesModalLabel">Contratos Pendientes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nro. Sucursal</th>
                                    <th>Descripción Sucursal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contratosPendientes as $contrato): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($contrato['NRO_SUCURSAL']); ?></td>
                                        <td><?php echo htmlspecialchars($contrato['DESC_SUCURSAL']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="btnDescargarPendientes">
                            <i class="fas fa-download me-2"></i>Descargar
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-close me-2"></i>Cerrar
                    </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Contratos Pronto a Vencer -->
        <div class="modal fade" id="prontoVencimientoModal" tabindex="-1" aria-labelledby="prontoVencimientoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="prontoVencimientoModalLabel">Contratos Pronto a Vencer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nro. Sucursal</th>
                                    <th>Descripción Sucursal</th>
                                    <th>Vencimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contratosPorVencer as $contrato): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($contrato['NRO_SUCURS']); ?></td>
                                        <td><?php echo htmlspecialchars($contrato['DESC_SUCURS']); ?></td>
                                        <td><?= $contrato['VIG_HASTA']->format('Y-m-d') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <script src="js/detalleAlquiler.js"></script>
    <script>

       // Función para descargar los contratos pendientes en formato XLSX
        function descargarContratosPendientes() {
            // Obtener los datos de la tabla
            var data = [['Nro. Sucursal', 'Descripción Sucursal']];
            $('#contratosPendientesModal table tbody tr').each(function() {
                var row = $(this);
                data.push([
                    row.find('td:eq(0)').text(),
                    row.find('td:eq(1)').text()
                ]);
            });

            // Crear un libro de trabajo y una hoja de cálculo
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.aoa_to_sheet(data);

            // Añadir la hoja al libro
            XLSX.utils.book_append_sheet(wb, ws, "Contratos Pendientes");

            // Generar el archivo XLSX y descargarlo
            XLSX.writeFile(wb, "contratos_pendientes.xlsx");
        }

        // Agregar el evento click al botón de descarga
        $(document).ready(function() {
            $('#btnDescargarPendientes').on('click', descargarContratosPendientes);
        });
        
    </script>
    
</body>
</html>