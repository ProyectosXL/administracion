<?php

/*
    Único punto de acceso a SUCURSALES_LAKERS del módulo Alquileres.

    Dos modos, según se pase o no un período:

      - Histórico (sin período): el catálogo completo de RO_V_SUCURSALES_ALQUILERES,
        o sea activas + cerradas con historial. Es lo que usan reportes, exports y
        selectores, que miran rangos de fechas o no miran ninguna.

      - Carga (con período): además filtra por ese período. Una sucursal entra si
        está habilitada para carga (activa hoy, o excepción que la fuerza), o si ya
        tiene datos en ESE mes, o si tiene un contrato vigente en ESE mes.

    La distinción no es cosmética: la lista que devuelve esta función es la que
    cargaAlquileres.php usa para armar el INSERT de un período nuevo, así que sin el
    filtro por período cada mes futuro crearía filas en cero para toda sucursal cerrada.

    Los dos formatos de período conviven en el módulo y no son intercambiables:
      $periodo      'M-YYYY'  sin cero a la izquierda (RO_T_DETALLE_ALQUILERES.PERIODO)
      $fechaPeriodo 'YYYY-MM'                          (vigencia de contratos)
*/
function getSucursalesAlquileres(?string $nroSucursal = null, ?string $periodo = null, ?string $fechaPeriodo = null): array
{
    require_once $_SERVER['DOCUMENT_ROOT'] . '/administracion/class/conexion.php';

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $esUy = isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy';

    $cid = new Conexion();
    $conexion = $esUy ? $cid->conectar('uy') : $cid->conectar('central');

    if ($conexion === false) {
        error_log('getSucursalesAlquileres: no se pudo conectar - ' . print_r(sqlsrv_errors(), true));
        throw new Exception('No se pudieron obtener las sucursales');
    }

    $params = array();

    if ($esUy) {
        // El exterior no tiene vista propia ni distingue cerradas: se mantiene como estaba.
        $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL, NRO_SUCURSAL as ID, DESC_SUCURSAL as SUCURSAL,
                       HABILITADO, CAST(0 AS BIT) as TIENE_DATOS, 0 as ORDEN,
                       CAST(1 AS BIT) as HABILITADA_CARGA
                FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WITH (NOLOCK)
                WHERE CANAL = 'EXTERIOR'";

        if ($nroSucursal !== null) {
            $sql .= " AND NRO_SUCURSAL = ?";
            $params[] = $nroSucursal;
        }

        $sql .= " ORDER BY DESC_SUCURSAL";
    } else {
        // La vista no filtra las ocultas (para que Parámetros pueda listarlas): el
        // filtro de visibilidad se aplica acá.
        $sql = "SELECT V.NRO_SUCURSAL, V.DESC_SUCURSAL, V.ID, V.SUCURSAL, V.HABILITADO,
                       V.TIENE_DATOS, V.ORDEN, V.HABILITADA_CARGA
                FROM RO_V_SUCURSALES_ALQUILERES V
                WHERE V.VISIBLE_FINAL = 1";

        if ($nroSucursal !== null) {
            $sql .= " AND V.NRO_SUCURSAL = ?";
            $params[] = $nroSucursal;
        }

        if ($periodo !== null) {
            /*
                Una sucursal entra en el período si:
                  a) ya tiene datos cargados en ESE mes — siempre, sin importar las
                     excepciones, porque hay que poder consultarlos y editarlos; o
                  b) no tiene veto explícito de carga (CARGA_EXPLICITA = 0) y además
                     está habilitada para carga o tiene contrato vigente en ESE mes.

                El veto de (b) es duro a propósito: si alguien marcó CARGA = 0, un
                contrato vigente no debe resucitarla en un período nuevo.
            */
            $sql .= " AND (EXISTS (
                                SELECT 1
                                FROM RO_T_DETALLE_ALQUILERES D WITH (NOLOCK)
                                WHERE LTRIM(RTRIM(CONVERT(VARCHAR(20), D.NRO_SUCURS))) COLLATE Modern_Spanish_CI_AI
                                    = LTRIM(RTRIM(CONVERT(VARCHAR(20), V.NRO_SUCURSAL))) COLLATE Modern_Spanish_CI_AI
                                  AND LTRIM(RTRIM(D.PERIODO)) = ?
                           )
                           OR (ISNULL(V.CARGA_EXPLICITA, 1) = 1
                               AND (V.HABILITADA_CARGA = 1";
            $params[] = $periodo;

            if ($fechaPeriodo !== null) {
                $sql .= " OR EXISTS (
                                SELECT 1
                                FROM RO_T_CONTRATOS_ALQUILERES C WITH (NOLOCK)
                                WHERE LTRIM(RTRIM(CONVERT(VARCHAR(20), C.NRO_SUCURS))) COLLATE Modern_Spanish_CI_AI
                                    = LTRIM(RTRIM(CONVERT(VARCHAR(20), V.NRO_SUCURSAL))) COLLATE Modern_Spanish_CI_AI
                                  AND CONVERT(VARCHAR(7), C.VIG_DESDE, 120) <= ?
                                  AND CONVERT(VARCHAR(7), C.VIG_HASTA, 120) >= ?
                          )";
                $params[] = $fechaPeriodo;
                $params[] = $fechaPeriodo;
            }

            $sql .= ")))";
        }

        $sql .= " ORDER BY V.ORDEN, V.DESC_SUCURSAL";
    }

    $stmt = sqlsrv_prepare($conexion, $sql, $params);

    if ($stmt === false || !sqlsrv_execute($stmt)) {
        // Nunca devolver un array vacío acá: sin sucursales la pantalla queda en blanco
        // y el error real (vista inexistente, permisos, linked server caído) se pierde.
        error_log('getSucursalesAlquileres: ' . print_r(sqlsrv_errors(), true));
        throw new Exception('No se pudieron obtener las sucursales');
    }

    // Indexado por NRO_SUCURSAL: aunque la vista ya garantiza una fila por sucursal,
    // un duplicado no debe poder volver a desalinear la tabla de cargaAlquileres.
    $rows = array();

    while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $clave = trim((string) $v['NRO_SUCURSAL']);

        if (!isset($rows[$clave])) {
            $rows[$clave] = $v;
        }
    }

    return array_values($rows);
}

