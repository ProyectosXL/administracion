-- =====================================================================
-- Cronograma de contenedores - permitir alias repetidos entre proveedores
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente: se puede correr mas de una vez sin romper.
--
-- Motivo: un mismo proveedor puede figurar con mas de un codigo/razon
-- social (ej. GUANGZHOU AOPIYA LEATHER INDUSTRIAL LLC y AOPIYA GROUP
-- LIMITED) y necesita compartir el mismo alias de 2 letras.
-- =====================================================================

IF EXISTS (
    SELECT 1
    FROM sys.key_constraints
    WHERE name = 'UQ_RO_T_IMP_ALIAS'
      AND parent_object_id = OBJECT_ID('RO_T_IMPORTACIONES_PROVEEDOR_ALIAS')
)
BEGIN
    ALTER TABLE RO_T_IMPORTACIONES_PROVEEDOR_ALIAS
        DROP CONSTRAINT UQ_RO_T_IMP_ALIAS;
END
GO
