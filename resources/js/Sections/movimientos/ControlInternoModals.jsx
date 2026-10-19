import { useEffect, useMemo, useRef, useState } from 'react';
import http from '../../http';
import Modal from '../../Components/Modal';
import PdfLinkButton from '../../Components/PdfLinkButton';
import { useToast } from '../../Components/Toast';
import errorMessage from '../../errorMessage';

const MONTHS = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];

const labelCls = 'block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
const cellCls =
    'w-full min-h-10 px-2 py-1.5 border border-mist rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20';

const PRODUCTS = {
    milk: { label: 'Leche evaporada', icon: 'fa-bottle-water', unit: 'TARROS' },
    oat: { label: 'Hojuela de quinua avena', icon: 'fa-wheat-awn', unit: 'KG' },
};

const CHECKS = {
    milk: [
        'Verificó el contrato del producto',
        'Conformidad microbiológica',
        'Conformidad organoléptica',
        'Conformidad físico-química',
        'Conformidad micronutrientes',
        'Conformidad de esterilidad',
        'Conformidad de calidad',
        'Declaración jurada de micronutrientes',
        'Declaración jurada de insumos nacionales',
    ],
    oat: [
        'Verificó el contrato del producto',
        'Conformidad microbiológica',
        'Conformidad organoléptica',
        'Conformidad físico-química',
        'Conformidad micronutrientes',
        'Conformidad de calidad',
        'Declaración jurada de micronutrientes',
        'Declaración jurada de insumos nacionales',
    ],
};

const EXTRAS = [
    'Almacén de la MDE entregó PECOSA',
    'Entrega de guía o factura del producto',
    'Personal (estibador) del proveedor cumplieron los protocolos anti COVID-19',
];

const todayIso = () => new Date().toISOString().split('T')[0];
const isoToDisplay = (iso) => (iso ? iso.split('-').reverse().join('/') : '');
const monthOf = (iso) => (iso ? MONTHS[Number(iso.split('-')[1]) - 1] ?? '' : '');
const pdfUrl = (path) => `${window.APP_URL}/${path}`;
const pdfBody = (product, payload) => ({ product, payload: JSON.stringify(payload) });

function YesNo({ name, label, value, onChange }) {
    const option = (key, text) => (
        <label className={`inline-flex min-h-9 min-w-12 cursor-pointer items-center justify-center rounded-lg border-2 px-3 text-xs font-extrabold transition-colors ${value === key ? 'border-leaf bg-leaf text-white' : 'border-wheat bg-white text-slate-600 hover:border-leaf'}`}>
            <input
                type="radio"
                name={name}
                checked={value === key}
                onChange={() => onChange(key)}
                onClick={() => value === key && onChange(null)}
                className="sr-only"
            />
            {text}
        </label>
    );
    return (
        <div role="radiogroup" aria-label={label} className="flex items-center justify-between gap-3 border-b border-mist py-2">
            <span className="text-sm font-semibold text-charcoal">{label}</span>
            <div className="flex shrink-0 gap-2">{option('si', 'Sí')}{option('no', 'No')}</div>
        </div>
    );
}

