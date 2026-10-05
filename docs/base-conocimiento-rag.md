# Base de Conocimiento IA (RAG)

Módulo que permite subir documentos (PDF, Word y Excel) para que queden indexados y disponibles para hacer preguntas en lenguaje natural sobre su contenido, usando IA generativa (Gemini).

## ¿Qué es RAG?

RAG (Retrieval-Augmented Generation) es la técnica de: en vez de que la IA "adivine" la respuesta con lo que ya sabe, primero se busca el fragmento de texto más relevante dentro de los documentos subidos, y luego se le pide a la IA que responda **basándose únicamente en ese fragmento**. Esto evita respuestas inventadas y permite citar la fuente exacta (archivo y fragmento).

## Flujo completo

### 1. Subida de un documento

1. El usuario sube un archivo (`.pdf`, `.docx`, `.xls` o `.xlsx`) desde la sección **Base de Conocimiento IA** del panel.
2. El sistema calcula un hash SHA-256 del archivo. Si ya existe un documento con el mismo hash, se rechaza como duplicado (no se vuelve a indexar dos veces el mismo archivo).
3. El archivo se guarda comprimido dentro de la base de datos (no en disco), igual que en el módulo de Reportes PVL.
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

## Tablas de base de datos

**`kb_documents`** — un registro por archivo subido:
- `title`, `file_name`, `mime_type`, `file_size`
- `file_hash`: hash SHA-256 del archivo (para detectar duplicados)
- `file_data`: contenido del archivo comprimido y en base64 (se guarda dentro de la BD, no en disco)
- `index_status`: `PENDIENTE` → `INDEXANDO` → `INDEXADO` o `ERROR`
- `index_error`: mensaje de error si falló el indexado
- `created_by`: usuario que lo subió

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

## Limitaciones conocidas

- No hay motor de base de datos vectorial: la búsqueda es un recorrido en memoria con similitud coseno calculada en PHP. Funciona bien para una base de documentos de tamaño moderado (cientos/pocos miles de fragmentos), pero no escala indefinidamente.
- No soporta `.doc` (Word antiguo, binario). Solo `.docx`.
- El tamaño máximo de archivo se controla con `knowledge_base.max_document_kb` en `config/knowledge_base.php` (20 MB por defecto).
