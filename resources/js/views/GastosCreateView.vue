<template>
    <div class="content-wrapper p-2 md:p-6">
        <div class="module-page max-w-7xl mx-auto">
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-800">Registrar Paquete de Gastos</h1>
                    <p class="text-xs md:text-sm text-slate-500">Agrupa conceptos y adjunta un comprobante por cada uno.</p>
                </div>
                <button @click="router.push('/gastos')" class="btn-secondary flex items-center gap-2 px-4 py-2 rounded-xl text-sm w-full sm:w-auto justify-center">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- COLUMNA IZQUIERDA: CONSTRUCTOR -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- 1. DATOS DEL VIAJE -->
                        <div class="form-section shadow-premium">
                            <div class="section-title">
                                <i class="fas fa-map-marked-alt"></i> 1. Información del Viaje
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div class="form-group">
                                    <label class="label-style">Fecha de Inicio</label>
                                    <input v-model="form.fecha" type="date" class="form-input w-full" required>
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Estado / Región</label>
                                    <div class="relative">
                                        <select v-model="form.estado_nombre" class="form-input w-full appearance-none" required>
                                            <option value="" disabled>Seleccione el estado...</option>
                                            <option v-for="e in estados" :key="e.id" :value="e.estado">{{ e.estado }}</option>
                                        </select>
                                        <i v-if="loadingEstados" class="fas fa-spinner fa-spin absolute right-4 top-1/2 -translate-y-1/2 text-red-600"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. AGREGAR CONCEPTOS -->
                        <div class="form-section shadow-premium bg-slate-50/50">
                            <div class="section-title">
                                <i class="fas fa-plus-circle"></i> 2. Detalle de Conceptos y Comprobantes
                            </div>
                            
                            <div class="bg-white p-5 md:p-8 rounded-[2rem] border border-slate-100 shadow-sm space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                                    <div :class="tempSub.concepto === 'Otros' ? 'sm:col-span-3' : 'sm:col-span-5'">
                                        <label class="label-mini">Concepto</label>
                                        <select v-model="tempSub.concepto" class="form-input text-sm font-bold w-full">
                                            <option value="" disabled>Seleccione...</option>
                                            <option value="Peaje y gasolina">⛽ Gasolina</option>
                                            <option value="Peaje">🛣️ Peaje</option>
                                            <option value="Alimentación">🍽️ Alimentación</option>
                                            <option value="Hospedaje">🏨 Hospedaje</option>
                                            <option value="Mantenimiento de vehículo">🔧 Mantenimiento</option>
                                            <option value="Papelería y artículos">📁 Papelería</option>
                                            <option value="Otros">✨ Otros (Especificar)</option>
                                        </select>
                                    </div>

                                    <div v-if="tempSub.concepto === 'Otros'" class="sm:col-span-3">
                                        <label class="label-mini">Descripción</label>
                                        <input v-model="tempSub.descripcion_otros" type="text" class="form-input text-sm w-full" placeholder="¿En qué se gastó?">
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label class="label-mini">Monto ($)</label>
                                        <input v-model.number="tempSub.monto" type="number" step="0.01" class="form-input text-sm font-black w-full text-red-700" placeholder="0.00">
                                    </div>

                                    <div class="sm:col-span-4">
                                        <label class="label-mini">Subir Comprobante *</label>
                                        <div class="relative">
                                            <input type="file" ref="lineFileInput" @change="handleLineFileSelect" class="hidden" accept=".jpg,.jpeg,.png,.pdf">
                                           
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-50">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox" v-model="tempSub.es_facturado" class="w-5 h-5 accent-red-600 cursor-pointer">
                                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest group-hover:text-red-600 transition-colors">¿Cuenta con factura fiscal?</span>
                                    </label>
                                    <button type="button" @click="addSubExpense" class="btn-primary py-3.5 px-10 rounded-2xl flex items-center justify-center gap-2 text-xs uppercase font-black tracking-widest shadow-lg" :disabled="!isLineValid">
                                        <i class="fas fa-plus"></i> Añadir al Paquete
                                    </button>
                                </div>
                            </div>

                            <!-- TABLA DE REVISIÓN -->
                            <div class="mt-8 overflow-hidden rounded-[1.5rem] md:rounded-[2rem] border border-slate-200 bg-white shadow-premium w-full">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm border-collapse">
                                        <thead class="bg-slate-900 text-white uppercase text-[9px] font-black tracking-[0.2em] hidden md:table-header-group">
                                            <tr>
                                                <th class="px-6 py-5 text-left">Concepto / Descripción</th>
                                                <th class="px-6 py-5 text-center">Documento</th>
                                                <th class="px-6 py-5 text-center">Fiscal</th>
                                                <th class="px-6 py-5 text-right">Monto</th>
                                                <th class="px-6 py-5 text-center"></th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-slate-100 block md:table-row-group">
                                            <tr v-for="(item, index) in subExpenses" :key="index" 
                                                class="hover:bg-slate-50/50 transition-colors animate-fade-in group block md:table-row relative p-4 md:p-0 border-b md:border-none">
                                                
                                                <td class="px-0 md:px-6 py-2 md:py-5 block md:table-cell">
                                                    <div class="flex items-center gap-3">
                                                        <div class="hidden md:flex w-8 h-8 bg-slate-100 text-slate-500 rounded-lg items-center justify-center font-black text-xs">
                                                            {{ index + 1 }}
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="font-black text-slate-800 text-sm md:text-[13px] uppercase leading-tight break-words">
                                                                {{ item.concepto === 'Otros' ? item.descripcion_otros : item.concepto }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="px-0 md:px-6 py-2 md:py-5 block md:table-cell text-left md:text-center">
                                                    <span class="md:hidden block text-[9px] font-black text-slate-400 uppercase mb-1">Documento Adjunto</span>
                                                    <div class="inline-flex items-center gap-2 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl max-w-full">
                                                        <i class="fas" :class="item.file.type.includes('pdf') ? 'fa-file-pdf text-red-500' : 'fa-file-image text-blue-500'"></i>
                                                        <span class="text-[10px] font-bold text-slate-600 truncate max-w-[120px] sm:max-w-[200px] uppercase tracking-tighter">
                                                            {{ item.file.name }}
                                                        </span>
                                                    </div>
                                                </td>

                                                <td class="px-0 md:px-6 py-2 md:py-5 block md:table-cell text-left md:text-center">
                                                    <span v-if="item.es_facturado" class="status-badge bg-green-100 text-green-700 border border-green-200">Facturado</span>
                                                    <span v-else class="status-badge bg-slate-100 text-slate-400">Sin Factura</span>
                                                </td>

                                                <td class="px-0 md:px-6 py-2 md:py-5 block md:table-cell text-left md:text-right font-black text-red-700 text-lg md:text-base">
                                                    ${{ item.monto.toFixed(2) }}
                                                </td>

                                                <td class="px-0 md:px-6 py-2 md:py-5 block md:table-cell text-center absolute top-4 right-4 md:static">
                                                    <button type="button" @click="removeSubExpense(index)" 
                                                        class="flex items-center justify-center text-slate-300 hover:text-red-600 transition-all hover:scale-110 font-bold text-xs uppercase gap-1">
                                                        <i class="fas fa-trash-alt"></i> <span class="hidden md:inline">Borrar</span>
                                                    </button>
                                                </td>
                                            </tr>

                                            <tr v-if="subExpenses.length === 0">
                                                <td colspan="5" class="px-6 py-16 text-center text-slate-300">
                                                    <div class="flex flex-col items-center">
                                                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-dashed border-slate-200">
                                                            <i class="fas fa-receipt text-3xl opacity-20"></i>
                                                        </div>
                                                        <p class="italic font-black uppercase text-[10px] tracking-[0.2em] text-slate-400">No hay conceptos agregados</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>

                                        <tfoot v-if="subExpenses.length > 0" class="bg-slate-900 text-white block md:table-footer-group">
                                            <tr class="flex flex-col md:table-row p-6 md:p-0">
                                                <td colspan="3" class="hidden md:table-cell px-6 py-6 text-right uppercase font-black text-slate-400 text-[10px] tracking-[0.2em]">
                                                    Inversión Total del Paquete:
                                                </td>
                                                <td class="md:px-6 md:py-8 block md:table-cell text-center md:text-right">
                                                    <span class="md:hidden block text-[10px] font-black text-slate-500 uppercase mb-2 tracking-widest">Total Acumulado</span>
                                                    <div class="text-3xl md:text-2xl font-black text-red-400">
                                                        <span class="text-sm md:text-lg">$</span>{{ totalMonto.toFixed(2) }}
                                                    </div>
                                                </td>
                                                <td class="hidden md:table-cell px-6 py-6"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: ACCIÓN -->
                    <div class="lg:col-span-1">
                        <div class="form-section shadow-premium sticky top-28 bg-slate-900 text-white border-none">
                            <div class="section-title !text-white !border-white/20">
                                <i class="fas fa-paper-plane"></i> Finalizar Registro
                            </div>
                            
                            <div class="space-y-6 py-4">
                                <div class="p-5 bg-white/5 rounded-2xl border border-white/10">
                                    <p class="text-[10px] font-black text-white/40 uppercase mb-4 tracking-[0.2em]">Resumen de Envío</p>
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-sm font-medium text-white/60">Conceptos:</span>
                                        <span class="font-black">{{ subExpenses.length }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-white/60">Total a Postear:</span>
                                        <span class="text-xl font-black text-red-400">${{ totalMonto.toFixed(2) }}</span>
                                    </div>
                                </div>

                                <div class="bg-amber-500/10 border border-amber-500/20 p-4 rounded-2xl text-center">
                                    <p class="text-[10px] text-amber-500 font-black uppercase leading-tight">
                                        <i class="fas fa-info-circle mr-1"></i> 
                                        El proceso subirá los archivos a Dropbox automáticamente.
                                    </p>
                                </div>

                                <button type="submit" class="btn-primary w-full py-5 rounded-[2rem] text-lg font-black tracking-widest uppercase shadow-2xl" :disabled="loading || subExpenses.length === 0">
                                    <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-cloud-upload-alt mr-2"></i>
                                    {{ loading ? (uploadProgress > 0 ? `Subiendo ${uploadProgress}%...` : 'Sincronizando...') : 'Postear Gasto' }}
                                </button>
                                
                                <p v-if="subExpenses.length === 0" class="text-[9px] text-center text-white/30 font-bold uppercase tracking-widest">
                                    Debe agregar al menos un gasto para habilitar el envío
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- MODAL DE OVERLAY -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="modal.visible" class="modal-overlay-wrapper">
                    
                    <div v-if="modal.type !== 'success'" class="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100 animate-scale-in">
                        <div :class="['h-2 w-full', modal.type === 'danger' ? 'bg-red-600' : 'bg-blue-600']"></div>
                        <div class="p-10 text-center">
                            <div :class="['w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg', modal.type === 'danger' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600']">
                                <i :class="['fas fa-2xl', modal.type === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle']"></i>
                            </div>
                            <h3 class="text-2xl font-black text-slate-800 leading-tight mb-2 uppercase tracking-tighter">{{ modal.title }}</h3>
                            <p class="text-slate-500 text-sm leading-relaxed font-medium">{{ modal.message }}</p>
                        </div>
                        <div class="bg-slate-50 p-6 flex gap-3 border-t">
                            <button @click="closeModal" class="flex-1 py-4 text-xs font-black text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">Cerrar</button>
                        </div>
                    </div>

                    <div v-else class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tighter">{{ modal.title }}</h2>
                        <p class="text-sm text-slate-500 mb-8 font-medium px-4">{{ modal.message }}</p>
                        <button @click="closeAndRedirect" class="btn-primary w-full py-4 bg-slate-900 border-none shadow-none text-white uppercase font-black tracking-widest">Ir al Historial</button>
                    </div>

                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const loading = ref(false);
const uploadProgress = ref(0);
const loadingEstados = ref(false);
const estados = ref([]);
const lineFileInput = ref(null);

const subExpenses = ref([]);
const tempSub = reactive({ 
    concepto: '', 
    descripcion_otros: '',
    monto: null, 
    es_facturado: false,
    file: null 
});

const modal = reactive({ visible: false, title: '', message: '', type: 'info' });
const form = reactive({ fecha: new Date().toISOString().split('T')[0], estado_nombre: '' });

const totalMonto = computed(() => subExpenses.value.reduce((acc, item) => acc + item.monto || 0, 0));

const isLineValid = computed(() => {
    const hasConcept = tempSub.concepto !== '';
    const hasDescription = tempSub.concepto === 'Otros' ? (tempSub.descripcion_otros && tempSub.descripcion_otros.trim() !== '') : true;
    const hasAmount = tempSub.monto !== null && tempSub.monto > 0;
    const hasFile = tempSub.file !== null;
    return hasConcept && hasDescription && hasAmount && hasFile;
});

onMounted(() => { fetchEstados(); });

const fetchEstados = async () => {
    loadingEstados.value = true;
    try {
        const response = await axios.get('/estados');
        estados.value = response.data;
    } catch (e) { console.error(e); } finally { loadingEstados.value = false; }
};

const handleLineFileSelect = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 3072 * 1024) {
        openModal('Archivo muy pesado', 'El comprobante no debe exceder los 3MB.', 'danger');
        e.target.value = '';
        return;
    }
    tempSub.file = file;
};

