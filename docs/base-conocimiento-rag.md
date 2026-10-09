# Base de Conocimiento IA (RAG)

Módulo que permite subir documentos (PDF, Word y Excel) para que queden indexados y disponibles para hacer preguntas en lenguaje natural sobre su contenido, usando IA generativa (Gemini).

## ¿Qué es RAG?

RAG (Retrieval-Augmented Generation) es la técnica de: en vez de que la IA "adivine" la respuesta con lo que ya sabe, primero se busca el fragmento de texto más relevante dentro de los documentos subidos, y luego se le pide a la IA que responda **basándose únicamente en ese fragmento**. Esto evita respuestas inventadas y permite citar la fuente exacta (archivo y fragmento).

## Flujo completo

### 1. Subida de un documento

1. El usuario sube un archivo (`.pdf`, `.docx`, `.xls` o `.xlsx`) desde la sección **Base de Conocimiento IA** del panel.
2. El sistema calcula un hash SHA-256 del archivo. Si ya existe un documento con el mismo hash, se rechaza como duplicado (no se vuelve a indexar dos veces el mismo archivo).
3. El binario se guarda en el disco local privado de Laravel (`storage/app/rag/...`) y la base de datos conserva su ruta, hash y metadatos.
4. Se dispara el proceso de indexado.

### 2. Extracción de texto

Según el tipo de archivo:

- **PDF**: se intenta extraer el texto página por página con la librería `smalot/pdfparser`. Si el PDF no tiene texto seleccionable (por ejemplo, es un escaneo), se usa Gemini como respaldo para leer el contenido.
- **Word (.docx)**: se lee el archivo como un ZIP (los `.docx` son en realidad un ZIP con XML adentro), se extrae `word/document.xml` y se limpia el XML para quedarnos con el texto plano.
- **Excel (.xls / .xlsx)**: se usa `PhpSpreadsheet` para recorrer todas las hojas y todas las filas, convirtiendo cada fila en una línea de texto.

> Nota: los archivos `.doc` (formato binario antiguo de Word, anterior a 2007) **no están soportados**. Hay que guardarlos como `.docx` antes de subirlos.

### 3. Fragmentación (chunking)

El texto extraído es demasiado largo para procesarlo de una sola vez, así que se divide en fragmentos ("chunks") de ~1200 caracteres, con un solape de 200 caracteres entre fragmentos consecutivos (para no cortar ideas a la mitad). Cada fragmento queda asociado a su página de origen (cuando aplica, como en PDF).

### 4. Generación de embeddings (vectorización)

Por cada fragmento de texto se llama a la API de Gemini (`gemini-embedding-001`) para convertirlo en un **embedding**: un vector de números que representa el "significado" del texto. Dos fragmentos con contenido parecido tendrán vectores parecidos, aunque usen palabras distintas.

Estos vectores se guardan en la base de datos en una columna `embedding` de tipo JSON (un array de números decimales). El proyecto no usa un motor de base de datos vectorial dedicado (como pgvector); es MySQL normal guardando el vector como JSON.

### 5. Pregunta del usuario (búsqueda + respuesta)

Cuando el usuario escribe una pregunta en el chat:

1. La pregunta también se convierte en un embedding (mismo modelo).
2. Se comparan ese embedding contra los embeddings de **todos** los fragmentos indexados, calculando la **similitud coseno** (una fórmula matemática que mide qué tan "parecidos" son dos vectores, entre 0 y 1). Esta comparación se hace en PHP, en memoria, recorriendo los fragmentos uno por uno (no hay índice vectorial optimizado — funciona bien con la cantidad de documentos esperada, pero no está pensado para millones de fragmentos).
3. Además de la similitud semántica, se suma un puntaje léxico simple (cuántas palabras clave de la pregunta aparecen literalmente en el fragmento). El puntaje final combina ambos: 85% semántico + 15% léxico.
4. Se toman los mejores fragmentos (por defecto, los 8 con mayor puntaje) y se descartan los que tengan puntaje muy bajo (posible pregunta sin relación con los documentos).
5. Esos fragmentos se arman como "contexto" y se envían a Gemini junto con una instrucción del tipo: *"Responde solo con la información de este contexto, no inventes datos, cita la fuente"*.
6. La respuesta de la IA se muestra en el chat, junto con la lista de fuentes (archivo, página y número de fragmento) que se usaron para generarla.

