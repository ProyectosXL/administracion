"""
Script para forzar la re-indexación de todos los documentos PDF.
Llama al endpoint de webhook del servicio RAG para cada PDF.
"""

import requests
import json
from pathlib import Path
import time

# Configuración
RAG_SERVICE_URL = "http://localhost:8000"
DOCUMENTOS_PATH = Path(__file__).parent.parent / "documentos"

def index_document(pdf_path: Path, document_id: int):
    """Indexa un documento PDF llamando al webhook."""
    
    webhook_url = f"{RAG_SERVICE_URL}/webhook/document-uploaded"
    
    payload = {
        "document_id": document_id,
        "titulo": pdf_path.stem,
        "ruta_archivo": str(pdf_path.absolute()),
        "sector_nombre": "General",
        "tipo": "politica"
    }
    
    try:
        print(f"Indexando: {pdf_path.name}...", end=" ")
        response = requests.post(webhook_url, json=payload, timeout=120)
        
        if response.status_code == 200:
            result = response.json()
            chunks = result.get('chunks_count', 0)
            tiempo = result.get('tiempo_procesamiento_ms', 0)
            print(f"✓ OK ({chunks} chunks, {tiempo:.0f}ms)")
            return True
        else:
            print(f"✗ Error {response.status_code}: {response.text}")
            return False
            
    except requests.exceptions.Timeout:
        print(f"✗ Timeout (>120s)")
        return False
    except Exception as e:
        print(f"✗ Error: {e}")
        return False


def main():
    """Indexa todos los PDFs en el directorio de documentos."""
    
    print("=" * 80)
    print("FORCE RE-INDEX - Indexación masiva de documentos")
    print("=" * 80)
    
    # Verificar que el servicio RAG esté corriendo
    try:
        response = requests.get(f"{RAG_SERVICE_URL}/health", timeout=5)
        if response.status_code != 200:
            print(f"\n❌ El servicio RAG no está disponible (status {response.status_code})")
            print(f"Inicia el servicio con: python -m uvicorn app.main:app --port 8000")
            return
    except Exception as e:
        print(f"\n❌ No se puede conectar al servicio RAG: {e}")
        print(f"Inicia el servicio con: python -m uvicorn app.main:app --port 8000")
        return
    
    print(f"✓ Servicio RAG en línea: {RAG_SERVICE_URL}\n")
    
    # Buscar todos los PDFs
    if not DOCUMENTOS_PATH.exists():
        print(f"❌ No se encontró el directorio de documentos: {DOCUMENTOS_PATH}")
        return
    
    pdf_files = sorted(DOCUMENTOS_PATH.glob("*.pdf"))
    
    if not pdf_files:
        print(f"❌ No se encontraron archivos PDF en: {DOCUMENTOS_PATH}")
        return
    
    print(f"Encontrados {len(pdf_files)} archivos PDF para indexar\n")
    
    # Indexar cada PDF
    success_count = 0
    failed_count = 0
    start_time = time.time()
    
    for idx, pdf_path in enumerate(pdf_files, start=1):
        document_id = 1000 + idx  # IDs únicos empezando desde 1001
        
        if index_document(pdf_path, document_id):
            success_count += 1
        else:
            failed_count += 1
        
        # Pequeña pausa entre documentos para no saturar
        time.sleep(0.5)
    
    elapsed = time.time() - start_time
    
    print("\n" + "=" * 80)
    print("RESULTADO DE LA INDEXACIÓN")
    print("=" * 80)
    print(f"  Exitosos: {success_count}/{len(pdf_files)}")
    print(f"  Fallidos:  {failed_count}/{len(pdf_files)}")
    print(f"  Tiempo total: {elapsed:.1f}s")
    print("=" * 80)
    
    # Verificar estadísticas finales
    try:
        response = requests.get(f"{RAG_SERVICE_URL}/api/stats", timeout=5)
        if response.status_code == 200:
            stats = response.json()
            print(f"\nEstadísticas finales:")
            print(f"  Documentos indexados: {stats.get('total_documentos_indexados', 0)}")
            print(f"  Chunks totales: {stats.get('total_chunks', 0)}")
    except:
        pass


if __name__ == "__main__":
    main()
