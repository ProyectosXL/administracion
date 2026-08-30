-- =====================================================================
-- Cronograma de contenedores - fecha de distribucion + historial de fechas
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente.
--
-- IMPORTANTE sobre el nombre de la columna:
-- La fecha de distribucion NO se llama FECHA_DIST. En central la columna
-- existente es FECHA_DISTRI (date, nullable, posicion 29). En uy no existe
-- todavia. Este script crea FECHA_DISTRI donde falte, para que las dos
-- bases queden con el mismo nombre, y todo el codigo nuevo usa FECHA_DISTRI.
--
-- Los esquemas de las dos bases divergieron con el tiempo; otras
-- diferencias relevadas y NO tocadas por este script:
--   central: FECHA_OC en pos 11, FECHA_RECIBIDO datetime, PROVEEDOR varchar(100)
--   uy:      FECHA_FACT en pos 11, FECHA_RECIBIDO date,     PROVEEDOR varchar(50)
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) FECHA_DISTRI: existe en central, falta en uy.
-- ---------------------------------------------------------------------
IF COL_LENGTH('RO_T_IMPORTACIONES_ENCABEZADO', 'FECHA_DISTRI') IS NULL
BEGIN
    ALTER TABLE RO_T_IMPORTACIONES_ENCABEZADO ADD FECHA_DISTRI DATE NULL;
END
GO

-- ---------------------------------------------------------------------
-- 2) DIST_ORIGEN: como se llego al valor de FECHA_DISTRI.
--      A = automatica (la recalcula el sistema al mover FECHA_ARR)
--      M = movida a mano (no se recalcula)
--      C = confirmada   (no se recalcula, y moverla exige observacion)
-- Sin esta columna no hay forma de que el recalculo automatico evite pisar
-- una fecha que abastecimiento ajusto a mano.
-- ---------------------------------------------------------------------
IF COL_LENGTH('RO_T_IMPORTACIONES_ENCABEZADO', 'DIST_ORIGEN') IS NULL
BEGIN
    ALTER TABLE RO_T_IMPORTACIONES_ENCABEZADO
        ADD DIST_ORIGEN CHAR(1) NOT NULL
            CONSTRAINT DF_RO_T_IMP_ENC_DIST_ORIGEN DEFAULT 'A';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.check_constraints
               WHERE name = 'CK_RO_T_IMP_ENC_DIST_ORIGEN')
BEGIN
    ALTER TABLE RO_T_IMPORTACIONES_ENCABEZADO
        ADD CONSTRAINT CK_RO_T_IMP_ENC_DIST_ORIGEN
            CHECK (DIST_ORIGEN IN ('A', 'M', 'C'));
END
GO

-- ---------------------------------------------------------------------
-- 3) Historial de cambios de fecha.
-- Se alimenta desde el cronograma (drag & drop y edicion manual) y desde
-- gestion de despachos, para que ninguna edicion quede sin registro.
--
-- ORDEN_COMPRA es varchar(14) porque asi esta declarada en el encabezado
-- (y en CPA35.N_ORDEN_CO). Los valores reales traen espacios a la
-- izquierda, del estilo ' 0000100014381', y se guardan tal cual.
-- ---------------------------------------------------------------------
IF OBJECT_ID('RO_T_IMPORTACIONES_FECHAS_HIST', 'U') IS NULL
BEGIN
    CREATE TABLE RO_T_IMPORTACIONES_FECHAS_HIST (
        ID             INT IDENTITY(1,1) NOT NULL,
        ID_ENCABEZADO  INT          NOT NULL,
        ORDEN_COMPRA   VARCHAR(14)  NULL,
        CONTENEDOR     VARCHAR(50)  NULL,
        -- FECHA_EST_EMB | FECHA_EMB | FECHA_ARR | FECHA_DESP_ADU | FECHA_DISTRI
        CAMPO          VARCHAR(30)  NOT NULL,
        VALOR_ANTERIOR DATE         NULL,
        VALOR_NUEVO    DATE         NULL,
        MOTIVO         VARCHAR(40)  NULL,
        OBSERVACION    VARCHAR(500) NULL,
        USUARIO        VARCHAR(50)  NULL,
        ORIGEN         VARCHAR(20)  NOT NULL,   -- CRONOGRAMA | GESTION_DESPACHOS
        FECHA_ALTA     DATETIME     NOT NULL
            CONSTRAINT DF_RO_T_IMP_HIST_ALTA DEFAULT GETDATE(),
        CONSTRAINT PK_RO_T_IMP_HIST PRIMARY KEY (ID),
        CONSTRAINT FK_RO_T_IMP_HIST_ENC
            FOREIGN KEY (ID_ENCABEZADO)
            REFERENCES RO_T_IMPORTACIONES_ENCABEZADO(ID)
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes
               WHERE name = 'IX_RO_T_IMP_HIST_ENC'
                 AND object_id = OBJECT_ID('RO_T_IMPORTACIONES_FECHAS_HIST'))
BEGIN
    CREATE INDEX IX_RO_T_IMP_HIST_ENC
        ON RO_T_IMPORTACIONES_FECHAS_HIST(ID_ENCABEZADO, FECHA_ALTA DESC);
END
GO
