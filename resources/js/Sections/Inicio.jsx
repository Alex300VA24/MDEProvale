import { useEffect, useRef, useState } from 'react';
import Chart from 'chart.js/auto';
import http from '../http';

const BASE = '/api/dashboard/inicio';
const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
const CHART_FONT = { family: 'Plus Jakarta Sans', size: 11 };
const GRID_COLOR = '#D4E4F7';
const TICK_COLOR = '#5A7FA8';

function StatCard({ icon, iconClass, barClass, badge, badgeClass, value, label }) {
    return (
        <div className="stat-card bg-white rounded-xl sm:rounded-2xl p-3 sm:p-5 border border-mist shadow-sm relative overflow-hidden">
            <div className={`absolute top-0 left-0 right-0 h-1 ${barClass}`} />
            <div className="flex items-center justify-between mb-3 sm:mb-4">
                <div className={`w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl flex items-center justify-center text-sm sm:text-lg ${iconClass}`}>
                    <i className={`fas ${icon}`} />
                </div>
                <span className={`text-[9px] sm:text-[11px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full ${badgeClass}`}>{badge}</span>
            </div>
            <div className="text-xl sm:text-3xl font-bold text-navy leading-none mb-1">{value}</div>
            <div className="text-[10px] sm:text-xs font-medium text-slate">{label}</div>
        </div>
    );
}

function QuickButton({ href, icon, label, bgClass, tileClass, textClass }) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noreferrer"
            className={`quick-btn flex flex-col items-center gap-1 p-2 sm:p-3 rounded-lg sm:rounded-xl transition-all group ${bgClass}`}
        >
            <div className={`w-7 h-7 sm:w-8 sm:h-8 rounded-md sm:rounded-lg flex items-center justify-center text-white text-xs sm:text-sm group-hover:scale-105 transition-all ${tileClass}`}>
                <i className={`fas ${icon}`} />
            </div>
            <span className={`text-[8px] sm:text-[10px] font-semibold text-center leading-tight ${textClass}`}>{label}</span>
        </a>
    );
}

