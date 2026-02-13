<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-800 uppercase tracking-tighter">Modificar Paquete de Gastos</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium">Estás editando el registro ID: <span class="text-red-700 font-black">#{{ gastoId }}</span></p>
                </div>
                <div class="flex gap-3 w-full sm:w-auto">
                    <button @click="router.push('/gastos')" class="btn-secondary flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold bg-white border-2 border-slate-200 text-black uppercase transition-all hover:bg-slate-50">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                </div>
            </div>

            <!-- Loader de hidratación inicial -->
            <div v-if="loadingInitial" class="py-20 text-center animate-pulse">
                <i class="fas fa-circle-notch fa-spin text-5xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Recuperando expediente del servidor...</p>
            </div>

            <form v-else class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- COLUMNA IZQUIERDA: CONSTRUCTOR -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- 1. DATOS DEL VIAJE -->
                        <div class="form-section shadow-premium border-t-4 border-t-red-700">
                            <div class="section-title">
                                <i class="fas fa-map-marked-alt text-red-700"></i> 1. Información del Viaje
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div class="form-group">
                                    <label class="label-style">Fecha de Inicio</label>
                                    <!-- Input de fecha pre-rellenado -->
                                    <input v-model="form.fecha" type="date" class="form-input w-full font-bold" required>
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Estado / Región</label>
                                    <div class="relative">
                                        <!-- Selector de estado pre-rellenado -->
                                        <select v-model="form.estado_nombre" class="form-input w-full appearance-none font-bold" required>
                                            <option value="" disabled>Seleccione el estado...</option>
                                            <option v-for="e in estados" :key="e.id" :value="e.estado">{{ e.estado }}</option>
                                        </select>
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
                                            <option value="Peaje y gasolina">Gasolina</option>
                                            <option value="Peaje">Peaje</option>
                                            <option value="Alimentación">Alimentación</option>
                                            <option value="Hospedaje">Hospedaje</option>
                                            <option value="Mantenimiento de vehículo">Mantenimiento</option>
                                            <option value="Papelería y artículos">Papelería</option>
                                            <option value="Otros">Otros (Especificar)</option>
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
<br><br>
                                    <div class="sm:col-span-4">
                                        <label class="label-mini">Subir Comprobante *</label>
                                        <br><br>
                                        <div class="relative">
                                            <input type="file" ref="lineFileInput" @change="handleLineFileSelect" class="hidden" accept=".jpg,.jpeg,.png,.pdf">
                                           
                                        </div>
                                    </div>
                                </div>
<br>
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-50">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox" v-model="tempSub.es_facturado" class="w-5 h-5 accent-red-600 cursor-pointer">
                                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest group-hover:text-red-600 transition-colors">¿Cuenta con factura fiscal?</span>
                                    </label>
                                    <br><br>
                                    <button type="button" @click="addSubExpense" class="btn-primary py-3.5 px-10 rounded-2xl flex items-center justify-center gap-2 text-xs uppercase font-black tracking-widest shadow-lg" :disabled="!isLineValid">
                                        <i class="fas fa-plus"></i> Añadir al Paquete
                                    </button>
                                </div>
                            </div>
