<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    <h1 v-if="loadingPrecarga" class="text-2xl font-black text-slate-300 animate-pulse uppercase">Sincronizando expediente...</h1>
                    <h1 v-else-if="selectedCliente" class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight">
                        Seguimiento: {{ selectedCliente.name }}
                    </h1>
                    <h1 v-else class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight text-red-800">Seguimiento de Prospectos</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium mt-1 uppercase tracking-tighter italic">Actualiza el expediente y registra los nuevos acuerdos de la visita subsecuente.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm shrink-0 w-full sm:w-auto bg-white border-2 border-slate-200 text-black uppercase" :disabled="loading">
                    <i class="fas fa-arrow-left"></i> Volver al Historial
                </button>
            </div>

            <!-- Alerta de Error -->
            <div v-if="errorMessage" class="error-message-container mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl animate-fade-in shadow-sm">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-red-600 mt-1"></i>
                    <div>
                        <p class="text-red-800 font-black uppercase text-[10px] tracking-widest">Error al procesar registro</p>
                        <p class="text-red-600 text-xs mt-1 font-medium whitespace-pre-wrap">{{ errorMessage }}</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                   

                    <!-- BLOQUE 2: DETALLES DE LA NUEVA VISITA -->
                    <div class="space-y-8">
                        <div class="form-section shadow-premium border-t-4 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="section-title text-black">
                                <i class="fas fa-calendar-plus text-slate-800"></i> 2. Detalles de la Interacción
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="form-group">
                                    <label class="label-style">Fecha de la Visita *</label>
                                    <input v-model="form.visita.fecha" type="date" class="form-input font-bold" required :disabled="loading">
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Persona Entrevistada *</label>
                                    <input v-model="form.visita.persona_entrevistada" type="text" class="form-input font-bold" placeholder="¿Quién atendió?" required minlength="3" :disabled="loading">
                                </div>
                            </div>

                            <div class="form-group mb-6">
                                <label class="label-style">Cargo / Puesto de la Persona *</label>
                                <select v-model="form.visita.cargo" class="form-input font-bold" required :disabled="loading">
                                    <option value="Director/Coordinador">Director/Coordinador</option>
                                    <option value="Subdirector">Subdirector</option>
                                    <option value="Jefe de Departamento">Jefe de Departamento</option>
                                    <option value="Profesor">Profesor</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>

                            <div v-if="form.visita.cargo === 'Otro'" class="form-group mb-6 animate-fade-in">
                                <label class="label-style">Especifique el Cargo *</label>
                                <input v-model="form.visita.cargo_especifico" type="text" class="form-input font-bold border-red-100" placeholder="Escriba el puesto real..." required :disabled="loading">
                            </div>
                        </div>

                        <!-- APARTADO A: LIBROS DE INTERÉS -->
                        <div class="form-section shadow-premium border-t-4 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="bg-slate-50 p-6 rounded-[2.5rem] border border-slate-100 mb-6 relative" style="overflow: visible !important;">
                                <label class="label-mini mb-4 text-slate-600 font-black tracking-tighter"><i class="fas fa-eye mr-1 text-blue-500"></i>  Libros de Interés (Venta)</label>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                    <div class="form-group">
                                        <label class="text-[9px] font-black uppercase text-slate-400 mb-1 block">Filtrar por Serie</label>
                                        <select v-model="selectedSerieIdA" class="form-input font-bold text-xs" @change="handleSerieChange('interest')">
                                            <option value="">Cualquier serie...</option>
                                            <option v-for="s in seriesFiltradas" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                            <option value="otro">VER TODAS</option>
                                        </select>
                                    </div>
                                    <div class="form-group relative">
                                        <label class="text-[9px] font-black uppercase text-slate-400 mb-1 block">Buscar y Añadir Libro</label>
                                        <div class="relative">
                                            <input v-model="interestInput.titulo" type="text" class="form-input pr-10 font-bold" placeholder="Título o ISBN..." @input="searchBooks($event, 'interest')" autocomplete="off">
                                            <i v-if="searchingInterest" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                        <ul v-if="interestSuggestions.length" class="autocomplete-list shadow-2xl border border-slate-100">
                                            <li v-for="b in interestSuggestions" :key="b.id" @click="addMaterial(b, 'interest')" class="text-[11px] font-black uppercase text-slate-700 hover:bg-blue-50 p-3 flex justify-between items-center transition-colors">
                                                <span>{{ b.titulo }}</span>
                                                <span class="text-[7px] bg-slate-100 px-1.5 rounded">{{ b.type }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div v-if="selectedInterestBooks.length" class="table-container mt-6">
                                   <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm">
                                <table class="min-width-full divide-y divide-gray-200">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="table-header">Material / Serie</th>
                                            <th class="table-header text-center w-36">Formato</th>
                                            <th class="px-6 py-3 w-12"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        <tr v-for="(item, idx) in selectedInterestBooks" :key="idx" class="hover:bg-gray-50 transition-colors">
                                            <td class="table-cell">
                                                <div class="text-xs font-black text-slate-800 uppercase leading-tight">
                                                    {{ item.titulo }}
                                                </div>
                                                <div class="text-[9px] font-black text-slate-400 uppercase tracking-tighter mt-1">
                                                    Serie: {{ item.serie_nombre }}
                                                </div>
                                            </td>
                                            <td class="table-cell text-center">
                                                <select v-model="item.tipo" class="select-table">
                                                    <option v-if="item.original_type === 'digital'" value="digital">DIGITAL</option>
                                                    <template v-else>
                                                        <option value="fisico">FÍSICO</option>
                                                        <option value="paquete">PAQUETE</option>
                                                        <option value="por_revisar">POR REVISAR</option>
                                                    </template>
                                                </select>
                                            </td>
                                            <td class="table-cell text-center">
                                                <button type="button" @click="selectedInterestBooks.splice(idx, 1)" 
                                                        class="btn-icon-delete-simple">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                                </div>
                                <div v-else class="text-center py-8 border-2 border-dashed border-slate-200 rounded-3xl bg-white/50">
                                    <p class="text-[10px] font-bold text-slate-300 uppercase italic">Sin libros añadidos</p>
                                </div>
                            </div>
                        </div>

                        <!-- APARTADO B: PROMOCIÓN -->
                        <div class="form-section shadow-premium border-t-4 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="bg-red-50/30 p-6 rounded-[2.5rem] border border-red-100 mb-8 relative" style="overflow: visible !important;">
                                <label class="label-mini mb-4 text-red-800 font-black tracking-tighter"><i class="fas fa-box-open mr-1"></i>  Muestras de Promoción Entregadas</label>
                                
                                <div class="form-group relative mb-4">
                                    <label class="label-mini">Buscar Libro de Promoción (Solo Material Físico)</label>
                                    <div class="relative">
                                        <input v-model="deliveredInput.titulo" type="text" class="form-input pr-10 font-bold border-red-100 shadow-sm" placeholder="Escribe título o ISBN..." @input="searchBooks($event, 'delivered')" autocomplete="off">
                                        <i v-if="searchingDelivered" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-400"></i>
                                    </div>
                                    <ul v-if="deliveredSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                        <li v-for="b in deliveredSuggestions" :key="b.id" @click="addMaterial(b, 'delivered')" class="text-[11px] font-black uppercase text-slate-700 hover:bg-red-50 p-3 transition-colors">
                                            <span>{{ b.titulo }}</span>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div v-if="selectedDeliveredBooks.length" class="table-container mt-6">
                                    <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm">
                                    <table class="min-width-full divide-y divide-gray-200">
                                        <thead class="bg-red-50">
                                            <tr>
                                                <th class="table-header text-red-900/60">Muestra Física</th>
                                                <th class="table-header text-center w-32">Cantidad</th>
                                                <th class="px-6 py-3 w-16"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-red-50">
                                            <tr v-for="(item, idx) in selectedDeliveredBooks" :key="idx" class="hover:bg-red-50/20 transition-colors">
                                                <td class="table-cell">
                                                    <div class="text-xs font-black text-slate-800 uppercase leading-tight">
                                                        {{ item.titulo }}
                                                    </div>
                                                    <div class="text-[8px] font-black text-red-400 uppercase tracking-widest mt-1">
                                                        Serie: {{ item.serie_nombre }}
                                                    </div>
                                                </td>
                                                <td class="table-cell text-center">
                                                    <div class="flex justify-center">
                                                        <div class="quantity-control-wrapper">
                                                            <input v-model.number="item.cantidad" 
                                                                type="number" 
                                                                min="1" 
                                                                class="input-table text-center" />
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="table-cell text-right">
                                                    <button @click="selectedDeliveredBooks.splice(idx, 1)" 
                                                            class="btn-icon-delete">
                                                        <i class="fas fa-trash-alt"></i> Quitar
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                </div>
                                <div v-else class="text-center py-8 border-2 border-dashed border-red-100 rounded-3xl bg-white/50">
                                    <p class="text-[10px] font-black text-red-300 uppercase tracking-widest">No se dejaron muestras hoy</p>
                                </div>
                            </div>
                        </div>

                        <!-- RESULTADO Y COMENTARIOS -->
                        <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="form-group mb-6">
                                <label class="label-style">Resolución / Resultado Actual</label>
                                <select v-model="form.visita.resultado_visita" class="form-input font-black uppercase tracking-widest text-slate-700" required :disabled="loading">
                                    <option value="seguimiento">CONTINUAR SEGUIMIENTO</option>
                                    <option value="compra">DECISIÓN DE COMPRA</option>
                                    <option value="rechazo">NO INTERESADO</option>
                                </select>
                            </div>

                            <div v-if="form.visita.resultado_visita === 'seguimiento'" class="form-group mb-6 p-6 bg-orange-50 rounded-[2.5rem] border-2 border-orange-100 animate-fade-in shadow-inner">
                                <label class="text-orange-800 font-black uppercase text-[9px] mb-3 block tracking-widest">Próxima Acción Agendada *</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input v-model="form.visita.proxima_visita" type="date" class="form-input border-orange-200 font-bold" required :disabled="loading">
                                    <select v-model="form.visita.proxima_accion" class="form-input border-orange-200 font-bold" :disabled="loading">
                                        <option value="visita">Visita de Seguimiento</option>
                                        <option value="presentacion">Presentación Académica</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="label-style">Comentarios y Acuerdos de la Sesión</label>
                                <textarea v-model="form.visita.comentarios" class="form-input font-medium" rows="4" placeholder="Resumen detallado de los puntos tratados (Mínimo 20 caracteres)..." required minlength="20" :disabled="loading"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOTONES DE ACCIÓN -->
                <div class="mt-10 flex flex-col md:flex-row justify-end gap-4 border-t border-slate-100 pt-8 pb-20">
                    <button type="button" @click="$router.push('/visitas')" class="btn-secondary px-10 py-4 rounded-2xl font-bold uppercase text-xs tracking-widest bg-white border-2 border-slate-200 text-black" :disabled="loading">Cancelar</button>
                    <button type="submit" class="btn-primary px-20 py-4 shadow-xl shadow-red-900/10 transition-all active:scale-95" :disabled="loading || !selectedCliente || gettingLocation">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin mr-2' : 'fa-save mr-2'"></i> 
                        {{ loading ? 'Sincronizando...' : 'Postear Seguimiento' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL DE ÉXITO DINÁMICO -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showSuccessModal" class="modal-overlay-custom">
                    <div class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="modal-title-success">¡Seguimiento Exitoso!</h2>
                        <p class="modal-text-success">La bitácora se guardó. El plantel ahora figura como <strong>{{ savedClientType }}</strong> activo.</p>
                        <button @click="goToHistory" class="btn-primary w-full mt-8 bg-slate-900 border-none text-white uppercase font-black tracking-widest py-4">Regresar al Listado</button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, nextTick } from 'vue';
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
    visita: { fecha: new Date().toISOString().split('T')[0], persona_entrevistada: '', cargo: 'Director/Coordinador', cargo_especifico: '', comentarios: '', resultado_visita: 'seguimiento', proxima_visita: '', proxima_accion: 'visita' }
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
        (p) => { 
            form.plantel.latitud = p.coords.latitude; 
            form.plantel.longitud = p.coords.longitude; 
            gettingLocation.value = false; 
        },
        () => { gettingLocation.value = false; alert("Permiso de GPS denegado."); },
        { enableHighAccuracy: true }
    );
};

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
    
    form.plantel.name = c.name;
    form.plantel.rfc = c.rfc || '';
    form.plantel.direccion = c.direccion || '';
    form.plantel.estado_id = c.estado_id || '';
    form.plantel.telefono = c.telefono || '';
    form.plantel.email = c.email || '';
    form.plantel.director = c.contacto || '';
    
    if (c.nivel_educativo) {
        const nombres = c.nivel_educativo.split(',').map(n => n.trim());
        form.plantel.niveles = nivelesCatalog.value
            .filter(niv => nombres.includes(niv.nombre))
            .map(niv => niv.id);
    } else {
        form.plantel.niveles = [];
    }
    
    form.plantel.latitud = c.latitud || null;
    form.plantel.longitud = c.longitud || null;

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

