<?php
/**
 * Migración segura para Sistema de Novedades RRHH
 * - Tablas nuevas con prefijo FP_
 * - Columnas nuevas nullable / con default en novedades
 * - Depuración segura de tipos_novedad
 */
require_once __DIR__ . '/../class/Database.php';

$db = Database::getInstance();

try {
    $db->beginTransaction();

    // 1. Ampliar precision de porcentaje_1 y porcentaje_2 en novedades (de DECIMAL(5,4) a DECIMAL(7,4))
    $db->query("
        IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'novedades' AND COLUMN_NAME = 'porcentaje_1')
        BEGIN
            ALTER TABLE novedades ALTER COLUMN porcentaje_1 DECIMAL(7,4) NULL;
        END
    ");
    $db->query("
        IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'novedades' AND COLUMN_NAME = 'porcentaje_2')
        BEGIN
            ALTER TABLE novedades ALTER COLUMN porcentaje_2 DECIMAL(7,4) NULL;
        END
    ");
    echo "OK: porcentaje_1 y porcentaje_2 ampliados a DECIMAL(7,4)\n";

    // 2. Agregar columnas nuevas a novedades si no existen
    $nuevasColumnas = [
        'es_global' => "ALTER TABLE novedades ADD es_global BIT NOT NULL DEFAULT 0",
        'lote_id' => "ALTER TABLE novedades ADD lote_id VARCHAR(36) NULL",
        'etapa_multiple' => "ALTER TABLE novedades ADD etapa_multiple VARCHAR(20) NULL",
        'convenio' => "ALTER TABLE novedades ADD convenio VARCHAR(20) NULL",
        'escala_sueldo_basico' => "ALTER TABLE novedades ADD escala_sueldo_basico VARCHAR(30) NULL",
        'aplica_plus' => "ALTER TABLE novedades ADD aplica_plus BIT NULL"
    ];

    foreach ($nuevasColumnas as $col => $alterSql) {
        $check = $db->query(
            "SELECT COUNT(*) AS existe FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'novedades' AND COLUMN_NAME = ?",
            [$col]
        )->fetch();
        if ((int)$check['existe'] === 0) {
            $db->query($alterSql);
            echo "OK: Columna '$col' agregada a novedades\n";
        } else {
            echo "SKIP: Columna '$col' ya existe en novedades\n";
        }
    }

    // 3. Crear tabla FP_NOVEDADES_COMISION_SUCURSALES
    $db->query("
        IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='FP_NOVEDADES_COMISION_SUCURSALES' AND xtype='U')
        CREATE TABLE FP_NOVEDADES_COMISION_SUCURSALES (
            id INT IDENTITY(1,1) PRIMARY KEY,
            novedad_id INT NOT NULL,
            nro_sucursal INT NOT NULL,
            nombre_sucursal VARCHAR(100) NOT NULL,
            porcentaje_1 DECIMAL(7,4) NOT NULL,
            tiene_tope BIT NOT NULL DEFAULT 0,
            porcentaje_2 DECIMAL(7,4) NULL,
            fecha_creacion DATETIME DEFAULT GETDATE()
        )
    ");
    echo "OK: Tabla FP_NOVEDADES_COMISION_SUCURSALES verificada/creada\n";

    // 4. Crear tabla FP_NOVEDADES_PREMIO_LOCAL_ESCALA
    $db->query("
        IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='FP_NOVEDADES_PREMIO_LOCAL_ESCALA' AND xtype='U')
        CREATE TABLE FP_NOVEDADES_PREMIO_LOCAL_ESCALA (
            id INT IDENTITY(1,1) PRIMARY KEY,
            novedad_id INT NOT NULL,
            nivel_codigo VARCHAR(40) NOT NULL,
            nivel_orden TINYINT NOT NULL,
            nivel_descripcion VARCHAR(150) NOT NULL,
            importe_enc_sub DECIMAL(15,2) NOT NULL,
            importe_vend DECIMAL(15,2) NOT NULL,
            fecha_creacion DATETIME DEFAULT GETDATE()
        )
    ");
    echo "OK: Tabla FP_NOVEDADES_PREMIO_LOCAL_ESCALA verificada/creada\n";

    // 5. Depuracion de tipos_novedad
    // Tipos sin registros historicos (0 en novedades): eliminar fisicamente si no tienen registros
    $tiposEliminar = [3, 8, 9, 10, 11];
    foreach ($tiposEliminar as $idTipo) {
        $cnt = $db->query("SELECT COUNT(*) AS cnt FROM novedades WHERE tipo_novedad = ?", [$idTipo])->fetch();
        if ((int)$cnt['cnt'] === 0) {
            $db->query("DELETE FROM tipos_novedad WHERE id = ?", [$idTipo]);
            echo "OK: Tipo id=$idTipo eliminado (0 novedades asociadas)\n";
        } else {
            $db->query("UPDATE tipos_novedad SET activo = 0 WHERE id = ?", [$idTipo]);
            echo "OK: Tipo id=$idTipo desactivado (tenia novedades asociadas)\n";
        }
    }

    // Tipo 55 (A prueba) tiene 4 registros historicos -> desactivar (activo = 0) para preservar historial
    $db->query("UPDATE tipos_novedad SET activo = 0 WHERE id = 55");
    echo "OK: Tipo id=55 (A prueba) desactivado (activo = 0) preservando historial\n";

    $db->commit();
    echo "\nMIGRACION COMPLETADA EXITOSAMENTE\n";
} catch (Exception $e) {
    $db->rollback();
    echo "ERROR EN MIGRACION: " . $e->getMessage() . "\n";
    exit(1);
}
