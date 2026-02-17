<template>
    <div class="content-wrapper p-2 md:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto">
            
            <!-- Encabezado del Expediente -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 animate-fade-in">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-3 mb-1">
                        <span class="text-[10px] font-black text-red-700 bg-red-50 px-3 py-1 rounded-full border border-red-100 uppercase tracking-widest">
                            ID: #CP-{{ id }}
                        </span>
                        <span v-if="sesion" class="status-badge-majestic" :class="sesion.status.toLowerCase()">
                            {{ sesion.status }}
                        </span>
                    </div>
                    <h1 v-if="sesion" class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight uppercase truncate">
                        {{ sesion.title }}
                    </h1>
                    <h1 v-else class="text-2xl font-black text-slate-300 animate-pulse uppercase">Cargando expediente...</h1>
                </div>
                
                <div class="flex gap-2 w-full sm:w-auto">
                    <button @click="router.push({ name: 'CapacitacionesEdit', params: { id } })" class="btn-primary flex-1 sm:flex-none flex items-center justify-center gap-2 px-8 py-3 rounded-2xl text-sm font-black shadow-lg shadow-red-100 transition-all active:scale-95">
                        <i class="fas fa-edit"></i> EDITAR EXPEDIENTE
                    </button>
                    <button @click="router.push('/capacitaciones')" class="btn-secondary flex-1 sm:flex-none flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-sm font-black border-2 border-slate-200 bg-white hover:bg-slate-50 transition-all">
                        <i class="fas fa-arrow-left"></i> VOLVER
                    </button>
                </div>
            </header>

            <!-- Loader Inicial -->
            <div v-if="loading" class="py-20 text-center animate-pulse">
                <i class="fas fa-circle-notch fa-spin text-5xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs italic">Consultando base de datos técnica...</p>
            </div>

            <!-- Contenido del Expediente -->
            <div v-else-if="sesion" class="animate-fade-in space-y-8 pb-20">
                
                <!-- 1. RESUMEN DE TIEMPO (BANNER) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="summary-card shadow-premium border-t-8 border-t-slate-800">
                        <label class="label-mini">Fecha de la sesión</label>
                        <p class="text-2xl font-black text-slate-800 tracking-tighter uppercase">{{ formatDateLong(sesion.start) }}</p>
                    </div>
                    <div class="summary-card shadow-premium border-t-8 border-t-red-700">
                        <label class="label-mini">Rango de horario</label>
                        <p class="text-2xl font-black text-slate-800 tracking-tighter uppercase">
                            {{ formatTime(sesion.start) }} <span class="text-red-700 mx-1">—</span> {{ formatTime(sesion.end || sesion.start) }}
                        </p>
                    </div>
                    <div class="summary-card shadow-premium bg-slate-900 text-white border-none">
                        <label class="label-mini !text-slate-400">Capacitador asignado</label>
                        <p class="text-xl font-black text-red-400 uppercase tracking-tight">{{ sesion.extendedProps.profesional }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- COLUMNA IZQUIERDA: IDENTIDAD (75%) -->
                    <div class="lg:col-span-8 space-y-8">
                        
                        <!-- BLOQUE: IDENTIDAD DEL PLANTEL -->
                        <div class="form-section shadow-premium border border-slate-100 bg-white p-8 rounded-[2.5rem]">
                            <div class="section-title">
                                <i class="fas fa-school text-red-700"></i> 1. Información de la Institución
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6">
                                <div class="data-group">
                                    <label class="label-style">Nombre del Plantel</label>
                                    <p class="text-lg font-black text-slate-800 uppercase leading-tight">{{ sesion.title }}</p>
                                </div>
                                <div class="data-group">
                                    <label class="label-style">Sucursal / Ubicación</label>
                                    <p class="text-sm font-bold text-slate-600 uppercase">{{ sesion.extendedProps.sucursal }}</p>
                                </div>
                                <div class="data-group col-span-full pt-4 border-t border-slate-50">
                                    <label class="label-style">Dirección Registrada</label>
                                    <p class="text-xs font-medium text-slate-500 italic leading-relaxed uppercase">
                                        <i class="fas fa-map-marker-alt text-red-400 mr-1"></i>
                                        CALLE DE LOS MAESTROS #402, COL. CENTRO, CULIACÁN, SINALOA, CP 80000.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- BLOQUE: OBJETIVOS ACADÉMICOS -->
                        <div class="form-section shadow-premium border border-slate-100 bg-white p-8 rounded-[2.5rem]">
                            <div class="section-title">
                                <i class="fas fa-bullseye text-slate-400"></i> 2. Objetivos Académicos y Técnicos
                            </div>
                            
                            <div class="mt-6 bg-slate-50 p-8 rounded-[2rem] border border-dashed border-slate-200">
                                <p class="text-slate-700 text-lg font-medium italic leading-relaxed whitespace-pre-wrap">
                                    "{{ sesion.extendedProps.objetivo || 'NO SE ESPECIFICÓ UN OBJETIVO DETALLADO PARA ESTA SESIÓN EN EL REGISTRO INICIAL.' }}"
                                </p>
                            </div>
                        </div>

                    </div>

                    <!-- COLUMNA DERECHA: ESTADO Y LOGS (25%) -->
                    <aside class="lg:col-span-4 space-y-8">
                        
                        <!-- BLOQUE: SEGUIMIENTO DE ESTADO -->
                        <div class="form-section shadow-premium border border-slate-100 bg-white p-8 rounded-[2.5rem]">
                            <div class="section-title !text-[10px]">
                                <i class="fas fa-info-circle text-red-700"></i> Estado de la sesión
                            </div>
                            
                            <div class="mt-6 space-y-4">
                                <div class="p-6 rounded-3xl border-2 flex flex-col items-center text-center gap-3 transition-all"
                                     :class="getStatusContainerClass(sesion.status)">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-xl shadow-inner bg-white">
                                        <i class="fas" :class="getStatusIcon(sesion.status)"></i>
                                    </div>
                                    <h3 class="text-xl font-black uppercase tracking-tighter">{{ sesion.status }}</h3>
                                </div>

                                <div class="pt-6 border-t border-slate-50">
                                    <label class="label-mini text-center block mb-4">Acciones de estatus</label>
                                    <div class="grid grid-cols-1 gap-2">
                                        <button v-if="sesion.status === 'PROGRAMADA'" class="btn-action-status hover:bg-green-50 hover:text-green-700 hover:border-green-200">
                                            MARCAR COMO ATENDIDA
                                        </button>
                                        <button v-if="sesion.status === 'PROGRAMADA'" class="btn-action-status hover:bg-red-50 hover:text-red-700 hover:border-red-200">
                                            CANCELAR SESIÓN
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BLOQUE: AUDITORÍA RÁPIDA -->
                        <div class="p-6 bg-slate-900 rounded-[2.5rem] text-white shadow-xl">
                            <h3 class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-6">Auditoría del expediente</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center text-[10px]">
                                    <span class="text-slate-500 font-bold uppercase">Creado por:</span>
                                    <span class="font-black uppercase">SISTEMA MAJESTIC</span>
                                </div>
                                <div class="flex justify-between items-center text-[10px]">
                                    <span class="text-slate-500 font-bold uppercase">Fecha registro:</span>
                                    <span class="font-black uppercase">{{ formatDateLong(new Date()) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-[10px]">
                                    <span class="text-slate-500 font-bold uppercase">Modificaciones:</span>
                                    <span class="font-black">0</span>
                                </div>
                            </div>
                        </div>

                    </aside>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const id = route.params.id;

const loading = ref(true);
const sesion = ref(null);

// Simulación de hidratación (Hardcoded temporal)
const fetchSessionDetail = () => {
    loading.value = true;
    setTimeout(() => {
        sesion.value = {
            id: id,
            title: 'COLEGIO MÉXICO AMERICANO',
            start: '2026-02-16T12:21:00',
            end: '2026-02-16T14:30:00',
            status: 'PROGRAMADA',
            extendedProps: {
                sucursal: 'CALZADA DE LAS LOMAS #123, CULIACÁN',
                profesional: 'Angel David Morales Cuenca',
                objetivo: 'CAPACITACIÓN TÉCNICA SOBRE EL USO DE LA PLATAFORMA MAJESTIC DIGITAL PARA DOCENTES DE NUEVO INGRESO. REVISIÓN DE LICENCIAS Y MÉTODOS DE EVALUACIÓN.'
            }
        };
        loading.value = false;
    }, 800);
};

const formatDateLong = (date) => {
    return new Date(date).toLocaleDateString('es-ES', { 
        day: 'numeric', month: 'long', year: 'numeric' 
    });
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('es-ES', { 
        hour: 'numeric', minute: '2-digit', hour12: true 
    });
};

const getStatusContainerClass = (status) => {
    switch (status?.toUpperCase()) {
        case 'ATENDIDA': return 'bg-green-50 border-green-200 text-green-700';
        case 'CANCELADA': return 'bg-red-50 border-red-200 text-red-700';
        default: return 'bg-amber-50 border-amber-200 text-amber-700';
    }
};

const getStatusIcon = (status) => {
    switch (status?.toUpperCase()) {
        case 'ATENDIDA': return 'fa-check-double';
        case 'CANCELADA': return 'fa-times-circle';
        default: return 'fa-calendar-check';
    }
};

onMounted(fetchSessionDetail);
</script>

<style scoped>
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.form-section { background: white; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #1e293b; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 2px; border-bottom: 2px solid #f8fafc; padding-bottom: 15px; margin-bottom: 20px; }

.summary-card { background: white; padding: 30px; border-radius: 35px; display: flex; flex-col: column; justify-content: center; }

.label-style { @apply text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-[0.15em]; }

.data-group p { line-height: 1.4; }

.status-badge-majestic { @apply px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border border-transparent inline-flex items-center; }
.status-badge-majestic.programada { @apply bg-blue-50 text-blue-600 border-blue-100; }
.status-badge-majestic.atendida { @apply bg-emerald-50 text-emerald-600 border-emerald-100; }
.status-badge-majestic.cancelada { @apply bg-red-50 text-red-600 border-red-100; }

.btn-primary { 
    background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); 
    color: white; border: none; cursor: pointer;
}

.btn-action-status {
    @apply w-full bg-white border-2 border-slate-100 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all;
}

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>