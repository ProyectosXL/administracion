"""
API FastAPI para el servicio RAG de DocuGest.
Endpoints para webhooks desde PHP y consultas RAG.
"""

import logging
import time
import requests
from typing import Optional
from pathlib import Path

from fastapi import FastAPI, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse

from app import __version__
from app.models import (
    DocumentUploadedWebhook,
    DocumentUpdatedWebhook,
    QueryRequest,
    QueryResponse,
    IndexingResponse,
    DeleteResponse,
    SuccessResponse,
    ErrorResponse,
    HealthResponse,
    StatsResponse,
    ReindexRequest,
    TestIndexRequest
)
from app.pdf_processor import extract_text_from_pdf, extract_text_from_pdf_bytes, PDFProcessingError
from app.chunking import create_chunks, generate_chunk_id, ChunkingError
from app.embeddings import generate_embeddings_batch, test_gemini_connection, EmbeddingError, EMBEDDING_DIMENSION
from app.database import get_chroma_client, ChromaDBError
from app.rag import generate_rag_response, RAGError
from app.config import DOCUMENTOS_PATH, IS_RAILWAY, PHP_SERVER_URL

# ============================================================================
# CONFIGURACIÓN DE LOGGING
# ============================================================================

from app.config import LOG_FILE, LOG_LEVEL, IS_RAILWAY

# En Railway, solo logs a STDOUT (no archivos)
if IS_RAILWAY:
    logging.basicConfig(
        level=LOG_LEVEL,
        format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        handlers=[
            logging.StreamHandler()  # Solo STDOUT en Railway
        ]
    )
else:
    # Local/Producción: logs a archivo y STDOUT
    logging.basicConfig(
        level=LOG_LEVEL,
        format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        handlers=[
            logging.FileHandler(LOG_FILE),
            logging.StreamHandler()
        ]
    )

logger = logging.getLogger(__name__)

# ============================================================================
# INICIALIZACIÓN DE FASTAPI
# ============================================================================

app = FastAPI(
    title="DocuGest RAG Service",
    description="Servicio de búsqueda semántica y consultas sobre documentos corporativos",
    version=__version__,
    docs_url="/docs",
    redoc_url="/redoc"
)

# Configurar CORS (permitir requests desde PHP)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # En producción, especificar dominios
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ============================================================================
# EVENT HANDLERS
# ============================================================================

