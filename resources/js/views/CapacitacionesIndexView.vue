<template>
  <div class="content-wrapper p-8">
    <div class="max-w-7xl mx-auto">
      
      <header class="flex justify-between items-end mb-10">
        <div class="stack-1">
          <h1 class="text-3xl font-black text-slate-800 tracking-tight">Capacitaciones y agenda</h1>
          <p class="text-slate-500 font-medium">
            Administra sesiones programadas, confirma asistencia y gestiona el calendario técnico.
          </p>
        </div>
        <div class="flex gap-3">
          <button @click="router.push('/capacitaciones/crear')" class="btn-primary-agenda">
            Nueva sesión
          </button>
        </div>
      </header>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
        
        <aside class="lg:col-span-1 space-y-6 order-2 lg:order-1">
          
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm">
            <h3 class="text-[10px] font-black uppercase text-slate-400 mb-6 tracking-widest">Búsqueda rápida</h3>
            <div class="space-y-4">
              <div class="form-group-agenda">
                <label>Estado</label>
                <select v-model="statusFilter" class="input-agenda">
                  <option value="all">Todos los estados</option>
                  <option value="PROGRAMADA">Programada</option>
                  <option value="ATENDIDA">Atendida</option>
                  <option value="CANCELADA">Cancelada</option>
                </select>
              </div>
              <button @click="refreshEvents" class="btn-filter-agenda w-full">Actualizar Vista</button>
            </div>
          </div>

          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm">
            <h3 class="text-[10px] font-black uppercase text-slate-400 mb-4 tracking-widest">Sesiones de Hoy</h3>
            <div class="space-y-4">
              <div v-for="sesion in hoySesiones" :key="sesion.id" 
                class="border-l-4 border-slate-800 pl-3 py-1 transition-all hover:bg-slate-50 cursor-pointer rounded-r-lg"
                @click="focusEvent(sesion)">
                <p class="text-[10px] font-black text-slate-400">{{ sesion.hora }}</p>
                <p class="text-xs font-bold text-slate-700 truncate">{{ sesion.title }}</p>
                <span class="status-pill-mini" :class="sesion.status.toLowerCase()">{{ sesion.status }}</span>
              </div>
              <p v-if="hoySesiones.length === 0" class="text-xs text-slate-400 italic text-center py-4">
                No hay sesiones para hoy.
              </p>
            </div>
          </div>
        </aside>

        <main class="lg:col-span-3 order-1 lg:order-2">
          <div class="calendar-card shadow-xl">
            <FullCalendar ref="fullCalendar" :options="calendarOptions" />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

// Componentes de FullCalendar
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';

const router = useRouter();
const fullCalendar = ref(null);
const statusFilter = ref('all');

// Estado de las sesiones (Sincronizado con tus datos previos)
const sesiones = ref([
  { 
    id: '1', 
    title: 'Cap. Angel Morales', 
    start: '2026-01-10T12:21:00', 
    end: '2026-01-10T13:30:00',
    backgroundColor: '#f1f5f9',
    textColor: '#64748b',
    extendedProps: { status: 'PROGRAMADA', sucursal: 'Culiacán Norte' } 
  },
  { 
    id: '2', 
    title: 'Diana Méndez Rojas', 
    start: '2026-01-09T10:18:00', 
    backgroundColor: '#fee2e2',
    textColor: '#ef4444',
    extendedProps: { status: 'CANCELADA', sucursal: 'Consultorio Satélite' } 
  },
  { 
    id: '3', 
    title: 'Atención Técnica', 
    start: '2026-01-10T15:00:00', 
    backgroundColor: '#d1fae5',
    textColor: '#059669',
    extendedProps: { status: 'ATENDIDA', sucursal: 'Centro Histórico' } 
  }
]);

