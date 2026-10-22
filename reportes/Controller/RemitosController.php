<?php
/**
 * RemitosController.php
 * Pestaña Remitos pendientes de facturar.
 *
 *   POST Controller/RemitosController.php?action=...
 *     periodos   las opciones del período con su rango, y hoy
 *     clientes   los clientes con remitos en un período y tipo, para el filtro
 *     consultar  tarjetas, resumen por tipo y por cliente, y leyenda
 *     detalle    las filas para el Excel, con su encabezado
 *   responde { success, message, ... }
 *
 * Todas son lecturas de ra.tab.remitos_pendientes (AuthReportes::LECTURAS).
 * consultar y detalle corren LO MISMO con los mismos filtros: lo que se
 * descarga es exactamente lo que se resumió en pantalla.
 *
 * "Hoy" lo pone el servidor, en hora de Argentina: es el tope del Hasta y el
 * final del pozo de facturas. El front no lo manda.
 */
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

require_once __DIR__ . '/autorizacion.php';
require_once __DIR__ . '/../Class/RemitosPendientes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
$usuario = autorizar('Remitos', $action);

/** Los filtros del POST, sin validar */
function filtrosDelPost() {
    return [
        'desde' => $_POST['desde'] ?? '',
        'hasta' => $_POST['hasta'] ?? '',
        'tipo' => $_POST['tipo'] ?? '',
        'clientes' => $_POST['clientes'] ?? []
    ];
}

/**
 * Valida los filtros del POST contra la base.
 *
 * @return array La validación, o corta con success = false
 */
function filtrosValidados(RemitosPendientes $r, $hoy) {
    $val = $r->validar(filtrosDelPost(), $hoy);

    if (!$val['ok']) {
        echo json_encode(['success' => false, 'message' => $val['message']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $val;
}

/** Lo que describe la consulta: lo mismo en pantalla y en el Excel */
function descripcion(array $val, $hoy) {
    return [
        'filtros' => $val['filtros'],
        'hoy' => $hoy,
        'leyenda' => RemitosPendientes::leyenda($val['filtros'], $hoy),
        'filtrosTexto' => RemitosPendientes::filtrosEnTexto($val['filtros'], $val['nombres'])
    ];
}

try {
    $hoy = RemitosPendientes::hoy();

    switch ($action) {
        case 'periodos':
            $respuesta = ['success' => true, 'hoy' => $hoy, 'periodos' => RemitosPendientes::periodos($hoy)];
            break;

        case 'clientes':
            $per = RemitosPendientes::validarPeriodo(filtrosDelPost(), $hoy);

            if (!$per['ok']) {
                $respuesta = ['success' => false, 'message' => $per['message']];
                break;
            }

            $respuesta = ['success' => true, 'filtros' => $per['filtros'],
                'clientes' => (new RemitosPendientes())->clientes($per['filtros'])];
            break;

        case 'consultar':
            $r = new RemitosPendientes();
            $val = filtrosValidados($r, $hoy);
            $resumen = RemitosPendientes::resumir($r->detalle($val['filtros'], $hoy));

            $respuesta = array_merge(['success' => true], descripcion($val, $hoy), $resumen);
            break;

        case 'detalle':
            $r = new RemitosPendientes();
            $val = filtrosValidados($r, $hoy);
            $filas = $r->detalle($val['filtros'], $hoy);

            // Las filas viajan como listas, como en la valorización
            $respuesta = array_merge(['success' => true], descripcion($val, $hoy), [
                'resumen' => RemitosPendientes::resumir($filas),
                'columnas' => RemitosPendientes::COLUMNAS,
                'filas' => array_map(function ($f) {
                    return array_map(function ($c) use ($f) {
                        return $f[$c];
                    }, RemitosPendientes::COLUMNAS);
                }, $filas),
                'usuario' => $usuario,
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
    error_log('Reportes/RemitosController ' . $action . ': ' . $e->getMessage());
    $respuesta = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
