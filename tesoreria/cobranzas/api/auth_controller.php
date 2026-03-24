
<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// ── ACCESO DIRECTO DESDE PORTAL FRANQUICIADO ─────────────────────────────────
// Si el usuario ya está autenticado en franquicias/grupo y NO tiene sesión
// activa en cobranzas, la creamos automáticamente sin pedirle login.
if (
    isset($_SESSION['username'])
    && isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'GRUPO'
    && !isset($_SESSION['usuario_id'])
) {
    try {
        $conn = Database::getConnection('central');

        $sql_usuario = "SELECT ID, NOMBRE, COD_CLIENT, TIPO FROM SOF_USUARIOS WHERE NOMBRE = ?";
        $stmt_usuario = sqlsrv_query($conn, $sql_usuario, [$_SESSION['username']]);

        if ($stmt_usuario && $usuario = sqlsrv_fetch_array($stmt_usuario, SQLSRV_FETCH_ASSOC)) {
            $_SESSION['usuario_id']                    = $usuario['ID'];
            $_SESSION['usuario_nombre']                = $usuario['NOMBRE'];
            $_SESSION['usuario_rol']                   = 'cliente';
            $_SESSION['usuario_cod_client_individual'] = $usuario['COD_CLIENT'];

            $cod_client = $usuario['COD_CLIENT'];
            $sql_grupo  = "SELECT COD_GVA62, RAZON_SOCI FROM GVA14 WHERE COD_CLIENT = ?";
            $stmt_grupo = sqlsrv_query($conn, $sql_grupo, [$cod_client]);
            $codigo_grupo = null;

            if ($stmt_grupo && $row_grupo = sqlsrv_fetch_array($stmt_grupo, SQLSRV_FETCH_ASSOC)) {
                $codigo_grupo             = $row_grupo['COD_GVA62'];
                $_SESSION['razon_social'] = trim($row_grupo['RAZON_SOCI']);
            }

            if ($codigo_grupo) {
                $sql_locales  = "SELECT COD_CLIENT FROM GVA14 WHERE COD_GVA62 = ?";
                $stmt_locales = sqlsrv_query($conn, $sql_locales, [$codigo_grupo]);
                $codigos_locales = [];
                if ($stmt_locales) {
                    while ($row = sqlsrv_fetch_array($stmt_locales, SQLSRV_FETCH_ASSOC)) {
                        $codigos_locales[] = $row['COD_CLIENT'];
                    }
                }
                $_SESSION['codigos_cliente_agrupados'] = !empty($codigos_locales)
                    ? $codigos_locales
                    : [$cod_client];
            } else {
                $_SESSION['codigos_cliente_agrupados'] = [$cod_client];
            }

            require_once __DIR__ . '/vencimientos_controller.php';
            $conn_apps = Database::getConnection('apps');
            verificarYActualizarVencimientosCliente($conn_apps, $_SESSION['codigos_cliente_agrupados']);
        }
    } catch (Exception $e) {
        error_log("Error en acceso directo desde portal franquiciado: " . $e->getMessage());
    }
}
// ── FIN: Acceso directo ──────────────────────────────────────────────────────

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

            if ($rol === 'cliente' && !empty($usuario['COD_CLIENT'])) {
                
                $sql_grupo = "SELECT COD_GVA62, RAZON_SOCI FROM GVA14 WHERE COD_CLIENT = ?";
                $stmt_grupo = sqlsrv_query($conn, $sql_grupo, [$usuario['COD_CLIENT']]);
                
                $codigo_grupo = null;
                if ($stmt_grupo && $row_grupo = sqlsrv_fetch_array($stmt_grupo, SQLSRV_FETCH_ASSOC)) {
                    $codigo_grupo = $row_grupo['COD_GVA62'];
                    $_SESSION['razon_social'] = $row_grupo['RAZON_SOCI']; 
                }

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
                    $_SESSION['codigos_cliente_agrupados'] = [$usuario['COD_CLIENT']];
                }
            }

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