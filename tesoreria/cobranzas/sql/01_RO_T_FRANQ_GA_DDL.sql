/*
================================================================================
    LIQUIDACION SEMANAL FRANQUICIAS GA  --  PASO 1: MODELO DE DATOS
================================================================================

    BASE DESTINO:  [XL-LAKERBIS].[FRANQUICIAS_LAKERS]
    Correr este script CONECTADO a FRANQUICIAS_LAKERS (no a LAKER_SA).

    POR QUE FRANQUICIAS_LAKERS Y NO LAKER_SA
    ----------------------------------------
    Los comprobantes del canal FRANQUICIAS GA viven en
    FRANQUICIAS_LAKERS.DBO.CTA02 / CTA03, al dia (verificado 2026-09-21).
    LAKER_SA.DBO.CTA02 es la tabla historica de PROPIOS/CENTRAL: no tiene una
    sola fila de la sucursal 501 y su ultimo movimiento real es 2025-08-26.
    Ademas FRANQUICIAS_LAKERS ya es el hogar del resto de los objetos RO_* de
    franquicias (RO_T_COMPARA_VENTAS_FRANQ, RO_SP_COMPARAR_VENTAS_FRANQUICIA,
    RO_SP_BI_SALES_FRANQUICIAS_SIN_TANGO, etc.), asi que las tablas nuevas
    quedan donde corresponde.

    Consecuencia practica: los SP del paso 2 corren ACA y leen CTA02 / CTA03 /
    GVA17 / GVA12 como tablas LOCALES. El unico cruce entre bases es
    SUCURSALES_LAKERS, que esta en LOCALES_LAKERS -- misma instancia
    (XL-LAKERBIS), o sea acceso cross-database, NO linked server:

        [LOCALES_LAKERS].DBO.SUCURSALES_LAKERS

    COLLATIONS (verificado contra INFORMATION_SCHEMA, no supuesto)
    -------------------------------------------------------------
    FRANQUICIAS_LAKERS y LOCALES_LAKERS son las dos Modern_Spanish_CI_AI.
    Las columnas de Tango (T_COMP, N_COMP, COD_ARTICU, COD_CLIENT) son
    Latin1_General_BIN. Por eso cada columna de abajo declara su COLLATE
    EXPLICITO: replica la collation de su origen y evita que un restore o un
    cambio de default de la base las haga mutar y rompa los joins.

    OJO -- esto corrige el supuesto del pedido original: NRO_SUCURS es SMALLINT
    en CTA02 / CTA03 / GVA12, y NRO_SUCURSAL es INT en SUCURSALES_LAKERS. El
    join por sucursal es NUMERICO: no lleva COLLATE (seria error de sintaxis) y
    no puede dar conflicto de collation. Lo mismo COD_ARTICU entre CTA03 y
    GVA17: las dos son Latin1_General_BIN dentro de la MISMA base, asi que
    tampoco hay conflicto. El COLLATE explicito sigue estando en las columnas
    por prolijidad y para blindar el futuro, no porque hoy haya un conflicto.

    DECIMALES: se respeta DECIMAL(22,7) de Tango en cantidades e importes. No
    se redondea a 2 decimales en ningun lado del modelo -- el match exacto por
    importe contra GVA12.IMPORTE (tambien DECIMAL(22,7)) depende de eso.

    IDEMPOTENTE: se puede correr varias veces sin efecto. No borra nada.

    Requiere despues: 02_RO_SP_FRANQ_GA_*.sql
================================================================================
*/

SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

/* ---------------------------------------------------------------------------
   1. ENCABEZADO DEL LOTE
   Un lote = un periodo (lunes a domingo) para TODO el canal FRANQUICIAS GA.
   El detalle discrimina por sucursal; el encabezado no.
--------------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'RO_T_FRANQ_GA_LOTE')
BEGIN
    CREATE TABLE RO_T_FRANQ_GA_LOTE (
        ID_LOTE         INT             IDENTITY(1,1) NOT NULL,
        PERIODO_DESDE   DATE            NOT NULL,
        PERIODO_HASTA   DATE            NOT NULL,
        FECHA_EJECUCION DATETIME        NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_LOTE_FEJEC DEFAULT (GETDATE()),
        /* Lista de precios usada para valuar. Smallint igual que GVA17.NRO_DE_LIS. */
        NRO_LISTA       SMALLINT        NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_LOTE_LISTA DEFAULT (30),
        ESTADO          VARCHAR(20)     COLLATE Modern_Spanish_CI_AI NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_LOTE_ESTADO DEFAULT ('GENERADO'),
        IMPORTE_TOTAL   DECIMAL(22,7)   NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_LOTE_IMPORTE DEFAULT (0),
        USUARIO_GENERA  VARCHAR(50)     COLLATE Modern_Spanish_CI_AI NULL,
        OBSERVACIONES   VARCHAR(500)    COLLATE Modern_Spanish_CI_AI NULL,
        CONSTRAINT PK_RO_T_FRANQ_GA_LOTE PRIMARY KEY CLUSTERED (ID_LOTE),
        CONSTRAINT CK_RO_T_FRANQ_GA_LOTE_ESTADO
            CHECK (ESTADO IN ('GENERADO', 'COBRADO', 'ANULADO')),
        CONSTRAINT CK_RO_T_FRANQ_GA_LOTE_PERIODO
            CHECK (PERIODO_HASTA >= PERIODO_DESDE)
    );
