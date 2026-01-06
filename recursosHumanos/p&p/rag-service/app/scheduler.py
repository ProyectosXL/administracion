"""
Scheduler para auto-refresh de documentos cada 24 horas.
Solo se activa en Railway, no en local ni producción.
"""

import logging
import requests
from apscheduler.schedulers.background import BackgroundScheduler
from app.config import IS_RAILWAY

logger = logging.getLogger(__name__)


def refresh_documents():
    """
    Ejecuta refresh automático de documentos desde PHP server.
    Se conecta al endpoint /admin/refresh del mismo servicio.
    """
    try:
        logger.info("🔄 Iniciando refresh automático de documentos...")
        
        # Llamar al endpoint de refresh (localhost porque es el mismo servicio)
        response = requests.post(
            "http://localhost:8000/admin/refresh",
            timeout=300  # 5 minutos de timeout
        )
        
        if response.status_code == 200:
            data = response.json()
            logger.info(
                f"✅ Refresh exitoso: {data.get('nuevos_indexados', 0)} nuevos documentos indexados, "
                f"Total chunks: {data.get('total_chunks', 0)}"
            )
        else:
            logger.error(
                f"❌ Refresh falló con código {response.status_code}: {response.text}"
            )
            
    except requests.exceptions.Timeout:
        logger.error("❌ Timeout en refresh (>5 minutos). Verificar cantidad de documentos.")
    except Exception as e:
        logger.error(f"❌ Error inesperado en refresh automático: {str(e)}")


def start_scheduler():
    """
    Inicia el scheduler solo si estamos en Railway.
    Ejecuta refresh inmediatamente al iniciar y luego cada 24 horas.
    
    Returns:
        Scheduler instance o None si no está en Railway
    """
    if not IS_RAILWAY:
        logger.info("ℹ️  Scheduler deshabilitado (no estamos en Railway)")
        return None
    
    try:
        logger.info("⏰ Iniciando scheduler de auto-refresh...")
        
        scheduler = BackgroundScheduler(
            job_defaults={
                'coalesce': True,  # Si se acumulan ejecuciones, solo ejecutar una
                'max_instances': 1  # Solo una instancia del job a la vez
            }
        )
        
        # Agregar job que se ejecuta cada 24 horas
        scheduler.add_job(
            refresh_documents,
            'interval',
            hours=24,
            id='refresh_documents_24h',
            name='Auto-refresh documentos desde PHP',
            replace_existing=True
        )
        
        scheduler.start()
        
        logger.info("✅ Scheduler iniciado - refresh cada 24 horas")
        logger.info("🚀 Ejecutando primer refresh inmediatamente...")
        
        # Ejecutar inmediatamente al iniciar (no esperar 24h)
        refresh_documents()
        
        return scheduler
        
    except Exception as e:
        logger.error(f"❌ Error al iniciar scheduler: {str(e)}")
        return None
