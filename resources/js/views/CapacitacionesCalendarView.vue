<template>
    <div class="content-wrapper p-2 md:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto">
            
            <!-- Encabezado de Navegación -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 animate-fade-in">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight leading-tight uppercase flex items-center gap-3">
                        <i class="fas fa-calendar-alt text-red-600"></i> Agenda Maestra
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium mt-1 uppercase tracking-widest italic">
                        Calendario integral de actividades técnicas y académicas.
                    </p>
                </div>
                <div class="flex gap-3 w-full sm:w-auto">
                    <button @click="router.push('/capacitaciones')" class="btn-secondary !border-2 !rounded-2xl flex items-center gap-2 uppercase font-black text-[10px] px-6">
                        <i class="fas fa-list"></i> VOLVER AL LISTADO
                    </button>
                </div>
            </header>

            <!-- ✅ Layout Principal: 30% detalles | 70% calendario -->
            <div class="main-layout">
                
                <!-- Panel de Detalles — 30% -->
                <aside class="details-panel">
                    <Transition name="slide-left" mode="out-in">
                        <div v-if="selectedEvent" :key="selectedEvent.id" class="bg-white rounded-[2.5rem] shadow-premium border border-slate-100 overflow-hidden animate-fade-in">
                            <!-- Cabecera con color de estatus -->
                            <div class="p-6 text-white transition-colors duration-500" :class="getStatusBg(selectedEvent.extendedProps.status)">
                                <div class="flex justify-between items-start">
                                    <span class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">Expediente de Cita</span>
                                    <button @click="selectedEvent = null" class="text-white/50 hover:text-white transition-colors">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <h2 class="text-xl font-black uppercase tracking-tighter mt-4 leading-tight">
                                    {{ selectedEvent.title }}
                                </h2>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-[9px] font-black uppercase bg-white/20 px-3 py-1 rounded-full border border-white/10">
                                        {{ selectedEvent.extendedProps.status }}
                                    </span>
                                </div>
                            </div>

                            <!-- Cuerpo de Información -->
                            <div class="p-8 space-y-6">
                                <div class="space-y-4">
                                    <div class="data-group">
                                        <label class="label-mini">Institución / Plantel</label>
                                        <p class="text-sm font-black text-slate-800 uppercase">
                                            <i class="fas fa-school text-red-700 mr-2"></i> {{ selectedEvent.extendedProps.sucursal }}
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="data-group">
                                            <label class="label-mini">Fecha</label>
                                            <p class="text-xs font-bold text-slate-600 uppercase">
                                                {{ formatDateLong(selectedEvent.start) }}
                                            </p>
                                        </div>
                                        <div class="data-group">
                                            <label class="label-mini">Hora</label>
                                            <p class="text-xs font-bold text-slate-600 uppercase">
                                                <i class="far fa-clock text-red-600 mr-1"></i> {{ formatTime(selectedEvent.start) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="data-group border-t border-slate-50 pt-4">
                                        <label class="label-mini">Representante Majestic</label>
                                        <p class="text-sm font-black text-slate-700 uppercase">
                                            {{ selectedEvent.extendedProps.profesional }}
                                        </p>
                                    </div>
                                    
                                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                        <label class="label-mini !text-slate-400">Objetivo de la sesión</label>
                                        <p class="text-[11px] font-medium text-slate-600 italic leading-relaxed mt-1">
                                            "Actualización técnica sobre las nuevas licencias digitales para el ciclo escolar 2026."
                                        </p>
                                    </div>
                                </div>

                                <!-- Botones de Acción -->
                                <div class="space-y-3 pt-4 border-t border-slate-50">
                                    <button @click="router.push('/capacitaciones/editar/' + selectedEvent.id)" class="btn-primary w-full !py-4 flex items-center justify-center gap-2">
                                        <i class="fas fa-edit"></i> MODIFICAR CITA
                                    </button>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button class="btn-secondary !text-[9px] !font-black !py-3 uppercase">ATENDIDA</button>
                                        <button class="btn-secondary !text-[9px] !font-black !py-3 uppercase hover:!text-red-600 hover:!border-red-100">CANCELAR</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado vacío del panel -->
                        <div v-else class="bg-white/50 border-2 border-dashed border-slate-200 rounded-[2.5rem] p-12 text-center flex flex-col items-center justify-center min-h-[600px] animate-fade-in shadow-inner">
                            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm mb-6">
                                <i class="fas fa-mouse-pointer text-slate-200 text-3xl"></i>
                            </div>
                            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest">Panel de detalles</h3>
                            <p class="text-[10px] text-slate-400 font-medium uppercase mt-2 max-w-[200px] leading-relaxed tracking-wider">
                                Haz clic en una cita del calendario para ver el expediente técnico detallado.
                            </p>
                        </div>
                    </Transition>
                </aside>

                <!-- Calendario — 70% -->
                <div class="calendar-panel bg-white p-6 rounded-[2.5rem] shadow-premium border border-slate-100 animate-fade-in overflow-hidden">
                    <FullCalendar ref="fullCalendar" :options="calendarOptions" />
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';

const router = useRouter();
const selectedEvent = ref(null);

const sesiones = ref([
    { id: '1', title: 'Diana Carolina Méndez Rojas', start: '2026-02-16T12:21:00', backgroundColor: '#fef3c7', textColor: '#b45309', borderColor: '#fde68a', extendedProps: { status: 'PROGRAMADA', sucursal: 'Consultorio Satélite CDMX No.7', profesional: 'Angel David Morales Cuenca' } },
    { id: '2', title: 'Plantel Educativo Morelos', start: '2026-02-15T10:18:00', backgroundColor: '#fee2e2', textColor: '#b91c1c', borderColor: '#fecaca', extendedProps: { status: 'CANCELADA', sucursal: 'Centro Cuernavaca', profesional: 'Angel David Morales Cuenca' } },
    { id: '3', title: 'Escuela Técnica Superior', start: '2026-02-16T15:00:00', backgroundColor: '#dcfce7', textColor: '#15803d', borderColor: '#bbf7d0', extendedProps: { status: 'ATENDIDA', sucursal: 'CDMX Norte', profesional: 'Angel David Morales Cuenca' } },
    { id: '4', title: 'Capacitación Colegio Francés', start: '2026-02-20T09:30:00', backgroundColor: '#fef3c7', textColor: '#b45309', borderColor: '#fde68a', extendedProps: { status: 'PROGRAMADA', sucursal: 'Polanco III', profesional: 'Angel David Morales Cuenca' } },
    { id: '5', title: 'Reunión Coordinación', start: '2026-02-17T11:00:00', backgroundColor: '#fef3c7', textColor: '#b45309', borderColor: '#fde68a', extendedProps: { status: 'PROGRAMADA', sucursal: 'Majestic Central', profesional: 'Angel David Morales Cuenca' } }
]);

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: esLocale,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    height: '750px',
    selectable: true,
    events: sesiones.value,
    eventClick: (info) => {
        selectedEvent.value = {
            id: info.event.id,
            title: info.event.title,
            start: info.event.start,
            extendedProps: info.event.extendedProps
        };
    },
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: 'short' },
    dayMaxEvents: true
}));