@app.on_event("startup")
async def startup_event():
    """
    Ejecutar al iniciar el servicio.
    Valida configuración, conexiones y re-indexa automáticamente si es necesario.
    En Railway, inicia el scheduler de auto-refresh.
    """
    logger.info("=" * 80)
    logger.info(f"Iniciando DocuGest RAG Service v{__version__}")
    if IS_RAILWAY:
        logger.info("Entorno: RAILWAY")
    logger.info("=" * 80)
    
    try:
        # Verificar conexión con sistema de embeddings
        logger.info("Verificando sistema de embeddings...")
        if not test_gemini_connection():
            logger.error("⚠️  No se pudo inicializar el sistema de embeddings")
        
        # Inicializar ChromaDB
        logger.info("Inicializando ChromaDB...")
        chroma_client = get_chroma_client()
        stats = chroma_client.get_stats()
        logger.info(
            f"✓ ChromaDB OK - {stats['total_chunks']} chunks, "
            f"{stats['total_documentos_indexados']} documentos indexados"
        )
        
        # Iniciar scheduler solo en Railway
        if IS_RAILWAY:
            from app.scheduler import start_scheduler
            start_scheduler()
        
        # DETECCIÓN AUTOMÁTICA Y RE-INDEXACIÓN (solo para entorno local/producción con filesystem)
        if not IS_RAILWAY:
            needs_reindex = False
        
            # 1. Verificar si hay PDFs pero no hay chunks indexados
            pdf_files = list(Path(DOCUMENTOS_PATH).glob("*.pdf")) if Path(DOCUMENTOS_PATH).exists() else []
            if pdf_files and stats['total_chunks'] == 0:
                logger.warning(f"⚠️  Encontrados {len(pdf_files)} PDFs pero ChromaDB está vacío")
                needs_reindex = True
            
            # 2. Verificar dimensión de embeddings (si cambió el modelo)
            if stats['total_chunks'] > 0:
                # Intentar obtener un chunk para verificar dimensión
                try:
                    collection = chroma_client.collection
                    sample = collection.get(limit=1, include=["embeddings"])
                    if sample['embeddings'] and len(sample['embeddings'][0]) != EMBEDDING_DIMENSION:
                        logger.warning(
                            f"⚠️  Dimensión de embeddings incorrecta: "
                            f"{len(sample['embeddings'][0])} vs esperado {EMBEDDING_DIMENSION}"
                        )
                        needs_reindex = True
                except Exception as e:
                    logger.warning(f"No se pudo verificar dimensión de embeddings: {e}")
            
            # 3. Re-indexar automáticamente si es necesario
            if needs_reindex:
                logger.info("Re-indexacion automatica iniciada...")
                try:
                    # Recrear colección (elimina y recrea con nueva dimensión)
                    chroma_client.reset_collection()
                    logger.info("Colección ChromaDB recreada")
                    
                    # Indexar todos los PDFs
                    indexed_count = 0
                    failed_count = 0
                    
                    for pdf_path in pdf_files:
                        try:
                            logger.info(f"Indexando: {pdf_path.name}")
                            
                            # Extraer texto (retorna tupla)
                            text, num_pages = extract_text_from_pdf(str(pdf_path))
                            
                            # Preparar metadatos base
                            base_metadata = {
                                'document_id': 0,  # Se asigna al guardar
                                'documento_nombre': pdf_path.name,
                                'titulo': pdf_path.stem,
                                'sector': 'General',
                                'tipo': 'politica'
                            }
                            
                            # Crear chunks
                            chunks = create_chunks(text, base_metadata)
                            
                            # Generar embeddings
                            chunk_texts = [chunk['text'] for chunk in chunks]
                            embeddings = generate_embeddings_batch(chunk_texts, show_progress=False)
                            
                            # Preparar datos para ChromaDB
                            chunk_ids = []
                            chunk_metadatas = []
                            for i, chunk in enumerate(chunks):
                                chunk_id = f"doc_{indexed_count}_{pdf_path.stem}_chunk_{i}"
                                chunk_ids.append(chunk_id)
                                chunk_metadatas.append(chunk['metadata'])
                            
                            # Agregar a ChromaDB
                            chroma_client.add_chunks(
                                chunk_ids=chunk_ids,
                                embeddings=embeddings,
                                documents=chunk_texts,
                                metadatas=chunk_metadatas
                            )
                            indexed_count += 1
                            logger.info(f"  OK {pdf_path.name}: {len(chunks)} chunks indexados")
                            
                        except Exception as e:
                            failed_count += 1
                            logger.error(f"  X Error indexando {pdf_path.name}: {e}")
                    
                    logger.info(
                        f"Re-indexacion completada: {indexed_count} documentos OK, "
                        f"{failed_count} errores"
                    )
                    
                    # Actualizar stats
                    stats = chroma_client.get_stats()
                    
                except Exception as e:
                    logger.error(f"Error durante re-indexación automática: {e}")
        
        logger.info("=" * 80)
        logger.info("Servicio RAG iniciado exitosamente")
        if IS_RAILWAY:
            logger.info("  Escuchando en: Railway (puerto asignado)")
        else:
            logger.info("  Escuchando en: http://localhost:8000")
        logger.info("  Documentacion: http://localhost:8000/docs")
        logger.info(f"  Documentos indexados: {stats['total_documentos_indexados']}")
        logger.info(f"  Chunks totales: {stats['total_chunks']}")
        logger.info("=" * 80)
        
    except Exception as e:
        logger.error(f"Error durante startup: {str(e)}")
        raise


@app.on_event("shutdown")
async def shutdown_event():
    """
    Ejecutar al cerrar el servicio.
    """
    logger.info("Cerrando DocuGest RAG Service...")


# ============================================================================
# ENDPOINTS - WEBHOOKS (Desde PHP)
# ============================================================================

