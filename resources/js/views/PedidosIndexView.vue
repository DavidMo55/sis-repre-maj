<template>
    <div class="content-wrapper">
        <div class="module-page">
            
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Resumen de Pedidos</h1>
                    <p>Pedidos generados por tu cuenta, ordenados por fecha reciente.</p>
                </div>
                <router-link to="/GenerarPedido" class="btn-primary flex-row-centered gap-2"> 
                    <i class="fas fa-plus"></i> Nuevo Pedido
                </router-link>
            </div>

            <!-- MENSAJE DE ERROR DETALLADO -->
            <div v-if="error" class="error-message p-4 mb-4 bg-red-50 border border-red-200 rounded-lg text-red-700 animate-fade-in">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle mt-1"></i>
                    <div>
                        <p class="font-bold">Error al cargar pedidos</p>
                        <p class="text-sm">{{ error }}</p>
                        <p v-if="errorDetail" class="text-xs mt-2 p-2 bg-red-100 rounded font-mono break-all">
                            <strong>Detalle del Servidor:</strong> {{ errorDetail }}
                        </p>
                        <button @click="fetchPedidos" class="mt-3 text-xs font-bold uppercase tracking-widest hover:underline">
                            <i class="fas fa-sync-alt"></i> Reintentar conexión
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="loading" class="loading-state mt-8 text-center py-10">
                <i class="fas fa-spinner fa-spin text-3xl mb-2 text-red-600"></i>
                <p class="text-gray-500 font-medium">Consultando base de datos...</p>
            </div>

            <div v-else-if="pedidos.length === 0 && !error" class="cart-empty-message mt-8 text-center py-10 bg-gray-50 rounded-xl border-2 border-dashed">
                <i class="fas fa-box-open mb-3 text-4xl text-gray-300"></i>
                <p class="text-gray-500">Aún no has generado ningún pedido.</p>
                <router-link to="/pedidos/nuevo" class="text-red-600 font-bold hover:underline mt-2 inline-block">¡Crear mi primer pedido ahora!</router-link>
            </div>

            <div v-else-if="pedidos.length > 0" class="table-responsive table-shadow-lg mt-8 border rounded-xl overflow-hidden shadow-sm">
                <div class="table-responsive table-shadow-lg mt-8 border rounded-xl overflow-hidden shadow-sm">
    <table class="min-width-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
            <tr>
                <th class="table-header w-24"># Pedido</th>
                <th class="table-header">Cliente</th>
                <th class="table-header text-center">Tipo</th>
                <th class="table-header text-center">Prioridad</th>
                <th class="table-header">Fecha</th>
                <th class="table-header text-center">Ítems</th>
                <th class="table-header">Estado</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
            <tr v-for="pedido in pedidos" :key="pedido.id" class="hover:bg-gray-50 transition-colors">
                
                <td class="table-cell font-bold text-red-800">
                    {{ pedido.display_id || pedido.numero_referencia || ('PED-' + pedido.id) }}
                </td>
                
                <td class="table-cell">
                    <div class="text-sm font-bold text-gray-800 text-truncate max-w-cliente" :title="pedido.cliente?.name">
                        {{ pedido.cliente?.name || 'Cliente no encontrado' }}
                    </div>
                    <div class="text-[10px] text-gray-400 uppercase tracking-tighter">
                        {{ pedido.cliente?.tipo || 'N/A' }}
                    </div>
                </td>

                <td class="table-cell text-center">
                    <span class="type-badge" 
                          :class="pedido.tipo_pedido === 'promocion' ? 'badge-purple' : 'badge-blue'">
                        {{ (pedido.tipo_pedido || 'normal').toUpperCase() }}
                    </span>
                </td>

                <td class="table-cell text-center">
                    <span class="priority-badge" :class="getPriorityBadgeClass(pedido.prioridad)">
                        {{ (pedido.prioridad || 'media').toUpperCase() }}
                    </span>
                </td>

                <td class="table-cell text-gray-500 text-xs italic">
                    {{ formatDate(pedido.created_at) }}
                </td>

                <td class="table-cell text-center font-bold text-gray-700">
                    {{ calculateTotalItems(pedido.detalles) }}
                </td>

                <td class="table-cell">
                    <span class="status-badge" :class="getStatusClass(pedido.status)">
                        <i class="fas fa-circle text-[6px] mr-2"></i>
                        {{ pedido.status.toUpperCase() }}
                    </span>
                </td>

                <td class="table-cell-action text-right">
                    <router-link 
                        :to="{ name: 'DetallePedido', params: { id: pedido.id } }" 
                        class="text-red-link"
                    >
                        DETALLES <i class="fas fa-chevron-right ml-1"></i>
                    </router-link>
                </td>
            </tr>
        </tbody>
    </table>
