
<?php
require_once 'Class/Alquiler.php';

$alquiler = new Alquiler();
$franquicias = $alquiler->traerFranquicias();
$sucursal = isset($_GET['sucursal']) ? $_GET['sucursal'] : '';
$vigente = isset($_GET['vigente']) ? $_GET['vigente'] : 'actual';
$contratos = $alquiler->obtenerContratos($sucursal, $vigente);

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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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

        <div class="row mb-3 align-items-end">
            <div class="col-md-4">
                <label for="sucursal" class="form-label">Sucursal</label>
                <select id="sucursal" class="form-select">
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
                <select id="contratoVigente" class="form-select">
                    <option value="">Todos</option>
                    <option value="actual" <?php echo $vigente === 'actual' ? 'selected' : ''; ?>>Actual</option>
                </select>
            </div>
            <div class="col-md-2">
                <button id="btnBuscar" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Buscar
                </button>
            </div>
        </div>
                        
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
                            <td class="vigente-cell"></td>
                            <td class="btn-center">
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>

    <script>
        
        $(document).ready(function() {
        // Manejador para los botones "Subir archivo"
        $(document).on('click', '.subir-archivo', function() {
            var tipo = $(this).data('tipo');
            var id = $(this).data('id');
            $('#contratoId').val(id);
            $('#tipoArchivo').val(tipo);
            $('#subirArchivoModalLabel').text('Subir ' + tipo.charAt(0).toUpperCase() + tipo.slice(1));
            
            // Limpiar el input de archivo y ocultar el botón de ver PDF
            $('#archivo').val('');
            $('#nombreArchivo').text('');
            $('#verPdfBtn').hide();
            
            var myModal = new bootstrap.Modal(document.getElementById('subirArchivoModal'));
            myModal.show();
        });

        function calcularVigencia() {
                var today = new Date();
                today.setHours(0, 0, 0, 0);

                $('#contratosTable tbody tr').each(function() {
                    var vigDesde = new Date($(this).find('td:eq(3)').text());
                    var vigHasta = new Date($(this).find('td:eq(4)').text());
                    
                    if (today >= vigDesde && today <= vigHasta) {
                        $(this).find('.vigente-cell').html('<i class="fas fa-check-circle text-success"></i>');
                    } else {
                        $(this).find('.vigente-cell').html('<i class="fas fa-times-circle text-danger"></i>');
                    }
                });
            }
        
        // Mostrar el nombre del archivo seleccionado
        $('#archivo').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $('#nombreArchivo').text(fileName);
        });

        $('#btnGuardarArchivo').on('click', function() {
            var formData = new FormData($('#subirArchivoForm')[0]);
            
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Subiendo...');
            
            $.ajax({
                url: 'controller/subirArchivoController.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Archivo subido exitosamente',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                        $('#verPdfBtn').show().data('archivo', response.fileName);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al subir el archivo: ' + response.message,
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error en la solicitud AJAX: ' + textStatus,
                    });
                },
                complete: function() {
                    $('#btnGuardarArchivo').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
                }
            });
        });

        // Manejador para el botón "Ver PDF" en el modal
        $('#verPdfBtn').on('click', function() {
            var archivo = $(this).data('archivo');
            if (archivo) {
                window.open('archivos/' + archivo, '_blank');
            }
        });

        // Manejador para los botones "Ver archivo" en la tabla
        $(document).on('click', '.ver-archivo', function() {
            var archivo = $(this).data('archivo');
            if (archivo) {
                window.open('archivos/' + archivo, '_blank');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo encontrar el archivo.',
                });
            }
        });
    });

    </script>
    
</body>
</html>