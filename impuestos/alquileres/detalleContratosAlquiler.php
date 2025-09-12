
<?php 
require_once "Class/Alquiler.php";
$alquiler = new Alquiler();
$estado = (isset($_GET['estado'])) ? $_GET['estado'] : 0;

if($estado == 0){
    $contratos = $alquiler->traerContratoVigente();
}elseif($estado == 1){
    $contratos = $alquiler->traerContratoAnterior();
}else{
    $contratos = $alquiler->traerContratosFuturos();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
    $checked = 'checked';
}else{
    $checked = '';
}
    
$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$dataOnValue = 'ARG';
$dataOffValue = 'UY';

// Imagen de la bandera que se mostrará al lado del toggle
$imagenBandera = ($checkedValue === 'central') ? '../../assets/images/bandera_con_sol__55757_std.jpg' : '../../assets/images/UY.png';

$nombrePais = ($checkedValue === 'central') ? 'Argentina' : 'Uruguay';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Contratos de Alquiler</title>
    
    <!-- CSS Includes -->
    <?php require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php'; ?>
    
    <!-- External Libraries -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/detalleContratosAlquiler.css">

    <style>

        /* Toggle simple sin banderas - Sobreescribir estilos del CSS externo */
        .toggle-on, .toggle-off {
            font-size: 12px !important;
            font-weight: bold !important;
            color: white !important;
            text-shadow: none !important;
            line-height: 30px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            /* Sobreescribir estilos conflictivos del CSS externo */
            background-size: auto !important;
            background-image: none !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            height: auto !important;
            width: auto !important;
            min-height: 34px !important;
            min-width: 45px !important;
        }

        /* Custom toggle styles */
        .toggle.btn {
            height: 38px !important;
            min-width: 90px !important;
            border-radius: 6px !important;
            padding: 0 !important;
        }

        /* Colores específicos para cada estado */
        .toggle-on {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }
        
        .toggle-off {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
        }

        /* Bandera al lado del toggle */
        .flag-indicator {
            width: 40px;
            height: 30px;
            margin-left: 10px;
            border-radius: 4px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border: 2px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .environment-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .country-label {
            color: white;
            font-size: 14px;
            font-weight: bold;
            margin-left: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        /* Asegurar que el toggle tenga colores correctos y no sean sobreescritos */
        .toggle.off .toggle-off {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
        }
        
        .toggle:not(.off) .toggle-on {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }

        /* Forzar estilos del texto del toggle */
        .toggle .toggle-handle {
            background-color: white !important;
            border: 1px solid #ccc !important;
        }

        /* Estilos para botones de acción */
        .btn-edit {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.25rem;
            transition: all 0.15s ease-in-out;
        }

        .btn-edit:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,123,255,0.3);
        }

        .btn-edit:focus {
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }

        /* Estilos para el modal */
        .modal-header.bg-primary {
            background-color: #007bff !important;
        }

        .modal-body {
            background-color: #f8f9fa;
        }

        .form-group label {
            margin-bottom: 0.5rem;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }

        .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
        }

        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }

        /* Mejoras en la tabla */
        .contracts-table tbody tr:hover {
            background-color: rgba(0,123,255,0.05);
        }

        .contracts-table .btn-edit {
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }

        .contracts-table tbody tr:hover .btn-edit {
            opacity: 1;
        }

    </style>
</head>

