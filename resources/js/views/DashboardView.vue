<template>
    <div class="dashboard-page">

        <!-- Header -->
        <div class="dashboard-header mb-8">
            <div class="header-accent"></div>
            <div>
                <h2 class="rojo">Bienvenido(a) al Sistema</h2>
                <p class="summary-text">Resumen de actividad · <span class="date-badge">{{ currentDate }}</span></p>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="kpi-card kpi-red">
                <div class="kpi-inner">
                    <div>
                        <p class="kpi-label">Prospectos Activos</p>
                        <p class="kpi-value">{{ loading ? '—' : (stats.prospectos || 0) }}</p>
                    </div>
                    <div class="kpi-icon"><i class="fas fa-user-tag"></i></div>
                </div>
                <div class="kpi-bar" style="width: 70%"></div>
            </div>
            <div class="kpi-card kpi-green">
                <div class="kpi-inner">
                    <div>
                        <p class="kpi-label">Clientes / Dist.</p>
                        <p class="kpi-value">{{ loading ? '—' : (stats.clientes || 0) }}</p>
                    </div>
                    <div class="kpi-icon"><i class="fas fa-handshake"></i></div>
                </div>
                <div class="kpi-bar" style="width: 55%"></div>
            </div>
            <div class="kpi-card kpi-orange">
                <div class="kpi-inner">
                    <div>
                        <p class="kpi-label">Pedidos Pendientes</p>
                        <p class="kpi-value">{{ loading ? '—' : (stats.pedidos_pendientes || 0) }}</p>
                    </div>
                    <div class="kpi-icon"><i class="fas fa-truck"></i></div>
                </div>
                <div class="kpi-bar" style="width: 40%"></div>
            </div>
            <div class="kpi-card kpi-purple">
                <div class="kpi-inner">
                    <div>
                        <p class="kpi-label">Próxima Visita</p>
                        <div v-if="!loading">
                            <p v-if="stats.proxima_visita" class="kpi-value-sm">{{ stats.proxima_visita.plantel }}</p>
                            <p v-if="stats.proxima_visita" class="kpi-date"><i class="far fa-calendar-alt"></i> {{ stats.proxima_visita.fecha }}</p>
                            <p v-else class="kpi-empty">Sin visitas</p>
                        </div>
                        <p v-else class="kpi-value">—</p>
                    </div>
                    <div class="kpi-icon"><i class="fas fa-calendar-day"></i></div>
                </div>
                <div class="kpi-bar" style="width: 85%"></div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <!-- Bar Chart: Visitas por semana -->
            <div class="chart-card lg:col-span-2">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">Visitas por Semana</h3>
                        <p class="chart-sub">Actividad de campo semanal</p>
                    </div>
                    <div class="chart-badge badge-red">Este Mes</div>
                </div>
                <div class="chart-body" style="height:240px">
                    <Bar v-if="hasChartData" :data="chartData" :options="barOptions" />
                    <div v-else class="no-data">No hay datos para graficar</div>
                </div>
            </div>

            <!-- Doughnut Chart: Distribución tipo contacto -->
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">Tipo de Contacto</h3>
                        <p class="chart-sub">Distribución actual</p>
                    </div>
                    <div class="chart-badge badge-blue">Global</div>
                </div>
                <div class="chart-body" style="height:240px; position:relative">
                    <Doughnut :data="doughnutData" :options="doughnutOptions" />
                    <div class="doughnut-center">
                        <span class="doughnut-total">{{ (stats.prospectos || 0) + (stats.clientes || 0) }}</span>
                        <span class="doughnut-label">Total</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <!-- Line Chart: Tendencia mensual -->
            <div class="chart-card lg:col-span-2">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">Tendencia de Visitas</h3>
                        <p class="chart-sub">Evolución en los últimos 6 meses</p>
                    </div>
                    <div class="chart-badge badge-green">Histórico</div>
                </div>
                <div class="chart-body" style="height:220px">
                    <Line :data="lineData" :options="lineOptions" />
                </div>
            </div>

            <!-- Resumen mensual stats -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Resumen Mensual</h3>
                </div>
                <div class="stat-list">
                    <div class="stat-row stat-row-red">
                        <div class="stat-icon"><i class="fas fa-school"></i></div>
                        <div class="stat-text">
                            <p class="stat-label">Total Visitas</p>
                            <p class="stat-val">{{ stats.visitas_totales || 0 }}</p>
                        </div>
                    </div>
                    <div class="stat-row stat-row-green">
                        <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                        <div class="stat-text">
                            <p class="stat-label">Nuevos Prospectos</p>
                            <p class="stat-val">{{ stats.prospectos_nuevos || 0 }}</p>
                        </div>
                    </div>
                    <div class="stat-divider"></div>
                    <div v-if="stats.top_escuelas && stats.top_escuelas.length > 0" class="top-school">
                        <p class="top-school-label"><i class="fas fa-trophy"></i> Escuela más concurrida</p>
                        <p class="top-school-name">{{ stats.top_escuelas[0].nombre }}</p>
                        <p class="top-school-count">{{ stats.top_escuelas[0].conteo }} visitas este mes</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 3 -->
        <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

            <!-- Horizontal Bar: Visitas por escuela (top 5) -->
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">Top Escuelas</h3>
                        <p class="chart-sub">Más visitadas del mes</p>
                    </div>
                    <div class="chart-badge badge-purple">Top 5</div>
                </div>
                <div class="chart-body" style="height:220px">
                    <Bar :data="horizontalBarData" :options="horizontalBarOptions" />
                </div>
            </div>

            
        </div>

        <!-- Table: Detalle por escuela -->
        <div v-if="!loading" class="chart-card mb-8">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Detalle de Impacto por Escuela</h3>
                    <p class="chart-sub">Registro completo de visitas realizadas</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Escuela / Plantel</th>
                            <th class="text-center">Visitas Realizadas</th>
                            <th>Progreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in stats.reporte_tabla" :key="index">
                            <td class="row-num">{{ index + 1 }}</td>
                            <td class="row-name">{{ item.nombre }}</td>
                            <td class="text-center"><span class="visit-badge">{{ item.visitas }}</span></td>
                            <td>
                                <div class="progress-track">
                                    <div class="progress-fill" :style="{ width: getProgress(item.visitas) + '%' }"></div>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!stats.reporte_tabla || stats.reporte_tabla.length === 0">
                            <td colspan="4" class="no-rows">No hay datos de actividad para mostrar.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="error-box">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <p class="error-title">Error de sincronización</p>
                <p class="error-msg">{{ error }}</p>
            </div>
            <button @click="fetchDashboardStats" class="btn-retry"><i class="fas fa-sync-alt"></i> Reintentar</button>
        </div>

        <!-- Quick Actions -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Acciones Rápidas</h3>
            </div>
            <div class="quick-grid">
                <router-link to="/primeras-visitas" class="quick-btn quick-btn-red">
                    <i class="fas fa-plus-circle"></i>
                    <span>Nueva Visita</span>
                </router-link>
                <router-link to="/GenerarPedido" class="quick-btn quick-btn-green">
                    <i class="fas fa-cart-plus"></i>
                    <span>Ingresar Pedido</span>
                </router-link>
                <router-link to="/gastos" class="quick-btn quick-btn-blue">
                    <i class="fas fa-receipt"></i>
                    <span>Gestionar Gastos</span>
                </router-link>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from '../axios';
