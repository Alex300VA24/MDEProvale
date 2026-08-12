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
    const [datePart, timePart] = value.split(' ');
    const [y, m, day] = (datePart || '').split('-');
    const time = timePart || '';
    if (!y || !m || !day) return value;
    return `${day}/${m}/${y}${time ? ' ' + time.slice(0, 5) : ''}`;
}

export function vigenciaBadge(resolution) {
    const endRaw = resolution?.date_end;
    if (!endRaw) return { label: 'Sin vigencia', cls: 'bg-gray-100 text-gray-600' };
    const end = new Date(`${dateValue(endRaw)}T00:00:00`);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (Number.isNaN(end.getTime())) return { label: 'Sin vigencia', cls: 'bg-gray-100 text-gray-600' };
    if (end < today) return { label: 'Vencido', cls: 'bg-red-100 text-red-800' };
    return { label: 'Vigente', cls: 'bg-green-100 text-green-800' };
}

export function stateBadge(state) {
    if (!state) return { label: 'Sin estado', cls: 'badge-inactive' };
    const active = state.abbreviation === 'A' || state.title === 'Activo';
    return { label: state.title || 'Sin estado', cls: active ? 'badge-active' : 'badge-inactive' };
}

export function datetimeInputValue(dt) {
    if (!dt) return '';
    return String(dt).replace(' ', 'T').slice(0, 16);
}

export function datetimeToSubmit(value) {
    if (!value) return '';
    return String(value).replace('T', ' ');
}
