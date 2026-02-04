<?php
// Carpeta: ../controller/traerOrdenesController.php

header('Content-Type: application/json; charset=utf-8');
require_once '../class/OrdenDeCompra.php';

try {
    if (!isset($_GET['proveedor']) || empty($_GET['proveedor'])) {
        echo json_encode([]);
        exit;
    }
    
    $proveedor = $_GET['proveedor'];
    $cuenta = new OrdenDeCompra();
    $ordenes = $cuenta->traerOrdenDeCompra($proveedor);
    
    echo json_encode($ordenes);
    
} catch (Exception $e) {
    error_log("Error en traerOrdenesController: " . $e->getMessage());
    echo json_encode([]);
}
?>