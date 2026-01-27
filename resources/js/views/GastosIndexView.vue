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

            <div class="filter-section form-section bck">
                <div class="section-title">
                    <i class="fas fa-filter"></i> Filtros y Búsqueda
                </div>
                <div class="filter-grid">
                    <div class="form-group col-span-2">
                        <label for="search">Buscar por viaje:</label>
                        <div class="relative">
                            <i class="fas fa-search search-icon"></i>
                            <input 
                                type="text" 
                                id="search" 
                                v-model="filters.search" 
                                class="form-input pl-10" 
                                placeholder="Ej: Hidalgo, CDMX..."
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
                            <i class="fas fa-eraser"></i> Borrar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <br>
            <!-- TABLA DE GASTOS -->
            <div v-if="loading" class="loading-state mt-8 text-center py-10">
                <i class="fas fa-spinner fa-spin text-3xl mb-2 text-red-600"></i>
                <p class="text-gray-500 font-medium">Consultando registros...</p>
            </div>

            <div v-else-if="error" class="error-message text-center py-4 text-red-600 font-bold">
                {{ error }}
            </div>

            <div v-else-if="filteredGastos.length === 0" class="cart-empty-message mt-8 text-center py-10 bg-gray-50 rounded-xl border-2 border-dashed">
                <i class="fas fa-info-circle mb-2 text-xl text-gray-300"></i>
                <p class="mt-2 text-gray-500">No se encontraron gastos que coincidan con los filtros aplicados.</p>
            </div>

            <div v-else class="table-responsive table-shadow-lg mt-8 border rounded-xl overflow-hidden shadow-sm">
                <table class="min-width-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="table-header">Fecha</th>
                            <th class="table-header">Paquete de gastos</th>
                            <th class="table-header text-right">Monto</th>
                            <th class="table-header text-center">Estado</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <tr v-for="gasto in filteredGastos" :key="gasto.id" 
                            class="hover:bg-gray-50 transition-colors"
                            :class="{'bg-red-50/30': !gasto.comprobantes.length}">
                            
                            <td class="table-cell table-cell-bold text-gray-700">
                                {{ formatDate(gasto.fecha) }}
                            </td>

                            <td class="table-cell">
                                <div class="text-sm font-medium text-gray-800 text-truncate max-w-concepto" :title="gasto.concepto">
                                    {{ gasto.concepto }}
                                </div>
                            </td>

                            <td class="table-cell table-cell-bold text-right text-gray-900">
                                {{ formatCurrency(gasto.monto) }}
                            </td>

                            <td class="table-cell text-center">
                                <span class="status-badge" 
                                      :class="gasto.comprobantes.length ? 'badge-completed' : 'badge-pending'">
                                    <i :class="gasto.comprobantes.length ? 'fas fa-check-circle' : 'fas fa-clock'" class="mr-1"></i>
                                    {{ gasto.comprobantes.length ? 'COMPLETADO' : 'PENDIENTE' }}
                                </span>
                            </td>

                            <td class="table-cell text-right">
                                <button @click="viewDetails(gasto)" class="text-red-link">
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
    search: '',
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

    // 3. Ordenar: Conforme se agregan (Nuevos arriba por ID)
    result.sort((a, b) => b.id - a.id);

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
        gastos.value = response.data.data || response.data; 
    } catch (err) {
        error.value = 'No se pudo obtener la lista de gastos.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

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
.bck{
 background-color: #fdfdfd; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px;
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
}

.status-badge {
    padding: 6px 12px;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
}

.completed-row:hover {
    background-color: #f0fdf4;
}

.text-red-link {
    background: none;
    border: none;
    cursor: pointer;
    color: #b91c1c;
    text-decoration: none;
    transition: color 0.2s;
    font-weight: 700;
    font-size: 0.85rem;
}

.text-red-link:hover {
    color: #7f1d1d;
    text-decoration: underline;
}

.gap-2 { gap: 8px; }

.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    background: white;
}

table {
    table-layout: fixed;
    width: 100%;
    border-collapse: collapse;
}

.table-header {
    padding: 14px 16px;
    font-size: 0.7rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    text-align: left;
    letter-spacing: 0.025em;
}

.table-cell {
    padding: 16px;
    font-size: 0.85rem;
    vertical-align: middle;
}

.table-cell-bold {
    font-weight: 700;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.max-w-concepto {
    max-width: 250px;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.badge-completed {
    background-color: #dcfce7;
    color: #166534;
}

.badge-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.table-shadow-lg {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.text-right { text-align: right; }
.text-center { text-align: center; }

.btn-primary { 
    background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); 
    color: white; 
    border-radius: 20px; 
    font-weight: 900; 
    cursor: pointer; 
    border: none; 
    box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); 
    transition: all 0.2s; 
    text-transform: uppercase; 
    font-size: 0.8rem; 
    letter-spacing: 0.05em; 
}
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.btn-secondary {
    padding: 8px 15px;
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    cursor: pointer;
}
</style>