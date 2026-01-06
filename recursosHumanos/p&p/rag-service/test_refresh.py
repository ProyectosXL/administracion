"""
Script de prueba para el endpoint /admin/refresh (simula Railway).
Ejecuta localmente pero se comporta como si estuviera en Railway.
"""

import os
import sys

# Simular entorno Railway
os.environ['RAILWAY_ENVIRONMENT'] = 'production'
os.environ['GOOGLE_API_KEY'] = 'AIzaSyDUAWRkNRV4s11G2w4M3AG1Bu5H1h482DE'
os.environ['PHP_SERVER_URL'] = 'https://app.xl.com.ar/administracion/recursosHumanos/p&p'

# Importar después de setear variables de entorno
import requests
import json

print("=" * 80)
print("PRUEBA LOCAL DEL ENDPOINT /admin/refresh")
print("=" * 80)
print(f"PHP_SERVER_URL: {os.environ['PHP_SERVER_URL']}")
print()

# 1. Verificar que el servicio RAG esté corriendo
print("1. Verificando servicio RAG local...")
try:
    response = requests.get("http://localhost:8000/health", timeout=5)
    if response.status_code == 200:
        print("   ✅ Servicio RAG corriendo")
        data = response.json()
        print(f"   📊 Chunks actuales: {data.get('total_chunks', 0)}")
        print(f"   📊 Documentos: {data.get('total_documents', 0)}")
    else:
        print("   ❌ Servicio no responde correctamente")
        sys.exit(1)
except Exception as e:
    print(f"   ❌ Error: {e}")
    print("   ℹ️  Asegúrate de iniciar el servicio RAG primero:")
    print("      python -m uvicorn app.main:app --reload")
    sys.exit(1)

print()

# 2. Verificar PHP server
print("2. Verificando PHP server...")
try:
    response = requests.get(
        f"{os.environ['PHP_SERVER_URL']}/api/listar_documentos.php",
        timeout=10
    )
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            print(f"   ✅ PHP server OK - {len(data.get('documentos', []))} documentos disponibles")
        else:
            print(f"   ⚠️  PHP server responde pero con error: {data.get('mensaje')}")
    else:
        print(f"   ❌ PHP server responde con código: {response.status_code}")
except Exception as e:
    print(f"   ❌ Error conectando a PHP: {e}")

print()

# 3. Ejecutar refresh
print("3. Ejecutando /admin/refresh...")
print("   (Esto puede tardar varios minutos con muchos documentos)")
print()

try:
    response = requests.post(
        "http://localhost:8000/admin/refresh",
        timeout=300  # 5 minutos
    )
    
    print(f"   Status Code: {response.status_code}")
    print()
    
    if response.status_code == 200:
        data = response.json()
        print("   " + "=" * 70)
        print("   ✅ REFRESH EXITOSO")
        print("   " + "=" * 70)
        print(f"   Nuevos indexados: {data.get('nuevos_indexados', 0)}")
        print(f"   Errores: {data.get('errores', 0)}")
        print(f"   Chunks agregados: {data.get('chunks_agregados', 0)}")
        print(f"   Total chunks: {data.get('total_chunks', 0)}")
        print(f"   Total documentos: {data.get('total_documentos', 0)}")
        print("   " + "=" * 70)
    else:
        print("   ❌ REFRESH FALLÓ")
        print(f"   Respuesta: {response.text}")
        
except requests.exceptions.Timeout:
    print("   ⏱️  TIMEOUT (>5 minutos)")
    print("   Puede ser normal con muchos documentos")
except Exception as e:
    print(f"   ❌ Error: {e}")

print()
print("=" * 80)
print("PRUEBA COMPLETADA")
print("=" * 80)
