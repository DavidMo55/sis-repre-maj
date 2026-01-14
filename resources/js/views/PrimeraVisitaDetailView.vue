<template>
    <div class="content-wrapper">
        <div class="module-page">
            <!-- Encabezado Adaptable -->
            <div class="module-header detail-header-flex">
                <div class="header-info overflow-hidden">
                    <h1 v-if="visita" class="header-title" :title="visita.nombre_plantel || visita.cliente?.name">
                        Bitácora: {{ visita.nombre_plantel || visita.cliente?.name || 'Sin nombre' }}
                    </h1>
                    <h1 v-else-if="loading" class="header-title">Cargando Bitácora...</h1>
                    <p class="header-subtitle">Resumen técnico de acuerdos, materiales y seguimiento comercial.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary flex-shrink-0 mt-mobile-4">
                    <i class="fas fa-arrow-left mr-2"></i> Volver al Historial
                </button>
            </div>

            <!-- Estado de Carga -->
            <div v-if="loading" class="loading-state py-12 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-red-600 mb-3"></i>
                <p class="text-gray-500 font-medium">Recuperando información de la base de datos...</p>
            </div>

            <!-- Manejo de Errores -->
            <div v-else-if="error" class="error-message-container mt-6 p-8 text-center bg-red-50 border border-red-200 rounded-xl">
                <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i>
                <h2 class="text-xl font-bold text-red-800">No se pudo cargar la visita</h2>
                <p class="text-red-600 mt-2">{{ error }}</p>
                <button @click="fetchVisitaDetail" class="btn-primary mt-6">Reintentar</button>
            </div>

            <!-- Contenido Principal -->
            <div v-else-if="visita" class="detail-container mt-6 animate-fade-in space-y-8">
                
                <!-- SECCIÓN SUPERIOR: GRID RESPONSIVO (1, 2 o 3 columnas) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- TARJETA 1: IDENTIDAD DEL PLANTEL -->
                    <div class="info-card border-brand">
                        <h2 class="card-title text-red-700"><i class="fas fa-school"></i> Plantel / Prospecto</h2>
                        <div class="mt-4 space-y-4 text-sm">
                            <div class="data-block">
                                <span class="label-xs">Nombre Oficial:</span>
                                <p class="text-red-900 font-bold leading-tight break-words">
                                    {{ visita.nombre_plantel || visita.cliente?.name || 'No disponible' }}
                                </p>
                            </div>
                            <div class="data-block">
                                <span class="label-xs">Niveles Educativos:</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span v-for="n in formatLevels(visita.nivel_educativo_plantel || visita.cliente?.nivel_educativo)" :key="n" class="level-badge">
                                        {{ n }}
                                    </span>
                                </div>
                            </div>
                            <div class="data-block">
                                <span class="label-xs">Dirección:</span>
                                <p class="text-gray-600 text-xs italic break-words">
                                    {{ visita.direccion_plantel || visita.cliente?.direccion || 'Sin dirección registrada' }}
                                </p>
                            </div>
                            <div class="pt-2 border-t border-gray-50 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="overflow-hidden">
                                    <span class="label-xs">Director:</span>
                                    <span class="text-gray-700 font-bold text-[11px] block truncate" :title="visita.director_plantel || visita.cliente?.contacto">
                                        {{ visita.director_plantel || visita.cliente?.contacto || 'N/A' }}
                                    </span>
                                </div>
                                <div class="overflow-hidden">
                                    <span class="label-xs">Teléfono:</span>
                                    <span class="text-gray-700 text-[11px] block truncate">
                                        {{ visita.telefono_plantel || visita.cliente?.telefono || 'N/A' }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="visita.latitud" class="bg-blue-50 p-3 rounded-xl border border-blue-100 flex items-center justify-between mt-2">
                                <div class="overflow-hidden mr-2">
                                    <span class="label-xs text-blue-700">GPS Capturado:</span>
                                    <span class="text-[9px] font-mono text-blue-600 uppercase">Verificado</span>
                                </div>
                                <a :href="`https://www.google.com/maps?q=${visita.latitud},${visita.longitud}`" target="_blank" class="text-blue-700 hover:text-blue-900 flex-shrink-0 p-2 bg-white rounded-lg shadow-sm">
                                    <i class="fas fa-map-marked-alt fa-lg"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- TARJETA 2: DETALLES DE LA ENTREVISTA -->
                    <div class="info-card">
                        <h2 class="card-title text-gray-800"><i class="fas fa-user-tie"></i> Datos de Entrevista</h2>
                        <div class="mt-4 space-y-4">
                            <div class="visit-data-box">
                                <span class="label-xs">Atendido por:</span>
                                <p class="text-red-700 font-bold break-words">{{ visita.persona_entrevistada || 'Persona no registrada' }}</p>
                                <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest truncate">{{ visita.cargo || 'Responsable' }}</p>
                            </div>
                            <div class="flex flex-wrap justify-between items-end gap-3">
                                <div class="min-w-[120px]">
                                    <span class="label-xs">Fecha de Visita:</span>
                                    <span class="text-gray-800 font-bold text-sm">{{ formatDate(visita.fecha) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="label-xs">Estatus Final:</span>
                                    <span :class="getOutcomeClass(visita.resultado_visita)" class="status-badge">
                                        {{ (visita.resultado_visita || 'seguimiento').toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="visita.comentarios" class="mt-2">
                                <span class="label-xs">Acuerdos y Notas:</span>
                                <p class="comment-text">
                                    "{{ visita.comentarios }}"
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- TARJETA 3: PRÓXIMA ACCIÓN / AGENDA -->
                    <div class="info-card">
                        <h2 class="card-title text-gray-800"><i class="fas fa-calendar-alt"></i> Seguimiento</h2>
                        <div class="mt-4 space-y-4">
                            <div class="visit-data-box border-l-4" :class="visita.proxima_visita_estimada ? 'border-l-green-500 bg-green-50/30' : 'border-l-gray-300'">
                                <span class="label-xs">Compromiso Agendado:</span>
                                <div v-if="visita.proxima_visita_estimada">
                                    <p class="text-green-700 font-black text-xl">{{ formatDate(visita.proxima_visita_estimada) }}</p>
                                    <p class="text-[10px] font-black uppercase text-green-600 tracking-widest mt-1">
                                        <i class="fas fa-bullseye mr-1"></i> {{ visita.proxima_accion === 'presentacion' ? 'Presentación Académica' : 'Visita de Seguimiento' }}
                                    </p>
                                </div>
                                <p v-else class="text-gray-400 italic text-xs py-2">No se agendó fecha de re-visita en esta sesión.</p>
                            </div>
                            
                            <div class="pt-4">
                                <span class="label-xs text-red-700 mb-2">Acciones disponibles:</span>
                                <button 
                                    v-if="visita.resultado_visita !== 'rechazo'"
                                    @click="router.push({ name: 'Visitas' })" 
                                    class="btn-primary w-full flex items-center justify-center gap-2 py-3"
                                >
                                    <i class="fas fa-plus-circle"></i> Nuevo Seguimiento
                                </button>
                                <p v-else class="text-center text-[10px] text-red-400 font-bold uppercase p-2 border-2 border-dashed border-red-100 rounded-xl">
                                    El plantel fue marcado como No Interesado
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN INFERIOR: TABLA DE MATERIALES (ABAJO DE TODO Y FULL WIDTH) -->
                <div class="info-card w-full border-top-red overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 border-b border-slate-100 pb-4">
                        <h2 class="card-title border-0 mb-0">
                            <i class="fas fa-book-open text-red-700"></i> Desglose de Material de Interés y Promoción
                        </h2>
                        <div class="flex items-center gap-2">
                            <span class="bg-slate-100 text-slate-500 text-[10px] font-black px-3 py-1 rounded-full uppercase">
                                {{ librosParsed.length }} Ítems registrados
                            </span>
                        </div>
                    </div>
                    
                    <!-- Contenedor con scroll horizontal para móviles -->
                    <div v-if="librosParsed.length" class="table-scroll-container">
                        <div class="min-w-[600px] border rounded-2xl shadow-sm bg-white overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="th-custom">Material / Libro</th>
                                        <th class="th-custom">Formato Solicitado</th>
                                        <th class="th-custom text-center">Muestra de Promoción</th>
                                        <th class="th-custom text-center">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="(libro, index) in librosParsed" :key="index" class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="item-index">
                                                    {{ index + 1 }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="font-bold text-slate-800 text-sm leading-tight break-words">{{ libro.titulo }}</p>
                                                    <p class="text-[9px] text-gray-400 font-bold uppercase mt-1">Serie: <br>{{ libro.serie_nombre }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <span class="format-badge">
                                                {{ libro.formato || libro.format || 'Físico' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <span v-if="libro.entrega_promo || libro.promo_delivered" class="delivered-badge">
                                                <i class="fas fa-check-circle mr-1"></i> Entregado
                                            </span>
                                            <span v-else class="not-delivered-text">Sin entrega</span>
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <span v-if="libro.entrega_promo || libro.promo_delivered" class="text-slate-700 font-black text-sm">
                                                {{ libro.cantidad_promo || libro.promo_qty || 1 }}
                                            </span>
                                            <span v-else class="text-gray-200">--</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div v-else class="empty-state-container mt-2">
                        <i class="fas fa-book-medical text-4xl text-slate-200 mb-3"></i>
                        <p class="text-slate-400 text-sm font-medium italic">No se registró interés en libros específicos para esta sesión.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../axios';

const route = useRoute();
const router = useRouter();
const visita = ref(null);
const loading = ref(true);
const error = ref(null);

/**
 * Recupera el detalle de la visita
 */
const fetchVisitaDetail = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/visitas/${route.params.id}`);
        visita.value = response.data;
    } catch (err) {
        console.error("Error al recuperar detalle:", err);
        error.value = err.response?.data?.message || "No se pudo conectar con el servidor.";
    } finally {
        loading.value = false;
    }
};

/**
 * Procesa el campo libros_interes (JSON a Array) con compatibilidad para formatos antiguos
 */
const librosParsed = computed(() => {
    if (!visita.value || !visita.value.libros_interes) return [];
    
    if (Array.isArray(visita.value.libros_interes)) return visita.value.libros_interes;

    try {
        const parsed = JSON.parse(visita.value.libros_interes);
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [{ 
            titulo: visita.value.libros_interes, 
            serie_nombre: 'Dato Histórico', 
            formato: 'No especificado',
            entrega_promo: visita.value.material_entregado,
            cantidad_promo: visita.value.material_cantidad
        }];
    }
});

/**
 * Formatea los niveles educativos para mostrarlos como badges
 */
const formatLevels = (levels) => {
    if (!levels) return ['Sin nivel definido'];
    if (Array.isArray(levels)) return levels;
    return levels.split(',').map(l => l.trim()).filter(l => l.length > 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '---';
    const parts = dateString.split('T')[0].split('-');
    const date = new Date(parts[0], parts[1] - 1, parts[2]);
    return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const getOutcomeClass = (outcome) => {
    if (outcome === 'compra') return 'bg-green-100 text-green-700 border-green-200';
    if (outcome === 'rechazo') return 'bg-red-100 text-red-700 border-red-200';
    return 'bg-orange-100 text-orange-700 border-orange-200';
};

onMounted(fetchVisitaDetail);
</script>

<style scoped>
.info-card {
    background: #fff;
    padding: 24px;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    min-width: 0;
    height: 100%;
}

.border-brand { border-top: 5px solid #a93339; }
.border-top-red { border-top: 5px solid #f8fafc; }

.card-title {
    font-size: 1.1rem;
    font-weight: 800;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f8fafc;
    display: flex;
    align-items: center;
    gap: 10px;
}

.visit-data-box {
    background: #f8fafc;
    padding: 15px;
    border-radius: 12px;
    border: 1px solid #edf2f7;
}

.label-xs {
    font-size: 0.65rem;
    color: #94a3b8;
    text-transform: uppercase;
    font-weight: 900;
    display: block;
    margin-bottom: 4px;
    letter-spacing: 0.05em;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 900;
    border: 1px solid transparent;
    white-space: nowrap;
}

.detail-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 15px;
}

@media (max-width: 640px) {
    .detail-header-flex {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    .mt-mobile-4 { margin-top: 1rem; }
    .info-card { padding: 16px; }
}

.header-title {
    font-size: 1.6rem;
    font-weight: 900;
    line-height: 1.2;
    color: #1e293b;
    word-break: break-word;
}

@media (min-width: 768px) {
    .header-title { font-size: 1.8rem; }
}

.header-subtitle {
    color: #64748b;
    margin-top: 4px;
    font-size: 0.9rem;
}

.comment-text {
    text-align: justify;
    color: #475569;
    font-size: 0.75rem;
    line-height: 1.5;
    font-style: italic;
    background: #f8fafc;
    padding: 12px;
    border-radius: 12px;
    border: 1px solid #f1f5f9;
    word-break: break-word;
}

.level-badge {
    background: #f1f5f9;
    color: #475569;
    text-transform: uppercase;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.table-scroll-container {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 1rem; /* Espacio para el scrollbar */
}

.th-custom {
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.65rem;
    font-weight: 900;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    white-space: nowrap;
}

.item-index {
    width: 2rem;
    height: 2rem;
    background: #fef2f2;
    color: #a93339;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.format-badge {
    background: #fef2f2;
    color: #a93339;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.65rem;
    font-weight: 900;
    text-transform: uppercase;
    border: 1px solid #fee2e2;
    white-space: nowrap;
}

.delivered-badge {
    color: #166534;
    font-weight: 900;
    font-size: 0.65rem;
    text-transform: uppercase;
    background: #f0fdf4;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    border: 1px solid #dcfce7;
    white-space: nowrap;
}

.not-delivered-text {
    color: #94a3b8;
    font-style: italic;
    font-size: 0.65rem;
    white-space: nowrap;
}

.empty-state-container {
    padding: 3rem 0;
    text-align: center;
    border: 2px dashed #f1f5f9;
    border-radius: 1.5rem;
    background: rgba(248, 250, 252, 0.5);
}

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.break-words {
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
}
</style>