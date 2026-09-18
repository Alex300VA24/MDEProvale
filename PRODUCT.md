# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Personal municipal autorizado que administra el Programa del Vaso de Leche de la Municipalidad Distrital de La Esperanza. Trabaja con padrones, comités, compras, inventario, distribuciones, responsables y documentos oficiales por período.

## Product Purpose

PROVALE centraliza la operación del Programa del Vaso de Leche. La ampliación de reportes permite preparar, revisar y emitir los anexos oficiales PVL y Ración A, además de un informe sustentatorio con datos verificables del sistema y de documentos de respaldo.

## Positioning

La generación documental combina datos estructurados, recuperación documental trazable y extracción con el proveedor seleccionado mediante `AI_PROVIDER`; Laravel conserva la autoridad sobre reglas, cálculos, validación y autorización humana, y las plantillas Blade oficiales conservan el diseño final.

## Operating Context

El trabajo se organiza por mes y año. El personal revisa beneficiarios, comités, productos, PECOSAs, movimientos de stock, raciones, responsables y archivos antes de emitir documentos PDF oficiales. Los conflictos y datos faltantes se revisan en la interfaz administrativa, no dentro del PDF.

## Capabilities and Constraints

- Laravel 10, PHP 8.1, Inertia React, Tailwind CSS y DOMPDF.
- Acceso regido por los permisos existentes del módulo `reportes`.
- La selección `AI_PROVIDER=google|groq` se aplica al asistente, al análisis estructurado y a la extracción visual; ninguna credencial se expone o duplica.
- El proveedor de IA solo devuelve datos JSON estructurados. No genera HTML, Blade ni cálculos determinísticos finales.
- Google aporta embeddings semánticos. Groq usa recuperación léxica determinística porque su API no ofrece embeddings; los PDF con texto se extraen localmente y las imágenes usan `GROQ_VISION_MODEL`.
- Blade es la plantilla maestra fija; Formato PVL usa A4 horizontal y Ración A usa A4 vertical.
- Cuando se generan ambos anexos, se emite un tercer PDF sustentatorio con resumen, matriz de acreditación, fuentes, advertencias, conclusiones y relación de anexos.
- El informe solo declara la remisión a Contraloría como acreditada cuando existe constancia documental indexada y código de envío confirmado.
- Datos desconocidos permanecen nulos o vacíos según la semántica del campo. Nunca se inventan valores administrativos.
- Fuentes documentales se filtran por período y metadatos, se fragmentan y conservan trazabilidad.

## Brand Commitments

Nombre PROVALE y lenguaje institucional municipal en español. La interfaz nueva extiende el sistema visual existente y conserva sus componentes, tipografía, paleta, navegación y comportamiento responsive.

## Evidence on Hand

- Plantillas maestras y contratos de ejemplo en `plv_blade_templates/`.
- Datos estructurados existentes de beneficiarios, comités, productos, PECOSAs, stock, raciones, responsables y períodos.
- Configuración de Google/Groq y cliente unificado en `config/services.php` y `app/Services/AssistantAiService.php`.
- No existen tablas estructuradas de proveedores, financiamiento ni certificados; esos campos requieren evidencia documental o entrada autorizada y no pueden fabricarse.

## Product Principles

- Trazabilidad antes que automatización opaca.
- Cálculos críticos siempre determinísticos en backend.
- Revisión humana cuando haya conflicto o ausencia crítica.
- Plantillas oficiales estables; solo cambian sus datos.
- Reutilizar arquitectura y permisos existentes.

## Accessibility & Inclusion

La interfaz debe conservar navegación por teclado, foco visible, etiquetas explícitas, estados comprensibles y adaptación móvil del dashboard existente.
