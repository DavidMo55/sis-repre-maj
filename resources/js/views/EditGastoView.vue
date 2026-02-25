<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen max-w-full overflow-x-hidden">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 bg-white p-4 md:p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl md:text-2xl font-black text-slate-800 uppercase tracking-tighter truncate">Modificar Paquete</h1>
                </div>
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                   <button @click="router.push('/gastos/' + gastoId)" 
                        class="btn-secondary flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black transition-all hover:bg-slate-50 text-black uppercase border-2 border-slate-200 bg-white">
                        <i class="fas fa-eye"></i> Ver Detalle
                    </button>
                </div>
            </div>

            <!-- Loader de hidratación inicial -->
            <div v-if="loadingInitial" class="py-20 text-center animate-pulse">
                <i class="fas fa-circle-notch fa-spin text-5xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Recuperando expediente del servidor...</p>
            </div>

            <!-- BLOQUEO POR REGLA DE NEGOCIO -->
            <div v-else-if="isLocked" class="py-12 md:py-20 text-center bg-white rounded-[2.5rem] md:rounded-[3rem] shadow-premium border-2 border-red-100 animate-fade-in mx-2">
                <div class="w-16 h-16 md:w-20 md:h-20 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-lock text-2xl md:text-3xl"></i>
                </div>
                <h2 class="text-xl md:text-2xl font-black text-slate-800 uppercase tracking-tighter px-4">Edición Bloqueada</h2>
                <p class="text-slate-500 max-w-md mx-auto mt-4 font-medium px-6 text-sm">
                    Este paquete ya fue <strong>FINALIZADO</strong> y modificado una vez. Por políticas de auditoría, no se permiten cambios adicionales.
                </p>
                <button @click="router.push('/gastos')" class="mt-8 btn-primary px-10 py-3 text-xs">Volver al Historial</button>
            </div>

            <form v-else class="space-y-6 pb-24 lg:pb-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
                    
                    <!-- COLUMNA IZQUIERDA: CONSTRUCTOR -->
                    <div class="lg:col-span-2 space-y-6 min-w-0">
                        <!-- 1. DATOS DEL VIAJE -->
                        <div class="form-section shadow-premium border-t-4 border-t-red-700 bg-white p-6 md:p-8 rounded-[2rem] md:rounded-[2.5rem] border border-slate-100">
                            <div class="section-title flex flex-wrap gap-2 items-center">
                                <i class="fas fa-map-marked-alt text-red-700"></i> 
                                <span class="flex-1 section-title ">1. Información del Viaje</span>
                             </div>
                             
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mt-4">
                                <div class="form-group">
                                    <label class="label-style">Fecha de Viaje</label>
                                    <input v-model="form.fecha" type="date" class="form-input w-full font-bold" :disabled="form.status === 'BORRADOR'" :class="{'bg-slate-50 opacity-60': form.status === 'BORRADOR'}" required>
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Estado</label>
                                    <div class="relative">
                                        <select v-model="form.estado_nombre" class="form-input w-full appearance-none font-bold" :disabled="form.status === 'BORRADOR'" :class="{'bg-slate-50 opacity-60': form.status === 'BORRADOR'}" required>
                                            <option value="" disabled>Seleccione el estado...</option>
                                            <option v-for="e in estados" :key="e.id" :value="e.estado">{{ e.estado }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. AGREGAR CONCEPTOS -->
                        <div class="form-section shadow-premium bg-slate-50/50 p-6 md:p-8 rounded-[2rem] md:rounded-[2.5rem] border border-slate-100">
                            <div class="section-title">
                                <i class="fas fa-plus-circle"></i> 2. DETALLE DE CONCEPTOS Y COMPROBANTES
                            </div>
                            
                            <div class="bg-white p-5 md:p-8 rounded-[1.5rem] md:rounded-[2rem] border border-slate-100 shadow-sm space-y-6 mt-4">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                                    <div class="sm:col-span-6 lg:col-span-3">
                                        <label class="label-mini uppercase">Concepto</label>
                                        <select v-model="tempSub.concepto" class="form-input text-sm font-bold w-full">
                                            <option value="" disabled>Seleccione...</option>
                                            <option value="Gasolina">Gasolina</option>
                                            <option value="Peaje">Peaje</option>
                                            <option value="Alimentación">Alimentación</option>
                                            <option value="Hospedaje">Hospedaje</option>
                                            <option value="Mantenimiento">Mantenimiento</option>
                                            <option value="Papelería ">Papelería</option>
                                            <option value="Otros">Otros (Especificar)</option>
                                        </select>
                                    </div>

                                    <div class="sm:col-span-6 lg:col-span-3">
                                        <label class="label-mini uppercase">Descripción</label>
                                        <input v-model="tempSub.descripcion_otros" type="text" class="form-input text-sm w-full font-bold uppercase" placeholder="¿En qué se gastó? mínimo 10 caracteres" required minlength="10" >
                                    </div>

                                    <div class="sm:col-span-6 lg:col-span-2">
                                        <label class="label-mini uppercase">Monto ($)</label>
                                        <input v-model.number="tempSub.monto" type="number" step="0.01" class="form-input text-sm font-black w-full text-red-700" placeholder="0.00">
                                    </div>
                                    <br/><br/>
                                    <div class="sm:col-span-6 lg:col-span-4">
                                        <label class="label-mini uppercase text-red-600">Subir Comprobante *</label>
                                        <div class="relative">
                                            <br/><br/>
                                            <input type="file" ref="lineFileInput" @change="handleLineFileSelect" class="hidden" accept=".jpg,.jpeg,.png,.pdf">
                                           
                                        </div>
                                    </div>
                                </div>
                                <br/><br/>
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-50">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox" v-model="tempSub.es_facturado" class="w-5 h-5 accent-red-600 cursor-pointer">
                                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest group-hover:text-red-600 transition-colors">¿Cuenta con Factura fiscal?</span>
                                    </label>

                                    <br/><br/>
                                    <button type="button" @click="addSubExpense" class="btn-primary w-full sm:w-auto py-3.5 px-10 rounded-2xl flex items-center justify-center gap-2 text-xs uppercase font-black tracking-widest shadow-lg" :disabled="!isLineValid">
                                        <i class="fas fa-plus"></i> Añadir al Paquete
                                    </button>
                                </div>
                            </div>
                            <br/><br/>
                           <!-- TABLA DE REVISIÓN CORREGIDA (RESPONSIVA) -->
                            <div class="mt-8 overflow-hidden rounded-[1.5rem] md:rounded-[2.5rem] border border-slate-200 bg-white shadow-premium">
                                <div class="table-responsive">
                                    <table class="w-full text-sm border-collapse responsive-table">
                                        <thead class="bg-slate-900 text-white hidden md:table-header-group">
                                            <tr class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">
                                                <th class="px-6 py-5 text-left w-16">N</th>
                                                <th class="px-6 py-5 text-left">Concepto / Descripción</th>
                                                <th class="px-6 py-5 text-center w-40">Comprobante</th>
                                                <th class="px-6 py-5 text-right w-32">Monto</th>
                                                <th class="px-6 py-5 text-center w-32">Factura</th>
                                                <th class="px-6 py-5 w-16"></th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-gray-100 block md:table-row-group">
                                            <tr v-for="(item, index) in subExpenses" :key="item.localId" 
                                                class="hover:bg-slate-50/50 transition-colors group animate-fade-in block md:table-row relative p-5 md:p-0 border-b md:border-none">
                                                
                                                <td class="table-cell hidden md:table-cell text-center">
                                                    <div class="flex w-7 h-7 bg-slate-100 text-slate-500 rounded-lg items-center justify-center mx-auto font-black text-[9px] shrink-0">
                                                        {{ index + 1 }}
                                                    </div>
                                                </td>

                                                <td class="table-cell  block md:table-cell" data-label="CONCEPTO / DESCRIPCIÓN">
                                                    <div class="space-y-1.5 ">
                                                        <select v-model="item.concepto" class="status-select-mini form-input  w-full uppercase lbb !bg-white md:!bg-slate-50">
                                                            <option value="Gasolina">Gasolina</option>
                                                            <option value="Peaje">Peaje</option>
                                                            <option value="Alimentación">Alimentación</option>
                                                            <option value="Hospedaje">Hospedaje</option>
                                                            <option value="Mantenimiento">Mantenimiento</option>
                                                            <option value="Papelería">Papelería</option>
                                                            <option value="Otros">Otros</option>
                                                        </select>
                                                        <br>
                                                        <input v-model="item.descripcion_otros" type="text" class="edit-inline-input form-input  w-full" placeholder="Descripción obligatoria..." required minlength="10">
                                                    </div>
                                                </td>

                                                <td class="table-cell block md:table-cell text-left md:text-center" data-label="COMPROBANTE">
                                                    <div class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border-2 transition-all w-full md:w-auto" 
                                                        :class="[
                                                            item.file ? 'border-green-400 bg-green-50 text-green-700' : 
                                                            (item.hasCloudFile ? 'border-slate-200 bg-slate-50 text-slate-500' : 'border-red-500 bg-red-50 text-red-600 animate-pulse')
                                                        ]">
                                                        <i v-if="item.file" class="fas fa-check-circle"></i>
                                                        <i v-else-if="item.hasCloudFile" class="fas fa-cloud"></i>
                                                        <i v-else class="fas fa-exclamation-circle"></i>
                                                        <span class="text-[9px] font-black truncate uppercase tracking-tighter">
                                                            {{ item.file ? 'DOCUMENTO ADJUNTO' : (item.hasCloudFile ? 'DOCUMENTO ADJUNTO' : 'FALTANTE') }}
                                                        </span>
                                                    </div>  
                                                </td>

                                                <td class="table-cell block md:table-cell text-left md:text-right" data-label="MONTO ($)">
                                                    <div class="flex items-center justify-start md:justify-end">
                                                        <span class="text-[10px] mr-1 opacity-50 font-black md:hidden">$</span>
                                                        <input v-model.number="item.monto" type="number" step="0.01" class="edit-inline-input form-input  w-full md:w-24 text-left md:text-right font-black !text-red-700 md:!text-red-400">
                                                    </div>
                                                </td>

                                                <td class="table-cell block md:table-cell text-left md:text-center " data-label="¿Cuenta con Factura fiscal?">
                                                    <select v-model="item.es_facturado" class="status-select-mini form-input  !text-[9px] w-full md:w-auto" :class="item.es_facturado ? 'text-green-700 font-black' : 'text-red-400'">
                                                        <option :value="true">FACTURADO</option>
                                                        <option :value="false">SIN FACTURA</option>
                                                    </select>
                                                </td>

                                                <td class="table-cell block md:table-cell text-center absolute top-5 right-5 md:static">
                                                    <button type="button" @click="confirmDeleteConcept(item.localId)" class="btn-secondary">
                                                        <i class="fas fa-trash-alt"></i>Quitar
                                                    </button>
                                                </td>
                                            </tr>

                                            <tr v-if="subExpenses.length === 0">
                                                <td colspan="6" class="px-6 py-16 text-center italic font-black uppercase text-[10px] tracking-[0.2em] text-slate-400">Sin conceptos registrados</td>
                                            </tr>
                                        </tbody>

                                        <tfoot v-if="subExpenses.length > 0" class="bg-slate-900 text-white block md:table-footer-group">
                                            <tr class="flex flex-col md:table-row p-6 md:p-0">
                                                <td colspan="3" class="hidden md:table-cell px-6 py-6 text-right uppercase font-black text-slate-500 text-[10px] tracking-[0.2em]">Total:</td>
                                                <td class="px-6 py-6 text-center md:text-right border-x border-white/5">
                                                    <div class="text-3xl md:text-xl font-black text-red-400 leading-none">
                                                        <span class="text-xs opacity-60">$</span>{{ totalMonto.toFixed(2) }}
                                                    </div>
                                                </td>
                                                <td class="hidden md:table-cell" colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    
                    </div>

                        
                        <!-- REGLA: El campo de motivo siempre aparece si el paquete ya está FINALIZADO -->        
                        <div v-if="form.status === 'FINALIZADO'" class="bg-white/5 p-5 rounded-3xl border border-white/5 animate-fade-in">
                            <div class="form-section shadow-premium lg:sticky lg:top-28 bg-slate-900 text-white border-none p-6 md:p-8 rounded-[2.5rem] md:rounded-[3rem]">
                            
                            <div class="section-title !text-white !border-white/20">
                                <i class="fas fa-save"></i> 3. MOTIVO DE LA MODIFICACIÓN
                            </div>
                                    <textarea v-model="form.motivo_cambio" class="form-input !bg-slate-800 !border-slate-700 !text-white text-xs" rows="3" placeholder="EXPLIQUE POR QUÉ SE MODIFICA EL PAQUETE..."></textarea>
                                </div>
                    </div>

                    <!-- COLUMNA DERECHA: ACCIÓN -->
                    <div class="lg:col-span-1 min-w-0">
                        <div class="form-section shadow-premium lg:sticky lg:top-28 bg-slate-900 text-white border-none p-6 md:p-8 rounded-[2.5rem] md:rounded-[3rem]">
                            <div class="section-title !text-white !border-white/20">
                                <i class="fas fa-save"></i>  Guardar Paquete de Gastos
                            </div>
                            
                            <div class="space-y-6 mt-6">

                                <!-- ALERTA DE COMPROBANTES FALTANTES -->
                                <div v-if="!allFilesPresent && subExpenses.length > 0" class="bg-red-500/10 border border-red-500/20 p-4 rounded-2xl animate-pulse">
                                    <p class="text-[10px] text-red-400 font-black uppercase leading-tight text-center">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> 
                                        CADA CONCEPTO REQUIERE UN COMPROBANTE
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <button type="button" @click="handleFinalSubmit('FINALIZADO')" class="btn-secondary w-full py-5 rounded-[2rem] text-sm font-black tracking-widest uppercase shadow-2xl transition-all flex items-center justify-center gap-3" :disabled="loading || subExpenses.length === 0 || !allFilesPresent">
                                        <i v-if="loading && form.status === 'FINALIZADO'" class="fas fa-spinner fa-spin"></i>
                                        <i v-else class="fas fa-check-double"></i>
                                        {{ (loading && form.status === 'FINALIZADO') ? 'ENVIANDO...' : 'Finalizar' }}
                                    </button>

                                    <button type="button" @click="handleFinalSubmit('BORRADOR')" class="w-full btn-primary py-4 rounded-2xl text-[11px] font-black tracking-[0.2em] uppercase transition-all bg-white/10 hover:bg-white/20 text-white border border-white/10 flex items-center justify-center gap-3" :disabled="loading || subExpenses.length === 0 || !allFilesPresent">
                                        <i v-if="loading && form.status === 'BORRADOR'" class="fas fa-spinner fa-spin"></i>
                                        <i v-else class="fas fa-save"></i>
                                        {{ (loading && form.status === 'BORRADOR') ? 'GUARDANDO...' : 'Borrador' }}
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
                    
                    <div v-if="modal.type !== 'success'" class="bg-white w-full form-section max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100 animate-scale-in mx-4">
                        <div :class="['h-2 w-full', modal.type === 'danger' ? 'bg-red-600' : 'bg-blue-600']"></div>
                        <div class="p-8 md:p-10 text-center">
                            <div :class="['w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg', modal.type === 'danger' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600']">
                                <i :class="['fas fa-2xl', modal.type === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle']"></i>
                            </div>
                            <h3 class="text-xl md:text-2xl font-black text-slate-800 leading-tight mb-2 uppercase tracking-tighter">{{ modal.title }}</h3>
                            
                            <!-- Lista de errores específicos -->
                            <div v-if="modal.errorList && modal.errorList.length" class="mt-4 space-y-2 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <p v-for="(err, i) in modal.errorList" :key="i" class="text-[11px] font-black text-slate-600 uppercase tracking-tight text-center">
                                    <i class="fas fa-caret-right text-red-400 mr-1"></i> {{ err }}
                                </p>
                            </div>
                            <p v-else class="text-slate-500 text-sm leading-relaxed font-medium whitespace-pre-wrap">{{ modal.message }}</p>
                        </div>
                        <div class="bg-slate-50 p-6 flex gap-3 border-t">
                            <button v-if="modal.confirm" @click="closeModal" class="flex-1 btn-secondary !py-4 text-xs font-black text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest border-none bg-transparent cursor-pointer">Cancelar</button>
                            <button @click="handleModalConfirm" class="flex-1 btn-primary py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl transition-all active:scale-95 border-none cursor-pointer" :class="modal.type === 'danger' ? 'bg-red-600 text-white' : 'bg-slate-900 text-white'">
                                {{ modal.confirm ? 'Confirmar' : 'Entendido' }}
                            </button>
                        </div>
                    </div>

                    <div v-else class="modal-content-success animate-scale-in mx-4 form-section">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tighter">¡Éxito!</h2>
                        <p class="text-sm text-slate-500 mb-8 font-medium px-4">{{ modal.message }}</p>
                        <button @click="closeAndRedirect" class="btn-primary w-full py-4 bg-slate-900 border-none text-white uppercase font-black tracking-widest rounded-2xl shadow-xl cursor-pointer">Continuar</button>
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

