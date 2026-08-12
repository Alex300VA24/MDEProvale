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

export function periodLabel(dp) {
    return dp ? `${fmtDate(dp.start_date)} al ${fmtDate(dp.end_date)}` : '';
}

export function detailOptionLabel(dp) {
    if (!dp) return '';
    const name = dp.product_title || 'Sin nombre';
    const abbr = dp.product_abbreviation ? ` (${dp.product_abbreviation})` : '';
    return `${name}${abbr} - Stock: ${Number(dp.available_stock || 0)} (${periodLabel(dp)})`;
}
