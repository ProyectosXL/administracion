<?php

class Gasto
{
    private $cid_central;
    private $directorioFotos;

    function __construct(){
        try {
            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            $this->cid_central = $cid->conectar('central');
            
            // Validar que la conexión se estableció correctamente
            if (!$this->cid_central) {
                error_log("Error: No se pudo establecer conexión a la base de datos central");
                throw new Exception("Error de conexión a base de datos");
            }
            
            // Ruta relativa corregida - desde tesoreria/Class/ ir a administracion/image/gastosTesoreria/
            $this->directorioFotos = __DIR__.'/../../image/gastosTesoreria/';
            
        } catch (Exception $e) {
            error_log("Error en constructor de Gasto: " . $e->getMessage());
            throw $e;
        }
    } 

    public function traerGastos($desde, $hasta)
    {
        $sql = "SELECT * FROM RO_V_GASTOS_TESORERIA WHERE FECHA BETWEEN ? AND ? ORDER BY FECHA DESC";
        $stmt = sqlsrv_query($this->cid_central, $sql, array($desde, $hasta));

        try{
            $rows = array();
            while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $v;
            }
            return $rows;
        } catch (\Throwable $th){
            print_r($th);
        }
    }

    public function subirFotos($codComp, $nComp, $codCta, $fotos) {
        try {
            $codComp = trim($codComp);
            $nComp = trim($nComp);
            $codCta = trim($codCta);

            error_log("=== INICIO SUBIR FOTOS ===");
            error_log("Parámetros: $codComp, $nComp, $codCta");
            error_log("Directorio: " . $this->directorioFotos);
            error_log("Directorio absoluto: " . realpath($this->directorioFotos));

            // Validar que se recibieron archivos
            if (!isset($fotos['tmp_name']) || !is_array($fotos['tmp_name'])) {
                error_log("Error: No se recibieron archivos válidos");
                return ['success' => false, 'error' => 'No se recibieron archivos válidos'];
            }

            $resultados = [];
            $numFoto = $this->obtenerUltimoNumeroFoto($codComp, $nComp, $codCta) + 1;
            $archivosSubidos = 0;

            // Crear directorio si no existe
            if (!file_exists($this->directorioFotos)) {
                if (!mkdir($this->directorioFotos, 0755, true)) {
                    error_log("Error: No se pudo crear el directorio: " . $this->directorioFotos);
                    return ['success' => false, 'error' => 'No se pudo crear el directorio de fotos'];
                }
                error_log("Directorio creado: " . $this->directorioFotos);
            }

            // Verificar permisos de escritura
            if (!is_writable($this->directorioFotos)) {
                error_log("Error: El directorio no tiene permisos de escritura: " . $this->directorioFotos);
                return ['success' => false, 'error' => 'El directorio no tiene permisos de escritura'];
            }

            error_log("Número de foto inicial: $numFoto");

            foreach ($fotos['tmp_name'] as $key => $tmpName) {
                error_log("Procesando archivo $key: $tmpName");
                
                // Verificar que el archivo temporal existe y no hay errores
                if (empty($tmpName) || !file_exists($tmpName)) {
                    $resultados[] = "Archivo temporal no encontrado para {$fotos['name'][$key]}";
                    error_log("Archivo temporal no encontrado: $tmpName");
                    continue;
                }

                if ($fotos['error'][$key] !== UPLOAD_ERR_OK) {
                    $error = $this->getUploadError($fotos['error'][$key]);
                    $resultados[] = "Error en el archivo {$fotos['name'][$key]}: $error";
                    error_log("Error de upload: $error");
                    continue;
                }

                $extension = strtolower(pathinfo($fotos['name'][$key], PATHINFO_EXTENSION));
                $nombreArchivo = $codComp . '_' . $nComp . '_' . $codCta . '_' . $numFoto . '.' . $extension;
                $rutaCompleta = $this->directorioFotos . $nombreArchivo;

                error_log("Extensión: $extension");
                error_log("Nombre archivo: $nombreArchivo");
                error_log("Ruta completa: $rutaCompleta");

                if (in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'])) {
                    $fileSize = filesize($tmpName);
                    error_log("Tamaño archivo original: $fileSize bytes");

                    if (move_uploaded_file($tmpName, $rutaCompleta)) {
                        if (file_exists($rutaCompleta)) {
                            $finalSize = filesize($rutaCompleta);
                            error_log("Archivo subido exitosamente. Tamaño final: $finalSize bytes");
                            $resultados[] = "Archivo $nombreArchivo subido con éxito.";
                            $numFoto++;
                            $archivosSubidos++;
                        } else {
                            error_log("El archivo no existe después de move_uploaded_file");
                            $resultados[] = "Error: El archivo no se guardó correctamente: $nombreArchivo";
                        }
                    } else {
                        $error = error_get_last();
                        $errorMsg = $error ? $error['message'] : 'Error desconocido en move_uploaded_file';
                        error_log("Error en move_uploaded_file: $errorMsg");
                        $resultados[] = "Error al subir el archivo $nombreArchivo: $errorMsg";
                    }
                } else {
                    $resultados[] = "Tipo de archivo no permitido: $extension";
                    error_log("Extensión no permitida: $extension");
                }
            }

            error_log("Archivos subidos exitosamente: $archivosSubidos");
            error_log("=== FIN SUBIR FOTOS ===");

            return [
                'success' => $archivosSubidos > 0,
                'archivos_subidos' => $archivosSubidos,
                'mensajes' => $resultados,
                'directorio' => $this->directorioFotos
            ];

        } catch (Exception $e) {
            error_log("Exception en subirFotos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'archivos_subidos' => 0
            ];
        }
    }

    private function getUploadError($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'El archivo excede el tamaño máximo permitido en php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo excede el tamaño máximo permitido en el formulario';
            case UPLOAD_ERR_PARTIAL:
                return 'El archivo se subió parcialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'No se subió ningún archivo';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Falta la carpeta temporal';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Error al escribir el archivo en disco';
            case UPLOAD_ERR_EXTENSION:
                return 'Subida detenida por extensión';
            default:
                return 'Error desconocido';
        }
    }
    
    public function obtenerFotos($codComp, $nComp, $codCta) {
        try {
            $codComp = trim($codComp);
            $nComp = trim($nComp);
            $codCta = trim($codCta);
            
            $patron = $codComp . '_' . $nComp . '_' . $codCta . '_*.*';
            $rutaBusqueda = $this->directorioFotos . $patron;
            
            error_log("Buscando fotos con patrón: $rutaBusqueda");
            
            $archivos = glob($rutaBusqueda);
            
            if ($archivos === false) {
                error_log("Error en glob() para patrón: $rutaBusqueda");
                return [];
            }
            
            $fotosEncontradas = array_map('basename', $archivos);
            
            // Verificar que los archivos realmente existan y tengan contenido válido
            $fotosValidas = [];
            foreach ($fotosEncontradas as $foto) {
                $rutaCompleta = $this->directorioFotos . $foto;
                if (file_exists($rutaCompleta)) {
                    $tamaño = filesize($rutaCompleta);
                    // Archivo debe existir y tener al menos 100 bytes (no estar vacío o corrupto)
                    if ($tamaño > 100) {
                        $fotosValidas[] = $foto;
                        error_log("Foto válida: $foto (Tamaño: $tamaño bytes)");
                    } else {
                        error_log("Foto inválida (muy pequeña): $foto (Tamaño: $tamaño bytes)");
                        // Eliminar archivos corruptos o vacíos
                        unlink($rutaCompleta);
                        error_log("Archivo corrupto eliminado: $foto");
                    }
                }
            }
            
            error_log("Fotos encontradas: " . count($fotosEncontradas) . ", Fotos válidas: " . count($fotosValidas));
            
            return $fotosValidas;
            
        } catch (Exception $e) {
            error_log("Error en obtenerFotos: " . $e->getMessage());
            return [];
        }
    }

    private function obtenerUltimoNumeroFoto($codComp, $nComp, $codCta) {
        try {
            $codComp = trim($codComp);
            $nComp = trim($nComp);
            $codCta = trim($codCta);
            
            $patron = $codComp . '_' . $nComp . '_' . $codCta . '_*.*';
            $archivos = glob($this->directorioFotos . $patron);
            
            if ($archivos === false) {
                return 0;
            }
            
            $ultimoNumero = 0;

            foreach ($archivos as $archivo) {
                $nombreArchivo = basename($archivo);
                $partes = explode('_', $nombreArchivo);
                
                if (count($partes) >= 4) {
                    $numeroConExtension = $partes[3];
                    $numero = (int)pathinfo($numeroConExtension, PATHINFO_FILENAME);
                    
                    if ($numero > $ultimoNumero) {
                        $ultimoNumero = $numero;
                    }
                }
            }

            return $ultimoNumero;
            
        } catch (Exception $e) {
            error_log("Error en obtenerUltimoNumeroFoto: " . $e->getMessage());
            return 0;
        }
    }

    public function eliminarFoto($foto, $codComp, $nComp) {
        $rutaFoto = $this->directorioFotos . trim($foto);
        
        error_log("Intentando eliminar foto: $rutaFoto");
        
        if (file_exists($rutaFoto)) {
            if (unlink($rutaFoto)) {
                error_log("Foto eliminada exitosamente: $rutaFoto");
                
                // Verificar si quedan más fotos válidas para este registro
                $codCta = $this->extraerCodCtaDeNombreArchivo($foto);
                if ($codCta) {
                    $fotosRestantes = $this->obtenerFotos($codComp, $nComp, $codCta);
                    error_log("Fotos restantes después de eliminar: " . count($fotosRestantes));
                }
                
                return ['success' => true, 'mensaje' => 'Archivo eliminado correctamente'];
            } else {
                error_log("No se pudo eliminar el archivo: $rutaFoto");
                return ['success' => false, 'error' => 'No se pudo eliminar el archivo.'];
            }
        } else {
            error_log("Archivo no encontrado: $rutaFoto");
            return ['success' => false, 'error' => 'El archivo no existe.'];
        }
    }

    // Método auxiliar para extraer COD_CTA del nombre del archivo
    private function extraerCodCtaDeNombreArchivo($nombreArchivo) {
        $partes = explode('_', $nombreArchivo);
        return (count($partes) >= 3) ? $partes[2] : null;
    }

    public function verificarEnVista($codComp, $nComp, $codCta) {
        $sql = "SELECT TOP 1 * FROM RO_V_GASTOS_TESORERIA WHERE COD_COMP = ? AND N_COMP = ? AND COD_CTA = ?";
        $params = array(trim($codComp), trim($nComp), trim($codCta));
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            return ['success' => false, 'error' => 'Error al consultar la vista'];
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? ['success' => true, 'data' => $row] : ['success' => false, 'error' => 'Registro no encontrado en la vista'];
    }

    public function guardarRegistro($codComp, $nComp, $codCta) {
        $codComp = trim($codComp);
        $nComp = trim($nComp);
        $codCta = trim($codCta);

        if ($this->verificarRegistroGuardado($codComp, $nComp, $codCta)) {
            return ['success' => false, 'error' => 'El registro ya existe en la tabla de destino'];
        }
    
        $resultadoVista = $this->verificarEnVista($codComp, $nComp, $codCta);
        if (!$resultadoVista['success']) {
            return $resultadoVista;
        }
    
        $datosVista = $resultadoVista['data'];
    
        $sql = "INSERT INTO RO_T_GASTOS_TESORERIA (COD_COMP, N_COMP, COD_CTA, FECHA, DESC_CUENTA, MONTO, USUARIO, LEYENDA, FECHA_CARGA)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE())";
        
        $params = array(
            $datosVista['COD_COMP'],
            $datosVista['N_COMP'],
            $datosVista['COD_CTA'],
            $datosVista['FECHA'],
            $datosVista['DESC_CUENTA'],
            $datosVista['MONTO'],
            $datosVista['USUARIO'],
            $datosVista['LEYENDA']
        );
    
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            return ['success' => false, 'error' => sqlsrv_errors()];
        }
        
        return (sqlsrv_rows_affected($stmt) > 0) ? ['success' => true] : ['success' => false, 'error' => 'No se pudo insertar el registro'];
    }

    public function verificarEstado($codComp, $nComp, $codCta) {
        try {
            if (!$this->cid_central) {
                throw new Exception("No hay conexión a base de datos");
            }
            
            $fotos = $this->obtenerFotos($codComp, $nComp, $codCta);
            $tieneFotos = false;
            
            if (count($fotos) > 0) {
                foreach ($fotos as $foto) {
                    $rutaCompleta = $this->directorioFotos . $foto;
                    if (file_exists($rutaCompleta) && filesize($rutaCompleta) > 0) {
                        $tieneFotos = true;
                        break;
                    }
                }
            }
            
            $estaGuardado = $this->verificarRegistroGuardado($codComp, $nComp, $codCta);
            
            return [
                'tieneFotos' => $tieneFotos,
                'estaGuardado' => $estaGuardado,
                'numeroFotos' => count($fotos)
            ];
        } catch (Exception $e) {
            error_log("Error en verificarEstado: " . $e->getMessage());
            return [
                'tieneFotos' => false,
                'estaGuardado' => false,
                'numeroFotos' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function verificarRegistroGuardado($codComp, $nComp, $codCta) {
        try {
            if (!$this->cid_central) {
                throw new Exception("No hay conexión a base de datos");
            }
            
            $sql = "SELECT COUNT(*) as count FROM RO_T_GASTOS_TESORERIA WHERE COD_COMP = ? AND LTRIM(RTRIM(N_COMP)) = ? AND COD_CTA = ?";
            $params = array(trim($codComp), trim($nComp), trim($codCta));
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en verificarRegistroGuardado: " . print_r($errors, true));
                return false;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['count'] > 0;
        } catch (Exception $e) {
            error_log("Error en verificarRegistroGuardado: " . $e->getMessage());
            return false;
        }
    }

    public function listarEgresosEfectivo($nroSucurs) {
        try {
            require_once __DIR__.'/../../Class/conexion.php';
            $conexion = new Conexion();
            $cid_local = $conexion->setearDnsBaseName($nroSucurs);
            $cid_local = $conexion->conectar('');

            if(!$cid_local){
                $sql = "SELECT CAST(FECHA AS DATE) FECHA, COD_COMP, N_COMP, CANT_MONE FROM [LAKERBIS].LOCALES_LAKERS.DBO.CTA29
                        WHERE COD_CTA = '100100' AND NRO_SUCURS = ? AND FECHA >= DATEADD(day, -45, GETDATE()) AND D_H = 'D'
                        AND N_COMP COLLATE Latin1_General_BIN NOT IN (SELECT N_COMP COLLATE Latin1_General_BIN FROM RO_EGRESOS_GUIA_RETIROS_SUC WHERE NRO_SUCURS = ?)
                        ORDER BY N_COMP DESC";

                $params = array($nroSucurs, $nroSucurs);
                $stmt = sqlsrv_query($this->cid_central, $sql, $params);

                if ($stmt === false) {
                    throw new Exception("Error en la consulta de remitos: " . print_r(sqlsrv_errors(), true));
                }

                $resultados = [];
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $row['FECHA'] = $row['FECHA']->format('d/m/Y');
                    $resultados[] = $row;
                }

            }else{
                $sql = "SELECT CAST(FECHA AS DATE) FECHA, COD_COMP, N_COMP, CANT_MONE FROM SBA05
                WHERE COD_CTA = '100100' AND FECHA >= DATEADD(day, -45, GETDATE()) AND D_H = 'D'
                ORDER BY N_COMP DESC";

                $stmt = sqlsrv_query($cid_local, $sql);

                if ($stmt === false) {
                    throw new Exception("Error en la consulta de remitos: " . print_r(sqlsrv_errors(), true));
                }

                $resultados = [];

                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $row['FECHA'] = $row['FECHA']->format('d/m/Y');
                    $resultados[] = $row;
                }

                $remitosCentral = $this->traerEgresosCentral($nroSucurs);

                foreach ($resultados as $key => $value) {
                    if(in_array($value['N_COMP'], $remitosCentral)){
                        unset($resultados[$key]);
                    }
                }

                return $resultados;
            }

            return $resultados;
        } catch (Exception $e) {
            error_log("Error en listarEgresos: " . $e->getMessage());
            return [];
        }
    }

    public function traerEgresosCentral ($nroSucurs) {
        $sql = "SELECT N_COMP COLLATE Latin1_General_BIN as remitos FROM RO_EGRESOS_GUIA_RETIROS_SUC WHERE NRO_SUCURS = ?";
        $params = array($nroSucurs);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        if ($stmt === false) {
            throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $remitos = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $remitos[] = $row['remitos'];
        }

        return $remitos;
    }

    public function limpiarEgresos($nroRegistro, $nroSucurs){
        try {
            $sql = "DELETE FROM RO_EGRESOS_GUIA_RETIROS_SUC WHERE NRO_REGISTRO = ? AND NRO_SUCURS = ?";
            $params = [$nroRegistro, $nroSucurs];
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
        public function eliminarGasto($codComp, $nComp, $codCta) {
        $codComp = trim($codComp);
        $nComp = trim($nComp);
        $codCta = trim($codCta);

        try {
            // 1. Obtener y eliminar todos los archivos asociados
            $fotosAEliminar = $this->obtenerFotos($codComp, $nComp, $codCta);
            error_log("Revirtiendo gasto: $codComp, $nComp, $codCta. Fotos a eliminar: " . count($fotosAEliminar));

            foreach ($fotosAEliminar as $foto) {
                $rutaFoto = $this->directorioFotos . $foto;
                if (file_exists($rutaFoto)) {
                    if (!unlink($rutaFoto)) {
                        // Si falla la eliminación de un archivo, detenemos la operación
                        error_log("Error al eliminar el archivo: $rutaFoto. Permisos insuficientes?");
                        return ['success' => false, 'error' => "No se pudo eliminar el archivo: $foto. Verifique los permisos."];
                    }
                }
            }
            error_log("Todos los archivos asociados han sido eliminados.");

            // 2. Eliminar el registro de la tabla de seguimiento
            $sql = "DELETE FROM RO_T_GASTOS_TESORERIA WHERE COD_COMP = ? AND LTRIM(RTRIM(N_COMP)) = ? AND COD_CTA = ?";
            $params = array($codComp, $nComp, $codCta);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error al eliminar registro de RO_T_GASTOS_TESORERIA: " . print_r($errors, true));
                return ['success' => false, 'error' => 'Error al revertir el registro en la base de datos.'];
            }
            
            error_log("Gasto revertido a estado pendiente exitosamente.");
            return ['success' => true, 'mensaje' => 'Fotos eliminadas y estado revertido correctamente.'];

        } catch (Exception $e) {
            error_log("Exception en eliminarGasto/revertir: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
        // CORRECCIÓN: Nuevo método para inserción por lotes (Batch Insert).
    public function insertarMultiplesEgresos($nroRegistro, $egresos, $nroSucursal) {
        if (empty($egresos)) {
            return true;
        }

        try {
            $sql = "INSERT INTO RO_EGRESOS_GUIA_RETIROS_SUC (NRO_REGISTRO, FECHA_COMP, T_COMP, N_COMP, NRO_SUCURS) VALUES ";
            
            $params = [];
            $valuePlaceholders = [];

            foreach ($egresos as $egreso) {
                // Preparamos los placeholders para la consulta preparada
                $valuePlaceholders[] = "(?, ?, ?, ?, ?)";
                
                // Formateamos la fecha y la agregamos a los parámetros
                $fechaObj = DateTime::createFromFormat('d/m/Y', $egreso['fecha']);
                $fechaConvertida = $fechaObj ? $fechaObj->format('Y-m-d') : null;
                
                // Agregamos todos los valores al array de parámetros
                $params[] = $nroRegistro;
                $params[] = $fechaConvertida;
                $params[] = $egreso['tipo'];
                $params[] = $egreso['comprobante'];
                $params[] = $nroSucursal;
            }

            // Unimos todos los placeholders: (?, ?, ?, ?, ?), (?, ?, ?, ?, ?), ...
            $sql .= implode(', ', $valuePlaceholders);

            $stmt = sqlsrv_query($this->cid_central, $sql, $params);

            if ($stmt === false) {
                throw new Exception("Error en la consulta de inserción múltiple de egresos: " . print_r(sqlsrv_errors(), true));
            }

            return true;
        } catch (Exception $e) {
            error_log("Error en insertarMultiplesEgresos: " . $e->getMessage());
            // Relanzamos la excepción para que la transacción la capture
            throw $e; 
        }
    }
}
?>