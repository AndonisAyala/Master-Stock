# Master-Stock

Descripción
- Proyecto de referencia para gestión de inventarios y manejo avanzado de imágenes.
- Integra datos provenientes del sistema administrativo A2Softway para sincronizar productos y completar información útil para vendedores.
- Propósito: referencia técnica y demostración; requiere adaptación y auditoría para uso en producción.

Estado
- Prototipo / Demo — uso educativo y de referencia.

Resumen de la integración con A2Softway
- Fuente de datos: A2Softway actúa como sistema administrativo principal. Master-Stock se alimenta de la base de datos de A2Softway para obtener y retroalimentar información de productos.
- Datos sincronizados: productos (descripciones, cantidades, códigos de referencia, posición en almacén, marca), peso y metadatos necesarios para venta.
- Operaciones soportadas desde Master-Stock (cuando aplica): ver y editar descripciones, actualizar cantidades, editar códigos de referencia, ajustar posición/ubicación, asignar marca y registrar peso.
- Flujo: A2Softway → Master-Stock (lectura) y Master-Stock → A2Softway (cuando se requiere retroalimentación), según la configuración del sistema administrativo.

Módulos de imágenes
- Remoción de fondo: integración con scripts en Python que limpian el fondo de las fotos (ej.: `removeBgBatch6WatchDog.py`), para generar imágenes listas para marketplace.
- Subida para carga masiva ML: el proyecto usa ImgBB para alojar imágenes y generar enlaces públicos que se incorporan al formulario Excel de carga masiva de MercadoLibre. ImgBB es, actualmente, el servicio soportado para obtener enlaces de imágenes que MercadoLibre acepta en su carga masiva cuando no hay integración API directa.
- Nota: No se utiliza Google Drive para este flujo; por favor, no versionar credenciales de servicios externos.

Características principales
- Soporta la consolidación de datos desde A2Softway para preparar productos de venta.
- Herramientas para facilitar el trabajo de vendedores (imágenes optimizadas, metadatos completos).
- Alternativa de publicación en MercadoLibre mediante generación de Excel + enlaces de ImgBB para usuarios sin API.
- Módulos de administración (vistas para cliente/vendedor, carga/edición de productos, gestión de inventario).

Requisitos
- PHP 7.4+ o PHP 8.x
- Servidor web (Apache, Nginx) o entorno como Laragon/XAMPP
- Base de datos MySQL o MariaDB (conexión a la BD de A2Softway según permisos)
- Python 3.x (para scripts de procesamiento de imágenes)
- `git` y opcional `gh` (CLI de GitHub)

Instalación rápida (local)
1. Clonar:
   git clone https://github.com/AndonisAyala/Master-Stock.git
   cd Master-Stock
2. Colocar el proyecto en la carpeta de tu servidor local (p. ej. `www` de Laragon) o configurar un virtual host.
3. Configurar conexión a la base de datos de A2Softway: ajustar `Master-Stocks/php/connectDataBase.php` o usar variables de entorno/configuración fuera del repositorio. Asegúrate de tener permisos y acuerdos para leer/escribir la DB de A2Softway.
4. Instalar las extensiones PHP necesarias (`mysqli`, `gd`/`imagick`), y dependencias Python necesarias (`pip install -r requirements.txt` si existe).
5. Configurar las credenciales para ImgBB (API key) en un archivo local excluido por `.gitignore` o mediante variables de entorno.

## Dependencias Python (módulos de imagen)

Los módulos de procesamiento y subida de imágenes requieren Python 3.x y las siguientes dependencias:

- Para subida a ImgBB y conexión a MySQL (`uploadImg02.py`):
  - `requests`
  - `mysql-connector-python`
  - `tqdm`

- Para remoción de fondo y watchdog (`removeBgBatch6WatchDog.py`):
  - `rembg`
  - `pillow`
  - `watchdog`
  - `onnxruntime`
  - `numpy`

Notas:
- Para GPU usa `onnxruntime-gpu` si tu entorno lo soporta.
- `rembg` descargará modelos (ej. `u2net`) la primera vez que se ejecute.
- Mantén las claves/API tokens fuera del repositorio; configúralos como variables de entorno o en archivos locales excluidos por `.gitignore`.