/**
 * Conexión que usa la administración de parámetros. Las excepciones sólo aplican al
 * entorno central: en 'uy' no existen ni la vista ni la tabla.
 */
function conexionParametrosSucursales()
{
    require_once $_SERVER['DOCUMENT_ROOT'] . '/administracion/class/conexion.php';

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') {
        return null;
    }

    $cid = new Conexion();

    return $cid->conectar('central');
}

/**
 * Listado completo para la pantalla de Parámetros.
 * A diferencia de getSucursalesAlquileres(), NO filtra por VISIBLE_FINAL: incluye las
 * ocultas, que si no quedarían fuera de la única pantalla desde donde se las revierte.
 */
function getSucursalesParametros(): array
{
    $conexion = conexionParametrosSucursales();

    if ($conexion === false || $conexion === null) {
        throw new Exception('No se pudieron obtener las sucursales');
    }

    $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL, HABILITADO, TIENE_DATOS, ORDEN,
                   VISIBLE_FINAL, HABILITADA_CARGA, VISIBLE_EXPLICITA, CARGA_EXPLICITA,
                   OBSERVACION, USUARIO, FECHA_MODIF
            FROM RO_V_SUCURSALES_ALQUILERES
            ORDER BY ORDEN, NRO_SUCURSAL";

    $stmt = sqlsrv_query($conexion, $sql);

    if ($stmt === false) {
        error_log('getSucursalesParametros: ' . print_r(sqlsrv_errors(), true));
        throw new Exception('No se pudieron obtener las sucursales');
    }

    $rows = array();

    while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($v['FECHA_MODIF'] instanceof DateTime) {
            $v['FECHA_MODIF'] = $v['FECHA_MODIF']->format('d/m/Y H:i');
        }

        $rows[] = $v;
    }

    return $rows;
}

/**
 * Alta/modificación de la excepción de una sucursal.
 * Si los dos flags quedan en automático (null) y no hay observación, se borra la fila:
 * la tabla sólo debe contener excepciones reales.
 *
 * @param int|null $visible 1 = mostrar siempre, 0 = ocultar siempre, null = automático
 * @param int|null $carga   1 = cargar siempre, 0 = nunca cargar,     null = automático
 */
