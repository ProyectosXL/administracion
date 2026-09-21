/*
================================================================================
    LIQUIDACION SEMANAL FRANQUICIAS GA  --  PASO 2: STORED PROCEDURES
================================================================================

    BASE DESTINO:  [XL-LAKERBIS].[FRANQUICIAS_LAKERS]
    Correr CONECTADO a FRANQUICIAS_LAKERS.
    Requiere 01_RO_T_FRANQ_GA_DDL.sql, 04_RO_T_FRANQ_GA_SUCURSAL_INICIO.sql
    y 06_RO_T_FRANQ_GA_LOTE_ENVIO.sql.

    Objetos que crea (todos CREATE OR ALTER, se puede recorrer varias veces):
      RO_SP_FRANQ_GA_GENERAR_LOTE
      RO_SP_FRANQ_GA_RESUMEN
      RO_SP_FRANQ_GA_DETALLE
      RO_SP_FRANQ_GA_RECIBOS_CANDIDATOS
      RO_SP_FRANQ_GA_RECALCULAR_ESTADO_LOTE   (auxiliar, ver nota 4)
      RO_SP_FRANQ_GA_VINCULAR_RECIBO
      RO_SP_FRANQ_GA_DESVINCULAR_RECIBO

    NOTAS DE DISEÑO
    ---------------
    1) CTA02 / CTA03 / GVA17 / GVA12 son LOCALES a esta base. El unico cruce es
       [LOCALES_LAKERS].DBO.SUCURSALES_LAKERS: misma instancia (XL-LAKERBIS),
       cross-database, sin linked server.

    2) COLLATIONS. Las columnas de Tango son Latin1_General_BIN y las nuestras
       Modern_Spanish_CI_AI. En las comparaciones columna-vs-literal no hay
       conflicto (el literal es coercible y adopta la collation de la columna).
       El COLLATE explicito se pone solo donde se comparan DOS columnas de
       collation distinta -- hoy, unicamente SUCURSALES_LAKERS.CANAL.
       El join por sucursal (NRO_SUCURS smallint = NRO_SUCURSAL int) es
       NUMERICO: no lleva ni puede llevar COLLATE.

       OJO CON Latin1_General_BIN: es case y accent SENSITIVE. Por eso
       'FAC' <> 'fac' y LIKE '[FN]%' solo matchea F y N mayusculas. Tango
       graba T_COMP en mayusculas (verificado), asi que es correcto, pero si
       algun dia aparece un T_COMP en minuscula va a quedar afuera en silencio.

    3) NOTAS DE CREDITO. La regla original era T_COMP LIKE 'NC%', que NO
       matchea 'N/C' -- y en esta base 'N/C' tiene 4.353 comprobantes vigentes
       (ultimo 2026-09-18). Con LIKE 'NC%' esas notas de credito SUMARIAN en
       vez de restar. Por eso la condicion es una LISTA EXPLICITA:

           T_COMP IN ('NCR', 'NCP', 'NCI', 'NCD', 'N/C')

       Si aparece un tipo de nota de credito nuevo hay que agregarlo ACA, en
       RO_SP_FRANQ_GA_GENERAR_LOTE, que es el unico lugar donde se aplica el
       signo. Se eligio lista explicita en vez de patron justamente para que un
       T_COMP nuevo quede visible como error de importe y no se cuele sumando.

    4) RO_SP_FRANQ_GA_RECALCULAR_ESTADO_LOTE no estaba en el pedido. Se agrego
       porque vincular y desvincular necesitan exactamente la misma regla de
       recalculo, y duplicarla en dos SP es la forma de que se desincronicen.
       No se llama nunca desde PHP: es interno de los otros dos.

    5) El estado 'COBRADO' es del LOTE COMPLETO (todas las franquicias del
       periodo), porque el lote es por periodo y el detalle es el que
       discrimina por sucursal. RO_SP_FRANQ_GA_RESUMEN devuelve ademas un
       ESTADO_SUCURSAL calculado por franquicia (PENDIENTE / PARCIAL /
       COBRADO), que es lo que conviene mostrar en la grilla.
================================================================================
*/

SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO


