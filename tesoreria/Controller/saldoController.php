<?php
// Configurar el manejo de errores para evitar que aparezcan en la respuesta JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../Class/saldo.php';

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

// Función para enviar respuesta JSON limpia
function enviarRespuestaJSON($data) {
    header('Content-Type: application/json');
    ob_clean(); // Limpiar cualquier salida previa
    echo json_encode($data);
    exit;
}

switch ($accion) {
    case 'obtenerMovimientos':
        obtenerMovimientos();
        break;
    case 'obtenerFotosEgreso':
        obtenerFotosEgreso();
        break;
    case 'verificarEstadoEgreso':
        verificarEstadoEgreso();
        break;
    case 'obtenerEstadisticas':
        obtenerEstadisticas();
        break;
    case 'actualizarControl':
        actualizarControl();
        break;
    case 'actualizarControlMasivo':
        actualizarControlMasivo();
        break;
    case 'diagnosticar':
        diagnosticar();
        break;
    case 'regenerarControl':
        regenerarControl();
        break;
    case 'repararTabla':
        repararTabla();
        break;
    case 'diagnosticarUltimaFecha':
        diagnosticarUltimaFecha();
        break;
    default:
        enviarRespuestaJSON(['error' => 'Acción no reconocida']);
        break;
}

function obtenerMovimientos() {
    try {
        if (!isset($_GET['desde']) || !isset($_GET['hasta'])) {
            enviarRespuestaJSON(['error' => 'Faltan parámetros de fecha']);
            return;
        }

        $saldo = new Saldo();
        $desde = $_GET['desde'];
        $hasta = $_GET['hasta'];

        $movimientos = $saldo->obtenerMovimientosCaja($desde, $hasta);
        $saldoActual = $saldo->obtenerSaldoActual($hasta);
        $estadisticas = $saldo->obtenerEstadisticasMovimientos($desde, $hasta);
        $ultimaFechaControlada = $saldo->obtenerUltimaFechaControlada($desde, $hasta);

        // Log para debug
        error_log("Controller obtenerMovimientos - ultimaFechaControlada: " . ($ultimaFechaControlada ? $ultimaFechaControlada->format('Y-m-d') : 'NULL'));

        // Formatear los movimientos para JSON
        $movimientosFormateados = [];
        foreach ($movimientos as $mov) {
            $movFormateado = [
                'FECHA' => $mov['FECHA']->format('Y-m-d'),
                'FECHA_DISPLAY' => $mov['FECHA']->format('d/m/Y'),
                'COD_COMP' => $mov['COD_COMP'],
                'N_COMP' => $mov['N_COMP'],
                'TIPO' => $mov['TIPO'],
                'LEYENDA' => $mov['LEYENDA'],
                'MONTO' => number_format($mov['MONTO'], 2, '.', ''),
                'DEBE' => number_format($mov['DEBE'], 2, '.', ''),
                'HABER' => number_format($mov['HABER'], 2, '.', ''),
                'SALDO' => number_format($mov['SALDO'], 2, '.', ''),
                'MONTO_DISPLAY' => number_format($mov['MONTO'], 0, ',', '.'),
                'DEBE_DISPLAY' => number_format($mov['DEBE'], 0, ',', '.'),
                'HABER_DISPLAY' => number_format($mov['HABER'], 0, ',', '.'),
                'SALDO_DISPLAY' => number_format($mov['SALDO'], 0, ',', '.'),
                // Incluir información de fotos
                'TIENE_FOTOS' => isset($mov['TIENE_FOTOS']) ? $mov['TIENE_FOTOS'] : false,
                'ESTA_GUARDADO' => isset($mov['ESTA_GUARDADO']) ? $mov['ESTA_GUARDADO'] : false,
                'COD_CTA_CONTRAPARTIDA' => isset($mov['COD_CTA_CONTRAPARTIDA']) ? $mov['COD_CTA_CONTRAPARTIDA'] : null,
                // Incluir información de control
                'ID_SBA05' => $mov['ID_SBA05'],
                'CONTROLADO' => isset($mov['CONTROLADO']) ? (bool)$mov['CONTROLADO'] : false,
                'FECHA_CONTROL' => isset($mov['FECHA_CONTROL']) && $mov['FECHA_CONTROL'] ? $mov['FECHA_CONTROL']->format('d/m/Y H:i') : null
            ];
            $movimientosFormateados[] = $movFormateado;
        }

        enviarRespuestaJSON([
            'success' => true,
            'movimientos' => $movimientosFormateados,
            'saldoActual' => number_format($saldoActual, 2, '.', ''),
            'saldoActualDisplay' => number_format($saldoActual, 0, ',', '.'),
            'estadisticas' => [
                'totalMovimientos' => $estadisticas['TOTAL_MOVIMIENTOS'] ?? 0,
                'totalIngresos' => number_format($estadisticas['TOTAL_INGRESOS'] ?? 0, 0, ',', '.'),
                'totalEgresos' => number_format($estadisticas['TOTAL_EGRESOS'] ?? 0, 0, ',', '.'),
                'cantIngresos' => $estadisticas['CANT_INGRESOS'] ?? 0,
                'cantEgresos' => $estadisticas['CANT_EGRESOS'] ?? 0
            ],
            'ultimaFechaControlada' => $ultimaFechaControlada ? $ultimaFechaControlada->format('d/m/Y') : null
        ]);

    } catch (Exception $e) {
        error_log("Error en obtenerMovimientos controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => 'Error interno del servidor: ' . $e->getMessage()
        ]);
    }
}