export function FichaControlInternoModal({ onClose }) {
    const toast = useToast();
    const [product, setProduct] = useState('milk');
    const [form, setForm] = useState({
        folio_number: '',
        date: todayIso(),
        time: '',
        observations: '',
        expiry: '',
        quantity: '',
        unit: PRODUCTS.milk.unit,
        lots: '',
        weighing_detail: '',
        observations_end: '',
    });
    const [answers, setAnswers] = useState({});
    const [weighing, setWeighing] = useState(null);

    const set = (field, value) => setForm((current) => ({ ...current, [field]: value }));
    const answer = (key, value) => setAnswers((current) => ({ ...current, [key]: value }));
    const checks = CHECKS[product];

    // Cambiar de producto trae el último ingreso del mes para no tipear lo ya registrado.
    useEffect(() => {
        let active = true;
        const now = new Date();
        set('unit', PRODUCTS[product].unit);
        http.get('/api/dashboard/movimientos/tarjeta-control', { params: { product, year: now.getFullYear(), month: now.getMonth() + 1 } })
            .then(({ data }) => {
                const last = data.ingresos?.[data.ingresos.length - 1];
                if (!active || !last) return;
                setForm((current) => ({
                    ...current,
                    date: last.date || current.date,
                    time: last.time || current.time,
                    quantity: String(last.quantity ?? current.quantity),
                    expiry: last.expiry || current.expiry,
                }));
            })
            .catch(() => {});
        return () => { active = false; };
    }, [product]);

    const payload = useMemo(() => ({
        folio_month: monthOf(form.date),
        folio_number: form.folio_number,
        date: isoToDisplay(form.date),
        time: form.time,
        observations: form.observations,
        expiry: isoToDisplay(form.expiry),
        quantity: form.quantity,
        unit: form.unit,
        lots: form.lots,
        weighing_detail: form.weighing_detail,
        observations_end: form.observations_end,
        weighing,
        items: checks.map((label) => ({ label, value: answers[`${product}:${label}`] ?? null })),
        extras: EXTRAS.map((label) => ({ label, value: answers[label] ?? null })),
    }), [form, weighing, answers, checks, product]);

    const productInfo = PRODUCTS[product];

    return (
        <Modal open onClose={onClose} title="Ficha de Control Interno" icon="fa-clipboard-check" iconClass="text-navy" maxWidth="sm:max-w-4xl">
            <div className="p-4 sm:p-6">
                <div className="mb-6 rounded-lg border border-mist bg-blue-light/40 p-4">
                    <label className={labelCls} htmlFor="control-product-type">Producto a controlar *</label>
                    <select id="control-product-type" value={product} onChange={(event) => setProduct(event.target.value)} className={inputCls}>
                        <option value="milk">Leche</option>
                        <option value="oat">Hojuela</option>
                    </select>
                    <p className="mt-2 text-xs text-earth">Se completan los datos del último ingreso del mes. Revíselos antes de generar la ficha.</p>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <fieldset className="space-y-4">
                        <legend className="mb-3 flex items-center gap-2 text-sm font-extrabold text-navy">
                            <i className="fas fa-file-lines" aria-hidden="true" />Datos del ingreso
                        </legend>
                        <div>
                            <label className={labelCls} htmlFor="ficha-folio">N° de ficha</label>
                            <input id="ficha-folio" type="text" value={form.folio_number} onChange={(event) => set('folio_number', event.target.value)} className={inputCls} placeholder="Ej. 291" />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className={labelCls} htmlFor="ficha-date">Fecha de ingreso *</label>
                                <input id="ficha-date" type="date" value={form.date} onChange={(event) => set('date', event.target.value)} className={inputCls} required />
                            </div>
                            <div>
                                <label className={labelCls} htmlFor="ficha-time">Hora</label>
                                <input id="ficha-time" type="time" value={form.time} onChange={(event) => set('time', event.target.value)} className={inputCls} />
                            </div>
                        </div>
                        <div>
                            <label className={labelCls} htmlFor="ficha-expiry">Fecha de vencimiento</label>
                            <input id="ficha-expiry" type="date" value={form.expiry} onChange={(event) => set('expiry', event.target.value)} className={inputCls} />
                        </div>
                        <div className="grid grid-cols-[1fr_8rem] gap-3">
                            <div>
                                <label className={labelCls} htmlFor="ficha-quantity">Cantidad recepcionada</label>
                                <input id="ficha-quantity" type="number" min="0" step="0.01" value={form.quantity} onChange={(event) => set('quantity', event.target.value)} className={inputCls} />
                            </div>
                            <div>
                                <label className={labelCls} htmlFor="ficha-unit">Unidad</label>
                                <input id="ficha-unit" type="text" value={form.unit} onChange={(event) => set('unit', event.target.value.toUpperCase())} className={inputCls} />
                            </div>
                        </div>
                        <div>
                            <label className={labelCls} htmlFor="ficha-lots">Número de lote(s)</label>
                            <input id="ficha-lots" type="text" value={form.lots} onChange={(event) => set('lots', event.target.value)} className={inputCls} placeholder="Ej. 12623, 22623" />
                        </div>
                        <div>
                            <label className={labelCls} htmlFor="ficha-obs">Observaciones (conformidades)</label>
                            <textarea id="ficha-obs" rows={3} value={form.observations} onChange={(event) => set('observations', event.target.value)} className={`${inputCls} resize-y`} />
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend className="mb-1 flex items-center gap-2 text-sm font-extrabold text-navy">
                            <i className={`fas ${productInfo.icon}`} aria-hidden="true" />Conformidades de {productInfo.label.toLowerCase()}
                        </legend>
                        {checks.map((label) => (
                            <YesNo key={`${product}:${label}`} name={`${product}:${label}`} label={label} value={answers[`${product}:${label}`] ?? null} onChange={(value) => answer(`${product}:${label}`, value)} />
                        ))}
                    </fieldset>
                </div>

                <fieldset className="mt-6 grid grid-cols-1 gap-6 border-t border-mist pt-5 lg:grid-cols-2">
                    <legend className="mb-3 flex items-center gap-2 text-sm font-extrabold text-navy">
                        <i className="fas fa-scale-balanced" aria-hidden="true" />Pesaje y entrega de documentos
                    </legend>
                    <div className="space-y-4">
                        <div>
                            <label className={labelCls} htmlFor="ficha-weighing-detail">Pesaje aleatorio: detalle</label>
                            <input id="ficha-weighing-detail" type="text" value={form.weighing_detail} onChange={(event) => set('weighing_detail', event.target.value)} className={inputCls} placeholder="Ej. 1 caja tomada" />
                        </div>
                        <YesNo name="weighing" label="Pesaje aleatorio del producto adecuado" value={weighing} onChange={setWeighing} />
                        <div>
                            <label className={labelCls} htmlFor="ficha-obs-end">Observaciones (pesaje)</label>
                            <textarea id="ficha-obs-end" rows={3} value={form.observations_end} onChange={(event) => set('observations_end', event.target.value)} className={`${inputCls} resize-y`} />
                        </div>
                    </div>
                    <div>
                        {EXTRAS.map((label) => (
                            <YesNo key={label} name={label} label={label} value={answers[label] ?? null} onChange={(value) => answer(label, value)} />
                        ))}
                    </div>
                </fieldset>

                <div className="mt-6 flex flex-col-reverse gap-3 border-t border-mist pt-5 sm:flex-row sm:justify-end">
                    <button type="button" onClick={onClose} className="btn-secondary sm:min-w-32">Cerrar</button>
                    <PdfLinkButton
                        href={pdfUrl('movimientos-ficha-control')}
                        postData={pdfBody(product, payload)}
                        loadingTitle="Generando Ficha de Control Interno"
                        icon="fa-print"
                        className="btn-primary inline-flex min-h-11 items-center justify-center gap-2 sm:min-w-52"
                    >
                        Generar ficha PDF
                    </PdfLinkButton>
                </div>
            </div>
        </Modal>
    );
}