/* ============================================================================
   1. RO_SP_FRANQ_GA_GENERAR_LOTE
   ----------------------------------------------------------------------------
   Genera el lote de un periodo. Sin parametros de fecha calcula el lunes a
   domingo inmediatos anteriores (asi lo llama el job).

   IDEMPOTENTE: si ya hay un lote no anulado para ese periodo, no inserta nada
   y devuelve el existente con YA_EXISTIA = 1.

   REZAGADOS: entra todo comprobante con FECHA_EMIS <= @Hasta que no este ya en
   RO_T_FRANQ_GA_LOTE_DETALLE. Los anteriores a @Desde se marcan ES_REZAGADO=1.
   Quien garantiza que nada se liquide dos veces es el indice unico
   UX_RO_T_FRANQ_GA_DET_COMPROBANTE; el NOT EXISTS es para no chocar contra el.

   PISO POR SUCURSAL: los rezagados se cortan en la FECHA_INICIO que cada
   franquicia tiene en RO_T_FRANQ_GA_SUCURSAL_INICIO. Sin ese piso, el PRIMER
   lote de una sucursal se lleva toda su historia -- que fue lo que paso con la
   501, cuyos tres primeros comprobantes eran pruebas de puesta en marcha.

   El JOIN con esa tabla es INNER a proposito: una sucursal del canal sin fila
   de alta NO se liquida. Es preferible eso a liquidarla de mas barriendo su
   historial. Para que no pase en silencio, el result set devuelve
   CANT_SUCURSALES_SIN_ALTA y la pantalla lo muestra como advertencia.

   PRECIO: Lista 30 VIGENTE AL MOMENTO DE EJECUTAR, congelado en el detalle.
============================================================================ */
CREATE OR ALTER PROCEDURE RO_SP_FRANQ_GA_GENERAR_LOTE
    @Desde    DATE        = NULL,
    @Hasta    DATE        = NULL,
    @Usuario  VARCHAR(50) = NULL,
    @NroLista SMALLINT    = 30
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    DECLARE @IdLote    INT = NULL,
            @YaExistia BIT = 0;

    /*  Periodo por defecto: lunes a domingo inmediatos anteriores.
        El calculo se ancla en 1900-01-01, que fue lunes, y NO depende de
        SET DATEFIRST ni del idioma de la sesion -- importante porque el job
        del Agent corre con la configuracion de su propia cuenta de servicio. */
    IF @Desde IS NULL OR @Hasta IS NULL
    BEGIN
        DECLARE @LunesDeEstaSemana DATE =
            DATEADD(DAY,
                    DATEDIFF(DAY, CAST('19000101' AS DATE), CAST(GETDATE() AS DATE)) / 7 * 7,
                    CAST('19000101' AS DATE));

        SET @Desde = DATEADD(DAY, -7, @LunesDeEstaSemana);
        SET @Hasta = DATEADD(DAY,  6, @Desde);
    END

    IF @Hasta < @Desde
        THROW 50001, 'RO_SP_FRANQ_GA_GENERAR_LOTE: periodo invalido, @Hasta es anterior a @Desde.', 1;

    IF @NroLista IS NULL
        THROW 50002, 'RO_SP_FRANQ_GA_GENERAR_LOTE: @NroLista no puede ser NULL.', 1;

    /*  Idempotencia. Se ignoran los ANULADOS a proposito: un lote anulado deja
        el periodo libre para regenerarse (igual criterio que el indice
        filtrado UX_RO_T_FRANQ_GA_LOTE_PERIODO).                              */
    SELECT TOP 1 @IdLote = L.ID_LOTE
    FROM   RO_T_FRANQ_GA_LOTE L
    WHERE  L.PERIODO_DESDE = @Desde
      AND  L.PERIODO_HASTA = @Hasta
      AND  L.ESTADO <> 'ANULADO'
    ORDER BY L.ID_LOTE;

    IF @IdLote IS NOT NULL
        SET @YaExistia = 1;

    IF @YaExistia = 0
    BEGIN
        BEGIN TRY
            BEGIN TRANSACTION;

            INSERT INTO RO_T_FRANQ_GA_LOTE
                (PERIODO_DESDE, PERIODO_HASTA, NRO_LISTA, ESTADO, IMPORTE_TOTAL, USUARIO_GENERA)
            VALUES
                (@Desde, @Hasta, @NroLista, 'GENERADO', 0, ISNULL(@Usuario, 'JOB'));

            SET @IdLote = CAST(SCOPE_IDENTITY() AS INT);

            /*  El GROUP BY por (NRO_SUCURS, T_COMP, N_COMP, COD_ARTICU) no es
                cosmetico: es exactamente la clave del indice unico. Hoy CTA03
                no tiene el mismo articulo repetido dentro de un comprobante
                (verificado: 0 casos), pero si algun dia lo tuviera, sin este
                GROUP BY el INSERT entero fallaria por violacion de indice.
                Agrupando, los renglones repetidos se suman, que es lo que
                corresponde para liquidar.                                    */
            INSERT INTO RO_T_FRANQ_GA_LOTE_DETALLE
                (ID_LOTE, NRO_SUCURS, DESC_SUCURSAL, FECHA_EMIS, T_COMP, N_COMP,
                 COD_ARTICU, CANTIDAD, PRECIO_UNITARIO, IMPORTE,
                 ES_REZAGADO, SIN_PRECIO_LISTA)
            SELECT
                @IdLote,
                A.NRO_SUCURS,
                MAX(C.DESC_SUCURSAL),
                MIN(A.FECHA_EMIS),
                A.T_COMP,
                A.N_COMP,
                ISNULL(B.COD_ARTICU, ''),
                SUM(ISNULL(B.CANTIDAD, 0)),
                MAX(ISNULL(D.PRECIO, 0)),
                /*  Signo por tipo de comprobante -- ver nota 3 de la cabecera. */
                CASE WHEN A.T_COMP IN ('NCR', 'NCP', 'NCI', 'NCD', 'N/C')
                     THEN -1 ELSE 1 END
                    * SUM(ISNULL(B.CANTIDAD, 0)) * MAX(ISNULL(D.PRECIO, 0)),
                CASE WHEN MIN(A.FECHA_EMIS) < CAST(@Desde AS DATETIME)
                     THEN 1 ELSE 0 END,
                CASE WHEN MAX(ISNULL(D.PRECIO, 0)) = 0 THEN 1 ELSE 0 END
            FROM        CTA02 A WITH (NOLOCK)
            INNER JOIN  CTA03 B WITH (NOLOCK)
                    ON  A.T_COMP     = B.T_COMP
                    AND A.N_COMP     = B.N_COMP
                    AND A.NRO_SUCURS = B.NRO_SUCURS
            INNER JOIN  [LOCALES_LAKERS].DBO.SUCURSALES_LAKERS C WITH (NOLOCK)
                    ON  A.NRO_SUCURS = C.NRO_SUCURSAL
            /*  INNER, no LEFT: sucursal sin fecha de alta no se liquida. */
            INNER JOIN  RO_T_FRANQ_GA_SUCURSAL_INICIO INI
                    ON  INI.NRO_SUCURS = A.NRO_SUCURS
            LEFT JOIN   GVA17 D WITH (NOLOCK)
                    ON  B.COD_ARTICU = D.COD_ARTICU
                    AND D.NRO_DE_LIS = @NroLista
            WHERE   C.CANAL COLLATE Modern_Spanish_CI_AI = 'FRANQUICIAS GA'
              AND   A.T_COMP LIKE '[FN]%'
              /*  Piso de la franquicia. Corta los rezagados: nada anterior a
                  su alta entra al lote, ni siquiera en la primera corrida.  */
              AND   A.FECHA_EMIS >= CAST(INI.FECHA_INICIO AS DATETIME)
              /*  Hasta el fin del periodo inclusive. Se compara con
                  @Hasta + 1 dia en vez de castear FECHA_EMIS a DATE para no
                  perder el indice por columna envuelta en funcion.           */
              AND   A.FECHA_EMIS <  DATEADD(DAY, 1, CAST(@Hasta AS DATETIME))
              AND   NOT EXISTS (
                        SELECT 1
                        FROM   RO_T_FRANQ_GA_LOTE_DETALLE X
                        WHERE  X.NRO_SUCURS = A.NRO_SUCURS
                          AND  X.T_COMP     = A.T_COMP
                          AND  X.N_COMP     = A.N_COMP
                          AND  X.COD_ARTICU = ISNULL(B.COD_ARTICU, '')
                    )
            GROUP BY A.NRO_SUCURS, A.T_COMP, A.N_COMP, ISNULL(B.COD_ARTICU, '');

            UPDATE  L
            SET     L.IMPORTE_TOTAL = ISNULL(T.TOTAL, 0)
            FROM    RO_T_FRANQ_GA_LOTE L
            OUTER APPLY (
                        SELECT SUM(D.IMPORTE) AS TOTAL
                        FROM   RO_T_FRANQ_GA_LOTE_DETALLE D
                        WHERE  D.ID_LOTE = L.ID_LOTE
                    ) T
            WHERE   L.ID_LOTE = @IdLote;

            COMMIT TRANSACTION;
        END TRY
        BEGIN CATCH
            IF @@TRANCOUNT > 0
                ROLLBACK TRANSACTION;
            THROW;
        END CATCH
    END

    /*  Resultado para el job y para el boton "Generar lote manual". */
    SELECT
        L.ID_LOTE,
        L.PERIODO_DESDE,
        L.PERIODO_HASTA,
        L.FECHA_EJECUCION,
        L.NRO_LISTA,
        L.ESTADO,
        L.IMPORTE_TOTAL,
        L.USUARIO_GENERA,
        @YaExistia                                          AS YA_EXISTIA,
        (SELECT COUNT(*)
           FROM RO_T_FRANQ_GA_LOTE_DETALLE D
          WHERE D.ID_LOTE = L.ID_LOTE)                      AS CANT_RENGLONES,
        (SELECT COUNT(*)
           FROM (SELECT DISTINCT D.NRO_SUCURS, D.T_COMP, D.N_COMP
                   FROM RO_T_FRANQ_GA_LOTE_DETALLE D
                  WHERE D.ID_LOTE = L.ID_LOTE) Z)           AS CANT_COMPROBANTES,
        (SELECT COUNT(DISTINCT D.NRO_SUCURS)
           FROM RO_T_FRANQ_GA_LOTE_DETALLE D
          WHERE D.ID_LOTE = L.ID_LOTE)                      AS CANT_SUCURSALES,
        (SELECT COUNT(*)
           FROM RO_T_FRANQ_GA_LOTE_DETALLE D
          WHERE D.ID_LOTE = L.ID_LOTE AND D.ES_REZAGADO = 1) AS CANT_REZAGADOS,
        (SELECT COUNT(*)
           FROM RO_T_FRANQ_GA_LOTE_DETALLE D
          WHERE D.ID_LOTE = L.ID_LOTE AND D.SIN_PRECIO_LISTA = 1) AS CANT_SIN_PRECIO,
        /*  Franquicias del canal que quedaron afuera por no tener fecha de
            alta en RO_T_FRANQ_GA_SUCURSAL_INICIO. Si esto es > 0, alguien
            sumo una sucursal al canal y no la dio de alta: no se liquido.   */
        (SELECT COUNT(*)
           FROM [LOCALES_LAKERS].DBO.SUCURSALES_LAKERS S WITH (NOLOCK)
          WHERE S.CANAL COLLATE Modern_Spanish_CI_AI = 'FRANQUICIAS GA'
            AND NOT EXISTS (SELECT 1
                              FROM RO_T_FRANQ_GA_SUCURSAL_INICIO I
                             WHERE I.NRO_SUCURS = S.NRO_SUCURSAL)) AS CANT_SUCURSALES_SIN_ALTA
    FROM  RO_T_FRANQ_GA_LOTE L
    WHERE L.ID_LOTE = @IdLote;
