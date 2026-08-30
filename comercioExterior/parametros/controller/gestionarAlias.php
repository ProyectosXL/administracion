<?php
/**
 * ABM de alias de proveedor (2 letras) para los badges del cronograma.
 *
 * Lo consumen la pestaña Alias de Parámetros y el modal del cronograma, que
 * usan exactamente los mismos endpoints.
 */
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

try {
    require_once __DIR__ . '/../../../class/conexion.php';

    $cid  = new Conexion();
    $db   = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);
    if (!$conn) {
        throw new Exception('No se pudo establecer conexión a la base de datos');
    }

    $accion = isset($_POST['accion']) ? $_POST['accion'] : (isset($_GET['accion']) ? $_GET['accion'] : 'listar');

    switch ($accion) {

        // ---------------------------------------------------------------
        case 'listar':
            // LEFT JOIN contra los proveedores que realmente aparecen en el
            // cronograma, para poder mostrar cuantos contenedores usa cada
            // alias y detectar los que quedaron sin uso.
            $sql = "SELECT A.COD_PROVEE, A.PROVEEDOR, A.ALIAS, A.ACTIVO,
                           A.USUARIO, A.FECHA_ALTA, A.FECHA_MOD,
                           ISNULL(P.OCS, 0) AS OCS
                    FROM RO_T_IMPORTACIONES_PROVEEDOR_ALIAS A
                    LEFT JOIN (
                        SELECT COD_PROVEE, COUNT(*) AS OCS
                        FROM RO_T_IMPORTACIONES_ENCABEZADO
                        WHERE FECHA_MOV >= GETDATE()-360
                        GROUP BY COD_PROVEE
                    ) P ON A.COD_PROVEE = P.COD_PROVEE
                    ORDER BY A.ALIAS";

            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                throw new Exception('Error al listar alias: ' . print_r(sqlsrv_errors(), true));
            }

            $filas = [];
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                foreach (['FECHA_ALTA', 'FECHA_MOD'] as $c) {
                    if ($r[$c] instanceof DateTime) $r[$c] = $r[$c]->format('Y-m-d H:i');
                }
                $r['ACTIVO'] = (int) $r['ACTIVO'];
                $r['OCS']    = (int) $r['OCS'];
                $filas[] = $r;
            }
            echo json_encode(['success' => true, 'entorno' => $db, 'data' => $filas], JSON_UNESCAPED_UNICODE);
            break;

        // ---------------------------------------------------------------
        case 'proveedores':
            // Proveedores del cronograma, marcando cuales ya tienen alias.
            $sql = "SELECT E.COD_PROVEE,
                           MAX(E.PROVEEDOR) AS PROVEEDOR,
                           COUNT(*) AS OCS,
                           MAX(CASE WHEN A.COD_PROVEE IS NULL THEN 0 ELSE 1 END) AS TIENE_ALIAS
                    FROM RO_T_IMPORTACIONES_ENCABEZADO E
                    LEFT JOIN RO_T_IMPORTACIONES_PROVEEDOR_ALIAS A
                           ON E.COD_PROVEE = A.COD_PROVEE
                    WHERE E.FECHA_MOV >= GETDATE()-360
                      AND E.COD_PROVEE IS NOT NULL
                    GROUP BY E.COD_PROVEE
                    ORDER BY MAX(E.PROVEEDOR)";

            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                throw new Exception('Error al listar proveedores: ' . print_r(sqlsrv_errors(), true));
            }

            $filas = [];
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $r['OCS']         = (int) $r['OCS'];
                $r['TIENE_ALIAS'] = (int) $r['TIENE_ALIAS'];
                $filas[] = $r;
            }
            echo json_encode(['success' => true, 'data' => $filas], JSON_UNESCAPED_UNICODE);
            break;

        // ---------------------------------------------------------------
        case 'guardar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $codProvee = isset($_POST['cod_provee']) ? trim($_POST['cod_provee']) : '';
            $aliasIn   = isset($_POST['alias']) ? trim($_POST['alias']) : '';
            $activo    = isset($_POST['activo']) ? (int) $_POST['activo'] : 1;

            if ($codProvee === '') {
                throw new Exception('Falta el código de proveedor');
            }

            // Se normaliza a mayúscula acá y no solo en el front: el CHECK de
            // la tabla es binario y rechazaría una minúscula con un error de
            // base, mucho menos claro que este mensaje.
            $alias = strtoupper($aliasIn);

            if (!preg_match('/^[A-Z]{2}$/', $alias)) {
                throw new Exception('El alias debe ser exactamente 2 letras de la A a la Z, sin números ni símbolos');
            }

            // Unicidad: si ya lo usa otro proveedor, se dice cuál.
            $stmt = sqlsrv_query($conn,
                "SELECT COD_PROVEE, PROVEEDOR FROM RO_T_IMPORTACIONES_PROVEEDOR_ALIAS
                 WHERE ALIAS = ? AND COD_PROVEE <> ?",
                [$alias, $codProvee]);

            if ($stmt === false) {
                throw new Exception('Error al validar el alias: ' . print_r(sqlsrv_errors(), true));
            }
            if ($duenio = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                throw new Exception(
                    'El alias "' . $alias . '" ya lo usa ' .
                    trim($duenio['PROVEEDOR'] ?: $duenio['COD_PROVEE']) .
                    ' (' . trim($duenio['COD_PROVEE']) . ')'
                );
            }

            // El nombre se toma del encabezado, para no depender de que el
            // front lo mande bien.
            $stmt = sqlsrv_query($conn,
                "SELECT MAX(PROVEEDOR) AS PROVEEDOR FROM RO_T_IMPORTACIONES_ENCABEZADO
                 WHERE COD_PROVEE = ?", [$codProvee]);
            $nombre = null;
            if ($stmt !== false && ($f = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
                $nombre = $f['PROVEEDOR'];
            }

            $stmt = sqlsrv_query($conn,
                "SELECT COD_PROVEE FROM RO_T_IMPORTACIONES_PROVEEDOR_ALIAS WHERE COD_PROVEE = ?",
                [$codProvee]);
            $existe = ($stmt !== false && sqlsrv_fetch($stmt));

            $usuario = isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null;

            if ($existe) {
                $ok = sqlsrv_query($conn,
                    "UPDATE RO_T_IMPORTACIONES_PROVEEDOR_ALIAS
                     SET ALIAS = ?, PROVEEDOR = ?, ACTIVO = ?, USUARIO = ?, FECHA_MOD = GETDATE()
                     WHERE COD_PROVEE = ?",
                    [$alias, $nombre, $activo, $usuario, $codProvee]);
            } else {
                $ok = sqlsrv_query($conn,
                    "INSERT INTO RO_T_IMPORTACIONES_PROVEEDOR_ALIAS
                     (COD_PROVEE, PROVEEDOR, ALIAS, ACTIVO, USUARIO)
                     VALUES (?, ?, ?, ?, ?)",
                    [$codProvee, $nombre, $alias, $activo, $usuario]);
            }

            if ($ok === false) {
                throw new Exception('Error al guardar: ' . print_r(sqlsrv_errors(), true));
            }

            echo json_encode([
                'success' => true,
                'message' => 'Alias ' . ($existe ? 'actualizado' : 'creado') . ' correctamente',
                'alias'   => $alias,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ---------------------------------------------------------------
        case 'eliminar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $codProvee = isset($_POST['cod_provee']) ? trim($_POST['cod_provee']) : '';
            if ($codProvee === '') {
                throw new Exception('Falta el código de proveedor');
            }

            $ok = sqlsrv_query($conn,
                "DELETE FROM RO_T_IMPORTACIONES_PROVEEDOR_ALIAS WHERE COD_PROVEE = ?",
                [$codProvee]);

            if ($ok === false) {
                throw new Exception('Error al eliminar: ' . print_r(sqlsrv_errors(), true));
            }

            echo json_encode(['success' => true, 'message' => 'Alias eliminado'], JSON_UNESCAPED_UNICODE);
            break;

        // ---------------------------------------------------------------
        default:
            throw new Exception('Acción no reconocida: ' . $accion);
    }

} catch (Exception $e) {
    error_log('gestionarAlias: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
