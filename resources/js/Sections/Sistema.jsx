import { useRef, useState } from 'react';
import { useRuntimeState } from '../RuntimeState';
import UsuariosTab from './sistema/UsuariosTab';
import RolesTab from './sistema/RolesTab';
import ModulosTab from './sistema/ModulosTab';
import NotificacionesTab from './sistema/NotificacionesTab';
import AsistenteConfigTab from './sistema/AsistenteConfigTab';
import ParametrosTab from './sistema/ParametrosTab';
import AuditoriaTab from './sistema/AuditoriaTab';
import HistorialCambiosTab from './sistema/HistorialCambiosTab';
import ConocimientoTab from './sistema/ConocimientoTab';

const HEADERS = {
    usuarios: { icon: 'fa-users', title: 'Gestión de Usuarios', newLabel: 'Registrar Usuario', description: 'Administra los usuarios del sistema.' },
    roles: { icon: 'fa-user-tag', title: 'Gestión de Roles', newLabel: 'Registrar Rol', description: 'Administra los roles y permisos del sistema.' },
    modulos: { icon: 'fa-puzzle-piece', title: 'Gestión de Módulos', newLabel: 'Registrar Módulo', description: 'Administra los módulos del sistema.' },
    notificaciones: { icon: 'fa-bell', title: 'Notificaciones', description: 'Administra las notificaciones del sistema.' },
    asistente: { icon: 'fa-robot', title: 'Asistente IA', description: 'Configura el límite de consultas del asistente IA por usuario.' },
    parametros: { icon: 'fa-sliders', title: 'Parámetros', description: 'Configura límites globales del sistema.' },
    auditoria: { icon: 'fa-shield-halved', title: 'Auditoría', description: 'Consulta eventos automáticos y cambios sensibles.' },
    historial: { icon: 'fa-clock-rotate-left', title: 'Historial de Cambios', description: 'Trazabilidad: quién modificó qué, cuándo y desde dónde.' },
    conocimiento: { icon: 'fa-database', title: 'Base de Conocimiento IA', adminOnly: true, description: 'Documentos indexados, consultas con IA e historial de cambios detectados.' },
};

export default function Sistema() {
    const { modules, currentUser } = useRuntimeState();
    const isAdmin = Boolean(currentUser?.is_admin);
    const mod = (modules ?? []).find((m) => m.slug === 'sistema');
    const can = {
        view: !!mod?.can_view,
        create: !!mod?.can_create,
        edit: !!mod?.can_edit,
        del: !!mod?.can_delete,
    };

    const [tab, setTab] = useState('usuarios');
    const usuariosRef = useRef(null);
    const rolesRef = useRef(null);
    const modulosRef = useRef(null);

    const header = HEADERS[tab];

    if (!can.view) {
        return (
            <div className="empty-state">
                <i className="fas fa-lock" />
                <p>No tiene acceso a este módulo.</p>
            </div>
        );
    }

    const createHandlers = { usuarios: usuariosRef, roles: rolesRef, modulos: modulosRef };
    const activeCreateRef = createHandlers[tab];

    return (
        <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
            <div className="px-4 sm:px-6 py-4 sm:py-5 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                        <i className={`fas ${header.icon} text-leaf`} /> {header.title}
                    </h3>
                    <p className="text-earth text-xs sm:text-sm mt-1">{header.description}</p>
                </div>
                <div className="flex flex-wrap items-center gap-2 shrink-0">
                    {header.newLabel && can.create && activeCreateRef && (
                        <button
                            type="button"
                            onClick={() => activeCreateRef.current?.openCreate()}
                            className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                        >
                            <i className="fas fa-plus" /> {header.newLabel}
                        </button>
                    )}
                </div>
            </div>

            <div className="flex items-center px-4 sm:px-6 py-3 border-b-2 border-wheat">
                <div className="flex items-center gap-1 overflow-x-auto w-full">
                    {Object.entries(HEADERS).filter(([, h]) => !h.adminOnly || isAdmin).map(([key, h]) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setTab(key)}
                            className={`flex items-center gap-2 px-3 sm:px-4 py-2 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition-all ${
                                tab === key ? 'text-leaf border-leaf' : 'text-earth border-transparent hover:text-charcoal'
                            }`}
                        >
                            <i className={`fas ${h.icon}`} /> {h.title.replace('Gestión de ', '')}
                        </button>
                    ))}
                </div>
            </div>

            <div className="p-4 sm:p-6">
                <div className={tab === 'usuarios' ? '' : 'hidden'}>
                    <UsuariosTab ref={usuariosRef} can={can} />
                </div>
                <div className={tab === 'roles' ? '' : 'hidden'}>
                    <RolesTab ref={rolesRef} can={can} />
                </div>
                <div className={tab === 'modulos' ? '' : 'hidden'}>
                    <ModulosTab ref={modulosRef} can={can} />
                </div>
                <div className={tab === 'notificaciones' ? '' : 'hidden'}>
                    <NotificacionesTab />
                </div>
                <div className={tab === 'asistente' ? '' : 'hidden'}>
                    <AsistenteConfigTab can={can} />
                </div>
                <div className={tab === 'parametros' ? '' : 'hidden'}>
                    <ParametrosTab can={can} />
                </div>
                <div className={tab === 'auditoria' ? '' : 'hidden'}>
                    <AuditoriaTab />
                </div>
                <div className={tab === 'historial' ? '' : 'hidden'}>
                    {tab === 'historial' && <HistorialCambiosTab />}
                </div>
                {isAdmin && (
                    <div className={tab === 'conocimiento' ? '' : 'hidden'}>
                        {tab === 'conocimiento' && <ConocimientoTab />}
                    </div>
                )}
            </div>
        </div>
    );
}
