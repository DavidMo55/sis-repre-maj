<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-black uppercase tracking-tighter">Edición de Pedido #{{ pedidoFolio }}</h1>
                    <p class="text-xs md:text-sm text-red-600 font-bold uppercase tracking-widest mt-1">Modifique cualquier dato del expediente, incluyendo la logística y dirección.</p>
                </div>
                <button @click="router.push('/pedidos')" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto uppercase">
                    <i class="fas fa-arrow-left mr-2"></i> Cancelar y Volver
                </button>
            </div>

            <!-- Loader Inicial -->
            <div v-if="loadingInitial" class="py-20 text-center animate-pulse">
                <i class="fas fa-circle-notch fa-spin text-4xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Cargando expediente del pedido...</p>
            </div>
            
            <form v-else @submit.prevent="submitUpdate" class="space-y-6 animate-fade-in">

                <!-- 1. INFORMACIÓN DEL CLIENTE (Editable) -->
                <div class="form-section shadow-premium border-t-4 border-t-black !overflow-visible" :class="{'border-red-500 ring-1 ring-red-100': errors.clientId}">
                    <div class="section-title text-black">
                        <i class="fas fa-user-circle text-red-700"></i> 1. Información del Cliente
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group relative">
                            <label class="label-style">Cambiar Plantel / Distribuidor *</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    class="form-input pl-10 font-bold lbb uppercase"  
                                    placeholder="BUSCAR POR NOMBRE..." 
                                    v-model="orderForm.clientName"
                                    @input="searchClients"
                                    autocomplete="off"
                                    required
                                >
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-red-400"></i>
                            </div>
                            <ul v-if="clientSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                <li v-for="client in clientSuggestions" :key="client.id" @click="selectClient(client)" class="p-3 border-b last:border-0 hover:bg-red-50 transition-colors cursor-pointer">
                                    <div class="text-xs font-black text-black uppercase">{{ client.name }}</div>
                                    <div class="text-[9px] text-red-500 uppercase font-black tracking-widest mt-1">{{ client.tipo }}</div>
                                </li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label class="label-style">Prioridad de Atención:</label>
                            <select v-model="orderForm.prioridad" class="form-input font-bold text-red-700 uppercase">
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. RECEPCIÓN Y LOGÍSTICA -->
                <div class="form-section shadow-premium border-t-4 border-t-black !overflow-visible">
                    <div class="section-title text-black">
                        <i class="fas fa-truck text-slate-800"></i> 2. Recepción y Logística de Envío
                    </div>
                    
                    <div class="space-y-6">
                        <div class="form-group">
                            <label class="label-style">Método de Envío:</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                                <label class="shipping-card" :class="{'active': orderForm.logistics.deliveryOption === 'paqueteria'}">
                                    <input type="radio" value="paqueteria" v-model="orderForm.logistics.deliveryOption" class="hidden">
                                    <i class="fas fa-box"></i>
                                    <span>Paquetería</span>
                                </label>
                                <label class="shipping-card" :class="{'active': orderForm.logistics.deliveryOption === 'recoleccion'}">
                                    <input type="radio" value="recoleccion" v-model="orderForm.logistics.deliveryOption" class="hidden">
                                    <i class="fas fa-warehouse"></i>
                                    <span>Recolección</span>
                                </label>
                                <label class="shipping-card" :class="{'active': orderForm.logistics.deliveryOption === 'entrega'}">
                                    <input type="radio" value="entrega" v-model="orderForm.logistics.deliveryOption" class="hidden">
                                    <i class="fas fa-shuttle-van"></i>
                                    <span>Entrega Directa</span>
                                </label>
                            </div>

                            <div class="mt-6 space-y-4 animate-fade-in">
                                <div v-if="orderForm.logistics.deliveryOption === 'paqueteria'">
                                    <label class="label-mini">Empresa de Paquetería sugerida:</label>
                                    <input v-model="orderForm.logistics.paqueteria_nombre" type="text" class="form-input border-red-200 text-red-700 font-bold uppercase" placeholder="DHL, FEDEX, ETC.">
                                </div>
                                <div v-if="['recoleccion', 'entrega'].includes(orderForm.logistics.deliveryOption)">
                                    <label class="label-mini">Instrucciones Logísticas:</label>
                                    <textarea v-model="orderForm.logistics.comentarios_logistica" class="form-input text-red-600 font-medium uppercase" rows="2" placeholder="NOTAS PARA ALMACÉN..."></textarea>
                                </div>
                            </div>
                        </div>

                        <hr class="border-red-100">

                        <!-- SELECTOR DE ORIGEN DE DATOS -->
                        <div class="bg-red-50/20 p-5 rounded-3xl border border-red-100 flex flex-wrap gap-6 items-center">
                            <label class="text-[10px] font-black text-red-800 uppercase tracking-widest">Origen de Datos de Envío:</label>
                            <div class="flex flex-wrap gap-4 md:gap-8">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" value="cliente" v-model="orderForm.receiverType" class="w-4 h-4 accent-red-600" :disabled="!orderForm.clientId">
                                    <span class="text-[11px] font-black text-red-700 uppercase">Datos del Cliente</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" value="existente" v-model="orderForm.receiverType" class="w-4 h-4 accent-red-600">
                                    <span class="text-[11px] font-black text-red-700 uppercase">Buscar Datos</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" value="nuevo" v-model="orderForm.receiverType" class="w-4 h-4 accent-red-600">
                                    <span class="text-[11px] font-black text-red-700 uppercase">Ingresar datos</span>
                                </label>
                            </div>
                        </div>

                        <transition name="fade-slide">
                            <div v-if="isFormBlockedByDuplicates" class="bg-red-600 text-white rounded-4 shadow-lg p-4 flex items-center gap-4">
                                <i class="fas fa-exclamation-triangle fs-4"></i>
                                <div class="bgcolor flex-1 p-3 rounded-3 shadow-inner">
                                    <h4 class="mb-1 font-black uppercase text-xs text-red-700">Registros duplicados detectados</h4>
                                    <p class="mb-0 font-bold uppercase text-[9px] text-red-600">El RFC, Correo o Teléfono ya existen. Verifique o use "Buscar Datos".</p>
                                </div>
                            </div>
                        </transition>

                        <!-- BÚSQUEDA DE RECEPTOR EXISTENTE -->
                        <div v-if="orderForm.receiverType === 'existente'" class="animate-fade-in space-y-4">
                            <div class="form-group relative">
                                <label class="label-style">Buscar Receptor Registrado</label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        class="form-input pl-10 font-bold uppercase border-red-200" 
                                        v-model="searchReceiverQuery" 
                                        @input="searchExistingReceivers"
                                        placeholder="ESCRIBA RFC O NOMBRE..."
                                        autocomplete="off"
                                    >
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-red-400"></i>
                                    <i v-if="searchingExisting" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                                </div>
                                <ul v-if="receiverSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                    <li v-for="rec in receiverSuggestions" :key="rec.id" @click="selectExistingReceiver(rec)" class="p-3 border-b last:border-0 hover:bg-red-50 cursor-pointer">
                                        <div class="text-xs font-black text-slate-800 uppercase">{{ rec.nombre }}</div>
                                        <div class="flex gap-4 mt-1">
                                            <span class="text-[8px] font-bold text-red-500 uppercase">RFC: {{ rec.rfc }}</span>
                                            <span class="text-[8px] font-bold text-slate-400 uppercase">TEL: {{ rec.telefono }}</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- FICHA RESUMEN (PARA CLIENTE O EXISTENTE) -->
                        <div v-if="['cliente', 'existente'].includes(orderForm.receiverType)" class="animate-fade-in">
                            <div v-if="activeReceiverDisplay" class="receiver-summary-card shadow-sm border border-red-100 rounded-[2.5rem] p-8 bg-white relative overflow-hidden group">
                                <div class="relative z-10 space-y-1">
                                    <p class="text-[10px] font-black text-red-400 uppercase tracking-[0.2em] mb-3">Información de Destino Seleccionada</p>
                                    <h4 class="text-2xl font-black text-black leading-none mb-3 uppercase tracking-tighter">{{ activeReceiverDisplay.nombre || activeReceiverDisplay.contacto || activeReceiverDisplay.name }}</h4>
                                    
                                    <div class="flex flex-wrap gap-x-8 gap-y-2">
                                        <p class="text-xs font-bold text-red-600 uppercase"><i class="fas fa-id-card mr-2 text-red-300"></i> RFC: <span class="text-black font-black">{{ activeReceiverDisplay.rfc }}</span></p>
                                        <p class="text-xs font-bold text-red-600 uppercase"><i class="fas fa-envelope mr-2 text-red-300"></i> {{ activeReceiverDisplay.correo || activeReceiverDisplay.email }}</p>
                                    </div>

                                    <div class="mt-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Régimen Fiscal</label>
                                        <p class="text-xs font-bold text-slate-800 uppercase flex items-center">
                                            <i class="fas fa-file-invoice text-red-700 mr-2"></i>
                                            {{ activeReceiverDisplay.regimen_fiscal || activeReceiverDisplay.receiver_regimen_fiscal || 'SIN RÉGIMEN' }}
                                        </p>
                                    </div>
                                    
                                   <p class="text-xs text-red-500 mt-4 italic font-medium leading-relaxed uppercase">
                                        <i class="fas fa-map-marker-alt mr-2 text-red-400"></i> 
                                        {{ activeReceiverDisplay.direccion }}
                                    </p>
                                </div>
                                <i class="fas fa-user-check absolute -right-6 -bottom-6 text-[10rem] text-red-50/50"></i>
                            </div>
                        </div>

                        <!-- FORMULARIO MANUAL (COMPLETAMENTE EDITABLE) -->
                        <div v-if="orderForm.receiverType === 'nuevo'" class="animate-fade-in space-y-6 bg-white border border-red-100 p-8 rounded-[3rem] shadow-sm">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="form-group relative">
                                    <label class="label-style">RFC del Receptor *</label>
                                    <input v-model="orderForm.receiver.rfc" @blur="validateUniqueness('rfc')" type="text" class="form-input font-mono uppercase font-black" :class="fieldValidation.rfc.error ? 'border-red-600 bg-red-50 text-red-700' : ''" placeholder="XXXXXXXXXXXXX" required maxlength="13">
                                </div>
                                <div class="form-group relative">
                                    <label class="label-style">Nombre / Destinatario *</label>
                                    <input v-model="orderForm.receiver.persona_recibe" @blur="validateUniqueness('persona_recibe')" type="text" class="form-input font-bold uppercase" :class="fieldValidation.persona_recibe.error ? 'border-red-600 bg-red-50 text-red-700' : ''" placeholder="NOMBRE COMPLETO" required minlength="5">
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Régimen Fiscal *</label>
                                    <select v-model="orderForm.receiver.regimen_fiscal" required class="form-input font-bold text-xs uppercase">
                                        <option value="">SELECCIONAR...</option>
                                        <option value="601">601 - GENERAL MORALES</option>
                                        <option value="612">612 - PF ACT. EMPRESARIAL</option>
                                        <option value="626">626 - RESICO</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="label-style">Correo Electrónico *</label>
                                    <input v-model="orderForm.receiver.correo" @blur="validateUniqueness('correo')" type="email" class="form-input text-red-700 font-bold" :class="fieldValidation.correo.error ? 'border-red-600 bg-red-50' : ''" placeholder="correo@ejemplo.com" required>
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Teléfono de Contacto *</label>
                                    <input v-model="orderForm.receiver.telefono" @blur="validateUniqueness('telefono')" type="tel" class="form-input text-red-700 font-bold uppercase" :class="fieldValidation.telefono.error ? 'border-red-600 bg-red-50' : ''" placeholder="10 DÍGITOS" required minlength="10" maxlength="10">
                                </div>
                            </div>

                            <!-- DIRECCIÓN MANUAL DESGLOSADA -->
                            <div class="bg-red-50/20 p-8 rounded-[2.5rem] border border-red-100 space-y-6">
                                <p class="text-[10px] font-black text-red-800 uppercase tracking-widest border-b border-red-100 pb-2 mb-4">Ubicación de Entrega</p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                                    <div class="form-group relative">
                                        <label class="label-mini">C.P. *</label>
                                        <div class="relative">
                                            <input v-model="orderForm.receiver.cp" required type="text" class="form-input font-mono font-black uppercase" maxlength="5" @input="handleCPInput" placeholder="00000">
                                            <i v-if="searchingCP" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                                        </div>
                                    </div>
                                    <div class="form-group col-span-1">
                                        <label class="label-mini">Estado</label>
                                        <input v-model="orderForm.receiver.estado" type="text" placeholder="AUTO" class="form-input bg-white font-bold text-red-800 uppercase" readonly>
                                    </div>
                                    <div class="form-group col-span-2">
                                        <label class="label-mini">Municipio / Alcaldía</label>
                                        <input v-model="orderForm.receiver.municipio" type="text" placeholder="AUTO" class="form-input bg-white font-bold text-red-800 uppercase" readonly>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-group">
                                        <label class="label-mini">Colonia / Asentamiento *</label>
                                        <select v-model="orderForm.receiver.colonia" required class="form-input font-bold text-red-700 uppercase" :disabled="!colonias.length">
                                            <option value="" disabled>{{ colonias.length ? 'SELECCIONE...' : 'INGRESE CP' }}</option>
                                            <option v-for="(col, idx) in colonias" :key="idx" :value="col">{{ col }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="label-mini">Calle y Número Exterior *</label>
                                        <input required v-model="orderForm.receiver.calle_num" type="text" class="form-input font-bold text-red-700 uppercase" placeholder="EJ: AV. JUÁREZ 123">
                                    </div>
                                </div>
                            </div>
                        </div>

                       
                    </div>
                </div>

                 <div class="form-section shadow-premium border-t-4 border-t-black !overflow-visible">
                            <div class="form-group">
                                <label class="label-style">Comentarios Generales del Pedido:</label>
                                <textarea v-model="orderForm.comments" required minlength="10" class="form-input text-red-600 font-medium uppercase" rows="3" placeholder="NOTAS ADICIONALES PARA ALMACÉN..."></textarea>
                            </div>
                        </div>

                <!-- 3. GESTIÓN DE MATERIALES -->
                <div class="form-section !overflow-visible shadow-premium border-t-4 border-t-black" :class="{'border-red-500 ring-1 ring-red-100': errors.items}">
                    <div class="section-title text-black"><i class="fas fa-book-open text-red-700"></i> 3. Selección de Material</div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end bg-red-50/20 p-6 rounded-[2.5rem] border border-red-100">
                        <div class="md:col-span-2"><label class="label-mini">Rubro</label><select v-model="currentOrderItem.tipo_material" class="form-input font-black uppercase text-[10px] text-red-700"><option value="promocion">PROMO</option><option value="venta">VENTA</option></select></div>
                        <div class="md:col-span-3 relative">
                            <label class="label-mini">Buscar Libro</label>
                            <input type="text" class="form-input pr-10 font-bold text-black uppercase" v-model="currentOrderItem.bookName" placeholder="TÍTULO..." @input="searchBooks" autocomplete="off">
                            <ul v-if="currentOrderItem.bookSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                <li v-for="book in currentOrderItem.bookSuggestions" :key="book.id" @click="selectBook(book)" class="p-3 border-b last:border-0 hover:bg-red-50 transition-colors cursor-pointer text-xs font-black uppercase text-black">{{ book.titulo }}</li>
                            </ul>
                        </div>
                        <div class="md:col-span-3"><label class="label-mini">Formato / Licencia</label><select class="form-input font-bold text-red-700 uppercase text-xs" v-model="currentOrderItem.sub_type" :disabled="!currentOrderItem.bookId"><option value="" disabled>SELECCIONAR...</option><option v-for="opt in availableSubTypes" :key="opt" :value="opt">{{ opt }}</option></select></div>
                        <div class="md:col-span-1"><label class="label-mini">Cant.</label><input type="number" min="1" class="form-input font-bold text-red-700 text-center" v-model.number="currentOrderItem.quantity"></div>
                        <div class="md:col-span-2"><label class="label-mini">Precio ($)</label><input type="number" step="0.01" class="form-input font-black text-red-700" v-model.number="currentOrderItem.price" :disabled="currentOrderItem.tipo_material === 'promocion'"></div>
                        <div class="md:col-span-1"><button type="button" @click="addItemToCart" class="btn-primary w-full py-4 rounded-2xl shadow-xl transition-all active:scale-95"><i class="fas fa-cart-plus"></i>Agregar</button></div>
                    </div>

                    <div class="mt-8 overflow-hidden rounded-[2.5rem] border border-red-100 bg-white">
                        <div class="table-responsive">
                            <table class="min-width-full divide-y divide-red-200">
                                <thead class="bg-black text-white text-[9px] font-black uppercase tracking-widest">
                                    <tr><th class="px-6 py-5 text-left">Título</th><th class="px-6 py-5 text-center">Tipo</th><th class="px-6 py-5 text-center">Cant.</th><th class="px-6 py-5 text-right">Total</th><th class="px-6 py-5 w-20"></th></tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-red-50">
                                    <tr v-for="(item, index) in orderForm.orderItems" :key="item.id" class="hover:bg-red-50/50 transition-colors group">
                                        <td class="table-cell">
                                            <div class="font-black text-black text-[13px] uppercase leading-tight">{{ item.bookName }}</div>
                                            <div class="text-[9px] text-red-500 uppercase font-black mt-1">{{ item.sub_type }}</div>
                                        </td>
                                        <td class="table-cell text-center"><span :class="item.tipo_material === 'promocion' ? 'badge-material-promo' : 'badge-material-sale'">{{ item.tipo_material.toUpperCase() }}</span></td>
                                        <td class="table-cell text-center font-black text-red-800 text-lg">{{ item.quantity }}</td>
                                        <td class="table-cell text-right font-black text-red-700 text-sm">{{ formatCurrency(item.totalCost) }}</td>
                                        <td class="table-cell text-center"><button type="button" @click="orderForm.orderItems.splice(index, 1)" class="btn-delete-item">BORRAR</button></td>
                                    </tr>
                                    <tr v-if="!orderForm.orderItems.length"><td colspan="5" class="px-6 py-20 text-center italic text-slate-300 font-black text-[10px] uppercase tracking-widest">No hay materiales en este pedido</td></tr>
                                </tbody>
                                <tfoot v-if="orderForm.orderItems.length" class="bg-red-50/30 border-t-2 border-red-100">
                                    <tr>
                                        <td colspan="2" class="px-8 py-8 text-right font-black text-[10px] uppercase text-red-800 tracking-widest">Total Actualizado:</td>
                                        <td class="px-6 py-8 text-center font-black text-red-700 text-2xl border-x border-red-100/50">{{ totalUnits }}</td>
                                        <td class="px-6 py-8 text-right font-black text-3xl text-red-700 tracking-tighter leading-none">{{ formatCurrency(orderTotal) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-12 flex justify-end">
                    <button type="submit" class="btn-primary px-20 py-6 text-lg font-black tracking-widest shadow-2xl transition-all active:scale-95" :disabled="loading || isFormBlockedByDuplicates">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save mr-3'"></i> GUARDAR CAMBIOS
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL DE ÉXITO -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="successModal" class="modal-overlay-wrapper">
                    <div class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="text-2xl font-black text-black mb-3 uppercase tracking-tighter">¡Pedido Actualizado!</h2>
                        <p class="text-sm text-slate-500 mb-8 font-medium">Los cambios y la logística se han sincronizado con éxito.</p>
                        <button @click="router.push('/pedidos')" class="btn-primary w-full py-5 bg-black border-none text-white font-black uppercase tracking-widest rounded-2xl">Regresar al Historial</button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const route = useRoute();
const id = route.params.id;

const loading = ref(false);
const loadingInitial = ref(true);
const searchingCP = ref(false);
const successModal = ref(false);
const pedidoFolio = ref('');
const searchingLibros = ref(false);
const searchingClients = ref(false);
const searchingExisting = ref(false);
const clientSuggestions = ref([]);
const receiverSuggestions = ref([]);
const selectedCliente = ref(null); 
const selectedExistingReceiver = ref(null);
const searchReceiverQuery = ref('');
const estados = ref([]);
const colonias = ref([]);

const orderForm = reactive({
    prioridad: 'media', 
    clientId: null, 
    clientName: '', 
    receiverType: 'cliente',
    receiver: { persona_recibe: '', rfc: '', regimen_fiscal: '', telefono: '', correo: '', cp: '', estado: '', municipio: '', colonia: '', calle_num: '' },
    logistics: { deliveryOption: 'paqueteria', paqueteria_nombre: '', comentarios_logistica: '' },
    comments: '', 
    orderItems: [], 
});

const fieldValidation = reactive({ rfc: { error: false }, correo: { error: false }, telefono: { error: false }, persona_recibe: { error: false } });
const validatingFields = reactive({ rfc: false, correo: false, telefono: false, persona_recibe: false });
const currentOrderItem = reactive({ bookId: null, bookName: '', tipo_material: 'venta', category: '', sub_type: '', quantity: 1, price: 0, bookSuggestions: [] });
const errors = reactive({ clientId: false, items: false });

const isFormBlockedByDuplicates = computed(() => {
    if (orderForm.receiverType !== 'nuevo') return false;
    return fieldValidation.rfc.error || fieldValidation.correo.error || fieldValidation.telefono.error || fieldValidation.persona_recibe.error;
});

const activeReceiverDisplay = computed(() => {
    if (orderForm.receiverType === 'cliente') return selectedCliente.value;
    if (orderForm.receiverType === 'existente') return selectedExistingReceiver.value;
    return null;
});

const fetchPedidoData = async () => {
    loadingInitial.value = true;
    try {
        const [resEst, resPedido] = await Promise.all([
            axios.get('/estados'),
            axios.get(`/pedidos/${id}`)
        ]);
        
        estados.value = resEst.data;
        const p = resPedido.data;
        pedidoFolio.value = p.numero_referencia || p.id;
        
        orderForm.clientId = p.cliente_id;
        orderForm.clientName = p.cliente?.name;
        selectedCliente.value = p.cliente;
        orderForm.prioridad = p.prioridad;
        
        // Identificar modo de origen
        if (p.receptor_id) {
            orderForm.receiverType = 'existente';
            selectedExistingReceiver.value = p.receptor;
            searchReceiverQuery.value = p.receptor?.nombre || '';
        } else {
            orderForm.receiverType = p.receiver_type || 'cliente';
        }

        orderForm.logistics.deliveryOption = p.delivery_option === 'none' ? 'entrega' : p.delivery_option;
        orderForm.logistics.paqueteria_nombre = p.paqueteria_nombre;
        orderForm.logistics.comentarios_logistica = p.commentary_delivery_option;
        orderForm.comments = p.comments;

        // Cargar datos actuales del receptor (snapshot)
        orderForm.receiver = {
            persona_recibe: p.receiver_nombre || '',
            rfc: p.receiver_rfc || '',
            regimen_fiscal: p.receiver_regimen_fiscal || '',
            telefono: p.receiver_telefono || '',
            correo: p.receiver_correo || '',
            cp: p.delivery_cp || '',
            estado: p.delivery_municipio || '', 
            municipio: p.delivery_municipio || '',
            colonia: p.delivery_colonia || '',
            calle_num: p.delivery_calle_num || ''
        };

        orderForm.orderItems = p.detalles.map(d => ({
            id: d.id, bookId: d.libro_id, bookName: d.libro?.titulo,
            tipo_material: d.tipo, sub_type: d.tipo_licencia, quantity: d.cantidad,
            price: d.precio_unitario, totalCost: d.costo_total
        }));
    } catch (e) {
        console.error(e);
        router.push('/pedidos');
    } finally {
        loadingInitial.value = false;
    }
};

const handleCPInput = () => { 
    if (orderForm.receiver.cp?.length === 5) fetchAddressByCP(orderForm.receiver.cp); 
};

const fetchAddressByCP = async (cp) => {
    searchingCP.value = true;
    try {
        const res = await axios.get(`/proxy/dipomex`, { params: { cp } });
        if (res.data && res.data.codigo_postal) {
            orderForm.receiver.estado = res.data.codigo_postal.estado.toUpperCase();
            orderForm.receiver.municipio = res.data.codigo_postal.municipio.toUpperCase();
            colonias.value = res.data.codigo_postal.colonias.map(c => (c.colonia || c).toUpperCase());
        }
    } catch (e) { console.warn(e); } finally { searchingCP.value = false; }
};

const validateUniqueness = async (field) => {
    if (orderForm.receiverType !== 'nuevo') return;
    let val = '';
    let queryParam = field; 
    if (field === 'persona_recibe') { val = orderForm.receiver.persona_recibe?.trim(); queryParam = 'nombre'; }
    else if (field === 'rfc') val = orderForm.receiver.rfc?.trim().toUpperCase();
    else if (field === 'correo') val = orderForm.receiver.correo?.trim().toLowerCase();
    else if (field === 'telefono') val = orderForm.receiver.telefono?.trim();

    if (!val || val.length < 5) { fieldValidation[field].error = false; return; }
    validatingFields[field] = true;
    try {
        const res = await axios.get('/search/receptores/rfc', { params: { [queryParam]: val } });
        fieldValidation[field].error = !!(res.data && res.data.id);
    } catch (e) { fieldValidation[field].error = false; }
    finally { validatingFields[field] = false; }
};

const searchClients = () => {
    if (orderForm.clientName.length < 3) { clientSuggestions.value = []; return; }
    searchingClients.value = true;
    setTimeout(async () => {
        try {
            const res = await axios.get('/search/clientes', { params: { query: orderForm.clientName, include_prospectos: true } });
            clientSuggestions.value = res.data;
        } catch (e) { console.error(e); } finally { searchingClients.value = false; }
    }, 400);
};

const selectClient = (c) => {
    if (!c) return;
    orderForm.clientId = c.id; orderForm.clientName = c.name; selectedCliente.value = c; clientSuggestions.value = [];
    if (orderForm.receiverType === 'cliente') orderForm.receiver.regimen_fiscal = c.regimen_fiscal ? c.regimen_fiscal.split(' ')[0] : '';
};

const searchExistingReceivers = () => {
    selectedExistingReceiver.value = null; 
    if (searchReceiverQuery.value.length < 3) { receiverSuggestions.value = []; return; }
    searchingExisting.value = true;
    setTimeout(async () => {
        try {
            const res = await axios.get('/search/receptores', { params: { query: searchReceiverQuery.value } });
            receiverSuggestions.value = res.data;
        } catch (e) { console.error(e); } finally { searchingExisting.value = false; }
    }, 400);
};

const selectExistingReceiver = (rec) => {
    selectedExistingReceiver.value = rec;
    orderForm.receiver = { ...rec, persona_recibe: rec.nombre }; 
    receiverSuggestions.value = [];
    searchReceiverQuery.value = rec.nombre;
};

const searchBooks = async () => {
    if (currentOrderItem.bookName.length < 3) { currentOrderItem.bookSuggestions = []; return; }
    searchingLibros.value = true;
    try {
        const res = await axios.get('/search/libros', { params: { query: currentOrderItem.bookName } });
        currentOrderItem.bookSuggestions = res.data;
    } catch (e) { console.error(e); } finally { searchingLibros.value = false; }
};

const selectBook = (book) => {
    currentOrderItem.bookId = book.id; currentOrderItem.bookName = book.titulo; currentOrderItem.bookSuggestions = [];
};

const addItemToCart = () => {
    if (!currentOrderItem.bookId) return;
    orderForm.orderItems.push({
        id: Date.now(), bookId: currentOrderItem.bookId, bookName: currentOrderItem.bookName.toUpperCase(), 
        tipo_material: currentOrderItem.tipo_material, sub_type: currentOrderItem.sub_type, 
        quantity: currentOrderItem.quantity, price: currentOrderItem.price || 0, totalCost: (currentOrderItem.price || 0) * currentOrderItem.quantity
    });
    Object.assign(currentOrderItem, { bookId: null, bookName: '', sub_type: '', quantity: 1, price: 0 });
};

const submitUpdate = async () => {
    loading.value = true;
    try {
        const itemsPayload = orderForm.orderItems.map(i => ({ 
            bookId: i.bookId, quantity: i.quantity, price: i.price, sub_type: i.sub_type, tipo_material: i.tipo_material 
        }));

        const finalData = JSON.parse(JSON.stringify(orderForm));
        
        // Mapeo Final de Receptor
        if (orderForm.receiverType === 'cliente' && selectedCliente.value) {
            finalData.receiver = {
                persona_recibe: selectedCliente.value.contacto || selectedCliente.value.name,
                rfc: selectedCliente.value.rfc || '',
                regimen_fiscal: selectedCliente.value.regimen_fiscal ? selectedCliente.value.regimen_fiscal.split(' ')[0] : '', 
                telefono: selectedCliente.value.telefono || '',
                correo: selectedCliente.value.email || '',
                cp: selectedCliente.value.cp || '00000',
                estado: 'CARGADO', municipio: 'CARGADO', colonia: 'CARGADO',
                calle_num: selectedCliente.value.calle_num || selectedCliente.value.direccion || ''
            };
        } else if (orderForm.receiverType === 'existente' && selectedExistingReceiver.value) {
            finalData.receptor_id = selectedExistingReceiver.value.id;
            finalData.receiver = {
                persona_recibe: selectedExistingReceiver.value.nombre,
                rfc: selectedExistingReceiver.value.rfc,
                regimen_fiscal: selectedExistingReceiver.value.receiver_regimen_fiscal || selectedExistingReceiver.value.regimen_fiscal || '',
                telefono: selectedExistingReceiver.value.telefono,
                correo: selectedExistingReceiver.value.correo,
                cp: '00000', estado: 'CARGADO', municipio: 'CARGADO', colonia: 'CARGADO',
                calle_num: selectedExistingReceiver.value.direccion
            };
        }
        // En modo 'nuevo', el objeto ya tiene los campos componentes de la dirección

        await axios.put(`/pedidos/${id}`, { ...finalData, items: itemsPayload });
        successModal.value = true;
    } catch (e) {
        alert(e.response?.data?.message || "Ocurrió un fallo en la actualización.");
    } finally {
        loading.value = false;
    }
};

// Limpieza de campos al entrar en modo manual
watch(() => orderForm.receiverType, (newVal) => {
    if (newVal === 'nuevo') {
        orderForm.receiver = { 
            persona_recibe: '', rfc: '', regimen_fiscal: '', telefono: '', 
            correo: '', cp: '', estado: '', municipio: '', colonia: '', calle_num: '' 
        };
        selectedExistingReceiver.value = null;
        searchReceiverQuery.value = '';
        colonias.value = [];
    }
});

const totalUnits = computed(() => orderForm.orderItems.reduce((s, i) => s + i.quantity, 0));
const orderTotal = computed(() => orderForm.orderItems.reduce((s, i) => s + i.totalCost, 0));
const formatCurrency = (v) => Number(v).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const availableSubTypes = computed(() => ['Solo Físico', 'Pack (Físico + Digital)', 'Licencia Digital']);

onMounted(fetchPedidoData);
</script>

<style scoped>
.label-style { @apply text-xs font-black text-red-600 uppercase tracking-widest mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block; }
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.form-section { background: white; padding: 30px; border-radius: 40px; border: 1px solid #fee2e2; margin-bottom: 30px; }
.section-title { font-weight: 900; color: #000; margin-bottom: 25px; border-bottom: 2px solid #fee2e2; padding-bottom: 12px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.85rem; }
.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #fee2e2; font-weight: 700; color: #334155; background: #fff; transition: 0.2s; font-size: 0.9rem; }
.form-input:focus { border-color: #000; outline: none; }
.autocomplete-list { position: absolute; z-index: 10000; width: 100%; background: white !important; border: 2px solid #fee2e2; border-radius: 16px; max-height: 300px; overflow-y: auto; padding: 8px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); top: 100%; left: 0; margin-top: 6px; }
.shipping-card { @apply border-2 border-red-50 p-5 rounded-3xl flex flex-col items-center gap-3 cursor-pointer transition-all bg-white text-red-300; }
.shipping-card span { @apply text-[10px] font-black uppercase tracking-widest text-center; }
.shipping-card.active { @apply border-black text-black shadow-xl scale-[1.02]; }
.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; transition: 0.2s; text-transform: uppercase; font-size: 0.8rem; }
.modal-overlay-wrapper { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; background: rgba(15,23,42,0.85); backdrop-filter: blur(8px); }
.modal-content-success { background: white; padding: 50px; border-radius: 50px; text-align: center; width: 90%; max-width: 450px; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4); border: 1px solid #fee2e2; }
.success-icon-wrapper { width: 85px; height: 85px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 30px; border: 4px solid white; }
.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.table-cell { padding: 20px 24px; vertical-align: middle; color: #dc2626; font-weight: 700; }
.btn-delete-item { background: none; border: none; color: #fca5a5; font-size: 11px; font-weight: 900; cursor: pointer; }
.badge-material-sale { @apply bg-black text-white px-3 py-1 rounded-full text-[9px] font-black; }
.badge-material-promo { @apply bg-red-600 text-white px-3 py-1 rounded-full text-[9px] font-black; }
.bgcolor { background: #e7f684; border-radius: 12px; padding: 10px; }
</style>