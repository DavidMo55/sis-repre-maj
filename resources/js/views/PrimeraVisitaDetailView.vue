<template>
    <div class="content-wrapper p-2 md:p-6">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado Dinámico -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div class="header-info min-w-0">
                    <h1 v-if="visita" class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight break-words">
                        Bitácora: {{ visita.nombre_plantel || visita.cliente?.name || 'Sin nombre' }}
                    </h1>
                    <h1 v-else-if="loading" class="text-2xl font-black text-slate-300 animate-pulse">Cargando bitácora...</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium mt-1">Resumen técnico de acuerdos, prospectación y materiales de interés.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm shrink-0 w-full sm:w-auto">
                    <i class="fas fa-arrow-left"></i> Volver al Historial
                </button>
            </div>

            <!-- Loader -->
            <div v-if="loading" class="loading-state py-20 text-center">
                <i class="fas fa-circle-notch fa-spin text-4xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Sincronizando información...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="error-message-container p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle fa-2xl"></i>
                </div>
                <h2 class="text-xl font-black text-red-800 uppercase tracking-tighter">Error de Sistema</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium">{{ error }}</p>
                <button @click="fetchVisitaDetail" class="btn-primary mt-6 px-10 py-3 rounded-2xl shadow-lg">Reintentar</button>
            </div>

            <!-- Contenido Principal -->
            <div v-else-if="visita" class="space-y-8 animate-fade-in">
                
                <!-- 1. GRID DE INFORMACIÓN GENERAL -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- Tarjeta Plantel -->
                    <div class="info-card shadow-premium border-t-4 border-t-red-700">
                        <div class="section-title">
                            <i class="fas fa-school text-red-700"></i> 1. Identidad del Plantel
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="label-mini">Nombre Oficial / Razón</label>
                                <p class="text-sm font-black text-slate-800 uppercase leading-tight">{{ visita.nombre_plantel || visita.cliente?.name }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label-mini">Niveles Educativos</label>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <span v-for="n in formatLevels(visita.nivel_educativo_plantel || visita.cliente?.nivel_educativo)" :key="n" class="status-badge bg-slate-100 text-slate-600 border border-slate-200">
                                            {{ n }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="label-mini">RFC Registrado</label>
                                    <p class="text-[10px] font-mono font-black text-slate-500 uppercase">{{ visita.rfc_plantel || visita.cliente?.rfc || 'No registrado' }}</p>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-50">
                                <label class="label-mini">Ubicación Registrada</label>
                                <p class="text-[11px] text-slate-500 italic leading-relaxed">
                                    <i class="fas fa-map-marker-alt text-red-500 mr-1"></i> 
                                    {{ visita.direccion_plantel || visita.cliente?.direccion || 'Sin dirección' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta Entrevista -->
                    <div class="info-card shadow-premium border-t-4 border-t-slate-800">
                        <div class="section-title">
                            <i class="fas fa-user-tie text-slate-800"></i> 2. Detalles de Entrevista
                        </div>
                        <div class="space-y-4">
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <label class="label-mini">Persona Entrevistada</label>
                                <p class="text-sm font-black text-red-800 uppercase leading-none mb-1">{{ visita.persona_entrevistada || 'N/A' }}</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">{{ visita.cargo || 'Responsable' }}</p>
                            </div>
                            <div class="flex justify-between items-end border-t border-slate-50 pt-4">
                                <div>
                                    <label class="label-mini">Fecha de Visita</label>
                                    <p class="text-sm font-black text-slate-700">{{ formatDate(visita.fecha) }}</p>
                                </div>
                                <div class="text-right">
                                    <label class="label-mini">Resultado Actual</label>
                                    <span :class="getOutcomeClass(visita.resultado_visita)" class="status-badge">
                                        {{ (visita.resultado_visita || 'seguimiento').toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <div class="pt-2">
                                <label class="label-mini">Director del Plantel</label>
                                <p class="text-xs font-bold text-slate-600">{{ visita.director_plantel || visita.cliente?.contacto || 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta Seguimiento -->
                    <div class="info-card shadow-premium border-t-4 border-t-red-800 bg-slate-50/30">
                        <div class="section-title">
                            <i class="fas fa-calendar-check text-red-800"></i> 3. Agenda y Seguimiento
                        </div>
                        <div class="space-y-5">
                            <div class="p-5 rounded-[2rem] border-2 border-white bg-white shadow-sm">
                                <label class="label-mini text-slate-400">Próximo Compromiso</label>
                                <div v-if="visita.proxima_visita_estimada" class="mt-2">
                                    <p class="text-2xl font-black text-green-700 tracking-tighter leading-none">{{ formatDate(visita.proxima_visita_estimada) }}</p>
                                    <p class="text-[10px] font-black uppercase text-green-600 tracking-widest mt-2">
                                        <i class="fas fa-bullseye mr-1"></i> {{ visita.proxima_accion === 'presentacion' ? 'Presentación Académica' : 'Visita Subsecuente' }}
                                    </p>
                                </div>
                                <p v-else class="text-slate-300 italic text-[10px] font-black uppercase tracking-widest py-2">Sin fecha de seguimiento agendada</p>
                            </div>
                            
                            <button v-if="visita.resultado_visita !== 'rechazo'" 
                                @click="router.push({ name: 'SeguimientoID', params: { id: visita.id } })" 
                                class="btn-primary w-full py-4 rounded-2xl font-black uppercase tracking-widest text-[11px] flex justify-center items-center gap-2 shadow-lg shadow-red-100 active:scale-95 transition-all">
                                <i class="fas fa-plus-circle"></i> Nuevo Seguimiento
                            </button>

                            <div v-if="visita.latitud" class="flex items-center justify-between p-3 bg-blue-50 border border-blue-100 rounded-xl">
                                <span class="text-[9px] font-black text-blue-700 uppercase"><i class="fas fa-map-marker-alt"></i> GPS Capturado</span>
                                <a :href="`https://www.google.com/maps?q=${visita.latitud},${visita.longitud}`" target="_blank" class="text-blue-600 hover:underline font-black text-[10px] uppercase">Ver en Mapa</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. DESGLOSE DE MATERIALES (DISEÑO MEJORADO PARA NUEVO FORMATO) -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between px-2">
                        <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">
                            <i class="fas fa-book-open text-red-700 mr-2"></i> Expediente de Materiales
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- APARTADO A: INTERÉS -->
                        <div class="info-card !p-0 overflow-hidden border border-slate-200 shadow-premium">
                            <div class="p-5 bg-slate-900 text-white flex justify-between items-center">
                                <h3 class="text-[10px] font-black uppercase tracking-widest">Apartado A: Libros de Interés</h3>
                                <span class="bg-white/10 px-2 py-1 rounded text-[9px] font-bold">{{ materialesInteres.length }} Ítems</span>
                            </div>
                            <div class="table-responsive">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 border-b border-slate-100">
                                        <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                            <th class="px-6 py-4 text-left">Título / Material</th>
                                            <th class="px-6 py-4 text-center">Tipo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="(libro, idx) in materialesInteres" :key="idx" class="hover:bg-slate-50/50">
                                            <td class="px-6 py-4">
                                                <p class="font-black text-slate-800 text-[11px] uppercase">{{ libro.titulo }}</p>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="text-[10px] font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100 uppercase">{{ libro.tipo || 'Físico' }}</span>
                                            </td>
                                        </tr>
                                        <tr v-if="!materialesInteres.length">
                                            <td colspan="2" class="px-6 py-10 text-center text-slate-300 italic text-xs font-bold uppercase tracking-widest">Sin registros de interés</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- APARTADO B: PROMOCIÓN -->
                        <div class="info-card !p-0 overflow-hidden border border-red-200 shadow-premium">
                            <div class="p-5 bg-red-800 text-white flex justify-between items-center">
                                <h3 class="text-[10px] font-black uppercase tracking-widest">Apartado B: Muestras Entregadas</h3>
                                <span class="bg-white/10 px-2 py-1 rounded text-[9px] font-bold">{{ materialesEntregados.length }} Ítems</span>
                            </div>
                            <div class="table-responsive">
                                <table class="w-full text-sm">
                                    <thead class="bg-red-50 border-b border-red-100">
                                        <tr class="text-[9px] font-black text-red-300 uppercase tracking-widest">
                                            <th class="px-6 py-4 text-left">Título / Muestra</th>
                                            <th class="px-6 py-4 text-center">Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-red-50">
                                        <tr v-for="(libro, idx) in materialesEntregados" :key="idx" class="hover:bg-red-50/30">
                                            <td class="px-6 py-4">
                                                <p class="font-black text-red-900 text-[11px] uppercase">{{ libro.titulo }}</p>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="text-base font-black text-red-700">{{ libro.cantidad || '1' }}</span>
                                            </td>
                                        </tr>
                                        <tr v-if="!materialesEntregados.length">
                                            <td colspan="2" class="px-6 py-10 text-center text-slate-300 italic text-xs font-bold uppercase tracking-widest">No se entregaron muestras</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. OBSERVACIONES -->
                <div v-if="visita.comentarios" class="info-card border-none bg-amber-50/50 p-8 rounded-[2.5rem] border border-amber-100 shadow-sm">
                    <h3 class="text-[10px] font-black text-amber-600 uppercase mb-3 tracking-widest flex items-center gap-2">
                        <i class="fas fa-comment-dots"></i> Acuerdos y Notas del Representante
                    </h3>
                    <p class="text-sm text-slate-700 italic leading-relaxed whitespace-pre-wrap">{{ visita.comentarios }}</p>
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

const fetchVisitaDetail = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/visitas/${route.params.id}`);
        visita.value = response.data;
    } catch (err) {
        console.error("Error al cargar detalle:", err);
        error.value = err.response?.data?.message || "No se pudo recuperar la información de la bitácora.";
    } finally {
        loading.value = false;
    }
};

/**
 * Procesa el campo JSON de libros para separar Interés y Entregados
 * Soporta el formato nuevo { interes: [], entregado: [] } y el legacy [strings]
 */
const librosRaw = computed(() => {
    if (!visita.value || !visita.value.libros_interes) return null;
    
    // Si ya es un objeto (Laravel Cast)
    if (typeof visita.value.libros_interes === 'object' && !Array.isArray(visita.value.libros_interes)) {
        return visita.value.libros_interes;
    }

    // Si es un JSON string
    try {
        const parsed = JSON.parse(visita.value.libros_interes);
        return parsed;
    } catch (e) {
        return null;
    }
});

// Getter para Apartado A (Interés)
const materialesInteres = computed(() => {
    const data = librosRaw.value;
    if (data && data.interes) return data.interes;
    
    // Fallback: Si es el formato viejo (lista plana)
    if (Array.isArray(visita.value?.libros_interes)) {
        return visita.value.libros_interes.map(titulo => ({ titulo: typeof titulo === 'string' ? titulo : titulo.titulo }));
    }
    return [];
});

// Getter para Apartado B (Entregados)
const materialesEntregados = computed(() => {
    const data = librosRaw.value;
    if (data && data.entregado) return data.entregado;
    
    // Fallback: Si no hay objeto nuevo, verificamos si hay material_cantidad (legacy)
    if (visita.value?.material_entregado && visita.value?.libros_interes) {
        // En registros viejos el material entregado solía ser el primero de la lista de interés
        return [{ titulo: 'Material de Promoción Registrado', cantidad: visita.value.material_cantidad || 1 }];
    }
    return [];
});

/**
 * Formatea los niveles educativos para mostrarlos como badges
 */
const formatLevels = (levels) => {
    if (!levels) return ['General'];
    if (Array.isArray(levels)) return levels;
    return levels.split(',').map(l => l.trim()).filter(l => l.length > 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '---';
    const parts = dateString.split('T')[0].split('-');
    const date = new Date(parts[0], parts[1] - 1, parts[2]);
    return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getOutcomeClass = (outcome) => {
    const base = 'status-badge uppercase tracking-tighter ';
    if (outcome === 'compra') return base + 'bg-green-100 text-green-700 border border-green-200';
    if (outcome === 'rechazo') return base + 'bg-red-100 text-red-700 border border-red-200';
    return base + 'bg-orange-100 text-orange-700 border border-orange-200';
};

onMounted(fetchVisitaDetail);
</script>

<style scoped>
.info-card { background: white; padding: 30px; border-radius: 32px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #1e293b; margin-bottom: 20px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-[0.1em]; }
.status-badge { padding: 4px 14px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; }

.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.05); }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Forzar que las palabras largas no rompan el layout en móviles */
.break-words {
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-word;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
}
</style>