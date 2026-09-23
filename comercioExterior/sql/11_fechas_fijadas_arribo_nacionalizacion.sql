-- =====================================================================
-- Comercio Exterior - el arribo y la nacionalizacion tambien se pueden
-- fijar a mano, y la marca sobrevive al cierre de la pantalla
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente. No borra ni pisa nada de lo que ya existe.
--
-- CORRERLO ANTES QUE 12_parametros_fechas_derivadas.sql. El 12 cambia la
-- regla de la nacionalizacion de "arribo + 2" a "arribo + 5"; si las fechas
-- puestas a mano no estan marcadas antes, el primer recalculo que pase por
-- ellas las pisa y no hay como distinguirlas despues.
--
-- ---------------------------------------------------------------------
-- QUE PROBLEMA RESUELVE
--
-- Es EXACTAMENTE el bug que corrigio sql/10_fecha_pago_manual.sql para
-- FECHA_EST_PAGO, en los otros dos campos que el navegador calcula:
--
--   fechaArriboIsManual   en js/cargaInicial.js
--   fechaDespachoIsManual en js/cargaInicial.js
--
-- Los dos viven SOLO en memoria del navegador. Al reabrir la pantalla
-- arrancan en false, asi que la unica forma que tenia el codigo de no
-- pisar una fecha corregida a mano era encenderlos a ciegas al cargar los
-- datos: "si la fila trae FECHA_ARR, marcala como manual". Eso protege,
-- pero al precio de que NINGUNA fecha ya guardada vuelva a recalcularse
-- nunca -ni cuando cambia el ETD, ni cuando cambia un parametro-. La
-- pantalla no distingue "esto lo decidio alguien" de "esto vino asi".
--
-- ---------------------------------------------------------------------
-- POR QUE EL ARRIBO NO LLEVA UN BIT NUEVO
--
-- Porque ya lo tiene, con otro nombre: ETA_CONFIRMADA. Desde que se saco
-- el checkbox "ETA Confirmada", ese bit se enciende SOLO al editar la ETA
-- a mano -ver el datepicker .js-datepicker-arribo en cargaInicial.js- que
-- es literalmente la definicion de "fecha fijada a mano". Ya se persiste,
-- ya se relee al abrir la pantalla y ya lo leen el cronograma
-- -CronogramaFechas::esFechaReal()- y las dos pestanas del cashflow.
--
-- Agregar un FECHA_ARR_CONF al lado seria tener DOS columnas afirmando el
-- mismo hecho, con la garantia de que algun dia se contradicen. Lo que si
-- falta es el QUIEN y el CUANDO, que el patron del script 10 si trae y
-- que el badge de la pantalla necesita para el tooltip: eso son las dos
-- columnas ETA_CONF_USUARIO / ETA_CONF_FECHA de mas abajo.
--
-- La nacionalizacion, en cambio, no tiene nada equivalente, asi que ahi si
-- van las tres columnas completas.
--
-- ---------------------------------------------------------------------
-- POR QUE UN BIT Y NO UN FLAG DEDUCIDO DEL HISTORIAL
--
-- Mismo motivo que en el script 10, y ahora esta medido contra los datos:
-- en central, RO_T_IMPORTACIONES_FECHAS_HIST tiene 36 filas de
-- FECHA_DESP_ADU y las 36 son ORIGEN = 'GESTION_DESPACHOS'. El recalculo
-- automatico y la edicion manual llegan por el MISMO POST del formulario
-- y dejan la misma fila. El rastro no separa una cosa de la otra.
--
-- El backfill de mas abajo NO se apoya en el historial: se apoya en la
-- aritmetica, que es lo unico que si distingue.
--
-- ---------------------------------------------------------------------
-- SI ESTE SCRIPT NO SE CORRIO, NADA SE ROMPE
--
-- El codigo pregunta por las columnas antes de usarlas -mismo criterio que
-- Encabezado::tieneFechaPagoConf() y AlicuotasVigencia::disponible()- y sin
-- ellas la pantalla se comporta como venia: toda fecha ya guardada queda
-- congelada, que es el comportamiento viejo.
-- =====================================================================

