<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header">
                <h1>Ingreso de Pedidos</h1>
                <p>Completa los siguientes pasos para registrar el pedido solicitado por el cliente.</p>
            </div>
            
            <form @submit.prevent="submitOrder">

                <!-- SECCIÓN 1: SELECCIÓN DEL CLIENTE (CON AUTOCOMPLETADO) -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-building"></i> 1. Selección de Cliente
                    </div>

                    <div class="form-group relative">
                        <label for="cliente">Seleccionar Cliente:</label>
                        <input 
                            type="text" 
                            id="cliente" 
                            class="form-input" 
                            placeholder="Buscar por Nombre del Plantel (Solo CLIENTES)..." 
                            v-model="orderForm.clientName"
                            @input="searchClients"
                            required
                        >
                        <ul v-if="clientSuggestions.length" class="autocomplete-list">
                            <li 
                                v-for="client in clientSuggestions" 
                                :key="client.id"
                                @click="selectClient(client)"
                            >
                                {{ client.name }} ({{ client.tipo }})
                            </li>
                        </ul>
                        
                        <p v-if="clientSelected && orderForm.clientStatus !== 'CLIENTE' && orderForm.clientStatus !== 'DISTRIBUIDOR'" class="error-message">
                            El plantel seleccionado es {{ orderForm.clientStatus }}. No puede ingresar un pedido.
                        </p>
                        <p v-if="orderForm.clientId" class="success-message-small">
                            Cliente Seleccionado: {{ orderForm.clientName }} 
                        </p>
                    </div>
                </div>

                <!-- SECCIÓN 2: DATOS DE RECEPCIÓN Y ENTREGA -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-map-marker-alt"></i> 2. Datos de Recepción y Entrega
                    </div>

                    <div class="form-group">
                        <label>Persona que recibirá el pedido:</label>
                        <div class="flex-row-centered space-between-radio">
                            <input type="radio" id="use-client-data" value="cliente" v-model="orderForm.receiverType">
                            <label for="use-client-data">Usar datos de contacto del Cliente</label>
                            
                            <input type="radio" id="new-data" value="nuevo" v-model="orderForm.receiverType">
                            <label for="new-data">Ingresar datos nuevos</label>
                        </div>
                    </div>
                    <div v-if="orderForm.receiverType === 'nuevo'" class="new-receiver-form">
                        <div class="grid-form-cols-2">
                            <div class="form-group"><label for="nombre">Nombre</label><input type="text" id="nombre" class="form-input" v-model="orderForm.receiver.nombre" :required="orderForm.receiverType === 'nuevo'"></div>
                            <div class="form-group"><label for="rfc">RFC (Opcional)</label><input type="text" id="rfc" class="form-input" v-model="orderForm.receiver.rfc"></div>
                            <div class="form-group"><label for="telefono">Teléfono</label><input type="tel" id="telefono" class="form-input" v-model="orderForm.receiver.telefono" :required="orderForm.receiverType === 'nuevo'"></div>
                            <div class="form-group"><label for="correo">Correo Electrónico</label><input type="email" id="correo" class="form-input" v-model="orderForm.receiver.correo" :required="orderForm.receiverType === 'nuevo'"></div>
                        </div>

                        <div class="form-group mt-4">
                            <label for="direccion">Dirección completa de la entrega (Opcional)</label>
                            <input type="text" id="direccion" class="form-input" v-model="orderForm.receiver.direccion">
                        </div>
                        
                    </div>
                    
                    <div class="form-group mt-4 pt-4 border-top-gray">
                        <label>Opciones de Logística (Solo una opción - Opcional):</label>
                        <div class="flex-row-centered space-between-radio">
                            <input type="radio" id="logistica-recoleccion" value="recoleccion" v-model="orderForm.logistics.deliveryOption"> 
                            <label for="logistica-recoleccion">Recolección en almacén</label>
                            
                            <input type="radio" id="logistica-paqueteria" value="paqueteria" v-model="orderForm.logistics.deliveryOption"> 
                            <label for="logistica-paqueteria">Paquetería sugerida</label>
                            
                            <input type="radio" id="logistica-none" value="none" v-model="orderForm.logistics.deliveryOption"> 
                            <label for="logistica-none">Ninguna / Estándar</label>
                        </div>
                    </div>

                    <div class="form-group mt-6 pt-4 border-top-gray">
                        <label for="comments">Comentarios Generales (Opcional)</label>
                        <textarea 
                            id="comments" 
                            class="form-input" 
                            v-model="orderForm.comments" 
                            rows="3" 
                            placeholder="Notas importantes sobre la entrega, facturación, o requisitos especiales..."
                        ></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-book-open"></i> 3. Agregar Libro al Pedido
                    </div>
                    
                    <div class="grid-book-add-form grid-cols-5 gap-4 items-end">
                        
                        <div class="form-group relative col-span-2-mobile">
                            <label>Libro</label>
                            <input type="text" class="form-input" v-model="currentOrderItem.bookName" placeholder="Buscar título..." @input="searchBooks" @focus="currentOrderItem.bookSuggestions = []">
                            <ul v-if="currentOrderItem.bookSuggestions.length" class="autocomplete-list">
                                <li v-for="book in currentOrderItem.bookSuggestions" :key="book.id" @click="selectBook(book)">
                                    {{ book.titulo }} ({{ book.ISBN }})
                                </li>
                            </ul>
                        </div>
                        
                        <div class="form-group">
                            <label>Tipo</label>
                            <select class="form-input" v-model="currentOrderItem.type">
                                <option value="" disabled>Seleccionar Tipo</option>
                                <option>Físico (Recurso MED)</option>
                                <option>Digital (Plataforma Blink)</option>
                                <option>Pack (Físico + Digital)</option>
                                <option>Acceso Demo</option>
                                <option>Licencia docente</option>
                                <option>Acceso Docente MED</option>
                            </select>
                        </div>
                        
                        <div class="form-group form-group-qty">
                            <label>Cantidad</label>
                            <input type="number" min="1" class="form-input" v-model.number="currentOrderItem.quantity" placeholder="Ej: 50">
                        </div>

                        <div class="form-group form-group-qty">
                            <label>Precio Unitario</label>
                            <input 
                                type="number" 
                                min="0" 
                                step="0.01" 
                                class="form-input" 
                                v-model.number="currentOrderItem.price" 
                                placeholder="Ej: $350.00"
                            >
                        </div>

                        <div class="form-group">
                            <button 
                                type="button" 
                                @click="addItemToCart" 
                                class="btn-primary btn-add-full-width"
                                :disabled="!isCurrentItemValid || loading"
                            >
                                <i class="fas fa-cart-plus"></i> Agregar
                            </button>
                        </div>
                    </div>
                    
                    <p v-if="currentItemError" class="error-message-small mt-2">{{ currentItemError }}</p>


                    <div class="cart-summary-container">
                        <h4 class="cart-summary-title">Resumen del pedido:</h4>
                        <div v-if="orderForm.orderItems.length === 0" class="cart-empty-message">
                            No hay libros en el carrito. Agrega un ítem.
                        </div>

                        <div v-else class="table-responsive border-rounded-lg">
                            <table class="min-width-full divide-y-gray-200">
                                <thead class="bg-light-gray">
                                    
                                    <tr>
                                        <th class="table-header">Libro</th>
                                        <th class="table-header">Tipo</th>
                                        <th class="table-header">Total de unidades</th>
                                        <th class="table-header text-right">Precio Unitario</th>
                                        <th class="table-header text-right">Costo Total</th>
                                        <th class="table-header-action"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y-gray-200">
                                    <tr v-for="(item, index) in orderForm.orderItems" :key="item.id">
                                        <td class="table-cell ">{{ item.bookName }}</td>
                                        <td class="table-cell ">{{ item.type }}</td>
                                        <td class="table-cell">{{ item.quantity }}</td>
                                        <td class="table-cell ">{{ formatCurrency(item.price) }}</td>
                                        <td class="table-cell ">{{ formatCurrency(item.totalCost) }}</td>
                                        <td class="table-cell-action">
                                            <button 
                                                type="button" 
                                                @click="removeItemFromCart(index)" 
                                                class="text-red-link"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>

                                    <tr class="summary-row">
                                            <th class="summary-cell">
                                            </th>
                                            <th class="summary-cell">
                                            </th>
                                            <th class="summary-cell">
                                            &nbsp;&nbsp;  {{ orderForm.orderItems.length }}
                                            </th>
                                            <th class="summary-cell">
                                            </th>
                                            <th class="summary-cell">
                                                 {{ formatCurrency(orderTotal) }}
                                            </th>
                                            <th class="summary-cell"></th>
                                        </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="btn-row-final">
                    <button 
                        type="submit" 
                        class="login-button btn-primary" 
                        :disabled="loading || !orderForm.clientId || orderForm.orderItems.length === 0"
                    >
                        <i class="fas fa-paper-plane"></i> 
                        {{ loading ? 'Enviando Pedido...' : 'Confirmar y Enviar Pedido' }}
                    </button>
                    <p v-if="orderForm.orderItems.length === 0" class="error-message-small ml-4 mt-1">El carrito está vacío.</p>
                </div>
            </form>
            
            <p v-if="error" class="error-message mt-4">{{ error }}</p>
            <p v-if="successMessage" class="success-message mt-4">{{ successMessage }}</p>

        </div>
    </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import axios from '../axios';

