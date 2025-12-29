<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Detalle de Gasto</h1>
                    <p>Información detallada y documentos probatorios adjuntos.</p>
                </div>
                <button @click="router.push('/gastos')" class="btn-secondary flex-row-centered gap-2">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </button>
            </div>

            <div v-if="loading" class="loading-state py-10">
                <i class="fas fa-spinner fa-spin text-3xl text-red-600 mb-3"></i>
                <p>Cargando información del gasto...</p>
            </div>

            <div v-else-if="error" class="error-message p-6 text-center bg-red-50 rounded-lg">
                <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                <p>{{ error }}</p>
            </div>

            <div v-else-if="gasto" class="detail-container mt-6">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Resumen del Gasto -->
                    <div class="lg:col-span-1">
                        <div class="info-card border-brand shadow-sm">
                            <h2 class="card-title text-red-800">
                                <i class="fas fa-file-invoice-dollar"></i> Resumen
                            </h2>
                            <div class="gasto-data-list mt-4">
                                <div class="data-item">
                                    <span class="label">ID Registro:</span>
                                    <span class="value">#{{ gasto.id }}</span>
                                </div>
                                <div class="data-item">
                                    <span class="label">Fecha:</span>
                                    <span class="value font-bold">{{ formatDate(gasto.fecha) }}</span>
                                </div>
                                <div class="data-item">
                                    <span class="label">Concepto:</span>
                                    <span class="value">{{ gasto.concepto }}</span>
                                </div>
                                <div class="data-item">
                                    <span class="label">Monto Total:</span>
                                    <span class="value text-2xl font-black text-red-700">{{ formatCurrency(gasto.monto) }}</span>
                                </div>
                            </div>
                            <button @click="showUploadModal = true" class="btn-primary w-full mt-6">
                                <i class="fas fa-plus"></i> Añadir Comprobante
                            </button>
                        </div>
                    </div>

                    <!-- Documentos Adjuntos -->
                    <div class="lg:col-span-2">
                        <div class="info-card bg-white shadow-sm">
                            <h2 class="card-title text-gray-800">
                                <i class="fas fa-folder-open"></i> Documentos ({{ gasto.comprobantes.length }})
                            </h2>

                            <div v-if="gasto.comprobantes.length === 0" class="empty-docs py-12 text-center text-gray-400">
                                <i class="fas fa-file-excel text-5xl mb-4 opacity-20"></i>
                                <p>No hay documentos adjuntos todavía.</p>
                            </div>

                            <div v-else class="comprobantes-grid mt-6">
                                <div 
                                    v-for="file in gasto.comprobantes" 
                                    :key="file.id" 
                                    class="file-card"
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
                                            <span class="mt-2 text-xs font-bold uppercase">{{ file.extension }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="file-info p-3">
                                        <p class="file-name truncate">{{ file.name }}</p>
                                        <div class="file-actions mt-3">
                                            <a :href="file.public_url" target="_blank" class="btn-file-view">
                                                <i class="fas fa-external-link-alt"></i> Ver pantalla completa
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    try {
        const response = await axios.get(`/gastos/${gastoId}`);
        gasto.value = response.data;
    } catch (err) {
        error.value = 'No se pudo cargar el detalle.';
    } finally {
        loading.value = false;
    }
};

const handleImageError = (e) => {
    e.target.style.display = 'none';
    e.target.parentElement.innerHTML = '<i class="fas fa-image text-gray-300 text-4xl"></i>';
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatCurrency = (value) => {
    return Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};

const isImage = (ext) => ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext.toLowerCase());

const getFileIcon = (ext) => {
    const e = ext.toLowerCase();
    if (e === 'pdf') return 'fas fa-file-pdf text-red-600';
    return 'fas fa-file-alt text-gray-400';
};

onMounted(fetchGastoDetail);
</script>

<style scoped>
.info-card { padding: 24px; background: white; border-radius: 12px; border: 1px solid #e2e8f0; }
.info-card.border-brand { border-top: 5px solid var(--brand-red-dark); }
.card-title { font-size: 1.25rem; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 20px; }
.comprobantes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
.file-card { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.file-preview-area { height: 150px; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.preview-img { width: 100%; height: 100%; object-fit: cover; }
.btn-file-view { display: block; text-align: center; padding: 8px; background: #f1f5f9; border-radius: 6px; font-size: 0.75rem; font-weight: 700; color: #475569; text-decoration: none; }
.btn-file-view:hover { background: var(--brand-red-dark); color: white; }
</style>