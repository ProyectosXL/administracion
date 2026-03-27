<?php
// Carpeta: ../class/OrdenDeCompra.php
// Objetivo: SOLO DEFINICIÓN DE CLASE Y MÉTODOS DE BASE DE DATOS.

class OrdenDeCompra
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

        if ($this->cid_central === false) {
            error_log("Error crítico: Conexión a DB fallida en OrdenDeCompra.");
        }
    }

    private function retornarArray($sql)
    {
        try {
            sqlsrv_configure("QueryTimeout", 60);
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $rows = array();
            while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                foreach ($v as $key => $value) {
                    if (is_string($value)) {
                        $v[$key] = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                    }
                }
                $rows[] = $v;
            }
        } catch (Exception $e) {
            error_log("Error SQL en retornarArray: " . $e->getMessage());
            return [];
        }
        return $rows;
    }

    // --- MÉTODOS DE NEGOCIO ---

    public function traerOrdenDeCompra($proveedor)
    {
        $cod_proveedor_limpio = trim($proveedor);

        try {
            // Consulta SQL optimizada y limpia
            $sql = "SELECT DISTINCT T1.COD_PROVEE, T1.N_ORDEN_CO 
                    FROM CPA35 T1
                    WHERE LTRIM(RTRIM(T1.COD_PROVEE)) = LTRIM(RTRIM('$cod_proveedor_limpio'))";

            return $this->retornarArray($sql);

        } catch (\Throwable $th) {
            error_log("Error al buscar OC: " . $th->getMessage());
            return [];
        }
    }

    public function traerOrdenesPendientes()
    {
        try {
            // Consulta SQL que trae todas las OC del último año y medio que NO tienen despacho asignado
            $sql = "SELECT 
                        C.COD_PROVEE, 
                        UPPER(LTRIM(RTRIM(P.NOM_PROVEE))) AS PROVEEDOR,
                        C.N_ORDEN_CO,
                        CAST(C.FECHA_INGRESO AS DATE) AS FECHA_INGRESO
                    FROM CPA35 C
                    LEFT JOIN CPA01 P ON LTRIM(RTRIM(C.COD_PROVEE)) = LTRIM(RTRIM(P.COD_PROVEE))
                    WHERE C.N_ORDEN_CO NOT IN (
                        SELECT ORDEN_COMPRA 
                        FROM RO_T_IMPORTACIONES_ENCABEZADO 
                        WHERE ORDEN_COMPRA IS NOT NULL AND ORDEN_COMPRA <> ''
                    )
                    AND C.FECHA_INGRESO >= DATEADD(MONTH, -18, GETDATE()) AND C.COD_PROVEE LIKE 'Z%'
                    ORDER BY C.FECHA_INGRESO DESC";

            $rows = $this->retornarArray($sql);

            // Formatear fechas DateTime a string
            foreach ($rows as &$row) {
                if (isset($row['FECHA_INGRESO']) && is_object($row['FECHA_INGRESO'])) {
                    $row['FECHA_INGRESO'] = $row['FECHA_INGRESO']->format('Y-m-d');
                }
            }

            return $rows;

        } catch (\Throwable $th) {
            error_log("Error al buscar OC pendientes: " . $th->getMessage());
            return [];
        }
    }

    public function verificarOrdenCompra($ordenDeCompra)
    {
        $orden_limpia = trim($ordenDeCompra);
        $sql = "SELECT N_ORDEN_CO FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ORDEN_COMPRA = '$orden_limpia'";

        $rows = $this->retornarArray($sql);
        if (empty($rows)) {
            return 'ok';
        } else {
            return 'El comprobante existe';
        }
    }

    public function verificarOrdenCompraUy($ordenesDeCompra)
    {
        $cadenaSinComillas = trim($ordenesDeCompra, '"');
        $sql = "SELECT ORDEN_COMPRA, COUNT(*) AS existencia FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ORDEN_COMPRA in  ($cadenaSinComillas)  GROUP BY ORDEN_COMPRA";
        $rows = $this->retornarArray($sql);

        $resultados = array();
        foreach ($rows as $row) {
            if (isset($row['existencia']) && $row['existencia'] > 0) {
                $resultados[] = array('ordenDeCompra' => $row['ORDEN_COMPRA'], 'existe' => true);
            }
        }
        return $resultados;
    }

    public function deleteDetalle($idEncabezado)
    {
        $sql = "DELETE RO_T_IMPORTACIONES_DETALLE WHERE ID_MG ='" . $idEncabezado . "'";
        try {
            sqlsrv_query($this->cid_central, $sql);
        } catch (Exception $e) {
            error_log('Excepción capturada en deleteDetalle: ' . $e->getMessage());
        }
    }

    public function insertDetalle($datosDetalle, $idCabezera)
    {
        foreach ($datosDetalle as $dato) {
            $sql = "
            INSERT INTO RO_T_IMPORTACIONES_DETALLE(ID_MG, IMPORTE_U\$S, TIPO_CAMBIO, IMPORTE_$, PORCENTAJE, OBSERVACIONES, FECHA_MOD,GASTOS)
            VALUES (" . $idCabezera . ", " . $dato['importeEnDolares'] . ", " . $dato['tipoCambio'] . ", " . $dato['importeEnPesos'] . ", " . $dato['sobreFob'] . ", '" . $dato['observaciones'] . "',GETDATE(),'" . $dato['gastos'] . "')
            ;";

            try {
                sqlsrv_query($this->cid_central, $sql);
            } catch (Exception $e) {
                error_log('Excepción capturada en insertDetalle: ' . $e->getMessage());
            }
        }
    }
}
// *** IMPORTANTE: ELIMINAMOS TODA LA LÓGICA DE EJECUCIÓN DEL FINAL. ***
?>