<br><br>
                            <!-- TABLA DE REVISIÓN Y EDICIÓN -->
                            <div class="mt-8 overflow-hidden rounded-[2.5rem] border border-slate-200 bg-white shadow-premium w-full">
                                <div class="overflow-x-auto">
                                    <div class="table-responsive">
                                        <table class="w-full text-sm border-collapse">
                                            <thead class="bg-slate-900 text-white hidden md:table-header-group">
                                                <tr class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">
                                                    <th class="px-6 py-5 text-left">Concepto / Descripción</th>
                                                    <th class="px-6 py-5 text-center">Documento</th>
                                                    <th class="px-6 py-5 text-center">Fiscal</th>
                                                    <th class="px-6 py-5 text-right">Monto</th>
                                                    <th class="px-6 py-5 text-center w-24">Eliminar</th>
                                                </tr>
                                            </thead>

                                            <tbody class="bg-white divide-y divide-gray-100 block md:table-row-group">
                                                <tr v-for="(item, index) in subExpenses" :key="item.localId" 
                                                    class="hover:bg-slate-50/50 transition-colors group block md:table-row relative p-5 md:p-0 border-b md:border-none animate-fade-in">
                                                    
                                                    <td class="table-cell block md:table-cell">
                                                        <div class="flex items-center gap-3">
                                                            <div class="hidden md:flex w-8 h-8 bg-slate-100 text-slate-500 rounded-lg items-center justify-center font-black text-[10px]">
                                                                {{ index + 1 }}
                                                            </div>
                                                            <div class="min-w-0">
                                                                <input v-model="item.descripcion_otros" type="text" class="edit-inline-input form-input" placeholder="Escriba el concepto...">
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td class="table-cell block md:table-cell text-left md:text-center">
                                                        <span class="md:hidden block text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">Documento</span>
                                                        <div class="flex flex-col items-center gap-2">
                                                            <div class="inline-flex items-center gap-2 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl max-w-full" :class="item.file ? 'border-green-200 bg-green-50 text-green-700' : ''">
                                                                <i v-if="item.file" class="fas" :class="item.file.type.includes('pdf') ? 'fa-file-pdf text-red-500' : 'fa-file-image text-blue-500'"></i>
                                                                <i v-else class="fas fa-cloud text-slate-400"></i>
                                                                <span class="text-[10px] font-bold truncate max-w-[120px] uppercase tracking-tighter">
                                                                    {{ item.file ? item.file.name : 'ARCHIVO EN NUBE' }}
                                                                </span>
                                                            </div>
                                                            <div class="flex gap-2">
                                                                <button type="button" @click="triggerChangeFile(item.localId)" class="text-[9px] btn-secondary font-black text-blue-600 uppercase hover:underline">
                                                                    <i class="fas fa-sync-alt"></i> Sustituir
                                                                </button>
                                                                <button v-if="item.file" type="button" @click="item.file = null" class="text-[9px] font-black text-red-600 uppercase hover:underline">
                                                                    <i class="fas fa-times"></i> Quitar
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td class="table-cell block md:table-cell text-left md:text-center">
                                                        <select v-model="item.es_facturado" class="status-select-mini form-input" :class="item.es_facturado ? 'text-green-700 font-black' : 'text-slate-400'">
                                                            <option :value="true">FACTURADO</option>
                                                            <option :value="false">SIN FACTURA</option>
                                                        </select>
                                                    </td>

                                                    <td class="table-cell block md:table-cell text-left md:text-right font-black text-red-700 text-lg md:text-base">
                                                        <div class="flex items-center justify-end">
                                                            <span class="text-xs mr-1 opacity-50 font-black">$</span>
                                                            <input v-model.number="item.monto" type="number" step="0.01" class="edit-inline-input form-input w-24 text-right pr-0 border-b-slate-100">
                                                        </div>
                                                    </td>

                                                    <td class="table-cell block md:table-cell text-center absolute top-4 right-4 md:static">
                                                        <button 
                                                            type="button" 
                                                            @click="confirmDeleteConcept(item.localId)" 
                                                            class="btn-primary  py-3.5 px-10 rounded-2xl flex items-center justify-center gap-2 text-xs uppercase font-black tracking-widest shadow-lg" 
                                                            title="Eliminar esta fila del paquete"
                                                        >
                                                            <i class="fas fa-trash-alt"></i>
                                                            <span class="md:hidden  ml-2 text-[10px] lbc1 uppercase">Borrar Fila</span>
                                                        </button>
                                                    </td>
                                                </tr>

                                                <tr v-if="subExpenses.length === 0">
                                                    <td colspan="5" class="px-6 py-16 text-center">
                                                        <div class="flex flex-col items-center">
                                                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-dashed border-slate-300">
                                                                <i class="fas fa-receipt text-2xl text-slate-200"></i>
                                                            </div>
                                                            <p class="italic font-black uppercase text-[10px] tracking-[0.2em] text-slate-400">El paquete se encuentra vacío</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>

                                            <tfoot v-if="subExpenses.length > 0" class="bg-slate-900 text-white block md:table-footer-group">
                                                <tr>
                                                    <td colspan="3" class="hidden md:table-cell px-8 py-8 text-right uppercase font-black text-slate-500 text-[10px] tracking-[0.2em]">
                                                        Inversión Total Actualizada:
                                                    </td>
                                                    <td class="md:px-8 md:py-8 block md:table-cell text-center md:text-right">
                                                        <div class="text-3xl md:text-2xl font-black text-red-400">
                                                            <span class="text-sm md:text-lg opacity-60">$</span>{{ totalMonto.toFixed(2) }}
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
                    </div>

                    <!-- COLUMNA DERECHA: ACCIÓN -->
                    <div class="lg:col-span-1">
                        <div class="form-section shadow-premium sticky top-28 bg-slate-900 text-white border-none !p-8 rounded-[3rem]">
                            <div class="section-title !text-white !border-white/20">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </div>
                            
                            <div class="space-y-6 py-4">
                                <div class="p-5 bg-white/5 rounded-3xl border border-white/10">
                                    <p class="text-[10px] font-black text-white/40 uppercase mb-4 tracking-[0.2em]">Resumen del Paquete</p>
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-sm font-medium text-white/60">Conceptos:</span>
                                        <span class="font-black">{{ subExpenses.length }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-white/60">Total Actual:</span>
                                        <span class="text-xl font-black text-red-400">${{ totalMonto.toFixed(2) }}</span>
                                    </div>
                                </div>
<br><br>
                                <div class="bg-white/5 p-5 rounded-3xl border border-white/5">
                                    <label class="label-style !text-red-400 mb-2">Motivo del Ajuste (Obligatorio)</label>
                                    <textarea v-model="form.motivo_cambio" class="form-input !bg-slate-800 !border-slate-700 !text-white text-xs" rows="3" placeholder="¿Por qué se eliminó o cambió un gasto?"></textarea>
                                </div>

                                <div class="bg-amber-500/10 border border-amber-500/20 p-4 rounded-2xl">
                                    <p class="text-[10px] text-amber-500 font-black uppercase leading-tight text-center">
                                        <i class="fas fa-info-circle mr-1"></i> 
                                        *Se guardará el registro, detallando qué conceptos fueron eliminados o alterados.
                                    </p>
                                </div>
<br><br>
                                <div class="space-y-4">
                                    <button type="button" @click="handleFinalSubmit('FINALIZADO')" class="btn-primary w-full py-5 rounded-[2rem] text-sm font-black tracking-widest uppercase shadow-2xl transition-all flex items-center justify-center gap-3" :disabled="loading || subExpenses.length === 0">
                                        <i v-if="loading && form.status === 'FINALIZADO'" class="fas fa-spinner fa-spin"></i>
                                        <i v-else class="fas fa-check-double"></i>
                                        {{ (loading && form.status === 'FINALIZADO') ? 'SINCRONIZANDO...' : 'Finalizar y Cerrar' }}
                                    </button>

                                    <button type="button" @click="handleFinalSubmit('BORRADOR')" class="w-full btn-secondary py-4 rounded-2xl text-[11px] font-black tracking-[0.2em] uppercase transition-all bg-white/10 hover:bg-white/20 text-white border border-white/10 flex items-center justify-center gap-3" :disabled="loading || subExpenses.length === 0">
                                        <i v-if="loading && form.status === 'BORRADOR'" class="fas fa-spinner fa-spin"></i>
                                        <i v-else class="fas fa-save"></i>
                                        {{ (loading && form.status === 'BORRADOR') ? 'GUARDANDO...' : 'Actualizar Borrador' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- MODAL DE OVERLAY DINÁMICO -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="modal.visible" class="modal-overlay-wrapper" @click.self="modal.type !== 'success' && !modal.confirm ? modal.visible = false : null">
                    
                    <div v-if="modal.type !== 'success'" class="bg-white form-section  w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100 animate-scale-in">
                        <div :class="['h-2 w-full', modal.type === 'danger' ? 'bg-red-600' : 'bg-blue-600']"></div>
                        <div class="p-10 text-center">
                            <div :class="['w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg', modal.type === 'danger' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600']">
                                <i :class="['fas fa-2xl', modal.type === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle']"></i>
                            </div>
                            <h3 class="text-2xl font-black text-slate-800 leading-tight mb-2 uppercase tracking-tighter">{{ modal.title }}</h3>
                            <p class="text-slate-500 text-sm leading-relaxed font-medium whitespace-pre-wrap">{{ modal.message }}</p>
                        </div>
                        <div class="bg-slate-50 p-6 flex gap-3 border-t">
                            <button v-if="modal.confirm" @click="closeModal" class="flex-1 py-4 text-xs font-black text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">Cancelar</button>
                            <button @click="handleModalConfirm" class="flex-1 py-4 btn-primary rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl transition-all active:scale-95" :class="modal.type === 'danger' ? 'bg-red-600 text-white' : 'bg-slate-900 text-white'">
                                {{ modal.confirm ? 'Confirmar' : 'Entendido' }}
                            </button>
                        </div>
                    </div>

                    <div v-else class="modal-content-success form-section  animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tighter">{{ modal.title }}</h2>
                        <p class="text-sm text-slate-500 mb-8 font-medium px-4">{{ modal.message }}</p>
                        <button @click="closeAndRedirect" class="btn-primary w-full py-4 bg-slate-900 border-none text-white uppercase font-black tracking-widest rounded-2xl shadow-xl">Ir al Historial</button>
                    </div>

                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, nextTick } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const route = useRoute();
const gastoId = route.params.id;

const loading = ref(false);
const loadingInitial = ref(true);
const uploadProgress = ref(0);
const loadingEstados = ref(false);
const estados = ref([]);
const lineFileInput = ref(null);

// Usamos un Map para las referencias de archivos para evitar errores de índice al eliminar filas
const rowFileInputs = ref(new Map());

const subExpenses = ref([]);
const tempSub = reactive({ concepto: '', descripcion_otros: '', monto: null, es_facturado: false, file: null });

const modal = reactive({ visible: false, title: '', message: '', type: 'info', confirm: null });
const form = reactive({ fecha: '', estado_nombre: '', status: 'BORRADOR', motivo_cambio: '' });

const totalMonto = computed(() => subExpenses.value.reduce((acc, item) => acc + (parseFloat(item.monto) || 0), 0));

const isLineValid = computed(() => {
    return tempSub.concepto !== '' && 
           (tempSub.concepto === 'Otros' ? (tempSub.descripcion_otros && tempSub.descripcion_otros.trim() !== '') : true) && 
           tempSub.monto > 0 && 
           tempSub.file !== null;
});

onMounted(async () => {
    await fetchEstados();
    await fetchGastoData();
});

const fetchEstados = async () => {
    loadingEstados.value = true;
    try {
        const response = await axios.get('/estados');
        estados.value = response.data;
    } finally { loadingEstados.value = false; }
};

const fetchGastoData = async () => {
    loadingInitial.value = true;
    try {
        const response = await axios.get(`/gastos/${gastoId}`);
        const data = response.data;
        
        // --- HIDRATACIÓN DE CABECERA CORREGIDA ---
        // 1. Formateo de fecha para input date (YYYY-MM-DD)
        form.fecha = data.fecha ? data.fecha.split('T')[0] : '';
        
        // 2. Normalización de estado para asegurar match en el select
        if (data.estado_nombre && estados.value.length > 0) {
            const match = estados.value.find(e => 
                e.estado.trim().toLowerCase() === data.estado_nombre.trim().toLowerCase()
            );
            form.estado_nombre = match ? match.estado : data.estado_nombre;
        } else {
            form.estado_nombre = data.estado_nombre || '';
        }

        form.status = data.status;

        // --- HIDRATACIÓN DE DETALLES ---
        if (data.detalles && Array.isArray(data.detalles)) {
            // Filtramos registros corruptos y asignamos ID local único
            subExpenses.value = data.detalles
                .filter(d => (d.concepto && d.concepto.trim() !== '') || (d.monto && d.monto > 0))
                .map(d => ({
                    localId: Math.random().toString(36).substring(7),
                    concepto: d.concepto || '',
                    descripcion_otros: d.descripcion || d.concepto || '',
                    monto: d.monto || 0,
                    es_facturado: !!d.es_facturado,
                    file: null 
                }));
        }

        await nextTick();
    } catch (e) {
        openModal('Error de Carga', 'No se pudo recuperar el paquete de gastos.', 'danger');
    } finally { loadingInitial.value = false; }
};

const handleLineFileSelect = (e) => {
    const file = e.target.files[0];
    if (file && file.size > 3072 * 1024) {
        openModal('Archivo muy pesado', 'El comprobante no debe exceder los 3MB.', 'danger');
        e.target.value = '';
        return;
    }
    tempSub.file = file;
};

// Vincula el input de archivo con el ID local de la fila
const setRowFileRef = (el, localId) => {
    if (el) rowFileInputs.value.set(localId, el);
};

const triggerChangeFile = (localId) => {
    const el = rowFileInputs.value.get(localId);
    if (el) el.click();
};

const handleRowFileChange = (e, localId) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 3072 * 1024) {
            openModal('Archivo muy pesado', 'Máximo 3MB.', 'danger');
            return;
        }
        const item = subExpenses.value.find(i => i.localId === localId);
        if (item) item.file = file;
    }
};

