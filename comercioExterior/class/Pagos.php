<?php
/**
 * Pagos al proveedor del exterior.
 *
 * LA MONEDA ES EL DÓLAR.
 * RO_T_IMPORTACIONES_ENCABEZADO_PAGOS.MONTO está expresado en U$S, y el saldo
 * pendiente se calcula contra VALOR_FOB_DOLAR:
 *
 *     Saldo U$S = VALOR_FOB_DOLAR - SUMA(MONTO)
 *
 * Antes se comparaba contra VALOR_FOB_PESO. La columna no tiene marcador de
 * moneda y el relevamiento del 19/09/2026 encontró las dos conviviendo: 84
 * filas cargadas en dólares -la gente tipeaba lo que figuraba en la factura
 * del proveedor- y 7 en pesos, todas del 21/08/2026 y todas iguales al
 * VALOR_FOB_PESO exacto. El script comercioExterior/sql/08_pagos_en_dolares.sql
 * convirtió esas 7 dividiéndolas por el TIPO_CAMBIO del contenedor y dejó el
 * importe original en MONTO_ORIGEN_ARS, que es lo que hace que la conversión
 * sea auditable y reversible en vez de una pérdida de información.
 *
 * ESTA TABLA NO LA LEE NADIE MÁS.
 * Verificado sobre ProyectosXL/finanzas en develop: el cashflow lee
 * RO_T_IMPORTACIONES_ENCABEZADO, RO_T_IMPORTACIONES_DETALLE y
 * RO_T_IMPORTACIONES_ESTIMACION_DETALLE, y no toca los pagos en ningún lado.
 * Comercio Exterior es el único dueño de este circuito.
 */
class Pagos {
    private $cid_central;
    private $encabezado;

    function __construct() {
        require_once __DIR__ . '/../../class/conexion.php';
        require_once __DIR__ . '/encabezado.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($db);
        $this->encabezado  = new Encabezado();
    }

    /**
     * Obtiene todos los pagos de un encabezado.
     * Si el ID es de una hija, resuelve al principal antes de consultar.
     */
    public function obtenerPagosPorEncabezado($idEncabezado) {
        $idEncabezado = $this->encabezado->resolverIdPrincipal($idEncabezado);
        try {
            /* MONTO_ORIGEN_ARS es de un script posterior (el 08). Si todavía
               no se corrió se pide NULL con su nombre, para que el resto del
               código no tenga que preguntar si la columna existe. Es el mismo
               recurso que usa Comex::getProveedoresExterior() en Finanzas con
               COTIZ_USD_EDIT, y acá el literal NULL dice lo correcto: sin la
               columna no hay ninguna fila convertida. */
            $origenArs = $this->tieneColumnaOrigenArs()
                ? 'MONTO_ORIGEN_ARS'
                : 'CAST(NULL AS DECIMAL(18,2))';

            $sql = "SELECT ID, ID_ENCABEZADO, FECHA_PAGO, FORMA_PAGO, MEDIO_PAGO, MONTO,
                           FECHA_CREACION, " . $origenArs . " AS MONTO_ORIGEN_ARS
                    FROM RO_T_IMPORTACIONES_ENCABEZADO_PAGOS
                    WHERE ID_ENCABEZADO = ?
                    ORDER BY FECHA_PAGO ASC";
            
            $stmt = sqlsrv_prepare($this->cid_central, $sql, array(&$idEncabezado));
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al preparar consulta: " . print_r($errors, true));
            }
            
            if (!sqlsrv_execute($stmt)) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al ejecutar consulta: " . print_r($errors, true));
            }
            
