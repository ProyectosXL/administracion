<?php
session_start();
require_once 'config/database.php';

$cliente_param = $_GET['cliente'] ?? '';

if (empty($cliente_param)) {
    header('Location: login.php');
    exit;
}

try {
    $conn = Database::getConnection('central');

    // Buscamos el usuario en SOF_USUARIOS que coincide con el cliente pasado, asumiendo que es de TIPO GRUPO
    $sql_usuario = "SELECT ID, NOMBRE, COD_CLIENT, TIPO FROM SOF_USUARIOS WHERE COD_CLIENT = ? AND TIPO = 'GRUPO'";
    $stmt_usuario = sqlsrv_query($conn, $sql_usuario, [$cliente_param]);

    if ($stmt_usuario === false) {
        throw new Exception("Error al consultar el usuario.");
    }
    
    $usuario = sqlsrv_fetch_array($stmt_usuario, SQLSRV_FETCH_ASSOC);

    if ($usuario) {
        $rol = 'cliente';

        // Iniciamos sesión con los datos del grupo
        $_SESSION['usuario_id'] = $usuario['ID'];
        $_SESSION['usuario_nombre'] = $usuario['NOMBRE'];
        $_SESSION['usuario_rol'] = $rol;
        $_SESSION['usuario_cod_client_individual'] = $usuario['COD_CLIENT']; // Ej: 'RIPOLL'
        
        // Asignamos una razón social genérica o podemos buscar la descripción en la tabla (ej. ALBERTO RIPOLL)
        $_SESSION['razon_social'] = $usuario['NOMBRE']; 
        
        // --- NUEVO: MARCAMOS EL ORIGEN PARA REDIRIGIR AL CERRAR SESIÓN ---
        $_SESSION['origen'] = 'grupo';

        // En lugar de buscar COD_GVA62 a través del COD_CLIENT de un local, 
        // asumimos que el COD_CLIENT del grupo (ej. 'RIPOLL') **ES** el COD_GVA62 en GVA14.
        $codigo_grupo = $usuario['COD_CLIENT'];
        
        $sql_locales = "SELECT COD_CLIENT FROM GVA14 WHERE COD_GVA62 = ?";
        $stmt_locales = sqlsrv_query($conn, $sql_locales, [$codigo_grupo]);
        
        $codigos_locales = [];
        if ($stmt_locales) {
            while ($row = sqlsrv_fetch_array($stmt_locales, SQLSRV_FETCH_ASSOC)) {
                $codigos_locales[] = $row['COD_CLIENT'];
            }
        }
        
        // Si por alguna razón GVA14 no tiene locales bajo ese COD_GVA62, usamos el código enviado por defecto
        $_SESSION['codigos_cliente_agrupados'] = !empty($codigos_locales) ? $codigos_locales : [$codigo_grupo];

        // Lógica de vencimientos
        if (!empty($_SESSION['codigos_cliente_agrupados'])) {
            require_once __DIR__ . '/api/vencimientos_controller.php';
            $conn_apps = Database::getConnection('apps');
            verificarYActualizarVencimientosCliente($conn_apps, $_SESSION['codigos_cliente_agrupados']);
        }

        // Redirigimos al portal del cliente
        header('Location: portal_cliente.php');
        exit;
    } else {
        // En caso de que no exista el usuario grupo, podríamos intentar un login tradicional
        // Pero para este caso específico de portal, lo mandamos al login local con error.
        header('Location: login.php?error=Usuario_no_valido');
        exit;
    }

} catch (Exception $e) {
    error_log("Error en seleccionar_sucursal.php: " . $e->getMessage());
    header('Location: login.php?error=db_error');
    exit;
}
?>
