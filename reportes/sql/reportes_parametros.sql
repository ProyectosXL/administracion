/* ============================================================================
   REPORTES DE ADMINISTRACION - PARAMETROS E HISTORIAL
   ----------------------------------------------------------------------------
   Base   : central (LAKER_SA)
   Orden  : 1 de 2. Independiente de sql/reportes_permisos.sql, que va a apps.
            Correrlo ANTES de publicar el codigo.
   ----------------------------------------------------------------------------
   QUE HACE

   0. Verifica que esta sea la base central: tienen que estar la tabla
      historica de valorizacion y las fuentes del costo actual (STA11, STA12,
      SJ_VIEW_STA11 y RO_ORDEN_COMPRA_IMPORT_COSTO_NAC). Si falta alguna, avisa
      y NO HACE NADA: esta base no es la del reporte.

   1. RO_T_REPORTES_ADM_PARAMETROS, una fila por parametro:
        CLAVE, NOMBRE, VALOR DECIMAL(10,4), DESCRIPCION
        USUARIO_ALTA / FECHA_ALTA, USUARIO_MODIF / FECHA_MODIF
      La pestaña Parametros se arma desde esta tabla: el proximo reporte que
      necesite un parametro suma su fila aca (y su regla de validacion en
      Class/Parametros.php::REGLAS), sin tocar la pantalla.

   2. RO_T_REPORTES_ADM_PARAMETROS_HIST: cada cambio, con el valor anterior,
      el nuevo, quien y cuando. Lo escribe Parametros::guardar() en la misma
      transaccion que el UPDATE.

   3. La semilla: FACTOR_NAC_ESTIMADO = 1.65, el valor que la vista
      RO_V_VALORIZACION_STOCK_DETALLADO_LAKERS tiene escrito. Lo usa SOLO el
      modo "costo actual" de la valorizacion, para los articulos con partida
      sin costo de nacionalizacion cargado. La vista no lo lee: sigue con su
      1.65 propio.

   El backend escribe SIEMPRE las columnas de auditoria y el front no manda
   ninguna. Las fechas las pone el servidor (GETDATE()).

   Reejecutable: tablas y columnas con IF NOT EXISTS / COL_LENGTH, la semilla
   solo si la clave no existe (no pisa un valor ya modificado). No borra nada.

   SI NO SE CORRE: la pestaña Parametros no muestra nada y el modo "costo
   actual" de la valorizacion no se puede consultar (avisa que falta el
   parametro). El modo historico funciona igual.
   ========================================================================== */

SET NOCOUNT ON;
GO

/* ---- 0. Base correcta ---- */
DECLARE @falta NVARCHAR(400) = CONCAT(
    CASE WHEN OBJECT_ID('dbo.RO_T_VALORIZACION_STOCK_DETALLADO_LAKERS', 'U') IS NULL THEN N' RO_T_VALORIZACION_STOCK_DETALLADO_LAKERS' END,
    CASE WHEN OBJECT_ID('dbo.STA11') IS NULL THEN N' STA11' END,
    CASE WHEN OBJECT_ID('dbo.STA12') IS NULL THEN N' STA12' END,
    CASE WHEN OBJECT_ID('dbo.SJ_VIEW_STA11') IS NULL THEN N' SJ_VIEW_STA11' END,
    CASE WHEN OBJECT_ID('dbo.RO_ORDEN_COMPRA_IMPORT_COSTO_NAC') IS NULL THEN N' RO_ORDEN_COMPRA_IMPORT_COSTO_NAC' END);

DECLARE @base NVARCHAR(128) = DB_NAME();

IF @falta <> N''
BEGIN
    RAISERROR(N'Este script va en la base central (LAKER_SA) y en %s faltan:%s. No se hizo nada.', 16, 1, @base, @falta);
    SET NOEXEC ON;
END
ELSE
    PRINT CONCAT('Base ', DB_NAME(), ': estan la tabla historica y las fuentes del costo actual.');
GO

