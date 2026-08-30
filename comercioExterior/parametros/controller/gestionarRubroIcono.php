<?php
/**
 * ABM de iconos por rubro para los badges del cronograma.
 *
 * ICONO acepta dos formatos: una clase de bootstrap-icons ('bi-gem') o un
 * emoji suelto. La columna es NVARCHAR porque en VARCHAR los emoji se
 * perderian.
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
            // El COLLATE DATABASE_DEFAULT de los dos lados es obligatorio:
            // SOF_RUBROS_TANGO.RUBRO es Modern_Spanish_CI_AI en central pero
            // Latin1_General_BIN en uy, asi que sin esto el join revienta en
            // uy con conflicto de collation.
            $sql = "SELECT I.ID, I.RUBRO, I.ICONO, I.ORDEN, I.ACTIVO,
                           ISNULL(T.ARTICULOS, 0) AS ARTICULOS
                    FROM RO_T_IMPORTACIONES_RUBRO_ICONO I
                    LEFT JOIN (
                        SELECT RUBRO, COUNT(*) AS ARTICULOS
                        FROM SOF_RUBROS_TANGO GROUP BY RUBRO
                    ) T ON I.RUBRO COLLATE DATABASE_DEFAULT = T.RUBRO COLLATE DATABASE_DEFAULT
                    ORDER BY I.ORDEN, I.RUBRO";

            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                throw new Exception('Error al listar iconos: ' . print_r(sqlsrv_errors(), true));
            }

            $filas = [];
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $r['ID']        = (int) $r['ID'];
                $r['ORDEN']     = (int) $r['ORDEN'];
                $r['ACTIVO']    = (int) $r['ACTIVO'];
                $r['ARTICULOS'] = (int) $r['ARTICULOS'];
                $filas[] = $r;
            }

            // Rubros que existen en Tango pero no tienen icono mapeado.
            $sqlFaltan = "SELECT T.RUBRO, COUNT(*) AS ARTICULOS
                          FROM SOF_RUBROS_TANGO T
                          LEFT JOIN RO_T_IMPORTACIONES_RUBRO_ICONO I
                                 ON T.RUBRO COLLATE DATABASE_DEFAULT = I.RUBRO COLLATE DATABASE_DEFAULT
                          WHERE I.ID IS NULL AND T.RUBRO IS NOT NULL
                          GROUP BY T.RUBRO
                          ORDER BY COUNT(*) DESC";

            $sinMapear = [];
            $stmt = sqlsrv_query($conn, $sqlFaltan);
            if ($stmt !== false) {
                while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $sinMapear[] = ['RUBRO' => $r['RUBRO'], 'ARTICULOS' => (int) $r['ARTICULOS']];
                }
            }

            echo json_encode([
                'success'   => true,
                'entorno'   => $db,
                'data'      => $filas,
                'sinMapear' => $sinMapear,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ---------------------------------------------------------------
        case 'guardar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $rubro  = isset($_POST['rubro']) ? trim($_POST['rubro']) : '';
            $icono  = isset($_POST['icono']) ? trim($_POST['icono']) : '';
            $orden  = isset($_POST['orden']) ? (int) $_POST['orden'] : 0;
            $activo = isset($_POST['activo']) ? (int) $_POST['activo'] : 1;

            if ($rubro === '') {
                throw new Exception('El rubro es obligatorio');
            }
            if (mb_strlen($rubro) > 40) {
                throw new Exception('El rubro no puede superar los 40 caracteres');
            }
            if ($icono === '') {
                throw new Exception('El icono es obligatorio');
            }
            if (mb_strlen($icono) > 60) {
                throw new Exception('El icono no puede superar los 60 caracteres');
            }

            // Si dice ser una clase de bootstrap-icons, que tenga forma de
            // tal: asi un typo se detecta acá y no como un cuadrado vacío en
            // el calendario.
            if (strpos($icono, 'bi-') === 0 && !preg_match('/^bi-[a-z0-9-]+$/', $icono)) {
                throw new Exception('La clase de icono solo admite minúsculas, números y guiones (ej: bi-handbag-fill)');
            }

            if ($id > 0) {
                $ok = sqlsrv_query($conn,
                    "UPDATE RO_T_IMPORTACIONES_RUBRO_ICONO
                     SET RUBRO = ?, ICONO = ?, ORDEN = ?, ACTIVO = ?
                     WHERE ID = ?",
                    [$rubro, $icono, $orden, $activo, $id]);
            } else {
                $stmt = sqlsrv_query($conn,
                    "SELECT ID FROM RO_T_IMPORTACIONES_RUBRO_ICONO WHERE RUBRO = ?", [$rubro]);
                if ($stmt !== false && sqlsrv_fetch($stmt)) {
                    throw new Exception('El rubro "' . $rubro . '" ya tiene un icono asignado');
                }

                $ok = sqlsrv_query($conn,
                    "INSERT INTO RO_T_IMPORTACIONES_RUBRO_ICONO (RUBRO, ICONO, ORDEN, ACTIVO)
                     VALUES (?, ?, ?, ?)",
                    [$rubro, $icono, $orden, $activo]);
            }

            if ($ok === false) {
                throw new Exception('Error al guardar: ' . print_r(sqlsrv_errors(), true));
            }

            echo json_encode([
                'success' => true,
                'message' => 'Icono guardado correctamente',
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ---------------------------------------------------------------
        case 'eliminar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                throw new Exception('Falta el identificador');
            }

            $ok = sqlsrv_query($conn,
                "DELETE FROM RO_T_IMPORTACIONES_RUBRO_ICONO WHERE ID = ?", [$id]);

            if ($ok === false) {
                throw new Exception('Error al eliminar: ' . print_r(sqlsrv_errors(), true));
            }

            echo json_encode(['success' => true, 'message' => 'Icono eliminado'], JSON_UNESCAPED_UNICODE);
            break;

        // ---------------------------------------------------------------
        default:
            throw new Exception('Acción no reconocida: ' . $accion);
    }

} catch (Exception $e) {
    error_log('gestionarRubroIcono: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
