<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen overflow-x-hidden">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    <h1 v-if="gasto" class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight break-words">
                        DETALLE DE PAQUETE #{{ gasto.id }}
                    </h1>
                    <h1 v-else-if="loading" class="text-2xl font-black text-slate-300 animate-pulse uppercase">Sincronizando...</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium uppercase tracking-widest mt-1">Expediente de comprobación y bitácora de auditoría.</p>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <!-- REGLA DE NEGOCIO: Botón inteligente de edición -->
                    <button 
                        v-if="gasto && (gasto.status === 'BORRADOR' || (gasto.status === 'FINALIZADO' && (gasto.modificaciones_finalizadas || 0) < 1))"
                        @click="router.push({ name: 'GastoEdit', params: { id: gasto.id } })" 
                        class="btn-primary flex-1 sm:flex-none flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-black shadow-sm transition-all"
                    >
                        <i class="fas fa-edit"></i> 
                        {{ gasto.status === 'BORRADOR' ? 'EDITAR BORRADOR' : 'EDITAR (ÚNICA VEZ)' }}
                    </button>

                    <button @click="router.push('/gastos')" class="btn-secondary flex-1 sm:flex-none flex items-center justify-center gap-2 px-8 py-3 rounded-2xl text-sm font-black shadow-sm transition-all border-2 border-slate-200 bg-white hover:bg-slate-50">
                        <i class="fas fa-arrow-left"></i> VOLVER
                    </button>
                </div>
            </div>

            <!-- Estado de Carga -->
            <div v-if="loading" class="loading-state py-20 text-center animate-pulse">
                <i class="fas fa-circle-notch fa-spin text-4xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Cargando datos del servidor...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="error-message-container p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle fa-2xl"></i>
                </div>
                <h2 class="text-xl font-black text-red-800 uppercase tracking-tighter">Error de Acceso</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium">{{ error }}</p>
                <button @click="fetchGastoDetail" class="btn-primary-action mt-6 px-10 py-3 rounded-2xl shadow-lg">Reintentar Conexión</button>
            </div>

            <!-- Contenido del Gasto -->
            <div v-else-if="gasto" class="space-y-8 animate-fade-in pb-20">
                
                <!-- 1. RESUMEN EJECUTIVO (TOP BAR) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="summary-stat shadow-premium border-t-4 border-t-slate-800">
                        <span class="label-xs">Fecha del Viaje</span>
                        <p class="stat-value">{{ formatDate(gasto.fecha) }}</p>
                    </div>
                    <div class="summary-stat shadow-premium border-t-4 border-t-red-700">
                        <span class="label-xs">Estado / Región</span>
                        <p class="stat-value uppercase truncate" :title="gasto.estado_nombre">{{ gasto.estado_nombre || 'No definido' }}</p>
                    </div>
                    <div class="summary-stat shadow-premium bg-slate-900 border-none">
                        <span class="label-xs text-slate-400">Inversión Total</span>
                        <p class="stat-value text-red-400 font-black">{{ formatCurrency(gasto.monto) }}</p>
                    </div>
                    <div class="summary-stat shadow-premium border-t-4" 
                         :class="gasto.status === 'FINALIZADO' ? 'border-t-green-600' : 'border-t-amber-500'">
                        <span class="label-xs">Estatus Técnico</span>
                        <p class="stat-value flex items-center gap-2" 
                           :class="gasto.status === 'FINALIZADO' ? 'text-green-700' : 'text-amber-600'">
                            <i class="fas" :class="gasto.status === 'FINALIZADO' ? 'fa-check-circle' : 'fa-pen-nib'"></i>
                            {{ gasto.status }}
                        </p>
                    </div>
                </div>

                <!-- 2. TABLA: DESGLOSE DE CONCEPTOS -->
                <div class="info-card shadow-premium !p-0 overflow-hidden border border-slate-200 rounded-[2.5rem] bg-white">
                    <div class="p-6 md:p-8 border-b border-slate-50 flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-50 text-red-600 rounded-xl flex items-center justify-center shadow-sm">
                            <i class="fas fa-receipt text-lg"></i>
                        </div>
                        <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Desglose de Conceptos</h2>
                    </div>

                    <div class="overflow-x-auto p-4 md:p-8">
                        <div class="table-responsive border rounded-[2rem] overflow-hidden shadow-sm bg-white">
                            <table class="min-width-full divide-y divide-gray-200">
                                <thead class="bg-slate-900">
                                    <tr class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-left">
                                        <th class="px-6 py-5 text-center w-16">#</th>
                                        <th class="px-6 py-5">Rubro / Detalles</th>
                                        <th class="px-6 py-5 text-center">Comprobante</th>
                                        <th class="px-6 py-5 text-center w-32">Estatus</th>
                                        <th class="px-6 py-5 text-right w-40">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="(sub, idx) in gasto.detalles" :key="idx" class="hover:bg-slate-50/50 transition-colors">
                                        <td class="table-cell text-center font-black text-slate-300">
                                            {{ idx + 1 }}
                                        </td>
                                        <td class="table-cell">
                                            <p class="font-black text-slate-800 text-sm uppercase leading-tight">
                                                {{ sub.concepto || 'Sin rubro' }}
                                            </p>
                                            <div v-if="sub.descripcion" class="mt-1.5 flex items-start gap-2">
                                                <i class="fas fa-caret-right text-red-400 mt-0.5"></i>
                                                <p class="text-[9px] text-slate-400 font-bold uppercase leading-relaxed tracking-tighter">
                                                    {{ sub.descripcion }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="table-cell text-center">
                                            <div v-if="gasto.comprobantes && gasto.comprobantes[idx]" class="inline-flex items-center">
                                                <a :href="getViewableUrl(gasto.comprobantes[idx].public_url)" 
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="btn-file-view">
                                                    <i class="fas mr-2 text-[11px]" :class="getFileIcon(gasto.comprobantes[idx].extension)"></i>
                                                    EXPEDIENTE
                                                </a>
                                            </div>
                                            <span v-else class="text-[9px] font-black text-slate-300 uppercase italic">Faltante</span>
                                        </td>
                                        <td class="table-cell text-center">
                                            <span :class="sub.es_facturado ? 'badge-tax-success' : 'badge-tax-none'">
                                                {{ sub.es_facturado ? 'FACTURADO' : 'S/F' }}
                                            </span>
                                        </td>
                                        <td class="table-cell text-right font-black text-red-700 text-base tracking-tighter">
                                            {{ formatCurrency(sub.monto) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-slate-900 border-t border-slate-800">
                                    <tr>
                                        <td colspan="4" class="px-8 py-8 text-right font-black uppercase text-[11px] tracking-[0.2em] text-slate-400">
                                            Suma Total del Paquete:
                                        </td>
                                        <td class="px-6 py-8 text-right font-black text-3xl text-red-400 leading-none tracking-tighter">
                                            {{ formatCurrency(gasto.monto) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 3. OBSERVACIONES ORIGINALES -->
                <div v-if="gasto.comments" class="bg-white p-8 md:p-10 rounded-[3rem] border border-amber-200 shadow-premium relative overflow-hidden mx-2">
                    <div class="absolute -right-6 -top-6 w-32 h-32 bg-amber-50 rounded-full flex items-center justify-center opacity-40">
                        <i class="fas fa-quote-right text-6xl text-amber-200 rotate-12"></i>
                    </div>
                    <h3 class="text-[10px] font-black text-amber-600 uppercase mb-5 tracking-[0.2em] flex items-center gap-2 relative z-10">
                        <i class="fas fa-comment-dots"></i> Notas del Representante
                    </h3>
                    <div class="relative z-10 bg-amber-50/50 p-6 rounded-3xl border border-dashed border-amber-200">
                        <p class="text-slate-800 text-base font-bold italic leading-relaxed whitespace-pre-wrap">
                            "{{ gasto.comments }}"
                        </p>
                    </div>
                </div>

                <!-- 4. HISTORIAL DE AJUSTES (AUDITORÍA TÉCNICA) -->
                <div class="info-card shadow-premium border-t-8 border-t-slate-800 bg-white p-0 rounded-[2.5rem] border border-slate-100 overflow-hidden mt-16">
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between bg-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-history"></i>
                            </div>
                            <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Bitácora de Ajustes Técnicos</h2>
                        </div>
                        <span v-if="gasto.logs?.length" class="text-[9px] font-black bg-red-600 text-white px-3 py-1 rounded-full uppercase tracking-widest">
                            {{ gasto.logs.length }} MODIFICACIONES
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse">
                            <thead class="bg-slate-50 text-slate-400 uppercase text-[9px] font-black tracking-[0.2em] border-b border-slate-100">
                                <tr>
                                    <th class="px-8 py-4 text-center w-24">ID</th>
                                    <th class="px-6 py-4 text-left">Motivo de la Modificación</th>
                                    <th class="px-6 py-4 text-left w-56">Responsable</th>
                                    <th class="px-8 py-4 text-right w-48">Sincronización</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 bg-white">
                                <tr v-for="(log, index) in gasto.logs" :key="log.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-8 py-6 text-center">
                                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-black border-2" 
                                              :class="index === 0 ? 'bg-red-50 border-red-100 text-red-600' : 'bg-slate-50 border-slate-100 text-slate-400'">
                                           {{ gasto.logs.length - index }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-6">
                                        <p class="text-[13px] font-bold text-slate-700 italic leading-relaxed uppercase">
                                            {{ log.motivo_cambio || 'SIN JUSTIFICACIÓN TÉCNICA REGISTRADA' }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-6">
                                        <div class="flex items-center gap-2">
                                           
                                            <span class="text-[11px] font-black text-slate-800 uppercase tracking-tight">
                                                {{ log.user?.name || 'Sistema' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="text-[11px] font-black text-slate-800 uppercase tracking-tighter">{{ formatDateOnly(log.created_at) }}</span>
                                            <br>
                                            <span class="text-[9px] font-bold text-slate-400 mt-1 tracking-widest">{{ formatTimeOnly(log.created_at) }}</span>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr v-if="!gasto.logs || gasto.logs.length === 0">
                                    <td colspan="4" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center opacity-40">
                                            <i class="fas fa-shield-alt text-4xl text-slate-300 mb-4"></i>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Expediente con integridad original</p>
                                            <p class="text-[9px] text-slate-400 mt-1 italic uppercase tracking-wider">No se han registrado ajustes posteriores al registro inicial.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <UploadComprobanteModal 
            v-if="gasto"
            :visible="showUploadModal"
            :gasto="gasto"
            @close="showUploadModal = false"
            @uploaded="fetchGastoDetail"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../axios';
import UploadComprobanteModal from '../components/Modales/SubidaComprobantes.vue';

const route = useRoute();
const router = useRouter();
const gastoId = route.params.id;

const gasto = ref(null);
const loading = ref(true);
const error = ref(null);
const showUploadModal = ref(false);

const fetchGastoDetail = async () => {
    loading.value = true;
    error.value = null;
    try {
        // CORRECCIÓN: Solicitamos explícitamente los logs con sus usuarios para la tabla de auditoría
        const response = await axios.get(`/gastos/${gastoId}`);
        gasto.value = response.data;
    } catch (err) {
        error.value = err.response?.data?.message || "Expediente no localizado. Verifique su conexión al servidor.";
    } finally {
        loading.value = false;
    }
};

const getViewableUrl = (url) => {
    if (!url) return '#';
    return url.replace('dl=1', 'dl=0');
};

const formatDate = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatDateOnly = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatTimeOnly = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
};

const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};

const getFileIcon = (ext) => {
    const e = ext?.toLowerCase();
    return e === 'pdf' ? 'fa-file-pdf text-red-600' : 'fa-file-image text-blue-600';
};

onMounted(fetchGastoDetail);
</script>

<style scoped>
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.info-card { background: white; border: 1px solid #f1f5f9; }

.summary-stat {
    background: white;
    padding: 25px;
    border-radius: 35px;
    border: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.stat-value {
    font-size: 1.4rem;
    font-weight: 900;
    color: #1e293b;
    margin-top: 6px;
    letter-spacing: -0.025em;
    line-height: 1.2;
}

.label-xs {
    font-size: 0.65rem;
    color: #94a3b8;
    text-transform: uppercase;
    font-weight: 900;
    display: block;
    letter-spacing: 0.15em;
}

.table-responsive { width: 100%; overflow-x: auto; background: white; }
.table-cell { padding: 20px 24px; vertical-align: middle; }

/* Badges Fiscales */
.badge-tax-success { background-color: #dcfce7; color: #15803d; padding: 6px 14px; border-radius: 12px; font-size: 9px; font-weight: 900; border: 1px solid #bbf7d0; }
.badge-tax-none { background-color: #f1f5f9; color: #94a3b8; padding: 6px 14px; border-radius: 12px; font-size: 9px; font-weight: 900; }

/* Botón Archivo */
.btn-file-view {
    display: inline-flex;
    align-items: center;
    background-color: white;
    border: 2px solid #f1f5f9;
    padding: 10px 18px;
    border-radius: 14px;
    font-size: 9px;
    font-weight: 900;
    color: #475569;
    transition: all 0.2s;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.btn-file-view:hover { border-color: #dc2626; color: #b91c1c; background-color: #fef2f2; }

.btn-primary { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; border-radius: 16px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.section-title { font-weight: 900; color: #1e293b; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 2px; }
</style>