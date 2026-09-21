/*
================================================================================
    LIQUIDACION SEMANAL FRANQUICIAS GA  --  PASO 4: JOB DE SQL SERVER AGENT
================================================================================

    NO INSTALAR SIN LEER ESTO PRIMERO.

    QUE HACE
    --------
    Todos los lunes a las 06:00 ejecuta RO_SP_FRANQ_GA_GENERAR_LOTE SIN
    parametros de fecha. El SP calcula por su cuenta el lunes a domingo
    inmediatos anteriores, incorpora los rezagados y congela los precios de la
    Lista 30 vigentes en ese momento.

    DONDE CORRE
    -----------
    El job se crea en la instancia XL-LAKERBIS (que es donde estan el SP y las
    tablas), con @database_name = 'FRANQUICIAS_LAKERS'. Correr este script
    conectado a XL-LAKERBIS, apuntando a msdb.

    IMPORTANTE: NO se crea en XL-TANGO. Si se instalara alla habria que llamar
    al SP por linked server y la transaccion pasaria a ser distribuida (MSDTC),
    que es exactamente el problema que no queremos tener un lunes a la mañana.

    POR QUE 06:00
    -------------
    El SP toma comprobantes con FECHA_EMIS <= domingo. A las 06:00 del lunes el
    cierre del domingo ya replico y todavia no hay movimiento del lunes que
    pueda confundirse. Si se corre mas temprano hay riesgo de que falten
    comprobantes del domingo a la noche; esos NO se pierden (entrarian como
    rezagados la semana siguiente), pero la liquidacion saldria incompleta.

    ES SEGURO QUE CORRA DOS VECES
    -----------------------------
    RO_SP_FRANQ_GA_GENERAR_LOTE es idempotente: si ya existe un lote no anulado
    para el periodo, no inserta nada y devuelve el existente. Reintentar el job
    a mano despues de una falla no duplica nada.

    QUE PASA SI FALLA
    -----------------
    El paso esta configurado con 2 reintentos cada 10 minutos. Si igual falla,
    el job queda en error y la pantalla tiene el boton "Generar lote manual"
    para reprocesar sin depender del Agent.

    NOTIFICACION POR MAIL: viene COMENTADA al final. Requiere Database Mail
    configurado y un operador creado. Descomentar solo si esas dos cosas existen
    -- si no, el sp_update_job falla y deja el job a medio configurar.

    IDEMPOTENTE: si el job ya existe, lo borra y lo vuelve a crear.
================================================================================
*/

USE [msdb];
GO

SET NOCOUNT ON;
GO

DECLARE @NombreJob     SYSNAME       = N'RO_FRANQ_GA_LIQUIDACION_SEMANAL';
DECLARE @BaseDestino   SYSNAME       = N'FRANQUICIAS_LAKERS';
DECLARE @Duenio        SYSNAME       = N'sa';
DECLARE @JobId         BINARY(16);

/* ---------------------------------------------------------------------------
   0. Chequeos previos. Mejor fallar aca con un mensaje claro que crear un job
      que va a reventar todos los lunes a las 6 de la mañana.
--------------------------------------------------------------------------- */
IF DB_ID(@BaseDestino) IS NULL
BEGIN
    RAISERROR('No existe la base %s en esta instancia. Estas conectado al servidor correcto (XL-LAKERBIS)?', 16, 1, @BaseDestino);
    RETURN;
END

DECLARE @SqlChequeo NVARCHAR(MAX) = N'
    IF NOT EXISTS (SELECT 1 FROM ' + QUOTENAME(@BaseDestino) + N'.sys.procedures
                    WHERE name = ''RO_SP_FRANQ_GA_GENERAR_LOTE'')
        RAISERROR(''Falta RO_SP_FRANQ_GA_GENERAR_LOTE. Corre antes 02_RO_SP_FRANQ_GA.sql.'', 16, 1);';
EXEC sp_executesql @SqlChequeo;

IF NOT EXISTS (SELECT 1 FROM sys.syslogins WHERE name = @Duenio)
BEGIN
    RAISERROR('No existe el login %s para ser dueño del job. Ajusta @Duenio.', 16, 1, @Duenio);
    RETURN;
END

/* ---------------------------------------------------------------------------
   1. Si el job ya existe, se borra para recrearlo limpio.
--------------------------------------------------------------------------- */
IF EXISTS (SELECT 1 FROM msdb.dbo.sysjobs WHERE name = @NombreJob)
BEGIN
    PRINT 'El job ya existia. Se elimina para recrearlo.';
    EXEC msdb.dbo.sp_delete_job @job_name = @NombreJob, @delete_unused_schedule = 1;
