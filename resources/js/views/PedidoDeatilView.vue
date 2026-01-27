<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    <h1 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight break-words">
                        Detalle del Pedido #{{ pedido && (pedido.numero_referencia || pedido.display_id) ? (pedido.numero_referencia || pedido.display_id) : id }}
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium mt-1 uppercase tracking-tighter italic">Expediente logístico y financiero detallado.</p>
                </div>
                <button @click="router.push('/pedidos')" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto">
                    <i class="fas fa-arrow-left mr-2"></i> Volver al Historial
                </button>
            </div>
            
            <!-- Loader -->
            <div v-if="loading" class="loading-state py-20 text-center animate-pulse">
                <i class="fas fa-circle-notch fa-spin text-4xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Cargando datos del servidor...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="error-message-container p-6 md:p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in mx-2">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle fa-2xl"></i>
                </div>
                <h2 class="text-xl font-black text-red-800 uppercase tracking-tighter">Pedido no visible</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium">{{ error }}</p>
                <button @click="fetchPedidoDetail" class="btn-primary mt-6 px-10 py-3 rounded-2xl shadow-lg bg-black text-white uppercase font-black text-xs tracking-widest">Reintentar</button>
            </div>

            <div v-else-if="pedido" class="space-y-6 md:space-y-8 animate-fade-in px-1 md:px-0 overflow-hidden">
                
                <!-- 1. GRID DE INFORMACIÓN GENERAL -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- Datos del Receptor (Mapeado con tu SQL) -->
                    <div class="info-card shadow-premium border-t-4 border-t-red-700 bg-white p-6 rounded-3xl">
                        <div class="section-title !mb-6">
                            <i class="fas fa-user-tag text-red-700"></i> 1. Información del Receptor
                        </div>
                        <div class="space-y-4">
                            <!-- Datos del Plantel Base -->
                            <div class="pb-3 border-b border-slate-50">
                                <label class="label-mini text-slate-400">Plantel / Cliente Origen</label>
                                <p class="text-sm font-black text-slate-800  lbb uppercase leading-tight truncate" :title="pedido.cliente?.name">
                                    {{ pedido.cliente?.name || 'No disponible' }}
                                </p>
                            </div>

                            <!-- Datos del Destinatario (Extraídos de pedido_receptores si es nuevo) -->
                            <div>
                                <label class="label-mini text-red-800 font-bold">Consignatario / Recibe:</label>
                                <p class="text-base font-black lbb text-slate-900 uppercase break-words leading-tight">
                                    {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.nombre || 'Datos no cargados') : (pedido.cliente?.contacto || 'Titular de Cuenta') }}
                                </p>
                                <span class="text-[8px] font-black uppercase px-2  py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200 mt-1.5 inline-block">
                                    {{ pedido.receiver_type === 'nuevo' ? 'Datos Temporales de Entrega' : 'Datos Registrados en Sistema' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label class="label-mini">RFC Fiscal</label>
                                    <p class="text-[10px] font-mono font-black text-slate-700 lbb uppercase tracking-tighter truncate">
                                        {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.rfc || 'N/A') : (pedido.cliente?.rfc || 'N/A') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="label-mini">Teléfono</label>
                                    <p class="text-[10px] font-black lbb text-slate-700 truncate">
                                        {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.telefono || 'N/A') : (pedido.cliente?.telefono || 'N/A') }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="pedido.receiver_type === 'nuevo' || pedido.cliente?.email" class="pt-3 border-t border-slate-50 min-w-0">
                                <label class="label-mini">Email de Contacto</label>
                                <p class="text-xs font-bold text-red-600 lbb truncate" :title="pedido.receiver_type === 'nuevo' ? pedido.receptor?.correo : pedido.cliente?.email">
                                    {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.correo || '---') : (pedido.cliente?.email || '---') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Logística y Dirección -->
                    <div class="info-card shadow-premium border-t-4 border-t-slate-800 bg-white p-6 rounded-3xl">
                        <div class="section-title !mb-6">
                            <i class="fas fa-truck text-slate-800"></i> 2. Logística y Destino
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="label-mini text-slate-400 uppercase">Dirección de Envío</label>
                                <div class="text-[11px] lbb text-slate-600 leading-relaxed font-medium italic bg-slate-50 p-3 rounded-xl border border-slate-100 break-words">
                                    <i class="fas  lbb fa-map-marker-alt text-red-500 mr-1"></i> 
                                    {{ formatFullAddress(pedido) }}
                                </div>
                            </div>
                            <br>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-red-50/30 p-2.5 rounded-xl border border-red-100 min-w-0">
                                    <label class="label-mini">Método</label>
                                    <br>
                                    <span class="text-[10px] font-black text-red-700  lbb uppercase truncate block">{{ getDeliveryOption(pedido.delivery_option) }}</span>
                                </div>
                                <br>
                                <div v-if="pedido.paqueteria_nombre" class="bg-slate-50 p-2.5 rounded-xl border border-slate-100 min-w-0">
                                    <label class="label-mini">Empresa</label>
                                    <br>
                                    <span class="text-[10px] font-black text-slate-800 lbb uppercase truncate block">{{ pedido.paqueteria_nombre }}</span>
                                </div>
                            </div>
                            <div>
                                <br>
                                <label class="label-mini">Estatus del Envío</label>
                                <br>
                                <span :class="getStatusClass(pedido.status)" class="status-badge w-full text-center py-2 shadow-sm">
                                    {{ pedido.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Finanzas y Prioridad -->
                    <div class="info-card shadow-premium border-t-4 border-t-red-800 bg-slate-50/50 p-6 rounded-3xl">
                        <div class="section-title !mb-6">
                            <i class="fas fa-wallet text-red-800"></i> 3. Resumen Financiero
                        </div>
                        <div class="space-y-5">
                            <div class="flex justify-between items-start gap-2">
                                <div class="min-w-0">
                                    <label class="label-mini">Tipo Pedido</label>
                                    <p class="text-[10px] lbb font-black uppercase truncate" :class="pedido.tipo_pedido === 'promocion' ? 'text-purple-600' : 'text-slate-800'">
                                        {{ pedido.tipo_pedido === 'promocion' ? 'PROMOCIÓN' : 'VENTA NORMAL' }}
                                    </p>
                                </div>
                                <br>
                                <div class="text-right shrink-0">
                                    <label class="label-mini">Prioridad</label>
                                    <br>
                                    <span :class="getPriorityClass(pedido.prioridad)" class="status-badge !px-3 !py-1">
                                        {{ (pedido.prioridad || 'media').toUpperCase() }}
                                    </span>
                                </div>
                            </div><br>
                            
                            <div class="pt-4 border-t border-slate-200 space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Unidades:</span>
                                    <br>
                                    <span class="text-sm lbb font-black text-slate-700">{{ calculateTotalItems(pedido.detalles) }} Libros</span>
                                </div>
                                <br>
                                <div>
                                    <label class="label-mini text-red-900 font-bold mb-0">Total Inversión:</label>
                                    <p class="text-3xl font-black text-red-700 lbb tracking-tighter leading-none truncate">{{ totalOrderCost }}</p>
                                </div>
                               
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. EXPEDIENTE DIGITAL -->
                <div class="info-card shadow-premium border-l-8 border-l-slate-800 bg-white p-6 rounded-3xl overflow-hidden">
                    <div class="section-title !border-b-0 mb-6">
                        <i class="fas fa-folder-open text-slate-800"></i> Expediente Digital y Documentos
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all" 
                             :class="pedido.factura_path ? 'border-red-50 bg-red-50/20' : 'border-slate-50 bg-slate-50/30 opacity-60'">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white shadow-sm shrink-0">
                                    <i class="fas fa-file-invoice text-xl" :class="pedido.factura_path ? 'text-red-600' : 'text-slate-300'"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest truncate">Factura</p>
                                    <p class="text-xs font-bold lbb text-slate-700 truncate">{{ pedido.factura_path ? 'PDF Disponible' : 'No cargada' }}</p>
                                </div>
                            </div>
                            <a v-if="pedido.factura_path" :href="pedido.factura_url" target="_blank" class="btn-icon-action bg-red-600 shrink-0">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>

                        <div class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all" 
                             :class="pedido.guia_envio_path ? 'border-blue-50 bg-blue-50/20' : 'border-slate-50 bg-slate-50/30 opacity-60'">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white shadow-sm shrink-0">
                                    <i class="fas fa-shipping-fast text-xl" :class="pedido.guia_envio_path ? 'text-blue-600' : 'text-slate-300'"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest truncate">Guía</p>
                                    <p class="text-xs font-bold lbb text-slate-700 truncate">{{ pedido.guia_envio_path ? 'Rastreo Listo' : 'Pendiente' }}</p>
                                </div>
                            </div>
                            <a v-if="pedido.guia_envio_path" :href="pedido.guia_url" target="_blank" class="btn-icon-action bg-blue-600 shrink-0">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 3. DESGLOSE DE MATERIALES -->
                <div class="mt-8">
                    <div class="flex items-center justify-between mb-4 px-2">
                        <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">
                            <i class="fas fa-book text-red-800 mr-1"></i> Libros Solicitados
                        </h2>
                    </div>
                    
                    <div class="table-responsive shadow-premium border border-slate-200 rounded-[2.5rem] bg-white overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse min-w-[800px]">
                                <thead class="bg-slate-900 text-white uppercase text-[9px] font-black tracking-[0.15em]">
                                    <tr>
                                        <th class="px-6 py-5 text-left">Material / Libro</th>
                                        <th class="px-6 py-5 text-center w-40">Tipo/Licencia</th>
                                        <th class="px-6 py-5 text-center w-24">Cant.</th>
                                        <th class="px-6 py-5 text-right w-32">Unitario</th>
                                        <th class="px-6 py-5 text-right w-32">Subtotal</th>
                                        <th class="px-6 py-5 text-center w-24">Adjunto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="detalle in pedido.detalles" :key="detalle.id" class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-5">
                                            <p class="font-black text-slate-800 text-[13px] uppercase leading-tight">
                                                {{ detalle.libro?.titulo || 'Material no encontrado' }}
                                            </p>
                                            <p class="text-[9px] font-mono lbb text-slate-400 mt-1 uppercase tracking-widest bg-slate-50 px-2 py-0.5 rounded border inline-block">
                                                ISBN: {{ detalle.libro?.ISBN || 'N/A' }}
                                            </p>
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <span class="badge-format">{{ detalle.tipo_licencia }}</span>
                                        </td>
                                        <td class="px-6 py-5 text-center font-black text-red-700 text-lg">
                                            {{ detalle.cantidad }}
                                        </td>
                                        <td class="px-6 py-5 text-right font-mono text-[11px] text-slate-500 font-bold">
                                            {{ formatCurrency(detalle.precio_unitario) }}
                                        </td>
                                        <td class="px-6 py-5 text-right font-black text-slate-800 text-[14px]">
                                            {{ formatCurrency(detalle.costo_total) }}
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <a v-if="detalle.archivo_path" :href="detalle.archivo_url" target="_blank" class="link-pdf-icon">
                                                <i class="fas fa-file-pdf fa-lg"></i>
                                            </a>
                                            <span v-else class="text-slate-200 text-[10px] italic">--</span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-slate-900 border-t lbb border-white/10">
                                    <tr>
                                        <td colspan="4" class="px-8 py-8 text-right lbb font-black uppercase text-[10px] tracking-[0.2em] text-slate-400">Total Acumulado del Pedido:</td>
                                        <td class="px-6 py-8 text-right font-black lbb text-3xl text-red-400 leading-none">
                                            {{ formatCurrency(totalOrderCost) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div v-if="pedido.comments" class="info-card border-none bg-amber-50/50 p-8 rounded-[2.5rem] border border-amber-100 shadow-sm mb-10">
                    <h3 class="text-[10px] font-black text-amber-600 uppercase mb-3 tracking-widest flex items-center gap-2">
                        <i class="fas fa-comment-dots"></i> Notas Adicionales de Gestión
                    </h3>
                    <p class="text-sm lbb text-slate-700 italic leading-relaxed">{{ pedido.comments }}</p>
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
             error.value = 'No se pudieron cargar los detalles técnicos del pedido.';
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
    return Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};

const calculateTotalItems = (detalles) => detalles ? detalles.reduce((sum, item) => sum + Number(item.cantidad), 0) : 0;

const getStatusClass = (status) => {
    const base = 'status-badge ';
    switch (status) {
        case 'ENTREGADO': return base + 'bg-green-100 text-green-700 border border-green-200';
        case 'PENDIENTE': return base + 'bg-yellow-100 text-yellow-700 border border-yellow-200';
        case 'REVISIÓN': return base + 'bg-orange-100 text-orange-700 border border-orange-200';
        case 'PROCESO': return base + 'bg-blue-100 text-blue-700 border border-blue-200';
        case 'CANCELADO': return base + 'bg-red-100 text-red-700 border border-red-200';
        default: return base + 'bg-slate-100 text-slate-400';
    }
};

const getPriorityClass = (priority) => {
    if (!priority) return 'bg-slate-100 text-slate-600';
    switch (priority.toLowerCase()) {
        case 'alta': return 'bg-red-600 text-white font-black px-3 shadow-sm';
        case 'media': return 'bg-orange-100 text-orange-700 font-bold border border-orange-200';
        case 'baja': return 'bg-slate-100 text-slate-400 border border-slate-200';
        default: return 'bg-slate-50 text-slate-300';
    }
};

/**
 * Formatea la dirección completa priorizando los datos del receptor
 */
const formatFullAddress = (p) => {
    if (p.delivery_address) return p.delivery_address;
    // Si es tipo nuevo, extrae la dirección del modelo PedidoReceptor
    if (p.receiver_type === 'nuevo' && p.receptor?.direccion) return p.receptor.direccion;
    return p.cliente?.direccion || 'Entrega en Sucursal / Oficina';
};

const getDeliveryOption = (option) => {
    switch (option) {
        case 'recoleccion': return 'ALMACÉN';
        case 'paqueteria': return 'PAQUETERÍA';
        case 'entrega': return 'DIRECTA';
        default: return 'SUCURSAL';
    }
};

onMounted(() => {
    window.scrollTo(0, 0);
    fetchPedidoDetail();
});
</script>

<style scoped>
.info-card { background: white; font-weight: bold; padding: 25px; border-radius: 24px; border: 1px solid #f1f5f9; }
.section-title {  color: #1e293b; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-[0.1em]; }
.status-badge { padding: 4px 14px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; text-transform: uppercase; }

.shadow-premium { box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05); }

.table-responsive {
    width: 100%;
    background: white;
    border-radius: 1.5rem;
}

table {
    table-layout: auto;
    width: 100%;
    border-collapse: collapse;

}

.table-cell {
    padding: 16px 20px;
    vertical-align: middle;
}

.badge-format {
    display: inline-block;
    font-size: 10px;
    font-weight: 900;
    color: #64748b;
    text-transform: uppercase;
    background-color: #f8fafc;
    padding: 15px 20px;
    border-radius: 9999px;
    border: 1px solid #e2e8f0;
    white-space: nowrap;
}

.link-pdf-icon {
    color: #dc2626;
    transition: all 0.2s;
}

.link-pdf-icon:hover {
    color: #991b1b;
    transform: scale(1.1);
}

.btn-icon-action {
    width: 34px;
    height: 34px;
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s, opacity 0.2s;
    flex-shrink: 0;
}

.btn-secondary-custom { @apply bg-white border-2 border-slate-200 py-2.5 px-6 rounded-xl text-sm font-black transition-all hover:bg-slate-50 text-black; }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 640px) {
    .info-card { padding: 20px; }
}

.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.lbb{
    color: black;
}
</style>