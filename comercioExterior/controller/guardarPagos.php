<?php
require_once __DIR__ . '/../class/Pagos.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['idEncabezado']) || empty($_POST['idEncabezado'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID del encabezado no proporcionado'
        ]);
        exit;
    }
    
    if (!isset($_POST['pagos']) || empty($_POST['pagos'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No se enviaron pagos'
        ]);
        exit;
    }
    
    $idEncabezado = $_POST['idEncabezado'];
    $pagosJson = $_POST['pagos'];
    $pagosArray = json_decode($pagosJson, true);
    
    if (!is_array($pagosArray)) {
        echo json_encode([
            'success' => false,
            'message' => 'Formato de pagos inválido'
        ]);
        exit;
    }
    
    $pagosClass = new Pagos();
    
    try {
        // Primero, eliminar todos los pagos existentes del encabezado
        $resultadoEliminar = $pagosClass->eliminarPagosPorEncabezado($idEncabezado);
        
        if ($resultadoEliminar !== true) {
            throw new Exception($resultadoEliminar);
        }
        
        // Luego, insertar todos los pagos nuevos
        foreach ($pagosArray as $pago) {
            // Validar que el pago tenga todos los campos requeridos
            if (!isset($pago['fechaPago']) || !isset($pago['formaPago']) || 
                !isset($pago['medioPago']) || !isset($pago['monto'])) {
                throw new Exception('Faltan campos requeridos en uno de los pagos');
            }
            
            $resultadoInsertar = $pagosClass->insertarPago(
                $idEncabezado,
                $pago['fechaPago'],
                $pago['formaPago'],
                $pago['medioPago'],
                $pago['monto']
            );
            
            if ($resultadoInsertar !== true) {
                throw new Exception($resultadoInsertar);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Pagos guardados correctamente'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar pagos: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>