</div>
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
const errorDetail = ref(null);

const fetchPedidos = async () => {
    loading.value = true;
    error.value = null;
    errorDetail.value = null;
    try {
        const response = await axios.get('/pedidos'); 
        
        // Laravel paginate() devuelve la data en response.data.data
        const dataReceived = response.data.data || response.data;
        pedidos.value = Array.isArray(dataReceived) ? dataReceived : [];
        
    } catch (err) {
        error.value = 'No se pudo obtener el listado de pedidos.';
        // Extraemos el error_detail enviado por nuestro controlador para facilitar el debugeo
        errorDetail.value = err.response?.data?.error_detail || err.response?.data?.message || err.message;
        console.error('Error fetching pedidos:', err);
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
};

const calculateTotalItems = (detalles) => {
    if (!detalles || !Array.isArray(detalles)) return 0;
    return detalles.reduce((sum, item) => sum + (parseInt(item.cantidad) || 0), 0);
};

const getStatusClass = (status) => {
    switch (status) {
        case 'ENTREGADO': return 'bg-green-100 text-green-800 border border-green-200'; 
        case 'PENDIENTE': return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
        case 'EN PROCESO': return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'CANCELADO': return 'bg-red-100 text-red-800 border border-red-200'; 
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getPriorityBadgeClass = (priority) => {
    const p = (priority || 'media').toLowerCase();
    if (p === 'alta') return 'bg-red-600 text-white shadow-sm';
    if (p === 'media') return 'bg-orange-100 text-orange-700 border border-orange-200';
    return 'bg-gray-100 text-gray-500 border border-gray-200';
};

onMounted(() => {
    fetchPedidos();
});
</script>

<style scoped>
.table-header { padding: 12px 16px; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em; text-align: left; }
.table-cell { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; }
.text-red-link { color: var(--brand-red-light); text-decoration: none; transition: color 0.2s; }
.text-red-link:hover { color: var(--brand-red-dark); }
.gap-2 { gap: 8px; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.table-responsive {
    width: 100%;
    overflow-x: auto;
    background: white;
}

table {
    table-layout: fixed;
    width: 100%;
}

/* Cabeceras Estilo Reporte */
.table-header {
    padding: 14px 16px;
    font-size: 0.7rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.table-cell {
    padding: 14px 16px;
    font-size: 0.85rem;
    vertical-align: middle;
}

/* Truncado para Cliente */
.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.max-w-cliente {
    max-width: 180px;
}

/* Badges de Tipo (Promoción vs Normal) */
.type-badge {
    font-size: 10px;
    font-weight: 900;
    padding: 4px 10px;
    border-radius: 9999px;
    border: 1px solid transparent;
}

.badge-purple { background: #faf5ff; color: #7e22ce; border-color: #f3e8ff; }
.badge-blue { background: #eff6ff; color: #1d4ed8; border-color: #dbeafe; }

/* Badges de Prioridad */
.priority-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

/* Clases de utilidad para estados y prioridades (vienen de tus métodos Vue) */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
}

/* Botón de Acción */
.text-red-link {
    color: #b91c1c;
    text-decoration: none;
    font-weight: 800;
    font-size: 0.75rem;
    transition: color 0.2s;
}

.text-red-link:hover {
    color: #7f1d1d;
    text-decoration: underline;
}

/* Auxiliares */
.w-24 { width: 6rem; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.table-shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
</style>