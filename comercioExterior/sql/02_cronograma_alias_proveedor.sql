-- =====================================================================
-- Cronograma de contenedores - alias de proveedor (2 letras)
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente: se puede correr mas de una vez sin romper.
--
-- COD_PROVEE se declara varchar(6) COLLATE Latin1_General_BIN para
-- espejar exactamente RO_T_IMPORTACIONES_ENCABEZADO.COD_PROVEE en ambos
-- entornos. Sin ese COLLATE explicito la columna tomaria la collation de
-- la base (Modern_Spanish_CI_AI) y todo join contra el encabezado
-- fallaria por conflicto de collation.
-- =====================================================================

IF OBJECT_ID('RO_T_IMPORTACIONES_PROVEEDOR_ALIAS', 'U') IS NULL
BEGIN
    CREATE TABLE RO_T_IMPORTACIONES_PROVEEDOR_ALIAS (
        COD_PROVEE  VARCHAR(6) COLLATE Latin1_General_BIN NOT NULL,
        PROVEEDOR   VARCHAR(100) NULL,   -- referencia visual, no clave
        ALIAS       CHAR(2)      NOT NULL,
        ACTIVO      BIT          NOT NULL CONSTRAINT DF_RO_T_IMP_ALIAS_ACTIVO DEFAULT 1,
        USUARIO     VARCHAR(50)  NULL,
        FECHA_ALTA  DATETIME     NOT NULL CONSTRAINT DF_RO_T_IMP_ALIAS_ALTA DEFAULT GETDATE(),
        FECHA_MOD   DATETIME     NULL,
        CONSTRAINT PK_RO_T_IMP_ALIAS PRIMARY KEY (COD_PROVEE),
        CONSTRAINT UQ_RO_T_IMP_ALIAS UNIQUE (ALIAS),
        -- El COLLATE binario es necesario: con la collation de la base
        -- (case-insensitive) el patron [A-Z] tambien aceptaria minusculas.
        CONSTRAINT CK_RO_T_IMP_ALIAS_MAYUS
            CHECK (ALIAS COLLATE Latin1_General_BIN2 LIKE '[A-Z][A-Z]')
    );
END
GO
