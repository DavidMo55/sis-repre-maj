<template>
    <div class="content-wrapper p-2 md:p-6">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div class="header-info min-w-0">
                    <h1 v-if="loadingPrecarga" class="text-2xl font-black text-slate-300 animate-pulse uppercase">Sincronizando prospecto...</h1>
                    <h1 v-else-if="selectedCliente" class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight">
                        Seguimiento: {{ selectedCliente.name }}
                    </h1>
                    <h1 v-else class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight text-red-800">Seguimiento de Prospectos</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium mt-1 italic">
                        <i class="fas fa-info-circle text-red-600 mr-1"></i> 
                        Registra avances y entrega de muestras para instituciones en proceso de venta.
                    </p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm shrink-0 w-full sm:w-auto" :disabled="loading">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="mt-2">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- COLUMNA IZQUIERDA: IDENTIFICACIÓN E HISTORIAL -->
                    <div class="lg:col-span-1 space-y-6">
                        
                        <!-- 1. Identificar Plantel -->
                        <div class="info-card shadow-premium border-t-4 border-t-red-700 relative" style="overflow: visible;">
                            <div class="section-title">
                                <i class="fas fa-search text-red-700"></i> 1. Identificar Plantel
                            </div>
                            
                            <div v-if="!route.params.id" class="form-group relative mb-4">
                                <label class="label-mini">Buscar Prospecto / Cliente</label>
                                <div class="relative">
                                    <input 
                                        v-model="searchQuery" 
                                        type="text" 
                                        class="form-input pl-10 font-bold" 
                                        placeholder="Escribe nombre del plantel..."
                                        @input="searchProspectos"
                                        autocomplete="off"
                                    >
                                    <i class="fas fa-university absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                </div>
                                
                                <ul v-if="clientesSuggestions.length" class="autocomplete-list shadow-2xl border border-slate-100">
                                    <li v-for="v in clientesSuggestions" :key="v.id" @click="selectProspecto(v)" class="hover:bg-red-50 transition-colors border-b last:border-0 border-slate-50">
                                        <div class="flex justify-between items-start">
                                            <div class="text-xs font-black text-slate-800 uppercase line-clamp-1">{{ v.name }}</div>
                                            <span class="text-[7px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-black uppercase">{{ v.tipo }}</span>
                                        </div>
                                        <div class="text-[9px] text-slate-400 font-bold uppercase mt-1 italic line-clamp-1">{{ v.direccion || 'Ubicación pendiente' }}</div>
                                    </li>
                                </ul>
                            </div>

                            <!-- Tarjeta de Plantel Seleccionado -->
                            <div v-if="selectedCliente" class="selected-client-card animate-fade-in p-5 rounded-2xl border-2 border-red-100 bg-red-50/30 overflow-hidden shadow-sm">
                                <div class="flex justify-between items-start mb-3">
                                    <span class="text-[9px] font-black text-red-700 uppercase tracking-widest bg-white px-2 py-0.5 rounded-full shadow-sm border border-red-100">Expediente del Prospecto</span>
                                </div>
                                <h4 class="font-black text-slate-800 text-sm uppercase leading-tight mb-3">
                                    {{ selectedCliente.name }}
                                </h4>
                                <div class="space-y-2 border-t border-red-100 pt-3">
                                    <p class="text-[10px] text-slate-500 font-medium leading-relaxed">
                                        <i class="fas fa-map-marker-alt mr-1 text-red-400"></i> {{ selectedCliente.direccion || 'Sin dirección registrada.' }}
                                    </p>
                                    <div class="flex flex-col gap-1 mt-2">
                                        <p class="text-[10px] text-slate-600 font-black">
                                            <i class="fas fa-user-tie mr-1 text-red-400"></i> {{ selectedCliente.contacto || 'Sin contacto asignado' }}
                                        </p>
                                        <div class="flex justify-between items-center mt-1">
                                            <p class="text-[9px] font-mono font-bold text-slate-400">RFC: {{ selectedCliente.rfc || 'No registrado' }}</p>
                                            <p class="text-[8px] bg-red-600 text-white px-2 py-0.5 rounded font-black uppercase">En Seguimiento</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="!loadingPrecarga" class="text-center py-12 border-2 border-dashed border-slate-100 rounded-3xl bg-slate-50/30">
                                <i class="fas fa-search-location text-slate-200 text-4xl mb-3"></i>
                                <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Selecciona una institución arriba</p>
                            </div>
                        </div>

                        <!-- 2. Historial de Visitas -->
                        <div v-if="selectedCliente" class="info-card shadow-premium animate-fade-in border-t-4 border-t-slate-800">
                            <div class="section-title">
                                <i class="fas fa-history text-slate-800"></i> Historial Reciente
                            </div>
                            <div v-if="loadingHistory" class="text-center py-12">
                                <i class="fas fa-circle-notch fa-spin text-red-600 text-2xl mb-2"></i>
                                <p class="text-[9px] font-black text-slate-400 uppercase">Consultando bitácora...</p>
                            </div>
                            <div v-else class="timeline-container space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scroll">
                                <div v-for="h in historialVisitas" :key="h.id" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm animate-fade-in relative border-l-4" :class="getOutcomeBorder(h.resultado_visita)">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-[10px] font-black text-slate-800 bg-slate-50 px-2 py-1 rounded-lg border">
                                           <i class="far fa-calendar-alt mr-1"></i> {{ formatDateShort(h.fecha) }}
                                        </span>
                                        <span :class="getOutcomeClass(h.resultado_visita)" class="status-badge !text-[8px]">
                                           {{ h.resultado_visita }}
                                        </span>
                                    </div>
                                    <div class="mt-2 pt-2 border-t border-slate-50">
                                        <p class="text-[11px] text-slate-500 italic leading-relaxed line-clamp-3" :title="h.comentarios">
                                           "{{ h.comentarios || 'Visita sin observaciones específicas.' }}"
                                        </p>
                                    </div>
                                </div>
                                <div v-if="!historialVisitas.length" class="text-center py-10 text-slate-300 italic text-[10px] font-black uppercase tracking-widest">
                                    No hay registros previos.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: FORMULARIO -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="info-card shadow-premium transition-opacity duration-300" :class="{'opacity-40 pointer-events-none grayscale': !selectedCliente || loadingPrecarga}">
                            <div class="section-title">
                                <i class="fas fa-calendar-plus text-slate-800"></i> Detalles de la Nueva Interacción
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="form-group">
                                    <label class="label-style">Fecha de hoy *</label>
                                    <input v-model="form.fecha" type="date" class="form-input font-bold" required :disabled="loading">
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Próxima Visita Estimada</label>
                                    <input v-model="form.proxima_visita" type="date" class="form-input" :disabled="loading">
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Atendido por (Persona en plantel) *</label>
                                    <input v-model="form.persona_entrevistada" type="text" class="form-input font-bold" placeholder="Nombre completo" required :disabled="loading">
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Cargo / Departamento *</label>
                                    <input v-model="form.cargo" type="text" class="form-input font-bold" placeholder="Ej: Dirección, Coordinación..." required :disabled="loading">
                                </div>
                            </div>

                            <!-- SECCIÓN A: MATERIALES DE INTERÉS (FILTRADO POR SERIE) -->
                            <div class="bg-slate-50 p-6 rounded-[2.5rem] border border-slate-100 mb-6 relative" style="overflow: visible !important;">
                                <label class="label-mini mb-4 text-slate-600 font-black tracking-tighter"><i class="fas fa-eye mr-1 text-blue-500"></i> Apartado A: Libros de Interés del Plantel</label>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                    <div class="form-group">
                                        <label class="text-[9px] font-black uppercase text-slate-400 mb-1 block">Filtrar por Serie</label>
                                        <select v-model="selectedSerieIdA" class="form-input font-bold text-xs" @change="handleSerieChange('interest')">
                                            <option value="">Cualquier serie...</option>
                                            <option v-for="s in seriesFiltradas" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                            <option value="otro">VER TODAS LAS SERIES</option>
                                        </select>
                                    </div>
                                    <div class="form-group relative">
                                        <label class="text-[9px] font-black uppercase text-slate-400 mb-1 block">Buscar y Añadir Libro</label>
                                        <div class="relative">
                                            <input 
                                                v-model="interestInput.titulo" 
                                                type="text" 
                                                class="form-input pr-10 font-bold" 
                                                placeholder="Título o ISBN..." 
                                                @input="searchBooks($event, 'interest')"
                                                autocomplete="off"
                                            >
                                            <i v-if="searchingInterest" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                        <ul v-if="interestSuggestions.length" class="autocomplete-list shadow-2xl border border-slate-100">
                                            <li v-for="b in interestSuggestions" :key="b.id" @click="addMaterial(b, 'interest')" class="text-[11px] font-black uppercase text-slate-700 hover:bg-red-50 p-3">
                                                <div class="flex justify-between items-center w-full">
                                                    <span class="truncate">{{ b.titulo }}</span>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- TABLA APARTADO A -->
                                <div v-if="selectedInterestBooks.length" class="table-container mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                    <table class="w-full text-sm border-collapse">
                                        <thead class="bg-slate-100 text-slate-500 uppercase text-[9px] font-black tracking-widest">
                                            <tr>
                                                <th class="px-4 py-4 text-left">Título / Serie</th>
                                                <th class="px-4 py-4 text-center w-32">Tipo</th>
                                                <th class="px-4 py-4 text-center w-12"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50">
                                            <tr v-for="(item, idx) in selectedInterestBooks" :key="idx" class="hover:bg-slate-50/50 transition-colors group">
                                                <td class="px-4 py-4">
                                                    <p class="font-bold text-slate-800 uppercase text-[11px] leading-tight">{{ item.titulo }}</p>
                                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">{{ item.serie_nombre }}</span>
                                                </td>
                                                <td class="px-4 py-4 text-center">
                                                    <select v-model="item.tipo" class="form-input py-1 px-2 text-[10px] font-black uppercase bg-slate-50 border-slate-100 h-auto">
                                                        <option value="fisico">Físico</option>
                                                        <option value="digital">Digital</option>
                                                        <option value="paquete">Paquete</option>
                                                    </select>
                                                </td>
                                                <td class="px-4 py-4 text-center">
                                                    <button type="button" @click="selectedInterestBooks.splice(idx, 1)" class="text-slate-300 hover:text-red-600 transition-colors">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="mt-6 text-center py-6 border-2 border-dashed border-slate-200 rounded-3xl bg-white/50">
                                    <p class="text-[10px] font-bold text-slate-300 uppercase italic">Sin libros de interés agregados</p>
                                </div>
                            </div>

                             <br><br>
                            <!-- SECCIÓN B: MUESTRAS ENTREGADAS (INVENTARIO FÍSICO) -->
                            <div class="bg-red-50/30 p-6 rounded-[2.5rem] border border-red-100 mb-8 relative" style="overflow: visible !important;">
                                <label class="label-mini mb-4 text-red-800 font-black tracking-tighter"><i class="fas fa-box-open mr-1"></i> Muestras de Promoción Entregadas (Opcional)</label>
                                
                                <div class="form-group relative mb-4">
                                    <label class="text-[9px] font-black uppercase text-slate-400 mb-1 block">Buscar Libro para Entrega Física (Solo Material Físico)</label>
                                    <div class="relative">
                                        <input 
                                            v-model="deliveredInput.titulo" 
                                            type="text" 
                                            class="form-input pr-10 font-bold border-red-100 shadow-sm" 
                                            placeholder="Escribe título o ISBN para búsqueda global..." 
                                            @input="searchBooks($event, 'delivered')"
                                            autocomplete="off"
                                        >
                                        <i v-if="searchingDelivered" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-400"></i>
                                    </div>
                                    <ul v-if="deliveredSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                        <li v-for="b in deliveredSuggestions" :key="b.id" @click="addMaterial(b, 'delivered')" class="text-[11px] font-black uppercase text-slate-700 hover:bg-red-50 p-3">
                                            <div class="flex justify-between items-center w-full">
                                                <span class="truncate">{{ b.titulo }}</span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                
                                <!-- TABLA APARTADO B -->
                                <div v-if="selectedDeliveredBooks.length" class="table-container mt-6 overflow-hidden rounded-2xl border border-red-200 bg-white shadow-sm">
                                    <table class="w-full text-sm border-collapse">
                                        <thead class="bg-red-50 text-red-800 uppercase text-[9px] font-black tracking-widest">
                                            <tr>
                                                <th class="px-6 py-4 text-left">Título / Muestra</th>
                                                <th class="px-6 py-4 text-center w-24">Cantidad</th>
                                                <th class="px-6 py-4 text-center w-16"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-red-50">
                                            <tr v-for="(item, idx) in selectedDeliveredBooks" :key="idx" class="hover:bg-red-50/20 transition-colors group">
                                                <td class="px-6 py-4">
                                                    <p class="font-bold text-slate-800 uppercase text-[11px] leading-tight">{{ item.titulo }}</p>
                                                    <span class="text-[8px] font-black text-red-400 uppercase tracking-widest">Entrega Física</span>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <div class="flex items-center justify-center">
                                                        <input v-model.number="item.cantidad" type="number" min="1" class="w-16 bg-red-50/50 border border-red-100 text-center font-black text-red-700 text-sm py-1.5 rounded-lg focus:ring-red-200">
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <button type="button" @click="selectedDeliveredBooks.splice(idx, 1)" class="text-slate-300 hover:text-red-600 transition-colors">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="mt-6 text-center py-8 border-2 border-dashed border-red-100 rounded-3xl bg-white/50">
                                    <p class="text-[10px] font-black text-red-300 uppercase tracking-widest">No se registraron muestras físicas hoy</p>
                                </div>
                            </div>
                             <br><br>

                            <div class="form-group mb-8">
                                <label class="label-style">Resultados de la reunión y acuerdos técnicos</label>
                                <textarea v-model="form.comentarios" class="form-input font-medium" rows="5" placeholder="Escribe los puntos clave tratados en la reunión..." :disabled="loading"></textarea>
                            </div>
                           
                            <div class="form-group bg-slate-900 p-8 rounded-[2.5rem] border-2 border-slate-800 shadow-xl">
                                <label class="label-mini text-center !text-slate-400 mb-4 tracking-[0.2em]">Resolución de la Sesión</label>
                                <select v-model="form.resultado_visita" class="form-input font-black text-center uppercase tracking-widest bg-slate-800 border-none text-white text-lg py-4 rounded-2xl cursor-pointer hover:bg-slate-700 transition-colors" required :disabled="loading">
                                    <option value="seguimiento">Continuar Seguimiento</option>
                                    <option value="compra">Decisión de Compra</option>
                                    <option value="rechazo">Rechazado</option>
                                </select>
                            </div>

                            <div class="mt-10 flex justify-end">
                                <button type="submit" class="btn-primary-action px-16 py-5 shadow-2xl w-full sm:w-auto" :disabled="loading || !selectedCliente">
                                    <i class="fas" :class="loading ? 'fa-spinner fa-spin mr-2' : 'fa-save mr-2'"></i> 
                                    {{ loading ? 'Sincronizando...' : 'Postear Seguimiento' }}
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
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const loadingPrecarga = ref(false);
const loadingHistory = ref(false);
const searchingInterest = ref(false);
const searchingDelivered = ref(false);

