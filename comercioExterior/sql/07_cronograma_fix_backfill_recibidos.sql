-- =====================================================================
-- Cronograma - correccion del backfill del script 06
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
--
-- Que paso:
-- La primera version del script 06 filtraba los contenedores ya recibidos
-- con FECHA_RECIBIDO IS NULL, pero la recepcion tiene DOS fuentes. Ademas de
-- esa columna esta STA20 con los comprobantes 'RP' de Tango, que es de donde
-- sale la recepcion de la mayoria de los contenedores (el COALESCE de la
-- query del cronograma usa las dos). Resultado: se le proyecto una fecha de
-- distribucion a contenedores que ya habian sido recibidos hace meses.
--   central: 84 de 243 filas
--   uy:      17 de 34 filas
--
-- Este script las devuelve a NULL. El 06 ya quedo corregido, asi que una
-- instalacion desde cero no necesita correr este.
--
-- El filtro es deliberadamente estrecho: solo toca las filas cuyo
-- FECHA_DISTRI es EXACTAMENTE el valor que escribio el backfill
-- (arribo + DIAS_ARR_DIST). Si alguien ya la movio, la confirmo o el sistema
-- la reanclo a la recepcion, el valor es otro y no se toca.
-- =====================================================================

WITH RECEPCIONES AS (
    SELECT N_ORDEN_CO, MAX(CAST(FECHA_MOV AS DATE)) AS FECHA_REC
    FROM STA20
    WHERE TCOMP_IN_S = 'RP' AND FECHA_MOV >= GETDATE()-360
    GROUP BY N_ORDEN_CO
)
UPDATE E
SET FECHA_DISTRI = NULL
FROM RO_T_IMPORTACIONES_ENCABEZADO E
CROSS JOIN (SELECT VALOR FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
            WHERE CLAVE = 'DIAS_ARR_DIST') P
LEFT JOIN RECEPCIONES R ON E.ORDEN_COMPRA = R.N_ORDEN_CO
WHERE E.DIST_ORIGEN = 'A'
  AND E.FECHA_DISTRI IS NOT NULL
  AND E.FECHA_ARR IS NOT NULL
  -- ya recibido por cualquiera de las dos vias
  AND (E.FECHA_RECIBIDO IS NOT NULL OR R.FECHA_REC IS NOT NULL)
  -- y con el valor tal cual lo dejo el backfill, sin tocar
  AND E.FECHA_DISTRI = DATEADD(day, P.VALOR, E.FECHA_ARR);
GO

-- ---------------------------------------------------------------------
-- Verificacion: FECHA_DISTRI sobre contenedores ya recibidos debe dar 0
-- ---------------------------------------------------------------------
WITH RECEPCIONES AS (
    SELECT N_ORDEN_CO, MAX(CAST(FECHA_MOV AS DATE)) AS FECHA_REC
    FROM STA20
    WHERE TCOMP_IN_S = 'RP' AND FECHA_MOV >= GETDATE()-360
    GROUP BY N_ORDEN_CO
)
SELECT
    SUM(CASE WHEN E.FECHA_DISTRI IS NOT NULL THEN 1 ELSE 0 END) AS CON_FECHA_DISTRI,
    SUM(CASE WHEN E.FECHA_DISTRI IS NOT NULL AND E.DIST_ORIGEN = 'A'
              AND (E.FECHA_RECIBIDO IS NOT NULL OR R.FECHA_REC IS NOT NULL)
             THEN 1 ELSE 0 END) AS AUTO_SOBRE_RECIBIDOS
FROM RO_T_IMPORTACIONES_ENCABEZADO E
LEFT JOIN RECEPCIONES R ON E.ORDEN_COMPRA = R.N_ORDEN_CO;
GO