@app.post(
    "/webhook/document-uploaded",
    response_model=IndexingResponse,
    status_code=status.HTTP_200_OK,
    tags=["Webhooks"]
)
async def webhook_document_uploaded(payload: DocumentUploadedWebhook):
    """
    Webhook llamado por PHP cuando se sube un nuevo documento.
    
    Proceso:
    1. Recibe información del documento
    2. Extrae texto del PDF
    3. Genera chunks
    4. Crea embeddings
    5. Indexa en ChromaDB
    """
    start_time = time.time()
    
    try:
        logger.info(
            f"Webhook recibido: Nuevo documento {payload.document_id} - {payload.titulo}"
        )
        
        # 1. Extraer texto del PDF
        logger.info(f"Extrayendo texto de: {payload.ruta_archivo}")
        text, num_pages = extract_text_from_pdf(payload.ruta_archivo)
        logger.info(f"✓ Texto extraído: {len(text)} caracteres, {num_pages} páginas")
        
        # 2. Crear chunks
        metadata = {
            'document_id': payload.document_id,
            'titulo': payload.titulo,
            'sector': payload.sector_nombre,
            'tipo': payload.tipo
        }
        
        logger.info("Creando chunks...")
        chunks = create_chunks(text, metadata)
        logger.info(f"✓ {len(chunks)} chunks creados")
        
        # 3. Generar embeddings
        logger.info("Generando embeddings...")
        chunk_texts = [chunk['text'] for chunk in chunks]
        embeddings = generate_embeddings_batch(chunk_texts, show_progress=False)
        logger.info(f"✓ {len(embeddings)} embeddings generados")
        
        # 4. Preparar datos para ChromaDB
        chunk_ids = [
            generate_chunk_id(payload.document_id, chunk['metadata']['chunk_index'])
            for chunk in chunks
        ]
        documents = chunk_texts
        metadatas = [chunk['metadata'] for chunk in chunks]
        
        # 5. Indexar en ChromaDB
        logger.info("Indexando en ChromaDB...")
        chroma_client = get_chroma_client()
        chroma_client.add_chunks(chunk_ids, embeddings, documents, metadatas)
        
        elapsed_ms = (time.time() - start_time) * 1000
        
        logger.info(
            f"✓ Documento {payload.document_id} indexado exitosamente en {elapsed_ms:.2f}ms"
        )
        
        return IndexingResponse(
            status="success",
            document_id=payload.document_id,
            chunks_count=len(chunks),
            tiempo_procesamiento_ms=elapsed_ms
        )
        
    except PDFProcessingError as e:
        logger.error(f"Error al procesar PDF: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Error al procesar PDF: {str(e)}"
        )
    except (ChunkingError, EmbeddingError, ChromaDBError) as e:
        logger.error(f"Error en indexación: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error en indexación: {str(e)}"
        )
    except Exception as e:
        logger.error(f"Error inesperado: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error inesperado: {str(e)}"
        )


@app.post(
    "/webhook/document-updated",
    response_model=IndexingResponse,
    status_code=status.HTTP_200_OK,
    tags=["Webhooks"]
)
async def webhook_document_updated(payload: DocumentUpdatedWebhook):
    """
    Webhook llamado por PHP cuando se actualiza un documento existente.
    
    Proceso:
    1. Eliminar chunks antiguos
    2. Re-indexar documento completo
    """
    start_time = time.time()
    
    try:
        logger.info(
            f"Webhook recibido: Actualización documento {payload.document_id} - {payload.titulo}"
        )
        
        # 1. Eliminar chunks antiguos
        chroma_client = get_chroma_client()
        deleted_count = chroma_client.delete_document_chunks(payload.document_id)
        logger.info(f"✓ Eliminados {deleted_count} chunks antiguos")
        
        # 2. Re-indexar (mismo proceso que document-uploaded)
        text, num_pages = extract_text_from_pdf(payload.ruta_archivo)
        
        metadata = {
            'document_id': payload.document_id,
            'titulo': payload.titulo,
            'sector': payload.sector_nombre,
            'tipo': payload.tipo
        }
        
        chunks = create_chunks(text, metadata)
        chunk_texts = [chunk['text'] for chunk in chunks]
        embeddings = generate_embeddings_batch(chunk_texts, show_progress=False)
        
        chunk_ids = [
            generate_chunk_id(payload.document_id, chunk['metadata']['chunk_index'])
            for chunk in chunks
        ]
        documents = chunk_texts
        metadatas = [chunk['metadata'] for chunk in chunks]
        
        chroma_client.add_chunks(chunk_ids, embeddings, documents, metadatas)
        
        elapsed_ms = (time.time() - start_time) * 1000
        
        logger.info(
            f"✓ Documento {payload.document_id} re-indexado exitosamente en {elapsed_ms:.2f}ms"
        )
        
        return IndexingResponse(
            status="success",
            document_id=payload.document_id,
            chunks_count=len(chunks),
            tiempo_procesamiento_ms=elapsed_ms
        )
        
    except Exception as e:
        logger.error(f"Error en actualización: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error en actualización: {str(e)}"
        )