const searchQuery = ref('');
const clientesSuggestions = ref([]);
const selectedCliente = ref(null);
const historialVisitas = ref([]);
const allSeries = ref([]);

// Secciones de materiales
const interestSuggestions = ref([]);
const deliveredSuggestions = ref([]);
const selectedInterestBooks = ref([]);
const selectedDeliveredBooks = ref([]);

// IDs de series seleccionadas
const selectedSerieIdA = ref(''); 

const interestInput = reactive({ titulo: '' });
const deliveredInput = reactive({ titulo: '' });

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
 * Lógica de filtrado de series basado en los niveles educativos del plantel
 */
const seriesFiltradas = computed(() => {
    if (!selectedCliente.value || !selectedCliente.value.nivel_educativo) return [];
    const niveles = selectedCliente.value.nivel_educativo.split(',').map(n => n.trim());
    
    return allSeries.value.filter(serie => {
        // Necesitaríamos que nivelesCatalog estuviera disponible para mapear nombres a IDs
        // o que las series tengan el nombre del nivel. Asumimos coincidencia por ahora.
        return true; // Por simplicidad en este ajuste mostramos todas si no hay mapeo claro
    });
});

const verificarPrecarga = async () => {
    const idParam = route.params.id;
    if (!idParam) return;
    loadingPrecarga.value = true;
    try {
        const response = await axios.get(`/visitas/${idParam}`);
        const data = response.data;
        
        selectedCliente.value = data.cliente || {
            id: data.cliente_id,
            name: data.nombre_plantel,
            direccion: data.direccion_plantel,
            contacto: data.director_plantel,
            rfc: data.rfc_plantel,
            tipo: 'PROSPECTO',
            nivel_educativo: data.nivel_educativo_plantel
        };

        form.cliente_id = data.cliente_id;
        form.persona_entrevistada = data.persona_entrevistada;
        form.cargo = data.cargo;
        searchQuery.value = selectedCliente.value.name;
        fetchHistorial(data.cliente_id);
    } catch (e) {
        console.error("Error en precarga de seguimiento:", e);
    } finally {
        loadingPrecarga.value = false;
    }
};

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
        const res = await axios.get(`/visitas`, { params: { search: searchQuery.value } });
        const data = res.data.data || res.data;
        historialVisitas.value = Array.isArray(data) ? data.filter(v => v.cliente_id == id) : [];
    } catch (e) { 
        historialVisitas.value = []; 
    } finally { 
        loadingHistory.value = false; 
    }
};