export default function Inicio({ onNavigate }) {
    const [panel, setPanel] = useState(null);
    const [error, setError] = useState(false);

    const pecosasCanvas = useRef(null);
    const productosCanvas = useRef(null);
    const donutCanvas = useRef(null);
    const topComitesCanvas = useRef(null);
    const charts = useRef({});

    useEffect(() => {
        let active = true;
        (async () => {
            try {
                const res = await http.get(`${BASE}/panel`);
                if (active) setPanel(res.data);
            } catch {
                if (active) setError(true);
            }
        })();
        return () => {
            active = false;
        };
    }, []);

    useEffect(() => {
        if (!panel) return undefined;

        Object.values(charts.current).forEach((c) => c?.destroy());
        charts.current = {};

        const pecosaData = panel.pecosas_por_mes.data;
        if (pecosasCanvas.current && !pecosaData.every((v) => v === 0)) {
            charts.current.pecosas = new Chart(pecosasCanvas.current, {
                type: 'bar',
                data: {
                    labels: MESES,
                    datasets: [{
                        label: 'PECOSAs',
                        data: pecosaData,
                        backgroundColor: '#4A90D9',
                        borderRadius: 4,
                        borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: CHART_FONT, color: TICK_COLOR } },
                        y: { grid: { color: GRID_COLOR }, ticks: { font: CHART_FONT, color: TICK_COLOR }, beginAtZero: true },
                    },
                },
            });
        }

        const { leche, hojuelas } = panel.productos_distribuidos;
        if (productosCanvas.current && !(leche.every((v) => v === 0) && hojuelas.every((v) => v === 0))) {
            charts.current.productos = new Chart(productosCanvas.current, {
                type: 'line',
                data: {
                    labels: MESES,
                    datasets: [
                        {
                            label: 'Leche',
                            data: leche,
                            borderColor: '#1E5799',
                            backgroundColor: 'rgba(30,87,153,0.10)',
                            borderWidth: 2,
                            pointBackgroundColor: '#1E5799',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 1.5,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            tension: 0.35,
                        },
                        {
                            label: 'Hojuelas',
                            data: hojuelas,
                            borderColor: '#B87300',
                            backgroundColor: 'rgba(184,115,0,0.10)',
                            borderWidth: 2,
                            pointBackgroundColor: '#B87300',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 1.5,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            tension: 0.35,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: true, position: 'top', align: 'end', labels: { font: CHART_FONT, boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'circle' } },
                        tooltip: { mode: 'index', intersect: false },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: CHART_FONT, color: TICK_COLOR } },
                        y: { grid: { color: GRID_COLOR }, ticks: { font: CHART_FONT, color: TICK_COLOR }, beginAtZero: true },
                    },
                },
            });
        }

        if (donutCanvas.current) {
            const { socios, beneficiarios } = panel.socios_vs_beneficiarios;
            charts.current.donut = new Chart(donutCanvas.current, {
                type: 'doughnut',
                data: {
                    labels: ['Socios', 'Beneficiarios'],
                    datasets: [{
                        data: [socios, beneficiarios],
                        backgroundColor: ['#4A90D9', '#0E8A7A'],
                        borderWidth: 0,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: { legend: { display: false } },
                },
            });
        }

        if (topComitesCanvas.current) {
            const labels = panel.top_comites.map((c) => c.nombre.slice(0, 12));
            const data = panel.top_comites.map((c) => c.total);
            charts.current.topComites = new Chart(topComitesCanvas.current, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Beneficiarios',
                        data,
                        backgroundColor: '#4A90D9',
                        borderRadius: 3,
                        borderSkipped: false,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { color: GRID_COLOR }, ticks: { font: CHART_FONT, color: TICK_COLOR } },
                        y: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 }, color: TICK_COLOR } },
                    },
                },
            });
        }

        return () => {
            Object.values(charts.current).forEach((c) => c?.destroy());
            charts.current = {};
        };
    }, [panel]);

    if (error) {
        return (
            <div className="empty-state">
                <i className="fas fa-exclamation-triangle" />
                <p>No se pudo cargar el panel de inicio. Recarga la página.</p>
            </div>
        );
    }

    if (!panel) {
        return (
            <div className="flex items-center justify-center py-10 text-earth">
                <i className="fas fa-spinner fa-spin mr-2" /> Cargando panel...
            </div>
        );
    }

    const { stats, pecosas_por_mes: pecosasPorMes, socios_vs_beneficiarios: sociosVsBeneficiarios, top_comites: topComites } = panel;
    const anio = pecosasPorMes.anio;

    return (
        <div>
            <div className="relative rounded-2xl sm:rounded-3xl overflow-hidden mb-6 sm:mb-8 shadow-lg">
                <div className="absolute inset-0">
                    <img src="/img/niños.jpg" alt="Banner" className="w-full h-full object-cover" />
                </div>
                <div className="absolute inset-0 bg-gradient-to-r from-blue/60 to-navy/40" />
                <div className="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between p-5 sm:p-8 gap-4 sm:gap-8">
                    <div>
                        <div className="flex items-center gap-2 mb-2 sm:mb-3">
                            <span className="w-2 h-2 rounded-full pulse-dot" style={{ background: '#D6EAFC' }} />
                            <span className="text-white/90 text-[10px] sm:text-xs font-semibold uppercase tracking-widest">Sistema activo</span>
                        </div>
                        <h1 className="text-white font-extrabold text-xl sm:text-3xl leading-tight mb-2">
                            Panel de Control
                            <br />
                            <span style={{ color: '#FEF3DC' }}>PROVALE</span>
                        </h1>
                        <p className="text-white/70 text-xs sm:text-sm font-medium max-w-md">
                            Gestiona beneficiarios, club de madres y entregas de manera eficiente.
                        </p>
                        <div className="flex flex-wrap gap-2 sm:gap-3 mt-4 sm:mt-5">
                            <button
                                type="button"
                                onClick={() => onNavigate?.('productos')}
                                className="px-3 sm:px-4 py-2 bg-white/20 backdrop-blur-sm text-white font-semibold rounded-lg text-xs sm:text-sm hover:bg-white/30 transition-all border border-white/20"
                            >
                                <i className="fas fa-plus mr-1" />Nueva Pecosa
                            </button>
                            <button
                                type="button"
                                onClick={() => onNavigate?.('comites')}
                                className="px-3 sm:px-4 py-2 bg-white text-blue font-semibold rounded-lg text-xs sm:text-sm hover:bg-blue-light transition-all shadow-sm"
                            >
                                <i className="fas fa-file-alt mr-1" />Comites
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
                <StatCard
                    icon="fa-users"
                    iconClass="bg-blue-light text-blue"
                    barClass="bg-gradient-to-r from-blue to-sky"
                    badge="+12%"
                    badgeClass="text-blue bg-blue-light"
                    value={stats.total_socios}
                    label="Total Socios"
                />
                <StatCard
                    icon="fa-user-check"
                    iconClass="bg-sky-light text-sky"
                    barClass="bg-gradient-to-r from-sky to-[#7ec3e8]"
                    badge="+8%"
                    badgeClass="text-sky bg-sky-light"
                    value={stats.total_beneficiarios}
                    label="Beneficiarios"
                />
                <StatCard
                    icon="fa-heart"
                    iconClass="bg-amber-light text-amber"
                    barClass="bg-gradient-to-r from-amber to-[#f0c567]"
                    badge="+5%"
                    badgeClass="text-amber bg-amber-light"
                    value={stats.total_comites}
                    label="Club de Madres"
                />
                <StatCard
                    icon="fa-box"
                    iconClass="bg-teal-light text-teal"
                    barClass="bg-gradient-to-r from-teal to-[#5ec4b3]"
                    badge="-3%"
                    badgeClass="text-teal bg-teal-light"
                    value={stats.stock_total}
                    label="Stock Total"
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div className="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-mist shadow-sm">
                    <div className="flex items-center justify-between mb-4">
                        <div>
                            <h3 className="dashboard-section-title font-extrabold text-sm sm:text-base">PECOSAs por Mes</h3>
                            <p className="text-slate text-xs sm:text-sm">Salidas {anio}</p>
                        </div>
                        <span className="px-2 py-1 text-[10px] sm:text-xs font-bold bg-blue-light text-blue rounded-lg">{pecosasPorMes.total_anio} total</span>
                    </div>
                    <div className="chart-wrap h-40 sm:h-48 relative">
                        {pecosasPorMes.data.every((v) => v === 0) ? (
                            <div className="empty-state absolute inset-0">
                                <i className="fas fa-file-invoice" />
                                <p className="text-xs sm:text-sm">Aún no hay PECOSAs registradas en {anio}</p>
                            </div>
                        ) : (
                            <canvas ref={pecosasCanvas} />
                        )}
                    </div>
                </div>

                <div className="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-mist shadow-sm">
                    <div className="flex items-center justify-between mb-4">
                        <div>
                            <h3 className="dashboard-section-title font-extrabold text-sm sm:text-base">Productos Distribuidos</h3>
                            <p className="text-slate text-xs sm:text-sm">Leche y Hojuelas - {anio}</p>
                        </div>
                        <div className="flex gap-1 sm:gap-2">
                            <span className="px-1.5 sm:px-2 py-1 text-[10px] sm:text-xs font-semibold bg-blue-light text-blue rounded">Leche</span>
                            <span className="px-1.5 sm:px-2 py-1 text-[10px] sm:text-xs font-semibold bg-amber-light text-amber rounded">Hojuelas</span>
                        </div>
                    </div>
                    <div className="chart-wrap h-40 sm:h-48 relative">
                        {panel.productos_distribuidos.leche.every((v) => v === 0) && panel.productos_distribuidos.hojuelas.every((v) => v === 0) ? (
                            <div className="empty-state absolute inset-0">
                                <i className="fas fa-boxes-stacked" />
                                <p className="text-xs sm:text-sm">Aún no hay movimientos de productos en {anio}</p>
                            </div>
                        ) : (
                            <canvas ref={productosCanvas} />
                        )}
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div className="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-5 border border-mist shadow-sm">
                    <h3 className="dashboard-section-title font-extrabold text-sm sm:text-base mb-1">Socios vs Beneficiarios</h3>
                    <p className="text-slate text-xs sm:text-sm mb-3">Comparativa total</p>
                    <div className="chart-wrap h-32 sm:h-36">
                        <canvas ref={donutCanvas} />
                    </div>
                    <div className="mt-3 grid grid-cols-2 gap-2">
                        <div className="text-center p-2 bg-blue-light rounded-lg">
                            <div className="text-lg sm:text-xl font-bold text-blue">{sociosVsBeneficiarios.socios}</div>
                            <div className="text-[9px] sm:text-[10px] font-medium text-slate uppercase">Socios</div>
                        </div>
                        <div className="text-center p-2 bg-sky-light rounded-lg">
                            <div className="text-lg sm:text-xl font-bold text-sky">{sociosVsBeneficiarios.beneficiarios}</div>
                            <div className="text-[9px] sm:text-[10px] font-medium text-slate uppercase">Beneficiarios</div>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-5 border border-mist shadow-sm">
                    <h3 className="dashboard-section-title font-extrabold text-sm sm:text-base mb-1">Top Comités</h3>
                    <p className="text-slate text-xs sm:text-sm mb-3">Con más beneficiarios</p>
                    <div className="chart-wrap h-36 sm:h-44">
                        {topComites.length === 0 ? (
                            <div className="empty-state">
                                <i className="fas fa-people-roof" />
                                <p className="text-xs sm:text-sm">Aún no hay comités con beneficiarios</p>
                            </div>
                        ) : (
                            <canvas ref={topComitesCanvas} />
                        )}
                    </div>
                </div>

                <div className="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-5 border border-mist shadow-sm">
                    <h3 className="dashboard-section-title font-extrabold text-sm sm:text-base mb-4">Reportes Rápidos</h3>
                    <div className="grid grid-cols-2 gap-2">
                        <QuickButton
                            href="/socios-beneficiarios/beneficiarios-padron"
                            icon="fa-file-pdf"
                            label="Padrón Beneficiarios"
                            bgClass="bg-blue-light hover:bg-blue/10"
                            tileClass="bg-blue"
                            textClass="text-blue"
                        />
                        <QuickButton
                            href="/club-reconocimientos/club-padron"
                            icon="fa-file-pdf"
                            label="Padrón Club"
                            bgClass="bg-amber-light hover:bg-amber/10"
                            tileClass="bg-amber"
                            textClass="text-amber"
                        />
                        <QuickButton
                            href="/productos-pecosas/productos-reporte/reparticion"
                            icon="fa-file-pdf"
                            label="Repartición"
                            bgClass="bg-teal-light hover:bg-teal/10"
                            tileClass="bg-teal"
                            textClass="text-teal"
                        />
                        <button
                            type="button"
                            onClick={() => onNavigate?.('productos')}
                            className="quick-btn flex flex-col items-center gap-1 p-2 sm:p-3 rounded-lg sm:rounded-xl bg-sky-light hover:bg-sky/10 transition-all group"
                        >
                            <div className="w-7 h-7 sm:w-8 sm:h-8 bg-sky rounded-md sm:rounded-lg flex items-center justify-center text-white text-xs sm:text-sm group-hover:scale-105 transition-all">
                                <i className="fas fa-box" />
                            </div>
                            <span className="text-[8px] sm:text-[10px] font-semibold text-sky text-center leading-tight">Pecosas</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
