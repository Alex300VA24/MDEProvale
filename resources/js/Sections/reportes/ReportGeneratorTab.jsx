import { useEffect, useMemo, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';

const BASE = '/api/dashboard/reportes';

const selectCls =
    'w-full px-3 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

export default function ReportGeneratorTab() {
    const toast = useToast();
    const [loadingConfig, setLoadingConfig] = useState(true);
    const [configError, setConfigError] = useState(false);
    const [entidades, setEntidades] = useState({});
    const [sources, setSources] = useState({});

    const [seleccionadas, setSeleccionadas] = useState([]);
    const [columnas, setColumnas] = useState({});
    const [filtros, setFiltros] = useState({});
    const [agruparPor, setAgruparPor] = useState({});
    const [generating, setGenerating] = useState(false);

    useEffect(() => {
        let active = true;
        (async () => {
            try {
                const res = await http.get(`${BASE}/config`);
                if (!active) return;
                setEntidades(res.data?.entidades ?? {});
                setSources(res.data?.sources ?? {});
            } catch {
                if (active) setConfigError(true);
            } finally {
                if (active) setLoadingConfig(false);
            }
        })();
        return () => {
            active = false;
        };
    }, []);

    const toggleEntidad = (key) => {
        setSeleccionadas((prev) => {
            if (prev.includes(key)) {
                return prev.filter((e) => e !== key);
            }
            const def = entidades[key];
            setColumnas((c) => ({ ...c, [key]: c[key] ?? def?.default_columns ?? [] }));
            setFiltros((f) => ({ ...f, [key]: f[key] ?? {} }));
            setAgruparPor((a) => ({ ...a, [key]: a[key] ?? '' }));
            return [...prev, key];
        });
    };

    const toggleColumna = (entidad, key) => {
        setColumnas((prev) => {
            const actuales = prev[entidad] ?? [];
            const next = actuales.includes(key) ? actuales.filter((c) => c !== key) : [...actuales, key];
            return { ...prev, [entidad]: next };
        });
    };

    const setFiltro = (entidad, key, value) => {
        setFiltros((prev) => ({ ...prev, [entidad]: { ...(prev[entidad] ?? {}), [key]: value } }));
    };

    const setGrupo = (entidad, value) => {
        setAgruparPor((prev) => ({ ...prev, [entidad]: value }));
    };

    const columnasDisponibles = useMemo(() => {
        const map = {};
        seleccionadas.forEach((key) => {
            const def = entidades[key];
            map[key] = def ? Object.entries(def.columns ?? {}).map(([k, label]) => ({ key: k, label })) : [];
        });
        return map;
    }, [seleccionadas, entidades]);

    const handleGenerate = async () => {
        if (seleccionadas.length === 0) {
            toast.error('Selecciona al menos una entidad para el reporte.');
            return;
        }
        for (const key of seleccionadas) {
            if ((columnas[key] ?? []).length === 0) {
                toast.error(`Selecciona al menos una columna para "${entidades[key]?.label}".`);
                return;
            }
        }

        setGenerating(true);
        const preview = window.open('', '_blank');
        if (!preview) {
            setGenerating(false);
            toast.error('Habilita las ventanas emergentes para este sitio e inténtalo de nuevo.');
            return;
        }
        try {
            const params = new URLSearchParams();
            seleccionadas.forEach((e) => params.append('entidades[]', e));
            seleccionadas.forEach((e) => {
                (columnas[e] ?? []).forEach((c) => params.append(`columnas[${e}][]`, c));
                if (agruparPor[e]) params.set(`agrupar_por[${e}]`, agruparPor[e]);
                Object.entries(filtros[e] ?? {}).forEach(([k, v]) => {
                    if (v !== '' && v !== null && v !== undefined) params.set(`filtros[${e}][${k}]`, v);
                });
            });

            const res = await fetch(`/reportes/generar?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.message || 'No se pudo generar el reporte.');
            }
            const blob = await res.blob();
            preview.location = URL.createObjectURL(blob);
        } catch (e) {
            preview.close();
            toast.error(e.message);
        } finally {
            setGenerating(false);
        }
    };

    if (loadingConfig) {
        return (
            <div className="text-center py-10 text-earth">
                <i className="fas fa-spinner fa-spin text-2xl mb-2" />
                <p>Cargando generador de reportes...</p>
            </div>
        );
    }

    if (configError) {
        return (
            <div className="text-center py-10 text-clay">
                <i className="fas fa-exclamation-triangle text-2xl mb-2" />
                <p>No se pudo cargar la configuración del generador de reportes.</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Selección de entidades (combinables) */}
            <div>
                <label className="block text-sm font-bold text-charcoal mb-2">
                    <i className="fas fa-database mr-1" /> Entidades a incluir en el reporte
                </label>
                <p className="text-xs text-earth mb-2">
                    Puedes combinar varias entidades en un solo PDF. Cada una tendrá su propia sección y el
                    documento cerrará con una tabla resumen general.
                </p>
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    {Object.entries(entidades).map(([key, def]) => {
                        const activa = seleccionadas.includes(key);
                        return (
                            <button
                                key={key}
                                type="button"
                                onClick={() => toggleEntidad(key)}
                                className={`flex flex-col items-center justify-center gap-2 px-3 py-4 rounded-xl border-2 text-center transition-all ${
                                    activa
                                        ? 'border-leaf bg-leaf-light text-leaf'
                                        : 'border-wheat text-earth hover:border-leaf/50'
                                }`}
                            >
                                <i className={`fas ${def.icon} text-xl`} />
                                <span className="text-xs font-bold">{def.label}</span>
                                {activa && <i className="fas fa-check-circle text-leaf" />}
                            </button>
                        );
                    })}
                </div>
            </div>

            {/* Panel de configuración por cada entidad seleccionada */}
            {seleccionadas.map((key) => {
                const def = entidades[key];
                if (!def) return null;
                return (
                    <div key={key} className="border-2 border-wheat rounded-xl overflow-hidden">
                        <div className="bg-base px-4 py-3 border-b-2 border-wheat flex items-center gap-2">
                            <i className={`fas ${def.icon} text-leaf`} />
                            <span className="font-bold text-charcoal text-sm">{def.label}</span>
                        </div>
                        <div className="p-4 space-y-4">
                            {/* Columnas */}
                            <div>
                                <label className="block text-sm font-bold text-charcoal mb-2">
                                    <i className="fas fa-table-columns mr-1" /> Columnas
                                </label>
                                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 p-3 border-2 border-wheat rounded-xl bg-base">
                                    {(columnasDisponibles[key] ?? []).map((col) => (
                                        <label key={col.key} className="flex items-center gap-2 text-sm text-charcoal cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={(columnas[key] ?? []).includes(col.key)}
                                                onChange={() => toggleColumna(key, col.key)}
                                                className="w-4 h-4 accent-leaf"
                                            />
                                            {col.label}
                                        </label>
                                    ))}
                                </div>
                            </div>

                            {/* Filtros + agrupación */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                {Object.entries(def.filters ?? {}).map(([fkey, filtro]) => (
                                    <div key={fkey}>
                                        <label className="block text-sm font-bold text-charcoal mb-2">{filtro.label}</label>
                                        {filtro.type === 'select' ? (
                                            <select
                                                value={filtros[key]?.[fkey] ?? ''}
                                                onChange={(e) => setFiltro(key, fkey, e.target.value)}
                                                className={selectCls}
                                            >
                                                <option value="">Todos</option>
                                                {(sources[filtro.source] ?? []).map((opt) => (
                                                    <option key={opt.id} value={opt.id}>
                                                        {opt.name}
                                                    </option>
                                                ))}
                                            </select>
                                        ) : (
                                            <input
                                                type="date"
                                                value={filtros[key]?.[fkey] ?? ''}
                                                onChange={(e) => setFiltro(key, fkey, e.target.value)}
                                                className={selectCls}
                                            />
                                        )}
                                    </div>
                                ))}

                                <div>
                                    <label className="block text-sm font-bold text-charcoal mb-2">
                                        <i className="fas fa-layer-group mr-1" /> Agrupar por
                                    </label>
                                    <select
                                        value={agruparPor[key] ?? ''}
                                        onChange={(e) => setGrupo(key, e.target.value)}
                                        className={selectCls}
                                    >
                                        <option value="">Sin agrupar</option>
                                        {Object.entries(def.group_by ?? {}).map(([gkey, label]) => (
                                            <option key={gkey} value={gkey}>
                                                {label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                );
            })}

            {seleccionadas.length > 0 && (
                <div className="flex justify-end pt-2">
                    <button
                        type="button"
                        onClick={handleGenerate}
                        disabled={generating}
                        className="btn-primary flex items-center gap-2 disabled:opacity-60"
                    >
                        <i className={`fas ${generating ? 'fa-spinner fa-spin' : 'fa-file-pdf'}`} />
                        Generar Reporte PDF
                    </button>
                </div>
            )}
        </div>
    );
}
