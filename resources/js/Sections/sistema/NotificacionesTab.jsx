import { useCallback, useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/sistema';

const STATUS_LABEL = {
    pending: { label: 'Pendiente', cls: 'bg-sun-light text-[#D97706]' },
    approved: { label: 'Aprobada', cls: 'bg-leaf-light text-leaf' },
    rejected: { label: 'Rechazada', cls: 'bg-clay-light text-clay' },
};

function fmtDateTime(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('es-PE');
}

export default function NotificacionesTab() {
    const toast = useToast();
    const { auth } = usePage().props;
    const isAdmin = auth?.user?.rol_id === 1;

    const [notifications, setNotifications] = useState(null);
    const [loading, setLoading] = useState(true);
    const [processing, setProcessing] = useState(null);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res = await http.get(`${BASE}/notifications`);
            setNotifications(res.data.data);
        } catch {
            toast.error('No se pudo cargar las notificaciones.');
        } finally {
            setLoading(false);
        }
    }, [toast]);

    useEffect(() => {
        load();
        http.post(`${BASE}/notifications/mark-seen`).catch(() => {});
    }, [load]);

    const handleApprove = async (n) => {
        setProcessing(n.id);
        try {
            await http.post(`${BASE}/notifications/${n.id}/approve`);
            toast.success('Solicitud aprobada: la contraseña fue restaurada al DNI.');
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo aprobar la solicitud.'));
        } finally {
            setProcessing(null);
        }
    };

    const handleReject = async (n) => {
        setProcessing(n.id);
        try {
            await http.post(`${BASE}/notifications/${n.id}/reject`);
            toast.success('Solicitud rechazada.');
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo rechazar la solicitud.'));
        } finally {
            setProcessing(null);
        }
    };

    if (loading && !notifications) {
        return (
            <div className="flex items-center justify-center py-10 text-earth">
                <i className="fas fa-spinner fa-spin mr-2" /> Cargando notificaciones...
            </div>
        );
    }

    if (!notifications) return null;

    return (
        <div className="space-y-3">
            {notifications.length === 0 ? (
                <div className="empty-state">
                    <i className="fas fa-bell" />
                    <p>No hay notificaciones{isAdmin ? '' : ' para tu usuario'}.</p>
                </div>
            ) : (
                notifications.map((n) => {
                    const status = STATUS_LABEL[n.status] || { label: n.status, cls: 'bg-gray-100 text-gray-800' };
                    return (
                        <div key={n.id} className="p-4 rounded-xl border-2 border-wheat bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div className="flex-1">
                                <div className="flex items-center gap-2 mb-1">
                                    <span className={`px-2 py-1 text-[10px] font-bold rounded-full ${status.cls}`}>{status.label}</span>
                                    <span className="font-bold text-charcoal text-sm">{n.title}</span>
                                </div>
                                <p className="text-sm text-earth">{n.description}</p>
                                <p className="text-xs text-earth mt-1">
                                    Solicitado por {n.requested_by_name || '—'} el {fmtDateTime(n.requested_at)}
                                    {n.processed_at && ` · Procesado por ${n.processed_by_name || '—'} el ${fmtDateTime(n.processed_at)}`}
                                </p>
                            </div>
                            {isAdmin && n.status === 'pending' && (
                                <div className="flex gap-2">
                                    <button
                                        type="button"
                                        disabled={processing === n.id}
                                        onClick={() => handleApprove(n)}
                                        className="btn-primary text-xs"
                                    >
                                        <i className={`fas ${processing === n.id ? 'fa-spinner fa-spin' : 'fa-check'} mr-1`} /> Aprobar
                                    </button>
                                    <button
                                        type="button"
                                        disabled={processing === n.id}
                                        onClick={() => handleReject(n)}
                                        className="btn-secondary text-xs"
                                    >
                                        <i className="fas fa-times mr-1" /> Rechazar
                                    </button>
                                </div>
                            )}
                        </div>
                    );
                })
            )}
        </div>
    );
}
