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
        // CONSULTA SEGURA para prevenir inyección SQL
        $sql = "SELECT ID, NOMBRE, COD_CLIENT, TIPO FROM SOF_USUARIOS WHERE NOMBRE = ? AND PASS = ?";
        $params = [$nombre, $pass];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            throw new Exception("Error en la consulta de autenticación.");
        }

        $usuario = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($usuario) {
            // Definimos el rol del usuario. ASUMIMOS que "SUPERVISION" es un admin.
            // Si el TIPO es FRANQUICIA o LOCAL_PROPIO, es un cliente.
            $rol = 'cliente'; // Rol por defecto
            if (strtoupper($usuario['TIPO']) === 'SUPERVISION' || empty($usuario['COD_CLIENT'])) {
                 $rol = 'admin';
            }

            // Guardamos los datos en la sesión
            $_SESSION['usuario_id'] = $usuario['ID'];
            $_SESSION['usuario_nombre'] = $usuario['NOMBRE'];
            $_SESSION['usuario_cod_client'] = $usuario['COD_CLIENT'];
            $_SESSION['usuario_rol'] = $rol;

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