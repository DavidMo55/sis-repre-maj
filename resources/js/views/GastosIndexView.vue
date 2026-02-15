<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            
            <div class="module-header detail-header-flex">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight">Gestión de Gastos</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium uppercase tracking-widest mt-1">Visualiza tus gastos asignados y gestiona los comprobantes de forma directa.</p>
                </div>
                <button 
                    @click="router.push({ name: 'GastosCreate' })" 
                    class="btn-primary flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-sm font-black shadow-lg shadow-red-100 transition-all"
                >
                    <i class="fas fa-plus-circle"></i> Registrar Nuevo Gasto
                </button>
            </div>

            <div class="filter-section form-section bck mt-6 shadow-sm border border-slate-200">
                <div class="section-title">
                    <i class="fas fa-filter text-slate-400"></i> Filtros y Búsqueda
                </div>
                <div class="filter-grid mt-4">
                    <div class="form-group col-span-2">
                        <label for="search">Buscar por viaje:</label>
                        <div class="relative">
                            <i class="fas fa-search search-icon text-slate-400"></i>
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
                        <label for="registroStatus">Estado Registro:</label>
                        <select v-model="filters.status" id="registroStatus" class="form-input font-bold">
                            <option value="all">Todos los estados</option>
                            <option value="BORRADOR">Borradores</option>
                            <option value="FINALIZADO">Finalizados</option>
                        </select>
                    </div>

                    <!-- NUEVO FILTRO: Auditoría de cambios -->
                    <div class="form-group">
                        <label for="modificadoStatus">Modificaciones:</label>
                        <select v-model="filters.modificado" id="modificadoStatus" class="form-input font-bold text-red-700">
                            <option value="all">Cualquiera</option>
                            <option value="edited">Solo Editados</option>
                            <option value="original">Solo Originales</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="comprobanteStatus">Archivos:</label>
                        <select v-model="filters.tiene_comprobante" id="comprobanteStatus" class="form-input font-bold">
                            <option value="all">Mostrar Todos</option>
                            <option value="missing">Pendientes</option>
                            <option value="uploaded">Completados</option>
                        </select>
                    </div>
                    
                    <div class="form-group flex items-end gap-2">
                        <button @click="fetchGastos" class="btn-primary flex-1 py-3 text-xs">
                            <i class="fas fa-sync-alt"></i> Buscar
                        </button>
                        <button @click="resetFilters" class="btn-secondary px-4 text-slate-400" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <br>
            <!-- TABLA DE GASTOS -->
            <div v-if="loading" class="loading-state mt-8 text-center py-20">
                <i class="fas fa-circle-notch fa-spin text-4xl mb-4 text-red-600"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Sincronizando información...</p>
            </div>

            <div v-else-if="error" class="error-message text-center py-10 bg-red-50 rounded-[2.5rem] border-2 border-red-100 mx-2">
                <i class="fas fa-exclamation-circle text-red-600 text-2xl mb-2"></i>
                <p class="text-red-800 font-black uppercase text-xs">{{ error }}</p>
            </div>

            <div v-else-if="filteredGastos.length === 0" class="mt-8 text-center py-20 bg-white rounded-[3rem] border-2 border-dashed border-slate-100 shadow-sm">
                <i class="fas fa-folder-open mb-3 text-3xl text-slate-200"></i>
                <p class="text-slate-500 font-bold uppercase text-[10px] tracking-widest">No se encontraron gastos con estos criterios.</p>
            </div>

            <div v-else class="table-responsive table-shadow-lg mt-8 border rounded-[2rem] overflow-hidden shadow-sm bg-white animate-fade-in">
                <table class="min-width-full divide-y divide-slate-100">
                    <thead class="bg-slate-900">
                        <tr>
                            <th class="table-header w-32 text-white">Fecha</th>
                            <th class="table-header text-white">Paquete de gastos</th>
                            <th class="table-header text-center w-36 text-white">Registro</th>
                            <th class="table-header text-right w-36 text-white">Monto</th>
                            <th class="table-header text-center w-36 text-white">Archivos</th>
                            <th class="px-6 py-3 w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        <tr v-for="gasto in filteredGastos" :key="gasto.id" 
                            class="hover:bg-slate-50/50 transition-colors"
                            :class="{'bg-amber-50/10': gasto.status === 'BORRADOR'}">
                            
                            <td class="table-cell table-cell-bold text-slate-700">
                                {{ formatDate(gasto.fecha) }}
                            </td>

                            <td class="table-cell">
                                <div class="text-sm font-black  uppercase leading-tight text-truncate max-w-concepto" :title="gasto.concepto">
                                      <i class="fas text-slate-700 sl7 fa-map-marker-alt mr-1 opacity-50"></i> {{ gasto.estado_nombre }}
                                </div>
                               
                            </td>

                            <td class="table-cell text-center">
                                <span class="status-badge" 
                                      :class="gasto.status === 'BORRADOR' ? 'badge-draft' : 'badge-final'">
                                    <i :class="gasto.status === 'BORRADOR' ? 'fas fa-edit' : 'fas fa-lock'" class="mr-1.5 opacity-60"></i>
                                    {{ gasto.status }}
                                </span>
                            </td>

                            <td class="table-cell table-cell-bold text-right text-red-700 text-lg">
                                {{ formatCurrency(gasto.monto) }}
                            </td>

                            <td class="table-cell text-center">
                                <span class="status-badge" 
                                      :class="gasto.comprobantes?.length ? 'badge-completed' : 'badge-pending'">
                                    <i :class="gasto.comprobantes?.length ? 'fas fa-file-invoice' : 'fas fa-clock'" class="mr-1.5 opacity-60"></i>
                                    {{ gasto.comprobantes?.length ? 'LISTO' : 'FALTANTE' }}
                                </span>
                            </td>

                            <td class="table-cell text-right">
                                <button @click="viewDetails(gasto)" class="text-red-link flex items-center justify-end gap-1 w-full">
                                    <i class="fas fa-eye"></i> VER
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
import { ref, onMounted, computed, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const gastos = ref([]);
const loading = ref(false);
const error = ref(null);

const filters = reactive({
    search: '',
    fecha_desde: '',
    fecha_hasta: '',
    tiene_comprobante: 'all', 
    status: 'all',
    modificado: 'all' 
});

/**
 * Lógica para determinar si un registro fue editado.
 * Se considera editado si updated_at es posterior a created_at por más de 2 segundos.
 */
const isEdited = (g) => {
    if (!g.created_at || !g.updated_at) return false;
    const created = new Date(g.created_at).getTime();
    const updated = new Date(g.updated_at).getTime();
    // Margen de 2 seg para procesos de guardado asíncronos iniciales
    return updated > (created + 2000);
};

const filteredGastos = computed(() => {
    let result = [...gastos.value];

    // 1. Filtrar por búsqueda de texto
    if (filters.search.trim() !== '') {
        const searchTerm = filters.search.toLowerCase();
        result = result.filter(g => 
            g.concepto.toLowerCase().includes(searchTerm) || 
            g.estado_nombre?.toLowerCase().includes(searchTerm)
        );
    }

    // 2. Filtrar por estado del registro
    if (filters.status !== 'all') {
        result = result.filter(g => g.status === filters.status);
    }

    // 3. Filtrar por presencia de comprobantes
    if (filters.tiene_comprobante === 'missing') {
        result = result.filter(g => !g.comprobantes || g.comprobantes.length === 0);
    } else if (filters.tiene_comprobante === 'uploaded') {
        result = result.filter(g => g.comprobantes && g.comprobantes.length > 0);
    }

    // 4. Filtrar por estado de edición (Logica nueva)
    if (filters.modificado === 'edited') {
        result = result.filter(g => isEdited(g));
    } else if (filters.modificado === 'original') {
        result = result.filter(g => !isEdited(g));
    }

    // 5. Ordenar: Nuevos arriba
    result.sort((a, b) => b.id - a.id);

    return result;
});

const fetchGastos = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/gastos'); 
        const data = response.data.data || response.data;
        gastos.value = Array.isArray(data) ? data : []; 
    } catch (err) {
        error.value = 'No se pudo sincronizar el historial de gastos.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    Object.assign(filters, {
        search: '',
        fecha_desde: '',
        fecha_hasta: '',
        tiene_comprobante: 'all',
        status: 'all',
        modificado: 'all'
    });
    fetchGastos();
};

