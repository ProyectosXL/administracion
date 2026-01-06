"""
Configuración del módulo RAG.
Soporta 3 entornos:
- Railway: Lee variables de entorno (GOOGLE_API_KEY, PHP_SERVER_URL, PORT)
- Producción (app.xl.com.ar): Usa API key hardcodeada
- Local: Carga GOOGLE_API_KEY del .env compartido
NO incluye configuración de SQL Server - eso lo maneja PHP.
"""

import os
import socket
from pathlib import Path
from dotenv import load_dotenv

# ============================================================================
# DETECCIÓN DE ENTORNO
# ============================================================================

def is_railway():
    """Detecta si estamos en Railway.app"""
    return os.getenv("RAILWAY_ENVIRONMENT") is not None or os.getenv("RAILWAY_PROJECT_ID") is not None

def is_production():
    """
    Detecta si estamos en producción analizando el hostname.
    Retorna True si está en el servidor de producción.
    """
    try:
        hostname = socket.gethostname().lower()
        # Detectar si estamos en el servidor de producción
        return 'app.xl.com.ar' in hostname or 'xl.com.ar' in hostname
    except:
        return False

IS_RAILWAY = is_railway()
IS_PRODUCTION = is_production() and not IS_RAILWAY
IS_LOCAL = not IS_RAILWAY and not IS_PRODUCTION

# ============================================================================
# CONFIGURACIÓN DE GOOGLE GEMINI API
# ============================================================================

if IS_RAILWAY:
    # Railway: Leer de variables de entorno
    GOOGLE_API_KEY = os.getenv("GOOGLE_API_KEY")
    if not GOOGLE_API_KEY:
        raise ValueError("GOOGLE_API_KEY no configurada en Railway. Configurar en Variables de entorno.")
    
    # Nueva configuración para Railway
    PHP_SERVER_URL = os.getenv("PHP_SERVER_URL")
    if not PHP_SERVER_URL:
        raise ValueError("PHP_SERVER_URL no configurada en Railway. Configurar en Variables de entorno.")
    
    PORT = int(os.getenv("PORT", 8000))
    
elif IS_PRODUCTION:
    # Producción: API key hardcodeada (no necesita .env)
    GOOGLE_API_KEY = "AIzaSyDUAWRkNRV4s11G2w4M3AG1Bu5H1h482DE"
    PHP_SERVER_URL = None  # No usado en producción local
    PORT = 8000
    
else:
    # Local: Cargar desde .env
    env_path = Path(__file__).resolve().parent.parent.parent.parent.parent / ".env"
    load_dotenv(env_path)
    GOOGLE_API_KEY = os.getenv("GOOGLE_API_KEY")
    if not GOOGLE_API_KEY:
        raise ValueError(
            "GOOGLE_API_KEY no encontrada en el archivo .env. "
            "Agregá la API key al archivo .env en la raíz del proyecto."
        )
    PHP_SERVER_URL = None  # No usado en local
    PORT = 8000

# ============================================================================
# CONFIGURACIÓN DE CHROMADB
# ============================================================================

# Ruta donde ChromaDB persistirá los datos
CHROMA_DB_PATH = Path(__file__).parent.parent / "chroma_db"
CHROMA_COLLECTION_NAME = "document_chunks"

# ============================================================================
# CONFIGURACIÓN DE PROCESAMIENTO DE PDF
# ============================================================================

# Configuración de chunking
CHUNK_SIZE = 1000  # Caracteres por chunk
CHUNK_OVERLAP = 200  # Overlap entre chunks para mantener contexto
CHUNK_SEPARATORS = ["\n\n", "\n", ". ", " ", ""]  # Prioriza párrafos completos

# ============================================================================
# CONFIGURACIÓN DE EMBEDDINGS (ARQUITECTURA HÍBRIDA)
# ============================================================================

# Usar embeddings de Google Gemini (consistente en todos los entornos)
# Railway, Local y Producción usan el mismo modelo para compatibilidad
USE_LOCAL_EMBEDDINGS = False