// Configuración del Calendario FullCalendar
const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: esLocale,
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },
  buttonText: {
    today: 'Hoy',
    month: 'Mes',
    week: 'Semana',
    day: 'Día'
  },
  events: statusFilter.value === 'all' 
    ? sesiones.value 
    : sesiones.value.filter(s => s.extendedProps.status === statusFilter.value),
  editable: true,
  selectable: true,
  dayMaxEvents: true,
  height: '700px',
  // Al hacer clic en una fecha vacía
  select: (info) => {
    if(confirm(`¿Deseas programar una nueva sesión para el ${info.startStr}?`)) {
      router.push({ name: 'CapacitacionesCreate', query: { date: info.startStr } });
    }
  },
  // Al hacer clic en un evento
  eventClick: (info) => {
    alert(`Sesión: ${info.event.title}\nEstado: ${info.event.extendedProps.status}\nSucursal: ${info.event.extendedProps.sucursal}`);
  }
}));

// Lógica para la lista "Sesiones de Hoy"
const hoySesiones = computed(() => {
  const hoy = new Date().toISOString().slice(0, 10);
  return sesiones.value
    .filter(s => s.start.startsWith(hoy))
    .map(s => ({
      ...s,
      hora: new Date(s.start).toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit" }),
      status: s.extendedProps.status
    }));
});

const focusEvent = (sesion) => {
  const api = fullCalendar.value.getApi();
  api.gotoDate(sesion.start);
  api.changeView('timeGridDay');
};

const refreshEvents = () => {
  // Aquí iría la llamada axios.get('/api/capacitaciones')
  console.log("Refrescando agenda...");
};

onMounted(refreshEvents);
</script>

<style scoped>
.content-wrapper { background-color: #f8fafc; min-height: 100vh; }

/* CONTENEDOR DEL CALENDARIO */
.calendar-card {
  @apply bg-white border border-slate-200 rounded-[2.5rem] p-8 shadow-sm;
}

/* CUSTOMIZACIÓN DE FULLCALENDAR (PARA QUE SE VEA COMO KLINIA) */
:deep(.fc) {
  --fc-border-color: #f1f5f9;
  --fc-button-bg-color: #ffffff;
  --fc-button-border-color: #e2e8f0;
  --fc-button-text-color: #64748b;
  --fc-button-hover-bg-color: #f8fafc;
  --fc-button-active-bg-color: #1e293b;
  --fc-button-active-border-color: #1e293b;
  --fc-event-resizer-thickness: 10px;
  font-family: 'Inter', system-ui, sans-serif;
}

:deep(.fc-header-toolbar) {
  @apply mb-8 px-2;
}

:deep(.fc-toolbar-title) {
  @apply text-xl font-black text-slate-800 uppercase tracking-tighter;
}

:deep(.fc-button) {
  @apply rounded-xl font-bold text-xs px-4 py-2 transition-all shadow-sm capitalize border;
}

:deep(.fc-button-primary:not(:disabled).fc-button-active) {
  @apply bg-slate-800 border-slate-800 text-white shadow-lg scale-105;
}

:deep(.fc-daygrid-day-number) {
  @apply text-[10px] font-black text-slate-400 p-3 uppercase;
}

:deep(.fc-col-header-cell-cushion) {
  @apply text-[10px] font-black text-slate-300 uppercase py-3 tracking-widest;
}

:deep(.fc-event) {
  @apply rounded-xl border-none p-1 px-2 cursor-pointer shadow-sm font-bold text-[10px];
}

/* BOTONES Y FORMULARIOS */
.btn-primary-agenda { @apply bg-slate-800 text-white px-6 py-2.5 rounded-2xl text-sm font-bold shadow-lg hover:bg-slate-700 transition-all active:scale-95; }
.input-agenda { @apply w-full border border-slate-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-slate-800 transition-all; }
.btn-filter-agenda { @apply bg-slate-100 text-slate-600 py-2 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-800 hover:text-white transition-all; }

.status-pill-mini { @apply text-[8px] font-black px-1.5 py-0.5 rounded-md uppercase tracking-tighter; }
.status-pill-mini.programada { @apply bg-slate-100 text-slate-500; }
.status-pill-mini.atendida { @apply bg-emerald-100 text-emerald-600; }
.status-pill-mini.cancelada { @apply bg-red-100 text-red-500; }

.form-group-agenda label { @apply block text-[10px] font-black uppercase text-slate-400 mb-1 ml-1; }
.stack-1 > * + * { margin-top: 0.25rem; }
</style>