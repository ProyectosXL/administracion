# DocuGest RAG Service

Servicio de búsqueda semántica y consultas en lenguaje natural sobre documentos corporativos usando Retrieval-Augmented Generation (RAG).

## 📋 Descripción

Este módulo Python proporciona capacidades de inteligencia artificial al sistema DocuGest, permitiendo:

- **Indexación automática** de documentos PDF en una base de datos vectorial
- **Búsqueda semántica** por similitud de contenido
- **Consultas en lenguaje natural** con respuestas generadas por IA
- **Integración vía webhooks** con el sistema PHP existente

## 🏗️ Arquitectura

```
┌─────────────┐                    ┌─────────────────┐
│  DocuGest   │  HTTP Webhooks     │   RAG Service   │
│    (PHP)    │ ─────────────────> │    (Python)     │
│             │                    │                 │
│  SQL Server │                    │   ChromaDB      │
└─────────────┘                    │   (Vectores)    │
                                   │                 │
                                   │  Google Gemini  │
                                   └─────────────────┘
```

### Flujo de Trabajo

**Indexación:**
1. Usuario sube PDF en DocuGest (PHP)
2. PHP guarda archivo y hace POST webhook a RAG service
3. Python extrae texto del PDF
4. Divide texto en chunks semánticos (~1000 chars)
5. Genera embeddings con Google Gemini
6. Almacena en ChromaDB para búsqueda vectorial

**Consulta:**
1. Usuario hace pregunta en lenguaje natural
2. Sistema genera embedding de la pregunta
3. Busca chunks más similares en ChromaDB
4. Construye contexto con documentos relevantes
5. Gemini genera respuesta basada en el contexto
6. Retorna respuesta + fuentes citadas

## 🚀 Requisitos

### Software
- Python 3.10 o superior
- pip (gestor de paquetes de Python)

### Servicios Externos
- **Google AI Studio**: Cuenta gratuita para obtener API key de Gemini
  - Crear cuenta en: https://aistudio.google.com/
  - Obtener API key desde: https://aistudio.google.com/app/apikey

## 📦 Instalación

### 1. Crear Entorno Virtual

```powershell
# Navegar a la carpeta rag-service
cd c:\xampp\htdocs\administracion\recursosHumanos\p&p\rag-service

# Crear entorno virtual
python -m venv venv

# Activar entorno virtual (Windows)
venv\Scripts\activate

# En Linux/Mac:
# source venv/bin/activate
```

### 2. Instalar Dependencias

```powershell
pip install -r requirements.txt
```

### 3. Configurar API Key

Agregar la siguiente línea al archivo `.env` compartido del proyecto (ubicado en la raíz):

```
GOOGLE_API_KEY=tu_api_key_de_gemini_aqui
```

**IMPORTANTE:** Este módulo NO necesita configuración de SQL Server. Solo requiere la GOOGLE_API_KEY.

## ▶️ Ejecución

### Iniciar el Servicio

```powershell
# Asegurarse de estar en rag-service con venv activado
cd c:\xampp\htdocs\administracion\recursosHumanos\p&p\rag-service
venv\Scripts\activate

# Iniciar servidor FastAPI
uvicorn app.main:app --reload --port 8000
```

El servicio quedará corriendo en: http://localhost:8000

- **Documentación interactiva:** http://localhost:8000/docs
- **Documentación alternativa:** http://localhost:8000/redoc

### Verificar que Funciona

Abrir en el navegador: http://localhost:8000/health

Debería retornar:
```json
{
  "status": "ok",
  "version": "1.0.0",
  "chroma_collections": 1,
  "total_chunks": 0
}
```

## 🧪 Testing

### Script de Prueba

El script `test_indexing.py` permite probar el sistema sin depender de PHP:

```powershell
# Asegurarse de tener un PDF en la carpeta documentos
cd scripts
python test_indexing.py --pdf "../documentos/ejemplo.pdf"
```

Esto ejecutará:
1. Extracción de texto del PDF
2. División en chunks
3. Generación de embeddings
4. Indexación en ChromaDB
5. Consulta RAG de prueba

## 📡 Endpoints de la API

### Webhooks (Llamados por PHP)

#### POST `/webhook/document-uploaded`
Indexa un nuevo documento.

**Request:**
```json
{
  "document_id": 123,
  "titulo": "Política de Seguridad",
  "ruta_archivo": "C:\\xampp\\htdocs\\...\\documento.pdf",
  "sector_nombre": "IT",
  "tipo": "politica"
}
```

**Response:**
```json
{
  "status": "success",
  "document_id": 123,
  "chunks_count": 42,
  "tiempo_procesamiento_ms": 8742.5
}
```

#### POST `/webhook/document-updated`
Re-indexa un documento actualizado (misma estructura que `document-uploaded`).

### API de Consultas

#### POST `/api/query`
Consulta RAG en lenguaje natural.

**Request:**
```json
{
  "pregunta": "¿Cuál es la política de vacaciones?",
  "top_k": 5
}
```

**Response:**
```json
{
  "respuesta": "Según la Política de Recursos Humanos...",
  "fuentes": [
    {
      "document_id": 45,
      "titulo": "Política de RRHH",
      "sector": "Recursos Humanos",
      "tipo": "politica",
      "chunk_text": "Los empleados tienen derecho a...",
      "similarity_score": 0.89
    }
  ],
  "total_chunks_encontrados": 5,
  "tiempo_busqueda_ms": 245.3,
  "tiempo_generacion_ms": 1823.7
}
```