# Modelo de embeddings LOCAL (descarga automática) - OPCIONAL
# paraphrase-multilingual-MiniLM-L12-v2: Multilingüe, 384 dimensiones, rápido
# Solo se usa si USE_LOCAL_EMBEDDINGS = True (requiere sentence-transformers)
LOCAL_EMBEDDING_MODEL = "paraphrase-multilingual-MiniLM-L12-v2"

# Modelo de embeddings de Gemini (RECOMENDADO - consistente en todos los entornos)
GEMINI_EMBEDDING_MODEL = "models/text-embedding-004"
EMBEDDING_DIMENSION = 768  # Dimensión de text-embedding-004

# Task types para embeddings (Gemini API)
EMBEDDING_TASK_DOCUMENT = "retrieval_document"  # Para indexar chunks
EMBEDDING_TASK_QUERY = "retrieval_query"  # Para preguntas

# Batch size para procesar embeddings
EMBEDDING_BATCH_SIZE = 10  # Procesamiento por lotes para eficiencia

# ============================================================================
# CONFIGURACIÓN DE GENERACIÓN DE RESPUESTAS
# ============================================================================

# Modelo de generación (Gemma 3-27B Instruction Tuned - 14,400 RPD, 30 RPM)
GENERATION_MODEL = "models/gemma-3-27b-it"

# Parámetros de generación
GENERATION_TEMPERATURE = 0.7  # Balance entre creatividad y precisión
GENERATION_MAX_TOKENS = 2048  # Aumentado para respuestas completas

# ============================================================================
# CONFIGURACIÓN DE RAG
# ============================================================================

# Número de chunks a recuperar en búsqueda semántica
DEFAULT_TOP_K = 5

# ============================================================================
# CONFIGURACIÓN DE RATE LIMITING Y RETRIES
# ============================================================================

# Rate limit de Gemini API (tier gratuito: 15 requests/minuto)
GEMINI_RPM_LIMIT = 15

# Configuración de retries con exponential backoff
MAX_RETRIES = 3
RETRY_MIN_WAIT = 1  # segundos
RETRY_MAX_WAIT = 60  # segundos

# ============================================================================
# CONFIGURACIÓN DE LOGGING
# ============================================================================

LOG_LEVEL = "INFO"
LOG_DIR = Path(__file__).parent.parent / "logs"
LOG_FILE = LOG_DIR / "rag-service.log"

# Crear directorio de logs si no existe
LOG_DIR.mkdir(exist_ok=True)

# ============================================================================
# CONFIGURACIÓN DE RUTAS
# ============================================================================

# Ruta base del proyecto p&p
BASE_DIR = Path(__file__).parent.parent.parent

# Ruta donde se guardan los PDFs
DOCUMENTOS_PATH = BASE_DIR / "documentos"

# ============================================================================
# PROMPT TEMPLATE
# ============================================================================

RAG_PROMPT_TEMPLATE = """Sos un asistente virtual inteligente de DocuGest, especializado en políticas y procedimientos corporativos.

CONTEXTO DE DOCUMENTOS DISPONIBLES:
{contexto_chunks}

PREGUNTA DEL USUARIO:
{pregunta}

INSTRUCCIONES:
1. Si la pregunta es un saludo, presentación o consulta general sobre vos (ej: "hola", "¿quién eres?", "¿qué puedes hacer?"):
   - Respondé de forma amigable y natural
   - Presentate como el asistente virtual de DocuGest
   - Explicá brevemente que podés ayudar con información sobre políticas y procedimientos
   - NO menciones que no encontraste información en documentos

2. Si la pregunta requiere información específica de documentos:
   - Usá ÚNICAMENTE la información de los documentos proporcionados arriba
   - Cuando cites un documento, usa EXACTAMENTE el título que aparece en "Documento:" (por ejemplo, si dice "Documento: Instructivo creación de repositorio en GitHub", citá ese título exacto)
   - NO uses nombres de archivo, rutas o identificadores técnicos
   - Cita los documentos entre comillas dobles, por ejemplo: según el instructivo "Instructivo creación de repositorio en GitHub"
   - Si la información no está en los documentos, decí: "No encontré esa información en los documentos disponibles"
   - Sé preciso, claro y detallado

3. Siempre respondé en español con tono profesional pero amigable
4. Si hay información contradictoria entre documentos, mencionalo

RESPUESTA:
"""
