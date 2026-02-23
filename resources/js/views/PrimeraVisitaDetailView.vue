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
                <button @click="router.push('/visitas')" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto">
                    <i class="fas fa-arrow-left mr-2"></i> VOLVER AL LISTADO
                </button>
            </div>

            <!-- Loader de Sistema -->
            <div v-if="loading" class="loading-state py-20 text-center">
                <i class="fas fa-circle-notch fa-spin text-5xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Consultando base de datos maestra...</p>
            </div>

            <!-- Error de Conexión -->
            <div v-else-if="error" class="error-message-container p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in">
                <i class="fas fa-exclamation-triangle fa-3xl text-red-600 mb-6"></i>
                <h2 class="text-xl font-black text-black uppercase tracking-tighter">Error de Sincronización</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium">{{ error }}</p>
                <button @click="fetchVisitaDetail" class="btn-primary-action mt-6 px-10">Reintentar</button>
            </div>

            <!-- Contenido Principal -->
            <div v-else-if="visita" class="space-y-8 animate-fade-in pb-20">
                
                <!-- 1. IDENTIDAD DEL PLANTEL -->
                <div class="info-card shadow-premium border-t-8 border-t-red-700">
                    <div class="section-title">
                        <i class="fas fa-school text-red-700"></i> 1. Identidad y Ubicación del Plantel
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-6">
                        <!-- Columna Datos Base -->
                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Nombre del Plantel</label>
                                <p class="value-text text-xl leading-none uppercase font-black">{{ visita.nombre_plantel || visita.cliente?.name }}</p>
                            </div>

                             <div class="data-row">
                                    <label class="label-large">Estatus</label>
                                    <p class="font-black text-sm value-text  uppercase tracking-wider" :class="visita?.cliente?.tipo === 'CLIENTE' ? 'text-green-600' : 'text-red-700'">
                                        {{ visita?.cliente?.tipo || 'PROSPECTO' }}
                                    </p>
                                </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">RFC del Plantel</label>
                                    <p class="value-text font-mono uppercase tracking-widest">{{ visita.rfc_plantel || visita.cliente?.rfc || 'No registrado' }}</p>
                                </div>

                                 <div class="data-row">
                                <label class="label-large">Ubicación Geográfica</label>
                                <div v-if="visita.latitud" class="flex items-center gap-3 bg-red-50/30 p-4 rounded-2xl border border-red-100 mt-2">
                                    <div class="w-12 h-12 bg-red-700 text-white rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-mono font-bold text-red-600 mt-1">{{ visita.latitud }}, {{ visita.longitud }}</p>
                                    </div>
                                    <a :href="`https://www.google.com/maps?q=${visita.latitud},${visita.longitud}`" target="_blank" class="text-[10px] font-black uppercase text-red-700 hover:underline px-3 border-l border-red-100">Ver Mapa</a>
                                </div>
                                <p v-else class="value-text text-slate-300 italic text-sm">Sin coordenadas registradas</p>
                            </div>

                                <div class="data-row">
                                    <label class="label-large">Niveles Educativos</label>
                                    <div class="flex flex-wrap gap-1.5 mt-1">
                                        <span v-for="n in formatLevels(visita.nivel_educativo_plantel || visita.cliente?.nivel_educativo)" :key="n" class="value-text   badge-red-outline">
                                          -  {{ n }}
                                            <br>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">Estado</label>
                                    <p class="value-text uppercase">{{ visita.estado?.estado || 'No especificado' }}</p>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Dirección Completa</label>
                                    <p class="value-text uppercase">{{ visita.direccion_plantel|| 'No especificado' }}</p>
                                </div>
                               
                            </div>
                        </div>  
                        <br>

                        <!-- Columna Contacto -->
                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Teléfono</label>
                                <p class="value-text tracking-tighter"><i class="fas fa-phone-alt mr-2 opacity-30"></i>{{ visita.telefono_plantel || visita.cliente?.telefono || 'N/A' }}</p>
                            </div>
                          <div class="data-row">
                                    <label class="label-large">Correo Electrónico</label>
                                    <!-- APLICACIÓN DE CORREO EN MINÚSCULAS CON ESTILO PRIORITARIO -->
                                    <p class="value-text text-sm" style="text-transform: none !important;">
                                        <i class="fas fa-envelope mr-2 opacity-30"></i>
                                        {{ (visita.email_plantel || visita.cliente?.email || 'N/A').toLowerCase() === 'n/a' ? 'N/A' : (visita.email_plantel || visita.cliente?.email).toLowerCase() }}
                                    </p>
                                </div>
                            <div class="data-row">
                                <label class="label-large">Nombre del Director / Coordinador</label>
                                <p class="value-text italic leading-relaxed text-sm">{{ visita.director_plantel || visita.cliente?.contacto || 'Sin director registrado' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HISTORIAL CRONOLÓGICO -->
                <div class="info-card space-y-6 mt-16">
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-2 h-8 bg-red-700 rounded-full"></div>
                        <div class="section-title text-black !border-black/5">
                            <i class="fas fa-handshake text-black"></i> 2. Historial Cronológico de Interacciones
                        </div>
                    </div>

                    <div v-if="loadingHistory" class="py-10 text-center animate-pulse">
                        <i class="fas fa-spinner fa-spin text-red-600 text-3xl"></i>
                        <p class="text-[10px] font-black text-slate-400 uppercase mt-4">Sincronizando cadena de seguimiento...</p>
                    </div>

                    <div v-else-if="historial.length" class="space-y-4">
                        <div v-for="(h, index) in historial" :key="h.id" class="border border-slate-100 rounded-3xl overflow-hidden shadow-sm relative group bg-white">
                            <!-- Header de la tarjeta -->
                            <div class="p-6 md:p-8 flex flex-col md:flex-row justify-between items-center gap-6 transition-colors">
                                
                                <div class="flex items-center gap-6 w-full md:w-auto">
                                    <div class="min-w-0">
                                        <p class="text-[8px] label-large font-black uppercase tracking-[0.2em] mb-1" :class="h.es_primera_visita ? 'text-blue-600' : 'text-purple-600'">
                                            {{ h.es_primera_visita ? 'Apertura de Prospecto' : 'Interacción de Seguimiento' }}
                                        </p>
                                        <h4 class="text-xl font-black text-black uppercase tracking-tight truncate max-w-[200px] md:max-w-none">
                                            {{ formatDate(h.fecha) }}
                                        </h4>
                                         <p class="label-large"><i class="fas fa-comment-dots text-red-700 label-large"></i>Estatus</p>
                                            <span :class="getOutcomeClass(h.resultado_visita)" class="status-badge !px-5 !py-2 uppercase shadow-sm">
                                        {{ h.resultado_visita }}
                                    </span>
                                         <div>
                                                        <label class="label-large">Persona Entrevistada</label>
                                                        <p class="value-text">{{ h.persona_entrevistada }}</p>
                                                    </div>
                                                    <div>
                                                        <label class="label-large">Cargo/Puesto</label>
                                                        <p class="value-text">{{ h.cargo }}</p>
                                                    </div>
                                    </div>
                                </div>


                                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                                     
                                    <!-- LÓGICA: Solo permite modificar si no se ha modificado antes -->
                                    <button 
                                        v-if="(h.modificaciones_realizadas || 0) < 1"
                                        @click="router.push({ name: 'VisitaEdit', params: { id: h.id } })"
                                        class="btn-secondary hover:scale-105 transition-all"
                                    >
                                        <i class="fas fa-edit mr-1"></i> MODIFICAR
                                    </button>
<br><br>
                                    <!-- BOTÓN VER DETALLE (Controlador del Acordeón) -->
                                    <button 
                                        @click="toggleExpand(h.id)"
                                        class="btn-primary !border-red-600 !text-red-700 hover:bg-red-50 hover:scale-105 transition-all"
                                    >
                                        <i class="fas" :class="expandedId === h.id ? 'fa-eye-slash' : 'fa-plus-circle'"></i>
                                        <span class="ml-2">{{ expandedId === h.id ? 'OCULTAR' : 'VER DETALLE' }}</span>
                                    </button>

                                   
                                    
                                    <div @click="toggleExpand(h.id)" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-300 cursor-pointer hover:text-red-600 transition-colors">
                                        <i class="fas fa-chevron-down transition-transform duration-500" 
                                           :class="{'rotate-180 text-red-600': expandedId === h.id}"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Contenido Desplegable (Expediente) -->
                            <div v-if="expandedId === h.id" class="p-8 md:p-12 bg-slate-50/40 border-t border-slate-100 animate-fade-in">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                                    <div class="space-y-8">
                                        
                                    </div>

                                    <div class="space-y-8">
                                        <h5 class="text-black font-black label-large uppercase text-[11px] tracking-[0.2em] mb-4 flex items-center gap-2">
                                            <i class="fas fa-book-open text-red-700"></i> Materiales y Muestras
                                        </h5>
                                        
                                       <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 animate-fade-in">
                                            <div class="table-container">
                                                <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white">
                                                    <table class="min-width-full divide-y divide-gray-200">
                                                        <thead class="bg-gray-100">
                                                            <tr>
                                                                <th class="table-header">Material de Interés</th>
                                                                <th class="table-header text-right">Formato</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white bk divide-y divide-gray-100">
                                                            <tr v-for="(item, i) in parseMateriales(h.libros_interes).interes" :key="i" class="hover:bg-gray-50 transition-colors">
                                                                <td class="table-cell">
                                                                    <div class="text-sm font-bold text-gray-800 uppercase leading-tight">
                                                                        {{ item.titulo }}
                                                                    </div>
                                                                </td>
                                                                <td class="table-cell text-right">
                                                                    <span class="status-badge bg-blue-50 text-blue-700 border border-blue-100">
                                                                        {{ (item.tipo || 'Físico').toUpperCase() }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr v-if="!parseMateriales(h.libros_interes).interes.length">
                                                                <td colspan="2" class="px-5 py-10 text-center text-[10px] text-slate-300 font-black uppercase tracking-widest italic">
                                                                    Sin intereses registrados
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                
                                            </div>
                                        <br>
                                            <div class="table-container">
                                                <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white border-red-100">
                                                    <table class="min-width-full divide-y divide-gray-200">
                                                        <thead class="bg-red-50">
                                                            <tr>
                                                                <th class="table-header !text-red-800">Muestra Entregada</th>
                                                                <th class="table-header text-right !text-red-800">Cantidad</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white bk divide-y divide-red-50">
                                                            <tr v-for="(item, i) in parseMateriales(h.libros_interes).entregado" :key="i" class="hover:bg-red-50/30 transition-colors">
                                                                <td class="table-cell">
                                                                    <div class="text-sm font-bold text-red-900 uppercase leading-tight">
                                                                        {{ item.titulo }}
                                                                    </div>
                                                                </td>
                                                                <td class="table-cell text-right">
                                                                    <span class="text-sm font-black text-red-600 bg-red-100 px-3 py-1 rounded-lg border border-red-200">
                                                                        {{ item.cantidad }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr v-if="!parseMateriales(h.libros_interes).entregado.length">
                                                                <td colspan="2" class="px-5 py-10 text-center text-[10px] text-red-200 font-black uppercase tracking-widest italic">
                                                                    No se entregaron muestras físicas
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                        <div>

                                            

                                            <h5 class="text-black font-black uppercase text-[11px] label-large tracking-widest mb-4 flex items-center gap-2">
                                                <i class="fas fa-comment-dots text-red-700 label-large"></i> Observaciones
                                            </h5>
                                            <div class="bg-amber-50 p-8 rounded-3xl border border-amber-100 italic text-slate-700 text-sm leading-relaxed font-medium shadow-inner">
                                                "{{ h.comentarios || 'El representante no dejó observaciones escritas en esta sesión.' }}"
                                            </div>
                                            <br>
                                           
                                        </div>
    </div>
</div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-20 bg-white rounded-[3rem] border-2 border-dashed border-slate-100 opacity-60">
                        <i class="fas fa-history text-4xl text-slate-200 mb-4 block"></i>
                        <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest italic">Aún no se han registrado seguimientos posteriores.</p>
                    </div>
                </div>



                <!-- 4. PRÓXIMO COMPROMISO -->
                <div class="info-card border-none bg-slate-100 p-10 rounded-[3rem] border border-slate-200 shadow-sm mt-8 text-center">
                    <div class="flex flex-col items-center gap-6">
                        <div class="section-title text-black !border-black/5 !mb-0">
                            <i class="fas fa-calendar-alt text-black"></i> 3. Próximo Compromiso y Acción
                        </div>
                        
                        <div v-if="proximoCompromisoFinal" class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-200 w-full max-w-lg mx-auto">
                            <label class="label-large !text-red-700">Fecha Agendada</label>
                            <p class="text-4xl font-black text-black tracking-tighter mt-2">{{ formatDate(proximoCompromisoFinal.fecha) }}</p>
                            <div class="flex items-center justify-center gap-2 mt-4 bg-red-700 text-white p-3 rounded-2xl">
                                <p class="label-large "><i class="fas fa-bullseye text-sm"></i>Objetivo</p>
                                <span class="text-[11px] font-black uppercase tracking-widest">{{ proximoCompromisoFinal.accion }}</span>
                            </div>
                            <br>
                        </div>
                        
                        <p v-else class="text-slate-400 italic text-sm">Sin fecha programada de retorno</p>

                        <button v-if="visita.resultado_visita === 'seguimiento' && visita.cliente?.tipo !== 'CLIENTE'" 
                            @click="router.push({ name: 'SeguimientoID', params: { id: visita.id } })" 
                            class="w-full max-w-md btn-primary-action shadow-2xl transition-all active:scale-95 mx-auto">
                            <i class="fas fa-plus-circle mr-2 "></i> Registrar Nuevo Seguimiento
                        </button>
                    </div>
                </div>

                                <!-- 3. BITÁCORA DE AJUSTES TÉCNICOS (Auditoría) -->
                <div class="info-card shadow-premium border-t-8 border-t-slate-800 bg-white p-0 rounded-[2.5rem] border border-slate-100 overflow-hidden mt-16">
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between bg-white">
                        
                        <div class="section-title text-black !border-black/5">
                            <i class="fas fa-handshake text-black"></i> 4. Bitácora de Ajustes Técnicos
                        </div>
                    </div>

                    <div class="table-container mt-4 animate-fade-in">
    <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white">
        <table class="min-width-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="table-header w-64">Intervención Editada</th>
                    <th class="table-header">Motivo de la Modificación</th>
                    <th class="table-header w-56">Responsable</th>
                    <th class="table-header text-right w-48">Sincronización</th>
                </tr>
            </thead>
            <tbody class="bg-white bk divide-y divide-gray-100">
                <tr v-for="(log, index) in allLogs" :key="log.id" class="hover:bg-gray-50 transition-colors">
                    <td class="table-cell">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-red-800 uppercase tracking-tighter">
                                {{ log.visit_type === 'primera' ? 'Primera Visita' : 'Seguimiento' }}
                            </span>
                            <br>
                            <span class="text-[10px] font-bold text-gray-400 uppercase mt-1 italic">
                                {{ formatDateShort(log.visit_date) }}
                            </span>
                        </div>
                    </td>
                    <td class="table-cell">
                        <p class="text-[11px] font-bold text-slate-700 italic leading-relaxed uppercase">
                            "{{ log.motivo_cambio || 'SIN JUSTIFICACIÓN TÉCNICA' }}"
                        </p>
                    </td>
                    <td class="table-cell">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-black text-gray-800 uppercase tracking-tight">
                                {{ log.user?.name || 'Representante' }}
                            </span>
                        </div>
                    </td>
                    <td class="table-cell text-right">
                        <div class="flex flex-col items-end">
                            <span class="text-[11px] font-black text-gray-800 uppercase">
                                {{ formatDateOnly(log.created_at) }}
                            </span>
                            <br>
                            <span class="text-[9px] font-bold text-gray-400 mt-0.5 tracking-tighter uppercase">
                                {{ formatTimeOnly(log.created_at) }}
                            </span>
                        </div>
                    </td>
                </tr>

                <tr v-if="allLogs.length === 0">
                    <td colspan="4" class="px-6 py-20 text-center">
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
        
        if (visita.value.cliente_id || visita.value.nombre_plantel) {
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
        // Solicitamos el historial completo incluyendo los logs para la auditoría
        const response = await axios.get('/visitas', { 
            params: { 
                cliente_id: clienteId, 
                full_history: 1, 
                include_logs: 1 
            } 
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

/**
 * LÓGICA DE AUDITORÍA:
 * Agregamos todos los logs de todas las visitas del historial en una sola lista cronológica.
 */
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
    if (!visita.value) return null;
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
    const cleanDate = dateString.split('T')[0];
    const [year, month, day] = cleanDate.split('-').map(Number);
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
    const cleanDate = dateString.split('T')[0];
    const [year, month, day] = cleanDate.split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('es-ES', { month: 'short' }).replace('.', '').toUpperCase();
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
.info-card { background: white; padding: 40px; border-radius: 40px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #000000; margin-bottom: 30px; border-bottom: 2px solid #f8fafc; padding-bottom: 15px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 2px; }

.label-large { display: block; font-size: 0.72rem; font-weight: 900; text-transform: uppercase; color: #000000; margin-bottom: 6px; letter-spacing: 0.12em; opacity: 0.8; }
.value-text { color: #be5e5e; line-height: 1.4;  }

.status-badge { padding: 6px 16px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; border: 1px solid transparent; }
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0, 0, 0, 0.08); }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

.table-responsive { width: 100%; background: white; transition: all 0.3s ease; }
.table-shadow-lg { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }

.table-cell { padding: 16px 20px; vertical-align: middle; }

.btn-primary-action { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; padding: 14px 45px; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: center; gap: 10px; }

/* Botón Editar y Desplegar dentro de Historial */
.btn-edit-inline { @apply bg-white border-2 border-slate-200 text-slate-500 py-1.5 px-4 rounded-xl text-[10px] font-black uppercase hover:bg-red-50 hover:text-red-700 hover:border-red-200; cursor: pointer; }

.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d8afbb 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
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



.badge-red-outline { @apply bg-red-50 text-red-700 text-[9px] font-black uppercase px-2 py-0.5 rounded-lg border border-red-100; }
</style>