# ============================================================================
# ENDPOINTS - API DE CONSULTAS RAG
# ============================================================================

@app.post(
    "/api/query",
    response_model=QueryResponse,
    status_code=status.HTTP_200_OK,
    tags=["RAG"]
)
async def query_documents(request: QueryRequest):
    """
    Endpoint principal para consultas RAG en lenguaje natural.
    
    Retorna una respuesta generada basada en los documentos más relevantes.
    """
    try:
        logger.info(f"Consulta recibida: '{request.pregunta[:100]}...'")
        
        # Generar respuesta RAG
        result = generate_rag_response(
            query=request.pregunta,
            top_k=request.top_k
        )
        
        return QueryResponse(**result)
        
    except RAGError as e:
        error_msg = str(e)
        logger.error(f"Error en RAG: {error_msg}")
        
        # Detectar error de cuota de API
        if "429" in error_msg or "quota" in error_msg.lower():
            raise HTTPException(
                status_code=status.HTTP_429_TOO_MANY_REQUESTS,
                detail="Has excedido la cuota de la API de Gemini. Por favor, espera unos momentos e intenta nuevamente."
            )
        
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error al procesar consulta: {error_msg}"
        )
    except Exception as e:
        logger.error(f"Error inesperado: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error inesperado: {str(e)}"
        )


# ============================================================================
# ENDPOINTS - GESTIÓN DE DOCUMENTOS
# ============================================================================

@app.post(
    "/api/reindex/{document_id}",
    response_model=IndexingResponse,
    status_code=status.HTTP_200_OK,
    tags=["Documentos"]
)
async def reindex_document(document_id: int, payload: ReindexRequest):
    """
    Re-indexa un documento específico.
    Útil cuando se necesita forzar una re-indexación.
    """
    start_time = time.time()
    
    try:
        logger.info(f"Re-indexación solicitada para documento {document_id}")
        
        # Eliminar chunks antiguos
        chroma_client = get_chroma_client()
        deleted_count = chroma_client.delete_document_chunks(document_id)
        logger.info(f"✓ Eliminados {deleted_count} chunks antiguos")
        
        # Re-indexar
        text, _ = extract_text_from_pdf(payload.ruta_archivo)
        
        metadata = {
            'document_id': document_id,
            'titulo': payload.titulo,
            'sector': payload.sector_nombre,
            'tipo': payload.tipo
        }
        
        chunks = create_chunks(text, metadata)
        chunk_texts = [chunk['text'] for chunk in chunks]
        embeddings = generate_embeddings_batch(chunk_texts, show_progress=False)
        
        chunk_ids = [
            generate_chunk_id(document_id, chunk['metadata']['chunk_index'])
            for chunk in chunks
        ]
        documents = chunk_texts
        metadatas = [chunk['metadata'] for chunk in chunks]
        
        chroma_client.add_chunks(chunk_ids, embeddings, documents, metadatas)
        
        elapsed_ms = (time.time() - start_time) * 1000
        
        logger.info(f"✓ Re-indexación completada en {elapsed_ms:.2f}ms")
        
        return IndexingResponse(
            status="success",
            document_id=document_id,
            chunks_count=len(chunks),
            tiempo_procesamiento_ms=elapsed_ms
        )
        
    except Exception as e:
        logger.error(f"Error en re-indexación: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error en re-indexación: {str(e)}"
        )


@app.delete(
    "/api/document/{document_id}",
    response_model=DeleteResponse,
    status_code=status.HTTP_200_OK,
    tags=["Documentos"]
)
async def delete_document(document_id: int):
    """
    Elimina todos los chunks de un documento específico.
    Llamar cuando PHP elimina un documento.
    """
    try:
        logger.info(f"Solicitud de eliminación para documento {document_id}")
        
        chroma_client = get_chroma_client()
        deleted_count = chroma_client.delete_document_chunks(document_id)
        
        logger.info(f"✓ {deleted_count} chunks eliminados")
        
        return DeleteResponse(
            status="success",
            document_id=document_id,
            deleted_chunks=deleted_count
        )
        
    except ChromaDBError as e:
        logger.error(f"Error al eliminar: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error al eliminar documento: {str(e)}"
        )


