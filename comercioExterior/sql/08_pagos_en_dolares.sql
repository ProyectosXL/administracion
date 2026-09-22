-- =====================================================================
-- Comercio Exterior - los pagos al proveedor del exterior pasan a U$S
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente: se puede correr las veces que haga falta.
-- NO borra ninguna fila ni ningun importe: lo que convierte lo deja
-- guardado antes en una columna nueva.
--
-- ---------------------------------------------------------------------
-- QUE PROBLEMA RESUELVE
--
-- RO_T_IMPORTACIONES_ENCABEZADO_PAGOS.MONTO no tiene marcador de moneda,
-- y la pantalla calculaba el saldo pendiente contra VALOR_FOB_PESO. El
-- relevamiento contra la base del 19/09/2026 encontro las dos monedas
-- conviviendo en la misma columna:
--
--   central : 65 filas en U$S (MONTO ~ VALOR_FOB_DOLAR)
--              7 filas en $   (MONTO = VALOR_FOB_PESO exacto; IDs 96 a 102,
--                              todas cargadas el 21/08/2026)
--   uy      : 19 filas en U$S (en uy VALOR_FOB_PESO = VALOR_FOB_DOLAR,
--                              asi que la cuenta contra pesos daba dolares
--                              y nadie cargo nunca en moneda local)
--
-- No hay ninguna fila ambigua: la separacion entre los dos grupos es de
-- tres ordenes de magnitud (el tipo de cambio ronda 1400). El corte que
-- usa este script -MONTO > VALOR_FOB_DOLAR * 10- clasifico las 91 filas
-- sin un solo caso dudoso.
--
-- ---------------------------------------------------------------------
-- QUE HACE
--
-- MONTO pasa a significar U$S, siempre. Las filas que estaban en pesos se
-- dividen por el TIPO_CAMBIO del contenedor y el importe tal como se
-- tipeo queda en MONTO_ORIGEN_ARS, que es la columna que hace que esta
-- conversion no sea una perdida de informacion: el dato original se
-- puede leer, auditar y revertir.
--
-- FECHA_CONV_USD dice CUANDO se convirtio. Sin ella, tres meses despues
-- una fila convertida por este script y una cargada en dolares desde el
-- principio son indistinguibles, y MONTO_ORIGEN_ARS en NULL no alcanza
-- para diferenciarlas (una fila que siempre estuvo en dolares tambien lo
-- tiene en NULL).
--
-- ---------------------------------------------------------------------
-- POR QUE NO SE PISA UNA FILA SIN TIPO_CAMBIO
--
-- Dividir por un tipo de cambio que no esta cargado daria una division
-- por cero o un importe inventado. Esas filas se dejan como estan y el
-- script las lista al final para que alguien decida. En la base actual
-- no hay ninguna.
--
-- ---------------------------------------------------------------------
-- QUIEN MAS LEE ESTA TABLA
--
-- EL CASHFLOW. Este bloque decia "nadie fuera de Comercio Exterior" y
-- dejo de ser cierto con la rama feature/comex-saldo-pendiente de
-- ProyectosXL/finanzas: su pestana Proveedores del exterior y la fila del
-- tablero pasaron de proyectar el VALOR_FOB_DOLAR entero a proyectar LO
-- QUE FALTA PAGAR, y para eso leen RO_T_IMPORTACIONES_ENCABEZADO_PAGOS.
--
-- Lee SUM(MONTO) por contenedor -resuelto a la OC principal, igual que
-- Pagos::obtenerResumen()- y muestra los pagos uno por uno en su grilla.
-- SOLO LECTURA: no inserta, no actualiza y no borra. El circuito sigue
-- siendo de Comercio Exterior.
--
-- POR QUE IMPORTA PARA ESTE SCRIPT EN PARTICULAR: lo que este script
-- decidio es que MONTO SIGNIFICA DOLARES, siempre. El cashflow lee esa
-- columna dando por hecho eso, y la resta contra VALOR_FOB_DOLAR no
-- tiene forma de notar si una fila vuelve a cargarse en pesos: daria un
-- pendiente negativo, que alla se toma como cero, y el egreso
-- desapareceria del tablero sin que nada falle. Si alguna vez se revierte
-- esta conversion, hay que avisar del otro lado.
--
-- Y SI LA REGLA DEL SALDO CAMBIA, HAY QUE REPLICARLA ALLA:
-- Pagos::obtenerResumen() esta copiada -no incluida: son dos
-- aplicaciones y dos despliegues- en Comex::saldoPendiente() de
-- ProyectosXL/finanzas, con los mismos cuatro estados y la misma
-- tolerancia de un centavo. Nada en el codigo lo detecta solo.
-- =====================================================================