END
GO


/* ============================================================================
   2. RO_SP_FRANQ_GA_RESUMEN
   ----------------------------------------------------------------------------
   Una fila por lote y franquicia. Es la grilla principal de la pestaña.

   @Desde / @Hasta filtran por SOLAPAMIENTO con el periodo del lote, no por
   igualdad: un rango de dos semanas devuelve los dos lotes.

   @Estado no estaba en la firma pedida. Se agrego porque la pantalla tiene
   filtro por estado del lote y hacerlo en PHP obligaria a traer todo y
   descartar.

   ENVIO POR MAIL: las columnas ULTIMO_ENVIO_* y CANT_ENVIOS salen de
   RO_T_FRANQ_GA_LOTE_ENVIO (06_RO_T_FRANQ_GA_LOTE_ENVIO.sql). Se toma solo el
   ultimo envio con RESULTADO = 'OK': un intento fallido no cuenta como
   "enviado" para la grilla. CANT_ENVIOS si cuenta todos los intentos.
============================================================================ */
CREATE OR ALTER PROCEDURE RO_SP_FRANQ_GA_RESUMEN
    @Desde       DATE        = NULL,
    @Hasta       DATE        = NULL,
    @NroSucursal SMALLINT    = NULL,
    @Estado      VARCHAR(20) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    ;WITH DET AS (
        SELECT  D.ID_LOTE,
                D.NRO_SUCURS,
                MAX(D.DESC_SUCURSAL)                                    AS DESC_SUCURSAL,
                COUNT(*)                                                AS CANT_RENGLONES,
                SUM(CASE WHEN D.ES_REZAGADO      = 1 THEN 1 ELSE 0 END)  AS CANT_REZAGADOS,
                SUM(CASE WHEN D.SIN_PRECIO_LISTA = 1 THEN 1 ELSE 0 END)  AS CANT_SIN_PRECIO,
                SUM(D.IMPORTE)                                          AS IMPORTE_TOTAL
        FROM    RO_T_FRANQ_GA_LOTE_DETALLE D
        GROUP BY D.ID_LOTE, D.NRO_SUCURS
    ),
    COMP AS (
        SELECT  Z.ID_LOTE, Z.NRO_SUCURS, COUNT(*) AS CANT_COMPROBANTES
        FROM    (SELECT DISTINCT D.ID_LOTE, D.NRO_SUCURS, D.T_COMP, D.N_COMP
                 FROM   RO_T_FRANQ_GA_LOTE_DETALLE D) Z
        GROUP BY Z.ID_LOTE, Z.NRO_SUCURS
    ),
    REC AS (
        SELECT  R.ID_LOTE,
                R.NRO_SUCURS,
                COUNT(*)                AS CANT_RECIBOS,
                SUM(R.IMPORTE_RECIBO)   AS IMPORTE_COBRADO
        FROM    RO_T_FRANQ_GA_LOTE_RECIBO R
        GROUP BY R.ID_LOTE, R.NRO_SUCURS
    ),
    /*  Ultimo envio OK por lote y sucursal, mas la cantidad total de intentos. */
    ENV AS (
        SELECT  E.ID_LOTE,
                E.NRO_SUCURS,
                E.FECHA_ENVIO,
                E.DESTINATARIO,
                E.USUARIO_ENVIA,
                COUNT(*) OVER (PARTITION BY E.ID_LOTE, E.NRO_SUCURS) AS CANT_ENVIOS_TOTAL,
                ROW_NUMBER() OVER (PARTITION BY E.ID_LOTE, E.NRO_SUCURS
                                   ORDER BY CASE WHEN E.RESULTADO = 'OK' THEN 0 ELSE 1 END,
                                            E.FECHA_ENVIO DESC) AS RN,
                E.RESULTADO
        FROM    RO_T_FRANQ_GA_LOTE_ENVIO E
    )
    SELECT
        L.ID_LOTE,
        L.PERIODO_DESDE,
        L.PERIODO_HASTA,
        L.FECHA_EJECUCION,
        L.NRO_LISTA,
        L.ESTADO                                    AS ESTADO_LOTE,
        L.USUARIO_GENERA,
        DET.NRO_SUCURS,
        DET.DESC_SUCURSAL,
        DET.CANT_RENGLONES,
        ISNULL(COMP.CANT_COMPROBANTES, 0)           AS CANT_COMPROBANTES,
        DET.CANT_REZAGADOS,
        DET.CANT_SIN_PRECIO,
        DET.IMPORTE_TOTAL,
        ISNULL(REC.CANT_RECIBOS, 0)                 AS CANT_RECIBOS,
        ISNULL(REC.IMPORTE_COBRADO, 0)              AS IMPORTE_COBRADO,
        DET.IMPORTE_TOTAL - ISNULL(REC.IMPORTE_COBRADO, 0) AS SALDO,
        /*  Estado POR FRANQUICIA. El ESTADO_LOTE es del periodo completo
            (ver nota 5 de la cabecera); esto es lo que se muestra por fila. */
        CASE
            WHEN L.ESTADO = 'ANULADO'                                        THEN 'ANULADO'
            WHEN DET.IMPORTE_TOTAL - ISNULL(REC.IMPORTE_COBRADO, 0) <= 0     THEN 'COBRADO'
            WHEN ISNULL(REC.IMPORTE_COBRADO, 0) > 0                          THEN 'PARCIAL'
            ELSE 'PENDIENTE'
        END                                         AS ESTADO_SUCURSAL,
        /*  Envio por mail. NULL en las tres ULTIMO_ENVIO_* = nunca se envio OK.
            El RN=1 con RESULTADO='OK' garantiza que un fallo posterior a un
            envio exitoso no lo "borre" de la grilla.                         */
        CASE WHEN ENV.RESULTADO = 'OK' THEN ENV.FECHA_ENVIO   END AS ULTIMO_ENVIO_FECHA,
        CASE WHEN ENV.RESULTADO = 'OK' THEN ENV.DESTINATARIO  END AS ULTIMO_ENVIO_DEST,
        CASE WHEN ENV.RESULTADO = 'OK' THEN ENV.USUARIO_ENVIA END AS ULTIMO_ENVIO_USUARIO,
        ISNULL(ENV.CANT_ENVIOS_TOTAL, 0)            AS CANT_ENVIOS
    FROM        RO_T_FRANQ_GA_LOTE L
    INNER JOIN  DET  ON DET.ID_LOTE  = L.ID_LOTE
    LEFT JOIN   COMP ON COMP.ID_LOTE = DET.ID_LOTE AND COMP.NRO_SUCURS = DET.NRO_SUCURS
    LEFT JOIN   REC  ON REC.ID_LOTE  = DET.ID_LOTE AND REC.NRO_SUCURS  = DET.NRO_SUCURS
    LEFT JOIN   ENV  ON ENV.ID_LOTE  = DET.ID_LOTE AND ENV.NRO_SUCURS  = DET.NRO_SUCURS
                    AND ENV.RN = 1
    WHERE   (@Desde       IS NULL OR L.PERIODO_HASTA >= @Desde)
      AND   (@Hasta       IS NULL OR L.PERIODO_DESDE <= @Hasta)
      AND   (@NroSucursal IS NULL OR DET.NRO_SUCURS   = @NroSucursal)
      AND   (@Estado      IS NULL OR L.ESTADO         = @Estado)
    ORDER BY L.PERIODO_DESDE DESC, DET.NRO_SUCURS;