SET NOCOUNT ON;
GO

-- ---------------------------------------------------------------------
-- 1) El quien y el cuando de la ETA confirmada.
--
-- NULL-ables las dos, y no solo porque describan un hecho que puede no
-- haber ocurrido: hay 49 filas en central con ETA_CONFIRMADA = 1 que son
-- anteriores a estas columnas y para las que nadie guardo quien fue.
-- Inventarles un 'BACKFILL' seria afirmar algo falso; el badge ya sabe
-- mostrar "fijada a mano" sin decir quien -ver actualizarBadgeFechaEstPago()-.
--
-- VARCHAR(50) es el ancho que ya usan RO_T_IMPORTACIONES_FECHAS_HIST.USUARIO
-- y FECHA_PAGO_CONF_USUARIO.
-- ---------------------------------------------------------------------
IF COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO', 'ETA_CONF_USUARIO') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_IMPORTACIONES_ENCABEZADO
        ADD ETA_CONF_USUARIO VARCHAR(50) NULL;

    PRINT 'ETA_CONF_USUARIO: columna creada.';
END
ELSE
    PRINT 'ETA_CONF_USUARIO: ya existia, no se toca.';
GO

IF COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO', 'ETA_CONF_FECHA') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_IMPORTACIONES_ENCABEZADO
        ADD ETA_CONF_FECHA DATETIME NULL;

    PRINT 'ETA_CONF_FECHA: columna creada.';
END
ELSE
    PRINT 'ETA_CONF_FECHA: ya existia, no se toca.';
GO

-- ---------------------------------------------------------------------
-- 2) La nacionalizacion fijada a mano.
--
-- FECHA_DESP_CONF es NOT NULL con DEFAULT 0, igual que FECHA_PAGO_CONF:
-- "no se sabe" no es un estado util. Toda fila nace en 0 -automatica- y el
-- paso 3 sube a 1 solo las que se puede demostrar que alguien movio.
--
-- OJO con el nombre: la columna de fecha es FECHA_DESP_ADU, pero el bit se
-- llama FECHA_DESP_CONF y no FECHA_DESP_ADU_CONF, para que los tres
-- sufijos queden iguales a los del script 10 (CONF / CONF_USUARIO /
-- CONF_FECHA) y no haya que recordar dos convenciones.
-- ---------------------------------------------------------------------
IF COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO', 'FECHA_DESP_CONF') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_IMPORTACIONES_ENCABEZADO
        ADD FECHA_DESP_CONF BIT NOT NULL
                CONSTRAINT DF_RO_T_IMP_ENC_FECHA_DESP_CONF DEFAULT 0;

    PRINT 'FECHA_DESP_CONF: columna creada.';
END
ELSE
    PRINT 'FECHA_DESP_CONF: ya existia, no se toca.';
GO

IF COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO', 'FECHA_DESP_CONF_USUARIO') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_IMPORTACIONES_ENCABEZADO
        ADD FECHA_DESP_CONF_USUARIO VARCHAR(50) NULL;

    PRINT 'FECHA_DESP_CONF_USUARIO: columna creada.';
END
ELSE
    PRINT 'FECHA_DESP_CONF_USUARIO: ya existia, no se toca.';
GO

IF COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO', 'FECHA_DESP_CONF_FECHA') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_IMPORTACIONES_ENCABEZADO
        ADD FECHA_DESP_CONF_FECHA DATETIME NULL;

    PRINT 'FECHA_DESP_CONF_FECHA: columna creada.';
END
ELSE
    PRINT 'FECHA_DESP_CONF_FECHA: ya existia, no se toca.';
GO

