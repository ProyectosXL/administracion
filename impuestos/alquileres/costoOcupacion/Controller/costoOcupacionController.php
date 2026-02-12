<?php

/**
 * Controlador para Costo de Ocupación
 * Maneja las peticiones AJAX y coordina la lógica de negocio
 */

header('Content-Type: application/json; charset=utf-8');

// Verificar acción
$accion = isset($_GET['accion']) ? $_GET['accion'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($accion) {
    case 'fetch':
        obtenerDatosCostoOcupacion();
        break;

    case 'obtenerRangoDefault':
        obtenerRangoDefault();
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida',
            'accion_recibida' => $accion
        ]);
        break;
}

/**
 * Obtiene el rango de fechas por defecto (últimos 12 meses completos previos)
 */
function obtenerRangoDefault()
{
    try {
        require_once __DIR__ . '/../Class/costoOcupacionService.php';

        $service = new CostoOcupacionService();
        $rango = $service->calcularRangoDefault();

        echo json_encode([
            'success' => true,
            'data' => $rango
        ]);

    } catch (Exception $e) {
        error_log("Error en obtenerRangoDefault: " . $e->getMessage());

        echo json_encode([
            'success' => false,
            'message' => 'Error al calcular rango de fechas'
        ]);
    }
}

/**
 * Obtiene los datos completos para la pantalla de Costo de Ocupación
 */
function obtenerDatosCostoOcupacion()
{
    try {
        require_once __DIR__ . '/../Class/costoOcupacionService.php';

        // Obtener parámetros
        $idSucursal = isset($_POST['id_sucursal']) ? $_POST['id_sucursal'] : '';
        $fechaDesde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : '';
        $fechaHasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : '';

        // Validar sucursal
        if (empty($idSucursal)) {
            echo json_encode([
                'success' => false,
                'message' => 'Debe seleccionar una sucursal'
            ]);
            return;
        }

        // Crear servicio y obtener datos
        $service = new CostoOcupacionService();

        // Si no vienen fechas, el servicio setea el rango por defecto
        $dataset = $service->construirDataset($idSucursal, $fechaDesde, $fechaHasta);

        // Validar que haya datos
        if (empty($dataset['filas'])) {
            echo json_encode([
                'success' => false,
                'message' => 'No se encontraron datos para el rango seleccionado',
                'meses' => $dataset['meses']
            ]);
            return;
        }

        // Calcular período anterior (YoY) usando el dataset simplificado
        $fechaDesdeReal = $dataset['fecha_desde'];
        $fechaHastaReal = $dataset['fecha_hasta'];

        $dateDesde = new DateTime($fechaDesdeReal);
        $dateHasta = new DateTime($fechaHastaReal);

        $dateDesde->modify('-12 months');
        $dateHasta->modify('-12 months');

        $fechaDesdeAnterior = $dateDesde->format('Y-m-d');
        $fechaHastaAnterior = $dateHasta->format('Y-m-d');

        // Obtener % Costo de Ocupación del período anterior
        $datasetAnterior = $service->construirDatasetSimplificado(
            $idSucursal,
            $fechaDesdeAnterior,
            $fechaHastaAnterior
        );

        $porcentajeAnterior = null;
        if (isset($datasetAnterior['porcentaje_costo_ocupacion'])) {
            $porcentajeAnterior = floatval($datasetAnterior['porcentaje_costo_ocupacion']);
        }

        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'data' => [
                'meses' => $dataset['meses'],
                'filas' => $dataset['filas'],
                'kpis' => $dataset['kpis'],
                'fecha_desde' => $dataset['fecha_desde'],
                'fecha_hasta' => $dataset['fecha_hasta'],
                'porcentaje_anterior_yoy' => $porcentajeAnterior,
                'fecha_desde_anterior' => $fechaDesdeAnterior,
                'fecha_hasta_anterior' => $fechaHastaAnterior
            ]
        ]);

    } catch (Exception $e) {
        error_log("Error en obtenerDatosCostoOcupacion: " . $e->getMessage());

        echo json_encode([
            'success' => false,
            'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
        ]);
    }
}