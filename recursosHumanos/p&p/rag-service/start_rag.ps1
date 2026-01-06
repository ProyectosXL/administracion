$ErrorActionPreference = "Stop"
Set-Location "C:\xampp\htdocs\administracion\recursosHumanos\p&p\rag-service"
$env:PYTHONIOENCODING = "utf-8"
Write-Host "Iniciando servicio RAG desde: $(Get-Location)" -ForegroundColor Cyan
& "C:\Users\cfede\miniconda3\envs\rag-docugest\python.exe" -m uvicorn app.main:app --host 127.0.0.1 --port 8002
