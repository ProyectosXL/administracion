/*
================================================================================
    LIQUIDACION SEMANAL FRANQUICIAS GA  --  PASO 5: PISO POR SUCURSAL
================================================================================

    BASE DESTINO:  [XL-LAKERBIS].[FRANQUICIAS_LAKERS]
    Correr CONECTADO a FRANQUICIAS_LAKERS. Requiere 01 y 02 ya aplicados.
    DESPUES de este script hay que re-aplicar 02_RO_SP_FRANQ_GA.sql, que trae
    RO_SP_FRANQ_GA_GENERAR_LOTE ya adaptado a esta tabla.

    QUE PROBLEMA RESUELVE
    ---------------------
    La regla de rezagados dice que el lote incluye "todos los comprobantes
    anteriores del canal que no esten en ningun lote previo". Eso funciona bien
    a partir del segundo lote, pero en el PRIMERO no tiene piso: se lleva toda
    la historia de la sucursal.

    Paso exactamente eso con la 501. Sus tres primeros comprobantes eran
    pruebas de puesta en marcha:

        09/09  FAC B0000600000001   $100,00   *****SEÑAS       cliente 000000
        10/09  FAC B0000600000002   $  1,00   *****DIF PRECIO  cliente 000000
        10/09  FAC B0000600000003   $  1,00   *****DIF PRECIO  cliente 000000

    Despues no hubo movimiento hasta el 14/09, que es cuando la franquicia
    empezo a facturar de verdad ($168.100 el primer dia, contra $1 y $100 de
    las pruebas). Esos tres entraron al lote #1 marcados como rezagados.

    Esta tabla fija, POR SUCURSAL, la fecha a partir de la cual se liquida.
    RO_SP_FRANQ_GA_GENERAR_LOTE no toma nada anterior, ni siquiera como
    rezagado.

    POR QUE POR SUCURSAL Y NO UN PISO GLOBAL
    ----------------------------------------
    Hoy el canal tiene una sola franquicia, pero el modelo es multi-franquicia.
    Con un piso global, la franquicia que se sume el año que viene arrastraria
    toda su historia previa a su primer lote -- el mismo bug, corrido de fecha.
    Con la fecha de alta por sucursal, cada una arranca donde corresponde.

    OJO -- UNA SUCURSAL SIN FILA ACA NO SE LIQUIDA
    ----------------------------------------------
    Es a proposito: es preferible que una franquicia nueva no se liquide y
    alguien lo note, a que se liquide de mas barriendo su historial completo.
    Para que no pase en silencio, RO_SP_FRANQ_GA_GENERAR_LOTE devuelve
    CANT_SUCURSALES_SIN_ALTA y la pantalla lo muestra como advertencia.

    DAR DE ALTA UNA FRANQUICIA NUEVA:

        INSERT INTO RO_T_FRANQ_GA_SUCURSAL_INICIO
            (NRO_SUCURS, FECHA_INICIO, OBSERVACION, USUARIO)
        VALUES (NNN, '2027-01-05', 'Alta del local en el canal GA', 'usuario');

    IDEMPOTENTE: se puede correr varias veces. No pisa filas existentes.
================================================================================
*/

SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'RO_T_FRANQ_GA_SUCURSAL_INICIO')
BEGIN
    CREATE TABLE RO_T_FRANQ_GA_SUCURSAL_INICIO (
        NRO_SUCURS   SMALLINT     NOT NULL,
        /*  Primer dia liquidable, INCLUSIVE. Nada anterior entra al lote. */
        FECHA_INICIO DATE         NOT NULL,
        OBSERVACION  VARCHAR(200) COLLATE Modern_Spanish_CI_AI NULL,
        USUARIO      VARCHAR(50)  COLLATE Modern_Spanish_CI_AI NULL,
        FECHA_ALTA   DATETIME     NOT NULL
                     CONSTRAINT DF_RO_T_FRANQ_GA_SUC_INI_FECHA DEFAULT (GETDATE()),
        CONSTRAINT PK_RO_T_FRANQ_GA_SUCURSAL_INICIO PRIMARY KEY CLUSTERED (NRO_SUCURS)
    );
END
GO

/* ---------------------------------------------------------------------------
   Alta de la sucursal 501 (LOMAS DE ZAMORA).
   14/09/2026: primer dia de facturacion real, posterior a las pruebas de
   puesta en marcha del 09 y 10/09.
--------------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM RO_T_FRANQ_GA_SUCURSAL_INICIO WHERE NRO_SUCURS = 501)
BEGIN
    INSERT INTO RO_T_FRANQ_GA_SUCURSAL_INICIO
        (NRO_SUCURS, FECHA_INICIO, OBSERVACION, USUARIO)
    VALUES
        (501, '2026-09-14',
         'Primer dia de facturacion real. Los comprobantes 1 a 3 (09 y 10/09) son pruebas de puesta en marcha.',
         'sistemas');
END
GO


/* ---------------------------------------------------------------------------
   VERIFICACION
--------------------------------------------------------------------------- */

/* 1. Altas cargadas, y que franquicias del canal quedarian SIN liquidar. */
SELECT  S.NRO_SUCURSAL                          AS NRO_SUCURS,
        S.DESC_SUCURSAL,
        I.FECHA_INICIO,
        I.OBSERVACION,
        CASE WHEN I.NRO_SUCURS IS NULL
             THEN 'SIN ALTA -- NO SE VA A LIQUIDAR'
             ELSE 'OK' END                      AS SITUACION
FROM        [LOCALES_LAKERS].DBO.SUCURSALES_LAKERS S WITH (NOLOCK)
LEFT JOIN   RO_T_FRANQ_GA_SUCURSAL_INICIO I
        ON  I.NRO_SUCURS = S.NRO_SUCURSAL
WHERE       S.CANAL COLLATE Modern_Spanish_CI_AI = 'FRANQUICIAS GA'
ORDER BY    S.NRO_SUCURSAL;

/* 2. Que comprobantes quedan excluidos por el piso (deberian ser los 3 de prueba). */
SELECT  A.NRO_SUCURS, A.FECHA_EMIS, A.T_COMP, A.N_COMP,
        A.IMPORTE       AS IMPORTE_CTA02,
        I.FECHA_INICIO  AS PISO_DE_LA_SUCURSAL
FROM        CTA02 A WITH (NOLOCK)
INNER JOIN  RO_T_FRANQ_GA_SUCURSAL_INICIO I
        ON  I.NRO_SUCURS = A.NRO_SUCURS
WHERE       A.T_COMP LIKE '[FN]%'
  AND       A.FECHA_EMIS < CAST(I.FECHA_INICIO AS DATETIME)
ORDER BY    A.NRO_SUCURS, A.FECHA_EMIS, A.N_COMP;
GO
