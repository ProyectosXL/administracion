<?php
/**
 * TabController.php
 * Sirve el HTML de una pestaña, como el del Informe Económico.
 *
 * Sólo sirve las pestañas que declara Menu, y sólo con su permiso: la lista de
 * pestañas válidas no se repite acá, sale del mismo dato que dibuja las
 * solapas. El nombre del archivo que se incluye sale de Menu, nunca del
 * request.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Class/Menu.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

$def = Menu::buscar(isset($_POST['tab']) ? trim((string) $_POST['tab']) : '');

if ($def === null) {
    http_response_code(400);
    exit('Pestaña no válida');
}

if (!AuthReportes::estaAutenticado()) {
    http_response_code(401);
    echo '<div class="ra-estado"><div class="ra-estado-icon"><i class="bi bi-person-x"></i></div>'
        . '<h3>Sesión vencida</h3><p>No se pudo identificar tu usuario. Volvé a ingresar al sistema.</p></div>';
    exit;
}

if (!AuthReportes::puede($def['tab'])) {
    http_response_code(403);
    echo '<div class="ra-estado"><div class="ra-estado-icon"><i class="bi bi-lock"></i></div>'
        . '<h3>Acceso restringido</h3><p>Tu usuario no tiene el permiso <code>'
        . htmlspecialchars(AuthReportes::claveVer($def['tab'])) . '</code> para esta pestaña.</p></div>';
    exit;
}

include __DIR__ . '/../Tabs/' . $def['tab'] . '.php';
