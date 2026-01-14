<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Detalle del Pedido #{{ pedido && pedido.display_id ? pedido.display_id : id }}</h1>
                    <p class="text-sm text-slate-500">Consulta la información logística, financiera y el desglose de materiales.</p>
                </div>
                <button @click="router.push('/pedidos')" class="btn-secondary flex-row-centered gap-2 px-6">
                    <i class="fas fa-arrow-left"></i> <span class="hidden sm:inline">Volver al Listado</span><span class="sm:hidden">Volver</span>
                </button>
            </div>
            
            <div v-if="loading" class="loading-state mt-12">
                <i class="fas fa-spinner fa-spin text-3xl text-red-600 mb-3"></i>
                <p class="font-medium text-slate-600">Cargando detalles del pedido...</p>
            </div>

            <div v-else-if="error" class="error-message text-center py-8 bg-red-50 rounded-2xl border border-red-100 mt-6">
                <i class="fas fa-exclamation-circle text-2xl text-red-500 mb-2"></i>
                <p class="font-bold text-red-800 uppercase text-xs tracking-widest">Error de Sistema</p>
                <p class="text-red-600">{{ error }}</p>
            </div>

            <div v-else-if="pedido" class="mt-6 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <div class="form-section shadow-premium border-t-4 border-t-red-700">
                        <div class="section-title">
                            <i class="fas fa-user-tag text-red-700"></i> 1. Datos del Cliente
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="label-mini">Nombre Completo</label>
                                <p class="text-sm font-black text-slate-800 uppercase">{{ pedido.cliente.name }}</p>
                            </div>
                            <div>
                                <label class="label-mini">Perfil de Cuenta</label>
                                <span :class="{'bg-red-100 text-red-700': pedido.cliente.tipo === 'CLIENTE', 'bg-blue-100 text-blue-700': pedido.cliente.tipo === 'DISTRIBUIDOR'}" class="status-badge">
                                    {{ pedido.cliente.tipo }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="label-mini">Contacto</label>
                                    <p class="text-[11px] font-bold text-slate-600">{{ pedido.cliente.contacto }}</p>
                                </div>
                                <div>
                                    <label class="label-mini">Teléfono</label>
                                    <p class="text-[11px] font-bold text-slate-600">{{ pedido.cliente.telefono }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section shadow-premium border-t-4 border-t-slate-800">
                        <div class="section-title">
                            <i class="fas fa-truck-moving text-slate-800"></i> 2. Entrega y Logística
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="label-mini">Receptor Final</label>
                                <p class="text-sm font-black text-slate-800">{{ getReceiverName(pedido) }}</p>
                            </div>
                            <div>
                                <label class="label-mini">Dirección de Envío</label>
                                <p class="text-[11px] text-slate-600 leading-tight italic">
                                    <i class="fas fa-map-marker-alt text-red-500 mr-1"></i> {{ pedido.delivery_address || 'Entrega en Sucursal / No especificada' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <div class="flex-1">
                                    <label class="label-mini">Método</label>
                                    <span class="text-[10px] font-black text-red-600 uppercase tracking-tighter">{{ getDeliveryOption(pedido.delivery_option) }}</span>
                                </div>
                                <div v-if="pedido.paqueteria_nombre">
                                    <label class="label-mini">Paquetería</label>
                                    <span class="text-[10px] font-bold text-slate-800 italic underline">{{ pedido.paqueteria_nombre }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="label-mini">Estatus Actual</label>
                                <span :class="getStatusClass(pedido.status)" class="status-badge w-full text-center">
                                    {{ pedido.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="form-section shadow-premium border-t-4 border-t-red-800 bg-slate-50/50">
                        <div class="section-title">
                            <i class="fas fa-info-circle text-red-800"></i> 3. Configuración y Costo
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <label class="label-mini">Tipo de Operación</label>
                                    <p class="text-[10px] font-black uppercase" :class="pedido.tipo_pedido === 'promocion' ? 'text-purple-600' : 'text-slate-800'">
                                        {{ pedido.tipo_pedido === 'promocion' ? 'PROMOCIÓN / REGALO' : 'VENTA NORMAL' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <label class="label-mini">Prioridad</label>
                                    <span :class="getPriorityClass(pedido.prioridad)" class="status-badge">
                                        {{ (pedido.prioridad || 'media').toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="label-mini">Registro de Sistema</label>
                                <p class="text-[11px] font-medium text-slate-500">{{ formatDate(pedido.created_at) }}</p>
                            </div>
                            
                            <div class="pt-4 border-t border-slate-200">
                                <div class="flex justify-between items-end">
                                    <span class="text-[10px] font-black text-slate-400 uppercase">Total Unidades:</span>
                                    <span class="text-sm font-black text-slate-700">{{ calculateTotalItems(pedido.detalles) }}</span>
                                </div>
                                <div class="mt-1 flex justify-between items-center">
                                    <span class="text-xs font-black text-red-900 uppercase tracking-widest">Monto Total:</span>
                                    <span class="text-2xl font-black text-red-800 tracking-tighter">{{ totalOrderCost }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="pedido.comments" class="form-section shadow-premium animate-fade-in bg-yellow-50/30 border border-yellow-100">
                    <h2 class="section-title !text-slate-700 !border-yellow-200"><i class="fas fa-comment-dots"></i> Observaciones</h2>
                    <p class="text-sm text-slate-600 italic whitespace-pre-wrap leading-relaxed">{{ pedido.comments }}</p>
                </div>

                <div class="mt-8">
                    <div class="section-title !border-none !mb-4">
                        <i class="fas fa-book text-red-800"></i> Libros Solicitados
                    </div>
                    
                    <div class="table-responsive table-shadow-lg border rounded-2xl overflow-hidden bg-white">
                        <table class="min-width-full">
                            <thead class="bg-red-800 hidden md:table-header-group">
                                <tr>
                                    <th class="table-header-red">Material / Libro</th>
                                    <th class="table-header-red">Configuración</th>
                                    <th class="table-header-red text-center">Cant.</th>
                                    <th class="table-header-red text-right">Unitario</th>
                                    <th class="table-header-red text-right">Total</th>
                                    <th class="table-header-red">ISBN</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 block md:table-row-group">
                                <tr v-for="detalle in pedido.detalles" :key="detalle.id" 
                                    class="block md:table-row p-4 md:p-0 hover:bg-slate-50 transition-colors relative">
                                    
                                    <td class="table-cell block md:table-cell font-black text-slate-800 text-sm md:text-[13px]">
                                        <span class="md:hidden block text-[9px] uppercase text-slate-400 mb-1">Libro</span>
                                        {{ detalle.libro ? detalle.libro.titulo : 'Libro no encontrado' }}
                                    </td>
                                    
                                    <td class="table-cell block md:table-cell py-2 md:py-4">
                                        <span class="md:hidden inline-block text-[9px] uppercase text-slate-400 mr-2">Formato:</span>
                                        <span class="text-[10px] font-black text-slate-500 uppercase bg-slate-100 px-2 py-0.5 rounded">{{ detalle.tipo_licencia }}</span>
                                    </td>
                                    
                                    <td class="table-cell block md:table-cell text-left md:text-center py-2 md:py-4">
                                        <span class="md:hidden inline-block text-[9px] uppercase text-slate-400 mr-2">Cantidad:</span>
                                        <span class="text-sm font-black text-red-700 bg-red-50 md:bg-transparent px-2 py-0.5 rounded md:p-0">{{ detalle.cantidad }}</span>
                                    </td>
                                    
                                    <td class="table-cell block md:table-cell text-left md:text-right text-xs text-slate-500 font-bold py-2 md:py-4">
                                        <span class="md:hidden inline-block text-[9px] uppercase text-slate-400 mr-2">Precio Unitario:</span>
                                        {{ formatCurrency(detalle.precio_unitario) }}
                                    </td>
                                    
                                    <td class="table-cell block md:table-cell text-left md:text-right font-black text-red-800 text-base md:text-[14px] py-2 md:py-4">
                                        <span class="md:hidden block text-[9px] uppercase text-slate-400 mb-1">Costo Total Línea</span>
                                        {{ formatCurrency(detalle.costo_total) }}
                                    </td>
                                    
                                    <td class="table-cell block md:table-cell text-[10px] font-mono text-slate-400 py-2 md:py-4">
                                        <span class="md:hidden inline-block text-[9px] uppercase text-slate-400 mr-2">ISBN:</span>
                                        {{ detalle.libro ? detalle.libro.ISBN : 'N/A' }}
                                    </td>
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
/* El script permanece igual, solo se añadieron clases de utilidad */
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
    } finally {
        loading.value = false;
    }
};

const totalOrderCost = computed(() => {
    if (!pedido.value || !pedido.value.detalles) return formatCurrency(0);
    const total = pedido.value.detalles.reduce((sum, detalle) => sum + (parseFloat(detalle.costo_total) || 0), 0);
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

const calculateTotalItems = (detalles) => detalles ? detalles.reduce((sum, item) => sum + item.cantidad, 0) : 0;

const getStatusClass = (status) => {
    switch (status) {
        case 'ENTREGADO': return 'bg-green-100 text-green-800 border border-green-200 uppercase';
        case 'PENDIENTE': return 'bg-yellow-100 text-yellow-800 border border-yellow-200 uppercase';
        case 'EN PROCESO': return 'bg-blue-100 text-blue-800 border border-blue-200 uppercase';
        case 'CANCELADO': return 'bg-red-100 text-red-800 border border-red-200 uppercase';
        default: return 'bg-slate-100 text-slate-800 uppercase';
    }
};

const getPriorityClass = (priority) => {
    if (!priority) return 'bg-slate-100 text-slate-600';
    switch (priority.toLowerCase()) {
        case 'alta': return 'bg-red-600 text-white font-black px-3 shadow-sm uppercase';
        case 'media': return 'bg-orange-100 text-orange-700 font-bold border border-orange-200 uppercase';
        case 'baja': return 'bg-slate-100 text-slate-600 font-medium border border-slate-200 uppercase';
        default: return 'bg-slate-50 text-slate-500 uppercase';
    }
};

const getReceiverName = (p) => {
    if (p.receiver_type === 'nuevo') return `${p.receiver_nombre || 'Desconocido'} (Temporal)`; 
    return p.cliente?.contacto || p.cliente?.name || 'Cliente'; 
};

const getDeliveryOption = (option) => {
    switch (option) {
        case 'recoleccion': return 'RECOLECCIÓN ALMACÉN';
        case 'paqueteria': return 'PAQUETERÍA';
        case 'none': return 'ESTÁNDAR';
        default: return 'NO DEFINIDO';
    }
};

onMounted(() => fetchPedidoDetail());
</script>

<style scoped>
.form-section { background: white; padding: 24px; border-radius: 20px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #1e293b; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-widest; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; display: inline-block; }
.table-header-red { padding: 14px 16px; text-align: left; font-size: 0.7rem; font-weight: 900; color: white; background-color: #a93339; text-transform: uppercase; letter-spacing: 1px; }
.table-cell { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
.shadow-premium { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.04); }

/* Responsividad extra */
@media (max-width: 640px) {
    .content-wrapper { padding: 12px !important; }
    .form-section { padding: 20px 15px !important; }
    .table-cell { padding: 10px 15px !important; }
    .detail-header-flex { flex-direction: column; align-items: flex-start; gap: 1rem; }
}

.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>