            $pagos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $pagos[] = $row;
            }
            
            return $pagos;
            
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /** Cache: la existencia de la columna no cambia dentro de un request. */
    private $columnaOrigenArs = null;

    private function tieneColumnaOrigenArs() {
        if ($this->columnaOrigenArs !== null) {
            return $this->columnaOrigenArs;
        }

        $stmt = sqlsrv_query($this->cid_central,
            "SELECT COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO_PAGOS', 'MONTO_ORIGEN_ARS') AS C");

        $this->columnaOrigenArs = false;
        if ($stmt !== false) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $this->columnaOrigenArs = $row && $row['C'] !== null;
        }

        return $this->columnaOrigenArs;
    }

    /**
     * El estado de pago del contenedor, todo en U$S.
     *
     * Es el único lugar donde vive la cuenta del saldo. Antes la hacía el
     * controller de lectura y la repetía el navegador sumando el texto de una
     * columna de la tabla; con dos implementaciones, agregar un pago y
     * recargar la pantalla podían dar números distintos sin que nada lo
     * dijera.
     *
     * OC HIJAS: el FOB y los pagos se leen siempre de la OC PRINCIPAL. Un
     * contenedor con varias órdenes de compra tiene un solo pago al
     * proveedor, no uno por orden.
     *
     * @return array ['fobUsd','totalPagado','saldoPendiente','cantidad','estado']
     */
    public function obtenerResumen($idEncabezado) {
        $idPrincipal = $this->encabezado->resolverIdPrincipal($idEncabezado);

        $resumen = [
            'idEncabezado'   => $idPrincipal,
            'fobUsd'         => 0.0,
            'totalPagado'    => 0.0,
            'saldoPendiente' => 0.0,
            'cantidad'       => 0,
            'estado'         => 'SIN_FOB',
        ];

        $sql = "SELECT
                    E.VALOR_FOB_DOLAR                  AS FOB_USD,
                    ISNULL(SUM(P.MONTO), 0)            AS PAGADO_USD,
                    COUNT(P.ID)                        AS CANTIDAD
                FROM RO_T_IMPORTACIONES_ENCABEZADO E
                LEFT JOIN RO_T_IMPORTACIONES_ENCABEZADO_PAGOS P
                       ON P.ID_ENCABEZADO = E.ID
                WHERE E.ID = ?
                GROUP BY E.VALOR_FOB_DOLAR";

        $stmt = sqlsrv_query($this->cid_central, $sql, array($idPrincipal));

        if ($stmt === false) {
            error_log('[Pagos::obtenerResumen] ' . print_r(sqlsrv_errors(), true));
            return $resumen;
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row) {
            return $resumen;
        }

        $resumen['fobUsd']      = floatval($row['FOB_USD']);
        $resumen['totalPagado'] = floatval($row['PAGADO_USD']);
        $resumen['cantidad']    = intval($row['CANTIDAD']);
        $resumen['saldoPendiente'] = $resumen['fobUsd'] - $resumen['totalPagado'];

        /* Un centavo de tolerancia. Los pagos se cargan redondeados a dos
           decimales y la suma de varios parciales casi nunca da exacto: sin
           esta tolerancia, un contenedor efectivamente cancelado quedaría
           mostrando "faltan U$S 0,01" para siempre. */
        $tolerancia = 0.01;

        if ($resumen['fobUsd'] <= 0) {
            $resumen['estado'] = 'SIN_FOB';
        } elseif ($resumen['saldoPendiente'] < -$tolerancia) {
            $resumen['estado'] = 'SOBREPAGO';
        } elseif (abs($resumen['saldoPendiente']) <= $tolerancia) {
            $resumen['estado'] = 'CANCELADO';
        } else {
            $resumen['estado'] = 'PENDIENTE';
        }

        return $resumen;
    }

    /**
     * Inserta un nuevo pago.
     * Si el ID es de una hija, resuelve al principal antes de insertar.
     */
    public function insertarPago($idEncabezado, $fechaPago, $formaPago, $medioPago, $monto) {
        $idEncabezado = $this->encabezado->resolverIdPrincipal($idEncabezado);
        try {
            $sql = "INSERT INTO RO_T_IMPORTACIONES_ENCABEZADO_PAGOS
                    (ID_ENCABEZADO, FECHA_PAGO, FORMA_PAGO, MEDIO_PAGO, MONTO)
                    VALUES (?, ?, ?, ?, ?)";
            
            $params = array(&$idEncabezado, &$fechaPago, &$formaPago, &$medioPago, &$monto);
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al preparar inserción: " . print_r($errors, true));
            }
            
            if (!sqlsrv_execute($stmt)) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al insertar pago: " . print_r($errors, true));
            }
            
            return true;
            
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Un pago por su ID. Lo necesita la edición para conservar los campos que
     * no se mandan y para saber de qué contenedor recalcular el saldo.
     */
    public function obtenerPago($id) {
        $sql = "SELECT ID, ID_ENCABEZADO, FECHA_PAGO, FORMA_PAGO, MEDIO_PAGO, MONTO
                FROM RO_T_IMPORTACIONES_ENCABEZADO_PAGOS
                WHERE ID = ?";

        $stmt = sqlsrv_query($this->cid_central, $sql, array(intval($id)));

        if ($stmt === false) {
            error_log('[Pagos::obtenerPago] ' . print_r(sqlsrv_errors(), true));
            return null;
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        if (is_object($row['FECHA_PAGO'])) {
            $row['FECHA_PAGO'] = $row['FECHA_PAGO']->format('Y-m-d H:i:s');
        }

        return $row;
    }

    /**
     * Actualiza un pago existente. MONTO va en U$S.
     */
    public function actualizarPago($id, $fechaPago, $formaPago, $medioPago, $monto) {
        try {
            $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO_PAGOS 
                    SET FECHA_PAGO = ?, FORMA_PAGO = ?, MEDIO_PAGO = ?, MONTO = ? 
                    WHERE ID = ?";
            
            $params = array(&$fechaPago, &$formaPago, &$medioPago, &$monto, &$id);
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al preparar actualización: " . print_r($errors, true));
            }
            
            if (!sqlsrv_execute($stmt)) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al actualizar pago: " . print_r($errors, true));
            }
            
            return true;
            
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Elimina un pago
     */
    public function eliminarPago($id) {
        try {
            $sql = "DELETE FROM RO_T_IMPORTACIONES_ENCABEZADO_PAGOS WHERE ID = ?";
            $stmt = sqlsrv_prepare($this->cid_central, $sql, array(&$id));
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al preparar eliminación: " . print_r($errors, true));
            }
            
            if (!sqlsrv_execute($stmt)) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al eliminar pago: " . print_r($errors, true));
            }
            
            return true;
            
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Elimina todos los pagos de un encabezado.
     * Si el ID es de una hija, resuelve al principal antes de eliminar.
     */
    public function eliminarPagosPorEncabezado($idEncabezado) {
        $idEncabezado = $this->encabezado->resolverIdPrincipal($idEncabezado);
        try {
            $sql = "DELETE FROM RO_T_IMPORTACIONES_ENCABEZADO_PAGOS WHERE ID_ENCABEZADO = ?";
            $stmt = sqlsrv_prepare($this->cid_central, $sql, array(&$idEncabezado));
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al preparar eliminación: " . print_r($errors, true));
            }
            
            if (!sqlsrv_execute($stmt)) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al eliminar pagos: " . print_r($errors, true));
            }
            
            return true;
            
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}
?>