### 6. Importación de normativa municipal para PROVALE

En la pestaña **Documentos**, el botón **Extraer normativa PROVALE** consulta el repositorio oficial de la Municipalidad Distrital de La Esperanza: `https://www.muniesperanza.gob.pe/website/mde2026/normativa.php`.

El flujo:

1. Revisa ordenanzas, resoluciones, decretos y acuerdos publicados por el portal.
2. Hace un prefiltro flexible, sin distinguir mayúsculas, tildes ni signos, con los términos configurados en `config/normativa.php`. Incluye Vaso de Leche, PVL, Club/Clubes de Madres, comités y productos como hojuela de quinua y avena fortificada con vitaminas y minerales.
3. Las coincidencias expresamente configuradas en `direct_import_keywords` se consideran relevantes directamente. Para las coincidencias generales, el proveedor de IA descarta menciones incidentales.
4. Abre la página de **Copia verificable**, resuelve su enlace interno y valida la firma `%PDF-`. Luego guarda el PDF real en `storage/app/rag/normativa` y registra en la base de datos su nombre, tipo MIME, tamaño, hash y ruta privada antes de vectorizarlo.
5. Evita duplicados mediante el identificador oficial del portal y vuelve a intentar documentos fallidos. Las normas antiguas ya vectorizadas obtienen su copia local en la siguiente extracción, sin duplicar fragmentos.
6. Presenta los documentos del más reciente al más antiguo. Cada norma ofrece acciones separadas para ver el PDF guardado, descargarlo y abrir la Copia verificable oficial.
7. Incorpora los fragmentos, junto con los archivos subidos manualmente, a las respuestas de **Preguntar**. Cuando existe copia local, la fuente abre el PDF conservado por PROVALE.

La acción requiere permiso de creación sobre el módulo `base-conocimiento` y usa un bloqueo temporal para impedir dos extracciones simultáneas.

También se puede importar una norma puntual desde el campo **Copia verificable o número de resolución**:

- Enlace oficial, por ejemplo: `https://www.muniesperanza.gob.pe/website/mde2026/norma_descargar.php?id=35639`.
- Número de resolución, por ejemplo: `0750-2026-MDE`. El backend lo busca con el parámetro público `q` del repositorio y exige una coincidencia del número.

La importación puntual se considera una selección explícita del usuario, por lo que no necesita la clasificación de relevancia por IA. Solo admite enlaces del host y la ruta oficial, limita el tamaño del PDF y no permite redirecciones a rutas de archivos ajenas al portal.

Si el PDF es un escaneo sin capa de texto y el OCR de Gemini/Groq falla, el documento ya no queda descartado: se indexan como respaldo los metadatos oficiales publicados por el portal (título, tipo, número, fecha, asunto y concepto). Cada fragmento registra `text_source=portal_metadata` para conservar la trazabilidad de esa extracción parcial.

## Dónde está cada cosa en el código

| Parte | Archivo |
|---|---|
| Tablas de base de datos | `database/migrations/2026_09_28_000001_create_knowledge_base_tables.php` |
| Registro del módulo (permisos) | `database/migrations/2026_09_28_000002_add_knowledge_base_module.php` |
| Modelo documento | `app/Models/KnowledgeBaseDocument.php` |
| Modelo fragmento | `app/Models/KnowledgeBaseDocumentChunk.php` |
| Lógica de indexado, búsqueda y respuesta (RAG) | `app/Services/KnowledgeBase/KnowledgeBaseRagService.php` |
| Endpoints (subir, listar, borrar, preguntar) | `app/Http/Controllers/Api/KnowledgeBaseController.php` |
| Rutas | `routes/dashboard-api.php` (prefijo `dashboard/base-conocimiento`) |
| Configuración (tamaño de fragmento, límites) | `config/knowledge_base.php` |
| Pantalla del panel (subir + chat) | `resources/js/Sections/BaseConocimiento.jsx` |
| Extracción y clasificación de normativa | `app/Services/Normativa/NormativaScraperService.php` |
| Indexación y búsqueda de normativa | `app/Services/Normativa/NormativaRagService.php` |
| Términos de relevancia PVL | `config/normativa.php` |

## Tablas de base de datos