function obtenerFotosEgreso() {
    try {
        if (!isset($_GET['codComp']) || !isset($_GET['nComp'])) {
            enviarRespuestaJSON(['error' => 'Faltan parámetros requeridos']);
            return;
        }

        $saldo = new Saldo();
        $codComp = $_GET['codComp'];
        $nComp = $_GET['nComp'];

        // Primero obtener información del gasto guardado para conseguir COD_CTA
        $infoGasto = $saldo->obtenerInfoFotosEgreso($codComp, $nComp);
        
        if (!$infoGasto['esta_guardado']) {
            enviarRespuestaJSON([
                'success' => false,
                'error' => 'Este egreso no está guardado en la tabla de gastos',
                'archivos' => [],
                'estaGuardado' => false,
                'tieneFotos' => false
            ]);
            return;
        }

        enviarRespuestaJSON([
            'success' => true,
            'archivos' => $infoGasto['archivos'] ?? [],
            'estaGuardado' => $infoGasto['esta_guardado'],
            'tieneFotos' => $infoGasto['tiene_fotos'],
            'codCta' => $infoGasto['cod_cta']
        ]);

    } catch (Exception $e) {
        error_log("Error en obtenerFotosEgreso controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => $e->getMessage(),
            'archivos' => [],
            'estaGuardado' => false,
            'tieneFotos' => false
        ]);
    }
}

function verificarEstadoEgreso() {
    try {
        if (!isset($_GET['codComp']) || !isset($_GET['nComp'])) {
            enviarRespuestaJSON(['error' => 'Faltan parámetros requeridos']);
            return;
        }

        $saldo = new Saldo();
        $codComp = $_GET['codComp'];
        $nComp = $_GET['nComp'];
        $codCta = isset($_GET['codCta']) ? $_GET['codCta'] : '100101';

        $fotos = $saldo->verificarFotosEgreso($codComp, $nComp, $codCta);
        $estaGuardado = $saldo->verificarRegistroGuardado($codComp, $nComp, $codCta);
        $tieneFotos = count($fotos) > 0;

        enviarRespuestaJSON([
            'success' => true,
            'tieneFotos' => $tieneFotos,
            'estaGuardado' => $estaGuardado,
            'numeroFotos' => count($fotos)
        ]);

    } catch (Exception $e) {
        error_log("Error en verificarEstadoEgreso controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'tieneFotos' => false,
            'estaGuardado' => false,
            'numeroFotos' => 0,
            'error' => $e->getMessage()
        ]);
    }
}

