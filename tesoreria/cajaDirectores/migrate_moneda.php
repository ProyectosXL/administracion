<?php
/**
 * Migración: agrega columnas importe_dolares y moneda a ingresos y egresos.
 * EJECUTAR UNA SOLA VEZ y luego borrar este archivo.
 */
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../../../class/conexion.php';

$conexion = new Conexion();
$db = $conexion->conectar('apps');

if ($db === false) {
    die('<p style="color:red">Error al conectar con la base de datos APPS.</p>');
}

$pasos = [];

// Helper: ejecuta SQL y registra el resultado
function ejecutar($db, $label, $sql) {
    global $pasos;
    $stmt = sqlsrv_query($db, $sql);
    if ($stmt === false) {
        $errores = sqlsrv_errors();
        $msg = $errores[0]['message'] ?? 'Error desconocido';
        $pasos[] = ['ok' => false, 'label' => $label, 'msg' => $msg];
    } else {
        sqlsrv_free_stmt($stmt);
        $pasos[] = ['ok' => true, 'label' => $label, 'msg' => 'OK'];
    }
}

// Helper: verifica si una columna existe
function columnaExiste($db, $tabla, $columna) {
    $sql = "SELECT COUNT(*) as cnt
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = sqlsrv_query($db, $sql, [$tabla, $columna]);
    if ($stmt === false) return false;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    return ($row['cnt'] ?? 0) > 0;
}

// ── TABLA ingresos ──────────────────────────────────────────────────────────
if (!columnaExiste($db, 'ingresos', 'importe_dolares')) {
    ejecutar($db, 'ingresos: ADD importe_dolares',
        "ALTER TABLE ingresos ADD importe_dolares DECIMAL(18,2) NOT NULL CONSTRAINT DF_ingresos_importe_dolares DEFAULT 0");
} else {
    $pasos[] = ['ok' => true, 'label' => 'ingresos: importe_dolares', 'msg' => 'Ya existe, omitido'];
}

if (!columnaExiste($db, 'ingresos', 'moneda')) {
    ejecutar($db, 'ingresos: ADD moneda',
        "ALTER TABLE ingresos ADD moneda VARCHAR(3) NOT NULL CONSTRAINT DF_ingresos_moneda DEFAULT 'ARS'");
} else {
    $pasos[] = ['ok' => true, 'label' => 'ingresos: moneda', 'msg' => 'Ya existe, omitido'];
}

// ── TABLA egresos ───────────────────────────────────────────────────────────
if (!columnaExiste($db, 'egresos', 'importe_dolares')) {
    ejecutar($db, 'egresos: ADD importe_dolares',
        "ALTER TABLE egresos ADD importe_dolares DECIMAL(18,2) NOT NULL CONSTRAINT DF_egresos_importe_dolares DEFAULT 0");
} else {
    $pasos[] = ['ok' => true, 'label' => 'egresos: importe_dolares', 'msg' => 'Ya existe, omitido'];
}

if (!columnaExiste($db, 'egresos', 'moneda')) {
    ejecutar($db, 'egresos: ADD moneda',
        "ALTER TABLE egresos ADD moneda VARCHAR(3) NOT NULL CONSTRAINT DF_egresos_moneda DEFAULT 'ARS'");
} else {
    $pasos[] = ['ok' => true, 'label' => 'egresos: moneda', 'msg' => 'Ya existe, omitido'];
}

$todosOk = array_reduce($pasos, fn($carry, $p) => $carry && $p['ok'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración Moneda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container" style="max-width:600px">
        <h3>Migración: columnas importe_dolares / moneda</h3>
        <table class="table table-bordered mt-3">
            <thead class="table-dark">
                <tr><th>Paso</th><th>Resultado</th></tr>
            </thead>
            <tbody>
                <?php foreach ($pasos as $p): ?>
                <tr class="<?= $p['ok'] ? 'table-success' : 'table-danger' ?>">
                    <td><?= htmlspecialchars($p['label']) ?></td>
                    <td><?= htmlspecialchars($p['msg']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($todosOk): ?>
            <div class="alert alert-success">
                <strong>Migración completada.</strong> Ya podés borrar este archivo
                (<code>migrate_moneda.php</code>) del servidor.
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <strong>Uno o más pasos fallaron.</strong> Revisá los mensajes arriba.
            </div>
        <?php endif; ?>

        <a href="index.php" class="btn btn-primary">Volver a Caja Directores</a>
    </div>
</body>
</html>
