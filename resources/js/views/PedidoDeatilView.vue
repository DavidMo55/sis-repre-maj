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
                
                <div class="info-card border-red">
                    <h2 class="card-title text-red-700 flex-row-centered space-x-2"><i class="fas fa-user-tag"></i> Cliente</h2>
                    <p class="mb-2"><span class="font-semibold">Nombre:</span> {{ pedido.cliente.name }}</p>
                    <p class="mb-2"><span class="font-semibold">Tipo:</span> 
                        <span :class="{'text-red-600': pedido.cliente.tipo === 'CLIENTE', 'text-blue-600': pedido.cliente.tipo === 'DISTRIBUIDOR'}" class="font-bold">
                            {{ pedido.cliente.tipo }}
                        </span>
                    </p>
                    <p class="mb-2"><span class="font-semibold">Contacto Principal:</span> {{ pedido.cliente.contacto }}</p>
                    <p class="mb-2"><span class="font-semibold">Teléfono:</span> {{ pedido.cliente.telefono }}</p>
                </div>

                <div class="info-card border-red">
                    <h2 class="card-title text-red-700 flex-row-centered space-x-2"><i class="fas fa-truck-moving"></i> Entrega y Logística</h2>
                    <p class="mb-2"><span class="font-semibold">Receptor:</span> {{ getReceiverName(pedido) }}</p>
                    <p class="mb-2"><span class="font-semibold">Dirección de Entrega:</span> {{ pedido.delivery_address || 'No especificada' }}</p>
                    <p class="mb-2"><span class="font-semibold">Opción Logística:</span> 
                        <span class="font-bold text-red-500 text-uppercase">{{ getDeliveryOption(pedido.delivery_option) }}</span>
                    </p>
                    <p class="mb-2"><span class="font-semibold">Estatus:</span> 
                        <span :class="getStatusClass(pedido.status)" class="status-badge">
                            {{ pedido.status }}
                        </span>
                    </p>
                </div>

                <div class="info-card border-red">
                    <h2 class="card-title text-red-700 flex-row-centered space-x-2"><i class="fas fa-calendar-alt"></i> Info General & Costo</h2>
                    <p class="mb-2"><span class="font-semibold">Fecha de Creación:</span> {{ formatDate(pedido.created_at) }}</p>
                    <p class="mb-2"><span class="font-semibold">Representante ID:</span> {{ pedido.user_id }}</p>
                    <hr class="separator-red">
                    <p class="mb-2 text-lg font-bold text-gray-800"><span class="font-semibold">Total Unidades:</span> {{ calculateTotalItems(pedido.detalles) }}</p>
                    <p class="text-2xl font-extrabold text-red-800"><span class="font-semibold">Costo Total:</span> {{ totalOrderCost }}</p>
                </div>

                <div v-if="pedido.comments" class="col-span-full info-card border-gray-400">
                    <h2 class="card-title text-gray-700 flex-row-centered space-x-2"><i class="fas fa-comment-dots"></i> Comentarios Generales</h2>
                    <p class="text-gray-600 italic whitespace-pre-wrap">{{ pedido.comments }}</p>
                </div>

                <div class="col-span-full mt-6">
                    <h2 class="books-title flex-row-centered space-x-2"><i class="fas fa-book"></i> Libros Solicitados</h2>
                    <div class="table-responsive table-shadow-lg">
                        <table class="min-width-full divide-y-gray-200">
                            <thead class="bg-red-light">
                                <tr>
                                    <th class="table-header-red">Libro</th>
                                    <th class="table-header-red">Tipo de Licencia</th>
                                    <th class="table-header-red text-center">Cantidad</th>
                                    <th class="table-header-red text-right">Precio Unitario</th>
                                    <th class="table-header-red text-right">Costo Total</th>
                                    <th class="table-header-red">ISBN</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y-gray-200">
                                <tr v-for="detalle in pedido.detalles" :key="detalle.id">
                                    <td class="table-cell table-cell-bold text-dark-gray">{{ detalle.libro ? detalle.libro.titulo : 'Libro no encontrado' }}</td>
                                    <td class="table-cell text-gray-700-specific">{{ detalle.tipo_licencia }}</td>
                                    <td class="table-cell text-lg-total text-red-700 text-center">{{ detalle.cantidad }}</td>
                                    <td class="table-cell text-medium-gray text-right">{{ formatCurrency(detalle.precio_unitario) }}</td>
                                    <td class="table-cell text-lg-total text-red-700 text-right">{{ formatCurrency(detalle.costo_total) }}</td>
                                    <td class="table-cell text-medium-gray">{{ detalle.libro ? detalle.libro.ISBN : 'N/A' }}</td>
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
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
};

const calculateTotalItems = (detalles) => {
    if (!detalles) return 0;
    return detalles.reduce((sum, item) => sum + item.cantidad, 0);
};

const getStatusClass = (status) => {
    switch (status) {
        case 'ENTREGADO': return 'bg-green-100 text-green-800';
        case 'PENDIENTE': return 'bg-yellow-100 text-yellow-800';
        case 'EN PROCESO': return 'bg-blue-100 text-blue-800';
        case 'CANCELADO': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getReceiverName = (p) => {
    if (p.receiver_type === 'nuevo') {
        // Asumiendo que receiver_nombre se devuelve directamente en el modelo Pedido si receiver_type='nuevo'
        return `${p.receiver_nombre} (Datos Temporales)`; 
    }
    // Si receiver_type='cliente', usa el contacto principal
    return p.cliente.contacto || p.cliente.name; 
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
