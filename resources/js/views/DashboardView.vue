<template>
    <div class="dashboard-page">
        <div class="dashboard-header mb-6">
            <h2>Bienvenido(a) al Sistema de Representantes</h2>
            <p class="summary-text text-gray-500">Resumen de tu actividad reciente y próximos pasos.</p>
        </div>

        <div class="kpi-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="kpi-card shadow-sm border border-gray-100 p-6 rounded-xl bg-white relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="kpi-title text-gray-500 font-semibold uppercase text-xs tracking-wider">Prospectos Activos</p>
                        <p class="kpi-value text-3xl font-black mt-1">{{ loading ? '...' : (stats.prospectos || 0) }}</p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg"><i class="fas fa-user-tag text-red-600 text-xl"></i></div>
                </div>
            </div>

            <div class="kpi-card shadow-sm border border-gray-100 p-6 rounded-xl bg-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="kpi-title text-gray-500 font-semibold uppercase text-xs tracking-wider">Clientes / Dist.</p>
                        <p class="kpi-value text-3xl font-black mt-1">{{ loading ? '...' : (stats.clientes || 0) }}</p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg"><i class="fas fa-handshake text-green-600 text-xl"></i></div>
                </div>
            </div>

            <div class="kpi-card shadow-sm border border-gray-100 p-6 rounded-xl bg-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="kpi-title text-gray-500 font-semibold uppercase text-xs tracking-wider">Pedidos Pendientes</p>
                        <p class="kpi-value text-3xl font-black mt-1">{{ loading ? '...' : (stats.pedidos_pendientes || 0) }}</p>
                    </div>
                    <div class="p-3 bg-orange-50 rounded-lg"><i class="fas fa-truck text-orange-600 text-xl"></i></div>
                </div>
            </div>

            <div class="kpi-card shadow-sm border border-gray-100 p-6 rounded-xl bg-white bg-gradient-to-br from-white to-purple-50">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="kpi-title text-gray-500 font-semibold uppercase text-xs tracking-wider">Próxima Visita</p>
                        <div v-if="!loading">
                            <p v-if="stats.proxima_visita" class="kpi-value-small font-bold text-purple-700 mt-1">{{ stats.proxima_visita.plantel }}</p>
                            <p v-if="stats.proxima_visita" class="text-xs text-purple-500 font-semibold"><i class="far fa-calendar-alt"></i> {{ stats.proxima_visita.fecha }}</p>
                            <p v-else class="text-sm text-gray-400 mt-2 italic">Sin visitas</p>
                        </div>
                        <p v-else class="mt-1">...</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-lg"><i class="fas fa-calendar-day text-purple-600 text-xl"></i></div>
                </div>
            </div>
        </div>

        <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="module-page  bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Visitas por Semana</h3>
                    <div style="height: 250px;">
                        <Bar v-if="hasChartData" :data="chartData" :options="chartOptions" />
                        <div v-else class="flex items-center justify-center h-full text-gray-400 italic">No hay datos para graficar</div>
                    </div>
                </div>
                <br>
                <br>
                <div class="module-page bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-50 bg-gray-50/50">
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Detalle de Impacto por Escuela</h3>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-gray-400 uppercase text-[10px] tracking-widest border-b">
                                <th class="px-6 py-3 font-semibold">Escuela / Plantel</th>
                                <th class="px-6 py-3 font-semibold text-center">Visitas Realizadas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(item, index) in stats.reporte_tabla" :key="index" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-700">{{ item.nombre }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded-md font-bold">{{ item.visitas }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                </td>
                            </tr>
                            <tr v-if="!stats.reporte_tabla || stats.reporte_tabla.length === 0">
                                <td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">No hay datos de actividad para mostrar en la tabla.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <br>
            <div class=" module-page bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <h3 class="text-sm font-bold text-gray-700 mb-6 uppercase tracking-wider">Resumen Mensual</h3>
                
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                            <i class="fas fa-school text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-bold">Total Visitas</p>
                            <p class="text-2xl font-black text-gray-800">{{ stats.visitas_totales || 0 }}</p>
                            
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                            <i class="fas fa-user-plus text-xl"></i>
                        </div>
                        <div>
                             <p class="text-xs text-gray-500 uppercase font-bold">Nuevos Prospectos</p>
                            <p class="text-2xl font-black text-gray-800">{{ stats.prospectos_nuevos || 0 }}</p>
                           
                        </div>
                    </div>

                    <hr class="border-gray-50">

                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Escuela más concurrida</p>
                        <div v-if="stats.top_escuelas && stats.top_escuelas.length > 0" class="p-4 bg-purple-50 rounded-xl border border-purple-100">
                            <p class="font-bold text-purple-900">{{ stats.top_escuelas[0].nombre }}</p>
                            <p class="text-xs text-purple-600">{{ stats.top_escuelas[0].conteo }} visitas este mes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>

        <div v-if="error" class="error-container mb-8 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                <div>
                    <p class="text-red-800 font-bold">Error de sincronización</p>
                    <p class="text-red-600 text-sm">{{ error }}</p>
                </div>
            </div>
            <button @click="fetchDashboardStats" class="btn-secondary text-sm py-2"><i class="fas fa-sync-alt"></i> Reintentar</button>
        </div>

        <div class="module-page mt-8">
            <h3 class="text-lg font-bold mb-4 text-gray-800 border-b pb-2">Acciones Rápidas</h3>
            <div class="quick-links grid grid-cols-1 md:grid-cols-3 gap-4">
                <router-link to="/primeras-visitas" class="quick-link-btn cl3">
                    <div class="flex items-center gap-4 p-4 rounded-lg bg-red-50 hover:bg-red-100 transition-colors border border-red-100">
                        <i class="fas fa-plus-circle text-red-600 text-2xl"></i>
                        <div><span class="block font-bold text-red-900">Nueva Visita</span></div>
                    </div>
                </router-link>
                <router-link to="/GenerarPedido" class="quick-link-btn cl2">
                    <div class="flex items-center gap-4 p-4 rounded-lg bg-green-50 hover:bg-green-100 transition-colors border border-green-100">
                        <i class="fas fa-cart-plus text-green-600 text-2xl"></i>
                        <div><span class="block font-bold text-green-900">Ingresar Pedido</span></div>
                    </div>
                </router-link>
                <router-link to="/gastos" class="quick-link-btn cl1">
                    <div class="flex items-center gap-4 p-4 rounded-lg bg-blue-50 hover:bg-blue-100 transition-colors border border-blue-100">
                        <i class="fas fa-receipt text-blue-600 text-2xl"></i>
                        <div><span class="block font-bold text-blue-900">Gestionar Gastos</span></div>
                    </div>
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from '../axios';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const loading = ref(true);
const error = ref(null);
const stats = ref({
    prospectos: 0,
    clientes: 0,
    pedidos_pendientes: 0,
    proxima_visita: null,
    visitas_totales: 0,
    prospectos_nuevos: 0,
    top_escuelas: [],
    reporte_tabla: [], // Datos para la nueva tabla
    grafico_visitas: { labels: [], values: [] }
});

const hasChartData = computed(() => stats.value.grafico_visitas?.labels?.length > 0);

const chartData = computed(() => ({
    labels: stats.value.grafico_visitas?.labels || [],
    datasets: [{
        label: 'Visitas',
        backgroundColor: '#ef4444',
        borderRadius: 4,
        data: stats.value.grafico_visitas?.values || []
    }]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } }
};

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
.kpi-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
.quick-link-btn { text-decoration: none; }
</style>