const viewDetails = (gasto) => {
    router.push({ name: 'GastosDetail', params: { id: gasto.id } });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (value) => {
    return Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};

onMounted(fetchGastos);
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
    z-index: 10;
}

.text-red-link {
    background: none;
    border: none;
    cursor: pointer;
    color: #b91c1c;
    text-decoration: none;
    transition: color 0.2s;
    font-weight: 900;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.text-red-link:hover {
    color: #7f1d1d;
    text-decoration: underline;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
    background: white;
}

table {
    table-layout: fixed;
    width: 100%;
}

.table-header {
    padding: 18px 20px;
    font-size: 0.7rem;
    font-weight: 900;
    color: #64748b;
    text-transform: uppercase;
    text-align: left;
    letter-spacing: 0.1em;
}

.table-cell {
    padding: 20px;
    font-size: 0.85rem;
    vertical-align: middle;
}

.table-cell-bold {
    font-weight: 800;
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
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

/* Badges para Archivos */
.badge-completed {
    background-color: #f0f9ff;
    color: #0369a1;
}

.badge-pending {
    background-color: #fef2f2;
    color: #b91c1c;
}

/* Badges para Estado Registro */
.badge-draft {
    background-color: #fffbeb;
    color: #b45309;
}

.badge-final {
    background-color: #95c48e;
    color: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Badges para Auditoría de Modificaciones */
.badge-edited {
    background-color: #f5f3ff;
    color: #7c3aed;
    border: 1px solid #ddd6fe;
}

.badge-original {
    background-color: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.table-shadow-lg {
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
}

.section-title { 
    font-weight: 900; 
    color: #1e293b; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    text-transform: uppercase; 
    font-size: 0.8rem; 
    letter-spacing: 1px; 
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
.btn-secondary:hover { background-color: #f8fafc; border-color: #94a3b8; }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-2 block tracking-widest; }

.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05); }

.sl7{
    font-weight: bold;
}
</style>