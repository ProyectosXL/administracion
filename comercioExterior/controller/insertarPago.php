<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    // Validar que la solicitud sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no permitido');
    }
    
    // Validar parámetros requeridos
    $required = ['id_despacho', 'fecha_pago', 'forma_pago', 'medio_pago', 'monto'];
    foreach ($required as $param) {
        if (!isset($_POST[$param]) || empty($_POST[$param])) {
            throw new Exception("Parámetro requerido: $param");
        }
    }
    
    $idDespacho = intval($_POST['id_despacho']);
    $fechaPago = $_POST['fecha_pago'];
    $formaPago = $_POST['forma_pago'];
    $medioPago = $_POST['medio_pago'];

    /* EL IMPORTE ES EN U$S y tiene que ser un número positivo.
       MONTO es NOT NULL en la tabla, así que un valor no numérico no entraba
       igual: fallaba con un error de SQL Server en pantalla en vez de decir
       qué estaba mal. Se acepta coma o punto decimal porque el campo del
       formulario admite las dos. */
    $montoCrudo = str_replace(',', '.', trim((string) $_POST['monto']));

    if (!is_numeric($montoCrudo)) {
        throw new Exception('El importe en U$S no es un número válido');
    }

    $monto = round(floatval($montoCrudo), 2);

    if ($monto <= 0) {
        throw new Exception('El importe en U$S tiene que ser mayor a cero');
    }

    // Convertir fecha de DD/MM/YYYY a YYYY-MM-DD HH:MM:SS
    $partes = explode('/', $fechaPago);
    if (count($partes) !== 3) {
        throw new Exception('Formato de fecha inválido');
    }
    $fechaFormato = $partes[2] . '-' . $partes[1] . '-' . $partes[0] . ' 00:00:00';

    // Incluir clases necesarias
    require_once('../class/Pagos.php');

    $pagosClass = new Pagos();

    // Insertar el pago
    $resultado = $pagosClass->insertarPago($idDespacho, $fechaFormato, $formaPago, $medioPago, $monto);

    if ($resultado === true) {
        /* Se devuelve el saldo recalculado por el servidor en lugar de dejar
           que la pantalla lo deduzca sumando lo que ya tenía dibujado: un
           pago cargado desde otra pestaña, o un FOB corregido mientras tanto,
           harían que las dos cuentas no coincidan. */
        $resumen = $pagosClass->obtenerResumen($idDespacho);

        echo json_encode([
            'success'        => true,
            'message'        => 'Pago insertado correctamente',
            'fobUsd'         => $resumen['fobUsd'],
            'totalPagado'    => $resumen['totalPagado'],
            'saldoPendiente' => $resumen['saldoPendiente'],
            'estado'         => $resumen['estado']
        ]);
    } else {
        throw new Exception($resultado);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
