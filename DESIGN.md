---
name: PROVALE
description: Sistema institucional claro para operar, revisar y documentar el Programa del Vaso de Leche.
colors:
  primary: "#0B3A66"
  primary-deep: "#072A4D"
  primary-mid: "#175A91"
  primary-soft: "#E1EDF7"
  canvas: "#EEF4FC"
  surface: "#FFFFFF"
  surface-subtle: "#F1F5F9"
  text: "#1A2E4A"
  text-secondary: "#506E8D"
  border: "#D6E1EC"
  success: "#115E59"
  success-soft: "#CCFBF1"
  warning: "#B87300"
  warning-soft: "#FEF3DC"
  danger: "#B4232D"
  danger-soft: "#FEE2E2"
  focus: "#FBBF24"
typography:
  headline:
    fontFamily: "Lexend, Source Sans 3, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 800
    lineHeight: 1.25
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Lexend, Source Sans 3, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 800
    lineHeight: 1.3
    letterSpacing: "-0.01em"
  body:
    fontFamily: "Source Sans 3, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Source Sans 3, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "0.05em"
rounded:
  control: "0.375rem"
  surface: "0.75rem"
  modal: "1rem"
  pill: "9999px"
spacing:
  xs: "0.25rem"
  sm: "0.5rem"
  md: "1rem"
  lg: "1.5rem"
  xl: "2rem"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.surface}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "0.625rem 1rem"
  button-primary-hover:
    backgroundColor: "{colors.primary-mid}"
    textColor: "{colors.surface}"
    rounded: "{rounded.control}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.primary}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "0.625rem 1rem"
  field:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "0.625rem 0.75rem"
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.surface}"
    padding: "1rem"
  status-badge:
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: "0.25rem 0.625rem"
  nav-item:
    backgroundColor: "transparent"
    textColor: "rgba(255, 255, 255, 0.7)"
    typography: "{typography.body}"
    rounded: "{rounded.surface}"
    padding: "0.75rem 1rem"
  nav-item-active:
    backgroundColor: "rgba(255, 255, 255, 0.1)"
    textColor: "{colors.surface}"
    rounded: "{rounded.surface}"
---

# Design System: PROVALE

## Overview

**Creative North Star: "El Expediente Municipal Claro"**

PROVALE se siente como un expediente público bien ordenado: institucional, sobrio y fácil de verificar. La interfaz prioriza densidad operativa, lectura rápida y jerarquías inequívocas; el azul municipal sostiene la identidad y los colores semánticos aparecen solo cuando comunican estado o riesgo.

El sistema combina superficies blancas sobre un lienzo azul muy tenue, tipografía humanista para datos y formularios, y encabezados geométricos para orientar. En Reportes PVL, esta misma lógica mantiene visibles el periodo, el estado de validación, las fuentes y la acción siguiente sin competir con los anexos oficiales ni con el informe sustentatorio.

**Key Characteristics:**

- Operativo, institucional y trazable.
- Jerarquía compacta con títulos fuertes y etiquetas breves.
- Superficies claras, bordes fríos y elevación contenida.
- Color semántico reservado para éxito, advertencia y error.
- Comportamiento responsive que conserva la tarea principal.

## Colors

La paleta usa azul municipal como única voz de marca; neutros fríos sostienen el contenido y los acentos semánticos conservan significado estable.

### Primary

- **Azul Municipal:** acción principal, navegación lateral, encabezados de tabla y texto institucional fuerte.
- **Azul Profundo:** contraste estructural en bordes y estados activos oscuros.
- **Azul Documento:** acento intermedio para títulos, iconos y trazos de orientación.
- **Azul Papel:** selección, hover y fondos informativos sin competir con el contenido.

### Secondary

- **Verde Verificado:** éxito, disponibilidad y estados listos.
- **Ámbar de Revisión:** advertencias y decisiones pendientes.
- **Rojo de Incidencia:** errores, hallazgos críticos y acciones destructivas.

### Neutral

- **Lienzo Administrativo:** fondo continuo del área de trabajo.
- **Papel Oficial:** tarjetas, formularios, tablas y modales.
- **Niebla de Separación:** bordes, divisores y estados vacíos.
- **Tinta Institucional:** contenido principal.
- **Pizarra Informativa:** metadatos, ayudas y texto secundario.

**The Semantic Restraint Rule.** Verde, ámbar y rojo solo codifican estado, riesgo o consecuencia; nunca decoran superficies completas.

## Typography

**Display Font:** Lexend (con Source Sans 3 y sans-serif como respaldo)  
**Body Font:** Source Sans 3 (con ui-sans-serif, system-ui y sans-serif como respaldo)

**Character:** Lexend aporta títulos inequívocos y contemporáneos; Source Sans 3 conserva legibilidad institucional en formularios, tablas y metadatos densos.

### Hierarchy

- **Headline:** extranegrita, compacta y reservada para el título de cada módulo.
- **Title:** extranegrita para paneles, grupos de revisión y modales.
- **Body:** tamaño compacto y altura de línea holgada para datos operativos.
- **Label:** negrita, mayúsculas y tracking amplio para campos, columnas y métricas.

