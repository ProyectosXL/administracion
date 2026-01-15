<?php
/**
 * Controlador para obtener terminales según el entorno
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../class/terminal.php';
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $terminalClass = new Terminal();
    
    // Obtener entorno actual
    $entorno = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    
    // Traer terminales según entorno
    $terminales = $terminalClass->traerTerminales($entorno);
    
    echo json_encode([
        'success' => true,
        'entorno' => $entorno,
        'terminales' => $terminales
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
