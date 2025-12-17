
<?php
require_once '../Class/Gasto.php';

$accion = $_GET['accion'] ?? '';

switch ($accion) {
    case 'obtenerModulos':
        obtenerModulos();
        break;
    case 'eliminarModulo':
        eliminarModulo();
        break;
    case 'reprocesarModulo':
        reprocesarModulo();
        break;
    case 'contarRegistrosModulo':
        contarRegistrosModulo();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

function obtenerModulos() {
    require_once __DIR__.'/../../class/conexion.php';
    $cid = new Conexion();
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $cid_central = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') 
        ? $cid->conectar('uy') 
        : $cid->conectar('central');
    
    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    
    $sql = "SELECT MODULO, COUNT(*) AS CANTIDAD 
            FROM RO_T_INTEGRAL_TANGO_2 
            WHERE FECHA BETWEEN '$desde' AND '$hasta'
            GROUP BY MODULO
            ORDER BY MODULO";
    
    $stmt = sqlsrv_query($cid_central, $sql);
    $modulos = [];
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $modulos[] = $row;
    }
    
    echo json_encode($modulos);
}

function contarRegistrosModulo() {
    require_once __DIR__.'/../../class/conexion.php';
    $cid = new Conexion();
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $cid_central = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') 
        ? $cid->conectar('uy') 
        : $cid->conectar('central');
    
    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    $modulo = $_POST['modulo'];
    
    $sql = "SELECT COUNT(*) AS CANTIDAD 
            FROM RO_T_INTEGRAL_TANGO_2 
            WHERE FECHA BETWEEN '$desde' AND '$hasta' AND MODULO = '$modulo'";
    
    $stmt = sqlsrv_query($cid_central, $sql);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    echo json_encode($row);
}

function eliminarModulo() {
    require_once __DIR__.'/../../class/conexion.php';
    $cid = new Conexion();
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $cid_central = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') 
        ? $cid->conectar('uy') 
        : $cid->conectar('central');
    
    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    $modulo = $_POST['modulo'];
    
    // Validar que el módulo sea válido
    $modulosValidos = ['COMPRAS', 'VENTAS', 'CONTABILIDAD', 'TESORERIA'];
    if (!in_array(strtoupper($modulo), $modulosValidos)) {
        echo json_encode(['success' => false, 'message' => 'Módulo no válido']);
        return;
    }
    
    $sql = "EXEC RO_SP_ELIMINAR_MODULO_INTEGRAL ?, ?, ?";
    $params = [$desde, $hasta, strtoupper($modulo)];
    
    $stmt = sqlsrv_query($cid_central, $sql, $params);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        echo json_encode(['success' => false, 'message' => $errors[0]['message']]);
        return;
    }
    
    $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    echo json_encode([
        'success' => true, 
        'registros_eliminados' => $result['REGISTROS_ELIMINADOS'],
        'modulo' => $result['MODULO']
    ]);
}

function reprocesarModulo() {
    require_once __DIR__.'/../../class/conexion.php';
    $cid = new Conexion();
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $cid_central = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') 
        ? $cid->conectar('uy') 
        : $cid->conectar('central');
    
    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    $modulo = $_POST['modulo'];
    
    // Validar módulo
    $modulosValidos = ['COMPRAS', 'VENTAS', 'CONTABILIDAD', 'TESORERIA'];
    if (!in_array(strtoupper($modulo), $modulosValidos)) {
        echo json_encode(['success' => false, 'message' => 'Módulo no válido']);
        return;
    }
    
    $sql = "EXEC RO_SP_INTEGRAL_MODULO ?, ?, ?";
    $params = [$desde, $hasta, strtoupper($modulo)];
    
    $stmt = sqlsrv_query($cid_central, $sql, $params);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        echo json_encode(['success' => false, 'message' => $errors[0]['message']]);
        return;
    }
    
    $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    echo json_encode([
        'success' => true, 
        'registros_insertados' => $result['REGISTROS_INSERTADOS'],
        'modulo' => $result['MODULO']
    ]);
}
?>