const rowFileInputs = ref(new Map());

const subExpenses = ref([]);
const originalItemsSnapshot = ref([]); // Snapshot para detectar cambios en datos previos
const tempSub = reactive({ concepto: '', descripcion_otros: '', monto: null, es_facturado: false, file: null });

const modal = reactive({ visible: false, title: '', message: '', type: 'info', confirm: null, errorList: [] });
const form = reactive({ fecha: '', estado_nombre: '', status: 'BORRADOR', motivo_cambio: '', modificaciones_finalizadas: 0 });

const totalMonto = computed(() => subExpenses.value.reduce((acc, item) => acc + (parseFloat(item.monto) || 0), 0));

const isLocked = computed(() => {
    return form.status === 'FINALIZADO' && form.modificaciones_finalizadas >= 1;
});

/**
 * REGLA: Detecta si se ha modificado o eliminado un concepto que ya existía.
 */
const hasModifiedOriginals = computed(() => {
    // 1. ¿Se eliminó algún concepto original?
    const currentOriginals = subExpenses.value.filter(i => originalItemsSnapshot.value.some(o => o.localId === i.localId));
    if (currentOriginals.length < originalItemsSnapshot.value.length) return true;

    // 2. ¿Se modificó el contenido de un concepto original?
    for (const original of originalItemsSnapshot.value) {
        const current = subExpenses.value.find(i => i.localId === original.localId);
        if (current) {
            if (current.concepto !== original.concepto || 
                current.descripcion_otros !== original.descripcion_otros || 
                current.monto !== original.monto || 
                current.es_facturado !== original.es_facturado) {
                return true;
            }
        }
    }
    return false;
});

