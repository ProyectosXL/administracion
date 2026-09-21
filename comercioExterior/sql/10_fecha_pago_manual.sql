-- =====================================================================
-- Comercio Exterior - la fecha estimada de pago se puede fijar a mano
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente. No borra ni pisa nada de lo que ya existe.
--
-- ---------------------------------------------------------------------
-- QUE PROBLEMA RESUELVE
--
-- RO_T_IMPORTACIONES_ENCABEZADO.FECHA_EST_PAGO la calcula el navegador
-- como "fecha base + 5 dias", donde la base es FECHA_EMB -el ETD real-
-- con fallback a FECHA_EST_EMB. Si el usuario la corrige a mano, esa
-- correccion no sobrevive:
--
--   * El flag que la protege -fechaEstPagoIsManual en cargaInicial.js-
--     vive solo en memoria del navegador. Al volver a abrir la pantalla
--     arranca en false y la fecha guardada a mano vuelve a nacer
--     automatica.
--   * Ni siquiera sobrevive dentro de la misma sesion: el handler del
--     datepicker del ETD lo ponia en false explicitamente, asi que se
--     cargaba la fecha a mano, se tocaba el ETD, y se pisaba.
--
-- Y lo mismo desde el otro lado: la pestana Proveedores Exterior del
-- cashflow escribe FECHA_EST_PAGO directo sobre este maestro
-- -Comex::guardarFecha()-, y el recalculo de Comercio Exterior no tiene
-- como saber que esa fecha la puso una persona.
--
-- Estas tres columnas son ese dato: SI LA FECHA ESTA FIJADA A MANO.
--
-- ---------------------------------------------------------------------
-- POR QUE UN BIT EN EL MAESTRO Y NO UN FLAG DERIVADO DEL HISTORIAL
--
-- RO_T_IMPORTACIONES_FECHAS_HIST (script 05) ya guarda quien movio que
-- fecha y desde donde, y a este script lo acompania sumarle
-- FECHA_EST_PAGO a lo que registra. Seria tentador deducir de ahi si la
-- fecha esta fijada: "hay un cambio manual" -> esta fijada.
--
-- No alcanza. El recalculo automatico y la edicion manual llegan al
-- backend POR EL MISMO POST del formulario de gestion de despachos: en
-- los dos casos el navegador manda un FECHA_EST_PAGO distinto al que
-- habia, y el historial registra exactamente la misma fila. El rastro es
-- INDISTINGUIBLE, asi que un flag derivado marcaria como manual toda
-- fecha que el JS haya recalculado alguna vez.
--
-- Distinguirlos de verdad exigiria mover el calculo de +5 dias del JS al
-- backend, que es lo unico que le permitiria al servidor saber quien
-- propuso cada valor. Eso queda como deuda anotada y fuera de esta
-- entrega: el BIT resuelve el problema sin tocar donde se calcula.
--
-- EL BIT Y EL RASTRO DEL CASHFLOW NO SON LO MISMO, y por eso conviven:
--
--     RO_T_CASHFLOW_COMEX_FECHA_EDIT   dice QUIEN la movio y DESDE DONDE
--     FECHA_PAGO_CONF                  dice SI ESTA FIJADA
--
-- Una fecha fijada desde Comercio Exterior no deja rastro en la tabla del
-- cashflow -es de otra aplicacion- y aun asi tiene que quedar protegida.
--
-- ---------------------------------------------------------------------
-- POR QUE NO HAY UN BIT EQUIVALENTE PARA FECHA_DESP_ADU
--
-- Porque la nacionalizacion no la recalcula nadie en contra del usuario:
-- fechaDespachoIsManual se enciende al cargar los datos de la base
-- -cargaInicial.js- asi que una fecha guardada ya queda protegida. El
-- problema es especifico de FECHA_EST_PAGO, que es la unica que el JS
-- vuelve a calcular sobre datos ya guardados. Agregar el BIT igual seria
-- cargar la tabla con una columna que nada lee.
--
-- ---------------------------------------------------------------------
-- SI ESTE SCRIPT NO SE CORRIO, NADA SE ROMPE
--
-- Las dos aplicaciones preguntan por la columna antes de usarla -mismo
-- criterio que AlicuotasVigencia::disponible() y que
-- Comex::tieneCotizEdit()- y sin ella se comportan exactamente como hoy:
-- Comercio Exterior recalcula siempre y el cashflow marca "Editada" con
-- Comex::marcaVigente() como venia haciendo.
-- =====================================================================