const addSubExpense = () => {
    if (!isLineValid.value) return;
    
    const newItem = { 
        localId: Math.random().toString(36).substring(7),
        concepto: tempSub.concepto,
        descripcion_otros: tempSub.concepto === 'Otros' ? tempSub.descripcion_otros : tempSub.concepto,
        monto: parseFloat(tempSub.monto),
        es_facturado: tempSub.es_facturado,
        file: tempSub.file 
    };
    
    subExpenses.value.push(newItem);
    Object.assign(tempSub, { concepto: '', descripcion_otros: '', monto: null, es_facturado: false, file: null });
    if (lineFileInput.value) lineFileInput.value.value = '';
};

// ELIMINACIÓN INDIVIDUAL SEGURA POR ID LOCAL
const confirmDeleteConcept = (localId) => {
    const item = subExpenses.value.find(i => i.localId === localId);
    if (!item) return;
    
    const conceptName = item.descripcion_otros || item.concepto;
    openModal(
        '¿Eliminar concepto?', 
        `Estás por quitar "${conceptName}" de este paquete. Esta acción se hará permanente al sincronizar los cambios.`, 
        'danger', 
        () => executeRemoveSubExpense(localId)
    );
};

const executeRemoveSubExpense = (localId) => {
    const index = subExpenses.value.findIndex(i => i.localId === localId);
    if (index !== -1) {
        subExpenses.value.splice(index, 1);
        rowFileInputs.value.delete(localId);
    }
    closeModal();
};

