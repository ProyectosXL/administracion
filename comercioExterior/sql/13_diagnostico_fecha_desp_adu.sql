-- =====================================================================
-- Comercio Exterior - DIAGNOSTICO: en que quedaron las FECHA_DESP_ADU que
-- se calcularon con la regla vieja
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
--
-- ESTE SCRIPT NO ESCRIBE NADA. Son puros SELECT. Existe para poder decidir
-- con numeros si conviene migrar los contenedores pendientes de arribo + 2
-- a arribo + 5, y para poder volver a correrlo despues de migrar.
--
-- Correrlo DESPUES de 11_fechas_fijadas_arribo_nacionalizacion.sql: una de
-- las columnas que reporta es FECHA_DESP_CONF, que la crea el 11.
--
-- ---------------------------------------------------------------------
-- COMO CLASIFICA
--
-- Reproduce en T-SQL las dos reglas y compara contra lo que hay guardado:
--
--   REGLA VIEJA   arribo + 2, corrido al siguiente dia habil
--                 (js/cargaInicial.js hasta esta rama)
--   REGLA NUEVA   arribo + DIAS_ARR_DESP, leido de
--                 RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
--
-- El corrimiento a dia habil importa: sin el, un contenedor automatico cuyo
-- arribo + 2 cae sabado aparece con delta 4 y parece corregido a mano. En
-- central hay doce contenedores asi.
--
-- Las categorias son cuatro, y la del medio es la que importa:
--
--   SOLO_VIEJA    la explica arribo + 2 y no arribo + 5 -> automatica,
--                 es la que se moveria al migrar
--   SOLO_NUEVA    ya esta en arribo + 5
--   AMBAS         las dos reglas dan el mismo dia; migrar no la mueve
--   NINGUNA       no la explica ninguna regla -> la tecleo una persona
--
-- ---------------------------------------------------------------------
-- QUE CONTENEDORES ENTRAN
--
-- Solo los NO NACIONALIZADOS: sin numero de despacho, sin fecha de
-- recepcion y con FECHA_DESP_ADU todavia en el futuro. Los ya nacionalizados
-- o recibidos no se tocan ni se cuentan: su fecha es un hecho consumado.
--
-- ---------------------------------------------------------------------
-- LOS NUMEROS AL 22/09/2026, para poder comparar
--
--   central    SOLO_VIEJA 28 | SOLO_NUEVA 8 | AMBAS 7 | NINGUNA 11
--              corrimiento de las SOLO_VIEJA: entre 1 y 5 dias, promedio 3,2
--   uy         no hay ningun contenedor pendiente: los 72 ya estan
--              nacionalizados, asi que no hay nada que migrar
-- =====================================================================

SET NOCOUNT ON;
GO

-- Misma foto de feriados que usa el backfill del script 11, y por el mismo
-- motivo: T-SQL no puede leer class/cache/feriados_ar_AAAA.json, que es de
-- donde los saca el navegador.
DECLARE @FERIADOS TABLE (F DATE PRIMARY KEY);

INSERT INTO @FERIADOS (F) VALUES
     ('2026-01-01'),('2026-02-16'),('2026-02-17'),('2026-03-24')
    ,('2026-04-02'),('2026-04-03'),('2026-05-01'),('2026-05-25')
    ,('2026-06-15'),('2026-06-20'),('2026-07-09'),('2026-08-17')
    ,('2026-10-12'),('2026-11-23'),('2026-12-08'),('2026-12-25')
    ,('2027-01-01'),('2027-02-08'),('2027-02-09'),('2027-03-24')
    ,('2027-03-26'),('2027-04-02'),('2027-05-01'),('2027-05-25')
    ,('2027-06-20'),('2027-06-21'),('2027-07-09'),('2027-08-16')
    ,('2027-10-11'),('2027-11-20'),('2027-12-08'),('2027-12-25');

-- La regla nueva sale de la tabla, no de un literal: si alguien ajusta
-- DIAS_ARR_DESP desde el ABM, este diagnostico sigue diciendo la verdad.
DECLARE @DIAS_ARR_DESP INT =
    (SELECT VALOR FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA WHERE CLAVE = 'DIAS_ARR_DESP');

