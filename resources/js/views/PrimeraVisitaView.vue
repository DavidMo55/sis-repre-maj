<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Registro de Primera Visita</h1>
                    <p>Ingresa la información del nuevo plantel y los detalles de la entrevista.</p>
                </div>
                <button @click="$router.push('/visitas')" class="btn-secondary" :disabled="loading">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>

            <!-- MENSAJE DE ERROR DINÁMICO MEJORADO -->
            <div v-if="errorMessage" class="error-message-container mb-6 p-4 bg-red-50 border border-red-200 rounded-lg animate-fade-in">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-red-600 mt-1"></i>
                    <div>
                        <p class="text-red-800 font-bold">Atención ({{ lastErrorStatus }})</p>
                        <p class="text-red-600 text-sm whitespace-pre-wrap">{{ errorMessage }}</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
                    
                    <!-- BLOQUE 1: DATOS DEL PLANTEL -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-school"></i> Datos del Plantel
                        </div>
                        
                        <div class="form-group mb-4">
                            <label>Nombre del Plantel</label>
                            <input v-model="form.plantel.name" type="text" class="form-input" placeholder="Ej: Escuela Primaria Niños Héroes" required :disabled="loading">
                        </div>

                        <!-- SELECTOR DE MODO DE UBICACIÓN -->
                        <div class="form-group mb-4">
                            <label class="font-bold text-gray-700 block mb-2 text-sm uppercase tracking-wider">Método de Ubicación</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button 
                                    type="button" 
                                    @click="locationMode = 'manual'"
                                    class="p-2 rounded-xl border-2 font-bold text-xs transition-all flex items-center justify-center gap-2"
                                    :class="locationMode === 'manual' ? 'border-red-600 bg-red-50 text-red-700' : 'border-gray-200 text-gray-400 bg-white'"
                                >
                                    <i class="fas fa-edit"></i> Dirección Manual
                                </button>
                                <button 
                                    type="button" 
                                    @click="locationMode = 'gps'"
                                    class="p-2 rounded-xl border-2 font-bold text-xs transition-all flex items-center justify-center gap-2"
                                    :class="locationMode === 'gps' ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-400 bg-white'"
                                >
                                    <i class="fas fa-crosshairs"></i> Capturar GPS
                                </button>
                            </div>
                        </div>

                        <!-- DIRECCIÓN Y ESTADO (SOLO MODO MANUAL) -->
                        <Transition name="fade">
                            <div v-if="locationMode === 'manual'" class="animate-fade-in">
                                <div class="form-group mb-4">
                                    <label>Dirección Completa</label>
                                    <textarea v-model="form.plantel.direccion" class="form-input" rows="2" placeholder="Calle, número, colonia..." required :disabled="loading"></textarea>
                                </div>
                                <div class="form-group mb-4">
                                    <label>Estado</label>
                                    <select v-model="form.plantel.estado_id" class="form-input" required :disabled="loading">
                                        <option value="">Seleccionar Estado...</option>
                                        <option v-for="e in estados" :key="e.id" :value="e.id">{{ e.estado }}</option>
                                    </select>
                                </div>
                            </div>
                        </Transition>

                        <!-- UBICACIÓN GPS (SOLO MODO GPS) -->
                        <Transition name="fade">
                            <div v-if="locationMode === 'gps'" class="bg-blue-50 p-4 rounded-xl border border-blue-200 mb-4 animate-fade-in">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-bold text-blue-700 uppercase tracking-wider">Geolocalización Directa</label>
                                    <span v-if="form.plantel.latitud" class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">
                                        <i class="fas fa-check"></i> Coordenadas Listas
                                    </span>
                                </div>
                                <button type="button" @click="getLocation" class="btn-primary bg-blue-600 border-none w-full py-2 flex items-center justify-center gap-2" :disabled="gettingLocation || loading">
                                    <i class="fas" :class="gettingLocation ? 'fa-spinner fa-spin' : 'fa-map-marker-alt'"></i>
                                    {{ form.plantel.latitud ? 'Ubicación Actualizada' : 'Capturar mi ubicación actual' }}
                                </button>
                                
                            </div>
                        </Transition>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label>Nivel Educativo</label>
                                <select v-model="form.plantel.nivel_educativo" class="form-input" required :disabled="loading">
                                    <option value="">Seleccionar...</option>
                                    <option>Preescolar</option>
                                    <option>Primaria</option>
                                    <option>Secundaria</option>
                                    <option>Bachillerato</option>
                                    <option>Superior</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Celular de contacto</label>
                                <input v-model="form.plantel.telefono" type="tel" class="form-input" required :disabled="loading">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label>Correo Electrónico</label>
                                <input v-model="form.plantel.email" type="email" class="form-input" required :disabled="loading">
                            </div>
                            <div class="form-group">
                                <label>Nombre del Director / Coordinador</label>
                                <input v-model="form.plantel.director" type="text" class="form-input" required :disabled="loading">
                            </div>
                        </div>
                    </div>

                    <!-- BLOQUE 2: DETALLES DE LA VISITA -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-handshake"></i> Detalles de la Visita
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label>Fecha de Visita Actual</label>
                                <input v-model="form.visita.fecha" type="date" class="form-input" required :disabled="loading">
                            </div>
                            <Transition name="fade">
                                <div v-if="form.visita.resultado_visita === 'seguimiento'" class="form-group animate-fade-in">
                                    <label>Próxima Visita Estimada</label>
                                    <input v-model="form.visita.proxima_visita" type="date" class="form-input" :disabled="loading">
                                </div>
                            </Transition>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label>Persona Entrevistada</label>
                                <input v-model="form.visita.persona_entrevistada" type="text" class="form-input" required :disabled="loading">
                            </div>
                            <div class="form-group">
                                <label>Cargo</label>
                                <input v-model="form.visita.cargo" type="text" class="form-input" required :disabled="loading">
                            </div>
                        </div>

                        <!-- SECCIÓN DE LIBROS DE INTERÉS -->
                        <div class="form-section-inner mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <label class="font-bold text-gray-700 block mb-2 text-xs uppercase">Libros de Interés</label>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                                <div class="md:col-span-8 relative">
                                    <input 
                                        v-model="currentBook.titulo" 
                                        type="text" 
                                        class="form-input" 
                                        placeholder="Buscar libro..." 
                                        @input="searchBooks"
                                        :disabled="loading"
                                    >
                                    <ul v-if="bookSuggestions.length" class="autocomplete-list">
                                        <li v-for="book in bookSuggestions" :key="book.id" @click="selectBook(book)">
                                            {{ book.titulo }}
                                        </li>
                                    </ul>
                                </div>
                                <div class="md:col-span-4">
                                    <button type="button" @click="addBookToList" class="btn-primary w-full py-3" :disabled="!currentBook.id || loading">
                                        <i class="fas fa-plus"></i> Añadir
                                    </button>
                                </div>
                            </div>
                            <!-- Tabla rápida de libros seleccionados -->
                            <div v-if="selectedBooks.length" class="mt-3 space-y-1">
                                <div v-for="(item, idx) in selectedBooks" :key="idx" class="flex justify-between items-center bg-white p-2 rounded border text-xs">
                                    <span class="font-bold">{{ item.titulo }}</span>
                                    <button type="button" @click="selectedBooks.splice(idx, 1)" class="text-red-500"><i class="fas fa-times"></i>-</button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="flex items-center gap-3 cursor-pointer select-none">
                                <input v-model="form.visita.material_entregado" type="checkbox" class="w-5 h-5 accent-red-600" :disabled="loading">
                                <span class="font-bold text-gray-700">¿Se entregó material de promoción?</span>
                            </label>
                        </div>

                        <Transition name="fade">
                            <div v-if="form.visita.material_entregado" class="form-group mb-4 p-4 bg-red-50 rounded-xl border border-red-100 animate-fade-in">
                                <label class="text-red-800 font-bold">Cantidad de libros entregados</label>
                                <input v-model.number="form.visita.material_cantidad" type="number" min="1" class="form-input mt-1 border-red-200" placeholder="Ej: 5" required :disabled="loading">
                            </div>
                        </Transition>

                        <!-- RESULTADO Y CAMBIO DE ESTADO -->
                        <div class="form-group bg-gray-50 p-4 rounded-xl border-2 border-red-100 mb-4">
                            <label class="font-black text-red-800 text-xs uppercase tracking-widest mb-2 block">Resolución de la Visita</label>
                            <select v-model="form.visita.resultado_visita" class="form-input font-bold" required :disabled="loading">
                                <option value="seguimiento">CONTINUAR SEGUIMIENTO (Mantener como Prospecto)</option>
                                <option value="compra"> DECISIÓN DE COMPRA (Convertir a Cliente)</option>
                                <option value="rechazo"> NO INTERESADO (Inactivar registro)</option>
                            </select>
                        </div>

                        <div class="form-group mt-4">
                            <label>Comentarios y Acuerdos</label>
                            <textarea v-model="form.visita.comentarios" class="form-input" rows="3" placeholder="Puntos tratados..." :disabled="loading"></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-4 border-t pt-6">
                    <button type="button" @click="$router.push('/visitas')" class="btn-secondary" :disabled="loading">Cancelar</button>
                    <button type="submit" class="btn-primary px-16 shadow-lg" :disabled="loading || (locationMode === 'gps' && !form.plantel.latitud)">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i> 
                        {{ loading ? 'Guardando...' : 'Finalizar Registro' }}
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
                    <h2 class="modal-title-success">¡Registro Exitoso!</h2>
                    <p class="modal-text-success">La bitácora ha sido guardada correctamente en el historial.</p>
                    <div class="modal-actions-success">
                        <button @click="goToHistory" class="btn-modal-success">Entendido</button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const loading = ref(false);
