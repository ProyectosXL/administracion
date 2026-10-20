<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../Class/sucursal.php';

$response = ['success' => false, 'message' => 'Acción no válida.'];
$action = $_POST['action'] ?? '';

$validarFecha = function ($valor) {
    $d = DateTime::createFromFormat('Y-m-d', (string)$valor);
    if (!$d || $d->format('Y-m-d') !== $valor) {
        throw new Exception('Fecha inválida.');
    }
    return $valor;
};

$numero = function ($valor) {
    return $valor !== null ? (float)$valor : null;
};

try {
    // El constructor abre la sesión
    $sucursal = new Sucursal();

    // El SP y la lógica del detalle son de Argentina (RO_T_VENTA_DIARIA_SUCURSALES)
    if (isset($_SESSION['entorno']) && in_array($_SESSION['entorno'], ['uy', 'suc_uy'])) {
        throw new Exception('El detalle y el reprocesamiento solo están disponibles para Argentina.');
    }

    $nroSucursal = (string)($_POST['sucursal'] ?? '');
    $nrosValidos = array_column((array)$sucursal->traerLocales(true), 'NRO_SUCURSAL');
    if (!in_array($nroSucursal, $nrosValidos)) {
        throw new Exception('Sucursal inválida.');
    }

    switch ($action) {
        case 'detalle':
            $fecha = $validarFecha($_POST['fecha'] ?? '');

            $movimientos = $sucursal->traerDetalleCobranzaDia($nroSucursal, $fecha);
            $descripciones = $sucursal->traerDescripcionCuentasTesoreria(array_column($movimientos, 'COD_CTA'));

            $detalle = [];
            foreach ($movimientos as $m) {
                $codCta = $m['COD_CTA'] !== null ? trim($m['COD_CTA']) : null;
                $detalle[] = [
                    'MEDIO_PAGO'    => $m['MEDIO_PAGO'],
                    'COD_CTA'       => $codCta,
                    'DESC_CTA'      => $codCta !== null && isset($descripciones[$codCta]) ? $descripciones[$codCta] : '',
                    'T_COMP'        => trim((string)$m['T_COMP']),
                    'N_COMP'        => trim((string)$m['N_COMP']),
                    'IMPORTE'       => $numero($m['IMPORTE']),
                    'COTIZACION_TC' => $numero($m['COTIZACION_TC']),
                ];
            }

            $tabla = [];
            foreach ($sucursal->traerVentaDiariaRango($nroSucursal, $fecha, $fecha) as $fila) {
                $tabla[] = [
                    'MEDIO_PAGO'      => $fila['MEDIO_PAGO'],
                    'IMPORTE_SISTEMA' => $numero($fila['IMPORTE_SISTEMA']),
                    'COTIZACION_TC'   => $numero($fila['COTIZACION_TC']),
                    'VERIFICADO'      => (int)$fila['VERIFICADO'] === 1,
                ];
            }

            $response = ['success' => true, 'detalle' => $detalle, 'tabla' => $tabla];
            break;

        case 'reprocesar':
            $desde = $validarFecha($_POST['desde'] ?? '');
            $hasta = $validarFecha($_POST['hasta'] ?? '');
            if ($desde > $hasta) {
                throw new Exception('La fecha desde no puede ser mayor que la fecha hasta.');
            }

            $clave = function ($fila) {
                return $fila['FECHA']->format('Y-m-d') . '|' . $fila['MEDIO_PAGO'] . '|' . $fila['COTIZACION_TC'];
            };

            $antes = [];
            foreach ($sucursal->traerVentaDiariaRango($nroSucursal, $desde, $hasta) as $fila) {
                $antes[$clave($fila)] = $numero($fila['IMPORTE_SISTEMA']);
            }

            $sucursal->reprocesarVentaDiaria($nroSucursal, $desde, $hasta);

            $cambios = [];
            foreach ($sucursal->traerVentaDiariaRango($nroSucursal, $desde, $hasta) as $fila) {
                $anterior = $antes[$clave($fila)] ?? null;
                $actual = $numero($fila['IMPORTE_SISTEMA']);
                if ($anterior !== $actual) {
                    $cambios[] = [
                        'FECHA'      => $fila['FECHA']->format('Y-m-d'),
                        'MEDIO_PAGO' => $fila['MEDIO_PAGO'],
                        'ANTES'      => $anterior,
                        'DESPUES'    => $actual,
                    ];
                }
            }

            $response = ['success' => true, 'cambios' => $cambios];
            break;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
