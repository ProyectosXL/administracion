
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


    public function insertarEncabezadoGuiaRetiro($datos, $nroSucursal, $firma, $estado) {
        try {
            $sql = "INSERT INTO RO_ENC_GUIA_RETIROS_SUC 
                    (FECHA_REG, NRO_REGISTRO, NRO_SUCURS, ENTREGO, RECIBIO, ENVIA_VALORES, PRECINTO, OBSERVACIONES, FIRMA, ESTADO) 
                    VALUES (GETDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $datos['numeroRegistro'],
                $nroSucursal,
                $datos['entrego'],
                $datos['recibio'],
                $datos['enviaValores'] === 'SI' ? 1 : 0,
                (isset($datos['numeroPrecinto']) && $datos['numeroPrecinto'] != '') ? $datos['numeroPrecinto'] : null,
                $datos['observaciones'],
                $firma,
                $estado
            ];
    
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
       
            return ['success' => true, 'data' => $datos['numeroRegistro']];
            
        } catch (Exception $e) {
            error_log("Error en insertarEncabezadoGuiaRetiro: " . $e->getMessage());
            return false;
        }
    }

    public function limpiarEgresos($nroRegistro, $nroSucurs){

        try {
            $sql = "DELETE FROM RO_EGRESOS_GUIA_RETIROS_SUC WHERE NRO_REGISTRO = ? AND NR_SUCURS = $nroSucurs";
            $params = [$nroRegistro];
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            return true;
        } catch (Exception $e) {
            error_log("Error en limpiarEgresos: " . $e->getMessage());
            return false;
        }

    }

    public function insertarEgresos($nroRegistro, $fecha, $tComp, $nComp, $nroSucursal) {
        try {

            $sql = "INSERT INTO RO_EGRESOS_GUIA_RETIROS_SUC (NRO_REGISTRO, FECHA_COMP, T_COMP, N_COMP, NRO_SUCURS) 
                    VALUES (?, ?, ?, ?, ?)";

            $params = [
                $nroRegistro,
                $fecha,
                $tComp,
                $nComp,
                $nroSucursal
            ];

            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
        
            return true;
        } catch (Exception $e) {
            error_log("Error en insertarEgresos: " . $e->getMessage());
            return false;
        }
    }

    public function limpiarRemitos($nroRegistro, $nroSucurs){

        $sql = "DELETE FROM RO_REMITOS_GUIA_RETIROS_SUC WHERE NRO_REGISTRO = ? AND NRO_SUCURS = $nroSucurs";
        $params = [$nroRegistro];
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        if ($stmt === false) {
            throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
        }
        return true;


    }
    
    public function insertarRemitos($nroRegistro, $fecha, $remito, $destino, $bultos, $nroSucurs) {
        try {
            
            $sql = "INSERT INTO RO_REMITOS_GUIA_RETIROS_SUC (NRO_REGISTRO, FECHA_REM, N_COMP, DESTINO, BULTOS, NRO_SUCURS) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    
           
            $params = [
                $nroRegistro,
                $fecha,
                $remito,
                $destino,
                $bultos,
                $nroSucurs
            ];

            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error en insertarRemitos: " . $e->getMessage());
            return false;
        }
    }
    


    public function listarUsuarios(){
        try {
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
                    ORDER BY NOMBRE_VEN";
    
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
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
                        T_COMP, 
                        N_COMP REMITO, 
                        B.DESC_SUCURSAL DESTINO 
                    FROM [LAKERBIS].LOCALES_LAKERS.DBO.CTA09 A
                    INNER JOIN [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS B 
                        ON A.SUC_DESTIN = B.NRO_SUCURSAL AND A.COD_PRO_CL = B.COD_CLIENT COLLATE Latin1_General_BIN
                    WHERE T_COMP = 'REM' 
                        AND A.NRO_SUCURS = ? 
                        AND A.FECHA_MOV >= DATEADD(day, -45, GETDATE())
                        AND COD_PRO_CL LIKE 'GT%'
                        AND N_COMP COLLATE Latin1_General_BIN NOT IN (
                            SELECT N_COMP COLLATE Latin1_General_BIN 
                            FROM RO_REMITOS_GUIA_RETIROS_SUC
                        )
                    ORDER BY A.FECHA_MOV DESC, N_COMP DESC";
    
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

    public function listarEgresosEfectivo($nroSucurs) {
        try {
            $sql = "SELECT CAST(FECHA AS DATE) FECHA, COD_COMP, N_COMP, CANT_MONE FROM [LAKERBIS].LOCALES_LAKERS.DBO.CTA29 
                    WHERE COD_CTA = '100100' AND NRO_SUCURS = ? AND FECHA >= DATEADD(day, -45, GETDATE()) AND D_H = 'D'
                    AND N_COMP COLLATE Latin1_General_BIN NOT IN (SELECT N_COMP COLLATE Latin1_General_BIN FROM RO_EGRESOS_GUIA_RETIROS_SUC)
                    ORDER BY N_COMP DESC";

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
            error_log("Error en listarEgresos: " . $e->getMessage());
            return [];
        }
    }


 
    public function listarGuiasRetiro($nroSucurs, $fechaDesde, $fechaHasta) {
        try {
            $sql = "SELECT 
                        FORMAT(FECHA_REG, 'dd/MM/yyyy HH:mm') as FECHA,
                        NRO_REGISTRO,
                        CASE 
                            WHEN CHARINDEX('++', ENTREGO) > 0 
                            THEN LEFT(ENTREGO, CHARINDEX('++', ENTREGO) - 1)
                            ELSE ENTREGO 
                        END as EMISOR,
                        ESTADO
                    FROM RO_ENC_GUIA_RETIROS_SUC
                    WHERE NRO_SUCURS = ?
                        AND CAST(FECHA_REG AS DATE) BETWEEN ? AND ?
                    ORDER BY FECHA_REG DESC";
            $params = array($nroSucurs, $fechaDesde, $fechaHasta);

            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Asegurarse de que la fecha sea una cadena
                if ($row['FECHA'] instanceof DateTime) {
                    $row['FECHA'] = $row['FECHA']->format('d/m/Y H:i');
                }
                
                // Limpiar el emisor de espacios extras
                $row['EMISOR'] = trim($row['EMISOR']);
                
                $resultados[] = $row;
            }

            return $resultados;

        } catch (Exception $e) {
            error_log("Error en listarGuiasRetiro: " . $e->getMessage());
            throw new Exception("Error al obtener las guías: " . $e->getMessage());
        }
    }

    public function traerDatosGuiaRetiro($id, $nroSucurs) {

        try {
            $sql = "SELECT 
                FORMAT(FECHA_REG, 'dd/MM/yyyy HH:mm') as FECHA,
                NRO_REGISTRO,
                ENTREGO,
                RECIBIO,
                ENVIA_VALORES,
                OBSERVACIONES,
                PRECINTO,
                FIRMA
            FROM RO_ENC_GUIA_RETIROS_SUC
            WHERE NRO_REGISTRO = ? AND NRO_SUCURS = ?";

            $params = array($id, $nroSucurs);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $resultados = [];

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultados[] = $row;
            }

            return $resultados;

        } catch (Exception $e) {
            error_log("Error en listarGuiasRetiro: " . $e->getMessage());
            throw new Exception("Error al obtener las guías: " . $e->getMessage());
        }
    }



    function ultimoRegistro($nroSucursal) {

        $sql = "SELECT  MAX(CAST(SUBSTRING(NRO_REGISTRO, 2, LEN(NRO_REGISTRO)) AS int)) AS NRO_REGISTRO FROM RO_ENC_GUIA_RETIROS_SUC WHERE NRO_SUCURS = ?";

        $params = array($nroSucursal);

        $stmt = sqlsrv_query($this->cid_central, $sql, $params);

        if ($stmt === false) {
            throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $nroRegistro = 0;

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if($row['NRO_REGISTRO'] == NULL){
                continue;
            }
            $nroRegistro = $row['NRO_REGISTRO'];
        }

        return $nroRegistro;
        

    }

    public function listarRemitosPorGuia($id, $nroSucurs) {
        try {
            $sql = "SELECT 
                        CAST(FECHA_REM AS DATE) FECHA, 
                        N_COMP REMITO, 
                        DESTINO, 
                        BULTOS 
                    FROM RO_REMITOS_GUIA_RETIROS_SUC 
                    WHERE NRO_REGISTRO = ? 
                    AND NRO_SUCURS = ?
                    ORDER BY FECHA_REM DESC, N_COMP DESC";
    
            $params = array($id, $nroSucurs);   
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
            error_log("Error en listarRemitosPorGuia: " . $e->getMessage());
            return [];
        }
    }

    public function listarEgresosPorGuia($idGuia, $nroSucurs) {
        $sql = "SELECT T_COMP, N_COMP, FECHA_COMP 
                FROM RO_EGRESOS_GUIA_RETIROS_SUC 
                WHERE NRO_REGISTRO = ? AND NRO_SUCURS = ?";
        $params = array($idGuia, $nroSucurs);
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);

        if ($stmt === false) {
            throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $resultados = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        return $resultados;

    }
    
    public function actualizarEncabezadoGuiaRetiro($datos, $nroSucursal, $firma, $estado) {
        try {
            $sql = "UPDATE RO_ENC_GUIA_RETIROS_SUC 
                    SET FECHA_REG = GETDATE(), ENTREGO = ?, RECIBIO = ?, ENVIA_VALORES = ?, OBSERVACIONES = ?, FIRMA = ?, ESTADO = ?, PRECINTO = ?  
                    WHERE NRO_REGISTRO = ? AND NRO_SUCURS = ?";
                    
            $params = [
                $datos['entrego'],
                $datos['recibio'],
                $datos['enviaValores'] === 'SI' ? 1 : 0,
                $datos['observaciones'],
                $firma,
                $estado,
                (isset($datos['numeroPrecinto']) && $datos['numeroPrecinto'] != '') ? $datos['numeroPrecinto'] : null,
                $datos['numeroRegistro'],
                $nroSucursal
                
            ];
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
    
            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }
        } catch (Exception $e) {
            error_log("Error al actualizar la guía: " . $e->getMessage());
            throw $e;
        }
    }
}

?>

