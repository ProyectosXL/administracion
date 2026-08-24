/*
    RO_V_SUCURSALES_ALQUILERES

    Catálogo de sucursales del módulo Alquileres, con las banderas ya resueltas.

    El universo de la vista son las sucursales que: están operativas hoy
    (CANAL = 'PROPIOS' AND HABILITADO = 1), o tienen historial de alquileres, o tienen
    una fila en RO_T_SUCURSALES_ALQUILERES_EXC.

    OJO: la vista NO filtra las ocultas — devuelve la bandera VISIBLE_FINAL y el filtro
    lo aplica getSucursalesAlquileres() en Class/Sucursal.php. Es a propósito: la
    pantalla de Parámetros necesita poder listar una sucursal marcada VISIBLE = 0 para
    que alguien pueda revertirla. Si la vista la escondiera, quedaría inalcanzable.

    Columnas:
      TIENE_DATOS        1 si existe historial de alquileres asociado.
      ORDEN              0 = activa, 1 = cerrada.
      VISIBLE_FINAL      1 si corresponde mostrarla en el módulo (excepción o regla automática).
      HABILITADA_CARGA   1 si corresponde crear filas nuevas para ella al abrir un período.
      VISIBLE_EXPLICITA  el flag crudo de la tabla de excepciones (NULL = automático).
      CARGA_EXPLICITA    idem. NULL = automático; 0 = veto duro, ni un contrato vigente
                         la mete en un período nuevo.

    El filtro por período (datos ya cargados en ESE mes, o contrato vigente en ESE mes)
    se aplica parametrizado desde el helper, porque una vista no recibe parámetros.

    IMPORTANTE: la vista devuelve UNA SOLA FILA POR SUCURSAL. SUCURSALES_LAKERS puede
    tener más de una fila por NRO_SUCURSAL (distinto CANAL o HABILITADO), y un DISTINCT
    no las colapsa porque las columnas calculadas difieren entre ellas. Un duplicado acá
    rompe el render de cargaAlquileres.php: la cabecera recorre la lista de sucursales y
    el cuerpo un array indexado por NRO_SUCURSAL, así que las claves repetidas colapsan y
    sobran columnas al final. De ahí el ROW_NUMBER.

    Vive en la misma base que las tablas RO_T_* (conexión 'central'); SUCURSALES_LAKERS
    se referencia vía el linked server, igual que el resto del módulo.

    Requiere: RO_T_SUCURSALES_ALQUILERES_EXC.sql (correrlo ANTES que este script)
*/
CREATE OR ALTER VIEW RO_V_SUCURSALES_ALQUILERES
AS
WITH SUC AS (
    /*
        Una fila por sucursal. Ante filas repetidas gana la operativa
        (PROPIOS + habilitada); si ninguna lo es, la que esté habilitada.
    */
    SELECT
        S.NRO_SUCURSAL,
        S.DESC_SUCURSAL COLLATE Modern_Spanish_CI_AI AS DESC_SUCURSAL,
        S.CANAL         COLLATE Modern_Spanish_CI_AI AS CANAL,
        S.HABILITADO,
        LTRIM(RTRIM(CONVERT(VARCHAR(20), S.NRO_SUCURSAL))) COLLATE Modern_Spanish_CI_AI AS CLAVE,
        ROW_NUMBER() OVER (
            PARTITION BY S.NRO_SUCURSAL
            ORDER BY
                CASE WHEN S.CANAL COLLATE Modern_Spanish_CI_AI = 'PROPIOS'
                       AND S.HABILITADO = 1
                     THEN 0 ELSE 1 END,
                S.HABILITADO DESC
        ) AS RN
    FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS S WITH (NOLOCK)
),
HIST AS (
    /*
        Sucursales con historial de alquileres. Se resuelve una sola vez con UNION
        (que además deduplica) en lugar de repetir EXISTS por cada criterio: evita
        múltiples viajes al linked server.
    */
    SELECT LTRIM(RTRIM(CONVERT(VARCHAR(20), NRO_SUCURS))) COLLATE Modern_Spanish_CI_AI AS CLAVE
    FROM RO_T_CONTRATOS_ALQUILERES WITH (NOLOCK)
    UNION
    SELECT LTRIM(RTRIM(CONVERT(VARCHAR(20), NRO_SUCURS))) COLLATE Modern_Spanish_CI_AI
    FROM RO_T_DETALLE_ALQUILERES WITH (NOLOCK)
    UNION
    SELECT LTRIM(RTRIM(CONVERT(VARCHAR(20), NRO_SUCURS))) COLLATE Modern_Spanish_CI_AI
    FROM RO_T_PORC_CONCEPTOS_ALQUILERES WITH (NOLOCK)
    UNION
    SELECT LTRIM(RTRIM(CONVERT(VARCHAR(20), NRO_SUCURS))) COLLATE Modern_Spanish_CI_AI
    FROM RO_T_RENTABILIDAD_BRUTA WITH (NOLOCK)
)
SELECT
    S.NRO_SUCURSAL,
    S.DESC_SUCURSAL,
    S.NRO_SUCURSAL AS ID,
    S.DESC_SUCURSAL AS SUCURSAL,
    S.HABILITADO,
    CAST(CASE WHEN H.CLAVE IS NOT NULL THEN 1 ELSE 0 END AS BIT) AS TIENE_DATOS,
    CASE WHEN S.CANAL = 'PROPIOS' AND S.HABILITADO = 1 THEN 0 ELSE 1 END AS ORDEN,
    CAST(
        ISNULL(
            E.VISIBLE,
            CASE WHEN (S.CANAL = 'PROPIOS' AND S.HABILITADO = 1) OR H.CLAVE IS NOT NULL
                 THEN 1 ELSE 0 END
        ) AS BIT
    ) AS VISIBLE_FINAL,
    CAST(
        ISNULL(
            E.CARGA,
            CASE WHEN S.CANAL = 'PROPIOS' AND S.HABILITADO = 1 THEN 1 ELSE 0 END
        ) AS BIT
    ) AS HABILITADA_CARGA,
    E.VISIBLE     AS VISIBLE_EXPLICITA,
    E.CARGA       AS CARGA_EXPLICITA,
    E.OBSERVACION,
    E.USUARIO,
    E.FECHA_MODIF
FROM SUC S
LEFT JOIN HIST H
       ON H.CLAVE = S.CLAVE
LEFT JOIN RO_T_SUCURSALES_ALQUILERES_EXC E WITH (NOLOCK)
       ON LTRIM(RTRIM(CONVERT(VARCHAR(20), E.NRO_SUCURS))) COLLATE Modern_Spanish_CI_AI = S.CLAVE
WHERE S.RN = 1
  AND (
        (S.CANAL = 'PROPIOS' AND S.HABILITADO = 1)
        OR H.CLAVE IS NOT NULL
        OR E.NRO_SUCURS IS NOT NULL
      );
GO
