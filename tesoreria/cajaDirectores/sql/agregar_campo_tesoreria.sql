-- Agregar campo ID_TESORERIA a la tabla ingresos
-- Ejecutar este script en la base de datos APPS

USE [nombre_de_tu_base_apps];

-- Verificar si la columna ya existe antes de agregarla
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('ingresos') AND name = 'ID_TESORERIA')
BEGIN
    ALTER TABLE ingresos 
    ADD ID_TESORERIA INT NULL;
    
    PRINT 'Campo ID_TESORERIA agregado exitosamente a la tabla ingresos';
END
ELSE
BEGIN
    PRINT 'El campo ID_TESORERIA ya existe en la tabla ingresos';
END

-- Crear índice para mejorar el rendimiento de las consultas
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID('ingresos') AND name = 'IX_ingresos_ID_TESORERIA')
BEGIN
    CREATE INDEX IX_ingresos_ID_TESORERIA ON ingresos (ID_TESORERIA);
    PRINT 'Índice IX_ingresos_ID_TESORERIA creado exitosamente';
END
ELSE
BEGIN
    PRINT 'El índice IX_ingresos_ID_TESORERIA ya existe';
END