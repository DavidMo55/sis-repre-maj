<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado Dinámico -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    <div class="flex items-center gap-3 mb-1">
                        <span :class="visita?.cliente?.tipo === 'CLIENTE' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'" 
                              class="text-[10px] font-black uppercase px-3 py-1 rounded-full border border-current opacity-80">
                            {{ visita?.cliente?.tipo || 'PROSPECTO' }}
                        </span>
                        <span class="text-slate-300">/</span>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">ID: #{{ visita?.id }}</span>
                    </div>
                    <h1 v-if="visita" class="text-2xl md:text-4xl font-black text-slate-800 tracking-tight leading-tight break-words">
                        {{ visita.nombre_plantel || visita.cliente?.name || 'Sin nombre' }}
                    </h1>
                    <h1 v-else-if="loading" class="text-2xl font-black text-slate-300 animate-pulse">Cargando bitácora...</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium mt-1 uppercase tracking-tighter">Expediente técnico de prospectación y acuerdos académicos.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary flex items-center justify-center gap-2 px-8 py-3 rounded-2xl text-sm font-black shadow-sm shrink-0 w-full sm:w-auto bg-white border-2 border-slate-200 hover:bg-slate-50 transition-all">
                    <i class="fas fa-arrow-left"></i> VOLVER AL LISTADO
                </button>
            </div>

            <!-- Loader -->
            <div v-if="loading" class="loading-state py-20 text-center">
                <i class="fas fa-circle-notch fa-spin text-5xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Sincronizando información...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="error-message-container p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in">
                <i class="fas fa-exclamation-triangle fa-3xl text-red-600 mb-6"></i>
                <h2 class="text-xl font-black text-red-800 uppercase tracking-tighter">Error de Sistema</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium">{{ error }}</p>
                <button @click="fetchVisitaDetail" class="btn-primary mt-6 px-10 py-3 rounded-2xl shadow-lg">Reintentar</button>
            </div>

            <!-- Contenido Principal -->
            <div v-else-if="visita" class="space-y-8 animate-fade-in pb-10">
                
                <!-- 1. IDENTIDAD DEL PLANTEL -->
                <div class="info-card shadow-premium border-t-8 border-t-red-700">
                    <div class="section-title">
                        <i class="fas fa-school text-red-700"></i> 1. Identidad y Ubicación del Plantel
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-6">
                        <!-- Columna Datos Base -->
                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Nombre Oficial / Razón</label>
                                <p class="value-text text-lg">{{ visita.nombre_plantel || visita.cliente?.name }}</p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">RFC Fiscal</label>
                                    <p class="value-text font-mono">{{ visita.rfc_plantel || visita.cliente?.rfc || 'No registrado' }}</p>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Niveles Educativos</label>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <span v-for="n in formatLevels(visita.nivel_educativo_plantel || visita.cliente?.nivel_educativo)" :key="n" class="status-badge bg-slate-100 text-slate-700 font-black border border-slate-200">
                                            {{ n }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">Estado</label>
                                    <p class="value-text uppercase">{{ visita.estado?.estado || 'No especificado' }}</p>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Estatus de Cuenta</label>
                                    <p class="font-black text-sm uppercase tracking-wider" :class="visita?.cliente?.tipo === 'CLIENTE' ? 'text-green-600' : 'text-orange-600'">
                                        {{ visita?.cliente?.tipo || 'PROSPECTO' }}
                                    </p>
                                </div>
                            </div>

                            <div class="data-row">
                                <label class="label-large">Ubicación GPS (Coordenadas)</label>
                                <div v-if="visita.latitud" class="flex items-center gap-3 bg-blue-50 p-3 rounded-2xl border border-blue-100 mt-2">
                                    <div class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-md">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[10px] font-black text-blue-800 uppercase leading-none">Punto Exacto Capturado</p>
                                        <p class="text-xs font-mono font-bold text-blue-600 mt-1">{{ visita.latitud }}, {{ visita.longitud }}</p>
                                    </div>
                                    <a :href="`https://www.google.com/maps?q=${visita.latitud},${visita.longitud}`" target="_blank" class="text-[10px] font-black uppercase text-blue-700 hover:underline">Ver Mapa</a>
                                </div>
                                <p v-else class="value-text text-slate-300 italic">GPS no capturado en esta visita</p>
                            </div>
                        </div>

                        <!-- Columna Contacto -->
                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Dirección Completa</label>
                                <p class="value-text italic leading-relaxed text-sm">{{ visita.direccion_plantel || visita.cliente?.direccion || 'Sin dirección registrada' }}</p>
                            </div>
                            
                            <div class="data-row">
                                <label class="label-large">Nombre del Director / Coordinador</label>
                                <p class="value-text text-slate-700">{{ visita.director_plantel || visita.cliente?.contacto || 'No especificado' }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">Teléfono / Celular</label>
                                    <p class="value-text"><i class="fas fa-phone-alt mr-2 text-slate-300"></i>{{ visita.telefono_plantel || visita.cliente?.telefono || 'N/A' }}</p>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Correo Electrónico</label>
                                    <p class="value-text lowercase text-sm"><i class="fas fa-envelope mr-2 text-slate-300"></i>{{ visita.email_plantel || visita.cliente?.email || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. DETALLES DE LA VISITA -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 info-card shadow-premium border-t-8 border-t-slate-800">
                        <div class="section-title">
                            <i class="fas fa-handshake text-slate-800"></i> 2. Detalles de la Entrevista
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div class="data-row bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                    <label class="label-large !text-red-700">Atendido por:</label>
                                    <p class="value-text !text-lg !font-black uppercase">{{ visita.persona_entrevistada || 'N/A' }}</p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">{{ visita.cargo || 'Responsable de área' }}</p>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Fecha de la Sesión</label>
                                    <p class="value-text text-slate-700">{{ formatDate(visita.fecha) }}</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="data-row">
                                    <label class="label-large">Resultado de la Entrevista</label>
                                    <br>
                                    <span :class="getOutcomeClass(visita.resultado_visita)" class="status-badge !text-xs !py-2 !px-6 shadow-sm">
                                        {{ (visita.resultado_visita || 'seguimiento').toUpperCase() }}
                                    </span>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Objetivo de la sesión</label>
                                    <p class="value-text uppercase text-xs font-black text-slate-500">{{ visita.es_primera_visita ? 'Primera Visita / Prospectación' : 'Visita de Seguimiento' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. AGENDA Y SEGUIMIENTO -->
                    <div class="info-card shadow-premium border-t-8 border-t-red-800 bg-slate-900 text-white">
                        <div class="section-title !text-white !border-white/10">
                            <i class="fas fa-calendar-check"></i> 3. Agenda y Seguimiento
                        </div>
                        
                        <div class="space-y-6 mt-4">
                            <div class="p-6 bg-white/5 rounded-3xl border border-white/10">
                                <label class="label-large !text-red-400">Próximo Compromiso</label>
                                <div v-if="visita.proxima_visita_estimada" class="mt-3">
                                    <p class="text-3xl font-black text-white tracking-tighter">{{ formatDate(visita.proxima_visita_estimada) }}</p>
                                    <div class="flex items-center gap-2 mt-4 bg-red-600/20 p-2 rounded-xl border border-red-600/30">
                                        <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center text-xs">
                                            <i class="fas fa-bullseye"></i>
                                        </div>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-red-200">
                                            {{ visita.proxima_accion === 'presentacion' ? 'Presentación Académica' : 'Seguimiento Estándar' }}
                                        </span>
                                    </div>
                                </div>
                                <p v-else class="text-white/30 italic text-[11px] font-black uppercase tracking-widest mt-4">Sin fecha agendada</p>
                            </div>

                            <!-- REGLA: Solo se permite seguimiento si el estatus actual es SEGUIMIENTO y no es CLIENTE -->
                            <button v-if="visita.resultado_visita === 'seguimiento' && visita.cliente?.tipo !== 'CLIENTE'" 
                                @click="router.push({ name: 'SeguimientoID', params: { id: visita.id } })" 
                                class="w-full btn-primary py-4 rounded-[2rem] bg-red-700 hover:bg-red-600 text-white font-black uppercase tracking-widest text-[11px] shadow-2xl shadow-red-900/50 transition-all active:scale-95">
                                <i class="fas fa-plus-circle mr-2 "></i> Iniciar Segunda Visita
                            </button>
                            <div v-else class="bg-white/10 p-4 rounded-2xl border border-white/5">
                                <p class="text-[9px] font-black text-slate-400 uppercase text-center leading-tight">
                                    Etapa de prospectación cerrada. <br> No se requieren seguimientos adicionales.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EXPEDIENTE DE MATERIALES -->
                <div class="space-y-6">
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-1.5 h-8 bg-red-700 rounded-full"></div>
                        <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Expediente de Materiales</h2>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- APARTADO A: INTERÉS -->
                        <div class="info-card bg1 !p-0 overflow-hidden border border-slate-200 shadow-premium bg-white">
                            <div class="p-6 bg-slate-800 text-white flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-star text-yellow-400"></i>
                                    <h3 class="text-[11px] font-black uppercase tracking-widest">Libros de Interés</h3>
                                </div>
                                <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest">{{ materialesInteres.length }} Títulos</span>
                            </div>
                            <div class="table-responsive table-shadow-lg border-t overflow-hidden">
                                <table class="min-width-full divide-y divide-gray-200">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="table-header">Material</th>
                                            <th class="table-header text-right">Formato Solicitado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        <tr v-for="(libro, idx) in materialesInteres" :key="idx" class="hover:bg-gray-50 transition-colors">
                                            <td class="table-cell">
                                                <div class="text-sm font-bold text-gray-800 uppercase leading-tight text-truncate max-w-titulo" :title="libro.titulo">
                                                    {{ libro.titulo }}
                                                </div>
                                                <div class="text-[10px] text-gray-400 font-bold mt-1 uppercase tracking-tighter">
                                                    SERIE: {{ libro.serie_nombre || 'N/A' }}
                                                </div>
                                            </td>
                                            <td class="table-cell text-right">
                                                <span class="type-badge badge-blue-outline">
                                                    {{ libro.tipo || 'Físico' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="!materialesInteres.length">
                                            <td colspan="2" class="px-8 py-16 text-center">
                                                <i class="fas fa-box-open text-gray-200 text-5xl mb-4 block"></i>
                                                <p class="text-gray-400 font-bold uppercase text-[11px] tracking-widest italic">Sin registros de prospección</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- APARTADO B: PROMOCIÓN -->
                        <div class="info-card bg2 !p-0 overflow-hidden border border-red-100 shadow-premium bg-white">
                            <div class="p-6 bg-red-900 text-white flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-box-open text-red-300"></i>
                                    <h3 class="text-[11px] font-black uppercase tracking-widest"> Muestras Entregadas</h3>
                                </div>
                                <span class="text-[9px] font-black text-red-200 uppercase tracking-widest">{{ materialesEntregados.length }} Entregas</span>
                            </div>
                            <div class="table-responsive table-shadow-lg border-t overflow-hidden">
                            <table class="min-width-full divide-y divide-gray-200">
                                <thead class="bg-red-50">
                                    <tr>
                                        <th class="table-header text-red-900/60">Título / Muestra Física</th>
                                        <th class="table-header text-center">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-red-50">
                                    <tr v-for="(libro, idx) in materialesEntregados" :key="idx" class="hover:bg-red-50/30 transition-colors">
                                        <td class="table-cell">
                                            <div class="text-sm font-black text-red-900 uppercase leading-tight">
                                                {{ libro.titulo }}
                                            </div>
                                        </td>
                                        <td class="table-cell text-center">
                                            <div class="flex flex-col items-center">
                                                <span class="text-2xl font-black text-red-700 tracking-tighter">{{ libro.cantidad || '1' }}</span>
                                                <span class="text-[9px] text-red-300 font-bold uppercase">Unidades</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!materialesEntregados.length">
                                        <td colspan="2" class="px-8 py-16 text-center">
                                            <i class="fas fa-folder-open text-red-100 text-5xl mb-4 block"></i>
                                            <p class="text-red-200 font-bold uppercase text-[11px] tracking-widest italic">No se entregaron muestras físicas</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- OBSERVACIONES FINALES -->
                <div v-if="visita.comentarios" class="info-card border-none bg-amber-50 p-10 rounded-[3rem] border border-amber-200 shadow-sm">
                    <h3 class="text-[11px] font-black text-amber-700 uppercase mb-4 tracking-[0.3em] flex items-center gap-2">
                        <i class="fas fa-comment-dots"></i> Acuerdos y Notas del Representante
                    </h3>
                    <p class="text-base text-slate-700 italic leading-relaxed whitespace-pre-wrap font-medium">"{{ visita.comentarios }}"</p>
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
        error.value = err.response?.data?.message || "No se pudo recuperar la información de la bitácora.";
    } finally {
        loading.value = false;
    }
};

/**
 * Procesa el campo JSON de libros
 */
const librosRaw = computed(() => {
    if (!visita.value || !visita.value.libros_interes) return null;
    if (typeof visita.value.libros_interes === 'object' && !Array.isArray(visita.value.libros_interes)) {
        return visita.value.libros_interes;
    }
    try {
        const parsed = JSON.parse(visita.value.libros_interes);
        return parsed;
    } catch (e) {
        return null;
    }
});

const materialesInteres = computed(() => {
    const data = librosRaw.value;
    if (data && data.interes) return data.interes;
    if (Array.isArray(visita.value?.libros_interes)) {
        return visita.value.libros_interes.map(titulo => ({ 
            titulo: typeof titulo === 'string' ? titulo : titulo.titulo 
        }));
    }
    return [];
});

const materialesEntregados = computed(() => {
    const data = librosRaw.value;
    if (data && data.entregado) return data.entregado;
    if (visita.value?.material_entregado && visita.value?.libros_interes) {
        return [{ titulo: 'Material de Promoción', cantidad: visita.value.material_cantidad || 1 }];
    }
    return [];
});

const formatLevels = (levels) => {
    if (!levels) return ['General'];
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
    const base = 'status-badge uppercase font-black tracking-widest ';
    if (outcome === 'compra') return base + 'bg-green-100 text-green-700 border-green-200';
    if (outcome === 'rechazo') return base + 'bg-red-100 text-red-700 border-red-200';
    return base + 'bg-orange-100 text-orange-700 border-orange-200';
};

onMounted(fetchVisitaDetail);
</script>

<style scoped>
.info-card { background: white; padding: 40px; border-radius: 40px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #3b1e1e; margin-bottom: 30px; border-bottom: 2px solid #f8fafc; padding-bottom: 15px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 2px; }

/* ETIQUETAS Y TEXTO */
.label-large { 
    display: block;
    font-size: 0.72rem; 
    font-weight: 900; 
    text-transform: uppercase; 
    color: #000000; 
    margin-bottom: 6px; 
    letter-spacing: 0.12em; 
}

.value-text { 
    font-weight: 800; 
    color: #be5e5e; 
    line-height: 1.4;
}

.status-badge { padding: 6px 16px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; }

.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0, 0, 0, 0.08); }

.animate-fade-in { animation: fadeIn 0.6s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.table-responsive { width: 100%; overflow-x: auto; }

/* Estilo para las cabeceras de tabla */
th {
    white-space: nowrap;
}

@media (max-width: 640px) {
    .info-card { padding: 25px; border-radius: 24px; }
}

.btn-primary { background: linear-gradient(135deg, #cb7e81 0%, #e96a90 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: center; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.table-responsive {
    width: 100%;
    overflow-x: auto;
    background: white;
}

table {
    table-layout: fixed;
    width: 100%;
    border-collapse: collapse;
}

/* Cabeceras */
.table-header {
    padding: 16px;
    font-size: 0.7rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

/* Celdas */
.table-cell {
    padding: 16px 20px;
    vertical-align: middle;
}

/* Badges de Formato */
.type-badge {
    padding: 6px 14px;
    border-radius: 12px;
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-block;
}

.badge-blue-outline {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #dbeafe;
}

/* Truncado de Texto */
.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.max-w-titulo {
    max-width: 300px;
}

/* Efectos y Sombras */
.table-shadow-lg {
    box-shadow: 0 4px 20px -5px rgba(0, 0, 0, 0.05);
}

.transition-colors {
    transition: background-color 0.2s ease;
}

/* Alineaciones */
.text-right { text-align: right; }
.text-center { text-align: center; }
.text-left { text-align: left; }

.bg1{
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}
.bg2{
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}
</style>