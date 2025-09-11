
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
                'SALDO_DISPLAY' => number_format($mov['SALDO'], 0, ',', '.')
            ];
            $movimientosFormateados[] = $movFormateado;
        }

        enviarRespuestaJSON([
            'success' => true,
            'movimientos' => $movimientosFormateados,
            'saldoActual' => number_format($saldoActual, 2, '.', ''),
            'saldoActualDisplay' => number_format($saldoActual, 0, ',', '.'),
            // En la función obtenerMovimientos(), reemplaza la sección de estadísticas:
            'estadisticas' => [
                'totalMovimientos' => isset($estadisticas['TOTAL_MOVIMIENTOS']) ? $estadisticas['TOTAL_MOVIMIENTOS'] : 0,
                'totalIngresos' => isset($estadisticas['TOTAL_INGRESOS']) ? number_format($estadisticas['TOTAL_INGRESOS'], 0, ',', '.') : '0',
                'totalEgresos' => isset($estadisticas['TOTAL_EGRESOS']) ? number_format($estadisticas['TOTAL_EGRESOS'], 0, ',', '.') : '0',
                'cantIngresos' => isset($estadisticas['CANT_INGRESOS']) ? $estadisticas['CANT_INGRESOS'] : 0,
                'cantEgresos' => isset($estadisticas['CANT_EGRESOS']) ? $estadisticas['CANT_EGRESOS'] : 0
            ]
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
        $codCta = isset($_GET['codCta']) ? $_GET['codCta'] : '100101';

        $fotos = $saldo->verificarFotosEgreso($codComp, $nComp, $codCta);
        $estaGuardado = $saldo->verificarRegistroGuardado($codComp, $nComp, $codCta);

        enviarRespuestaJSON([
            'success' => true,
            'archivos' => $fotos,
            'estaGuardado' => $estaGuardado,
            'tieneFotos' => count($fotos) > 0
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
?>