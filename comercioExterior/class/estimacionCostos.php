<?php

class EstimacionCostos
{
    private $cid_central;

    private $encabezado;

    function __construct() {
        require_once __DIR__.'/../../class/conexion.php';
        require_once __DIR__.'/encabezado.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($db);
        $this->encabezado  = new Encabezado();
    }

    /**
     * Listado de despachos para PCI (Proyección de Costos de Importación).
     * Solo retorna OCs PRINCIPALES (ID_PADRE IS NULL). Las hijas se ocultan.
     * Incluye OCS_VINCULADAS y CANT_OCS para el badge "+N OCs".
     */
    public function listarDespachosConEstado() {
        $sql = "SELECT
                    E.ID,
                    E.FECHA_MOV,
                    E.PROVEEDOR,
                    E.CONTENEDOR,
                    E.MATERIAL,
                    E.ORDEN_COMPRA,
                    E.VALOR_FOB_DOLAR,
                    STUFF((
                        SELECT ', ' + LTRIM(RTRIM(H.ORDEN_COMPRA))
                        FROM RO_T_IMPORTACIONES_ENCABEZADO H
                        WHERE H.ID_PADRE = E.ID
                        FOR XML PATH(''), TYPE
                    ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS OCS_VINCULADAS,
                    1 + (SELECT COUNT(*) FROM RO_T_IMPORTACIONES_ENCABEZADO H WHERE H.ID_PADRE = E.ID) AS CANT_OCS,
                    CASE
                        WHEN EXISTS (
                            SELECT 1 FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
                            WHERE D.ID_MG = E.ID AND D.CONFIRMADO = 1
                        ) THEN 'CONFIRMADO'
                        WHEN EXISTS (
                            SELECT 1 FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
                            WHERE D.ID_MG = E.ID
                        ) THEN 'BORRADOR'
                        ELSE 'PENDIENTE'
                    END AS ESTADO
                FROM RO_T_IMPORTACIONES_ENCABEZADO E
                LEFT JOIN RO_T_IMPORTACIONES_DETALLE F ON E.ID = F.ID_MG
                WHERE E.FECHA_MOV >= DATEADD(MONTH, -6, GETDATE())
                  AND F.ID_MG IS NULL
                  AND E.ID_PADRE IS NULL
                ORDER BY E.FECHA_MOV DESC";
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                error_log("Error en listarDespachosConEstado: " . print_r(sqlsrv_errors(), true));
                return [];
            }
            
            $despachos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir objetos DateTime a strings
                if (isset($row['FECHA_MOV']) && is_object($row['FECHA_MOV'])) {
                    $row['FECHA_MOV'] = $row['FECHA_MOV']->format('Y-m-d');
                }
                $despachos[] = $row;
            }
            
            return $despachos;
            
        } catch (Exception $e) {
            error_log('Error en listarDespachosConEstado: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Listado de despachos para Gestión de Despachos.
     * Retorna TODAS las OCs (principales e hijas). Las hijas incluyen
     * ID_PADRE, OCS_VINCULADAS (para principales) y ORDEN_COMPRA_PADRE.
     */
    public function listarDespachosTodosConPadre() {
        $sql = "SELECT
                    E.ID,
                    E.FECHA_MOV,
                    E.PROVEEDOR,
                    E.CONTENEDOR,
                    E.MATERIAL,
                    E.ORDEN_COMPRA,
                    E.VALOR_FOB_DOLAR,
                    E.ID_PADRE,
                    CASE WHEN E.ID_PADRE IS NULL THEN
                        STUFF((
                            SELECT ', ' + LTRIM(RTRIM(H.ORDEN_COMPRA))
                            FROM RO_T_IMPORTACIONES_ENCABEZADO H
                            WHERE H.ID_PADRE = E.ID
                            FOR XML PATH(''), TYPE
                        ).value('.', 'NVARCHAR(MAX)'), 1, 2, '')
                    END AS OCS_VINCULADAS,
                    (SELECT P.ORDEN_COMPRA
                     FROM RO_T_IMPORTACIONES_ENCABEZADO P
                     WHERE P.ID = E.ID_PADRE) AS ORDEN_COMPRA_PADRE
                FROM RO_T_IMPORTACIONES_ENCABEZADO E
                LEFT JOIN RO_T_IMPORTACIONES_DETALLE F ON E.ID = F.ID_MG
                WHERE E.FECHA_MOV >= DATEADD(MONTH, -6, GETDATE())
                  AND F.ID_MG IS NULL
                ORDER BY E.FECHA_MOV DESC";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                error_log("Error en listarDespachosTodosConPadre: " . print_r(sqlsrv_errors(), true));
                return [];
            }

            $despachos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                if (isset($row['FECHA_MOV']) && is_object($row['FECHA_MOV'])) {
                    $row['FECHA_MOV'] = $row['FECHA_MOV']->format('Y-m-d');
                }
                $despachos[] = $row;
            }

            return $despachos;

        } catch (Exception $e) {
            error_log('Error en listarDespachosTodosConPadre: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los conceptos de estimación configurados
     */
    public function obtenerConceptos() {
        $sql = "SELECT 
                    ID_CE,
                    CONCEPTO,
                    TIPO_VALOR,
                    VALOR_DEFAULT_1,
                    VALOR_DEFAULT_2
                FROM RO_T_CONCEPTOS_ESTIMACION_COMEX
                ORDER BY ID_CE";
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                error_log("Error en obtenerConceptos: " . print_r(sqlsrv_errors(), true));
                return [];
            }
            
            $conceptos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $conceptos[] = $row;
            }
            
            return $conceptos;
            
        } catch (Exception $e) {
            error_log('Error en obtenerConceptos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estimación existente para un despacho
     */
    public function obtenerEstimacion($idMg) {
        $idMg = $this->encabezado->resolverIdPrincipal($idMg);
        $sql = "SELECT
                    D.ID,
                    D.ID_MG,
                    D.ID_CE,
                    D.VALOR_DEFAULT_1,
                    D.VALOR_DEFAULT_2,
                    D.IMPORTE,
                    D.CONFIRMADO,
                    D.FECHA_MOD,
                    C.CONCEPTO,
                    C.TIPO_VALOR
                FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
                INNER JOIN RO_T_CONCEPTOS_ESTIMACION_COMEX C ON D.ID_CE = C.ID_CE
                WHERE D.ID_MG = ?
                ORDER BY C.ID_CE";
        
        try {
            $params = array($idMg);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error en obtenerEstimacion: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $estimacion = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir DateTime
                if (isset($row['FECHA_MOD']) && is_object($row['FECHA_MOD'])) {
                    $row['FECHA_MOD'] = $row['FECHA_MOD']->format('Y-m-d H:i:s');
                }
                $estimacion[] = $row;
            }
            
            return count($estimacion) > 0 ? $estimacion : null;
            
        } catch (Exception $e) {
            error_log('Error en obtenerEstimacion: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener datos del despacho (encabezado).
     * Si el ID es de una OC hija, resuelve al principal y devuelve sus datos
     * junto con OCS_VINCULADAS y CANT_OCS para el header del editor PCI.
     */
    public function obtenerDespacho($idMg) {
        $idMg = $this->encabezado->resolverIdPrincipal($idMg);
        $sql = "SELECT
                    E.ID,
                    E.FECHA_MOV,
                    E.COD_PROVEE,
                    E.PROVEEDOR,
                    E.CONTENEDOR,
                    E.MATERIAL,
                    E.ORIGEN,
                    E.VALOR_FOB_DOLAR,
                    E.ORDEN_COMPRA,
                    E.DESPACHANTE,
                    STUFF((
                        SELECT ', ' + LTRIM(RTRIM(H.ORDEN_COMPRA))
                        FROM RO_T_IMPORTACIONES_ENCABEZADO H
                        WHERE H.ID_PADRE = E.ID
                        FOR XML PATH(''), TYPE
                    ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS OCS_VINCULADAS,
                    1 + (SELECT COUNT(*) FROM RO_T_IMPORTACIONES_ENCABEZADO H WHERE H.ID_PADRE = E.ID) AS CANT_OCS
                FROM RO_T_IMPORTACIONES_ENCABEZADO E
                WHERE E.ID = ?";
        
        try {
            $params = array($idMg);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error en obtenerDespacho: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($row) {
                // Convertir DateTime
                if (isset($row['FECHA_MOV']) && is_object($row['FECHA_MOV'])) {
                    $row['FECHA_MOV'] = $row['FECHA_MOV']->format('Y-m-d');
                }
                return $row;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log('Error en obtenerDespacho: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Guardar o actualizar estimación completa
     */
    public function guardarEstimacion($idMg, $conceptos) {
        $idMg = $this->encabezado->resolverIdPrincipal($idMg);
        try {
            // Verificar si ya existe estimación
            $existente = $this->obtenerEstimacion($idMg);
            
            if ($existente) {
                // Actualizar registros existentes
                return $this->actualizarEstimacion($idMg, $conceptos);
            } else {
                // Insertar nuevos registros
                return $this->insertarEstimacion($idMg, $conceptos);
            }
            
        } catch (Exception $e) {
            error_log('Error en guardarEstimacion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener parámetros dinámicos para concepto DESPACHANTE según despachante del despacho
     */
    private function obtenerParametrosDespachante($idMg, $idCe, &$valor1, &$valor2) {
        // Verificar si es el concepto DESPACHANTE
        $sqlCheck = "SELECT CONCEPTO FROM RO_T_CONCEPTOS_ESTIMACION_COMEX WHERE ID_CE = ?";
        $stmtCheck = sqlsrv_query($this->cid_central, $sqlCheck, array($idCe));
        
        if ($stmtCheck === false) {
            return false;
        }
        
        $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        if (!$rowCheck || strcasecmp($rowCheck['CONCEPTO'], 'DESPACHANTE') !== 0) {
            return false; // No es el concepto DESPACHANTE, mantener valores originales
        }
        
        // Obtener despachante del despacho y valores del concepto
        $sql = "SELECT 
                    enc.DESPACHANTE,
                    conc.VALOR_DEFAULT_1,
                    conc.VALOR_DEFAULT_2
                FROM RO_T_IMPORTACIONES_ENCABEZADO enc
                CROSS JOIN RO_T_CONCEPTOS_ESTIMACION_COMEX conc
                WHERE enc.ID = ?
                  AND conc.ID_CE = ?";
        
        $params = array($idMg, $idCe);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            error_log("Error obteniendo parámetros despachante: " . print_r(sqlsrv_errors(), true));
            return false;
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        if ($row) {
            $despachante = $row['DESPACHANTE'];
            
            // Lógica de asignación según despachante
            if ($despachante === 'Farre') {
                // Farre usa VALOR_DEFAULT_2
                $valor1 = $row['VALOR_DEFAULT_2'];
                $valor2 = null;
            } else {
                // Laffitte o cualquier otro caso usa VALOR_DEFAULT_1
                $valor1 = $row['VALOR_DEFAULT_1'];
                $valor2 = null;
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Insertar nueva estimación
     */
    private function insertarEstimacion($idMg, $conceptos) {
        try {
            foreach ($conceptos as $concepto) {
                // Valores por defecto
                $valor1 = isset($concepto['valor_default_1']) ? $concepto['valor_default_1'] : null;
                $valor2 = isset($concepto['valor_default_2']) ? $concepto['valor_default_2'] : null;
                
                // Aplicar lógica dinámica para concepto DESPACHANTE
                $this->obtenerParametrosDespachante($idMg, $concepto['id_ce'], $valor1, $valor2);
                
                $sql = "INSERT INTO RO_T_IMPORTACIONES_ESTIMACION_DETALLE 
                        (ID_MG, ID_CE, VALOR_DEFAULT_1, VALOR_DEFAULT_2, IMPORTE, CONFIRMADO, FECHA_MOD)
                        VALUES (?, ?, ?, ?, ?, 0, GETDATE())";
                
                $params = array(
                    $idMg,
                    $concepto['id_ce'],
                    $valor1,
                    $valor2,
                    $concepto['importe']
                );
                
                $stmt = sqlsrv_query($this->cid_central, $sql, $params);
                
                if ($stmt === false) {
                    error_log("Error insertando concepto: " . print_r(sqlsrv_errors(), true));
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Error en insertarEstimacion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar estimación existente
     */
    private function actualizarEstimacion($idMg, $conceptos) {
        try {
            foreach ($conceptos as $concepto) {
                // Valores por defecto
                $valor1 = isset($concepto['valor_default_1']) ? $concepto['valor_default_1'] : null;
                $valor2 = isset($concepto['valor_default_2']) ? $concepto['valor_default_2'] : null;
                
                // Aplicar lógica dinámica para concepto DESPACHANTE
                $this->obtenerParametrosDespachante($idMg, $concepto['id_ce'], $valor1, $valor2);
                
                $sql = "UPDATE RO_T_IMPORTACIONES_ESTIMACION_DETALLE 
                        SET VALOR_DEFAULT_1 = ?,
                            VALOR_DEFAULT_2 = ?,
                            IMPORTE = ?,
                            FECHA_MOD = GETDATE()
                        WHERE ID_MG = ? AND ID_CE = ?";
                
                $params = array(
                    $valor1,
                    $valor2,
                    $concepto['importe'],
                    $idMg,
                    $concepto['id_ce']
                );
                
                $stmt = sqlsrv_query($this->cid_central, $sql, $params);
                
                if ($stmt === false) {
                    error_log("Error actualizando concepto: " . print_r(sqlsrv_errors(), true));
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Error en actualizarEstimacion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Confirmar estimación (marcar como confirmada)
     */
    public function confirmarEstimacion($idMg) {
        $idMg = $this->encabezado->resolverIdPrincipal($idMg);
        $sql = "UPDATE RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                SET CONFIRMADO = 1,
                    FECHA_MOD = GETDATE()
                WHERE ID_MG = ?";
        
        try {
            $params = array($idMg);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error en confirmarEstimacion: " . print_r(sqlsrv_errors(), true));
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Error en confirmarEstimacion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si una estimación está confirmada
     */
    public function estaConfirmada($idMg) {
        $idMg = $this->encabezado->resolverIdPrincipal($idMg);
        $sql = "SELECT TOP 1 CONFIRMADO
                FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                WHERE ID_MG = ?";
        
        try {
            $params = array($idMg);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                return false;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            return $row && $row['CONFIRMADO'] == 1;
            
        } catch (Exception $e) {
            error_log('Error en estaConfirmada: ' . $e->getMessage());
            return false;
        }
    }
}
