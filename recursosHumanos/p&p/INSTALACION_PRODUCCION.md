# Guía de Instalación en Producción - Servicio RAG DocuGest

## 📋 Requisitos del Servidor

- **Sistema Operativo**: Linux (Ubuntu 20.04+ o similar)
- **PHP**: 7.3.x (ya instalado en app.xl.com.ar)
- **Python**: 3.8+ 
- **Acceso**: SSH con permisos sudo
- **Puertos**: Puerto 8000 debe estar disponible internamente
- **Memoria**: Mínimo 2GB RAM disponibles

## 🚀 Instalación Paso a Paso

### 1. Conectar al Servidor

```bash
ssh usuario@app.xl.com.ar
cd /ruta/al/directorio/administracion/recursosHumanos/p&p
```

### 2. Verificar Python

```bash
# Verificar versión de Python
python3 --version  # Debe ser 3.8 o superior

# Si no está instalado Python 3.8+
sudo apt update
sudo apt install python3 python3-pip python3-venv -y
```

### 3. Crear Entorno Virtual Python

```bash
# Navegar al directorio rag-service
cd rag-service

# Crear entorno virtual
python3 -m venv venv

# Activar entorno virtual
source venv/bin/activate

# El prompt debe cambiar mostrando (venv)
```

### 4. Instalar Dependencias Python

```bash
# Con el entorno virtual activado
pip install --upgrade pip
pip install -r requirements.txt

# Esto instalará:
# - fastapi
# - uvicorn
# - google-generativeai
# - chromadb
# - PyMuPDF
# - python-dotenv
# - langchain-text-splitters
```

### 5. ~~Configurar Variables de Entorno~~ (NO NECESARIO)

✅ **En producción la API key está hardcodeada en el código.**

No necesitás crear ni modificar ningún archivo `.env` en el servidor.
El sistema detecta automáticamente que está en producción por el hostname y usa la API key configurada.

### 6. Ajustar Ruta de Python en config_rag.php (Si es Necesario)

```bash
# Editar el archivo de configuración
nano config_rag.php
```

Buscar la sección de PRODUCCIÓN y verificar/ajustar:

```php
'python_path' => '/usr/bin/python3', // Ajustar si Python está en otra ruta
```

Para encontrar la ruta correcta:
```bash
which python3
```

### 7. Crear Script de Inicio como Servicio (Opcional pero Recomendado)

```bash
# Crear archivo de servicio systemd
sudo nano /etc/systemd/system/rag-docugest.service
```

Contenido del archivo:

```ini
[Unit]
Description=RAG DocuGest Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/ruta/completa/a/administracion/recursosHumanos/p&p/rag-service
ExecStart=/ruta/completa/a/administracion/recursosHumanos/p&p/rag-service/venv/bin/python -m uvicorn app.main:app --host 0.0.0.0 --port 8000
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**IMPORTANTE**: Reemplazar `/ruta/completa/a/` con la ruta absoluta real.

Para encontrar la ruta absoluta:
```bash
cd /ruta/a/p&p/rag-service
pwd  # Esto muestra la ruta absoluta
```

Luego habilitar y arrancar el servicio:

```bash
# Recargar configuración de systemd
sudo systemctl daemon-reload

# Habilitar inicio automático
sudo systemctl enable rag-docugest

# Iniciar el servicio
sudo systemctl start rag-docugest

# Verificar estado
sudo systemctl status rag-docugest

# Ver logs en tiempo real
sudo journalctl -u rag-docugest -f
```

### 8. Verificar que el Servicio Esté Corriendo

```bash
# Desde el servidor
curl http://localhost:8000/health

# Debe responder algo como:
# {"status":"ok","version":"1.0.0","chroma_collections":1,"total_chunks":0}
```

### 9. Probar Indexación Manual (Primera Vez)

```bash
# Activar entorno virtual si no está activado
cd /ruta/a/p&p/rag-service
source venv/bin/activate

# Ejecutar un script de prueba para indexar un documento
python3 << 'EOF'
import requests
import json