END
GO

/*  Idempotencia del job: un solo lote VIVO por periodo.
    El indice es FILTRADO por ESTADO <> 'ANULADO' a proposito -- si un lote se
    anula, el periodo queda libre para regenerarse. Sin el filtro, un lote mal
    generado y despues anulado bloquearia para siempre su propia semana.     */
IF NOT EXISTS (SELECT 1 FROM sys.indexes
               WHERE name = 'UX_RO_T_FRANQ_GA_LOTE_PERIODO'
                 AND object_id = OBJECT_ID('RO_T_FRANQ_GA_LOTE'))
BEGIN
    CREATE UNIQUE NONCLUSTERED INDEX UX_RO_T_FRANQ_GA_LOTE_PERIODO
        ON RO_T_FRANQ_GA_LOTE (PERIODO_DESDE, PERIODO_HASTA)
        WHERE ESTADO <> 'ANULADO';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes
               WHERE name = 'IX_RO_T_FRANQ_GA_LOTE_ESTADO'
                 AND object_id = OBJECT_ID('RO_T_FRANQ_GA_LOTE'))
BEGIN
    CREATE NONCLUSTERED INDEX IX_RO_T_FRANQ_GA_LOTE_ESTADO
        ON RO_T_FRANQ_GA_LOTE (ESTADO, PERIODO_DESDE);
END
GO


/* ---------------------------------------------------------------------------
   2. DETALLE DEL LOTE
   Precio CONGELADO: PRECIO_UNITARIO e IMPORTE se graban al generar el lote y
   no se recalculan nunca. Un cambio posterior en GVA17 no toca lo ya liquidado.
--------------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'RO_T_FRANQ_GA_LOTE_DETALLE')
BEGIN
    CREATE TABLE RO_T_FRANQ_GA_LOTE_DETALLE (
        ID_DETALLE      INT             IDENTITY(1,1) NOT NULL,
        ID_LOTE         INT             NOT NULL,
        NRO_SUCURS      SMALLINT        NOT NULL,
        DESC_SUCURSAL   VARCHAR(50)     COLLATE Modern_Spanish_CI_AI NULL,
        FECHA_EMIS      DATETIME        NOT NULL,
        T_COMP          VARCHAR(3)      COLLATE Latin1_General_BIN NOT NULL,
        N_COMP          VARCHAR(14)     COLLATE Latin1_General_BIN NOT NULL,
        /*  CTA03.COD_ARTICU admite NULL. Aca se guarda cadena vacia en vez de
            NULL: en SQL Server un indice UNIQUE trata los NULL como iguales
            entre si, asi que dos renglones sin articulo del mismo comprobante
            chocarian contra UX_RO_T_FRANQ_GA_DET_COMPROBANTE. Con cadena
            vacia el problema no existe y la clave sigue siendo comparable.  */
        COD_ARTICU      VARCHAR(15)     COLLATE Latin1_General_BIN NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_DET_ARTICU DEFAULT (''),
        CANTIDAD        DECIMAL(22,7)   NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_DET_CANT DEFAULT (0),
        PRECIO_UNITARIO DECIMAL(22,7)   NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_DET_PRECIO DEFAULT (0),
        /*  Ya viene con signo: negativo para NC. No re-aplicar el signo al sumar. */
        IMPORTE         DECIMAL(22,7)   NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_DET_IMPORTE DEFAULT (0),
        ES_REZAGADO     BIT             NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_DET_REZAG DEFAULT (0),
        /*  1 cuando el articulo no tenia precio en la Lista 30 al generar el
            lote (hoy son 501 de 93.736 articulos). Se liquida en 0 y queda
            marcado para que la pantalla lo muestre y alguien lo revise.     */
        SIN_PRECIO_LISTA BIT            NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_DET_SINPREC DEFAULT (0),
        CONSTRAINT PK_RO_T_FRANQ_GA_LOTE_DETALLE PRIMARY KEY CLUSTERED (ID_DETALLE),
        CONSTRAINT FK_RO_T_FRANQ_GA_DET_LOTE FOREIGN KEY (ID_LOTE)
            REFERENCES RO_T_FRANQ_GA_LOTE (ID_LOTE)
    );