# ============================================================================
# ENDPOINTS - HEALTH CHECK Y ESTADÍSTICAS
# ============================================================================

@app.get(
    "/health",
    response_model=HealthResponse,
    status_code=status.HTTP_200_OK,
    tags=["Sistema"]
)
async def health_check():
    """
    Health check del servicio.
    Verifica que todo esté funcionando correctamente.
    """
    try:
        chroma_client = get_chroma_client()
        stats = chroma_client.get_stats()
        
        return HealthResponse(
            status="ok",
            version=__version__,
            chroma_collections=1,
            total_chunks=stats['total_chunks']
        )
        
    except Exception as e:
        logger.error(f"Health check falló: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail=f"Servicio no disponible: {str(e)}"
        )


@app.get(
    "/api/stats",
    response_model=StatsResponse,
    status_code=status.HTTP_200_OK,
    tags=["Sistema"]
)
async def get_statistics():
    """
    Obtiene estadísticas del sistema RAG.
    """
    try:
        chroma_client = get_chroma_client()
        stats = chroma_client.get_stats()
        
        return StatsResponse(
            total_documentos_indexados=stats['total_documentos_indexados'],
            total_chunks=stats['total_chunks'],
            documentos_por_tipo=stats['documentos_por_tipo'],
            document_ids=stats['document_ids'],
            documents_by_title=stats.get('documents_by_title', {})
        )
        
    except Exception as e:
        logger.error(f"Error al obtener stats: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error al obtener estadísticas: {str(e)}"
        )


# ============================================================================
# ENDPOINTS - TESTING
# ============================================================================

@app.post(
    "/test/index-sample",
    response_model=IndexingResponse,
    status_code=status.HTTP_200_OK,
    tags=["Testing"]
)
async def test_index_sample(payload: TestIndexRequest):
    """
    Endpoint de prueba para indexar un PDF sin depender de PHP.
    Útil para verificar que el sistema funciona correctamente.
    """
    start_time = time.time()
    
    try:
        logger.info(f"Test: Indexando PDF de prueba: {payload.pdf_path}")
        
        # Usar un document_id temporal para testing
        test_document_id = 999999
        
        # Procesar PDF
        text, _ = extract_text_from_pdf(payload.pdf_path)
        
        metadata = {
            'document_id': test_document_id,
            'titulo': payload.titulo,
            'sector': payload.sector,
            'tipo': payload.tipo
        }
        
        chunks = create_chunks(text, metadata)
        chunk_texts = [chunk['text'] for chunk in chunks]
        embeddings = generate_embeddings_batch(chunk_texts, show_progress=True)
        
        chunk_ids = [
            generate_chunk_id(test_document_id, chunk['metadata']['chunk_index'])
            for chunk in chunks
        ]
        documents = chunk_texts
        metadatas = [chunk['metadata'] for chunk in chunks]
        
        chroma_client = get_chroma_client()
        chroma_client.add_chunks(chunk_ids, embeddings, documents, metadatas)
        
        elapsed_ms = (time.time() - start_time) * 1000
        
        logger.info(f"✓ Test completado en {elapsed_ms:.2f}ms")
        
        return IndexingResponse(
            status="success",
            document_id=test_document_id,
            chunks_count=len(chunks),
            tiempo_procesamiento_ms=elapsed_ms
        )
        
    except Exception as e:
        logger.error(f"Error en test: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error en test: {str(e)}"
        )


# ============================================================================
# ROOT ENDPOINT
# ============================================================================

@app.get("/", tags=["Sistema"])
async def root():
    """
    Endpoint raíz con información del servicio.
    """
    return {
        "service": "DocuGest RAG Service",
        "version": __version__,
        "status": "running",
        "documentation": "/docs",
        "railway": IS_RAILWAY
    }