const loading = ref(false);
const error = ref(null);
const successMessage = ref(null);

const clientSuggestions = ref([]);
const clientSelected = ref(false);

const currentItemError = ref(null);

const currentOrderItem = reactive({
    id: null,
    bookName: '',
    type: '',
    quantity: 1,
    price: null, 
    bookSuggestions: [],
    bookId: null,
});

const resetCurrentItem = () => {
    Object.assign(currentOrderItem, {
        id: null,
        bookName: '',
        type: '',
        quantity: 1,
        price: null, 
        bookSuggestions: [],
        bookId: null,
    });
    currentItemError.value = null;
}

const isCurrentItemValid = computed(() => {
    const isPriceValid = currentOrderItem.price !== null && currentOrderItem.price >= 0;
    
    return currentOrderItem.bookId !== null &&
           currentOrderItem.bookName.trim() !== '' &&
           currentOrderItem.type !== '' &&
           currentOrderItem.quantity >= 1 &&
           isPriceValid;
});

const addItemToCart = () => {
    if (!isCurrentItemValid.value) {
        currentItemError.value = "Por favor, selecciona un libro, su tipo, cantidad y un precio unitario válido.";
        return;
    }

    orderForm.orderItems.push({ 
        id: getUniqueId(), 
        bookId: currentOrderItem.bookId,
        bookName: currentOrderItem.bookName,
        type: currentOrderItem.type,
        quantity: currentOrderItem.quantity,
        price: currentOrderItem.price, 
        totalCost: currentOrderItem.price * currentOrderItem.quantity 
    });
    
    resetCurrentItem();
};