END
GO

/*  LA regla del circuito: un comprobante no entra en dos lotes.
    El indice es GLOBAL (no incluye ID_LOTE) y por eso es el que garantiza que
    un rezagado no se liquide dos veces. Para reprocesar un lote hay que sacar
    sus filas de aca primero (ver RO_T_FRANQ_GA_LOTE_DETALLE_BAK).           */
IF NOT EXISTS (SELECT 1 FROM sys.indexes
               WHERE name = 'UX_RO_T_FRANQ_GA_DET_COMPROBANTE'
                 AND object_id = OBJECT_ID('RO_T_FRANQ_GA_LOTE_DETALLE'))
BEGIN
    CREATE UNIQUE NONCLUSTERED INDEX UX_RO_T_FRANQ_GA_DET_COMPROBANTE
        ON RO_T_FRANQ_GA_LOTE_DETALLE (NRO_SUCURS, T_COMP, N_COMP, COD_ARTICU);
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes
               WHERE name = 'IX_RO_T_FRANQ_GA_DET_LOTE_SUC'
                 AND object_id = OBJECT_ID('RO_T_FRANQ_GA_LOTE_DETALLE'))
BEGIN
    CREATE NONCLUSTERED INDEX IX_RO_T_FRANQ_GA_DET_LOTE_SUC
        ON RO_T_FRANQ_GA_LOTE_DETALLE (ID_LOTE, NRO_SUCURS)
        INCLUDE (IMPORTE, ES_REZAGADO);
END
GO


/* ---------------------------------------------------------------------------
   3. BACKUP DEL DETALLE
   Destino obligatorio de cualquier borrado de detalle (anulacion o reproceso
   manual de un lote). Nunca se hace DELETE del detalle sin copiar aca primero,
   y siempre dentro de la misma transaccion.
--------------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'RO_T_FRANQ_GA_LOTE_DETALLE_BAK')
BEGIN
    CREATE TABLE RO_T_FRANQ_GA_LOTE_DETALLE_BAK (
        ID_BAK          INT             IDENTITY(1,1) NOT NULL,
        FECHA_BAK       DATETIME        NOT NULL
                        CONSTRAINT DF_RO_T_FRANQ_GA_BAK_FECHA DEFAULT (GETDATE()),
        USUARIO_BAK     VARCHAR(50)     COLLATE Modern_Spanish_CI_AI NULL,
        MOTIVO_BAK      VARCHAR(200)    COLLATE Modern_Spanish_CI_AI NULL,
        ID_DETALLE      INT             NOT NULL,
        ID_LOTE         INT             NOT NULL,
        NRO_SUCURS      SMALLINT        NOT NULL,
        DESC_SUCURSAL   VARCHAR(50)     COLLATE Modern_Spanish_CI_AI NULL,
        FECHA_EMIS      DATETIME        NOT NULL,
        T_COMP          VARCHAR(3)      COLLATE Latin1_General_BIN NOT NULL,
        N_COMP          VARCHAR(14)     COLLATE Latin1_General_BIN NOT NULL,
        COD_ARTICU      VARCHAR(15)     COLLATE Latin1_General_BIN NOT NULL,
        CANTIDAD        DECIMAL(22,7)   NOT NULL,
        PRECIO_UNITARIO DECIMAL(22,7)   NOT NULL,
        IMPORTE         DECIMAL(22,7)   NOT NULL,
        ES_REZAGADO     BIT             NOT NULL,
        SIN_PRECIO_LISTA BIT            NOT NULL,
        CONSTRAINT PK_RO_T_FRANQ_GA_LOTE_DETALLE_BAK PRIMARY KEY CLUSTERED (ID_BAK)
    );
END
GO


/* ---------------------------------------------------------------------------
   4. VINCULO LOTE <-> RECIBO
   Un recibo de GVA12 se imputa a UN solo lote. El recibo no se modifica nunca:
   esta tabla es la unica que registra la imputacion.
--------------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'RO_T_FRANQ_GA_LOTE_RECIBO')
BEGIN
    CREATE TABLE RO_T_FRANQ_GA_LOTE_RECIBO (
        ID_VINCULO         INT           IDENTITY(1,1) NOT NULL,
        ID_LOTE            INT           NOT NULL,
        NRO_SUCURS         SMALLINT      NOT NULL,
        N_COMP_RECIBO      VARCHAR(14)   COLLATE Latin1_General_BIN NOT NULL,
        FECHA_EMIS_RECIBO  DATETIME      NULL,
        IMPORTE_RECIBO     DECIMAL(22,7) NOT NULL
                           CONSTRAINT DF_RO_T_FRANQ_GA_REC_IMPORTE DEFAULT (0),
        TIPO_MATCH         VARCHAR(10)   COLLATE Modern_Spanish_CI_AI NOT NULL
                           CONSTRAINT DF_RO_T_FRANQ_GA_REC_MATCH DEFAULT ('MANUAL'),
        USUARIO_VINCULA    VARCHAR(50)   COLLATE Modern_Spanish_CI_AI NULL,
        FECHA_VINCULO      DATETIME      NOT NULL
                           CONSTRAINT DF_RO_T_FRANQ_GA_REC_FECHA DEFAULT (GETDATE()),
        CONSTRAINT PK_RO_T_FRANQ_GA_LOTE_RECIBO PRIMARY KEY CLUSTERED (ID_VINCULO),
        CONSTRAINT FK_RO_T_FRANQ_GA_REC_LOTE FOREIGN KEY (ID_LOTE)
            REFERENCES RO_T_FRANQ_GA_LOTE (ID_LOTE),
        CONSTRAINT CK_RO_T_FRANQ_GA_REC_MATCH
            CHECK (TIPO_MATCH IN ('AUTO', 'MANUAL'))
    );
END
GO

/*  Un recibo no se puede imputar dos veces (ni al mismo lote ni a otro).
    Desvincular = DELETE de esta fila, con lo cual el recibo vuelve a estar
    disponible para RO_SP_FRANQ_GA_RECIBOS_CANDIDATOS.                      */
