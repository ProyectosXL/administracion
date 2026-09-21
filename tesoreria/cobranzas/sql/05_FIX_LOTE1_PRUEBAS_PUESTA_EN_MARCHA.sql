/*
================================================================================
    CORRECCION PUNTUAL DEL LOTE #1  --  SACAR LAS PRUEBAS DE PUESTA EN MARCHA
================================================================================

    BASE DESTINO:  [XL-LAKERBIS].[FRANQUICIAS_LAKERS]
    Correr CONECTADO a FRANQUICIAS_LAKERS, DESPUES de 04 y de re-aplicar 02.

    QUE HACE
    --------
    El lote #1 (14/09 al 20/09) se genero antes de que existiera el piso por
    sucursal, asi que se llevo como rezagados los tres comprobantes de prueba
    de la 501:

        ID_DETALLE 1   09/09  FAC B0000600000001  *****SEÑAS       $0,45
        ID_DETALLE 2   10/09  FAC B0000600000002  *****DIF PRECIO  $0,45
        ID_DETALLE 3   10/09  FAC B0000600000003  *****DIF PRECIO  $0,45

    Este script los copia a RO_T_FRANQ_GA_LOTE_DETALLE_BAK, los borra del
    detalle y recalcula IMPORTE_TOTAL del encabezado.

        254 renglones  ->  251 renglones
        $4.389.426,00  ->  $4.389.424,65

    Los 251 que quedan son los del periodo y no se tocan: conservan su precio
    congelado y sus ID_DETALLE originales.

    POR QUE NO ALCANZABA CON BORRARLOS
    ----------------------------------
    Sin el piso de 04, la corrida siguiente los volvia a incorporar como
    rezagados, porque el NOT EXISTS ya no los encontraba en ningun lote.
    Correr ESTE script sin haber aplicado 04 + 02 no arregla nada.

    SEGURIDAD
    ---------
    Todo va en una transaccion. El script VERIFICA que 04 y 02 esten aplicados
    y que el borrado sea exactamente de 3 filas; si algo no cuadra hace
    ROLLBACK y no toca nada. Es seguro correrlo dos veces: la segunda vez no
    encuentra nada para borrar y avisa.
================================================================================
*/

SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

DECLARE @IdLote     INT = 1;
DECLARE @Usuario    VARCHAR(50) = 'sistemas';
DECLARE @Motivo     VARCHAR(200) = 'Pruebas de puesta en marcha de la suc. 501 (comprobantes 1 a 3, 09 y 10/09). Fuera de liquidacion por el piso de RO_T_FRANQ_GA_SUCURSAL_INICIO.';
DECLARE @Borrar     INT;
DECLARE @TotalAntes DECIMAL(22,7);
DECLARE @TotalDesp  DECIMAL(22,7);

/* ---------------------------------------------------------------------------
   0. Precondiciones. Mejor abortar que dejar el lote a medio corregir.
--------------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'RO_T_FRANQ_GA_SUCURSAL_INICIO')
BEGIN
    RAISERROR('Falta RO_T_FRANQ_GA_SUCURSAL_INICIO. Corre antes 04_RO_T_FRANQ_GA_SUCURSAL_INICIO.sql.', 16, 1);
    RETURN;
END

IF NOT EXISTS (SELECT 1 FROM RO_T_FRANQ_GA_SUCURSAL_INICIO WHERE NRO_SUCURS = 501)
BEGIN
    RAISERROR('La sucursal 501 no tiene fecha de alta. Corre 04 completo antes que este script.', 16, 1);
    RETURN;
END

/*  Si RO_SP_FRANQ_GA_GENERAR_LOTE todavia no tiene el piso, la proxima corrida
    vuelve a meter los comprobantes y esta limpieza no sirve de nada.        */
IF NOT EXISTS (
        SELECT 1
        FROM   sys.sql_modules m
        JOIN   sys.procedures p ON p.object_id = m.object_id
        WHERE  p.name = 'RO_SP_FRANQ_GA_GENERAR_LOTE'
          AND  m.definition LIKE '%RO_T_FRANQ_GA_SUCURSAL_INICIO%')
