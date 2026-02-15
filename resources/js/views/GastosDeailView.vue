<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    <h1 v-if="gasto" class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight break-words">
                        Detalle de Paquete #{{ gasto.id }}
                    </h1>
                    <h1 v-else-if="loading" class="text-2xl font-black text-slate-300 animate-pulse uppercase">Cargando...</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium uppercase tracking-widest mt-1">Desglose técnico de comprobantes y auditoría.</p>
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
                <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Sincronizando información...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="error-message-container p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle fa-2xl"></i>
                </div>
                <h2 class="text-xl font-black text-red-800 uppercase tracking-tighter">Error de Conexión</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium">{{ error }}</p>
                <button @click="fetchGastoDetail" class="btn-primary-action mt-6 px-10 py-3 rounded-2xl shadow-lg">Intentar de nuevo</button>
            </div>

            <!-- Contenido del Gasto -->
            <div v-else-if="gasto" class="space-y-8 animate-fade-in">
                
                <!-- 1. RESUMEN EJECUTIVO (TOP BAR) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="summary-stat shadow-premium border-t-4 border-t-slate-800">
                        <span class="label-xs">Fecha del Viaje</span>
                        <p class="stat-value">{{ formatDate(gasto.fecha) }}</p>
                    </div>
                    <div class="summary-stat shadow-premium border-t-4 border-t-red-700">
                        <span class="label-xs">Región / Estado</span>
                        <p class="stat-value uppercase truncate" :title="gasto.estado_nombre">{{ gasto.estado_nombre || 'No definido' }}</p>
                    </div>
                    <div class="summary-stat shadow-premium bg-slate-900 border-none">
                        <span class="label-xs text-slate-400">Monto Total</span>
                        <p class="stat-value text-red-400 font-black">{{ formatCurrency(gasto.monto) }}</p>
                    </div>
                    <div class="summary-stat shadow-premium border-t-4" 
                         :class="gasto.status === 'FINALIZADO' ? 'border-t-green-600' : 'border-t-amber-500'">
                        <span class="label-xs">Estatus del Registro</span>
                        <p class="stat-value flex items-center gap-2" 
                           :class="gasto.status === 'FINALIZADO' ? 'text-green-700' : 'text-amber-600'">
                            <i class="fas" :class="gasto.status === 'FINALIZADO' ? 'fa-check-circle' : 'fa-pen-nib'"></i>
                            {{ gasto.status }}
                        </p>
                    </div>
                </div>

                <!-- 2. TABLA UNIFICADA: CONCEPTOS + COMPROBANTES -->
                <div class="form-section shadow-premium !p-0 overflow-hidden border border-slate-200 rounded-[2.5rem] bg-white">
                    <div class="p-6 md:p-8 border-b border-slate-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center shadow-sm">
                                <i class="fas fa-receipt text-xl"></i>
                            </div>
                            <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Desglose de Conceptos</h2>
                        </div>
                    </div>

                    <div class="overflow-x-auto p-4 md:p-8">
                        <div class="table-responsive table-shadow-lg border rounded-3xl overflow-hidden shadow-sm bg-white">
                            <table class="min-width-full divide-y divide-gray-200">
                                <thead class="bg-slate-900">
                                    <tr class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                        <th class="px-6 py-5 text-center w-16">#</th>
                                        <th class="px-6 py-5 text-left">Concepto / Descripción</th>
                                        <th class="px-6 py-5 text-center">Comprobante</th>
                                        <th class="px-6 py-5 text-center">Tipo</th>
                                        <th class="px-6 py-5 text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="(sub, idx) in gasto.detalles" :key="idx" class="hover:bg-slate-50 transition-colors group">
                                        <td class="table-cell text-center font-black text-slate-300">
                                            {{ idx + 1 }}
                                        </td>

                                        <td class="table-cell">
                                            <p class="font-black text-slate-800 text-sm uppercase leading-tight">
                                                {{ sub.concepto || 'Sin rubro' }}
                                            </p>
                                            <!-- COMENTARIO/DESCRIPCIÓN POR FILA -->
                                            <div v-if="sub.descripcion" class="mt-2 bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest leading-relaxed">
                                                    <i class="fas fa-info-circle mr-1 text-slate-400"></i>
                                                    {{ sub.descripcion }}
                                                </p>
                                            </div>
                                        </td>

                                        <td class="table-cell text-center">
                                            <div v-if="gasto.comprobantes && gasto.comprobantes[idx]" class="inline-flex items-center">
                                                <a :href="getViewableUrl(gasto.comprobantes[idx].public_url)" 
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="btn-file-view group-hover:border-red-600 transition-all">
                                                    <i class="fas mr-2" :class="getFileIcon(gasto.comprobantes[idx].extension)"></i>
                                                    VER ARCHIVO
                                                </a>
                                            </div>
                                            <div v-else>
                                                <button v-if="gasto.status === 'BORRADOR'" 
                                                    @click="showUploadModal = true"
                                                    class="btn-file-upload shadow-sm hover:shadow-md"
                                                >
                                                    <i class="fas fa-plus-circle mr-2"></i>
                                                    PENDIENTE
                                                </button>
                                                <span v-else class="text-[9px] font-black text-slate-300 uppercase italic">Faltante</span>
                                            </div>
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
                                
                                <tfoot class="bg-slate-900 border-t-2 border-slate-800">
                                    <tr>
                                        <td colspan="4" class="px-8 py-8 text-right font-black uppercase text-[11px] tracking-[0.2em] text-slate-400">
                                            Inversión Total del Paquete:
                                        </td>
                                        <td class="px-6 py-8 text-right font-black text-3xl text-red-400 tracking-tighter leading-none">
                                            {{ formatCurrency(gasto.monto) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 3. COMENTARIOS GENERALES / MOTIVO DE AJUSTE (ESTILO PREMIUM) -->
                <div v-if="gasto.comments" class="comments-section bg-white p-8 md:p-10 rounded-[3rem] border border-amber-200 shadow-premium relative overflow-hidden animate-fade-in mx-2">
                    <div class="absolute -right-6 -top-6 w-32 h-32 bg-amber-50 rounded-full flex items-center justify-center opacity-50">
                        <i class="fas fa-quote-right text-6xl text-amber-200 rotate-12"></i>
                    </div>
                    
                    <h3 class="text-[11px] font-black text-amber-600 uppercase mb-6 tracking-[0.2em] flex items-center gap-3 relative z-10">
                        <div class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></div>
                        Observaciones y Motivo del Ajuste
                    </h3>
                    
                    <div class="relative z-10 bg-amber-50/50 p-6 rounded-[2rem] border border-dashed border-amber-200 shadow-inner">
                        <p class="text-slate-700 text-base md:text-lg font-medium italic leading-relaxed whitespace-pre-wrap">
                            "{{ gasto.comments }}"
                        </p>
                    </div>
                </div>

                <!-- Banner Informativo para Borradores -->
                <div v-if="gasto.status === 'BORRADOR'" class="bg-amber-50 border-2 border-dashed border-amber-200 p-6 rounded-[2.5rem] flex items-center gap-4 animate-fade-in shadow-sm">
                    <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-info-circle fa-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-black text-amber-900 uppercase">Paquete en modo borrador</p>
                        <p class="text-xs text-amber-700 font-medium leading-relaxed">
                            Este registro aún permite ediciones. Una vez revisados los conceptos y archivos, use el botón <strong>Editar Borrador</strong> para finalizar.
                        </p>
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
        error.value = err.response?.data?.message || "No se pudo recuperar la información del paquete. Verifique su conexión.";
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
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};

const getFileIcon = (ext) => {
    const e = ext?.toLowerCase();
    return e === 'pdf' ? 'fa-file-pdf text-red-500' : 'fa-file-image text-blue-500';
};

onMounted(fetchGastoDetail);
</script>

<style scoped>
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.form-section { background: white; padding: 25px; border-radius: 24px; border: 1px solid #f1f5f9; }

.summary-stat {
    background: white;
    padding: 25px;
    border-radius: 30px;
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

.btn-secondary-custom { @apply bg-white border-2 border-slate-200 text-slate-600 hover:bg-slate-50 text-xs; }
.btn-edit-action { @apply bg-slate-900 text-white hover:bg-slate-800 text-xs shadow-xl; }

.btn-primary-action {
    background: linear-gradient(135deg, #a93339 0%, #881337 100%);
    color: white;
    padding: 12px 30px;
    border-radius: 14px;
    font-weight: 900;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    text-transform: uppercase;
}

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.table-responsive {
    width: 100%;
    overflow-x: auto;
    background: white;
}

table {
    table-layout: fixed;
    width: 100%;
}

.table-cell {
    padding: 20px 24px;
    vertical-align: middle;
}

/* Botón Ver Archivo */
.btn-file-view {
    display: inline-flex;
    align-items: center;
    background-color: white;
    border: 2px solid #f1f5f9;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 9px;
    font-weight: 900;
    color: #475569;
    transition: all 0.2s;
    text-decoration: none;
    text-transform: uppercase;
}

.btn-file-view:hover {
    border-color: #dc2626;
    color: #b91c1c;
    background-color: #fef2f2;
}

/* Botón Subir Archivo */
.btn-file-upload {
    display: inline-flex;
    align-items: center;
    background-color: #fffbeb;
    border: 2px dashed #fcd34d;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 9px;
    font-weight: 900;
    color: #b45309;
    transition: all 0.2s;
    cursor: pointer;
    text-transform: uppercase;
}

.btn-file-upload:hover {
    background-color: #b45309;
    border-style: solid;
    color: white;
}

/* Badges Fiscales */
.badge-tax-success {
    background-color: #dcfce7;
    color: #15803d;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 9px;
    font-weight: 900;
    border: 1px solid #bbf7d0;
}

.badge-tax-none {
    background-color: #f1f5f9;
    color: #94a3b8;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 9px;
    font-weight: 900;
}

.table-shadow-lg {
    box-shadow: 0 4px 20px -5px rgba(0, 0, 0, 0.05);
}

.btn-primary { 
    background: linear-gradient(135deg, #a93339 0%, #881337 100%); 
    color: white; 
    border-radius: 20px; 
    font-weight: 900; 
    cursor: pointer; 
    border: none; 
    box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); 
    transition: all 0.2s; 
    text-transform: uppercase; 
    font-size: 0.8rem; 
    letter-spacing: 0.05em; 
}
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.btn-secondary {
    padding: 8px 15px;
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    cursor: pointer;
}
</style>