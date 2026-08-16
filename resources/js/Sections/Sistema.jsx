import { useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import UsuariosTab from './sistema/UsuariosTab';
import RolesTab from './sistema/RolesTab';
import ModulosTab from './sistema/ModulosTab';
import NotificacionesTab from './sistema/NotificacionesTab';

const HEADERS = {
    usuarios: { icon: 'fa-users', title: 'Gestión de Usuarios', newLabel: 'Nuevo Usuario' },
    roles: { icon: 'fa-user-tag', title: 'Gestión de Roles', newLabel: 'Nuevo Rol' },
    modulos: { icon: 'fa-puzzle-piece', title: 'Gestión de Módulos', newLabel: 'Nuevo Módulo' },
    notificaciones: { icon: 'fa-bell', title: 'Notificaciones' },
};

export default function Sistema() {
    const { modules } = usePage().props;
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
            <div className="px-4 sm:px-6 py-4 sm:py-5">
                <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                    <i className={`fas ${header.icon} text-leaf`} /> {header.title}
                </h3>
            </div>

            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 border-b-2 border-wheat gap-3">
                <div className="flex flex-wrap items-center gap-2">
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

                <div className="flex items-center gap-1 overflow-x-auto">
                    {Object.entries(HEADERS).map(([key, h]) => (
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
            </div>
        </div>
    );
}