@app.post(
    "/admin/refresh",
    status_code=status.HTTP_200_OK,
    tags=["Admin"]
)
async def refresh_from_php():
    """
    Sincroniza documentos desde PHP server (solo Railway).
    Descarga e indexa documentos faltantes.
    
    Este endpoint:
    1. Obtiene lista de documentos desde {PHP_SERVER_URL}/api/listar_documentos.php
    2. Compara con documentos ya indexados en ChromaDB
    3. Para documentos faltantes:
       - Descarga PDF desde {PHP_SERVER_URL}/api/descargar_pdf.php?id=X
       - Extrae texto e indexa en ChromaDB
    4. Retorna resumen de la sincronización
    
    Returns:
        {"status": "success", "nuevos_indexados": 3, "total_chunks": 150, ...}
    """
    if not IS_RAILWAY:
        raise HTTPException(
            status_code=400,
            detail="Endpoint solo disponible en Railway"
        )
    
    try:
        logger.info("=" * 80)
        logger.info("🔄 Iniciando sincronización desde PHP server...")
        logger.info(f"PHP Server: {PHP_SERVER_URL}")
        
        # 1. Obtener lista de documentos desde PHP
        logger.info("Obteniendo lista de documentos desde PHP...")
        response = requests.get(
            f"{PHP_SERVER_URL}/api/listar_documentos.php",
            timeout=30
        )
        response.raise_for_status()
        php_data = response.json()
        
        if not php_data.get('success'):
            raise HTTPException(400, "Error al obtener documentos de PHP: " + php_data.get('mensaje', 'Error desconocido'))
        
        php_docs = php_data['documentos']
        logger.info(f"✓ Obtenidos {len(php_docs)} documentos desde PHP")
        
        # 2. Obtener documentos ya indexados en ChromaDB
        chroma_client = get_chroma_client()
        stats = chroma_client.get_stats()
        
        # Obtener títulos de documentos indexados
        collection = chroma_client.collection
        all_metadata = collection.get(include=["metadatas"])
        indexed_titles = set()
        for metadata in all_metadata['metadatas']:
            if 'titulo' in metadata:
                indexed_titles.add(metadata['titulo'])
        
        logger.info(f"✓ ChromaDB tiene {len(indexed_titles)} documentos indexados")
        
        # 3. Identificar documentos faltantes
        nuevos_docs = []
        for doc in php_docs:
            if doc['titulo'] not in indexed_titles:
                nuevos_docs.append(doc)
        
        logger.info(f"📋 Documentos faltantes: {len(nuevos_docs)}")
        
        if len(nuevos_docs) == 0:
            logger.info("✅ Todos los documentos ya están indexados")
            return {
                "status": "success",
                "mensaje": "Todos los documentos ya están indexados",
                "nuevos_indexados": 0,
                "ya_indexados": len(indexed_titles),
                "total_chunks": stats['total_chunks'],
                "total_documentos": stats['total_documentos_indexados']
            }
        
        # 4. Descargar e indexar documentos faltantes
        indexados = 0
        errores = 0
        chunks_agregados = 0
        
        for doc in nuevos_docs:
            try:
                doc_id = doc['id']
                titulo = doc['titulo']
                logger.info(f"📥 Descargando: {titulo} (ID: {doc_id})")
                
                # Descargar PDF
                pdf_response = requests.get(
                    f"{PHP_SERVER_URL}/api/descargar_pdf.php?id={doc_id}",
                    timeout=60
                )
                pdf_response.raise_for_status()
                pdf_bytes = pdf_response.content
                
                logger.info(f"  ✓ Descargado: {len(pdf_bytes)} bytes")
                
                # Extraer texto del PDF
                text, num_pages = extract_text_from_pdf_bytes(pdf_bytes)
                logger.info(f"  ✓ Texto extraído: {len(text)} caracteres, {num_pages} páginas")
                
                # Preparar metadata
                metadata = {
                    'document_id': doc_id,
                    'titulo': titulo,
                    'documento_nombre': doc.get('archivo_nombre', f"{titulo}.pdf"),
                    'sector': doc.get('sector', 'General'),
                    'tipo': doc.get('tipo', 'politica'),
                    'ruta_archivo': doc.get('ruta_archivo', '')
                }
                
                # Crear chunks
                chunks = create_chunks(text, metadata)
                logger.info(f"  ✓ Chunks creados: {len(chunks)}")
                
                # Generar embeddings
                chunk_texts = [chunk['text'] for chunk in chunks]
                embeddings = generate_embeddings_batch(chunk_texts, show_progress=False)
                logger.info(f"  ✓ Embeddings generados: {len(embeddings)}")
                
                # Preparar datos para ChromaDB
                chunk_ids = []
                chunk_metadatas = []
                for i, chunk in enumerate(chunks):
                    chunk_id = f"doc_{doc_id}_{i}"
                    chunk_ids.append(chunk_id)
                    chunk_metadatas.append(chunk['metadata'])
                
                # Agregar a ChromaDB
                chroma_client.add_chunks(
                    chunk_ids=chunk_ids,
                    embeddings=embeddings,
                    documents=chunk_texts,
                    metadatas=chunk_metadatas
                )
                
                indexados += 1
                chunks_agregados += len(chunks)
                logger.info(f"  ✅ Indexado exitosamente: {titulo}")
                
            except Exception as e:
                errores += 1
                logger.error(f"  ❌ Error indexando {doc.get('titulo', 'desconocido')}: {str(e)}")
                continue
        
        # 5. Obtener stats actualizados
        stats_final = chroma_client.get_stats()
        
        logger.info("=" * 80)
        logger.info(f"✅ Sincronización completada")
        logger.info(f"  Nuevos indexados: {indexados}")
        logger.info(f"  Errores: {errores}")
        logger.info(f"  Chunks agregados: {chunks_agregados}")
        logger.info(f"  Total documentos: {stats_final['total_documentos_indexados']}")
        logger.info(f"  Total chunks: {stats_final['total_chunks']}")
        logger.info("=" * 80)
        
        return {
            "status": "success",
            "mensaje": f"Sincronización completada: {indexados} nuevos documentos indexados",
            "nuevos_indexados": indexados,
            "errores": errores,
            "chunks_agregados": chunks_agregados,
            "total_chunks": stats_final['total_chunks'],
            "total_documentos": stats_final['total_documentos_indexados']
        }
        
    except requests.exceptions.RequestException as e:
        logger.error(f"❌ Error de conexión con PHP server: {str(e)}")
        raise HTTPException(
            status_code=503,
            detail=f"Error de conexión con PHP server: {str(e)}"
        )
    except Exception as e:
        logger.error(f"❌ Error inesperado en refresh: {str(e)}")
        raise HTTPException(
            status_code=500,
            detail=f"Error interno: {str(e)}"
        )


