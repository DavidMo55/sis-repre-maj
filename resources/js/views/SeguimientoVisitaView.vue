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
                <button @click="router.push('/visitas')" class="btn-secondary flex-row-centered gap-2 px-6" :disabled="loading">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="mt-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-1 space-y-6 min-w-0">
                        <div class="form-section shadow-premium" style="overflow: visible;">
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
                                    <i class="fas fa-university absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                                
                                <ul v-if="clientesSuggestions.length" class="autocomplete-list shadow-xl">
                                    <li v-for="v in clientesSuggestions" :key="v.id" @click="selectProspecto(v)">
                                        <div class="text-xs font-bold text-gray-800 text-break line-clamp-1 uppercase">{{ v.name }}</div>
                                        <div class="text-[10px] text-gray-500 text-break line-clamp-1">{{ v.direccion }}</div>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="selectedCliente" class="selected-client-card animate-fade-in p-4 rounded-2xl border-2 border-red-100 bg-red-50/50 overflow-hidden shadow-sm mt-2">
                                <h4 class="font-black text-red-900 text-xs text-break line-clamp-2 uppercase" :title="selectedCliente.name">
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

                        <div v-if="selectedCliente" class="form-section shadow-premium animate-fade-in overflow-hidden">
                            <div class="section-title"><i class="fas fa-history"></i> Historial Reciente</div>
                            
                            <div v-if="loadingHistory" class="text-center py-10">
                                <i class="fas fa-spinner fa-spin text-red-600 text-xl"></i>
                            </div>
                            
                            <div v-else class="timeline-container space-y-3 pr-1">
                                <div v-for="h in historialVisitas" :key="h.id" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm animate-fade-in relative border-l-4 border-l-red-500">
                                    <div class="flex justify-between items-start gap-2 mb-2">
                                        <span class="status-badge bg-red-100 text-red-800 uppercase">
                                           {{ formatDateShort(h.fecha) }}
                                        </span>
                                        <span class="text-[9px] font-black uppercase text-slate-400 text-right">
                                           {{ h.resultado_visita }}
                                        </span>
                                    </div>
                                    <div class="mt-1 border-t border-slate-50 pt-2">
                                        <p class="text-[11px] text-slate-600 italic leading-relaxed">
                                           "{{ h.comentarios || 'Sin observaciones.' }}"
                                        </p>
                                    </div>
                                </div>
                                <div v-if="!historialVisitas.length" class="text-center py-6 text-slate-400 italic text-xs">
                                    No hay registros previos.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="form-section shadow-premium" :class="{'opacity-50 pointer-events-none': !selectedCliente || loadingPrecarga}">
                            <div class="section-title"><i class="fas fa-calendar-plus"></i> Detalles de la Nueva Interacción</div>

                            <div class="filter-grid mb-6">
                                <div class="form-group">
                                    <label class="label-style">Fecha de Hoy</label>
                                    <input v-model="form.fecha" type="date" class="form-input" required :disabled="loading">
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Próxima Visita Estimada</label>
                                    <input v-model="form.proxima_visita" type="date" class="form-input" :disabled="loading">
                                </div>
                            </div>

                            <div class="filter-grid mb-6">
                                <div class="form-group">
                                    <label class="label-style">Persona Entrevistada</label>
                                    <input v-model="form.persona_entrevistada" type="text" class="form-input" placeholder="Nombre completo" required :disabled="loading">
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Cargo / Puesto</label>
                                    <input v-model="form.cargo" type="text" class="form-input" placeholder="Ej: Director" required :disabled="loading">
                                </div>
                            </div>

                            <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 mb-6" style="overflow: visible !important;">
                                <label class="label-mini mb-3">Libros de Interés</label>
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                    <div class="sm:col-span-10 relative">
                                        <input 
                                            v-model="bookInput.titulo" 
                                            type="text" 
                                            class="form-input" 
                                            placeholder="Buscar título..." 
                                            @input="searchBooks"
                                            @keydown.enter.prevent="addBookToList"
                                            autocomplete="off"
                                        >
                                        <ul v-if="bookSuggestions.length" class="autocomplete-list shadow-2xl">
                                            <li v-for="b in bookSuggestions" :key="b.id" @click="selectBook(b)" class="text-xs font-bold uppercase">
                                                {{ b.titulo }}
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <button type="button" @click="addBookToList" class="btn-primary w-full py-3.5 rounded-xl">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div v-if="selectedBooks.length" class="mt-4 flex flex-wrap gap-2">
                                    <div v-for="(item, idx) in selectedBooks" :key="idx" class="bg-white px-3 py-1.5 rounded-full border border-slate-200 shadow-sm text-[10px] font-black flex items-center gap-2 animate-fade-in uppercase">
                                        <span class="text-slate-700">{{ item.titulo }}</span>
                                        <button type="button" @click="selectedBooks.splice(idx, 1)" class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-6">
                                <label class="label-style">Comentarios y Acuerdos de hoy</label>
                                <textarea v-model="form.comentarios" class="form-input" rows="4" placeholder="¿Qué se trató en esta visita?" :disabled="loading"></textarea>
                            </div>

                            <div class="form-group bg-red-50/30 p-6 rounded-2xl border-2 border-red-100">
                                <label class="label-mini text-center !text-red-800 mb-3">Resolución de la Visita</label>
                                <select v-model="form.resultado_visita" class="form-input font-black text-center uppercase tracking-widest text-red-900" required :disabled="loading">
                                    <option value="seguimiento">CONTINUAR SEGUIMIENTO</option>
                                    <option value="compra">DECISIÓN DE COMPRA</option>
                                    <option value="rechazo">RECHAZADO</option>
                                </select>
                            </div>

                            <div class="mt-8 flex justify-end">
                                <button type="submit" class="btn-primary py-4 px-12 shadow-xl text-lg font-black tracking-widest uppercase rounded-2xl w-full sm:w-auto" :disabled="loading || !selectedCliente">
                                    <i class="fas" :class="loading ? 'fa-spinner fa-spin mr-2' : 'fa-save mr-2'"></i> 
                                    {{ loading ? 'Procesando...' : 'Guardar Seguimiento' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
/* HEREDADO DE REGISTRO DE GASTOS */
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: flex-end;
}

.status-badge {
    padding: 4px 10px;
    font-size: 0.7rem;
    font-weight: 800;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    letter-spacing: 0.5px;
}

.label-style { @apply text-xs font-black text-slate-500 uppercase tracking-tighter mb-2 block; }
.label-mini { @apply text-[10px] uppercase font-black text-slate-400 mb-1 block; }

.shadow-premium { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
.form-section { background: white; padding: 24px; border-radius: 24px; border: 1px solid #f1f5f9; }

.section-title { font-weight: 900; color: #a93339; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }

/* AUTOCOMPLETE & CONTAINERS */
.autocomplete-list {
    position: absolute;
    z-index: 1000;
    width: 100%;
    background: white;
    border: 1px solid #f1f5f9;
    border-radius: 16px;
    max-height: 250px;
    overflow-y: auto;
    margin-top: 8px;
}
.autocomplete-list li { padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f8fafc; transition: background 0.2s; }
.autocomplete-list li:hover { background: #fff1f2; }

.timeline-container {
    max-height: 500px;
    overflow-y: auto;
}

/* RESPONSIVIDAD */
@media (max-width: 640px) {
    .content-wrapper { padding: 15px !important; }
    .form-section { padding: 20px 15px !important; border-radius: 18px !important; }
    .form-input { font-size: 16px !important; }
    .detail-header-flex { flex-direction: column; align-items: flex-start; gap: 1rem; }
    .btn-secondary { width: 100%; }
}

.text-break { overflow-wrap: break-word; word-break: break-all; }
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

.timeline-container::-webkit-scrollbar { width: 4px; }
.timeline-container::-webkit-scrollbar-thumb { background: #fee2e2; border-radius: 10px; }
</style>

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