const handleSerieChange = (type) => {
    if (type === 'interest') {
        interestInput.titulo = ''; 
        interestSuggestions.value = [];
    }
};

/**
 * BUSCADOR DE LIBROS DUAL
 */
const searchBooks = (event, type) => {
    const val = event.target.value;
    if (val.length < 3) {
        if (type === 'interest') interestSuggestions.value = [];
        else deliveredSuggestions.value = [];
        return;
    }
    
    if (type === 'interest') searchingInterest.value = true;
    else searchingDelivered.value = true;

    clearTimeout(searchTimeout);
    
    const serieId = type === 'interest' 
        ? (selectedSerieIdA.value === 'otro' ? null : selectedSerieIdA.value)
        : null; 

    searchTimeout = setTimeout(async () => {
        try {
            const res = await axios.get('/search/libros', { 
                params: { 
                    query: val,
                    serie_id: serieId 
                } 
            });
            
            if (type === 'interest') {
                interestSuggestions.value = res.data;
            } else {
                // FILTRO: Solo materiales físicos
                deliveredSuggestions.value = res.data.filter(book => book.type !== 'digital');
            }
        } catch (e) { 
            console.error("Error al buscar libros:", e); 
        } finally {
            searchingInterest.value = false;
            searchingDelivered.value = false;
        }
    }, 400);
};

