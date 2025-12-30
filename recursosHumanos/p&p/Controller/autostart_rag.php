<?php
/**
 * Script de auto-inicio del servicio RAG y auto-indexación de documentos.
 * Se ejecuta automáticamente al cargar el index.php del sistema.
 * Funciona tanto en local (Windows) como en producción (Linux).
 */

header('Content-Type: application/json');

// Cargar configuración
require_once __DIR__ . '/../config_rag.php';
$config = RagConfig::getInstance();

$response = [
    'success' => false,
    'servicio_iniciado' => false,
    'documentos_indexados' => false,
    'mensaje' => '',
    'detalles' => [],
    'environment' => $config->get('environment')
];

try {
    // Si RAG no está habilitado, salir
    if (!$config->isRagEnabled()) {
        $response['success'] = true;
        $response['mensaje'] = "Servicio RAG deshabilitado en configuración";
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    // ============================================================================
    // 1. VERIFICAR SI EL SERVICIO RAG ESTÁ CORRIENDO
    // ============================================================================
    
    $rag_url = $config->getRagServiceUrl('/health');
    $ch = curl_init($rag_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    $health_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $servicio_corriendo = ($http_code === 200);
    
    if (!$servicio_corriendo && $config->shouldAutoStart()) {
        $response['detalles'][] = "Servicio RAG no está corriendo, iniciando en segundo plano...";
        
        // ============================================================================
        // 2. INICIAR EL SERVICIO RAG (ADAPTA A WINDOWS O LINUX)
        // ============================================================================
        
        $rag_service_dir = realpath(__DIR__ . '/../rag-service');
        $is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        
        if ($is_windows) {
            // ==================== WINDOWS ====================
            $python_exe = $config->get('python_path');
            
            if (file_exists($python_exe) && is_dir($rag_service_dir)) {
                // Crear un archivo .bat temporal
                $temp_bat = tempnam(sys_get_temp_dir(), 'rag_') . '.bat';
                $bat_content = "@echo off\n";
                $bat_content .= "cd /d \"$rag_service_dir\"\n";
                $bat_content .= "\"$python_exe\" -m uvicorn app.main:app --reload --port 8000\n";
                
                file_put_contents($temp_bat, $bat_content);
                
                // Ejecutar el bat en segundo plano
                $command = "start /B \"\" \"$temp_bat\"";
                pclose(popen($command, 'r'));
                
                $response['servicio_iniciado'] = true;
                $response['detalles'][] = "Servicio RAG iniciándose en Windows (10-15 segundos)";
            } else {
                $response['detalles'][] = "Error: Python no encontrado en $python_exe";
            }
            
        } else {
            // ==================== LINUX ====================
            $python_exe = $config->get('python_path', 'python3');
            
            if (is_dir($rag_service_dir)) {
                // Script de inicio para Linux (daemon simple)
                $start_script = $rag_service_dir . '/start_production.sh';
                
                // Crear script si no existe
                if (!file_exists($start_script)) {
                    $script_content = "#!/bin/bash\n";
                    $script_content .= "cd \"$rag_service_dir\"\n";
                    $script_content .= "$python_exe -m uvicorn app.main:app --host 0.0.0.0 --port 8000 > /dev/null 2>&1 &\n";
                    $script_content .= "echo \$! > /tmp/rag_service.pid\n";
                    
                    file_put_contents($start_script, $script_content);
                    chmod($start_script, 0755);
                }
                
                // Ejecutar en background
                exec("bash \"$start_script\" > /dev/null 2>&1 &");
                
                $response['servicio_iniciado'] = true;
                $response['detalles'][] = "Servicio RAG iniciándose en Linux (10-15 segundos)";
            } else {
                $response['detalles'][] = "Error: Directorio RAG no encontrado";
            }
        }
    } else {
        $response['servicio_iniciado'] = true;
        $response['detalles'][] = "Servicio RAG ya estaba corriendo";
        
        // ============================================================================
        // 3. AUTO-INDEXACIÓN INTELIGENTE (SI HAY POCOS CHUNKS)
        // ============================================================================
        
        if ($config->shouldAutoIndex()) {
            $health_data = json_decode($health_response, true);
            $chunks_actuales = $health_data['total_chunks'] ?? 0;
            
            $response['detalles'][] = "Chunks actuales en ChromaDB: $chunks_actuales";
            
            // Si no hay chunks, indexar en SEGUNDO PLANO
            if ($chunks_actuales < 10) {
                $response['detalles'][] = "Pocos chunks detectados, indexación en segundo plano...";
                
                // Crear script de indexación en background
                $indexer_script = __DIR__ . '/indexar_background_' . time() . '.php';
                $webhook_url = $config->getRagServiceUrl('/webhook/document-uploaded');
                $documentos_path = $config->getDocumentosPath();
                
                $indexer_content = '<?php
ignore_user_abort(true);
set_time_limit(0);

$documentos_path = "' . addslashes($documentos_path) . '";
$webhook_url = "' . addslashes($webhook_url) . '";

if (is_dir($documentos_path)) {
    $archivos = glob($documentos_path . "/*.pdf");
    foreach ($archivos as $archivo_path) {
        $document_id = abs(crc32($archivo_path));
        $data = [
            "document_id" => $document_id,
            "file_path" => $archivo_path,
            "titulo" => pathinfo(basename($archivo_path), PATHINFO_FILENAME),
            "sector" => "Políticas y Procedimientos",
            "tipo" => "PDF"
        ];
        
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_exec($ch);
        curl_close($ch);
    }
}

@unlink(__FILE__);
?>';
                
                file_put_contents($indexer_script, $indexer_content);
                
                // Ejecutar en background (funciona en Windows y Linux)
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    pclose(popen('start /B php "' . $indexer_script . '" > NUL 2>&1', 'r'));
                } else {
                    exec('php "' . $indexer_script . '" > /dev/null 2>&1 &');
                }
                
                $response['documentos_indexados'] = true;
                $response['detalles'][] = "Indexación iniciada en segundo plano";
            } else {
                $response['documentos_indexados'] = true;
                $response['detalles'][] = "Ya hay suficientes chunks indexados";
            }
        }
    }
    
    // ============================================================================
    // 4. RESULTADO FINAL
    // ============================================================================
    
    $response['success'] = true;
    $response['mensaje'] = $servicio_corriendo 
        ? "Sistema RAG operativo" 
        : "Sistema RAG iniciándose en segundo plano";
    
} catch (Exception $e) {
    $response['mensaje'] = "Error: " . $e->getMessage();
    $response['detalles'][] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
