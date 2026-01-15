<template>
    <div class="content-wrapper p-2 md:p-6">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div class="header-info min-w-0">
                    <h1 v-if="loadingPrecarga" class="text-2xl font-black text-slate-300 animate-pulse uppercase">Sincronizando expediente...</h1>
                    <h1 v-else-if="selectedCliente" class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight">
                        Seguimiento: {{ selectedCliente.name }}
                    </h1>
                    <h1 v-else class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight text-red-800">Seguimiento de Prospectos</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium mt-1 uppercase tracking-tighter">Actualiza avances, materiales y estatus de negociación.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm shrink-0 w-full sm:w-auto" :disabled="loading">
                    <i class="fas fa-arrow-left"></i> Volver al Historial
                </button>
            </div>

            <!-- Alerta de Error -->
            <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl animate-fade-in shadow-sm">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-red-600 mt-1"></i>
                    <div>
                        <p class="text-red-800 font-black uppercase text-[10px]">Error en validación</p>
                        <p class="text-red-600 text-xs mt-1 font-medium">{{ errorMessage }}</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- COLUMNA IZQUIERDA: IDENTIFICACIÓN E HISTORIAL -->
                    <div class="lg:col-span-1 space-y-6">
                        
                        <!-- 1. Buscador y Datos del Plantel -->
                        <div class="form-section shadow-premium border-t-4 border-t-red-700 relative" style="overflow: visible;">
                            <div class="section-title">
                                <i class="fas fa-search text-red-700"></i> 1. Identificar Plantel
                            </div>
                            
                            <div v-if="!route.params.id" class="form-group relative mb-6">
                                <label class="label-mini">Buscar Prospecto Activo</label>
                                <div class="relative">
                                    <input v-model="searchQuery" type="text" class="form-input pl-10 font-bold" placeholder="Escribe nombre del plantel..." @input="searchProspectos" autocomplete="off">
                                    <i class="fas fa-university absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                </div>
                                <ul v-if="clientesSuggestions.length" class="autocomplete-list shadow-2xl border border-slate-100">
                                    <li v-for="v in clientesSuggestions" :key="v.id" @click="selectProspecto(v)" class="hover:bg-red-50 transition-colors border-b last:border-0 border-slate-50">
                                        <div class="flex justify-between items-start">
                                            <div class="text-xs font-black text-slate-800 uppercase line-clamp-1">{{ v.name }}</div>
                                            <span class="text-[7px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-black uppercase">{{ v.tipo }}</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="selectedCliente" class="space-y-6 animate-fade-in">
                                <!-- Datos Identidad -->
                                <div class="form-group">
                                    <label class="label-style">RFC Confirmado</label>
                                    <input v-model="form.plantel.rfc" type="text" class="form-input uppercase font-mono font-black text-red-900 border-red-100" required>
                                </div>

                                <div class="form-group">
                                    <label class="label-style">Nombre del Director / Coordinador</label>
                                    <input v-model="form.plantel.director" type="text" class="form-input font-bold" required>
                                </div>
                                
                                <!-- GPS y Ubicación -->
                                <div class="bg-blue-50 p-6 rounded-3xl border border-blue-100 shadow-inner">
                                    <label class="label-mini text-blue-700 font-black mb-3 block text-center uppercase tracking-widest">Geolocalización</label>
                                    <button type="button" @click="getLocation" class="btn-primary bg-blue-600 border-none w-full py-4 rounded-2xl shadow-lg shadow-blue-100 active:scale-95 transition-all flex items-center justify-center gap-2" :disabled="gettingLocation">
                                        <i class="fas" :class="gettingLocation ? 'fa-spinner fa-spin' : 'fa-crosshairs'"></i>
                                        <span class="font-black uppercase tracking-widest text-[11px]">{{ form.plantel.latitud ? 'GPS Actualizado ✓' : 'Capturar GPS' }}</span>
                                    </button>
                                </div>

                                <div class="form-group">
                                    <label class="label-style">Estado / Región</label>
                                    <select v-model="form.plantel.estado_id" class="form-input font-bold" required>
                                        <option value="">Seleccionar...</option>
                                        <option v-for="e in estados" :key="e.id" :value="e.id">{{ e.estado }}</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="label-style">Dirección Completa</label>
                                    <textarea v-model="form.plantel.direccion" class="form-input font-medium" rows="2" required></textarea>
                                </div>

                                <!-- Contacto -->
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="form-group">
                                        <label class="label-style">Celular / Teléfono</label>
                                        <input v-model="form.plantel.telefono" type="tel" class="form-input font-bold" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="label-style">Correo Electrónico</label>
                                        <input v-model="form.plantel.email" type="email" class="form-input font-bold" required>
                                    </div>
                                </div>

                                <!-- Niveles -->
                                <div class="form-group">
                                    <label class="label-style">Niveles Educativos</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label v-for="nivel in nivelesCatalog" :key="nivel.id" 
                                            class="flex items-center gap-2 p-3 border rounded-2xl cursor-pointer text-[10px] font-black uppercase transition-all"
                                            :class="form.plantel.niveles.includes(nivel.id) ? 'bg-red-50 border-red-200 text-red-700 shadow-sm' : 'bg-white border-slate-100 text-slate-400'"
                                        >
                                            <input type="checkbox" :value="nivel.id" v-model="form.plantel.niveles" class="w-3 h-3 accent-red-600">
                                            {{ nivel.nombre }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="!loadingPrecarga" class="text-center py-12 border-2 border-dashed border-slate-100 rounded-3xl bg-slate-50/30">
                                <i class="fas fa-search-location text-slate-200 text-4xl mb-3"></i>
                                <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Selecciona una institución arriba</p>
                            </div>
                        </div>

                        <!-- 2. Historial de Visitas -->
                        <div v-if="selectedCliente" class="form-section shadow-premium border-t-4 border-t-slate-800">
                            <div class="section-title">
                                <i class="fas fa-history text-slate-800"></i> Historial Reciente
                            </div>
                            <div v-if="loadingHistory" class="text-center py-10"><i class="fas fa-spinner fa-spin text-red-600"></i></div>
                            <div v-else class="space-y-4 max-h-[300px] overflow-y-auto pr-2 custom-scroll">
                                <div v-for="h in historialVisitas" :key="h.id" class="p-4 bg-white border-l-4 rounded-xl shadow-sm border-slate-200" :class="getOutcomeBorder(h.resultado_visita)">
                                    <div class="flex justify-between mb-1">
                                        <span class="text-[9px] font-black text-slate-400 uppercase">{{ formatDateShort(h.fecha) }}</span>
                                        <br><br>
                                        <span :class="getOutcomeClass(h.resultado_visita)" class="status-badge !text-[7px]">{{ h.resultado_visita }}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 italic line-clamp-2">"{{ h.comentarios || 'Sin comentarios.' }}"</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: FORMULARIO DE INTERACCIÓN -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="form-section shadow-premium border-t-4 border-t-slate-800 transition-opacity" :class="{'opacity-40 pointer-events-none': !selectedCliente}">
                            <div class="section-title">
                                <i class="fas fa-calendar-plus text-slate-800"></i> 2. Detalles de la Nueva Interacción
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="form-group">
                                    <label class="label-style">Fecha de la Visita *</label>
                                    <input v-model="form.visita.fecha" type="date" class="form-input font-bold" required>
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Persona Entrevistada *</label>
                                    <input v-model="form.visita.persona_entrevistada" type="text" class="form-input font-bold" placeholder="¿Quién atendió?" required>
                                </div>
                            </div>

                            <div class="form-group mb-8">
                                <label class="label-style">Cargo / Puesto de la Persona *</label>
                                <select v-model="form.visita.cargo" class="form-input font-bold" required>
                                    <option value="Director/Coordinador">Director/Coordinador</option>
                                    <option value="Subdirector">Subdirector</option>
                                    <option value="Jefe de Departamento">Jefe de Departamento</option>
                                    <option value="Profesor">Profesor</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>

                                </div>
                                <div class="form-section ">
                            <!-- APARTADO A: INTERÉS -->
                            <div class="bg-slate-50 p-6 rounded-[2.5rem] border border-slate-100 mb-6 relative">
                                <label class="label-mini mb-4 text-slate-600 font-black"><i class="fas fa-eye mr-1 text-blue-500"></i> Apartado A: Libros de Interés (Venta)</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                    <select v-model="selectedSerieIdA" class="form-input font-bold text-xs" @change="handleSerieChange('interest')">
                                        <option value="">Cualquier serie...</option>
                                        <option v-for="s in seriesFiltradas" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                        <option value="otro">VER TODAS</option>
                                    </select>
                                    <div class="relative">
                                        <input v-model="interestInput.titulo" type="text" class="form-input pr-10 font-bold" placeholder="Buscar libro..." @input="searchBooks($event, 'interest')">
                                        <i v-if="searchingInterest" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <ul v-if="interestSuggestions.length" class="autocomplete-list shadow-2xl border border-slate-100">
                                            <li v-for="b in interestSuggestions" :key="b.id" @click="addMaterial(b, 'interest')" class="text-[11px] font-black uppercase text-slate-700 hover:bg-red-50 p-3 flex justify-between">
                                                <span>{{ b.titulo }}</span>
                                                <span class="text-[7px] bg-slate-100 px-1.5 rounded">{{ b.type }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div v-if="selectedInterestBooks.length" class="table-modern-wrapper">
                                    <table class="table-modern">
                                        <thead>
                                            <tr>
                                                <th>Material / Serie</th>
                                                <th class="text-center w-36">Formato</th>
                                                <th class="w-12"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, idx) in selectedInterestBooks" :key="idx">
                                                <td>
                                                    <p class="font-black text-slate-800 uppercase text-[11px] leading-tight">{{ item.titulo }}</p>
                                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">{{ item.serie_nombre }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <select v-model="item.tipo" class="form-input py-1 px-2 text-[10px] font-black uppercase bg-slate-50 border-slate-100 h-auto">
                                                        <option v-if="item.original_type === 'digital'" value="digital">Digital</option>
                                                        <template v-else>
                                                            <option value="fisico">Físico</option>
                                                            <option value="paquete">Paquete</option>
                                                            <option value="por_revisar">Por Revisar</option>
                                                        </template>
                                                    </select>
                                                </td>
                                                <td class="text-center">
                                                    <button @click="selectedInterestBooks.splice(idx, 1)" class="text-slate-300 hover:text-red-600"><i class="fas fa-trash-alt"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="text-center py-6 border-2 border-dashed border-slate-200 rounded-3xl bg-white/50 text-[10px] font-black text-slate-300 uppercase italic">Sin libros añadidos</div>
                            </div>
                                </div>
                                <div class="form-section ">
                            <!-- APARTADO B: PROMOCIÓN -->
                            <div class="bg-red-50/30 p-6 rounded-[2.5rem] border border-red-100 mb-8 relative">
                                <label class="label-mini mb-4 text-red-800 font-black"><i class="fas fa-box-open mr-1"></i> Apartado B: Muestras de Promoción Entregadas</label>
                                <div class="relative mb-4">
                                    <input v-model="deliveredInput.titulo" type="text" class="form-input pr-10 font-bold border-red-100 shadow-sm" placeholder="Buscar libros de PROMOCIÓN física..." @input="searchBooks($event, 'delivered')">
                                    <i v-if="searchingDelivered" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-400"></i>
                                    <ul v-if="deliveredSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                        <li v-for="b in deliveredSuggestions" :key="b.id" @click="addMaterial(b, 'delivered')" class="text-[11px] font-black uppercase text-slate-700 hover:bg-red-50 p-3">
                                            <span>{{ b.titulo }}</span>
                                        </li>
                                    </ul>
                                </div>
                                <div v-if="selectedDeliveredBooks.length" class="table-modern-wrapper">
                                    <table class="table-modern variant-red">
                                        <thead>
                                            <tr>
                                                <th>Muestra Física</th>
                                                <th class="text-center w-32">Cantidad</th>
                                                <th class="w-16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, idx) in selectedDeliveredBooks" :key="idx">
                                                <td>
                                                    <p class="font-black text-slate-800 uppercase text-[11px] leading-tight">{{ item.titulo }}</p>
                                                    <span class="text-[8px] font-black text-red-400 uppercase tracking-widest">Inventario de Promoción</span>
                                                </td>
                                                <td>
                                                    <div class="flex justify-center">
                                                        <div class="quantity-control">
                                                            <input v-model.number="item.cantidad" type="number" min="1" />
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <button @click="selectedDeliveredBooks.splice(idx, 1)" class="btn-secondary text-slate-300 hover:text-red-600 transition-colors">
                                                        <i class="fas fa-trash-alt mr-1"></i> Quitar
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="text-center py-8 border-2 border-dashed border-red-100 rounded-3xl bg-white/50 text-[10px] font-black text-red-300 uppercase tracking-widest">No se dejaron muestras hoy</div>
                            </div>
                                </div>
                                <div class="form-section ">
                            <div class="form-group mb-6">
                                <label class="label-style">Resolución Actual</label>
                                <select v-model="form.visita.resultado_visita" class="form-input font-black uppercase tracking-widest text-slate-700" required>
                                    <option value="seguimiento">CONTINUAR SEGUIMIENTO</option>
                                    <option value="compra">DECISIÓN DE COMPRA</option>
                                    <option value="rechazo">NO INTERESADO</option>
                                </select>
                            </div>

                            <div v-if="form.visita.resultado_visita === 'seguimiento'" class="form-group mb-6 p-6 bg-orange-50 rounded-[2.5rem] border-2 border-orange-100 animate-fade-in shadow-inner">
                                <label class="text-orange-800 font-black uppercase text-[9px] mb-3 block tracking-widest">Próxima Acción *</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input v-model="form.visita.proxima_visita" type="date" class="form-input border-orange-200 font-bold" required>
                                    <select v-model="form.visita.proxima_accion" class="form-input border-orange-200 font-bold">
                                        <option value="visita">Visita de Seguimiento</option>
                                        <option value="presentacion">Presentación Académica</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="label-style">Comentarios y Acuerdos de la Sesión</label>
                                <textarea v-model="form.visita.comentarios" class="form-input font-medium" rows="3" placeholder="Resumen de lo tratado (Mínimo 20 caracteres)..." required minlength="20"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end gap-4">
                            <button type="submit" class="btn-primary-action px-20 py-5 w-full md:w-auto" :disabled="loading || !selectedCliente">
                                <i class="fas" :class="loading ? 'fa-spinner fa-spin mr-2' : 'fa-save mr-2'"></i> 
                                {{ loading ? 'Sincronizando...' : 'Postear Seguimiento' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- MODAL ÉXITO -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showSuccessModal" class="modal-overlay-custom">
                    <div class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="modal-title-success">¡Seguimiento Registrado!</h2>
                        <p class="modal-text-success">La bitácora ha sido almacenada. El plantel se mantiene como <strong>{{ savedClientType }}</strong> activo.</p>
                        <button @click="goToHistory" class="btn-primary-action w-full mt-8 bg-slate-900 border-none shadow-none text-white">Regresar al Historial</button>
                    </div>
                </div>
            </Transition>
        </Teleport>
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
const loadingInitial = ref(true);
const loadingHistory = ref(false);
const gettingLocation = ref(false);
const showSuccessModal = ref(false);
const errorMessage = ref(null);

const searchingInterest = ref(false);
const searchingDelivered = ref(false);

const estados = ref([]);
const nivelesCatalog = ref([]); 
const allSeries = ref([]); 
const historialVisitas = ref([]);

const searchQuery = ref('');
const clientesSuggestions = ref([]);
const selectedCliente = ref(null);

const selectedSerieIdA = ref(''); 
const interestSuggestions = ref([]);
const deliveredSuggestions = ref([]);
const selectedInterestBooks = ref([]);
const selectedDeliveredBooks = ref([]);

const interestInput = reactive({ titulo: '' });
const deliveredInput = reactive({ titulo: '' });

let bookTimer = null;
let clientTimer = null;

const form = reactive({
    plantel: { name: '', rfc: '', niveles: [], direccion: '', estado_id: '', telefono: '', email: '', director: '', latitud: null, longitud: null },
    visita: { fecha: new Date().toISOString().split('T')[0], persona_entrevistada: '', cargo: 'Director/Coordinador', comentarios: '', resultado_visita: 'seguimiento', proxima_visita: '', proxima_accion: 'visita' }
});

const seriesFiltradas = computed(() => {
    if (form.plantel.niveles.length === 0) return [];
    return allSeries.value.filter(serie => form.plantel.niveles.includes(serie.nivel_educativo_id));
});

const savedClientType = computed(() => form.visita.resultado_visita === 'compra' ? 'Cliente' : 'Prospecto');

const getLocation = () => {
    if (!navigator.geolocation) return alert("Navegador no soporta GPS.");
    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (p) => { form.plantel.latitud = p.coords.latitude; form.plantel.longitud = p.coords.longitude; gettingLocation.value = false; },
        () => { gettingLocation.value = false; alert("Permiso de GPS denegado."); },
        { enableHighAccuracy: true }
    );
};

/**
 * BUSCADOR DE PROSPECTOS/CLIENTES
 */
const searchProspectos = () => {
    if (searchQuery.value.length < 3) { clientesSuggestions.value = []; return; }
    if (clientTimer) clearTimeout(clientTimer);
    clientTimer = setTimeout(async () => {
        try {
            const res = await axios.get('/search/clientes', { params: { query: searchQuery.value, include_prospectos: true } });
            clientesSuggestions.value = res.data;
        } catch (e) { console.error(e); }
    }, 400);
};

const selectProspecto = (c) => {
    selectedCliente.value = c;
    searchQuery.value = c.name;
    clientesSuggestions.value = [];
    
    // Mapeo de datos al formulario
    form.plantel.name = c.name;
    form.plantel.rfc = c.rfc || '';
    form.plantel.direccion = c.direccion || '';
    form.plantel.estado_id = c.estado_id || '';
    form.plantel.telefono = c.telefono || '';
    form.plantel.email = c.email || '';
    form.plantel.director = c.contacto || '';
    
    // Procesar niveles (si vienen como string separados por coma)
    if (c.nivel_educativo) {
        const nombres = c.nivel_educativo.split(',').map(n => n.trim());
        form.plantel.niveles = nivelesCatalog.value.filter(niv => nombres.includes(niv.nombre)).map(niv => niv.id);
    }

    fetchHistorial(c.id);
};

const fetchHistorial = async (id) => {
    loadingHistory.value = true;
    try {
        const res = await axios.get(`/visitas`, { params: { search: searchQuery.value } });
        const dataReceived = res.data.data || res.data;
        historialVisitas.value = Array.isArray(dataReceived) ? dataReceived.filter(v => v.cliente_id === id) : [];
    } catch (e) { console.error(e); } finally { loadingHistory.value = false; }
};

const handleSerieChange = (type) => {
    if (type === 'interest') { interestInput.titulo = ''; interestSuggestions.value = []; }
};

const searchBooks = (event, type) => {
    const val = event.target.value;
    if (val.length < 3) { type === 'interest' ? interestSuggestions.value = [] : deliveredSuggestions.value = []; return; }
    
    type === 'interest' ? searchingInterest.value = true : searchingDelivered.value = true;
    if (bookTimer) clearTimeout(bookTimer);
    
    const serieId = type === 'interest' ? (selectedSerieIdA.value === 'otro' ? null : selectedSerieIdA.value) : null; 

    bookTimer = setTimeout(async () => {
        try {
            const res = await axios.get('search/libros', { params: { query: val, serie_id: serieId } });
            if (type === 'interest') {
                interestSuggestions.value = res.data.filter(b => b.type !== 'promocion');
            } else {
                deliveredSuggestions.value = res.data.filter(b => b.type === 'promocion' && b.type !== 'digital');
            }
        } catch (e) { console.error(e); } 
        finally { searchingInterest.value = false; searchingDelivered.value = false; }
    }, 400);
};

const addMaterial = (book, type) => {
    const serie = allSeries.value.find(s => s.id == book.serie_id);
    const serieNombre = serie ? serie.nombre : 'Sin Serie';

    if (type === 'interest') {
        if (!selectedInterestBooks.value.find(b => b.id === book.id)) {
            selectedInterestBooks.value.push({ id: book.id, titulo: book.titulo, serie_nombre: serieNombre, original_type: book.type, tipo: book.type === 'digital' ? 'digital' : 'fisico' });
        }
        interestInput.titulo = ''; interestSuggestions.value = [];
    } else {
        if (!selectedDeliveredBooks.value.find(b => b.id === book.id)) {
            selectedDeliveredBooks.value.push({ id: book.id, titulo: book.titulo, serie_nombre: serieNombre, cantidad: 1 });
        }
        deliveredInput.titulo = ''; deliveredSuggestions.value = [];
    }
};

const handleSubmit = async () => {
    if (!selectedCliente.value) return;
    if (form.plantel.niveles.length === 0) {
        errorMessage.value = "Selecciona niveles educativos.";
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    errorMessage.value = null;
    loading.value = true;

    try {
        const nivelNombres = nivelesCatalog.value.filter(n => form.plantel.niveles.includes(n.id)).map(n => n.nombre);
        const materiales = {
            interes: selectedInterestBooks.value.map(b => ({ titulo: b.titulo, tipo: b.tipo })),
            entregado: selectedDeliveredBooks.value.map(b => ({ titulo: b.titulo, cantidad: b.cantidad }))
        };

        const payload = { 
            cliente_id: selectedCliente.value.id,
            plantel: { ...form.plantel, niveles: nivelNombres }, 
            visita: { ...form.visita, libros_interes: materiales },
            // Mantenemos compatibilidad con el endpoint de seguimiento
            fecha: form.visita.fecha,
            persona_entrevistada: form.visita.persona_entrevistada,
            cargo: form.visita.cargo,
            libros_interes: materiales,
            comentarios: form.visita.comentarios,
            resultado_visita: form.visita.resultado_visita,
            proxima_visita: form.visita.proxima_visita,
            proxima_accion: form.visita.proxima_accion
        };
        
        await axios.post('visitas/seguimiento', payload);
        showSuccessModal.value = true;
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Error al guardar el seguimiento.";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } finally { loading.value = false; }
};

const goToHistory = () => { showSuccessModal.value = false; router.push('/visitas'); };

const verificarPrecarga = async () => {
    const idParam = route.params.id;
    if (!idParam) return;
    loadingPrecarga.value = true;
    try {
        const res = await axios.get(`/visitas/${idParam}`);
        const v = res.data;
        // Hidratamos cliente desde la visita
        if (v.cliente) selectProspecto(v.cliente);
        else {
            selectedCliente.value = { id: v.cliente_id, name: v.nombre_plantel };
            searchQuery.value = v.nombre_plantel;
            form.plantel.rfc = v.rfc_plantel;
            form.plantel.direccion = v.direccion_plantel;
            form.plantel.director = v.director_plantel;
            form.plantel.email = v.email_plantel;
            form.plantel.telefono = v.telefono_plantel;
            fetchHistorial(v.cliente_id);
        }
    } catch (e) { console.error(e); } finally { loadingPrecarga.value = false; }
};

const formatDateShort = (d) => {
    if (!d) return '---';
    return new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }).toUpperCase();
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
    loadingInitial.value = true;
    try {
        const [resEst, resNiv, resSer] = await Promise.all([
            axios.get('estados'), axios.get('search/niveles'), axios.get('search/series')
        ]);
        estados.value = resEst.data;
        nivelesCatalog.value = resNiv.data;
        allSeries.value = resSer.data;
        await verificarPrecarga();
    } catch (e) { console.error(e); } finally { loadingInitial.value = false; }
});
</script>

<style scoped>
.form-section { background: #fff; padding: 30px; border-radius: 32px; border: 1px solid #f1f5f9; }
.section-title { color: #b91c1c; font-weight: 900; font-size: 1.1rem; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px; }

.label-style { @apply text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-widest; }

.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #f1f5f9; font-weight: 700; color: #334155; background: #fafbfc; transition: all 0.2s; font-size: 0.9rem; }
.form-input:focus { border-color: #a93339; background: white; outline: none; box-shadow: 0 0 0 4px rgba(169, 51, 57, 0.05); }

.btn-primary-action { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary-action:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.autocomplete-list { position: absolute; z-index: 2000; width: 100%; background: white; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.2); max-height: 250px; overflow-y: auto; list-style: none; padding: 10px; margin: 8px 0 0; }
.autocomplete-list li { padding: 12px 16px; cursor: pointer; border-radius: 12px; border-bottom: 1px solid #f8fafc; transition: all 0.2s; }

.modal-overlay-custom { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-content-success { background: white; padding: 45px; border-radius: 40px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4); border: 1px solid #f1f5f9; }
.success-icon-wrapper { width: 80px; height: 80px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 25px; border: 4px solid white; }

.table-modern-wrapper { @apply bg-transparent border-none shadow-none overflow-visible; }
.table-modern { @apply w-full; border-collapse: separate; border-spacing: 0 8px; }
.table-modern thead th { @apply px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 border-none; }
.table-modern tbody td { @apply bg-white py-3 px-4 border-y border-slate-100 transition-all; }
.table-modern tbody td:first-child { @apply rounded-l-[1.2rem] border-l border-slate-100; }
.table-modern tbody td:last-child { @apply rounded-r-[1.2rem] border-r border-slate-100; }
.table-modern tbody tr:hover td { @apply bg-blue-50/50 border-blue-100 transform -translate-y-0.5 shadow-md; }
.table-modern.variant-red tbody tr:hover td { @apply bg-red-50/50 border-red-100; }

.quantity-control input { @apply w-16 text-center font-black text-red-600 bg-red-50/50 border-2 border-red-100 py-1.5 rounded-xl focus:border-red-400 focus:bg-white outline-none text-sm; }

.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; text-transform: uppercase; }

.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #f1f5f9; border-radius: 10px; }

.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05); }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; appearance: none; }
</style>