const addMaterial = (book, type) => {
    const serie = allSeries.value.find(s => s.id == book.serie_id);
    const serieNombre = serie ? serie.nombre : 'Sin Serie';

    if (type === 'interest') {
        if (!selectedInterestBooks.value.find(b => b.id === book.id)) {
            selectedInterestBooks.value.push({ 
                id: book.id, 
                titulo: book.titulo,
                serie_nombre: serieNombre,
                tipo: 'fisico'
            });
        }
        interestInput.titulo = ''; interestSuggestions.value = [];
    } else {
        if (!selectedDeliveredBooks.value.find(b => b.id === book.id)) {
            selectedDeliveredBooks.value.push({ 
                id: book.id, 
                titulo: book.titulo, 
                serie_nombre: serieNombre,
                cantidad: 1 
            });
        }
        deliveredInput.titulo = ''; deliveredSuggestions.value = [];
    }
};

const handleSubmit = async () => {
    if (!selectedCliente.value) return;
    loading.value = true;

    const materialData = {
        interes: selectedInterestBooks.value.map(b => ({
            titulo: b.titulo,
            tipo: b.tipo
        })),
        entregado: selectedDeliveredBooks.value.map(b => ({
            titulo: b.titulo,
            cantidad: b.cantidad
        }))
    };

    form.libros_interes = materialData; // Laravel Cast maneja el JSON
    
    try {
        await axios.post('/visitas/seguimiento', form);
        router.push('/visitas');
    } catch (e) { 
        alert(e.response?.data?.message || "Fallo al guardar el seguimiento.");
    } finally { 
        loading.value = false; 
    }
};

