<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Detalle de Gasto</h1>
                    <p>Información detallada del paquete y documentos probatorios adjuntos.</p>
                </div>
                <button @click="router.push('/gastos')" class="btn-secondary flex-row-centered gap-2">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </button>
            </div>

            <div v-if="loading" class="loading-state py-12 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-red-600 mb-3"></i>
                <p class="text-slate-500 font-medium">Recuperando información ...</p>
            </div>

            <div v-else-if="error" class="error-message-container mt-6 p-8 text-center bg-red-50 border border-red-200 rounded-xl">
                <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i>
                <h2 class="text-xl font-bold text-red-800">No se pudo cargar el detalle</h2>
                <p class="text-red-600 mt-2">{{ error }}</p>
                <button @click="fetchGastoDetail" class="btn-primary mt-6">Reintentar</button>
            </div>

            <div v-else-if="gasto" class="detail-container mt-6 animate-fade-in space-y-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Resumen del Gasto -->
                    <div class="lg:col-span-1">
                        <div class="info-card border-brand shadow-premium h-full">
                            <h2 class="card-title text-red-800">
                                <i class="fas fa-file-invoice-dollar"></i> Resumen
                            </h2>
                            <div class="space-y-4 mt-6">
                                <div>
                                    <span class="label-xs">ID Registro:</span>
                                    <span class="text-sm font-bold text-slate-700">#{{ gasto.id }}</span>
                                </div>
                                <div>
                                    <span class="label-xs">Fecha:</span>
                                    <span class="text-sm font-bold text-slate-700">{{ formatDate(gasto.fecha) }}</span>
                                </div>
                                <div>
                                    <span class="label-xs">Región / Estado:</span>
                                    <span class="text-sm font-bold text-slate-700 uppercase">{{ gasto.estado_nombre || 'No especificado' }}</span>
                                </div>
                                <div class="pt-4 border-t border-slate-50">
                                    <span class="label-xs text-red-700">Monto Total:</span>
                                    <span class="text-3xl font-black text-red-700">{{ formatCurrency(gasto.monto) }}</span>
                                </div>
                            </div>
                            <button @click="showUploadModal = true" class="btn-primary w-full mt-8 shadow-lg">
                                <i class="fas fa-plus"></i> Añadir Comprobante
                            </button>
                        </div>
                    </div>

                    <!-- Documentos Adjuntos -->
                    <div class="lg:col-span-2">
                        <div class="info-card bg-white shadow-premium h-full">
                            <h2 class="card-title text-slate-800">
                                <i class="fas fa-folder-open text-red-700"></i> Documentos Adjuntos ({{ gasto.comprobantes?.length || 0 }})
                            </h2>

                            <div v-if="!gasto.comprobantes || gasto.comprobantes.length === 0" class="empty-docs py-12 text-center text-slate-400">
                                <i class="fas fa-file-excel text-5xl mb-4 opacity-20"></i>
                                <p class="italic">No hay documentos adjuntos todavía.</p>
                            </div>

                            <div v-else class="comprobantes-grid mt-6">
                                <div 
                                    v-for="file in gasto.comprobantes" 
                                    :key="file.id" 
                                    class="file-card group"
                                >
                                    <div class="file-preview-area">
                                        <!-- Previsualización Real -->
                                        <img 
                                            v-if="isImage(file.extension)" 
                                            :src="file.public_url" 
                                            class="preview-img"
                                            @error="handleImageError"
                                        />
                                        <div v-else class="file-icon-placeholder">
                                            <i :class="getFileIcon(file.extension)" class="text-4xl"></i>
                                            <span class="mt-2 text-[10px] font-black uppercase text-slate-400">{{ file.extension }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="file-info p-3 bg-white">
                                        <p class="file-name truncate text-[10px] font-bold text-slate-500 uppercase">{{ file.name }}</p>
                                        <div class="file-actions mt-2">
                                            <a :href="file.public_url" target="_blank" class="btn-file-view">
                                                <i class="fas fa-external-link-alt mr-1"></i> Ver pantalla completa
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DESGLOSE DE SUB-GASTOS (PAQUETE) -->
                <div v-if="gasto.detalles && gasto.detalles.length" class="info-card w-full shadow-premium animate-fade-in">
                    <h2 class="card-title text-slate-800 border-0 mb-6">
                        <i class="fas fa-list-ul text-red-700"></i> Desglose Detallado de Conceptos
                    </h2>
                    
                    <div class="overflow-hidden border border-slate-100 rounded-[2rem] bg-white shadow-sm">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">#</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Concepto / Rubro</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Estatus Fiscal</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(sub, idx) in gasto.detalles" :key="idx" class="hover:bg-red-50/30 transition-colors">
                                    <td class="px-6 py-4 font-black text-slate-300">{{ idx + 1 }}</td>
                                    <td class="px-6 py-4 font-bold text-slate-700">{{ sub.concepto }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="sub.es_facturado" class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[9px] font-black uppercase border border-green-200">Facturado</span>
                                        <span v-else class="bg-slate-100 text-slate-400 px-3 py-1 rounded-full text-[9px] font-black uppercase border border-slate-200">Sin Factura</span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-slate-800">{{ formatCurrency(sub.monto) }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-900 text-white">
                                <tr>
                                    <td colspan="3" class="px-6 py-5 text-right font-black uppercase text-[11px] tracking-widest">Total Acumulado:</td>
                                    <td class="px-6 py-5 text-right font-black text-xl text-red-400">{{ formatCurrency(gasto.monto) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
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
        error.value = 'No se pudo cargar el detalle del gasto. Verifique su conexión.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const handleImageError = (e) => {
    e.target.style.display = 'none';
    e.target.parentElement.innerHTML = '<i class="fas fa-image text-gray-300 text-4xl"></i>';
};

const formatDate = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '$0.00';
    return Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};

const isImage = (ext) => ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext?.toLowerCase());

const getFileIcon = (ext) => {
    const e = ext?.toLowerCase();
    if (e === 'pdf') return 'fas fa-file-pdf text-red-600';
    return 'fas fa-file-alt text-gray-400';
};

onMounted(fetchGastoDetail);
</script>

<style scoped>
.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05); }
.info-card { padding: 28px; background: white; border-radius: 32px; border: 1px solid #f1f5f9; }
.info-card.border-brand { border-top: 6px solid #a93339; }

.card-title { font-size: 1.1rem; font-weight: 900; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; }

.label-xs { font-size: 0.65rem; color: #94a3b8; text-transform: uppercase; font-weight: 900; display: block; margin-bottom: 4px; letter-spacing: 0.05em; }

.comprobantes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
.file-card { border: 1px solid #f1f5f9; border-radius: 20px; overflow: hidden; transition: transform 0.3s; }
.file-card:hover { transform: translateY(-5px); border-color: #fee2e2; }

.file-preview-area { height: 140px; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; border-bottom: 1px solid #f1f5f9; }
.preview-img { width: 100%; height: 100%; object-fit: cover; }

.btn-file-view { display: block; text-align: center; padding: 10px; background: #f8fafc; border-radius: 12px; font-size: 0.7rem; font-weight: 800; color: #64748b; text-decoration: none; text-transform: uppercase; border: 1px solid #e2e8f0; transition: all 0.2s; }
.btn-file-view:hover { background: #a93339; color: white; border-color: #a93339; }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>