END
GO


/* ============================================================================
   3. RO_SP_FRANQ_GA_DETALLE
   ----------------------------------------------------------------------------
   Comprobantes de un lote, con el flag de rezagado. Los rezagados salen
   PRIMERO para que la pantalla los pueda destacar sin reordenar en el cliente.
============================================================================ */
CREATE OR ALTER PROCEDURE RO_SP_FRANQ_GA_DETALLE
    @IdLote      INT,
    @NroSucursal SMALLINT = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF @IdLote IS NULL
        THROW 50003, 'RO_SP_FRANQ_GA_DETALLE: @IdLote es obligatorio.', 1;

    SELECT
        D.ID_DETALLE,
        D.ID_LOTE,
        L.PERIODO_DESDE,
        L.PERIODO_HASTA,
        L.ESTADO            AS ESTADO_LOTE,
        D.NRO_SUCURS,
        D.DESC_SUCURSAL,
        D.FECHA_EMIS,
        D.T_COMP,
        D.N_COMP,
        D.COD_ARTICU,
        D.CANTIDAD,
        D.PRECIO_UNITARIO,
        D.IMPORTE,
        D.ES_REZAGADO,
        D.SIN_PRECIO_LISTA,
        /*  Se expone el signo aplicado para que la pantalla pueda explicar por
            que un renglon resta, sin tener que replicar la lista de NC en JS. */
        CASE WHEN D.IMPORTE < 0 THEN 'NOTA DE CREDITO' ELSE 'FACTURA / DEBITO' END AS CLASE_COMP
    FROM        RO_T_FRANQ_GA_LOTE_DETALLE D
    INNER JOIN  RO_T_FRANQ_GA_LOTE L ON L.ID_LOTE = D.ID_LOTE
    WHERE   D.ID_LOTE = @IdLote
      AND   (@NroSucursal IS NULL OR D.NRO_SUCURS = @NroSucursal)
    ORDER BY D.ES_REZAGADO DESC, D.NRO_SUCURS, D.FECHA_EMIS, D.T_COMP, D.N_COMP, D.COD_ARTICU;
