
<?php
class Sucursal {
    private $cid_central;

    function __construct(){
        require_once __DIR__.'/../../Class/conexion.php';
        $conexion = new Conexion();
        $this->cid_central = $conexion->conectar('central');
    }

    private function getArray($sql){
        try {
            if (!$this->cid_central) {
                throw new Exception("Error de conexión a la base de datos");
            }

            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $v = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $v[] = $row;
            }

            return $v;
        }
        catch (Exception $e) {
            error_log("Error en getArray: " . $e->getMessage());
            return [];
        }
    }

    public function listarUsuarios($nroSucurs){
        try {
            $nroSucurs = intval($nroSucurs); // Sanitizar la entrada
            
            $sql = "SELECT DISTINCT 
                        CASE 
                            WHEN CHARINDEX(' -', NOMBRE_VEN) > 0 THEN LEFT(NOMBRE_VEN, CHARINDEX(' -', NOMBRE_VEN) - 1)
                            WHEN CHARINDEX('-', NOMBRE_VEN) > 0 THEN LEFT(NOMBRE_VEN, CHARINDEX('-', NOMBRE_VEN) - 1)
                            ELSE NOMBRE_VEN
                        END AS NOMBRE_VEN, 
                        A.BLOQUE, 
                        B.DESC_SUCURSAL, 
                        A.XML_CA_1118_NUM_SUCURSAL NRO_SUCURSAL 
                    FROM 
                    (
                        SELECT COD_VENDED BLOQUE, NOMBRE_VEN,
                        GVA23_CAMPOS_ADICIONALES.XML_CA.value('CA_1118_NUM_SUCURSAL', 'VARCHAR(6)') XML_CA_1118_NUM_SUCURSAL 
                        FROM GVA23
                        OUTER APPLY GVA23.CAMPOS_ADICIONALES.nodes('CAMPOS_ADICIONALES') as GVA23_CAMPOS_ADICIONALES(XML_CA)
                        WHERE INHABILITA = 0
                    ) A
                    INNER JOIN (SELECT * FROM [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE CANAL = 'PROPIOS') B 
                        ON A.XML_CA_1118_NUM_SUCURSAL = B.NRO_SUCURSAL
                    WHERE XML_CA_1118_NUM_SUCURSAL IS NOT NULL 
                    AND XML_CA_1118_NUM_SUCURSAL = ?
                    
                    UNION ALL
                    
                    SELECT DISTINCT
                        CASE 
                            WHEN CHARINDEX(' -', NOMBRE_VEN) > 0 THEN LEFT(NOMBRE_VEN, CHARINDEX(' -', NOMBRE_VEN) - 1)
                            WHEN CHARINDEX('-', NOMBRE_VEN) > 0 THEN LEFT(NOMBRE_VEN, CHARINDEX('-', NOMBRE_VEN) - 1)
                            ELSE NOMBRE_VEN
                        END AS NOMBRE_VEN, 
                        A.BLOQUE, 
                        B.DESC_SUCURSAL, 
                        A.XML_CA_1118_NUM_SUCURSAL NRO_SUCURSAL 
                    FROM 
                    (
                        SELECT COD_VENDED BLOQUE, NOMBRE_VEN,
                        GVA23_CAMPOS_ADICIONALES.XML_CA.value('CA_1118_NUM_SUCURSAL', 'VARCHAR(6)') XML_CA_1118_NUM_SUCURSAL 
                        FROM TASKY_SA.DBO.GVA23
                        OUTER APPLY GVA23.CAMPOS_ADICIONALES.nodes('CAMPOS_ADICIONALES') as GVA23_CAMPOS_ADICIONALES(XML_CA)
                        WHERE INHABILITA = 0 AND COD_VENDED LIKE '6%'
                    ) A
                    INNER JOIN (SELECT * FROM [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE CANAL = 'EXTERIOR') B 
                        ON A.XML_CA_1118_NUM_SUCURSAL = B.NRO_SUCURSAL
                    WHERE XML_CA_1118_NUM_SUCURSAL IS NOT NULL
                    AND XML_CA_1118_NUM_SUCURSAL = ?
                    ORDER BY NOMBRE_VEN";

            $params = array($nroSucurs, $nroSucurs);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Limpiamos los datos antes de devolverlos
                $row['NOMBRE_VEN'] = trim($row['NOMBRE_VEN']);
                $row['BLOQUE'] = trim($row['BLOQUE']);
                $resultados[] = $row;
            }

            return $resultados;

        } catch (Exception $e) {
            error_log("Error en listarUsuarios: " . $e->getMessage());
            return [];
        }
    }

    public function listarFleteros(){
        try {
            $sql = "SELECT DISTINCT NOMBRE_APELLIDO 
                    FROM RO_T_FLETEROS 
                    WHERE NOMBRE_APELLIDO IS NOT NULL 
                    ORDER BY NOMBRE_APELLIDO";

            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta de fleteros: " . print_r(sqlsrv_errors(), true));
            }

            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultados[] = [
                    'NOMBRE_APELLIDO' => trim($row['NOMBRE_APELLIDO'])
                ];
            }

            return $resultados;

        } catch (Exception $e) {
            error_log("Error en listarFleteros: " . $e->getMessage());
            return [];
        }
    }

    public function listarRemitos($nroSucurs) {
        try {
            $sql = "SELECT 
                        CAST(A.FECHA_MOV AS DATE) FECHA, 
                        N_COMP REMITO, 
                        B.DESC_SUCURSAL DESTINO 
                    FROM [LAKERBIS].LOCALES_LAKERS.DBO.CTA09 A
                    INNER JOIN [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS B 
                        ON A.SUC_DESTIN = B.NRO_SUCURSAL
                    WHERE T_COMP = 'REM' 
                        AND A.NRO_SUCURS = ? 
                        AND A.FECHA_MOV >= DATEADD(day, -45, GETDATE())
                        AND SUC_DESTIN > 1
                    ORDER BY A.FECHA_MOV DESC";
    
            $params = array($nroSucurs);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta de remitos: " . print_r(sqlsrv_errors(), true));
            }
    
            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $row['FECHA'] = $row['FECHA']->format('d/m/Y');
                $resultados[] = $row;
            }
    
            return $resultados;
        } catch (Exception $e) {
            error_log("Error en listarRemitos: " . $e->getMessage());
            return [];
        }
    }

}
?>