const formatDateLong = (date) => new Date(date).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
const formatTime    = (date) => new Date(date).toLocaleTimeString('es-ES', { hour: 'numeric', minute: '2-digit', hour12: true });

const getStatusBg = (status) => {
    switch (status?.toUpperCase()) {
        case 'ATENDIDA':  return 'bg-gradient-to-br from-green-600 to-green-700 shadow-lg shadow-green-100';
        case 'CANCELADA': return 'bg-gradient-to-br from-red-600 to-red-700 shadow-lg shadow-red-100';
        default:          return 'bg-gradient-to-br from-amber-500 to-amber-600 shadow-lg shadow-amber-100';
    }
};
</script>

<style scoped>

/* =============================================
   LAYOUT PRINCIPAL: 30% | 70%
   ============================================= */
.main-layout {
    display: flex;
    gap: 32px;
    align-items: flex-start;
}

/* Panel de detalles: exactamente 30% */
.details-panel {
    width: 30%;
    flex-shrink: 0;       /* No se encoge */
    position: sticky;
    top: 112px;           /* sticky respecto al scroll */
}

/* Calendario: ocupa el resto (70%) */
.calendar-panel {
    flex: 1;              /* flex: 1 = toma todo el espacio restante */
    min-width: 0;         /* evita desbordamiento */
}

/* En móvil/tablet: apilados en columna */
@media (max-width: 1024px) {
    .main-layout {
        flex-direction: column;
    }
    .details-panel,
    .calendar-panel {
        width: 100%;
    }
    .details-panel {
        position: static;
        order: 2;
    }
    .calendar-panel {
        order: 1;
    }
}

/* =============================================
   ESTILOS GLOBALES
   ============================================= */
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }

.label-mini {
    font-size: 9px;
    font-weight: 900;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    display: block;
    margin-bottom: 6px;
}

.btn-primary {
    background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%);
    color: white;
    border-radius: 16px;
    font-weight: 900;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    transition: 0.2s;
    border: none;
    cursor: pointer;
    padding: 12px;
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(169, 51, 57, 0.2); }

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
    transition: 0.2s;
}

/* =============================================
   FULLCALENDAR — PERSONALIZACIÓN
   ============================================= */
:deep(.fc) {
    --fc-border-color: #f1f5f9;
    --fc-button-bg-color: #ffffff;
    --fc-button-border-color: #e2e8f0;
    --fc-button-text-color: #1e293b;
    --fc-button-hover-bg-color: #f8fafc;
    --fc-button-active-bg-color: #1e293b;
    --fc-button-active-border-color: #1e293b;
    font-family: 'Inter', system-ui, sans-serif;
}

:deep(.fc-header-toolbar)  { margin-bottom: 2rem; padding: 0 8px; }
:deep(.fc-toolbar-title)   { font-size: 1.25rem; font-weight: 900; color: #1e293b; text-transform: uppercase; letter-spacing: -0.03em; }
:deep(.fc-button)          { border-radius: 12px; font-weight: 700; font-size: 0.75rem; padding: 6px 14px; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
:deep(.fc-button-primary:not(:disabled).fc-button-active) { background: #1e293b; border-color: #1e293b; color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

:deep(.fc-daygrid-day-number)    { font-size: 10px; font-weight: 900; color: #94a3b8; padding: 10px 12px; text-decoration: none !important; text-transform: uppercase; }
:deep(.fc-col-header-cell-cushion) { font-size: 10px; font-weight: 900; color: #cbd5e1; text-transform: uppercase; padding: 12px 0; text-decoration: none !important; letter-spacing: 0.1em; }

:deep(.fc-event) {
    border-radius: 10px;
    border: none;
    padding: 2px 8px;
    cursor: pointer;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    box-shadow: 0 2px 6px -2px rgba(0,0,0,0.1);
    transition: transform 0.15s ease;
}
:deep(.fc-event:hover) { transform: scale(1.02); z-index: 50; }

/* Animaciones */
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.slide-left-enter-active, .slide-left-leave-active { transition: all 0.3s ease; }
.slide-left-enter-from { opacity: 0; transform: translateX(-30px); }
.slide-left-leave-to   { opacity: 0; transform: translateX(-30px); }
</style>