<?php
/**
 * Script de indexación masiva de documentos existentes al servicio RAG
 * 
 * Este script lee todos los documentos de la tabla Politicas_Procedimientos
 * y los envía al servicio RAG para su indexación en ChromaDB.
 * 
 * Uso: Ejecutar directamente desde navegador o CLI
 */

// Configuración
set_time_limit(300); // 5 minutos máximo
ini_set('memory_limit', '256M');

// Incluir la clase Politica
require_once '../Class/Politica.php';

// URL del servicio RAG
$RAG_SERVICE_URL = 'http://localhost:8000';

// Crear instancia de la clase
$politicaObj = new Politica();

// Obtener todos los documentos activos
$documentos = $politicaObj->obtenerTodos();

// Estadísticas
$total = count($documentos);
$exitosos = 0;
$fallidos = 0;
$errores = [];

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Indexación Masiva RAG</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            padding: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .stat-card.total {
            background: #3498db;
            color: white;
        }
        .stat-card.success {
            background: #27ae60;
            color: white;
        }
        .stat-card.error {
            background: #e74c3c;
            color: white;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .progress-container {
            background: #ecf0f1;
            border-radius: 10px;
            height: 30px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .log-container {
            max-height: 400px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        .log-item {
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .log-item.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .log-item.error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .log-icon {
            font-size: 18px;
        }
        .log-text {
            flex: 1;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 Indexación Masiva de Documentos</h1>
        <p class='subtitle'>Servicio RAG - DocuGest</p>
        
        <div class='stats'>
            <div class='stat-card total'>
                <div class='stat-number'>$total</div>
                <div class='stat-label'>Total Documentos</div>
            </div>
            <div class='stat-card success'>
                <div class='stat-number' id='success-count'>0</div>
                <div class='stat-label'>Indexados</div>
            </div>
            <div class='stat-card error'>
                <div class='stat-number' id='error-count'>0</div>
                <div class='stat-label'>Errores</div>
            </div>
        </div>
        
        <div class='progress-container'>
            <div class='progress-bar' id='progress-bar' style='width: 0%'>0%</div>
        </div>
        
        <div class='log-container' id='log-container'>";

// Función para enviar al servicio RAG
function indexarDocumentoRAG($documento, $rag_url) {
    $webhook_url = $rag_url . '/webhook/document-uploaded';
    
    // Preparar datos para el webhook
    $payload = [
        'document_id' => $documento['id'],
        'titulo' => $documento['titulo'],
        'tipo' => $documento['tipo'],
        'sector' => $documento['sector_nombre'],
        'ruta_pdf' => $documento['ruta_archivo'],
        'descripcion' => isset($documento['descripcion']) ? $documento['descripcion'] : '',
        'tags' => isset($documento['tags']) ? $documento['tags'] : '',
        'fecha_creacion' => isset($documento['fecha_creacion']) ? $documento['fecha_creacion']->format('Y-m-d') : date('Y-m-d')
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Ejecutar y capturar respuesta
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

// Procesar cada documento
foreach ($documentos as $index => $doc) {
    $numero = $index + 1;
    $titulo = htmlspecialchars($doc['titulo']);
    
    // Verificar que el archivo existe
    if (!file_exists($doc['ruta_archivo'])) {
        echo "<div class='log-item error'>
                <span class='log-icon'>❌</span>
                <span class='log-text'><strong>[$numero/$total]</strong> $titulo - <em>Archivo no encontrado: {$doc['ruta_archivo']}</em></span>
              </div>";
        echo "<script>
                document.getElementById('error-count').textContent = " . (++$fallidos) . ";
                document.getElementById('progress-bar').style.width = '" . (($numero / $total) * 100) . "%';
                document.getElementById('progress-bar').textContent = '" . round(($numero / $total) * 100) . "%';
                document.getElementById('log-container').scrollTop = document.getElementById('log-container').scrollHeight;
              </script>";
        flush();
        ob_flush();
        continue;
    }
    
    // Enviar al servicio RAG
    $resultado = indexarDocumentoRAG($doc, $RAG_SERVICE_URL);
    
    if ($resultado['success']) {
        $chunks = isset($resultado['response']['chunks_count']) ? $resultado['response']['chunks_count'] : 0;
        echo "<div class='log-item success'>
                <span class='log-icon'>✅</span>
                <span class='log-text'><strong>[$numero/$total]</strong> $titulo - <em>$chunks chunks indexados</em></span>
              </div>";
        $exitosos++;
    } else {
        $error_msg = htmlspecialchars($resultado['error']);
        echo "<div class='log-item error'>
                <span class='log-icon'>❌</span>
                <span class='log-text'><strong>[$numero/$total]</strong> $titulo - <em>Error: $error_msg</em></span>
              </div>";
        $fallidos++;
        $errores[] = ['documento' => $titulo, 'error' => $resultado['error']];
    }
    
    // Actualizar estadísticas y progreso
    echo "<script>
            document.getElementById('success-count').textContent = $exitosos;
            document.getElementById('error-count').textContent = $fallidos;
            document.getElementById('progress-bar').style.width = '" . (($numero / $total) * 100) . "%';
            document.getElementById('progress-bar').textContent = '" . round(($numero / $total) * 100) . "%';
            document.getElementById('log-container').scrollTop = document.getElementById('log-container').scrollHeight;
          </script>";
    
    flush();
    ob_flush();
    
    // Pequeña pausa para no saturar el servicio
    usleep(200000); // 200ms
}

echo "      </div>
        
        <h3 style='margin-top: 30px; color: #2c3e50;'>📊 Resumen Final</h3>
        <ul style='background: #f8f9fa; padding: 20px; border-radius: 6px;'>
            <li><strong>Total procesados:</strong> $total documentos</li>
            <li style='color: #27ae60;'><strong>Exitosos:</strong> $exitosos</li>
            <li style='color: #e74c3c;'><strong>Fallidos:</strong> $fallidos</li>
            <li><strong>Tasa de éxito:</strong> " . ($total > 0 ? round(($exitosos / $total) * 100, 2) : 0) . "%</li>
        </ul>";

if (!empty($errores)) {
    echo "<h4 style='color: #e74c3c; margin-top: 20px;'>⚠️ Detalles de Errores</h4>
          <ul style='background: #fff3cd; padding: 20px; border-radius: 6px; border-left: 4px solid #ffc107;'>";
    foreach ($errores as $error) {
        echo "<li><strong>" . htmlspecialchars($error['documento']) . ":</strong> " . htmlspecialchars($error['error']) . "</li>";
    }
    echo "</ul>";
}

echo "  <a href='../index.php' class='btn'>← Volver al inicio</a>
    </div>
</body>
</html>";
?>