function guardarSucursalExcepcion(string $nroSucursal, ?int $visible, ?int $carga, ?string $observacion, ?string $usuario): bool
{
    $conexion = conexionParametrosSucursales();

    if ($conexion === false || $conexion === null) {
        throw new Exception('No se pudo guardar el parámetro');
    }

    $observacion = ($observacion !== null) ? trim($observacion) : null;

    if ($visible === null && $carga === null && ($observacion === null || $observacion === '')) {
        return eliminarSucursalExcepcion($nroSucursal);
    }

    $sql = "IF EXISTS (SELECT 1 FROM RO_T_SUCURSALES_ALQUILERES_EXC WHERE NRO_SUCURS = ?)
                UPDATE RO_T_SUCURSALES_ALQUILERES_EXC
                   SET VISIBLE = ?, CARGA = ?, OBSERVACION = ?, USUARIO = ?, FECHA_MODIF = GETDATE()
                 WHERE NRO_SUCURS = ?
            ELSE
                INSERT INTO RO_T_SUCURSALES_ALQUILERES_EXC
                       (NRO_SUCURS, VISIBLE, CARGA, OBSERVACION, USUARIO, FECHA_MODIF)
                VALUES (?, ?, ?, ?, ?, GETDATE())";

    /*
        Los flags son BIT y pueden ir en NULL (= automático). Se tipan explícitamente:
        dejar que el driver infiera el tipo de un NULL dentro de un batch IF/ELSE es
        justo el tipo de cosa que falla en producción y no en una prueba rápida.
    */
    $bit = function ($valor) {
        if ($valor === null) {
            return array(null, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT);
        }
        return array($valor, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_BIT);
    };

    $texto = function ($valor, $largo) {
        if ($valor === null || $valor === '') {
            return array(null, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR($largo));
        }
        return array(
            $valor,
            SQLSRV_PARAM_IN,
            SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR),
            SQLSRV_SQLTYPE_VARCHAR($largo)
        );
    };

    $params = array(
        $nroSucursal,
        $bit($visible), $bit($carga), $texto($observacion, 200), $texto($usuario, 50), $nroSucursal,
        $nroSucursal, $bit($visible), $bit($carga), $texto($observacion, 200), $texto($usuario, 50)
    );

    $stmt = sqlsrv_query($conexion, $sql, $params);

    if ($stmt === false) {
        error_log('guardarSucursalExcepcion: ' . print_r(sqlsrv_errors(), true));
        throw new Exception('No se pudo guardar el parámetro');
    }

    return true;
}

/**
 * Vuelve la sucursal al comportamiento automático borrando su fila de excepciones.
 */
function eliminarSucursalExcepcion(string $nroSucursal): bool
{
    $conexion = conexionParametrosSucursales();

    if ($conexion === false || $conexion === null) {
        throw new Exception('No se pudo eliminar el parámetro');
    }

    $stmt = sqlsrv_query(
        $conexion,
        "DELETE FROM RO_T_SUCURSALES_ALQUILERES_EXC WHERE NRO_SUCURS = ?",
        array($nroSucursal)
    );

    if ($stmt === false) {
        error_log('eliminarSucursalExcepcion: ' . print_r(sqlsrv_errors(), true));
        throw new Exception('No se pudo eliminar el parámetro');
    }

    return true;
}

class Sucursal
{
    private $cid;
    private $cid_central;
    private $cid_locales;
    private $conexion;
    private $cid_uy;

    function __construct()
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/administracion/class/conexion.php';
        $this->cid = new Conexion();

        $this->cid_central = $this->cid->conectar('central');
        $this->cid_locales =($this->cid->env == 'DEV') ? $this->cid->conectar('central') : $this->cid->conectar('locales');
        $this->cid_uy = $this->cid->conectar('uy');

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){

            $this->conexion = $this->cid->conectar('uy');
        }else{
            $this->conexion = $this->cid->conectar('central');

        }

    }

    /**
     * @param bool|null    $orderByName  true = alfabético; si no, por número de sucursal.
     * @param string|null  $periodo      'M-YYYY' — activa el modo carga (ver getSucursalesAlquileres).
     * @param string|null  $fechaPeriodo 'YYYY-MM' — habilita el criterio de contrato vigente.
     */
    public function traerLocales($orderByName = null, ?string $periodo = null, ?string $fechaPeriodo = null)
    {
        $rows = getSucursalesAlquileres(null, $periodo, $fechaPeriodo);

        if ($orderByName == true) {
            // La vista ya devuelve ORDEN, DESC_SUCURSAL (activas primero, alfabético).
            return $rows;
        }

        // Orden numérico puro: las cerradas van en su posición por número, no al final.
        // Se distinguen igual por el badge "Cerrada" en la cabecera.
        usort($rows, function ($a, $b) {
            return strnatcmp((string) ($a['ID'] ?? ''), (string) ($b['ID'] ?? ''));
        });

        return $rows;
    }

    /**
     * Obtiene información de una sucursal por su ID
     */
    public function obtenerSucursalPorId($idSucursal)
    {
        $rows = getSucursalesAlquileres((string) $idSucursal);

        if (empty($rows)) {
            throw new Exception('Sucursal no encontrada');
        }

        return $rows[0];
    }
}
