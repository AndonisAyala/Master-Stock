import os
import requests
import mysql.connector
from mysql.connector import Error, pooling
from concurrent.futures import ThreadPoolExecutor
from tqdm import tqdm
import concurrent.futures
import time
from datetime import datetime

# Configuración
IMG_BB_API_KEY = "8cfa29eb505ce48692460a0b3a00d397"
IMAGES_FOLDER = "ready"
MAX_WORKERS = 3  # Reducido para mayor estabilidad

# Configuración MySQL con Connection Pool
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'Master-Stocks',
    'pool_name': 'mypool',
    'pool_size': 5,
    'autocommit': True
}

# Crear connection pool
try:
    connection_pool = mysql.connector.pooling.MySQLConnectionPool(**DB_CONFIG)
    print("✅ Connection Pool de MySQL creado exitosamente")
except Error as e:
    print(f"❌ Error creando Connection Pool: {e}")
    connection_pool = None

def get_db_connection():
    """Obtiene una conexión del pool"""
    try:
        return connection_pool.get_connection()
    except Error as e:
        print(f"❌ Error obteniendo conexión: {e}")
        return None

def check_existing_image(connection, idProducto, nameImg):
    """Verifica si la imagen ya existe en la BD"""
    cursor = None
    try:
        cursor = connection.cursor()
        query = "SELECT COUNT(*) FROM imagenes WHERE idProducto = %s AND nameImg = %s"
        cursor.execute(query, (idProducto, nameImg))
        result = cursor.fetchone()[0] > 0
        return result
    except Error as e:
        print(f"❌ Error verificando {nameImg}: {e}")
        return False
    finally:
        if cursor:
            cursor.close()

def insert_image(connection, idProducto, nameImg, url):
    """Inserta una nueva imagen en la BD con fecha y hora"""
    cursor = None
    try:
        cursor = connection.cursor()
        # Obtener fecha y hora actual
        fecha_actual = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        query = """
        INSERT INTO imagenes (idProducto, nameImg, url, fecha_subida, hora_subida) 
        VALUES (%s, %s, %s, %s, %s)
        """
        cursor.execute(query, (idProducto, nameImg, url, fecha_actual, fecha_actual))
        return True
    except Error as e:
        print(f"❌ Error insertando {nameImg}: {e}")
        return False
    finally:
        if cursor:
            cursor.close()

def update_image_timestamp(connection, idProducto, nameImg):
    """Actualiza la fecha y hora de una imagen existente"""
    cursor = None
    try:
        cursor = connection.cursor()
        fecha_actual = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        query = """
        UPDATE imagenes 
        SET fecha_subida = %s, hora_subida = %s 
        WHERE idProducto = %s AND nameImg = %s
        """
        cursor.execute(query, (fecha_actual, fecha_actual, idProducto, nameImg))
        return True
    except Error as e:
        print(f"❌ Error actualizando timestamp de {nameImg}: {e}")
        return False
    finally:
        if cursor:
            cursor.close()

def upload_to_imgbb(image_path):
    """Sube una imagen a imgBB y devuelve el enlace directo"""
    try:
        # Verificar que el archivo existe y tiene tamaño
        file_size = os.path.getsize(image_path)
        if file_size == 0:
            print(f"❌ Archivo vacío: {os.path.basename(image_path)}")
            return None
            
        with open(image_path, 'rb') as file:
            response = requests.post(
                "https://api.imgbb.com/1/upload",
                params={'key': IMG_BB_API_KEY},
                files={'image': file},
                timeout=60  # Aumentado timeout
            )
            response.raise_for_status()
            return response.json()['data']['url']
    except Exception as e:
        print(f"❌ Error subiendo {os.path.basename(image_path)}: {str(e)}")
        return None

