
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
                $datos['numeroPrecinto'] ?? null,
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
    
    public function insertarEgresos($nroRegistro, $fecha, $tComp, $nComp) {
        try {
            $sql = "INSERT INTO RO_EGRESOS_GUIA_RETIROS_SUC (NRO_REGISTRO, FECHA_COMP, T_COMP, N_COMP) 
                    VALUES (?, ?, ?, ?)";

            $params = [
                $nroRegistro,
                $fecha,
                $tComp,
                $nComp
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
    
    public function insertarRemitos($nroRegistro, $fecha, $remito, $destino, $bultos) {
        try {
            $sql = "INSERT INTO RO_REMITOS_GUIA_RETIROS_SUC (NRO_REGISTRO, FECHA_REM, N_COMP, DESTINO, BULTOS) 
                    VALUES (?, ?, ?, ?, ?)";
    
           
            $params = [
                $nroRegistro,
                $fecha,
                $remito,
                $destino,
                $bultos
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

    public function traerDatosGuiaRetiro($id) {

        try {
            $sql = "SELECT 
                FORMAT(FECHA_REG, 'dd/MM/yyyy HH:mm') as FECHA,
                NRO_REGISTRO,
                ENTREGO,
                RECIBIO,
                ENVIA_VALORES,
                OBSERVACIONES,
                FIRMA
            FROM RO_ENC_GUIA_RETIROS_SUC
            WHERE NRO_REGISTRO = ?";

            $params = array($id);
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

    public function actualizarGuiaRetiro($id, $entrego, $recibio, $enviaValores, $observaciones, $firma) {
        try {
            $sql = "UPDATE RO_ENC_GUIA_RETIROS_SUC 
                    SET ENTREGO = ?, RECIBIO = ?, ENVIA_VALORES = ?, OBSERVACIONES = ?, FIRMA = CONVERT(VARBINARY(MAX), ?) 
                    WHERE NRO_REGISTRO = ?";
                    
            $params = [$entrego, $recibio, $enviaValores, $observaciones, $firma, $id];
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
    
            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }
        } catch (Exception $e) {
            error_log("Error al actualizar la guía: " . $e->getMessage());
            throw $e;
        }
    }
    

    function ultimoRegistro($nroSucursal) {

        $sql = "SELECT MAX (NRO_REGISTRO) AS NRO_REGISTRO FROM RO_ENC_GUIA_RETIROS_SUC WHERE NRO_SUCURS = ?";

        $params = array($nroSucursal);

        $stmt = sqlsrv_query($this->cid_central, $sql, $params);

        if ($stmt === false) {
            throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $nroRegistro = 0;

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $nroRegistro = $row['NRO_REGISTRO'];
        }

        return $nroRegistro;
        

    }
}

?>

