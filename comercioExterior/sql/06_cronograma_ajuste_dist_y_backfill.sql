-- =====================================================================
-- Cronograma - ajuste de la cadena de estimadas + backfill de FECHA_DISTRI
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente.
--
-- Por que:
-- La distribucion es la salida del deposito central hacia los locales, o
-- sea que es POSTERIOR a la recepcion, no anterior. Con los valores
-- sembrados por el script 04 la recepcion estimada caia en arribo + 7 + 3 =
-- arribo + 10, exactamente el mismo dia que la distribucion estimada
-- (arribo + 10). Dos hitos pisados y en un orden imposible.
--
-- Reglas nuevas:
--   recepcion estimada    = arribo + 7 + 2  = arribo + 9
--   distribucion estimada = arribo + 10                (un dia despues)
--   con recepcion REAL    = recepcion + DIAS_REC_DIST  (al dia siguiente)
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) DIAS_DESP_REC baja de 3 a 2, para que la recepcion estimada quede en
--    arribo + 9 y la distribucion caiga un dia despues y no encima.
--    Solo se pisa si sigue en el valor sembrado por el script 04: si
--    alguien ya lo ajusto a mano desde Parametros, se respeta.
-- ---------------------------------------------------------------------
UPDATE RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
SET VALOR = 2,
    DESCRIPCION = 'Dias corridos entre despacho y recepcion estimada',
    FECHA_MOD = GETDATE()
WHERE CLAVE = 'DIAS_DESP_REC' AND VALOR = 3;
GO

-- ---------------------------------------------------------------------
-- 2) Nuevo parametro: dias entre la recepcion REAL y la distribucion.
--    En el deposito se distribuye casi siempre al dia siguiente.
-- ---------------------------------------------------------------------
IF NOT EXISTS (SELECT 1 FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
               WHERE CLAVE = 'DIAS_REC_DIST')
BEGIN
    INSERT INTO RO_T_IMPORTACIONES_PARAM_CRONOGRAMA (CLAVE, VALOR, DESCRIPCION)
    VALUES ('DIAS_REC_DIST', 1, 'Dias corridos entre la recepcion REAL y la distribucion');
END
GO

-- ---------------------------------------------------------------------
-- 3) Backfill de FECHA_DISTRI.
--
-- Alcance deliberadamente acotado a los contenedores que TODAVIA NO
-- fueron recibidos: ahi la proyeccion sirve para planificar. Para los ya
-- recibidos hace meses, inventar por formula una distribucion pasada que
-- nadie confirmo solo ensuciaria el calendario historico.
--
-- Condiciones:
--   DIST_ORIGEN = 'A'      -> no pisa lo movido a mano ni lo confirmado
--   FECHA_DISTRI IS NULL   -> no pisa nada ya calculado
--   FECHA_ARR IS NOT NULL  -> sin arribo no hay de donde proyectar
--   FECHA_RECIBIDO IS NULL -> solo los no recibidos
-- ---------------------------------------------------------------------
UPDATE E
SET FECHA_DISTRI = DATEADD(day, P.VALOR, E.FECHA_ARR)
FROM RO_T_IMPORTACIONES_ENCABEZADO E
CROSS JOIN (SELECT VALOR FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
            WHERE CLAVE = 'DIAS_ARR_DIST') P
WHERE E.DIST_ORIGEN = 'A'
  AND E.FECHA_DISTRI IS NULL
  AND E.FECHA_ARR IS NOT NULL
  AND E.FECHA_RECIBIDO IS NULL;
GO

-- ---------------------------------------------------------------------
-- Verificacion
-- ---------------------------------------------------------------------
SELECT CLAVE, VALOR, DESCRIPCION
FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
ORDER BY CLAVE;
GO

SELECT COUNT(*) AS CON_FECHA_DISTRI,
       SUM(CASE WHEN FECHA_RECIBIDO IS NULL THEN 1 ELSE 0 END) AS NO_RECIBIDOS
FROM RO_T_IMPORTACIONES_ENCABEZADO
WHERE FECHA_DISTRI IS NOT NULL;
GO