def process_single_image(image_file):
    """Procesa una imagen individual con su propia conexión"""
    if '_' not in image_file:
        return (None, None, None, "invalid_format")
    
    connection = None
    try:
        idProducto = image_file.split('_')[0]
        nameImg = image_file
        
        # Obtener conexión para este hilo
        connection = get_db_connection()
        if not connection:
            return (idProducto, nameImg, None, "db_connection_error")
        
        # Verificar si ya existe en la base de datos
        if check_existing_image(connection, idProducto, nameImg):
            # Actualizar timestamp de imagen existente
            if update_image_timestamp(connection, idProducto, nameImg):
                return (idProducto, nameImg, None, "exists_updated")
            else:
                return (idProducto, nameImg, None, "exists")
        
        # Subir imagen a imgBB
        image_path = os.path.join(IMAGES_FOLDER, image_file)
        
        # Verificar que el archivo existe
        if not os.path.exists(image_path):
            return (idProducto, nameImg, None, "file_not_found")
            
        url = upload_to_imgbb(image_path)
        
        if url:
            # Insertar en BD con fecha y hora
            if insert_image(connection, idProducto, nameImg, url):
                return (idProducto, nameImg, url, "uploaded")
            else:
                return (idProducto, nameImg, None, "db_error")
        else:
            return (idProducto, nameImg, None, "upload_error")
            
    except Exception as e:
        print(f"⚠️ Error procesando {image_file}: {str(e)}")
        return (idProducto, nameImg, None, "process_error")
    finally:
        if connection and connection.is_connected():
            connection.close()

def get_current_datetime():
    """Obtiene la fecha y hora actual formateada"""
    return datetime.now().strftime('%Y-%m-%d %H:%M:%S')

