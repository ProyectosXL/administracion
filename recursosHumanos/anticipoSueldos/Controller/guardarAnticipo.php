
<?php
// Controller/guardarAnticipo.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('America/Argentina/Buenos_Aires');

header('Content-Type: application/json');

try {
    require_once '../../Class/Anticipo.php';
    $anticipo = new Anticipo();

    // Obtener y validar los datos POST
    if (!isset($_POST['registros'])) {
        throw new Exception('No se recibieron datos para guardar');
    }

    $registros = json_decode($_POST['registros'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al procesar los datos recibidos');
    }

    // Validar período vigente
    $sql = "SELECT COUNT(*) as hay_vigente 
            FROM RO_T_FECHA_ANTICIPOS 
            WHERE GETDATE() BETWEEN VIG_DESDE AND VIG_HASTA";
    
    $hayVigente = $anticipo->obtenerValor($sql);
    
    if (!$hayVigente || $hayVigente == 0) {
        throw new Exception('No se pueden cargar anticipos fuera del período habilitado');
    }

    // Obtener período vigente
    $sql = "SELECT TOP 1 PERIODO 
            FROM RO_T_FECHA_ANTICIPOS 
            WHERE GETDATE() BETWEEN VIG_DESDE AND VIG_HASTA";
    
    $periodoVigente = $anticipo->obtenerValor($sql);
    
    if (!$periodoVigente) {
        throw new Exception('Error al obtener el período vigente');
    }

    // Iniciar transacción
    $anticipo->beginTransaction();

    try {
        foreach ($registros as $registro) {
            // Validar datos requeridos
            if (!isset($registro['legajo'], $registro['nombre'], $registro['dni'], $registro['importe'])) {
                throw new Exception('Datos incompletos en el registro');
            }

            // Validar duplicados
            $sql = "SELECT COUNT(*) FROM RO_T_DETALLE_ANTICIPOS 
                    WHERE NRO_LEGAJO = ? AND PERIODO = ?";
            
            $duplicado = $anticipo->obtenerValor($sql, array($registro['legajo'], $periodoVigente));

            if ($duplicado > 0) {
                throw new Exception("Ya existe un anticipo para el legajo {$registro['legajo']} en este período");
            }

            // Preparar el importe (limpiar formato)
            $importe = str_replace(['$', ',', ' '], '', $registro['importe']);
            if (!is_numeric($importe)) {
                throw new Exception('El importe no es válido');
            }

            // Insertar registro
            $sql = "INSERT INTO RO_T_DETALLE_ANTICIPOS 
                    (NRO_LEGAJO, APELLIDO_Y_NOMBRE, DNI, IMPORTE, PERIODO, FECHA_CARGA) 
                    VALUES (?, ?, ?, ?, ?, GETDATE())";
            
            $params = array(
                $registro['legajo'],
                $registro['nombre'],
                $registro['dni'],
                floatval($importe),
                $periodoVigente
            );

            if (!$anticipo->ejecutar($sql, $params)) {
                throw new Exception('Error al insertar el registro');
            }
        }

        // Si todo salió bien, confirmar la transacción
        $anticipo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Anticipos registrados correctamente'
        ]);

    } catch (Exception $e) {
        // Si hubo error, revertir la transacción
        $anticipo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error en guardarAnticipo: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}