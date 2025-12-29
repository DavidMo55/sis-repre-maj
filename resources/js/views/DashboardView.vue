<template>
    <div class="dashboard-page">
        <div class="dashboard-header mb-6">
            <h2>Bienvenido(a) al Sistema de Representantes</h2>
            <p class="summary-text text-gray-500">Resumen de tu actividad reciente y próximos pasos.</p>
        </div>

        <!-- KPI GRID -->
        <div class="kpi-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Prospectos -->
            <div class="kpi-card shadow-sm border border-gray-100 p-6 rounded-xl bg-white relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="kpi-title text-gray-500 font-semibold uppercase text-xs tracking-wider">Prospectos Activos</p>
                        <p class="kpi-value text-3xl font-black mt-1">{{ loading ? '...' : (stats.prospectos || 0) }}</p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg">
                        <i class="fas fa-user-tag text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Clientes -->
            <div class="kpi-card shadow-sm border border-gray-100 p-6 rounded-xl bg-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="kpi-title text-gray-500 font-semibold uppercase text-xs tracking-wider">Clientes / Dist.</p>
                        <p class="kpi-value text-3xl font-black mt-1">{{ loading ? '...' : (stats.clientes || 0) }}</p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <i class="fas fa-handshake text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Pedidos Pendientes -->
            <div class="kpi-card shadow-sm border border-gray-100 p-6 rounded-xl bg-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="kpi-title text-gray-500 font-semibold uppercase text-xs tracking-wider">Pedidos Pendientes</p>
                        <p class="kpi-value text-3xl font-black mt-1">{{ loading ? '...' : (stats.pedidos_pendientes || 0) }}</p>
                    </div>
                    <div class="p-3 bg-orange-50 rounded-lg">
                        <i class="fas fa-truck text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Próxima Visita -->
            <div class="kpi-card shadow-sm border border-gray-100 p-6 rounded-xl bg-white bg-gradient-to-br from-white to-purple-50">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="kpi-title text-gray-500 font-semibold uppercase text-xs tracking-wider">Próxima Visita</p>
                        <div v-if="!loading">
                            <p v-if="stats.proxima_visita" class="kpi-value-small font-bold text-purple-700 mt-1">
                                {{ stats.proxima_visita.plantel }}
                            </p>
                            <p v-if="stats.proxima_visita" class="text-xs text-purple-500 font-semibold">
                                <i class="far fa-calendar-alt"></i> {{ stats.proxima_visita.fecha }}
                            </p>
                            <p v-else class="text-sm text-gray-400 mt-2 italic">Sin visitas agendadas</p>
                        </div>
                        <p v-else class="mt-1">...</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-calendar-day text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE ERROR -->
        <div v-if="error" class="error-container mb-8 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                <div>
                    <p class="text-red-800 font-bold">Error de sincronización</p>
                    <p class="text-red-600 text-sm">{{ error }}</p>
                </div>
            </div>
            <button @click="fetchDashboardStats" class="btn-secondary text-sm py-2">
                <i class="fas fa-sync-alt"></i> Reintentar
            </button>
        </div>

        <!-- ACCESOS RÁPIDOS -->
        <div class="module-page mt-8">
            <h3 class="text-lg font-bold mb-4 text-gray-800 border-b pb-2">Acciones Rápidas</h3>
            <div class="quick-links grid grid-cols-1 md:grid-cols-3 gap-4">
                <router-link to="/primeras-visitas" class="quick-link-btn">
                    <div class="flex items-center gap-4 p-4 rounded-lg bg-red-50 hover:bg-red-100 transition-colors border border-red-100">
                        <i class="fas fa-plus-circle text-red-600 text-2xl"></i>
                        <div>
                            <span class="block font-bold text-red-900">Nueva Visita</span>
                        </div>
                    </div>
                </router-link>

                <router-link to="/GenerarPedido" class="quick-link-btn">
                    <div class="flex items-center gap-4 p-4 rounded-lg bg-green-50 hover:bg-green-100 transition-colors border border-green-100">
                        <i class="fas fa-cart-plus text-green-600 text-2xl"></i>
                        <div>
                            <span class="block font-bold text-green-900">Ingresar Pedido</span>
                        </div>
                    </div>
                </router-link>

                <router-link to="/gastos" class="quick-link-btn">
                    <div class="flex items-center gap-4 p-4 rounded-lg bg-blue-50 hover:bg-blue-100 transition-colors border border-blue-100">
                        <i class="fas fa-receipt text-blue-600 text-2xl"></i>
                        <div>
                            <span class="block font-bold text-blue-900">Gestionar Gastos</span>
                        </div>
                    </div>
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from '../axios';

const loading = ref(true);
const error = ref(null);
const stats = ref({
    prospectos: 0,
    clientes: 0,
    pedidos_pendientes: 0,
    proxima_visita: null
});

const fetchDashboardStats = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/dashboard/stats');
        stats.value = response.data;
    } catch (err) {
        console.error("Error cargando dashboard:", err);
        // Intentamos extraer el mensaje de error de Laravel si existe
        error.value = err.response?.data?.message || "No se pudieron obtener las estadísticas. Revisa los logs del servidor.";
    } finally {
        loading.value = false;
    }
};

onMounted(fetchDashboardStats);
</script>

<style scoped>
.kpi-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}
.quick-link-btn {
    text-decoration: none;
}
.error-container {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>