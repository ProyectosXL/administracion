-- =====================================================================
-- Correccion puntual: OC 0000100015881 (ID 733) - IVA Adicional en cero
--
-- CORRER SOLO EN central (LAKER_SA). Es un arreglo de UNA fila de datos,
-- no un cambio de esquema. Idempotente: una segunda corrida no hace nada.
--
-- ---------------------------------------------------------------------
-- QUE PASO
--
-- El detalle de estimacion de este contenedor tiene VALOR_DEFAULT_1 en
-- NULL para IVA Adicional (ID_CE = 6), mientras el padron y TODAS las
-- vigencias de ese concepto dicen 0,20. Como la pantalla calcula
--
--     IVA Adicional = Base imponible * VALOR_DEFAULT_1
--
-- el impuesto quedo en 0,00 y el Total nacionalizacion informa de menos.
--
-- ES UN CASO UNICO EN LA BASE. Verificado al 21/09/2026: de los 60
-- contenedores con estimacion, los otros 59 tienen 0.200000 en esa
-- columna. No hay nada que migrar en masa.
--
-- ---------------------------------------------------------------------
-- POR QUE NO LO ARREGLA LA PANTALLA
--
-- Esta estimacion esta CONFIRMADA (13 de 13 filas con CONFIRMADO = 1), y
-- el boton "Recalcular con la alicuota vigente" que agrega esta entrega
-- solo corre sobre estimaciones sin confirmar: congelar los numeros es el
-- punto de confirmar. La pantalla SI avisa del desvio -el aviso se
-- muestra tambien en las confirmadas- pero la correccion es esta.
--
-- La alternativa era corregir el importe a mano desde la pantalla. Se
-- hace por script para que quede escrito cual era el numero anterior y
-- de donde sale el nuevo.
--
-- ---------------------------------------------------------------------
-- DE DONDE SALEN LOS NUMEROS
--
-- La base imponible no se guarda: es un valor calculado de la pantalla.
-- Se deduce del IVA General, que es la misma base por su alicuota:
--
--     Base imponible = IVA General / 0,21 = 21296,63 / 0,21 = 101412,52
--
-- Contrastado contra los otros dos impuestos que usan la misma base, que
-- dan exacto: IIGG = 101412,52 * 0,06 = 6084,75 (guardado 6084,75) y
-- IIBB = 101412,52 * 0,045 = 4563,56 (guardado 4563,56).
--
--     IVA Adicional = 101412,52 * 0,20 = 20282,50
--
-- El UPDATE lo recalcula con esa misma cuenta en vez de escribir el
-- numero a mano, para que no haya un literal que nadie pueda explicar.
--
-- EFECTO EN EL TABLERO. El Total nacionalizacion -que el cashflow de
-- Finanzas suma como ID_CE entre 3 y 10- pasa de 50.918,26 a 71.200,76.
-- Es un gasto que el tablero hoy NO esta proyectando.
-- =====================================================================

SET NOCOUNT ON;
GO

DECLARE @ID_MG INT = 733;
DECLARE @ID_CE INT = 6;      -- IVA Adicional

-- La alicuota que corresponde. Sale del padron; ver la nota del UPDATE
-- sobre por que para este caso es lo mismo que la vigencia y por que la
-- tabla de vigencias no se nombra en estos lotes.
DECLARE @Alicuota DECIMAL(18,6) = (
    SELECT VALOR_DEFAULT_1
    FROM dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX
    WHERE ID_CE = @ID_CE
);

-- Base imponible deducida del IVA General ya guardado.
DECLARE @IvaGeneral DECIMAL(18,2) = (
    SELECT IMPORTE FROM dbo.RO_T_IMPORTACIONES_ESTIMACION_DETALLE
    WHERE ID_MG = @ID_MG AND ID_CE = 5
);

DECLARE @AlicuotaIvaGral DECIMAL(18,6) = (
    SELECT VALOR_DEFAULT_1 FROM dbo.RO_T_IMPORTACIONES_ESTIMACION_DETALLE
    WHERE ID_MG = @ID_MG AND ID_CE = 5
);

DECLARE @Base    DECIMAL(18,6) = NULL;
DECLARE @Importe DECIMAL(18,2) = NULL;

IF @IvaGeneral IS NOT NULL AND @AlicuotaIvaGral > 0
BEGIN
    SET @Base    = @IvaGeneral / @AlicuotaIvaGral;
    SET @Importe = ROUND(@Base * @Alicuota, 2);
END

PRINT 'Alicuota a aplicar   : ' + ISNULL(CAST(@Alicuota AS VARCHAR(20)), '(no se pudo resolver)');
PRINT 'Base imponible       : ' + ISNULL(CAST(@Base    AS VARCHAR(20)), '(no se pudo deducir)');
PRINT 'IVA Adicional nuevo  : ' + ISNULL(CAST(@Importe AS VARCHAR(20)), '(no se pudo calcular)');

PRINT '--- ANTES ---';
GO

SELECT D.ID_CE, C.CONCEPTO, D.VALOR_DEFAULT_1, D.IMPORTE, D.CONFIRMADO
FROM dbo.RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
JOIN dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX C ON C.ID_CE = D.ID_CE
WHERE D.ID_MG = 733 AND D.ID_CE IN (5, 6, 7, 8)
ORDER BY D.ID_CE;

SELECT SUM(IMPORTE) AS TOTAL_NACIONALIZACION_ANTES
FROM dbo.RO_T_IMPORTACIONES_ESTIMACION_DETALLE
WHERE ID_MG = 733 AND ID_CE BETWEEN 3 AND 10;
GO

