export function formatDate(value) {
    if (!value) return '';
    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) return value;
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    return `${dd}/${mm}/${date.getFullYear()}`;
}

export function personFullName(person) {
    if (!person) return '';
    return [person.names, person.father_lastname, person.mother_lastname].filter(Boolean).join(' ');
}

export function personLabel(person) {
    if (!person) return '';
    const name = personFullName(person);
    return person.dni ? `${name} (${person.dni})` : name;
}

// Clase de la etiqueta de estado, uniforme en todo el sistema. Resuelve por
// abreviatura y, si falta o es inesperada, por titulo normalizado.
const STATE_CLASS_BY_ABBR = { ACT: 'badge-active', VIG: 'badge-current', PEN: 'badge-pending', INA: 'badge-inactive', VEN: 'badge-expired' };
const STATE_CLASS_BY_TITLE = { activo: 'badge-active', vigente: 'badge-current', pendiente: 'badge-pending', inactivo: 'badge-inactive', vencido: 'badge-expired' };

export function stateClass(state) {
    if (!state) return 'badge-unknown';
    const abbr = String(state.abbreviation || '').trim().toUpperCase();
    if (STATE_CLASS_BY_ABBR[abbr]) return STATE_CLASS_BY_ABBR[abbr];
    const title = String(state.title || '').trim().toLowerCase();
    return STATE_CLASS_BY_TITLE[title] || 'badge-unknown';
}
