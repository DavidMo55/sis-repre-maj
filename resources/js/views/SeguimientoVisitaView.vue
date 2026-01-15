<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div class="header-info overflow-hidden">
                    <h1 v-if="loadingPrecarga">Cargando datos del prospecto...</h1>
                    <h1 v-else-if="selectedCliente" class="text-break line-clamp-2" :title="selectedCliente.name">
                        Seguimiento: {{ selectedCliente.name }}
                    </h1>
                    <h1 v-else>Registro de Visita Subsecuente</h1>
                    <p class="text-truncate">Actualiza el avance de negociación basándote en registros previos.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary flex-shrink-0" :disabled="loading">
                    <i class="fas fa-arrow-left mr-2"></i> Volver
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="mt-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1 space-y-6 min-w-0">
                        <div class="form-section" style="overflow: visible;">
                            <div class="section-title">
                                <i class="fas fa-search"></i> 1. Identificar Plantel
                            </div>
                            
                            <div v-if="!route.params.id" class="form-group relative">
                                <div class="relative">
                                    <input 
                                        v-model="searchQuery" 
                                        type="text" 
                                        class="form-input pl-10" 
                                        placeholder="Nombre del plantel..."
                                        @input="searchProspectos"
                                        autocomplete="off"
                                    >
                                    <i class="fas fa-history absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                                
                                <ul v-if="clientesSuggestions.length" class="autocomplete-list shadow-xl">
                                    <li v-for="v in clientesSuggestions" :key="v.id" @click="selectProspecto(v)">
                                        <div class="text-xs font-bold text-gray-800 text-break line-clamp-1">{{ v.name }}</div>
                                        <div class="text-[10px] text-gray-500 text-break line-clamp-1">{{ v.direccion }}</div>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="selectedCliente" class="selected-client-card animate-fade-in p-4 rounded-xl border-2 border-red-100 bg-red-50 overflow-hidden shadow-sm">
                                <h4 class="font-black text-red-900 text-sm text-break line-clamp-2 uppercase" :title="selectedCliente.name">
                                    {{ selectedCliente.name }}
                                </h4>
                                <div class="mt-3 space-y-2 border-t border-red-200 pt-2">
                                    <p class="text-[10px] text-gray-600 text-break line-clamp-2" :title="selectedCliente.direccion">
                                        <i class="fas fa-map-marker-alt mr-1 text-red-400"></i> {{ selectedCliente.direccion }}
                                    </p>
                                    <p class="text-[10px] text-gray-600 font-bold text-break line-clamp-1">
                                        <i class="fas fa-user-tie mr-1 text-red-400"></i> {{ selectedCliente.contacto }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedCliente" class="form-section animate-fade-in overflow-hidden">
                            <div class="section-title text-xs"><i class="fas fa-history"></i> Historial Reciente</div>
                            
                            <div v-if="loadingHistory" class="text-center py-6">
                                <i class="fas fa-spinner fa-spin text-red-600"></i>
                            </div>
                            
                            <div v-else class="timeline-container space-y-3">
                                <div v-for="h in historialVisitas" :key="h.id" class="timeline-item bg-white p-3 rounded-lg border border-gray-100 shadow-sm overflow-hidden mb-3">
                                    <div class="flex justify-between items-center mb-2 gap-4">
                                        <span class="text-[10px] font-black text-red-700 bg-red-50 px-2 py-1 rounded border border-red-100 shrink-0">
                                            {{ formatDateShort(h.fecha) }}
                                        </span>
                                        <span class="text-[9px] font-bold uppercase truncate text-gray-400">
                                            {{ h.resultado_visita }}
                                        </span>
                                    </div>
                                    <div class="mt-1 border-t border-gray-50 pt-2">
                                        <p class="text-[11px] text-gray-700 italic text-break leading-normal">
                                            "{{ h.comentarios || 'Sin observaciones.' }}"
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="form-section" :class="{'opacity-50 pointer-events-none': !selectedCliente || loadingPrecarga}">
                            <div class="section-title"><i class="fas fa-calendar-plus"></i> Detalles de la Nueva Interacción</div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label>Fecha de Hoy</label>
                                    <input v-model="form.fecha" type="date" class="form-input" required :disabled="loading">
                                </div>
                                <div class="form-group">
                                    <label>Próxima Visita Estimada</label>
                                    <input v-model="form.proxima_visita" type="date" class="form-input" :disabled="loading">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label>Persona Entrevistada</label>
                                    <input v-model="form.persona_entrevistada" type="text" class="form-input" placeholder="Nombre completo" required :disabled="loading">
                                </div>
                                <div class="form-group">
                                    <label>Cargo</label>
                                    <input v-model="form.cargo" type="text" class="form-input" placeholder="Ej: Director" required :disabled="loading">
                                </div>
                            </div>

                            <div class="form-section-inner mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200" style="overflow: visible !important;">
                                <label class="font-bold text-gray-700 block mb-2 text-xs uppercase tracking-widest">Libros de Interés</label>
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
                                    <div class="md:col-span-10 relative">
                                        <input 
                                            v-model="bookInput.titulo" 
                                            type="text" 
                                            class="form-input" 
                                            placeholder="Buscar título..." 
                                            @input="searchBooks"
                                            @keydown.enter.prevent="addBookToList"
                                            autocomplete="off"
                                        >
                                        <ul v-if="bookSuggestions.length" class="autocomplete-list">
                                            <li v-for="b in bookSuggestions" :key="b.id" @click="selectBook(b)">{{ b.titulo }}</li>
                                        </ul>
                                    </div>
                                    <div class="md:col-span-2">
                                        <button type="button" @click="addBookToList" class="btn-primary w-full py-3">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="selectedBooks.length" class="mt-3 flex flex-wrap gap-2">
                                    <div v-for="(item, idx) in selectedBooks" :key="idx" class="bg-white px-3 py-1 rounded-full border text-[10px] font-bold flex items-center gap-2">
                                        <span class="text-gray-700">{{ item.titulo }}</span>
                                        <button type="button" @click="selectedBooks.splice(idx, 1)" class="text-red-500"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-6">
                                <label>Comentarios y Acuerdos de hoy</label>
                                <textarea v-model="form.comentarios" class="form-input" rows="5" placeholder="¿Qué se trató en esta visita?" :disabled="loading"></textarea>
                            </div>

                            <div class="form-group bg-gray-50 p-5 rounded-xl border-2 border-red-100">
                                <label class="font-black text-red-800 text-xs uppercase tracking-widest mb-3 block text-center">Resolución</label>
                                <select v-model="form.resultado_visita" class="form-input font-bold text-center" required :disabled="loading">
                                    <option value="seguimiento">CONTINUAR SEGUIMIENTO</option>
                                    <option value="compra">DECISIÓN DE COMPRA</option>
                                    <option value="rechazo">RECHAZADO</option>
                                </select>
                            </div>

                            <div class="mt-8 flex justify-end">
                                <button type="submit" class="btn-primary py-3 px-16 shadow-xl" :disabled="loading || !selectedCliente">
                                    <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save mr-2'"></i> 
                                    Guardar Seguimiento
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const loadingPrecarga = ref(false);
const loadingHistory = ref(false);

const searchQuery = ref('');
const clientesSuggestions = ref([]);
const selectedCliente = ref(null);
const historialVisitas = ref([]);
const bookSuggestions = ref([]);
const selectedBooks = ref([]);
const bookInput = reactive({ id: null, titulo: '' });
let searchTimeout = null;

const form = reactive({
    cliente_id: null,
    fecha: new Date().toISOString().split('T')[0],
    persona_entrevistada: '',
    cargo: '',
    comentarios: '',
    libros_interes: '',
    proxima_visita: '',
    resultado_visita: 'seguimiento'
});

/**
 * LÓGICA DE PRECARGA POR ID
 */
const verificarPrecarga = async () => {
    const idParam = route.params.id;
    if (!idParam) return;

    loadingPrecarga.value = true;
    try {
        const response = await axios.get(`/visitas/${idParam}`);
        const data = response.data;

        selectedCliente.value = data.cliente;
        form.cliente_id = data.cliente_id;
        form.persona_entrevistada = data.persona_entrevistada;
        form.cargo = data.cargo;
        searchQuery.value = data.cliente.name;

        fetchHistorial(data.cliente_id);
    } catch (e) {
        console.error("Error en precarga:", e);
    } finally {
        loadingPrecarga.value = false;
    }
};

/**
 * BUSCADORES Y SELECCIÓN
 */
const searchProspectos = async () => {
    if (searchQuery.value.length < 3) {
        clientesSuggestions.value = [];
        return;
    }
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        try {
            const res = await axios.get('/search/prospectos', { params: { query: searchQuery.value } });
            clientesSuggestions.value = res.data;
        } catch (e) { console.error(e); }
    }, 400);
};

