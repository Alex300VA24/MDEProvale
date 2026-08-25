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

export function stateBadge(state) {
    if (!state) return { label: 'Sin estado', cls: 'badge-unknown' };

    const classes = {
        ACT: 'badge-active',
        VIG: 'badge-current',
        PEN: 'badge-pending',
        INA: 'badge-inactive',
        VEN: 'badge-expired',
    };

    return { label: state.title || 'Sin estado', cls: classes[state.abbreviation] || 'badge-unknown' };
}

export function datetimeInputValue(dt) {
    if (!dt) return '';
    return String(dt).replace(' ', 'T').slice(0, 16);
}

export function datetimeToSubmit(value) {
    if (!value) return '';
    return String(value).replace('T', ' ');
}
