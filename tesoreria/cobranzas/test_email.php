<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/api/notificaciones_controller.php';

try {
    $email = 'franco.pertus@xl.com.ar';
    $titulo = 'Test Notifications';
    $cuerpo = 'This is a test to see if notifications are broken.';
    echo "Sending notification...\n";
    enviarNotificacion($email, $titulo, $cuerpo);
    echo "Notification sent successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
