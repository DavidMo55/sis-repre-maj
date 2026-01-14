<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Registrar Paquete de Gastos</h1>
                    <p>Agrupa múltiples conceptos y adjunta obligatoriamente tus comprobantes para poder guardar.</p>
                </div>
                <button @click="router.push('/gastos')" class="btn-secondary flex-row-centered gap-2">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="mt-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-2 space-y-6">
                        <!-- SECCIÓN 1: DATOS GENERALES -->
                        <div class="form-section shadow-premium">
                            <div class="section-title">
                                <i class="fas fa-map-marked-alt"></i> 1. Información del Viaje / Ruta
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label>Fecha de Inicio / Registro</label>
                                    <input v-model="form.fecha" type="date" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label>Estado / Región de la Visita</label>
                                    <div class="relative">
                                        <select v-model="form.estado_nombre" class="form-input" required>
                                            <option value="" disabled>Seleccione el estado...</option>
                                            <option v-for="e in estados" :key="e.id" :value="e.estado">{{ e.estado }}</option>
                                        </select>
                                        <i v-if="loadingEstados" class="fas fa-spinner fa-spin absolute right-8 top-1/2 -translate-y-1/2 text-red-600"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 2: CONSTRUCTOR DE SUB-GASTOS -->
                        <div class="form-section shadow-premium bg-slate-50/50">
                            <div class="section-title">
                                <i class="fas fa-plus-circle"></i> 2. Agregar Conceptos al Paquete
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                                <div class="md:col-span-5 form-group">
                                    <label class="text-[10px] uppercase font-black text-slate-400 mb-1">Concepto</label>
                                    <select v-model="tempSub.concepto" class="form-input text-sm font-bold">
                                        <option value="" disabled>Seleccione...</option>
                                        <option value="Peaje y gasolina">Gasolina</option>
                                        <option value="Peaje">Peaje</option>
                                        <option value="Alimentación">Alimentación</option>
                                        <option value="Hospedaje">Hospedaje</option>
                                        <option value="Mantenimiento de vehículo">Mantenimiento</option>
                                        <option value="Papelería y artículos">Papelería</option>
                                        
                                        <option value="Otros">Otros</option>
                                    </select>
                                </div>
                                <div class="md:col-span-3 form-group">
                                    <label class="text-[10px] uppercase font-black text-slate-400 mb-1">Monto ($)</label>
                                    <input v-model.number="tempSub.monto" type="number" step="0.01" class="form-input text-sm font-bold" placeholder="0.00">
                                </div>
                                <div class="md:col-span-2 form-group flex flex-col items-center">
                                    <label class="text-[10px] uppercase font-black text-slate-400 mb-1">¿Factura?</label>
                                    <input type="checkbox" v-model="tempSub.es_facturado" class="w-6 h-6 accent-red-600 cursor-pointer">
                                </div>
                                <div class="md:col-span-2">
                                    <button type="button" @click="addSubExpense" class="btn-primary w-full py-3 rounded-xl flex items-center justify-center gap-2" :disabled="!tempSub.concepto || !tempSub.monto">
                                        <i class="fas fa-plus"></i> Añadir
                                    </button>
                                </div>
                            </div>

                            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-100 text-slate-500 uppercase text-[10px] font-black tracking-widest">
                                        <tr>
                                            <th class="px-6 py-4 text-left">Concepto</th>
                                            <th class="px-6 py-4 text-center">Fiscal</th>
                                            <th class="px-6 py-4 text-right">Monto</th>
                                            <th class="px-6 py-4 text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="(item, index) in subExpenses" :key="index" class="hover:bg-slate-50 transition-colors animate-fade-in">
                                            <td class="px-6 py-4 font-bold text-slate-700">{{ item.concepto }}</td>
                                            <td class="px-6 py-4 text-center">
                                                <span v-if="item.es_facturado" class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-[9px] font-black uppercase">Con Factura</span>
                                                <span v-else class="bg-slate-100 text-slate-400 px-2 py-0.5 rounded text-[9px] font-black uppercase">Sin Factura</span>
                                            </td>
                                            <td class="px-6 py-4 text-right font-black text-red-700">${{ item.monto.toFixed(2) }}</td>
                                            <td class="px-6 py-4 text-center">
                                                <button type="button" @click="removeSubExpense(index)" class="text-slate-300 hover:text-red-600 transition-colors">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="subExpenses.length === 0">
                                            <td colspan="4" class="px-6 py-10 text-center text-slate-400 italic">No se han añadido conceptos a este paquete.</td>
                                        </tr>
                                    </tbody>
                                    <tfoot v-if="subExpenses.length > 0" class="bg-slate-900 text-white">
                                        <tr>
                                            <td colspan="2" class="px-6 py-4 text-right uppercase font-black tracking-tighter">Costo Final del Paquete:</td>
                                            <td class="px-6 py-4 text-right font-black text-lg text-red-400">${{ totalMonto.toFixed(2) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-1 space-y-6">
                        <!-- SECCIÓN 3: COMPROBANTES (OBLIGATORIOS) -->
                        <div class="form-section shadow-premium h-full flex flex-col" :class="{'border-red-200 bg-red-50/20': !selectedFiles.length}">
                            <div class="section-title">
                                <i class="fas fa-cloud-upload-alt"></i> 3. Comprobantes *
                            </div>
                            
                            <div class="upload-zone flex-grow flex flex-col items-center justify-center border-2 border-dashed rounded-3xl p-6 transition-all" 
                                :class="selectedFiles.length ? 'border-green-200 bg-green-50/10' : 'border-slate-200 hover:border-red-300 hover:bg-red-50'"
                                @dragover.prevent @drop.prevent="handleDrop" @click="$refs.fileInput.click()">
                                
                                <input type="file" ref="fileInput" multiple @change="handleFileSelect" class="hidden" accept=".jpg,.jpeg,.png,.pdf">
                                
                                <div v-if="!selectedFiles.length" class="text-center">
                                    <div class="w-16 h-16 bg-white shadow-sm text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-red-100">
                                        <i class="fas fa-file-upload text-2xl"></i>
                                    </div>
                                    <p class="text-sm font-black text-slate-600 uppercase tracking-tighter">Adjuntar Documentación</p>
                                    <p class="text-[9px] text-red-400 mt-2 font-bold uppercase tracking-widest italic">Este campo es obligatorio para postear</p>
                                </div>

                                <div v-else class="w-full space-y-3">
                                    <div v-for="(file, index) in selectedFiles" :key="index" class="flex items-center justify-between bg-white p-3 rounded-2xl border border-slate-100 shadow-sm animate-fade-in">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <i class="fas text-red-600" :class="file.type.includes('pdf') ? 'fa-file-pdf' : 'fa-file-image'"></i>
                                            <span class="text-[10px] font-black text-slate-700 truncate max-w-[150px] uppercase">{{ file.name }}</span>
                                        </div>
                                        <button @click.stop="removeFile(index)" class="text-slate-300 hover:text-red-500">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8">
                                <button type="submit" class="btn-primary w-full py-4 shadow-xl text-lg font-black tracking-widest uppercase flex items-center justify-center gap-3 rounded-2xl" :disabled="loading">
                                    <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-paper-plane"></i>
                                    {{ loading ? 'Sincronizando...' : 'Postear Gasto' }}
                                </button>
                                <p v-if="!selectedFiles.length" class="text-[9px] text-center mt-3 text-red-500 font-black uppercase tracking-widest animate-pulse">
                                    <i class="fas fa-info-circle"></i> Debes subir al menos un documento
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- MODALES DE ESTADO -->
        <Transition name="fade">
            <div v-if="modal.show" class="modal-overlay" @click.self="closeModal">
                <div class="modal-content" :class="modal.type">
                    <div class="modal-icon">
                        <i :class="modal.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
                    </div>
                    <h2 class="text-2xl font-black mb-2">{{ modal.title }}</h2>
                    <p class="text-slate-500 text-sm mb-8">{{ modal.message }}</p>
                    <button @click="closeModal" class="btn-modal w-full py-4 rounded-2xl text-lg font-black shadow-lg">
                        {{ modal.type === 'success' ? 'Ir al Historial' : 'Revisar Formulario' }}
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const loading = ref(false);
const loadingEstados = ref(false);
const estados = ref([]);
const fileInput = ref(null);
const selectedFiles = ref([]);

const subExpenses = ref([]);
const tempSub = reactive({ concepto: '', monto: null, es_facturado: false });

const modal = reactive({ show: false, type: 'success', title: '', message: '' });

const form = reactive({ fecha: new Date().toISOString().split('T')[0], estado_nombre: '' });

const totalMonto = computed(() => subExpenses.value.reduce((acc, item) => acc + item.monto, 0));

onMounted(() => { fetchEstados(); });

const fetchEstados = async () => {
    loadingEstados.value = true;
    try {
        const response = await axios.get('/estados');
        estados.value = response.data;
    } catch (e) { console.error(e); } finally { loadingEstados.value = false; }
};

const addSubExpense = () => {
    if (!tempSub.concepto || !tempSub.monto) return;
    subExpenses.value.push({ ...tempSub });
    tempSub.concepto = ''; tempSub.monto = null; tempSub.es_facturado = false;
};

const removeSubExpense = (index) => { subExpenses.value.splice(index, 1); };

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
            showStatusModal('error', 'Archivo excedido', `El archivo ${file.name} supera los 3MB.`);
            return;
        }
        selectedFiles.value.push(file);
    });
};