const confirmDeletePackage = () => {
    if (!form.motivo_cambio || form.motivo_cambio.length < 10) {
        return openModal('Falta Justificación', 'Debe explicar el motivo de la eliminación para el historial (mín. 10 car.).', 'danger');
    }
    openModal(
        '¿Eliminar Paquete Completo?', 
        'Esta acción borrará TODO el registro y todos sus archivos asociados de forma permanente.', 
        'danger', 
        executeDeletePackage
    );
};

const executeDeletePackage = async () => {
    loading.value = true;
    closeModal();
    try {
        await axios.delete(`/gastos/${gastoId}`, {
            params: { motivo: form.motivo_cambio }
        });
        router.push('/gastos');
    } catch (e) {
        openModal('Error', 'No se pudo eliminar el paquete.', 'danger');
    } finally {
        loading.value = false;
    }
};

const openModal = (title, message, type = 'info', confirmCallback = null) => {
    modal.title = title; modal.message = message; modal.type = type; modal.confirm = confirmCallback; modal.visible = true;
};
const closeModal = () => { modal.visible = false; modal.confirm = null; };
const handleModalConfirm = () => { if (modal.confirm) modal.confirm(); else closeModal(); };
const closeAndRedirect = () => { modal.visible = false; router.push('/gastos'); };

