<template>
    <Teleport to="body">
        <transition name="fade">
            <div v-if="visible" class="modal-overlay" @click.self="closeModal">
                <div class="modal-card max-w-lg w-full">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800">Subir Comprobante(s)</h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-red-600 transition-colors text-2xl">&times;</button>
                    </div>

                    <div class="modal-body">
                        <div v-if="gasto" class="bg-gray-50 border border-gray-100 rounded-xl p-4 mb-6">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <p class="text-gray-500 uppercase font-bold text-[10px] tracking-widest col-span-2">Detalles del registro</p>
                                <p class="font-medium text-gray-700">Concepto:</p>
                                <p class="text-gray-900 text-right">{{ gasto.concepto }}</p>
                                <p class="font-medium text-gray-700">Monto:</p>
                                <p class="text-red-600 font-bold text-right">${{ formatCurrency(gasto.monto) }}</p>
                            </div>
                        </div>

                        <form @submit.prevent="handleUpload">
                            <div class="form-group mb-4">
                                <label 
                                    for="fileInput" 
                                    class="relative flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-white hover:bg-red-50 hover:border-red-300 transition-all"
                                    :class="{ 'border-red-400 bg-red-50': files.length > 0 }"
                                >
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                       <p class="text-xs text-gray-500">
                                            <span class="font-bold text-red-600" v-if="!files.length">Haz clic para buscar</span>
                                            <span v-else class="font-bold text-red-700">{{ files.length }} seleccionado(s)</span>
                                        </p>
                                        <p class="text-[10px] text-gray-400 mt-1">JPG, PNG o PDF (Máx. 3MB)</p>
                                    </div>
                                    <input 
                                        type="file" 
                                        id="fileInput" 
                                        ref="fileInput"
                                        multiple 
                                        accept=".jpg, .jpeg, .png, .pdf"
                                        class="hidden"
                                        @change="onFileChange"
                                    >
                                </label>
                            </div>

                            <div v-if="files.length" class="max-h-32 overflow-y-auto mb-4 border border-gray-100 rounded-lg p-2 bg-white">
                                <ul class="space-y-1">
                                    <li v-for="(file, index) in files" :key="index" class="flex justify-between items-center text-[11px] bg-gray-50 p-2 rounded">
                                        <span class="truncate w-40 text-gray-700">{{ file.name }}</span>
                                        <span class="text-gray-400">{{ (file.size / 1024 / 1024).toFixed(2) }} MB</span>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="error" class="p-3 mb-4 text-xs text-red-700 bg-red-50 rounded-lg border border-red-100">
                                {{ error }}
                            </div>
                            
                            <div v-if="successMessage" class="p-3 mb-4 text-xs text-green-700 bg-green-50 rounded-lg border border-green-100">
                                {{ successMessage }}
                            </div>

                            <div v-if="progress > 0 && progress < 100" class="mb-4">
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-red-600 h-1.5 rounded-full transition-all duration-300" :style="{ width: progress + '%' }"></div>
                                </div>
                                <p class="text-right text-[10px] text-red-600 font-bold mt-1">Subiendo: {{ progress }}%</p>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <button 
                                    type="submit" 
                                    :disabled="loading || files.length === 0" 
                                    class="flex-1 bg-red-600 text-white font-bold py-3 rounded-xl hover:bg-red-700 disabled:bg-gray-200 disabled:cursor-not-allowed transition-all shadow-lg shadow-red-100"
                                >
                                    {{ loading ? 'Subiendo archivos...' : 'Confirmar Subida' }}
                                </button>
                                <button 
                                    type="button" 
                                    @click="closeModal" 
                                    class="px-6 py-3 border border-gray-200 text-gray-500 font-bold rounded-xl hover:bg-gray-50 transition-all"
                                    :disabled="loading"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(17, 24, 39, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 9999;
}

.modal-card {
    background: white;
    padding: 30px;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

/* Scrollbar sutil para la lista de archivos */
.file-list::-webkit-scrollbar {
    width: 4px;
}
.file-list::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
</style>

<script setup>
import { ref, watch } from 'vue';
import axios from '../../axios';

const props = defineProps({
    visible: Boolean,
    gasto: Object,
});

const emit = defineEmits(['close', 'uploaded']);

const loading = ref(false);
const error = ref(null);
const successMessage = ref(null);
const files = ref([]);
const progress = ref(0);
const fileInput = ref(null); 

const formatCurrency = (value) => {
    if (value === null || isNaN(value)) return '$0.00';
    return value.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};

const resetForm = () => {
    files.value = [];
    error.value = null;
    successMessage.value = null;
    progress.value = 0;
    if (fileInput.value) {
        fileInput.value.value = ''; 
    }
};

const closeModal = () => {
    resetForm();
    emit('close');
};

const onFileChange = (event) => {
    error.value = null;
    successMessage.value = null;
    files.value = Array.from(event.target.files);

    const MAX_SIZE_MB = 3;
    const oversizedFiles = files.value.filter(file => file.size > MAX_SIZE_MB * 1024 * 1024);

    if (oversizedFiles.length > 0) {
        error.value = `Los archivos exceden el límite de ${MAX_SIZE_MB}MB.`;
        files.value = [];
    }
};

const handleUpload = async () => {
    if (files.value.length === 0 || !props.gasto) return;

    loading.value = true;
    error.value = null;
    successMessage.value = null;
    progress.value = 0;

    const formData = new FormData();
    formData.append('gasto_id', props.gasto.id);

    files.value.forEach((file, index) => {
        formData.append(`files[${index}]`, file);
    });

    try {
        const response = await axios.post('/gastos/comprobante', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                progress.value = percentCompleted;
            },
        });

        successMessage.value = response.data.message || 'Subida completada.';
        emit('uploaded'); 
        
        setTimeout(() => {
            closeModal();
        }, 2000); 

    } catch (err) {
        if (err.response && err.response.data && err.response.data.errors) {
            const validationErrors = Object.values(err.response.data.errors).flat();
            error.value = 'Error de validación: ' + validationErrors.join('; ');
        } else if (err.response && err.response.data && err.response.data.message) {
            error.value = err.response.data.message;
        } else {
            error.value = 'Error de red o servidor al subir archivos.';
            console.error('Upload Error:', err);
        }
    } finally {
        loading.value = false;
    }
};

watch(() => props.visible, (newVal) => {
    if (!newVal) {
        resetForm();
    }
});
</script>

