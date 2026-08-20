import { usePage } from '@inertiajs/react';

const MODULE_DOCS = {
    'socios-beneficiarios': {
        title: 'Socios y Beneficiarios',
        icon: 'fa-user-friends',
        summary: 'Registra a los socios, beneficiarios y personas del programa Vaso de Leche, y genera el padrón oficial.',
        steps: [
            'Registra primero a las personas en la pestaña "Personas": nombres, DNI, fechas y datos de ubicación.',
            'Crea los socios desde la pestaña "Socios", asignando la persona representante de cada familia.',
            'Registra los beneficiarios en la pestaña "Beneficiarios" y vincúlalos al socio correspondiente.',
            'Usa los botones de impresión para generar la ficha del beneficiario y el padrón de beneficiarios.',
        ],
    },
    'club-madres': {
        title: 'Club de Madres',
        icon: 'fa-female',
        summary: 'Administra los clubes o comités de madres, sus integrantes y la designación de la presidenta.',
        steps: [
            'Crea el comité registrando su nombre y zona correspondiente.',
            'Asigna la presidenta del comité desde el botón "Asignar Presidenta".',
            'Consulta el padrón del club para revisar la lista de beneficiarias del comité.',
        ],
    },
    reconocimientos: {
        title: 'Reconocimientos',
        icon: 'fa-award',
        summary: 'Gestiona las resoluciones de reconocimiento de los comités, incluyendo la consulta y descarga externa.',
        steps: [
            'Registra la resolución indicando el comité reconocido y los datos de la resolución.',
            'Usa la búsqueda externa para validar o consultar la resolución en el portal municipal.',
            'Descarga el documento oficial o la vista previa cuando sea necesario.',
        ],
    },
    productos: {
        title: 'Productos',
        icon: 'fa-box',
        summary: 'Mantiene el catálogo de productos (leche, hojuelas, etc.), sus unidades de medida y el stock disponible.',
        steps: [
            'Registra un producto con su nombre, unidad de medida y presentación.',
            'Revisa el stock por producto y las entradas/salidas registradas.',
            'Genera los reportes de productos según el período que necesites.',
        ],
    },
    pecosas: {
        title: 'Pecosas',
        icon: 'fa-file-alt',
        summary: 'Registra las entregas de productos a los comités (pecosas) y genera los comprobantes de salida.',
        steps: [
            'Crea una nueva pecosa seleccionando el comité y los productos a entregar.',
            'Revisa el detalle de la pecosa antes de guardar para evitar errores de stock.',
            'Genera el comprobante de salida y la programación de entrega para imprimir o descargar.',
        ],
    },
    movimientos: {
        title: 'Movimientos',
        icon: 'fa-exchange-alt',
        summary: 'Controla el kardex de existencias y genera la repartición mensual de raciones por comité.',
        steps: [
            'Registra las transacciones de entrada y salida de productos en la pestaña Kardex.',
            'Selecciona año y mes para consultar la repartición calculada con la ración vigente.',
            'Descarga el reporte de repartición en PDF para distribución o archivo.',
        ],
    },
    'responsables-raciones': {
        title: 'Responsables y Raciones',
        icon: 'fa-sliders',
        summary: 'Define quiénes son los responsables del programa y configura la ración de hojuelas y leche por año.',
        steps: [
            'En "Responsables", cambia al Subgerente de Programas Sociales o al Encargado de PROVALE cuando corresponda.',
            'En "Raciones", crea la ración del año indicando los gramos de hojuelas y los mililitros de leche por beneficiario.',
            'Edita o elimina ración solo si es necesario; el sistema usa la ración activa del año para la repartición.',
        ],
    },
    reportes: {
        title: 'Reportes',
        icon: 'fa-chart-bar',
        summary: 'Concentra los reportes e indicadores del sistema para el seguimiento del programa.',
        steps: [
            'Consulta los reportes disponibles según tu acceso.',
            'Filtra por período o criterio requerido y descarga o imprime el resultado.',
        ],
    },
    sistema: {
        title: 'Sistema',
        icon: 'fa-gear',
        summary: 'Configuración avanzada: usuarios, roles, módulos y notificaciones del sistema.',
        steps: [
            'En "Usuarios", crea o edita cuentas y asigna el rol correspondiente.',
            'En "Roles", define qué módulos ve cada rol y con qué permisos (Ver, Crear, Editar, Eliminar).',
            'En "Módulos", revisa o ajusta el estado y orden de los módulos del sistema.',
            'En "Notificaciones", aprueba o rechaza las solicitudes pendientes.',
        ],
    },
};

