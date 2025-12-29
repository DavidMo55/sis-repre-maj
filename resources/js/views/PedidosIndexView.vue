<template>
    <div class="content-wrapper">
        <div class="module-page">
            
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Resumen de Pedidos</h1>
                    <p>Pedidos generados por tu cuenta, ordenados por fecha reciente.</p>
                </div>
                <router-link to="/GenerarPedido" class="btn-primary flex-row-centered" active-class="active"> 
                    <i class="fas fa-plus"></i> Nuevo Pedido
                </router-link>
            </div>

            <div v-if="loading" class="loading-state mt-8">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>Cargando pedidos...</p>
            </div>

            <div v-else-if="error" class="error-message text-center py-4">
                Error al cargar pedidos: {{ error }}
            </div>

            <div v-else-if="pedidos.length === 0" class="cart-empty-message mt-8">
                <i class="fas fa-exclamation-triangle mb-2 text-xl"></i>
                <p class="mt-2">Aún no has generado ningún pedido. ¡Es hora de empezar!</p>
            </div>

            <div v-else class="table-responsive table-shadow-lg mt-8">
                <table class="min-width-full divide-y-gray-200">
                    <thead class="bg-light-gray">
                        <tr>
                            <th class="table-header"># Pedido</th>
                            <th class="table-header">Cliente</th>
                            <th class="table-header">Fecha</th>
                            <th class="table-header">Total Ítems</th>
                            <th class="table-header">Estado</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y-gray-200">
                        <tr v-for="pedido in pedidos" :key="pedido.id">
                            <!-- CAMBIO CRÍTICO: Usamos pedido.display_id (PEDDDMMYYID) -->
                            <td class="table-cell table-cell-bold text-dark-gray">{{ pedido.display_id }}</td>
                            
                            <td class="table-cell text-medium-gray">{{ pedido.cliente ? pedido.cliente.name : 'N/A' }}</td>
                            <td class="table-cell text-medium-gray">{{ formatDate(pedido.created_at) }}</td>
                            <td class="table-cell table-cell-bold text-dark-gray">{{ calculateTotalItems(pedido.detalles) }}</td>
                            <td class="table-cell">
                                <span :class="getStatusClass(pedido.status)" class="status-badge">
                                    {{ pedido.status }}
                                </span>
                            </td>
                            <td class="table-cell-action">
                                <router-link 
                                    :to="{ name: 'DetallePedido', params: { id: pedido.id } }" 
                                    class="text-red-link"
                                >
                                    Ver Detalle
                                </router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const pedidos = ref([]);
const loading = ref(false);
const error = ref(null);

const fetchPedidos = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/pedidos'); 
        
        const dataReceived = response.data.data || response.data;
        pedidos.value = Array.isArray(dataReceived) ? dataReceived : [];
        
    } catch (err) {
        error.value = 'No se pudieron cargar los pedidos. Intenta más tarde.';
        pedidos.value = []; 
        console.error('Error fetching pedidos:', err);
    } finally {
        loading.value = false;
    }
};
const goToNewOrder = () => {
    router.push('/pedidos/nuevo');
};

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
};

const calculateTotalItems = (detalles) => {
    if (!detalles) return 0;
    return detalles.reduce((sum, item) => sum + item.cantidad, 0);
};

const getStatusClass = (status) => {
    switch (status) {
        case 'ENTREGADO':
            return 'bg-green-100 text-green-800'; 
        case 'PENDIENTE':
            return 'bg-yellow-100 text-yellow-800';
        case 'EN PROCESO':
            return 'bg-blue-100 text-blue-800';
        case 'CANCELADO':
            return 'bg-red-100 text-red-800'; 
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const viewDetails = (pedido) => {
    alert(`Visualizando detalles del Pedido ID: ${pedido.display_id}`);
};

onMounted(() => {
    fetchPedidos();
});
</script>