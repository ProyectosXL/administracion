-- RO_SP_VENTA_DIARIA_SUCURSALES_ACTUALIZAR
-- Recalcula IMPORTE_$_SISTEMA de RO_T_VENTA_DIARIA_SUCURSALES (solo filas existentes y no verificadas).
-- Lo usa el botón "Reprocesar" de controlSucursales/controlMasivoCaja.php.
--
-- Fix 2026-10: el bloque PROMO BANCO estaba copiado del de EFECTIVO (CTA03 VISA/ICBC) y pisaba el
-- PROMO BANCO con un valor distinto al que graba RO_SP_VENTA_DIARIA_SUCURSALES. Ahora usa la misma
-- lógica que el SP original: CTA29 cuenta 250800 y N_COMP_IMPUTADO = ''.

ALTER PROCEDURE RO_SP_VENTA_DIARIA_SUCURSALES_ACTUALIZAR
    @FECHA_DESDE  DATE = NULL,   -- Si no se pasa: hoy - 60 días
    @FECHA_HASTA  DATE = NULL,   -- Si no se pasa: hoy
    @NRO_SUCURSAL INT  = NULL    -- Si no se pasa: todas las sucursales
AS
BEGIN
    SET NOCOUNT ON;

    -- VALORES POR DEFECTO (COMPORTAMIENTO ORIGINAL)
    SET @FECHA_DESDE = ISNULL(@FECHA_DESDE, DATEADD(DAY, -60, CAST(GETDATE() AS DATE)));
    SET @FECHA_HASTA = ISNULL(@FECHA_HASTA, CAST(GETDATE() AS DATE));

    IF @FECHA_DESDE > @FECHA_HASTA
    BEGIN
        RAISERROR('La fecha desde no puede ser mayor que la fecha hasta.', 16, 1);
        RETURN;
    END;

    -- LÍMITE SUPERIOR EXCLUSIVO (INCLUYE TODO EL DÍA HASTA, AUNQUE FECHA_EMIS TENGA HORA)
    DECLARE @FECHA_HASTA_EXCL DATE = DATEADD(DAY, 1, @FECHA_HASTA);

    DECLARE @TABLA TABLE
    (
        FECHA         DATE,
        NRO_SUCURSAL  INT,
        DESC_SUCURSAL VARCHAR(40),
        MEDIO_PAGO    VARCHAR(20),
        IMPORTE       FLOAT,
        COTIZACION_TC FLOAT
    );

    INSERT INTO @TABLA (FECHA, NRO_SUCURSAL, DESC_SUCURSAL, MEDIO_PAGO, IMPORTE, COTIZACION_TC)
    SELECT
        A.FECHA_EMIS,
        NRO_SUCURSAL,
        DESC_SUCURSAL,
        MEDIO_PAGO,
        CAST(SUM(IMPORTE) AS DECIMAL(10,0)) AS IMPORTE,
        COTIZACION_TC
    FROM
    (
        SELECT
            A.FECHA_EMIS,
            A.NRO_SUCURSAL,
            A.DESC_SUCURSAL,
            MEDIO_PAGO,
            CAST(SUM(IMPORTE) AS FLOAT) AS IMPORTE,
            COTIZACION_TC
        FROM
        (
            SELECT
                FECHA_EMIS,
                SUCURSAL.NRO_SUCURSAL,
                SUCURSAL.DESC_SUCURSAL,
                T_COMP,
                A.N_COMP,
                CASE
                    WHEN COD_CTA_CUENTA_TESORERIA = '100190' THEN 'GO CUOTAS'
                    WHEN COD_CTA_CUENTA_TESORERIA = '100193' THEN 'MODO QR'
                    WHEN COD_CTA_CUENTA_TESORERIA = '100194' THEN 'CUENTA DNI'
                    WHEN COD_CTA_CUENTA_TESORERIA = '100198' THEN 'MERCADO PAGO QR'
                    WHEN COD_CTA_CUENTA_TESORERIA = '100195' THEN 'MERCADO PAGO'
                    WHEN COD_CTA_CUENTA_TESORERIA = '100199' THEN 'MERCADO PAGO'
                    WHEN COD_CTA_CUENTA_TESORERIA = '100900' THEN 'DOLARES'
                    WHEN COD_CTA_CUENTA_TESORERIA = '100910' THEN 'EUROS'
                    WHEN COD_CTA_CUENTA_TESORERIA = '100800' THEN 'BONUS SHOPPING'
                    WHEN COD_CTA_CUENTA_TESORERIA = '100850' THEN 'BONUS SHOPPING'
                    WHEN CTA_CUENTA_TESORERIA.TIPO = 'T' THEN 'TARJETA'
                    WHEN CTA_CUENTA_TESORERIA.TIPO = 'O' THEN 'EFECTIVO'
                END AS MEDIO_PAGO,
                CASE WHEN T_COMP LIKE 'NC%' THEN C.MONTO * -1 ELSE B.MONTO END AS IMPORTE,
                CASE WHEN T_COMP LIKE 'NC%' THEN C.COTIZACION_TC ELSE B.COTIZACION_TC END AS COTIZACION_TC
            FROM CTA02 A
            LEFT JOIN (
                SELECT
                    FECHA,
                    COD_COMP,
                    N_COMP,
                    NRO_SUCURS,
                    COD_CTA,
                    CASE WHEN COD_CTA IN ('100900','100910') THEN CANT_MONE ELSE MONTO END AS MONTO,
                    CASE WHEN COD_CTA IN ('100900','100910') THEN COTIZ_MONE ELSE NULL END AS COTIZACION_TC
                FROM CTA29
                WHERE D_H = 'D'
                  AND COD_COMP = 'FAC'
                  AND CONCILIADO != 1
            ) B
                ON A.T_COMP = B.COD_COMP
               AND A.N_COMP = B.N_COMP
               AND A.NRO_SUCURS = B.NRO_SUCURS
            LEFT JOIN (
                SELECT
                    FECHA,
                    COD_COMP,
                    N_COMP,
                    NRO_SUCURS,
                    COD_CTA,
                    CASE WHEN COD_CTA IN ('100900','100910') THEN CANT_MONE ELSE MONTO END AS MONTO,
                    CASE WHEN COD_CTA IN ('100900','100910') THEN COTIZ_MONE ELSE NULL END AS COTIZACION_TC
                FROM CTA29
                WHERE D_H = 'H'
                  AND COD_COMP = 'NCR'
                  AND NRO_SUCURS != 1
            ) C
                ON A.T_COMP = C.COD_COMP
               AND A.N_COMP = C.N_COMP
               AND A.NRO_SUCURS = C.NRO_SUCURS
            LEFT JOIN CTA_CUENTA_TESORERIA
                ON (CTA_CUENTA_TESORERIA.COD_CTA_CUENTA_TESORERIA = B.COD_CTA)
                OR (CTA_CUENTA_TESORERIA.COD_CTA_CUENTA_TESORERIA = C.COD_CTA)
            LEFT JOIN CTA_CLIENTE
                ON A.COD_CLIENT = CTA_CLIENTE.COD_CLIENTE
            LEFT JOIN SUCURSAL
                ON (A.NRO_SUCURS = 0 AND SUCURSAL.ID_SUCURSAL = (SELECT EMPRESA.ID_SUCURSAL FROM EMPRESA))
                OR A.NRO_SUCURS = SUCURSAL.NRO_SUCURSAL
            WHERE FECHA_EMIS >= @FECHA_DESDE
              AND FECHA_EMIS <  @FECHA_HASTA_EXCL
              AND A.ESTADO <> 'ANU'
        ) A
        WHERE IMPORTE IS NOT NULL
          AND NRO_SUCURSAL != 1
          AND (@NRO_SUCURSAL IS NULL OR NRO_SUCURSAL = @NRO_SUCURSAL)
        GROUP BY
            A.NRO_SUCURSAL,
            A.DESC_SUCURSAL,
            MEDIO_PAGO,
            A.FECHA_EMIS,
            A.COTIZACION_TC

        UNION ALL

        SELECT
            A.FECHA_EMIS,
            A.NRO_SUCURS,
            SUCURSAL.DESC_SUCURSAL,
            'EFECTIVO' AS MEDIO_PAGO,
            CAST(IMPORTE AS FLOAT) AS IMPORTE,
            NULL
        FROM CTA02 A
        INNER JOIN (
            SELECT T_COMP, N_COMP, NRO_SUCURS
            FROM CTA03
            WHERE (COD_ARTICU LIKE '%VISA%' OR COD_ARTICU LIKE '%ICBC%')
            GROUP BY T_COMP, N_COMP, NRO_SUCURS
        ) B
            ON A.NRO_SUCURS = B.NRO_SUCURS
           AND A.N_COMP    = B.N_COMP
           AND A.T_COMP    = B.T_COMP
        LEFT JOIN SUCURSAL
            ON (A.NRO_SUCURS = 0 AND SUCURSAL.ID_SUCURSAL = (SELECT EMPRESA.ID_SUCURSAL FROM EMPRESA))
            OR A.NRO_SUCURS = SUCURSAL.NRO_SUCURSAL
        WHERE FECHA_EMIS >= @FECHA_DESDE
          AND FECHA_EMIS <  @FECHA_HASTA_EXCL
          AND A.T_COMP LIKE 'N%'
          AND A.NRO_SUCURS != '1'
          AND (@NRO_SUCURSAL IS NULL OR A.NRO_SUCURS = @NRO_SUCURSAL)

        UNION ALL

        -- PROMO BANCO: misma lógica que RO_SP_VENTA_DIARIA_SUCURSALES (cuenta 250800, NC sin imputar)
        SELECT
            A.FECHA_EMIS,
            A.NRO_SUCURS,
            SUCURSAL.DESC_SUCURSAL,
            'PROMO BANCO' AS MEDIO_PAGO,
            CAST(-IMPORTE AS FLOAT) AS IMPORTE,
            NULL
        FROM CTA02 A
        INNER JOIN (
            SELECT COD_COMP, N_COMP, NRO_SUCURS
            FROM CTA29
            WHERE COD_CTA = '250800'
            GROUP BY COD_COMP, N_COMP, NRO_SUCURS
        ) B
            ON A.NRO_SUCURS = B.NRO_SUCURS
           AND A.N_COMP    = B.N_COMP
           AND A.T_COMP    = B.COD_COMP
        LEFT JOIN SUCURSAL
            ON (A.NRO_SUCURS = 0 AND SUCURSAL.ID_SUCURSAL = (SELECT EMPRESA.ID_SUCURSAL FROM EMPRESA))
            OR A.NRO_SUCURS = SUCURSAL.NRO_SUCURSAL
        WHERE FECHA_EMIS >= @FECHA_DESDE
          AND FECHA_EMIS <  @FECHA_HASTA_EXCL
          AND A.T_COMP LIKE 'N%'
          AND A.NRO_SUCURS != '1'
          AND A.N_COMP_IMPUTADO = ''
          AND (@NRO_SUCURSAL IS NULL OR A.NRO_SUCURS = @NRO_SUCURSAL)
    ) A
    GROUP BY
        NRO_SUCURSAL,
        DESC_SUCURSAL,
        MEDIO_PAGO,
        FECHA_EMIS,
        COTIZACION_TC
    ORDER BY
        NRO_SUCURSAL;

    -- ACTUALIZA EN CASO QUE HAYA DIFERENCIAS EN LOS IMPORTES DEL SISTEMA (NULL-SAFE)
    UPDATE A
        SET A.IMPORTE_$_SISTEMA = B.IMPORTE
    FROM RO_T_VENTA_DIARIA_SUCURSALES A
    INNER JOIN @TABLA B
        ON A.FECHA        = B.FECHA
       AND A.NRO_SUCURSAL = B.NRO_SUCURSAL
       AND A.MEDIO_PAGO   = B.MEDIO_PAGO
    WHERE A.FECHA >= @FECHA_DESDE
      AND A.FECHA <  @FECHA_HASTA_EXCL
      AND (@NRO_SUCURSAL IS NULL OR A.NRO_SUCURSAL = @NRO_SUCURSAL)
      AND (A.VERIFICADO IS NULL OR A.VERIFICADO = 0)
      AND ISNULL(A.IMPORTE_$_SISTEMA, 0) <> ISNULL(B.IMPORTE, 0)
    OPTION (RECOMPILE);

END;
