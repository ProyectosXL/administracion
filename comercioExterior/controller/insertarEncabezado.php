<?php
header('Content-Type: application/json');

/**
 * Convierte fecha de formato DD/MM/YYYY a YYYY-MM-DD para SQL Server
 */
function convertirFecha($fecha) {
    if (empty($fecha)) return null;
    
    // Si ya está en formato YYYY-MM-DD, devolverla tal cual
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $fecha;
    }
    
    // Convertir de DD/MM/YYYY a YYYY-MM-DD
    $partes = explode('/', $fecha);
    if (count($partes) == 3) {
        return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
    }
    
    return $fecha;
}

try {
    require_once '../Class/encabezado.php';
    $cid = new Encabezado();

    // Log de datos recibidos para debugging
    error_log("POST recibido: " . print_r($_POST, true));
    
    // Detectar modo de operación
    $modoEdicion = isset($_POST['modoEdicion']) && $_POST['modoEdicion'] === 'true';
    $idDespacho = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Validar que se recibieron los datos necesarios
    if (!isset($_POST['cod_proveedor']) || !isset($_POST['ordenCompra'])) {
        throw new Exception('Faltan datos obligatorios: cod_proveedor o ordenCompra');
    }
    
    if (!isset($_POST['fechaEstEmb']) || empty($_POST['fechaEstEmb'])) {
        throw new Exception('Falta la Fecha Estimada de Embarque (fechaEstEmb)');
    }
    
    // Si es modo edición, validar ID
    if ($modoEdicion && $idDespacho <= 0) {
        throw new Exception('ID de despacho inválido para actualización');
    }

    $datosDeCabezera = [];
    
    // Sección 1 - Datos Iniciales (obligatorios)
    $datosDeCabezera['cod_proveedor'] = $_POST['cod_proveedor'];
    $datosDeCabezera['proveedor'] = $_POST['proveedor'];
    $datosDeCabezera['contenedor'] = $_POST['contenedor'];
    $datosDeCabezera['material'] = $_POST['material'];
    $datosDeCabezera['origen'] = $_POST['origen'];
    $datosDeCabezera['valorFobDolar'] = $_POST['valorFobDolar'];
    // Convertir fechas de DD/MM/YYYY a YYYY-MM-DD para SQL Server
    $datosDeCabezera['fechaEstEmb'] = convertirFecha($_POST['fechaEstEmb']);
    $datosDeCabezera['ocm'] = $_POST['ocm'];
    
    // DESPACHANTE - con valor por defecto 'Laffitte' si no se especifica
    $datosDeCabezera['despachante'] = isset($_POST['despachante']) && !empty($_POST['despachante']) 
        ? $_POST['despachante'] 
        : 'Laffitte';
    
    error_log("DESPACHANTE capturado: " . $datosDeCabezera['despachante']);
    
    // Campos calculados automáticamente desde Sección 1
    if (isset($_POST['fechaArr']) && !empty($_POST['fechaArr'])) {
        $datosDeCabezera['fechaArr'] = convertirFecha($_POST['fechaArr']);
    }
    
    if (isset($_POST['fechaPago']) && !empty($_POST['fechaPago'])) {
        $datosDeCabezera['fechaPago'] = convertirFecha($_POST['fechaPago']);
    }
    
    if (isset($_POST['fechaDespAdu']) && !empty($_POST['fechaDespAdu'])) {
        $datosDeCabezera['fechaDespAdu'] = convertirFecha($_POST['fechaDespAdu']);
    }
    
    // Campos opcionales de secciones 2 y 3 (solo agregar si tienen valor)
    // FECHA_EMB es ETD (Estimated Time of Departure - fecha de salida real)
    if (isset($_POST['fechaEmb']) && !empty($_POST['fechaEmb'])) {
        $datosDeCabezera['fechaEmb'] = convertirFecha($_POST['fechaEmb']);
    }
    
    if (isset($_POST['numeroBl']) && !empty($_POST['numeroBl'])) {
        $datosDeCabezera['numeroBl'] = $_POST['numeroBl'];
    }
    
    if (isset($_POST['factura']) && !empty($_POST['factura'])) {
        $datosDeCabezera['facturaProveedor'] = $_POST['factura'];
    }
    
    if (isset($_POST['puertoOrigen']) && !empty($_POST['puertoOrigen'])) {
        $datosDeCabezera['puertoOrigen'] = $_POST['puertoOrigen'];
        error_log("PUERTO_ORIGEN capturado: " . $datosDeCabezera['puertoOrigen']);
    }
    
    if (isset($_POST['terminal']) && !empty($_POST['terminal'])) {
        $datosDeCabezera['terminal'] = $_POST['terminal'];
        error_log("TERMINAL capturado: " . $datosDeCabezera['terminal']);
    }
    
    // Fecha Estimada de Pago
    if (isset($_POST['fechaEstPago']) && !empty($_POST['fechaEstPago'])) {
        $datosDeCabezera['fechaEstPago'] = convertirFecha($_POST['fechaEstPago']);
    }
    
    if (isset($_POST['tipoCambio']) && !empty($_POST['tipoCambio'])) {
        $datosDeCabezera['tipoCambio'] = $_POST['tipoCambio'];
    }
    
    if (isset($_POST['valorFobPeso']) && !empty($_POST['valorFobPeso'])) {
        $datosDeCabezera['valorFobPeso'] = $_POST['valorFobPeso'];
    }
    
    if (isset($_POST['formaPago']) && !empty($_POST['formaPago'])) {
        $datosDeCabezera['formaPago'] = $_POST['formaPago'];
    }
    
    if (isset($_POST['despacho']) && !empty($_POST['despacho'])) {
        $datosDeCabezera['despacho'] = $_POST['despacho'];
    }
    
    // ETA Confirmada - checkbox (0 o 1) - SIEMPRE capturar el valor enviado
    $datosDeCabezera['etaConfirmada'] = isset($_POST['eta_confirmada']) ? intval($_POST['eta_confirmada']) : 0;

    $ordenes = json_decode($_POST['ordenCompra'], true);

    if (!is_array($ordenes) || count($ordenes) == 0) {
        throw new Exception('No se especificaron órdenes de compra');
    }

    $arrayResult = [];

    if ($modoEdicion) {
        // MODO ACTUALIZACIÓN - actualizar el despacho existente
        // Preservar el espacio adelante si existe en órdenes de 13 dígitos
        $ordenesFormateadas = array_map(function($orden) {
            $ordenTrim = trim($orden);
            if(strlen($ordenTrim) == 13){
                return ' ' . $ordenTrim;
            }
            return $ordenTrim;
        }, $ordenes);
        
        // En modo edición, todas las órdenes se concatenan en un solo registro
        $datosDeCabezera['ordenCompra'] = implode(', ', $ordenesFormateadas);
        
        $result = $cid->actualizarEncabezado($idDespacho, $datosDeCabezera);
        
        if ($result) {
            $arrayResult[] = $result;
        }
        
        echo json_encode([
            'success' => true,
            'ids' => $arrayResult,
            'message' => 'Despacho actualizado correctamente'
        ]);
    } else {
        // MODO INSERCIÓN - crear nuevo(s) despacho(s)
        foreach ($ordenes as  $orden) {

            if(strlen(trim($orden)) == 13){
                $orden = ' '.trim($orden);
            }

            $datosDeCabezera['ordenCompra'] = $orden;

            $result = $cid->insertarEncabezado($datosDeCabezera);

            if ($result) {
                $arrayResult[] = $result;
            }

        }

        echo json_encode([
            'success' => true,
            'ids' => $arrayResult,
            'message' => 'Despacho(s) guardado(s) correctamente'
        ]);
    }

} catch (Exception $e) {
    error_log("Error en insertarEncabezado.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}




