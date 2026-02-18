<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    <h1 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight break-words uppercase">
                        Expediente #{{ pedido && (pedido.numero_referencia || pedido.display_id) ? (pedido.numero_referencia || pedido.display_id) : id }}
                    </h1>
                    <p class="text-xs md:text-sm text-red-600 font-bold mt-1 uppercase tracking-widest italic">Gestión logística y auditoría de materiales.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <!-- BOTÓN DE EDICIÓN: Solo visible si el estatus permite modificaciones -->
                    <button 
                        v-if="pedido && pedido.status === 'PENDIENTE'"
                        @click="router.push({ name: 'PedidoEdit', params: { id: pedido.id } })" 
                        class="btn-edit-action shadow-sm shrink-0 w-full sm:w-auto uppercase flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-edit"></i> Editar Pedido
                    </button>

                    <button @click="router.push('/pedidos')" class="btn-secondary-custom shadow-sm shrink-0 w-full sm:w-auto flex items-center justify-center gap-2">
                        <i class="fas fa-arrow-left"></i> Volver al Historial
                    </button>
                </div>
            </div>
            
            <!-- Loader de Sistema -->
            <div v-if="loading" class="loading-state py-20 text-center animate-pulse">
                <i class="fas fa-circle-notch fa-spin text-4xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Sincronizando con base de datos central...</p>
            </div>

            <!-- Error de Sincronización -->
            <div v-else-if="error" class="error-message-container p-6 md:p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in mx-2">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle fa-2xl"></i>
                </div>
                <h2 class="text-xl font-black text-red-800 uppercase tracking-tighter">Error de Acceso</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium uppercase">{{ error }}</p>
                <button @click="fetchPedidoDetail" class="btn-primary mt-6 px-10 py-3 rounded-2xl shadow-lg bg-black text-white uppercase font-black text-xs tracking-widest">Reintentar Conexión</button>
            </div>

            <div v-else-if="pedido" class="space-y-6 md:space-y-8 animate-fade-in px-1 md:px-0 overflow-hidden">
                
                <!-- 1. GRID DE INFORMACIÓN GENERAL -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- Bloque: Cliente Maestro -->
                    <div class="info-card shadow-premium border-t-4 border-t-red-700 bg-white p-6 rounded-3xl border border-slate-100">
                        <div class="section-title !mb-6">
                            <i class="fas fa-user-tag text-red-700"></i> 1. Cuenta Principal
                        </div>
                        <div class="space-y-4">
                            <div class="pb-3 border-b border-slate-50">
                                <label class="label-mini label-large text-slate-400">Institución Vinculada</label>
                                <p class="text-sm font-black text-slate-800 value-text uppercase leading-tight">
                                    {{ pedido.cliente?.name || 'No disponible' }}
                                </p>
                            </div>
                            <div class="pt-2">
                                <label class="label-mini label-large text-slate-400">Prioridad Asignada</label>
                                <span :class="getPriorityClass(pedido.prioridad)" class="status-badge !px-3 !py-1 text-[10px]">
                                    {{ (pedido.prioridad || 'media').toUpperCase() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Bloque: Logística y Destino -->
                    <div class="info-card shadow-premium border-t-4 border-t-slate-800 bg-white p-6 rounded-3xl border border-slate-100">
                        <div class="section-title !mb-6">
                            <i class="fas fa-truck text-slate-800"></i> 2. Logística de Entrega
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="label-mini text-red-800 label-large font-bold uppercase">Nombre del Receptor:</label>
                                <p class="text-base font-black value-text text-slate-900 uppercase break-words leading-tight">
                                    {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.nombre || pedido.receiver_nombre || 'S/D') : (pedido.cliente?.contacto || 'TITULAR DE CUENTA') }}
                                </p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label class="label-mini label-large">RFC</label>
                                    <p class="text-[10px] font-mono font-black text-slate-700 value-text uppercase tracking-tighter truncate">
                                        {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.rfc || pedido.receiver_rfc || 'N/A') : (pedido.cliente?.rfc || 'N/A') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="label-mini label-large text-slate-400">Régimen Fiscal</label>
                                    <p class="text-[10px] font-black text-red-600 value-text uppercase leading-tight truncate">
                                        {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.receiver_regimen_fiscal || pedido.receiver_regimen_fiscal || 'SIN DATO') : (pedido.cliente?.regimen_fiscal || 'SIN DATO') }}
                                    </p>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-50 min-w-0">
                                <label class="label-mini label-large text-slate-400">Correo y Contacto</label>
                                <p class="text-xs font-bold text-slate-800 value-text truncate lowercase" style="text-transform: none !important;">
                                    <i class="fas fa-envelope mr-1 text-red-300"></i>
                                    {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.correo || pedido.receiver_correo || '---') : (pedido.cliente?.email || '---') }}
                                </p>
                                <p class="text-xs font-bold text-slate-800 value-text mt-1 uppercase">
                                    <i class="fas fa-phone mr-1 text-red-300"></i>
                                    {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.telefono || pedido.receiver_telefono || 'N/A') : (pedido.cliente?.telefono || 'N/A') }}
                                </p>
                            </div>

                            <div>
                                <label class="label-mini label-large text-slate-400 uppercase">Ubicación de Destino</label>
                                <div class="text-[10px] text-slate-600 leading-relaxed font-medium italic bg-slate-50 p-3 rounded-xl border border-slate-100 break-words uppercase">
                                    <i class="fas value-text fa-map-marker-alt text-red-500 mr-1"></i> 
                                    {{ formatFullAddress(pedido) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bloque: Resumen de Envío -->
                    <div class="info-card shadow-premium border-t-4 border-t-red-700 bg-white p-6 rounded-3xl border border-slate-100">
                        <div class="section-title !mb-6">
                            <i class="fas fa-box-open text-red-700"></i> 3. Estatus de Envío
                        </div>
                        <div class="space-y-6">
                            <div>
                                <label class="label-mini label-large">Estado de la Orden</label>
                                <span :class="getStatusClass(pedido.status)" class="status-badge w-full text-center value-text py-2.5 shadow-sm rounded-xl">
                                    {{ pedido.status }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div class="bg-red-50/30 p-4 rounded-2xl border border-red-100">
                                    <label class="label-mini label-large">Método de Envío</label>
                                    <span class="text-xs font-black text-red-700 value-text uppercase block">{{ getDeliveryOption(pedido.delivery_option) }}</span>
                                </div>
                                
                                <div v-if="pedido.delivery_option === 'paqueteria'" class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                    <label class="label-mini label-large">Paquetería Sugerida</label>
                                    <span class="text-xs font-black text-slate-800 value-text uppercase block">{{ pedido.paqueteria_nombre || 'POR DEFINIR' }}</span>
                                </div>

                                <div v-else-if="pedido.commentary_delivery_option" class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                    <label class="label-mini label-large">Instrucciones Logísticas</label>
                                    <span class="text-[10px] font-bold text-slate-500 value-text uppercase leading-tight block">
                                        {{ pedido.commentary_delivery_option }}
                                    </span>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                                <span class="text-[10px] font-black text-slate-400 label-large uppercase tracking-widest">Unidades:</span>
                                <span class="text-sm value-text font-black text-slate-700 uppercase">{{ calculateTotalItems(pedido.detalles) }} Materiales</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. DESGLOSE DE MATERIALES -->
                <div class="mt-8">
                    <div class="info-card !p-0 shadow-premium border border-slate-200 rounded-[2.5rem] bg-white overflow-hidden">
                        <div class="p-8 border-b border-slate-50 flex items-center gap-3">
                            <i class="fas fa-list-ol text-red-700"></i>
                            <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Selección Técnica de Materiales</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse bg-white min-w-[800px]">
                                <thead class="bg-slate-900 text-white uppercase text-[9px] font-black tracking-[0.2em]">
                                    <tr>
                                        <th class="px-6 py-5 text-left">Libro / Título</th>
                                        <th class="px-6 py-5 text-center w-32">Formato</th>
                                        <th class="px-6 py-5 text-center w-40">Rubro</th>
                                        <th class="px-6 py-5 text-center w-24">Cant.</th>
                                        <th class="px-6 py-5 text-right w-32">P. Unitario</th>
                                        <th class="px-6 py-5 text-right w-32">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="detalle in pedido.detalles" :key="detalle.id" class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-5">
                                            <p class="font-black text-black label-large text-[13px] uppercase leading-tight">
                                                {{ detalle.libro?.titulo || 'Material no identificado' }}
                                            </p>
                                            <p class="text-[9px] font-mono font-black text-red-600 mt-1 uppercase tracking-widest bg-red-50/50 px-2 py-0.5 rounded border border-red-100 inline-block">
                                                ISBN: {{ detalle.libro?.ISBN || 'N/A' }}
                                            </p>
                                        </td>
                                        <td class="px-6 py-5 text-center"><span class="badge-format-red">{{ detalle.tipo_licencia }}</span></td>
                                        <td class="px-6 py-5 text-center"><span class="badge-format-red">{{ (detalle.tipo || 'VENTA').toUpperCase() }}</span></td>
                                        <td class="px-6 py-5 text-center font-black text-red-700 text-lg">{{ detalle.cantidad }}</td>
                                        <td class="px-6 py-5 text-right font-mono text-[11px] text-red-600 font-black">{{ formatCurrency(detalle.precio_unitario) }}</td>
                                        <td class="px-6 py-5 text-right font-black text-red-700 text-[14px]">{{ formatCurrency(detalle.costo_total) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-slate-900 border-t border-white/10">
                                    <tr>
                                        <td colspan="5" class="px-8 py-8 text-right font-black uppercase text-[10px] tracking-[0.2em] text-slate-400">Total Neto del Expediente:</td>
                                        <td class="px-6 py-8 text-right font-black text-3xl text-red-400 leading-none tracking-tighter">{{ formatCurrency(totalOrderCostNum) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 4. OBSERVACIONES GENERALES -->
                <div v-if="pedido.comments" class="info-card border-none bg-amber-50/50 p-8 rounded-[2.5rem] border border-amber-100 shadow-sm relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center opacity-40">
                        <i class="fas fa-quote-right text-3xl text-amber-300 rotate-12"></i>
                    </div>
                    <h3 class="text-[10px] font-black text-amber-600 uppercase mb-3 tracking-widest flex items-center gap-2 relative z-10">
                        <i class="fas fa-comment-dots"></i> 4. Comentarios Generales del Pedido
                    </h3>
                    <p class="text-sm value-text text-slate-700 italic leading-relaxed whitespace-pre-wrap font-medium relative z-10">"{{ pedido.comments }}"</p>
                </div>

                <!-- 5. HISTORIAL DE MODIFICACIONES (AUDITORÍA) -->
                <div class="info-card shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                    <div class="section-title !mb-8 !border-b-0">
                        <i class="fas fa-history text-slate-800"></i> 5. Historial de Ajustes Académicos
                    </div>

                    <div v-if="pedido.logs && pedido.logs.length" class="relative">
                        <!-- Línea de tiempo vertical -->
                        <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gradient-to-b from-red-300 via-slate-200 to-transparent hidden md:block"></div>

                        <div class="space-y-5">
                            <div
                                v-for="(log, index) in pedido.logs"
                                :key="log.id"
                                class="log-card relative md:pl-16 group"
                            >
                                <!-- Dot en la línea de tiempo -->
                                <div
                                    class="absolute left-3.5 top-6 w-5 h-5 rounded-full border-4 border-white shadow-md hidden md:flex items-center justify-center z-10"
                                    :class="index === 0 ? 'bg-red-600' : 'bg-slate-300'"
                                ></div>

                                <!-- Tarjeta del log -->
                                <div
                                    class="rounded-2xl border overflow-hidden transition-all duration-300 group-hover:shadow-lg group-hover:-translate-y-0.5"
                                    :class="index === 0
                                        ? 'border-red-200 bg-gradient-to-br from-red-50 to-white shadow-sm shadow-red-100'
                                        : 'border-slate-100 bg-white shadow-sm'"
                                >
                                    <!-- Header de la tarjeta -->
                                    <div
                                        class="flex items-center justify-between px-6 py-3 border-b"
                                        :class="index === 0 ? 'border-red-100 bg-red-600/5' : 'border-slate-50 bg-slate-50'"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="w-6 h-6 rounded-lg flex items-center justify-center text-[9px] font-black text-white shrink-0"
                                                :class="index === 0 ? 'bg-red-600' : 'bg-slate-400'"
                                            >
                                                #{{ pedido.logs.length - index }}
                                            </span>
                                            <span
                                                class="text-[9px] font-black uppercase tracking-widest"
                                                :class="index === 0 ? 'text-red-600' : 'text-slate-400'"
                                            >
                                                {{ index === 0 ? 'Último Ajuste Registrado' : 'Ajuste Anterior' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="text-[9px] font-black text-white px-2.5 py-1 rounded-lg uppercase tracking-wider"
                                                :class="index === 0 ? 'bg-slate-900' : 'bg-slate-300'"
                                            >
                                                <i class="fas fa-user-shield mr-1 opacity-60"></i>
                                                {{ log.user?.name || 'Sistema' }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Cuerpo de la tarjeta -->
                                    <div class="px-6 py-5 flex flex-col sm:flex-row justify-between gap-4 items-start sm:items-center">
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-[9px] font-black uppercase tracking-widest mb-2 flex items-center gap-1.5"
                                                :class="index === 0 ? 'text-red-500' : 'text-slate-400'"
                                            >
                                                <i class="fas fa-comment-alt text-[8px]"></i>
                                                Motivo del Ajuste:
                                            </p>
                                            <p class="text-sm font-bold text-slate-800 italic leading-snug">
                                                "{{ log.motivo_cambio || 'Sin comentario registrado.' }}"
                                            </p>
                                        </div>

                                        <!-- Fecha -->
                                        <div class="shrink-0 flex flex-col items-end gap-1 text-right border-l pl-6 border-slate-100">
                                            <i class="fas fa-calendar-alt text-slate-200 text-xl"></i>
                                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Fecha</p>
                                            <p class="text-xs font-black text-slate-700 uppercase whitespace-nowrap">{{ formatDate(log.created_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado cuando no hay ediciones -->
                    <div v-else class="text-center py-16 bg-slate-50/50 rounded-[2rem] border-2 border-dashed border-slate-200 flex flex-col items-center">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
                            <i class="fas fa-check-circle text-slate-200 text-2xl"></i>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Este pedido no ha sido editado</p>
                        <p class="text-[9px] text-slate-300 uppercase mt-1 italic">Mantiene su integridad original desde el registro.</p>
                    </div>
                </div>

                <!-- 6. EXPEDIENTE DIGITAL -->
                <div class="info-card shadow-premium border-l-8 border-l-slate-800 bg-white p-6 rounded-3xl overflow-hidden border border-slate-100">
                    <div class="section-title !border-b-0 mb-6">
                        <i class="fas fa-folder-open text-slate-800 "></i> 6. Expediente Digital y Documentos
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="flex items-center justify-between p-5 rounded-2xl border-2 transition-all" 
                             :class="pedido.factura_path ? 'border-red-50 bg-red-50/20' : 'border-slate-50 bg-slate-50/30 opacity-60'">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-white shadow-sm shrink-0">
                                    <i class="fas fa-file-invoice text-xl" :class="pedido.factura_path ? 'text-red-600' : 'text-slate-300'"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest label-large">Archivo Factura</p>
                                    <p class="text-xs font-bold value-text text-slate-700 truncate">{{ pedido.factura_path ? 'PDF DISPONIBLE' : 'NO CARGADA' }}</p>
                                </div>
                            </div>
                            <a v-if="pedido.factura_path" :href="pedido.factura_url" target="_blank" class="btn-icon-action bg-red-600 shrink-0 shadow-lg shadow-red-100 hover:scale-110 transition-transform"><i class="fas fa-download"></i></a>
                        </div>
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
        error.value = err.response?.data?.message || 'No se pudieron cargar los detalles técnicos del pedido.';
    } finally {
        loading.value = false;
    }
};

const totalOrderCostNum = computed(() => {
    if (!pedido.value || !pedido.value.detalles) return 0;
    return pedido.value.detalles.reduce((sum, d) => sum + (parseFloat(d.costo_total) || 0), 0);
});

const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
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
        case 'alta': return 'bg-red-600 text-white font-black px-3 shadow-sm rounded-lg text-[10px] uppercase';
        case 'media': return 'bg-orange-100 text-orange-700 font-bold border border-orange-200 rounded-lg text-[10px] uppercase';
        case 'baja': return 'bg-slate-100 text-slate-400 border border-slate-200 rounded-lg text-[10px] uppercase';
        default: return 'bg-slate-50 text-slate-300 rounded-lg text-[10px] uppercase';
    }
};

const formatFullAddress = (p) => {
    if (p.delivery_address) return p.delivery_address;
    if (p.receiver_type === 'nuevo' && (p.delivery_calle_num || p.receptor?.direccion)) {
        return p.delivery_calle_num 
            ? `${p.delivery_calle_num}, COL. ${p.delivery_colonia}, ${p.delivery_municipio}, CP ${p.delivery_cp}`
            : (p.receptor?.direccion || 'DIRECCIÓN NO ESPECIFICADA');
    }
    return p.cliente?.direccion || 'ENTREGA EN SUCURSAL / OFICINA';
};

const getDeliveryOption = (option) => {
    switch (option) {
        case 'recoleccion': return 'RECOLECCIÓN EN ALMACÉN';
        case 'paqueteria': return 'PAQUETERÍA SUGERIDA';
        case 'entrega': return 'ENTREGA DIRECTA';
        default: return 'ENTREGA DIRECTA';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-MX', { 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    window.scrollTo(0, 0);
    fetchPedidoDetail();
});
</script>

<style scoped>
.info-card { background: white; padding: 25px; border-radius: 24px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #000000; margin-bottom: 25px; border-bottom: 2px solid #fee2e2; padding-bottom: 12px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 2px; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-[0.1em]; }
.status-badge { padding: 4px 14px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; text-transform: uppercase; }

.shadow-premium { box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05); }

.table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }

.badge-format-red {
    display: inline-block;
    font-size: 10px;
    font-weight: 900;
    color: #b91c1c;
    text-transform: uppercase;
    background-color: #ffffff;
    padding: 4px 12px;
    border-radius: 8px;
    border: 1px solid #fee2e2;
    letter-spacing: 0.05em;
}

.btn-icon-action { width: 34px; height: 34px; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0; border: none; cursor: pointer; }
.btn-edit-action { @apply bg-slate-900 text-white py-2.5 px-6 rounded-xl text-xs font-black transition-all hover:bg-slate-700; border: none; cursor: pointer; }
.btn-secondary-custom { @apply bg-white border-2 border-slate-200 py-2.5 px-6 rounded-xl text-xs font-black transition-all hover:bg-slate-50 text-black; cursor: pointer; }

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.value-text { color: #be5e5e; line-height: 1.4; }
.label-large { display: block; font-size: 0.72rem; font-weight: 900; text-transform: uppercase; color: #000000; margin-bottom: 6px; letter-spacing: 0.12em; opacity: 0.8; }

/* Log timeline cards */
.log-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
</style>