END
GO


/* ============================================================================
   4. RO_SP_FRANQ_GA_RECIBOS_CANDIDATOS
   ----------------------------------------------------------------------------
   Recibos (GVA12, T_COMP = 'REC') del canal FRANQUICIAS GA que todavia no
   estan vinculados a ningun lote, ordenados por proximidad al saldo pendiente.
   MATCH_EXACTO = 1 cuando el importe coincide exactamente con el saldo.

   El saldo se calcula para el alcance pedido: si viene @NroSucursal, es el
   saldo de esa franquicia en el lote; si no, el del lote completo.
============================================================================ */
CREATE OR ALTER PROCEDURE RO_SP_FRANQ_GA_RECIBOS_CANDIDATOS
    @IdLote      INT,
    @NroSucursal SMALLINT = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF @IdLote IS NULL
        THROW 50004, 'RO_SP_FRANQ_GA_RECIBOS_CANDIDATOS: @IdLote es obligatorio.', 1;

    DECLARE @ImporteLote DECIMAL(22,7),
            @Cobrado     DECIMAL(22,7),
            @Saldo       DECIMAL(22,7);

    SELECT @ImporteLote = ISNULL(SUM(D.IMPORTE), 0)
    FROM   RO_T_FRANQ_GA_LOTE_DETALLE D
    WHERE  D.ID_LOTE = @IdLote
      AND  (@NroSucursal IS NULL OR D.NRO_SUCURS = @NroSucursal);

    SELECT @Cobrado = ISNULL(SUM(R.IMPORTE_RECIBO), 0)
    FROM   RO_T_FRANQ_GA_LOTE_RECIBO R
    WHERE  R.ID_LOTE = @IdLote
      AND  (@NroSucursal IS NULL OR R.NRO_SUCURS = @NroSucursal);

    SET @Saldo = @ImporteLote - @Cobrado;

    SELECT
        G.NRO_SUCURS,
        S.DESC_SUCURSAL,
        G.T_COMP,
        G.N_COMP                                        AS N_COMP_RECIBO,
        G.FECHA_EMIS                                    AS FECHA_EMIS_RECIBO,
        G.COD_CLIENT,
        CAST(ISNULL(G.IMPORTE, 0) AS DECIMAL(22,7))     AS IMPORTE_RECIBO,
        G.LEYENDA,
        @ImporteLote                                    AS IMPORTE_LOTE,
        @Cobrado                                        AS IMPORTE_COBRADO,
        @Saldo                                          AS SALDO_PENDIENTE,
        /*  Comparacion exacta sobre DECIMAL(22,7) en los dos lados: por eso el
            modelo no redondea a 2 decimales en ningun punto.                 */
        CASE WHEN CAST(ISNULL(G.IMPORTE, 0) AS DECIMAL(22,7)) = @Saldo
             THEN 1 ELSE 0 END                          AS MATCH_EXACTO,
        ABS(CAST(ISNULL(G.IMPORTE, 0) AS DECIMAL(22,7)) - @Saldo) AS DIFERENCIA
    FROM        GVA12 G WITH (NOLOCK)
    INNER JOIN  [LOCALES_LAKERS].DBO.SUCURSALES_LAKERS S WITH (NOLOCK)
            ON  G.NRO_SUCURS = S.NRO_SUCURSAL
    WHERE   S.CANAL COLLATE Modern_Spanish_CI_AI = 'FRANQUICIAS GA'
      AND   G.T_COMP = 'REC'
      /*  GVA12.N_COMP admite NULL; un recibo sin numero no se puede vincular
          porque el numero es parte de la clave unica del vinculo.            */
      AND   G.N_COMP IS NOT NULL
      AND   (@NroSucursal IS NULL OR G.NRO_SUCURS = @NroSucursal)
      AND   NOT EXISTS (
                SELECT 1
                FROM   RO_T_FRANQ_GA_LOTE_RECIBO R
                WHERE  R.NRO_SUCURS    = G.NRO_SUCURS
                  AND  R.N_COMP_RECIBO = G.N_COMP
            )
    ORDER BY ABS(CAST(ISNULL(G.IMPORTE, 0) AS DECIMAL(22,7)) - @Saldo) ASC,
             G.FECHA_EMIS DESC;
