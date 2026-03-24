
<?php
// tesoreria/cobranzas/api/sucursales_grupo_controller.php
header('Content-Type: application/json');
require_once '../config/database.php';

$cod_client = isset($_GET['cod_client']) ? trim($_GET['cod_client']) : '';

if (empty($cod_client)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'cod_client requerido.']);
    exit;
}

try {
    $conn = Database::getConnection('central');

    // Primero verificamos si el código recibido es un GRUPO_EMPR en GVA62
    $sql_es_grupo = "SELECT GRUPO_EMPR, NOMBRE_GRU FROM GVA62 WHERE GRUPO_EMPR = ?";
    $stmt_es_grupo = sqlsrv_query($conn, $sql_es_grupo, [$cod_client]);
    $es_grupo_directo = false;
    $nombre_grupo = '';

    if ($stmt_es_grupo && $row_g = sqlsrv_fetch_array($stmt_es_grupo, SQLSRV_FETCH_ASSOC)) {
        $es_grupo_directo = true;
        $nombre_grupo = trim($row_g['NOMBRE_GRU'] ?? '');
    }

    if ($es_grupo_directo) {
        // El código recibido ES un GRUPO_EMPR: traemos todas sus sucursales
        $sql_sucursales = "
            SELECT A.COD_CLIENT, C.DESC_SUCURSAL, B.GRUPO_EMPR, B.NOMBRE_GRU
            FROM GVA14 A
            LEFT JOIN GVA62 B ON A.GRUPO_EMPR = B.GRUPO_EMPR
            LEFT JOIN [XL-LAKERBIS].LOCALES_LAKERS.dbo.SUCURSALES_LAKERS C
                ON A.COD_CLIENT = C.COD_CLIENT COLLATE Modern_Spanish_CI_AI
            WHERE A.GRUPO_EMPR = ?
              AND A.COD_CLIENT LIKE 'F%'
              AND C.HABILITADO = 1
              AND C.NRO_SUC_MADRE IS NULL
            GROUP BY A.COD_CLIENT, C.DESC_SUCURSAL, B.GRUPO_EMPR, B.NOMBRE_GRU
            ORDER BY C.DESC_SUCURSAL
        ";
        $stmt_sucursales = sqlsrv_query($conn, $sql_sucursales, [$cod_client]);

        if ($stmt_sucursales === false) {
            throw new Exception("Error al consultar sucursales del grupo: " . print_r(sqlsrv_errors(), true));
        }

        $sucursales = [];
        while ($row = sqlsrv_fetch_array($stmt_sucursales, SQLSRV_FETCH_ASSOC)) {
            $sucursales[] = [
                'cod_client'    => trim($row['COD_CLIENT']),
                'desc_sucursal' => trim($row['DESC_SUCURSAL'] ?? $row['COD_CLIENT']),
                'nombre_grupo'  => trim($row['NOMBRE_GRU'] ?? ''),
            ];
        }

        echo json_encode([
            'success'      => true,
            'data'         => $sucursales,
            'es_grupo'     => true,
            'nombre_grupo' => $nombre_grupo,
        ]);
        exit;
    }

    // Si no es GRUPO_EMPR, lo tratamos como COD_CLIENT individual
    // Verificamos si ese COD_CLIENT pertenece a algún grupo
    $sql_grupo = "
        SELECT A.GRUPO_EMPR, B.NOMBRE_GRU
        FROM GVA14 A
        LEFT JOIN GVA62 B ON A.GRUPO_EMPR = B.GRUPO_EMPR
        WHERE A.COD_CLIENT = ?
    ";
    $stmt_grupo = sqlsrv_query($conn, $sql_grupo, [$cod_client]);

    if ($stmt_grupo === false) {
        throw new Exception("Error al consultar grupo del cliente: " . print_r(sqlsrv_errors(), true));
    }

    $row_grupo = sqlsrv_fetch_array($stmt_grupo, SQLSRV_FETCH_ASSOC);

    if ($row_grupo && !empty($row_grupo['GRUPO_EMPR'])) {
        // Tiene grupo: traemos todas las sucursales del grupo
        $grupo_empr   = $row_grupo['GRUPO_EMPR'];
        $nombre_grupo = trim($row_grupo['NOMBRE_GRU'] ?? '');

        $sql_sucursales = "
            SELECT A.COD_CLIENT, C.DESC_SUCURSAL, B.GRUPO_EMPR, B.NOMBRE_GRU
            FROM GVA14 A
            LEFT JOIN GVA62 B ON A.GRUPO_EMPR = B.GRUPO_EMPR
            LEFT JOIN [XL-LAKERBIS].LOCALES_LAKERS.dbo.SUCURSALES_LAKERS C
                ON A.COD_CLIENT = C.COD_CLIENT COLLATE Modern_Spanish_CI_AI
            WHERE A.GRUPO_EMPR = ?
              AND A.COD_CLIENT LIKE 'F%'
              AND C.HABILITADO = 1
              AND C.NRO_SUC_MADRE IS NULL
            GROUP BY A.COD_CLIENT, C.DESC_SUCURSAL, B.GRUPO_EMPR, B.NOMBRE_GRU
            ORDER BY C.DESC_SUCURSAL
        ";
        $stmt_sucursales = sqlsrv_query($conn, $sql_sucursales, [$grupo_empr]);

        if ($stmt_sucursales === false) {
            throw new Exception("Error al consultar sucursales: " . print_r(sqlsrv_errors(), true));
        }

        $sucursales = [];
        while ($row = sqlsrv_fetch_array($stmt_sucursales, SQLSRV_FETCH_ASSOC)) {
            $sucursales[] = [
                'cod_client'    => trim($row['COD_CLIENT']),
                'desc_sucursal' => trim($row['DESC_SUCURSAL'] ?? $row['COD_CLIENT']),
                'nombre_grupo'  => $nombre_grupo,
            ];
        }

        echo json_encode([
            'success'      => true,
            'data'         => $sucursales,
            'es_grupo'     => true,
            'nombre_grupo' => $nombre_grupo,
        ]);

    } else {
        // No tiene grupo: cliente individual con una sola sucursal
        $sql_single = "
            SELECT A.COD_CLIENT, C.DESC_SUCURSAL
            FROM GVA14 A
            LEFT JOIN [XL-LAKERBIS].LOCALES_LAKERS.dbo.SUCURSALES_LAKERS C
                ON A.COD_CLIENT = C.COD_CLIENT COLLATE Modern_Spanish_CI_AI
            WHERE A.COD_CLIENT = ?
              AND C.HABILITADO = 1
              AND C.NRO_SUC_MADRE IS NULL
        ";
        $stmt_single = sqlsrv_query($conn, $sql_single, [$cod_client]);

        $sucursales = [];
        if ($stmt_single) {
            while ($r = sqlsrv_fetch_array($stmt_single, SQLSRV_FETCH_ASSOC)) {
                $sucursales[] = [
                    'cod_client'    => trim($r['COD_CLIENT']),
                    'desc_sucursal' => trim($r['DESC_SUCURSAL'] ?? $r['COD_CLIENT']),
                    'nombre_grupo'  => null,
                ];
            }
        }

        if (empty($sucursales)) {
            $sucursales[] = [
                'cod_client'    => $cod_client,
                'desc_sucursal' => $cod_client,
                'nombre_grupo'  => null,
            ];
        }

        echo json_encode([
            'success'      => true,
            'data'         => $sucursales,
            'es_grupo'     => false,
            'nombre_grupo' => '',
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}