@app.post(
    "/admin/reset-database",
    status_code=status.HTTP_200_OK,
    tags=["Admin"]
)
async def reset_database():
    """
    ELIMINA todos los chunks de ChromaDB para forzar re-indexación.
    USO: Solo cuando se necesita re-indexar todos los documentos desde cero.
    """
    try:
        logger.warning("RESET DATABASE solicitado - Eliminando todos los chunks")
        
        chroma_client = get_chroma_client()
        collection = chroma_client.collection
        
        # Obtener todos los IDs
        all_data = collection.get(include=[])
        all_ids = all_data['ids']
        
        if all_ids:
            # Eliminar todos
            collection.delete(ids=all_ids)
            logger.info(f"✓ Eliminados {len(all_ids)} chunks de ChromaDB")
            
            return {
                "status": "success",
                "mensaje": "Base de datos reseteada correctamente",
                "chunks_eliminados": len(all_ids)
            }
        else:
            return {
                "status": "success",
                "mensaje": "Base de datos ya estaba vacía",
                "chunks_eliminados": 0
            }
            
    except Exception as e:
        logger.error(f"Error al resetear base de datos: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error al resetear: {str(e)}"
        )


# ============================================================================
# EXCEPTION HANDLERS
# ============================================================================

@app.exception_handler(Exception)
async def global_exception_handler(request, exc):
    """
    Manejador global de excepciones no capturadas.
    """
    logger.error(f"Error no manejado: {str(exc)}", exc_info=True)
    
    return JSONResponse(
        status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
        content={
            "status": "error",
            "error": "Error interno del servidor",
            "details": str(exc)
        }
    )


# ============================================================================
# MAIN (Para ejecutar directamente con python)
# ============================================================================

if __name__ == "__main__":
    import uvicorn
    
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=8000,
        reload=True,
        log_level="info"
    )
