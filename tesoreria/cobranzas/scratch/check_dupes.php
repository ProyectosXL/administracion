<?php
require_once '../config/database.php';
try {
    $conn = Database::getConnection('central');
    $sql = "SELECT COD_CLIENT, COUNT(*) as cant FROM RO_T_PARAMETROS_DESC_CLIENTES GROUP BY COD_CLIENT HAVING COUNT(*) > 1";
    $stmt = sqlsrv_query($conn, $sql);
    $dups = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $dups[] = $row;
    }
    echo json_encode(['duplicates' => $dups]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
