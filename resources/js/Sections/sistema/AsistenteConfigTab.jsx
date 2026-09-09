import { useEffect, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/sistema';

const labelCls = 'block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all disabled:opacity-60';

const DEFAULT_LIMITES = { max_consultas: [1, 100], ventana_horas: [1, 72] };

export default function AsistenteConfigTab({ can }) {
    const toast = useToast();
    const [maxConsultas, setMaxConsultas] = useState(5);
    const [ventanaHoras, setVentanaHoras] = useState(3);
    const [limites, setLimites] = useState(DEFAULT_LIMITES);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let activo = true;
        http.get(`${BASE}/asistente-config`)
            .then(({ data }) => {
                if (!activo) return;
                setMaxConsultas(data.data.max_consultas);
                setVentanaHoras(data.data.ventana_horas);
                setLimites(data.data.limites ?? DEFAULT_LIMITES);
            })
            .catch((err) => toast.error(errorMessage(err, 'No se pudo cargar la configuración del asistente.')))
            .finally(() => activo && setLoading(false));
        return () => {
            activo = false;
        };
    }, [toast]);

    const guardar = async (event) => {
        event.preventDefault();
        if (!can?.edit) return;
        setSaving(true);
        try {
            const { data } = await http.put(`${BASE}/asistente-config`, {
                max_consultas: Number(maxConsultas),
                ventana_horas: Number(ventanaHoras),
            });
            setMaxConsultas(data.data.max_consultas);
            setVentanaHoras(data.data.ventana_horas);
            toast.success(data.message || 'Configuración actualizada.');
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo guardar la configuración.'));
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="text-earth text-sm py-8 text-center">
                <i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" /> Cargando configuración...
            </div>
        );
    }

    const [minMax, maxMax] = limites.max_consultas ?? DEFAULT_LIMITES.max_consultas;
    const [minVent, maxVent] = limites.ventana_horas ?? DEFAULT_LIMITES.ventana_horas;

    return (
        <div className="max-w-xl">
            <div className="bg-blue-light/40 border-2 border-blue/20 rounded-2xl px-4 py-3 mb-6 flex items-start gap-3">
                <i className="fas fa-circle-info text-blue mt-0.5" aria-hidden="true" />
                <p className="text-earth text-sm">
                    Define cuántas consultas puede hacer cada usuario al asistente IA (Consultas IA y widget flotante)
                    y cada cuántas horas se reinicia ese contador. El límite se aplica por usuario.
                </p>
            </div>

            <form onSubmit={guardar} className="space-y-5">
                <div>
                    <label className={labelCls} htmlFor="asistente-max">
                        Consultas por ventana
                    </label>
                    <input
                        id="asistente-max"
                        type="number"
                        min={minMax}
                        max={maxMax}
                        value={maxConsultas}
                        onChange={(event) => setMaxConsultas(event.target.value)}
                        className={inputCls}
                        disabled={!can?.edit || saving}
                        required
                    />
                    <p className="text-earth text-xs mt-1">Entre {minMax} y {maxMax} consultas.</p>
                </div>

                <div>
                    <label className={labelCls} htmlFor="asistente-ventana">
                        Ventana de tiempo (horas)
                    </label>
                    <input
                        id="asistente-ventana"
                        type="number"
                        min={minVent}
                        max={maxVent}
                        value={ventanaHoras}
                        onChange={(event) => setVentanaHoras(event.target.value)}
                        className={inputCls}
                        disabled={!can?.edit || saving}
                        required
                    />
                    <p className="text-earth text-xs mt-1">Entre {minVent} y {maxVent} horas.</p>
                </div>

                <div className="bg-base border-2 border-wheat rounded-xl px-4 py-3 text-sm text-charcoal font-semibold">
                    <i className="fas fa-gauge-high text-leaf mr-2" aria-hidden="true" />
                    Cada usuario podrá hacer {Number(maxConsultas) || 0} consultas cada {Number(ventanaHoras) || 0} h.
                </div>

                {can?.edit && (
                    <button type="submit" disabled={saving} className="btn-primary flex items-center gap-2 disabled:opacity-60">
                        <i className={`fas ${saving ? 'fa-spinner fa-spin' : 'fa-save'}`} aria-hidden="true" />
                        Guardar cambios
                    </button>
                )}
            </form>
        </div>
    );
}