SET NOCOUNT ON;
GO

-- ---------------------------------------------------------------------
-- 1) Las tres columnas.
--
-- FECHA_PAGO_CONF es NOT NULL con DEFAULT 0 y no NULL-able con tres
-- estados: "no se sabe" no es un estado util aca. Toda fila que ya existe
-- nace en 0 -automatica, que es lo que la aplicacion afirma hoy- y el
-- paso 2 sube a 1 solo las que se puede demostrar que alguien movio.
--
-- USUARIO y FECHA quedan NULL-ables porque describen un hecho que puede
-- no haber ocurrido: con CONF = 0 no hay nadie que haya fijado nada. Y
-- USUARIO puede quedar en NULL incluso con CONF = 1, porque
-- comercioExterior todavia no tiene login y $_SESSION['usuario_dns'] no
-- se puebla en este modulo -mismo caso que
-- CronogramaFechas::registrarHistorial()-.
--
-- VARCHAR(50) para el usuario: es el ancho que ya usan
-- RO_T_IMPORTACIONES_FECHAS_HIST.USUARIO y
-- RO_T_CASHFLOW_COMEX_FECHA_EDIT.USUARIO.
-- ---------------------------------------------------------------------
IF COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO', 'FECHA_PAGO_CONF') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_IMPORTACIONES_ENCABEZADO
        ADD FECHA_PAGO_CONF BIT NOT NULL
                CONSTRAINT DF_RO_T_IMP_ENC_FECHA_PAGO_CONF DEFAULT 0;

    PRINT 'FECHA_PAGO_CONF: columna creada.';
END
ELSE
    PRINT 'FECHA_PAGO_CONF: ya existia, no se toca.';
GO

IF COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO', 'FECHA_PAGO_CONF_USUARIO') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_IMPORTACIONES_ENCABEZADO
        ADD FECHA_PAGO_CONF_USUARIO VARCHAR(50) NULL;

    PRINT 'FECHA_PAGO_CONF_USUARIO: columna creada.';
END
ELSE
    PRINT 'FECHA_PAGO_CONF_USUARIO: ya existia, no se toca.';
GO

IF COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO', 'FECHA_PAGO_CONF_FECHA') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_IMPORTACIONES_ENCABEZADO
        ADD FECHA_PAGO_CONF_FECHA DATETIME NULL;

    PRINT 'FECHA_PAGO_CONF_FECHA: columna creada.';
END
ELSE
    PRINT 'FECHA_PAGO_CONF_FECHA: ya existia, no se toca.';
GO

-- ---------------------------------------------------------------------
-- 2) Backfill: las fechas que YA estan puestas a mano desde el cashflow.
--
-- QUE SE MARCA. Los contenedores con un rastro VIGENTE de CAMPO = 'PAGO'
-- en RO_T_CASHFLOW_COMEX_FECHA_EDIT cuya FECHA_NUEVA coincida con el
-- FECHA_EST_PAGO que hoy tiene el maestro.
--
-- Es exactamente la regla de Comex::marcaVigente(), y la coincidencia es
-- la parte que importa: el rastro dice "el cashflow escribio esta fecha",
-- pero si despues la app de Comercio Exterior la movio, el rastro sigue
-- siendo cierto y ya NO describe lo que hay en la celda. Marcar esas como
-- fijadas congelaria una fecha que nadie fijo.
--
-- POR QUE ESTAS Y NO OTRAS. Son las unicas que se puede demostrar que
-- movio una persona. Las demas pueden haber sido editadas a mano desde
-- Comercio Exterior -no hay forma de saberlo, es justamente el agujero
-- que estas columnas vienen a tapar- y marcarlas por las dudas dejaria
-- fijada media base, rompiendo el recalculo automatico para todos.
-- Subestimar es recuperable desde la pantalla con un clic; sobrestimar
-- exige revisar contenedor por contenedor.
--
-- CAST A DATE en los dos lados: FECHA_NUEVA es DATE y FECHA_EST_PAGO en
-- el maestro puede ser DATETIME. Sin el cast, una fecha guardada con hora
-- distinta de medianoche nunca empareja.
--
-- USUARIO Y FECHA salen del rastro, que es donde esta el dato real de
-- quien la movio y cuando. Inventar 'BACKFILL' seria perder el unico dato
-- bueno que hay.
--
-- SOLO SUBE, NUNCA BAJA: el WHERE pide FECHA_PAGO_CONF = 0, asi que una
-- segunda corrida no pisa nada de lo que se haya fijado desde la
-- pantalla, ni revierte una que alguien haya vuelto a auto.
--
-- SI LA TABLA DEL CASHFLOW NO EXISTE EN ESTA BASE -es el caso de uy, y de
-- cualquier instalacion donde no se corrio
-- finanzas/sql/cashflow_comex_fecha_maestra.sql- el backfill se saltea
-- con un PRINT y el script termina bien. No hay nada que migrar: sin esa
-- tabla nunca se edito una fecha desde el cashflow.
-- ---------------------------------------------------------------------
IF OBJECT_ID('dbo.RO_T_CASHFLOW_COMEX_FECHA_EDIT', 'U') IS NULL
BEGIN
    PRINT 'Backfill SALTEADO: RO_T_CASHFLOW_COMEX_FECHA_EDIT no existe en esta base.';
    PRINT '  (No hay nada que migrar: sin esa tabla nunca se edito una fecha desde el cashflow.)';