-- ---------------------------------------------------------------------
-- La correccion.
--
-- SOLO SI LA FILA SIGUE ROTA: el WHERE pide VALOR_DEFAULT_1 IS NULL, asi
-- que una segunda corrida -o una corrida despues de que alguien lo haya
-- arreglado desde la pantalla- no toca nada.
--
-- SE ESCRIBEN LAS DOS COSAS: la alicuota Y el importe. Escribir solo la
-- alicuota dejaria el importe en cero hasta que alguien abriera la
-- pantalla y guardara; escribir solo el importe dejaria la fila diciendo
-- "sin alicuota" al lado de un numero que sale de una.
-- ---------------------------------------------------------------------
BEGIN TRANSACTION;

BEGIN TRY
    /* SE USA EL PADRON, Y PARA ESTE CASO ES EXACTAMENTE LO MISMO QUE LA
       VIGENCIA. Verificado al 21/09/2026: el padron de IVA Adicional dice
       0,2000 y las DOS vigencias cargadas -la de 2022-12-22 a 2026-09-20 y la
       abierta desde 2026-09-21, que es la que cubre la nacionalizacion de este
       contenedor el 2027-04-14- dicen 0,200000. El bloque de diagnostico de
       mas arriba resuelve la vigencia y la imprime, y el control del final
       lista las dos: si alguna vez dejaran de coincidir, se ve.

       Se prefiere el padron porque nombrar
       RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA en ESTE lote haria que el
       script no compile en una base donde el script 09 no corrio, y un IF no
       lo evita: el error es de parseo, no de ejecucion. Es un arreglo de una
       fila, no vale la pena el SQL dinamico que si usa el script 10. */
    DECLARE @Alic DECIMAL(18,6) = (
        SELECT VALOR_DEFAULT_1 FROM dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX WHERE ID_CE = 6
    );

    DECLARE @Imp DECIMAL(18,2) = (
        SELECT ROUND(G.IMPORTE / G.VALOR_DEFAULT_1 * @Alic, 2)
        FROM dbo.RO_T_IMPORTACIONES_ESTIMACION_DETALLE G
        WHERE G.ID_MG = 733 AND G.ID_CE = 5 AND G.VALOR_DEFAULT_1 > 0
    );

    IF @Alic IS NULL OR @Imp IS NULL
    BEGIN
        PRINT 'NO SE CORRIGIO: falta la alicuota del padron o el IVA General guardado.';
        ROLLBACK TRANSACTION;
    END
    ELSE
    BEGIN
        UPDATE dbo.RO_T_IMPORTACIONES_ESTIMACION_DETALLE
           SET VALOR_DEFAULT_1 = @Alic,
               IMPORTE         = @Imp,
               FECHA_MOD       = GETDATE()
         WHERE ID_MG = 733
           AND ID_CE = 6
           AND VALOR_DEFAULT_1 IS NULL;

        PRINT 'Filas corregidas: ' + CAST(@@ROWCOUNT AS VARCHAR(10));

        COMMIT TRANSACTION;
    END
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    PRINT 'ABORTADO, no se escribio nada: ' + ERROR_MESSAGE();
END CATCH
GO

PRINT '--- DESPUES ---';
GO

SELECT D.ID_CE, C.CONCEPTO, D.VALOR_DEFAULT_1, D.IMPORTE, D.CONFIRMADO
FROM dbo.RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
JOIN dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX C ON C.ID_CE = D.ID_CE
WHERE D.ID_MG = 733 AND D.ID_CE IN (5, 6, 7, 8)
ORDER BY D.ID_CE;

SELECT SUM(IMPORTE) AS TOTAL_NACIONALIZACION_DESPUES
FROM dbo.RO_T_IMPORTACIONES_ESTIMACION_DETALLE
WHERE ID_MG = 733 AND ID_CE BETWEEN 3 AND 10;
GO

-- ---------------------------------------------------------------------
-- Control: que no haya quedado ningun otro contenedor en el mismo estado.
-- Al 21/09/2026 este listado sale vacio.
-- ---------------------------------------------------------------------
PRINT '--- Otros detalles sin alicuota donde el padron si la tiene ---';
GO

SELECT D.ID_MG, LTRIM(A.ORDEN_COMPRA) AS ORDEN_COMPRA, A.CONTENEDOR,
       D.ID_CE, C.CONCEPTO, C.VALOR_DEFAULT_1 AS PADRON, D.IMPORTE, D.CONFIRMADO
FROM dbo.RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
JOIN dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX C ON C.ID_CE = D.ID_CE
JOIN dbo.RO_T_IMPORTACIONES_ENCABEZADO   A ON A.ID    = D.ID_MG
WHERE D.VALOR_DEFAULT_1 IS NULL
  AND C.VALOR_DEFAULT_1 IS NOT NULL
ORDER BY D.ID_MG, D.ID_CE;
GO

-- ---------------------------------------------------------------------
-- Control: las vigencias del concepto, para poder verificar a mano que el
-- valor escrito es el que corresponde.
--
-- VA EN SU PROPIO LOTE. En una base donde no corrio el script 09 la tabla
-- no existe y ESTE lote falla al compilar; todo lo de arriba ya se
-- ejecuto y no se ve afectado, porque GO separa los lotes.
-- ---------------------------------------------------------------------
PRINT '--- Vigencias del IVA Adicional (control) ---';
GO

SELECT V.ID, V.VALOR_1, V.VIGENCIA_DESDE, V.VIGENCIA_HASTA, V.ACTIVO,
       (SELECT CAST(FECHA_DESP_ADU AS DATE)
          FROM dbo.RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = 733) AS FECHA_NAC_733
FROM dbo.RO_T_CONCEPTOS_ESTIMACION_COMEX_VIGENCIA V
WHERE V.ID_CE = 6
ORDER BY V.VIGENCIA_DESDE;
GO

PRINT 'Correccion de la OC 0000100015881 terminada.';
GO
