
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

    /**
     * Verifica si ya existe un registro de guía de retiro con el mismo número de registro y sucursal.
     *
     * @param string $nroRegistro El número de registro a verificar.
     * @param int $nroSucurs El número de sucursal a verificar.
     * @return bool True si existe un registro duplicado, false en caso contrario.
     */
    public function checkExistingRetiro($nroRegistro, $nroSucurs) {
        try {
            $sql = "SELECT COUNT(*) AS count FROM RO_ENC_GUIA_RETIROS_SUC WHERE NRO_REGISTRO = ? AND NRO_SUCURS = ?";
            $params = array($nroRegistro, $nroSucurs);

            $stmt = sqlsrv_query($this->cid_central, $sql, $params);

            if ($stmt === false) {
                throw new Exception("Error en la consulta de verificación de duplicados: " . print_r(sqlsrv_errors(), true));
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            return $row['count'] > 0;

        } catch (Exception $e) {
            error_log("Error en checkExistingRetiro: " . $e->getMessage());
            // En caso de error, asumimos que no hay duplicado para no bloquear la operación,
            // pero registramos el error. Podrías querer manejar esto de otra manera.
            return false;
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
            require_once __DIR__.'/../../Class/conexion.php';
            $conexion = new Conexion();

            $cid_local = $conexion->setearDnsBaseName($nroSucurs);

            $cid_local = $conexion->conectar('');


            if(!$cid_local){

                $sql = "SELECT 
                            CAST(A.FECHA_MOV AS DATE) AS FECHA,
                            A.T_COMP, 
                            A.N_COMP AS REMITO, 
                            B.DESC_SUCURSAL AS DESTINO 
                        FROM [LAKERBIS].LOCALES_LAKERS.DBO.CTA09 A
                        INNER JOIN [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS B 
                            ON A.SUC_DESTIN = B.NRO_SUCURSAL 
                            AND A.COD_PRO_CL = B.COD_CLIENT COLLATE Latin1_General_BIN
                        WHERE A.T_COMP = 'REM' 
                            AND A.NRO_SUCURS = ? 
                            AND A.FECHA_MOV >= DATEADD(DAY, -45, GETDATE())
                            AND A.COD_PRO_CL LIKE 'GT%'
                            AND A.N_COMP COLLATE Latin1_General_BIN NOT IN (
                                SELECT N_COMP COLLATE Latin1_General_BIN 
                                FROM RO_REMITOS_GUIA_RETIROS_SUC
                            )

                        UNION ALL

                        SELECT 
                            CAST(A.FECHA_MOV AS DATE) AS FECHA,
                            A.T_COMP, 
                            A.N_COMP AS REMITO, 
                            B.DESC_SUCURSAL AS DESTINO 
                        FROM [LAKERBIS].SUCURSALES_URUGUAY.DBO.CTA09 A
                        INNER JOIN [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS B 
                            ON A.SUC_DESTIN = B.NRO_SUCURSAL 
                            AND A.COD_PRO_CL = B.COD_CLIENT COLLATE Latin1_General_BIN
                        WHERE A.T_COMP = 'REM' 
                            AND A.NRO_SUCURS = ? 
                            AND A.FECHA_MOV >= DATEADD(DAY, -45, GETDATE())
                            AND A.COD_PRO_CL LIKE 'U%'
                            AND A.N_COMP COLLATE Latin1_General_BIN NOT IN (
                                SELECT N_COMP COLLATE Latin1_General_BIN 
                                FROM RO_REMITOS_GUIA_RETIROS_SUC
                            )

                        ORDER BY FECHA DESC, REMITO DESC;";
        
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

            }else{

                $sql ="SELECT 
                            CAST(A.FECHA_MOV AS DATE) AS FECHA,
                            A.T_COMP, 
                            A.N_COMP AS REMITO, 
                            CASE 
                                WHEN B.DESC_SUCURSAL = 'LAKERS SA' THEN 'CASA CENTRAL' 
                                WHEN B.DESC_SUCURSAL = 'TASKY S.A' THEN 'CASA CENTRAL'
                                WHEN LEFT(B.DESC_SUCURSAL, 6) = 'TASKY ' THEN SUBSTRING(B.DESC_SUCURSAL, 7, LEN(B.DESC_SUCURSAL))
                                ELSE B.DESC_SUCURSAL 
                            END AS DESTINO 
                        FROM STA14 A
                        INNER JOIN SUCURSAL B 
                            ON A.SUC_DESTIN = B.NRO_SUCURSAL
                        WHERE A.T_COMP = 'REM' 
                            AND A.FECHA_MOV >= DATEADD(DAY, -45, GETDATE())
                            AND A.COD_PRO_CL LIKE '[GU][TR]%'
                        ORDER BY A.FECHA_MOV DESC, A.N_COMP DESC;
                        ";

                $params = array($nroSucurs);

                $stmt = sqlsrv_query($cid_local, $sql, $params);

                if ($stmt === false) {
                    throw new Exception("Error en la consulta de remitos: " . print_r(sqlsrv_errors(), true));
                }

                $resultados = [];

                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $row['FECHA'] = $row['FECHA']->format('d/m/Y');
                    $resultados[] = $row;
                }

                $remitosCentral = $this->traerRemitosCentral();

                foreach ($resultados as $key => $value) {
                    if(in_array($value['REMITO'], $remitosCentral)){
                        unset($resultados[$key]);
                    }
                }

                return $resultados;

            }
        } catch (Exception $e) {
            error_log("Error en listarRemitos: " . $e->getMessage());
            return [];
        }
    }

    public function traerRemitosCentral (){

        $sql ="SELECT N_COMP COLLATE Latin1_General_BIN  as remitos
        FROM RO_REMITOS_GUIA_RETIROS_SUC";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        if ($stmt === false) {
            throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $remitos = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $remitos[] = $row['remitos'];
        }

        return $remitos;

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
        // --- PASO 1: Traer los datos del encabezado ---
        $sqlEncabezado = "
            SELECT 
                FORMAT(FECHA_REG, 'dd/MM/yyyy HH:mm') as FECHA,
                NRO_REGISTRO,
                ENTREGO,
                RECIBIO,
                CASE WHEN ENVIA_VALORES = 1 THEN 'SI' ELSE 'NO' END AS ENVIA_VALORES,
                OBSERVACIONES,
                PRECINTO AS NRO_PRECINTO,
                FIRMA
            FROM RO_ENC_GUIA_RETIROS_SUC
            WHERE NRO_REGISTRO = ? AND NRO_SUCURS = ?
        ";
        $paramsEncabezado = array($id, $nroSucurs);
        $stmtEncabezado = sqlsrv_query($this->cid_central, $sqlEncabezado, $paramsEncabezado);
        
        if ($stmtEncabezado === false) {
            throw new Exception("Error en la consulta del encabezado: " . print_r(sqlsrv_errors(), true));
        }

        $resultado = sqlsrv_fetch_array($stmtEncabezado, SQLSRV_FETCH_ASSOC);

        // Si no se encontró el encabezado, no hay nada más que hacer.
        if (!$resultado) {
            return [];
        }

        // --- PASO 2: Traer los remitos asociados (reutilizando tu propia función) ---
        // Tu clase ya tiene una función para esto, ¡usémosla!
        $remitos = $this->listarRemitosPorGuia($id, $nroSucurs);
        $resultado['remitos'] = $remitos; // Añadimos el array de remitos al resultado

        // --- PASO 3: Traer los egresos asociados ---
        require_once __DIR__.'../gasto.php'; // Incluimos la clase Gasto
        $gasto = new Gasto();
        // Llamamos a la función que ya confirmamos que existe y funciona en gasto.php
        $egresos = $gasto->listarEgresosPorGuia($id, $nroSucurs);
        $resultado['egresos'] = $egresos; // Añadimos el array de egresos al resultado

        // --- PASO 4: Devolver todo junto ---
        // Devolvemos un único array que contiene el encabezado y los subarrays de detalles
        return [$resultado];

    } catch (Exception $e) {
        error_log("Error en traerDatosGuiaRetiro: " . $e->getMessage());
        throw new Exception("Error al obtener los datos de la guía: " . $e->getMessage());
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
        // CORRECCIÓN: Volvemos a añadir el filtro por NRO_SUCURS
        $sql = "SELECT 
                    FORMAT(FECHA_REM, 'dd/MM/yyyy') AS fecha,
                    N_COMP AS remito,
                    DESTINO AS destino,
                    BULTOS AS bultos
                FROM RO_REMITOS_GUIA_RETIROS_SUC 
                WHERE NRO_REGISTRO = ? AND NRO_SUCURS = ?
                ORDER BY N_COMP";

        // CORRECCIÓN: Volvemos a usar ambos parámetros
        $params = array($id, $nroSucurs);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("Error en la consulta de remitos por guía: " . print_r(sqlsrv_errors(), true));
        }

        $resultados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        return $resultados;
    } catch (Exception $e) {
        error_log("Error en listarRemitosPorGuia: " . $e->getMessage());
        return [];
    }
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
        // ========================================================================
    // === NUEVOS MÉTODOS AÑADIDOS PARA LAS CORRECCIONES =====================
    // ========================================================================

    /**
     * Devuelve el objeto de conexión a la base de datos.
     * Necesario para que el controlador pueda manejar transacciones.
     * @return resource La conexión a la base de datos.
     */
    public function getConexion() {
        return $this->cid_central;
    }

    /**
     * Inserta múltiples remitos en la base de datos en una sola operación (Batch Insert).
     * Es mucho más eficiente que insertar uno por uno.
     *
     * @param string $nroRegistro El número de registro al que pertenecen los remitos.
     * @param array $remitos Un array de objetos de remito.
     * @param int $nroSucursal El número de la sucursal.
     * @return bool True si la operación fue exitosa.
     * @throws Exception Si ocurre un error en la base de datos.
     */
    public function insertarMultiplesRemitos($nroRegistro, $remitos, $nroSucursal) {
        if (empty($remitos)) {
            return true;
        }

        try {
            $sql = "INSERT INTO RO_REMITOS_GUIA_RETIROS_SUC (NRO_REGISTRO, FECHA_REM, N_COMP, DESTINO, BULTOS, NRO_SUCURS) VALUES ";
            
            $params = [];
            $valuePlaceholders = [];

            foreach ($remitos as $remito) {
                // Preparamos los placeholders (?, ?, ?, ?, ?, ?) para la consulta.
                $valuePlaceholders[] = "(?, ?, ?, ?, ?, ?)";
                
                // Convertimos la fecha del formato d/m/Y a Y-m-d para la base de datos.
                $fechaObj = DateTime::createFromFormat('d/m/Y', $remito['fecha']);
                $fechaConvertida = $fechaObj ? $fechaObj->format('Y-m-d') : null;
                
                // Agregamos todos los valores al array de parámetros.
                $params[] = $nroRegistro;
                $params[] = $fechaConvertida;
                $params[] = $remito['remito'];
                $params[] = $remito['destino'];
                $params[] = $remito['bultos'];
                $params[] = $nroSucursal;
            }

            // Unimos los placeholders: (?, ?, ?, ?, ?, ?), (?, ?, ?, ?, ?, ?), ...
            $sql .= implode(', ', $valuePlaceholders);

            $stmt = sqlsrv_query($this->cid_central, $sql, $params);

            if ($stmt === false) {
                // Si falla, lanzamos una excepción para que la transacción haga rollback.
                throw new Exception("Error en la inserción múltiple de remitos: " . print_r(sqlsrv_errors(), true));
            }

            return true;
        } catch (Exception $e) {
            error_log("Error en insertarMultiplesRemitos: " . $e->getMessage());
            // Relanzamos la excepción para que el controlador la capture.
            throw $e;
        }
    }
}

?>

