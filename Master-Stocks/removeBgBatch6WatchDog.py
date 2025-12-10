import os
import io
import time
import logging
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler
from rembg import remove
from rembg.session_factory import new_session
from PIL import Image

# ======= CONFIGURACIÓN ======= #
INPUT_FOLDER = "uploads"          # Carpeta donde se monitorean nuevas imágenes
OUTPUT_FOLDER = "ready"           # Carpeta donde se guardan las imágenes procesadas
MODELO = "u2net"                  # Modelo de rembg (u2net, u2netp, u2net_human_seg)
LOG_FILE = "procesamiento.log"    # Archivo de registro de eventos
CHECK_INTERVAL = 1                # Intervalo de verificación (segundos)

# Configurar logging
logging.basicConfig(
    filename=LOG_FILE,
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s"
)

# ======= FUNCIONES AUXILIARES ======= #
def configurar_entorno():
    """Configura variables de entorno para rembg."""
    os.environ["U2NET_HOME"] = os.path.expanduser("~/.u2net")
    os.makedirs(os.environ["U2NET_HOME"], exist_ok=True)
    # Opcional: Forzar CPU si hay problemas con CUDA
    os.environ["ORT_DISABLE_CUDA"] = "0"  # 1 para forzar CPU

def verificar_directorios():
    """Asegura que las carpetas de entrada y salida existan."""
    os.makedirs(INPUT_FOLDER, exist_ok=True)
    os.makedirs(OUTPUT_FOLDER, exist_ok=True)

def archivo_ya_procesado(nombre_archivo):
    """Evita reprocesar archivos ya convertidos."""
    nombre_base = os.path.splitext(nombre_archivo)[0]
    for archivo in os.listdir(OUTPUT_FOLDER):
        if os.path.splitext(archivo)[0] == nombre_base:
            return True
    return False

def recortar_espacio_vacio(imagen):
    """Recorta bordes transparentes/innecesarios."""
    if imagen.mode != 'RGBA':
        imagen = imagen.convert('RGBA')
    bbox = imagen.getbbox()
    return imagen.crop(bbox) if bbox else imagen

def aplicar_fondo_blanco(imagen_bytes):
    """Convierte fondo transparente a blanco."""
    imagen = Image.open(io.BytesIO(imagen_bytes))
    if imagen.mode in ('RGBA', 'LA'):
        fondo = Image.new('RGB', imagen.size, (255, 255, 255))
        fondo.paste(imagen, mask=imagen.split()[-1])
        imagen = fondo
    img_byte_arr = io.BytesIO()
    imagen.save(img_byte_arr, format='PNG')
    return img_byte_arr.getvalue()

# ======= PROCESAMIENTO DE IMÁGENES ======= #
def procesar_imagen(input_path, filename, session):
    """Procesa una imagen: remueve fondo, recorta y aplica fondo blanco."""
    try:
        if archivo_ya_procesado(filename):
            logging.info(f"Archivo omitido (ya existe): {filename}")
            print(f"⏩ {filename} (ya procesado)")
            return False
        
        output_path = os.path.join(OUTPUT_FOLDER, filename)
        
        with open(input_path, "rb") as f_in:
            # Remover fondo con rembg
            imagen_procesada = remove(f_in.read(), session=session)
            
            # Recortar y aplicar fondo blanco
            img_pil = Image.open(io.BytesIO(imagen_procesada))
            img_pil = recortar_espacio_vacio(img_pil)
            output_buffer = io.BytesIO()
            img_pil.save(output_buffer, format='PNG')
            imagen_procesada = aplicar_fondo_blanco(output_buffer.getvalue())
        
        # Guardar imagen final
        with open(output_path, "wb") as f_out:
            f_out.write(imagen_procesada)
        
        logging.info(f"Procesada: {filename}")
        print(f"✅ {filename}")
        return True

    except Exception as e:
        logging.error(f"Error procesando {filename}: {str(e)}")
        print(f"❌ Error en {filename}: {str(e)}")
        return False

# ======= MONITOREO CON WATCHDOG ======= #
class ImageHandler(FileSystemEventHandler):
    """Maneja eventos de nuevos archivos."""
    def __init__(self, session):
        self.session = session
    
    def on_created(self, event):
        if not event.is_directory:
            file_path = event.src_path
            if file_path.lower().endswith(('.png', '.jpg', '.jpeg', '.webp')):
                filename = os.path.basename(file_path)
                procesar_imagen(file_path, filename, self.session)

def iniciar_servicio():
    """Inicia el servicio de monitoreo."""
    print("\n" + "="*50)
    print("   🖼️  Servicio de Procesamiento de Imágenes   ")
    print("="*50)
    print(f"● Monitorizando: {os.path.abspath(INPUT_FOLDER)}")
    print(f"● Modelo: {MODELO}")
    print(f"● Fondo: Blanco")
    print(f"● Log: {LOG_FILE}")
    print("\nPresiona Ctrl + C para detener\n")

    configurar_entorno()
    verificar_directorios()
    session = new_session(MODELO)
    
    event_handler = ImageHandler(session)
    observer = Observer()
    observer.schedule(event_handler, INPUT_FOLDER, recursive=False)
    observer.start()
    
    try:
        while True:
            time.sleep(CHECK_INTERVAL)
    except KeyboardInterrupt:
        print("\nDeteniendo el servicio...")
        observer.stop()
    observer.join()

if __name__ == "__main__":
    iniciar_servicio()