const removeFile = (index) => { selectedFiles.value.splice(index, 1); };

const showStatusModal = (type, title, message) => {
    modal.type = type; modal.title = title; modal.message = message; modal.show = true;
};

const closeModal = () => {
    modal.show = false;
    if (modal.type === 'success') router.push('/gastos');
};

/**
 * REGLA DE NEGOCIO: No permite postear si no hay comprobantes
 */
const handleSubmit = async () => {
    if (subExpenses.value.length === 0) {
        showStatusModal('error', 'Formulario Incompleto', 'Debes añadir al menos un concepto de gasto.');
        return;
    }

    // REGLA CRÍTICA: Bloqueo de posteo sin documentos
    if (selectedFiles.value.length === 0) {
        showStatusModal('error', 'Comprobante Obligatorio', 'No se puede postear el gasto sin adjuntar los documentos probatorios (Tickets o Facturas). Por favor, sube al menos un archivo.');
        return;
    }

    loading.value = true;
    try {
        const resGasto = await axios.post('/gastos-nuevos', {
            fecha: form.fecha,
            estado_nombre: form.estado_nombre,
            monto_total: totalMonto.value,
            conceptos: subExpenses.value
        });
        
        const gastoId = resGasto.data.gasto.id;

        // Subida de Comprobantes
        const formData = new FormData();
        formData.append('gasto_id', gastoId);
        selectedFiles.value.forEach((file) => {
            formData.append('files[]', file); 
        });

        await axios.post('/gastos/comprobante', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        showStatusModal('success', '¡Gasto Posteado!', 'El paquete de gastos y sus comprobantes se han registrado correctamente.');
    } catch (e) {
        const msg = e.response?.data?.message || "Ocurrió un error en el servidor.";
        showStatusModal('error', 'Fallo en Registro', msg);
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05); }
.form-section { background: white; padding: 28px; border-radius: 24px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #a93339; margin-bottom: 25px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1.5px; }

.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-content { background: white; padding: 45px; border-radius: 40px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 35px 70px -15px rgba(0,0,0,0.6); }
.modal-icon { font-size: 5rem; margin-bottom: 20px; }
.success .modal-icon { color: #10b981; }
.error .modal-icon { color: #ef4444; }

.btn-modal { background: #a93339; color: white; border: none; cursor: pointer; transition: all 0.3s; }
.btn-modal:hover { transform: scale(1.03); background: #881337; }

.fade-enter-active, .fade-leave-active { transition: all 0.4s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: scale(0.9); }

.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>