-- =====================================================================
-- Comercio Exterior - las alicuotas pasan a tener vigencia
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente. No borra ni pisa nada de lo que ya existe.
--
-- ---------------------------------------------------------------------
-- QUE PROBLEMA RESUELVE
--
-- RO_T_CONCEPTOS_ESTIMACION_COMEX tiene UNA fila por concepto y se edita
-- con un UPDATE sobre VALOR_DEFAULT_1 / VALOR_DEFAULT_2. Cuando cambia
-- una alicuota -Derechos del 20% al 22%, por ejemplo- el valor anterior
-- desaparece, y con el la unica forma de saber que alicuota regia cuando
-- se nacionalizo un contenedor de hace ocho meses.
--
-- Esta tabla es ese historico: cada edicion INSERTA una vigencia nueva en
-- vez de pisar la anterior.
--
-- ---------------------------------------------------------------------
-- POR QUE UNA TABLA APARTE Y NO MAS FILAS EN LA DE CONCEPTOS
--
-- Porque ID_CE identifica al CONCEPTO, no a una version suya, y hay dos
-- lugares donde eso ya esta asumido y romperlo seria un problema ajeno a
-- esta entrega:
--
--   1. RO_T_IMPORTACIONES_ESTIMACION_DETALLE.ID_CE apunta al concepto.
--      Con varias filas por concepto el JOIN devolveria una fila por
--      vigencia y cada estimacion se multiplicaria.
--
--   2. El cashflow de la aplicacion de Finanzas suma los gastos de
--      nacionalizacion con "WHERE ID_CE BETWEEN 3 AND 10" escrito en
--      duro (ver cashflow/Class/Comex.php en ProyectosXL/finanzas).
--      Una vigencia nueva con un ID_CE nuevo caeria dentro o fuera de
--      ese rango por azar.
--
-- Asi, RO_T_CONCEPTOS_ESTIMACION_COMEX sigue siendo el padron de
-- conceptos -identidad, nombre, TIPO_VALOR- y esta tabla es el historico
-- de sus valores. Nada de lo que hoy lee la tabla de conceptos cambia de
-- comportamiento.
--
-- ---------------------------------------------------------------------
-- COMO SE RESUELVE QUE ALICUOTA APLICA
--
-- Contra la FECHA DE NACIONALIZACION del contenedor
-- (RO_T_IMPORTACIONES_ENCABEZADO.FECHA_DESP_ADU), no contra la fecha de
-- hoy ni la de carga:
--
--     VIGENCIA_DESDE <= FECHA_DESP_ADU
--     AND (VIGENCIA_HASTA IS NULL OR VIGENCIA_HASTA >= FECHA_DESP_ADU)
--
-- Los dos extremos entran: una operacion nacionalizada exactamente el
-- VIGENCIA_DESDE, o exactamente el VIGENCIA_HASTA, usa esa alicuota.
-- VIGENCIA_HASTA en NULL significa "sigue vigente".
--
-- Si ninguna vigencia cubre la fecha, el codigo cae al VALOR_DEFAULT_1 /
-- VALOR_DEFAULT_2 del padron, que es exactamente lo que hace hoy. Por eso
-- la aplicacion no se rompe si este script no se corrio.
--
-- ---------------------------------------------------------------------
-- POR QUE VIGENCIA_HASTA EXISTE SI SE PODRIA DEDUCIR
--
-- Se podria: la vigencia siguiente empieza donde termina la anterior. Se
-- guarda igual porque habilita el caso que no se deduce -una alicuota que
-- rigio un periodo y despues NO fue reemplazada por ninguna, como un
-- impuesto que se elimina- y porque hace que el hueco entre dos periodos
-- sea un dato explicito en vez de un efecto de como se ordenan las filas.
--
-- ---------------------------------------------------------------------
-- NO HAY UNIQUE SOBRE (ID_CE, VIGENCIA_DESDE)
--
-- Nada impide corregir dos veces el mismo dia una alicuota recien
-- tipeada mal; con un UNIQUE la segunda correccion fallaria con un error
-- de indice. El desempate entre dos vigencias que arrancan el mismo dia
-- es por ID, que es IDENTITY: gana la insertada despues. Es el mismo
-- criterio de RO_T_CASHFLOW_COBEL_ALICUOTA en la aplicacion de Finanzas.
--
-- La SUPERPOSICION de periodos, en cambio, si se valida: la valida PHP al
-- guardar (AlicuotasVigencia::validarSuperposicion), porque depende de
-- las otras filas del mismo concepto y un CHECK de columna no puede
-- mirarlas.
--
-- ---------------------------------------------------------------------
-- NO HAY BAJAS FISICAS
--
-- Una vigencia se retira con ACTIVO = 0. Borrarla dejaria estimaciones
-- historicas apuntando a una alicuota que ya no se puede explicar.
-- =====================================================================

