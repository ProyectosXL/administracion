/*
    RO_T_SUCURSALES_ALQUILERES_EXC

    Excepciones manuales a la regla automática de visibilidad/carga de sucursales
    del módulo Alquileres (ver RO_V_SUCURSALES_ALQUILERES).

    La tabla se crea VACÍA y así debería quedar en condiciones normales: la regla
    automática (activa hoy, o con historial de alquileres) resuelve el caso general.
    Sólo se carga una fila cuando hace falta pisar esa regla para un caso puntual.

    VISIBLE  NULL = automático | 1 = mostrar siempre | 0 = ocultar siempre
    CARGA    NULL = automático | 1 = incluir siempre en cargas de períodos nuevos
                              | 0 = nunca incluir en cargas de períodos nuevos

    Ejemplos de uso:
      -- Una sucursal cerrada a la que todavía hay que imputarle un gasto tardío:
      INSERT INTO RO_T_SUCURSALES_ALQUILERES_EXC (NRO_SUCURS, CARGA, OBSERVACION, USUARIO)
      VALUES ('99', 1, 'Gasto final de rescisión pendiente', 'usuario');

      -- Una sucursal con historial que no se quiere ver más en el módulo:
      INSERT INTO RO_T_SUCURSALES_ALQUILERES_EXC (NRO_SUCURS, VISIBLE, OBSERVACION, USUARIO)
      VALUES ('99', 0, 'Migrada a otro circuito', 'usuario');

    Para volver al comportamiento automático, borrar la fila (o poner el flag en NULL).
*/

IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'RO_T_SUCURSALES_ALQUILERES_EXC')
BEGIN
    CREATE TABLE RO_T_SUCURSALES_ALQUILERES_EXC (
        NRO_SUCURS  VARCHAR(20)  NOT NULL,
        VISIBLE     BIT          NULL,
        CARGA       BIT          NULL,
        OBSERVACION VARCHAR(200) NULL,
        USUARIO     VARCHAR(50)  NULL,
        FECHA_MODIF DATETIME     NOT NULL CONSTRAINT DF_RO_T_SUC_ALQ_EXC_FECHA DEFAULT (GETDATE()),
        CONSTRAINT PK_RO_T_SUCURSALES_ALQUILERES_EXC PRIMARY KEY (NRO_SUCURS)
    );
END
GO
