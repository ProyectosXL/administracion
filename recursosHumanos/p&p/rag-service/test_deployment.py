"""
Script de verificación completa del servicio RAG.
Prueba todos los endpoints y funcionalidades críticas.

USO:
    # Probar localmente (simula Railway)
    python test_deployment.py --local
    
    # Probar contra Railway deployado
    python test_deployment.py --railway https://tu-url.railway.app
    
    # Probar contra producción
    python test_deployment.py --production https://app.xl.com.ar/rag-service
"""

import sys
import os
import argparse
import requests
import json
from typing import Dict, List, Tuple
import time

class Colors:
    GREEN = '\033[92m'
    RED = '\033[91m'
    YELLOW = '\033[93m'
    BLUE = '\033[94m'
    CYAN = '\033[96m'
    WHITE = '\033[97m'
    BOLD = '\033[1m'
    END = '\033[0m'

def print_header(text: str):
    print(f"\n{Colors.BOLD}{Colors.CYAN}{'=' * 80}{Colors.END}")
    print(f"{Colors.BOLD}{Colors.CYAN}{text.center(80)}{Colors.END}")
    print(f"{Colors.BOLD}{Colors.CYAN}{'=' * 80}{Colors.END}\n")

def print_success(text: str):
    print(f"{Colors.GREEN}✅ {text}{Colors.END}")

def print_error(text: str):
    print(f"{Colors.RED}❌ {text}{Colors.END}")

def print_warning(text: str):
    print(f"{Colors.YELLOW}⚠️  {text}{Colors.END}")

def print_info(text: str):
    print(f"{Colors.BLUE}ℹ️  {text}{Colors.END}")

def print_test(text: str):
    print(f"\n{Colors.BOLD}{Colors.WHITE}🧪 TEST: {text}{Colors.END}")

