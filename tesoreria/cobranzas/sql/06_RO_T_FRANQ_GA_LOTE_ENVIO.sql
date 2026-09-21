/*
================================================================================
    LIQUIDACION SEMANAL FRANQUICIAS GA  --  PASO 6: REGISTRO DE ENVIOS POR MAIL
================================================================================

    BASE DESTINO:  [XL-LAKERBIS].[FRANQUICIAS_LAKERS]
    Correr CONECTADO a FRANQUICIAS_LAKERS. Requiere 01_RO_T_FRANQ_GA_DDL.sql.
    DESPUES de este script hay que re-aplicar 02_RO_SP_FRANQ_GA.sql, que trae
    RO_SP_FRANQ_GA_RESUMEN con las columnas del ultimo envio.

    QUE ES
    ------
    Cada vez que desde la pantalla se manda la liquidacion de una franquicia
    por mail, queda una fila aca: a quien, cuando, quien la mando y si salio
    bien. Sin esto no hay forma de saber si una liquidacion ya se envio, y
    termina mandandose dos veces o ninguna.

    SE REGISTRAN TAMBIEN LOS FALLIDOS (RESULTADO = 'ERROR'), con el detalle en
    ERROR_DETALLE. Es justamente cuando mas sirve el rastro.

    SIN INDICE UNICO A PROPOSITO: reenviar es legitimo (se corrigio algo, el
    destinatario no lo recibio). La grilla muestra el ultimo envio OK y el modal
    avisa si ya se mando antes; la decision de reenviar es del operador.

    El registro lo escribe api/franquicias_ga_controller.php (action=enviar_mail).
    El mail fisico lo manda enviarNotificacion() de notificaciones_controller.php,
    que ademas deja su propia linea en api/notificaciones.log.

    IDEMPOTENTE: se puede correr varias veces sin efecto.
================================================================================
*/

SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'RO_T_FRANQ_GA_LOTE_ENVIO')
BEGIN
    CREATE TABLE RO_T_FRANQ_GA_LOTE_ENVIO (
        ID_ENVIO        INT             IDENTITY(1,1) NOT NULL,
        ID_LOTE         INT             NOT NULL,
        NRO_SUCURS      SMALLINT        NOT NULL,
        /*  Tal cual se mando. Puede diferir de SUCURSALES_LAKERS.MAIL porque el
            operador puede editarlo en el modal antes de enviar.              */
        DESTINATARIO    VARCHAR(200)    COLLATE Modern_Spanish_CI_AI NOT NULL,
        ASUNTO          VARCHAR(300)    COLLATE Modern_Spanish_CI_AI NULL,
        CON_ADJUNTO     BIT             NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_ENV_ADJ DEFAULT (0),
        RESULTADO       VARCHAR(20)     COLLATE Modern_Spanish_CI_AI NOT NULL,
        ERROR_DETALLE   VARCHAR(500)    COLLATE Modern_Spanish_CI_AI NULL,
        USUARIO_ENVIA   VARCHAR(50)     COLLATE Modern_Spanish_CI_AI NULL,
        FECHA_ENVIO     DATETIME        NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_ENV_FECHA DEFAULT (GETDATE()),
        CONSTRAINT PK_RO_T_FRANQ_GA_LOTE_ENVIO PRIMARY KEY CLUSTERED (ID_ENVIO),
        CONSTRAINT FK_RO_T_FRANQ_GA_ENV_LOTE FOREIGN KEY (ID_LOTE)
            REFERENCES RO_T_FRANQ_GA_LOTE (ID_LOTE),
        CONSTRAINT CK_RO_T_FRANQ_GA_ENV_RESULTADO
            CHECK (RESULTADO IN ('OK', 'ERROR'))
    );
END
GO

/*  Para "ultimo envio de este lote y sucursal": el ORDER BY FECHA_ENVIO DESC
    del indice hace que el TOP 1 sea una lectura directa.                     */
IF NOT EXISTS (SELECT 1 FROM sys.indexes
               WHERE name = 'IX_RO_T_FRANQ_GA_ENV_LOTE_SUC'
                 AND object_id = OBJECT_ID('RO_T_FRANQ_GA_LOTE_ENVIO'))
BEGIN
    CREATE NONCLUSTERED INDEX IX_RO_T_FRANQ_GA_ENV_LOTE_SUC
        ON RO_T_FRANQ_GA_LOTE_ENVIO (ID_LOTE, NRO_SUCURS, FECHA_ENVIO DESC)
        INCLUDE (RESULTADO, DESTINATARIO, USUARIO_ENVIA);
END
GO


/* ---------------------------------------------------------------------------
   VERIFICACION -- esperado: 10 columnas, 2 indices, 1 FK, 1 CHECK, 0 filas.
--------------------------------------------------------------------------- */
SELECT  t.name                                    AS TABLA,
        (SELECT COUNT(*) FROM sys.columns c
          WHERE c.object_id = t.object_id)        AS COLUMNAS,
        (SELECT COUNT(*) FROM sys.indexes i
          WHERE i.object_id = t.object_id
            AND i.type_desc <> 'HEAP')            AS INDICES,
        (SELECT COUNT(*) FROM sys.foreign_keys f
          WHERE f.parent_object_id = t.object_id) AS FKS,
        (SELECT COUNT(*) FROM sys.check_constraints k
          WHERE k.parent_object_id = t.object_id) AS CHECKS,
        (SELECT COUNT(*) FROM RO_T_FRANQ_GA_LOTE_ENVIO) AS FILAS
FROM    sys.tables t
WHERE   t.name = 'RO_T_FRANQ_GA_LOTE_ENVIO';
GO
