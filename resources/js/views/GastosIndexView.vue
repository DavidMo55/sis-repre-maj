<template>
    <div class="content-wrapper">
        <div class="module-page">
            
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Gestión de Gastos</h1>
                    <p>Visualiza tus gastos asignados y gestiona los comprobantes de forma directa.</p>
                </div>
                <button 
                    @click="router.push({ name: 'GastosCreate' })" 
                    class="btn-primary flex-row-centered gap-2 px-6 shadow-lg"
                >
                    <i class="fas fa-plus-circle"></i> Registrar Nuevo Gasto
                </button>
            </div>

            <div class="filter-section form-section">
                <div class="section-title">
                    <i class="fas fa-filter"></i> Filtros y Búsqueda
                </div>
                <div class="filter-grid">
                    <div class="form-group col-span-2">
                        <label for="search">Buscar por concepto:</label>
                        <div class="relative">
                            <i class="fas fa-search search-icon"></i>
                            <input 
                                type="text" 
                                id="search" 
                                v-model="filters.search" 
                                class="form-input pl-10" 
                                placeholder="Ej: Gasolina, Alimentos..."
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fechaDesde">Desde:</label>
                        <input type="date" id="fechaDesde" v-model="filters.fecha_desde" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="fechaHasta">Hasta:</label>
                        <input type="date" id="fechaHasta" v-model="filters.fecha_hasta" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="comprobanteStatus">Estado:</label>
                        <select v-model="filters.tiene_comprobante" id="comprobanteStatus" class="form-input">
                            <option value="all">Mostrar Todos</option>
                            <option value="missing">Pendientes</option>
                            <option value="uploaded">Completados</option>
                        </select>
                    </div>
                    
                    <div class="form-group flex items-end gap-2">
                        <button @click="fetchGastos" class="btn-primary flex-1">
                            <i class="fas fa-sync-alt"></i> Buscar
                        </button>
                        <button @click="resetFilters" class="btn-secondary" title="Limpiar Filtros">
                            <i class="fas fa-eraser">Borrar Filtros</i>
                        </button>
                    </div>
                </div>
            </div>


            <!-- TABLA DE GASTOS -->
            <div v-if="loading" class="loading-state mt-8">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>Consultando registros...</p>
            </div>

            <div v-else-if="error" class="error-message text-center py-4">
                {{ error }}
            </div>

            <div v-else-if="filteredGastos.length === 0" class="cart-empty-message mt-8">
                <i class="fas fa-info-circle mb-2 text-xl"></i>
                <p class="mt-2">No se encontraron gastos que coincidan con los filtros aplicados.</p>
            </div>

            <div v-else class="table-responsive table-shadow-lg mt-8">
                <table class="min-width-full divide-y-gray-200">
                    <thead class="bg-light-gray">
                        <tr>
                            <th class="table-header">Fecha</th>
                            <th class="table-header">Concepto</th>
                            <th class="table-header text-right">Monto</th>
                            <th class="table-header text-center">Facturado</th>
                            <th class="table-header text-center">Estado</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y-gray-200">
                        <tr 
                            v-for="gasto in filteredGastos" 
                            :key="gasto.id"
                            :class="{'missing-comprobante-row': !gasto.comprobantes.length, 'completed-row': gasto.comprobantes.length}"
                        >
                            <td class="table-cell table-cell-bold">{{ formatDate(gasto.fecha) }}</td>
                            <td class="table-cell text-medium-gray">{{ gasto.concepto }}</td>
                            <td class="table-cell table-cell-bold text-right">{{ formatCurrency(gasto.monto) }}</td>
                            <td class="table-cell text-center">
                                <span v-if="gasto.facturado" class="text-green-600 font-bold">SÍ</span>
                                <span v-else class="text-gray-400">NO</span>
                            </td>
                            <td class="table-cell text-center">
                                <span :class="getComprobanteClass(gasto.comprobantes.length)">
                                    <i :class="gasto.comprobantes.length ? 'fas fa-check-circle' : 'fas fa-clock'"></i>
                                    {{ gasto.comprobantes.length ? 'COMPLETADO' : 'PENDIENTE' }}
                                </span>
                            </td>
                            <td class="table-cell-action">
                                <button 
                                    @click="openUploadModal(gasto)" 
                                    class="btn-action-link"
                                    :class="gasto.comprobantes.length ? 'text-teal-600' : 'text-red-700'"
                                >
                                    <i class="fas fa-file-upload"></i> 
                                    {{ gasto.comprobantes.length ? 'Subir Más' : 'Subir Comprobante' }}
                                </button>
                                
                                <button @click="viewDetails(gasto)" class="text-red-link font-bold ml-4">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
        </div>
        
        <!-- MODAL DE SUBIDA -->
        <UploadComprobanteModal 
            :visible="showUploadModal"
            :gasto="selectedGasto"
            @close="showUploadModal = false"
            @uploaded="handleUploadSuccess"
        />
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';
import UploadComprobanteModal from '../components/Modales/SubidaComprobantes.vue';

