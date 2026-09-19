<?php

class EstimacionCostos
{
    private $cid_central;

    private $encabezado;

    function __construct() {
        require_once __DIR__.'/../../class/conexion.php';
        require_once __DIR__.'/encabezado.php';
        require_once __DIR__.'/AlicuotasVigencia.php';
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
     * Obtener todos los conceptos de estimación configurados.
     *
     * Si se pasa la fecha de nacionalización del contenedor, los valores no
     * salen del padrón sino de la vigencia que regía A ESA FECHA: una
     * operación nacionalizada en marzo usa la alícuota de marzo, aunque desde
     * julio rija otra. Ver AlicuotasVigencia.
     *
     * Sin fecha -o sin vigencia que la cubra, o sin el script 09 corrido- se
     * usa el padrón, que es lo que la aplicación hacía antes de esta tanda.
     *
     * @param string|null $fechaNacionalizacion FECHA_DESP_ADU del contenedor
     */
    public function obtenerConceptos($fechaNacionalizacion = null) {
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        if ($db === 'uy') {
            $sql = "SELECT 
                        ID_CE,
                        CONCEPTO,
                        TIPO_VALOR,
                        VALOR_DEFAULT_1,
                        VALOR_DEFAULT_2,
                        ID_REF_CONCEPTO
                    FROM RO_T_CONCEPTOS_ESTIMACION_COMEX
                    ORDER BY ID_CE";
        } else {
            $sql = "SELECT 
                        ID_CE,
                        CONCEPTO,
                        TIPO_VALOR,
                        VALOR_DEFAULT_1,
                        VALOR_DEFAULT_2,
                        NULL AS ID_REF_CONCEPTO
                    FROM RO_T_CONCEPTOS_ESTIMACION_COMEX
                    ORDER BY ID_CE";
        }
        
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

            $resolucion = AlicuotasVigencia::resolver($this->cid_central, $fechaNacionalizacion);

            return AlicuotasVigencia::aplicarAConceptos($conceptos, $resolucion);

        } catch (Exception $e) {
            error_log('Error en obtenerConceptos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Resumen de qué vigencia se usó, para que la pantalla lo pueda decir.
     * Sin esto, trece números en pantalla son indistinguibles entre "salió de
     * la alícuota que regía ese día" y "salió del padrón porque no había".
     */
    public function resumenVigencia($fechaNacionalizacion) {
        $resolucion = AlicuotasVigencia::resolver($this->cid_central, $fechaNacionalizacion);

        return [
            'disponible' => AlicuotasVigencia::disponible($this->cid_central),
            'fecha'      => $resolucion['fecha'],
            'aplicada'   => $resolucion['aplicada'],
            'conceptos'  => count($resolucion['valores']),
        ];
    }

    /**
     * Obtener estimación existente para un despacho
     */
    public function obtenerEstimacion($idMg) {
        $idMg = $this->encabezado->resolverIdPrincipal($idMg);
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        
        if ($db === 'uy') {
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
                        C.TIPO_VALOR,
                        C.ID_REF_CONCEPTO
                    FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
                    INNER JOIN RO_T_CONCEPTOS_ESTIMACION_COMEX C ON D.ID_CE = C.ID_CE
                    WHERE D.ID_MG = ?
                    ORDER BY C.ID_CE";
        } else {
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
                        C.TIPO_VALOR,
                        NULL AS ID_REF_CONCEPTO
                    FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
                    INNER JOIN RO_T_CONCEPTOS_ESTIMACION_COMEX C ON D.ID_CE = C.ID_CE
                    WHERE D.ID_MG = ?
                    ORDER BY C.ID_CE";
        }
        
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
                    E.FECHA_DESP_ADU,
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
                // La fecha que decide qué alícuota aplica. Va en 'Y-m-d' y no
                // en 'd/m/Y' como las del formulario: acá se usa para comparar
                // contra vigencias, no para mostrar.
                if (isset($row['FECHA_DESP_ADU']) && is_object($row['FECHA_DESP_ADU'])) {
                    $row['FECHA_DESP_ADU'] = $row['FECHA_DESP_ADU']->format('Y-m-d');
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
        
        // Obtener despachante del despacho y valores del concepto.
        // FECHA_DESP_ADU viaja para resolver la vigencia unas líneas más abajo:
        // el honorario del despachante también es un valor que cambia en el
        // tiempo, y una estimación histórica tiene que usar el de su fecha.
        $sql = "SELECT
                    enc.DESPACHANTE,
                    enc.FECHA_DESP_ADU,
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

            $v1 = $row['VALOR_DEFAULT_1'];
            $v2 = $row['VALOR_DEFAULT_2'];

            // Si hay una vigencia que cubre la fecha de nacionalización, sus
            // valores ganan sobre los del padrón. Si no la hay, quedan los
            // del padrón: mismo criterio que obtenerConceptos().
            $resolucion = AlicuotasVigencia::resolver($this->cid_central, $row['FECHA_DESP_ADU']);
            if (isset($resolucion['valores'][intval($idCe)])) {
                $v1 = $resolucion['valores'][intval($idCe)]['VALOR_1'];
                $v2 = $resolucion['valores'][intval($idCe)]['VALOR_2'];
            }

            // Lógica de asignación según despachante
            if ($despachante === 'Farre') {
                // Farre usa VALOR_DEFAULT_2
                $valor1 = $v2;
                $valor2 = null;
            } else {
                // Laffitte o cualquier otro caso usa VALOR_DEFAULT_1
                $valor1 = $v1;
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
     * Actualizar estimación existente.
     *
     * NO GENERA FILAS DUPLICADAS: el UPDATE va por (ID_MG, ID_CE), que es la
     * clave natural de la estimación -un importe por concepto y contenedor-.
     * Guardar diez veces el mismo contenedor deja siempre trece filas.
     *
     * El INSERT de respaldo cubre el concepto que se creó DESPUÉS de que esta
     * estimación naciera: sin él, su importe se tipearía en pantalla, el
     * UPDATE no afectaría ninguna fila y el valor se perdería en silencio al
     * volver a abrir. Tampoco genera duplicados: solo inserta si no hay fila.
     *
     * CONFIRMADO no se toca. Editar los importes de un contenedor confirmado
     * no lo devuelve a borrador: sigue confirmado y con los valores nuevos.
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

                if (sqlsrv_rows_affected($stmt) > 0) {
                    continue;
                }

                // El concepto todavía no tenía fila en esta estimación.
                // Hereda el CONFIRMADO del resto para no quedar como una fila
                // suelta en borrador dentro de una estimación confirmada.
                $sqlInsert = "INSERT INTO RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                                (ID_MG, ID_CE, VALOR_DEFAULT_1, VALOR_DEFAULT_2,
                                 IMPORTE, CONFIRMADO, FECHA_MOD)
                              SELECT ?, ?, ?, ?, ?,
                                     ISNULL((SELECT TOP 1 CONFIRMADO
                                             FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                                             WHERE ID_MG = ?), 0),
                                     GETDATE()
                              WHERE NOT EXISTS (
                                  SELECT 1 FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                                  WHERE ID_MG = ? AND ID_CE = ?
                              )";

                $stmtInsert = sqlsrv_query($this->cid_central, $sqlInsert, array(
                    $idMg, $concepto['id_ce'], $valor1, $valor2, $concepto['importe'],
                    $idMg, $idMg, $concepto['id_ce']
                ));

                if ($stmtInsert === false) {
                    error_log("Error insertando concepto nuevo en estimación existente: "
                              . print_r(sqlsrv_errors(), true));
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
