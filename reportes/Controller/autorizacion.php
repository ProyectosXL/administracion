<?php
/**
 * La guarda de todos los controllers JSON del módulo.
 *
 * Cada controller la llama UNA vez, apenas lee $action y antes del switch:
 *
 *     $usuario = autorizar('Parametros', $action);
 *
 * y después usa $usuario en cada escritura. Lo que decide qué permiso pide
 * cada acción es el mapa de AuthReportes (ESCRITURAS / LECTURAS), no el
 * controller.
 *
 * POR QUÉ ANTES DEL SWITCH Y NO EN CADA case: un case nuevo no puede olvidarse
 * de pedir permiso, porque no es el case el que lo pide. Si nadie lo declaró
 * en el mapa, la acción se rechaza (403) y tests/test_auth_reportes.php falla.
 *
 * Y POR QUÉ ACÁ Y NO EN EL catch DE CADA CONTROLLER: una negativa de permisos
 * tiene que salir con su código HTTP -401 o 403- para que el front la
 * distinga de un error de validación, que sale 200 con success = false.
 */

require_once __DIR__ . '/../Class/AuthReportes.php';

/**
 * Exige lo que el mapa pide para esta acción y devuelve el username del
 * padrón. Sin usuario corta con 401; sin permiso o sin declarar, con 403. En
 * los dos casos el cuerpo es el JSON de siempre: {success: false, message}.
 *
 * @param string $controlador Nombre del controller sin "Controller"
 * @param string $accion
 * @return string
 */
function autorizar($controlador, $accion) {
    try {
        return AuthReportes::exigirAccion($controlador, $accion);
    } catch (AuthReportesSinUsuario $e) {
        responderSinAutorizacion(401, $e->getMessage());
    } catch (AuthReportesSinPermiso $e) {
        responderSinAutorizacion(403, $e->getMessage());
    }

    return null;
}

/**
 * Corta el request con el código y el mensaje.
 *
 * @param int $codigo
 * @param string $mensaje
 */
function responderSinAutorizacion($codigo, $mensaje) {
    http_response_code($codigo);

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode([
        'success' => false,
        'message' => $mensaje
    ], JSON_UNESCAPED_UNICODE);

    exit;
}
