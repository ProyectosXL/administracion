<?php
/**
 * Endpoint para obtener el progreso de indexación en tiempo real
 * Lee el archivo de log de indexación y devuelve el estado actual
 */

header('Content-Type: application/json');

$log_file = __DIR__ . '/../rag-service/.indexacion_pdfs.log';

$response = [
    'indexando' => false,
    'progreso' => '',
    'ultimo_mensaje' => '',
    'timestamp' => null
];

try {
    if (file_exists($log_file)) {
        // Verificar si el archivo fue modificado en los últimos 2 minutos
        $ultima_modificacion = filemtime($log_file);
        $tiempo_transcurrido = time() - $ultima_modificacion;
        
        if ($tiempo_transcurrido < 120) { // 2 minutos
            $response['indexando'] = true;
            $response['timestamp'] = date('Y-m-d H:i:s', $ultima_modificacion);
            
            // Leer últimas líneas del log
            $lineas = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lineas && count($lineas) > 0) {
                // Obtener última línea
                $ultima_linea = end($lineas);
                $response['ultimo_mensaje'] = $ultima_linea;
                
                // Buscar resumen si existe
                foreach (array_reverse($lineas) as $linea) {
                    if (strpos($linea, 'Total procesados:') !== false) {
                        $response['progreso'] = trim(substr($linea, strpos($linea, ' - ') + 3));
                        break;
                    }
                    if (strpos($linea, 'Indexando:') !== false) {
                        $response['progreso'] = trim(substr($linea, strpos($linea, ' - ') + 3));
                        break;
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