SET NOCOUNT ON;
GO

-- ---------------------------------------------------------------------
-- 1) Las dos columnas nuevas.
-- ---------------------------------------------------------------------
IF COL_LENGTH('RO_T_IMPORTACIONES_ENCABEZADO_PAGOS', 'MONTO_ORIGEN_ARS') IS NULL
BEGIN
    ALTER TABLE RO_T_IMPORTACIONES_ENCABEZADO_PAGOS
        ADD MONTO_ORIGEN_ARS DECIMAL(18,2) NULL;
END
GO

IF COL_LENGTH('RO_T_IMPORTACIONES_ENCABEZADO_PAGOS', 'FECHA_CONV_USD') IS NULL
BEGIN
    ALTER TABLE RO_T_IMPORTACIONES_ENCABEZADO_PAGOS
        ADD FECHA_CONV_USD DATETIME NULL;
END
GO

-- ---------------------------------------------------------------------
-- 2) Foto del antes, para poder comparar.
-- ---------------------------------------------------------------------
PRINT '--- ANTES ---';
GO

SELECT
    CASE
        WHEN P.MONTO_ORIGEN_ARS IS NOT NULL                 THEN '3. ya convertida'
        WHEN E.VALOR_FOB_DOLAR IS NULL OR E.VALOR_FOB_DOLAR <= 0
                                                            THEN '4. sin FOB U$S de referencia'
        WHEN P.MONTO > E.VALOR_FOB_DOLAR * 10               THEN '2. en pesos, a convertir'
        ELSE                                                     '1. ya en U$S'
    END                       AS CLASIFICACION,
    COUNT(*)                  AS FILAS,
    MIN(P.MONTO)              AS MONTO_MIN,
    MAX(P.MONTO)              AS MONTO_MAX
FROM RO_T_IMPORTACIONES_ENCABEZADO_PAGOS P
LEFT JOIN RO_T_IMPORTACIONES_ENCABEZADO E ON E.ID = P.ID_ENCABEZADO
GROUP BY
    CASE
        WHEN P.MONTO_ORIGEN_ARS IS NOT NULL                 THEN '3. ya convertida'
        WHEN E.VALOR_FOB_DOLAR IS NULL OR E.VALOR_FOB_DOLAR <= 0
                                                            THEN '4. sin FOB U$S de referencia'
        WHEN P.MONTO > E.VALOR_FOB_DOLAR * 10               THEN '2. en pesos, a convertir'
        ELSE                                                     '1. ya en U$S'
    END
ORDER BY 1;
GO

-- ---------------------------------------------------------------------
-- 3) La conversion.
--
-- Las tres guardas del WHERE, y que hace cada una:
--   MONTO_ORIGEN_ARS IS NULL  -> no reconvierte lo ya convertido. Es lo
--                                que hace al script reejecutable; sin
--                                esto, la segunda corrida dividiria otra
--                                vez por el tipo de cambio.
--   MONTO > FOB_USD * 10      -> el corte entre las dos monedas.
--   TIPO_CAMBIO > 0           -> no inventa un importe cuando no hay con
--                                que convertir.
-- ---------------------------------------------------------------------
UPDATE P
SET P.MONTO_ORIGEN_ARS = P.MONTO,
    P.MONTO            = CAST(P.MONTO / E.TIPO_CAMBIO AS DECIMAL(18,2)),
    P.FECHA_CONV_USD   = GETDATE()
