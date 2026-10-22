/* ============================================================================
   REPORTES DE ADMINISTRACION - ALTA EN GESTIONUSUARIOS Y EL HUB
   ----------------------------------------------------------------------------
   Base   : apps (la de Gestionusuarios: FP_MODULOS, FP_PERMISOS, FP_ROL_PERMISO)
   Orden  : 2 de 2. Independiente de sql/reportes_parametros.sql, que va a
            central. Correrlo cuando el codigo ya este publicado: apenas corre,
            la tarjeta aparece en el HUB para quien tenga el permiso.
   ----------------------------------------------------------------------------
   QUE HACE

   1. El modulo en FP_MODULOS, por codigo REPORTES_ADMINISTRACION. El PHP
      resuelve el modulo_id por este codigo (AuthReportes::MODULO_CODIGO): no
      hay ningun id escrito en el codigo.
   2. El permiso de HUB hub.app.reportes_administracion: es lo que hace
      aparecer la tarjeta en el HUB.
   3. Los permisos internos, con descripcion "[Seccion] ...":
        ra.tab.valorizacion_stock   ver la pestaña y descargar el Excel
        ra.tab.remitos_pendientes   ver los remitos pendientes de facturar y
                                    descargar el Excel (no escribe: no lleva
                                    clave de edicion)
        ra.tab.parametros          ver los parametros
        ra.editar.parametros        modificarlos (exige tambien ra.tab.parametros)
      Son exactamente los de AuthReportes::claves(); tests/test_auth_reportes.php
      falla si las dos listas no coinciden.

   NO ASIGNA ROLES. Los accesos se dan desde Gestionusuarios. El administrador
   (sector Proyectos + es_admin / CONTROLTOTAL) entra a todo sin asignarle nada.

   Reejecutable: todo va con IF NOT EXISTS. No borra ni modifica nada existente.
   Donde ya se corrio, volver a correrlo solo agrega las claves nuevas (como
   ra.tab.remitos_pendientes) y deja las que estaban como estan.

   SI NO SE CORRE: el modulo no aparece en el HUB y solo entra el
   administrador.
   ========================================================================== */

SET NOCOUNT ON;
GO

/* ---- 0. Base correcta: si no estan las tablas del padron, no se sigue ---- */
IF OBJECT_ID('dbo.FP_MODULOS', 'U') IS NULL OR OBJECT_ID('dbo.FP_PERMISOS', 'U') IS NULL
BEGIN
    RAISERROR('Este script va en la base apps de Gestionusuarios y en %s no estan FP_MODULOS / FP_PERMISOS. No se hizo nada.', 16, 1, @@SERVERNAME);
    SET NOEXEC ON;
END
ELSE
    PRINT CONCAT('Base ', DB_NAME(), ': FP_MODULOS y FP_PERMISOS presentes.');
GO

/* ---- 1. Modulo ---- */
IF NOT EXISTS (SELECT 1 FROM dbo.FP_MODULOS WHERE codigo = 'REPORTES_ADMINISTRACION')
BEGIN
    INSERT INTO dbo.FP_MODULOS (codigo, nombre, ruta_url, icono, activo, categoria, descripcion, orden, color, target_blank)
    VALUES ('REPORTES_ADMINISTRACION', N'Reportes de Administración', '/administracion/reportes/', 'fa-file-invoice-dollar', 1,
            N'Administración & Finanzas',
            N'Reportes a medida para Administración, en Excel: valorización de stock por cierre y los que se vayan sumando.',
            100,
            -- Bordo: distinto de sus vecinos en el HUB, que son el gris de
            -- ADMIN, el ambar de FINAN, el verde de COMPR, el verde azulado de
            -- Cashflow (90) y el indigo del Informe Economico (95).
            N'linear-gradient(135deg, #be123c 0%, #e11d48 100%)',
            0);
    PRINT 'Creado el modulo REPORTES_ADMINISTRACION.';
END
ELSE
    PRINT 'El modulo REPORTES_ADMINISTRACION ya existe: no se toca.';
GO

/* ---- 2 y 3. Permisos ---- */
DECLARE @MODULO INT = (SELECT id FROM dbo.FP_MODULOS WHERE codigo = 'REPORTES_ADMINISTRACION');

DECLARE @PERMISOS TABLE (clave VARCHAR(100), nombre NVARCHAR(100), descripcion NVARCHAR(255));
INSERT INTO @PERMISOS VALUES
('hub.app.reportes_administracion', N'Acceso a Reportes de Administración en Hub',
 N'[Hub] Reportes a medida para Administración: valorización de stock y los que se vayan sumando.'),
('ra.tab.valorizacion_stock', N'Solapa Valorización de stock',
 N'[Reportes] Valorización de stock de LAKERS SA por fecha de cierre, a costo histórico o actual, con descarga en Excel'),
('ra.tab.remitos_pendientes', N'Solapa Remitos pendientes de facturar',
 N'[Reportes] Remitos de LAKERS SA que seguían sin facturar a una fecha de corte, con la factura que los resolvió después y descarga en Excel'),
('ra.tab.parametros',         N'Solapa Parámetros',
 N'[Configuración] Ver los parámetros de los reportes y su historial de cambios'),
('ra.editar.parametros',      N'Editar parámetros',
 N'[Edición] Modificar los parámetros de los reportes, como el factor de nacionalización estimado');

INSERT INTO dbo.FP_PERMISOS (modulo_id, clave, nombre, descripcion)
SELECT @MODULO, P.clave, P.nombre, P.descripcion
FROM @PERMISOS P
WHERE NOT EXISTS (SELECT 1 FROM dbo.FP_PERMISOS X WHERE X.clave = P.clave);

PRINT CONCAT('Permisos: ', @@ROWCOUNT, ' nuevos.');

IF EXISTS (SELECT 1 FROM dbo.FP_PERMISOS X JOIN @PERMISOS P ON P.clave = X.clave WHERE X.modulo_id <> @MODULO)
    PRINT 'ATENCION: hay claves ra.* o hub.app.reportes_administracion colgadas de OTRO modulo. No se tocan; revisalas en Gestionusuarios.';
GO

/* ---- Control: el modulo puede estar dado de alta pero apagado ----
   Este script no lo prende (no modifica nada existente). */
IF EXISTS (SELECT 1 FROM dbo.FP_MODULOS WHERE codigo = 'REPORTES_ADMINISTRACION' AND activo = 0)
    PRINT 'ATENCION: REPORTES_ADMINISTRACION existe con activo = 0. Para mostrarlo: UPDATE dbo.FP_MODULOS SET activo = 1 WHERE codigo = ''REPORTES_ADMINISTRACION''.';
GO

PRINT 'Alta de Reportes de Administracion en Gestionusuarios lista.';
GO

SET NOEXEC OFF;
GO