/** Select con escritura libre: la lista completa se muestra al abrir y se filtra solo al teclear. */
function ComboBox({ value, options, onChange, label, placeholder }) {
    const [open, setOpen] = useState(false);
    const [filter, setFilter] = useState('');
    const [rect, setRect] = useState(null);
    const wrapRef = useRef(null);

    const show = () => {
        setRect(wrapRef.current.getBoundingClientRect());
        setFilter('');
        setOpen(true);
    };

    useEffect(() => {
        if (!open) return undefined;
        const close = (event) => { if (!wrapRef.current?.contains(event.target) && !event.target.closest?.('[data-combo-list]')) setOpen(false); };
        const reposition = () => setOpen(false);
        document.addEventListener('mousedown', close);
        window.addEventListener('scroll', reposition, true);
        window.addEventListener('resize', reposition);
        return () => {
            document.removeEventListener('mousedown', close);
            window.removeEventListener('scroll', reposition, true);
            window.removeEventListener('resize', reposition);
        };
    }, [open]);

    const visible = options.filter((name) => name.toLowerCase().includes(filter.toLowerCase()));

    return (
        <div ref={wrapRef} className="relative">
            <input
                type="text"
                role="combobox"
                aria-label={label}
                aria-expanded={open}
                aria-autocomplete="list"
                autoComplete="off"
                placeholder={placeholder}
                value={value}
                onFocus={show}
                onChange={(event) => { onChange(event.target.value); if (!open) show(); setFilter(event.target.value); }}
                onKeyDown={(event) => { if (event.key === 'Escape') setOpen(false); }}
                className={`${cellCls} pr-8`}
            />
            <button type="button" tabIndex={-1} aria-label="Ver responsables" onMouseDown={(event) => event.preventDefault()} onClick={() => (open ? setOpen(false) : show())} className="absolute inset-y-0 right-0 flex w-8 items-center justify-center text-earth">
                <i className={`fas fa-chevron-${open ? 'up' : 'down'} text-xs`} aria-hidden="true" />
            </button>
            {open && rect && (
                <ul data-combo-list role="listbox" style={{ position: 'fixed', top: rect.bottom + 2, left: rect.left, width: Math.max(rect.width, 220), zIndex: 9999 }} className="max-h-56 overflow-y-auto rounded-lg border border-mist bg-white py-1 shadow-lg">
                    {visible.map((name) => (
                        <li key={name} role="option" aria-selected={name === value} onMouseDown={(event) => { event.preventDefault(); onChange(name); setOpen(false); }} className={`cursor-pointer px-3 py-2 text-sm font-semibold hover:bg-blue-light/60 ${name === value ? 'text-leaf' : 'text-charcoal'}`}>{name}</li>
                    ))}
                    {visible.length === 0 && <li className="px-3 py-2 text-xs text-earth">Sin coincidencias. Se usará el texto escrito.</li>}
                </ul>
            )}
        </div>
    );
}

