<template>
    <div class="content-wrapper p-2 md:p-6">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado del Módulo -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight">Registro de Primera Visita</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium mt-1">Alta de prospecto en sistema y captura de necesidades iniciales con inventario de muestras.</p>
                </div>
                <button @click="$router.push('/visitas')" class="btn-secondary flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm shrink-0 w-full sm:w-auto" :disabled="loading">
                    <i class="fas fa-arrow-left"></i> Volver al Historial
                </button>
            </div>

            <!-- Alertas de Error del Servidor -->
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
                    
                    <!-- BLOQUE 1: DATOS DEL PLANTEL -->
                    <div class="form-section shadow-premium border-t-4 border-t-red-700">
                        <div class="section-title">
                            <i class="fas fa-school text-red-700"></i> Datos del Plantel
                        </div>
                        
                        <div class="form-group mb-6">
                            <label class="label-style">Nombre del Plantel *</label>
                            <input v-model="form.plantel.name" type="text" class="form-input font-bold" placeholder="Nombre oficial de la institución" required :disabled="loading">
                        </div>

                        <div class="form-group mb-6">
                            <label class="label-style">RFC del Plantel (Obligatorio) *</label>
                            <input v-model="form.plantel.rfc" type="text" class="form-input uppercase font-mono border-red-100 font-black text-red-900" placeholder="RFC para facturación" required :disabled="loading">
                            <p class="text-[9px] text-red-400 mt-2 font-black uppercase italic tracking-tighter">Indispensable para el alta como prospecto en el sistema maestro.</p>
                        </div>

                        <!-- SECCIÓN GEO: GPS CAPTURE -->
                        <div class="bg-blue-50 p-6 rounded-[2rem] border border-blue-200 mb-6 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <label class="text-[10px] font-black text-blue-700 uppercase tracking-[0.2em]">
                                    <i class="fas fa-map-marker-alt mr-1"></i> Ubicación Geográfica (GPS)
                                </label>
                                <span v-if="form.plantel.latitud" class="text-[9px] bg-green-100 text-green-700 px-3 py-1 rounded-full font-black uppercase">✓ Capturada</span>
                            </div>
                            <button type="button" @click="getLocation" class="btn-primary bg-blue-600 border-none w-full py-4 rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-blue-100 active:scale-95 transition-all" :disabled="gettingLocation || loading">
                                <i class="fas" :class="gettingLocation ? 'fa-spinner fa-spin' : 'fa-crosshairs'"></i>
                                <span class="font-black uppercase tracking-widest text-[11px]">{{ form.plantel.latitud ? 'Actualizar Coordenadas GPS' : 'Capturar GPS de este Plantel' }}</span>
                            </button>
                        </div>

                        <!-- Niveles Educativos -->
                        <div class="form-group mb-6">
                            <label class="label-style">Niveles Educativos del Plantel *</label>
                            <div v-if="loadingInitial" class="py-2 animate-pulse text-[10px] text-slate-400 font-black uppercase tracking-widest italic">Sincronizando catálogo...</div>
                            <div v-else class="grid grid-cols-2 gap-3">
                                <label v-for="nivel in nivelesCatalog" :key="nivel.id" 
                                    class="flex items-center gap-3 p-3 border-2 rounded-2xl cursor-pointer hover:border-red-200 transition-all shadow-sm bg-white" 
                                    :class="{'border-red-600 bg-red-50/30': form.plantel.niveles.includes(nivel.id), 'border-slate-50': !form.plantel.niveles.includes(nivel.id)}"
                                >
                                    <input type="checkbox" :value="nivel.id" v-model="form.plantel.niveles" class="w-4 h-4 accent-red-600">
                                    <span class="text-[10px] font-black uppercase text-slate-700 leading-none">{{ nivel.nombre }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-6">
                            <label class="label-style">Estado / Región *</label>
                            <select v-model="form.plantel.estado_id" class="form-input font-bold" required :disabled="loading">
                                <option value="">Seleccionar Estado...</option>
                                <option v-for="e in estados" :key="e.id" :value="e.id">{{ e.estado }}</option>
                            </select>
                        </div>

                        <div class="form-group mb-6">
                            <label class="label-style">Dirección Completa</label>
                            <textarea v-model="form.plantel.direccion" class="form-input font-medium" rows="2" placeholder="Calle, número, colonia, CP..." required :disabled="loading"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="label-style">Celular / Teléfono *</label>
                                <input v-model="form.plantel.telefono" type="tel" class="form-input font-bold" required :disabled="loading">
                            </div>
                            <div class="form-group">
                                <label class="label-style">Correo Electrónico *</label>
                                <input v-model="form.plantel.email" type="email" class="form-input font-bold" required :disabled="loading">
                            </div>
                        </div>

                        <div class="form-group mt-6">
                            <label class="label-style">Nombre del Director / Coordinador *</label>
                            <input v-model="form.plantel.director" type="text" class="form-input font-bold" required :disabled="loading">
                        </div>
                    </div>

                    <!-- BLOQUE 2: DETALLES DE LA VISITA E INTERESES -->
                    <div class="form-section shadow-premium border-t-4 border-t-slate-800">
                        <div class="section-title">
                            <i class="fas fa-handshake text-slate-800"></i> Detalles de la Visita
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div class="form-group">
                                <label class="label-style">Fecha de la Visita *</label>
                                <input v-model="form.visita.fecha" type="date" class="form-input font-bold" required :disabled="loading">
                            </div>
                            <div class="form-group">
                                <label class="label-style">Persona Entrevistada *</label>
                                <input v-model="form.visita.persona_entrevistada" type="text" class="form-input font-bold" placeholder="¿Quién nos atendió?" required :disabled="loading">
                            </div>
                        </div>
                        <br
                        <!-- SECCIÓN A: MATERIALES DE INTERÉS (FILTRADO POR SERIE) -->
                        <div class="bg-slate-50 p-6 rounded-[2.5rem] border border-slate-100 mb-6 relative" style="overflow: visible !important;">
                            <label class="label-mini mb-4 text-slate-600 font-black tracking-tighter"><i class="fas fa-eye mr-1 text-blue-500"></i> Libros de Interés del Plantel por Serie</label>
                            
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
                                        <li v-for="b in interestSuggestions" :key="b.id" @click="addMaterial(b, 'interest')" class="text-[11px] font-black uppercase text-slate-700 hover:bg-blue-50 p-3">
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
                                            <td class="px-4 py-4">
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
                            <div v-else class="text-center py-8 border-2 border-dashed border-slate-200 rounded-3xl bg-white/50">
                                <p class="text-[10px] font-bold text-slate-300 uppercase italic">Sin libros de interés agregados</p>
                            </div>
                        </div>
                        <br><br>
                        <!-- SECCIÓN B: MUESTRAS ENTREGADAS (SOLO FÍSICOS) -->
                        <div class="bg-red-50/30 p-6 rounded-[2.5rem] border border-red-100 mb-8 relative" style="overflow: visible !important;">
                            <label class="label-mini mb-4 text-red-800 font-black tracking-tighter"><i class="fas fa-box-open mr-1"></i> Muestras de Promoción Entregadas </label>
                            
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
                                                <span class="text-[8px] font-black text-red-400 uppercase tracking-widest">Entrega de Inventario</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center justify-center">
                                                    <input v-model.number="item.cantidad" type="number" min="1" class="w-16 bg-red-50/50 border border-red-100 text-center font-black text-red-700 text-sm py-1.5 rounded-lg focus:ring-red-200">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <button type="button" @click="selectedDeliveredBooks.splice(idx, 1)" class="text-slate-300 hover:text-red-600 transition-colors">
                                                    <i class="fas fa-trash-alt"></i>Quitar
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-8 border-2 border-dashed border-red-100 rounded-3xl bg-white/50">
                                <p class="text-[10px] font-black text-red-300 uppercase tracking-widest">No se registraron muestras físicas en esta visita</p>
                            </div>
                        </div>
                        <br><br>
                        <div class="form-group mb-6">
                            <label class="label-style">Resolución / Resultado Inicial</label>
                            <select v-model="form.visita.resultado_visita" class="form-input font-black uppercase tracking-widest text-slate-700" required :disabled="loading">
                                <option value="seguimiento">CONTINUAR SEGUIMIENTO (Mantener Prospecto)</option>
                                <option value="compra">DECISIÓN DE COMPRA (Convertir a Cliente)</option>
                                <option value="rechazo">NO INTERESADO (Inactivar prospecto)</option>
                            </select>
                        </div>

                        <!-- Campo de próxima visita si es seguimiento -->
                        <div v-if="form.visita.resultado_visita === 'seguimiento'" class="form-group mb-6 p-6 bg-orange-50 rounded-[2.5rem] border-2 border-orange-100 animate-fade-in shadow-inner">
                            <label class="text-orange-800 font-black uppercase text-[10px] mb-4 block tracking-widest">Próxima Acción Estimada *</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[9px] text-orange-600 font-black uppercase mb-1 block">Fecha de Cita</label>
                                    <input v-model="form.visita.proxima_visita" type="date" class="form-input border-orange-200 font-bold" required :disabled="loading">
                                </div>
                                <div>
                                    <label class="text-[9px] text-orange-600 font-black uppercase mb-1 block">Objetivo</label>
                                    <select v-model="form.visita.proxima_accion" class="form-input border-orange-200 font-bold" :disabled="loading">
                                        <option value="visita">Visita de Seguimiento</option>
                                        <option value="presentacion">Presentación Académica</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="label-style">Comentarios y Acuerdos de la Sesión</label>
                            <textarea v-model="form.visita.comentarios" class="form-input font-medium" rows="3" placeholder="Resumen de lo tratado..." :disabled="loading"></textarea>
                        </div>
                    </div>
                </div>

                <!-- BOTONES DE ACCIÓN FINALES -->
                <div class="mt-10 flex flex-col md:flex-row justify-end gap-4 border-t border-slate-100 pt-8">
                    <button type="button" @click="$router.push('/visitas')" class="btn-secondary px-10 py-4 rounded-2xl font-bold uppercase text-xs tracking-widest" :disabled="loading">Cancelar</button>
                    <button type="submit" class="btn-primary px-20 py-4" :disabled="loading || (selectedInterestBooks.length === 0 && selectedDeliveredBooks.length === 0) || gettingLocation">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin mr-2' : 'fa-cloud-upload-alt mr-2'"></i> 
                        {{ loading ? 'Sincronizando...' : 'Finalizar Registro y Alta' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL DE ÉXITO -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showSuccessModal" class="modal-overlay-custom">
                    <div class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="modal-title-success">¡Alta de Prospecto Exitosa!</h2>
                        <p class="modal-text-success">El plantel se ha registrado como <strong>Prospecto</strong> activo y la bitácora ha sido almacenada en el historial.</p>
                        <button @click="goToHistory" class="btn-primary-action w-full mt-8 bg-slate-900 border-none shadow-none">Regresar al Listado</button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const loading = ref(false);
const loadingInitial = ref(true);
const gettingLocation = ref(false);
const showSuccessModal = ref(false);
const errorMessage = ref(null);

const searchingInterest = ref(false);
const searchingDelivered = ref(false);

const estados = ref([]);
const nivelesCatalog = ref([]); 
const allSeries = ref([]); 

// IDs de series seleccionadas
const selectedSerieIdA = ref(''); 

const interestSuggestions = ref([]);
const deliveredSuggestions = ref([]);
const selectedInterestBooks = ref([]);
const selectedDeliveredBooks = ref([]);

const interestInput = reactive({ titulo: '' });
const deliveredInput = reactive({ titulo: '' });

let bookTimer = null;

const form = reactive({
    plantel: {
        name: '',
        rfc: '',
        niveles: [], 
        direccion: '',
        estado_id: '',
        telefono: '',
        email: '',
        director: '',
        latitud: null,
        longitud: null
    },
    visita: {
        fecha: new Date().toISOString().split('T')[0],
        persona_entrevistada: '',
        cargo: 'Director/Coordinador',
        comentarios: '',
        resultado_visita: 'seguimiento',
        proxima_visita: '',
        proxima_accion: 'visita'
    }
});

/**
 * Lógica de filtrado de series basado en los niveles educativos del plantel (Para Apartado A)
 */
const seriesFiltradas = computed(() => {
    if (form.plantel.niveles.length === 0) return [];
    return allSeries.value.filter(serie => {
        return form.plantel.niveles.includes(serie.nivel_educativo_id);
    });
});

/**
 * LÓGICA GEO: Obtener ubicación GPS actual
 */
const getLocation = () => {
    if (!navigator.geolocation) return alert("Tu navegador no soporta geolocalización.");
    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.plantel.latitud = position.coords.latitude;
            form.plantel.longitud = position.coords.longitude;
            gettingLocation.value = false;
        },
        (error) => { 
            gettingLocation.value = false;
            alert("No se pudo obtener la ubicación. Verifica los permisos de tu navegador.");
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

const handleSerieChange = (type) => {
    if (type === 'interest') {
        interestInput.titulo = ''; 
        interestSuggestions.value = [];
    }
};

/**
 * Buscador de libros dual
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

    if (bookTimer) clearTimeout(bookTimer);
    
    const serieId = type === 'interest' 
        ? (selectedSerieIdA.value === 'otro' ? null : selectedSerieIdA.value)
        : null; 

    bookTimer = setTimeout(async () => {
        try {
            const res = await axios.get('search/libros', { 
                params: { 
                    query: val,
                    serie_id: serieId 
                } 
            });
            
            if (type === 'interest') {
                interestSuggestions.value = res.data;
            } else {
                // FILTRO CRÍTICO: El apartado B solo permite materiales físicos (No digitales)
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

/**
 * Envío de datos al backend
 */
const handleSubmit = async () => {
    if (form.plantel.niveles.length === 0) {
        errorMessage.value = "Debe seleccionar al menos un nivel educativo para el plantel.";
        return;
    }

    errorMessage.value = null;
    loading.value = true;

    try {
        const nivelNombres = nivelesCatalog.value
            .filter(n => form.plantel.niveles.includes(n.id))
            .map(n => n.nombre);

        // Estructura consolidada de libros para el bitácora
        const materiales = {
            interes: selectedInterestBooks.value.map(b => ({
                titulo: b.titulo,
                tipo: b.tipo
            })),
            entregado: selectedDeliveredBooks.value.map(b => ({
                titulo: b.titulo,
                cantidad: b.cantidad
            }))
        };

        const payload = { 
            plantel: {
                ...form.plantel,
                niveles: nivelNombres 
            }, 
            visita: { 
                ...form.visita, 
                libros_interes: materiales 
            } 
        };
        
        await axios.post('visitas/primera', payload);
        showSuccessModal.value = true;
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Ocurrió un error al intentar guardar el registro.";
    } finally { 
        loading.value = false; 
    }
};

const goToHistory = () => {
    showSuccessModal.value = false;
    router.push('/visitas');
};

const fetchInitialData = async () => {
    loadingInitial.value = true;
    try {
        const [resEst, resNiv, resSer] = await Promise.all([
            axios.get('estados'),
            axios.get('search/niveles'),
            axios.get('search/series')
        ]);
        estados.value = resEst.data;
        nivelesCatalog.value = resNiv.data;
        allSeries.value = resSer.data;
    } catch (e) { 
        errorMessage.value = "Error al conectar con la base de datos de catálogos.";
    } finally {
        loadingInitial.value = false;
    }
};

onMounted(fetchInitialData);
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
.autocomplete-list li:hover { background: #fef2f2; color: #b91c1c; }

/* Modal de Sistema */
.modal-overlay-custom { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-content-success { background: white; padding: 45px; border-radius: 40px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4); border: 1px solid #f1f5f9; }
.success-icon-wrapper { width: 80px; height: 80px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 25px; border: 4px solid white; }
.modal-title-success { font-size: 1.75rem; font-weight: 900; color: #1e293b; margin-bottom: 12px; tracking-tighter: -0.025em; }
.modal-text-success { color: #64748b; font-size: 0.95rem; line-height: 1.6; font-weight: 500; }

.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05); }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Estilizar select */
select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; appearance: none; }

.table-container { 
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}
</style>