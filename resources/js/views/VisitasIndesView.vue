<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Seguimiento de Visitas</h1>
                    <p>Gestiona los prospectos y las interacciones con los planteles de forma directa.</p>
                </div>
                <div class="flex gap-3">
                    <router-link to="/primeras-visitas" class="btn-primary flex-row-centered gap-2">
                        <i class="fas fa-plus-circle"></i> Primera Visita
                    </router-link>
                    <router-link to="/visita-seguimiento" class="btn-secondary flex-row-centered gap-2">
                        <i class="fas fa-history"></i> Visita Subsecuente
                    </router-link>
                </div>
            </div>

            <div class="filter-section form-section mt-6">
                <div class="section-title">
                    <i class="fas fa-filter"></i> Filtros y Búsqueda
                </div>
                <div class="filter-grid">
                    <div class="form-group col-span-2">
                        <label for="search">Buscar Plantel o Persona:</label>
                        <div class="relative">
                            <i class="fas fa-search search-icon"></i>
                            <input 
                                type="text" 
                                id="search" 
                                v-model="filters.query" 
                                class="form-input pl-10" 
                                placeholder="Nombre de escuela o contacto..."
                                @keyup.enter="fetchVisitas"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fechaDesde">Desde:</label>
                        <input 
                            type="date" 
                            id="fechaDesde" 
                            v-model="filters.fecha_inicio" 
                            class="form-input"
                            @change="fetchVisitas"
                        >
                    </div>

                    <div class="form-group">
                        <label for="fechaHasta">Hasta:</label>
                        <input 
                            type="date" 
                            id="fechaHasta" 
                            v-model="filters.fecha_fin" 
                            class="form-input"
                            @change="fetchVisitas"
                        >
                    </div>

                    <div class="form-group">
                        <label for="resultadoStatus">Estado:</label>
                        <select 
                            v-model="filters.resultado" 
                            id="resultadoStatus" 
                            class="form-input"
                            @change="fetchVisitas"
                        >
                            <option value="">Mostrar Todos</option>
                            <option value="seguimiento">Seguimiento</option>
                            <option value="compra">Completados</option>
                            <option value="rechazo">Rechazados</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-100">
                    <button @click="resetFilters" class="btn-secondary" title="Limpiar Filtros">
                        <i class="fas fa-eraser mr-1"></i> Limpiar
                    </button>
                    <button @click="fetchVisitas" class="btn-primary py-2 px-8" :disabled="loading">
                        <i class="fas fa-sync-alt mr-1" :class="{'fa-spin': loading}"></i> 
                        {{ loading ? 'Buscando...' : 'Buscar' }}
                    </button>
                </div>
            </div>

            <div v-if="loading" class="loading-state py-10 mt-8 text-center">
                <i class="fas fa-spinner fa-spin text-3xl mb-2 text-red-600"></i>
                <p class="text-gray-500 font-medium">Consultando registros...</p>
            </div>

            <div v-else-if="visitas.length === 0" class="cart-empty-message mt-8 text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed">
                <i class="fas fa-info-circle mb-3 text-3xl text-gray-300"></i>
                <p class="text-gray-500 font-medium">No se encontraron visitas que coincidan con los filtros aplicados.</p>
            </div>

            <div v-else class="table-responsive table-shadow-lg mt-8 border rounded-xl overflow-hidden shadow-sm">
                <table class="min-width-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="table-header">Fecha</th>
                            <th class="table-header">Plantel</th>
                            <th class="table-header">Entrevistado</th>
                            <th class="table-header text-center">Tipo</th>
                            <th class="table-header text-center">Resultado</th>
                            <th class="table-header">Próxima</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <tr v-for="visita in visitas" :key="visita.id" class="hover:bg-gray-50 transition-colors">
                            <td class="table-cell table-cell-bold text-gray-700">{{ formatDate(visita.fecha) }}</td>
                            <td class="table-cell">
                                <div class="text-red-900 font-bold uppercase text-xs">{{ visita.nombre_plantel }}</div>
                                <div class="text-[10px] text-gray-400 uppercase tracking-tighter">{{ visita.nivel_educativo_plantel }}</div>
                            </td>
                            <td class="table-cell">
                                <div class="text-sm font-medium text-gray-800">{{ visita.persona_entrevistada }}</div>
                                <div class="text-[10px] text-gray-400 uppercase font-bold">{{ visita.cargo }}</div>
                            </td>
                            <td class="table-cell text-center">
                                <span :class="visita.es_primera_visita ? 'badge-blue' : 'badge-gray'">
                                    {{ visita.es_primera_visita ? 'Inicial' : 'Seguimiento' }}
                                </span>
                            </td>
                            <td class="table-cell text-center">
                                <span class="status-badge" :class="getOutcomeClass(visita.resultado_visita)">
                                    <i :class="getOutcomeIcon(visita.resultado_visita)" class="mr-1"></i>
                                    {{ (visita.resultado_visita || 'seguimiento').toUpperCase() }}
                                </span>
                            </td>
                            <td class="table-cell">
                                <div v-if="visita.proxima_visita_estimada" class="text-green-700 font-bold text-sm">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ formatDate(visita.proxima_visita_estimada) }}
                                </div>
                                <div v-else class="text-gray-300 text-[10px] italic">No agendada</div>
                            </td>
                            <td class="table-cell-action text-right">
                                <button @click="viewDetails(visita)" class="text-red-link font-bold text-sm">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const visitas = ref([]);
