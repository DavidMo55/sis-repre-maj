<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen overflow-x-hidden">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    <h1 v-if="gasto" class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight break-words uppercase">
                        DETALLES DEL PAQUETE DE GASTOS
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
                
                <!-- 1. INFORMACIÓN DE VIAJE -->
                <div class="info-card shadow-premium border-t-8 border-t-slate-800 bg-white p-6 rounded-[2.5rem] border border-slate-100">
                    <h2 class="text-xl label-large font-black text-slate-800 uppercase tracking-tight">1. información de viaje</h2>
                   
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
                        <div class="summary-stat shadow-premium border-t-4 border-t-slate-800">
                            <span class="label-lb">Fecha del Viaje</span>
                            <p class="value-text">{{ formatDate(gasto.fecha) }}</p>
                        </div>
                        <div class="summary-stat shadow-premium border-t-4 border-t-red-700">
                            <span class="label-lb">Estado</span>
                            <p class="value-text uppercase truncate" :title="gasto.estado_nombre">{{ gasto.estado_nombre || 'No definido' }}</p>
                        </div>
                        <div class="summary-stat shadow-premium border-t-4" 
                             :class="gasto.status === 'FINALIZADO' ? 'border-t-green-600' : 'border-t-amber-500'">
                            <span class="label-lb">Estatus</span>
                            <p class="value-text flex items-center gap-2" 
                               :class="gasto.status === 'FINALIZADO' ? 'text-green-700' : 'text-amber-600'">
                                <i class="fas" :class="gasto.status === 'FINALIZADO' ? 'fa-check-circle' : 'fa-pen-nib'"></i>
                                {{ gasto.status }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 2. TABLA: DESGLOSE DE CONCEPTOS -->
                <div class="info-card shadow-premium !p-0 overflow-hidden border border-slate-200 rounded-[2.5rem] bg-white">
                    <div class="p-6 md:p-8 border-b border-slate-50 flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-50 text-red-600 rounded-xl flex items-center justify-center shadow-sm">
                            <i class="fas fa-receipt text-lg"></i>
                        </div>
                        <h2 class="text-xl label-large font-black text-slate-800 uppercase tracking-tight">2. DETALLE DE CONCEPTOS Y COMPROBANTES</h2>
                    </div>

                    <div class="overflow-x-auto p-4 md:p-8">
                        <div class="table-container mt-4 animate-fade-in">
                            <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white">
                                <table class="min-width-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="table-header text-center w-16">N</th>
                                        <th class="table-header">CONCEPTO / DESCRIPCIÓN</th>
                                        <th class="table-header text-center">COMPROBANTE</th>
                                        <th class="table-header text-right w-40">MONTO</th>
                                        <th class="table-header text-center w-32">FACTURA</th>
                                    </tr>
                                </thead>

                                <tbody class="bg-white bk divide-y divide-gray-100">
                                    <tr v-for="(sub, idx) in gasto.detalles" :key="idx" class="hover:bg-gray-50 transition-colors">
                                        <td class="table-cell text-center font-black text-slate-300">
                                            {{ idx + 1 }}
                                        </td>
                                        <td class="table-cell">
                                            <p class="font-black text-slate-800 text-sm uppercase leading-tight">
                                                {{ sub.concepto || 'Sin rubro' }}
                                            </p>
                                            <div class="mt-1.5 flex items-start gap-2">
                                                <i class="fas fa-caret-right text-red-600 mt-0.5"></i>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase leading-relaxed tracking-tighter">
                                                    {{ sub.descripcion }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="table-cell text-center">
                                            <div v-if="gasto.comprobantes && gasto.comprobantes[idx]" class="inline-flex items-center">
                                                <a :href="getViewableUrl(gasto.comprobantes[idx].public_url)" 
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="btn-note !bg-white hover:!border-red-600 hover:!text-red-600 flex items-center gap-2">
                                                    <i class="fas text-[10px]" :class="getFileIcon(gasto.comprobantes[idx].extension)"></i>
                                                    Comprobante
                                                </a>
                                            </div>
                                            <span v-else class="text-[10px] font-black text-slate-300 uppercase italic">Faltante</span>
                                        </td>
                                        <td class="table-cell text-right font-black text-red-800 text-base tracking-tighter">
                                            {{ formatCurrency(sub.monto) }}
                                        </td>
                                        <td class="table-cell text-center">
                                            <span class="status-badge" :class="sub.es_facturado ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-slate-100 text-slate-500 border border-slate-200'">
                                                {{ sub.es_facturado ? 'FACTURA' : 'S/F' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>

                                <tfoot class="bg-slate-50 border-t-2 border-slate-100">
                                    <tr>
                                        <!-- CORRECCIÓN: colspan="3" para alinear el label antes del Monto -->
                                        <td colspan="3" class="px-8 py-6 text-right font-black uppercase text-[10px] tracking-[0.2em] text-slate-400">
                                            Total del Paquete:
                                        </td>
                                        <!-- CORRECCIÓN: El monto aparece bajo la columna 4 (Monto) -->
                                        <td class="px-6 py-6 text-right font-black text-2xl text-red-700 leading-none tracking-tighter border-x border-slate-200/50">
                                            {{ formatCurrency(gasto.monto) }}
                                        </td>
                                        <!-- Celda vacía para la columna Factura -->
                                        <td class="bg-slate-50"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                </div>

                <!-- 3. OBSERVACIONES ORIGINALES -->
                <div v-if="gasto.comments" class="bg-white p-8 md:p-10 rounded-[3rem] border border-amber-200 shadow-premium relative overflow-hidden mx-2">
                    <div class="absolute -right-6 -top-6 w-32 h-32 bg-amber-50 rounded-full flex items-center justify-center opacity-40">
                        <i class="fas fa-quote-right text-6xl text-amber-200 rotate-12"></i>
                    </div>
                    <h3 class="text-[10px] font-black text-amber-600 uppercase mb-5 tracking-[0.2em] flex items-center gap-2 relative z-10">
                        <i class="fas fa-comment-dots"></i> 3. Notas del Representante
                    </h3>
                    <div class="relative z-10 bg-amber-50/50 p-6 rounded-3xl border border-dashed border-amber-200">
                        <p class="text-slate-800 text-base font-bold italic leading-relaxed whitespace-pre-wrap">
                            "{{ gasto.comments }}"
                        </p>
                    </div>
                </div>

                <!-- 4. HISTORIAL DE AJUSTES (REACTIVO) -->
                <div v-if="gasto.logs && gasto.logs.length > 0" class="info-card shadow-premium border-t-8 border-t-slate-800 bg-white p-0 rounded-[2.5rem] border border-slate-100 overflow-hidden mt-16 animate-fade-in">
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between bg-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-history"></i>
                            </div>
                            <h2 class="text-xl label-large font-black text-slate-800 uppercase tracking-tight">4. Modificaciones</h2>
                        </div>
                    </div>

                    <div class="table-container mt-4 p-8 pt-0">
                        <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white">
                            <table class="min-width-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="table-header text-center w-24">N</th>
                                        <th class="table-header">Motivo de la Modificación</th>
                                        <th class="table-header w-56">Responsable</th>
                                        <th class="table-header text-right w-48">Sincronización</th>
                                    </tr>
                                </thead>
                                
                                <tbody class="bg-white bk divide-y divide-gray-100">
                                    <tr v-for="(log, index) in gasto.logs" :key="log.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="table-cell text-center">
                                            <div class="flex justify-center">
                                                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-black border-2" 
                                                      :class="index === 0 ? 'bg-red-50 border-red-100 text-red-600' : 'bg-slate-50 border-slate-100 text-slate-400'">
                                                   {{ gasto.logs.length - index }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="table-cell">
                                            <p class="text-[11px] font-bold text-slate-700 italic leading-relaxed uppercase">
                                                "{{ log.motivo_cambio || 'SIN JUSTIFICACIÓN TÉCNICA REGISTRADA' }}"
                                            </p>
                                        </td>
                                        <td class="table-cell">
                                            <div class="flex items-center gap-2">
                                                
                                                <span class="text-[11px] font-black text-slate-800 uppercase tracking-tight">
                                                    {{ log.user?.name || 'Sistema' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="table-cell text-right">
                                            <div class="flex flex-col items-end">
                                                <span class="text-[11px] font-black text-slate-800 uppercase leading-none tracking-tighter">
                                                    {{ formatDateOnly(log.created_at) }}
                                                </span>
                                                <span class="text-[9px] font-bold text-slate-400 mt-1.5 uppercase tracking-widest italic">
                                                    {{ formatTimeOnly(log.created_at) }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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

.label-large { display: block; font-size: 0.79rem; font-weight: 900; text-transform: uppercase; color: #000000; margin-bottom: 6px; letter-spacing: 0.12em; opacity: 0.8; }
.label-lb { display: block; font-size: 0.70rem; font-weight: 900; text-transform: uppercase; color: #000000; margin-bottom: 6px; letter-spacing: 0.12em; opacity: 0.8; }

.table-responsive { width: 100%; overflow-x: auto; background: white; }
.table-cell { padding: 20px 24px; vertical-align: middle; }

.table-header { padding: 14px 16px; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; text-align: left; letter-spacing: 0.025em; }

.status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; display: inline-flex; align-items: center; }

.btn-note { padding: 8px 15px; border-radius: 12px; color: #64748b; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; cursor: pointer; border: 1px solid #cbd5e1; transition: 0.2s; text-decoration: none; }

.btn-primary { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; border-radius: 16px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.btn-secondary { padding: 10px 20px; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.2s; }

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.value-text { color: #be5e5e; line-height: 1.4; font-size: 1rem; }

.table-shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02); }
</style>