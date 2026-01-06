<?php
/**
 * Script para indexar PDFs faltantes desde la carpeta /documentos
 * Se ejecuta en segundo plano y verifica cada PDF por su título
 */

// Configuración para ejecución en background
set_time_limit(300);
ini_set('memory_limit', '256M');
ignore_user_abort(true);

// Si se llama desde HTTP, terminar la conexión inmediatamente
if (php_sapi_name() !== 'cli' && function_exists('fastcgi_finish_request')) {
    http_response_code(202);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'accepted', 'mensaje' => 'Indexación de PDFs iniciada en segundo plano']);
    fastcgi_finish_request();
}

// Configuración
require_once __DIR__ . '/../config_rag.php';
$config = RagConfig::getInstance();
$RAG_SERVICE_URL = $config->getRagServiceUrl('');

/**
 * Indexa un PDF directamente al servicio RAG
 */
function indexarPDFDirecto($archivo_path, $rag_url) {
    $webhook_url = $rag_url . '/webhook/document-uploaded';
    
    // Extraer información del nombre del archivo
    $filename = basename($archivo_path);
    $titulo_limpio = pathinfo($filename, PATHINFO_FILENAME);
    
    // Remover timestamp del final
    $titulo_limpio = preg_replace('/-\d+$/', '', $titulo_limpio);
    
    // Normalizar: convertir guiones a espacios y capitalizar
    $titulo = ucwords(str_replace('-', ' ', $titulo_limpio));
    
    // Generar document_id único basado en el hash del archivo
    $document_id = abs(crc32($archivo_path));
    
    // Preparar datos para el webhook
    $payload = [
        'document_id' => $document_id,
        'titulo' => $titulo,
        'tipo' => 'instructivo',
        'sector_nombre' => 'General',
        'ruta_archivo' => $archivo_path,
        'descripcion' => 'Documento indexado automáticamente desde carpeta /documentos',
        'tags' => '',
        'fecha_creacion' => date('Y-m-d')
    ];
    
    // Configurar cURL
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($payload))
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    // Ejecutar
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code === 200) {
        return ['success' => true, 'response' => json_decode($response, true)];
    } else {
        return ['success' => false, 'error' => $error ?: "HTTP $http_code", 'response' => $response];
    }
}

// Log file para seguimiento
$log_file = __DIR__ . '/../rag-service/.indexacion_pdfs.log';
$log_handle = fopen($log_file, 'a');

function escribir_log($mensaje) {
    global $log_handle;
    if ($log_handle) {
        fwrite($log_handle, date('Y-m-d H:i:s') . " - " . $mensaje . "\n");
    }
}

try {
    escribir_log("=== Inicio de indexación de PDFs faltantes ===");
    
    // 1. Obtener documentos faltantes
    $verificar_url = 'http://localhost/administracion/recursosHumanos/p&p/Controller/verificar_documentos_faltantes.php';
    $ch = curl_init($verificar_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $verificar_response = curl_exec($ch);
    curl_close($ch);
    
    $verificar_data = json_decode($verificar_response, true);
    
    if (!$verificar_data['success'] || $verificar_data['faltantes'] == 0) {
        escribir_log("No hay documentos faltantes para indexar");
        fclose($log_handle);
        exit;
    }
    
    $documentos_faltantes = $verificar_data['documentos_faltantes'];
    $total = count($documentos_faltantes);
    escribir_log("Documentos faltantes detectados: $total");
    
    // 2. Indexar cada documento faltante
    $exitosos = 0;
    $fallidos = 0;
    
    foreach ($documentos_faltantes as $doc) {
        $pdf_path = $doc['ruta'];
        $filename = $doc['filename'];
        
        escribir_log("Indexando: $filename");
        
        if (!file_exists($pdf_path)) {
            escribir_log("ERROR: Archivo no encontrado - $filename");
            $fallidos++;
            continue;
        }
        
        $resultado = indexarPDFDirecto($pdf_path, $RAG_SERVICE_URL);
        
        if ($resultado['success']) {
            $chunks = isset($resultado['response']['chunks_count']) ? $resultado['response']['chunks_count'] : 0;
            escribir_log("✓ Exitoso: $filename - $chunks chunks indexados");
            $exitosos++;
        } else {
            escribir_log("✗ Error: $filename - " . $resultado['error']);
            $fallidos++;
        }
        
        // Pausa entre documentos para no saturar
        usleep(300000); // 300ms
    }
    
    escribir_log("=== Fin de indexación ===");
    escribir_log("Total procesados: $total | Exitosos: $exitosos | Fallidos: $fallidos");
    
} catch (Exception $e) {
    escribir_log("ERROR CRÍTICO: " . $e->getMessage());
}

if ($log_handle) {
    fclose($log_handle);
}