const addSubExpense = () => {
    if (!isLineValid.value) return;
    
    subExpenses.value.push({ 
        concepto: tempSub.concepto,
        descripcion_otros: tempSub.descripcion_otros,
        monto: tempSub.monto,
        es_facturado: tempSub.es_facturado,
        file: tempSub.file 
    });

    // Reset temporal
    tempSub.concepto = '';
    tempSub.descripcion_otros = '';
    tempSub.monto = null;
    tempSub.es_facturado = false;
    tempSub.file = null;
    if (lineFileInput.value) lineFileInput.value.value = '';
};

const removeSubExpense = (index) => { subExpenses.value.splice(index, 1); };

const openModal = (title, message, type = 'info') => {
    modal.title = title; modal.message = message; modal.type = type; modal.visible = true;
};

const closeModal = () => { modal.visible = false; };
const closeAndRedirect = () => { modal.visible = false; router.push('/gastos'); };


const handleSubmit = async () => {
    if (subExpenses.value.length === 0) return;

    loading.value = true;
    uploadProgress.value = 0;

    try {
        const payload = {
            fecha: form.fecha,
            estado_nombre: form.estado_nombre,
            monto_total: totalMonto.value,
            conceptos: subExpenses.value.map(item => ({
                concepto: item.concepto,
                descripcion: item.descripcion_otros || '',
                monto: item.monto,
                es_facturado: item.es_facturado
            }))
        };

        const resGasto = await axios.post('/gastos-nuevos', payload);
        const gastoId = resGasto.data.gasto.id;
        const fileFormData = new FormData();
        fileFormData.append('gasto_id', gastoId);
        subExpenses.value.forEach((item, index) => {
            fileFormData.append(`files[${index}]`, item.file);
        });

        await axios.post('/gastos/comprobante', fileFormData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (progressEvent) => {
                uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            }
        });

        openModal('¡Gasto Posteado!', 'El paquete de gastos se registró y todos los comprobantes se subieron con éxito.', 'success');
    } catch (e) {
        console.error("Fallo en el proceso de registro/subida:", e);
        const msg = e.response?.data?.message || "Ocurrió un error al procesar el paquete o los archivos.";
        openModal('Fallo en Registro', msg, 'danger');
    } finally {
        loading.value = false;
        uploadProgress.value = 0;
    }
};
</script>