END
GO


/* ============================================================================
   5. RO_SP_FRANQ_GA_RECALCULAR_ESTADO_LOTE   (auxiliar interno)
   ----------------------------------------------------------------------------
   Pone el lote en 'COBRADO' cuando el saldo del LOTE COMPLETO llega a cero, y
   lo devuelve a 'GENERADO' si vuelve a quedar saldo (por una desvinculacion).
   Nunca toca un lote ANULADO.

   No se llama desde PHP. Existe para que vincular y desvincular usen la MISMA
   regla -- ver nota 4 de la cabecera.
============================================================================ */
CREATE OR ALTER PROCEDURE RO_SP_FRANQ_GA_RECALCULAR_ESTADO_LOTE
    @IdLote INT
AS
BEGIN
    SET NOCOUNT ON;

    UPDATE  L
    SET     L.ESTADO = CASE
                           WHEN ISNULL(TOT.IMPORTE, 0) - ISNULL(COB.COBRADO, 0) <= 0
                            AND ISNULL(COB.COBRADO, 0) > 0
                           THEN 'COBRADO'
                           ELSE 'GENERADO'
                       END
    FROM    RO_T_FRANQ_GA_LOTE L
    OUTER APPLY (SELECT SUM(D.IMPORTE) AS IMPORTE
                 FROM   RO_T_FRANQ_GA_LOTE_DETALLE D
                 WHERE  D.ID_LOTE = L.ID_LOTE) TOT
    OUTER APPLY (SELECT SUM(R.IMPORTE_RECIBO) AS COBRADO
                 FROM   RO_T_FRANQ_GA_LOTE_RECIBO R
                 WHERE  R.ID_LOTE = L.ID_LOTE) COB
    WHERE   L.ID_LOTE = @IdLote
      AND   L.ESTADO <> 'ANULADO';