const emptyRow = () => ({ date: '', time: '', in: '', out: '', by: '' });
const num = (value) => {
    const parsed = parseFloat(String(value).replace(/,/g, ''));
    return Number.isNaN(parsed) ? 0 : parsed;
};
const fmt = (value) => value.toLocaleString('es-PE', { maximumFractionDigits: 2 });

export function TarjetaControlModal({ product, year, month, onClose }) {
    const toast = useToast();
    const [loading, setLoading] = useState(true);
    const [folio, setFolio] = useState('');
    const [monthName, setMonthName] = useState(MONTHS[month - 1] ?? '');
    const [rows, setRows] = useState([]);
    const [responsables, setResponsables] = useState([]);
    const info = PRODUCTS[product];

    useEffect(() => {
        let active = true;
        setLoading(true);
        http.get('/api/dashboard/movimientos/tarjeta-control', { params: { product, year, month } })
            .then(({ data }) => {
                if (!active) return;
                setMonthName(data.month);
                setResponsables(data.responsables ?? []);
                setRows(data.rows.map((row) => ({
                    date: row.date ?? '',
                    time: row.time ?? '',
                    in: row.in ?? '',
                    out: row.out ?? '',
                    by: row.by ?? '',
                })));
            })
            .catch((error) => toast.error(errorMessage(error, 'No se pudieron cargar los movimientos del mes.')))
            .finally(() => active && setLoading(false));
        return () => { active = false; };
    }, [product, year, month]);

    const changeRow = (index, field, value) =>
        setRows((current) => current.map((row, i) => (i === index ? { ...row, [field]: value } : row)));

    const computed = useMemo(() => {
        let balance = 0;
        let totalIn = 0;
        let totalOut = 0;
        const lines = rows.map((row) => {
            const blank = row.in === '' && row.out === '';
            if (!blank) {
                balance += num(row.in) - num(row.out);
                totalIn += num(row.in);
                totalOut += num(row.out);
            }
            return { balance: blank || row.out === '' ? null : balance };
        });
        return { lines, totalIn, totalOut, balance };
    }, [rows]);

    const payload = useMemo(() => ({
        folio,
        month: monthName,
        rows: rows.map((row) => ({ ...row })),
    }), [folio, monthName, rows]);

    const cellLabel = (field, index) => `${field} fila ${index + 1}`;

    return (
        <Modal open onClose={onClose} title={`Tarjeta de Control · ${info.label}`} icon="fa-table-list" iconClass="text-navy" maxWidth="sm:max-w-5xl">
            <div className="p-4 sm:p-6" aria-busy={loading}>
                <div className="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-[8rem_1fr_12rem]">
                    <div>
                        <label className={labelCls} htmlFor="tarjeta-folio">N° de tarjeta</label>
                        <input id="tarjeta-folio" type="text" value={folio} onChange={(event) => setFolio(event.target.value)} className={inputCls} placeholder="Ej. 291" />
                    </div>
                    <div>
                        <span className={labelCls}>Producto</span>
                        <p className="min-h-11 rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-semibold text-charcoal">{info.label}</p>
                    </div>
                    <div>
                        <label className={labelCls} htmlFor="tarjeta-mes">Mes</label>
                        <input id="tarjeta-mes" type="text" value={monthName} onChange={(event) => setMonthName(event.target.value)} className={inputCls} />
                    </div>
                </div>

                <p className="mb-3 text-xs text-earth">
                    Ingresos y salidas vienen de lo registrado en el mes. Hay una salida por vuelta de reparto, en dos jornadas: la primera mitad de las vueltas en la fecha de la primera PECOSA y la segunda al día siguiente. Corrija cualquier valor antes de generar el PDF.
                </p>

                {loading ? (
                    <div role="status" className="py-10 text-center text-sm font-semibold text-earth"><i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />Cargando movimientos…</div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-mist">
                        <table className="data-table w-full min-w-[760px] text-sm">
                            <thead>
                                <tr>
                                    <th className="px-2 py-2 text-left">Fecha</th>
                                    <th className="px-2 py-2 text-left">Hora</th>
                                    <th className="px-2 py-2 text-right">Ingreso</th>
                                    <th className="px-2 py-2 text-right">Salida</th>
                                    <th className="px-2 py-2 text-right">Saldo</th>
                                    <th className="px-2 py-2 text-left">Responsable</th>
                                    <th className="px-2 py-2"><span className="sr-only">Quitar</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row, index) => {
                                    const balance = computed.lines[index].balance;
                                    return (
                                        <tr key={index}>
                                            <td className="px-2 py-1.5 w-40"><input aria-label={cellLabel('Fecha', index)} type="date" value={row.date} onChange={(event) => changeRow(index, 'date', event.target.value)} className={cellCls} /></td>
                                            <td className="px-2 py-1.5 w-28"><input aria-label={cellLabel('Hora', index)} type="time" value={row.time} onChange={(event) => changeRow(index, 'time', event.target.value)} className={cellCls} /></td>
                                            <td className="px-2 py-1.5 w-28"><input aria-label={cellLabel('Ingreso', index)} type="number" step="0.01" value={row.in} onChange={(event) => changeRow(index, 'in', event.target.value)} className={`${cellCls} text-right`} /></td>
                                            <td className="px-2 py-1.5 w-28"><input aria-label={cellLabel('Salida', index)} type="number" step="0.01" value={row.out} onChange={(event) => changeRow(index, 'out', event.target.value)} className={`${cellCls} text-right`} /></td>
                                            <td className={`px-2 py-1.5 w-28 text-right font-extrabold tabular-nums ${balance !== null && balance < 0 ? 'text-red-600' : 'text-charcoal'}`}>{balance === null ? '' : balance === 0 ? '—' : fmt(balance)}</td>
                                            <td className="px-2 py-1.5"><ComboBox label={cellLabel('Responsable', index)} placeholder="Seleccione o escriba" options={responsables} value={row.by} onChange={(name) => changeRow(index, 'by', name)} /></td>
                                            <td className="px-2 py-1.5 w-12 text-center"><button type="button" aria-label={`Quitar fila ${index + 1}`} onClick={() => setRows((current) => current.filter((_, i) => i !== index))} className="min-h-10 min-w-10 rounded-lg text-red-600 hover:bg-red-50"><i className="fas fa-trash" aria-hidden="true" /></button></td>
                                        </tr>
                                    );
                                })}
                                {rows.length === 0 && <tr><td colSpan={7} className="px-3 py-8 text-center text-earth">No hay ingresos ni vueltas de {info.label.toLowerCase()} en este mes. Agregue filas para llenar la tarjeta a mano.</td></tr>}
                            </tbody>
                        </table>
                    </div>
                )}

                <div className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" onClick={() => setRows((current) => [...current, emptyRow()])} className="btn-secondary min-h-11"><i className="fas fa-plus mr-2" aria-hidden="true" />Agregar fila</button>
                    <p className="text-sm text-earth tabular-nums">
                        Total ingreso <strong className="text-charcoal">{fmt(computed.totalIn)}</strong> · Total salida <strong className="text-charcoal">{fmt(computed.totalOut)}</strong> · Saldo final <strong className="text-charcoal">{fmt(computed.balance)}</strong>
                    </p>
                </div>

                <div className="mt-6 flex flex-col-reverse gap-3 border-t border-mist pt-5 sm:flex-row sm:justify-end">
                    <button type="button" onClick={onClose} className="btn-secondary sm:min-w-32">Cerrar</button>
                    <PdfLinkButton
                        href={pdfUrl('movimientos-tarjeta-control')}
                        postData={pdfBody(product, payload)}
                        disabled={loading}
                        loadingTitle="Generando Tarjeta de Control"
                        icon="fa-print"
                        className="btn-primary inline-flex min-h-11 items-center justify-center gap-2 sm:min-w-52 disabled:opacity-40"
                    >
                        Generar tarjeta PDF
                    </PdfLinkButton>
                </div>
            </div>
        </Modal>
    );
}
