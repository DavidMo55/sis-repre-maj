<template>
    <div class="content-wrapper p-2 md:p-6">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight">Detalle de Paquete de Gastos</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium">Información resumida y desglose de conceptos con comprobantes.</p>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button @click="router.push('/gastos')" class="btn-secondary flex-1 sm:flex-none flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
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
                <button @click="fetchGastoDetail" class="btn-primary mt-6 px-10 py-3 rounded-2xl shadow-lg">Intentar de nuevo</button>
            </div>

            <!-- Contenido del Gasto -->
            <div v-else-if="gasto" class="space-y-8 animate-fade-in">
                
                <!-- 1. RESUMEN EJECUTIVO (TOP BAR) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="summary-stat shadow-premium">
                        <span class="label-xs">ID Registro</span>
                        <p class="stat-value">#{{ gasto.id }}</p>
                    </div>
                    <div class="summary-stat shadow-premium">
                        <span class="label-xs">Fecha del Viaje</span>
                        <p class="stat-value">{{ formatDate(gasto.fecha) }}</p>
                    </div>
                    <div class="summary-stat shadow-premium">
                        <span class="label-xs">Región / Estado</span>
                        <p class="stat-value uppercase truncate">{{ gasto.estado_nombre || 'No definido' }}</p>
                    </div>
                    <div class="summary-stat shadow-premium bg-slate-900 border-none">
                        <span class="label-xs text-slate-400">Inversión Total</span>
                        <p class="stat-value text-red-400">{{ formatCurrency(gasto.monto) }}</p>
                    </div>
                </div>

                <!-- 2. TABLA UNIFICADA: CONCEPTOS + COMPROBANTES -->
                <div class="form-section shadow-premium !p-0 overflow-hidden border border-slate-200 rounded-[2rem] bg-white">
                    <div class="p-6 md:p-8 border-b border-slate-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h2 class="text-lg font-black text-slate-800 uppercase tracking-tight">Desglose de Conceptos y Archivos</h2>
                        </div>
                        
                    </div>

                    <div class="overflow-x-auto">
                        <div class="table-responsive table-shadow-lg mt-8 border rounded-xl overflow-hidden shadow-sm">
                        <table class="min-width-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="table-header w-16 text-center">#</th>
                                    <th class="table-header">Concepto / Rubro</th>
                                    <th class="table-header text-center">Documento Probatorio</th>
                                    <th class="table-header text-center">Estatus Fiscal</th>
                                    <th class="table-header text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="(sub, idx) in gasto.detalles" :key="idx" class="hover:bg-gray-50 transition-colors group">
                                    <td class="table-cell text-center font-black text-slate-300">
                                        {{ idx + 1 }}
                                    </td>

                                    <td class="table-cell">
                                        <p class="font-black text-slate-800 text-sm uppercase leading-tight">
                                            {{ sub.concept }}
                                        </p>
                                        <p v-if="sub.descripcion" class="text-[10px] text-slate-400 mt-1 italic">
                                            {{ sub.descripcion }}
                                        </p>
                                    </td>

                                    <td class="table-cell text-center">
                                        <div v-if="gasto.comprobantes && gasto.comprobantes[idx]" class="inline-flex items-center">
                                            <a :href="gasto.comprobantes[idx].public_url" 
                                            target="_blank" 
                                            class="btn-file-view">
                                                <i class="fas mr-2" :class="getFileIcon(gasto.comprobantes[idx].extension)"></i>
                                                VER ARCHIVO
                                            </a>
                                        </div>
                                        <button v-else 
                                            @click="showUploadModal = true"
                                            class="btn-file-upload"
                                        >
                                            <i class="fas fa-plus-circle mr-2"></i>
                                            SUBIR COMPROBANTE
                                        </button>
                                    </td>

                                    <td class="table-cell text-center">
                                        <span :class="sub.es_facturado ? 'badge-tax-success' : 'badge-tax-none'">
                                            {{ sub.es_facturado ? 'FACTURADO' : 'SIN FACTURA' }}
                                        </span>
                                    </td>

                                    <td class="table-cell text-right font-black text-red-700 text-base">
                                        {{ formatCurrency(sub.monto) }}
                                    </td>
                                </tr>
                            </tbody>
                            
                            <tfoot class="bg-slate-900">
                                <tr>
                                    <td colspan="4" class="px-8 py-6 text-right font-black uppercase text-[10px] tracking-[0.2em] text-slate-400">
                                        Total Acumulado del Paquete:
                                    </td>
                                    <td class="px-6 py-6 text-right font-black text-2xl text-red-400">
                                        {{ formatCurrency(gasto.monto) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    </div>
                </div>

                <!-- Comentarios Generales -->
                <div v-if="gasto.comments" class="bg-amber-50 p-8 rounded-[2.5rem] border border-amber-100 shadow-sm animate-fade-in">
                    <h3 class="text-[10px] font-black text-amber-600 uppercase mb-3 tracking-widest"><i class="fas fa-comment-alt mr-2"></i> Notas de Almacén / Oficina</h3>
                    <p class="text-slate-700 text-sm font-medium italic leading-relaxed">"{{ gasto.comments }}"</p>
                </div>
            </div>
        </div>

        <UploadComprobanteModal 
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
        error.value = 'No se pudo recuperar la información del paquete. Verifique su conexión.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '$0.00';
    return Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};

const getFileIcon = (ext) => {
    const e = ext?.toLowerCase();
    if (e === 'pdf') return 'fa-file-pdf text-red-500';
    return 'fa-file-image text-blue-500';
};

onMounted(fetchGastoDetail);
</script>

<style scoped>
.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05); }
.form-section { background: white; padding: 25px; border-radius: 24px; border: 1px solid #f1f5f9; }

.summary-stat {
    background: white;
    padding: 20px 25px;
    border-radius: 24px;
    border: 1px solid #f1f5f9;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 900;
    color: #1e293b;
    margin-top: 4px;
    letter-spacing: -0.025em;
}

.label-xs {
    font-size: 0.6rem;
    color: #94a3b8;
    text-transform: uppercase;
    font-weight: 900;
    display: block;
    letter-spacing: 0.1em;
}

.status-badge {
    @apply px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest inline-flex items-center;
}

.btn-primary-action {
    background: linear-gradient(135deg, #a93339 0%, #881337 100%);
    color: white;
    border-radius: 14px;
    font-weight: 900;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.btn-primary-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(169, 51, 57, 0.2);
}

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
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

.table-header {
    padding: 14px 16px;
    font-size: 0.7rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.15em;
}

.table-cell {
    padding: 16px;
    vertical-align: middle;
}

/* Botón Ver Archivo */
.btn-file-view {
    display: inline-flex;
    align-items: center;
    background-color: white;
    border: 1px solid #e2e8f0;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 900;
    color: #475569;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-file-view:hover {
    border-color: #dc2626;
    color: #b91c1c;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Botón Subir Archivo */
.btn-file-upload {
    display: inline-flex;
    align-items: center;
    background-color: #fef2f2;
    border: 1px solid #fee2e2;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 900;
    color: #dc2626;
    transition: all 0.2s;
    cursor: pointer;
}

.btn-file-upload:hover {
    background-color: #dc2626;
    color: white;
}

/* Badges Fiscales */
.badge-tax-success {
    background-color: #dcfce7;
    color: #15803d;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 9px;
    font-weight: 800;
}

.badge-tax-none {
    background-color: #f1f5f9;
    color: #94a3b8;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 9px;
    font-weight: 800;
}

/* Footer Especial */
tfoot tr td {
    border-top: 2px solid #1e293b;
}

/* Auxiliares */
.w-16 { width: 4rem; }
.text-right { text-align: right; }
.text-center { text-align: center; }
</style>