-- =====================================================================
-- 3) Backfill de FECHA_DESP_CONF.
--
-- QUE SE MARCA. Los contenedores TODAVIA NO NACIONALIZADOS cuya
-- FECHA_DESP_ADU no se puede explicar con la regla que estuvo vigente
-- hasta hoy: "arribo + 2 dias, corrido al siguiente dia habil".
--
-- POR QUE ESA REGLA Y NO EL DELTA PELADO. Mirar solo
-- DATEDIFF(FECHA_ARR, FECHA_DESP_ADU) = 2 subestima muchisimo, porque
-- validarCampoFechaHabil() en cargaInicial.js corre la fecha calculada al
-- siguiente dia habil. En los datos reales de central, de los 54
-- contenedores pendientes hay deltas de 2, 3, 4, 5, 6 y 7 dias, y la
-- mayoria de los que no son 2 son el mismo arribo + 2 caido en sabado,
-- domingo o feriado. Doce filas con delta 3 o 4 son automaticas.
--
-- POR QUE ESTA VEZ SE MARCA DE MAS Y NO DE MENOS. Es la decision OPUESTA a
-- la del script 10, y a proposito:
--
--   * Marcar de mas una fecha automatica: deja de seguir al arribo. Se
--     arregla con un clic en "volver a auto" desde la pantalla.
--   * Marcar de menos una fecha puesta a mano: el cambio de regla de
--     arribo+2 a arribo+5 la pisa y el valor original queda solo en el
--     historial, si es que llego a haberlo.
--
-- El error caro es el segundo, asi que la duda se resuelve marcando. En el
-- script 10 era al reves porque alla marcar de mas rompia el recalculo
-- automatico de media base.
--
-- Concretamente, esto marca tambien a los que hoy ya estan en arribo + 5:
-- NINGUN codigo escribe +5 en el maestro -el cronograma solo lo dibuja-,
-- asi que un +5 guardado lo tecleo una persona.
--
-- LOS YA NACIONALIZADOS O RECIBIDOS NO SE TOCAN: quedan en 0. Su fecha es
-- un hecho consumado que ningun recalculo va a mirar, y marcarlos llenaria
-- la pantalla de badges "Manual" sobre contenedores cerrados.
--
-- SOLO SUBE, NUNCA BAJA: el WHERE pide FECHA_DESP_CONF = 0, asi que una
-- segunda corrida no pisa nada de lo que se haya fijado desde la pantalla
-- ni revierte lo que alguien haya vuelto a auto.
-- =====================================================================

-- Feriados argentinos, para poder reproducir el corrimiento a dia habil
-- que hace el navegador.
--
-- ES UNA FOTO Y SOLO SIRVE PARA ESTE BACKFILL. La aplicacion los saca de
-- la API Nager.Date y los cachea en class/cache/feriados_ar_AAAA.json
-- -ver FechasHabiles::obtenerFeriadosAPI()-; esta copia existe porque T-SQL
-- no puede leer ese cache y sin feriados el backfill marcaria como manual a
-- cuatro contenedores de central cuyo arribo + 2 cae el 1/1/2027 o el
-- 2/4/2027. Son los mismos valores de los dos JSON que hay en el repo.
--
-- Uruguay usa esta misma lista: feriadosArgentinos es lo unico que
-- tabs/cargaInicial.php le pasa al navegador en las dos bases. No es
-- correcto como regla de negocio, pero es lo que calculo las fechas que
-- este backfill tiene que clasificar, y clasificar con otra lista daria un
-- resultado equivocado.
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

BEGIN TRANSACTION;

