import { useLayoutEffect, useState } from 'react';

// Pistas de formulario comunes a todos los modales del sistema:
//  1. Detecta si el formulario tiene campos obligatorios (etiquetas con "*" o
//     controles "required") para mostrar la leyenda "(*) Campo obligatorio".
//  2. Completa un placeholder en los campos de texto que no definen uno propio,
//     a partir del texto de su etiqueta. Los placeholders explícitos se respetan.

const TEXT_INPUT_TYPES = new Set(['text', 'email', 'tel', 'number', 'search', 'url', 'password']);
const MAX_LABEL_LEVELS = 4;

function cleanLabel(text) {
    return String(text || '')
        .replace(/\(\s*opcional\s*\)/gi, '')
        .replace(/\([^)]*\)/g, '')
        .replace(/\*/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();
}

function labelTextFor(field) {
    if (field.id) {
        const explicit = field.ownerDocument.querySelector(`label[for="${CSS.escape(field.id)}"]`);
        if (explicit) return explicit.textContent;
    }
    let node = field.parentElement;
    for (let level = 0; node && level < MAX_LABEL_LEVELS; level += 1, node = node.parentElement) {
        const label = Array.from(node.children).find((child) => child.tagName === 'LABEL');
        if (label) return label.textContent;
        if (node.querySelectorAll('input, textarea, select').length > 1) break;
    }
    return '';
}

function applyPlaceholders(root) {
    root.querySelectorAll('input, textarea').forEach((field) => {
        if (field.getAttribute('placeholder')) return;
        const isTextarea = field.tagName === 'TEXTAREA';
        if (!isTextarea && !TEXT_INPUT_TYPES.has((field.getAttribute('type') || 'text').toLowerCase())) return;
        // Los campos de búsqueda de los combobox ya traen su propio placeholder.
        if (field.getAttribute('role') === 'combobox') return;
        const label = cleanLabel(labelTextFor(field));
        if (!label) return;
        field.setAttribute('placeholder', field.type === 'number' ? `Ej.: ${label}` : `Ingrese ${label}`);
    });
}

function hasRequiredFields(root) {
    if (root.querySelector('input[required], select[required], textarea[required], [aria-required="true"]')) return true;
    return Array.from(root.querySelectorAll('label, legend')).some((element) => element.textContent.includes('*'));
}

export default function useModalFormHints(dialogRef, enabled) {
    const [hasRequired, setHasRequired] = useState(false);

    useLayoutEffect(() => {
        const root = dialogRef.current;
        if (!enabled || !root) return undefined;

        let scheduled = false;
        const refresh = () => {
            scheduled = false;
            applyPlaceholders(root);
            setHasRequired(hasRequiredFields(root));
        };
        refresh();

        const observer = new MutationObserver(() => {
            if (scheduled) return;
            scheduled = true;
            window.requestAnimationFrame(refresh);
        });
        observer.observe(root, { childList: true, subtree: true });

        return () => observer.disconnect();
    }, [dialogRef, enabled]);

    return hasRequired;
}