**`kb_documents`** — un registro por archivo subido:
- `title`, `file_name`, `mime_type`, `file_size`
- `file_hash`: hash SHA-256 del archivo (para detectar duplicados)
- `file_path`: ruta privada del binario en el disco local; `file_data` queda solo como compatibilidad con registros antiguos
- `index_status`: `PENDIENTE` → `INDEXANDO` → `INDEXADO` o `ERROR`
- `index_error`: mensaje de error si falló el indexado
- `created_by`: usuario que lo subió

**`normativa_documents`** — un registro por norma encontrada en el portal:
- datos oficiales: tipo, número, título, asunto, concepto, fecha y enlace de Copia verificable
- `file_name`, `mime_type`, `file_size`, `file_hash`, `file_path`: metadatos y ubicación privada del PDF descargado
- `relevancia_pvl`, resumen y motivo de inclusión
- `index_status` e `index_error`: estado de la vectorización

**`kb_document_chunks`** — un registro por fragmento de texto:
- `kb_document_id`: a qué documento pertenece
- `page_number`: página de origen (si aplica)
- `chunk_index`: orden del fragmento dentro del documento
- `content`: el texto del fragmento
- `embedding`: el vector generado por Gemini (JSON, array de números)
- `metadata`: información adicional (nombre de archivo, número de página, etc.)

## Permisos y acceso

El módulo se llama `base-conocimiento` dentro del sistema de módulos/roles ya existente (el mismo que usan Socios, Movimientos, Reportes PVL, etc.):

- Los usuarios con rol **administrador** tienen acceso automático.
- Para otros roles, hay que asignarles el módulo **"Base de Conocimiento IA"** desde **Sistema → Roles**, indicando si pueden ver, crear (subir) o eliminar documentos.

## Requisito importante: proveedor de IA

El sistema tiene una única variable de configuración global (`AI_PROVIDER` en el archivo `.env`) que decide qué proveedor de IA se usa **en todo el sistema** (no solo en este módulo, también en Reportes PVL y Normativa):

- `AI_PROVIDER=google` → usa Gemini. Es el único proveedor que actualmente soporta generar embeddings, así que **es obligatorio para que la búsqueda semántica funcione**.
- `AI_PROVIDER=groq` → usa Groq. Groq no tiene endpoint de embeddings, así que sin Gemini activo, la búsqueda cae a un modo "solo por palabras clave" (mucho menos preciso) y no se pueden vectorizar documentos nuevos.

Además se necesita tener configurada la variable `GOOGLE_API_KEY` con una clave válida de Google AI Studio.

## Relación con Generación Inteligente de Reportes PVL

Reportes PVL no entrega a Gemini una conexión SQL ni credenciales de la base de datos. El backend consulta de forma controlada las tablas del periodo (movimientos, productos, PECOSAs, raciones, beneficiarios y comités), construye una instantánea estructurada y recién entonces la envía al proveedor de IA como `datos_bd`.

El orden de resolución de cada reporte es:

1. Datos estructurados consultados en la base de datos del sistema.
2. Evidencia recuperada de documentos RAG del mismo periodo.
3. Valores predeterminados, únicamente cuando ni la BD ni los documentos aportan el dato.
4. Conciliación de Gemini y validación determinística de Laravel.

Cada campo conserva su origen (`BD`, `PREDETERMINADO`, `RAG` o `ENTRADA_USUARIO`). Si al terminar todavía falta información, la respuesta del análisis incluye la ruta exacta del campo, el motivo y si puede completarse en la revisión del análisis o requiere registrar filas/cargar un respaldo. Los predeterminados se administran en el apartado **Valores predeterminados** y cubren identidad, administración, compras, financiamiento, raciones, distribuciones, certificados, composición y beneficiarios. Se aplican antes de llamar a la IA, pero cualquier dato real hallado durante el análisis los reemplaza. Totales, saldos derivados, periodo y fecha de impresión se calculan automáticamente y no se configuran como predeterminados.

## Limitaciones conocidas

- No hay motor de base de datos vectorial: la búsqueda es un recorrido en memoria con similitud coseno calculada en PHP. Funciona bien para una base de documentos de tamaño moderado (cientos/pocos miles de fragmentos), pero no escala indefinidamente.
- No soporta `.doc` (Word antiguo, binario). Solo `.docx`.
- El tamaño máximo de archivo se controla con `knowledge_base.max_document_kb` en `config/knowledge_base.php` (20 MB por defecto).