END
ELSE
BEGIN
    BEGIN TRANSACTION;

    BEGIN TRY
        /* SQL dinamico porque este bloque se compila aunque la rama no se
           ejecute: sin esto, en una base sin la tabla el script falla al
           parsear con "Invalid object name", que es justo lo que el IF de
           arriba viene a evitar. Mismo motivo por el que las columnas
           nuevas se referencian por nombre completo. */
        DECLARE @marcados INT = 0;

        DECLARE @sql NVARCHAR(MAX) = N'
            UPDATE A
               SET A.FECHA_PAGO_CONF         = 1,
                   A.FECHA_PAGO_CONF_USUARIO = E.USUARIO,
                   A.FECHA_PAGO_CONF_FECHA   = E.FECHA_ALTA
              FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO A
             INNER JOIN dbo.RO_T_CASHFLOW_COMEX_FECHA_EDIT E
                     ON E.ID_MG   = A.ID
                    AND E.CAMPO   = ''PAGO''
                    AND E.VIGENTE = 1
             WHERE A.FECHA_PAGO_CONF = 0
               AND A.FECHA_EST_PAGO IS NOT NULL
               AND CAST(A.FECHA_EST_PAGO AS DATE) = CAST(E.FECHA_NUEVA AS DATE);

            SET @salida = @@ROWCOUNT;';

        /* El conteo sale por OUTPUT y no de leer @@ROWCOUNT despues del EXEC:
           asi no depende de que entre el UPDATE y la lectura no se cuele
           ninguna otra sentencia. */
        EXEC sp_executesql @sql, N'@salida INT OUTPUT', @salida = @marcados OUTPUT;

        PRINT 'Backfill: contenedores marcados como fecha de pago fijada a mano: '
              + CAST(@marcados AS VARCHAR(10));

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;

        PRINT 'Backfill ABORTADO, no se escribio nada: ' + ERROR_MESSAGE();
    END CATCH
END
GO

-- ---------------------------------------------------------------------
-- 3) Control: como quedo el padron.
-- ---------------------------------------------------------------------
PRINT '--- Estado de FECHA_PAGO_CONF ---';
GO

SELECT
    CASE WHEN FECHA_PAGO_CONF = 1 THEN 'Fijada a mano' ELSE 'Automatica (+5 dias)' END AS ESTADO,
    COUNT(*)                                        AS CONTENEDORES,
    SUM(CASE WHEN FECHA_EST_PAGO IS NULL THEN 1 ELSE 0 END) AS SIN_FECHA
FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
GROUP BY FECHA_PAGO_CONF;
GO

PRINT '--- Detalle de las fijadas a mano (si no lista nada, no hay) ---';
GO

SELECT
    ID,
    ORDEN_COMPRA,
    CONTENEDOR,
    FECHA_EST_PAGO,
    FECHA_PAGO_CONF_USUARIO,
    FECHA_PAGO_CONF_FECHA
FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
WHERE FECHA_PAGO_CONF = 1
ORDER BY FECHA_PAGO_CONF_FECHA DESC, ID;
GO

PRINT 'Comercio Exterior: fecha estimada de pago fijable a mano, lista.';
GO