const allFilesPresent = computed(() => {
    if (subExpenses.value.length === 0) return false;
    return subExpenses.value.every(item => item.file !== null || item.hasCloudFile);
});

const isLineValid = computed(() => {
    const hasConcept = tempSub.concepto !== '';
    const hasDescription = tempSub.descripcion_otros && tempSub.descripcion_otros.trim().length >= 10;
    const hasAmount = tempSub.monto !== null && tempSub.monto > 0;
    const hasFile = tempSub.file !== null;
    return hasConcept && hasDescription && hasAmount && hasFile;
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
        
        form.fecha = data.fecha ? data.fecha.split('T')[0] : '';
        form.status = data.status;
        form.modificaciones_finalizadas = data.modificaciones_finalizadas || 0;
        
        if (data.estado_nombre && estados.value.length > 0) {
            const match = estados.value.find(e => 
                e.estado.trim().toLowerCase() === data.estado_nombre.trim().toLowerCase()
            );
            form.estado_nombre = match ? match.estado : data.estado_nombre;
        } else {
            form.estado_nombre = data.estado_nombre || '';
        }

        const standardConcepts = ["Gasolina", "Peaje", "Alimentación", "Hospedaje", "Mantenimiento", "Papelería"];

        if (data.detalles && Array.isArray(data.detalles)) {
            const mapped = data.detalles
                .filter(d => (d.concepto && d.concepto.trim() !== '') || (d.monto && d.monto > 0))
                .map((d, index) => {
                    const isStandard = standardConcepts.includes(d.concepto);
                    return {
                        localId: Math.random().toString(36).substring(7),
                        concepto: isStandard ? d.concepto : 'Otros',
                        descripcion_otros: d.descripcion || d.concepto || '',
                        monto: d.monto || 0,
                        es_facturado: !!d.es_facturado,
                        file: null,
                        hasCloudFile: !!(data.comprobantes && data.comprobantes[index])
                    };
                });
            
            subExpenses.value = mapped;
            // Creamos una copia profunda para comparar cambios
            originalItemsSnapshot.value = JSON.parse(JSON.stringify(mapped));
        }
    } catch (e) {
        openModal('Error de Carga', 'No se pudo recuperar el expediente del paquete.', 'danger');
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
        descripcion_otros: tempSub.descripcion_otros || tempSub.concepto,
        monto: parseFloat(tempSub.monto),
        es_facturado: tempSub.es_facturado,
        file: tempSub.file,
        hasCloudFile: false 
    };
    subExpenses.value.push(newItem);
    Object.assign(tempSub, { concepto: '', descripcion_otros: '', monto: null, es_facturado: false, file: null });
    if (lineFileInput.value) lineFileInput.value.value = '';
};

