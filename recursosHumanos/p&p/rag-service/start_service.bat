@echo off
echo ========================================
echo Iniciando Servicio RAG - DocuGest
echo ========================================
cd /d "c:\xampp\htdocs\administracion\recursosHumanos\p&p\rag-service"
C:\Users\cfede\miniconda3\envs\rag-docugest\python.exe -m uvicorn app.main:app --reload --port 8000
pause
