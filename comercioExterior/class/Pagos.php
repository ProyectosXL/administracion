<?php
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
            $sql = "SELECT ID, ID_ENCABEZADO, FECHA_PAGO, FORMA_PAGO, MEDIO_PAGO, MONTO, FECHA_CREACION
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
     * Actualiza un pago existente
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
