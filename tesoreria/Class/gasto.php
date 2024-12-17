
<?php

class Gasto
{
    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 

    public function traerGastos($desde, $hasta)
    {

  
        $sql = "SELECT * FROM RO_V_GASTOS_TESORERIA WHERE FECHA BETWEEN '$desde' AND '$hasta'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try{
            
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

    private $directorioFotos = '../../image/gastosTesoreria/';

    public function subirFotos($codComp, $nComp, $codCta, $fotos) {
        $resultados = [];
        $numFoto = $this->obtenerUltimoNumeroFoto($codComp, $nComp, $codCta) + 1;
    
        if (!file_exists($this->directorioFotos)) {
            mkdir($this->directorioFotos, 0777, true);
        }
    
        foreach ($fotos['tmp_name'] as $key => $tmpName) {
            $extension = strtolower(pathinfo($fotos['name'][$key], PATHINFO_EXTENSION));
            $nombreArchivo = $codComp . '_' . $nComp . '_' . $codCta . '_' . $numFoto . '.' . $extension;
            $rutaCompleta = $this->directorioFotos . $nombreArchivo;
    
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'])) {
                if (move_uploaded_file($tmpName, $rutaCompleta)) {
                    $resultados[] = "Archivo $nombreArchivo subido con exito.";
                    $numFoto++;
                } else {
                    $resultados[] = "Error al subir el archivo $nombreArchivo: " . error_get_last()['message'];
                }
            } else {
                $resultados[] = "Tipo de archivo no permitido: $extension";
            }
        }
    
        return $resultados;
    }
    

    public function obtenerFotos($codComp, $nComp, $codCta) {
        $patron = $codComp . '_' . $nComp . '_' . $codCta . '_*.*';
        $archivos = glob($this->directorioFotos . $patron);
        return array_map('basename', $archivos);
    }


    private function obtenerUltimoNumeroFoto($codComp, $nComp, $codCta) {
        $patron = $codComp . '_' . $nComp . '_' . $codCta . '_*.*';
        $archivos = glob($this->directorioFotos . $patron);
        $ultimoNumero = 0;
    
        foreach ($archivos as $archivo) {
            $partes = explode('_', basename($archivo));
            $numero = (int)pathinfo($partes[3], PATHINFO_FILENAME);
            if ($numero > $ultimoNumero) {
                $ultimoNumero = $numero;
            }
        }
    
        return $ultimoNumero;
    }

    public function eliminarFoto($foto, $codComp, $nComp) {
        $rutaFoto = $this->directorioFotos . $foto;
        if (file_exists($rutaFoto) && unlink($rutaFoto)) {
            return ['success' => true];
        } else {
            return ['success' => false];
        }
    }

    public function verificarEnVista($codComp, $nComp, $codCta) {
        $sql = "SELECT TOP 1 * FROM RO_V_GASTOS_TESORERIA WHERE COD_COMP = ? AND N_COMP = ? AND COD_CTA = ?";
        $params = array($codComp, $nComp, $codCta);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            error_log("Error al verificar en vista: " . print_r(sqlsrv_errors(), true));
            return ['success' => false, 'error' => 'Error al consultar la vista'];
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row) {
            error_log("Registro encontrado en la vista: " . print_r($row, true));
            return ['success' => true, 'data' => $row];
        } else {
            error_log("Registro no encontrado en la vista para COD_COMP=$codComp, N_COMP=$nComp, COD_CTA=$codCta");
            return ['success' => false, 'error' => 'Registro no encontrado en la vista'];
        }
    }

    public function guardarRegistro($codComp, $nComp, $codCta) {
        error_log("Intentando guardar registro: COD_COMP=$codComp, N_COMP=$nComp, COD_CTA=$codCta");
    
        // Verificar si ya existe en la tabla de destino
        $existeEnTabla = $this->verificarRegistroGuardado($codComp, $nComp, $codCta);
        if ($existeEnTabla) {
            return ['success' => false, 'error' => 'El registro ya existe en la tabla de destino'];
        }
    
        // Verificar en la vista
        $resultadoVista = $this->verificarEnVista($codComp, $nComp, $codCta);
        if (!$resultadoVista['success']) {
            return $resultadoVista;  // Devuelve el error si no se encuentra en la vista
        }
    
        // Si llegamos aquí, el registro existe en la vista pero no en la tabla de destino
        $datosVista = $resultadoVista['data'];
    
        // Preparar la consulta de inserción
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
            $errors = sqlsrv_errors();
            error_log("Error al guardar registro: " . print_r($errors, true));
            return ['success' => false, 'error' => $errors];
        }
        
        $rowsAffected = sqlsrv_rows_affected($stmt);
        error_log("Filas afectadas: $rowsAffected");
    
        if ($rowsAffected > 0) {
            error_log("Registro guardado exitosamente");
            return ['success' => true];
        } else {
            error_log("No se insertó ningún registro por razones desconocidas");
            return ['success' => false, 'error' => 'No se pudo insertar el registro por razones desconocidas'];
        }
    }

    public function verificarEstado($codComp, $nComp, $codCta) {
        $tieneFotos = count($this->obtenerFotos($codComp, $nComp, $codCta)) > 0;
        $estaGuardado = $this->verificarRegistroGuardado($codComp, $nComp, $codCta);
        
        return [
            'tieneFotos' => $tieneFotos,
            'estaGuardado' => $estaGuardado
        ];
    }
    
    public function verificarRegistroGuardado($codComp, $nComp, $codCta) {
        $sql = "SELECT COUNT(*) as count FROM RO_T_GASTOS_TESORERIA WHERE COD_COMP = ? AND LTRIM(N_COMP) = ? AND COD_CTA = ?";
        $stmt = sqlsrv_query($this->cid_central, $sql, array($codComp, $nComp, $codCta));
        if ($stmt === false) {
            return false;
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['count'] > 0;
    }

}
