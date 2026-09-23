-- =====================================================================
-- Comercio Exterior - migrar las FECHA_DESP_ADU calculadas con la regla
-- vieja (arribo + 2) a la regla nueva (arribo + DIAS_ARR_DESP)
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente: una segunda corrida no encuentra nada que mover.
--
-- CORRERLO DESPUES DE 11 Y DE 12, EN ESE ORDEN:
--   11  marca las fechas que alguien fijo a mano. Sin el, este script las
--       pisa y el valor original queda solo en el historial, si es que lo hay.
--   12  deja DIAS_ARR_DESP en 5, que es la regla contra la que este migra.
--
-- ---------------------------------------------------------------------
-- QUE MUEVE, EXACTAMENTE
--
-- Solo los contenedores que cumplen TODO esto:
--
--   * FECHA_DESP_CONF = 0       la fecha no esta fijada a mano
--   * sin numero de despacho    todavia no se nacionalizo
--   * FECHA_RECIBIDO IS NULL    todavia no se recibio
--   * FECHA_DESP_ADU > hoy      la fecha sigue siendo una proyeccion
--   * FECHA_ARR IS NOT NULL     hay de donde colgarla
--   * y el valor guardado COINCIDE EXACTAMENTE con lo que daba la regla
--     vieja: arribo + 2 corrido al siguiente dia habil
--
-- LA ULTIMA CONDICION ES LA QUE IMPORTA. No se migra "todo lo que no este
-- marcado": se migra lo que se puede DEMOSTRAR que lo calculo el sistema
-- con la regla vieja. Una fecha que no coincide con ninguna de las dos
-- reglas la tecleo una persona, y aunque el script 11 ya la haya marcado,
-- este WHERE es la segunda red.
--
-- POR QUE EL CORRIMIENTO A DIA HABIL. validarCampoFechaHabil() en
-- js/cargaInicial.js corre la fecha calculada al siguiente dia habil, asi
-- que un arribo + 2 caido en sabado quedo guardado con delta 4. Comparar
-- contra el delta pelado dejaria doce contenedores de central afuera.
--
-- LO QUE SE ESCRIBE TAMBIEN SE CORRE A DIA HABIL, por la misma razon:
-- si la regla nueva cae domingo, guardar el domingo dejaria una fecha que
-- la pantalla va a corregir -y avisar- la primera vez que alguien abra ese
-- contenedor.
--
-- ---------------------------------------------------------------------
-- ESTO MUEVE PLATA EN EL CASHFLOW DE ProyectosXL/finanzas
--
-- La pestana Crono Nacionalizacion del cashflow proyecta los gastos de
-- nacionalizacion por FECHA_DESP_ADU. Correr estas fechas mueve esos
-- importes de una semana -o de un mes- a la siguiente. No es un ajuste
-- interno de Comercio Exterior.
--
-- ---------------------------------------------------------------------
-- LOS NUMEROS AL 22/09/2026, para poder comparar antes de correrlo
--
--   central   28 contenedores a mover, corrimiento de 1 a 5 dias (prom 3,2)
--   uy        0: los 72 contenedores ya estan nacionalizados
--
-- Si estos numeros no coinciden con lo que reporta el script 13 antes de
-- correr este, algo cambio: revisar antes de seguir.
-- =====================================================================

SET NOCOUNT ON;
GO

IF COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO', 'FECHA_DESP_CONF') IS NULL
BEGIN
    RAISERROR('Falta FECHA_DESP_CONF. Corré primero 11_fechas_fijadas_arribo_nacionalizacion.sql en esta base.', 16, 1);
END
GO

-- Misma foto de feriados que el script 11 y el 13, y por el mismo motivo:
-- T-SQL no puede leer class/cache/feriados_ar_AAAA.json, que es de donde
-- los saca el navegador.
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

-- La regla nueva sale de la tabla y no de un literal: si alguien ajusto
-- DIAS_ARR_DESP desde el ABM, este script migra a ESE valor.
DECLARE @DIAS_ARR_DESP INT =
    (SELECT VALOR FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA WHERE CLAVE = 'DIAS_ARR_DESP');

IF @DIAS_ARR_DESP IS NULL
BEGIN
    RAISERROR('Falta DIAS_ARR_DESP. Corré 12_parametros_fechas_derivadas.sql en esta base.', 16, 1);
    RETURN;
END

PRINT 'Migrando de arribo + 2 a arribo + ' + CAST(@DIAS_ARR_DESP AS VARCHAR(10)) + ' dias.';

/* Candidatos y las dos fechas, vieja y nueva, en una tabla temporal.
   SE RESUELVE ANTES DE ESCRIBIR para poder listar exactamente lo que se va
   a tocar, escribirlo y despues dejar el historial de esas mismas filas.
   Con el UPDATE calculando al vuelo habria que repetir tres veces el mismo
   CROSS APPLY y confiar en que dan lo mismo. */
DECLARE @MOVER TABLE (
    ID            INT PRIMARY KEY,
    ORDEN_COMPRA  VARCHAR(14),
    CONTENEDOR    VARCHAR(50),
    FECHA_ARR     DATE,
    FECHA_VIEJA   DATE,
    FECHA_NUEVA   DATE
);