function obtenerEstadisticas() {
    try {
        if (!isset($_GET['desde']) || !isset($_GET['hasta'])) {
            enviarRespuestaJSON(['error' => 'Faltan parámetros de fecha']);
            return;
        }

        $saldo = new Saldo();
        $desde = $_GET['desde'];
        $hasta = $_GET['hasta'];

        $estadisticas = $saldo->obtenerEstadisticasMovimientos($desde, $hasta);

        enviarRespuestaJSON([
            'success' => true,
            'estadisticas' => $estadisticas
        ]);

    } catch (Exception $e) {
        error_log("Error en obtenerEstadisticas controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function actualizarControl() {
    try {
        if (!isset($_POST['idSba05']) || !isset($_POST['controlado'])) {
            enviarRespuestaJSON(['error' => 'Faltan parámetros requeridos']);
            return;
        }

        $saldo = new Saldo();
        $idSba05 = $_POST['idSba05'];
        $controlado = $_POST['controlado'] === 'true' || $_POST['controlado'] === '1';

        // Log para debug
        error_log("Controller actualizarControl - ID_SBA05: $idSba05, Controlado: " . ($controlado ? 'true' : 'false'));

        $resultado = $saldo->actualizarControlMovimiento($idSba05, $controlado);

        enviarRespuestaJSON([
            'success' => $resultado,
            'message' => 'Control actualizado correctamente'
        ]);

    } catch (Exception $e) {
        error_log("Error en actualizarControl controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => 'Error interno: ' . $e->getMessage()
        ]);
    }
}

function actualizarControlMasivo() {
    try {
        if (!isset($_POST['idsSba05']) || !isset($_POST['controlado'])) {
            enviarRespuestaJSON(['error' => 'Faltan parámetros requeridos']);
            return;
        }

        $saldo = new Saldo();
        $idsSba05 = json_decode($_POST['idsSba05'], true);
        $controlado = $_POST['controlado'] === 'true' || $_POST['controlado'] === '1';

        if (!is_array($idsSba05) || empty($idsSba05)) {
            enviarRespuestaJSON(['error' => 'IDs inválidos']);
            return;
        }

        $exitos = $saldo->actualizarControlMasivo($idsSba05, $controlado);

        enviarRespuestaJSON([
            'success' => true,
            'message' => "Se actualizaron $exitos registros correctamente",
            'procesados' => $exitos,
            'total' => count($idsSba05)
        ]);

    } catch (Exception $e) {
        error_log("Error en actualizarControlMasivo controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function diagnosticar() {
    try {
        if (!isset($_GET['desde']) || !isset($_GET['hasta'])) {
            enviarRespuestaJSON(['error' => 'Faltan parámetros de fecha']);
            return;
        }

        $saldo = new Saldo();
        $desde = $_GET['desde'];
        $hasta = $_GET['hasta'];

        $resultado = $saldo->diagnosticarStoredProcedure($desde, $hasta);

        enviarRespuestaJSON([
            'success' => $resultado,
            'message' => 'Diagnóstico completado, revisar logs'
        ]);

    } catch (Exception $e) {
        error_log("Error en diagnosticar controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function regenerarControl() {
    try {
        $saldo = new Saldo();
        
        $desde = isset($_GET['desde']) ? $_GET['desde'] : null;
        $hasta = isset($_GET['hasta']) ? $_GET['hasta'] : null;

        $contador = $saldo->regenerarRegistrosControl($desde, $hasta);

        enviarRespuestaJSON([
            'success' => true,
            'message' => "Se regeneraron $contador registros de control",
            'registros_creados' => $contador
        ]);

    } catch (Exception $e) {
        error_log("Error en regenerarControl controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function repararTabla() {
    try {
        $saldo = new Saldo();
        
        // Llamar al método de reparación de tabla usando reflexión
        $reflection = new ReflectionClass($saldo);
        $method = $reflection->getMethod('repararTablaControlMovimientos');
        $method->setAccessible(true);
        $resultado = $method->invoke($saldo);

        enviarRespuestaJSON([
            'success' => $resultado,
            'message' => 'Tabla reparada exitosamente'
        ]);

    } catch (Exception $e) {
        error_log("Error en repararTabla controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function diagnosticarUltimaFecha() {
    try {
        if (!isset($_GET['desde']) || !isset($_GET['hasta'])) {
            enviarRespuestaJSON(['error' => 'Faltan parámetros de fecha']);
            return;
        }

        $saldo = new Saldo();
        $desde = $_GET['desde'];
        $hasta = $_GET['hasta'];

        $resultado = $saldo->diagnosticarUltimaFechaControlada($desde, $hasta);

        enviarRespuestaJSON([
            'success' => $resultado,
            'message' => 'Diagnóstico de última fecha controlada completado, revisar logs'
        ]);

    } catch (Exception $e) {
        error_log("Error en diagnosticarUltimaFecha controller: " . $e->getMessage());
        enviarRespuestaJSON([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>