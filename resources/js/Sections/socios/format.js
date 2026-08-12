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