const handleFinalSubmit = async (targetStatus) => {
    if (!form.fecha || !form.estado_nombre) return openModal('Datos Incompletos', 'Indique fecha y estado del viaje.', 'danger');
    if (subExpenses.value.length === 0) return openModal('Sin Conceptos', 'Agregue al menos un gasto al paquete.', 'danger');
    
    if (!form.motivo_cambio || form.motivo_cambio.length < 10) {
        return openModal('Falta Justificación', 'Debe explicar el motivo de los cambios para el log de auditoría (mín. 10 car.).', 'danger');
    }

    form.status = targetStatus;
    handleSubmit(targetStatus);
};

const handleSubmit = async (targetStatus) => {
    loading.value = true;
    try {
        const payload = {
            fecha: form.fecha,
            estado_nombre: form.estado_nombre,
            monto_total: totalMonto.value,
            status: targetStatus,
            motivo_cambio: form.motivo_cambio, 
            conceptos: subExpenses.value.map(item => ({
                concepto: item.concepto,
                descripcion: item.descripcion_otros || item.concepto,
                monto: item.monto,
                es_facturado: item.es_facturado
            }))
        };

        await axios.put(`/gastos/${gastoId}`, payload);
        
        const fileFormData = new FormData();
        fileFormData.append('gasto_id', gastoId);
        let hasFiles = false;

        subExpenses.value.forEach((item, index) => {
            if (item.file) {
                fileFormData.append(`files[${index}]`, item.file);
                hasFiles = true;
            }
        });

        if (hasFiles) {
            await axios.post('/gastos/comprobante', fileFormData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                onUploadProgress: (p) => { uploadProgress.value = Math.round((p.loaded * 100) / p.total); }
            });
        }

        openModal('¡Sincronización Exitosa!', 'El paquete y sus archivos han sido actualizados y auditados.', 'success');
    } catch (e) {
        openModal('Fallo en Guardado', e.response?.data?.message || "Error al procesar la actualización.", 'danger');
    } finally { loading.value = false; }
};
</script>