BEGIN
    RAISERROR('RO_SP_FRANQ_GA_GENERAR_LOTE todavia no aplica el piso por sucursal. Re-aplica 02_RO_SP_FRANQ_GA.sql antes de limpiar, o los comprobantes vuelven a entrar la proxima corrida.', 16, 1);
    RETURN;
END

IF NOT EXISTS (SELECT 1 FROM RO_T_FRANQ_GA_LOTE WHERE ID_LOTE = @IdLote)
BEGIN
    RAISERROR('No existe el lote #%d.', 16, 1, @IdLote);
    RETURN;
END

/* ---------------------------------------------------------------------------
   1. Que se va a borrar: los renglones del lote anteriores al piso de su
      propia sucursal. No se listan IDs a mano -- se deduce de la regla nueva,
      asi que si mañana hay otro caso igual este script tambien lo cubre.
--------------------------------------------------------------------------- */
SELECT  @Borrar = COUNT(*)
FROM        RO_T_FRANQ_GA_LOTE_DETALLE D
INNER JOIN  RO_T_FRANQ_GA_SUCURSAL_INICIO I ON I.NRO_SUCURS = D.NRO_SUCURS
WHERE       D.ID_LOTE = @IdLote
  AND       D.FECHA_EMIS < CAST(I.FECHA_INICIO AS DATETIME);

SELECT @TotalAntes = IMPORTE_TOTAL FROM RO_T_FRANQ_GA_LOTE WHERE ID_LOTE = @IdLote;

PRINT 'Renglones a sacar del lote #' + CAST(@IdLote AS VARCHAR(10)) + ': ' + CAST(@Borrar AS VARCHAR(10));
PRINT 'Importe del lote antes: ' + CAST(@TotalAntes AS VARCHAR(40));

IF @Borrar = 0
BEGIN
    PRINT 'No hay nada anterior al piso. El lote ya esta correcto, no se toca nada.';
    RETURN;
END

IF @Borrar <> 3
BEGIN
    RAISERROR('Se esperaban 3 renglones para sacar y se encontraron %d. Abortado: revisar a mano antes de seguir.', 16, 1, @Borrar);
    RETURN;
END