**The Two-Font Rule.** Lexend orienta; Source Sans 3 permite operar. No introducir una tercera familia en la aplicación.

## Layout

El dashboard usa un shell de dos zonas: navegación lateral fija y área de trabajo flexible. La barra lateral mide 70px contraída y 240px expandida; en pantallas de hasta 768px se convierte en un panel superpuesto de 260px. El encabezado es sticky y el contenido usa relleno progresivo de 1rem, 1.5rem y 2rem.

Las pantallas operativas se componen con una superficie principal y subdivisiones por borde. Formularios y resúmenes usan grid cuando hay espacio; tablas preservan su densidad mediante desplazamiento horizontal en móvil. El ritmo base es múltiplo de 0.25rem y los grupos principales avanzan normalmente en saltos de 1rem o 1.5rem.

**The One Task Surface Rule.** Cada sección debe tener una superficie principal reconocible; las divisiones internas ordenan la tarea sin crear una colección de tarjetas anidadas.

## Elevation & Depth

La profundidad es híbrida y contenida: las superficies se separan primero por tono y borde; las sombras aparecen en el shell, modales, controles elevados y respuesta al hover. El encabezado sticky usa transparencia y desenfoque, mientras el contenido operativo permanece opaco.

### Shadow Vocabulary

- **Control reposo:** sombra corta para acciones primarias.
- **Control activo:** elevación media y desplazamiento vertical de 1px en hover.
- **Shell lateral:** sombra direccional que separa navegación y trabajo.
- **Modal:** elevación alta reservada para capas bloqueantes.

**The Flat-by-Default Rule.** Bordes y contraste tonal construyen la estructura; no añadir sombra a cada contenedor.

## Shapes

Los controles son sobrios y compactos, con esquinas de 0.375rem. Las superficies de contenido usan 0.75rem; los modales conservan 1rem y las etiquetas de estado son píldoras completas. Los bordes institucionales son fríos y visibles, con uno o dos píxeles según jerarquía.

**The Geometry by Role Rule.** El radio comunica función: compacto para acciones, medio para superficies, circular solo para estados y controles puntuales.

## Components

### Buttons

- **Shape:** control compacto con radio pequeño y altura táctil mínima de 44px en móvil.
- **Primary:** fondo azul municipal, texto blanco y peso seminegrita.
- **Hover / Focus:** elevación breve; foco visible con anillo azul o contorno ámbar sobre fondos oscuros.
- **Secondary:** papel blanco, texto y borde azul municipal; hover sobre azul papel.
- **Danger:** rojo sólido con texto blanco; uso exclusivo para acciones destructivas.

### Chips

- **Style:** píldora compacta, tipografía pequeña en negrita y pareja fondo/texto del mismo significado.
- **State:** azul para información, verde para listo, ámbar para revisión y rojo para error.

### Cards / Containers

- **Corner Style:** curva media y uniforme.
- **Background:** papel blanco sobre lienzo administrativo.
- **Shadow Strategy:** plana por defecto, con sombra leve solo donde mejora la separación.
- **Border:** uno o dos píxeles de niebla institucional.
- **Internal Padding:** 1rem en móvil y hasta 1.5rem en escritorio.

### Inputs / Fields

- **Style:** fondo blanco, borde de 2px, radio compacto y texto seminegrita.
- **Focus:** borde azul con anillo translúcido; no depender solo del cambio de color.
- **Error / Disabled:** mensaje explícito junto al campo; controles deshabilitados reducen opacidad y mantienen legibilidad.

### Navigation

La navegación vive sobre azul municipal. Los ítems usan texto blanco atenuado en reposo, superficie blanca translúcida en hover/activo y un filete azul claro en el borde izquierdo. En móvil, el panel entra desde la izquierda con overlay y conserva etiquetas completas.

### Data Tables

Los encabezados usan fondo azul municipal, texto blanco, etiquetas compactas en mayúsculas y borde inferior profundo. Las filas se separan con niebla, muestran hover azul papel y mantienen números tabulares cuando importa comparar cantidades.

## Do's and Don'ts

### Do:

- **Do** reutilizar los tokens semánticos y componentes compartidos antes de sumar variantes locales.
- **Do** mantener periodo, estado, trazabilidad y acción principal visibles en flujos documentales.
- **Do** usar bordes y jerarquía tipográfica como estructura primaria.
- **Do** conservar foco visible, etiquetas explícitas y objetivos táctiles de 44px en móvil.

### Don't:

- **Don't** usar color semántico como decoración o cambiar su significado entre módulos.
- **Don't** introducir radios grandes en controles ni píldoras en botones de acción.
- **Don't** apilar tarjetas decorativas dentro de la superficie principal.
- **Don't** ocultar tablas críticas en móvil; permitir desplazamiento horizontal con encabezados legibles.
- **Don't** generar una identidad visual separada para Reportes PVL: pertenece al mismo dashboard institucional.