const gettingLocation = ref(false);
const estados = ref([]);
const showSuccessModal = ref(false);
const locationMode = ref('manual');
const errorMessage = ref(null);
const lastErrorStatus = ref(null);

// Lógica de Libros de Interés
const currentBook = reactive({ id: null, titulo: '' });
const bookSuggestions = ref([]);
const selectedBooks = ref([]);
let bookTimer = null;

const form = reactive({
    plantel: {
        name: '',
        nivel_educativo: '',
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
        cargo: '',
        material_entregado: false,
        material_cantidad: null,
        comentarios: '',
        proxima_visita: '',
        resultado_visita: 'seguimiento', 
    }
});

const fetchEstados = async () => {
    try {
        const response = await axios.get('/estados'); 
        estados.value = response.data;
    } catch (e) {
        console.error("Error al cargar estados:", e);
    }
};

const searchBooks = () => {
    if (currentBook.titulo.length < 3) {
        bookSuggestions.value = [];
        return;
    }
    if (bookTimer) clearTimeout(bookTimer);
    bookTimer = setTimeout(async () => {
        try {
            const res = await axios.get('/search/libros', { params: { query: currentBook.titulo } });
            bookSuggestions.value = res.data;
        } catch (err) { console.error(err); }
    }, 400);
};

const selectBook = (book) => {
    currentBook.id = book.id;
    currentBook.titulo = book.titulo;
    bookSuggestions.value = [];
};

