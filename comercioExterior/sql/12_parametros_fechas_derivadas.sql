-- =====================================================================
-- Comercio Exterior - todas las fechas derivadas salen de parametros
--
-- CORRER EN LAS DOS BASES: central (LAKER_SA) y uy (TASKY_SA).
-- Script idempotente.
--
-- CORRER DESPUES DE 11_fechas_fijadas_arribo_nacionalizacion.sql. El 11
-- marca las fechas que alguien fijo a mano; este cambia la regla con la que
-- se calculan las automaticas. Al reves, el cambio de regla pisaria
-- correcciones manuales que todavia no estaban marcadas.
--
-- ---------------------------------------------------------------------
-- QUE PROBLEMA RESUELVE
--
-- La misma fecha se calculaba distinto segun la pantalla:
--
--   js/cargaInicial.js                        arribo = embarque + 45
--                                             pago   = embarque + 5
--                                             nacionalizacion = arribo + 2
--   RO_T_IMPORTACIONES_PARAM_CRONOGRAMA       DIAS_ARR_DESP = 7, y ninguna
--   (cronogramaDespachos)                     regla de pago
--
-- Con los mismos datos, gestion de despachos decia arribo + 2 y el
-- cronograma dibujaba arribo + 7. Se ve en los datos: en central, de los 54
-- contenedores sin nacionalizar hay FECHA_DESP_ADU a 2, 3, 4, 5, 6 y 7 dias
-- del arribo.
--
-- A partir de acá hay UNA sola cadena, y vive entera en esta tabla:
--
--     arribo          = embarque        + DIAS_EMB_ARR   (45)
--     pago            = embarque        + DIAS_EMB_PAGO   (5)  <- nuevo
--     nacionalizacion = arribo          + DIAS_ARR_DESP   (5)  <- pasa de 7
--     recepcion       = nacionalizacion + DIAS_DESP_REC   (2)
--     distribucion    = recepcion       + DIAS_REC_DIST   (1)
--
-- "embarque" es FECHA_EMB -el ETD real- con fallback a FECHA_EST_EMB, que es
-- lo que ya hacian las dos pantallas.
--
-- La calcula CronogramaFechas::cadenaDeFechas(), en el servidor y una sola
-- vez. El navegador ya no tiene ningun numero de dias escrito.
--
-- ---------------------------------------------------------------------
-- ESTOS PARAMETROS TAMBIEN MUEVEN EL CASHFLOW DE ProyectosXL/finanzas
--
-- El tablero de cashflow lee del maestro RO_T_IMPORTACIONES_ENCABEZADO:
--
--     FECHA_EST_PAGO   pestana Proveedores Exterior
--     FECHA_DESP_ADU   pestana Crono Nacionalizacion
--
-- o sea las dos puntas de esta cadena. Y va a leer ADEMAS esta tabla para
-- proyectar contenedores que todavia no existen como fila -cuando hay una
-- fecha de embarque prevista pero nadie cargo el contenedor-, que es lo que
-- hoy no puede hacer.
--
-- CONSECUENCIA PRACTICA: cambiar un valor de esta tabla desde Parametros >
-- Cronograma NO es un ajuste cosmetico de Comercio Exterior. Mueve fechas en
-- las dos aplicaciones. Subir DIAS_ARR_DESP corre plata de un mes al
-- siguiente en el tablero de Finanzas.
--
-- ---------------------------------------------------------------------
-- QUE PASA CON LAS FECHAS YA GUARDADAS
--
-- NADA. Este script no toca RO_T_IMPORTACIONES_ENCABEZADO. Los parametros
-- nuevos rigen de acá en más: para los contenedores que se den de alta y
-- para los que se editen. Los contenedores que ya tienen FECHA_DESP_ADU
-- calculada con la regla vieja se migran aparte, con criterio propio:
-- 13_diagnostico_fecha_desp_adu.sql los clasifica (solo SELECT) y
-- 14_migrar_fecha_desp_adu.sql los mueve.
-- =====================================================================

SET NOCOUNT ON;
GO

IF OBJECT_ID('RO_T_IMPORTACIONES_PARAM_CRONOGRAMA', 'U') IS NULL
BEGIN
    RAISERROR('Falta RO_T_IMPORTACIONES_PARAM_CRONOGRAMA. Corré primero 04_cronograma_parametros_dias.sql en esta base.', 16, 1);
END
GO