END
GO


/* ============================================================================
   6. RO_SP_FRANQ_GA_VINCULAR_RECIBO
   ----------------------------------------------------------------------------
   Imputa un recibo de GVA12 a un lote.

   El importe y la fecha se LEEN DE GVA12, no se reciben por parametro: el
   front manda solo sucursal y numero de recibo. Si los aceptara del cliente,
   cualquiera podria cerrar un lote con un importe inventado.

   El recibo de Tango NO se modifica. La imputacion vive solo en
   RO_T_FRANQ_GA_LOTE_RECIBO, asi que es 100% reversible.
============================================================================ */
CREATE OR ALTER PROCEDURE RO_SP_FRANQ_GA_VINCULAR_RECIBO
    @IdLote       INT,
    @NroSucursal  SMALLINT,
    @NCompRecibo  VARCHAR(14),
    @TipoMatch    VARCHAR(10) = 'MANUAL',
    @Usuario      VARCHAR(50) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    IF @IdLote IS NULL OR @NroSucursal IS NULL OR @NCompRecibo IS NULL
        THROW 50005, 'RO_SP_FRANQ_GA_VINCULAR_RECIBO: @IdLote, @NroSucursal y @NCompRecibo son obligatorios.', 1;

    IF @TipoMatch NOT IN ('AUTO', 'MANUAL')
        THROW 50006, 'RO_SP_FRANQ_GA_VINCULAR_RECIBO: @TipoMatch debe ser AUTO o MANUAL.', 1;

    DECLARE @EstadoLote VARCHAR(20);

    SELECT @EstadoLote = L.ESTADO
    FROM   RO_T_FRANQ_GA_LOTE L
    WHERE  L.ID_LOTE = @IdLote;

    IF @EstadoLote IS NULL
        THROW 50007, 'RO_SP_FRANQ_GA_VINCULAR_RECIBO: el lote indicado no existe.', 1;

    IF @EstadoLote = 'ANULADO'
        THROW 50008, 'RO_SP_FRANQ_GA_VINCULAR_RECIBO: el lote esta ANULADO, no admite vinculos.', 1;

    /*  El lote tiene que tener detalle de esa sucursal: si no, el recibo se
        estaria imputando a una franquicia que no se liquido en ese periodo. */
    IF NOT EXISTS (SELECT 1
                   FROM   RO_T_FRANQ_GA_LOTE_DETALLE D
                   WHERE  D.ID_LOTE = @IdLote
                     AND  D.NRO_SUCURS = @NroSucursal)
        THROW 50009, 'RO_SP_FRANQ_GA_VINCULAR_RECIBO: el lote no tiene comprobantes de esa sucursal.', 1;

    IF EXISTS (SELECT 1
               FROM   RO_T_FRANQ_GA_LOTE_RECIBO R
               WHERE  R.NRO_SUCURS    = @NroSucursal
                 AND  R.N_COMP_RECIBO = @NCompRecibo)
        THROW 50010, 'RO_SP_FRANQ_GA_VINCULAR_RECIBO: ese recibo ya esta vinculado a un lote.', 1;

    DECLARE @FechaRec DATETIME,
            @ImpRec   DECIMAL(22,7);

    SELECT TOP 1
           @FechaRec = G.FECHA_EMIS,
           @ImpRec   = CAST(ISNULL(G.IMPORTE, 0) AS DECIMAL(22,7))
    FROM        GVA12 G WITH (NOLOCK)
    INNER JOIN  [LOCALES_LAKERS].DBO.SUCURSALES_LAKERS S WITH (NOLOCK)
            ON  G.NRO_SUCURS = S.NRO_SUCURSAL
    WHERE   S.CANAL COLLATE Modern_Spanish_CI_AI = 'FRANQUICIAS GA'
      AND   G.T_COMP     = 'REC'
      AND   G.NRO_SUCURS = @NroSucursal
      AND   G.N_COMP     = @NCompRecibo
    ORDER BY G.FECHA_EMIS DESC;

    IF @ImpRec IS NULL
        THROW 50011, 'RO_SP_FRANQ_GA_VINCULAR_RECIBO: no se encontro el recibo en GVA12 para esa sucursal del canal FRANQUICIAS GA.', 1;

    BEGIN TRY
        BEGIN TRANSACTION;

        INSERT INTO RO_T_FRANQ_GA_LOTE_RECIBO
            (ID_LOTE, NRO_SUCURS, N_COMP_RECIBO, FECHA_EMIS_RECIBO,
             IMPORTE_RECIBO, TIPO_MATCH, USUARIO_VINCULA)
        VALUES
            (@IdLote, @NroSucursal, @NCompRecibo, @FechaRec,
             @ImpRec, @TipoMatch, @Usuario);

        EXEC RO_SP_FRANQ_GA_RECALCULAR_ESTADO_LOTE @IdLote = @IdLote;

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
        THROW;
    END CATCH

    /*  Estado resultante, para que la pantalla refresque sin otra consulta. */
    SELECT
        L.ID_LOTE,
        L.ESTADO                                        AS ESTADO_LOTE,
        @NroSucursal                                    AS NRO_SUCURS,
        @NCompRecibo                                    AS N_COMP_RECIBO,
        @ImpRec                                         AS IMPORTE_RECIBO,
        ISNULL(TOT.IMPORTE, 0)                          AS IMPORTE_SUCURSAL,
        ISNULL(COB.COBRADO, 0)                          AS COBRADO_SUCURSAL,
        ISNULL(TOT.IMPORTE, 0) - ISNULL(COB.COBRADO, 0) AS SALDO_SUCURSAL
    FROM  RO_T_FRANQ_GA_LOTE L
    OUTER APPLY (SELECT SUM(D.IMPORTE) AS IMPORTE
                 FROM   RO_T_FRANQ_GA_LOTE_DETALLE D
                 WHERE  D.ID_LOTE = L.ID_LOTE AND D.NRO_SUCURS = @NroSucursal) TOT
    OUTER APPLY (SELECT SUM(R.IMPORTE_RECIBO) AS COBRADO
                 FROM   RO_T_FRANQ_GA_LOTE_RECIBO R
                 WHERE  R.ID_LOTE = L.ID_LOTE AND R.NRO_SUCURS = @NroSucursal) COB
    WHERE L.ID_LOTE = @IdLote;
