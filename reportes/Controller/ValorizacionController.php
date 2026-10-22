<?php
/**
 * ValorizacionController.php
 * Pestaña Valorización de stock.
 *
 *   POST Controller/ValorizacionController.php?action=...
 *     fechas      las fechas de cierre, de la más reciente a la más vieja
 *     sucursales  las sucursales con stock en una fecha, con su sector
 *     consultar   resumen por sector y sucursal, tarjetas y leyenda
 *     detalle     las filas para el Excel, con su encabezado
 *   responde { success, message, ... }
 *
 * Todas son lecturas de ra.tab.valorizacion_stock (AuthReportes::LECTURAS).
 * consultar y detalle corren LA MISMA consulta con los mismos filtros: lo que
 * se descarga es exactamente lo que se resumió en pantalla.
 *
 * El factor de nacionalización lo lee el servidor de Parámetros; el front no
 * lo manda, y si lo mandara se ignoraría.
 */
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

require_once __DIR__ . '/autorizacion.php';
require_once __DIR__ . '/../Class/ValorizacionStock.php';
require_once __DIR__ . '/../Class/Parametros.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
$usuario = autorizar('Valorizacion', $action);

/**
 * Valida los filtros del POST y, en modo actual, lee el factor.
 *
 * @return array [filtros, factor] o corta con success = false
 */
function filtrosYFactor(ValorizacionStock $v) {
    $val = $v->validar([
        'fecha' => $_POST['fecha'] ?? '',
        'modo' => $_POST['modo'] ?? '',
        'sector' => $_POST['sector'] ?? '',
        'sucursales' => $_POST['sucursales'] ?? [],
        'articulo' => $_POST['articulo'] ?? ''
    ]);

    if (!$val['ok']) {
        echo json_encode(['success' => false, 'message' => $val['message']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $factor = null;

    if ($val['filtros']['modo'] === ValorizacionStock::MODO_ACTUAL) {
        $factor = (new Parametros())->valor(Parametros::FACTOR_NAC_ESTIMADO);
    }

    return [$val['filtros'], $factor];
}

/** Lo que describe la consulta: lo mismo en pantalla y en el Excel */
function descripcion(array $filtros, $factor) {
    return [
        'filtros' => $filtros,
        'leyenda' => ValorizacionStock::leyenda($filtros),
        'filtrosTexto' => ValorizacionStock::filtrosEnTexto($filtros),
        'factor' => $factor,
        'factorTexto' => $factor === null ? null : Parametros::formatear($factor)
    ];
}

try {
    $v = new ValorizacionStock();

    switch ($action) {
        case 'fechas':
            $respuesta = ['success' => true, 'fechas' => $v->fechas()];
            break;

        case 'sucursales':
            $fecha = trim((string) ($_POST['fecha'] ?? ''));

            if (!in_array($fecha, $v->fechas(), true)) {
                $respuesta = ['success' => false, 'message' => 'Elegí una fecha de cierre de la lista.'];
                break;
            }

            $respuesta = ['success' => true, 'fecha' => $fecha, 'sucursales' => $v->sucursales($fecha)];
            break;

        case 'consultar':
            list($filtros, $factor) = filtrosYFactor($v);
            $resumen = ValorizacionStock::resumir($v->detalle($filtros, $factor));

            $respuesta = array_merge(['success' => true], descripcion($filtros, $factor), $resumen);
            break;

        case 'detalle':
            list($filtros, $factor) = filtrosYFactor($v);
            $filas = $v->detalle($filtros, $factor);

            // Las filas viajan como listas y no como objetos: con 40.000 filas
            // repetir los nombres de las columnas es la mitad del peso.
            $respuesta = array_merge(['success' => true], descripcion($filtros, $factor), [
                'resumen' => ValorizacionStock::resumir($filas),
                'columnas' => ValorizacionStock::COLUMNAS,
                'filas' => array_map(function ($f) {
                    return array_map(function ($c) use ($f) {
                        return $f[$c];
                    }, ValorizacionStock::COLUMNAS);
                }, $filas),
                'usuario' => $usuario,
                // Con la zona escrita: el PHP de algunos servidores no la
                // tiene configurada (el de desarrollo está en Europe/Berlin)
                'generado' => (new DateTime('now', new DateTimeZone('America/Argentina/Buenos_Aires')))
                    ->format('Y-m-d H:i:s')
            ]);
            break;

        default:
            // No se llega: autorizar() ya rechazó lo que no está declarado
            http_response_code(400);
            $respuesta = ['success' => false, 'message' => 'Acción no válida.'];
    }
} catch (Throwable $e) {
    error_log('Reportes/ValorizacionController ' . $action . ': ' . $e->getMessage());
    $respuesta = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
