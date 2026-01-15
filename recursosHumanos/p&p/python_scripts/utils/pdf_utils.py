"""
Utilidades para extraer texto de archivos PDF.
"""
import fitz  # PyMuPDF


def extract_text_from_pdf(pdf_path):
    """
    Extrae todo el texto de un archivo PDF.
    
    Args:
        pdf_path (str): Ruta completa al archivo PDF
        
    Returns:
        str: Texto completo extraído del PDF
        
    Raises:
        FileNotFoundError: Si el archivo no existe
        Exception: Si hay error al procesar el PDF
    """
    try:
        doc = fitz.open(pdf_path)
        text = ""
        
        for page_num in range(len(doc)):
            page = doc[page_num]
            text += page.get_text()
        
        doc.close()
        
        # Limpiar texto: remover líneas vacías múltiples
        text = '\n'.join(line for line in text.split('\n') if line.strip())
        
        return text
        
    except FileNotFoundError:
        raise FileNotFoundError(f"Archivo PDF no encontrado: {pdf_path}")
    except Exception as e:
        raise Exception(f"Error al procesar PDF {pdf_path}: {str(e)}")


def extract_text_from_pdf_bytes(pdf_bytes):
    """
    Extrae texto de un PDF desde bytes en memoria.
    
    Args:
        pdf_bytes (bytes): Contenido del PDF en bytes
        
    Returns:
        str: Texto completo extraído
    """
    try:
        doc = fitz.open(stream=pdf_bytes, filetype="pdf")
        text = ""
        
        for page_num in range(len(doc)):
            page = doc[page_num]
            text += page.get_text()
        
        doc.close()
        
        text = '\n'.join(line for line in text.split('\n') if line.strip())
        
        return text
        
    except Exception as e:
        raise Exception(f"Error al procesar PDF desde bytes: {str(e)}")
