
<?php

class Saldo
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
            error_log("Error en constructor de Saldo: " . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerMovimientosCaja($desde, $hasta)
    {
        try {
            $sql = "EXEC RO_SP_MAYOR_CTA_100101 ?, ?";
            $params = array($desde, $hasta);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $movimientos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $movimientos[] = $row;
            }
            
            return $movimientos;
            
        } catch (Exception $e) {
            error_log("Error en obtenerMovimientosCaja: " . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerSaldoActual($hasta)
    {
        try {
            $sql = "SELECT 
                        COALESCE(SUM(CASE WHEN D_H = 'D' THEN MONTO ELSE -MONTO END), 0.00) AS SALDO
                    FROM SBA05
                    WHERE COD_CTA = '100101'
                      AND FECHA <= ?";
            
            $params = array($hasta);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta de saldo: " . print_r(sqlsrv_errors(), true));
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row ? $row['SALDO'] : 0;
            
        } catch (Exception $e) {
            error_log("Error en obtenerSaldoActual: " . $e->getMessage());
            return 0;
        }
    }

    public function verificarFotosEgreso($codComp, $nComp, $codCta = '100101')
    {
        try {
            $codComp = trim($codComp);
            $nComp = trim($nComp);
            $codCta = trim($codCta);
            
            $patron = $codComp . '_' . $nComp . '_' . $codCta . '_*.*';
            $rutaBusqueda = $this->directorioFotos . $patron;
            
            $archivos = glob($rutaBusqueda);
            
            if ($archivos === false) {
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
                    }
                }
            }
            
            return $fotosValidas;
            
        } catch (Exception $e) {
            error_log("Error en verificarFotosEgreso: " . $e->getMessage());
            return [];
        }
    }

    public function verificarRegistroGuardado($codComp, $nComp, $codCta = '100101')
    {
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

    public function obtenerEstadisticasMovimientos($desde, $hasta)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as TOTAL_MOVIMIENTOS,
                        COALESCE(SUM(CASE WHEN D_H = 'D' THEN MONTO ELSE 0 END), 0) AS TOTAL_INGRESOS,
                        COALESCE(SUM(CASE WHEN D_H = 'H' THEN MONTO ELSE 0 END), 0) AS TOTAL_EGRESOS,
                        COUNT(CASE WHEN D_H = 'D' THEN 1 END) AS CANT_INGRESOS,
                        COUNT(CASE WHEN D_H = 'H' THEN 1 END) AS CANT_EGRESOS
                    FROM SBA05
                    WHERE COD_CTA = '100101'
                      AND FECHA BETWEEN ? AND ?";
            
            $params = array($desde, $hasta);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta de estadísticas: " . print_r(sqlsrv_errors(), true));
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row ? $row : [];
            
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasMovimientos: " . $e->getMessage());
            return [];
        }
    }
}
?>