-- ---------------------------------------------------------------------
-- 1) DIAS_EMB_PAGO: la regla de pago, que hasta ahora no estaba en ninguna
--    tabla.
--
-- Vivia como un 5 hardcodeado en dos lugares que se juraban iguales:
-- recalcularFechaEstimadaPago() en js/cargaInicial.js y la constante
-- Encabezado::DIAS_EMB_EST_PAGO, que existia solo para que
-- revertirFechaPagoAuto() pudiera devolver la fecha ya resuelta y llevaba
-- escrito "si se cambia uno hay que cambiar el otro". Ahora hay uno solo y
-- es esta fila.
--
-- SE SIEMBRA SIN PISAR: si alguien ya la ajusto desde el ABM -imposible hoy,
-- pero este script se va a correr mas de una vez- el MERGE no la toca.
-- ---------------------------------------------------------------------
MERGE RO_T_IMPORTACIONES_PARAM_CRONOGRAMA AS destino
USING (VALUES
    ('DIAS_EMB_PAGO', 5, 'Dias corridos entre embarque y fecha estimada de pago al proveedor')
) AS origen (CLAVE, VALOR, DESCRIPCION)
ON destino.CLAVE = origen.CLAVE
WHEN NOT MATCHED BY TARGET THEN
    INSERT (CLAVE, VALOR, DESCRIPCION)
    VALUES (origen.CLAVE, origen.VALOR, origen.DESCRIPCION);

PRINT 'DIAS_EMB_PAGO: ' + CASE WHEN @@ROWCOUNT > 0 THEN 'sembrado en 5.' ELSE 'ya existia, no se toca.' END;
GO

-- =====================================================================
-- 2) DIAS_ARR_DESP TIENE QUE QUEDAR EN 5, y este es el unico valor que el
--    script pisa.
--
-- POR QUE SE PISA, SI LA REGLA DE ESTA TABLA ES NO PISAR NADA AJUSTADO.
-- Porque el 7 nunca fue un ajuste: es el valor con el que el script 04
-- sembro la tabla, tomado de lo que estaba hardcodeado en cronograma.js.
-- Nunca lo eligio nadie mirando cuanto tarda de verdad un despacho. La regla
-- correcta -arribo + 5- si es una decision del negocio, y tiene que valer en
-- las dos bases.
--
-- Y SE NOTA EN LOS DATOS que en central ya lo habian corregido a mano:
--
--     central   DIAS_ARR_DESP = 5, FECHA_MOD 15/09/2026 12:54   (desde el ABM)
--     uy        DIAS_ARR_DESP = 7, FECHA_MOD NULL               (como lo dejo el 04)
--
-- O sea que este UPDATE es un no-op en central y corrige uy. Pisar "lo
-- ajustado" acá no destruye ninguna decision: la confirma en la base donde
-- falta.
--
-- COMO SE RESUELVE, EXPLICITAMENTE: un UPDATE con nombre y apellido sobre
-- una sola CLAVE, con el WHERE VALOR <> 5 que lo hace idempotente y que
-- ademas evita ensuciar FECHA_MOD en corridas repetidas. NO se hizo con un
-- MERGE ... WHEN MATCHED THEN UPDATE junto a la siembra del paso 1, que
-- habria dejado la excepcion escondida dentro de una sentencia que por todos
-- lados dice "esto no pisa nada".
--
-- USUARIO queda marcado para que en el ABM se vea que lo movio un script y
-- no una persona.
--
-- LA DESCRIPCION VA APARTE, en un UPDATE propio mas abajo. Estaba pegada al
-- del valor y eso era un bug: en central, donde DIAS_ARR_DESP ya estaba en 5,
-- el WHERE VALOR <> 5 hacia que no ejecutara y la descripcion quedaba con la
-- del script 04. Las dos bases terminaron mostrando textos distintos para el
-- mismo parametro. Cada cosa con su propia condicion de idempotencia.
-- =====================================================================
UPDATE RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
   SET VALOR     = 5,
       USUARIO   = 'script 12',
       FECHA_MOD = GETDATE()
 WHERE CLAVE = 'DIAS_ARR_DESP'
   AND VALOR <> 5;

PRINT 'DIAS_ARR_DESP: ' + CASE WHEN @@ROWCOUNT > 0
        THEN 'corregido a 5.'
        ELSE 'ya estaba en 5, no se toca.' END;
GO

/* LA DESCRIPCION VA EN SU PROPIA SENTENCIA, y no pegada al UPDATE del valor.
   Pegada era un bug, y se vio en la primera corrida real: el UPDATE de arriba
   lleva AND VALOR <> 5 para ser idempotente, asi que en central -donde el
   valor YA estaba en 5- no ejecutaba, y la descripcion se quedo con la del
   script 04. Resultado: las dos bases mostrando textos distintos para el
   mismo parametro en el ABM, que es exactamente la divergencia que esta
   entrega vino a eliminar.

   Separada, cada UPDATE tiene su propia condicion de idempotencia y ninguno
   depende de que el otro haya corrido. Correr el script de nuevo la arregla. */
