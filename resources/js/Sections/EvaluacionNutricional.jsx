import { useEffect, useState } from 'react';
import http from '../http';
import { useToast } from '../Components/Toast';
import Combobox from '../Components/Combobox';
import PdfLinkButton from '../Components/PdfLinkButton';
import errorMessage from '../errorMessage';

const BASE = '/api/dashboard/evaluacion-nutricional';

const th = 'px-3 py-2 text-left text-xs font-bold text-slate-600 whitespace-nowrap bg-mist/60';
const td = 'px-3 py-2 text-xs sm:text-sm text-charcoal border-t border-mist';

export default function EvaluacionNutricional() {
    const toast = useToast();
    const [associations, setAssociations] = useState([]);
    const [associationId, setAssociationId] = useState('');
    const [rows, setRows] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        http
            .get(`${BASE}/options`)
            .then(({ data }) => setAssociations(data.associations ?? []))
            .catch((err) => toast.error(errorMessage(err, 'No se pudieron cargar los comités.')));
    }, []);

    useEffect(() => {
        if (!associationId) {
            setRows([]);
            return;
        }
        setLoading(true);
        http
            .get(`${BASE}/${associationId}/beneficiarios`)
            .then(({ data }) => setRows(data.rows ?? []))
            .catch((err) => toast.error(errorMessage(err, 'No se pudieron cargar los beneficiarios.')))
            .finally(() => setLoading(false));
    }, [associationId]);

    const options = associations.map((a) => ({ id: a.id, label: `${a.code} - ${a.name}` }));

    return (
        <section className="bg-white rounded-2xl border-2 border-mist shadow-sm overflow-hidden">
            <header className="px-4 py-4 sm:px-6 sm:py-5">
                <h1 className="flex items-center gap-3 text-xl font-extrabold text-navy sm:text-2xl">
                    <i className="fas fa-heart-pulse text-teal" aria-hidden="true" />
                    Evaluación Nutricional
                </h1>
                <p className="text-xs sm:text-sm text-earth mt-1">
                    Elige un comité para generar el acta con los datos de la ficha de beneficiarios.
                </p>
            </header>

            <div className="flex flex-col sm:flex-row sm:items-center gap-3 px-4 sm:px-6 py-3 border-y-2 border-mist">
                <div className="w-full sm:w-96">
                    <Combobox
                        value={associationId}
                        onChange={setAssociationId}
                        options={options}
                        placeholder="Buscar comité por código o nombre"
                        minQuery={0}
                        allowClear
                    />
                </div>
                <PdfLinkButton
                    href={`${window.APP_URL || ''}/socios-beneficiarios/evaluacion-nutricional/${associationId}/acta`}
                    loadingTitle="Generando Acta de Evaluación Nutricional"
                    icon="fa-file-signature"
                    disabled={!associationId}
                    className="btn-primary flex items-center justify-center gap-2 text-xs sm:text-sm disabled:opacity-60"
                >
                    Generar acta
                </PdfLinkButton>
            </div>

            <div className="p-4 sm:p-6">
                {!associationId ? (
                    <p className="text-sm text-earth py-10 text-center">Selecciona un comité para ver sus beneficiarios.</p>
                ) : loading ? (
                    <p className="text-sm text-earth py-10 text-center">
                        <i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />Cargando beneficiarios…
                    </p>
                ) : rows.length === 0 ? (
                    <p className="text-sm text-earth py-10 text-center">Este comité no tiene beneficiarios registrados.</p>
                ) : (
                    <div className="overflow-x-auto border-2 border-mist rounded-xl">
                        <table className="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th className={th}>N°</th>
                                    <th className={th}>Apellidos y nombres</th>
                                    <th className={th}>DNI</th>
                                    <th className={th}>Sexo</th>
                                    <th className={th}>Edad</th>
                                    <th className={th}>Parentesco</th>
                                    <th className={th}>Peso (kg)</th>
                                    <th className={th}>Talla (cm)</th>
                                    <th className={th}>Condición</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((r) => (
                                    <tr key={r.numero}>
                                        <td className={td}>{r.numero}</td>
                                        <td className={`${td} font-semibold`}>{r.name}</td>
                                        <td className={td}>{r.dni}</td>
                                        <td className={td}>{r.gender}</td>
                                        <td className={td}>{r.age}</td>
                                        <td className={td}>{r.relationship}</td>
                                        <td className={td}>{r.weight ?? '—'}</td>
                                        <td className={td}>{r.height ?? '—'}</td>
                                        <td className={td}>
                                            {[r.population_group === 'ADULTO_MAYOR' && 'Adulto Mayor (>65 años)', r.is_malnourished && 'Desnutrido', r.is_disabled && 'Discapacitado'].filter(Boolean).join(', ') || '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </section>
    );
}