class RAGServiceTester:
    def __init__(self, base_url: str, is_railway: bool = False):
        self.base_url = base_url.rstrip('/')
        self.is_railway = is_railway
        self.results = {
            'passed': 0,
            'failed': 0,
            'warnings': 0,
            'tests': []
        }
    
    def _request(self, method: str, endpoint: str, timeout: int = 30, **kwargs) -> Tuple[bool, any]:
        """Ejecuta request y maneja errores"""
        try:
            url = f"{self.base_url}{endpoint}"
            response = requests.request(method, url, timeout=timeout, **kwargs)
            return True, response
        except requests.exceptions.Timeout:
            return False, "Timeout"
        except requests.exceptions.ConnectionError:
            return False, "Error de conexión"
        except Exception as e:
            return False, str(e)
    
    def _test(self, name: str, passed: bool, message: str = "", warning: bool = False):
        """Registra resultado de un test"""
        self.results['tests'].append({
            'name': name,
            'passed': passed,
            'message': message,
            'warning': warning
        })
        
        if warning:
            self.results['warnings'] += 1
            print_warning(f"{name}: {message}")
        elif passed:
            self.results['passed'] += 1
            print_success(f"{name}")
        else:
            self.results['failed'] += 1
            print_error(f"{name}: {message}")
    
    def test_1_root_endpoint(self):
        """Test 1: Endpoint raíz /"""
        print_test("Endpoint raíz /")
        
        success, response = self._request('GET', '/')
        
        if not success:
            self._test("GET /", False, f"No responde: {response}")
            return False
        
        if response.status_code != 200:
            self._test("GET /", False, f"Status code: {response.status_code}")
            return False
        
        try:
            data = response.json()
            print_info(f"Service: {data.get('service', 'N/A')}")
            print_info(f"Version: {data.get('version', 'N/A')}")
            print_info(f"Railway: {data.get('railway', False)}")
            
            # Verificar que railway coincide con lo esperado
            if self.is_railway and not data.get('railway'):
                self._test("GET /", False, "Debería detectar Railway pero no lo hace", warning=True)
            
            self._test("GET /", True)
            return True
            
        except Exception as e:
            self._test("GET /", False, f"Error parseando JSON: {e}")
            return False
    
    def test_2_health_endpoint(self):
        """Test 2: Health check /health"""
        print_test("Health check /health")
        
        success, response = self._request('GET', '/health')
        
        if not success:
            self._test("GET /health", False, f"No responde: {response}")
            return False
        
        if response.status_code != 200:
            self._test("GET /health", False, f"Status code: {response.status_code}")
            return False
        
        try:
            data = response.json()
            print_info(f"Status: {data.get('status', 'N/A')}")
            print_info(f"Total chunks: {data.get('total_chunks', 0)}")
            print_info(f"Total documents: {data.get('total_documents', 0)}")
            
            if data.get('status') not in ['online', 'ok']:
                self._test("GET /health", False, f"Status: {data.get('status')}", warning=True)
            else:
                self._test("GET /health", True)
            
            return True
            
        except Exception as e:
            self._test("GET /health", False, f"Error parseando JSON: {e}")
            return False
    
    def test_3_stats_endpoint(self):
        """Test 3: Estadísticas /api/stats"""
        print_test("Estadísticas /api/stats")
        
        success, response = self._request('GET', '/api/stats')
        
        if not success:
            self._test("GET /api/stats", False, f"No responde: {response}")
            return False
        
        if response.status_code != 200:
            self._test("GET /api/stats", False, f"Status code: {response.status_code}")
            return False
        
        try:
            data = response.json()
            print_info(f"Total chunks: {data.get('total_chunks', 0)}")
            print_info(f"Total documentos: {data.get('total_documentos_indexados', 0)}")
            print_info(f"Documentos únicos: {len(data.get('documentos_indexados', []))}")
            
            self._test("GET /api/stats", True)
            return True
            
        except Exception as e:
            self._test("GET /api/stats", False, f"Error parseando JSON: {e}")
            return False
    
    def test_4_docs_endpoint(self):
        """Test 4: Documentación /docs"""
        print_test("Documentación /docs")
        
        success, response = self._request('GET', '/docs')
        
        if not success:
            self._test("GET /docs", False, f"No responde: {response}")
            return False
        
        if response.status_code != 200:
            self._test("GET /docs", False, f"Status code: {response.status_code}")
            return False
        
        if 'swagger' in response.text.lower() or 'openapi' in response.text.lower():
            self._test("GET /docs", True)
            return True
        else:
            self._test("GET /docs", False, "No parece ser documentación Swagger")
            return False
    
    def test_5_query_endpoint(self):
        """Test 5: Consulta RAG /api/query"""
        print_test("Consulta RAG /api/query")
        
        success, response = self._request(
            'POST',
            '/api/query',
            json={
                "pregunta": "¿Cuál es la política de vacaciones?",
                "top_k": 3
            },
            timeout=30
        )
        
        if not success:
            self._test("POST /api/query", False, f"No responde: {response}")
            return False
        
        if response.status_code == 404:
            self._test("POST /api/query", False, "ChromaDB vacío - sin documentos indexados", warning=True)
            return False
        
        if response.status_code != 200:
            self._test("POST /api/query", False, f"Status code: {response.status_code}")
            return False
        
        try:
            data = response.json()
            print_info(f"Respuesta: {data.get('answer', 'N/A')[:100]}...")
            print_info(f"Chunks usados: {len(data.get('chunks_usados', []))}")
            print_info(f"Tiene respuesta: {data.get('tiene_respuesta', False)}")
            
            if not data.get('tiene_respuesta'):
                self._test("POST /api/query", True, "Sin documentos suficientes para responder", warning=True)
            else:
                self._test("POST /api/query", True)
            
            return True
            
        except Exception as e:
            self._test("POST /api/query", False, f"Error parseando JSON: {e}")
            return False
    
    def test_6_refresh_endpoint(self):
        """Test 6: Refresh desde PHP /admin/refresh (solo Railway)"""
        print_test("Refresh desde PHP /admin/refresh (opcional)")
        
        if not self.is_railway:
            print_info("Este test solo funciona en Railway - OMITIDO")
            # No contar como warning, simplemente omitir
            return True
        
        print_info("⏳ Este test puede tardar varios minutos...")
        
        success, response = self._request(
            'POST',
            '/admin/refresh',
            timeout=300  # 5 minutos
        )
        
        if not success:
            self._test("POST /admin/refresh", False, f"No responde: {response}")
            return False
        
        if response.status_code == 400:
            # Puede ser que no esté en Railway
            self._test("POST /admin/refresh", False, "Endpoint solo disponible en Railway", warning=True)
            return False
        
        if response.status_code != 200:
            self._test("POST /admin/refresh", False, f"Status code: {response.status_code}")
            return False
        
        try:
            data = response.json()
            print_info(f"Status: {data.get('status', 'N/A')}")
            print_info(f"Nuevos indexados: {data.get('nuevos_indexados', 0)}")
            print_info(f"Errores: {data.get('errores', 0)}")
            print_info(f"Chunks agregados: {data.get('chunks_agregados', 0)}")
            print_info(f"Total chunks: {data.get('total_chunks', 0)}")
            print_info(f"Total documentos: {data.get('total_documentos', 0)}")
            
            if data.get('errores', 0) > 0:
                self._test("POST /admin/refresh", True, f"{data.get('errores')} errores encontrados", warning=True)
            else:
                self._test("POST /admin/refresh", True)
            
            return True
            
        except Exception as e:
            self._test("POST /admin/refresh", False, f"Error parseando JSON: {e}")
            return False
    
    def test_7_php_server_connection(self):
        """Test 7: Conexión con PHP server"""
        print_test("Conexión con PHP server (app.xl.com.ar)")
        
        php_url = "https://app.xl.com.ar/administracion/recursosHumanos/p&p"
        
        # Test 1: listar_documentos.php
        print_info("Verificando /api/listar_documentos.php...")
        try:
            response = requests.get(f"{php_url}/api/listar_documentos.php", timeout=10)
            
            if response.status_code != 200:
                self._test("PHP listar_documentos", False, f"Status code: {response.status_code}")
                return False
            
            data = response.json()
            if not data.get('success'):
                self._test("PHP listar_documentos", False, f"Error: {data.get('mensaje')}")
                return False
            
            num_docs = len(data.get('documentos', []))
            print_info(f"✓ PHP server OK - {num_docs} documentos disponibles")
            
            # Test 2: descargar_pdf.php (verificar que existe)
            if num_docs > 0:
                doc_id = data['documentos'][0]['id']
                print_info(f"Verificando /api/descargar_pdf.php?id={doc_id}...")
                
                response = requests.head(f"{php_url}/api/descargar_pdf.php?id={doc_id}", timeout=10)
                if response.status_code == 200:
                    print_info(f"✓ descargar_pdf.php responde OK")
                else:
                    self._test("PHP descargar_pdf", False, f"Status code: {response.status_code}", warning=True)
            
            self._test("PHP server connection", True)
            return True
            
        except Exception as e:
            self._test("PHP server connection", False, f"Error: {e}")
            return False
    
    def test_8_cors_headers(self):
        """Test 8: Headers CORS"""
        print_test("Headers CORS")
        
        success, response = self._request('OPTIONS', '/health')
        
        if not success:
            self._test("CORS headers", False, f"No responde: {response}")
            return False
        
        headers = response.headers
        has_cors = 'Access-Control-Allow-Origin' in headers
        
        if has_cors:
            print_info(f"✓ CORS habilitado: {headers.get('Access-Control-Allow-Origin')}")
            self._test("CORS headers", True)
        else:
            self._test("CORS headers", False, "CORS no configurado", warning=True)
        
        return has_cors
    
    def run_all_tests(self):
        """Ejecuta todos los tests"""
        print_header(f"VERIFICACIÓN COMPLETA - {self.base_url}")
        
        print_info(f"Entorno: {'Railway' if self.is_railway else 'Local/Producción'}")
        print_info(f"URL base: {self.base_url}")
        
        # Ejecutar tests en orden
        self.test_1_root_endpoint()
        self.test_2_health_endpoint()
        self.test_3_stats_endpoint()
        self.test_4_docs_endpoint()
        self.test_5_query_endpoint()
        self.test_6_refresh_endpoint()
        self.test_7_php_server_connection()
        self.test_8_cors_headers()
        
        # Mostrar resumen
        self.print_summary()
    
    def print_summary(self):
        """Muestra resumen de resultados"""
        print_header("RESUMEN DE TESTS")
        
        total = self.results['passed'] + self.results['failed']
        
        print(f"{Colors.BOLD}Total tests: {total}{Colors.END}")
        print(f"{Colors.GREEN}✅ Pasados: {self.results['passed']}{Colors.END}")
        print(f"{Colors.RED}❌ Fallados: {self.results['failed']}{Colors.END}")
        print(f"{Colors.YELLOW}⚠️  Advertencias: {self.results['warnings']}{Colors.END}")
        
        if self.results['failed'] == 0:
            print(f"\n{Colors.BOLD}{Colors.GREEN}🎉 ¡TODOS LOS TESTS PASARON!{Colors.END}")
            print(f"{Colors.GREEN}✅ El servicio está listo para usar{Colors.END}\n")
        else:
            print(f"\n{Colors.BOLD}{Colors.RED}⚠️  HAY TESTS FALLIDOS{Colors.END}")
            print(f"{Colors.RED}❌ Revisar errores antes de continuar{Colors.END}\n")
        
        return self.results['failed'] == 0


