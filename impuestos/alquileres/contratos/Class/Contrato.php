<?php

class Contrato
{
    private $cid_central;

    function __construct(){
        require_once $_SERVER['DOCUMENT_ROOT'] . '/administracion/class/conexion.php';

        $cid = new Conexion();

        $this->cid_central = $cid->conectar('central');

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
            $this->cid_central = $cid->conectar('uy');
        }
    }

    /**
     * Trae los contratos de alquiler según una fecha específica o todos
     * @param string|null $fecha - Fecha en formato YYYY-MM (opcional)
     * @return array - Array de contratos
     */
    function traerContratoAlquiler($fecha = null) {
        if($fecha != null){
            $sql = "
            DECLARE @periodo VARCHAR(7) = '$fecha';
            
            WITH CTE AS (
                SELECT
                    *,
                    ROW_NUMBER() OVER(PARTITION BY NRO_SUCURS ORDER BY ID DESC) AS rn
                FROM RO_T_CONTRATOS_ALQUILERES
                WHERE CONVERT(VARCHAR(7), VIG_DESDE, 120) <= @periodo
                  AND CONVERT(VARCHAR(7), VIG_HASTA, 120) >= @periodo
            )
            
            SELECT * FROM CTE WHERE rn = 1;
            ";
        }else{
            $sql = "SELECT * FROM RO_T_CONTRATOS_ALQUILERES";
        }

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
    
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;
        } catch (\Throwable $th) {
            throw $th; 
        }
    }

    /**
     * Trae los contratos vigentes actualmente
     * @return array - Array de contratos vigentes
     */
    function traerContratoVigente(){
        $sql = "
        DECLARE @fecha_actual DATE = GETDATE();
        
        WITH CTE AS (
            SELECT
                *,
                ROW_NUMBER() OVER(PARTITION BY NRO_SUCURS ORDER BY ID DESC) AS rn
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE VIG_DESDE <= @fecha_actual
              AND VIG_HASTA >= @fecha_actual
        )
        
        SELECT * FROM CTE WHERE rn IN (1)";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
    
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;
        } catch (\Throwable $th) {
            throw $th; 
        }
    }

    /**
     * Trae los contratos anteriores (ya vencidos)
     * @return array - Array de contratos anteriores
     */
    function traerContratoAnterior() {
        $sql = "
        DECLARE @fecha_actual DATE = GETDATE();

        WITH CTE AS (
            SELECT
                *,
                ROW_NUMBER() OVER(PARTITION BY NRO_SUCURS ORDER BY ABS(DATEDIFF(DAY, VIG_HASTA, @fecha_actual))) AS rn
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE VIG_HASTA <= @fecha_actual
        )
        
        SELECT * FROM CTE WHERE rn IN (1)";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
    
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;
        } catch (\Throwable $th) {
            throw $th; 
        }
    }

    /**
     * Trae los contratos futuros (que aún no han comenzado)
     * @return array - Array de contratos futuros
     */
    function traerContratosFuturos() {
        $sql = "
            DECLARE @fecha_actual DATE = GETDATE();
            
            WITH CTE AS (
                SELECT
                    *,
                    ROW_NUMBER() OVER(PARTITION BY NRO_SUCURS ORDER BY VIG_DESDE ASC) AS rn
                FROM RO_T_CONTRATOS_ALQUILERES
                WHERE VIG_DESDE > @fecha_actual
            )
            
            SELECT * FROM CTE WHERE rn = 1
            ORDER BY VIG_DESDE ASC
        ";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if (!$stmt) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $rows = array();
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }

            return $rows;
            
        } catch (\Throwable $th) {
            error_log("Error en traerContratosFuturos: " . $th->getMessage());
            return array();
        }
    }

    /**
     * Guarda un nuevo contrato de alquiler
     * @param string $nroSucursal - Número de sucursal
     * @param string $descSucursal - Descripción de la sucursal  
     * @param int $valorLlave - Valor llave
     * @param int $comisiones - Comisiones
     * @param int $lanzamiento - FPC Lanzamiento
     * @param string $vigDesde - Fecha de inicio (YYYY-MM-DD)
     * @param string $vigHasta - Fecha de fin (YYYY-MM-DD)
     * @return bool - True si se guardó correctamente, false en caso contrario
     */
    public function guardarContratoAlquiler($nroSucursal, $descSucursal, $valorLlave, $comisiones, $lanzamiento, $vigDesde, $vigHasta) {
        
        $sql = "
            INSERT INTO RO_T_CONTRATOS_ALQUILERES (
                NRO_SUCURS, 
                DESC_SUCURS, 
                VIG_DESDE, 
                VIG_HASTA, 
                ID_CA, 
                IMPORTE, 
                ID_CA_2, 
                IMPORTE_2, 
                ID_CA_3, 
                IMPORTE_3,
                FECHA_CARGA
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE()
            )
        ";

        try {
            $params = [
                $nroSucursal,
                $descSucursal, 
                $vigDesde,
                $vigHasta,
                4, // ID_CA para Valor Llave
                $valorLlave,
                5, // ID_CA_2 para Comisiones  
                $comisiones,
                18, // ID_CA_3 para FPC Lanzamiento
                $lanzamiento
            ];

            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                throw new \Exception("Error al preparar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            return true;

        } catch (\Throwable $th) {
            error_log("Error en guardarContratoAlquiler: " . $th->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los contratos activos de una sucursal (vigentes o futuros)
     * @param string $sucursal - Número de sucursal
     * @return array - Array de contratos activos para la sucursal
     */
    public function obtenerContratosActivosSucursal($sucursal) {
        
        $sql = "
            SELECT 
                ID,
                NRO_SUCURS,
                DESC_SUCURS,
                VIG_DESDE,
                VIG_HASTA,
                ID_CA,
                IMPORTE,
                ID_CA_2,
                IMPORTE_2,
                ID_CA_3,
                IMPORTE_3,
                FECHA_CARGA
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE NRO_SUCURS = '$sucursal'
            AND VIG_HASTA >= GETDATE()
            ORDER BY VIG_DESDE DESC
        ";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if (!$stmt) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $contratos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                
                // Formatear fechas si son objetos DateTime
                if ($row['VIG_DESDE'] instanceof DateTime) {
                    $row['VIG_DESDE'] = $row['VIG_DESDE']->format('Y-m-d');
                }
                if ($row['VIG_HASTA'] instanceof DateTime) {
                    $row['VIG_HASTA'] = $row['VIG_HASTA']->format('Y-m-d');
                }
                if ($row['FECHA_CARGA'] instanceof DateTime) {
                    $row['FECHA_CARGA'] = $row['FECHA_CARGA']->format('Y-m-d H:i:s');
                }
                
                $contratos[] = $row;
            }

            return $contratos;

        } catch (\Throwable $th) {
            error_log("Error en obtenerContratosActivosSucursal: " . $th->getMessage());
            return array();
        }
    }

    /**
     * Obtiene un contrato por su ID
     * @param int $contratoId - ID del contrato
     * @return array|false - Datos del contrato o false si no existe
     */
    public function obtenerContratoPorId($contratoId) {
        $sql = "
            SELECT 
                ID,
                NRO_SUCURS,
                DESC_SUCURS,
                VIG_DESDE,
                VIG_HASTA,
                ID_CA,
                IMPORTE,
                ID_CA_2,
                IMPORTE_2,
                ID_CA_3,
                IMPORTE_3,
                FECHA_CARGA
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE ID = ?
        ";

        try {
            $stmt = sqlsrv_prepare($this->cid_central, $sql, [$contratoId]);
            
            if (!$stmt) {
                throw new \Exception("Error al preparar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $contrato = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($contrato) {
                // Formatear fechas si son objetos DateTime
                if ($contrato['VIG_DESDE'] instanceof DateTime) {
                    $contrato['VIG_DESDE'] = $contrato['VIG_DESDE']->format('Y-m-d');
                }
                if ($contrato['VIG_HASTA'] instanceof DateTime) {
                    $contrato['VIG_HASTA'] = $contrato['VIG_HASTA']->format('Y-m-d');
                }
                if ($contrato['FECHA_CARGA'] instanceof DateTime) {
                    $contrato['FECHA_CARGA'] = $contrato['FECHA_CARGA']->format('Y-m-d H:i:s');
                }
                
                return $contrato;
            }
            
            return false;

        } catch (\Throwable $th) {
            error_log("Error en obtenerContratoPorId: " . $th->getMessage());
            return false;
        }
    }

    /**
     * Verifica si existe solapamiento de contratos para una sucursal en un período
     * @param string $sucursal - Número de sucursal
     * @param string $fechaDesde - Fecha de inicio (YYYY-MM-DD)
     * @param string $fechaHasta - Fecha de fin (YYYY-MM-DD)
     * @return array - Información sobre el solapamiento
     */
    public function verificarSolapamientoContrato($sucursal, $fechaDesde, $fechaHasta) {
        
        $sql = "
            SELECT TOP 1
                ID,
                NRO_SUCURS,
                DESC_SUCURS,
                VIG_DESDE,
                VIG_HASTA,
                ID_CA,
                IMPORTE,
                ID_CA_2,
                IMPORTE_2,
                ID_CA_3,
                IMPORTE_3
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE NRO_SUCURS = '$sucursal'
            AND (
                -- Caso 1: El nuevo contrato empieza durante un contrato existente
                ('$fechaDesde' BETWEEN VIG_DESDE AND VIG_HASTA)
                OR
                -- Caso 2: El nuevo contrato termina durante un contrato existente  
                ('$fechaHasta' BETWEEN VIG_DESDE AND VIG_HASTA)
                OR
                -- Caso 3: El nuevo contrato engloba completamente a uno existente
                ('$fechaDesde' <= VIG_DESDE AND '$fechaHasta' >= VIG_HASTA)
                OR
                -- Caso 4: Un contrato existente engloba completamente al nuevo
                (VIG_DESDE <= '$fechaDesde' AND VIG_HASTA >= '$fechaHasta')
            )
            ORDER BY VIG_DESDE DESC
        ";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if (!$stmt) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $contratoExistente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($contratoExistente) {
                // Formatear las fechas para la respuesta
                $vigDesde = $contratoExistente['VIG_DESDE'];
                $vigHasta = $contratoExistente['VIG_HASTA'];
                
                // Convertir objetos DateTime a string si es necesario
                if ($vigDesde instanceof DateTime) {
                    $vigDesde = $vigDesde->format('Y-m-d');
                }
                if ($vigHasta instanceof DateTime) {
                    $vigHasta = $vigHasta->format('Y-m-d');
                }

                return [
                    'solapamiento' => true,
                    'contrato_existente' => [
                        'ID' => $contratoExistente['ID'],
                        'NRO_SUCURS' => $contratoExistente['NRO_SUCURS'],
                        'DESC_SUCURS' => $contratoExistente['DESC_SUCURS'],
                        'VIG_DESDE' => $vigDesde,
                        'VIG_HASTA' => $vigHasta,
                        'IMPORTE_1' => $contratoExistente['IMPORTE'],
                        'IMPORTE_2' => $contratoExistente['IMPORTE_2'],
                        'IMPORTE_3' => $contratoExistente['IMPORTE_3']
                    ],
                    'mensaje' => 'Ya existe un contrato para esta sucursal que se solapa con el período seleccionado'
                ];
            } else {
                return [
                    'solapamiento' => false,
                    'mensaje' => 'No hay solapamiento de contratos'
                ];
            }

        } catch (\Throwable $th) {
            error_log("Error en verificarSolapamientoContrato: " . $th->getMessage());
            
            return [
                'solapamiento' => false,
                'error' => true,
                'mensaje' => 'Error al verificar solapamiento: ' . $th->getMessage()
            ];
        }
    }

    /**
     * Verifica si existe solapamiento de contratos excluyendo el contrato actual (para edición)
     * @param string $sucursal - Número de sucursal
     * @param string $fechaDesde - Fecha de inicio (YYYY-MM-DD)
     * @param string $fechaHasta - Fecha de fin (YYYY-MM-DD)
     * @param int $contratoId - ID del contrato que se está editando
     * @return array - Información sobre el solapamiento
     */
    public function verificarSolapamientoContratoEdicion($sucursal, $fechaDesde, $fechaHasta, $contratoId) {
        
        $sql = "
            SELECT TOP 1
                ID,
                NRO_SUCURS,
                DESC_SUCURS,
                VIG_DESDE,
                VIG_HASTA,
                ID_CA,
                IMPORTE,
                ID_CA_2,
                IMPORTE_2,
                ID_CA_3,
                IMPORTE_3
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE NRO_SUCURS = '$sucursal'
            AND ID != $contratoId
            AND (
                -- Caso 1: El contrato editado empieza durante un contrato existente
                ('$fechaDesde' BETWEEN VIG_DESDE AND VIG_HASTA)
                OR
                -- Caso 2: El contrato editado termina durante un contrato existente  
                ('$fechaHasta' BETWEEN VIG_DESDE AND VIG_HASTA)
                OR
                -- Caso 3: El contrato editado engloba completamente a uno existente
                ('$fechaDesde' <= VIG_DESDE AND '$fechaHasta' >= VIG_HASTA)
                OR
                -- Caso 4: Un contrato existente engloba completamente al editado
                (VIG_DESDE <= '$fechaDesde' AND VIG_HASTA >= '$fechaHasta')
            )
            ORDER BY VIG_DESDE DESC
        ";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if (!$stmt) {
                throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $contratoExistente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($contratoExistente) {
                // Formatear las fechas para la respuesta
                $vigDesde = $contratoExistente['VIG_DESDE'];
                $vigHasta = $contratoExistente['VIG_HASTA'];
                
                // Convertir objetos DateTime a string si es necesario
                if ($vigDesde instanceof DateTime) {
                    $vigDesde = $vigDesde->format('Y-m-d');
                }
                if ($vigHasta instanceof DateTime) {
                    $vigHasta = $vigHasta->format('Y-m-d');
                }

                return [
                    'solapamiento' => true,
                    'contrato_existente' => [
                        'ID' => $contratoExistente['ID'],
                        'NRO_SUCURS' => $contratoExistente['NRO_SUCURS'],
                        'DESC_SUCURS' => $contratoExistente['DESC_SUCURS'],
                        'VIG_DESDE' => $vigDesde,
                        'VIG_HASTA' => $vigHasta,
                        'IMPORTE_1' => $contratoExistente['IMPORTE'],
                        'IMPORTE_2' => $contratoExistente['IMPORTE_2'],
                        'IMPORTE_3' => $contratoExistente['IMPORTE_3']
                    ],
                    'mensaje' => "Se solapa con contrato ID {$contratoExistente['ID']} ({$vigDesde} a {$vigHasta})"
                ];
            } else {
                return [
                    'solapamiento' => false,
                    'mensaje' => 'No hay solapamiento de contratos'
                ];
            }

        } catch (\Throwable $th) {
            error_log("Error en verificarSolapamientoContratoEdicion: " . $th->getMessage());
            
            return [
                'solapamiento' => false,
                'error' => true,
                'mensaje' => 'Error al verificar solapamiento: ' . $th->getMessage()
            ];
        }
    }

    /**
     * Actualiza un contrato existente
     * @param int $contratoId - ID del contrato a actualizar
     * @param string $vigDesde - Nueva fecha de inicio (YYYY-MM-DD)
     * @param string $vigHasta - Nueva fecha de fin (YYYY-MM-DD)
     * @param float $valorLlave - Nuevo valor llave
     * @param float $comisiones - Nuevas comisiones
     * @param float $lanzamiento - Nuevo FPC Lanzamiento
     * @return bool - True si se actualizó correctamente, false en caso contrario
     */
    public function actualizarContrato($contratoId, $vigDesde, $vigHasta, $valorLlave, $comisiones, $lanzamiento) {
        
        $sql = "
            UPDATE RO_T_CONTRATOS_ALQUILERES SET
                VIG_DESDE = ?,
                VIG_HASTA = ?,
                IMPORTE = ?,
                IMPORTE_2 = ?,
                IMPORTE_3 = ?,
                FECHA_MODIF = GETDATE()
            WHERE ID = ?
        ";

        try {
            $params = [
                $vigDesde,
                $vigHasta,
                $valorLlave,
                $comisiones,
                $lanzamiento,
                $contratoId
            ];

            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                $errors = sqlsrv_errors();
                error_log("Error al preparar consulta actualizar contrato: " . print_r($errors, true));
                throw new \Exception("Error al preparar la consulta: " . print_r($errors, true));
            }

            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                $errors = sqlsrv_errors();
                error_log("Error al ejecutar consulta actualizar contrato: " . print_r($errors, true));
                throw new \Exception("Error al ejecutar la consulta: " . print_r($errors, true));
            }

            $rowsAffected = sqlsrv_rows_affected($stmt);
            return $rowsAffected > 0;

        } catch (\Throwable $th) {
            error_log("Error en actualizarContrato: " . $th->getMessage());
            return false;
        }
    }
}