/* ---------------------------------------------------------------------------
   2. Backup + borrado + recalculo, todo atomico.
--------------------------------------------------------------------------- */
BEGIN TRY
    BEGIN TRANSACTION;

    INSERT INTO RO_T_FRANQ_GA_LOTE_DETALLE_BAK
        (USUARIO_BAK, MOTIVO_BAK, ID_DETALLE, ID_LOTE, NRO_SUCURS, DESC_SUCURSAL,
         FECHA_EMIS, T_COMP, N_COMP, COD_ARTICU, CANTIDAD, PRECIO_UNITARIO,
         IMPORTE, ES_REZAGADO, SIN_PRECIO_LISTA)
    SELECT  @Usuario, @Motivo, D.ID_DETALLE, D.ID_LOTE, D.NRO_SUCURS, D.DESC_SUCURSAL,
            D.FECHA_EMIS, D.T_COMP, D.N_COMP, D.COD_ARTICU, D.CANTIDAD, D.PRECIO_UNITARIO,
            D.IMPORTE, D.ES_REZAGADO, D.SIN_PRECIO_LISTA
    FROM        RO_T_FRANQ_GA_LOTE_DETALLE D
    INNER JOIN  RO_T_FRANQ_GA_SUCURSAL_INICIO I ON I.NRO_SUCURS = D.NRO_SUCURS
    WHERE       D.ID_LOTE = @IdLote
      AND       D.FECHA_EMIS < CAST(I.FECHA_INICIO AS DATETIME);

    IF @@ROWCOUNT <> @Borrar
    BEGIN
        RAISERROR('El backup no copio la cantidad esperada de filas.', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END

    DELETE      D
    FROM        RO_T_FRANQ_GA_LOTE_DETALLE D
    INNER JOIN  RO_T_FRANQ_GA_SUCURSAL_INICIO I ON I.NRO_SUCURS = D.NRO_SUCURS
    WHERE       D.ID_LOTE = @IdLote
      AND       D.FECHA_EMIS < CAST(I.FECHA_INICIO AS DATETIME);

    IF @@ROWCOUNT <> @Borrar
    BEGIN
        RAISERROR('El borrado no afecto la cantidad esperada de filas.', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END

    UPDATE  L
    SET     L.IMPORTE_TOTAL = ISNULL(T.TOTAL, 0),
            L.OBSERVACIONES = LEFT(ISNULL(L.OBSERVACIONES + ' | ', '')
                                   + 'Corregido: se sacaron ' + CAST(@Borrar AS VARCHAR(10))
                                   + ' renglones anteriores al alta de la sucursal (ver RO_T_FRANQ_GA_LOTE_DETALLE_BAK).', 500)
    FROM    RO_T_FRANQ_GA_LOTE L
    OUTER APPLY (SELECT SUM(D.IMPORTE) AS TOTAL
                 FROM   RO_T_FRANQ_GA_LOTE_DETALLE D
                 WHERE  D.ID_LOTE = L.ID_LOTE) T
    WHERE   L.ID_LOTE = @IdLote;

    COMMIT TRANSACTION;

    SELECT @TotalDesp = IMPORTE_TOTAL FROM RO_T_FRANQ_GA_LOTE WHERE ID_LOTE = @IdLote;
    PRINT 'Importe del lote despues: ' + CAST(@TotalDesp AS VARCHAR(40));
    PRINT 'Listo. Los renglones sacados quedaron en RO_T_FRANQ_GA_LOTE_DETALLE_BAK.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;
    THROW;
END CATCH
GO


/* ---------------------------------------------------------------------------
   VERIFICACION
--------------------------------------------------------------------------- */

/* 1. El lote: esperado 251 renglones, 0 rezagados, $4.389.424,65 y encabezado
      igual a la suma del detalle. */
SELECT  L.ID_LOTE, L.PERIODO_DESDE, L.PERIODO_HASTA, L.ESTADO,
        L.IMPORTE_TOTAL                                     AS ENCABEZADO,
        (SELECT COUNT(*) FROM RO_T_FRANQ_GA_LOTE_DETALLE D
          WHERE D.ID_LOTE = L.ID_LOTE)                      AS RENGLONES,
        (SELECT COUNT(*) FROM RO_T_FRANQ_GA_LOTE_DETALLE D
          WHERE D.ID_LOTE = L.ID_LOTE AND D.ES_REZAGADO = 1) AS REZAGADOS,
        (SELECT SUM(D.IMPORTE) FROM RO_T_FRANQ_GA_LOTE_DETALLE D
          WHERE D.ID_LOTE = L.ID_LOTE)                      AS SUMA_DETALLE,
        L.IMPORTE_TOTAL - (SELECT SUM(D.IMPORTE) FROM RO_T_FRANQ_GA_LOTE_DETALLE D
                            WHERE D.ID_LOTE = L.ID_LOTE)    AS DIFERENCIA
FROM    RO_T_FRANQ_GA_LOTE L
WHERE   L.ID_LOTE = 1;

/* 2. Lo que se saco, en el backup. */
SELECT  ID_BAK, FECHA_BAK, USUARIO_BAK, ID_LOTE, NRO_SUCURS,
        FECHA_EMIS, T_COMP, N_COMP, COD_ARTICU, IMPORTE, MOTIVO_BAK
FROM    RO_T_FRANQ_GA_LOTE_DETALLE_BAK
ORDER BY ID_BAK;

/* 3. La prueba de fuego: volver a correr el SP no debe reincorporarlos.
      Esperado -> YA_EXISTIA = 1, y el lote sigue en 251 renglones. */
EXEC RO_SP_FRANQ_GA_GENERAR_LOTE;

SELECT  (SELECT COUNT(*) FROM RO_T_FRANQ_GA_LOTE)           AS LOTES,
        (SELECT COUNT(*) FROM RO_T_FRANQ_GA_LOTE_DETALLE)   AS RENGLONES_TOTALES;
GO
