"""
Script de prueba para verificar el funcionamiento del módulo RAG.
NO se conecta a SQL Server - solo prueba con un PDF local.
"""

import sys
import argparse
from pathlib import Path

# Agregar el directorio padre al path para importar módulos
sys.path.insert(0, str(Path(__file__).parent.parent))

from app.pdf_processor import extract_text_from_pdf, get_pdf_info
from app.chunking import create_chunks, calculate_chunk_statistics
from app.embeddings import generate_embeddings_batch, test_gemini_connection
from app.database import get_chroma_client
from app.rag import generate_rag_response
from app.chunking import generate_chunk_id


def test_pdf_extraction(pdf_path: str):
    """
    Prueba la extracción de texto de un PDF.
    """
    print("\n" + "=" * 80)
    print("TEST 1: Extracción de texto de PDF")
    print("=" * 80)
    
    try:
        # Obtener info del PDF
        print(f"\nObteniendo información del PDF: {pdf_path}")
        info = get_pdf_info(pdf_path)
        print(f"  ✓ Nombre: {info['filename']}")
        print(f"  ✓ Tamaño: {info['size_kb']} KB")
        print(f"  ✓ Páginas: {info['pages']}")
        
        # Extraer texto
        print(f"\nExtrayendo texto...")
        text, num_pages = extract_text_from_pdf(pdf_path)
        print(f"  ✓ Texto extraído: {len(text)} caracteres")
        print(f"  ✓ Páginas procesadas: {num_pages}")
        print(f"\n  Preview del texto (primeros 200 caracteres):")
        print(f"  {text[:200]}...")
        
        return text
        
    except Exception as e:
        print(f"  ✗ Error: {str(e)}")
        return None


def test_chunking(text: str):
    """
    Prueba la división del texto en chunks.
    """
    print("\n" + "=" * 80)
    print("TEST 2: División en chunks")
    print("=" * 80)
    
    try:
        metadata = {
            'document_id': 999,
            'titulo': 'Documento de Prueba',
            'sector': 'Testing',
            'tipo': 'politica'
        }
        
        print(f"\nCreando chunks...")
        chunks = create_chunks(text, metadata)
        print(f"  ✓ Chunks generados: {len(chunks)}")
        
        # Estadísticas
        stats = calculate_chunk_statistics(chunks)
        print(f"\n  Estadísticas:")
        print(f"    - Total chunks: {stats['total_chunks']}")
        print(f"    - Caracteres totales: {stats['total_characters']}")
        print(f"    - Promedio por chunk: {stats['avg_length']:.0f}")
        print(f"    - Mínimo: {stats['min_length']}")
        print(f"    - Máximo: {stats['max_length']}")
        
        # Mostrar primer chunk
        print(f"\n  Primer chunk:")
        print(f"    ID: {generate_chunk_id(999, 0)}")
        print(f"    Longitud: {len(chunks[0]['text'])} caracteres")
        print(f"    Preview: {chunks[0]['text'][:150]}...")
        
        return chunks
        
    except Exception as e:
        print(f"  ✗ Error: {str(e)}")
        return None


def test_embeddings(chunks: list):
    """
    Prueba la generación de embeddings.
    """
    print("\n" + "=" * 80)
    print("TEST 3: Generación de embeddings con Gemini")
    print("=" * 80)
    
    try:
        # Probar conexión primero
        print(f"\nVerificando conexión con Gemini API...")
        if not test_gemini_connection():
            print(f"  ✗ Error de conexión con Gemini")
            return None
        
        # Generar embeddings (solo primeros 5 chunks para no saturar)
        num_chunks_to_test = min(5, len(chunks))
        print(f"\nGenerando embeddings para {num_chunks_to_test} chunks...")
        
        chunk_texts = [chunk['text'] for chunk in chunks[:num_chunks_to_test]]
        embeddings = generate_embeddings_batch(chunk_texts, show_progress=True)
        
        print(f"  ✓ Embeddings generados: {len(embeddings)}")
        print(f"  ✓ Dimensión de cada embedding: {len(embeddings[0])}")
        
        return embeddings
        
    except Exception as e:
        print(f"  ✗ Error: {str(e)}")
        return None


def test_chromadb_indexing(chunks: list, embeddings: list):
    """
    Prueba la indexación en ChromaDB.
    """
    print("\n" + "=" * 80)
    print("TEST 4: Indexación en ChromaDB")
    print("=" * 80)
    
    try:
        print(f"\nInicializando ChromaDB...")
        chroma_client = get_chroma_client()
        
        # Limpiar chunks de prueba anteriores
        print(f"\nLimpiando chunks de prueba anteriores...")
        deleted = chroma_client.delete_document_chunks(999)
        print(f"  ✓ Eliminados {deleted} chunks antiguos")
        
        # Preparar datos
        num_chunks = len(embeddings)
        chunk_ids = [generate_chunk_id(999, i) for i in range(num_chunks)]
        documents = [chunk['text'] for chunk in chunks[:num_chunks]]
        metadatas = [chunk['metadata'] for chunk in chunks[:num_chunks]]
        
        # Indexar
        print(f"\nIndexando {num_chunks} chunks...")
        chroma_client.add_chunks(chunk_ids, embeddings, documents, metadatas)
        print(f"  ✓ Chunks indexados exitosamente")
        
        # Verificar
        indexed_chunks = chroma_client.get_document_chunks(999)
        print(f"  ✓ Verificación: {len(indexed_chunks)} chunks en ChromaDB")
        
        return True
        
    except Exception as e:
        print(f"  ✗ Error: {str(e)}")
        return False


