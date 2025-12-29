<template>
    <Teleport to="body">
        <div v-if="visible" class="modal-overlay" @click.self="closeModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3 class="modal-title">Subir Comprobante(s)</h3>
                    <button @click="closeModal" class="modal-close-btn">&times;</button>
                </div>

                <div class="modal-body">
                    <div v-if="gasto" class="gasto-info mb-4">
                        <p class="font-semibold">ID Gasto: <span class="text-gray-700">{{ gasto.id }}</span></p>
                        <p class="font-semibold">Concepto: <span class="text-gray-700">{{ gasto.concepto }}</span></p>
                        <p class="font-semibold">Monto: <span class="text-red-700">{{ formatCurrency(gasto.monto) }}</span></p>
                    </div>

                    <form @submit.prevent="handleUpload">
                        <div class="form-group">
                            <label for="fileInput" class="upload-label">
                                Seleccionar Archivo(s) (JPG, PNG, PDF - Máx. 3MB/archivo)
                            </label>
                            <input 
                                type="file" 
                                id="fileInput" 
                                ref="fileInput"
                                multiple 
                                accept=".jpg, .jpeg, .png, .pdf"
                                class="file-input-hidden"
                                @change="onFileChange"
                            >
                            <div class="file-display">
                                <span v-if="files.length">{{ files.length }} archivo(s) seleccionado(s)</span>
                                <span v-else class="text-gray-500 italic">Clic para buscar o arrastrar archivos aquí.</span>
                            </div>
                        </div>

                        <ul v-if="files.length" class="file-list">
                            <li v-for="(file, index) in files" :key="index">
                                {{ file.name }} ({{ (file.size / 1024 / 1024).toFixed(2) }} MB)
                            </li>
                        </ul>

                        <p v-if="error" class="error-message mt-3">{{ error }}</p>
                        <p v-if="successMessage" class="success-message mt-3">{{ successMessage }}</p>
                        <p v-if="progress > 0 && progress < 100" class="text-blue-500 mt-2">
                             Subiendo: {{ progress }}%
                        </p>

                        <div class="modal-footer mt-5">
                            <button type="submit" :disabled="loading || files.length === 0" class="btn-primary">
                                <i class="fas fa-upload"></i> {{ loading ? 'Subiendo...' : 'Confirmar Subida' }}
                            </button>
                            <button type="button" @click="closeModal" class="btn-secondary ml-3" :disabled="loading">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>

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
        error.value = `Los archivos exceden el límite de ${MAX_SIZE_MB}MB. Por favor, reduce el tamaño.`;
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

