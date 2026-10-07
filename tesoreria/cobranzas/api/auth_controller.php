<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'login_externo') {
    // Si ya existe sesión activa desde el Hub central (FP_SOF_USUARIOS)
    $authCentralUser = $_SESSION['fp_auth_user'] ?? null;
    $nombre = $_GET['user'] ?? ($authCentralUser['username'] ?? '');

    if (empty($nombre)) {
        header('Location: ../login.php');
        exit;
    }

    try {
        $usuario = null;
        $esFpSof = false;
        
        // 1. Intentar buscar primero en la base central de aplicaciones (FP_SOF_USUARIOS)
        try {
            $connApps = Database::getConnection('apps');
            if ($connApps) {
                $sqlFp = "SELECT u.id, u.username, u.nombre_completo, u.categoria_usuario, u.tipo, 
                                 u.cod_client, u.rol_id, r.codigo as rol_codigo, r.es_admin
                          FROM FP_SOF_USUARIOS u
                          LEFT JOIN FP_ROLES_PERMISOS_MAP r ON r.id = u.rol_id
                          WHERE (u.username = ? OR CAST(u.id AS VARCHAR) = ?) AND u.activo = 1";
                $stmtFp = sqlsrv_query($connApps, $sqlFp, [$nombre, $nombre]);
                if ($stmtFp && ($rowFp = sqlsrv_fetch_array($stmtFp, SQLSRV_FETCH_ASSOC))) {
                    $esAdminFp = !empty($rowFp['es_admin']) 
                        || (int)($rowFp['rol_id'] ?? 0) === 1
                        || (int)($rowFp['rol_id'] ?? 0) === 6 // Dirección
                        || (int)($rowFp['rol_id'] ?? 0) === 23 // Director de Operaciones
                        || strtoupper(trim($rowFp['rol_codigo'] ?? '')) === 'CONTROLTOTAL'
                        || strtoupper(trim($rowFp['rol_codigo'] ?? '')) === 'DIRECCION';

                    $tipoStr = strtoupper(trim($rowFp['tipo'] ?? ''));
                    $catStr = strtoupper(trim($rowFp['categoria_usuario'] ?? ''));

                    $usuario = [
                        'ID' => $rowFp['id'],
                        'NOMBRE' => $rowFp['username'],
                        'NOMBRE_COMPLETO' => $rowFp['nombre_completo'],
                        'COD_CLIENT' => trim($rowFp['cod_client'] ?? ''),
                        'TIPO' => $tipoStr ?: ($esAdminFp ? 'SUPERVISION' : 'PERSONAL'),
                        'ES_ADMIN' => $esAdminFp,
                        'CATEGORIA' => $catStr
                    ];
                    $esFpSof = true;
                }
            }
        } catch (\Throwable $thApps) {
            // Silently fallback a SOF_USUARIOS
        }

        // 2. Si no se encontró en FP_SOF_USUARIOS, buscar en la tabla histórica SOF_USUARIOS (central)
        $conn = Database::getConnection('central');
        if (!$usuario) {
            $sql_usuario = "SELECT ID, NOMBRE, COD_CLIENT, TIPO FROM SOF_USUARIOS WHERE NOMBRE = ?";
            $stmt_usuario = sqlsrv_query($conn, $sql_usuario, [$nombre]);

            if ($stmt_usuario && ($uHist = sqlsrv_fetch_array($stmt_usuario, SQLSRV_FETCH_ASSOC))) {
                $usuario = $uHist;
            }
        }

        if ($usuario) {
            $nombreLower = strtolower(trim($usuario['NOMBRE']));
            if (in_array($nombreLower, ['valeria', 'vvillarreal']) || (!empty($usuario['CATEGORIA']) && $usuario['CATEGORIA'] === 'MAYORISTA')) {
                $rol = 'mayoristas';
            } elseif (!empty($usuario['ES_ADMIN']) || strtoupper($usuario['TIPO']) === 'SUPERVISION' || empty($usuario['COD_CLIENT'])) {
                $rol = 'admin';
            } else {
                $rol = 'cliente';
            }
            
            $_SESSION['usuario_id'] = $usuario['ID'];
            $_SESSION['usuario_nombre'] = $usuario['NOMBRE'];
            $_SESSION['usuario_rol'] = $rol;
            $_SESSION['usuario_cod_client_individual'] = $usuario['COD_CLIENT'];
            $_SESSION['origen'] = 'grupo';

            if ($rol === 'cliente' && !empty($usuario['COD_CLIENT'])) {
                $sql_grupo = "SELECT COD_GVA62, RAZON_SOCI FROM GVA14 WHERE COD_CLIENT = ?";
                $stmt_grupo = sqlsrv_query($conn, $sql_grupo, [$usuario['COD_CLIENT']]);
                if ($stmt_grupo && $row_grupo = sqlsrv_fetch_array($stmt_grupo, SQLSRV_FETCH_ASSOC)) {
                    $_SESSION['razon_social'] = $row_grupo['RAZON_SOCI']; 
                    $codigo_grupo = $row_grupo['COD_GVA62'];

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
            }

            if ($rol === 'cliente' && !empty($_SESSION['codigos_cliente_agrupados'])) {
                require_once __DIR__ . '/vencimientos_controller.php';
                $conn_apps = Database::getConnection('apps');
                verificarYActualizarVencimientosCliente($conn_apps, $_SESSION['codigos_cliente_agrupados']);
            }

            if ($rol === 'mayoristas') {
                header('Location: ../mayoristas.php');
            } elseif ($rol === 'admin') {
                header('Location: ../index.php');
            } else {
                header('Location: ../portal_cliente.php');
            }
            exit;
        } else {
            header('Location: ../login.php?error=usuario_no_encontrado');
            exit;
        }
    } catch (Exception $e) {
        die("Error de autenticación externa: " . $e->getMessage());
    }
}

if ($action === 'login') {
    $nombre = $_POST['nombre'] ?? '';
    $pass = $_POST['pass'] ?? '';

    if (empty($nombre) || empty($pass)) {
        echo json_encode(['success' => false, 'message' => 'Usuario y contraseña son requeridos.']);
        exit;
    }

    try {
        $usuario = null;
        $esFpSof = false;

        // 1. Intentar validar primero contra la base central (FP_SOF_USUARIOS)
        try {
            $connApps = Database::getConnection('apps');
            if ($connApps) {
                $sqlFp = "SELECT u.id, u.username, u.password_plain, u.password, u.nombre_completo, 
                                 u.categoria_usuario, u.tipo, u.cod_client, u.rol_id, 
                                 r.codigo as rol_codigo, r.es_admin
                          FROM FP_SOF_USUARIOS u
                          LEFT JOIN FP_ROLES_PERMISOS_MAP r ON r.id = u.rol_id
                          WHERE u.username = ? AND u.activo = 1";
                $stmtFp = sqlsrv_query($connApps, $sqlFp, [$nombre]);
                if ($stmtFp && ($rowFp = sqlsrv_fetch_array($stmtFp, SQLSRV_FETCH_ASSOC))) {
                    $passSql = trim($rowFp['password_plain'] ?? $rowFp['password'] ?? '');
                    if ($passSql !== '' && $passSql === $pass) {
                        $esAdminFp = !empty($rowFp['es_admin']) 
                            || (int)($rowFp['rol_id'] ?? 0) === 1
                            || (int)($rowFp['rol_id'] ?? 0) === 6
                            || (int)($rowFp['rol_id'] ?? 0) === 23
                            || strtoupper(trim($rowFp['rol_codigo'] ?? '')) === 'CONTROLTOTAL'
                            || strtoupper(trim($rowFp['rol_codigo'] ?? '')) === 'DIRECCION';

                        $tipoStr = strtoupper(trim($rowFp['tipo'] ?? ''));
                        $catStr = strtoupper(trim($rowFp['categoria_usuario'] ?? ''));

                        $usuario = [
                            'ID' => $rowFp['id'],
                            'NOMBRE' => $rowFp['username'],
                            'NOMBRE_COMPLETO' => $rowFp['nombre_completo'],
                            'COD_CLIENT' => trim($rowFp['cod_client'] ?? ''),
                            'TIPO' => $tipoStr ?: ($esAdminFp ? 'SUPERVISION' : 'PERSONAL'),
                            'ES_ADMIN' => $esAdminFp,
                            'CATEGORIA' => $catStr
                        ];
                        $esFpSof = true;
                    }
                }
            }
        } catch (\Throwable $thApps) {
            // Silently fallback
        }

        // 2. Si no se validó con FP_SOF_USUARIOS, consultar SOF_USUARIOS histórica
        $conn = Database::getConnection('central');
        if (!$usuario) {
            $sql_usuario = "SELECT ID, NOMBRE, COD_CLIENT, TIPO FROM SOF_USUARIOS WHERE NOMBRE = ? AND PASS = ?";
            $params_usuario = [$nombre, $pass];
            $stmt_usuario = sqlsrv_query($conn, $sql_usuario, $params_usuario);

            if ($stmt_usuario === false) {
                throw new Exception("Error al consultar el usuario.");
            }
            if ($uHist = sqlsrv_fetch_array($stmt_usuario, SQLSRV_FETCH_ASSOC)) {
                $usuario = $uHist;
            }
        }

        if ($usuario) {
            $nombreLower = strtolower(trim($usuario['NOMBRE']));
            if (in_array($nombreLower, ['valeria', 'vvillarreal']) || (!empty($usuario['CATEGORIA']) && $usuario['CATEGORIA'] === 'MAYORISTA')) {
                $rol = 'mayoristas';
            } elseif (!empty($usuario['ES_ADMIN']) || strtoupper($usuario['TIPO']) === 'SUPERVISION' || empty($usuario['COD_CLIENT'])) {
                $rol = 'admin';
            } else {
                $rol = 'cliente';
            }

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
    $origen = $_SESSION['origen'] ?? '';
    session_destroy();
    if ($origen === 'grupo') {
        header('Location: https://app.xl.com.ar/sistemas/login.php');
    } else {
        header('Location: ../login.php');
    }
    exit;
}
?>