def test_rag_query():
    """
    Prueba una consulta RAG completa.
    """
    print("\n" + "=" * 80)
    print("TEST 5: Consulta RAG")
    print("=" * 80)
    
    try:
        # Hacer una consulta de prueba
        query = "¿Qué información contiene este documento?"
        print(f"\nPregunta: '{query}'")
        print(f"\nGenerando respuesta RAG...")
        
        result = generate_rag_response(query, top_k=3)
        
        print(f"\n  ✓ Respuesta generada:")
        print(f"    Tiempo de búsqueda: {result['tiempo_busqueda_ms']:.2f}ms")
        print(f"    Tiempo de generación: {result['tiempo_generacion_ms']:.2f}ms")
        print(f"    Chunks encontrados: {result['total_chunks_encontrados']}")
        print(f"    Fuentes: {len(result['fuentes'])}")
        
        print(f"\n  Respuesta:")
        print(f"  {'-' * 80}")
        print(f"  {result['respuesta']}")
        print(f"  {'-' * 80}")
        
        if result['fuentes']:
            print(f"\n  Fuentes utilizadas:")
            for i, fuente in enumerate(result['fuentes'], 1):
                print(f"    {i}. {fuente.titulo} ({fuente.sector})")
                print(f"       Similitud: {fuente.similarity_score:.3f}")
                print(f"       Preview: {fuente.chunk_text[:100]}...")
        
        return True
        
    except Exception as e:
        print(f"  ✗ Error: {str(e)}")
        return False


def main():
    """
    Función principal del script de prueba.
    """
    parser = argparse.ArgumentParser(
        description='Script de prueba para el módulo RAG de DocuGest'
    )
    parser.add_argument(
        '--pdf',
        type=str,
        required=True,
        help='Ruta al archivo PDF de prueba'
    )
    
    args = parser.parse_args()
    pdf_path = args.pdf
    
    print("\n" + "=" * 80)
    print("SCRIPT DE PRUEBA - MÓDULO RAG DOCUGEST")
    print("=" * 80)
    print(f"PDF de prueba: {pdf_path}")
    
    # Verificar que el archivo existe
    if not Path(pdf_path).exists():
        print(f"\n✗ Error: Archivo no encontrado: {pdf_path}")
        return
    
    # Ejecutar pruebas en secuencia
    print("\nEjecutando suite de pruebas...\n")
    
    # 1. Extraer texto
    text = test_pdf_extraction(pdf_path)
    if not text:
        print("\n✗ Prueba de extracción falló. Abortando.")
        return
    
    # 2. Crear chunks
    chunks = test_chunking(text)
    if not chunks:
        print("\n✗ Prueba de chunking falló. Abortando.")
        return
    
    # 3. Generar embeddings
    embeddings = test_embeddings(chunks)
    if not embeddings:
        print("\n✗ Prueba de embeddings falló. Abortando.")
        return
    
    # 4. Indexar en ChromaDB
    if not test_chromadb_indexing(chunks, embeddings):
        print("\n✗ Prueba de indexación falló. Abortando.")
        return
    
    # 5. Probar consulta RAG
    if not test_rag_query():
        print("\n✗ Prueba de RAG falló.")
        return
    
    # Resumen final
    print("\n" + "=" * 80)
    print("RESUMEN DE PRUEBAS")
    print("=" * 80)
    print("  ✓ Extracción de PDF")
    print("  ✓ División en chunks")
    print("  ✓ Generación de embeddings")
    print("  ✓ Indexación en ChromaDB")
    print("  ✓ Consulta RAG")
    print("\n✓ Todas las pruebas completadas exitosamente!")
    print("=" * 80)
    
    # Estadísticas finales
    try:
        chroma_client = get_chroma_client()
        stats = chroma_client.get_stats()
        print(f"\nEstadísticas de ChromaDB:")
        print(f"  - Total documentos indexados: {stats['total_documentos_indexados']}")
        print(f"  - Total chunks: {stats['total_chunks']}")
        print(f"  - Documentos por tipo: {stats['documentos_por_tipo']}")
        print()
    except Exception as e:
        print(f"\nNo se pudieron obtener estadísticas: {str(e)}")


if __name__ == "__main__":
    main()