;WITH PENDIENTES AS (
    SELECT ID, ORDEN_COMPRA, CONTENEDOR, FECHA_ARR,
           CAST(FECHA_DESP_ADU AS DATE) AS DESP
    FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
    WHERE FECHA_DESP_CONF = 0
      AND FECHA_ARR       IS NOT NULL
      AND FECHA_DESP_ADU  IS NOT NULL
      AND FECHA_RECIBIDO  IS NULL
      AND (DESPACHO IS NULL OR LTRIM(RTRIM(DESPACHO)) = '')
      AND CAST(FECHA_DESP_ADU AS DATE) > CAST(GETDATE() AS DATE)
),
/* Arribo + N corrido al siguiente dia habil, para las dos reglas.
   DATEPART(weekday) no se usa: depende de DATEFIRST y del idioma de la
   sesion, que no son iguales en las dos bases. La resta contra un lunes
   conocido (1900-01-01 fue lunes) da 0=lunes .. 6=domingo siempre. */
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
INSERT INTO @MOVER (ID, ORDEN_COMPRA, CONTENEDOR, FECHA_ARR, FECHA_VIEJA, FECHA_NUEVA)
SELECT ID, ORDEN_COMPRA, CONTENEDOR, FECHA_ARR, DESP, CAST(ESPERADA_NUEVA AS DATE)
FROM CALCULADAS
WHERE DESP = CAST(ESPERADA_VIEJA AS DATE)        -- la explica la regla vieja
  AND DESP <> CAST(ESPERADA_NUEVA AS DATE);      -- y la nueva da otra cosa

PRINT '--- Lo que se va a mover ---';

SELECT ID, ORDEN_COMPRA, CONTENEDOR, FECHA_ARR,
       FECHA_VIEJA, FECHA_NUEVA,
       DATEDIFF(day, FECHA_VIEJA, FECHA_NUEVA) AS DIAS_QUE_SE_CORRE
FROM @MOVER
ORDER BY FECHA_ARR, ID;

IF NOT EXISTS (SELECT 1 FROM @MOVER)
BEGIN
    PRINT 'No hay nada que migrar en esta base.';
END
ELSE
BEGIN
    BEGIN TRANSACTION;

    BEGIN TRY

        UPDATE A
           SET A.FECHA_DESP_ADU = M.FECHA_NUEVA
          FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO A
         INNER JOIN @MOVER M ON M.ID = A.ID
         /* La doble condicion no es redundante: entre el SELECT de arriba y
            este UPDATE alguien pudo fijar la fecha desde la pantalla. */
         WHERE A.FECHA_DESP_CONF = 0
           AND CAST(A.FECHA_DESP_ADU AS DATE) = M.FECHA_VIEJA;

        DECLARE @movidos INT = @@ROWCOUNT;

        /* El historial, con MOTIVO = RECALCULO_AUTOMATICO, que es el mismo
           que usa la cascada de distribucion -ver MotivosFecha- y no un
           motivo nuevo: para quien lo lee, esto ES un recalculo automatico,
           solo que disparado por un cambio de regla y no por un cambio de
           fecha. La OBSERVACION es la que dice cual fue el cambio.

           ORIGEN = 'GESTION_DESPACHOS' porque es el valor que ya usan las dos
           escrituras que no vienen del calendario; agregar un 'MIGRACION'
           obligaria a tocar el filtro de la pantalla de historial. */
        INSERT INTO dbo.RO_T_IMPORTACIONES_FECHAS_HIST
            (ID_ENCABEZADO, ORDEN_COMPRA, CONTENEDOR, CAMPO,
             VALOR_ANTERIOR, VALOR_NUEVO, MOTIVO, OBSERVACION, USUARIO, ORIGEN)
        SELECT M.ID, M.ORDEN_COMPRA, M.CONTENEDOR, 'FECHA_DESP_ADU',
               M.FECHA_VIEJA, M.FECHA_NUEVA,
               'RECALCULO_AUTOMATICO',
               'Migración script 14: la nacionalización pasa de arribo + 2 a arribo + '
                   + CAST(@DIAS_ARR_DESP AS VARCHAR(10)) + ' días',
               NULL,
               'GESTION_DESPACHOS'
        FROM @MOVER M
        INNER JOIN dbo.RO_T_IMPORTACIONES_ENCABEZADO A
                ON A.ID = M.ID AND CAST(A.FECHA_DESP_ADU AS DATE) = M.FECHA_NUEVA;

        PRINT 'Migrados: ' + CAST(@movidos AS VARCHAR(10)) + ' contenedores.';
        PRINT 'Historial: ' + CAST(@@ROWCOUNT AS VARCHAR(10)) + ' filas.';

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;

        PRINT 'Migración ABORTADA, no se escribió nada: ' + ERROR_MESSAGE();
    END CATCH
END
GO

-- ---------------------------------------------------------------------
-- Control: que quedo sin migrar, y por que.
--
-- Si despues de correr esto sigue habiendo filas en 'arribo + 2', o son
-- fechas fijadas a mano -FECHA_DESP_CONF = 1, correcto- o algo no anduvo.
-- ---------------------------------------------------------------------
PRINT '--- Estado de los contenedores sin nacionalizar ---';
GO

SELECT
    CASE WHEN FECHA_DESP_CONF = 1 THEN 'Fijada a mano (no se migra)'
         ELSE 'Automática' END                            AS ESTADO,
    COUNT(*)                                              AS CONTENEDORES,
    MIN(DATEDIFF(day, FECHA_ARR, FECHA_DESP_ADU))         AS DIAS_MIN,
    MAX(DATEDIFF(day, FECHA_ARR, FECHA_DESP_ADU))         AS DIAS_MAX
FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
WHERE FECHA_RECIBIDO IS NULL
  AND (DESPACHO IS NULL OR LTRIM(RTRIM(DESPACHO)) = '')
  AND FECHA_DESP_ADU > CAST(GETDATE() AS DATE)
  AND FECHA_ARR IS NOT NULL
GROUP BY FECHA_DESP_CONF;
GO

PRINT 'Comercio Exterior: migración de FECHA_DESP_ADU terminada.';
GO
