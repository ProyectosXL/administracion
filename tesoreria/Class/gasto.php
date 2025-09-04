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

    private $directorioFotos = __DIR__.'/../../../image/gastosTesoreria/';

    public function subirFotos($codComp, $nComp, $codCta, $fotos) {
        $codComp = trim($codComp);
        $nComp = trim($nComp);
        $codCta = trim($codCta);

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
        $codComp = trim($codComp);
        $nComp = trim($nComp);
        $codCta = trim($codCta);
        $patron = $codComp . '_' . $nComp . '_' . $codCta . '_*.*';
        $archivos = glob($this->directorioFotos . $patron);
        return array_map('basename', $archivos);
    }

    private function obtenerUltimoNumeroFoto($codComp, $nComp, $codCta) {
        $codComp = trim($codComp);
        $nComp = trim($nComp);
        $codCta = trim($codCta);
        $patron = $codComp . '_' . $nComp . '_' . $codCta . '_*.*';
        $archivos = glob($this->directorioFotos . $patron);
        $ultimoNumero = 0;
    
        foreach ($archivos as $archivo) {
            $partes = explode('_', basename($archivo));
            if (isset($partes[3])) {
                $numero = (int)pathinfo($partes[3], PATHINFO_FILENAME);
                if ($numero > $ultimoNumero) {
                    $ultimoNumero = $numero;
                }
            }
        }
    
        return $ultimoNumero;
    }

    public function eliminarFoto($foto, $codComp, $nComp) {
        $rutaFoto = $this->directorioFotos . trim($foto);
        if (file_exists($rutaFoto) && unlink($rutaFoto)) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'No se pudo encontrar o eliminar el archivo.'];
        }
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
        $tieneFotos = count($this->obtenerFotos($codComp, $nComp, $codCta)) > 0;
        $estaGuardado = $this->verificarRegistroGuardado($codComp, $nComp, $codCta);
        
        return [
            'tieneFotos' => $tieneFotos,
            'estaGuardado' => $estaGuardado
        ];
    }
    
    public function verificarRegistroGuardado($codComp, $nComp, $codCta) {
        $sql = "SELECT COUNT(*) as count FROM RO_T_GASTOS_TESORERIA WHERE COD_COMP = ? AND LTRIM(RTRIM(N_COMP)) = ? AND COD_CTA = ?";
        $params = array(trim($codComp), trim($nComp), trim($codCta));
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        if ($stmt === false) {
            return false;
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['count'] > 0;
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

        $sql = "SELECT N_COMP COLLATE Latin1_General_BIN as remitos FROM RO_EGRESOS_GUIA_RETIROS_SUC WHERE NRO_SUCURS = $nroSucurs";
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
}
?>
