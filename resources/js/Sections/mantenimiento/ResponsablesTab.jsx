import { useCallback, useEffect, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import Combobox from '../../Components/Combobox';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/mantenimiento';

const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';

function ResponsibleCard({ title, subtitle, icon, iconClass, responsible, people, canEdit, onSaved }) {
    const toast = useToast();
    const [open, setOpen] = useState(false);
    const [personId, setPersonId] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const openModal = () => {
        setPersonId(responsible?.person_id ?? '');
        setOpen(true);
    };

    const peopleOptions = people.map((p) => ({
        id: p.id,
        label: `${p.names} ${p.father_lastname} ${p.mother_lastname} - ${p.dni}`,
    }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!personId) {
            toast.error('Seleccione una persona.');
            return;
        }
        setSubmitting(true);
        try {
            await http.put(`${BASE}/responsibles/${title.type}`, { person_id: personId });
            toast.success('Responsable actualizado correctamente.');
            setOpen(false);
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo actualizar el responsable.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="bg-wheat/40 rounded-xl p-6">
            <div className="flex items-center gap-3 mb-4">
                <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${iconClass}`}>
                    <i className={`fas ${icon}`} />
                </div>
                <div>
                    <p className={labelCls}>{subtitle}</p>
                    <p className="font-bold text-charcoal">{responsible?.person_name || 'Sin asignar'}</p>
                    {responsible?.person_dni && <p className="text-xs text-earth">DNI: {responsible.person_dni}</p>}
                </div>
            </div>
            {canEdit && (
                <button type="button" onClick={openModal} className="btn-primary w-full text-sm">
                    <i className="fas fa-exchange-alt mr-2" /> Cambiar {title.label}
                </button>
            )}

            <Modal open={open} onClose={() => setOpen(false)} title={`Cambiar ${title.label}`} icon={icon} iconClass="text-leaf">
                <form onSubmit={handleSubmit} className="p-6 space-y-4">
                    <div>
                        <label className={labelCls}>Seleccionar Persona</label>
                        <Combobox
                            value={personId}
                            onChange={(id) => setPersonId(id ?? '')}
                            options={peopleOptions}
                            placeholder="Buscar persona..."
                        />
                    </div>
                    <div className="flex gap-3 pt-2">
                        <button type="button" onClick={() => setOpen(false)} className="btn-secondary flex-1">Cancelar</button>
                        <button type="submit" disabled={submitting} className="btn-primary flex-1">
                            <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} /> Guardar
                        </button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}

export default function ResponsablesTab({ can }) {
    const [data, setData] = useState(null);
    const [loadError, setLoadError] = useState(false);

    const load = useCallback(async () => {
        try {
            const res = await http.get(`${BASE}/responsibles`);
            setData(res.data);
        } catch {
            setLoadError(true);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    if (loadError) {
        return (
            <div className="empty-state">
                <i className="fas fa-exclamation-triangle" />
                <p>No se pudieron cargar los datos de la sección. Recarga la página.</p>
            </div>
        );
    }

    if (!data) {
        return (
            <div className="flex items-center justify-center py-10 text-earth">
                <i className="fas fa-spinner fa-spin mr-2" /> Cargando...
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <ResponsibleCard
                title={{ type: 'chief', label: 'Subgerente de Programas Sociales' }}
                subtitle="Subgerencia de Programas Sociales"
                icon="fa-user-shield"
                iconClass="bg-leaf-light text-leaf"
                responsible={data.chief}
                people={data.people}
                canEdit={can.edit}
                onSaved={load}
            />
            <ResponsibleCard
                title={{ type: 'storekeeper', label: 'Encargado de PROVALE' }}
                subtitle="Programa Vaso de Leche"
                icon="fa-warehouse"
                iconClass="bg-sky-light text-[#0284C7]"
                responsible={data.storekeeper}
                people={data.people}
                canEdit={can.edit}
                onSaved={load}
            />
        </div>
    );
}
