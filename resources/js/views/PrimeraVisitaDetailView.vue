<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado Dinámico -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    <h1 v-if="visita" class="text-2xl md:text-4xl font-black text-black tracking-tight leading-tight break-words">
                        {{ visita.nombre_plantel || visita.cliente?.name || 'Sin nombre' }}
                    </h1>
                    <h1 v-else-if="loading" class="text-2xl font-black text-slate-300 animate-pulse uppercase">Sincronizando información...</h1>
                    <p class="text-xs md:text-sm text-red-600 font-medium mt-1 uppercase tracking-tighter italic">Expediente técnico de prospectación y acuerdos académicos.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto uppercase bg-white border-2 border-slate-200 text-slate-600 py-2.5 px-6 rounded-xl font-black text-[10px] hover:bg-slate-50 transition-all">
                    <i class="fas fa-arrow-left mr-2"></i> VOLVER AL LISTADO
                </button>
            </div>

            <!-- Loader de Sistema -->
            <div v-if="loading" class="loading-state py-20 text-center">
                <i class="fas fa-circle-notch fa-spin text-5xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Consultando base de datos maestra...</p>
            </div>

            <!-- Contenido Principal -->
            <div v-else-if="visita" class="space-y-8 animate-fade-in pb-20">
                
                <!-- 1. IDENTIDAD DEL PLANTEL -->
                <div class="info-card shadow-premium border-t-8 border-t-red-700 bg-white p-10 rounded-[2.5rem] border border-slate-100">
                    <div class="section-title">
                        <i class="fas fa-school text-red-700"></i> 1. Identidad y Ubicación del Plantel
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-6">
                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Nombre del Plantel</label>
                                <p class="value-text text-xl leading-none uppercase font-black">{{ visita.nombre_plantel || visita.cliente?.name }}</p>
                            </div>

                             <div class="data-row">
                                    <label class="label-large">Estatus Actual</label>
                                    <p class="font-black text-sm uppercase tracking-wider" :class="visita?.cliente?.tipo === 'CLIENTE' ? 'text-green-600' : 'text-red-700'">
                                        {{ visita?.cliente?.tipo || 'PROSPECTO' }}
                                    </p>
                                </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">RFC del Plantel</label>
                                    <p class="value-text font-mono uppercase tracking-widest">{{ visita.rfc_plantel || visita.cliente?.rfc || 'No registrado' }}</p>
                                </div>

                                <div class="data-row">
                                <label class="label-large">Ubicación Geográfica (GPS)</label>
                                <div v-if="visita.latitud" class="flex items-center gap-3 bg-red-50/30 p-4 rounded-2xl border border-red-100 mt-2">
                                    <div class="w-10 h-10 bg-red-700 text-white rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-map-marker-alt text-xs"></i>
                                    </div>
                                    <a :href="`https://www.google.com/maps?q=${visita.latitud},${visita.longitud}`" target="_blank" class="text-[10px] font-black uppercase text-red-700 hover:underline">Ver Mapa</a>
                                </div>
                                <p v-else class="value-text text-slate-300 italic text-sm">Sin coordenadas</p>
                            </div>

                                <div class="data-row">
                                    <label class="label-large">Niveles Educativos</label>
                                    <div class="flex flex-wrap gap-1.5 mt-1">
                                        <span v-for="n in formatLevels(visita.nivel_educativo_plantel || visita.cliente?.nivel_educativo)" :key="n" class="badge-red-outline">
                                            {{ n }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>  

                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Celular / Teléfono</label>
                                <p class="value-text tracking-tighter"><i class="fas fa-phone-alt mr-2 opacity-30"></i>{{ visita.telefono_plantel || visita.cliente?.telefono || 'N/A' }}</p>
                            </div>
                            <div class="data-row">
                                <label class="label-large">Correo Electrónico</label>
                                <p class="value-text text-sm" style="text-transform: none !important;">
                                    <i class="fas fa-envelope mr-2 opacity-30"></i>
                                    {{ (visita.email_plantel || visita.cliente?.email || 'N/A').toLowerCase() }}
                                </p>
                            </div>
                            <div class="data-row">
                                <label class="label-large">Dirección</label>
                                <p class="value-text uppercase text-xs leading-tight">{{ visita.direccion_plantel || 'No especificada' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HISTORIAL CRONOLÓGICO DESPLEGABLE -->
                <div class="info-card space-y-6 mt-16 bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-premium">
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-2 h-8 bg-red-700 rounded-full"></div>
                        <div class="section-title text-black !border-b-0 !mb-0">
                            <i class="fas fa-history text-black"></i> 2. Historial de Interacciones Académicas
                        </div>
                    </div>

                    <div v-if="loadingHistory" class="py-10 text-center animate-pulse">
                        <i class="fas fa-spinner fa-spin text-red-600 text-3xl"></i>
                        <p class="text-[10px] font-black text-slate-400 uppercase mt-4">Recuperando cadena histórica...</p>
                    </div>

                    <div v-else-if="historial.length" class="space-y-4">
                        <div v-for="(h, index) in historial" :key="h.id" class="border border-slate-100 rounded-3xl overflow-hidden shadow-sm relative group">
                            <div class="p-6 md:p-8 flex flex-col md:flex-row justify-between items-center gap-6 transition-colors">
                                <div class="flex items-center gap-6 w-full md:w-auto">
                                    <div class="bg-red-700 w-14 h-14 text-white rounded-2xl flex flex-col items-center justify-center shrink-0 shadow-lg">
                                      
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[8px] font-black uppercase tracking-[0.2em] mb-1" :class="h.es_primera_visita ? 'text-blue-600' : 'text-purple-600'">
                                            {{ h.es_primera_visita ? 'Apertura de Prospecto' : 'Interacción de Seguimiento' }}
                                        </p>
                                        <h4 class="text-lg font-black text-black uppercase tracking-tight truncate">
                                            {{ formatDate(h.fecha) }}
                                        </h4>
                                        <p class="text-[10px] font-bold text-red-600 mt-0.5 uppercase tracking-tighter italic">
                                            Atendió: {{ h.persona_entrevistada }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                                    <button 
                                        v-if="(h.modificaciones_realizadas || 0) < 1"
                                        @click.stop="router.push({ name: 'VisitaEdit', params: { id: h.id } })"
                                        class="btn-edit-inline"
                                    >
                                        <i class="fas fa-edit mr-1"></i> MODIFICAR
                                    </button>
                                    <button @click="toggleExpand(h.id)" class="btn-secondary">
                                        {{ expandedId === h.id ? 'OCULTAR' : 'DETALLES' }}
                                        <i class="fas ml-2" :class="expandedId === h.id ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </button>
                                    <span :class="getOutcomeClass(h.resultado_visita)" class="status-badge !px-5 !py-2 uppercase shadow-sm">
                                        {{ h.resultado_visita }}
                                    </span>
                                </div>
                            </div>
                            <br>

                            <!-- Contenido Desplegable (Mejorado diseño de comentarios) -->
                            <div v-if="expandedId === h.id" class="p-8 md:p-12 bg-slate-50/40 border-t border-slate-100 animate-fade-in">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                                    <div class="space-y-8">
                                        <div>
                                            <h5 class="text-black font-black uppercase text-[10px] tracking-widest mb-4 flex items-center gap-2">
                                                <i class="fas fa-id-badge text-red-700"></i> Resumen de la Entrevista
                                            </h5>
                                            <div class="bg-white p-6 rounded-3xl border border-slate-200 space-y-4 shadow-sm">
                                                <div class="grid grid-cols-2 gap-6">
                                                    <div><label class="label-large">Contacto</label><p class="value-text text-[11px] uppercase">{{ h.persona_entrevistada }}</p></div>
                                                    <div><label class="label-large">Puesto</label><p class="value-text text-[11px] uppercase">{{ h.cargo }}</p></div>
                                                </div>
                                                <div v-if="h.proxima_visita_estimada" class="pt-3 border-t border-slate-50">
                                                    <label class="label-large">Siguiente Paso</label>
                                                    <p class="text-[10px] font-black text-black uppercase">
                                                        <i class="far fa-calendar-alt mr-1.5 text-red-600"></i>
                                                        {{ formatDate(h.proxima_visita_estimada) }} — <span class="text-red-700 italic">{{ h.proxima_accion }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- DISEÑO DE COMENTARIOS MEJORADO -->
                                        <div>
                                            <h5 class="text-black font-black uppercase text-[10px] tracking-widest mb-4 flex items-center gap-2">
                                                <i class="fas fa-comment-dots text-red-700"></i> Observaciones y Acuerdos
                                            </h5>
                                            <div class="bg-amber-50 p-6 md:p-8 rounded-[2rem] border-2 border-amber-200 border-dashed relative overflow-hidden shadow-sm">
                                                <div class="absolute -right-4 -top-4 w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center opacity-40">
                                                    <i class="fas fa-quote-right text-2xl text-amber-300"></i>
                                                </div>
                                                <p class="text-slate-700 text-sm md:text-base font-bold italic leading-relaxed relative z-10">
                                                    "{{ h.comentarios || 'El representante no registró observaciones escritas para esta interacción.' }}"
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                        <h5 class="text-black font-black uppercase text-[10px] tracking-widest mb-4 flex items-center gap-2">
                                            <i class="fas fa-book-open text-red-700"></i> Material Académico Relacionado
                                        </h5>
                                        <div class="grid grid-cols-1 gap-4">
                                            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                                                <div class="p-3 bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest flex justify-between">
                                                    <span>Material de Interés</span>
                                                    <i class="fas fa-eye opacity-50"></i>
                                                </div>
                                                <div class="p-4 space-y-2">
                                                    <div v-for="(item, i) in parseMateriales(h.libros_interes).interes" :key="i" class="flex justify-between items-center text-[10px] font-bold border-b border-slate-50 pb-1.5 last:border-0 uppercase">
                                                        <span class="text-slate-700 truncate max-w-[200px]">{{ item.titulo }}</span>
                                                        <span class="text-red-600 text-[8px] bg-red-50 px-2 py-0.5 rounded-lg">{{ item.tipo }}</span>
                                                    </div>
                                                    <p v-if="!parseMateriales(h.libros_interes).interes?.length" class="text-[10px] text-slate-300 italic text-center py-2">Sin materiales listados</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-20 bg-slate-50 rounded-[3rem] border-2 border-dashed border-slate-100 opacity-60">
                        <i class="fas fa-history text-4xl text-slate-200 mb-4 block"></i>
                        <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest italic">Iniciando cadena de seguimiento técnico.</p>
                    </div>
                </div>

                <!-- 3. TABLA DE AUDITORÍA DE AJUSTES -->
                <div class="info-card shadow-premium border-t-8 border-t-slate-800 bg-white p-0 rounded-[2.5rem] border border-slate-100 overflow-hidden mt-16">
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between bg-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Bitácora de Ajustes Técnicos</h2>
                        </div>
                        <span v-if="allLogs.length" class="text-[9px] font-black bg-red-600 text-white px-3 py-1 rounded-full uppercase tracking-widest">
                            {{ allLogs.length }} MODIFICACIONES
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse">
                            <thead class="bg-slate-50 text-slate-400 uppercase text-[9px] font-black tracking-[0.2em] border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-left w-64">Intervención Editada</th>
                                    <th class="px-6 py-4 text-left">Motivo de la Modificación</th>
                                    <th class="px-6 py-4 text-left w-56">Responsable</th>
                                    <th class="px-8 py-4 text-right w-48">Sincronización</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 bg-white">
                                <tr v-for="(log, index) in allLogs" :key="log.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-black text-red-700 uppercase tracking-tighter">
                                                {{ log.visit_type === 'primera' ? 'Primera Visita' : 'Seguimiento' }}
                                                <br>
                                            </span>
                                            <span class="text-[9px] font-bold text-slate-400 uppercase mt-1">{{ formatDateShort(log.visit_date) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6">
                                        <p class="text-[12px] font-bold text-slate-700 italic leading-relaxed uppercase">
                                            {{ log.motivo_cambio || 'SIN JUSTIFICACIÓN TÉCNICA' }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-6">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 bg-slate-900 text-white rounded-full flex items-center justify-center text-[8px] font-black">
                                            </div>
                                            <span class="text-[11px] font-black text-slate-800 uppercase tracking-tight">
                                                {{ log.user?.name || 'Representante' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="text-[11px] font-black text-slate-800 uppercase">{{ formatDateOnly(log.created_at) }}</span>
                                            <br>
                                            <span class="text-[9px] font-bold text-slate-400 mt-0.5 tracking-tighter">{{ formatTimeOnly(log.created_at) }}</span>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr v-if="allLogs.length === 0">
                                    <td colspan="5" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center opacity-40">
                                            <i class="fas fa-shield-alt text-4xl text-slate-300 mb-4"></i>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Expediente con integridad original</p>
                                            <p class="text-[9px] text-slate-400 mt-1 italic">No se han registrado ajustes posteriores al registro inicial.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. PRÓXIMO COMPROMISO -->
                <div class="info-card border-none bg-slate-900 p-10 rounded-[3rem] shadow-xl mt-8 text-center text-white relative overflow-hidden">
                    <div class="relative z-10">
                        <h3 class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-500 mb-8">Agenda de Retorno Estimada</h3>
                        
                        <div v-if="proximoCompromisoFinal" class="bg-white/5 p-8 rounded-[2.5rem] border border-white/10 max-w-lg mx-auto backdrop-blur-sm">
                            <p class="text-4xl font-black text-red-500 tracking-tighter">{{ formatDate(proximoCompromisoFinal.fecha) }}</p>
                            <div class="mt-4 inline-flex items-center gap-2 bg-red-700/20 text-red-400 px-6 py-2 rounded-full border border-red-700/30">
                                <i class="fas fa-bullseye text-[10px]"></i>
                            </div>
                        </div>
                        <p v-else class="text-slate-500 italic text-sm mb-6">Sin fecha de seguimiento programada</p>

                        <button v-if="visita.resultado_visita === 'seguimiento' && visita.cliente?.tipo !== 'CLIENTE'" 
                            @click="router.push({ name: 'SeguimientoID', params: { id: visita.id } })" 
                            class="btn-primary mt-8 mx-auto !px-20 !py-5 shadow-2xl shadow-red-900/40">
                            <i class="fas fa-plus-circle mr-2"></i> REGISTRAR SEGUIMIENTO
                        </button>
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
const historial = ref([]);
const loading = ref(true);
const loadingHistory = ref(true);
const error = ref(null);
const expandedId = ref(null);

const fetchVisitaDetail = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/visitas/${route.params.id}`);
        visita.value = response.data;
        
        if (visita.value.cliente_id) {
            fetchFullHistory(visita.value.cliente_id);
        }
    } catch (err) {
        error.value = "Expediente no localizado en el servidor central.";
    } finally {
        loading.value = false;
    }
};

const fetchFullHistory = async (clienteId) => {
    loadingHistory.value = true;
    try {
        const response = await axios.get('/visitas', { 
            params: { cliente_id: clienteId, full_history: 1, include_logs: 1 } 
        });
        
        const dataReceived = response.data.data || response.data;
        historial.value = Array.isArray(dataReceived) 
            ? dataReceived.sort((a,b) => a.id - b.id)
            : [];
            
    } catch (e) {
        console.error("Fallo al sincronizar historial:", e);
    } finally {
        loadingHistory.value = false;
    }
};

const allLogs = computed(() => {
    const aggregated = [];
    historial.value.forEach(v => {
        if (v.logs && v.logs.length) {
            v.logs.forEach(l => {
                aggregated.push({
                    ...l,
                    visit_type: v.es_primera_visita ? 'primera' : 'seguimiento',
                    visit_date: v.fecha
                });
            });
        }
    });
    return aggregated.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const proximoCompromisoFinal = computed(() => {
    const conAgenda = [...historial.value]
        .filter(h => h.proxima_visita_estimada)
        .sort((a, b) => b.id - a.id);

    if (conAgenda.length > 0) return { fecha: conAgenda[0].proxima_visita_estimada, accion: conAgenda[0].proxima_accion };
    return null;
});

const toggleExpand = (id) => expandedId.value = expandedId.value === id ? null : id;

const parseMateriales = (raw) => {
    if (!raw) return { interes: [], entregado: [] };
    if (typeof raw === 'object' && !Array.isArray(raw)) return raw;
    try { return JSON.parse(raw); } catch (e) { return { interes: [], entregado: [] }; }
};

const formatLevels = (levels) => {
    if (!levels) return ['General'];
    if (Array.isArray(levels)) return levels;
    return levels.split(',').map(l => l.trim()).filter(l => l.length > 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '---';
    const [year, month, day] = dateString.split('T')[0].split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatDateOnly = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatDateShort = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: '2-digit' });
};

const formatTimeOnly = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
};

const getMonthShort = (dateString) => {
    if (!dateString) return '---';
    const [year, month, day] = dateString.split('T')[0].split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('es-ES', { month: 'short' }).toUpperCase();
};

const getDay = (dateString) => dateString ? dateString.split('T')[0].split('-')[2] : '--';

const getOutcomeClass = (outcome) => {
    const base = 'status-badge uppercase font-black tracking-widest ';
    if (outcome === 'compra') return base + 'bg-green-100 text-green-700 border-green-200';
    if (outcome === 'rechazo') return base + 'bg-red-100 text-red-700 border-red-200';
    return base + 'bg-orange-100 text-orange-700 border-orange-200';
};

onMounted(fetchVisitaDetail);
</script>

<style scoped>
.info-card { background: white; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #000; margin-bottom: 30px; border-bottom: 2px solid #f8fafc; padding-bottom: 15px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 2px; }
.label-large { display: block; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; color: #94a3b8; margin-bottom: 6px; letter-spacing: 0.1em; }
.value-text { color: #1e293b; line-height: 1.4; font-weight: 800; }
.status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.6rem; font-weight: 900; display: inline-block; border: 1px solid transparent; }
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0, 0, 0, 0.08); }
.btn-primary-action { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; padding: 14px 45px; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; transition: 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-edit-inline { @apply bg-white border-2 border-slate-100 text-slate-400 py-1.5 px-4 rounded-xl hover:bg-red-50 hover:text-red-700 hover:border-red-100 transition-all cursor-pointer text-[10px] font-black uppercase; }
.btn-expand-history { @apply bg-slate-50 text-slate-500 py-2 px-5 rounded-xl text-[10px] font-black uppercase hover:bg-slate-100 transition-all cursor-pointer; }
.badge-red-outline { @apply bg-red-50 text-red-700 text-[9px] font-black uppercase px-2 py-0.5 rounded-lg border border-red-100; }
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
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
.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

</style>