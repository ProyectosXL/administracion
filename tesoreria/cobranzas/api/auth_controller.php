<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'login') {
    $nombre = $_POST['nombre'] ?? '';
    $pass = $_POST['pass'] ?? '';

    if (empty($nombre) || empty($pass)) {
        echo json_encode(['success' => false, 'message' => 'Usuario y contraseña son requeridos.']);
        exit;
    }

    try {
        $conn = Database::getConnection('central');
        
        // 1. Buscamos el usuario en SOF_USUARIOS
        $sql_usuario = "SELECT ID, NOMBRE, COD_CLIENT, TIPO FROM SOF_USUARIOS WHERE NOMBRE = ? AND PASS = ?";
        $params_usuario = [$nombre, $pass];
        $stmt_usuario = sqlsrv_query($conn, $sql_usuario, $params_usuario);

        if ($stmt_usuario === false) {
            throw new Exception("Error al consultar el usuario.");
        }
        $usuario = sqlsrv_fetch_array($stmt_usuario, SQLSRV_FETCH_ASSOC);

        if ($usuario) {
            $rol = (strtoupper($usuario['TIPO']) === 'SUPERVISION' || empty($usuario['COD_CLIENT'])) ? 'admin' : 'cliente';

            $_SESSION['usuario_id'] = $usuario['ID'];
            $_SESSION['usuario_nombre'] = $usuario['NOMBRE'];
            $_SESSION['usuario_rol'] = $rol;
            $_SESSION['usuario_cod_client_individual'] = $usuario['COD_CLIENT'];

            // ================== INICIO DE LA LÓGICA DE AGRUPACIÓN POR COD_GVA62 ==================
            if ($rol === 'cliente' && !empty($usuario['COD_CLIENT'])) {
                
                // 2. Con el COD_CLIENT del usuario, buscamos su código de grupo (COD_GVA62) en GVA14
                $sql_grupo = "SELECT COD_GVA62, RAZON_SOCI FROM GVA14 WHERE COD_CLIENT = ?";
                $stmt_grupo = sqlsrv_query($conn, $sql_grupo, [$usuario['COD_CLIENT']]);
                
                $codigo_grupo = null;
                if ($stmt_grupo && $row_grupo = sqlsrv_fetch_array($stmt_grupo, SQLSRV_FETCH_ASSOC)) {
                    $codigo_grupo = $row_grupo['COD_GVA62'];
                    // Guardamos la razón social del local específico con el que se logueó
                    $_SESSION['razon_social'] = $row_grupo['RAZON_SOCI']; 
                }

                // 3. Si encontramos el código de grupo, buscamos TODOS los locales asociados a él.
                if ($codigo_grupo) {
                    $sql_locales = "SELECT COD_CLIENT FROM GVA14 WHERE COD_GVA62 = ?";
                    $stmt_locales = sqlsrv_query($conn, $sql_locales, [$codigo_grupo]);
                    
                    $codigos_locales = [];
                    if ($stmt_locales) {
                        while ($row = sqlsrv_fetch_array($stmt_locales, SQLSRV_FETCH_ASSOC)) {
                            $codigos_locales[] = $row['COD_CLIENT'];
                        }
                    }
                    $_SESSION['codigos_cliente_agrupados'] = !empty($codigos_locales) ? $codigos_locales : [$usuario['COD_CLIENT']];
                } else {
                    // Si no se encuentra un código de grupo, el "grupo" es solo el local individual.
                    $_SESSION['codigos_cliente_agrupados'] = [$usuario['COD_CLIENT']];
                }
            }
            // =================== FIN DE LA LÓGICA DE AGRUPACIÓN POR COD_GVA62 ====================

            // Lógica de vencimientos (recibe el array correcto)
            if ($rol === 'cliente' && !empty($_SESSION['codigos_cliente_agrupados'])) {
                require_once __DIR__ . '/vencimientos_controller.php';
                $conn_apps = Database::getConnection('apps');
                
                verificarYActualizarVencimientosCliente($conn_apps, $_SESSION['codigos_cliente_agrupados']);
            }

            echo json_encode(['success' => true, 'rol' => $rol]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
} elseif ($action === 'logout') {
    session_destroy();
    header('Location: ../login.php');
    exit;
}
?>