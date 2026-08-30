-- =====================================================================
-- Cronograma de contenedores - iconos por rubro
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente: el seed usa MERGE por RUBRO, se puede recorrer.
--
-- RUBRO se declara varchar(40) para espejar SOF_RUBROS_TANGO.RUBRO.
-- OJO: esa columna tiene collation distinta en cada entorno
-- (Modern_Spanish_CI_AI en central, Latin1_General_BIN en uy), asi que
-- todo join por RUBRO contra SOF_RUBROS_TANGO tiene que llevar
-- COLLATE DATABASE_DEFAULT de los dos lados o rompe en uy.
--
-- ICONO acepta dos formatos:
--   - clase de bootstrap-icons ('bi-gem')  -> se renderiza <i class="bi bi-gem">
--   - caracter emoji ('*')                 -> se renderiza como texto
-- Bootstrap Icons no tiene glifos de indumentaria ni calzado, de ahi que
-- esos rubros vayan con emoji.
--
-- Todas las clases bi-* de este seed fueron verificadas contra
-- bootstrap-icons 1.10.0, que es la version que carga cronograma/index.php.
-- =====================================================================

IF OBJECT_ID('RO_T_IMPORTACIONES_RUBRO_ICONO', 'U') IS NULL
BEGIN
    CREATE TABLE RO_T_IMPORTACIONES_RUBRO_ICONO (
        ID      INT IDENTITY(1,1) NOT NULL,
        RUBRO   VARCHAR(40) NOT NULL,
        -- NVARCHAR y no VARCHAR: los emoji son pares suplentes UTF-16 y en
        -- una columna VARCHAR (codepage de Modern_Spanish) se perderian.
        ICONO   NVARCHAR(60) NOT NULL,
        ORDEN   INT NOT NULL CONSTRAINT DF_RO_T_IMP_RUBRO_ORDEN  DEFAULT 0,
        ACTIVO  BIT NOT NULL CONSTRAINT DF_RO_T_IMP_RUBRO_ACTIVO DEFAULT 1,
        CONSTRAINT PK_RO_T_IMP_RUBRO PRIMARY KEY (ID),
        CONSTRAINT UQ_RO_T_IMP_RUBRO UNIQUE (RUBRO)
    );
END
GO

-- ---------------------------------------------------------------------
-- Seed: los 37 rubros que existen hoy en SOF_RUBROS_TANGO.
-- El prompt original listaba 18; los otros 19 se relevaron contra la base
-- y se agregan aca para que el front no arranque escupiendo warnings de
-- rubro sin mapear. Varios tienen volumen alto (RELOJES 3948 articulos,
-- BILLETERAS DE CUERO 3182, _KITS 2176, LENTES 1599).
--
-- Las variantes OUTLET comparten el icono de su rubro base: el badge solo
-- muestra hasta 3 iconos, y duplicar glifos es preferible a inventar
-- simbolos que nadie va a reconocer. SINTETICOS OUTLET conserva bi-percent.
-- ---------------------------------------------------------------------
MERGE RO_T_IMPORTACIONES_RUBRO_ICONO AS destino
USING (VALUES
     ('CARTERAS DE CUERO',      'bi-handbag-fill',  10)
    ,('CARTERAS DE VINILICO',   'bi-handbag',       20)
    ,('CUERO OUTLET',           'bi-handbag-fill',  30)
    ,('BILLETERAS DE CUERO',    'bi-wallet-fill',   40)
    ,('BILLETERAS DE VINILICO', 'bi-wallet2',       50)
    ,('BILLETERAS OUTLET',      'bi-wallet2',       60)
    ,('ACCESORIOS DE CUERO',    'bi-tags-fill',     70)
    ,('ACCESORIOS DE VINILICO', 'bi-tags',          80)
    ,('ACCESORIOS OUTLET',      'bi-tags',          90)
    ,('CINTOS DE CUERO',        'bi-link-45deg',   100)
    ,('CINTOS DE VINILICO',     'bi-link',         110)
    ,('CALZADOS',               NCHAR(55357) + NCHAR(56415), 120)  -- zapatilla
    ,('CALZADOS OUTLET',        NCHAR(55357) + NCHAR(56415), 130)
    ,('CAMPERAS',               NCHAR(55358) + NCHAR(56805), 140)  -- campera
    ,('CAMPERAS OUTLET',        NCHAR(55358) + NCHAR(56805), 150)
    ,('INDUMENTARIA',           NCHAR(55357) + NCHAR(56405), 160)  -- remera
    ,('CHALINAS',               NCHAR(55358) + NCHAR(56803), 170)  -- bufanda
    ,('CHALINAS OUTLET',        NCHAR(55358) + NCHAR(56803), 180)
    ,('EQUIPAJES',              'bi-briefcase',    190)
    ,('EQUIPAJES OUTLET',       'bi-briefcase',    200)
    ,('RELOJES',                'bi-watch',        210)
    ,('RELOJES OUTLET',         'bi-watch',        220)
    ,('LENTES',                 'bi-eyeglasses',   230)
    ,('LENTES OUTLET',          'bi-eyeglasses',   240)
    ,('BIJOU',                  'bi-gem',          250)
    ,('ALHAJEROS',              'bi-box-seam',     260)
    ,('LLAVEROS',               'bi-key',          270)
    ,('PARAGUAS',               'bi-umbrella',     280)
    ,('PARAGUAS OUTLET',        'bi-umbrella',     290)
    ,('COSMETICA',              'bi-droplet',      300)
    ,('COSMETICA OUTLET',       'bi-droplet',      310)
    ,('HOME',                   'bi-house-door',   320)
    ,('HOME OUTLET',            'bi-house-door',   330)
    ,('TOALLAS',                'bi-droplet-half', 340)
    ,('SINTETICOS OUTLET',      'bi-percent',      350)
    ,('PACKAGING',              'bi-box2',         360)
    ,('_KITS',                  'bi-boxes',        370)
) AS origen (RUBRO, ICONO, ORDEN)
ON destino.RUBRO = origen.RUBRO
WHEN NOT MATCHED BY TARGET THEN
    INSERT (RUBRO, ICONO, ORDEN, ACTIVO)
    VALUES (origen.RUBRO, origen.ICONO, origen.ORDEN, 1);
GO