import { Bar, Line, Doughnut, Radar } from 'vue-chartjs';
import {
    Chart as ChartJS, Title, Tooltip, Legend,
    BarElement, CategoryScale, LinearScale,
    PointElement, LineElement, ArcElement,
    RadialLinearScale, Filler
} from 'chart.js';

ChartJS.register(
    Title, Tooltip, Legend,
    BarElement, CategoryScale, LinearScale,
    PointElement, LineElement, ArcElement,
    RadialLinearScale, Filler
);

const loading = ref(true);
const error = ref(null);

const currentDate = computed(() => {
    return new Date().toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

const stats = ref({
    prospectos: 0, clientes: 0, pedidos_pendientes: 0,
    proxima_visita: null, visitas_totales: 0, prospectos_nuevos: 0,
    top_escuelas: [], reporte_tabla: [],
    grafico_visitas: { labels: [], values: [] }
});

// ── Bar Chart (Visitas por semana) ────────────────────────────────────────────
const hasChartData = computed(() => stats.value.grafico_visitas?.labels?.length > 0);

const chartData = computed(() => ({
    labels: stats.value.grafico_visitas?.labels || [],
    datasets: [{
        label: 'Visitas',
        backgroundColor: (ctx) => {
            const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 250);
            g.addColorStop(0, 'rgba(239,68,68,0.85)');
            g.addColorStop(1, 'rgba(239,68,68,0.2)');
            return g;
        },
        borderRadius: 8,
        borderSkipped: false,
        data: stats.value.grafico_visitas?.values || []
    }]
}));

const barOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e293b', padding: 10, cornerRadius: 8 } },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
        y: { grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { color: '#94a3b8', font: { size: 11 } } }
    }
};

