<?php
/**
 * Controlador para ejecutar procesos largos de forma asíncrona
 * Maneja el prorrateo integral con seguimiento de progreso
 */

// Iniciar sesión
session_start();

// Configurar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en respuesta JSON
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Incluir clases necesarias usando la misma ruta que prorratear.php
require_once __DIR__.'/../../class/conexion.php';

class ProrrateoController {
    private $conn;
    
    public function __construct() {
        try {
            // Iniciar sesión si no está iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Establecer valores por defecto en sesión si no existen
            // Esto permite que funcione sin login previo
            if (!isset($_SESSION['entorno'])) {
                $_SESSION['entorno'] = 'central';
            }
            
            if (!class_exists('Conexion')) {
                throw new Exception('Clase Conexion no encontrada');
            }
            
            $conexion = new Conexion();
            
            // Intentar conectar con el entorno de la sesión
            $entorno = $_SESSION['entorno'] ?? 'central';
            $this->conn = $conexion->conectar($entorno);
            
            if (!$this->conn) {
                throw new Exception('No se pudo establecer conexión con la base de datos');
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Inicia el proceso de prorrateo de forma asíncrona
     */
    public function iniciarProrrateo($desde, $hasta, $periodo) {
        try {
            // Verificar conexión
            if (!$this->conn) {
                return [
                    'success' => false,
                    'message' => 'No hay conexión a base de datos'
                ];
            }
            
            $proceso = 'PRORRATEO';
            $usuario = $_SESSION['usuario'] ?? 'SISTEMA';
            
            // Intentar crear registro directamente sin SP
            $sql = "INSERT INTO RO_T_CONTROL_PROCESOS (PROCESO, PERIODO, USUARIO, ESTADO, MENSAJE, FECHA_INICIO) 
                    OUTPUT INSERTED.ID
                    VALUES (?, ?, ?, 'INICIADO', 'Proceso iniciado', GETDATE())";
            
            $params = array($proceso, $periodo, $usuario);
            $stmt = sqlsrv_query($this->conn, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                // Verificar si la tabla no existe
                if (isset($errors[0]['code']) && $errors[0]['code'] == 208) {
                    return [
                        'success' => false,
                        'message' => 'La tabla RO_T_CONTROL_PROCESOS no existe. Por favor ejecute el script de instalación SQL.'
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Error al crear registro: ' . $errors[0]['message']
                ];
            }
            
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if (!$resultado || !isset($resultado['ID'])) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener ID del proceso'
                ];
            }
            
            $idProceso = $resultado['ID'];
            
            // Ejecutar el proceso en segundo plano
            try {
                $this->ejecutarProcesoBackground($idProceso, $desde, $hasta);
            } catch (Exception $bgError) {
                return [
                    'success' => false,
                    'message' => 'Error al iniciar proceso en segundo plano: ' . $bgError->getMessage()
                ];
            }
            
            return [
                'success' => true,
                'id_proceso' => $idProceso,
                'message' => 'Proceso de prorrateo iniciado. Por favor espere...'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al iniciar proceso: ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }
    
    /**
     * Ejecuta el proceso de prorrateo en segundo plano
     */
    private function ejecutarProcesoBackground($idProceso, $desde, $hasta) {
        // En Windows, usar popen para ejecutar en background
        $scriptPath = __DIR__ . '/ejecutar_prorrateo.php';
        
        // Verificar que el script existe
        if (!file_exists($scriptPath)) {
            throw new Exception("Script de ejecución no encontrado: $scriptPath");
        }
        
        // Buscar PHP
        $phpPath = 'php'; // Por defecto asumir que está en PATH
        
        // Obtener entorno actual de la sesión
        $entorno = $_SESSION['entorno'] ?? 'central';
        
        // Construir comando (ahora incluye el entorno como 4to parámetro)
        $cmd = "\"$phpPath\" \"$scriptPath\" $idProceso \"$desde\" \"$hasta\" \"$entorno\"";
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - ejecutar sin mostrar ventana
            $fullCmd = "start /B cmd /c \"$cmd > NUL 2>&1\"";
            pclose(popen($fullCmd, "r"));
        } else {
            // Linux
            exec("$cmd > /dev/null 2>&1 &");
        }
        
        // No cerrar la conexión aquí, dejar que se cierre al finalizar la petición
    }
    
    /**
     * Consulta el estado de un proceso
     */
    public function consultarEstado($idProceso) {
        try {
            $sql = "EXEC RO_SP_CONSULTAR_ESTADO_PROCESO @ID_PROCESO = ?";
            $params = array($idProceso);
            
            $stmt = sqlsrv_query($this->conn, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception('Error al consultar estado: ' . print_r(sqlsrv_errors(), true));
            }
            
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if (!$resultado) {
                return [
                    'success' => false,
                    'message' => 'Proceso no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'estado' => $resultado['ESTADO'],
                'progreso' => $resultado['PROGRESO'] ?? 0,
                'mensaje' => $resultado['MENSAJE'] ?? '',
                'total_registros' => $resultado['TOTAL_REGISTROS'] ?? 0,
                'registros_procesados' => $resultado['REGISTROS_PROCESADOS'] ?? 0,
                'fecha_inicio' => $resultado['FECHA_INICIO'],
                'fecha_fin' => $resultado['FECHA_FIN'],
                'detalles_error' => $resultado['DETALLES_ERROR']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al consultar estado: ' . $e->getMessage()
            ];
        }
    }
}

// Procesar solicitudes
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $accion = $_POST['accion'] ?? '';
        
        if (empty($accion)) {
            echo json_encode([
                'success' => false,
                'message' => 'No se especificó acción'
            ]);
            exit;
        }
        
        $controller = new ProrrateoController();
        
        switch ($accion) {
            case 'iniciar':
                $desde = $_POST['desde'] ?? '';
                $hasta = $_POST['hasta'] ?? '';
                $periodo = $_POST['periodo'] ?? '';
                
                if (empty($desde) || empty($hasta) || empty($periodo)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Faltan parámetros requeridos (desde, hasta, periodo)'
                    ]);
                    exit;
                }
                
                $resultado = $controller->iniciarProrrateo($desde, $hasta, $periodo);
                echo json_encode($resultado);
                break;
                
            case 'consultar_estado':
                $idProceso = $_POST['id_proceso'] ?? 0;
                
                if (empty($idProceso)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ID de proceso no proporcionado'
                    ]);
                    exit;
                }
                
                $resultado = $controller->consultarEstado($idProceso);
                echo json_encode($resultado);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Acción no válida: ' . $accion
                ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido. Use POST.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error general: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