SET NOCOUNT ON;
GO

-- ---------------------------------------------------------------------
-- 1) La tabla.
-- ---------------------------------------------------------------------
IF OBJECT_ID('dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA (
        ID             INT IDENTITY(1,1) NOT NULL,
        ID_CE          INT           NOT NULL,

        -- Mismos dos parametros que el padron. La escala es (18,6) y no
        -- (18,4) como en RO_T_CONCEPTOS_ESTIMACION_COMEX porque es la que
        -- usa RO_T_IMPORTACIONES_ESTIMACION_DETALLE.VALOR_DEFAULT_1, que
        -- es donde termina copiado este valor al armar una estimacion.
        VALOR_1        DECIMAL(18,6) NULL,
        VALOR_2        DECIMAL(18,6) NULL,

        VIGENCIA_DESDE DATE          NOT NULL,
        VIGENCIA_HASTA DATE          NULL,   -- NULL = sigue vigente

        ACTIVO         BIT           NOT NULL
            CONSTRAINT DF_RO_T_CE_COMEX_VIG_ACTIVO DEFAULT (1),
        OBSERVACION    VARCHAR(200)  NULL,
        FECHA_ALTA     DATETIME      NOT NULL
            CONSTRAINT DF_RO_T_CE_COMEX_VIG_FALTA  DEFAULT (GETDATE()),
        USUARIO        VARCHAR(50)   NULL,

        CONSTRAINT PK_RO_T_CE_COMEX_VIGENCIA PRIMARY KEY CLUSTERED (ID),

        -- Un periodo que termina antes de empezar no es un periodo.
        CONSTRAINT CK_RO_T_CE_COMEX_VIG_RANGO
            CHECK (VIGENCIA_HASTA IS NULL OR VIGENCIA_HASTA >= VIGENCIA_DESDE)
    );

    -- Es el indice de "que alicuota rige para este concepto a esta
    -- fecha", que corre una vez por concepto en cada estimacion.
    CREATE NONCLUSTERED INDEX IX_RO_T_CE_COMEX_VIG_RESOLUCION
        ON dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA
           (ID_CE, ACTIVO, VIGENCIA_DESDE DESC, ID DESC)
        INCLUDE (VALOR_1, VALOR_2, VIGENCIA_HASTA);
END
GO

-- ---------------------------------------------------------------------
-- 2) La FK contra el padron de conceptos, SOLO si se puede crear.
--
-- En central RO_T_CONCEPTOS_ESTIMACION_COMEX tiene PK sobre ID_CE y la FK
-- entra. En uy la tabla es un heap sin PK ni indice unico -los dos
-- esquemas divergieron, igual que paso con FECHA_DISTRI en el script 05-
-- y SQL Server rechaza una FK contra una columna sin unicidad.
--
-- El script no fuerza una PK sobre una tabla de otra base con datos: eso
-- es una decision aparte. Sin la FK el modulo funciona igual; lo unico
-- que se pierde es que la base impida por si sola una vigencia huerfana.
-- ---------------------------------------------------------------------
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys
               WHERE name = 'FK_RO_T_CE_COMEX_VIG_CONCEPTO')
   AND EXISTS (SELECT 1 FROM sys.indexes
               WHERE object_id = OBJECT_ID('dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX')
                 AND is_unique = 1)
BEGIN
    ALTER TABLE dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA
        ADD CONSTRAINT FK_RO_T_CE_COMEX_VIG_CONCEPTO
        FOREIGN KEY (ID_CE)
        REFERENCES dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX (ID_CE);
END
GO

