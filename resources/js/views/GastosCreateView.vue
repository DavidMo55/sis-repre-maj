<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Registrar Nuevo Gasto</h1>
                    <p>Captura los detalles de tus gastos operativos y adjunta los comprobantes correspondientes.</p>
                </div>
                <button @click="router.push('/gastos')" class="btn-secondary flex-row-centered gap-2">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="mt-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-2 space-y-6">
                        <div class="form-section shadow-sm">
                            <div class="section-title">
                                <i class="fas fa-file-invoice-dollar"></i> 1. Información del Gasto
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label>Fecha del Gasto</label>
                                    <input v-model="form.fecha" type="date" class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label>Monto Total ($)</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                        <input v-model.number="form.monto" type="number" step="0.01" class="form-input pl-8" placeholder="0.00" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Concepto (Categoría)</label>
                                    <select v-model="form.concepto_base" class="form-input font-bold" required>
                                        <option value="" disabled>Seleccione un concepto...</option>
                                        <option value="Peaje y gasolina">Peaje y gasolina</option>
                                        <option value="Alimentación">Alimentación</option>
                                        <option value="Hospedaje">Hospedaje</option>
                                        <option value="Mantenimiento de vehículo">Mantenimiento de vehículo</option>
                                        <option value="Papelería y artículos de oficina">Papelería y artículos de oficina</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Estado / Ciudad de la Visita</label>
                                    <div class="relative">
                                        <select v-model="form.estado_nombre" class="form-input" required>
                                            <option value="" disabled>Seleccione el estado...</option>
                                            <option v-for="e in estados" :key="e.id" :value="e.estado">{{ e.estado }}</option>
                                        </select>
                                        <i v-if="loadingEstados" class="fas fa-spinner fa-spin absolute right-8 top-1/2 -translate-y-1/2 text-red-600"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 p-4 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                                <label class="block mb-2 font-bold text-gray-700 text-xs uppercase tracking-widest">Vista previa del concepto final:</label>
                                <p class="text-sm italic text-gray-600 font-bold">
                                    {{ form.concepto_base ? form.concepto_base : '...' }} visita {{ form.estado_nombre ? form.estado_nombre : '...' }}
                                </p>
                            </div>
                        </div>

                        <div class="form-section shadow-sm">
                            <div class="section-title">
                                <i class="fas fa-check-double"></i> 2. Detalles Fiscales
                            </div>
                            <div class="flex items-center gap-8 bg-red-50 p-4 rounded-xl border border-red-100">
                                <label class="font-bold text-red-900">¿El gasto cuenta con factura?</label>
                                <div class="flex gap-6">
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="radio" :value="true" v-model="form.es_facturado" class="w-4 h-4 accent-red-600">
                                        <span class="font-bold group-hover:text-red-600 transition-colors">SÍ</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="radio" :value="false" v-model="form.es_facturado" class="w-4 h-4 accent-red-600">
                                        <span class="font-bold group-hover:text-red-600 transition-colors">NO</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-1 space-y-6">
                        <div class="form-section shadow-sm h-full flex flex-col">
                            <div class="section-title">
                                <i class="fas fa-cloud-upload-alt"></i> 3. Comprobantes
                            </div>
                            
                            <div class="upload-zone flex-grow flex flex-col items-center justify-center border-2 border-dashed border-gray-200 rounded-2xl p-6 transition-all hover:border-red-300 hover:bg-red-50" 
                                @dragover.prevent @drop.prevent="handleDrop" @click="$refs.fileInput.click()">
                                
                                <input type="file" ref="fileInput" multiple @change="handleFileSelect" class="hidden" accept=".jpg,.jpeg,.png,.pdf">
                                
                                <div v-if="!selectedFiles.length" class="text-center">
                                    <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-file-upload text-2xl"></i>
                                    </div>
                                    <p class="text-sm font-bold text-gray-500">Haz clic o arrastra archivos</p>
                                    <p class="text-[10px] text-gray-400 mt-1">Formatos: JPG, PNG, PDF (Máx 3MB)</p>
                                </div>

                                <div v-else class="w-full space-y-3">
                                    <div v-for="(file, index) in selectedFiles" :key="index" class="flex items-center justify-between bg-white p-2 rounded-lg border border-gray-100 shadow-sm animate-fade-in">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <i class="fas text-red-600" :class="file.type.includes('pdf') ? 'fa-file-pdf' : 'fa-file-image'"></i>
                                            <span class="text-[10px] font-bold text-gray-700 truncate max-w-[120px]">{{ file.name }}</span>
                                        </div>
                                        <button @click.stop="removeFile(index)" class="text-gray-300 hover:text-red-500 transition-colors">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8">
                                <button type="submit" class="btn-primary w-full py-4 shadow-xl text-lg font-black tracking-widest uppercase flex items-center justify-center gap-3" :disabled="loading">
                                    <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-save"></i>
                                    {{ loading ? 'Procesando...' : 'Guardar Gasto' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <Transition name="fade">
            <div v-if="modal.show" class="modal-overlay" @click.self="closeModal">
                <div class="modal-content" :class="modal.type">
                    <div class="modal-icon">
                        <i :class="modal.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
                    </div>
                    <h2 class="text-xl font-black mb-2">{{ modal.title }}</h2>
                    <p class="text-gray-600 text-sm mb-6">{{ modal.message }}</p>
                    <button @click="closeModal" class="btn-modal w-full">
                        {{ modal.type === 'success' ? 'Entendido' : 'Cerrar' }}
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const loading = ref(false);
const loadingEstados = ref(false);
const estados = ref([]);
const fileInput = ref(null);
const selectedFiles = ref([]);

const modal = reactive({
    show: false,
    type: 'success',
    title: '',
    message: ''
});

const form = reactive({
    fecha: new Date().toISOString().split('T')[0],
    concepto_base: '',
    estado_nombre: '',
    monto: null,
    es_facturado: false
});

onMounted(() => {
    fetchEstados();
});

const fetchEstados = async () => {
    loadingEstados.value = true;
    try {
        const response = await axios.get('/estados');
        estados.value = response.data;
    } catch (e) {
        console.error("Error cargando estados:", e);
    } finally {
        loadingEstados.value = false;
    }
};

const handleFileSelect = (e) => {
    const files = Array.from(e.target.files);
    validateAndAddFiles(files);
};

const handleDrop = (e) => {
    const files = Array.from(e.dataTransfer.files);
    validateAndAddFiles(files);
};

const validateAndAddFiles = (files) => {
    files.forEach(file => {
        if (file.size > 3072 * 1024) {
            showStatusModal('error', 'Archivo muy grande', `El archivo ${file.name} supera los 3MB.`);
            return;
        }
        selectedFiles.value.push(file);
    });
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
};

const showStatusModal = (type, title, message) => {
    modal.type = type;
    modal.title = title;
    modal.message = message;
    modal.show = true;
};

const closeModal = () => {
    modal.show = false;
    if (modal.type === 'success') {
        router.push('/gastos');
    }
};

const handleSubmit = async () => {
    if (!selectedFiles.value.length && !confirm("¿Deseas guardar sin subir comprobantes?")) return;

    loading.value = true;
    try {
        // PASO 1: Registro del Gasto
        const resGasto = await axios.post('/gastos-nuevos', {
            fecha: form.fecha,
            monto: form.monto,
            concepto_base: form.concepto_base,
            estado_nombre: form.estado_nombre,
            es_facturado: form.es_facturado
        });
        
        const gastoId = resGasto.data.gasto.id;

        // PASO 2: Subida de Comprobantes (Solución al error files.0)
        if (selectedFiles.value.length > 0) {
            const formData = new FormData();
            formData.append('gasto_id', gastoId);
            
            // Usar 'files[]' para que Laravel lo procese como arreglo de archivos
            selectedFiles.value.forEach((file) => {
                formData.append('files[]', file); 
            });

            await axios.post('/gastos/comprobante', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        }

        showStatusModal('success', '¡Éxito!', 'El gasto se ha registrado correctamente.');
    } catch (e) {
        console.error("Error completo:", e.response?.data);
        const msg = e.response?.data?.message || "Ocurrió un error en el servidor.";
        showStatusModal('error', 'Error de Envío', msg);
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.form-section { background: white; padding: 24px; border-radius: 16px; border: 1px solid #e2e8f0; }
.section-title { font-weight: 900; color: #a93339; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; }
.upload-zone { cursor: pointer; min-height: 200px; }

/* ESTILOS DEL MODAL */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
}
.modal-content {
    background: white;
    padding: 35px;
    border-radius: 24px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
}
.modal-icon { font-size: 4rem; margin-bottom: 15px; }
.success .modal-icon { color: #10b981; }
.error .modal-icon { color: #ef4444; }

.btn-modal {
    background: #a93339;
    color: white;
    padding: 12px;
    border-radius: 12px;
    font-weight: 800;
    transition: transform 0.2s;
}
.btn-modal:hover { transform: scale(1.02); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>