const formatDateShort = (d) => {
    if (!d) return '---';
    const date = new Date(d);
    return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }).toUpperCase();
};

const getOutcomeClass = (outcome) => {
    const base = 'status-badge ';
    if (outcome === 'compra') return base + 'bg-green-100 text-green-700 border border-green-200';
    if (outcome === 'rechazo') return base + 'bg-red-100 text-red-700 border border-red-200';
    return base + 'bg-orange-100 text-orange-700 border border-orange-200';
};

const getOutcomeBorder = (outcome) => {
    if (outcome === 'compra') return 'border-l-green-500';
    if (outcome === 'rechazo') return 'border-l-red-500';
    return 'border-l-orange-500';
};

onMounted(async () => {
    verificarPrecarga();
    try {
        const resSer = await axios.get('/search/series');
        allSeries.value = resSer.data;
    } catch (e) { console.error(e); }
});
</script>

<style scoped>
.info-card { background: white; padding: 25px; border-radius: 32px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #1e293b; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }

.label-style { @apply text-xs font-black text-slate-500 uppercase tracking-tighter mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-widest; }

.form-input { width: 100%; padding: 12px 16px; border-radius: 14px; border: 2px solid #f1f5f9; font-weight: 700; color: #334155; background: #fafbfc; transition: all 0.2s; }
.form-input:focus { border-color: #a93339; background: white; outline: none; }

.btn-primary-action { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 15px 35px rgba(169, 51, 57, 0.25); transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.05em; }
.btn-primary-action:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 20px 40px rgba(169, 51, 57, 0.35); }

.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; text-transform: uppercase; }

.autocomplete-list { position: absolute; z-index: 2000; width: 100%; background: white; border-radius: 16px; max-height: 250px; overflow-y: auto; list-style: none; padding: 10px; margin-top: 8px; }
.autocomplete-list li { cursor: pointer; border-radius: 10px; margin-bottom: 4px; padding: 12px; }

.timeline-container::-webkit-scrollbar { width: 4px; }
.timeline-container::-webkit-scrollbar-thumb { background: #f1f5f9; border-radius: 10px; }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05); }

.table-container { box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }

@media (max-width: 640px) {
    .info-card { padding: 20px; }
}

select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; appearance: none; }
</style>