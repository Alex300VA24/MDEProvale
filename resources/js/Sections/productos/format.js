export function fmtDate(d) {
    if (!d) return '';
    const datePart = String(d).split('T')[0].split(' ')[0];
    const parts = datePart.split('-');
    return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : String(d);
}

export function dateValue(d) {
    if (!d) return '';
    return String(d).split('T')[0].split(' ')[0];
}

export function money(n) {
    const v = Number(n || 0);
    return v.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

export function stockInt(n) {
    const v = Math.round(Math.abs(Number(n || 0)));
    return v.toLocaleString('es-PE');
}

export function periodLabel(dp) {
    return dp ? `${fmtDate(dp.start_date)} al ${fmtDate(dp.end_date)}` : '';
}

export function detailOptionLabel(dp) {
    if (!dp) return '';
    const name = dp.product_title || 'Sin nombre';
    const abbr = dp.product_abbreviation ? ` (${dp.product_abbreviation})` : '';
    return `${name}${abbr} - Stock: ${Number(dp.available_stock || 0)} (${periodLabel(dp)})`;
}