IF NOT EXISTS (SELECT 1 FROM sys.indexes
               WHERE name = 'UX_RO_T_FRANQ_GA_REC_RECIBO'
                 AND object_id = OBJECT_ID('RO_T_FRANQ_GA_LOTE_RECIBO'))
BEGIN
    CREATE UNIQUE NONCLUSTERED INDEX UX_RO_T_FRANQ_GA_REC_RECIBO
        ON RO_T_FRANQ_GA_LOTE_RECIBO (NRO_SUCURS, N_COMP_RECIBO);
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes
               WHERE name = 'IX_RO_T_FRANQ_GA_REC_LOTE_SUC'
                 AND object_id = OBJECT_ID('RO_T_FRANQ_GA_LOTE_RECIBO'))
BEGIN
    CREATE NONCLUSTERED INDEX IX_RO_T_FRANQ_GA_REC_LOTE_SUC
        ON RO_T_FRANQ_GA_LOTE_RECIBO (ID_LOTE, NRO_SUCURS)
        INCLUDE (IMPORTE_RECIBO);
END
GO


/* ---------------------------------------------------------------------------
   VERIFICACION -- correr despues del script y revisar la salida.
   Esperado:
     RO_T_FRANQ_GA_LOTE                 ->  9 columnas, 3 indices, 0 FK
     RO_T_FRANQ_GA_LOTE_DETALLE         -> 13 columnas, 3 indices, 1 FK
     RO_T_FRANQ_GA_LOTE_DETALLE_BAK     -> 17 columnas, 1 indice,  0 FK
     RO_T_FRANQ_GA_LOTE_RECIBO          ->  9 columnas, 3 indices, 1 FK
--------------------------------------------------------------------------- */
SELECT  t.name                                    AS TABLA,
        (SELECT COUNT(*) FROM sys.columns c
          WHERE c.object_id = t.object_id)        AS COLUMNAS,
        (SELECT COUNT(*) FROM sys.indexes i
          WHERE i.object_id = t.object_id
            AND i.type_desc <> 'HEAP')            AS INDICES,
        (SELECT COUNT(*) FROM sys.foreign_keys f
          WHERE f.parent_object_id = t.object_id) AS FKS
FROM    sys.tables t
WHERE   t.name LIKE 'RO_T_FRANQ_GA[_]%'
ORDER BY t.name;
GO
