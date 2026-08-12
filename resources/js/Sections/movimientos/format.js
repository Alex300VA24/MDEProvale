export { fmtDate, dateValue, money, periodLabel, detailOptionLabel } from '../productos/format';

export function typeBadgeClass(title) {
    return String(title).toLowerCase() === 'ingreso'
        ? 'bg-leaf-light text-leaf'
        : 'bg-clay-light text-clay';
}
