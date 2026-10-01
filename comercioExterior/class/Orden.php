<?php
class Orden{

    function __construct() {

        require_once __DIR__ . '/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($db);

    }

    public function listarOrden($idEncabezado = null){
        
        $date = date('Y-m-d', strtotime("-90 days"));
        $sql = "SELECT ID,FECHA_MOV,COD_PROVEE,PROVEEDOR,DESPACHO,ORDEN_COMPRA,VALOR_FOB_PESO FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE FECHA_MOV > '$date' ORDER BY FECHA_MOV DESC";
        if($idEncabezado != null){
            $sql = " SELECT * FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = $idEncabezado ";
        }
     
        try{
        $stmt = sqlsrv_query( $this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }
        return ($rows);
        

        } catch (\Throwable $th) {

        print_r($th);

        }
    }

    public function traerPorOrdenCompra($id) {
        $sql = "SELECT * FROM RO_T_IMPORTACIONES_DETALLE WHERE ID_MG =".$id.";";
        try{
            $stmt = sqlsrv_query( $this->cid_central, $sql );
            $rows = array();
            
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }

            foreach ($rows as $key => &$value) {
                foreach ($value as $k => &$v) {
                    if((gettype($v)== 'string') && substr($v, 0, 1) == '.'){
                        $v = '0'.$v;
                    }
                }
            }

            // print_r($rows);
            // die();

            return ($rows);
            
    
            } catch (\Throwable $th) {
    
            print_r($th);
    
            }
    }
    public function traerOrdenPorFecha($desde, $hasta) {

        // COLLATE resuelve conflicto entre collations de distintas BDs (ej: Modern_Spanish_CI_AI vs Latin1_General_BIN)
        $sql = "SELECT
                    A.ID,
                    A.FECHA_MOV,
                    A.FECHA_DESP_ADU,
                    A.CONTENEDOR,
                    A.COD_PROVEE,
                    A.PROVEEDOR,
                    A.DESPACHO,
                    A.ORDEN_COMPRA,
                    A.VALOR_FOB_PESO,
                    ISNULL(B.COSTO_NAC, 0) * 100 AS COSTO_NAC,
                    A.ID_PADRE,
                    (SELECT P.ORDEN_COMPRA
                     FROM RO_T_IMPORTACIONES_ENCABEZADO P
                     WHERE P.ID = A.ID_PADRE) AS ORDEN_COMPRA_PADRE
                FROM RO_T_IMPORTACIONES_ENCABEZADO A
                LEFT JOIN RO_W_COSTO_NACIONALIZACION B
                    ON A.ORDEN_COMPRA COLLATE Latin1_General_BIN = B.ORDEN_COMPRA COLLATE Latin1_General_BIN
                WHERE A.FECHA_DESP_ADU BETWEEN '$desde' AND '$hasta'
                ORDER BY A.FECHA_DESP_ADU DESC;";
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('[traerOrdenPorFecha] Error SQL: ' . print_r($errors, true));
                return [];
            }

            $rows = array();
            while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $v;
            }
            return $rows;

        } catch (\Throwable $th) {
            error_log('[traerOrdenPorFecha] Excepción: ' . $th->getMessage());
            return [];
        }
    }

    /**
     * Inserta/actualiza RO_COSTOS_NACIONALIZACION para TODAS las OCs del grupo.
     * Si el encabezado recibido es una OC hija, primero resuelve al principal
     * y luego replica el mismo COSTO_NAC a cada OC del grupo.
     */
    public function insertarCostoNacionalizacion($nroOrden, $idEncabezado, $costoNac) {

        // Validar conexión antes de operar
        if ($this->cid_central === false || $this->cid_central === null) {
            error_log('[insertarCostoNacionalizacion] Sin conexión a la base de datos.');
            return ['success' => false, 'message' => 'Sin conexión a la base de datos. Verificar configuración del entorno.'];
        }

        require_once __DIR__ . '/encabezado.php';
        $encabezadoClass = new Encabezado();

        $idPrincipal  = $encabezadoClass->resolverIdPrincipal($idEncabezado);
        $ordenesGrupo = $encabezadoClass->obtenerOrdenesDelGrupo($idEncabezado);

        if (empty($ordenesGrupo)) {
            error_log('[insertarCostoNacionalizacion] No se encontraron OCs para idEncabezado=' . $idEncabezado);
            return ['success' => false, 'message' => 'No se encontró el encabezado ID=' . $idEncabezado];
        }

        $costoNacVal = floatval($costoNac);

        foreach ($ordenesGrupo as $ocReal) {
            $ocStr = is_array($ocReal) ? $ocReal['ORDEN_COMPRA'] : $ocReal;
            $ocEsc = str_replace("'", "''", $ocStr);

            // DELETE previo para esta OC
            $sqlDelete = "DELETE FROM RO_COSTOS_NACIONALIZACION WHERE N_ORDEN_CO = '$ocEsc'";
            $stmtDelete = sqlsrv_query($this->cid_central, $sqlDelete);
            if ($stmtDelete === false) {
                $errors   = sqlsrv_errors();
                $sqlMsg   = isset($errors[0]['message']) ? $errors[0]['message'] : 'Error SQL desconocido';
                $sqlCode  = isset($errors[0]['code'])    ? $errors[0]['code']    : '?';
                error_log('[insertarCostoNacionalizacion] Error DELETE OC=' . $ocStr . ': ' . print_r($errors, true));
                return [
                    'success' => false,
                    'message' => "Error al eliminar registro previo (OC: $ocStr) — [Código $sqlCode] $sqlMsg"
                ];
            }

            // Obtener FECHA_DESP_ADU del encabezado que corresponda a esta OC
            $sqlFecha = "SELECT FECHA_DESP_ADU FROM RO_T_IMPORTACIONES_ENCABEZADO
                         WHERE ORDEN_COMPRA = '$ocEsc'";
            $stmtFecha = sqlsrv_query($this->cid_central, $sqlFecha);
            $rowFecha  = ($stmtFecha !== false) ? sqlsrv_fetch_array($stmtFecha, SQLSRV_FETCH_ASSOC) : null;

            $fechaDespStr = ($rowFecha && $rowFecha['FECHA_DESP_ADU'])
                ? "'" . $rowFecha['FECHA_DESP_ADU']->format('Y-m-d') . "'"
                : 'NULL';

            $sqlInsert = "INSERT INTO RO_COSTOS_NACIONALIZACION (FECHA_DESP, N_ORDEN_CO, COSTO_NAC, FECHA_MODIF)
                          VALUES ($fechaDespStr, '$ocEsc', $costoNacVal, GETDATE())";

            $stmtInsert = sqlsrv_query($this->cid_central, $sqlInsert);
            if ($stmtInsert === false) {
                $errors   = sqlsrv_errors();
                $sqlMsg   = isset($errors[0]['message']) ? $errors[0]['message'] : 'Error SQL desconocido';
                $sqlCode  = isset($errors[0]['code'])    ? $errors[0]['code']    : '?';
                error_log('[insertarCostoNacionalizacion] Error INSERT OC=' . $ocStr . ': ' . print_r($errors, true));
                return [
                    'success' => false,
                    'message' => "Error al insertar costo (OC: $ocStr) — [Código $sqlCode] $sqlMsg"
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Costo de nacionalización actualizado en ' . count($ordenesGrupo) . ' OC(s)'
        ];
    }

    public function updateCostoNacionalizacion($nroOrden){

        $nroOrden = trim($nroOrden);
        if(strlen($nroOrden) == 13){
            $nroOrden = ' '.$nroOrden;
        }

        // DELETE previo
        $sqlDelete = "DELETE FROM RO_COSTOS_NACIONALIZACION WHERE N_ORDEN_CO = ?";
        $stmtDelete = sqlsrv_prepare($this->cid_central, $sqlDelete, array(&$nroOrden));
        if ($stmtDelete === false || sqlsrv_execute($stmtDelete) === false) {
            $errors = sqlsrv_errors();
            error_log('[updateCostoNacionalizacion] Error DELETE: ' . print_r($errors, true));
            return ['success' => false, 'message' => 'Error al eliminar registro previo: ' . print_r($errors, true)];
        }

        // Ejecutar SP
        $sqlExec = "EXEC RO_SP_INSERTAR_COSTO_NACIONALIZACION ?";
        ini_set('max_execution_time', 300);
        $stmtExec = sqlsrv_prepare($this->cid_central, $sqlExec, array(&$nroOrden));
        if ($stmtExec === false || sqlsrv_execute($stmtExec) === false) {
            $errors = sqlsrv_errors();
            error_log('[updateCostoNacionalizacion] Error EXEC SP: ' . print_r($errors, true));
            return ['success' => false, 'message' => 'Error al ejecutar SP: ' . print_r($errors, true)];
        }

        // Verificar cuántas filas insertó
        $sqlCheck = "SELECT COUNT(*) AS total FROM RO_COSTOS_NACIONALIZACION WHERE N_ORDEN_CO = ?";
        $stmtCheck = sqlsrv_prepare($this->cid_central, $sqlCheck, array(&$nroOrden));
        sqlsrv_execute($stmtCheck);
        $row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        $filasInsertadas = $row ? intval($row['total']) : 0;

        if ($filasInsertadas === 0) {
            error_log('[updateCostoNacionalizacion] SP ejecutado pero no insertó filas. N_ORDEN_CO: ' . $nroOrden);
            return ['success' => false, 'message' => 'El SP no encontró datos en RO_W_COSTO_NACIONALIZACION para la orden: ' . trim($nroOrden)];
        }

        return ['success' => true, 'message' => 'Costo de nacionalización actualizado correctamente'];

    }

    /* ======================================================================
       BORRAR UN DESPACHO

       CÓMO FALLABA. El borrado iba sin transacción y en este orden:
       estimación, detalle, encabezado. En central, el historial de fechas
       (FK_RO_T_IMP_HIST_ENC) y las hijas (FK_RO_T_IMP_ENC_PADRE) son NO ACTION,
       así que con cualquiera de las dos el DELETE del encabezado fallaba...
       DESPUÉS de haber borrado la estimación y los costos. Quedaba un
       contenedor sin costos ni estimación que nadie había pedido borrar. Los
       pagos, en cambio, se iban sin aviso: FK_PAGOS_ENCABEZADO es ON DELETE
       CASCADE en central. Y en uy no hay FK en pagos, así que quedaban
       huérfanos.

       AHORA: se pregunta antes qué se va a borrar -infoEliminacion(), que es
       lo que muestra el aviso de la pantalla-, y se borra todo en UNA
       transacción, explícitamente y en orden de dependencias, con rollback
       completo si cualquier paso falla.

       LA ÚNICA NEGATIVA ES UNA PRINCIPAL CON HIJAS. Borrarla dejaría a las
       hijas apuntando a nada, y resolver el borrado del grupo no vale la pena
       hoy: no hay hijas en ninguna de las dos bases.

       UNA HIJA borra su copia del detalle, su historial y su fila. La
       estimación y los pagos son del contenedor y viven en la principal: no se
       tocan.

       LO QUE NO SE BORRA: las tablas del cashflow de Finanzas
       (RO_T_CASHFLOW_COMEX_*) apuntan al ID_MG sin FK, a propósito -ver su
       README-comex.md-. Sus filas quedan huérfanas y no aparecen en ningún
       JOIN.
       ====================================================================== */

    const TABLA_HISTORIAL = 'RO_T_IMPORTACIONES_FECHAS_HIST';
    const TABLA_PAGOS     = 'RO_T_IMPORTACIONES_ENCABEZADO_PAGOS';

    /** Si una tabla existe en esta base: se pregunta antes de nombrarla. */
    private function existeTabla($tabla) {
        $stmt = sqlsrv_query($this->cid_central, "SELECT OBJECT_ID('dbo." . $tabla . "', 'U') AS T");
        if ($stmt === false) {
            return false;
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        return $row && $row['T'] !== null;
    }

    /** Un número de una consulta de una fila y una columna, o lanza. */
    private function escalar($sql, $params, $contexto) {
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        if ($stmt === false) {
            throw new Exception($contexto . ': ' . print_r(sqlsrv_errors(), true));
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);

        return $row ? $row[0] : null;
    }

    /**
     * Qué se borraría con este despacho, para el aviso previo.
     *
     * LO CALCULA EL SERVIDOR y no el navegador: la pantalla no tiene los
     * pagos, ni el historial, ni sabe si el contenedor tiene hijas, y armarlo
     * de lo que tiene sería mostrar un aviso incompleto justo antes de un
     * borrado que no se puede deshacer.
     *
     * @return array ['existe','id','contenedor','ordenCompra','esHija','idPrincipal',
     *                'ordenPrincipal','cantHijas','bloqueado','motivoBloqueo',
     *                'costos','estimacion','pagos','historial','requiereAviso','avisos']
     */
    public function infoEliminacion($id) {
        $id = intval($id);

        $stmt = sqlsrv_query($this->cid_central,
            "SELECT E.ID, E.ID_PADRE, E.CONTENEDOR, E.ORDEN_COMPRA, P.ORDEN_COMPRA AS ORDEN_PRINCIPAL,
                    (SELECT COUNT(*) FROM RO_T_IMPORTACIONES_ENCABEZADO H WHERE H.ID_PADRE = E.ID) AS HIJAS
             FROM RO_T_IMPORTACIONES_ENCABEZADO E
             LEFT JOIN RO_T_IMPORTACIONES_ENCABEZADO P ON P.ID = E.ID_PADRE
             WHERE E.ID = ?", [$id]);

        if ($stmt === false) {
            throw new Exception('No se pudo leer el despacho: ' . print_r(sqlsrv_errors(), true));
        }

        $fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (!$fila) {
            return ['existe' => false, 'id' => $id];
        }

        $esHija = $fila['ID_PADRE'] !== null;
        $cantHijas = intval($fila['HIJAS']);
        $avisos = [];

        $costos = intval($this->escalar(
            "SELECT COUNT(*) FROM RO_T_IMPORTACIONES_DETALLE WHERE ID_MG = ?", [$id],
            'No se pudieron contar los costos cargados'));

        $hayHistorial = $this->existeTabla(self::TABLA_HISTORIAL);
        $historial = $hayHistorial
            ? intval($this->escalar("SELECT COUNT(*) FROM " . self::TABLA_HISTORIAL . " WHERE ID_ENCABEZADO = ?",
                [$id], 'No se pudo contar el historial de fechas'))
            : 0;
        if (!$hayHistorial) {
            $avisos[] = 'No se encuentra la tabla ' . self::TABLA_HISTORIAL . ': no hay historial de fechas que borrar.';
        }

        /* La estimación y los pagos son del CONTENEDOR y viven en la
           principal. Para una hija no se cuentan porque no se borran. */
        $estimacion = ['filas' => 0, 'confirmada' => false];
        $pagos = ['cantidad' => 0, 'totalUsd' => 0.0];

        if (!$esHija) {
            $stmt = sqlsrv_query($this->cid_central,
                "SELECT COUNT(*) AS N, MAX(CAST(CONFIRMADO AS INT)) AS C
                 FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE WHERE ID_MG = ?", [$id]);
            if ($stmt === false) {
                throw new Exception('No se pudo leer la estimación: ' . print_r(sqlsrv_errors(), true));
            }
            $e = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $estimacion = ['filas' => intval($e['N']), 'confirmada' => intval($e['C']) === 1];

            if ($this->existeTabla(self::TABLA_PAGOS)) {
                $stmt = sqlsrv_query($this->cid_central,
                    "SELECT COUNT(*) AS N, ISNULL(SUM(MONTO), 0) AS TOTAL
                     FROM " . self::TABLA_PAGOS . " WHERE ID_ENCABEZADO = ?", [$id]);
                if ($stmt === false) {
                    throw new Exception('No se pudieron leer los pagos: ' . print_r(sqlsrv_errors(), true));
                }
                $p = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                $pagos = ['cantidad' => intval($p['N']), 'totalUsd' => floatval($p['TOTAL'])];
            } else {
                $avisos[] = 'No se encuentra la tabla ' . self::TABLA_PAGOS . ': no se pudo ver si el contenedor tiene pagos.';
            }
        }

        $bloqueado = (!$esHija && $cantHijas > 0);

        return [
            'existe'         => true,
            'id'             => $id,
            'contenedor'     => $fila['CONTENEDOR'],
            'ordenCompra'    => trim((string) $fila['ORDEN_COMPRA']),
            'esHija'         => $esHija,
            'idPrincipal'    => $esHija ? intval($fila['ID_PADRE']) : $id,
            'ordenPrincipal' => $esHija ? trim((string) $fila['ORDEN_PRINCIPAL']) : null,
            'cantHijas'      => $cantHijas,
            'bloqueado'      => $bloqueado,
            'motivoBloqueo'  => $bloqueado
                ? 'Es la OC principal de un contenedor con ' . $cantHijas
                  . ($cantHijas === 1 ? ' orden de compra vinculada' : ' órdenes de compra vinculadas')
                  . '. Borrarla dejaría a esas órdenes sin principal: no se borra nada.'
                : null,
            'costos'         => ['filas' => $costos],
            'estimacion'     => $estimacion,
            'pagos'          => $pagos,
            'historial'      => ['filas' => $historial],
            'requiereAviso'  => $costos > 0 || $estimacion['filas'] > 0
                                || $pagos['cantidad'] > 0 || $historial > 0,
            'avisos'         => $avisos,
        ];
    }

    /**
     * Borra un despacho y todo lo que cuelga de él, en una transacción.
     *
     * Vuelve a mirar qué hay ADENTRO de la transacción, en vez de confiar en
     * lo que se mostró en el aviso: entre el aviso y el clic puede haber
     * pasado cualquier cosa, y la negativa por hijas no puede depender de que
     * la pantalla esté al día.
     *
     * @return array ['success' => bool, 'message' => string, 'borrado' => array]
     */
    public function eliminarDespacho($id) {
        $id = intval($id);

        if (sqlsrv_begin_transaction($this->cid_central) === false) {
            return ['success' => false,
                    'message' => 'No se pudo abrir la transacción: no se borró nada.'];
        }

        try {
            $info = $this->infoEliminacion($id);

            if (!$info['existe']) {
                throw new Exception('El despacho #' . $id . ' ya no existe: puede que lo haya borrado otra persona.');
            }

            if ($info['bloqueado']) {
                sqlsrv_rollback($this->cid_central);

                return ['success' => false, 'bloqueado' => true, 'message' => $info['motivoBloqueo']];
            }

            $borrar = function ($sql, $contexto) use ($id) {
                $stmt = sqlsrv_query($this->cid_central, $sql, [$id]);
                if ($stmt === false) {
                    throw new Exception($contexto . ': ' . print_r(sqlsrv_errors(), true));
                }

                return sqlsrv_rows_affected($stmt);
            };

            $borrado = ['historial' => 0, 'pagos' => 0, 'estimacion' => 0, 'costos' => 0];

            /* En orden de dependencias: todo lo que apunta al encabezado
               primero. Los pagos van EXPLÍCITOS también en central, aunque ahí
               la FK los borre en cascada: así el borrado es el mismo en las dos
               bases, y en uy -sin FK- dejan de quedar huérfanos. */
            if ($this->existeTabla(self::TABLA_HISTORIAL)) {
                $borrado['historial'] = $borrar(
                    "DELETE FROM " . self::TABLA_HISTORIAL . " WHERE ID_ENCABEZADO = ?",
                    'Error al borrar el historial de fechas');
            }

            if (!$info['esHija']) {
                if ($this->existeTabla(self::TABLA_PAGOS)) {
                    $borrado['pagos'] = $borrar(
                        "DELETE FROM " . self::TABLA_PAGOS . " WHERE ID_ENCABEZADO = ?",
                        'Error al borrar los pagos');
                }

                $borrado['estimacion'] = $borrar(
                    "DELETE FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE WHERE ID_MG = ?",
                    'Error al borrar la estimación');
            }

            $borrado['costos'] = $borrar(
                "DELETE FROM RO_T_IMPORTACIONES_DETALLE WHERE ID_MG = ?",
                'Error al borrar los costos cargados');

            $filas = $borrar(
                "DELETE FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = ?",
                'Error al borrar el despacho');

            if ($filas !== 1) {
                throw new Exception('El despacho no se borró (filas afectadas: ' . intval($filas) . ').');
            }

            if (sqlsrv_commit($this->cid_central) === false) {
                throw new Exception('No se pudo confirmar la transacción.');
            }

            return [
                'success' => true,
                'message' => 'Despacho eliminado' . ($info['esHija']
                    ? '. La estimación y los pagos del contenedor siguen en la OC principal '
                      . $info['ordenPrincipal'] . '.'
                    : '.'),
                'borrado' => $borrado,
            ];

        } catch (Throwable $e) {
            sqlsrv_rollback($this->cid_central);
            error_log('[eliminarDespacho] ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'No se borró nada: ' . $e->getMessage(),
            ];
        }
    }

}