export function dateValue(d) {
    if (!d) return '';
    return String(d).split('T')[0].split(' ')[0];
}

export function fmtDate(d) {
    if (!d) return '';
    const value = dateValue(d);
    if (!value) return '';
    const [y, m, day] = value.split('-');
    if (!y || !m || !day) return value;
    return `${day}/${m}/${y}`;
}

export function fmtDateTime(dt) {
    if (!dt) return '';
    const value = String(dt).replace('T', ' ');
    const [datePart] = value.split(' ');
    const [y, m, day] = (datePart || '').split('-');
    if (!y || !m || !day) return value;
    return `${day}/${m}/${y}`;
}

const STATE_CLASS_BY_ABBR = {
    ACT: 'badge-active',
    VIG: 'badge-current',
    PEN: 'badge-pending',
    INA: 'badge-inactive',
    VEN: 'badge-expired',
};

const STATE_CLASS_BY_TITLE = {
    activo: 'badge-active',
    vigente: 'badge-current',
    pendiente: 'badge-pending',
    inactivo: 'badge-inactive',
    vencido: 'badge-expired',
};

// Resuelve la clase de la etiqueta de estado de forma uniforme en todo el
// sistema: primero por abreviatura (ACT/VIG/...), y si viene ausente o con un
// valor inesperado (p. ej. CHAR con espacios) cae al titulo normalizado.
export function stateClass(state) {
    if (!state) return 'badge-unknown';
    const abbr = String(state.abbreviation || '').trim().toUpperCase();
    if (STATE_CLASS_BY_ABBR[abbr]) return STATE_CLASS_BY_ABBR[abbr];
    const title = String(state.title || '').trim().toLowerCase();
    return STATE_CLASS_BY_TITLE[title] || 'badge-unknown';
}

export function stateBadge(state) {
    if (!state) return { label: 'Sin estado', cls: 'badge-unknown' };
    return { label: state.title || 'Sin estado', cls: stateClass(state) };
}

export function datetimeInputValue(dt) {
    if (!dt) return '';
    return String(dt).replace(' ', 'T').slice(0, 16);
}

export function datetimeToSubmit(value) {
    if (!value) return '';
    return String(value).replace('T', ' ');
}
