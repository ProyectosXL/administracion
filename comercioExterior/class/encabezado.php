<?php

class Encabezado
{
    private $cid_central;

    function __construct()
    {

        require_once __DIR__ . '/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($db);

    }

    public function insertarEncabezado($datosDeCabezera)
    {

        $codProv = substr($datosDeCabezera['cod_proveedor'], 0, 6);

        // Sección 1 - Datos Iniciales (campos obligatorios)
        $sql = "INSERT INTO RO_T_IMPORTACIONES_ENCABEZADO(
            FECHA_MOV, COD_PROVEE, PROVEEDOR, CONTENEDOR, MATERIAL, ORIGEN, 
            VALOR_FOB_DOLAR, FECHA_EST_EMB, ORDEN_COMPRA, OCM, DESPACHANTE";

        $values = "VALUES (
            GETDATE(),
            '" . $codProv . "',
            '" . $datosDeCabezera['proveedor'] . "',
            '" . $datosDeCabezera['contenedor'] . "',
            '" . $datosDeCabezera['material'] . "',
            '" . $datosDeCabezera['origen'] . "',
            '" . $datosDeCabezera['valorFobDolar'] . "',
            '" . $datosDeCabezera['fechaEstEmb'] . "',
            '" . $datosDeCabezera['ordenCompra'] . "',
            '" . $datosDeCabezera['ocm'] . "',
            '" . (isset($datosDeCabezera['despachante']) ? $datosDeCabezera['despachante'] : 'Laffitte') . "'";

        // Campos calculados automáticamente que se guardan desde la Sección 1
        if (isset($datosDeCabezera['fechaArr']) && !empty($datosDeCabezera['fechaArr'])) {
            $sql .= ", FECHA_ARR";
            $values .= ", '" . $datosDeCabezera['fechaArr'] . "'";
        }

        if (isset($datosDeCabezera['fechaPago']) && !empty($datosDeCabezera['fechaPago'])) {
            $sql .= ", FECHA_PAGO";
            $values .= ", '" . $datosDeCabezera['fechaPago'] . "'";
        }

        if (isset($datosDeCabezera['fechaDespAdu']) && !empty($datosDeCabezera['fechaDespAdu'])) {
            $sql .= ", FECHA_DESP_ADU";
            $values .= ", '" . $datosDeCabezera['fechaDespAdu'] . "'";
        }

        // Campos opcionales de secciones 2 y 3 (solo si vienen en el array)
        // FECHA_EMB es el ETD (Estimated Time of Departure - fecha de salida real)
        if (isset($datosDeCabezera['fechaEmb']) && !empty($datosDeCabezera['fechaEmb'])) {
            $sql .= ", FECHA_EMB";
            $values .= ", '" . $datosDeCabezera['fechaEmb'] . "'";
        }

        if (isset($datosDeCabezera['numeroBl']) && !empty($datosDeCabezera['numeroBl'])) {
            $sql .= ", NUMERO_BL";
            $values .= ", '" . $datosDeCabezera['numeroBl'] . "'";
        }

        if (isset($datosDeCabezera['facturaProveedor']) && !empty($datosDeCabezera['facturaProveedor'])) {
            $sql .= ", FACTURA";
            $values .= ", '" . $datosDeCabezera['facturaProveedor'] . "'";
        }

        if (isset($datosDeCabezera['tipoCambio']) && !empty($datosDeCabezera['tipoCambio'])) {
            $sql .= ", TIPO_CAMBIO";
            $values .= ", '" . $datosDeCabezera['tipoCambio'] . "'";
        }

        if (isset($datosDeCabezera['valorFobPeso']) && !empty($datosDeCabezera['valorFobPeso'])) {
            $sql .= ", VALOR_FOB_PESO";
            $values .= ", '" . (float) ($datosDeCabezera['valorFobPeso'] + 0.15) . "'";
        }

        if (isset($datosDeCabezera['formaPago']) && !empty($datosDeCabezera['formaPago'])) {
            $sql .= ", FORMA_PAGO";
            $values .= ", '" . $datosDeCabezera['formaPago'] . "'";
        }

        if (isset($datosDeCabezera['despacho']) && !empty($datosDeCabezera['despacho'])) {
            $sql .= ", DESPACHO";
            $values .= ", '" . $datosDeCabezera['despacho'] . "'";
        }

        if (isset($datosDeCabezera['fechaEstPago']) && !empty($datosDeCabezera['fechaEstPago'])) {
            $sql .= ", FECHA_EST_PAGO";
            $values .= ", '" . $datosDeCabezera['fechaEstPago'] . "'";
        }

        if (isset($datosDeCabezera['puertoOrigen']) && !empty($datosDeCabezera['puertoOrigen'])) {
            $sql .= ", PUERTO_ORIGEN";
            $values .= ", '" . $datosDeCabezera['puertoOrigen'] . "'";
        }

        if (isset($datosDeCabezera['terminal']) && !empty($datosDeCabezera['terminal'])) {
            $sql .= ", TERMINAL";
            $values .= ", '" . $datosDeCabezera['terminal'] . "'";
        }

        // ETA Confirmada (bit - 0 o 1)
        if (isset($datosDeCabezera['etaConfirmada'])) {
            $sql .= ", ETA_CONFIRMADA";
            $values .= ", " . $datosDeCabezera['etaConfirmada'];
        }

        $sql .= ") " . $values . ");";

        // Log del SQL para debugging
        error_log("SQL a ejecutar: " . $sql);

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en INSERT: " . print_r($errors, true));
                throw new Exception("Error al insertar: " . $errors[0]['message']);
            }

