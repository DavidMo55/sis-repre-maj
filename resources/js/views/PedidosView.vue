<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Ingreso de Pedidos</h1>
                    <p>Gestiona la entrega de material promocional o venta según las reglas de negocio.</p>
                </div>
            </div>
            
            <form @submit.prevent="submitOrder" class="mt-6">

                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user-circle"></i> 1. Información del Cliente
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group relative">
                            <label>Seleccionar Cliente / Distribuidor:</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    class="form-input pl-10" 
                                    placeholder="Buscar por nombre de plantel..." 
                                    v-model="orderForm.clientName"
                                    @input="searchClients"
                                    required
                                >
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <i v-if="searchingClients" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                            </div>
                            <ul v-if="clientSuggestions.length" class="autocomplete-list shadow-xl">
                                <li v-for="client in clientSuggestions" :key="client.id" @click="selectClient(client)">
                                    <div class="text-xs font-bold text-gray-800 text-break">{{ client.name }}</div>
                                    <div class="text-[10px] text-gray-500 uppercase font-black">{{ client.tipo }}</div>
                                </li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label>Prioridad del Pedido:</label>
                            <select v-model="orderForm.prioridad" class="form-input font-bold">
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-truck"></i> 2. Recepción y Logística
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <label class="block font-bold text-gray-700">¿Quién recibe?</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="cliente" v-model="orderForm.receiverType"> Datos del Cliente
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="nuevo" v-model="orderForm.receiverType"> Datos Nuevos
                                </label>
                            </div>
                            <div v-if="orderForm.receiverType === 'nuevo'" class="animate-fade-in bg-gray-50 p-4 rounded-xl border space-y-3">
                                <input v-model="orderForm.receiver.nombre" type="text" class="form-input" placeholder="Nombre completo" required>
                                <div class="grid grid-cols-2 gap-3">
                                    <input v-model="orderForm.receiver.telefono" type="tel" class="form-input" placeholder="Teléfono" required>
                                    <input v-model="orderForm.receiver.correo" type="email" class="form-input" placeholder="Email" required>
                                </div>
                                <input v-model="orderForm.receiver.direccion" type="text" class="form-input" placeholder="Dirección completa de entrega">
                            </div>
                        </div>
                        <div class="space-y-4">
                            <label class="block font-bold text-gray-700">Opciones de Envío:</label>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" value="none" v-model="orderForm.logistics.deliveryOption"> Estándar</label>
                                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" value="recoleccion" v-model="orderForm.logistics.deliveryOption"> Almacén</label>
                                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" value="paqueteria" v-model="orderForm.logistics.deliveryOption"> Paquetería</label>
                            </div>
                            <input v-if="orderForm.logistics.deliveryOption === 'paqueteria'" v-model="orderForm.logistics.paqueteria_nombre" type="text" class="form-input border-red-200 bg-red-50" placeholder="Nombre de la paquetería..." required>
                            <textarea v-model="orderForm.comments" class="form-input" rows="2" placeholder="Notas adicionales para almacén..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section" style="overflow: visible !important;">
                    <div class="section-title">
                        <i class="fas fa-book-open"></i> 3. Selección de Material
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end bg-gray-50 p-4 rounded-xl border border-gray-200" style="overflow: visible !important;">
                        <div class="md:col-span-2">
                            <label class="text-[10px] uppercase font-bold text-gray-500">Tipo</label>
                            <select v-model="currentOrderItem.tipo_material" class="form-input font-bold" @change="resetCurrentBook">
                                <option value="promocion">PROMO</option>
                                <option value="venta">VENTA</option>
                            </select>
                        </div>

                        <div class="md:col-span-3 relative">
                            <label class="text-[10px] uppercase font-bold text-gray-500">Libro</label>
                            <div class="relative">
                                <input type="text" class="form-input pr-10" v-model="currentOrderItem.bookName" placeholder="Buscar título..." @input="searchBooks">
                                <i v-if="searchingLibros" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                            </div>
                            <ul v-if="currentOrderItem.bookSuggestions.length" class="autocomplete-list shadow-xl">
                                <li v-for="book in currentOrderItem.bookSuggestions" :key="book.id" @click="selectBook(book)">
                                    <div class="flex justify-between items-center overflow-hidden">
                                        <span class="text-xs font-bold text-gray-800 text-break shrink">{{ book.titulo }}</span>
                                        <span class="text-[8px] bg-red-800 text-white px-2 py-0.5 rounded font-black uppercase shrink-0 ml-2">{{ book.type }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="md:col-span-3">
                            <label class="text-[10px] uppercase font-bold text-gray-500">Formato / Licencia</label>
                            <select class="form-input font-bold" v-model="currentOrderItem.sub_type" :disabled="!currentOrderItem.bookId">
                                <option value="" disabled>Seleccionar...</option>
                                <option v-for="opt in availableSubTypes" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="text-[10px] uppercase font-bold text-gray-500">Cant.</label>
                            <input type="number" min="1" class="form-input" v-model.number="currentOrderItem.quantity">
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-[10px] uppercase font-bold text-gray-500">P. Unitario</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                class="form-input" 
                                v-model.number="currentOrderItem.price" 
                                :disabled="currentOrderItem.tipo_material === 'promocion'"
                            >
                        </div>

                        <div class="md:col-span-1">
                            <button type="button" @click="addItemToCart" class="btn-primary w-full py-3" :disabled="!isCurrentItemValid || loading">
                                <i class="fas fa-plus"></i>Agregar
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 overflow-hidden border rounded-xl shadow-sm">
                        <table class="min-width-full divide-y divide-gray-200">
                            <thead class="bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-white uppercase">Material</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-white uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-bold text-white uppercase">Cant.</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-bold text-white uppercase">Total</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="(item, index) in orderForm.orderItems" :key="item.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-800 text-xs text-break">{{ item.bookName }}</div>
                                        <div class="text-[9px] text-gray-400 uppercase">{{ item.sub_type }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-[9px] font-black uppercase">
                                        <span :class="item.tipo_material === 'promocion' ? 'text-purple-600' : 'text-blue-600'">
                                            {{ item.tipo_material }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-red-800">{{ item.quantity }}</td>
                                    <td class="px-4 py-3 text-right font-black">{{ formatCurrency(item.totalCost) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" @click="orderForm.orderItems.splice(index, 1)" class="text-red-500 hover:text-red-700 text-xs">
                                            <i class="fas fa-trash"></i>Borrar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="orderForm.orderItems.length" class="bg-gray-50 border-t-2 font-black">
                                <tr>
                                    <td colspan="2" class="px-4 py-4 text-right text-[10px]">TOTAL ORDEN:</td>
                                    <td class="px-4 py-4 text-center text-red-700">{{ totalUnits }}</td>
                                    <td class="px-4 py-4 text-right text-lg text-red-700">{{ formatCurrency(orderTotal) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="btn-primary px-16 py-4 shadow-xl text-lg uppercase font-black" :disabled="loading || !orderForm.clientId || !orderForm.orderItems.length">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-paper-plane mr-2'"></i> 
                        Confirmar Pedido
                    </button>
                </div>
            </form>
        </div>

        <Transition name="fade">
            <div v-if="showSuccessModal" class="modal-overlay" @click.self="closeAndRedirect">
                <div class="modal-content-success">
                    <div class="success-icon-wrapper"><i class="fas fa-check"></i></div>
                    <h2 class="text-xl font-black text-gray-800 mb-2">¡Pedido Registrado!</h2>
                    <p class="text-xs text-gray-500 mb-6 font-mono font-bold text-red-700">FOLIO: {{ generatedOrderId }}</p>
                    <button @click="closeAndRedirect" class="btn-primary w-full py-3">Volver al Listado</button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const loading = ref(false);
const searchingLibros = ref(false);
const searchingClients = ref(false);
const showSuccessModal = ref(false);
const generatedOrderId = ref('');
const clientSuggestions = ref([]);

const orderForm = reactive({
    prioridad: 'media',
    clientId: null,
    clientName: '',
    receiverType: 'cliente',
    receiver: { nombre: '', telefono: '', correo: '', direccion: '' },
    logistics: { deliveryOption: 'none', paqueteria_nombre: '' },
    comments: '', 
    orderItems: [], 
});

const currentOrderItem = reactive({
    bookId: null,
    bookName: '',
    tipo_material: 'venta',
    category: '', 
    sub_type: '', 
    quantity: 1,
    price: 0,
    bookSuggestions: [],
});

// REGLAS DE NEGOCIO (IMAGEN)
const availableSubTypes = computed(() => {
    if (!currentOrderItem.bookId) return [];
    const isDigital = currentOrderItem.category === 'digital';
    const isPromo = currentOrderItem.tipo_material === 'promocion';

    if (isPromo) {
        if (isDigital) return ['Licencia docente', 'Demo'];
        return ['Físico (Promoción)'];
    } else {
        if (isDigital) return ['Libro Digital'];
        return ['Pack (Digital + Físico)', 'Solo Físico'];
    }
});

watch(() => currentOrderItem.tipo_material, (nv) => {
    if (nv === 'promocion') currentOrderItem.price = 0;
    resetCurrentBook();
});

const resetCurrentBook = () => {
    currentOrderItem.bookId = null;
    currentOrderItem.bookName = '';
    currentOrderItem.sub_type = '';
    currentOrderItem.category = '';
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
        price: currentOrderItem.price,
        totalCost: currentOrderItem.price * currentOrderItem.quantity
    });
    const lastType = currentOrderItem.tipo_material;
    Object.assign(currentOrderItem, { bookId: null, bookName: '', sub_type: '', category: '', quantity: 1, price: 0, bookSuggestions: [], tipo_material: lastType });
};

const orderTotal = computed(() => orderForm.orderItems.reduce((s, i) => s + i.totalCost, 0));
const totalUnits = computed(() => orderForm.orderItems.reduce((s, i) => s + i.quantity, 0));
const formatCurrency = (v) => v.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

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
};

const searchBooks = async () => {
    if (currentOrderItem.bookName.length < 3) { currentOrderItem.bookSuggestions = []; return; }
    searchingLibros.value = true;
    try {
        const res = await axios.get('/search/libros', { params: { query: currentOrderItem.bookName } });
        currentOrderItem.bookSuggestions = res.data;
    } catch (e) { console.error(e); } finally { searchingLibros.value = false; }
};

const submitOrder = async () => {
    loading.value = true;
    const payload = { ...orderForm, items: orderForm.orderItems };
    try {
        const res = await axios.post('/pedidos', payload);
        generatedOrderId.value = res.data.order_id;
        showSuccessModal.value = true;
    } catch (e) { alert(e.response?.data?.message || 'Error al enviar pedido.'); } finally { loading.value = false; }
};

const closeAndRedirect = () => { showSuccessModal.value = false; router.push('/pedidos'); };
</script>

<style scoped>
.text-break { overflow-wrap: break-word; word-break: break-all; }
.line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.form-section { background: white; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.section-title { font-weight: 900; color: #a93339; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 10px; display: flex; align-items: center; gap: 10px; }
.autocomplete-list { position: absolute; z-index: 1000; width: 100%; background: white; border: 1px solid #e2e8f0; border-radius: 12px; max-height: 250px; overflow-y: auto; list-style: none; padding: 5px; margin-top: 5px; }
.autocomplete-list li { padding: 10px; cursor: pointer; border-radius: 8px; border-bottom: 1px solid #f8fafc; }
.autocomplete-list li:hover { background: #fdf2f2; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 2000; }
.modal-content-success { background: white; padding: 40px; border-radius: 24px; text-align: center; width: 90%; max-width: 380px; }
.success-icon-wrapper { width: 60px; height: 60px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 20px; }
</style>