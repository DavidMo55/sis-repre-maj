<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Ingreso de Pedidos</h1>
                    <p>Gestiona la venta o entrega de material promocional siguiendo las reglas de negocio.</p>
                </div>
            </div>
            
            <form @submit.prevent="submitOrder" class="mt-6">

                <!-- SECCIÓN 1: CONFIGURACIÓN INICIAL -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-cog"></i> 1. Configuración del Pedido
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="form-group">
                            <label>Tipo de Pedido:</label>
                            <select v-model="orderForm.tipo_pedido" class="form-input font-bold" @change="handleTypeChange">
                                <option value="normal">Pedido Normal (Venta)</option>
                                <option value="promocion">Pedido de Promoción</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prioridad:</label>
                            <select v-model="orderForm.prioridad" class="form-input">
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                        <div class="form-group relative">
                            <label for="cliente">Seleccionar Cliente:</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    id="cliente" 
                                    class="form-input" 
                                    :placeholder="orderForm.tipo_pedido === 'promocion' ? 'Buscar Prospecto o Cliente...' : 'Buscar Cliente/Dist...'" 
                                    v-model="orderForm.clientName"
                                    @input="searchClients"
                                    required
                                >
                                <i v-if="searchingClients" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <ul v-if="clientSuggestions.length" class="autocomplete-list">
                                <li v-for="client in clientSuggestions" :key="client.id" @click="selectClient(client)">
                                    {{ client.name }} ({{ client.tipo }})
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p v-if="clientSelected && !isValidClientType" class="error-message-small mt-2 font-bold">
                        El plantel es {{ orderForm.clientStatus }}. Los pedidos normales solo permiten CLIENTE/DISTRIBUIDOR.
                    </p>
                </div>

                <!-- SECCIÓN 2: DATOS DE RECEPCIÓN Y LOGÍSTICA -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-truck"></i> 2. Recepción y Logística
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div>
                            <label class="block mb-2 font-bold text-gray-700">Persona que recibirá:</label>
                            <div class="flex gap-4 mb-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="cliente" v-model="orderForm.receiverType"> Datos del Cliente
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="nuevo" v-model="orderForm.receiverType"> Ingresar Datos Nuevos
                                </label>
                            </div>

                            <div v-if="orderForm.receiverType === 'nuevo'" class="animate-fade-in bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-3 shadow-inner">
                                <div class="grid grid-cols-2 gap-3">
                                    <input v-model="orderForm.receiver.nombre" type="text" class="form-input" placeholder="Nombre" required>
                                    <input v-model="orderForm.receiver.rfc" type="text" class="form-input" placeholder="RFC (Opcional)">
                                    <input v-model="orderForm.receiver.telefono" type="tel" class="form-input" placeholder="Teléfono" required>
                                    <input v-model="orderForm.receiver.correo" type="email" class="form-input" placeholder="Email" required>
                                </div>
                                <input v-model="orderForm.receiver.direccion" type="text" class="form-input" placeholder="Dirección de entrega completa">
                            </div>
                        </div>

                        <div>
                            <label class="block mb-2 font-bold text-gray-700">Opciones de Envío:</label>
                            <div class="flex flex-wrap gap-4 mb-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="none" v-model="orderForm.logistics.deliveryOption"> Estándar
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="recoleccion" v-model="orderForm.logistics.deliveryOption"> Recolección Almacén
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="paqueteria" v-model="orderForm.logistics.deliveryOption"> Paquetería Sugerida
                                </label>
                            </div>

                            <!-- CAMPO CONDICIONAL PAQUETERÍA -->
                            <Transition name="fade">
                                <div v-if="orderForm.logistics.deliveryOption === 'paqueteria'" class="animate-fade-in mb-4">
                                    <label class="text-sm font-bold text-red-700 uppercase tracking-tighter">Indique el Nombre de la Paquetería:</label>
                                    <input v-model="orderForm.logistics.paqueteria_nombre" type="text" class="form-input border-red-200 bg-red-50" placeholder="Ej: DHL, FedEx, Estafeta..." required>
                                </div>
                            </Transition>

                            <textarea v-model="orderForm.comments" class="form-input" rows="2" placeholder="Comentarios generales sobre la entrega..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: SELECCIÓN DE MATERIAL (DINÁMICA) -->
                <div class="form-section" style="overflow: visible !important;">
                    <div class="section-title">
                        <i class="fas fa-book-open"></i> 3. Selección de Material
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end bg-gray-50 p-4 rounded-xl border border-gray-200" style="overflow: visible !important;">
                        <div class="md:col-span-5 relative">
                            <label class="text-xs uppercase font-bold text-gray-500">Buscar Libro</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    class="form-input pr-10" 
                                    v-model="currentOrderItem.bookName" 
                                    placeholder="Escriba el título del material..." 
                                    @input="searchBooks"
                                >
                                <i v-if="searchingLibros" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <ul v-if="currentOrderItem.bookSuggestions.length" class="autocomplete-list">
                                <li v-for="book in currentOrderItem.bookSuggestions" :key="book.id" @click="selectBook(book)">
                                    <div class="flex justify-between items-center">
                                        <span>{{ book.titulo }}</span>
                                        <span class="text-[9px] bg-red-100 text-red-700 px-2 py-0.5 rounded font-black uppercase">{{ book.type }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="md:col-span-3">
                            <label class="text-xs uppercase font-bold text-gray-500">Formato / Licencia</label>
                            <select class="form-input font-bold" v-model="currentOrderItem.type" :disabled="!currentOrderItem.bookId">
                                <option value="" disabled>Seleccionar...</option>
                                <option v-for="opt in availableSubTypes" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="text-xs uppercase font-bold text-gray-500">Cant.</label>
                            <input type="number" min="1" class="form-input" v-model.number="currentOrderItem.quantity">
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs uppercase font-bold text-gray-500">P. Unitario</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                class="form-input" 
                                v-model.number="currentOrderItem.price" 
                                :disabled="orderForm.tipo_pedido === 'promocion'"
                            >
                        </div>

                        <div class="md:col-span-1">
                            <button 
                                type="button" 
                                @click="addItemToCart" 
                                class="btn-primary w-full py-3"
                                :disabled="!isCurrentItemValid || loading"
                            >
                                <i class="fas fa-plus"></i>Agregar
                            </button>
                        </div>
                    </div>
                    
                    <p v-if="currentItemError" class="error-message-small mt-2 font-bold">{{ currentItemError }}</p>

                    <!-- TABLA RESUMEN -->
                    <div class="mt-6">
                        <div v-if="orderForm.orderItems.length === 0" class="p-10 text-center border-2 border-dashed rounded-2xl text-gray-400 italic">
                            <i class="fas fa-shopping-basket text-3xl mb-2"></i><br>
                            El carrito está vacío. Agregue material para continuar.
                        </div>

                        <div v-else class="table-responsive border rounded-xl overflow-hidden shadow-md">
                            <table class="min-width-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Material</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Formato</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Cantidad</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Unitario</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <tr v-for="(item, index) in orderForm.orderItems" :key="item.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 font-bold text-gray-800">{{ item.bookName }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-600 font-medium uppercase tracking-tight">{{ item.type }}</td>
                                        <td class="px-4 py-3 text-center font-bold text-red-800">{{ item.quantity }}</td>
                                        <td class="px-4 py-3 text-right">{{ formatCurrency(item.price) }}</td>
                                        <td class="px-4 py-3 text-right font-black text-red-700">{{ formatCurrency(item.totalCost) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <button type="button" @click="removeItemFromCart(index)" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-trash-alt"></i>Quitar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50 font-black border-t-2">
                                    <tr>
                                        <td colspan="2" class="px-4 py-4 text-right uppercase text-xs">Totales de la Orden:</td>
                                        <td class="px-4 py-4 text-center text-red-700 text-lg">{{ totalUnits }}</td>
                                        <td></td>
                                        <td class="px-4 py-4 text-right text-2xl text-red-700">{{ formatCurrency(orderTotal) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button 
                        type="submit" 
                        class="btn-primary px-20 py-5 shadow-2xl text-xl uppercase tracking-widest" 
                        :disabled="loading || !orderForm.clientId || orderForm.orderItems.length === 0 || !isValidClientType"
                    >
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i> 
                        {{ loading ? 'Procesando...' : 'Confirmar Pedido' }}
                    </button>
                </div>
            </form>
            
            <p v-if="error" class="error-message mt-4 p-4 bg-red-50 rounded-xl border border-red-200">{{ error }}</p>
        </div>

        <!-- MODAL DE ÉXITO -->
        <Transition name="modal-fade">
            <div v-if="showSuccessModal" class="modal-overlay" @click.self="closeAndRedirect">
                <div class="modal-content-success">
                    <div class="success-icon-wrapper">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 class="text-2xl font-black text-gray-800 mb-2">¡Pedido Generado!</h2>
                    <p class="text-gray-500 mb-6">
                        El pedido se ha registrado correctamente con el folio:<br>
                        <span class="font-mono text-xl font-bold text-red-700">{{ generatedOrderId }}</span>
                    </p>
                    <button @click="closeAndRedirect" class="btn-primary w-full py-4 text-lg">
                        Entendido, ir al listado
                    </button>
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
const error = ref(null);
const showSuccessModal = ref(false);
const generatedOrderId = ref('');

const clientSuggestions = ref([]);
const clientSelected = ref(false);
const currentItemError = ref(null);

const orderForm = reactive({
    tipo_pedido: 'normal',
    prioridad: 'media',
    clientId: null,
    clientName: '',
    clientStatus: '', 
    receiverType: 'cliente',
    receiver: { nombre: '', rfc: '', telefono: '', correo: '', direccion: '' },
    logistics: { deliveryOption: 'none', paqueteria_nombre: '' },
    comments: '', 
    orderItems: [], 
});

const currentOrderItem = reactive({
    bookId: null,
    bookName: '',
    type: '', 
    category: '', 
    quantity: 1,
    price: 0,
    bookSuggestions: [],
});

const availableSubTypes = computed(() => {
    if (!currentOrderItem.bookId) return [];
    const isDigital = currentOrderItem.category === 'digital';
    if (orderForm.tipo_pedido === 'promocion') {
        if (isDigital) return ['Licencia para docente', 'Demo'];
        return ['Físico (Promoción)'];
    } else {
        if (isDigital) return ['Avalain', 'BlinkLearning'];
        return ['Pack (Físico + Digital)', 'Solo físico'];
    }
});

watch(availableSubTypes, (newVal) => {
    if (newVal.length === 1) currentOrderItem.type = newVal[0];
    else if (newVal.length > 1) currentOrderItem.type = '';
});

const isValidClientType = computed(() => {
    if (orderForm.tipo_pedido === 'promocion') return true; 
    return orderForm.clientStatus === 'CLIENTE' || orderForm.clientStatus === 'DISTRIBUIDOR';
});

const handleTypeChange = () => {
    if (orderForm.orderItems.length > 0) {
        if (confirm("Cambiar el tipo de pedido vaciará el carrito actual. ¿Desea continuar?")) {
            orderForm.orderItems = [];
        } else {
            orderForm.tipo_pedido = orderForm.tipo_pedido === 'normal' ? 'promocion' : 'normal';
        }
    }
    if (orderForm.tipo_pedido === 'promocion') currentOrderItem.price = 0;
};

const isCurrentItemValid = computed(() => {
    return currentOrderItem.bookId && currentOrderItem.type && currentOrderItem.quantity >= 1;
});

const addItemToCart = () => {
    if (!isCurrentItemValid.value) return;
    orderForm.orderItems.push({ 
        id: Date.now(),
        bookId: currentOrderItem.bookId,
        bookName: currentOrderItem.bookName,
        type: currentOrderItem.type,
        quantity: currentOrderItem.quantity,
        price: currentOrderItem.price, 
        totalCost: currentOrderItem.price * currentOrderItem.quantity 
    });
    Object.assign(currentOrderItem, { bookId: null, bookName: '', type: '', category: '', quantity: 1, price: 0, bookSuggestions: [] });
};

const removeItemFromCart = (index) => orderForm.orderItems.splice(index, 1);
const orderTotal = computed(() => orderForm.orderItems.reduce((sum, item) => sum + item.totalCost, 0));
const totalUnits = computed(() => orderForm.orderItems.reduce((sum, item) => sum + item.quantity, 0));
const formatCurrency = (value) => value.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

let clientTimeout = null;
const searchClients = () => {
    clearTimeout(clientTimeout);
    if (orderForm.clientName.length < 3) {
        clientSuggestions.value = [];
        searchingClients.value = false;
        return;
    }
    searchingClients.value = true;
    clientTimeout = setTimeout(async () => {
        try {
            const res = await axios.get('/search/clientes', {
                params: { query: orderForm.clientName, include_prospectos: orderForm.tipo_pedido === 'promocion' }
            });
            clientSuggestions.value = res.data;
        } catch (err) { console.error(err); }
        finally { searchingClients.value = false; }
    }, 400);
};

const selectClient = (client) => {
    orderForm.clientId = client.id;
    orderForm.clientName = client.name;
    orderForm.clientStatus = client.tipo; 
    clientSuggestions.value = [];
    clientSelected.value = true;
};

let bookTimeout = null;
const searchBooks = () => {
    clearTimeout(bookTimeout);
    if (currentOrderItem.bookName.length < 3) {
        currentOrderItem.bookSuggestions = [];
        searchingLibros.value = false;
        return;
    }
    searchingLibros.value = true;
    bookTimeout = setTimeout(async () => {
        try {
            const res = await axios.get('/search/libros', { 
                params: { query: currentOrderItem.bookName, tipo_pedido: orderForm.tipo_pedido } 
            });
            currentOrderItem.bookSuggestions = res.data;
        } catch (err) { console.error(err); }
        finally { searchingLibros.value = false; }
    }, 400);
};

const selectBook = (book) => {
    currentOrderItem.bookId = book.id;
    currentOrderItem.bookName = book.titulo;
    currentOrderItem.category = book.type; 
    currentOrderItem.bookSuggestions = []; 
};

const submitOrder = async () => {
    loading.value = true;
    error.value = null;

    const payload = {
        client_id: orderForm.clientId,
        client_name: orderForm.clientName,
        tipo_pedido: orderForm.tipo_pedido,
        prioridad: orderForm.prioridad,
        receiver_type: orderForm.receiverType,
        receiver_data: orderForm.receiverType === 'nuevo' ? orderForm.receiver : null,
        delivery_option: orderForm.logistics.deliveryOption,
        paqueteria_nombre: orderForm.logistics.paqueteria_nombre,
        comments: orderForm.comments,
        items: orderForm.orderItems.map(item => ({
            libro_id: item.bookId,
            bookName: item.bookName,
            type: item.type,
            quantity: item.quantity,
            price: item.price,
        })),
    };

    try {
        const res = await axios.post('/pedidos', payload);
        generatedOrderId.value = res.data.order_id;
        showSuccessModal.value = true; // Mostrar el modal al tener éxito
    } catch (err) {
        if (err.response && err.response.data && err.response.data.errors) {
            const firstError = Object.values(err.response.data.errors)[0][0];
            error.value = `Error de validación: ${firstError}`;
        } else {
            error.value = err.response?.data?.message || 'Error al enviar pedido.';
        }
    } finally {
        loading.value = false;
    }
};

const closeAndRedirect = () => {
    showSuccessModal.value = false;
    router.push('/pedidos'); // Redirigir al listado
};
</script>

<style scoped>
.form-section { background: white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 35px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.section-title { font-size: 1.3rem; font-weight: 800; color: #a93339; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; }
.autocomplete-list { position: absolute; z-index: 1000; width: 100%; background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); max-height: 250px; overflow-y: auto; list-style: none; padding: 5px; margin-top: 5px; }
.autocomplete-list li { padding: 12px 18px; cursor: pointer; border-radius: 8px; transition: background 0.2s; font-size: 0.95rem; }
.autocomplete-list li:hover { background: #fef2f2; color: #a93339; }
.animate-fade-in { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

/* ESTILOS DEL MODAL */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
}
.modal-content-success {
    background: white;
    padding: 40px;
    border-radius: 30px;
    width: 90%;
    max-width: 420px;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
    animation: modalScale 0.3s ease;
}
@keyframes modalScale {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.success-icon-wrapper {
    width: 80px;
    height: 80px;
    background: #dcfce7;
    color: #166534;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto 20px;
}
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>