FROM RO_T_IMPORTACIONES_ENCABEZADO_PAGOS P
INNER JOIN RO_T_IMPORTACIONES_ENCABEZADO E ON E.ID = P.ID_ENCABEZADO
WHERE P.MONTO_ORIGEN_ARS IS NULL
  AND E.VALOR_FOB_DOLAR IS NOT NULL
  AND E.VALOR_FOB_DOLAR > 0
  AND P.MONTO > E.VALOR_FOB_DOLAR * 10
  AND E.TIPO_CAMBIO IS NOT NULL
  AND E.TIPO_CAMBIO > 0;

PRINT 'Filas convertidas en esta corrida: ' + CAST(@@ROWCOUNT AS VARCHAR(10));
GO

-- ---------------------------------------------------------------------
-- 4) Lo que el script decidio NO tocar, fila por fila.
-- Si esta consulta devuelve algo, hay un pago en pesos que no se pudo
-- convertir porque su contenedor no tiene tipo de cambio cargado. El
-- saldo de ese contenedor va a quedar mal hasta que alguien lo resuelva:
-- o se le carga el TIPO_CAMBIO y se vuelve a correr el script, o se
-- corrige el MONTO a mano.
-- ---------------------------------------------------------------------
PRINT '--- PENDIENTES (si no lista nada, no quedo ninguno) ---';
GO

SELECT
    P.ID                 AS ID_PAGO,
    P.ID_ENCABEZADO,
    E.CONTENEDOR,
    P.MONTO,
    E.VALOR_FOB_DOLAR,
    E.TIPO_CAMBIO,
    'en pesos pero sin TIPO_CAMBIO para convertir' AS MOTIVO
FROM RO_T_IMPORTACIONES_ENCABEZADO_PAGOS P
INNER JOIN RO_T_IMPORTACIONES_ENCABEZADO E ON E.ID = P.ID_ENCABEZADO
WHERE P.MONTO_ORIGEN_ARS IS NULL
  AND E.VALOR_FOB_DOLAR IS NOT NULL
  AND E.VALOR_FOB_DOLAR > 0
  AND P.MONTO > E.VALOR_FOB_DOLAR * 10
  AND (E.TIPO_CAMBIO IS NULL OR E.TIPO_CAMBIO <= 0)
ORDER BY P.ID;
GO

-- ---------------------------------------------------------------------
-- 5) Control: saldo por contenedor, ya en U$S.
-- Un SOBREPAGO en esta lista es un dato a mirar, no necesariamente un
-- error: puede ser un pago cargado de mas o un FOB que hay que corregir,
-- que es justamente lo que el requerimiento 1 vino a permitir.
-- ---------------------------------------------------------------------
PRINT '--- DESPUES: saldo en U$S por contenedor con pagos ---';
GO

SELECT TOP 100
    E.ID,
    E.CONTENEDOR,
    E.VALOR_FOB_DOLAR                       AS FOB_USD,
    SUM(P.MONTO)                            AS PAGADO_USD,
    E.VALOR_FOB_DOLAR - SUM(P.MONTO)        AS SALDO_USD,
    COUNT(*)                                AS PAGOS,
    CASE
        WHEN E.VALOR_FOB_DOLAR - SUM(P.MONTO) < -0.01 THEN 'SOBREPAGO'
        WHEN ABS(E.VALOR_FOB_DOLAR - SUM(P.MONTO)) <= 0.01 THEN 'CANCELADO'
        ELSE 'PENDIENTE'
    END                                     AS ESTADO
FROM RO_T_IMPORTACIONES_ENCABEZADO_PAGOS P
INNER JOIN RO_T_IMPORTACIONES_ENCABEZADO E ON E.ID = P.ID_ENCABEZADO
GROUP BY E.ID, E.CONTENEDOR, E.VALOR_FOB_DOLAR
ORDER BY E.ID;
GO

PRINT 'Pagos de Comercio Exterior: MONTO expresado en U$S.';
GO
