<?php
/**
 * Controlador: traerOrdenesPendientesController.php
 * Objetivo: Trae todas las órdenes de compra del último año y medio 
 *           que NO tienen despacho asignado
 * Retorna: JSON con array de OC pendientes
 */

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Incluir la clase OrdenDeCompra
    require_once __DIR__ . '/../class/OrdenDeCompra.php';
    
    // Crear instancia y obtener las órdenes pendientes
    $ordenCompra = new OrdenDeCompra();
    $ordenesPendientes = $ordenCompra->traerOrdenesPendientes();
    
    // Retornar resultado como JSON
    echo json_encode($ordenesPendientes, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // En caso de error, retornar array vacío
    error_log("Error en traerOrdenesPendientesController: " . $e->getMessage());
    echo json_encode([]);
}
?>