### Gestión de Documentos

#### POST `/api/reindex/{document_id}`
Re-indexa un documento específico.

#### DELETE `/api/document/{document_id}`
Elimina todos los chunks de un documento.

### Sistema

#### GET `/health`
Health check del servicio.

#### GET `/api/stats`
Estadísticas del sistema (documentos indexados, chunks, etc.).

## 🔧 Integración con PHP

### Ejemplo de Webhook desde PHP

```php
<?php
// Después de guardar el PDF y el registro en SQL Server

$webhookUrl = "http://localhost:8000/webhook/document-uploaded";

$payload = [
    'document_id' => $documentId,
    'titulo' => $titulo,
    'ruta_archivo' => $rutaCompletaPDF,
    'sector_nombre' => $sectorNombre,
    'tipo' => $tipo
];

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    // Indexación exitosa
    $result = json_decode($response, true);
    echo "Documento indexado: " . $result['chunks_count'] . " chunks";
} else {
    // Error
    error_log("Error en indexación RAG: " . $response);
}
?>
```

## 📊 Tecnologías Utilizadas

- **FastAPI**: Framework web moderno y rápido para Python
- **ChromaDB**: Base de datos vectorial local sin servidor
- **Google Gemini**: 
  - `text-embedding-004`: Generación de embeddings (768 dimensiones)
  - `gemini-2.0-flash-exp`: Generación de respuestas
- **PyMuPDF (fitz)**: Extracción de texto de PDFs
- **LangChain**: División inteligente de texto en chunks
- **Pydantic**: Validación de datos
- **Tenacity**: Retry logic con exponential backoff

## 🐛 Troubleshooting

### Error: "GOOGLE_API_KEY no encontrada"

**Solución:** Verificar que el archivo `.env` en la raíz del proyecto contenga:
```
GOOGLE_API_KEY=tu_api_key_aqui
```

### Error: "Archivo no encontrado"

**Problema:** PHP está enviando rutas relativas o incorrectas.

**Solución:** Asegurarse de que PHP envíe la ruta completa absoluta del PDF:
```php
$rutaCompleta = realpath($rutaArchivo);
```

### Error: "Rate limit exceeded"

**Problema:** Superaste el límite gratuito de Gemini (15 requests/min).

**Solución:** El sistema tiene retry automático. Esperar unos segundos y reintentar. Para mayor capacidad, actualizar a tier pago de Gemini.

### ChromaDB locked

**Problema:** Otra instancia del servicio está corriendo.

**Solución:** 
1. Detener todas las instancias de uvicorn
2. Reiniciar el servicio

### PDF sin texto extraído

**Problema:** El PDF es escaneado sin OCR.

**Solución:** Procesar el PDF con OCR antes de subirlo a DocuGest.

## 📈 Métricas de Rendimiento

- **Indexación:** ~10 segundos por documento (depende del tamaño)
- **Consulta:** ~3 segundos (búsqueda + generación)
- **Capacidad:** 100+ documentos sin degradación

## 🔒 Seguridad

- El servicio corre localmente (localhost:8000)
- No expone puertos externos por defecto
- No almacena datos sensibles en ChromaDB (solo texto de documentos)
- API key de Gemini se carga desde .env (no hardcodeada)

## 📝 Logging

Los logs se guardan en: `rag-service/logs/rag-service.log`

Niveles de log:
- **INFO**: Operaciones normales
- **WARNING**: Situaciones anómalas pero no críticas
- **ERROR**: Errores que requieren atención

## 🔄 Actualización

Para actualizar el servicio:

```powershell
# Detener el servicio (Ctrl+C)

# Activar venv
venv\Scripts\activate

# Actualizar dependencias
pip install -r requirements.txt --upgrade

# Reiniciar servicio
uvicorn app.main:app --reload --port 8000
```

## 👨‍💻 Estructura del Código

```
rag-service/
├── app/
│   ├── __init__.py          # Inicialización del módulo
│   ├── main.py              # API FastAPI con endpoints
│   ├── config.py            # Configuración (solo GOOGLE_API_KEY)
│   ├── models.py            # Modelos Pydantic
│   ├── database.py          # Cliente ChromaDB
│   ├── embeddings.py        # Integración con Gemini embeddings
│   ├── pdf_processor.py     # Extracción de texto de PDFs
│   ├── chunking.py          # División de texto en chunks
│   └── rag.py               # Lógica RAG completa
├── scripts/
│   └── test_indexing.py     # Script de testing
├── chroma_db/               # (Creado automáticamente)
├── logs/                    # Logs del servicio
├── requirements.txt         # Dependencias Python
├── .gitignore
└── README.md
```

## 📄 Licencia

Este módulo es parte del sistema DocuGest y está sujeto a las mismas políticas de uso interno de la organización.

## 🆘 Soporte

Para problemas o consultas:
1. Revisar logs en `logs/rag-service.log`
2. Verificar documentación en http://localhost:8000/docs
3. Consultar sección Troubleshooting de este README

---

**Versión:** 1.0.0  
**Última actualización:** Diciembre 2025