<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-title">
                    <a href="http://192.168.0.13:8000/" class="home-button" title="Ir al menú principal">
                        <i class="bi bi-house-fill"></i>
                    </a>
                    <div class="title-info">
                        <h1><i class="bi bi-key"></i> Detalle Contratos de Alquiler</h1>
                        <p class="subtitle">Gestión y seguimiento de contratos</p>
                    </div>
                </div>
                
                <div class="environment-toggle">
                    <div class="environment-controls">
                        <input type="checkbox" <?= $checked ?> data-toggle="toggle" 
                               data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" 
                               class="custom-toggle" onchange="cambiarEntorno(this)" 
                               id="checkEntorno">
                        <div class="flag-indicator" style="background-image: url('<?= $imagenBandera ?>');" title="<?= $nombrePais ?>"></div>
                        <span class="country-label"><?= $nombrePais ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content-wrapper">
            <!-- Filters Section -->
            <div class="filters-section">
                <div class="section-title">
                    <i class="bi bi-funnel"></i>
                    <span>Filtros de Búsqueda</span>
                </div>
                
                <form action="#" method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="estado" class="filter-label">Estado del Contrato:</label>
                        <select name="estado" id="estado" class="filter-select">
                            <option value="0" <?= ($estado == "0") ? "selected" : "" ?>>
                                <i class="bi bi-check-circle"></i> Vigentes
                            </option>
                            <option value="1" <?= ($estado == "1") ? "selected" : "" ?>>
                                <i class="bi bi-clock-history"></i> Anteriores
                            </option>
                            <option value="2" <?= ($estado == "2") ? "selected" : "" ?>>
                                <i class="bi bi-calendar-plus"></i> Futuros
                            </option>
                        </select>
                        
                        <button type="submit" class="filter-button">
                            <i class="bi bi-search"></i>
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stats Cards -->
            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon vigente">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= count($contratos) ?></h3>
                        <p>
                            <?php 
                            if($estado == 0) echo "Contratos Vigentes";
                            elseif($estado == 1) echo "Contratos Anteriores";
                            else echo "Contratos Futuros";
                            ?>
                        </p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="contratosProximosVencer">0</h3>
                        <p>Próximos a Vencer</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="contratosVencidos">0</h3>
                        <p>Vencidos</p>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="table-section">
                <div class="table-header">
                    <h2><i class="bi bi-table"></i> Lista de Contratos</h2>
                    <div class="table-actions">
                        <button class="export-btn" onclick="exportarDatos()">
                            <i class="bi bi-download"></i>
                            Exportar
                        </button>
                        <a href="cargaContratoAlquileres.php" class="add-btn">
                            <i class="bi bi-plus-lg"></i>
                            Nuevo Contrato
                        </a>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="contracts-table" id="tablaAlquileres">
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash"></i> N° Sucursal</th>
                                <th><i class="bi bi-building"></i> Sucursal</th>
                                <th><i class="bi bi-calendar-event"></i> Desde</th>
                                <th><i class="bi bi-calendar-x"></i> Hasta</th>
                                <th><i class="bi bi-key-fill"></i> Valor Llave</th>
                                <th><i class="bi bi-percent"></i> Comisiones</th>
                                <th><i class="bi bi-rocket"></i> FPC Lanzamiento</th>
                                <th><i class="bi bi-calendar3"></i> Meses</th>
                                <th><i class="bi bi-info-circle"></i> Estado</th>
                                <th><i class="bi bi-gear-fill"></i> Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $contratosVencidos = 0;
                            $contratosProximosVencer = 0;
                            
                            foreach ($contratos as $key => $contrato) {
                                $vigDesde = new DateTime($contrato['VIG_DESDE']->format("Y-m-d"));
                                $vigHasta = new DateTime($contrato['VIG_HASTA']->format("Y-m-d"));
                                $hoy = new DateTime(date("Y-m-d"));
                                
                                $diferenciaDeDias = $vigHasta->diff($vigDesde)->days;
                                $mesesDiferencia = round(($diferenciaDeDias / 365) * 12);
                                if($mesesDiferencia == 0) $mesesDiferencia = 1;
                                
                                // Calcular estado del contrato
                                $estadoContrato = '';
                                $claseEstado = '';
                                $diasRestantes = 0;
                                
                                if ($hoy > $vigHasta) {
                                    $diasVencido = $vigHasta->diff($hoy)->days;
                                    $estadoContrato = "Vencido hace {$diasVencido} días";
                                    $claseEstado = 'status-expired';
                                    $contratosVencidos++;
                                } elseif ($hoy < $vigDesde) {
                                    $diasFuturos = $hoy->diff($vigDesde)->days;
                                    $estadoContrato = "Inicia en {$diasFuturos} días";
                                    $claseEstado = 'status-future';
                                } else {
                                    $diasRestantes = $hoy->diff($vigHasta)->days;
                                    if($diasRestantes <= 90){
                                        $estadoContrato = "Vence en {$diasRestantes} días";
                                        $claseEstado = 'status-warning';
                                        $contratosProximosVencer++;
                                    } else {
                                        $estadoContrato = "Vigente";
                                        $claseEstado = 'status-active';
                                    }
                                }
                            ?>
                            <tr class="contract-row" data-estado="<?= $claseEstado ?>">
                                <td class="text-center font-weight-bold"><?= $contrato['NRO_SUCURS'] ?></td>
                                <td class="sucursal-name"><?= $contrato['DESC_SUCURS'] ?></td>
                                <td class="text-center date-cell"><?= $vigDesde->format("d/m/Y") ?></td>
                                <td class="text-center date-cell"><?= $vigHasta->format("d/m/Y") ?></td>
                                <td class="text-right amount-cell">$<?= number_format($contrato['IMPORTE'], 0, ',', '.') ?></td>
                                <td class="text-right amount-cell">$<?= number_format($contrato['IMPORTE_2'], 0, ',', '.') ?></td>
                                <td class="text-right amount-cell">$<?= number_format($contrato['IMPORTE_3'], 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <span class="duration-badge"><?= (int)$mesesDiferencia ?> meses</span>
                                </td>
                                <td class="text-center">
                                    <span class="status-badge <?= $claseEstado ?>">
                                        <?php if($claseEstado == 'status-expired'): ?>
                                            <i class="bi bi-x-circle-fill"></i>
                                        <?php elseif($claseEstado == 'status-warning'): ?>
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                        <?php elseif($claseEstado == 'status-future'): ?>
                                            <i class="bi bi-calendar-plus"></i>
                                        <?php else: ?>
                                            <i class="bi bi-check-circle-fill"></i>
                                        <?php endif; ?>
                                        <?= $estadoContrato ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-primary btn-edit" 
                                            onclick="editarContrato(<?= $contrato['ID'] ?>, '<?= $contrato['NRO_SUCURS'] ?>', '<?= addslashes($contrato['DESC_SUCURS']) ?>', '<?= $vigDesde->format('Y-m-d') ?>', '<?= $vigHasta->format('Y-m-d') ?>', <?= $contrato['IMPORTE'] ?>, <?= $contrato['IMPORTE_2'] ?>, <?= $contrato['IMPORTE_3'] ?>)"
                                            title="Editar contrato">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar contrato -->
    <div class="modal fade" id="editarContratoModal" tabindex="-1" role="dialog" aria-labelledby="editarContratoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editarContratoModalLabel">
                        <i class="bi bi-pencil-square"></i> Editar Contrato de Alquiler
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formEditarContrato">
                        <input type="hidden" id="editContratoId" name="contratoId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editNroSucursal" class="font-weight-bold">
                                        <i class="bi bi-hash text-primary"></i> N° Sucursal:
                                    </label>
                                    <input type="text" class="form-control" id="editNroSucursal" name="nroSucursal" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editDescSucursal" class="font-weight-bold">
                                        <i class="bi bi-building text-primary"></i> Descripción Sucursal:
                                    </label>
                                    <input type="text" class="form-control" id="editDescSucursal" name="descSucursal" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editVigDesde" class="font-weight-bold">
                                        <i class="bi bi-calendar-event text-success"></i> Vigencia Desde:
                                    </label>
                                    <input type="date" class="form-control" id="editVigDesde" name="vigDesde" required>
                                    <small class="form-text text-muted">Fecha de inicio del contrato</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editVigHasta" class="font-weight-bold">
                                        <i class="bi bi-calendar-x text-danger"></i> Vigencia Hasta:
                                    </label>
                                    <input type="date" class="form-control" id="editVigHasta" name="vigHasta" required>
                                    <small class="form-text text-muted">Fecha de finalización del contrato</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="editValorLlave" class="font-weight-bold">
                                        <i class="bi bi-key-fill text-warning"></i> Valor Llave:
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" class="form-control" id="editValorLlave" name="valorLlave" 
                                               min="0" step="0.01" placeholder="0">
                                    </div>
                                    <small class="form-text text-muted">Opcional - Dejar vacío para 0</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="editComisiones" class="font-weight-bold">
                                        <i class="bi bi-percent text-info"></i> Comisiones:
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" class="form-control" id="editComisiones" name="comisiones" 
                                               min="0" step="0.01" placeholder="0">
                                    </div>
                                    <small class="form-text text-muted">Opcional - Dejar vacío para 0</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="editLanzamiento" class="font-weight-bold">
                                        <i class="bi bi-rocket text-success"></i> FPC Lanzamiento:
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" class="form-control" id="editLanzamiento" name="lanzamiento" 
                                               min="0" step="0.01" placeholder="0">
                                    </div>
                                    <small class="form-text text-muted">Opcional - Dejar vacío para 0</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Información:</strong> Solo las fechas son obligatorias. Los importes son opcionales y se asignarán como 0 si se dejan vacíos.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarEdicion">
                        <i class="bi bi-check-lg"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Actualizar contadores en las cards
            document.getElementById('contratosVencidos').textContent = <?= $contratosVencidos ?>;
            document.getElementById('contratosProximosVencer').textContent = <?= $contratosProximosVencer ?>;
            
            // Inicializar DataTable
            $('#tablaAlquileres').DataTable({
                "responsive": true,
                "lengthChange": true,
                "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "Todos"]],
                "pageLength": 25,
                "language": {
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "No hay registros disponibles",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "search": "Buscar:",
                    "searchPlaceholder": "Escriba para buscar...",
                    "paginate": {
                        "next": "Siguiente",
                        "previous": "Anterior",
                        "first": "Primero",
                        "last": "Último"
                    },
                    "emptyTable": "No hay datos disponibles en la tabla",
                    "zeroRecords": "No se encontraron registros coincidentes"
                },
                "order": [[2, "desc"]], // Ordenar por fecha desde (más recientes primero)
                "columnDefs": [
                    {
                        "targets": [0, 2, 3, 7, 8, 9], // Incluir la columna de acciones
                        "className": "text-center"
                    },
                    {
                        "targets": [4, 5, 6],
                        "className": "text-right"
                    },
                    {
                        "targets": [9], // Columna de acciones
                        "orderable": false, // No permitir ordenar por acciones
                        "searchable": false // No incluir en la búsqueda
                    }
                ],
                "drawCallback": function(settings) {
                    // Aplicar tooltips después de cada redibujado
                    $('[data-toggle="tooltip"]').tooltip();
                }
            });

            // Configurar toggle
            setTimeout(() => {
                const toggle = document.querySelector(".toggle");
                if (toggle) {
                    toggle.style.width = "90px";
                    toggle.style.height = "38px";
                }
                
                // Asegurar que el toggle muestre texto blanco y centrado
                const toggleOn = document.querySelector(".toggle-on");
                const toggleOff = document.querySelector(".toggle-off");
                
                if (toggleOn) {
                    // Limpiar estilos conflictivos del CSS externo
                    toggleOn.style.backgroundImage = "none";
                    toggleOn.style.backgroundSize = "auto";
                    toggleOn.style.width = "auto";
                    toggleOn.style.height = "auto";
                    toggleOn.style.minWidth = "45px";
                    toggleOn.style.minHeight = "34px";
                    
                    // Aplicar estilos correctos
                    toggleOn.style.fontSize = "12px";
                    toggleOn.style.fontWeight = "bold";
                    toggleOn.style.color = "white";
                    toggleOn.style.display = "flex";
                    toggleOn.style.alignItems = "center";
                    toggleOn.style.justifyContent = "center";
                    toggleOn.style.textShadow = "none";
                }
                if (toggleOff) {
                    // Limpiar estilos conflictivos del CSS externo
                    toggleOff.style.backgroundImage = "none";
                    toggleOff.style.backgroundSize = "auto";
                    toggleOff.style.width = "auto";
                    toggleOff.style.height = "auto";
                    toggleOff.style.minWidth = "45px";
                    toggleOff.style.minHeight = "34px";
                    
                    // Aplicar estilos correctos
                    toggleOff.style.fontSize = "12px";
                    toggleOff.style.fontWeight = "bold";
                    toggleOff.style.color = "white";
                    toggleOff.style.display = "flex";
                    toggleOff.style.alignItems = "center";
                    toggleOff.style.justifyContent = "center";
                    toggleOff.style.textShadow = "none";
                }
            }, 100);

            // Inicializar tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });

        const cambiarEntorno = (toggle) => {
            // Determinar el entorno basado en si el toggle está marcado
            // checked = true significa Argentina (central), false significa Uruguay (uy)
            const entorno = toggle.checked ? 0 : 1;
            
            console.log('Toggle checked:', toggle.checked, 'Entorno:', entorno);
            
            Swal.fire({
                title: 'Cambiando entorno',
                text: 'Por favor espere...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "Controller/cambiarEntorno.php",
                method: "POST",
                data: { entorno: entorno },
                dataType: 'json',
                success: function(data) {
                    console.log('Respuesta cambio entorno:', data);
                    if (data.success) {
                        setTimeout(() => {
                            location.reload();
                        }, 100);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cambiar entorno',
                            text: data.message || 'Error desconocido',
                            confirmButtonText: "Entendido",
                            confirmButtonColor: "#e74c3c"
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al cambiar entorno:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al cambiar entorno',
                        text: 'No se pudo cambiar el entorno. Intente nuevamente.',
                        confirmButtonText: "Entendido",
                        confirmButtonColor: "#e74c3c"
                    });
                }
            });
        };

        const exportarDatos = () => {
            Swal.fire({
                icon: 'info',
                title: 'Exportar datos',
                text: 'Funcionalidad de exportación en desarrollo',
                confirmButtonText: 'Entendido'
            });
        };

        // Función para abrir el modal de edición
        const editarContrato = (id, nroSucursal, descSucursal, vigDesde, vigHasta, valorLlave, comisiones, lanzamiento) => {
            // Llenar los campos del modal
            document.getElementById('editContratoId').value = id;
            document.getElementById('editNroSucursal').value = nroSucursal;
            document.getElementById('editDescSucursal').value = descSucursal;
            document.getElementById('editVigDesde').value = vigDesde;
            document.getElementById('editVigHasta').value = vigHasta;
            
            // Para los importes, mostrar vacío si el valor es 0
            document.getElementById('editValorLlave').value = valorLlave == 0 ? '' : valorLlave;
            document.getElementById('editComisiones').value = comisiones == 0 ? '' : comisiones;
            document.getElementById('editLanzamiento').value = lanzamiento == 0 ? '' : lanzamiento;
            
            // Mostrar el modal
            $('#editarContratoModal').modal('show');
        };

        // Función para validar las fechas
        const validarFechas = () => {
            const vigDesde = new Date(document.getElementById('editVigDesde').value);
            const vigHasta = new Date(document.getElementById('editVigHasta').value);
            
            if (vigDesde >= vigHasta) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en las fechas',
                    text: 'La fecha "Hasta" debe ser posterior a la fecha "Desde"',
                    confirmButtonText: 'Entendido'
                });
                return false;
            }
            
            return true;
        };

        // Función para validar los importes
        const validarImportes = () => {
            const valorLlaveInput = document.getElementById('editValorLlave');
            const comisionesInput = document.getElementById('editComisiones');
            const lanzamientoInput = document.getElementById('editLanzamiento');
            
            // Convertir valores vacíos a 0
            const valorLlave = valorLlaveInput.value === '' ? 0 : parseFloat(valorLlaveInput.value);
            const comisiones = comisionesInput.value === '' ? 0 : parseFloat(comisionesInput.value);
            const lanzamiento = lanzamientoInput.value === '' ? 0 : parseFloat(lanzamientoInput.value);
            
            // Actualizar los valores en los inputs (convertir vacíos a 0 para el envío)
            if (valorLlaveInput.value === '') valorLlaveInput.value = '0';
            if (comisionesInput.value === '') comisionesInput.value = '0';
            if (lanzamientoInput.value === '') lanzamientoInput.value = '0';
            
            if (valorLlave < 0 || comisiones < 0 || lanzamiento < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en los importes',
                    text: 'Los importes no pueden ser negativos',
                    confirmButtonText: 'Entendido'
                });
                return false;
            }
            
            return true;
        };

        // Función para guardar los cambios
        const guardarCambiosContrato = () => {
            // Validaciones
            if (!validarFechas() || !validarImportes()) {
                return;
            }
            
            // Recopilar datos del formulario
            const formData = new FormData(document.getElementById('formEditarContrato'));
            
            // Mostrar loading
            Swal.fire({
                title: 'Guardando cambios',
                text: 'Por favor espere...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar datos al controlador
            $.ajax({
                url: 'Controller/actualizarContrato.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: response.message || 'Contrato actualizado correctamente',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#28a745'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Cerrar modal y recargar página
                                $('#editarContratoModal').modal('hide');
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al actualizar',
                            text: response.message || 'No se pudo actualizar el contrato',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor. Intente nuevamente.',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        };

        // Event listener para el botón guardar
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('btnGuardarEdicion').addEventListener('click', guardarCambiosContrato);
        });
    </script>
</body>
</html>