pdf_path = "/ruta/absoluta/a/p&p/documentos/tu_documento.pdf"
data = {
    "document_id": 123456,
    "file_path": pdf_path,
    "titulo": "Documento de Prueba",
    "sector": "Políticas y Procedimientos",
    "tipo": "PDF"
}

response = requests.post(
    "http://localhost:8000/webhook/document-uploaded",
    json=data
)

print(response.status_code)
print(response.json())
EOF
```

### 10. Configurar Permisos

```bash
# Asegurar que www-data (usuario de Apache/Nginx) pueda leer los archivos
cd /ruta/a/p&p
sudo chown -R www-data:www-data rag-service/chroma_db
sudo chmod -R 755 rag-service
```

## 🔧 Configuración Adicional

### Ajustar config_rag.php (Si es necesario)

Si el script de auto-inicio no funciona, editar:

```php
// En config_rag.php, sección PRODUCCIÓN:
'auto_start' => false, // Cambiar a false si usas systemd
'python_path' => '/ruta/correcta/a/python3',
```

### Firewall (Si aplica)

```bash
# El puerto 8000 NO debe ser público, solo localhost
# Verificar que el firewall NO expone el puerto 8000 externamente
sudo ufw status

# Si el puerto 8000 está abierto públicamente, cerrarlo
sudo ufw deny 8000
```

## ✅ Verificación Final

### 1. Probar el Health Endpoint

```bash
curl http://localhost:8000/health
```

### 2. Probar desde el Navegador

Abrir: `https://app.xl.com.ar/administracion/recursosHumanos/p&p/index.php`

1. Abrir consola del navegador (F12)
2. Verificar que aparezca en logs:
   - `🚀 Iniciando sistema RAG automáticamente...`
   - `✅ Sistema RAG iniciado: ...`

### 3. Probar el Chatbot

1. Hacer clic en el botón violeta del chatbot
2. Escribir "Hola"
3. Verificar que responda

## 🐛 Troubleshooting

### Problema: Servicio no inicia

```bash
# Ver logs del servicio
sudo journalctl -u rag-docugest -n 50

# Verificar que Python y dependencias estén OK
cd /ruta/a/p&p/rag-service
source venv/bin/activate
python3 -c "import fastapi, uvicorn, google.generativeai, chromadb, fitz"
```

### Problema: ChromaDB vacío

```bash
# Ejecutar indexación manual desde PHP
curl "https://app.xl.com.ar/administracion/recursosHumanos/p&p/Controller/indexar_pdfs_automatico.php"
```

### Problema: Error de permisos

```bash
# Ajustar permisos
sudo chown -R www-data:www-data /ruta/a/p&p/rag-service/chroma_db
sudo chmod -R 755 /ruta/a/p&p/rag-service
```

### Problema: Timeout en requests

Editar `config_rag.php`:
```php
'timeout' => 120, // Aumentar a 120 segundos
```

## 📊 Monitoreo

### Ver Logs en Tiempo Real

```bash
sudo journalctl -u rag-docugest -f
```

### Verificar Uso de Recursos

```bash
# Ver procesos Python
ps aux | grep python

# Ver uso de memoria
free -h

# Ver espacio en disco
df -h
```

### Reiniciar el Servicio

```bash
sudo systemctl restart rag-docugest
```

### Detener el Servicio

```bash
sudo systemctl stop rag-docugest
```

## 📝 Notas Importantes

1. **El servicio RAG corre en el MISMO servidor** (no requiere servidor externo)
2. **Solo accesible internamente** (localhost:8000)
3. **Auto-inicio configurado** via systemd
4. **Auto-indexación** al entrar al sitio si ChromaDB está vacío
5. **Compatible con PHP 7.3.x**

## 🔄 Actualización del Código

Cuando actualices el código:

```bash
cd /ruta/a/p&p
git pull  # o actualizar archivos manualmente

# Reiniciar el servicio
sudo systemctl restart rag-docugest

# Verificar que arrancó correctamente
sudo systemctl status rag-docugest
```

## 📧 Contacto

Si algo no funciona, revisar logs:
```bash
sudo journalctl -u rag-docugest -n 100
```
