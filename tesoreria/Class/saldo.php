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
            // Ejecutar SP original para obtener movimientos base
            $sql = "EXEC RO_SP_MAYOR_CTA_100101 ?, ?";
            $params = array($desde, $hasta);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $movimientos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Obtener información de control si existe ID_SBA05
                if (isset($row['ID_SBA05']) && $row['ID_SBA05']) {
                    $controlInfo = $this->obtenerControlMovimiento($row['ID_SBA05']);
                    $row['CONTROLADO'] = $controlInfo['CONTROLADO'];
                    $row['FECHA_CONTROL'] = $controlInfo['FECHA_CONTROL'];
                } else {
                    $row['CONTROLADO'] = 0;
                    $row['FECHA_CONTROL'] = null;
                }
                
                // Agregar información de fotos para egresos
                if ($row['TIPO'] === 'EGRESO' && $row['COD_COMP'] !== 'SALDO_INI' && $row['N_COMP']) {
                    $infoFotos = $this->obtenerInfoFotosEgreso($row['COD_COMP'], $row['N_COMP']);
                    $row['TIENE_FOTOS'] = $infoFotos['tiene_fotos'];
                    $row['COD_CTA_CONTRAPARTIDA'] = $infoFotos['cod_cta'];
                    $row['ESTA_GUARDADO'] = $infoFotos['esta_guardado'];
                } else {
                    $row['TIENE_FOTOS'] = false;
                    $row['COD_CTA_CONTRAPARTIDA'] = null;
                    $row['ESTA_GUARDADO'] = false;
                }
                
                $movimientos[] = $row;
            }
            
            return $movimientos;
            
        } catch (Exception $e) {
            error_log("Error en obtenerMovimientosCaja: " . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerInfoFotosEgreso($codComp, $nComp)
    {
        try {
            // Buscar en gastos guardados para obtener el COD_CTA
            $sql = "SELECT COD_CTA FROM RO_T_GASTOS_TESORERIA 
                    WHERE COD_COMP = ? AND LTRIM(RTRIM(N_COMP)) = ?";
            
            $params = array(trim($codComp), trim($nComp));
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error en consulta de gastos guardados: " . print_r(sqlsrv_errors(), true));
                return ['tiene_fotos' => false, 'cod_cta' => null, 'esta_guardado' => false];
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($row) {
                $codCta = $row['COD_CTA'];
                $fotos = $this->verificarFotosEgreso($codComp, $nComp, $codCta);
                
                return [
                    'tiene_fotos' => count($fotos) > 0,
                    'cod_cta' => $codCta,
                    'esta_guardado' => true,
                    'archivos' => $fotos
                ];
            } else {
                return ['tiene_fotos' => false, 'cod_cta' => null, 'esta_guardado' => false];
            }
            
        } catch (Exception $e) {
            error_log("Error en obtenerInfoFotosEgreso: " . $e->getMessage());
            return ['tiene_fotos' => false, 'cod_cta' => null, 'esta_guardado' => false];
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
            $sql = "SET DATEFORMAT YMD
                    SELECT 
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

    public function obtenerUltimaFechaControl($desde, $hasta)
    {
        try {
            // Por ahora, voy a usar un enfoque más directo
            // Buscar directamente en los datos ya obtenidos por obtenerMovimientosCaja
            
            error_log("obtenerUltimaFechaControl - Iniciando consulta - Rango: $desde a $hasta");
            
            // Obtener los movimientos del período y buscar la fecha de control más reciente
            $movimientos = $this->obtenerMovimientosCaja($desde, $hasta);
            
            $ultimaFechaControl = null;
            
            foreach ($movimientos as $mov) {
                if (isset($mov['CONTROLADO']) && $mov['CONTROLADO'] && 
                    isset($mov['FECHA_CONTROL']) && $mov['FECHA_CONTROL']) {
                    
                    if (!$ultimaFechaControl || $mov['FECHA_CONTROL'] > $ultimaFechaControl) {
                        $ultimaFechaControl = $mov['FECHA_CONTROL'];
                    }
                }
            }
            
            error_log("obtenerUltimaFechaControl - Total movimientos en rango: " . count($movimientos));
            error_log("obtenerUltimaFechaControl - Resultado: " . ($ultimaFechaControl ? $ultimaFechaControl->format('Y-m-d H:i:s') : 'NULL'));
            
            return $ultimaFechaControl;
            
        } catch (Exception $e) {
            error_log("Error en obtenerUltimaFechaControl: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerControlMovimiento($idSba05)
    {
        try {
            if (!$this->cid_central) {
                return ['CONTROLADO' => 0, 'FECHA_CONTROL' => null];
            }
            
            $sql = "SELECT CONTROLADO, FECHA_CONTROL FROM RO_T_MOV_CAJA_TESORERIA WHERE ID_SBA05 = ?";
            $stmt = sqlsrv_query($this->cid_central, $sql, array($idSba05));
            
            if ($stmt === false) {
                error_log("Error en obtenerControlMovimiento: " . print_r(sqlsrv_errors(), true));
                return ['CONTROLADO' => 0, 'FECHA_CONTROL' => null];
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($row) {
                return [
                    'CONTROLADO' => $row['CONTROLADO'],
                    'FECHA_CONTROL' => $row['FECHA_CONTROL']
                ];
            } else {
                return ['CONTROLADO' => 0, 'FECHA_CONTROL' => null];
            }
            
        } catch (Exception $e) {
            error_log("Error en obtenerControlMovimiento: " . $e->getMessage());
            return ['CONTROLADO' => 0, 'FECHA_CONTROL' => null];
        }
    }

    public function actualizarControlMovimiento($idSba05, $controlado)
    {
        try {
            if (!$this->cid_central) {
                throw new Exception("No hay conexión a base de datos");
            }
            
            // Verificar si ya existe el registro
            $sqlCheck = "SELECT COUNT(*) as count FROM RO_T_MOV_CAJA_TESORERIA WHERE ID_SBA05 = ?";
            $stmtCheck = sqlsrv_query($this->cid_central, $sqlCheck, array($idSba05));
            
            if ($stmtCheck === false) {
                throw new Exception("Error verificando registro: " . print_r(sqlsrv_errors(), true));
            }
            
            $row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
            $existe = $row['count'] > 0;
            
            if ($existe) {
                // Actualizar registro existente
                $sql = "UPDATE RO_T_MOV_CAJA_TESORERIA 
                        SET CONTROLADO = ?, FECHA_CONTROL = GETDATE() 
                        WHERE ID_SBA05 = ?";
                $params = array($controlado ? 1 : 0, $idSba05);
            } else {
                // Insertar nuevo registro
                $sql = "INSERT INTO RO_T_MOV_CAJA_TESORERIA (ID_SBA05, CONTROLADO, FECHA_CONTROL) 
                        VALUES (?, ?, GETDATE())";
                $params = array($idSba05, $controlado ? 1 : 0);
            }
            
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error actualizando control: " . print_r(sqlsrv_errors(), true));
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error en actualizarControlMovimiento: " . $e->getMessage());
            throw $e;
        }
    }

    public function actualizarControlMasivo($idsSba05, $controlado)
    {
        try {
            if (!$this->cid_central || empty($idsSba05)) {
                throw new Exception("Parámetros inválidos");
            }
            
            $exitos = 0;
            foreach ($idsSba05 as $idSba05) {
                try {
                    $this->actualizarControlMovimiento($idSba05, $controlado);
                    $exitos++;
                } catch (Exception $e) {
                    error_log("Error actualizando ID $idSba05: " . $e->getMessage());
                }
            }
            
            return $exitos;
            
        } catch (Exception $e) {
            error_log("Error en actualizarControlMasivo: " . $e->getMessage());
            throw $e;
        }
    }
}
?>