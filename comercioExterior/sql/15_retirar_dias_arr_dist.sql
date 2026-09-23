-- =====================================================================
-- Comercio Exterior - DIAS_ARR_DIST se retira: la distribucion cuelga
-- siempre de la recepcion
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente. NO BORRA LA FILA: la marca como sin uso.
--
-- ---------------------------------------------------------------------
-- POR QUE SE RETIRA
--
-- DIAS_ARR_DIST valia 10 y el script 04 lo describio como "distribucion
-- estimada, un dia despues de la recepcion". Eso era cierto CON LA CADENA
-- VIEJA:
--
--     nacionalizacion = arribo + 7
--     recepcion       = arribo + 9
--     distribucion    = arribo + 10   <- un dia despues, efectivamente
--
-- Con la cadena nueva la nacionalizacion es arribo + 5, asi que la
-- recepcion estimada es arribo + 7 y "un dia despues" seria arribo + 8. El
-- 10 dejo de describir nada: son dos dias de aire que nadie decidio.
--
-- POR QUE ELIMINARLO EN VEZ DE BAJARLO A 8. Un 8 daria hoy el mismo
-- resultado, pero volveria a quedar desactualizado EN SILENCIO la proxima
-- vez que alguien toque DIAS_ARR_DESP o DIAS_DESP_REC desde el ABM. Es
-- exactamente el modo de falla que tenian el 45 / 7 / 2 repartidos por el
-- codigo: un numero plausible que describe una cadena que ya cambio.
-- Derivando la distribucion de la recepcion no hay nada que mantener
-- sincronizado.
--
-- ---------------------------------------------------------------------
-- POR QUE NO SE BORRA LA FILA
--
-- Porque un DELETE no deja rastro de que existio: dentro de seis meses,
-- alguien que lea el script 04 va a buscar DIAS_ARR_DIST en la tabla, no lo
-- va a encontrar y no va a tener forma de saber si se retiro a proposito o
-- si nunca se sembro en esta base. La fila con DESCRIPCION = 'SIN USO ...'
-- contesta esa pregunta sola.
--
-- El ABM la muestra tachada y deshabilitada -paramCronograma.js detecta el
-- prefijo 'SIN USO'- asi que nadie la puede ajustar esperando un efecto, y
-- actualizarParamCronograma.php ya la saco de la whitelist.
--
-- ---------------------------------------------------------------------
-- QUE PASA CON LAS FECHA_DISTRI YA GUARDADAS
--
-- SE MUEVEN SOLAS, y este script no las toca. Con DIST_ORIGEN = 'A' la
-- columna es apenas una cache: CronogramaFechas::derivarDistribucion() la
-- recalcula EN CADA LECTURA, asi que el calendario muestra la fecha nueva
-- desde el primer render. La columna se pone al dia sola la proxima vez que
-- se mueva el arribo de ese contenedor.
--
-- Las 'M' (movida a mano) y 'C' (confirmada) no se derivan ni se recalculan
-- nunca: son decisiones humanas.
--
-- NO AFECTA AL CASHFLOW DE ProyectosXL/finanzas: sus dos pestanas leen
-- FECHA_EST_PAGO y FECHA_DESP_ADU, no FECHA_DISTRI.
--
-- El control del final lista cuanto se mueve cada contenedor, para que el
-- cambio no sorprenda a nadie de abastecimiento.
-- =====================================================================

SET NOCOUNT ON;
GO

IF OBJECT_ID('RO_T_IMPORTACIONES_PARAM_CRONOGRAMA', 'U') IS NULL
BEGIN
    RAISERROR('Falta RO_T_IMPORTACIONES_PARAM_CRONOGRAMA. Corré primero 04_cronograma_parametros_dias.sql.', 16, 1);
END
GO

-- ---------------------------------------------------------------------
-- 1) Marcar la fila como retirada.
--
-- Se conserva el VALOR: es el ultimo valor que estuvo vigente y sirve para
-- entender las FECHA_DISTRI viejas que se calcularon con el.
-- ---------------------------------------------------------------------
UPDATE RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
   SET DESCRIPCION = 'SIN USO desde el script 15: la distribucion se deriva de la recepcion (DIAS_REC_DIST)',
       USUARIO     = 'script 15',
       FECHA_MOD   = GETDATE()
 WHERE CLAVE = 'DIAS_ARR_DIST'
   AND DESCRIPCION NOT LIKE 'SIN USO%';

PRINT 'DIAS_ARR_DIST: ' + CASE WHEN @@ROWCOUNT > 0
        THEN 'marcado como SIN USO.'
        ELSE 'ya estaba marcado (o no existe en esta base), no se toca.' END;
GO

-- ---------------------------------------------------------------------
-- 2) Control: cuanto se mueve la distribucion proyectada.
--
-- Compara la regla vieja -arribo + DIAS_ARR_DIST- contra la nueva -arribo +
-- DIAS_ARR_DESP + DIAS_DESP_REC + DIAS_REC_DIST- sobre los contenedores que
-- todavia no se recibieron y cuya distribucion es automatica. Son los
-- unicos que se derivan; el resto muestra lo guardado.
--
-- No escribe nada: es para poder avisar de cuanto es el corrimiento.
-- ---------------------------------------------------------------------
PRINT '--- Corrimiento de la distribucion proyectada ---';
GO

DECLARE @VIEJO INT = (SELECT VALOR FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA WHERE CLAVE = 'DIAS_ARR_DIST');
DECLARE @NUEVO INT = (
    SELECT SUM(VALOR) FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
     WHERE CLAVE IN ('DIAS_ARR_DESP', 'DIAS_DESP_REC', 'DIAS_REC_DIST')
);

IF @VIEJO IS NULL OR @NUEVO IS NULL
BEGIN
    PRINT 'No se puede comparar: falta alguno de los parametros. Corré el 12 en esta base.';
END
ELSE
BEGIN
    PRINT 'Regla vieja: arribo + ' + CAST(@VIEJO AS VARCHAR(10))
        + ' | Regla nueva: arribo + ' + CAST(@NUEVO AS VARCHAR(10))
        + ' (corrimiento de ' + CAST(@NUEVO - @VIEJO AS VARCHAR(10)) + ' dias)';

    SELECT
        COUNT(*)                                        AS CONTENEDORES_AFECTADOS,
        MIN(DATEADD(day, @VIEJO, FECHA_ARR))            AS PRIMERA_DISTRI_VIEJA,
        MIN(DATEADD(day, @NUEVO, FECHA_ARR))            AS PRIMERA_DISTRI_NUEVA
    FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
    WHERE DIST_ORIGEN    = 'A'
      AND FECHA_RECIBIDO IS NULL
      AND FECHA_ARR      IS NOT NULL;

    -- Detalle, para abastecimiento.
    SELECT TOP 100
        ID,
        ORDEN_COMPRA,
        CONTENEDOR,
        FECHA_ARR,
        DATEADD(day, @VIEJO, FECHA_ARR) AS DISTRI_ANTES,
        DATEADD(day, @NUEVO, FECHA_ARR) AS DISTRI_AHORA
    FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO
    WHERE DIST_ORIGEN    = 'A'
      AND FECHA_RECIBIDO IS NULL
      AND FECHA_ARR      IS NOT NULL
    ORDER BY FECHA_ARR, ID;
END
GO

PRINT 'Comercio Exterior: DIAS_ARR_DIST retirado.';
GO