// ── Doughnut Chart ────────────────────────────────────────────────────────────
const doughnutData = computed(() => ({
    labels: ['Prospectos', 'Clientes/Dist.', 'Pedidos'],
    datasets: [{
        data: [stats.value.prospectos || 0, stats.value.clientes || 0, stats.value.pedidos_pendientes || 0],
        backgroundColor: ['#ef4444', '#22c55e', '#f97316'],
        hoverBackgroundColor: ['#dc2626', '#16a34a', '#ea580c'],
        borderWidth: 0,
        hoverOffset: 6
    }]
}));

const doughnutOptions = {
    responsive: true, maintainAspectRatio: false,
    cutout: '70%',
    plugins: {
        legend: { position: 'bottom', labels: { color: '#64748b', font: { size: 11 }, padding: 12, usePointStyle: true } },
        tooltip: { backgroundColor: '#1e293b', padding: 10, cornerRadius: 8 }
    }
};

// ── Line Chart (Tendencia) ────────────────────────────────────────────────────
const lineData = computed(() => ({
    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
    datasets: [
        {
            label: 'Visitas',
            data: [12, 19, 15, 28, 22, stats.value.visitas_totales || 30],
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34,197,94,0.1)',
            fill: true, tension: 0.4,
            pointBackgroundColor: '#22c55e',
            pointRadius: 4, pointHoverRadius: 6,
            borderWidth: 2
        },
        {
            label: 'Prospectos',
            data: [5, 8, 6, 12, 9, stats.value.prospectos_nuevos || 14],
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239,68,68,0.05)',
            fill: true, tension: 0.4,
            pointBackgroundColor: '#ef4444',
            pointRadius: 4, pointHoverRadius: 6,
            borderWidth: 2
        }
    ]
}));

const lineOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top', labels: { color: '#64748b', font: { size: 11 }, usePointStyle: true, padding: 16 } },
        tooltip: { backgroundColor: '#1e293b', padding: 10, cornerRadius: 8 }
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
        y: { grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { color: '#94a3b8', font: { size: 11 } } }
    }
};

// ── Horizontal Bar Chart (Top Escuelas) ───────────────────────────────────────
const horizontalBarData = computed(() => {
    const escuelas = stats.value.top_escuelas?.slice(0, 5) || [];
    return {
        labels: escuelas.map(e => e.nombre?.split(' ').slice(0, 3).join(' ') || 'Escuela'),
        datasets: [{
            label: 'Visitas',
            data: escuelas.map(e => e.conteo || 0),
            backgroundColor: ['#8b5cf6','#a78bfa','#c4b5fd','#ddd6fe','#ede9fe'],
            borderRadius: 6,
            borderSkipped: false
        }]
    };
});

const horizontalBarOptions = {
    indexAxis: 'y',
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e293b', padding: 10, cornerRadius: 8 } },
    scales: {
        x: { grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { color: '#94a3b8', font: { size: 11 } } },
        y: { grid: { display: false }, ticks: { color: '#475569', font: { size: 11, weight: '500' } } }
    }
};

// ── Radar Chart ───────────────────────────────────────────────────────────────
const radarData = computed(() => ({
    labels: ['Visitas', 'Prospectos', 'Clientes', 'Pedidos', 'Cobertura', 'Eficiencia'],
    datasets: [{
        label: 'Este Mes',
        data: [
            Math.min(100, (stats.value.visitas_totales || 0) * 3),
            Math.min(100, (stats.value.prospectos_nuevos || 0) * 5),
            Math.min(100, (stats.value.clientes || 0) * 4),
            Math.min(100, (stats.value.pedidos_pendientes || 0) * 6),
            72, 65
        ],
        backgroundColor: 'rgba(249,115,22,0.15)',
        borderColor: '#f97316',
        pointBackgroundColor: '#f97316',
        pointRadius: 4, borderWidth: 2
    }]
}));

const radarOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: '#1e293b', padding: 10, cornerRadius: 8 }
    },
    scales: {
        r: {
            grid: { color: 'rgba(148,163,184,0.15)' },
            angleLines: { color: 'rgba(148,163,184,0.15)' },
            ticks: { display: false },
            pointLabels: { color: '#64748b', font: { size: 10, weight: '600' } }
        }
    }
};

// ── Table progress helper ─────────────────────────────────────────────────────
const getProgress = (visitas) => {
    const max = Math.max(...(stats.value.reporte_tabla?.map(i => i.visitas) || [1]));
    return max > 0 ? Math.round((visitas / max) * 100) : 0;
};

// ── Fetch ─────────────────────────────────────────────────────────────────────
const fetchDashboardStats = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/dashboard/stats');
        stats.value = { ...stats.value, ...response.data };
    } catch (err) {
        error.value = err.response?.data?.message || "Error al cargar datos.";
    } finally {
        loading.value = false;
    }
};

onMounted(fetchDashboardStats);
</script>

<style scoped>
/* ── Base ─────────────────────────────────────────────────────────── */
.dashboard-page { padding: 24px; background: hsl(357, 54%, 43%, 10%); min-height: 100vh; font-family: 'DM Sans', sans-serif; }

/* ── Header ──────────────────────────────────────────────────────── */
.dashboard-header { display: flex; align-items: center; gap: 16px; }
.header-accent { width: 5px; height: 48px; background: linear-gradient(180deg, #ef4444, #f97316); border-radius: 4px; flex-shrink: 0; }
.dashboard-header h2 { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.summary-text { color: #64748b; font-size: 0.85rem; margin-top: 2px; }
.date-badge { background: #f1f5f9; padding: 2px 8px; border-radius: 99px; font-size: 0.75rem; font-weight: 600; color: #475569; }

/* ── KPI Cards ───────────────────────────────────────────────────── */
.kpi-card { border-radius: 16px; background: #fff; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; overflow: hidden; position: relative; transition: transform 0.2s ease, box-shadow 0.2s ease; }
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.kpi-inner { display: flex; justify-content: space-between; align-items: flex-start; }
.kpi-label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; margin-bottom: 4px; }
.kpi-value { font-size: 2.2rem; font-weight: 900; color: #0f172a; line-height: 1; }
.kpi-value-sm { font-size: 1rem; font-weight: 700; color: #0f172a; margin-top: 4px; }
.kpi-date { font-size: 0.7rem; color: #8b5cf6; font-weight: 600; margin-top: 2px; }
.kpi-empty { font-size: 0.8rem; color: #cbd5e1; font-style: italic; margin-top: 6px; }
.kpi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.kpi-bar { position: absolute; bottom: 0; left: 0; height: 3px; border-radius: 0 2px 2px 0; transition: width 1s ease; }

.kpi-red .kpi-icon { background: #fef2f2; color: #ef4444; }
.kpi-red .kpi-bar { background: linear-gradient(90deg, #ef4444, #f87171); }
.kpi-green .kpi-icon { background: #f0fdf4; color: #22c55e; }
.kpi-green .kpi-bar { background: linear-gradient(90deg, #22c55e, #4ade80); }
.kpi-orange .kpi-icon { background: #fff7ed; color: #f97316; }
.kpi-orange .kpi-bar { background: linear-gradient(90deg, #f97316, #fb923c); }
.kpi-purple .kpi-icon { background: #f5f3ff; color: #8b5cf6; }
.kpi-purple .kpi-bar { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }

/* ── Chart Cards ─────────────────────────────────────────────────── */
.chart-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; }
.chart-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 20px 20px 0; }
.chart-title { font-size: 0.85rem; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: .05em; }
.chart-sub { font-size: 0.72rem; color: #94a3b8; margin-top: 2px; }
.chart-body { padding: 16px 20px 20px; }
.chart-badge { font-size: 0.65rem; font-weight: 700; padding: 3px 10px; border-radius: 99px; }
.badge-red { background: #fef2f2; color: #ef4444; }
.badge-blue { background: #eff6ff; color: #3b82f6; }
.badge-green { background: #f0fdf4; color: #22c55e; }
.badge-purple { background: #f5f3ff; color: #8b5cf6; }
.badge-orange { background: #fff7ed; color: #f97316; }
.no-data { display: flex; align-items: center; justify-content: center; height: 100%; color: #cbd5e1; font-style: italic; font-size: 0.85rem; }

/* ── Doughnut center ─────────────────────────────────────────────── */
.doughnut-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -58%); text-align: center; pointer-events: none; }
.doughnut-total { display: block; font-size: 1.8rem; font-weight: 900; color: #0f172a; line-height: 1; }
.doughnut-label { display: block; font-size: 0.65rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: .08em; margin-top: 2px; }

/* ── Stat list ───────────────────────────────────────────────────── */
.stat-list { padding: 16px 20px 20px; display: flex; flex-direction: column; gap: 16px; }
.stat-row { display: flex; align-items: center; gap: 14px; }
.stat-icon { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.stat-row-red .stat-icon { background: #fef2f2; color: #ef4444; }
.stat-row-green .stat-icon { background: #f0fdf4; color: #22c55e; }
.stat-label { font-size: 0.65rem; text-transform: uppercase; font-weight: 700; color: #94a3b8; letter-spacing: .06em; }
.stat-val { font-size: 1.8rem; font-weight: 900; color: #0f172a; line-height: 1.1; }
.stat-divider { height: 1px; background: #f1f5f9; }
.top-school { background: linear-gradient(135deg, #f5f3ff, #ede9fe); border: 1px solid #e9d5ff; border-radius: 12px; padding: 14px; }
.top-school-label { font-size: 0.65rem; text-transform: uppercase; font-weight: 800; color: #7c3aed; letter-spacing: .08em; margin-bottom: 4px; }
.top-school-name { font-size: 0.95rem; font-weight: 700; color: #4c1d95; }
.top-school-count { font-size: 0.72rem; color: #7c3aed; font-weight: 600; }

/* ── Table ───────────────────────────────────────────────────────── */
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; font-size: 0.82rem; border-collapse: collapse; }
.data-table thead tr { background: #f8fafc; border-bottom: 2px solid #f1f5f9; }
.data-table th { padding: 10px 20px; font-size: 0.65rem; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; font-weight: 700; white-space: nowrap; }
.data-table tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.15s; }
.data-table tbody tr:hover { background: #f8fafc; }
.row-num { padding: 12px 20px; color: #cbd5e1; font-weight: 600; }
.row-name { padding: 12px 20px; font-weight: 600; color: #334155; }
.visit-badge { background: #eff6ff; color: #2563eb; font-weight: 800; font-size: 0.78rem; padding: 3px 10px; border-radius: 8px; }
.progress-track { background: #f1f5f9; border-radius: 99px; height: 6px; width: 120px; overflow: hidden; }
.progress-fill { background: linear-gradient(90deg, #3b82f6, #60a5fa); height: 100%; border-radius: 99px; transition: width 0.8s ease; }
.no-rows { padding: 32px; text-align: center; color: #cbd5e1; font-style: italic; }

/* ── Error ───────────────────────────────────────────────────────── */
.error-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
.error-box i { color: #ef4444; font-size: 1.2rem; }
.error-title { font-weight: 700; color: #991b1b; font-size: 0.85rem; }
.error-msg { color: #dc2626; font-size: 0.75rem; }
.btn-retry { margin-left: auto; background: #fff; border: 1px solid #fca5a5; color: #ef4444; border-radius: 8px; padding: 6px 14px; font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
.btn-retry:hover { background: #fef2f2; }

/* ── Quick Actions ───────────────────────────────────────────────── */
.quick-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; padding: 16px 20px 20px; }
.quick-btn { display: flex; align-items: center; gap: 12px; padding: 16px; border-radius: 12px; font-weight: 700; font-size: 0.88rem; text-decoration: none; transition: transform 0.15s ease, box-shadow 0.15s ease; border: 1px solid transparent; }
.quick-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); }
.quick-btn i { font-size: 1.3rem; }
.quick-btn-red { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
.quick-btn-green { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
.quick-btn-blue { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }

@media (max-width: 640px) {
    .quick-grid { grid-template-columns: 1fr; }
    .dashboard-page { padding: 16px; }
}


</style>