<style scoped>
.label-style { @apply text-xs font-black text-slate-500 uppercase tracking-tighter mb-2 block; }
.label-mini { @apply text-[9px] md:text-[10px] uppercase font-black text-slate-400 mb-1 block; }

.shadow-premium { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.04); }
.form-section { background: white; padding: 25px; border-radius: 24px; border: 1px solid #f1f5f9; }

.section-title { font-weight: 900; color: #a93339; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 10px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }

.form-input { width: 100%; padding: 12px 16px; border-radius: 14px; border: 2px solid #f1f5f9; font-weight: 700; color: #334155; background: #fafbfc; transition: all 0.2s; }
.form-input:focus { border-color: #a93339; background: white; outline: none; }

.status-badge { @apply px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter inline-flex items-center; }

.modal-overlay-wrapper { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 1.5rem; background-color: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); overflow: hidden; }
.modal-content-success { background: white; padding: 45px; border-radius: 40px; text-align: center; width: 90%; max-width: 400px; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4); border: 1px solid #f1f5f9; }
.success-icon-wrapper { width: 75px; height: 75px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 25px; border: 4px solid white; }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

.modal-pop-enter-active, .modal-pop-leave-active { transition: all 0.3s ease; }
.modal-pop-enter-from, .modal-pop-leave-to { opacity: 0; }

select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; appearance: none; }
</style>