def main():
    start_time = datetime.now()
    start_datetime = start_time.strftime('%Y-%m-%d %H:%M:%S')
    
    print("🚀 Iniciando proceso de carga de imágenes a MySQL...")
    print(f"📅 Inicio del proceso: {start_datetime}")
    print("=" * 60)
    
    if not connection_pool:
        print("❌ No se pudo inicializar el Connection Pool")
        return
    
    # Verificar carpeta de imágenes
    if not os.path.exists(IMAGES_FOLDER):
        print(f"❌ Error: No se encuentra la carpeta '{IMAGES_FOLDER}'")
        return
    
    try:
        # Obtener lista de imágenes válidas
        image_files = [
            f for f in os.listdir(IMAGES_FOLDER) 
            if f.lower().endswith(('.jpg', '.jpeg', '.png')) and '_' in f
        ]
        
        if not image_files:
            print("ℹ️ No se encontraron imágenes válidas para procesar")
            return
        
        print(f"🔍 Encontradas {len(image_files)} imágenes para procesar")
        print("⏳ Procesando... Esto puede tomar varios minutos")
        print("💡 Usando Connection Pool para mayor estabilidad")
        
        # Procesar en paralelo
        results = {
            'uploaded': 0,
            'exists': 0,
            'exists_updated': 0, 
            'errors': 0,
            'db_errors': 0,
            'upload_errors': 0,
            'file_not_found': 0,
            'invalid_format': 0
        }
        
        # Procesar en lotes para mejor manejo de memoria
        batch_size = 100
        total_batches = (len(image_files) + batch_size - 1) // batch_size
        
        for batch_num in range(total_batches):
            batch_start_time = datetime.now()
            start_idx = batch_num * batch_size
            end_idx = min((batch_num + 1) * batch_size, len(image_files))
            batch_files = image_files[start_idx:end_idx]
            
            print(f"\n📦 Lote {batch_num + 1}/{total_batches} - Inicio: {batch_start_time.strftime('%H:%M:%S')}")
            print(f"   Imágenes en lote: {len(batch_files)}")
            
            with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
                futures = {executor.submit(process_single_image, img_file): img_file for img_file in batch_files}
                
                # Procesar resultados con barra de progreso
                for future in tqdm(concurrent.futures.as_completed(futures), 
                                total=len(futures), 
                                desc=f"Lote {batch_num + 1}"):
                    result = future.result()
                    if result:
                        idProducto, nameImg, url, status = result
                        
                        # Contar resultados
                        if status == "uploaded":
                            results['uploaded'] += 1
                        elif status == "exists":
                            results['exists'] += 1
                        elif status == "exists_updated":
                            results['exists_updated'] += 1
                        elif status == "db_error":
                            results['db_errors'] += 1
                        elif status == "upload_error":
                            results['upload_errors'] += 1
                        elif status == "file_not_found":
                            results['file_not_found'] += 1
                        elif status == "invalid_format":
                            results['invalid_format'] += 1
                        else:
                            results['errors'] += 1
            
            batch_end_time = datetime.now()
            batch_duration = (batch_end_time - batch_start_time).total_seconds()
            print(f"   Duración del lote: {batch_duration:.1f} segundos")
            
            # Pequeña pausa entre lotes
            if batch_num < total_batches - 1:
                print("⏳ Pausa entre lotes...")
                time.sleep(2)
        
        # Calcular tiempo total
        end_time = datetime.now()
        total_duration = (end_time - start_time).total_seconds()
        end_datetime = end_time.strftime('%Y-%m-%d %H:%M:%S')
        
        # Estadísticas finales
        print("\n" + "="*60)
        print("🎉 PROCESO COMPLETADO!")
        print("="*60)
        print(f"📅 INICIO: {start_datetime}")
        print(f"📅 FIN:    {end_datetime}")
        print(f"⏱️  DURACIÓN TOTAL: {total_duration:.2f} segundos")
        print("\n📊 RESULTADOS DETALLADOS:")
        print(f"✅ Imágenes nuevas subidas: {results['uploaded']}")
        print(f"🔄 Imágenes existentes (timestamp actualizado): {results['exists_updated']}")
        print(f"⏩ Imágenes existentes (sin cambios): {results['exists']}")
        print(f"📁 Archivos no encontrados: {results['file_not_found']}")
        print(f"🚫 Formato inválido: {results['invalid_format']}")
        print(f"💾 Errores de base de datos: {results['db_errors']}")
        print(f"🌐 Errores de subida a imgBB: {results['upload_errors']}")
        print(f"⚠️ Otros errores: {results['errors']}")
        
        total_processed = sum(results.values())
        success_rate = (results['uploaded'] / total_processed * 100) if total_processed > 0 else 0
        print(f"\n📈 ESTADÍSTICAS:")
        print(f"Total procesadas: {total_processed}")
        print(f"Tasa de éxito (nuevas subidas): {success_rate:.1f}%")
        print(f"Velocidad promedio: {total_processed/total_duration:.2f} imágenes/segundo")
        
    except Exception as e:
        print(f"❌ Error general en el proceso: {str(e)}")
    finally:
        if connection_pool:
            print("🔌 Cerrando Connection Pool...")

# Función para ver registros con fechas
def view_records_with_dates(limit=10):
    """Muestra los últimos registros con sus fechas"""
    connection = get_db_connection()
    if not connection:
        return
        
    try:
        cursor = connection.cursor()
        query = """
        SELECT idProducto, nameImg, fecha_subida, hora_subida 
        FROM imagenes 
        ORDER BY fecha_subida DESC, hora_subida DESC 
        LIMIT %s
        """
        cursor.execute(query, (limit,))
        records = cursor.fetchall()
        
        print(f"\n📋 ÚLTIMOS {limit} REGISTROS:")
        print("-" * 80)
        print(f"{'ID Producto':<12} {'Nombre Imagen':<30} {'Fecha':<12} {'Hora':<10}")
        print("-" * 80)
        for record in records:
            idProducto, nameImg, fecha, hora = record
            print(f"{idProducto:<12} {nameImg:<30} {fecha.strftime('%Y-%m-%d') if fecha else 'N/A':<12} {hora.strftime('%H:%M:%S') if hora else 'N/A':<10}")
            
    except Error as e:
        print(f"❌ Error consultando registros: {e}")
    finally:
        if connection and connection.is_connected():
            connection.close()

if __name__ == "__main__":
    import sys
    if len(sys.argv) > 1 and sys.argv[1] == "view":
        view_records_with_dates(20)
    else:
        main()
