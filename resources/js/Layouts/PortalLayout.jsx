import '../../css/portal.css';
import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import ThemeToggle from '../Components/ThemeToggle';

const base = typeof window !== 'undefined' ? window.APP_URL || '' : '';
export const portalPath = (path = '') => `${base}${path}`;

const NAV = [
    { key: 'inicio', label: 'Inicio', icon: 'fa-house', href: '/portal-presidentas', match: (p) => /\/portal-presidentas\/?$/.test(p) },
    { key: 'socios', label: 'Socios y beneficiarios', icon: 'fa-users', href: '/portal-presidentas/socios', match: (p) => p.includes('/portal-presidentas/socios') },
    { key: 'clave', label: 'Cambiar contraseña', icon: 'fa-key', href: '/portal-presidentas/cambiar-contrasena', match: (p) => p.includes('/cambiar-contrasena') },
];

export default function PortalLayout({ title, children }) {
    const { props } = usePage();
    const [showLoading, setShowLoading] = useState(false);
    const activeVisits = useRef(0);
    const loadingTimer = useRef(null);
    const flash = props?.flash ?? {};
    const pathname = typeof window !== 'undefined' ? window.location.pathname : '';
    const isPecosa = pathname.includes('/portal-presidentas/pecosas/');

    const logout = (e) => {
        e.preventDefault();
        router.post(portalPath('/portal-presidentas/logout'));
    };

    useEffect(() => {
        const stopStartListener = router.on('start', () => {
            activeVisits.current += 1;

            if (!loadingTimer.current) {
                loadingTimer.current = window.setTimeout(() => {
                    setShowLoading(true);
                    loadingTimer.current = null;
                }, 400);
            }
        });
        const stopFinishListener = router.on('finish', () => {
            activeVisits.current = Math.max(0, activeVisits.current - 1);

            if (activeVisits.current === 0) {
                window.clearTimeout(loadingTimer.current);
                loadingTimer.current = null;
                setShowLoading(false);
            }
        });

        return () => {
            stopStartListener();
            stopFinishListener();
            window.clearTimeout(loadingTimer.current);
        };
    }, []);

    return (
        <div className="portal-app">
            {title && <Head title={title} />}
            <a className="skip-link" href="#portal-main">Saltar al contenido</a>

            <header className="portal-topbar">
                <div className="portal-topbar-inner">
                    <div className="portal-brand">
                        <img src={portalPath('/img/logo-provale-sin-fondo.png')} width="44" height="44" alt="Logo PROVALE" />
                        <div>
                            <strong>Municipalidad Distrital de La Esperanza</strong>
                            <span>PROVALE · Portal de Presidentas</span>
                        </div>
                    </div>
                    <div className="portal-topbar-actions">
                        <ThemeToggle variant="portal" />
                        <form onSubmit={logout}>
                            <button className="portal-logout" type="submit">
                                <i className="fas fa-right-from-bracket" aria-hidden="true" /> Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <nav className="portal-navbar" aria-label="Navegación del portal">
                <div className="portal-navbar-inner">
                    {NAV.map((item) => {
                        const active = item.key === 'inicio' && isPecosa ? false : item.match(pathname);
                        return (
                            <Link
                                key={item.key}
                                href={portalPath(item.href)}
                                className={active ? 'is-active' : undefined}
                                aria-current={active ? 'page' : undefined}
                            >
                                <i className={`fas ${item.icon}`} aria-hidden="true" /> {item.label}
                            </Link>
                        );
                    })}
                </div>
            </nav>

            <main className="portal-container" id="portal-main" aria-busy={showLoading}>
                {flash.success && (
                    <div className="portal-flash is-success" role="status">
                        <i className="fas fa-circle-check" aria-hidden="true" /> {flash.success}
                    </div>
                )}
                {flash.error && (
                    <div className="portal-flash is-error" role="alert">
                        <i className="fas fa-circle-exclamation" aria-hidden="true" /> {flash.error}
                    </div>
                )}
                {children}
            </main>

            {showLoading && (
                <div className="portal-loading-backdrop" role="presentation">
                    <div className="portal-loading-modal" role="status" aria-live="polite" aria-label="Cargando contenido">
                        <span className="portal-loading-spinner" aria-hidden="true" />
                        <strong>Cargando información</strong>
                        <span>Espera un momento…</span>
                    </div>
                </div>
            )}
        </div>
    );
}