const PERM_LABELS = [
    { key: 'can_view', label: 'Ver', cls: 'bg-sky-light text-[#0284C7]' },
    { key: 'can_create', label: 'Crear', cls: 'bg-leaf-light text-leaf' },
    { key: 'can_edit', label: 'Editar', cls: 'bg-sun-light text-[#D97706]' },
    { key: 'can_delete', label: 'Eliminar', cls: 'bg-clay-light text-clay' },
];

export default function Ayuda() {
    const { auth, modules } = usePage().props;
    const user = auth?.user ?? null;
    const roleName = user?.rol ?? 'Usuario';

    const accessible = (modules ?? [])
        .filter((m) => m.can_view)
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0));

    return (
        <div className="space-y-6">
            <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
                <div className="px-4 sm:px-6 py-5">
                    <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                        <i className="fas fa-circle-question text-leaf" /> Centro de Ayuda
                    </h3>
                    <p className="text-earth text-xs sm:text-sm mt-1">
                        Guía de uso del sistema para el rol <span className="font-bold text-charcoal">{roleName}</span>.
                        A continuación se muestran los módulos a los que tu rol tiene acceso.
                    </p>
                </div>
            </div>

            <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
                <div className="px-4 sm:px-6 py-5">
                    <h4 className="font-extrabold text-charcoal text-lg flex items-center gap-2 mb-2">
                        <i className="fas fa-home text-leaf" /> Inicio
                    </h4>
                    <p className="text-earth text-sm">
                        Panel de control con los indicadores del programa: total de socios, beneficiarios, comités y stock.
                        Desde aquí puedes acceder rápido a una nueva pecosa o a los comités. Las gráficas muestran las
                        pecosas por mes y los productos distribuidos (leche y hojuelas).
                    </p>
                </div>
            </div>

            {accessible.length === 0 ? (
                <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm p-8 text-center">
                    <i className="fas fa-info-circle text-4xl text-earth mb-3" />
                    <p className="text-earth font-semibold">Tu rol no tiene módulos asignados. Contacta al administrador del sistema.</p>
                </div>
            ) : (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {accessible.map((mod) => {
                        const doc = MODULE_DOCS[mod.slug];
                        const title = doc?.title ?? mod.name;
                        const icon = doc?.icon ?? mod.icon ?? 'fa-cube';
                        const summary = doc?.summary ?? mod.description ?? 'Módulo del sistema PROVALE.';
                        const steps = doc?.steps ?? ['Ingresa al módulo desde el menú lateral y utiliza las opciones disponibles.'];

                        return (
                            <div key={mod.slug} className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden flex flex-col">
                                <div className="flex items-start gap-3 px-4 sm:px-5 py-4 border-b-2 border-wheat">
                                    <div className="w-10 h-10 rounded-xl bg-leaf-light flex items-center justify-center text-leaf flex-shrink-0">
                                        <i className={`fas ${icon}`} />
                                    </div>
                                    <div className="min-w-0">
                                        <h4 className="font-extrabold text-charcoal text-base leading-tight">{title}</h4>
                                        <p className="text-earth text-xs mt-1 leading-relaxed">{summary}</p>
                                    </div>
                                </div>

                                <div className="px-4 sm:px-5 py-4 flex-1">
                                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-3">Cómo usar este módulo</p>
                                    <ol className="space-y-2">
                                        {steps.map((step, i) => (
                                            <li key={i} className="flex items-start gap-2 text-sm text-charcoal">
                                                <span className="w-5 h-5 rounded-full bg-blue-light text-blue text-[11px] font-extrabold flex items-center justify-center flex-shrink-0 mt-0.5">
                                                    {i + 1}
                                                </span>
                                                <span className="leading-relaxed">{step}</span>
                                            </li>
                                        ))}
                                    </ol>
                                </div>

                                <div className="px-4 sm:px-5 py-3 bg-gray-50 border-t border-wheat">
                                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Permisos de tu rol en este módulo</p>
                                    <div className="flex flex-wrap gap-2">
                                        {PERM_LABELS.filter((p) => mod[p.key]).map((p) => (
                                            <span key={p.key} className={`px-2.5 py-1 text-xs font-bold rounded-full ${p.cls}`}>
                                                {p.label}
                                            </span>
                                        ))}
                                        {PERM_LABELS.every((p) => !mod[p.key]) && (
                                            <span className="px-2.5 py-1 text-xs font-bold rounded-full bg-gray-200 text-gray-600">Solo lectura</span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}