const addBookToList = () => {
    if (!currentBook.id) return;
    selectedBooks.value.push({ ...currentBook });
    currentBook.id = null;
    currentBook.titulo = '';
};

const getLocation = () => {
    if (!navigator.geolocation) return;
    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.plantel.latitud = position.coords.latitude;
            form.plantel.longitud = position.coords.longitude;
            gettingLocation.value = false;
        },
        () => { gettingLocation.value = false; },
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

const handleSubmit = async () => {
    errorMessage.value = null;
    loading.value = true;

    // Sincronizamos la lista de libros seleccionados en un string plano
    const librosTexto = selectedBooks.value.map(b => b.titulo).join(', ');

    /**
     * SINCRONIZACIÓN CRÍTICA CON EL BACKEND:
     * El controlador valida 'plantel.*' y 'visita.*'.
     * Pero tu controlador (visto anteriormente) usa 'proxima_visita' como llave en el objeto visita
     * para asignarla a la columna 'proxima_visita_estimada'.
     */
    const payload = {
        plantel: {
            name: form.plantel.name,
            nivel_educativo: form.plantel.nivel_educativo,
            direccion: locationMode.value === 'gps' ? 'Capturada vía GPS' : form.plantel.direccion,
            estado_id: locationMode.value === 'gps' ? null : (form.plantel.estado_id || null),
            telefono: form.plantel.telefono,
            email: form.plantel.email,
            director: form.plantel.director,
            latitud: form.plantel.latitud || null,
            longitud: form.plantel.longitud || null
        },
        visita: {
            fecha: form.visita.fecha,
            persona_entrevistada: form.visita.persona_entrevistada,
            cargo: form.visita.cargo,
            libros_interes: librosTexto,
            material_entregado: form.visita.material_entregado,
            material_cantidad: form.visita.material_entregado ? (form.visita.material_cantidad || 0) : null,
            comentarios: form.visita.comentarios,
            resultado_visita: form.visita.resultado_visita,
            // Sincronización: enviamos 'proxima_visita' porque el controlador espera esa llave para validación y mapeo
            proxima_visita: form.visita.resultado_visita === 'seguimiento' ? (form.visita.proxima_visita || null) : null,
            proxima_accion: 'visita'
        }
    };

    try {
        await axios.post('/visitas/primera', payload);
        showSuccessModal.value = true;
    } catch (err) {
        lastErrorStatus.value = err.response?.status || 'Error';
        if (err.response) {
            if (err.response.status === 422 && err.response.data.errors) {
                const errors = err.response.data.errors;
                const firstErrorKey = Object.keys(errors)[0];
                errorMessage.value = `Error de Validación: ${errors[firstErrorKey][0]}`;
            } else {
                // Captura el mensaje detallado del servidor si existe (QueryException, etc)
                errorMessage.value = err.response.data.error || err.response.data.message || "Fallo interno al insertar en la base de datos.";
            }
        } else {
            errorMessage.value = "Error de conexión con el servidor Laravel.";
        }
        console.error("Detalle técnico del error:", err);
    } finally {
        loading.value = false;
    }
};