const confirmDeleteConcept = (localId) => {
    const item = subExpenses.value.find(i => i.localId === localId);
    if (!item) return;
    openModal(
        '¿Eliminar concepto?', 
        `Estás por quitar "${item.descripcion_otros || item.concepto}" de este paquete.`, 
        'danger', 
        () => executeRemoveSubExpense(localId)
    );
};

const executeRemoveSubExpense = (localId) => {
    const index = subExpenses.value.findIndex(i => i.localId === localId);
    if (index !== -1) {
        subExpenses.value.splice(index, 1);
        if (rowFileInputs.value.has(localId)) {
            rowFileInputs.value.delete(localId);
        }
    }
    closeModal();
};

const openModal = (title, message, type = 'info', confirmCallback = null, errors = []) => {
    modal.title = title; modal.message = message; modal.type = type; 
    modal.confirm = confirmCallback; modal.errorList = errors; modal.visible = true;
};
const closeModal = () => { modal.visible = false; modal.confirm = null; modal.errorList = []; };
const handleModalConfirm = () => { if (modal.confirm) modal.confirm(); else closeModal(); };
const closeAndRedirect = () => { modal.visible = false; router.push('/gastos'); };

const handleFinalSubmit = async (targetStatus) => {
    const list = [];
    if (!form.fecha) list.push('Indique la fecha del viaje.');
    if (!form.estado_nombre) list.push('Indique el estado / región.');
    if (subExpenses.value.length === 0) list.push('No hay conceptos en el paquete.');
    
    subExpenses.value.forEach((item, idx) => {
        if (!item.file && !item.hasCloudFile) {
            list.push(`Concepto #${idx + 1} requiere comprobante.`);
        }
    });
    
    if (list.length > 0) return openModal('Datos Incompletos', '', 'danger', null, list);

    /**
     * REGLA SOLICITADA ACTUALIZADA:
     * El motivo SIEMPRE es obligatorio si el paquete ya está en estado FINALIZADO,
     * para asegurar que cualquier reapertura y re-envío quede auditado.
     */
    const needsJustification = form.status === 'FINALIZADO';

    if (targetStatus === 'FINALIZADO' && needsJustification && (!form.motivo_cambio || form.motivo_cambio.length < 10)) {
        return openModal('Justificación Requerida', 'Al ser un paquete previamente FINALIZADO, cualquier ajuste requiere una explicación del motivo (mín. 10 car.).', 'danger');
    }

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

        openModal('¡Éxito!', 'Los cambios se han guardado correctamente.', 'success');
    } catch (e) {
        openModal('Fallo en Guardado', e.response?.data?.message || "Error al procesar.", 'danger');
    } finally { loading.value = false; }
};
</script>