            $queryIdentity = "SELECT @@IDENTITY as ID";
            $stmtIdentity = sqlsrv_query($this->cid_central, $queryIdentity);

            if ($stmtIdentity === false) {
                error_log("Error al obtener ID: " . print_r(sqlsrv_errors(), true));
                return null;
            }

            $row = sqlsrv_fetch_array($stmtIdentity, SQLSRV_FETCH_ASSOC);
            $id = $row ? $row['ID'] : null;

            error_log("ID insertado: " . $id);
            return $id;

        } catch (Exception $e) {
            error_log('Excepción en insertarEncabezado: ' . $e->getMessage());
            throw $e;
        }

    }

    public function traerOrdenManual()
    {
        $sql = "SELECT MAX(cast (RIGHT(ORDEN_COMPRA,'8')as INT)+1 ) AS nroOrden FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE OCM = 1;";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

            $rows = $v;
        }
        return ($rows);

    }

    public function listarTodosLosDespachos()
    {
        $sql = "SELECT TOP 100
                A.ID,
                FECHA_MOV,
                COD_PROVEE,
                PROVEEDOR,
                CONTENEDOR,
                MATERIAL,
                ORDEN_COMPRA,
                FECHA_EST_EMB,
                FECHA_EMB,
                NUMERO_BL,
                FACTURA,
                A.TIPO_CAMBIO,
                VALOR_FOB_DOLAR
            FROM RO_T_IMPORTACIONES_ENCABEZADO A
            LEFT JOIN RO_T_IMPORTACIONES_DETALLE B ON A.ID = B.ID_MG
            WHERE DESPACHO IS NULL OR B.ID_MG IS NULL
            AND A.FECHA_MOV >= GETDATE()-90
            ORDER BY ID DESC";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                error_log("Error en listarTodosLosDespachos: " . print_r(sqlsrv_errors(), true));
                return [];
            }

            $despachos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir objetos DateTime a strings
                if (isset($row['FECHA_MOV']) && is_object($row['FECHA_MOV'])) {
                    $row['FECHA_MOV'] = $row['FECHA_MOV']->format('Y-m-d');
                }
                if (isset($row['FECHA_EST_EMB']) && is_object($row['FECHA_EST_EMB'])) {
                    $row['FECHA_EST_EMB'] = $row['FECHA_EST_EMB']->format('Y-m-d');
                }
                if (isset($row['FECHA_EMB']) && is_object($row['FECHA_EMB'])) {
                    $row['FECHA_EMB'] = $row['FECHA_EMB']->format('Y-m-d');
                }

                $despachos[] = $row;
            }

            return $despachos;

        } catch (Exception $e) {
            error_log('Error en listarTodosLosDespachos: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerDespachoPorId($id)
    {
        $sql = "SELECT * FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = ?";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql, array($id));

            if ($stmt === false) {
                error_log("Error en obtenerDespachoPorId: " . print_r(sqlsrv_errors(), true));
                return null;
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($row) {
                // Convertir objetos DateTime a strings en formato DD/MM/YYYY
                $dateFields = [
                    'FECHA_MOV',
                    'FECHA_EST_EMB',
                    'FECHA_EMB',
                    'FECHA_ARR',
                    'FECHA_PAGO',
                    'FECHA_DESP_ADU',
                    'FECHA_EST_PAGO'
                ];

                foreach ($dateFields as $field) {
                    if (isset($row[$field]) && is_object($row[$field])) {
                        $row[$field] = $row[$field]->format('d/m/Y');
                    }
                }

                return $row;
            }

            return null;

        } catch (Exception $e) {
            error_log('Error en obtenerDespachoPorId: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Marca una OC como hija de otra (vinculación por contenedor compartido).
     * Solo actualiza si la fila todavía no tiene padre (idempotente).
     */
    public function vincularComoPadre($idHijo, $idPadre) {
        $idHijo  = intval($idHijo);
        $idPadre = intval($idPadre);

        if ($idHijo <= 0 || $idPadre <= 0 || $idHijo === $idPadre) {
            return false;
        }

        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                SET ID_PADRE = ?
                WHERE ID = ? AND ID_PADRE IS NULL";

        $stmt = sqlsrv_query($this->cid_central, $sql, array($idPadre, $idHijo));

        if ($stmt === false) {
            error_log('[vincularComoPadre] Error: ' . print_r(sqlsrv_errors(), true));
            return false;
        }

        return true;
    }

    /**
     * Devuelve el ID de la OC principal del grupo.
     * Si el ID dado ya es principal (ID_PADRE IS NULL), retorna el mismo.
     * Si es hijo, retorna el padre.
     */
    public function resolverIdPrincipal($idMg) {
        $idMg = intval($idMg);
        $sql = "SELECT COALESCE(ID_PADRE, ID) AS ID_PRINCIPAL
                FROM RO_T_IMPORTACIONES_ENCABEZADO
                WHERE ID = ?";

        $stmt = sqlsrv_query($this->cid_central, $sql, array($idMg));
        if ($stmt === false) return $idMg;

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? intval($row['ID_PRINCIPAL']) : $idMg;
    }

    /**
     * Devuelve array con todos los IDs del grupo (principal + hijas).
     * Si el ID dado es hijo, primero resuelve al padre.
     */
    public function obtenerIdsDelGrupo($idMg) {
        $idPrincipal = $this->resolverIdPrincipal($idMg);

        $ids = [$idPrincipal];

        $sql = "SELECT ID FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID_PADRE = ?";
        $stmt = sqlsrv_query($this->cid_central, $sql, array($idPrincipal));

        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $ids[] = intval($row['ID']);
            }
        }

        return $ids;
    }

    /**
     * Devuelve array con los datos de todas las OCs del grupo.
     * Cada elemento: ['ID' => int, 'ORDEN_COMPRA' => string, 'ID_PADRE' => int|null]
     * El principal aparece primero.
     */
    public function obtenerOrdenesDelGrupo($idMg) {
        $idPrincipal = $this->resolverIdPrincipal($idMg);

        $sql = "SELECT ID, ORDEN_COMPRA, ID_PADRE
                FROM RO_T_IMPORTACIONES_ENCABEZADO
                WHERE ID = ? OR ID_PADRE = ?
                ORDER BY (CASE WHEN ID_PADRE IS NULL THEN 0 ELSE 1 END), ID";

        $stmt = sqlsrv_query($this->cid_central, $sql, array($idPrincipal, $idPrincipal));
        if ($stmt === false) return [];

        $resultado = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultado[] = $row;
        }
        return $resultado;
    }

    /**
     * Recalcula FECHA_DISTRI de las OCs indicadas cuando se mueve FECHA_ARR.
     *
     * Delega en CronogramaFechas, que es la misma regla que usa el drag & drop
     * del cronograma: solo se tocan las filas con DIST_ORIGEN = 'A'; las
     * movidas a mano ('M') o confirmadas ('C') quedan intactas.
     */
    private function recalcularDistribucion($ids, $fechaArribo, $antes = null) {
        $this->cargarCronogramaFechas();

        $tocadas = CronogramaFechas::recalcularDistribucion(
            $this->cid_central, $ids, $fechaArribo
        );

        if (empty($tocadas)) {
            return [];
        }

        error_log('recalcularDistribucion: ' . count($tocadas) .
                  ' OCs con FECHA_DISTRI recalculada a ' . reset($tocadas));

        // La cascada se registra aparte, con su propio motivo.
        if ($antes !== null) {
            $parametros = CronogramaFechas::obtenerParametros($this->cid_central);
            foreach ($tocadas as $id => $nueva) {
                if (!isset($antes[$id])) continue;
                CronogramaFechas::registrarHistorial($this->cid_central, [
                    'idEncabezado'  => $id,
                    'ordenCompra'   => $antes[$id]['ORDEN_COMPRA'],
                    'contenedor'    => $antes[$id]['CONTENEDOR'],
                    'campo'         => 'FECHA_DISTRI',
                    'valorAnterior' => CronogramaFechas::derivarDistribucion($antes[$id], $parametros),
                    'valorNuevo'    => $nueva,
                    'motivo'        => MotivosFecha::RECALCULO_AUTOMATICO,
                    'observacion'   => null,
                    'usuario'       => isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null,
                    'origen'        => 'GESTION_DESPACHOS',
                ]);
            }
        }

        return $tocadas;
    }

    private function cargarCronogramaFechas() {
        require_once __DIR__ . '/../cronogramaDespachos/class/CronogramaFechas.php';
        require_once __DIR__ . '/../cronogramaDespachos/class/MotivosFecha.php';
    }

    /**
     * Fechas de las OCs indicadas, para comparar antes y despues de un UPDATE.
     */
    private function leerFechasParaHistorial($ids) {
        $this->cargarCronogramaFechas();
        return CronogramaFechas::leerFechas($this->cid_central, $ids);
    }

    /**
     * Compara el estado previo contra el actual y registra una fila de
     * historial por cada campo de fecha que cambio.
     *
     * Es lo que evita que el historial quede con agujeros: sin esto, toda
     * edicion hecha desde gestion de despachos no dejaria rastro y el
     * historial solo mostraria lo movido desde el cronograma.
     *
     * MOTIVO y OBSERVACION quedan en NULL a proposito: el formulario de
     * gestion de despachos no los pide, y sumarle campos obligatorios no
     * entra en esta tanda.
     */
    private function registrarCambiosDeFecha($ids, $antes) {
        if (empty($antes)) return;

        $this->cargarCronogramaFechas();
        $despues = CronogramaFechas::leerFechas($this->cid_central, $ids);
        $usuario = isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null;

        foreach ($despues as $id => $filaDespues) {
            if (!isset($antes[$id])) continue;

            foreach (CronogramaFechas::CAMPOS_EDITABLES as $campo) {
                $previo = $antes[$id][$campo];
                $actual = $filaDespues[$campo];

                if ((string) $previo === (string) $actual) {
                    continue;
                }

                CronogramaFechas::registrarHistorial($this->cid_central, [
                    'idEncabezado'  => $id,
                    'ordenCompra'   => $antes[$id]['ORDEN_COMPRA'],
                    'contenedor'    => $antes[$id]['CONTENEDOR'],
                    'campo'         => $campo,
                    'valorAnterior' => $previo,
                    'valorNuevo'    => $actual,
                    'motivo'        => null,
                    'observacion'   => null,
                    'usuario'       => $usuario,
                    'origen'        => 'GESTION_DESPACHOS',
                ]);
            }
        }
    }

    /**
     * Actualiza campos comunes de embarque/despacho en TODAS las OCs del grupo.
     * No toca ORDEN_COMPRA, OCM ni ID.
     *
     * @param int   $idEncabezado  ID de cualquier OC del grupo
     * @param array $datos         ['COLUMNA' => valor, ...]
     * @return bool
     */
    public function actualizarEncabezadoGrupo($idEncabezado, $datos) {
        $idPrincipal = $this->resolverIdPrincipal($idEncabezado);
        if (!$idPrincipal) return false;

        $idsGrupo = $this->obtenerIdsDelGrupo($idPrincipal);
        if (empty($idsGrupo)) return false;

        $columnasPermitidas = [
            'FECHA_MOV', 'FECHA_EMB', 'FECHA_OC',
            'FACTURA', 'NUMERO_BL',
            'TIPO_CAMBIO', 'VALOR_FOB_DOLAR', 'VALOR_FOB_PESO',
            'FECHA_ARR', 'FECHA_DESP_ADU',
            'FECHA_EST_PAGO', 'FECHA_EST_EMB',
            'DESPACHANTE', 'PUERTO_ORIGEN', 'TERMINAL', 'ETA_CONFIRMADA',
            'CONTENEDOR', 'DESPACHO', 'MATERIAL', 'ORIGEN', 'FORMA_PAGO',
            'COD_PROVEE', 'PROVEEDOR',
        ];

        $campos     = [];
        $parametros = [];

        foreach ($datos as $col => $valor) {
            if (in_array($col, $columnasPermitidas, true)) {
                $campos[]     = "$col = ?";
                $parametros[] = ($valor === '' ? null : $valor);
            }
        }

        if (empty($campos)) return false;

        $placeholders = implode(',', array_fill(0, count($idsGrupo), '?'));
        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                SET "    . implode(', ', $campos) . "
                WHERE ID IN ($placeholders)";

        $parametros = array_merge($parametros, $idsGrupo);

        // Estado previo, para poder registrar en el historial que cambio.
        $antes = $this->leerFechasParaHistorial($idsGrupo);

        $stmt = sqlsrv_query($this->cid_central, $sql, $parametros);
        if ($stmt === false) {
            error_log('actualizarEncabezadoGrupo: ' . print_r(sqlsrv_errors(), true));
            return false;
        }

        $this->registrarCambiosDeFecha($idsGrupo, $antes);

        // Si se movio el arribo, la distribucion automatica lo sigue.
        if (array_key_exists('FECHA_ARR', $datos) && !empty($datos['FECHA_ARR'])) {
            $this->recalcularDistribucion($idsGrupo, $datos['FECHA_ARR'], $antes);
        }

        $afectados = sqlsrv_rows_affected($stmt);
        error_log("actualizarEncabezadoGrupo: idRecibido=$idEncabezado, " .
                  "idPrincipal=$idPrincipal, ocsGrupo=" . count($idsGrupo) .
                  ", filasAfectadas=$afectados");

        return true;
    }

    public function actualizarEncabezado($id, $datosDeCabezera)
    {
        $id = intval($id);
        $codProv = substr(str_replace("'", "''", $datosDeCabezera['cod_proveedor']), 0, 6);

        // Construir UPDATE dinámico
        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO SET ";
        $updates = [];

        // Función auxiliar para formatear números de forma segura para SQL
        $fmtNum = function ($val) {
            return str_replace(',', '.', (string) $val);
        };

        // Función auxiliar para escapar strings
        $esc = function ($val) {
            return str_replace("'", "''", (string) $val);
        };

        // Campos que siempre se actualizan
        $updates[] = "COD_PROVEE = '" . $esc($codProv) . "'";
        $updates[] = "PROVEEDOR = '" . $esc($datosDeCabezera['proveedor']) . "'";
        $updates[] = "CONTENEDOR = '" . $esc($datosDeCabezera['contenedor']) . "'";
        $updates[] = "MATERIAL = '" . $esc($datosDeCabezera['material']) . "'";
        $updates[] = "ORIGEN = '" . $esc($datosDeCabezera['origen']) . "'";
        $updates[] = "VALOR_FOB_DOLAR = '" . $fmtNum($datosDeCabezera['valorFobDolar']) . "'";
        $updates[] = "FECHA_EST_EMB = '" . $esc($datosDeCabezera['fechaEstEmb']) . "'";
        $updates[] = "ORDEN_COMPRA = '" . $esc($datosDeCabezera['ordenCompra']) . "'";
        $updates[] = "OCM = '" . $esc($datosDeCabezera['ocm']) . "'";

        // Despachante (con valor por defecto si no viene)
        if (isset($datosDeCabezera['despachante']) && !empty($datosDeCabezera['despachante'])) {
            $updates[] = "DESPACHANTE = '" . $esc($datosDeCabezera['despachante']) . "'";
        }

        // Campos opcionales - actualizar siempre si están en el array (incluso si vacíos)
        if (isset($datosDeCabezera['fechaArr'])) {
            if (!empty($datosDeCabezera['fechaArr'])) {
                $updates[] = "FECHA_ARR = '" . $esc($datosDeCabezera['fechaArr']) . "'";
            } else {
                $updates[] = "FECHA_ARR = NULL";
            }
        }

        if (isset($datosDeCabezera['fechaPago']) && !empty($datosDeCabezera['fechaPago'])) {
            $updates[] = "FECHA_PAGO = '" . $esc($datosDeCabezera['fechaPago']) . "'";
        }

        if (isset($datosDeCabezera['fechaDespAdu']) && !empty($datosDeCabezera['fechaDespAdu'])) {
            $updates[] = "FECHA_DESP_ADU = '" . $esc($datosDeCabezera['fechaDespAdu']) . "'";
        }

        if (isset($datosDeCabezera['fechaEmb']) && !empty($datosDeCabezera['fechaEmb'])) {
            $updates[] = "FECHA_EMB = '" . $esc($datosDeCabezera['fechaEmb']) . "'";
        }

        if (isset($datosDeCabezera['numeroBl']) && !empty($datosDeCabezera['numeroBl'])) {
            $updates[] = "NUMERO_BL = '" . $esc($datosDeCabezera['numeroBl']) . "'";
        }

        if (isset($datosDeCabezera['facturaProveedor']) && !empty($datosDeCabezera['facturaProveedor'])) {
            $updates[] = "FACTURA = '" . $esc($datosDeCabezera['facturaProveedor']) . "'";
        }

        if (isset($datosDeCabezera['tipoCambio']) && !empty($datosDeCabezera['tipoCambio'])) {
            $updates[] = "TIPO_CAMBIO = '" . $fmtNum($datosDeCabezera['tipoCambio']) . "'";
        }

        if (isset($datosDeCabezera['valorFobPeso']) && !empty($datosDeCabezera['valorFobPeso'])) {
            $updates[] = "VALOR_FOB_PESO = '" . $fmtNum($datosDeCabezera['valorFobPeso']) . "'";
        }

        if (isset($datosDeCabezera['formaPago']) && !empty($datosDeCabezera['formaPago'])) {
            $updates[] = "FORMA_PAGO = '" . $esc($datosDeCabezera['formaPago']) . "'";
        }

        if (isset($datosDeCabezera['despacho']) && !empty($datosDeCabezera['despacho'])) {
            $updates[] = "DESPACHO = '" . $esc($datosDeCabezera['despacho']) . "'";
        }

        if (isset($datosDeCabezera['fechaEstPago']) && !empty($datosDeCabezera['fechaEstPago'])) {
            $updates[] = "FECHA_EST_PAGO = '" . $esc($datosDeCabezera['fechaEstPago']) . "'";
        }

        if (isset($datosDeCabezera['puertoOrigen']) && !empty($datosDeCabezera['puertoOrigen'])) {
            $updates[] = "PUERTO_ORIGEN = '" . $esc($datosDeCabezera['puertoOrigen']) . "'";
        }

        if (isset($datosDeCabezera['terminal']) && !empty($datosDeCabezera['terminal'])) {
            $updates[] = "TERMINAL = '" . $esc($datosDeCabezera['terminal']) . "'";
        }

        // ETA Confirmada (bit - 0 o 1)
        if (isset($datosDeCabezera['etaConfirmada'])) {
            $updates[] = "ETA_CONFIRMADA = " . intval($datosDeCabezera['etaConfirmada']);
        }

        if (empty($updates))
            return false;

        $sql .= implode(", ", $updates);
        $sql .= " WHERE ID = " . $id;

        error_log("SQL UPDATE: " . $sql);

        // Estado previo, para el historial.
        $antes = $this->leerFechasParaHistorial([$id]);

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en actualizarEncabezado: " . print_r($errors, true));
                return false;
            }

            $this->registrarCambiosDeFecha([$id], $antes);

            // Igual que en actualizarEncabezadoGrupo: mover el arribo arrastra
            // la distribucion automatica. Aca alcanza con esta OC, porque este
            // metodo actualiza una sola fila.
            if (isset($datosDeCabezera['fechaArr']) && !empty($datosDeCabezera['fechaArr'])) {
                $this->recalcularDistribucion([$id], $datosDeCabezera['fechaArr'], $antes);
            }

            return $id;

        } catch (Exception $e) {
            error_log('Error en actualizarEncabezado: ' . $e->getMessage());
            return false;
        }
    }

}