const removeItemFromCart = (index) => {
    orderForm.orderItems.splice(index, 1);
};


const orderForm = reactive({
    clientId: null,
    clientName: '',
    clientStatus: '', 

    receiverType: 'cliente',
    receiver: {
        nombre: '',
        rfc: '',
        telefono: '',
        correo: '',
        direccion: '',
    },
    logistics: {
        deliveryOption: 'none', 
    },
    comments: '', 

    orderItems: [], 
});

const orderTotal = computed(() => {
    return orderForm.orderItems.reduce((sum, item) => sum + item.totalCost, 0);
});

const getUniqueId = () => Date.now() + Math.floor(Math.random() * 1000);

const formatCurrency = (value) => {
    if (value === null || isNaN(value)) return '$0.00';
    return value.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};


let clientSearchTimeout = null;

const searchClients = () => {
    clientSuggestions.value = [];
    orderForm.clientId = null;
    orderForm.clientStatus = '';
    clientSelected.value = false;

    clearTimeout(clientSearchTimeout);

    if (orderForm.clientName.length < 3) return;

    clientSearchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get('/search/clientes', {
                params: { query: orderForm.clientName }
            });
            clientSuggestions.value = response.data;
        } catch (err) {
            console.error('Error buscando clientes:', err);
        }
    }, 300); 
};

const selectClient = (client) => {
    orderForm.clientId = client.id;
    orderForm.clientName = client.name;
    orderForm.clientStatus = client.tipo; 
    clientSuggestions.value = [];
    clientSelected.value = true;
};


let bookSearchTimeout = null;

const searchBooks = () => {
    currentOrderItem.bookSuggestions = [];
    currentOrderItem.bookId = null;

    if (bookSearchTimeout) {
        clearTimeout(bookSearchTimeout);
    }

    const query = currentOrderItem.bookName;
    if (query.length < 3) return;

    bookSearchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get('/search/libros', {
                params: { query: query }
            });
            currentOrderItem.bookSuggestions = response.data;
        } catch (err) {
            console.error('Error buscando libros:', err);
        }
    }, 300); 
};

const selectBook = (book) => {
    currentOrderItem.bookId = book.id;
    currentOrderItem.bookName = book.titulo;
    currentOrderItem.bookSuggestions = []; 
};


const submitOrder = async () => {
    if (!orderForm.clientId) {
        error.value = 'Debes seleccionar un cliente válido de la lista de sugerencias.';
        return;
    }
    if (orderForm.orderItems.length === 0) {
        error.value = 'Debes agregar al menos un libro al pedido.';
        return;
    }

    loading.value = true;
    error.value = null;
    successMessage.value = null;

    const payload = {
        client_name: orderForm.clientName, 
        receiver_type: orderForm.receiverType,
        receiver_data: orderForm.receiverType === 'nuevo' ? orderForm.receiver : null, 
        delivery_option: orderForm.logistics.deliveryOption,
        comments: orderForm.comments, // AÑADIDO: Comentarios generales
        items: orderForm.orderItems.map(item => ({
            bookName: item.bookName, 
            type: item.type,
            quantity: item.quantity,
            price: item.price, 
        })),
    };

    try {
        const response = await axios.post('/pedidos', payload); 
        
        successMessage.value = `Pedido #${response.data.order_id || '1001'} ingresado exitosamente para ${orderForm.clientName}.`;
        
        Object.assign(orderForm, {
            clientId: null,
            clientName: '',
            clientStatus: '',
            receiverType: 'cliente',
            receiver: { nombre: '', rfc: '', telefono: '', correo: '', direccion: '' },
            logistics: { deliveryOption: 'none' }, 
            comments: '', // Resetear comentarios
            orderItems: [], 
        });
        clientSelected.value = false;
        resetCurrentItem(); 


    } catch (err) {
        if (err.response && err.response.data && err.response.data.message) {
            error.value = 'Error al enviar el pedido: ' + err.response.data.message;
        } else {
            error.value = 'Error de conexión al intentar enviar el pedido.';
        }
        console.error("Error al enviar pedido:", err);
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>

</style>