const router = useRouter();
const gastos = ref([]);
const loading = ref(false);
const error = ref(null);

const showUploadModal = ref(false);
const selectedGasto = ref(null);

const filters = ref({
    search: '', // NUEVO: Campo de búsqueda
    fecha_desde: '',
    fecha_hasta: '',
    tiene_comprobante: 'all', 
});

const filteredGastos = computed(() => {
    let result = [...gastos.value];

    // 1. Filtrar por búsqueda de texto (Concepto)
    if (filters.value.search.trim() !== '') {
        const searchTerm = filters.value.search.toLowerCase();
        result = result.filter(g => g.concepto.toLowerCase().includes(searchTerm));
    }

    // 2. Filtrar por estado de comprobante
    if (filters.value.tiene_comprobante === 'missing') {
        result = result.filter(g => g.comprobantes.length === 0);
    } else if (filters.value.tiene_comprobante === 'uploaded') {
        result = result.filter(g => g.comprobantes.length > 0);
    }

    // 3. Ordenar: Pendientes arriba
    result.sort((a, b) => {
        const aDone = a.comprobantes.length > 0;
        const bDone = b.comprobantes.length > 0;
        if (aDone === bDone) return new Date(b.fecha) - new Date(a.fecha);
        return aDone ? 1 : -1;
    });

    return result;
});

const fetchGastos = async () => {
    loading.value = true;
    error.value = null;
    try {
        const params = {
            fecha_desde: filters.value.fecha_desde,
            fecha_hasta: filters.value.fecha_hasta,
        };
        const response = await axios.get('/gastos', { params }); 
        gastos.value = response.data.data; 
    } catch (err) {
        error.value = 'No se pudo obtener la lista de gastos.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

// NUEVO: Función para resetear filtros
const resetFilters = () => {
    filters.value = {
        search: '',
        fecha_desde: '',
        fecha_hasta: '',
        tiene_comprobante: 'all',
    };
    fetchGastos();
};

const handleUploadSuccess = () => {
    fetchGastos(); 
};

const openUploadModal = (gasto) => {
    selectedGasto.value = gasto;
    showUploadModal.value = true;
};

const viewDetails = (gasto) => {
    router.push({ name: 'GastosDetail', params: { id: gasto.id } });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (value) => {
    return Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};

const getComprobanteClass = (count) => {
    const base = 'status-badge flex-row-centered gap-1 ';
    return count > 0 
        ? base + 'bg-green-100 text-green-800' 
        : base + 'bg-red-100 text-red-700 font-bold';
};

onMounted(() => {
    fetchGastos();
});
</script>

<style scoped>
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    align-items: flex-end;
}

/* Permitir que el buscador sea más ancho en pantallas grandes */
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
}

.status-badge {
    padding: 6px 12px;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
}

.missing-comprobante-row {
    background-color: #fff5f5;
    border-left: 4px solid #f87171;
}

.completed-row:hover {
    background-color: #f0fdf4;
}

.btn-action-link {
    background: none;
    border: none;
    font-weight: 700;
    cursor: pointer;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.text-red-link {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--brand-red-light);
    text-decoration: none;
    transition: color 0.2s;
}

.text-red-link:hover {
    color: var(--brand-red-dark);
    text-decoration: underline;
}

.gap-1 { gap: 4px; }
.gap-2 { gap: 8px; }
</style>