const loading = ref(true);

// Estado reactivo para los filtros
const filters = reactive({
    query: '',
    fecha_inicio: '',
    fecha_fin: '',
    resultado: ''
});

const fetchVisitas = async () => {
    loading.value = true;
    try {
        // Limpiamos los parámetros para no enviar campos vacíos
        const params = {};
        if (filters.query) params.search = filters.query;
        if (filters.fecha_inicio) params.desde = filters.fecha_inicio;
        if (filters.fecha_fin) params.hasta = filters.fecha_fin;
        if (filters.resultado) params.resultado = filters.resultado;

        const response = await axios.get('/visitas', { params });
        
        // Laravel paginate devuelve la data en response.data.data
        // Si no hay paginación, intentamos response.data
        const dataReceived = response.data.data || response.data;
        visitas.value = Array.isArray(dataReceived) ? dataReceived : [];
        
    } catch (err) {
        console.error("Error al cargar visitas:", err);
        visitas.value = [];
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    filters.query = '';
    filters.fecha_inicio = '';
    filters.fecha_fin = '';
    filters.resultado = '';
    fetchVisitas();
};

const formatDate = (dateString) => {
    if (!dateString) return null;
    // Ajuste para evitar desfase de zona horaria en objetos Date
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
};

const getOutcomeClass = (outcome) => {
    switch (outcome) {
        case 'compra': return 'bg-green-100 text-green-700 border border-green-200';
        case 'rechazo': return 'bg-red-100 text-red-700 border border-red-200';
        default: return 'bg-orange-100 text-orange-700 border border-orange-200';
    }
};

const getOutcomeIcon = (outcome) => {
    switch (outcome) {
        case 'compra': return 'fas fa-check-circle';
        case 'rechazo': return 'fas fa-times-circle';
        default: return 'fas fa-clock';
    }
};

const viewDetails = (visita) => {
    router.push({ name: 'VisitaDetalle', params: { id: visita.id } });
};

onMounted(fetchVisitas);
</script>

<style scoped>
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    align-items: flex-end;
}

@media (min-width: 1024px) {
    .col-span-2 {
        grid-column: span 2;
    }
}

.relative { position: relative; }
.pl-10 { padding-left: 2.5rem; }

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
    z-index: 10;
}

.filter-section {
    background-color: #fdfdfd;
    border: 1px solid #e2e8f0;
    padding: 20px;
    border-radius: 12px;
}

.table-header { padding: 14px 16px; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; text-align: left; letter-spacing: 0.025em; }
.table-cell { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
.table-cell-bold { font-weight: 700; }

.text-red-link { 
    color: #b91c1c; 
    background: none; 
    border: none; 
    cursor: pointer; 
    transition: color 0.2s; 
    font-weight: 700;
}
.text-red-link:hover { color: #7f1d1d; text-decoration: underline; }

.badge-blue { background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
.badge-gray { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 12px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }

.status-badge { 
    padding: 6px 12px; 
    border-radius: 20px; 
    font-size: 0.7rem; 
    font-weight: 800; 
    display: inline-flex; 
    align-items: center;
}

.detail-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .detail-header-flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}
</style>