IF @DIAS_ARR_DESP IS NULL
BEGIN
    RAISERROR('Falta DIAS_ARR_DESP. Corré 12_parametros_fechas_derivadas.sql en esta base.', 16, 1);
    RETURN;
END

PRINT 'Regla nueva en esta base: arribo + ' + CAST(@DIAS_ARR_DESP AS VARCHAR(10)) + ' dias.';

;WITH PENDIENTES AS (
    SELECT ID, ORDEN_COMPRA, CONTENEDOR, FECHA_ARR,
           CAST(FECHA_DESP_ADU AS DATE) AS DESP,
           FECHA_DESP_CONF
    FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
    WHERE FECHA_ARR       IS NOT NULL
      AND FECHA_DESP_ADU  IS NOT NULL
      AND FECHA_RECIBIDO  IS NULL
      AND (DESPACHO IS NULL OR LTRIM(RTRIM(DESPACHO)) = '')
      AND CAST(FECHA_DESP_ADU AS DATE) > CAST(GETDATE() AS DATE)
),
/* Arribo + N corrido al siguiente dia habil, para las dos reglas.
   El corrimiento se resuelve con un CROSS APPLY sobre los 10 dias
   siguientes en vez de con un bucle, para que todo el diagnostico sea una
   sola consulta.

   DATEPART(weekday) no se usa: depende de DATEFIRST y del idioma de la
   sesion, que no son iguales en las dos bases. La resta contra un lunes
   conocido (1900-01-01 fue lunes) da 0=lunes .. 6=domingo siempre. */
CALCULADAS AS (
    SELECT P.*,
           V.HABIL AS ESPERADA_VIEJA,
           N.HABIL AS ESPERADA_NUEVA
    FROM PENDIENTES P
    CROSS APPLY (
        SELECT TOP 1 C.D AS HABIL
        FROM (SELECT DATEADD(day, 2 + S.N, P.FECHA_ARR) AS D
              FROM (VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9)) AS S(N)) AS C
        WHERE DATEDIFF(day, '19000101', C.D) % 7 < 5
          AND NOT EXISTS (SELECT 1 FROM @FERIADOS X WHERE X.F = CAST(C.D AS DATE))
        ORDER BY C.D
    ) AS V
    CROSS APPLY (
        SELECT TOP 1 C.D AS HABIL
        FROM (SELECT DATEADD(day, @DIAS_ARR_DESP + S.N, P.FECHA_ARR) AS D
              FROM (VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9)) AS S(N)) AS C
        WHERE DATEDIFF(day, '19000101', C.D) % 7 < 5
          AND NOT EXISTS (SELECT 1 FROM @FERIADOS X WHERE X.F = CAST(C.D AS DATE))
        ORDER BY C.D
    ) AS N
),
CLASIFICADAS AS (
    SELECT *,
           CASE
             WHEN DESP = CAST(ESPERADA_VIEJA AS DATE)
              AND DESP = CAST(ESPERADA_NUEVA AS DATE) THEN 'AMBAS'
             WHEN DESP = CAST(ESPERADA_VIEJA AS DATE) THEN 'SOLO_VIEJA'
             WHEN DESP = CAST(ESPERADA_NUEVA AS DATE) THEN 'SOLO_NUEVA'
             ELSE 'NINGUNA'
           END AS CLASE
    FROM CALCULADAS
)
SELECT
    CLASE,
    COUNT(*)                                                     AS CONTENEDORES,
    SUM(CASE WHEN FECHA_DESP_CONF = 1 THEN 1 ELSE 0 END)         AS MARCADOS_A_MANO,
    MIN(ABS(DATEDIFF(day, DESP, ESPERADA_NUEVA)))                AS CORRIMIENTO_MIN,
    MAX(ABS(DATEDIFF(day, DESP, ESPERADA_NUEVA)))                AS CORRIMIENTO_MAX,
    CASE CLASE
        WHEN 'SOLO_VIEJA' THEN 'Automatica con la regla vieja: son las que se moverian'
        WHEN 'SOLO_NUEVA' THEN 'Ya esta en arribo + N: migrar no la toca'
        WHEN 'AMBAS'      THEN 'Las dos reglas dan el mismo dia: migrar no la mueve'
        ELSE                   'No la explica ninguna regla: la puso una persona'
    END AS QUE_SIGNIFICA