<style scoped>
.label-style { @apply text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block; }
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.form-section { background: white; padding: 30px; border-radius: 40px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #a93339; margin-bottom: 25px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 2px; }

.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #f1f5f9; font-weight: 700; color: #334155; background: #fafbfc; transition: 0.2s; font-size: 0.9rem; }
.form-input:focus { border-color: #a93339; background: white; outline: none; }

.edit-inline-input { @apply w-full bg-transparent border-b-2 border-transparent focus:border-red-500 outline-none font-black text-slate-800 uppercase transition-all py-1; font-size: 13px; }
.status-select-mini { @apply bg-slate-50 border-none rounded-lg py-1 px-2 text-[9px] font-black outline-none cursor-pointer hover:bg-slate-100 transition-colors; }

.table-responsive { width: 100%; overflow-x: auto; background: white; }
.table-cell { padding: 20px 24px; vertical-align: middle; }

.badge-tax-success { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 8px; font-size: 9px; font-weight: 800; border: 1px solid #bbf7d0; text-transform: uppercase; }
.badge-tax-none { background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 8px; font-size: 9px; font-weight: 800; text-transform: uppercase; }

/* REFUERZO VISUAL BOTÓN ELIMINAR FILA */
.btn-delete-row-action { 
    @apply flex items-center justify-center bg-red-50 text-red-600 p-2 md:bg-transparent rounded-xl transition-all hover:scale-110 hover:text-red-800; 
}
.btn-delete-row-action i { font-size: 1.1rem; }

.modal-overlay-wrapper { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 1.5rem; background-color: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); }
.modal-content-success { background: white; padding: 50px; border-radius: 50px; text-align: center; width: 90%; max-width: 400px; border: 1px solid #f1f5f9; }
.success-icon-wrapper { width: 85px; height: 85px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 30px; border: 5px solid white; }

.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.btn-secondary-custom { @apply bg-white border-2 border-slate-200 py-2.5 px-6 rounded-xl text-sm font-black transition-all hover:bg-slate-50 text-black; }
.btn-secondary { padding: 8px 15px; background: white; border: 1px solid #cbd5e1; border-radius: 12px; color: #64748b; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; cursor: pointer; }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.lbc1{ color: white;}
select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 1rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; appearance: none; }
</style>