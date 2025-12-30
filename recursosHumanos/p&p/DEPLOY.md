# 🚀 Despliegue Rápido a Producción

## ⚡ Instalación en 3 Pasos

### 1. Subir Código al Servidor

```bash
# Conectar al servidor
ssh usuario@app.xl.com.ar

# Navegar al directorio
cd /var/www/html/administracion/recursosHumanos/p&p
# O la ruta donde esté instalado

# Subir el código (via git, FTP, rsync, etc)
# Asegurarse de incluir TODO el directorio rag-service
```

### 2. Ejecutar Script de Instalación

```bash
# Hacer ejecutable el script
chmod +x install_production.sh

# Ejecutar
./install_production.sh

# Seguir las instrucciones en pantalla
```

El script automáticamente:
- ✅ Verifica Python 3
- ✅ Crea entorno virtual
- ✅ Instala dependencias
- ✅ Verifica GOOGLE_API_KEY
- ✅ Configura systemd
- ✅ Inicia el servicio

### 3. Verificar

```bash
# Ver estado
sudo systemctl status rag-docugest

# Probar health
curl http://localhost:8000/health

# Ver logs
sudo journalctl -u rag-docugest -f
```

## 🔧 Configuración Manual (Si el Script Falla)

Ver archivo: [INSTALACION_PRODUCCION.md](INSTALACION_PRODUCCION.md)

## ⚙️ Configuración Automática

El sistema detecta automáticamente si está en:
- **Local** (`localhost`) → Usa Python de Miniconda
- **Producción** (`app.xl.com.ar`) → Usa Python del sistema

**No requiere cambios manuales en el código.**

## 📁 Archivos Críticos

```
p&p/
├── config_rag.php              ← Configuración auto-adaptable
├── install_production.sh       ← Script de instalación
├── INSTALACION_PRODUCCION.md   ← Guía detallada
├── rag-service/
│   ├── app/                    ← Código Python
│   ├── requirements.txt        ← Dependencias
│   ├── chroma_db/              ← Base de datos vectorial
│   └── venv/                   ← Entorno virtual (se crea en instalación)
├── documentos/                 ← PDFs para indexar
└── .env                        ← GOOGLE_API_KEY (ya existe)
```

## 🌐 URLs en Producción

- **Sitio Web**: `https://app.xl.com.ar/administracion/recursosHumanos/p&p/`
- **Servicio RAG**: `http://localhost:8000` (solo accesible internamente)
- **API Docs**: `http://localhost:8000/docs` (solo interno)

## 🔐 Seguridad

- ✅ Servicio RAG **NO es público** (solo localhost)
- ✅ HTTPS en frontend (ya configurado)
- ✅ API key en .env (segura)
- ✅ ChromaDB local (no expuesto)

## 📊 Monitoreo

```bash
# Estado del servicio
sudo systemctl status rag-docugest

# Logs en tiempo real
sudo journalctl -u rag-docugest -f

# Reiniciar si es necesario
sudo systemctl restart rag-docugest

# Ver chunks indexados
curl http://localhost:8000/health
```

## ⚠️ Requisitos del Servidor

Mínimos:
- Ubuntu/Debian/Windows Server (o compatible)
- Python 3.8+
- 2GB RAM libres
- PHP 7.3.x (ya instalado)
- Puerto 8000 disponible internamente
- **NO necesita archivo .env** (API key hardcodeada)

## 🆘 Soporte

Si algo falla:

1. Ver logs: `sudo journalctl -u rag-docugest -n 100`
2. Verificar Python: `python3 --version`
3. Verificar dependencias: `cd rag-service && source venv/bin/activate && python -c "import fastapi"`
4. Consultar: [INSTALACION_PRODUCCION.md](INSTALACION_PRODUCCION.md)

## ✅ Checklist Post-Instalación

- [ ] Servicio corriendo: `sudo systemctl status rag-docugest`
- [ ] Health OK: `curl http://localhost:8000/health`
- [ ] Chunks > 0: Verificar en health response
- [ ] Sitio carga: `https://app.xl.com.ar/.../index.php`
- [ ] Chatbot responde: Probar con "Hola"
- [ ] Auto-inicio funciona: Verificar en consola del navegador (F12)

---

**Tiempo estimado de instalación:** 5-10 minutos

**¿Problemas?** Revisar [INSTALACION_PRODUCCION.md](INSTALACION_PRODUCCION.md) para troubleshooting detallado.