FROM CLASIFICADAS
GROUP BY CLASE
ORDER BY CASE CLASE WHEN 'SOLO_VIEJA' THEN 1 WHEN 'AMBAS' THEN 2
                    WHEN 'SOLO_NUEVA' THEN 3 ELSE 4 END;

PRINT '--- Detalle: una fila por contenedor pendiente ---';

;WITH PENDIENTES AS (
    SELECT ID, ORDEN_COMPRA, CONTENEDOR, FECHA_ARR,
           CAST(FECHA_DESP_ADU AS DATE) AS DESP,
           FECHA_DESP_CONF
    FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
    WHERE FECHA_ARR       IS NOT NULL
      AND FECHA_DESP_ADU  IS NOT NULL
      AND FECHA_RECIBIDO  IS NULL
      AND (DESPACHO IS NULL OR LTRIM(RTRIM(DESPACHO)) = '')
      AND CAST(FECHA_DESP_ADU AS DATE) > CAST(GETDATE() AS DATE)
),
CALCULADAS AS (
    SELECT P.*, V.HABIL AS ESPERADA_VIEJA, N.HABIL AS ESPERADA_NUEVA
    FROM PENDIENTES P
    CROSS APPLY (
        SELECT TOP 1 C.D AS HABIL
        FROM (SELECT DATEADD(day, 2 + S.N, P.FECHA_ARR) AS D
              FROM (VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9)) AS S(N)) AS C
        WHERE DATEDIFF(day, '19000101', C.D) % 7 < 5
          AND NOT EXISTS (SELECT 1 FROM @FERIADOS X WHERE X.F = CAST(C.D AS DATE))
        ORDER BY C.D
    ) AS V
    CROSS APPLY (
        SELECT TOP 1 C.D AS HABIL
        FROM (SELECT DATEADD(day, @DIAS_ARR_DESP + S.N, P.FECHA_ARR) AS D
              FROM (VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9)) AS S(N)) AS C
        WHERE DATEDIFF(day, '19000101', C.D) % 7 < 5
          AND NOT EXISTS (SELECT 1 FROM @FERIADOS X WHERE X.F = CAST(C.D AS DATE))
        ORDER BY C.D
    ) AS N
)
SELECT
    ID,
    ORDEN_COMPRA,
    CONTENEDOR,
    FECHA_ARR,
    DESP                                          AS FECHA_DESP_ADU_HOY,
    DATEDIFF(day, FECHA_ARR, DESP)                AS DIAS_HOY,
    CAST(ESPERADA_VIEJA AS DATE)                  AS DARIA_REGLA_VIEJA,
    CAST(ESPERADA_NUEVA AS DATE)                  AS DARIA_REGLA_NUEVA,
    CASE
      WHEN DESP = CAST(ESPERADA_VIEJA AS DATE)
       AND DESP = CAST(ESPERADA_NUEVA AS DATE) THEN 'AMBAS'
      WHEN DESP = CAST(ESPERADA_VIEJA AS DATE) THEN 'SOLO_VIEJA'
      WHEN DESP = CAST(ESPERADA_NUEVA AS DATE) THEN 'SOLO_NUEVA'
      ELSE 'NINGUNA'
    END                                           AS CLASE,
    FECHA_DESP_CONF                               AS MARCADA_A_MANO,
    -- Cuantos dias se correria si se migrara. Solo tiene sentido leerlo en
    -- las SOLO_VIEJA; en las NINGUNA dice cuanto se la estaria pisando.
    DATEDIFF(day, DESP, ESPERADA_NUEVA)           AS CORRIMIENTO
FROM CALCULADAS
ORDER BY CASE
           WHEN DESP = CAST(ESPERADA_VIEJA AS DATE)
            AND DESP = CAST(ESPERADA_NUEVA AS DATE) THEN 2
           WHEN DESP = CAST(ESPERADA_VIEJA AS DATE) THEN 1
           WHEN DESP = CAST(ESPERADA_NUEVA AS DATE) THEN 3
           ELSE 4
         END,
         FECHA_ARR, ID;
GO

PRINT 'Diagnostico terminado. Este script no escribio nada.';
GO