END

/* ---------------------------------------------------------------------------
   2. Job
--------------------------------------------------------------------------- */
EXEC msdb.dbo.sp_add_job
     @job_name        = @NombreJob,
     @enabled         = 1,
     @owner_login_name = @Duenio,
     @description     = N'Genera la liquidacion semanal del canal FRANQUICIAS GA (lunes a domingo anterior). Idempotente: si el lote del periodo ya existe, no duplica. Ver tesoreria/cobranzas/sql/.',
     @notify_level_eventlog = 2,   /* 2 = registrar en el event log solo si falla */
     @job_id          = @JobId OUTPUT;

/* ---------------------------------------------------------------------------
   3. Paso unico
      Se usa EXEC sin parametros a proposito: el SP resuelve el periodo. Asi la
      fecha la calcula un solo lugar y el job no puede quedar desfasado.
--------------------------------------------------------------------------- */
EXEC msdb.dbo.sp_add_jobstep
     @job_id           = @JobId,
     @step_name        = N'Generar lote de liquidacion',
     @step_id          = 1,
     @subsystem        = N'TSQL',
     @database_name    = @BaseDestino,
     @command          = N'
SET NOCOUNT ON;
SET XACT_ABORT ON;

DECLARE @IdLote INT, @Importe DECIMAL(22,7), @Renglones INT, @Rezagados INT,
        @YaExistia BIT, @SinAlta INT;

/*  El SP devuelve un result set. Se lo captura en una tabla temporal para
    poder loguear el resultado en el historial del job: si no, el operador ve
    "exito" sin saber si genero algo o si el lote ya estaba.                 */
CREATE TABLE #Resultado (
    ID_LOTE           INT,
    PERIODO_DESDE     DATE,
    PERIODO_HASTA     DATE,
    FECHA_EJECUCION   DATETIME,
    NRO_LISTA         SMALLINT,
    ESTADO            VARCHAR(20),
    IMPORTE_TOTAL     DECIMAL(22,7),
    USUARIO_GENERA    VARCHAR(50),
    YA_EXISTIA        BIT,
    CANT_RENGLONES    INT,
    CANT_COMPROBANTES INT,
    CANT_SUCURSALES   INT,
    CANT_REZAGADOS    INT,
    CANT_SIN_PRECIO   INT,
    /*  Si cambia el result set de RO_SP_FRANQ_GA_GENERAR_LOTE hay que tocar
        esta tabla tambien: el INSERT ... EXEC exige que coincidan exactamente. */
    CANT_SUCURSALES_SIN_ALTA INT
);

INSERT INTO #Resultado
EXEC dbo.RO_SP_FRANQ_GA_GENERAR_LOTE;

SELECT @IdLote    = ID_LOTE,
       @Importe   = IMPORTE_TOTAL,
       @Renglones = CANT_RENGLONES,
       @Rezagados = CANT_REZAGADOS,
       @YaExistia = YA_EXISTIA,
       @SinAlta   = CANT_SUCURSALES_SIN_ALTA
FROM   #Resultado;

IF @IdLote IS NULL
BEGIN
    RAISERROR(''El SP no devolvio un ID de lote. Revisar RO_T_FRANQ_GA_LOTE.'', 16, 1);
    RETURN;
END

DECLARE @Msg NVARCHAR(500) =
    CASE WHEN @YaExistia = 1
         THEN N''Ya existia el lote #'' + CAST(@IdLote AS NVARCHAR(20)) + N'' para el periodo. No se genero nada nuevo.''
         ELSE N''Lote #'' + CAST(@IdLote AS NVARCHAR(20)) + N'' generado. Importe: '' + CAST(@Importe AS NVARCHAR(40))
              + N'' | Renglones: '' + CAST(@Renglones AS NVARCHAR(20))
              + N'' | Rezagados: '' + CAST(@Rezagados AS NVARCHAR(20))
    END;

PRINT @Msg;

/*  Una franquicia del canal sin fecha de alta NO se liquida. El job no falla
    por eso -- el resto de la liquidacion es valida -- pero tiene que quedar
    visible en el historial para que alguien la de de alta.                  */
IF @SinAlta > 0
    PRINT N''ATENCION: hay '' + CAST(@SinAlta AS NVARCHAR(10)) +
          N'' franquicia(s) del canal FRANQUICIAS GA sin fila en '' +
          N''RO_T_FRANQ_GA_SUCURSAL_INICIO. NO se liquidaron.'';

