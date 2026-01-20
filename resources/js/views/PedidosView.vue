<template>
    <div class="content-wrapper p-2 md:p-6">
        <div class="module-page max-w-7xl mx-auto">
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-800">Ingreso de Pedidos</h1>
                    <p class="text-xs md:text-sm text-slate-500">Gestión logística avanzada vinculada a la ficha del cliente.</p>
                </div>
            </div>
            
            <form @submit.prevent="submitOrder" class="space-y-6">

                <!-- 1. INFORMACIÓN DEL CLIENTE -->
                <div class="form-section shadow-premium">
                    <div class="section-title">
                        <i class="fas fa-user-circle"></i> 1. Información del Cliente
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group relative">
                            <label class="label-style">Seleccionar Plantel / Distribuidor:</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    class="form-input pl-10 font-bold lbb"  
                                    placeholder="Buscar por nombre..." 
                                    v-model="orderForm.clientName"
                                    @input="searchClients"
                                    required
                                >
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <i v-if="searchingClients" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                            </div>
                            <ul v-if="clientSuggestions.length" class="autocomplete-list shadow-2xl">
                                <li v-for="client in clientSuggestions" :key="client.id" @click="selectClient(client)">
                                    <div class="text-xs font-black text-slate-800">{{ client.name }}</div>
                                    <div class="text-[9px] text-slate-400 uppercase font-black tracking-widest">{{ client.tipo }}</div>
                                </li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label class="label-style">Prioridad de Atención:</label>
                            <select v-model="orderForm.prioridad" class="form-input font-bold">
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. RECEPCIÓN Y LOGÍSTICA -->
                <div class="form-section shadow-premium">
                    <div class="section-title">
                        <i class="fas fa-truck"></i> 2. Recepción y Logística de Envío
                    </div>
                    
                    <div class="space-y-6">
                        <!-- Opciones de envío -->
                        <div class="form-group">
                            <label class="label-style">Método de Envío:</label>
                            <div class="grid  grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                                <label class="shipping-card" :class="{'active': orderForm.logistics.deliveryOption === 'paqueteria'}">
                                    <input type="radio" value="paqueteria" v-model="orderForm.logistics.deliveryOption" class="hidden">
                                    <i class="fas fa-box"></i>
                                    <span>Paquetería</span>
                                </label>
                                <label class="shipping-card" :class="{'active': orderForm.logistics.deliveryOption === 'recoleccion'}">
                                    <input type="radio" value="recoleccion" v-model="orderForm.logistics.deliveryOption" class="hidden">
                                    <i class="fas fa-warehouse"></i>
                                    <span>Recolección Almacén</span>
                                </label>
                                <label class="shipping-card" :class="{'active': orderForm.logistics.deliveryOption === 'entrega'}">
                                    <input type="radio" value="entrega" v-model="orderForm.logistics.deliveryOption" class="hidden">
                                    <i class="fas fa-shuttle-van"></i>
                                    <span>Entrega Directa</span>
                                </label>
                            </div>

                            <div class="mt-6 space-y-4 animate-fade-in">
                                <div v-if="orderForm.logistics.deliveryOption === 'paqueteria'">
                                    <label class="label-mini text-red-700 font-black">Empresa de Paquetería sugerida por el cliente:</label>
                                    <input v-model="orderForm.logistics.paqueteria_nombre" type="text" class="form-input border-red-200 bg-red-50/30" placeholder="DHL, FedEx, etc.">
                                </div>
                                <div v-if="['recoleccion', 'entrega'].includes(orderForm.logistics.deliveryOption)">
                                    <label class="label-mini text-slate-700 font-black">Instrucciones / Referencias Logísticas:</label>
                                    <textarea v-model="orderForm.logistics.comentarios_logistica" class="form-input" rows="2" placeholder="Ej: Pasará el chofer el viernes, oficina de segundo piso..."></textarea>
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100">

                        <!-- Selección de quién recibe -->
                        <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100 flex flex-wrap gap-6 items-center">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Datos Fiscales y Dirección:</label>
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" value="cliente" v-model="orderForm.receiverType" class="w-4 h-4 accent-red-600" :disabled="!orderForm.clientId">
                                    <span class="text-sm font-bold text-slate-700 group-hover:text-red-700">Usar datos de ultimo registro</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" value="nuevo" v-model="orderForm.receiverType" class="w-4 h-4 accent-red-600">
                                    <span class="text-sm font-bold text-slate-700 group-hover:text-red-700">Ingresar datos nuevos</span>
                                </label>
                            </div>
                        </div>

                        <!-- TARJETA: VISTA DE DATOS DEL CLIENTE -->
                        <div v-if="orderForm.receiverType === 'cliente'" class="animate-fade-in">
                            <div v-if="orderForm.clientId" class="receiver-summary-card shadow-sm border border-slate-200 rounded-[2.5rem] p-6 bg-white relative overflow-hidden group">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center relative z-10 gap-4">
                                    <div class="space-y-1">
                                        <h4 class="text-xl font-black text-slate-800 leading-none mb-2">{{ orderForm.receiver.persona_recibe || 'Sin Nombre' }}</h4>
                                        
                                        <div class="flex flex-wrap gap-x-4 gap-y-1">
                                            <p class="text-xs font-bold text-slate-500 uppercase"><i class="fas fa-id-card mr-1 text-slate-300"></i> RFC: <span :class="orderForm.receiver.rfc ? 'text-slate-700' : ' italic font-black'">{{ orderForm.receiver.rfc || 'FALTA RFC EN BD' }}</span></p>
                                            <p class="text-xs font-bold text-slate-500 uppercase"><i class="fas fa-envelope mr-1 text-slate-300"></i> {{ orderForm.receiver.correo || 'SIN CORREO' }}</p>
                                        </div>
                                        
                                        <p class="text-xs text-slate-400 mt-2 italic">
                                            <i class="fas fa-map-marker-alt mr-1"></i> 
                                            {{ orderForm.receiver.calle_num || 'Sin Calle' }}, Col. {{ orderForm.receiver.colonia || 'Sin Colonia' }}, {{ orderForm.receiver.municipio }}, {{ orderForm.receiver.estado }} | C.P. {{ orderForm.receiver.cp }}
                                        </p>
                                    </div>
                                   
                                </div>
                                <i class="fas fa-user-check absolute -right-6 -bottom-6 text-8xl text-slate-50"></i>
                            </div>
                            <div v-else class="text-center py-12 border-2 border-dashed border-slate-100 rounded-[2.5rem]">
                                <p class="text-sm text-slate-400 italic">Busca un cliente para visualizar su información fiscal.</p>
                            </div>
                        </div>

                        <!-- FORMULARIO: DATOS NUEVOS -->
                        <div v-if="orderForm.receiverType === 'nuevo'" class="animate-fade-in space-y-6 bg-white border border-slate-100 p-8 rounded-[2.5rem] shadow-sm">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="form-group">
                                    <label class="label-style">Destinatario *</label>
                                    <input v-model="orderForm.receiver.persona_recibe" type="text" class="form-input font-bold" required>
                                </div>
                                <div class="form-group">
                                    <label class="label-style">RFC *</label>
                                    <input v-model="orderForm.receiver.rfc" type="text" class="form-input font-mono uppercase font-black text-red-700 border-red-100" placeholder="12 o 13 carac." required>
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Régimen Fiscal</label>
                                    <select v-model="orderForm.receiver.regimen_fiscal" class="form-input font-bold text-xs">
                                        <option value="">Seleccionar...</option>
                                        <option value="601">601 - General Morales</option>
                                        <option value="612">612 - PF Act. Empresarial</option>
                                        <option value="626">626 - RESICO</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group"><label class="label-style">Correo Electrónico</label><input v-model="orderForm.receiver.correo" type="email" class="form-input" required></div>
                                <div class="form-group"><label class="label-style">Teléfono</label><input v-model="orderForm.receiver.telefono" type="tel" class="form-input" required></div>
                            </div>

                            <!-- DIRECCIÓN DIPOMEX -->
                            <div class="bg-slate-50 p-6 rounded-[2rem] border border-slate-100 space-y-4">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 border-b border-slate-200 pb-2">Geolocalización por Código Postal</p>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="form-group relative">
                                        <label class="label-mini">C.P. *</label>
                                        <input v-model="orderForm.receiver.cp" type="text" class="form-input font-mono font-black text-red-700" maxlength="5" @input="handleCPInput" placeholder="00000" required>
                                        <i v-if="searchingCP" class="fas fa-spinner fa-spin absolute right-3 top-9 text-red-600"></i>
                                    </div>
                                    <div class="form-group"><label class="label-mini">Estado</label><input v-model="orderForm.receiver.estado" type="text" class="form-input bg-white font-bold" readonly></div>
                                    <div class="form-group md:col-span-2"><label class="label-mini">Municipio / Alcaldía</label><input v-model="orderForm.receiver.municipio" type="text" class="form-input bg-white font-bold" readonly></div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="label-mini">Colonia / Asentamiento</label>
                                        <select v-model="orderForm.receiver.colonia" class="form-input font-bold" required :disabled="!colonias.length">
                                            <option value="" disabled>{{ colonias.length ? 'Seleccione colonia...' : 'Ingrese CP' }}</option>
                                            <option v-for="(col, idx) in colonias" :key="idx" :value="col.colonia || col">
                                                {{ col.colonia || col }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group"><label class="label-mini">Calle y Número</label><input v-model="orderForm.receiver.calle_num" type="text" class="form-input font-bold" placeholder="Ej: Av. Juárez 123" required></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="label-style">Comentarios Generales del Pedido:</label>
                            <textarea v-model="orderForm.comments" class="form-input" rows="2" placeholder="Notas adicionales para el equipo de almacén o facturación..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- 3. SELECCIÓN DE MATERIAL -->
                <div class="form-section !overflow-visible">
                    <div class="section-title">
                        <i class="fas fa-book-open"></i> 3. Selección de Material
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end bg-slate-50 p-4 rounded-3xl border border-slate-200">
                        <div class="md:col-span-2">
                            <label class="label-mini">Tipo</label>
                            <select v-model="currentOrderItem.tipo_material" class="form-input font-black uppercase text-[10px]">
                                <option value="promocion">PROMO</option>
                                <option value="venta">VENTA</option>
                            </select>
                        </div>

                        <div class="md:col-span-3 relative">
                            <label class="label-mini">Buscar Libro</label>
                            <div class="relative">
                                <input type="text" class="form-input pr-10" v-model="currentOrderItem.bookName" placeholder="Título..." @input="searchBooks">
                                <i v-if="searchingLibros" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                            </div>
                            <ul v-if="currentOrderItem.bookSuggestions.length" class="autocomplete-list shadow-2xl">
                                <li v-for="book in currentOrderItem.bookSuggestions" :key="book.id" @click="selectBook(book)">
                                    <div class="flex justify-between items-center overflow-hidden">
                                        <span class="text-xs font-bold text-slate-800 truncate">{{ book.titulo }}</span>
                                        <span class="text-[8px] bg-red-800 text-white px-2 py-0.5 rounded font-black uppercase ml-2">{{ book.type }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="md:col-span-3">
                            <label class="label-mini">Formato / Licencia</label>
                            <select class="form-input font-bold" v-model="currentOrderItem.sub_type" :disabled="!currentOrderItem.bookId">
                                <option value="" disabled>Seleccionar...</option>
                                <option v-for="opt in availableSubTypes" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="label-mini">Cant.</label>
                            <input type="number" min="1" class="form-input font-bold" v-model.number="currentOrderItem.quantity">
                        </div>

                        <div class="md:col-span-2">
                            <label class="label-mini">P. Unitario</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                class="form-input font-black" 
                                v-model.number="currentOrderItem.price" 
                                :disabled="currentOrderItem.tipo_material === 'promocion'"
                            >
                        </div>

                        <div class="md:col-span-1">
                            <button type="button" @click="addItemToCart" class="btn-primary w-full py-3.5 rounded-2xl" :disabled="!isCurrentItemValid || loading">
                                <i class="fas fa-plus"></i>Añadir
                            </button>
                        </div>
                    </div>

                    <!-- TABLA DE CARRITO -->
                    <div class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                        <div class="table-responsive table-shadow-lg mt-6 border rounded-xl overflow-hidden shadow-sm">
                            <table class="min-width-full divide-y divide-gray-200">
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="table-header-dark text-left">Material</th>
                                        <th class="table-header-dark text-center">Tipo</th>
                                        <th class="table-header-dark text-center">Cant.</th>
                                        <th class="table-header-dark text-right">Total</th>
                                        <th class="table-header-dark"></th>
                                    </tr>
                                </thead>

                                <tbody class="bg-white divide-y divide-slate-100">
                                    <tr v-for="(item, index) in orderForm.orderItems" :key="item.id" class="hover:bg-slate-50 transition-colors">
                                        <td class="table-cell">
                                            <div class="font-black text-slate-800 text-sm uppercase leading-tight">
                                                {{ item.bookName }}
                                            </div>
                                            <div class="text-[9px] text-slate-400 uppercase font-black tracking-widest mt-1">
                                                {{ item.sub_type }}
                                            </div>
                                        </td>

                                        <td class="table-cell text-center">
                                            <span :class="item.tipo_material === 'promocion' ? 'badge-material-promo' : 'badge-material-sale'">
                                                {{ item.tipo_material.toUpperCase() }}
                                            </span>
                                        </td>

                                        <td class="table-cell text-center font-black text-red-800 text-base">
                                            {{ item.quantity }}
                                        </td>

                                        <td class="table-cell text-right font-black text-slate-700 text-sm">
                                            {{ formatCurrency(item.totalCost) }}
                                        </td>

                                        <td class="table-cell text-center">
                                            <button type="button" 
                                                    @click="orderForm.orderItems.splice(index, 1)" 
                                                    class="btn-delete-item">
                                                <i class="fas fa-trash-alt mr-1"></i> Cancelar
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="!orderForm.orderItems.length">
                                        <td colspan="5" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-shopping-cart text-slate-100 text-5xl mb-4"></i>
                                                <p class="text-slate-300 font-black uppercase text-[10px] tracking-[0.2em] italic">
                                                    Sin ítems en la orden
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>

                                <tfoot v-if="orderForm.orderItems.length" class="bg-slate-50 border-t-2 border-slate-200">
                                    <tr>
                                        <td colspan="2" class="px-8 py-6 text-right font-black text-[10px] uppercase text-slate-400 tracking-[0.2em]">
                                            Inversión Total del Pedido:
                                        </td>
                                        <td class="px-6 py-6 text-center font-black text-red-700 text-xl border-x border-slate-200/50">
                                            <span class="text-[9px] block text-slate-400 font-bold mb-1">UNIDADES</span>
                                            {{ totalUnits }}
                                        </td>
                                        <td class="px-6 py-6 text-right font-black text-2xl text-red-700">
                                            <span class="text-[9px] block text-slate-400 font-bold mb-1">MONTO TOTAL</span>
                                            {{ formatCurrency(orderTotal) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex justify-end">
                    <button type="submit" class="btn-primary px-16 py-5 shadow-2xl" :disabled="loading || !orderForm.clientId || !orderForm.orderItems.length">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-paper-plane mr-2'"></i> 
                        Confirmar y Postear Pedido
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL 1: ÉXITO -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="showSuccessModal" class="modal-overlay-wrapper">
                    <div class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tighter">¡Pedido Registrado!</h2>
                        <p class="text-sm text-slate-500 mb-2 font-medium">La orden ha sido enviada al área de revisión.</p>
                        <p class="text-xs font-mono font-black text-red-700 bg-red-50 py-2 px-4 rounded-xl inline-block mb-8">FOLIO: {{ generatedOrderId }}</p>
                        <button @click="closeAndRedirect" class="btn-primary-action w-full py-4 bg-slate-900 border-none shadow-none">Ir al Historial</button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- MODAL 2: EDICIÓN DE RECEPTOR (OVERLAY) -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="showEditReceiverModal" class="modal-overlay-wrapper">
                    <div class="bg-white w-full max-w-2xl rounded-[3rem] shadow-2xl overflow-hidden animate-scale-in p-8 md:p-12 border border-slate-100">
                        <div class="flex justify-between items-center mb-8">
                            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Corregir Datos del Cliente</h2>
                            <button @click="showEditReceiverModal = false" class="text-slate-400 hover:text-red-700 transition-colors"><i class="fas fa-times fa-lg"></i></button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div class="form-group">
                                <label class="label-style">Destinatario Oficial *</label>
                                <input v-model="orderForm.receiver.persona_recibe" type="text" class="form-input font-bold">
                            </div>
                            <div class="form-group">
                                <label class="label-style">RFC *</label>
                                <input v-model="orderForm.receiver.rfc" type="text" class="form-input font-mono uppercase font-black text-red-700 border-red-100">
                            </div>
                            <div class="form-group">
                                <label class="label-style">Régimen Fiscal</label>
                                <select v-model="orderForm.receiver.regimen_fiscal" class="form-input font-bold text-xs">
                                    <option value="601">General Morales</option>
                                    <option value="612">PF Act. Empresarial</option>
                                    <option value="626">RESICO</option>
                                </select>
                            </div>
                            <div class="form-group"><label class="label-style">Teléfono</label><input v-model="orderForm.receiver.telefono" type="tel" class="form-input font-bold"></div>
                        </div>

                        <!-- CP DIPOMEX EN MODAL -->
                        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 space-y-4 mb-8">
                            <div class="grid grid-cols-4 gap-4">
                                <div class="form-group relative">
                                    <label class="label-mini">C.P. *</label>
                                    <input v-model="orderForm.receiver.cp" type="text" class="form-input font-mono font-black text-red-700" maxlength="5" @input="handleCPInput">
                                    <i v-if="searchingCP" class="fas fa-spinner fa-spin absolute right-3 top-9 text-red-600"></i>
                                </div>
                                <div class="col-span-3">
                                    <label class="label-mini">Estado / Municipio</label>
                                    <p class="text-xs font-black text-slate-600 mt-2 uppercase">
                                        {{ orderForm.receiver.estado || '---' }} | {{ orderForm.receiver.municipio || '---' }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="label-mini">Colonia</label>
                                    <select v-model="orderForm.receiver.colonia" class="form-input font-bold" :disabled="!colonias.length">
                                        <option value="" disabled>{{ colonias.length ? 'Seleccione colonia...' : 'Ingrese CP' }}</option>
                                        <option v-for="(col, idx) in colonias" :key="idx" :value="col.colonia || col">
                                            {{ col.colonia || col }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group"><label class="label-mini">Calle y Número</label><input v-model="orderForm.receiver.calle_num" type="text" class="form-input font-bold"></div>
                            </div>
                        </div>

                        <button @click="showEditReceiverModal = false" class="btn-primary-action w-full py-4 text-sm tracking-widest bg-slate-900 border-none shadow-none">Actualizar Datos Locales</button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const loading = ref(false);
const searchingLibros = ref(false);
const searchingClients = ref(false);
const searchingCP = ref(false);
const showSuccessModal = ref(false);
const showEditReceiverModal = ref(false);
const generatedOrderId = ref('');
const clientSuggestions = ref([]);
const estados = ref([]);
const colonias = ref([]);

const orderForm = reactive({
    prioridad: 'media',
    clientId: null,
    clientName: '',
    receiverType: 'cliente',
    receiver: { 
        persona_recibe: '', rfc: '', regimen_fiscal: '', telefono: '', correo: '', cp: '', estado: '', municipio: '', colonia: '', calle_num: '' 
    },
    logistics: { deliveryOption: 'paqueteria', paqueteria_nombre: '', comentarios_logistica: '' },
    comments: '',
    orderItems: [], 
});

const currentOrderItem = reactive({
    bookId: null,
    bookName: '',
    tipo_material: 'venta', // Venta por defecto
    category: '', 
    sub_type: '', 
    quantity: 1,
    price: 0,
    bookSuggestions: [],
});

/**
 * REGLA DE NEGOCIO: Limpiar búsqueda al cambiar entre Promo y Venta
 */
watch(() => currentOrderItem.tipo_material, () => {
    currentOrderItem.bookName = '';
    currentOrderItem.bookId = null;
    currentOrderItem.bookSuggestions = [];
    currentOrderItem.sub_type = '';
});

const availableSubTypes = computed(() => {
    if (!currentOrderItem.bookId) return [];
    const isDigital = currentOrderItem.category === 'digital';
    const isPromo = currentOrderItem.tipo_material === 'promocion';
    if (isPromo) {
        return isDigital ? ['Licencia docente', 'Demo'] : ['Físico (Promoción)'];
    } else {
        return isDigital ? ['Libro Digital'] : ['Pack (Digital + Físico)', 'Solo Físico'];
    }
});

/**
 * BÚSQUEDA DE DIRECCIÓN POR CP
 */
const handleCPInput = () => {
    const cp = orderForm.receiver.cp;
    if (cp && cp.length === 5) fetchAddressByCP(cp);
    else resetAddress();
};

const fetchAddressByCP = async (cp, preserveColonia = false) => {
    searchingCP.value = true;
    if (!preserveColonia) orderForm.receiver.colonia = '';
    colonias.value = [];
    try {
        const res = await axios.get(`/proxy/dipomex`, { params: { cp: cp } });
        if (res.data && !res.data.error && res.data.codigo_postal) {
            const data = res.data.codigo_postal;
            orderForm.receiver.estado = data.estado || '';
            orderForm.receiver.municipio = data.municipio || '';
            if (data.colonias && Array.isArray(data.colonias)) {
                colonias.value = data.colonias.map(c => c.colonia || c);
                if (preserveColonia && orderForm.receiver.colonia && !colonias.value.includes(orderForm.receiver.colonia)) colonias.value.unshift(orderForm.receiver.colonia);
                if (!preserveColonia && colonias.value.length === 1) orderForm.receiver.colonia = colonias.value[0];
            }
        }
    } catch (e) { console.error(e); } finally { searchingCP.value = false; }
};

const resetAddress = () => { orderForm.receiver.estado = ''; orderForm.receiver.municipio = ''; orderForm.receiver.colonia = ''; colonias.value = []; };

/**
 * LÓGICA DE CLIENTES
 */
let clientTimeout = null;
const searchClients = () => {
    clearTimeout(clientTimeout);
    if (orderForm.clientName.length < 3) { clientSuggestions.value = []; return; }
    searchingClients.value = true;
    clientTimeout = setTimeout(async () => {
        try {
            const res = await axios.get('/search/clientes', { params: { query: orderForm.clientName, include_prospectos: true } });
            clientSuggestions.value = res.data;
        } catch (e) { console.error(e); } finally { searchingClients.value = false; }
    }, 400);
};

const selectClient = (c) => {
    orderForm.clientId = c.id;
    orderForm.clientName = c.name;
    clientSuggestions.value = [];
    orderForm.receiver.persona_recibe = c.contacto || c.name || '';
    orderForm.receiver.rfc = c.rfc || '';
    orderForm.receiver.regimen_fiscal = c.regimen_fiscal || '';
    orderForm.receiver.telefono = c.telefono || '';
    orderForm.receiver.correo = c.email || '';
    orderForm.receiver.calle_num = c.calle_num || c.direccion || '';
    orderForm.receiver.colonia = c.colonia || ''; 
    if (c.cp) { orderForm.receiver.cp = c.cp; fetchAddressByCP(c.cp, true); } else resetAddress();
};

/**
 * LÓGICA DE LIBROS - FILTRADO POR TIPO
 */
const searchBooks = async () => {
    if (currentOrderItem.bookName.length < 3) { currentOrderItem.bookSuggestions = []; return; }
    searchingLibros.value = true;
    try {
        const res = await axios.get('/search/libros', { params: { query: currentOrderItem.bookName } });
        
        // REGLA DE NEGOCIO: Filtrar según el selector tipo_material
        if (currentOrderItem.tipo_material === 'promocion') {
            // Solo promoción
            currentOrderItem.bookSuggestions = res.data.filter(b => b.type === 'promocion');
        } else {
            // Solo venta/digital, NADA de promoción
            currentOrderItem.bookSuggestions = res.data.filter(b => b.type !== 'promocion');
        }
    } catch (e) { console.error(e); } finally { searchingLibros.value = false; }
};

const selectBook = (book) => {
    currentOrderItem.bookId = book.id;
    currentOrderItem.bookName = book.titulo;
    currentOrderItem.category = book.type; 
    currentOrderItem.bookSuggestions = [];
    setTimeout(() => { if (availableSubTypes.value.length === 1) currentOrderItem.sub_type = availableSubTypes.value[0]; }, 50);
};

const isCurrentItemValid = computed(() => currentOrderItem.bookId && currentOrderItem.sub_type && currentOrderItem.quantity >= 1);

const addItemToCart = () => {
    if (!isCurrentItemValid.value) return;
    orderForm.orderItems.push({
        id: Date.now(),
        bookId: currentOrderItem.bookId,
        bookName: currentOrderItem.bookName,
        tipo_material: currentOrderItem.tipo_material,
        sub_type: currentOrderItem.sub_type,
        quantity: currentOrderItem.quantity,
        price: currentOrderItem.price || 0,
        totalCost: (currentOrderItem.price || 0) * currentOrderItem.quantity
    });
    const lastType = currentOrderItem.tipo_material;
    Object.assign(currentOrderItem, { bookId: null, bookName: '', sub_type: '', category: '', quantity: 1, price: 0, bookSuggestions: [], tipo_material: lastType });
};

const orderTotal = computed(() => orderForm.orderItems.reduce((s, i) => s + i.totalCost, 0));
const totalUnits = computed(() => orderForm.orderItems.reduce((s, i) => s + i.quantity, 0));
const formatCurrency = (v) => v.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

const submitOrder = async () => {
    if (!orderForm.receiver.rfc) { alert("El RFC es obligatorio para procesar el pedido."); return; }
    loading.value = true;
    try {
        const res = await axios.post('/pedidos', { ...orderForm, items: orderForm.orderItems });
        generatedOrderId.value = res.data.order_id;
        showSuccessModal.value = true;
    } catch (e) { alert(e.response?.data?.message || 'Error al procesar pedido.'); } finally { loading.value = false; }
};

const closeAndRedirect = () => { showSuccessModal.value = false; router.push('/pedidos'); };

onMounted(async () => {
    try {
        const res = await axios.get('/estados');
        estados.value = res.data;
    } catch (e) { console.error(e); }
});
</script>

<style scoped>
.label-style { @apply text-xs font-black text-slate-500 uppercase tracking-tighter mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block; }

.shadow-premium { box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); }
.form-section { background: white; padding: 25px; border-radius: 24px; border: 1px solid #f1f5f9; margin-bottom: 25px; }
.section-title { font-weight: 900; color: #000000; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 10px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }

.form-input { width: 100%; padding: 12px 16px; border-radius: 14px; border: 2px solid #f1f5f9; font-weight: 700; color: #334155; background: #fafbfc; transition: all 0.2s; }
.form-input:focus { border-color: #000000; background: white; outline: none; }

.autocomplete-list { position: absolute; z-index: 1000; width: 100%; background: white; border: 1px solid #e2e8f0; border-radius: 12px; max-height: 250px; overflow-y: auto; list-style: none; padding: 5px; margin-top: 5px; }
.autocomplete-list li { padding: 10px 15px; cursor: pointer; border-radius: 8px; border-bottom: 1px solid #f8fafc; transition: background 0.2s; }
.autocomplete-list li:hover { background: #fdf2f2; }

.shipping-card { @apply border-2 border-slate-100 p-4 rounded-2xl flex flex-col items-center gap-2 cursor-pointer transition-all hover:border-red-100 text-slate-400 bg-white; }
.shipping-card i { @apply text-2xl; }
.shipping-card span { @apply text-[10px] font-black uppercase tracking-widest text-center leading-tight; }
.shipping-card.active { @apply border-red-600 bg-red-50 text-red-700 shadow-lg shadow-red-100; }

.btn-primary-action { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 15px 35px rgba(169, 51, 57, 0.25); transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.05em; }
.btn-primary-action:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 20px 40px rgba(169, 51, 57, 0.35); }

/* OVERLAY MODAL */
.modal-overlay-wrapper { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 1.5rem; background-color: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); overflow: hidden; }
.modal-content-success { background: white; padding: 45px; border-radius: 40px; text-align: center; width: 90%; max-width: 400px; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4); border: 1px solid #f1f5f9; }
.success-icon-wrapper { width: 75px; height: 75px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 25px; border: 4px solid white; }

.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.modal-pop-enter-active, .modal-pop-leave-active { transition: all 0.3s ease; }
.modal-pop-enter-from, .modal-pop-leave-to { opacity: 0; }

select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; appearance: none; }
.table-responsive {
    width: 100%;
    overflow-x: auto;
    background: white;
}

table {
    width: 100%;
    border-collapse: collapse;
}
.lbb{
    color: black;
}
/* Cabeceras Oscuras */
.table-header-dark {
    padding: 18px 24px;
    font-size: 0.65rem;
    font-weight: 900;
    color: #94a3b8; /* Slate-400 */
    text-transform: uppercase;
    letter-spacing: 0.2em;
}

.table-cell {
    padding: 16px 24px;
    vertical-align: middle;
}

/* Badges de Tipo de Material */
.badge-material-promo {
    background-color: #f5f3ff; /* Purple-50 */
    color: #7e22ce; /* Purple-700 */
    border: 1px solid #ddd6fe;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 9px;
    font-weight: 900;
}

.badge-material-sale {
    background-color: #eff6ff; /* Blue-50 */
    color: #1d4ed8; /* Blue-700 */
    border: 1px solid #dbeafe;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 9px;
    font-weight: 900;
}

/* Botón de Cancelar */
.btn-delete-item {
    background: none;
    border: none;
    color: #cbd5e1; /* Slate-300 */
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
}

.btn-delete-item:hover {
    color: #ef4444; /* Red-500 */
    transform: translateX(2px);
}

/* Footer: Tipografía de Impacto */
tfoot {
    border-top: 3px solid #f1f5f9;
}

.table-shadow-lg {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
}

/* Utilitarios */
.text-right { text-align: right; }
.text-center { text-align: center; }
.text-left { text-align: left; }
</style>