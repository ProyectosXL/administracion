
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
$dataOnValue = ($checkedValue === 'uy') ? 'UY' : 'ARG';
$dataOffValue = ($checkedValue === 'uy') ? 'ARG' : 'UY';
$imageOn = ($checkedValue === 'central') ? '../../assets/images/bandera_con_sol__55757_std.jpg' : '../../assets/images/UY.png';
$imageOff = ($checkedValue === 'central') ? '../../assets/images/UY.png' : '../../assets/images/bandera_con_sol__55757_std.jpg';
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

        .toggle-on, .toggle-off {
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            height: 30px;
            width: 30px;
            border-radius: 4px;
        }

        .toggle-on {
            background-image: url('<?= $imageOn ?>');
        }

        .toggle-off {
            background-image: url('<?= $imageOff ?>');
        }

        /* Custom toggle styles */
        .toggle.btn {
            height: 38px !important;
            min-width: 80px !important;
            border-radius: 6px !important;
        }

        .toggle-on, .toggle-off {
            font-size: 0 !important;
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
                    <input type="checkbox" <?= $checked ?> data-toggle="toggle" 
                           data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" 
                           class="custom-toggle" onchange="cambiarEntorno(this)" 
                           id="checkEntorno">
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
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
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
                        "targets": [0, 2, 3, 7, 8],
                        "className": "text-center"
                    },
                    {
                        "targets": [4, 5, 6],
                        "className": "text-right"
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
                const toggleOn = document.querySelector(".toggle-on");
                const toggleOff = document.querySelector(".toggle-off");
                
                if (toggle) toggle.style.width = "80px";
                if (toggleOn) toggleOn.style.fontSize = "0";
                if (toggleOff) toggleOff.style.fontSize = "0";
            }, 100);

            // Inicializar tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });

        const cambiarEntorno = (toggle) => {
            const entorno = toggle.getAttribute("data-off") === "ARG" ? 0 : 1;
            
            Swal.fire({
                title: 'Cambiando entorno',
                text: 'Por favor espere...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "Controller/cambiarEntorno.php",
                method: "POST",
                data: { entorno: entorno },
                success: function(data) {
                    location.reload();
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cambiar el entorno'
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
    </script>
</body>
</html>
