<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Registro de Primera Visita</h1>
                    <p>Ingresa la información del nuevo plantel (Prospecto) y los detalles de la entrevista.</p>
                </div>
                <button @click="$router.push('/visitas')" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
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
                            <input v-model="form.plantel.name" type="text" class="form-input" placeholder="Ej: Escuela Primaria Niños Héroes" required>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label>Nivel Educativo</label>
                                <select v-model="form.plantel.nivel_educativo" class="form-input" required>
                                    <option value="">Seleccionar...</option>
                                    <option>Preescolar</option>
                                    <option>Primaria</option>
                                    <option>Secundaria</option>
                                    <option>Bachillerato</option>
                                    <option>Superior</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Estado</label>
                                <select v-model="form.plantel.estado_id" class="form-input" required>
                                    <option value="">Seleccionar Estado...</option>
                                    <option v-for="e in estados" :key="e.id" :value="e.id">{{ e.estado }}</option>
                                </select>
                                <p v-if="!estados.length && !loadingStates" class="text-xs text-red-500 mt-1">
                                    No se pudieron cargar los estados. Verifique la conexión.
                                </p>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label>Dirección Completa</label>
                            <textarea v-model="form.plantel.direccion" class="form-input" rows="2" required></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label>Celular</label>
                                <input v-model="form.plantel.telefono" type="tel" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label>Teléfono Fijo</label>
                                <input v-model="form.plantel.tel_fijo" type="tel" class="form-input">
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label>Correo Electrónico</label>
                            <input v-model="form.plantel.email" type="email" class="form-input" required>
                        </div>

                        <div class="form-group mb-4">
                            <label>Nombre del Director / Coordinador</label>
                            <input v-model="form.plantel.director" type="text" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label>Día y hora sugerida para contactar</label>
                            <input v-model="form.plantel.horario_contacto" type="text" class="form-input" placeholder="Ej: Lunes a las 10:00 AM">
                        </div>
                    </div>

                    <!-- BLOQUE 2: DETALLES DE LA VISITA -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-handshake"></i> Detalles de la Visita
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label>Fecha de Visita</label>
                                <input v-model="form.visita.fecha" type="date" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label>Próxima Visita Estimada</label>
                                <input v-model="form.visita.proxima_visita" type="date" class="form-input">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label>Persona Entrevistada</label>
                                <input v-model="form.visita.persona_entrevistada" type="text" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label>Cargo</label>
                                <input v-model="form.visita.cargo" type="text" class="form-input" required>
                            </div>
                        </div>

                        <!-- SECCIÓN DE LIBROS DE INTERÉS -->
                        <div class="form-section-inner mb-6">
                            <label class="font-bold text-gray-700 block mb-2">Agregar Libros de Interés</label>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                                <div class="md:col-span-6 relative">
                                    <input 
                                        v-model="currentBook.titulo" 
                                        type="text" 
                                        class="form-input" 
                                        placeholder="Buscar libro..."
                                        @input="searchBooks"
                                    >
                                    <ul v-if="bookSuggestions.length" class="autocomplete-list">
                                        <li v-for="book in bookSuggestions" :key="book.id" @click="selectBook(book)">
                                            {{ book.titulo }} ({{ book.editorial }})
                                        </li>
                                    </ul>
                                </div>
                                <div class="md:col-span-4">
                                    <input 
                                        v-model="currentBook.comentario" 
                                        type="text" 
                                        class="form-input" 
                                        placeholder="Comentario sobre el libro"
                                    >
                                </div>
                                <div class="md:col-span-2">
                                    <button type="button" @click="addBookToList" class="btn-primary w-full" :disabled="!currentBook.id">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Tabla de libros agregados -->
                            <div v-if="selectedBooks.length" class="table-responsive mt-4 border rounded-lg">
                                <table class="min-width-full divide-y-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Libro</th>
                                            <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Comentario</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="(item, index) in selectedBooks" :key="index">
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ item.titulo }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500 italic">{{ item.comentario || 'Sin comentario' }}</td>
                                            <td class="px-4 py-2 text-right">
                                                <button type="button" @click="removeBook(index)" class="text-red-500 hover:text-red-700">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input v-model="form.visita.material_entregado" type="checkbox" class="w-5 h-5">
                                <span class="font-bold text-gray-700">¿Se entregó material de promoción?</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label>Comentarios Generales de la Entrevista</label>
                            <textarea v-model="form.visita.comentarios" class="form-input" rows="4" placeholder="Escribe aquí los puntos más importantes tratados..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-4 border-t pt-6">
                    <button type="button" @click="$router.push('/visitas')" class="btn-secondary" :disabled="loading">Cancelar</button>
                    <button type="submit" class="btn-primary px-10" :disabled="loading">
                        <i class="fas fa-save mr-2"></i> {{ loading ? 'Guardando...' : 'Finalizar Registro' }}
                    </button>
                </div>
            </form>

            <div v-if="errorMessage" class="error-message-container mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700 font-bold mb-1">Error al guardar:</p>
                <p class="text-red-600 text-sm">{{ errorMessage }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const loading = ref(false);
const loadingStates = ref(false);
const errorMessage = ref(null);
const estados = ref([]);

// Lógica de Libros
const currentBook = reactive({
    id: null,
    titulo: '',
    comentario: ''
});
const bookSuggestions = ref([]);
const selectedBooks = ref([]);
let bookSearchTimeout = null;

const form = ref({
    plantel: {
        name: '',
        tipo: 'PROSPECTO',
        nivel_educativo: '',
        direccion: '',
        estado_id: '',
        telefono: '',
        tel_fijo: '',
        email: '',
        web: '',
        director: '',
        horario_contacto: ''
    },
    visita: {
        fecha: new Date().toISOString().split('T')[0],
        persona_entrevistada: '',
        cargo: '',
        libros_interes: '', // INICIALIZADO COMO STRING
        material_entregado: false,
        comentarios: '',
        proxima_visita: ''
    }
});

const fetchEstados = async () => {
    loadingStates.value = true;
    try {
        const response = await axios.get('/estados'); 
        estados.value = response.data;
    } catch (e) {
        console.error("Error al cargar estados:", e);
        estados.value = []; 
    } finally {
        loadingStates.value = false;
    }
};

const searchBooks = () => {
    currentBook.id = null;
    clearTimeout(bookSearchTimeout);
    if (currentBook.titulo.length < 3) {
        bookSuggestions.value = [];
        return;
    }

    bookSearchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get('/search/libros', {
                params: { query: currentBook.titulo }
            });
            bookSuggestions.value = response.data;
        } catch (err) {
            console.error('Error buscando libros:', err);
        }
    }, 300);
};