const goToHistory = () => {
    showSuccessModal.value = false;
    router.push('/visitas');
};

onMounted(fetchEstados);
</script>

<style scoped>
.form-section { background: #fff; padding: 25px; border-radius: 16px; border: 1px solid #eef2f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.form-section-inner { background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; }
.section-title { color: var(--brand-red-dark); font-weight: 900; font-size: 1.2rem; border-bottom: 2px solid #fee2e2; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Autocomplete Libros */
.relative { position: relative; }
.autocomplete-list { 
    position: absolute; 
    z-index: 2000; 
    width: 100%; 
    background: white; 
    border: 1px solid #e2e8f0; 
    border-radius: 8px; 
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); 
    max-height: 200px; 
    overflow-y: auto; 
    list-style: none; 
    padding: 0; 
    margin: 5px 0 0; 
}
.autocomplete-list li { padding: 10px 15px; cursor: pointer; font-size: 0.85rem; border-bottom: 1px solid #f1f5f9; }
.autocomplete-list li:hover { background: #f8fafc; color: var(--brand-red-dark); }

/* MODAL */
.modal-overlay-custom { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 3000; }
.modal-content-success { background: white; padding: 40px; border-radius: 24px; width: 90%; max-width: 400px; text-align: center; }
.success-icon-wrapper { width: 70px; height: 70px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px; }
.btn-modal-success { width: 100%; background: #0f172a; color: white; padding: 14px; border-radius: 12px; font-weight: 700; border: none; cursor: pointer; }
</style>