-- ---------------------------------------------------------------------
-- 3) Semilla: una vigencia abierta por concepto, con lo que hay hoy.
--
-- DESDE CUANDO. Nadie registro nunca desde cuando rigen estos valores, y
-- la tabla de conceptos solo guarda ULT_ACTUA, que es cuando se toco por
-- ultima vez y no cuando empezo a regir. Se usa la fecha de
-- nacionalizacion mas antigua del maestro -2022-12-22 en central,
-- 2023-11-15 en uy al 19/09/2026-.
--
-- Por que esa y no la fecha de hoy, que seria lo unico estrictamente
-- verdadero: porque con la fecha de hoy TODA operacion ya nacionalizada
-- quedaria sin vigencia que la cubra, y el proposito del modulo es
-- justamente que una operacion historica encuentre su alicuota. Cubrir
-- desde el primer contenedor afirma lo mismo que la aplicacion afirma hoy
-- -estos son los unicos valores que el sistema conocio- sin inventar un
-- corte que nadie definio.
--
-- WHEN NOT MATCHED: una segunda corrida no agrega una vigencia mas ni
-- pisa una que alguien haya cargado desde Parametros.
-- ---------------------------------------------------------------------
DECLARE @DesdeInicial DATE = (
    SELECT COALESCE(MIN(FECHA_DESP_ADU), MIN(FECHA_MOV), CAST(GETDATE() AS DATE))
    FROM RO_T_IMPORTACIONES_ENCABEZADO
);

SET @DesdeInicial = ISNULL(@DesdeInicial, CAST(GETDATE() AS DATE));

PRINT 'Vigencia inicial desde: ' + CONVERT(VARCHAR(10), @DesdeInicial, 120);

MERGE dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA AS T
USING (
    SELECT C.ID_CE, C.VALOR_DEFAULT_1, C.VALOR_DEFAULT_2
    FROM dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX C
) AS S
    ON T.ID_CE = S.ID_CE
WHEN NOT MATCHED BY TARGET THEN
    INSERT (ID_CE, VALOR_1, VALOR_2, VIGENCIA_DESDE, VIGENCIA_HASTA, ACTIVO, OBSERVACION)
    VALUES (S.ID_CE, S.VALOR_DEFAULT_1, S.VALOR_DEFAULT_2, @DesdeInicial, NULL, 1,
            'Vigencia inicial: valores del padron al migrar');

PRINT 'Vigencias sembradas en esta corrida: ' + CAST(@@ROWCOUNT AS VARCHAR(10));
GO

-- ---------------------------------------------------------------------
-- 4) Control: periodos superpuestos.
--
-- Se imprime en vez de fallar. El script puede correrse sobre datos ya
-- editados desde la pantalla, y ahi el diagnostico sirve mas que un
-- error que corta la corrida. Sobre la semilla el resultado es siempre
-- vacio: hay una sola vigencia por concepto.
-- ---------------------------------------------------------------------
PRINT '--- Superposiciones (si no lista nada, no hay) ---';
GO

SELECT
    A.ID_CE,
    C.CONCEPTO,
    A.ID AS ID_A, A.VIGENCIA_DESDE AS DESDE_A, A.VIGENCIA_HASTA AS HASTA_A,
    B.ID AS ID_B, B.VIGENCIA_DESDE AS DESDE_B, B.VIGENCIA_HASTA AS HASTA_B
FROM dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA A
INNER JOIN dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA B
        ON B.ID_CE = A.ID_CE
       AND B.ID    > A.ID
       AND B.ACTIVO = 1
       AND A.VIGENCIA_DESDE <= ISNULL(B.VIGENCIA_HASTA, '9999-12-31')
       AND B.VIGENCIA_DESDE <= ISNULL(A.VIGENCIA_HASTA, '9999-12-31')
LEFT JOIN dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX C ON C.ID_CE = A.ID_CE
WHERE A.ACTIVO = 1
ORDER BY A.ID_CE, A.VIGENCIA_DESDE;
GO

-- ---------------------------------------------------------------------
-- 5) Control: como quedo el cuadro de vigencias.
-- ---------------------------------------------------------------------
PRINT '--- Vigencias por concepto ---';
GO

SELECT
    V.ID_CE,
    C.CONCEPTO,
    C.TIPO_VALOR,
    V.VALOR_1,
    V.VALOR_2,
    V.VIGENCIA_DESDE,
    V.VIGENCIA_HASTA,
    CASE WHEN V.VIGENCIA_HASTA IS NULL THEN 'vigente' ELSE 'cerrada' END AS ESTADO,
    V.ACTIVO
FROM dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA V
LEFT JOIN dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX C ON C.ID_CE = V.ID_CE
ORDER BY V.ID_CE, V.VIGENCIA_DESDE, V.ID;
GO

PRINT 'Alicuotas de Comercio Exterior: vigencias listas.';
GO