UPDATE RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
   SET DESCRIPCION = 'Dias corridos entre arribo y nacionalizacion (despacho de aduana) estimada'
 WHERE CLAVE = 'DIAS_ARR_DESP'
   AND DESCRIPCION <> 'Dias corridos entre arribo y nacionalizacion (despacho de aduana) estimada';
GO

-- ---------------------------------------------------------------------
-- 3) Descripciones al dia.
--
-- Las que sembro el script 04 describian la cadena vieja y ahora mienten.
-- Son las que se leen en el ABM, asi que estaban afirmando algo que dejo de
-- ser cierto.
--
-- Los UPDATE no tocan VALOR, asi que no pisan nada ajustado.
--
-- ESTE PASO NO TOCA DIAS_ARR_DIST, y es a proposito. Lo tocaba: le ponia una
-- descripcion que decia "a revisar", de cuando todavia no estaba decidido que
-- hacer con ese parametro. Cuando se decidio retirarlo, el script 15 paso a
-- ser el dueno de esa fila -le pone 'SIN USO desde el script 15: ...'- y los
-- dos scripts quedaron peleandose por el mismo texto.
--
-- SE VIO EN LA PRIMERA CORRIDA REAL: correr el 12 despues del 15 -algo
-- perfectamente razonable, los dos son idempotentes- deshacia la marca del
-- 15. En central quedo DIAS_ARR_DIST con el texto "a revisar" en vez de
-- "SIN USO", y como paramCronograma.js decide por ese prefijo si la fila va
-- deshabilitada, el ABM volvia a ofrecerla editable mientras
-- actualizarParamCronograma.php la rechazaba por no estar en la whitelist.
--
-- Un solo script escribe cada fila. Si el 12 corre solo, en una instalacion
-- nueva, DIAS_ARR_DIST se queda con la descripcion del 04 hasta que corra el
-- 15, que es el que sabe que paso con ese parametro.
-- ---------------------------------------------------------------------
UPDATE RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
   SET DESCRIPCION = 'Dias corridos entre embarque (ETD real, o estimado) y arribo'
 WHERE CLAVE = 'DIAS_EMB_ARR'
   AND DESCRIPCION <> 'Dias corridos entre embarque (ETD real, o estimado) y arribo';
GO

UPDATE RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
   SET DESCRIPCION = 'Dias corridos entre nacionalizacion y recepcion estimada'
 WHERE CLAVE = 'DIAS_DESP_REC'
   AND DESCRIPCION <> 'Dias corridos entre nacionalizacion y recepcion estimada';
GO

UPDATE RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
   SET DESCRIPCION = 'Dias corridos entre la recepcion y la distribucion'
 WHERE CLAVE = 'DIAS_REC_DIST'
   AND DESCRIPCION <> 'Dias corridos entre la recepcion y la distribucion';
GO

-- ---------------------------------------------------------------------
-- 4) Control: la cadena completa, como la va a leer el servidor.
-- ---------------------------------------------------------------------
PRINT '--- Parametros de fechas derivadas ---';
GO

SELECT
    CLAVE,
    VALOR,
    DESCRIPCION,
    USUARIO,
    FECHA_MOD
FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
ORDER BY CASE CLAVE
            WHEN 'DIAS_EMB_ARR'  THEN 1
            WHEN 'DIAS_EMB_PAGO' THEN 2
            WHEN 'DIAS_ARR_DESP' THEN 3
            WHEN 'DIAS_DESP_REC' THEN 4
            WHEN 'DIAS_REC_DIST' THEN 5
            ELSE 9
         END, CLAVE;
GO

-- Si esto lista algo, el servidor no puede calcular la cadena: las claves
-- ya no tienen default hardcodeado en el PHP a proposito -un default que
-- calla el problema fue lo que dejo que el 45/7/2 del JS y el 45/7 de la
-- tabla convivieran meses sin que nadie lo notara-.
PRINT '--- Claves que faltan (si no lista nada, esta completo) ---';
GO

SELECT F.CLAVE AS FALTA
FROM (VALUES ('DIAS_EMB_ARR'),('DIAS_EMB_PAGO'),('DIAS_ARR_DESP'),
             ('DIAS_DESP_REC'),('DIAS_REC_DIST')) AS F(CLAVE)
WHERE NOT EXISTS (
    SELECT 1 FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA P WHERE P.CLAVE = F.CLAVE
);
GO

PRINT 'Comercio Exterior: parametros de fechas derivadas, listos.';
GO
