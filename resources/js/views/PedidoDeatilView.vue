<template>
    <div class="content-wrapper p-2 md:p-6">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight">
                        Detalle del Pedido #{{ pedido && pedido.display_id ? pedido.display_id : id }}
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium">Consulta la información logística, financiera y el desglose de materiales.</p>
                </div>
                <button @click="router.push('/pedidos')" class="btn-secondary flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </button>
            </div>
            
            <!-- Loader -->
            <div v-if="loading" class="loading-state py-20 text-center animate-pulse">
                <i class="fas fa-circle-notch fa-spin text-4xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Sincronizando información...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="error-message-container p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle fa-2xl"></i>
                </div>
                <h2 class="text-xl font-black text-red-800 uppercase tracking-tighter">Error de Sistema</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium">{{ error }}</p>
                <button @click="fetchPedidoDetail" class="btn-primary mt-6 px-10 py-3 rounded-2xl shadow-lg">Reintentar</button>
            </div>

            <div v-else-if="pedido" class="space-y-8 animate-fade-in">
                
                <!-- 1. GRID DE INFORMACIÓN GENERAL -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- Datos del Cliente -->
                    <div class="info-card shadow-premium border-t-4 border-t-red-700">
                        <div class="section-title">
                            <i class="fas fa-user-tag text-red-700"></i> 1. Datos del Cliente
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="label-mini lbb ">Plantel / Distribuidor</label>
                                <p class="text-sm font-black text-slate-800 uppercase leading-tight">{{ pedido.cliente.name }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label-mini lbb">Perfil</label>
                                    <br>
                                   
                                    <span :class="pedido.cliente.tipo === 'CLIENTE' ? 'bg-red-50 text-red-700 border-red-100' : 'bg-blue-50 text-blue-700 border-blue-100'" class="status-badge">
                                        {{ pedido.cliente.tipo }}
                                    </span>
                                </div>
                                <div>
                                     <br>
                                    <label class="label-mini lbb">RFC</label>
                                    <p class="text-[10px] font-mono font-black text-slate-500 uppercase">{{ pedido.cliente.rfc || 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-50">
                                <label class="label-mini lbb">Contacto Directo</label>
                                <p class="text-xs font-bold text-slate-600">{{ pedido.cliente.contacto }}</p>
                                <p class="text-xs text-slate-400 mt-1"><i class="fas fa-phone-alt mr-1"></i> {{ pedido.cliente.telefono }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Logística -->
                    <div class="info-card shadow-premium border-t-4 border-t-slate-800">
                        <div class="section-title">
                            <i class="fas fa-truck text-slate-800"></i> 2. Entrega y Logística
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="label-mini lbb">Destinatario Final</label>
                                <p class="text-sm font-black text-slate-800">{{ getReceiverName(pedido) }}</p>
                            </div>
                            <div>
                                <label class="label-mini lbb">Dirección de Envío</label>
                                <p class="text-[11px] text-slate-500 leading-relaxed italic">
                                    <i class="fas fa-map-marker-alt text-red-500 mr-1"></i> 
                                    {{ pedido.delivery_address || 'Entrega en Sucursal / Oficina' }}
                                </p>
                            </div>
                            <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <div>
                                    <label class="label-mini lbb">Método</label>
                                    <br>
                                    <span class="text-[10px] font-black text-red-700 uppercase">{{ getDeliveryOption(pedido.delivery_option) }}</span>
                                    <br>
                                </div>
                                <br>
                                    <label class="label-mini lbb">Empresa</label>
                                    <br>
                                    <span class="text-[10px] font-bold text-slate-800">{{ pedido.paqueteria_nombre }}</span>
                                    <br>
                            
                            </div>
                            <br>
                            <div>
                                <label class="label-mini lbb">Estatus Actual</label>
                                <br>
                                <span :class="getStatusClass(pedido.status)" class="status-badge w-full text-center">
                                    {{ pedido.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Finanzas y Estatus -->
                    <div class="info-card shadow-premium border-t-4 border-t-red-800 bg-slate-50/30">
                        <div class="section-title">
                            <i class="fas fa-wallet text-red-800"></i> 3. Resumen Financiero
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <div>
                                    <label class="label-mini lbb">Tipo de Operación</label>
                                    <p class="text-[10px] font-black uppercase" :class="pedido.tipo_pedido === 'promocion' ? 'text-purple-600' : 'text-slate-800'">
                                        {{ pedido.tipo_pedido === 'promocion' ? 'PROMOCIÓN' : 'VENTA NORMAL' }}
                                    </p>
                                </div>
                                    <label class="label-mini lbb">Prioridad</label>
                                    <br>
                                    <p :class="getPriorityClass(pedido.prioridad)" class="status-badge mt-1">
                                        
                                        {{ (pedido.prioridad || 'media').toUpperCase() }}
                                    </p>
                            </div>
                            
                            <div class="pt-4 border-t border-slate-200">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[10px] font-black text-slate-400 uppercase lbb">Volumen total:</span>
                                    <br>
                                    <span class="text-xs font-black text-slate-700">{{ calculateTotalItems(pedido.detalles) }} Libros</span>
                                </div>
                                <br><br>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-black text-red-900 uppercase lbb">Inversión Final:</span>
                                    <br>
                                    <span class="text-2xl font-black text-red-700 tracking-tighter">{{ totalOrderCost }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. APARTADO DE ARCHIVOS ADJUNTOS (DISEÑO INTEGRADO) -->
                <div class="info-card shadow-premium border-l-8 border-l-slate-800">
                    <div class="section-title !border-b-0 mb-6">
                        <i class="fas fa-folder-open text-slate-800"></i> Expediente Digital y Documentos
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Factura del Pedido -->
                        <div class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all" 
                             :class="pedido.factura_path ? 'border-red-50 bg-red-50/20' : 'border-slate-50 bg-slate-50/30 opacity-60'">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white shadow-sm">
                                    <i class="fas fa-file-invoice text-xl" :class="pedido.factura_path ? 'text-red-600' : 'text-slate-300'"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest lbb">Factura</p>
                                    <p class="text-xs font-bold text-slate-700">{{ pedido.factura_path ? 'PDF Disponible' : 'No cargada' }}</p>
                                </div>
                            </div>
                            <a v-if="pedido.factura_path" :href="pedido.factura_url" target="_blank" class="btn-icon-action bg-red-600">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>

                        <!-- Guía de Envío -->
                        <div class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all" 
                             :class="pedido.guia_envio_path ? 'border-blue-50 bg-blue-50/20' : 'border-slate-50 bg-slate-50/30 opacity-60'">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white shadow-sm">
                                    <i class="fas fa-shipping-fast text-xl" :class="pedido.guia_envio_path ? 'text-blue-600' : 'text-slate-300'"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest lbb">Guía</p>
                                    <p class="text-xs font-bold text-slate-700">{{ pedido.guia_envio_path ? 'Rastreo Listo' : 'Pendiente' }}</p>
                                </div>
                            </div>
                            <a v-if="pedido.guia_envio_path" :href="pedido.guia_url" target="_blank" class="btn-icon-action bg-blue-600">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <br><br>
                <!-- 3. DESGLOSE DE MATERIALES -->
                <div class="mt-8">
                    <div class="section-title !border-none !mb-4">
                        <i class="fas fa-book text-red-800"></i> Libros Solicitados
                    </div>
                    
                    <div class="table-responsive shadow-premium border border-slate-200 rounded-[2rem] overflow-hidden bg-white">
                       <div class="table-responsive table-shadow-lg mt-6 border rounded-xl overflow-hidden shadow-sm">
    <table class="min-width-full divide-y divide-gray-200">
        <thead class="bg-red-800 text-white hidden md:table-header-group">
            <tr>
                <th class="table-header-red text-left">Material / Libro</th>
                <th class="table-header-red text-center">Formato</th>
                <th class="table-header-red text-center">Cant.</th>
                <th class="table-header-red text-right">Unitario</th>
                <th class="table-header-red text-right">Total</th>
                <th class="table-header-red text-center">Adjunto</th>
            </tr>
        </thead>

        <tbody class="bg-white divide-y divide-slate-100 block md:table-row-group">
            <tr v-for="detalle in pedido.detalles" :key="detalle.id" 
                class="hover:bg-slate-50 transition-colors block md:table-row p-5 md:p-0 relative border-b md:border-none">
                
                <td class="table-cell block md:table-cell">
                    <p class="font-black text-slate-800 text-sm uppercase leading-tight">
                        {{ detalle.libro?.titulo || 'Material no encontrado' }}
                    </p>
                    <p class="text-[9px] font-mono text-slate-400 mt-1 uppercase tracking-widest">
                        ISBN: {{ detalle.libro?.ISBN || 'N/A' }}
                    </p>
                </td>

                <td class="table-cell text-center block md:table-cell">
                    <span class="md:hidden inline-block text-[9px] font-black text-slate-400 uppercase mr-2 tracking-tighter">Formato:</span>
                    <span class="badge-format">{{ detalle.tipo_licencia }}</span>
                </td>

                <td class="table-cell text-center block md:table-cell font-black text-red-700 text-base">
                    <span class="md:hidden inline-block text-[9px] font-black text-slate-400 uppercase mr-2 tracking-tighter">Cant:</span>
                    {{ detalle.cantidad }}
                </td>

                <td class="table-cell text-right block md:table-cell font-mono text-[11px] text-slate-500">
                    <span class="md:hidden inline-block text-[9px] font-black text-slate-400 uppercase mr-2 tracking-tighter">Precio:</span>
                    {{ formatCurrency(detalle.precio_unitario) }}
                </td>

                <td class="table-cell text-right block md:table-cell font-black text-slate-800 text-[14px]">
                    <span class="md:hidden inline-block text-[9px] font-black text-slate-400 uppercase mr-2 tracking-tighter">Subtotal:</span>
                    {{ formatCurrency(detalle.costo_total) }}
                </td>

                <td class="table-cell text-center block md:table-cell">
                    <span class="md:hidden inline-block text-[9px] font-black text-slate-400 uppercase mr-2 tracking-tighter">Adjunto:</span>
                    <a v-if="detalle.archivo_path" :href="detalle.archivo_url" target="_blank" class="link-pdf-icon">
                        <i class="fas fa-file-pdf fa-lg"></i>
                    </a>
                    <span v-else class="text-slate-200 text-[10px] italic">--</span>
                </td>
            </tr>
        </tbody>

        <tfoot class="bg-slate-50 border-t-2 border-slate-200 hidden md:table-footer-group">
            <tr>
                <td colspan="4" class="px-8 py-6 text-right font-black uppercase text-[10px] tracking-[0.2em] text-slate-400">Total Acumulado del Pedido:</td>
                <td class="px-6 py-6 text-right font-black text-2xl text-red-700 leading-none">
                    {{ formatCurrency(totalOrderCost) }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
                    </div>
                </div>

                <br><br>
                <!-- Comentarios -->
                <div v-if="pedido.comments" class="info-card border-none bg-amber-50/50 p-8 rounded-[2.5rem] border border-amber-100">
                    <h3 class="text-[10px] font-black text-amber-600 uppercase mb-3 tracking-widest flex items-center gap-2">
                        <i class="fas fa-comment-dots lbb1"></i> Observaciones Adicionales
                    </h3>
                    <p class="text-sm text-slate-700 italic leading-relaxed">{{ pedido.comments }}</p>
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
             error.value = 'No se pudieron cargar los detalles del pedido profesional.';
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
    return new Date(dateString).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const calculateTotalItems = (detalles) => detalles ? detalles.reduce((sum, item) => sum + item.cantidad, 0) : 0;

const getStatusClass = (status) => {
    const base = 'status-badge ';
    switch (status) {
        case 'ENTREGADO': return base + 'bg-green-100 text-green-700 border border-green-200';
        case 'PENDIENTE': return base + 'bg-yellow-100 text-yellow-700 border border-yellow-200';
        case 'EN PROCESO': return base + 'bg-blue-100 text-blue-700 border border-blue-200';
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

const getReceiverName = (p) => {
    if (p.receiver_type === 'nuevo') return `${p.receiver_nombre || 'No especificado'} (Temporal)`; 
    return p.cliente?.contacto || p.cliente?.name || 'Titular de Cuenta'; 
};

const getDeliveryOption = (option) => {
    switch (option) {
        case 'recoleccion': return 'ALMACÉN';
        case 'paqueteria': return 'PAQUETERÍA';
        case 'none': return 'ESTÁNDAR';
        default: return 'SUCURSAL';
    }
};

onMounted(() => fetchPedidoDetail());
</script>

<style scoped>
.info-card { background: white; padding: 25px; border-radius: 24px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #1e293b; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-[0.1em];  }
.status-badge { padding: 4px 14px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; text-transform: uppercase; }

.shadow-premium { box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05); }

.table-header-red {
    padding: 18px 24px;
    font-size: 0.7rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    background-color: #f0f0f0;
    color: rgb(94, 94, 94);
}
.table-responsive {
    width: 100%;
    overflow-x: auto;
    background: white;
}
.lbb{
    color: black;
    font-weight: bold;
}
.lbb1{
    font-weight: bold;
}
table {
    width: 100%;
    border-collapse: collapse;
}

/* Cabecera Roja Especial */
.table-header-red {
    padding: 16px 24px;
    font-size: 0.7rem;
    font-weight: 800;
    color: #676767;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.table-cell {
    padding: 16px 24px;
    vertical-align: middle;
}

/* Badge de Formato */
.badge-format {
    display: inline-block;
    font-size: 10px;
    font-weight: 900;
    color: #64748b; /* Slate-500 */
    text-transform: uppercase;
    background-color: #f1f5f9; /* Slate-100 */
    padding: 4px 12px;
    border-radius: 9999px;
    border: 1px solid #e2e8f0;
}

/* Icono PDF */
.link-pdf-icon {
    color: #dc2626; /* Red-600 */
    transition: all 0.2s;
}

.link-pdf-icon:hover {
    color: #991b1b; /* Red-800 */
    transform: scale(1.1);
}

/* Sombras y Helpers */
.table-shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
}

.text-right { text-align: right; }
.text-center { text-align: center; }
.text-left { text-align: left; }

/* Responsive labels para Mobile */
@media (max-width: 767px) {
    .table-cell {
        padding: 8px 0;
    }
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
}

.btn-icon-action:hover {
    transform: scale(1.1);
    opacity: 0.9;
}

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 640px) {
    .info-card { padding: 20px; }
}
</style>