def main():
    parser = argparse.ArgumentParser(description='Test del servicio RAG en diferentes entornos')
    parser.add_argument('--local', action='store_true', help='Probar contra localhost:8000')
    parser.add_argument('--railway', type=str, help='Probar contra Railway (URL completa)')
    parser.add_argument('--production', type=str, help='Probar contra producción (URL completa)')
    
    args = parser.parse_args()
    
    # Determinar URL y entorno
    if args.local:
        # Simular Railway localmente
        os.environ['RAILWAY_ENVIRONMENT'] = 'production'
        os.environ['GOOGLE_API_KEY'] = 'AIzaSyDUAWRkNRV4s11G2w4M3AG1Bu5H1h482DE'
        os.environ['PHP_SERVER_URL'] = 'https://app.xl.com.ar/administracion/recursosHumanos/p&p'
        
        base_url = 'http://localhost:8002'
        is_railway = True
        
        print_info("Verificando que el servicio local esté corriendo...")
        try:
            response = requests.get(f"{base_url}/health", timeout=10)
            if response.status_code != 200:
                print_error("El servicio local no está corriendo en http://localhost:8002")
                print_info("Inicia el servicio con: python -m uvicorn app.main:app --host 127.0.0.1 --port 8002 --reload")
                sys.exit(1)
        except:
            print_error("El servicio local no está corriendo en http://localhost:8002")
            print_info("Inicia el servicio con: python -m uvicorn app.main:app --host 127.0.0.1 --port 8002 --reload")
            sys.exit(1)
    
    elif args.railway:
        base_url = args.railway
        is_railway = True
    
    elif args.production:
        base_url = args.production
        is_railway = False
    
    else:
        print_error("Debes especificar un entorno: --local, --railway o --production")
        parser.print_help()
        sys.exit(1)
    
    # Ejecutar tests
    tester = RAGServiceTester(base_url, is_railway)
    success = tester.run_all_tests()
    
    # Exit code según resultado
    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