/* ---- 1. Parametros ---- */
IF OBJECT_ID('dbo.RO_T_REPORTES_ADM_PARAMETROS', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.RO_T_REPORTES_ADM_PARAMETROS (
        CLAVE         VARCHAR(50)    NOT NULL CONSTRAINT PK_REPORTES_ADM_PARAMETROS PRIMARY KEY,
        NOMBRE        NVARCHAR(100)  NOT NULL,
        VALOR         DECIMAL(10,4)  NOT NULL,
        DESCRIPCION   NVARCHAR(500)  NULL,
        USUARIO_ALTA  VARCHAR(50)    NULL,
        FECHA_ALTA    DATETIME       NULL CONSTRAINT DF_REPORTES_ADM_PARAMETROS_FECHA_ALTA DEFAULT (GETDATE()),
        USUARIO_MODIF VARCHAR(50)    NULL,
        FECHA_MODIF   DATETIME       NULL
    );
    PRINT 'Creada RO_T_REPORTES_ADM_PARAMETROS.';
END
ELSE
    PRINT 'RO_T_REPORTES_ADM_PARAMETROS ya existe: se revisan sus columnas.';
GO

/* Columnas que pudieran faltar si la tabla se creo a mano antes */
IF COL_LENGTH('dbo.RO_T_REPORTES_ADM_PARAMETROS', 'DESCRIPCION') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_REPORTES_ADM_PARAMETROS ADD DESCRIPCION NVARCHAR(500) NULL;
    PRINT '   + DESCRIPCION';
END
IF COL_LENGTH('dbo.RO_T_REPORTES_ADM_PARAMETROS', 'USUARIO_ALTA') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_REPORTES_ADM_PARAMETROS ADD USUARIO_ALTA VARCHAR(50) NULL;
    PRINT '   + USUARIO_ALTA';
END
IF COL_LENGTH('dbo.RO_T_REPORTES_ADM_PARAMETROS', 'FECHA_ALTA') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_REPORTES_ADM_PARAMETROS ADD FECHA_ALTA DATETIME NULL
        CONSTRAINT DF_REPORTES_ADM_PARAMETROS_FECHA_ALTA DEFAULT (GETDATE());
    PRINT '   + FECHA_ALTA';
END
IF COL_LENGTH('dbo.RO_T_REPORTES_ADM_PARAMETROS', 'USUARIO_MODIF') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_REPORTES_ADM_PARAMETROS ADD USUARIO_MODIF VARCHAR(50) NULL;
    PRINT '   + USUARIO_MODIF';
END
IF COL_LENGTH('dbo.RO_T_REPORTES_ADM_PARAMETROS', 'FECHA_MODIF') IS NULL
BEGIN
    ALTER TABLE dbo.RO_T_REPORTES_ADM_PARAMETROS ADD FECHA_MODIF DATETIME NULL;
    PRINT '   + FECHA_MODIF';
END
GO

/* ---- 2. Historial ---- */
IF OBJECT_ID('dbo.RO_T_REPORTES_ADM_PARAMETROS_HIST', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.RO_T_REPORTES_ADM_PARAMETROS_HIST (
        ID              INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_REPORTES_ADM_PARAMETROS_HIST PRIMARY KEY,
        CLAVE           VARCHAR(50)    NOT NULL,
        VALOR_ANTERIOR  DECIMAL(10,4)  NULL,
        VALOR_NUEVO     DECIMAL(10,4)  NOT NULL,
        USUARIO_ALTA    VARCHAR(50)    NOT NULL,
        FECHA_ALTA      DATETIME       NOT NULL CONSTRAINT DF_REPORTES_ADM_PARAMETROS_HIST_FECHA_ALTA DEFAULT (GETDATE())
    );
    CREATE INDEX IX_REPORTES_ADM_PARAMETROS_HIST_CLAVE
        ON dbo.RO_T_REPORTES_ADM_PARAMETROS_HIST (CLAVE, FECHA_ALTA DESC);
    PRINT 'Creada RO_T_REPORTES_ADM_PARAMETROS_HIST.';
END
ELSE
    PRINT 'RO_T_REPORTES_ADM_PARAMETROS_HIST ya existe: no se toca.';
GO

/* ---- 3. Semilla ----
   Con un origen y no con NULL en USUARIO_ALTA: NULL quiere decir "no se sabe
   quien", y aca si se sabe: lo dio de alta este script. */
IF NOT EXISTS (SELECT 1 FROM dbo.RO_T_REPORTES_ADM_PARAMETROS WHERE CLAVE = 'FACTOR_NAC_ESTIMADO')
BEGIN
    INSERT INTO dbo.RO_T_REPORTES_ADM_PARAMETROS (CLAVE, NOMBRE, VALOR, DESCRIPCION, USUARIO_ALTA, FECHA_ALTA)
    VALUES ('FACTOR_NAC_ESTIMADO', N'Factor de nacionalización estimado', 1.65,
            N'Valorización de stock, modo costo actual: multiplica el costo de última compra de los artículos con partida (importados) que no tienen costo de nacionalización cargado.',
            'SISTEMA:reportes_parametros.sql', GETDATE());
    PRINT 'Semilla: FACTOR_NAC_ESTIMADO = 1.65.';
END
ELSE
BEGIN
    DECLARE @vigente DECIMAL(10,4) = (SELECT VALOR FROM dbo.RO_T_REPORTES_ADM_PARAMETROS WHERE CLAVE = 'FACTOR_NAC_ESTIMADO');
    PRINT CONCAT('FACTOR_NAC_ESTIMADO ya existe con valor ', @vigente, ': no se toca.');
END
GO

PRINT 'Parametros de Reportes de Administracion listos.';
GO

SET NOEXEC OFF;
GO
