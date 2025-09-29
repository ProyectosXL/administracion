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
                // Log para debug en los primeros registros
                if (count($movimientos) < 3) {
                    error_log("Registro del SP - Claves disponibles: " . implode(', ', array_keys($row)));
                    if (isset($row['ID_SBA05'])) {
                        error_log("ID_SBA05 encontrado: " . $row['ID_SBA05']);
                    } else {
                        error_log("ID_SBA05 NO encontrado en el registro");
                    }
                }
                
                // Si no tiene ID_SBA05, intentar obtenerlo desde SBA05
                if (!isset($row['ID_SBA05']) || !$row['ID_SBA05']) {
                    $row['ID_SBA05'] = $this->obtenerIdSba05($row);
                }
                
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
            
            error_log("Total movimientos obtenidos: " . count($movimientos));
            return $movimientos;
            
        } catch (Exception $e) {
            error_log("Error en obtenerMovimientosCaja: " . $e->getMessage());
            throw $e;
        }
    }

    // Método para obtener ID_SBA05 cuando no está en el SP
    private function obtenerIdSba05($movimiento)
    {
        try {
            $fecha = $movimiento['FECHA'];
            $codComp = trim($movimiento['COD_COMP'] ?? '');
            $nComp = trim($movimiento['N_COMP'] ?? '');
            $monto = $movimiento['MONTO'];
            
            error_log("obtenerIdSba05 - Buscando: Fecha={$fecha->format('Y-m-d')}, COD_COMP='$codComp', N_COMP='$nComp', MONTO=$monto");
            
            // Primero intentar búsqueda exacta
            $sql = "SELECT TOP 1 ID_SBA05 FROM SBA05 
                    WHERE COD_CTA = '100101' 
                      AND FECHA = ? 
                      AND COD_COMP = ? 
                      AND LTRIM(RTRIM(N_COMP)) = ? 
                      AND ABS(MONTO - ?) < 0.01
                    ORDER BY ID_SBA05 DESC";
                    
            $params = array($fecha, $codComp, $nComp, $monto);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error buscando ID_SBA05: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($row) {
                error_log("obtenerIdSba05 - ID encontrado: " . $row['ID_SBA05']);
                return $row['ID_SBA05'];
            }
            
            // Si no se encuentra, intentar búsqueda más flexible (sin N_COMP)
            $sqlFlexible = "SELECT TOP 1 ID_SBA05 FROM SBA05 
                           WHERE COD_CTA = '100101' 
                             AND FECHA = ? 
                             AND COD_COMP = ? 
                             AND ABS(MONTO - ?) < 0.01
                           ORDER BY ID_SBA05 DESC";
                           
            $paramsFlexible = array($fecha, $codComp, $monto);
            $stmtFlexible = sqlsrv_query($this->cid_central, $sqlFlexible, $paramsFlexible);
            
            if ($stmtFlexible !== false) {
                $rowFlexible = sqlsrv_fetch_array($stmtFlexible, SQLSRV_FETCH_ASSOC);
                if ($rowFlexible) {
                    error_log("obtenerIdSba05 - ID encontrado (búsqueda flexible): " . $rowFlexible['ID_SBA05']);
                    return $rowFlexible['ID_SBA05'];
                }
            }
            
            error_log("obtenerIdSba05 - No se encontró ID_SBA05");
            return null;
            
        } catch (Exception $e) {
            error_log("Error en obtenerIdSba05: " . $e->getMessage());
            return null;
        }
    }

    // Método para diagnosticar el stored procedure
    public function diagnosticarStoredProcedure($desde, $hasta)
    {
        try {
            error_log("=== DIAGNÓSTICO DEL STORED PROCEDURE ===");
            
            $sql = "EXEC RO_SP_MAYOR_CTA_100101 ?, ?";
            $params = array($desde, $hasta);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error ejecutando SP: " . print_r(sqlsrv_errors(), true));
                return false;
            }

            // Obtener metadata de columnas
            $metadata = sqlsrv_field_metadata($stmt);
            if ($metadata) {
                error_log("Columnas del SP:");
                foreach ($metadata as $field) {
                    error_log("- " . $field['Name'] . " (" . $field['Type'] . ")");
                }
            }

            // Obtener primer registro para análisis
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($row) {
                error_log("Primer registro - Claves disponibles: " . implode(', ', array_keys($row)));
                error_log("Valores del primer registro:");
                foreach ($row as $key => $value) {
                    $displayValue = is_object($value) ? get_class($value) : $value;
                    error_log("  $key: $displayValue");
                }
            } else {
                error_log("No se encontraron registros");
            }
            
            error_log("=== FIN DIAGNÓSTICO ===");
            return true;
            
        } catch (Exception $e) {
            error_log("Error en diagnóstico: " . $e->getMessage());
            return false;
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

    public function obtenerUltimaFechaControlada($desde, $hasta)
    {
        try {
            error_log("obtenerUltimaFechaControlada - Iniciando consulta directa - Buscando en TODA la tabla");
            
            // Primero verificar que la tabla existe
            $sqlTableExists = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'RO_T_MOV_CAJA_TESORERIA'";
            $stmtTable = sqlsrv_query($this->cid_central, $sqlTableExists);
            
            if ($stmtTable === false) {
                error_log("Error verificando tabla: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $tableRow = sqlsrv_fetch_array($stmtTable, SQLSRV_FETCH_ASSOC);
            if ($tableRow['count'] == 0) {
                error_log("Tabla RO_T_MOV_CAJA_TESORERIA no existe");
                return null;
            }
            
            // Hacer consulta directa con JOIN para obtener la fecha del movimiento controlado más reciente
            // MODIFICADO: Quitar el filtro de rango de fechas para buscar en TODA la tabla
            $sql = "SELECT TOP 1 s.FECHA 
                    FROM SBA05 s
                    INNER JOIN RO_T_MOV_CAJA_TESORERIA c ON s.ID_SBA05 = c.ID_SBA05
                    WHERE s.COD_CTA = '100101' 
                      AND c.CONTROLADO = 1
                    ORDER BY s.FECHA DESC";
                    
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en consulta directa: " . print_r($errors, true));
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($row && isset($row['FECHA'])) {
                $ultimaFechaControlada = $row['FECHA'];
                error_log("obtenerUltimaFechaControlada - Fecha encontrada en toda la tabla: " . $ultimaFechaControlada->format('Y-m-d'));
                return $ultimaFechaControlada;
            } else {
                error_log("obtenerUltimaFechaControlada - No se encontraron movimientos controlados en toda la tabla");
                return null;
            }
            
        } catch (Exception $e) {
            error_log("Error en obtenerUltimaFechaControlada: " . $e->getMessage());
            return null;
        }
    }

    // Método para diagnosticar la funcionalidad de última fecha controlada
    public function diagnosticarUltimaFechaControlada($desde, $hasta)
    {
        try {
            error_log("=== DIAGNÓSTICO ÚLTIMA FECHA CONTROLADA ===");
            
            // 1. Verificar que la tabla existe
            $sqlTableExists = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'RO_T_MOV_CAJA_TESORERIA'";
            $stmtTable = sqlsrv_query($this->cid_central, $sqlTableExists);
            
            if ($stmtTable === false) {
                error_log("Error verificando tabla: " . print_r(sqlsrv_errors(), true));
                return false;
            }
            
            $tableRow = sqlsrv_fetch_array($stmtTable, SQLSRV_FETCH_ASSOC);
            error_log("Tabla RO_T_MOV_CAJA_TESORERIA existe: " . ($tableRow['count'] > 0 ? 'SÍ' : 'NO'));
            
            if ($tableRow['count'] == 0) {
                error_log("Tabla no existe, creándola...");
                $this->crearTablaControlMovimientos();
                return false;
            }
            
            // 2. Verificar registros de control en el rango
            $sqlCount = "SELECT COUNT(*) as total_controlados 
                        FROM SBA05 s
                        INNER JOIN RO_T_MOV_CAJA_TESORERIA c ON s.ID_SBA05 = c.ID_SBA05
                        WHERE s.COD_CTA = '100101' 
                          AND s.FECHA BETWEEN ? AND ?
                          AND c.CONTROLADO = 1";
                          
            $params = array($desde, $hasta);
            $stmtCount = sqlsrv_query($this->cid_central, $sqlCount, $params);
            
            if ($stmtCount === false) {
                error_log("Error contando controlados: " . print_r(sqlsrv_errors(), true));
                return false;
            }
            
            $countRow = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
            error_log("Movimientos controlados en rango: " . $countRow['total_controlados']);
            
            // 3. Mostrar algunos registros controlados
            $sqlSample = "SELECT TOP 5 s.FECHA, s.COD_COMP, s.N_COMP, c.CONTROLADO, c.FECHA_CONTROL
                         FROM SBA05 s
                         INNER JOIN RO_T_MOV_CAJA_TESORERIA c ON s.ID_SBA05 = c.ID_SBA05
                         WHERE s.COD_CTA = '100101' 
                           AND s.FECHA BETWEEN ? AND ?
                           AND c.CONTROLADO = 1
                         ORDER BY s.FECHA DESC";
                         
            $stmtSample = sqlsrv_query($this->cid_central, $sqlSample, $params);
            
            if ($stmtSample !== false) {
                error_log("Muestra de registros controlados:");
                while ($sampleRow = sqlsrv_fetch_array($stmtSample, SQLSRV_FETCH_ASSOC)) {
                    $fecha = $sampleRow['FECHA']->format('Y-m-d');
                    $fechaControl = $sampleRow['FECHA_CONTROL'] ? $sampleRow['FECHA_CONTROL']->format('Y-m-d H:i:s') : 'NULL';
                    error_log("- Movimiento: $fecha {$sampleRow['COD_COMP']} {$sampleRow['N_COMP']} - Controlado en: $fechaControl");
                }
            }
            
            // 4. Probar la función principal
            $ultimaFecha = $this->obtenerUltimaFechaControlada($desde, $hasta);
            error_log("Resultado de obtenerUltimaFechaControlada: " . ($ultimaFecha ? $ultimaFecha->format('Y-m-d') : 'NULL'));
            
            error_log("=== FIN DIAGNÓSTICO ÚLTIMA FECHA CONTROLADA ===");
            return true;
            
        } catch (Exception $e) {
            error_log("Error en diagnóstico última fecha controlada: " . $e->getMessage());
            return false;
        }
    }

    // Mantener el método anterior por compatibilidad
    public function obtenerUltimaFechaControl($desde, $hasta)
    {
        // Redirigir al nuevo método
        return $this->obtenerUltimaFechaControlada($desde, $hasta);
            
    }

    public function obtenerControlMovimiento($idSba05)
    {
        try {
            if (!$this->cid_central) {
                return ['CONTROLADO' => 0, 'FECHA_CONTROL' => null];
            }
            
            // Verificar si la tabla existe
            $sqlTableExists = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'RO_T_MOV_CAJA_TESORERIA'";
            $stmtTableExists = sqlsrv_query($this->cid_central, $sqlTableExists);
            
            if ($stmtTableExists !== false) {
                $tableExistsRow = sqlsrv_fetch_array($stmtTableExists, SQLSRV_FETCH_ASSOC);
                if ($tableExistsRow['count'] == 0) {
                    // La tabla no existe, retornar valores por defecto
                    return ['CONTROLADO' => 0, 'FECHA_CONTROL' => null];
                }
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
            
            // Log para debug
            error_log("actualizarControlMovimiento - ID_SBA05: $idSba05, Controlado: " . ($controlado ? '1' : '0'));
            
            // Primero verificar si la tabla existe y tiene la estructura correcta
            $sqlTableExists = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'RO_T_MOV_CAJA_TESORERIA'";
            $stmtTableExists = sqlsrv_query($this->cid_central, $sqlTableExists);
            
            if ($stmtTableExists === false) {
                throw new Exception("Error verificando existencia de tabla: " . print_r(sqlsrv_errors(), true));
            }
            
            $tableExistsRow = sqlsrv_fetch_array($stmtTableExists, SQLSRV_FETCH_ASSOC);
            if ($tableExistsRow['count'] == 0) {
                // La tabla no existe, la creamos
                error_log("Tabla RO_T_MOV_CAJA_TESORERIA no existe, creándola...");
                $this->crearTablaControlMovimientos();
            } else {
                // Verificar estructura y reparar si es necesario
                try {
                    $this->repararTablaControlMovimientos();
                } catch (Exception $e) {
                    error_log("Advertencia en reparación de tabla: " . $e->getMessage());
                }
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
                error_log("Actualizando registro existente para ID_SBA05: $idSba05");
            } else {
                // Insertar nuevo registro
                $sql = "INSERT INTO RO_T_MOV_CAJA_TESORERIA (ID_SBA05, CONTROLADO, FECHA_CONTROL) 
                        VALUES (?, ?, GETDATE())";
                $params = array($idSba05, $controlado ? 1 : 0);
                error_log("Insertando nuevo registro para ID_SBA05: $idSba05");
            }
            
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error ejecutando SQL: " . print_r($errors, true));
                
                // Si el error es por IDENTITY, intentar reparar la tabla
                if (isset($errors[0]['code']) && $errors[0]['code'] == 544) {
                    error_log("Error de IDENTITY detectado, intentando reparar tabla...");
                    $this->repararTablaControlMovimientos();
                    
                    // Reintentar la operación
                    $stmt = sqlsrv_query($this->cid_central, $sql, $params);
                    if ($stmt === false) {
                        $errors = sqlsrv_errors();
                        throw new Exception("Error después de reparar tabla: " . print_r($errors, true));
                    }
                } else {
                    throw new Exception("Error actualizando control: " . print_r($errors, true));
                }
            }
            
            error_log("Control actualizado exitosamente para ID_SBA05: $idSba05");
            return true;
            
        } catch (Exception $e) {
            error_log("Error en actualizarControlMovimiento: " . $e->getMessage());
            throw $e;
        }
    }

    private function crearTablaControlMovimientos()
    {
        try {
            // Primero verificar si la tabla ya existe para evitar errores
            $sqlCheck = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'RO_T_MOV_CAJA_TESORERIA'";
            $stmtCheck = sqlsrv_query($this->cid_central, $sqlCheck);
            
            if ($stmtCheck !== false) {
                $checkRow = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
                if ($checkRow['count'] > 0) {
                    error_log("Tabla RO_T_MOV_CAJA_TESORERIA ya existe");
                    return;
                }
            }
            
            // Crear tabla con ID_SBA05 como clave primaria NO IDENTITY
            $sql = "CREATE TABLE RO_T_MOV_CAJA_TESORERIA (
                ID_SBA05 INT NOT NULL PRIMARY KEY,
                CONTROLADO BIT NOT NULL DEFAULT 0,
                FECHA_CONTROL DATETIME NULL
            )";
            
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error creando tabla RO_T_MOV_CAJA_TESORERIA: " . print_r($errors, true));
                throw new Exception("Error creando tabla RO_T_MOV_CAJA_TESORERIA: " . print_r($errors, true));
            }
            
            error_log("Tabla RO_T_MOV_CAJA_TESORERIA creada exitosamente");
            
        } catch (Exception $e) {
            error_log("Error en crearTablaControlMovimientos: " . $e->getMessage());
            throw $e;
        }
    }

    // Método para corregir la estructura de la tabla si tiene problemas
    private function repararTablaControlMovimientos()
    {
        try {
            error_log("Iniciando reparación de tabla RO_T_MOV_CAJA_TESORERIA");
            
            // Verificar si la tabla existe
            $sqlCheck = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'RO_T_MOV_CAJA_TESORERIA'";
            $stmtCheck = sqlsrv_query($this->cid_central, $sqlCheck);
            
            if ($stmtCheck === false) {
                throw new Exception("Error verificando tabla: " . print_r(sqlsrv_errors(), true));
            }
            
            $row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
            
            if ($row['count'] > 0) {
                // La tabla existe, verificar si ID_SBA05 es IDENTITY
                $sqlIdentity = "SELECT COLUMNPROPERTY(OBJECT_ID('RO_T_MOV_CAJA_TESORERIA'), 'ID_SBA05', 'IsIdentity') as IS_IDENTITY";
                $stmtIdentity = sqlsrv_query($this->cid_central, $sqlIdentity);
                
                if ($stmtIdentity !== false) {
                    $identityRow = sqlsrv_fetch_array($stmtIdentity, SQLSRV_FETCH_ASSOC);
                    
                    if ($identityRow['IS_IDENTITY'] == 1) {
                        error_log("Tabla tiene ID_SBA05 como IDENTITY, necesita ser recreada");
                        
                        // Respaldar datos existentes si los hay
                        $sqlBackup = "SELECT ID_SBA05, CONTROLADO, FECHA_CONTROL INTO RO_T_MOV_CAJA_TESORERIA_BACKUP FROM RO_T_MOV_CAJA_TESORERIA";
                        $stmtBackup = sqlsrv_query($this->cid_central, $sqlBackup);
                        
                        if ($stmtBackup !== false) {
                            error_log("Datos respaldados en RO_T_MOV_CAJA_TESORERIA_BACKUP");
                        }
                        
                        // Eliminar tabla problemática
                        $sqlDrop = "DROP TABLE RO_T_MOV_CAJA_TESORERIA";
                        $stmtDrop = sqlsrv_query($this->cid_central, $sqlDrop);
                        
                        if ($stmtDrop !== false) {
                            error_log("Tabla problemática eliminada");
                            
                            // Recrear tabla correctamente
                            $this->crearTablaControlMovimientos();
                            
                            // Restaurar datos si existía respaldo
                            $sqlRestore = "INSERT INTO RO_T_MOV_CAJA_TESORERIA (ID_SBA05, CONTROLADO, FECHA_CONTROL) 
                                          SELECT ID_SBA05, CONTROLADO, FECHA_CONTROL 
                                          FROM RO_T_MOV_CAJA_TESORERIA_BACKUP";
                            $stmtRestore = sqlsrv_query($this->cid_central, $sqlRestore);
                            
                            if ($stmtRestore !== false) {
                                error_log("Datos restaurados exitosamente");
                                
                                // Eliminar tabla de respaldo
                                $sqlDropBackup = "DROP TABLE RO_T_MOV_CAJA_TESORERIA_BACKUP";
                                sqlsrv_query($this->cid_central, $sqlDropBackup);
                            }
                        }
                    } else {
                        error_log("Tabla RO_T_MOV_CAJA_TESORERIA tiene estructura correcta");
                    }
                }
            } else {
                // La tabla no existe, crearla
                $this->crearTablaControlMovimientos();
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error en repararTablaControlMovimientos: " . $e->getMessage());
            throw $e;
        }
    }

    // Método de emergencia para regenerar registros de control
    public function regenerarRegistrosControl($desde = null, $hasta = null)
    {
        try {
            if (!$desde) $desde = date('Y-m-d', strtotime('-365 days')); // Último año por defecto
            if (!$hasta) $hasta = date('Y-m-d');
            
            error_log("Regenerando registros de control del $desde al $hasta");
            
            // Obtener todos los movimientos de la cuenta 100101 en el rango
            $sql = "SELECT DISTINCT ID_SBA05 FROM SBA05 
                    WHERE COD_CTA = '100101' 
                      AND FECHA BETWEEN ? AND ?
                      AND ID_SBA05 IS NOT NULL";
                      
            $params = array($desde, $hasta);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error obteniendo IDs SBA05: " . print_r(sqlsrv_errors(), true));
            }
            
            $contador = 0;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $idSba05 = $row['ID_SBA05'];
                
                // Verificar si ya existe registro de control
                $sqlCheck = "SELECT COUNT(*) as count FROM RO_T_MOV_CAJA_TESORERIA WHERE ID_SBA05 = ?";
                $stmtCheck = sqlsrv_query($this->cid_central, $sqlCheck, array($idSba05));
                
                if ($stmtCheck !== false) {
                    $checkRow = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
                    if ($checkRow['count'] == 0) {
                        // No existe, crear registro con estado no controlado
                        $sqlInsert = "INSERT INTO RO_T_MOV_CAJA_TESORERIA (ID_SBA05, CONTROLADO, FECHA_CONTROL) 
                                     VALUES (?, 0, NULL)";
                        $stmtInsert = sqlsrv_query($this->cid_central, $sqlInsert, array($idSba05));
                        
                        if ($stmtInsert !== false) {
                            $contador++;
                        }
                    }
                }
            }
            
            error_log("Registros de control creados: $contador");
            return $contador;
            
        } catch (Exception $e) {
            error_log("Error en regenerarRegistrosControl: " . $e->getMessage());
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

    public function obtenerIdsSba05PorRango($desde, $hasta)
    {
        try {
            // Ejecutar SP para obtener movimientos base
            $sql = "EXEC RO_SP_MAYOR_CTA_100101 ?, ?";
            $params = array($desde, $hasta);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $ids = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Si no tiene ID_SBA05, intentar obtenerlo desde SBA05
                if (!isset($row['ID_SBA05']) || !$row['ID_SBA05']) {
                    $row['ID_SBA05'] = $this->obtenerIdSba05($row);
                }
                
                // Solo agregar si tiene un ID_SBA05 válido (puede ser controlado)
                if (isset($row['ID_SBA05']) && $row['ID_SBA05']) {
                    $ids[] = $row['ID_SBA05'];
                }
            }
            
            error_log("Total IDs obtenidos para rango $desde-$hasta: " . count($ids));
            return $ids;
            
        } catch (Exception $e) {
            error_log("Error en obtenerIdsSba05PorRango: " . $e->getMessage());
            throw $e;
        }
    }
}
?>