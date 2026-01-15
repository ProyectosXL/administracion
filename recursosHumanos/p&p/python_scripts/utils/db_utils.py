"""
Utilidades para conexión a SQL Server.
Lee credenciales desde el archivo .env del proyecto.
"""
import pyodbc
import os
from pathlib import Path


def load_env_vars():
    """
    Carga variables de entorno desde el archivo .env del proyecto.
    
    Returns:
        dict: Diccionario con variables de entorno
    """
    env_vars = {}
    # El .env está en el root: administracion/.env
    # Desde utils/ debemos subir: utils -> python_scripts -> p&p -> recursosHumanos -> administracion
    env_path = Path(__file__).resolve().parent.parent.parent.parent.parent / '.env'
    
    if not env_path.exists():
        raise FileNotFoundError(f"Archivo .env no encontrado en: {env_path}")
    
    with open(env_path, 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                key, value = line.split('=', 1)
                env_vars[key.strip()] = value.strip().strip('"').strip("'")
    
    return env_vars


def get_db_connection():
    """
    Crea conexión a SQL Server usando credenciales del .env.
    
    Returns:
        pyodbc.Connection: Conexión activa a la base de datos central
        
    Raises:
        Exception: Si no puede conectar a la base de datos
    """
    try:
        env_vars = load_env_vars()
        
        # Obtener credenciales
        host = env_vars.get('HOST_CENTRAL', '')
        database = env_vars.get('DATABASE_CENTRAL', '')
        user = env_vars.get('USER', '')
        password = env_vars.get('PASS', '')
        
        # Construir connection string
        conn_string = (
            'DRIVER={SQL Server};'
            f'SERVER={host};'
            f'DATABASE={database};'
            f'UID={user};'
            f'PWD={password};'
        )
        
        conn = pyodbc.connect(conn_string)
        return conn
        
    except FileNotFoundError as e:
        raise Exception(f"Error cargando configuración: {str(e)}")
    except pyodbc.Error as e:
        raise Exception(f"Error conectando a SQL Server: {str(e)}")
    except Exception as e:
        raise Exception(f"Error inesperado en conexión: {str(e)}")


def get_documento_info(documento_id):
    """
    Obtiene información de un documento desde la BD.
    
    Args:
        documento_id (int): ID del documento
        
    Returns:
        dict: Información del documento (ruta_archivo, titulo, sector_id, tipo)
        
    Raises:
        Exception: Si el documento no existe o hay error de BD
    """
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        cursor.execute("""
            SELECT id, titulo, ruta_archivo, sector_id, tipo
            FROM Politicas_Procedimientos
            WHERE id = ?
        """, (documento_id,))
        
        row = cursor.fetchone()
        
        if not row:
            raise Exception(f"Documento con ID {documento_id} no encontrado")
        
        info = {
            'id': row[0],
            'titulo': row[1],
            'ruta_archivo': row[2],
            'sector_id': row[3],
            'tipo': row[4]
        }
        
        cursor.close()
        conn.close()
        
        return info
        
    except Exception as e:
        raise Exception(f"Error obteniendo información del documento: {str(e)}")


def get_glosario_existente():
    """
    Obtiene todos los términos del glosario existentes en la BD.
    
    Returns:
        list: Lista de dicts con términos existentes
    """
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        cursor.execute("""
            SELECT id, termino, definicion, sector_id
            FROM Glosario
        """)
        
        glosario = []
        for row in cursor.fetchall():
            glosario.append({
                'id': row[0],
                'termino': row[1],
                'definicion': row[2],
                'sector_id': row[3]
            })
        
        cursor.close()
        conn.close()
        
        return glosario
        
    except Exception as e:
        raise Exception(f"Error obteniendo glosario existente: {str(e)}")


def update_documento_tags(documento_id, tags_string):
    """
    Actualiza los tags de un documento en la BD.
    
    Args:
        documento_id (int): ID del documento
        tags_string (str): Tags separados por comas
    """
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        cursor.execute("""
            UPDATE Politicas_Procedimientos
            SET tags = ?
            WHERE id = ?
        """, (tags_string, documento_id))
        
        conn.commit()
        cursor.close()
        conn.close()
        
    except Exception as e:
        raise Exception(f"Error actualizando tags: {str(e)}")


def insert_termino_glosario(termino, definicion, sector_id):
    """
    Inserta un nuevo término en el glosario.
    Verifica que no exista duplicado antes de insertar.
    
    Args:
        termino (str): Término a agregar
        definicion (str): Definición del término
        sector_id (int): ID del sector
        
    Returns:
        bool: True si se insertó, False si ya existía
    """
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        # Verificar si ya existe (case-insensitive)
        cursor.execute("""
            SELECT COUNT(*) 
            FROM Glosario 
            WHERE LOWER(termino) = LOWER(?)
        """, (termino,))
        
        existe = cursor.fetchone()[0] > 0
        
        if existe:
            cursor.close()
            conn.close()
            return False
        
        # Insertar nuevo término
        cursor.execute("""
            INSERT INTO Glosario (termino, definicion, sector_id, fecha_creacion)
            VALUES (?, ?, ?, GETDATE())
        """, (termino, definicion, sector_id))
        
        conn.commit()
        cursor.close()
        conn.close()
        
        return True
        
    except Exception as e:
        raise Exception(f"Error insertando término en glosario: {str(e)}")
