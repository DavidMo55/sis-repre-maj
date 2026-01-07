<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <h1>Detalle del Pedido #{{ pedido && pedido.display_id ? pedido.display_id : id }}</h1>
                <button @click="router.push('/pedidos')" class="btn-secondary flex-row-centered space-x-2">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </button>
            </div>
            
            <div v-if="loading" class="loading-state">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>Cargando detalles del pedido...</p>
            </div>

            <div v-else-if="error" class="error-message text-center py-4">
                Error: {{ error }}
            </div>

            <div v-else-if="pedido" class="detail-grid mt-6">
                
                <!-- TARJETA: CLIENTE -->
                <div class="info-card border-red">
                    <h2 class="card-title text-red-700 flex-row-centered space-x-2"><i class="fas fa-user-tag"></i> Cliente</h2>
                    <p class="mb-2"><span class="font-semibold text-gray-500 text-xs uppercase block">Nombre:</span> {{ pedido.cliente.name }}</p>
                    <p class="mb-2"><span class="font-semibold text-gray-500 text-xs uppercase block">Tipo:</span> 
                        <span :class="{'text-red-600': pedido.cliente.tipo === 'CLIENTE', 'text-blue-600': pedido.cliente.tipo === 'DISTRIBUIDOR'}" class="font-bold">
                            {{ pedido.cliente.tipo }}
                        </span>
                    </p>
                    <p class="mb-2"><span class="font-semibold text-gray-500 text-xs uppercase block">Contacto Principal:</span> {{ pedido.cliente.contacto }}</p>
                    <p class="mb-2"><span class="font-semibold text-gray-500 text-xs uppercase block">Teléfono:</span> {{ pedido.cliente.telefono }}</p>
                </div>

                <!-- TARJETA: ENTREGA Y LOGÍSTICA -->
                <div class="info-card border-red">
                    <h2 class="card-title text-red-700 flex-row-centered space-x-2"><i class="fas fa-truck-moving"></i> Entrega y Logística</h2>
                    <p class="mb-2"><span class="font-semibold text-gray-500 text-xs uppercase block">Receptor:</span> {{ getReceiverName(pedido) }}</p>
                    <p class="mb-2"><span class="font-semibold text-gray-500 text-xs uppercase block">Dirección de Entrega:</span> {{ pedido.delivery_address || 'No especificada' }}</p>
                    <p class="mb-2"><span class="font-semibold text-gray-500 text-xs uppercase block">Opción Logística:</span> 
                        <span class="font-bold text-red-500 uppercase">{{ getDeliveryOption(pedido.delivery_option) }}</span>
                    </p>
                    <!-- NUEVO: Mostrar nombre de paquetería si se seleccionó esa opción -->
                    <p v-if="pedido.paqueteria_nombre" class="mb-2 animate-fade-in">
                        <span class="font-semibold text-gray-500 text-xs uppercase block">Paquetería Sugerida:</span>
                        <span class="font-bold text-gray-800 italic">{{ pedido.paqueteria_nombre }}</span>
                    </p>
                    <p class="mb-2"><span class="font-semibold text-gray-500 text-xs uppercase block">Estatus:</span> 
                        <span :class="getStatusClass(pedido.status)" class="status-badge">
                            {{ pedido.status }}
                        </span>
                    </p>
                </div>

                <!-- TARJETA: INFO GENERAL & CONFIGURACIÓN -->
                <div class="info-card border-red">
                    <h2 class="card-title text-red-700 flex-row-centered space-x-2"><i class="fas fa-info-circle"></i> Configuración y Costo</h2>
                    
                    <!-- NUEVO: Tipo de Pedido -->
                    <p class="mb-2">
                        <span class="font-semibold text-gray-500 text-xs uppercase block">Tipo de Pedido:</span>
                        <span class="font-bold" :class="pedido.tipo_pedido === 'promocion' ? 'text-purple-600' : 'text-gray-800'">
                            {{ pedido.tipo_pedido === 'promocion' ? '🎁 PROMOCIÓN / REGALO' : '📦 VENTA NORMAL' }}
                        </span>
                    </p>

                    <!-- NUEVO: Prioridad -->
                    <p class="mb-2">
                        <span class="font-semibold text-gray-500 text-xs uppercase block">Prioridad:</span>
                        <span :class="getPriorityClass(pedido.prioridad)" class="status-badge">
                            {{ (pedido.prioridad || 'media').toUpperCase() }}
                        </span>
                    </p>

                    <p class="mb-2"><span class="font-semibold text-gray-500 text-xs uppercase block">Fecha de Creación:</span> {{ formatDate(pedido.created_at) }}</p>
                    
                    <hr class="separator-red">
                    <p class="mb-1 text-sm font-bold text-gray-600">Total Unidades: {{ calculateTotalItems(pedido.detalles) }}</p>
                    <p class="text-2xl font-extrabold text-red-800">{{ totalOrderCost }}</p>
                </div>

                <!-- COMENTARIOS -->
                <div v-if="pedido.comments" class="col-span-full info-card border-gray-400">
                    <h2 class="card-title text-gray-700 flex-row-centered space-x-2"><i class="fas fa-comment-dots"></i> Comentarios Generales</h2>
                    <p class="text-gray-600 italic whitespace-pre-wrap">{{ pedido.comments }}</p>
                </div>

                <!-- TABLA DE MATERIALES -->
                <div class="col-span-full mt-6">
                    <h2 class="books-title flex-row-centered space-x-2"><i class="fas fa-book"></i> Libros Solicitados</h2>
                    <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden mt-4">
                        <table class="min-width-full divide-y-gray-200">
                            <thead class="bg-red-800">
                                <tr>
                                    <th class="table-header-red">Libro</th>
                                    <th class="table-header-red">Formato / Licencia</th>
                                    <th class="table-header-red text-center">Cantidad</th>
                                    <th class="table-header-red text-right">Precio Unitario</th>
                                    <th class="table-header-red text-right">Costo Total</th>
                                    <th class="table-header-red">ISBN</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y-gray-200">
                                <tr v-for="detalle in pedido.detalles" :key="detalle.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="table-cell table-cell-bold text-dark-gray">{{ detalle.libro ? detalle.libro.titulo : 'Libro no encontrado' }}</td>
                                    <td class="table-cell text-xs font-bold text-gray-500 uppercase">{{ detalle.tipo_licencia }}</td>
                                    <td class="table-cell text-lg-total text-red-700 text-center font-bold">{{ detalle.cantidad }}</td>
                                    <td class="table-cell text-medium-gray text-right">{{ formatCurrency(detalle.precio_unitario) }}</td>
                                    <td class="table-cell text-lg-total text-red-800 text-right font-black">{{ formatCurrency(detalle.costo_total) }}</td>
                                    <td class="table-cell text-medium-gray text-xs font-mono">{{ detalle.libro ? detalle.libro.ISBN : 'N/A' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../axios';

const route = useRoute();
const router = useRouter();
const id = route.params.id; 
const pedido = ref(null);
const loading = ref(false);
const error = ref(null);

const fetchPedidoDetail = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/pedidos/${id}`);
        pedido.value = response.data;
    } catch (err) {
        if (err.response && (err.response.status === 404 || err.response.status === 403)) {
             error.value = err.response.data.message;
        } else {
             error.value = 'Ocurrió un error al cargar los detalles del pedido.';
        }
        console.error('Error fetching pedido detail:', err);
    } finally {
        loading.value = false;
    }
};

const totalOrderCost = computed(() => {
    if (!pedido.value || !pedido.value.detalles) {
        return formatCurrency(0);
    }
    const total = pedido.value.detalles.reduce((sum, detalle) => {
        return sum + (parseFloat(detalle.costo_total) || 0);
    }, 0);
    
    return formatCurrency(total);
});

const formatCurrency = (value) => {
    if (value === null || isNaN(value)) return '$0.00';
    return value.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};


const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
};

const calculateTotalItems = (detalles) => {
    if (!detalles) return 0;
    return detalles.reduce((sum, item) => sum + item.cantidad, 0);
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

// NUEVO: Helper para colores de prioridad
const getPriorityClass = (priority) => {
    if (!priority) return 'bg-gray-100 text-gray-600';
    switch (priority.toLowerCase()) {
        case 'alta': return 'bg-red-600 text-white font-black px-3 shadow-sm';
        case 'media': return 'bg-orange-100 text-orange-700 font-bold border border-orange-200';
        case 'baja': return 'bg-gray-100 text-gray-600 font-medium border border-gray-200';
        default: return 'bg-gray-50 text-gray-500';
    }
};

const getReceiverName = (p) => {
    if (p.receiver_type === 'nuevo') {
        return `${p.receiver_nombre || 'Desconocido'} (Datos Temporales)`; 
    }
    return p.cliente?.contacto || p.cliente?.name || 'Cliente'; 
};

const getDeliveryOption = (option) => {
    switch (option) {
        case 'recoleccion': return 'RECOLECCIÓN EN ALMACÉN';
        case 'paqueteria': return 'PAQUETERÍA SUGERIDA';
        case 'none': return 'ESTÁNDAR';
        default: return 'NO ESPECIFICADO';
    }
};

onMounted(() => {
    fetchPedidoDetail();
});
</script>

<style scoped>
.info-card { background-color: #ffffff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; }
.card-title { font-size: 1.1rem; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 15px; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; display: inline-block; }
.separator-red { border-top: 1px solid #fee2e2; margin: 15px 0; }
.table-header-red { padding: 14px 16px; text-align: left; font-size: 0.75rem; font-weight: 700; color: white; background-color: #a93339; text-transform: uppercase; }
.table-cell { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>