BEGIN TRY

    /* Arribo + 2 corrido al siguiente dia habil.

       El corrimiento se resuelve con un CROSS APPLY que prueba los 10 dias
       siguientes y se queda con el primero habil, en vez de con un bucle:
       asi el backfill entero es una sola sentencia y no hay estado
       intermedio que pueda quedar a medias si algo falla.

       DATEPART(weekday) NO se usa porque depende de DATEFIRST y de la
       configuracion de idioma de la sesion, que no es la misma en las dos
       bases. La resta contra un lunes conocido (1900-01-01 fue lunes) da
       0=lunes .. 6=domingo sin depender de nada. */
    WITH PENDIENTES AS (
        SELECT ID, FECHA_ARR, CAST(FECHA_DESP_ADU AS DATE) AS DESP
        FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
        WHERE FECHA_DESP_CONF = 0
          AND FECHA_ARR       IS NOT NULL
          AND FECHA_DESP_ADU  IS NOT NULL
          AND FECHA_RECIBIDO  IS NULL
          AND (DESPACHO IS NULL OR LTRIM(RTRIM(DESPACHO)) = '')
          AND CAST(FECHA_DESP_ADU AS DATE) > CAST(GETDATE() AS DATE)
    ),
    ESPERADA AS (
        SELECT P.ID, P.DESP, H.HABIL AS ESPERADA
        FROM PENDIENTES P
        CROSS APPLY (
            SELECT TOP 1 C.D AS HABIL
            FROM (
                SELECT DATEADD(day, 2 + N.N, P.FECHA_ARR) AS D
                FROM (VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9)) AS N(N)
            ) AS C
            WHERE DATEDIFF(day, '19000101', C.D) % 7 < 5           -- ni sabado ni domingo
              AND NOT EXISTS (SELECT 1 FROM @FERIADOS X WHERE X.F = CAST(C.D AS DATE))
            ORDER BY C.D
        ) AS H
    )
    UPDATE A
       SET A.FECHA_DESP_CONF         = 1,
           A.FECHA_DESP_CONF_USUARIO = NULL,   -- nadie sabe quien fue; ver el comentario del paso 1
           A.FECHA_DESP_CONF_FECHA   = NULL
      FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO A
     INNER JOIN ESPERADA E ON E.ID = A.ID
     WHERE E.DESP <> CAST(E.ESPERADA AS DATE);

    PRINT 'Backfill: contenedores con FECHA_DESP_ADU marcada como fijada a mano: '
          + CAST(@@ROWCOUNT AS VARCHAR(10));

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;

    PRINT 'Backfill ABORTADO, no se escribio nada: ' + ERROR_MESSAGE();
END CATCH
GO

-- ---------------------------------------------------------------------
-- 4) Control: como quedo el padron.
-- ---------------------------------------------------------------------
PRINT '--- Estado de FECHA_DESP_CONF sobre los contenedores sin nacionalizar ---';
GO

SELECT
    CASE WHEN FECHA_DESP_CONF = 1 THEN 'Fijada a mano' ELSE 'Automatica (sigue al arribo)' END AS ESTADO,
    COUNT(*) AS CONTENEDORES
FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
WHERE FECHA_RECIBIDO IS NULL
  AND (DESPACHO IS NULL OR LTRIM(RTRIM(DESPACHO)) = '')
  AND FECHA_DESP_ADU > CAST(GETDATE() AS DATE)
GROUP BY FECHA_DESP_CONF;
GO

PRINT '--- Detalle de las fijadas a mano (si no lista nada, no hay) ---';
GO

SELECT
    ID,
    ORDEN_COMPRA,
    CONTENEDOR,
    FECHA_ARR,
    FECHA_DESP_ADU,
    DATEDIFF(day, FECHA_ARR, FECHA_DESP_ADU) AS DIAS_ARR_DESP
FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
WHERE FECHA_DESP_CONF = 1
ORDER BY FECHA_ARR, ID;
GO

PRINT '--- ETA confirmada: cuantas hay (el bit ya existia, esto es solo control) ---';
GO

SELECT
    CASE WHEN ETA_CONFIRMADA = 1 THEN 'ETA en firme' ELSE 'ETA estimada' END AS ESTADO,
    COUNT(*) AS CONTENEDORES,
    SUM(CASE WHEN ETA_CONF_USUARIO IS NOT NULL THEN 1 ELSE 0 END) AS CON_USUARIO
FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
GROUP BY CASE WHEN ETA_CONFIRMADA = 1 THEN 'ETA en firme' ELSE 'ETA estimada' END;
GO

PRINT 'Comercio Exterior: arribo y nacionalizacion fijables a mano, lista.';
GO