const selectBook = (book) => {
    currentBook.id = book.id;
    currentBook.titulo = book.titulo;
    bookSuggestions.value = [];
};

const addBookToList = () => {
    if (!currentBook.id) return;
    
    selectedBooks.value.push({
        id: currentBook.id,
        titulo: currentBook.titulo,
        comentario: currentBook.comentario
    });

    currentBook.id = null;
    currentBook.titulo = '';
    currentBook.comentario = '';
};

const removeBook = (index) => {
    selectedBooks.value.splice(index, 1);
};

const handleSubmit = async () => {
    loading.value = true;
    errorMessage.value = null;

    // TRANSFORMACIÓN CRÍTICA: Convertimos el arreglo de objetos a un string legible
    // para cumplir con la validación del servidor (vistas.libros_interes debe ser string)
    if (selectedBooks.value.length > 0) {
        form.value.visita.libros_interes = selectedBooks.value
            .map(book => `${book.titulo}${book.comentario ? ' (Nota: ' + book.comentario + ')' : ''}`)
            .join(', ');
    } else {
        form.value.visita.libros_interes = '';
    }

    try {
        const response = await axios.post('/visitas/primera', form.value);
        alert(response.data.message || "Registro completado con éxito.");
        router.push('/visitas');
    } catch (err) {
        if (err.response && err.response.data) {
            errorMessage.value = err.response.data.message || "Error interno del servidor.";
            if (err.response.data.errors) {
                const errors = Object.values(err.response.data.errors).flat().join(", ");
                errorMessage.value += " Detalles: " + errors;
            }
        } else {
            errorMessage.value = "Error de red o CORS. Verifique la conexión.";
        }
        console.error("Error en handleSubmit:", err);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchEstados);
</script>

<style scoped>
.form-section {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
.form-section-inner {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.section-title {
    color: var(--brand-red-dark);
    font-weight: 800;
    font-size: 1.2rem;
    border-bottom: 2px solid #fee2e2;
    padding-bottom: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.relative { position: relative; }

.autocomplete-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 50;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    max-height: 200px;
    overflow-y: auto;
    list-style: none;
    padding: 0;
    margin: 0;
}

.autocomplete-list li {
    padding: 10px 15px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background 0.2s;
}

.autocomplete-list li:hover {
    background: #f8fafc;
    color: var(--brand-red-dark);
}

.table-responsive {
    background: white;
    max-height: 300px;
    overflow-y: auto;
}

.min-width-full {
    width: 100%;
    border-collapse: collapse;
}

.gap-3 { gap: 12px; }

.error-message-container {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>