const selectProspecto = async (cliente) => {
    selectedCliente.value = cliente;
    form.cliente_id = cliente.id;
    searchQuery.value = cliente.name;
    clientesSuggestions.value = [];
    fetchHistorial(cliente.id);
};

const fetchHistorial = async (id) => {
    loadingHistory.value = true;
    try {
        const res = await axios.get(`/clientes/${id}/historial`);
        historialVisitas.value = res.data;
    } catch (e) { historialVisitas.value = []; }
    finally { loadingHistory.value = false; }
};

const searchBooks = async () => {
    if (bookInput.titulo.length < 3) { bookSuggestions.value = []; return; }
    try {
        const res = await axios.get('/search/libros', { params: { query: bookInput.titulo } });
        bookSuggestions.value = res.data;
    } catch (e) { console.error(e); }
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

const handleSubmit = async () => {
    loading.value = true;
    form.libros_interes = selectedBooks.value.map(b => b.titulo).join(', ');
    try {
        await axios.post('/visitas/seguimiento', form);
        router.push('/visitas');
    } catch (e) { alert("Error al guardar"); }
    finally { loading.value = false; }
};

const formatDateShort = (d) => {
    if (!d) return '---';
    const p = d.split('T')[0].split('-');
    return `${p[2]}/${p[1]}`;
};

onMounted(() => {
    verificarPrecarga();
});
</script>

<style scoped>
/* SOLUCIÓN AL TEXTO LARGO */
.text-break {
    overflow-wrap: break-word;
    word-break: break-all;
    display: block;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* DISEÑO DE UI */
.form-section { 
    background: white; 
    padding: 24px; 
    border-radius: 16px; 
    border: 1px solid #e2e8f0; 
    min-width: 0; 
}

.section-title { font-weight: 900; color: #b91c1c; margin-bottom: 20px; border-bottom: 2px solid #fee2e2; padding-bottom: 8px; display: flex; align-items: center; gap: 10px; }

.timeline-container {
    max-height: 450px;
    overflow-y: auto;
    padding-right: 5px;
}

.timeline-item {
    border-left: 4px solid #b91c1c;
    min-width: 0;
}

.autocomplete-list {
    position: absolute;
    z-index: 1000;
    width: 100%;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    max-height: 200px;
    overflow-y: auto;
    margin-top: 5px;
}

.autocomplete-list li { padding: 10px; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
.autocomplete-list li:hover { background: #fdf2f2; }

.form-section-inner { background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.timeline-container::-webkit-scrollbar { width: 4px; }
.timeline-container::-webkit-scrollbar-thumb { background: #fee2e2; border-radius: 10px; }
</style>