END
GO


/* ============================================================================
   7. RO_SP_FRANQ_GA_DESVINCULAR_RECIBO
   ----------------------------------------------------------------------------
   Saca la imputacion de un recibo y recalcula el estado del lote.

   Sobre el DELETE: va en transaccion pero NO lleva tabla de backup, a
   diferencia del detalle del lote. El motivo es que esta fila no contiene
   ningun dato propio -- importe, fecha y numero se releen de GVA12 y se puede
   volver a vincular en un clic. Lo que no es reconstruible es el PRECIO
   CONGELADO del detalle, y para eso si existe RO_T_FRANQ_GA_LOTE_DETALLE_BAK.
============================================================================ */
CREATE OR ALTER PROCEDURE RO_SP_FRANQ_GA_DESVINCULAR_RECIBO
    @IdVinculo INT,
    @Usuario   VARCHAR(50) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    IF @IdVinculo IS NULL
        THROW 50012, 'RO_SP_FRANQ_GA_DESVINCULAR_RECIBO: @IdVinculo es obligatorio.', 1;

    DECLARE @IdLote      INT,
            @NroSucursal SMALLINT,
            @NCompRecibo VARCHAR(14),
            @EstadoLote  VARCHAR(20);

    SELECT  @IdLote      = R.ID_LOTE,
            @NroSucursal = R.NRO_SUCURS,
            @NCompRecibo = R.N_COMP_RECIBO
    FROM    RO_T_FRANQ_GA_LOTE_RECIBO R
    WHERE   R.ID_VINCULO = @IdVinculo;

    IF @IdLote IS NULL
        THROW 50013, 'RO_SP_FRANQ_GA_DESVINCULAR_RECIBO: el vinculo indicado no existe.', 1;

    SELECT @EstadoLote = L.ESTADO
    FROM   RO_T_FRANQ_GA_LOTE L
    WHERE  L.ID_LOTE = @IdLote;

    IF @EstadoLote = 'ANULADO'
        THROW 50014, 'RO_SP_FRANQ_GA_DESVINCULAR_RECIBO: el lote esta ANULADO.', 1;

    BEGIN TRY
        BEGIN TRANSACTION;

        DELETE FROM RO_T_FRANQ_GA_LOTE_RECIBO
        WHERE  ID_VINCULO = @IdVinculo;

        EXEC RO_SP_FRANQ_GA_RECALCULAR_ESTADO_LOTE @IdLote = @IdLote;

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
        THROW;
    END CATCH

    SELECT
        L.ID_LOTE,
        L.ESTADO                                        AS ESTADO_LOTE,
        @NroSucursal                                    AS NRO_SUCURS,
        @NCompRecibo                                    AS N_COMP_RECIBO,
        ISNULL(TOT.IMPORTE, 0)                          AS IMPORTE_SUCURSAL,
        ISNULL(COB.COBRADO, 0)                          AS COBRADO_SUCURSAL,
        ISNULL(TOT.IMPORTE, 0) - ISNULL(COB.COBRADO, 0) AS SALDO_SUCURSAL
    FROM  RO_T_FRANQ_GA_LOTE L
    OUTER APPLY (SELECT SUM(D.IMPORTE) AS IMPORTE
                 FROM   RO_T_FRANQ_GA_LOTE_DETALLE D
                 WHERE  D.ID_LOTE = L.ID_LOTE AND D.NRO_SUCURS = @NroSucursal) TOT
    OUTER APPLY (SELECT SUM(R.IMPORTE_RECIBO) AS COBRADO
                 FROM   RO_T_FRANQ_GA_LOTE_RECIBO R
                 WHERE  R.ID_LOTE = L.ID_LOTE AND R.NRO_SUCURS = @NroSucursal) COB
    WHERE L.ID_LOTE = @IdLote;
END
GO


/* ---------------------------------------------------------------------------
   VERIFICACION -- deberia listar los 7 procedimientos.
--------------------------------------------------------------------------- */
SELECT  name AS PROCEDIMIENTO, create_date, modify_date
FROM    sys.procedures
WHERE   name LIKE 'RO_SP_FRANQ_GA[_]%'
ORDER BY name;
GO