/**
 * REGLA ACTUALIZADA: Obtener nombre de serie del catálogo
 */
const addMaterial = (book, type) => {
    const serie = allSeries.value.find(s => s.id == book.serie_id);
    const serieNombre = serie ? serie.nombre : 'Sin Serie';

    if (type === 'interest') {
        if (!selectedInterestBooks.value.find(b => b.id === book.id)) {
            selectedInterestBooks.value.push({ 
                id: book.id, titulo: book.titulo, serie_nombre: serieNombre, 
                original_type: book.type, tipo: book.type === 'digital' ? 'digital' : 'fisico' 
            });
        }
        interestInput.titulo = ''; interestSuggestions.value = [];
    } else {
        if (!selectedDeliveredBooks.value.find(b => b.id === book.id)) {
            selectedDeliveredBooks.value.push({ 
                id: book.id, titulo: book.titulo, serie_nombre: serieNombre, cantidad: 1 
            });
        }
        deliveredInput.titulo = ''; deliveredSuggestions.value = [];
    }
};

const handleSubmit = async () => {
    if (!selectedCliente.value) return;
    if (form.plantel.niveles.length === 0) {
        errorMessage.value = "Selecciona niveles educativos para completar el expediente del plantel.";
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

        const finalCargo = form.visita.cargo === 'Otro' ? form.visita.cargo_especifico : form.visita.cargo;

        const payload = { 
            cliente_id: selectedCliente.value.id,
            plantel: { ...form.plantel, niveles: nivelNombres }, 
            visita: { ...form.visita, cargo: finalCargo, libros_interes: materiales },
            fecha: form.visita.fecha,
            persona_entrevistada: form.visita.persona_entrevistada,
            cargo: finalCargo,
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
        if (v.cliente) selectProspecto(v.cliente);
        else {
            selectedCliente.value = { id: v.cliente_id, name: v.nombre_plantel };
            searchQuery.value = v.nombre_plantel;
            form.plantel.name = v.nombre_plantel;
            form.plantel.rfc = v.rfc_plantel || '';
            form.plantel.direccion = v.direccion_plantel || '';
            form.plantel.estado_id = v.estado_id || '';
            form.plantel.telefono = v.telefono_plantel || '';
            form.plantel.email = v.email_plantel || '';
            form.plantel.director = v.director_plantel || '';
            form.plantel.latitud = v.latitud || null;
            form.plantel.longitud = v.longitud || null;

            if (v.nivel_educativo_plantel) {
                const nombres = v.nivel_educativo_plantel.split(',').map(n => n.trim());
                form.plantel.niveles = nivelesCatalog.value
                    .filter(niv => nombres.includes(niv.nombre))
                    .map(niv => niv.id);
            }

            fetchHistorial(v.cliente_id);
        }
    } catch (e) { console.error(e); } finally { loadingPrecarga.value = false; }
};

/**
 * SOLUCIÓN AL BUG DE FECHA (Desfase de zona horaria):
 * Separamos la cadena por '-' y construimos el objeto Date LOCAL.
 */
const formatDateShort = (dateString) => {
    if (!dateString) return '---';
    const cleanDate = dateString.split('T')[0];
    const [year, month, day] = cleanDate.split('-').map(Number);
    const date = new Date(year, month - 1, day);
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
    // 1. Scroll inmediato al montar
    window.scrollTo({ top: 0, behavior: 'instant' });
    
    // 2. Esperar a que Vue procese el renderizado inicial y forzar de nuevo
    await nextTick();
    window.scrollTo(0, 0);

    loadingInitial.value = true;
    try {
        const [resEst, resNiv, resSer] = await Promise.all([
            axios.get('estados'), axios.get('search/niveles'), axios.get('search/series')
        ]);
        estados.value = resEst.data;
        nivelesCatalog.value = resNiv.data;
        allSeries.value = resSer.data;
        
        await verificarPrecarga();

        // 3. Scroll final después de la precarga
        await nextTick();
        window.scrollTo({ top: 0, behavior: 'smooth' });

    } catch (e) { 
        console.error(e); 
    } finally { 
        loadingInitial.value = false; 
    }
});
</script>

