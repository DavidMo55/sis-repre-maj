<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Visita de Seguimiento (Subsecuente)</h1>
                    <p>Registra el avance en la negociación con planteles existentes.</p>
                </div>
                <button @click="$router.push('/visitas')" class="btn-secondary" :disabled="loading">
                    <i class="fas fa-arrow-left"></i> Volver al Historial
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="mt-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- BLOQUE 1: SELECCIÓN Y ESTADO DEL PLANTEL -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-search"></i> 1. Identificar Plantel
                        </div>
                        
                        <div class="form-group mb-4 relative">
                            <label>Buscar por Nombre</label>
                            <div class="relative">
                                <input 
                                    v-model="searchQuery" 
                                    type="text" 
                                    class="form-input pl-10" 
                                    placeholder="Escriba el nombre del plantel o prospecto..."
                                    @input="searchClientes"
                                    :disabled="loading"
                                >
                                <i class="fas fa-school absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                
                                <div v-if="searchingClients" class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <i class="fas fa-spinner fa-spin text-gray-400"></i>
                                </div>
                                
                                <ul v-if="clientesSuggestions.length" class="autocomplete-list">
                                    <li v-for="c in clientesSuggestions" :key="c.id" @click="selectCliente(c)">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-800">{{ c.name }}</span>
                                            <div class="flex gap-2 items-center">
                                                <small v-if="c.status === 'inactivo'" class="status-pill bg-red-100 text-red-700">RECHAZADO</small>
                                                <small v-else-if="c.tipo === 'CLIENTE'" class="status-pill bg-green-100 text-green-700">CLIENTE ACTIVO</small>
                                                <small v-else class="status-pill bg-orange-100 text-orange-700">PROSPECTO</small>
                                                <small class="text-gray-500 truncate">{{ c.direccion }}</small>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Card de info del plantel seleccionado -->
                        <div v-if="selectedCliente" class="selected-client-card animate-fade-in">
                            <div class="p-4 rounded-xl border border-blue-200 bg-blue-50 shadow-sm">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-blue-900 leading-tight">{{ selectedCliente.name }}</h4>
                                        <p class="text-xs text-blue-700 mt-2"><i class="fas fa-map-marker-alt mr-1"></i> {{ selectedCliente.direccion }}</p>
                                        <p class="text-xs text-blue-700 mt-1"><i class="fas fa-user-tie mr-1"></i> {{ selectedCliente.contacto }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div v-if="selectedCliente.status === 'inactivo'" class="badge-custom badge-red">INACTIVO</div>
                                        <div v-else-if="selectedCliente.tipo === 'CLIENTE'" class="badge-custom badge-green">CLIENTE</div>
                                        <div v-else class="badge-custom badge-orange">PROSPECTO</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-10 text-center border-2 border-dashed border-gray-200 rounded-xl text-gray-400 italic">
                            Selecciona un plantel para habilitar el registro.
                        </div>
                    </div>

                    <!-- BLOQUE 2: DETALLES DE LA ENTREVISTA -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-calendar-check"></i> 2. Datos de la Visita
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label>Fecha de Hoy</label>
                                <input v-model="form.fecha" type="date" class="form-input" required :disabled="loading">
                            </div>
                            <div class="form-group">
                                <label>Tentativa Próxima Visita</label>
                                <input v-model="form.proxima_visita" type="date" class="form-input" :disabled="loading">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label>Atendido por:</label>
                                <input v-model="form.persona_entrevistada" type="text" class="form-input" placeholder="Nombre completo" required :disabled="loading">
                            </div>
                            <div class="form-group">
                                <label>Cargo:</label>
                                <input v-model="form.cargo" type="text" class="form-input" placeholder="Ej: Director, Docente" required :disabled="loading">
                            </div>
                        </div>

                        <!-- LIBROS DE INTERÉS -->
                        <div class="form-section-inner mb-6">
                            <label class="font-bold text-gray-700 block mb-2 text-sm">Libros de Interés</label>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
                                <div class="md:col-span-6 relative">
                                    <input v-model="bookInput.titulo" type="text" class="form-input text-sm" placeholder="Buscar título..." @input="searchBooks" @keydown.enter.prevent="addBookToList" :disabled="loading">
                                    <ul v-if="bookSuggestions.length" class="autocomplete-list">
                                        <li v-for="b in bookSuggestions" :key="b.id" @click="selectBook(b)">{{ b.titulo }}</li>
                                    </ul>
                                </div>
                                <div class="md:col-span-4">
                                    <input v-model="bookInput.comentario" type="text" class="form-input text-sm" placeholder="Nota corta" @keydown.enter.prevent="addBookToList" :disabled="loading">
                                </div>
                                <div class="md:col-span-2">
                                    <button type="button" @click="addBookToList" class="btn-primary w-full py-2" :disabled="!bookInput.id || loading"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>

                            <div v-if="selectedBooks.length" class="mt-4 border rounded-lg bg-white overflow-hidden shadow-sm">
                                <table class="min-width-full divide-y divide-gray-100">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-[10px] font-black text-gray-400 uppercase">Título</th>
                                            <th class="px-3 py-2 text-left text-[10px] font-black text-gray-400 uppercase">Nota</th>
                                            <th class="px-3 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="(item, idx) in selectedBooks" :key="idx">
                                            <td class="px-3 py-2 text-xs font-bold">{{ item.titulo }}</td>
                                            <td class="px-3 py-2 text-xs italic text-gray-500">{{ item.comentario || '-' }}</td>
                                            <td class="px-3 py-2 text-right">
                                                <button type="button" @click="removeBook(idx)" class="text-red-400 hover:text-red-600" :disabled="loading"><i class="fas fa-trash-alt"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="flex items-center gap-3 cursor-pointer select-none">
                                <input v-model="form.material_entregado" type="checkbox" class="w-5 h-5 accent-red-600" :disabled="loading">
                                <span class="font-bold text-gray-700 text-sm">¿Se entregó material físico de promoción?</span>
                            </label>
                        </div>

                        <div class="form-group mb-4">
                            <label>Comentarios y Acuerdos</label>
                            <textarea v-model="form.comentarios" class="form-input" rows="3" placeholder="Describe los puntos clave de la reunión..." :disabled="loading"></textarea>
                        </div>

                        <!-- RESULTADO Y CAMBIO DE ESTADO -->
                        <div class="form-group bg-gray-50 p-4 rounded-xl border-2 border-red-100">
                            <label class="font-black text-red-800 text-xs uppercase tracking-widest mb-2 block">Resolución de la Visita</label>
                            <select v-model="form.decision_final" class="form-input font-bold" required :disabled="loading">
                                <option value="seguimiento">CONTINUAR SEGUIMIENTO (Mantener como Prospecto)</option>
                                <option value="compra"> DECISIÓN DE COMPRA (Convertir a Cliente)</option>
                                <option value="rechazo"> NO INTERESADO (Inactivar registro)</option>
                            </select>
                            <div class="mt-2 text-[11px] text-gray-500 italic">
                                <p v-if="form.decision_final === 'compra'" class="text-green-600 font-bold">
                                    <i class="fas fa-info-circle"></i> El plantel pasará automáticamente a tu lista oficial de Clientes.
                                </p>
                                <p v-if="form.decision_final === 'rechazo'" class="text-red-600 font-bold">
                                    <i class="fas fa-exclamation-triangle"></i> El plantel se marcará como inactivo y se suspenderá el seguimiento comercial.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-4 border-t pt-6">
                    <button type="submit" class="btn-primary px-16 shadow-lg" :disabled="loading || !form.cliente_id">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i> 
                        {{ loading ? 'Guardando...' : 'Finalizar Seguimiento' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL DE ÉXITO ESTÉTICO -->
        <Transition name="modal-fade">
            <div v-if="showSuccessModal" class="modal-overlay-custom">
                <div class="modal-content-success">
                    <div class="success-icon-wrapper">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 class="modal-title-success">¡Registro Exitoso!</h2>
                    <p class="modal-text-success">
                        La visita de seguimiento se ha guardado correctamente en la bitácora.
                        <span v-if="form.decision_final === 'compra'" class="block mt-2 font-bold text-green-600">
                            El plantel ha sido convertido a CLIENTE.
                        </span>
                    </p>
                    <div class="modal-actions-success">
                        <button @click="goToHistory" class="btn-modal-success">
                            Entendido, volver al listado
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const loading = ref(false);
const showSuccessModal = ref(false);

// Clientes
const searchQuery = ref('');
const clientesSuggestions = ref([]);
const selectedCliente = ref(null);
const searchingClients = ref(false);
let clientSearchTimeout = null;

// Libros
const bookInput = reactive({ id: null, titulo: '', comentario: '' });
const bookSuggestions = ref([]);
const selectedBooks = ref([]);
let bookSearchTimeout = null;

const form = reactive({
    cliente_id: null,
    fecha: new Date().toISOString().split('T')[0],
    persona_entrevistada: '',
    cargo: '',
    libros_interes: '',
    material_entregado: false,
    comentarios: '',
    proxima_visita: '',
    decision_final: 'seguimiento'
});

const searchClientes = async () => {
    if (selectedCliente.value && searchQuery.value !== selectedCliente.value.name) {
        selectedCliente.value = null;
        form.cliente_id = null;
    }
    if (searchQuery.value.length < 3) {
        clientesSuggestions.value = [];
        return;
    }
    clearTimeout(clientSearchTimeout);
    clientSearchTimeout = setTimeout(async () => {
        searchingClients.value = true;
        try {
            const res = await axios.get('/search/clientes', { 
                params: { query: searchQuery.value, include_prospectos: true } 
            });
            clientesSuggestions.value = res.data;
        } catch (e) { console.error(e); } 
        finally { searchingClients.value = false; }
    }, 300);
};

const selectCliente = (c) => {
    selectedCliente.value = c;
    form.cliente_id = c.id;
    searchQuery.value = c.name;
    clientesSuggestions.value = [];
};

const searchBooks = async () => {
    if (bookInput.titulo.length < 3) {
        bookSuggestions.value = [];
        return;
    }
    clearTimeout(bookSearchTimeout);
    bookSearchTimeout = setTimeout(async () => {
        try {
            const res = await axios.get('/search/libros', { params: { query: bookInput.titulo } });
            bookSuggestions.value = res.data;
        } catch (e) { console.error(e); }
    }, 300);
};

const selectBook = (b) => {
    bookInput.id = b.id;
    bookInput.titulo = b.titulo;
    bookSuggestions.value = [];
};

const addBookToList = () => {
    if (!bookInput.id) return;
    selectedBooks.value.push({ ...bookInput });
    bookInput.id = null; bookInput.titulo = ''; bookInput.comentario = '';
};

const removeBook = (idx) => selectedBooks.value.splice(idx, 1);

const handleSubmit = async () => {
    if (!form.cliente_id) return;
    loading.value = true;
    
    form.libros_interes = selectedBooks.value
        .map(b => `${b.titulo}${b.comentario ? ' (' + b.comentario + ')' : ''}`)
        .join('; ');

    try {
        await axios.post('/visitas/seguimiento', form);
        // MOSTRAR MODAL EN LUGAR DE ALERT
        showSuccessModal.value = true;
    } catch (e) {
        alert(e.response?.data?.message || "Error al procesar la solicitud.");
    } finally {
        loading.value = false;
    }
};

const goToHistory = () => {
    showSuccessModal.value = false;
    router.push('/visitas');
};
</script>

<style scoped>
.form-section { background: white; padding: 25px; border-radius: 16px; border: 1px solid #eef2f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.form-section-inner { background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; }
.section-title { color: var(--brand-red-dark); font-weight: 900; font-size: 1.1rem; border-bottom: 2px solid #fee2e2; padding-bottom: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

.autocomplete-list { 
    position: absolute; width: 100%; background: white; border: 1px solid #e2e8f0; z-index: 1100; 
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); max-height: 250px; overflow-y: auto; 
    list-style: none; padding: 0; margin: 0; border-radius: 8px; margin-top: 4px;
}
.autocomplete-list li { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
.autocomplete-list li:hover { background: #f8fafc; }

.status-pill { padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; }
.badge-custom { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; border: 1px solid transparent; }
.badge-orange { background: #fff7ed; color: #c2410c; border-color: #ffedd5; }
.badge-green { background: #f0fdf4; color: #15803d; border-color: #dcfce7; }
.badge-red { background: #fef2f2; color: #b91c1c; border-color: #fee2e2; }

/* MODAL DE ÉXITO CUSTOM */
.modal-overlay-custom {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
}

.modal-content-success {
    background: white;
    padding: 40px;
    border-radius: 24px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.success-icon-wrapper {
    width: 70px;
    height: 70px;
    background: #dcfce7;
    color: #166534;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
    animation: scale-up 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.modal-title-success {
    font-size: 1.5rem;
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 10px;
}

.modal-text-success {
    color: #64748b;
    font-size: 1rem;
    line-height: 1.5;
    margin-bottom: 30px;
}

.btn-modal-success {
    width: 100%;
    background: #0f172a;
    color: white;
    padding: 14px;
    border-radius: 12px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-modal-success:hover {
    background: #1e293b;
    transform: translateY(-2px);
}

/* ANIMACIONES */
@keyframes scale-up {
    from { transform: scale(0); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>