Estructura del repositorio (resumen)
- `index.php` — Entrada principal.
- `Master-Stocks/` — Lógica del proyecto, controladores, vistas y scripts.
  - `controller/` — JS y PHP auxiliares.
  - `python/` — Scripts Python (p. ej. `uploadImgDrive.py` si existe por compatibilidad local, `removeBgBatch6WatchDog.py`).
  - `php/` — Endpoints y utilidades de backend.
- `model/` — Vistas y plantillas HTML/PHP.
- `style/` — CSS y assets.
- `uploads/` — Archivos subidos (NO versionar).
- `ready/` — Imágenes procesadas (NO versionar).

## Esquema MySQL exportado

Se ha añadido a la raíz el volcado de la estructura MySQL sin datos: `schema_mysql_sample.sql`.
Este archivo contiene las tablas principales utilizadas por Master-Stock. Ejemplos relevantes:

- Tablas referenciadas por la integración con A2Softway (estado: descontinuadas):
  - `producto`, `categoria`, `departamento`, `marca` — Estas tablas estaban originalmente sincronizadas con A2Softway; actualmente su uso en la integración ha sido descontinuado en favor de las tablas y flujos locales del proyecto. No se publica la estructura interna de A2Softway.

- Tablas/BD locales usadas por módulos del proyecto:
  - `invproducto` — almacena inventario de productos que no están en A2; utilizado por el módulo de "Inventario local" para registrar productos desde el punto de venta o inventario físico.
  - `imagenes` — metadatos de imágenes (nombre, URL, fecha/hora de subida) usados por `uploadImg02.py`.
  - `checkproducto` — registros de verificación de productos contra A2Softway (estado de verificación y fecha de última revisión).

Nota sobre el módulo de inventario local:
- El proyecto incluye un módulo que permite registrar/gestionar inventario de productos que aún no existen en A2. Este módulo está pensado para uso en la red local del establecimiento y puede accederse desde cualquier dispositivo con acceso a la LAN (PC, tablet, móvil). Su función principal es capturar código, descripción, marca, posición y cantidad, y opcionalmente preparar esos registros para su revisión o sincronización posterior.

No se incluye ni se publica la estructura interna de la base de datos de A2Softway; para integraciones con A2 es necesario contar con permisos y acuerdos con el administrador del sistema A2.

Seguridad y buenas prácticas
- Nunca almacenar tokens, credenciales ni `.env` dentro del repo. Añadir a `.gitignore` los archivos sensibles: `token.json`, `.env`, `.venv/`, `uploads/`, `ready/`, `Master-Stocks/temp/`.
- Si algún secreto se filtró, rotar/revocar inmediatamente (ImgBB API keys, otros). Luego limpiar el historial con `git filter-repo` o BFG (operación destructiva — hacer backup).
- Para archivos grandes (imágenes), usar `git-lfs` o almacenamiento externo (buckets, CDN).
- Auditoría: revisar permisos de acceso a la DB de A2Softway antes de habilitar escritura desde Master-Stock.

Flujo de trabajo recomendado
- Ramas por funcionalidad: `feature/xxx`, `fix/xxx`, `chore/xxx`.
- Commits atómicos con formato: `type(scope): mensaje corto` (ej.: `feat(images): add background removal script`).
- Usar Pull Requests para revisión y protección de `main` (requerir checks de CI y revisiones).

Integración continua (sugerencia)
- Añadir workflows en `.github/workflows/` para:
  - Comprobaciones básicas (PHP lint, pruebas).
  - Validaciones de seguridad (scans estáticos).
  - Opcional: pipeline para generar artefactos o tests de integración con una base de datos de prueba.

Contribuciones
- Fork → branch → commits atómicos → Pull Request con descripción y pasos de prueba.
- Documentar cambios que afectan la sincronización con A2Softway o la forma de generar Excel para MercadoLibre.

Licencia y uso
- Selecciona una licencia clara: MIT/Apache-2.0 para permitir reutilización, o un aviso propietario si deseas restringir uso. Consulta asesoría legal para cláusulas propietarias.
- Si el repositorio contiene material con restricciones (por ejemplo datos de A2Softway), documenta las condiciones de uso y accesos.

Contacto
- Autor: Andonis Ayala  
- GitHub: https://github.com/AndonisAyala