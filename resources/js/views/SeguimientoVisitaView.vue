<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1 v-if="loadingOriginal">Cargando datos...</h1>
                    <h1 v-else-if="selectedCliente">Seguimiento: {{ selectedCliente.name }}</h1>
                    <h1 v-else>Registro de Visita Subsecuente</h1>
                    <p>Registra el avance en la negociación buscando planteles en tu bitácora de visitas.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary flex-row-centered gap-2" :disabled="loading">
                    <i class="fas fa-arrow-left"></i> Volver al Historial
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="mt-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- BLOQUE 1: SELECCIÓN Y ESTADO DEL PLANTEL -->
                    <div class="form-section" style="overflow: visible !important;">
                        <div class="section-title">
                            <i class="fas fa-search"></i> 1. Identificar Plantel (Bitácora)
                        </div>
                        
                        <div v-if="!route.params.id" class="form-group mb-4 relative">
                            <label>Buscar en Historial de Visitas</label>
                            <div class="relative">
                                <input 
                                    v-model="searchQuery" 
                                    type="text" 
                                    class="form-input pl-10" 
                                    placeholder="Escriba el nombre del plantel..."
                                    @input="searchProspectos"
                                    :disabled="loading"
                                    autocomplete="off"
                                >
                                <i class="fas fa-history absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                
                                <div v-if="searchingClients" class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <i class="fas fa-spinner fa-spin text-red-600"></i>
                                </div>
                                
                                <ul v-if="clientesSuggestions.length" class="autocomplete-list">
                                    <li v-for="v in clientesSuggestions" :key="v.id" @click="selectProspecto(v)">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-800">{{ v.nombre_plantel }}</span>
                                            <div class="flex gap-2 items-center">
                                                <small class="status-pill bg-blue-100 text-blue-700">
                                                    {{ v.es_primera_visita ? 'PRIMERA VISITA' : 'SEGUIMIENTO PREVIO' }}
                                                </small>
                                                <small class="text-gray-500 truncate">{{ v.direccion_plantel }}</small>
                                            </div>
                                        </div>
                                    </li>
                                </ul>

                                <!-- MENSAJE SI NO HAY RESULTADOS -->
                                <div v-else-if="searchQuery.length >= 3 && !searchingClients && hasAttemptedSearch" class="autocomplete-list p-4 text-center text-xs text-gray-400">
                                    <i class="fas fa-search-minus mb-1 block text-lg"></i>
                                    No se encontraron registros previos para "{{ searchQuery }}".
                                </div>
                            </div>
                        </div>

                        <!-- Card de info del plantel seleccionado -->
                        <div v-if="selectedCliente" class="selected-client-card animate-fade-in">
                            <div class="p-5 rounded-xl border-2 border-red-100 bg-red-50 shadow-sm">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-black text-red-900 leading-tight text-lg">{{ selectedCliente.name }}</h4>
                                        <p class="text-xs text-red-700 mt-2 font-bold"><i class="fas fa-map-marker-alt mr-1"></i> {{ selectedCliente.direccion }}</p>
                                        <p class="text-xs text-gray-500 mt-1"><i class="fas fa-user-tie mr-1"></i> Director: {{ selectedCliente.contacto }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="badge-custom" :class="selectedCliente.visita_id ? 'badge-orange' : 'badge-green'">
                                            {{ selectedCliente.visita_id ? 'PROSPECTO' : 'CLIENTE' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-10 text-center border-2 border-dashed border-gray-200 rounded-xl text-gray-400 italic">
                            Escribe el nombre de la escuela para cargar sus datos previos.
                        </div>
                    </div>

                    <!-- BLOQUE 2: DETALLES DE LA ENTREVISTA -->
                    <div class="form-section" :class="{'opacity-50 pointer-events-none': !selectedCliente}">
                        <div class="section-title">
                            <i class="fas fa-calendar-check"></i> 2. Datos de la Visita Actual
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
                                <label>Persona Entrevistada</label>
                                <input v-model="form.persona_entrevistada" type="text" class="form-input" placeholder="¿Quién atendió hoy?" required :disabled="loading">
                            </div>
                            <div class="form-group">
                                <label>Cargo</label>
                                <input v-model="form.cargo" type="text" class="form-input" placeholder="Ej: Director" required :disabled="loading">
                            </div>
                        </div>

                        <!-- LIBROS DE INTERÉS -->
                        <div class="form-section-inner mb-6 bg-white p-4 rounded-xl border border-gray-100" style="overflow: visible !important;">
                            <label class="font-bold text-gray-700 block mb-2 text-xs uppercase tracking-widest">Libros de Interés</label>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
                                <div class="md:col-span-10 relative">
                                    <input 
                                        v-model="bookInput.titulo" 
                                        type="text" 
                                        class="form-input text-sm" 
                                        placeholder="Buscar título..." 
                                        @input="searchBooks"
                                        @keydown.enter.prevent="addBookToList"
                                        :disabled="loading"
                                        autocomplete="off"
                                    >
                                    <ul v-if="bookSuggestions.length" class="autocomplete-list">
                                        <li v-for="b in bookSuggestions" :key="b.id" @click="selectBook(b)">{{ b.titulo }}</li>
                                    </ul>
                                </div>
                                <div class="md:col-span-2">
                                    <button type="button" @click="addBookToList" class="btn-primary w-full py-3" :disabled="!bookInput.id || loading">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <div v-if="selectedBooks.length" class="mt-3 space-y-2">
                                <div v-for="(item, idx) in selectedBooks" :key="idx" class="flex justify-between items-center bg-gray-50 p-2 rounded border text-xs">
                                    <span class="font-bold text-gray-700">{{ item.titulo }}</span>
                                    <button type="button" @click="removeBook(idx)" class="text-red-500 hover:text-red-700" :disabled="loading">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="flex items-center gap-3 cursor-pointer select-none">
                                <input v-model="form.material_entregado" type="checkbox" class="w-5 h-5 accent-red-600" :disabled="loading">
                                <span class="font-bold text-gray-700 text-sm">¿Se entregó material físico de promoción?</span>
                            </label>
                        </div>

                        <Transition name="fade">
                            <div v-if="form.material_entregado" class="form-group mb-4 p-4 bg-red-50 rounded-xl border border-red-100 animate-fade-in">
                                <label class="text-red-800 font-bold">Cantidad de libros entregados</label>
                                <input v-model.number="form.material_cantidad" type="number" min="1" class="form-input mt-1 border-red-200" placeholder="Ej: 5" required :disabled="loading">
                            </div>
                        </Transition>

                        <div class="form-group mb-4">
                            <label>Comentarios y Acuerdos de esta sesión</label>
                            <textarea v-model="form.comentarios" class="form-input" rows="3" placeholder="Describe los puntos clave..." :disabled="loading"></textarea>
                        </div>

                        <!-- RESULTADO DE LA VISITA -->
                        <div class="form-group bg-gray-50 p-4 rounded-xl border-2 border-red-100">
                            <label class="font-black text-red-800 text-xs uppercase tracking-widest mb-2 block">Resolución de la Visita</label>
                            <select v-model="form.resultado_visita" class="form-input font-bold" required :disabled="loading">
                                <option value="seguimiento">CONTINUAR SEGUIMIENTO</option>
                                <option value="compra">DECISIÓN DE COMPRA (Convertir a Cliente)</option>
                                <option value="rechazo">NO INTERESADO / INACTIVAR</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-4 border-t pt-6">
                    <button type="submit" class="btn-primary px-20 shadow-lg" :disabled="loading || !selectedCliente">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i> 
                        {{ loading ? 'Procesando...' : 'Finalizar Seguimiento' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL DE ÉXITO -->
        <Transition name="modal-fade">
            <div v-if="showSuccessModal" class="modal-overlay-custom">
                <div class="modal-content-success">
                    <div class="success-icon-wrapper">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 class="modal-title-success">¡Seguimiento Guardado!</h2>
                    <p class="modal-text-success">
                        La bitácora se ha actualizado correctamente.
                        <span v-if="form.resultado_visita === 'compra'" class="block mt-2 font-bold text-green-600">
                            <i class="fas fa-star"></i> El plantel ahora es CLIENTE oficial.
                        </span>
                    </p>
                    <button @click="goToHistory" class="btn-modal-success">Entendido, volver</button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const loadingOriginal = ref(false);
const showSuccessModal = ref(false);
const hasAttemptedSearch = ref(false);

// Prospectos (Visitas previas)
const searchQuery = ref('');
const clientesSuggestions = ref([]);
const selectedCliente = ref(null);
const searchingClients = ref(false);
let clientSearchTimeout = null;

// Libros
const bookInput = reactive({ id: null, titulo: '' });
const bookSuggestions = ref([]);
const selectedBooks = ref([]);
let bookSearchTimeout = null;

const form = reactive({
    original_visita_id: null,
    cliente_id: null,
    fecha: new Date().toISOString().split('T')[0],
    persona_entrevistada: '',
    cargo: '',
    libros_interes: '',
    material_entregado: false,
    material_cantidad: null,
    comentarios: '',
    proxima_visita: '',
    resultado_visita: 'seguimiento',
    proxima_accion: 'visita'
});

const initView = async () => {
    const id = route.params.id;
    if (id) {
        loadingOriginal.value = true;
        try {
            const res = await axios.get(`/visitas/${id}`);
            const data = res.data;
            form.original_visita_id = data.id;
            selectProspecto(data);
            form.persona_entrevistada = data.persona_entrevistada;
            form.cargo = data.cargo;
        } catch (e) {
            console.error("Error cargando origen:", e);
        } finally {
            loadingOriginal.value = false;
        }
    }
};

/**
 * BUSCADOR DE PROSPECTOS: Consulta la tabla 'visitas'
 */
const searchProspectos = async () => {
    if (selectedCliente.value && searchQuery.value !== selectedCliente.value.name) {
        selectedCliente.value = null;
        form.cliente_id = null;
    }
    
    if (searchQuery.value.trim().length < 3) {
        clientesSuggestions.value = [];
        hasAttemptedSearch.value = false;
        return;
    }

    clearTimeout(clientSearchTimeout);
    clientSearchTimeout = setTimeout(async () => {
        searchingClients.value = true;
        hasAttemptedSearch.value = true;
        try {
            // Llamada al nuevo endpoint del SearchController
            const res = await axios.get('/search/prospectos', { 
                params: { query: searchQuery.value } 
            });
            
            // LOG DE DEPURACIÓN: Revisa esto en la consola F12
            console.log("Resultados de búsqueda prospectos:", res.data);
            
            const results = res.data.data || res.data;
            clientesSuggestions.value = Array.isArray(results) ? results : [];
        } catch (e) { 
            console.error("Error en búsqueda:", e);
            clientesSuggestions.value = [];
        } 
        finally { searchingClients.value = false; }
    }, 400);
};

const selectProspecto = (v) => {
    selectedCliente.value = {
        id: v.cliente_id || null, 
        visita_id: v.id,
        name: v.nombre_plantel,
        direccion: v.direccion_plantel,
        contacto: v.director_plantel,
    };
    
    form.cliente_id = v.cliente_id || null;
    form.original_visita_id = v.id;
    searchQuery.value = v.nombre_plantel;
    clientesSuggestions.value = [];
    hasAttemptedSearch.value = false;
};

const searchBooks = async () => {
    if (bookInput.titulo.trim().length < 3) {
        bookSuggestions.value = [];
        return;
    }
    clearTimeout(bookSearchTimeout);
    bookSearchTimeout = setTimeout(async () => {
        try {
            const res = await axios.get('/search/libros', { params: { query: bookInput.titulo } });
            const results = res.data.data || res.data;
            bookSuggestions.value = Array.isArray(results) ? results : [];
        } catch (e) { 
            bookSuggestions.value = [];
        }
    }, 400);
};

const selectBook = (b) => {
    bookInput.id = b.id;
    bookInput.titulo = b.titulo;
    bookSuggestions.value = [];
};

const addBookToList = () => {
    if (!bookInput.id) return;
    if (!selectedBooks.value.find(b => b.id === bookInput.id)) {
        selectedBooks.value.push({ ...bookInput });
    }
    bookInput.id = null; bookInput.titulo = '';
};

const removeBook = (idx) => selectedBooks.value.splice(idx, 1);

const handleSubmit = async () => {
    loading.value = true;
    form.libros_interes = selectedBooks.value.map(b => b.titulo).join(', ');

    try {
        await axios.post('/visitas/seguimiento', form);
        showSuccessModal.value = true;
    } catch (e) {
        alert(e.response?.data?.message || "Error al procesar el seguimiento.");
    } finally {
        loading.value = false;
    }
};

const goToHistory = () => {
    showSuccessModal.value = false;
    router.push('/visitas');
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
};

onMounted(initView);
</script>

<style scoped>
.form-section { background: white; padding: 25px; border-radius: 16px; border: 1px solid #eef2f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.form-section-inner { background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; }
.section-title { color: var(--brand-red-dark); font-weight: 900; font-size: 1.1rem; border-bottom: 2px solid #fee2e2; padding-bottom: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

.autocomplete-list { 
    position: absolute; width: 100%; background: white; border: 1px solid #e2e8f0; z-index: 9999; 
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); max-height: 250px; overflow-y: auto; 
    list-style: none; padding: 0; margin: 4px 0 0; border-radius: 8px;
}
.autocomplete-list li { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
.autocomplete-list li:hover { background: #f8fafc; }

.status-pill { padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: 900; text-transform: uppercase; margin-right: 5px; }
.badge-custom { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; border: 1px solid transparent; }
.badge-orange { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
.badge-green { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }

/* MODAL DE ÉXITO */
.modal-overlay-custom { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 2000; }
.modal-content-success { background: white; padding: 40px; border-radius: 24px; width: 90%; max-width: 400px; text-align: center; }
.success-icon-wrapper { width: 70px; height: 70px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px; }
.btn-modal-success { width: 100%; background: #0f172a; color: white; padding: 14px; border-radius: 12px; font-weight: 700; border: none; cursor: pointer; }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>