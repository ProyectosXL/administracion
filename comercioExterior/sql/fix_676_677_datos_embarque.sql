-- =============================================================================
-- fix_676_677_datos_embarque.sql
-- Iguala los campos de embarque del padre (676) con los de la hija (677),
-- que tiene los datos más recientes que el usuario cargó por error.
-- Ejecutar PRIMERO con ROLLBACK para verificar, luego cambiar a COMMIT.
-- =============================================================================

BEGIN TRY
    BEGIN TRANSACTION;

    UPDATE p
    SET
        p.FECHA_EMB       = h.FECHA_EMB,
        p.FACTURA         = h.FACTURA,
        p.NUMERO_BL       = h.NUMERO_BL,
        p.FECHA_EST_PAGO  = h.FECHA_EST_PAGO,
        p.PUERTO_ORIGEN   = h.PUERTO_ORIGEN,
        p.TERMINAL        = h.TERMINAL
    FROM RO_T_IMPORTACIONES_ENCABEZADO p
    INNER JOIN RO_T_IMPORTACIONES_ENCABEZADO h ON h.ID = 677
    WHERE p.ID = 676;

    -- Verificación: ambas filas deben tener los mismos valores después del UPDATE
    SELECT
        ID,
        ID_PADRE,
        LTRIM(RTRIM(ORDEN_COMPRA)) AS ORDEN_COMPRA,
        FECHA_EMB,
        FACTURA,
        NUMERO_BL,
        FECHA_EST_PAGO,
        PUERTO_ORIGEN,
        TERMINAL
    FROM RO_T_IMPORTACIONES_ENCABEZADO
    WHERE ID IN (676, 677)
    ORDER BY ID;

    -- Si los datos coinciden y todo se ve bien:
    --   1. Comentar la línea ROLLBACK
    --   2. Descomentar la línea COMMIT
    ROLLBACK TRANSACTION;
    -- COMMIT TRANSACTION;

END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    SELECT
        ERROR_NUMBER()  AS ErrorNumber,
        ERROR_MESSAGE() AS ErrorMessage,
        ERROR_LINE()    AS ErrorLine;
END CATCH