<style scoped>
.label-style { @apply text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block; }
.shadow-premium { box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); }
.form-section { background: white; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #030303; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 2px; }

.form-input { width: 100%; padding: 10px 10px; border-radius: 16px; border: 2px solid #f1f5f9; font-weight: 700; color: #334155; background: #fafbfc; transition: all 0.2s; font-size: 0.85rem; }
.form-input:focus { border-color: #a93339; background: white; outline: none; }
.form-input:disabled { cursor: not-allowed; }

.edit-inline-input { @apply w-full bg-transparent border-b-2 border-slate-100 focus:border-red-500 outline-none font-black text-slate-800 uppercase transition-all py-1; font-size: 11px; }
.status-select-mini { @apply bg-slate-50 border-none rounded-lg py-1 px-2 text-[9px] font-black outline-none cursor-pointer hover:bg-slate-100 transition-colors; }

.table-responsive { width: 100%; overflow-x: auto; background: white; -webkit-overflow-scrolling: touch; }
.table-cell { padding: 16px 20px; vertical-align: middle; }

.btn-delete-row-action { 
    @apply flex items-center justify-center bg-red-50 text-red-600 p-2 rounded-xl transition-all hover:scale-110 hover:text-red-800 border-none cursor-pointer; 
    width: 32px; height: 32px;
}
.btn-delete-row-action i { font-size: 0.9rem; }

.modal-overlay-wrapper { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; background-color: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); }
.modal-content-success { background: white; padding: 40px; border-radius: 40px; text-align: center; width: 90%; max-width: 400px; border: 1px solid #f1f5f9; }
.success-icon-wrapper { width: 70px; height: 70px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 20px; border: 4px solid white; }

.btn-primary { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; border-radius: 16px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 20px rgba(169, 51, 57, 0.1); transition: all 0.2s; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.btn-primary-gradient { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }

.btn-secondary-custom { @apply bg-white border-2 border-slate-200 py-2.5 px-6 rounded-xl text-sm font-black transition-all hover:bg-slate-50 text-black; }
.btn-secondary { padding: 8px 15px; background: white; border: 1px solid #cbd5e1; border-radius: 12px; color: #64748b; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; cursor: pointer; }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.scrollbar-thin::-webkit-scrollbar { height: 4px; }
.scrollbar-thin::-webkit-scrollbar-track { background: #f8fafc; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em; padding-right: 2.25rem; appearance: none; }

.bgcolor { background: #fef2f2; border: 1px solid #fee2e2; padding: 16px; }

@media (max-width: 768px) {
    .responsive-table { display: block; border: none; }
    .responsive-table thead { display: none; } 
    .responsive-table tbody { display: block; }
    .responsive-table tr { 
        display: block; 
        margin-bottom: 1.5rem; 
        border: 1px solid #f1f5f9; 
        border-radius: 20px; 
        padding: 15px;
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .responsive-table td { 
        display: flex; 
        flex-direction: column; 
        padding: 8px 0; 
        border: none;
        text-align: left !important;
    }
    .responsive-table td::before {
        content: attr(data-label);
        font-size: 8px;
        font-weight: 900;
        text-transform: uppercase;
        color: #d56b6b;
        margin-bottom: 4px;
        letter-spacing: 0.1em;
    }
}

</style>