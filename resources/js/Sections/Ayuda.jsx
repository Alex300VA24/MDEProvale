import { useCallback, useState } from 'react';
import { usePage } from '@inertiajs/react';
import Modal from '../Components/Modal';

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
    const [selectedGuide, setSelectedGuide] = useState(null);
    const closeGuide = useCallback(() => setSelectedGuide(null), []);

    const accessible = (modules ?? [])
        .filter((m) => m.can_view)
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0));

    const guides = [
        {
            slug: 'inicio',
            title: 'Inicio',
            icon: 'fa-home',
            summary: 'Panel de control con indicadores, accesos rápidos y gráficas del programa.',
            steps: [
                'Revisa los indicadores generales de socios, beneficiarios, comités y stock.',
                'Usa los accesos rápidos para registrar una pecosa o consultar los comités.',
                'Consulta las gráficas para comparar pecosas mensuales y productos distribuidos.',
            ],
            module: null,
        },
        ...accessible.map((module) => {
            const doc = MODULE_DOCS[module.slug];
            return {
                slug: module.slug,
                title: doc?.title ?? module.name,
                icon: doc?.icon ?? module.icon ?? 'fa-cube',
                summary: doc?.summary ?? module.description ?? 'Módulo del sistema PROVALE.',
                steps: doc?.steps ?? ['Ingresa al módulo desde el menú lateral y utiliza las opciones disponibles.'],
                module,
            };
        }),
    ];

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

            {accessible.length === 0 ? (
                <div className="bg-sun-light border-2 border-sun/30 rounded-2xl px-4 py-3 flex items-start gap-3">
                    <i className="fas fa-info-circle text-sun mt-0.5" aria-hidden="true" />
                    <p className="text-earth text-sm font-semibold">Tu rol no tiene módulos asignados. Contacta al administrador del sistema.</p>
                </div>
            ) : null}

            <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
                <div className="px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat">
                    <h4 className="font-extrabold text-charcoal text-lg sm:text-xl flex items-center gap-3">
                        <i className="fas fa-book-open text-leaf" /> Guías disponibles
                    </h4>
                    <p className="text-earth text-xs sm:text-sm mt-1">Selecciona una opción para abrir instrucciones y permisos.</p>
                </div>
                <div className="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    {guides.map((guide) => (
                        <button
                            key={guide.slug}
                            type="button"
                            onClick={() => setSelectedGuide(guide)}
                            className="group min-h-[148px] bg-white rounded-2xl border-2 border-wheat shadow-sm hover:border-leaf/40 hover:shadow-lg focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-leaf/25 transition-all text-left p-5 flex flex-col"
                            aria-label={`Abrir guía de ${guide.title}`}
                        >
                            <div className="flex items-start gap-3 w-full">
                                <span className="w-11 h-11 rounded-xl bg-leaf-light flex items-center justify-center text-leaf flex-shrink-0 group-hover:bg-leaf group-hover:text-white transition-colors">
                                    <i className={`fas ${guide.icon}`} aria-hidden="true" />
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block font-extrabold text-charcoal leading-tight">{guide.title}</span>
                                    <span className="block text-earth text-xs mt-1.5 leading-relaxed line-clamp-3">{guide.summary}</span>
                                </span>
                            </div>
                            <span className="mt-auto pt-4 text-blue text-xs font-extrabold flex items-center gap-2">
                                Ver guía <i className="fas fa-arrow-right group-hover:translate-x-1 transition-transform" aria-hidden="true" />
                            </span>
                        </button>
                    ))}
                </div>
            </div>

            <Modal
                open={Boolean(selectedGuide)}
                onClose={closeGuide}
                title={selectedGuide?.title ?? 'Guía'}
                icon={selectedGuide?.icon}
                maxWidth="sm:max-w-3xl"
            >
                {selectedGuide && (
                    <div className="max-h-[70vh] overflow-y-auto">
                        <div className="px-4 sm:px-6 py-5 border-b border-wheat">
                            <p className="text-earth text-sm leading-relaxed">{selectedGuide.summary}</p>
                        </div>
                        <div className="px-4 sm:px-6 py-5">
                            <p className="text-xs font-extrabold text-earth uppercase tracking-wider mb-4">Cómo usar esta sección</p>
                            <ol className="space-y-3">
                                {selectedGuide.steps.map((step, index) => (
                                    <li key={step} className="flex items-start gap-3 text-sm text-charcoal">
                                        <span className="w-7 h-7 rounded-full bg-blue-light text-blue text-xs font-extrabold flex items-center justify-center flex-shrink-0">
                                            {index + 1}
                                        </span>
                                        <span className="leading-relaxed pt-0.5">{step}</span>
                                    </li>
                                ))}
                            </ol>
                        </div>
                        {selectedGuide.module && (
                            <div className="px-4 sm:px-6 py-4 bg-gray-50 border-t border-wheat">
                                <p className="text-xs font-extrabold text-earth uppercase tracking-wider mb-3">Permisos de tu rol</p>
                                <div className="flex flex-wrap gap-2">
                                    {PERM_LABELS.filter((permission) => selectedGuide.module[permission.key]).map((permission) => (
                                        <span key={permission.key} className={`px-3 py-1.5 text-xs font-bold rounded-full ${permission.cls}`}>
                                            {permission.label}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </Modal>
        </div>
    );
}