<style scoped>
.form-section { background: #fff; padding: 30px; border-radius: 32px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; font-size: 1.1rem; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px; }

.label-style { @apply text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-widest; }

.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #f1f5f9; font-weight: 700; color: #000000; background: #fafbfc; transition: all 0.2s; font-size: 0.9rem; }
.form-input:focus { border-color: #000000; background: white; outline: none; box-shadow: 0 0 0 4px rgba(0,0,0,0.02); }

.btn-primary-black { background: #000000; color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: center; }
.btn-primary-black:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(0,0,0,0.2); }

.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.btn-primary-blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2); }

.autocomplete-list { position: absolute; z-index: 2000; width: 100%; background: white; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.2); max-height: 250px; overflow-y: auto; list-style: none; padding: 10px; margin: 8px 0 0; }
.autocomplete-list li { padding: 12px 16px; cursor: pointer; border-radius: 12px; border-bottom: 1px solid #f8fafc; transition: all 0.2s; }

.modal-overlay-custom { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-content-success { background: white; padding: 45px; border-radius: 40px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4); border: 1px solid #f1f5f9; }
.success-icon-wrapper { width: 80px; height: 80px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 25px; border: 4px solid white; }
.modal-title-success { font-size: 1.75rem; font-weight: 900; color: #000000; margin-bottom: 12px; }
.modal-text-success { color: #64748b; font-size: 0.95rem; line-height: 1.6; font-weight: 500; }

.table-responsive { width: 100%; overflow-x: auto; background: white; border-radius: 20px; }
.table-header { padding: 14px 16px; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; text-align: left; }
.table-cell { padding: 12px 16px; vertical-align: middle; }

.input-table, .select-table { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 10px; font-weight: 900; color: #1e293b; padding: 6px 8px; text-transform: uppercase; transition: all 0.2s; width: 100%; }
.input-table:focus, .select-table:focus { outline: none; border-color: #f87171; background-color: #fff; box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.1); }

.btn-icon-delete { background: none; border: none; color: #cbd5e1; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: color 0.2s; display: inline-flex; align-items: center; gap: 4px; }
.btn-icon-delete:hover { color: #dc2626; }
.btn-icon-delete-simple { background: none; border: none; color: #cbd5e1; font-size: 0.9rem; cursor: pointer; transition: color 0.2s; }
.btn-icon-delete-simple:hover { color: #dc2626; }

.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; text-transform: uppercase; }

.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #f1f5f9; border-radius: 10px; }

.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05); }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 1rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; appearance: none; }
</style>