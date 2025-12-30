#!/bin/bash

################################################################################
# Script de Instalación Automática - Servicio RAG DocuGest
# Para servidores Linux (Ubuntu/Debian)
################################################################################

set -e  # Detener en caso de error

echo "================================"
echo "  Instalación RAG DocuGest"
echo "================================"
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Obtener directorio actual
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
RAG_DIR="$SCRIPT_DIR/rag-service"

echo -e "${GREEN}✓${NC} Directorio actual: $SCRIPT_DIR"

# 1. Verificar Python
echo ""
echo "Verificando Python..."
if ! command -v python3 &> /dev/null; then
    echo -e "${RED}✗${NC} Python 3 no encontrado"
    echo "Instalando Python 3..."
    sudo apt update
    sudo apt install -y python3 python3-pip python3-venv
fi

PYTHON_VERSION=$(python3 --version | cut -d' ' -f2 | cut -d'.' -f1-2)
echo -e "${GREEN}✓${NC} Python $PYTHON_VERSION instalado"

# 2. Crear entorno virtual
echo ""
echo "Creando entorno virtual..."
cd "$RAG_DIR"

if [ -d "venv" ]; then
    echo -e "${YELLOW}⚠${NC} Entorno virtual ya existe"
else
    python3 -m venv venv
    echo -e "${GREEN}✓${NC} Entorno virtual creado"
fi

# 3. Activar entorno virtual e instalar dependencias
echo ""
echo "Instalando dependencias Python..."
source venv/bin/activate
pip install --upgrade pip --quiet
pip install -r requirements.txt --quiet
echo -e "${GREEN}✓${NC} Dependencias instaladas"

# 4. Configuración automática
echo ""
echo "Verificando configuración..."
echo -e "${GREEN}✓${NC} API key configurada en el código (producción)"

# 5. Crear directorio para ChromaDB
echo ""
echo "Configurando ChromaDB..."
mkdir -p "$RAG_DIR/chroma_db"
chmod 755 "$RAG_DIR/chroma_db"
echo -e "${GREEN}✓${NC} Directorio ChromaDB creado"

# 6. Crear script de inicio para systemd
echo ""
echo "¿Deseas instalar el servicio como systemd? (recomendado)"
read -p "Esto requiere permisos sudo (s/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Ss]$ ]]; then
    PYTHON_PATH="$RAG_DIR/venv/bin/python"
    SERVICE_FILE="/etc/systemd/system/rag-docugest.service"
    
    echo "Creando archivo de servicio systemd..."
    sudo tee $SERVICE_FILE > /dev/null <<EOF
[Unit]
Description=RAG DocuGest Service
After=network.target

[Service]
Type=simple
User=$USER
WorkingDirectory=$RAG_DIR
ExecStart=$PYTHON_PATH -m uvicorn app.main:app --host 0.0.0.0 --port 8000
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    sudo systemctl daemon-reload
    sudo systemctl enable rag-docugest
    sudo systemctl start rag-docugest
    
    echo -e "${GREEN}✓${NC} Servicio instalado y iniciado"
    echo ""
    echo "Ver estado: sudo systemctl status rag-docugest"
    echo "Ver logs: sudo journalctl -u rag-docugest -f"
else
    echo "Para iniciar manualmente:"
    echo "  cd $RAG_DIR"
    echo "  source venv/bin/activate"
    echo "  python -m uvicorn app.main:app --host 0.0.0.0 --port 8000"
fi

# 7. Verificar instalación
echo ""
echo "Esperando 10 segundos a que el servicio inicie..."
sleep 10

echo ""
echo "Verificando servicio..."
if curl -s http://localhost:8000/health > /dev/null; then
    echo -e "${GREEN}✓${NC} ¡Servicio RAG funcionando correctamente!"
    echo ""
    curl -s http://localhost:8000/health | python3 -m json.tool
else
    echo -e "${YELLOW}⚠${NC} El servicio aún no responde (puede tardar unos segundos más)"
fi

echo ""
echo "================================"
echo "  Instalación Completada"
echo "================================"
echo ""
echo "Próximos pasos:"
echo "1. Verificar: curl http://localhost:8000/health"
echo "2. Abrir: https://app.xl.com.ar/administracion/recursosHumanos/p&p/index.php"
echo "3. Probar el chatbot"
echo ""
echo "Para ver logs:"
echo "  sudo journalctl -u rag-docugest -f"
echo ""
