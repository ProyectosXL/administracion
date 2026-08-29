-- =====================================================================
-- Cronograma de contenedores - parametros de dias de offset
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente.
--
-- Por que tabla propia y no la de parametros existente:
-- RO_T_CONCEPTOS_ESTIMACION_COMEX es un catalogo de conceptos de costo
-- (ID_CE identity, CONCEPTO, TIPO_VALOR char(1), VALOR_DEFAULT_1/2 decimal)
-- y en uy suma cinco columnas mas de moneda y tipo de cambio. Es un
-- catalogo de costos, no un almacen clave/valor. Meter dias de offset ahi
-- obligaria a reinterpretar TIPO_VALOR y a arrastrar columnas de moneda que
-- no aplican.
--
-- Estos cuatro valores reemplazan los offsets que hoy estan hardcodeados en
-- js/cronograma.js: 45 en calcularFechaArriboEstimada() y 45/7/3 dentro de
-- crearTimeline().
-- =====================================================================

IF OBJECT_ID('RO_T_IMPORTACIONES_PARAM_CRONOGRAMA', 'U') IS NULL
BEGIN
    CREATE TABLE RO_T_IMPORTACIONES_PARAM_CRONOGRAMA (
        CLAVE       VARCHAR(40)  NOT NULL,
        VALOR       INT          NOT NULL,
        DESCRIPCION VARCHAR(150) NULL,
        USUARIO     VARCHAR(50)  NULL,
        FECHA_MOD   DATETIME     NULL,
        CONSTRAINT PK_RO_T_IMP_PARAM_CRONO PRIMARY KEY (CLAVE)
    );
END
GO

-- Seed. Solo inserta lo que falta: si alguien ya ajusto un valor desde el
-- ABM de Parametros, este script no lo pisa.
MERGE RO_T_IMPORTACIONES_PARAM_CRONOGRAMA AS destino
USING (VALUES
     ('DIAS_EMB_ARR',  45, 'Dias corridos entre embarque y arribo estimado')
    ,('DIAS_ARR_DESP',  7, 'Dias corridos entre arribo y despacho de aduana estimado')
    ,('DIAS_DESP_REC',  3, 'Dias corridos entre despacho y recepcion estimada')
    ,('DIAS_ARR_DIST', 10, 'Dias corridos entre arribo y distribucion estimada')
) AS origen (CLAVE, VALOR, DESCRIPCION)
ON destino.CLAVE = origen.CLAVE
WHEN NOT MATCHED BY TARGET THEN
    INSERT (CLAVE, VALOR, DESCRIPCION)
    VALUES (origen.CLAVE, origen.VALOR, origen.DESCRIPCION);
GO
