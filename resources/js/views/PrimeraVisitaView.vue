<template>
    <div class="content-wrapper">
        <div class="module-page">
            <!-- Encabezado del Módulo -->
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Registro de Primera Visita</h1>
                    <p>Alta de prospecto en sistema y captura de necesidades iniciales con detalle de promoción.</p>
                </div>
                <button @click="$router.push('/visitas')" class="btn-secondary" :disabled="loading">
                    <i class="fas fa-arrow-left"></i> Volver al Historial
                </button>
            </div>

            <!-- Alertas de Error del Servidor -->
            <div v-if="errorMessage" class="error-message-container mb-6 p-4 bg-red-50 border border-red-200 rounded-lg animate-fade-in">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-red-600 mt-1"></i>
                    <div>
                        <p class="text-red-800 font-bold">Error al procesar registro</p>
                        <p class="text-red-600 text-sm whitespace-pre-wrap">{{ errorMessage }}</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
                    
                    <!-- BLOQUE 1: DATOS DEL PLANTEL (TABLA CLIENTES) -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-school"></i> Datos del Plantel
                        </div>
                        
                        <div class="form-group mb-4">
                            <label>Nombre del Plantel *</label>
                            <input v-model="form.plantel.name" type="text" class="form-input" placeholder="Nombre oficial de la institución" required :disabled="loading">
                        </div>

                        <div class="form-group mb-4">
                            <label>RFC del Plantel (Obligatorio) *</label>
                            <input v-model="form.plantel.rfc" type="text" class="form-input uppercase font-mono border-red-100" placeholder="RFC para facturación" required :disabled="loading">
                            <p class="text-[9px] text-red-400 mt-1 font-bold uppercase italic">Indispensable para el alta como prospecto.</p>
                        </div>

                        <!-- SECCIÓN GEO: GPS CAPTURE -->
                        <div class="bg-blue-50 p-4 rounded-xl border border-blue-200 mb-6">
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-xs font-bold text-blue-700 uppercase tracking-wider">
                                    <i class="fas fa-map-marker-alt mr-1"></i> Ubicación Geográfica (GPS)
                                </label>
                                <span v-if="form.plantel.latitud" class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">✓ Capturada</span>
                            </div>
                            <button type="button" @click="getLocation" class="btn-primary bg-blue-600 border-none w-full py-2 flex items-center justify-center gap-2" :disabled="gettingLocation || loading">
                                <i class="fas" :class="gettingLocation ? 'fa-spinner fa-spin' : 'fa-crosshairs'"></i>
                                {{ form.plantel.latitud ? 'Actualizar GPS' : 'Capturar GPS Actual' }}
                            </button>
                        </div>

                        <!-- Niveles Educativos: Selección Múltiple desde Base de Datos -->
                        <div class="form-group mb-4">
                            <label class="mb-2 block text-sm font-bold text-gray-700">Niveles Educativos *</label>
                            <div v-if="loadingInitial" class="py-2 animate-pulse text-xs text-gray-400 italic">Cargando niveles...</div>
                            <div v-else class="grid grid-cols-2 gap-2">
                                <label v-for="nivel in nivelesCatalog" :key="nivel.id" 
                                    class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" 
                                    :class="{'bg-red-50 border-red-300': form.plantel.niveles.includes(nivel.id)}"
                                >
                                    <input type="checkbox" :value="nivel.id" v-model="form.plantel.niveles" class="accent-red-600">
                                    <span class="text-xs font-bold uppercase">{{ nivel.nombre }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label>Estado / Región *</label>
                            <select v-model="form.plantel.estado_id" class="form-input" required :disabled="loading">
                                <option value="">Seleccionar Estado...</option>
                                <option v-for="e in estados" :key="e.id" :value="e.id">{{ e.estado }}</option>
                            </select>
                        </div>

                        <div class="form-group mb-4">
                            <label>Dirección Completa</label>
                            <textarea v-model="form.plantel.direccion" class="form-input" rows="2" placeholder="Calle, número, colonia, CP..." required :disabled="loading"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label>Celular / Teléfono *</label>
                                <input v-model="form.plantel.telefono" type="tel" class="form-input" required :disabled="loading">
                            </div>
                            <div class="form-group">
                                <label>Correo Electrónico *</label>
                                <input v-model="form.plantel.email" type="email" class="form-input" required :disabled="loading">
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <label>Nombre del Director / Coordinador *</label>
                            <input v-model="form.plantel.director" type="text" class="form-input" required :disabled="loading">
                        </div>
                    </div>

                    <!-- BLOQUE 2: DETALLES DE LA VISITA E INTERESES -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-handshake"></i> Detalles de la Visita
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label>Fecha de Visita</label>
                                <input v-model="form.visita.fecha" type="date" class="form-input" required :disabled="loading">
                            </div>
                            <div class="form-group">
                                <label>Persona Entrevistada</label>
                                <input v-model="form.visita.persona_entrevistada" type="text" class="form-input" required :disabled="loading">
                            </div>
                        </div>

                        <!-- SERIES Y LIBROS DE INTERÉS -->
                        <div class="form-section-inner mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label class="text-[10px] font-bold uppercase text-gray-500">1. Series de Interés</label>
                                    <select v-model="selectedSerieId" class="form-input font-bold" @change="handleSerieChange">
                                        <option value="">Seleccione serie...</option>
                                        <optgroup label="Series del Nivel Seleccionado">
                                            <option v-for="s in seriesFiltradas" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                        </optgroup>
                                        <option value="otro">OTRO (Ver todas las series)</option>
                                    </select>
                                </div>
                                <div class="form-group relative">
                                    <label class="text-[10px] font-bold uppercase text-gray-500">2. Buscar Libro</label>
                                    <div class="relative">
                                        <input 
                                            v-model="currentBook.titulo" 
                                            type="text" 
                                            class="form-input" 
                                            placeholder="Título o ISBN..." 
                                            :disabled="!selectedSerieId || loading" 
                                            @input="searchBooks"
                                        >
                                        <ul v-if="bookSuggestions.length" class="autocomplete-list shadow-xl">
                                            <li v-for="book in bookSuggestions" :key="book.id" @click="selectBook(book)">
                                                <div class="flex justify-between items-center w-full">
                                                    <span>{{ book.titulo }}</span>
                                                    <span class="text-[8px] bg-red-50 text-red-700 px-1.5 py-0.5 rounded-full font-black uppercase border border-red-100">{{ book.type }}</span>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- TABLA DE MATERIALES SELECCIONADOS -->
                            <div class="selected-books-container">
                                <label class="text-[10px] font-black uppercase text-gray-400 mb-2 block tracking-widest">Materiales seleccionados y promoción:</label>
                                
                                <div v-if="selectedBooks.length" class="overflow-hidden border rounded-xl bg-white shadow-sm border-gray-200">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-[9px] font-black text-gray-400 uppercase">Libro</th>
                                                <th class="px-4 py-2 text-left text-[9px] font-black text-gray-400 uppercase">Formato</th>
                                                <th class="px-4 py-2 text-center text-[9px] font-black text-gray-400 uppercase">Muestra</th>
                                                <th class="px-4 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="(item, idx) in selectedBooks" :key="idx" class="text-xs transition-colors hover:bg-gray-50">
                                                <td class="px-4 py-3">
                                                    <p class="font-bold text-red-900 leading-tight">{{ item.titulo }}</p>
                                                    <p class="text-[9px] text-gray-400 font-bold uppercase">Serie: {{ item.serie_nombre }}</p>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <select v-model="item.formato" class="form-input py-1 px-2 text-[10px] border-gray-100 bg-gray-50 h-auto">
                                                        <option value="digital">Digital</option>
                                                        <option value="fisico">Físico</option>
                                                        <option value="paquete">Paquete</option>
                                                        <option value="por_revisar">Por Revisar</option>
                                                    </select>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2 justify-center">
                                                        <input type="checkbox" v-model="item.entrega_promo" class="w-4 h-4 accent-red-600 cursor-pointer">
                                                        <input 
                                                            v-if="item.entrega_promo" 
                                                            v-model.number="item.cantidad_promo" 
                                                            type="number" 
                                                            min="1" 
                                                            class="form-input py-1 px-1 w-12 text-center text-[10px] border-red-100 bg-red-50 h-auto"
                                                            placeholder="Cant."
                                                        >
                                                        <span v-else class="text-[8px] text-gray-300 italic font-bold">N/A</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button type="button" @click="selectedBooks.splice(idx, 1)" class="text-gray-300 hover:text-red-600 transition-colors">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="text-center py-10 text-gray-400 italic text-sm border-2 border-dashed border-gray-100 rounded-xl bg-white">
                                    <i class="fas fa-book-medical text-2xl mb-2 opacity-20"></i>
                                    <p>Añada libros de interés para continuar con el registro.</p>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label>Resolución / Resultado de la Visita</label>
                            <select v-model="form.visita.resultado_visita" class="form-input font-bold" required>
                                <option value="seguimiento">CONTINUAR SEGUIMIENTO (Mantener como Prospecto)</option>
                                <option value="compra">DECISIÓN DE COMPRA (Convertir a Cliente)</option>
                                <option value="rechazo">NO INTERESADO (Inactivar prospecto)</option>
                            </select>
                        </div>

                        <!-- Campo de próxima visita si es seguimiento -->
                        <div v-if="form.visita.resultado_visita === 'seguimiento'" class="form-group mb-4 p-4 bg-orange-50 rounded-xl border border-orange-200 animate-fade-in">
                            <label class="text-orange-800 font-bold uppercase text-[10px]">Próxima Visita Estimada *</label>
                            <input v-model="form.visita.proxima_visita" type="date" class="form-input border-orange-200" required>
                        </div>

                        <div class="form-group">
                            <label>Comentarios y Acuerdos</label>
                            <textarea v-model="form.visita.comentarios" class="form-input" rows="3" placeholder="Puntos tratados durante la sesión..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-4 border-t pt-6">
                    <button type="button" @click="$router.push('/visitas')" class="btn-secondary px-8" :disabled="loading">Cancelar</button>
                    <button type="submit" class="btn-primary px-16 shadow-lg" :disabled="loading || selectedBooks.length === 0 || gettingLocation">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i> 
                        {{ loading ? 'Sincronizando...' : 'Finalizar Registro' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL DE ÉXITO -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showSuccessModal" class="modal-overlay-custom">
                    <div class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper"><i class="fas fa-check"></i></div>
                        <h2 class="modal-title-success">¡Registro Exitoso!</h2>
                        <p class="modal-text-success">El plantel se ha registrado como <strong>Prospecto</strong> en el sistema con su RFC y niveles educativos correspondientes.</p>
                        <button @click="goToHistory" class="btn-modal-success w-full mt-6 py-4">Volver al Historial</button>
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

const estados = ref([]);
const nivelesCatalog = ref([]); 
const allSeries = ref([]); 

const selectedSerieId = ref('');
const currentBook = reactive({ id: null, titulo: '' });
const bookSuggestions = ref([]);
const selectedBooks = ref([]);
let bookTimer = null;

const form = reactive({
    plantel: {
        name: '',
        rfc: '',
        niveles: [], // Almacenará IDs (ej: [1, 2])
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
        proxima_visita: ''
    }
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

const clearGPS = () => {
    form.plantel.latitud = null;
    form.plantel.longitud = null;
};

/**
 * Lógica de filtrado de series basado en los niveles educativos seleccionados (IDs)
 */
const seriesFiltradas = computed(() => {
    if (selectedSerieId.value === 'otro') return allSeries.value;
    if (form.plantel.niveles.length === 0) return [];
    
    return allSeries.value.filter(serie => {
        return form.plantel.niveles.includes(serie.nivel_educativo_id);
    });
});

const handleSerieChange = () => {
    currentBook.titulo = ''; 
    currentBook.id = null; 
    bookSuggestions.value = [];
};

/**
 * Buscador de libros filtrado por serie seleccionada
 */
const searchBooks = () => {
    if (currentBook.titulo.length < 3) return bookSuggestions.value = [];
    if (bookTimer) clearTimeout(bookTimer);
    
    bookTimer = setTimeout(async () => {
        try {
            const params = { 
                query: currentBook.titulo, 
                serie_id: selectedSerieId.value === 'otro' ? null : selectedSerieId.value 
            };
            const res = await axios.get('search/libros', { params });
            bookSuggestions.value = Array.isArray(res.data) ? res.data : [];
        } catch (e) { 
            console.error("Error al buscar libros:", e); 
        }
    }, 400);
};

const selectBook = (book) => {
    // Evitar duplicados en la tabla
    if (selectedBooks.value.find(b => b.id === book.id)) {
        currentBook.titulo = '';
        bookSuggestions.value = [];
        return;
    }

    const serie = allSeries.value.find(s => s.id == book.serie_id);
    
    selectedBooks.value.push({
        id: book.id,
        titulo: book.titulo,
        serie_nombre: serie ? serie.nombre : 'Especial / Sin Serie',
        formato: 'fisico',
        entrega_promo: false,
        cantidad_promo: 1
    });

    currentBook.titulo = ''; 
    bookSuggestions.value = [];
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
        // Mapeamos los nombres de los niveles para el campo descriptivo del backend
        const nivelNombres = nivelesCatalog.value
            .filter(n => form.plantel.niveles.includes(n.id))
            .map(n => n.nombre);

        const payload = { 
            plantel: {
                ...form.plantel,
                niveles: nivelNombres 
            }, 
            visita: { 
                ...form.visita, 
                libros_interes: selectedBooks.value 
            } 
        };
        
        await axios.post('visitas/primera', payload);
        showSuccessModal.value = true;
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Ocurrió un error al intentar guardar el registro. Verifique su conexión.";
    } finally { 
        loading.value = false; 
    }
};

const goToHistory = () => {
    showSuccessModal.value = false;
    router.push('/visitas');
};

/**
 * Carga inicial de catálogos desde los nuevos endpoints
 */
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
        console.error("Error cargando catálogos:", e); 
        errorMessage.value = "Error al cargar catálogos iniciales. Verifique que el controlador backend esté configurado.";
    } finally {
        loadingInitial.value = false;
    }
};

onMounted(fetchInitialData);
</script>

<style scoped>
/* Contenedores de Formulario */
.form-section { background: #fff; padding: 25px; border-radius: 20px; border: 1px solid #eef2f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.section-title { color: #b91c1c; font-weight: 900; font-size: 1.1rem; border-bottom: 2px solid #fee2e2; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

/* Autocompletado */
.autocomplete-list { 
    position: absolute; z-index: 2000; width: 100%; background: white; border: 1px solid #e2e8f0; 
    border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15); max-height: 250px; 
    overflow-y: auto; list-style: none; padding: 8px; margin: 5px 0 0; 
}
.autocomplete-list li { 
    padding: 10px 15px; cursor: pointer; font-size: 0.85rem; border-radius: 8px; 
    border-bottom: 1px solid #f8fafc; transition: all 0.2s;
}
.autocomplete-list li:hover { background: #fef2f2; color: #b91c1c; }

/* Modal de Sistema */
.modal-overlay-custom { 
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); 
    backdrop-filter: blur(8px); display: flex; align-items: center; 
    justify-content: center; z-index: 9999; 
}
.modal-content-success { 
    background: white; padding: 45px; border-radius: 32px; width: 90%; 
    max-width: 450px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
}
.success-icon-wrapper { 
    width: 80px; height: 80px; background: #dcfce7; color: #166534; 
    border-radius: 50%; display: flex; align-items: center; 
    justify-content: center; font-size: 2.2rem; margin: 0 auto 25px; 
}
.modal-title-success { font-size: 1.75rem; font-weight: 900; color: #1e293b; margin-bottom: 10px; }
.modal-text-success { color: #64748b; font-size: 0.95rem; line-height: 1.6; }
.btn-modal-success { 
    background: #a93339; color: white; border-radius: 16px; font-weight: 800; 
    border: none; cursor: pointer; transition: transform 0.2s; box-shadow: 0 10px 15px -3px rgba(169, 51, 57, 0.3);
}
.btn-modal-success:hover { transform: scale(1.02); background: #881337; }

/* Animaciones */
.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>