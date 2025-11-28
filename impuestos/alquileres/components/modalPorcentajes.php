<?php
    // Obtener datos para el modal
    require_once "Class/Alquiler.php";
    require_once "Class/Sucursal.php";

    $alquilerModal = new Alquiler();
    $sucursalModal = new Sucursal();

    $conceptosModal = $alquilerModal->traerConceptosPorcentaje();
    $localesModal = $sucursalModal->traerLocales();
?>

<!-- Modal de Gestión de Porcentajes -->
<div class="modal fade" id="modalPorcentajes" tabindex="-1" role="dialog" aria-labelledby="modalPorcentajesLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content modal-porcentajes-content">
            <div class="modal-header modal-porcentajes-header">
                <h5 class="modal-title" id="modalPorcentajesLabel">
                    <i class="bi bi-gear-fill"></i> Gestión de Porcentajes por Concepto
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body modal-porcentajes-body">
                <!-- Controles de Filtro y Agregar -->
                <div class="porcentajes-controls">
                    <div class="control-group">
                        <div class="form-group-inline">
                            <label for="conceptosModal">Concepto:</label>
                            <select name="conceptos" id="conceptosModal" class="form-control form-control">
                                <?php foreach ($conceptosModal as $value) { ?>
                                    <option value="<?= $value['ID_CA'] ?>-<?= $value['CONCEPTO'] ?>">
                                        <?= $value['CONCEPTO'] ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <button class="btn btn-primary btn-sm" onclick="filtrarPorcentajesModal()">
                                <i class="bi bi-funnel-fill"></i> Filtrar
                            </button>
                        </div>
                    </div>

                    <div class="control-group">
                        <div class="form-group-inline">
                            <label for="localesModal">Sucursal:</label>
                            <select name="locales" id="localesModal" class="form-control form-control">
                                <?php foreach ($localesModal as $value) { ?>
                                    <option value="<?= $value['NRO_SUCURSAL'] ?>">
                                        <?= $value['NRO_SUCURSAL'] ?> - <?= $value['DESC_SUCURSAL'] ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <button class="btn btn-success btn-sm" onclick="agregarPorcentajeModal()">
                                <i class="bi bi-plus-circle"></i> Agregar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Porcentajes -->
                <div class="table-responsive mt-3" id="tablaPorcentajesContainer">
                    <table class="table table-hover table-sm table-porcentajes" id="tablaPorcentajes">
                        <thead class="thead-dark">
                            <tr>
                                <th style="width: 100px;">SUCURSAL</th>
                                <th>NOMBRE</th>
                                <th style="width: 150px;">PORCENTAJE (%)</th>
                                <th style="width: 100px;">ACCIÓN</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPorcentajes">
                            <!-- Se carga dinámicamente con JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer modal-porcentajes-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos CSS para el Modal -->
<style>
    /* Modal Principal */
    .modal-porcentajes-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    .modal-porcentajes-header {
        background: #6c757d;
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }

    .modal-porcentajes-header .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-porcentajes-header .close {
        color: white;
        opacity: 0.9;
        text-shadow: none;
        font-size: 1.5rem;
        transition: opacity 0.3s ease;
    }

    .modal-porcentajes-header .close:hover {
        opacity: 1;
    }

    .modal-porcentajes-body {
        padding: 1.5rem;
        background-color: #f8f9fa;
    }

    .modal-porcentajes-footer {
        border-top: 1px solid #dee2e6;
        padding: 1rem 1.5rem;
        background-color: #fff;
        border-radius: 0 0 12px 12px;
    }

    /* Controles de Filtro */
    .porcentajes-controls {
        display: flex;
        flex-direction: column;
        gap: 15px;
        background: white;
        padding: 1.25rem;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .control-group {
        display: flex;
        align-items: center;
    }

    .form-group-inline {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        width: 100%;
    }

    .form-group-inline label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0;
        min-width: 80px;
        font-size: 0.95rem;
    }

    .form-group-inline .form-control {
        flex: 1;
        max-width: 400px;
        border-radius: 6px;
        border: 1px solid #ced4da;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .form-group-inline .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .form-group-inline .btn {
        border-radius: 6px;
        font-weight: 500;
        padding: 0.375rem 1rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .form-group-inline .btn-primary {
        background: #007bff;
        border: none;
    }

    .form-group-inline .btn-primary:hover {
        background: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
    }

    .form-group-inline .btn-success {
        background: #28a745;
        border: none;
    }

    .form-group-inline .btn-success:hover {
        background: #218838;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
    }

    /* Tabla de Porcentajes */
    #tablaPorcentajesContainer {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .table-porcentajes {
        margin-bottom: 0;
        font-size: 0.95rem;
    }

    .table-porcentajes thead th {
        background: #343a40;
        color: white;
        border: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 0.875rem;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table-porcentajes tbody tr {
        transition: all 0.2s ease;
    }

    .table-porcentajes tbody tr:hover {
        background-color: #f1f3f5;
        transform: scale(1.01);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .table-porcentajes tbody td {
        vertical-align: middle;
        padding: 0.75rem;
        border-color: #e9ecef;
    }

    .table-porcentajes tbody td input[type="number"] {
        width: 100%;
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .table-porcentajes tbody td input[type="number"]:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        outline: none;
    }

    .table-porcentajes .btn-danger {
        background: #dc3545;
        border: none;
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .table-porcentajes .btn-danger:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .porcentajes-controls {
            padding: 1rem;
        }

        .form-group-inline {
            flex-direction: column;
            align-items: stretch;
        }

        .form-group-inline label {
            min-width: auto;
        }

        .form-group-inline .form-control {
            max-width: 100%;
        }
    }

    /* Animaciones */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal.show .modal-porcentajes-content {
        animation: fadeIn 0.3s ease;
    }

    /* Loading Spinner */
    .porcentajes-loading {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 3rem;
        color: #007bff;
    }

    .porcentajes-loading i {
        font-size: 2rem;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    /* Empty State */
    .porcentajes-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }

    .porcentajes-empty i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .porcentajes-empty p {
        font-size: 1.1rem;
        margin: 0;
    }
</style>