DROP TABLE #Resultado;
',
     @on_success_action = 1,    /* 1 = terminar informando exito */
     @on_fail_action    = 2,    /* 2 = terminar informando falla */
     @retry_attempts    = 2,
     @retry_interval    = 10;   /* minutos */

/* ---------------------------------------------------------------------------
   4. Schedule: todos los lunes 06:00
      freq_type 8 = semanal | freq_interval 2 = lunes (mascara de bits:
      domingo=1, lunes=2, martes=4, ...) | freq_recurrence_factor 1 = cada semana
--------------------------------------------------------------------------- */
EXEC msdb.dbo.sp_add_jobschedule
     @job_id                = @JobId,
     @name                  = N'RO_FRANQ_GA_LUNES_0600',
     @enabled               = 1,
     @freq_type             = 8,
     @freq_interval         = 2,
     @freq_recurrence_factor = 1,
     @active_start_time     = 60000;   /* 06:00:00 en formato HHMMSS */

/* ---------------------------------------------------------------------------
   5. Servidor donde corre
--------------------------------------------------------------------------- */
EXEC msdb.dbo.sp_add_jobserver
     @job_id      = @JobId,
     @server_name = N'(local)';

PRINT 'Job ' + @NombreJob + ' creado correctamente.';
GO


/* ---------------------------------------------------------------------------
   6. NOTIFICACION POR MAIL -- OPCIONAL, DESCOMENTAR SOLO SI CORRESPONDE

   Requiere Database Mail configurado en XL-LAKERBIS y un operador ya creado.
   Si alguna de las dos cosas falta, sp_update_job falla y el job queda a medio
   configurar. Verificar primero:

       SELECT name FROM msdb.dbo.sysoperators;
       SELECT is_broker_enabled FROM sys.databases WHERE name = 'msdb';

   Despues reemplazar 'NOMBRE_DEL_OPERADOR' y descomentar:

   EXEC msdb.dbo.sp_update_job
        @job_name          = N'RO_FRANQ_GA_LIQUIDACION_SEMANAL',
        @notify_level_email = 2,                      -- 2 = solo si falla
        @notify_email_operator_name = N'NOMBRE_DEL_OPERADOR';
--------------------------------------------------------------------------- */


/* ---------------------------------------------------------------------------
   VERIFICACION -- correr despues e inspeccionar la salida.
   PROXIMA_EJECUCION deberia caer el lunes siguiente a las 06:00.
--------------------------------------------------------------------------- */
SELECT  j.name                          AS JOB,
        j.enabled                       AS HABILITADO,
        s.name                          AS SCHEDULE,
        s.freq_type                     AS FREQ_TYPE_8_ES_SEMANAL,
        s.freq_interval                 AS FREQ_INTERVAL_2_ES_LUNES,
        STUFF(STUFF(RIGHT('000000' + CAST(s.active_start_time AS VARCHAR(6)), 6), 5, 0, ':'), 3, 0, ':') AS HORA,
        js.next_run_date                AS PROXIMA_FECHA,
        js.next_run_time                AS PROXIMA_HORA,
        st.database_name                AS BASE,
        st.retry_attempts               AS REINTENTOS
FROM        msdb.dbo.sysjobs j
LEFT JOIN   msdb.dbo.sysjobschedules js ON js.job_id = j.job_id
LEFT JOIN   msdb.dbo.sysschedules s     ON s.schedule_id = js.schedule_id
LEFT JOIN   msdb.dbo.sysjobsteps st     ON st.job_id = j.job_id AND st.step_id = 1
WHERE       j.name = N'RO_FRANQ_GA_LIQUIDACION_SEMANAL';
GO

/*
    PRUEBA MANUAL (sin esperar al lunes). Es seguro: por idempotencia, si el
    lote de la semana ya existe no se duplica nada.

        EXEC msdb.dbo.sp_start_job @job_name = N'RO_FRANQ_GA_LIQUIDACION_SEMANAL';

    Y para ver como salio:

        SELECT TOP 10 h.run_date, h.run_time, h.run_status, h.message
        FROM   msdb.dbo.sysjobhistory h
        JOIN   msdb.dbo.sysjobs j ON j.job_id = h.job_id
        WHERE  j.name = N'RO_FRANQ_GA_LIQUIDACION_SEMANAL'
        ORDER BY h.instance_id DESC;

    Para desinstalar el job:

        EXEC msdb.dbo.sp_delete_job
             @job_name = N'RO_FRANQ_GA_LIQUIDACION_SEMANAL',
             @delete_unused_schedule = 1;
*/
