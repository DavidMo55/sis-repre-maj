<template>
    <div class="content-wrapper p-2 md:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto">

            <!-- Encabezado Principal -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 animate-fade-in">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight leading-tight uppercase">
                        Capacitaciones y agenda
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium mt-1 uppercase tracking-widest italic">
                        Administra sesiones programadas, confirma asistencia y gestiona el calendario técnico.
                    </p>
                </div>
                <div class="flex gap-3 w-full sm:w-auto">
                    <button @click="router.push({ name: 'CapacitacionesCalendario' })" class="btn-secondary !border-2 !rounded-2xl flex items-center gap-2 uppercase font-black text-[10px] px-6 transition-all hover:bg-slate-50">
                        <i class="fas fa-calendar-alt"></i> VER CALENDARIO
                    </button>
                    <button @click="router.push({ name: 'NuevaCapacitacion' })" class="btn-primary flex items-center gap-2 shadow-lg px-8 py-3 transition-all active:scale-95">
                        <i class="fas fa-plus-circle"></i> NUEVA SESIÓN
                    </button>
                </div>
            </header>

            <!-- SECCIÓN: SESIONES DE HOY -->
            <section class="mb-8 animate-fade-in">
                <div class="bg-white p-6 rounded-[2.5rem] shadow-premium border border-slate-100">
                    <div class="flex items-center justify-between mb-4 px-2">
                        <h2 class="text-sm font-black text-slate-700 uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-bolt text-amber-500"></i> Prioridades de hoy
                        </h2>
                        <span class="text-[10px] font-black text-slate-400 uppercase bg-slate-50 px-3 py-1 rounded-full border border-slate-100 tracking-tighter">
                            {{ hoySesiones.length }} ACTIVIDADES
                        </span>
                    </div>

                    <div v-if="hoySesiones.length > 0" class="today-grid">
                        <div v-for="sesion in hoySesiones" :key="sesion.id"
                            class="today-card group"
                            @click="router.push({ name: 'DetalleIDCapacitacionesView', params: { id: sesion.id } })">
                            <div class="today-time-block group-hover:border-red-100">
                                <div class="today-hour">{{ sesion.hora.split(' ')[0] }}</div>
                                <div class="today-ampm">{{ sesion.hora.split(' ')[1] }}</div>
                            </div>
                            <div class="today-info">
                                <h4 class="today-title group-hover:text-red-700">{{ sesion.title }}</h4>
                                <p class="today-sucursal">
                                    <i class="fas fa-map-marker-alt"></i>{{ sesion.extendedProps.sucursal }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-10 text-slate-300 text-[10px] font-black uppercase tracking-widest italic border-2 border-dashed border-slate-50 rounded-[1.5rem]">
                        No hay actividades programadas para hoy
                    </div>
                </div>
            </section>
<br/><br/><br/>
            <!-- SECCIÓN: FILTROS TÉCNICOS -->
            <div class="filter-section bkk shadow-premium border border-slate-100 rounded-[2rem] p-6 mb-8 animate-fade-in">
                <div class="section-title mb-6 font-black text-slate-700 uppercase tracking-widest text-[10px] flex items-center gap-2">
                    <i class="fas fa-filter text-red-600"></i> Filtros de búsqueda técnica
                </div>

                <div class="filter-grid">
                    <div class="form-group md:col-span-2">
                        <label class="label-header">Buscar por Plantel o Profesional:</label>
                        <div class="relative">
                            <i class="fas fa-search search-icon text-slate-400"></i>
                            <input v-model="filters.search" type="text" class="form-input pl-10 w-full" placeholder="Nombre de escuela o representante...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label-header">Estado de Sesión:</label>
                        <select v-model="filters.status" class="form-input w-full font-bold text-xs uppercase">
                            <option value="all">TODOS LOS ESTADOS</option>
                            <option value="PROGRAMADA">PROGRAMADA</option>
                            <option value="ATENDIDA">ATENDIDA</option>
                            <option value="CANCELADA">CANCELADA</option>
                        </select>
                    </div>

                    <div class="form-group flex items-end gap-2">
                        <button @click="refreshEvents" class="btn-primary flex-1 h-[42px] flex items-center justify-center gap-2">
                            <i class="fas fa-sync-alt"></i> Buscar
                        </button>
                        <button v-if="hasFilters" @click="resetFilters" class="btn-secondary h-[42px] px-4 rounded-xl border-2 border-slate-100 text-slate-400 hover:text-red-600 transition-colors">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </div>
                </div>
            </div>
<br/><br/><br/>
            <!-- TABLA TÉCNICA (MODO DETALLADO) -->
            <div class="table-responsive table-shadow-lg border rounded-[2rem] overflow-hidden shadow-sm bg-white animate-fade-in mt-4">
                <table class="min-width-full divide-y divide-gray-200" style="width:100%">
                    <thead class="bg-slate-900 text-white uppercase text-[9px] font-black tracking-[0.15em]">
                        <tr>
                            <th class="px-6 py-5 text-left w-48">Fecha y hora</th>
                            <th class="px-6 py-5 text-left">Institución / Plantel</th>
                            <th class="px-6 py-5 text-left">Representante</th>
                            <th class="px-6 py-5 text-center w-36">Estado</th>
                            <th class="px-6 py-5 w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <tr v-for="sesion in filteredSesiones" :key="sesion.id" class="hover:bg-slate-50/50 transition-colors group">
                            <td class="table-cell">
                                <div class="text-sm font-black text-slate-800 uppercase leading-tight">{{ formatDateLong(sesion.start) }}</div>
                                <div class="text-[10px] text-red-600 font-bold uppercase mt-1 italic flex items-center gap-1.5 tracking-tighter">
                                    <i class="fas fa-clock opacity-50"></i> {{ formatTime(sesion.start) }}
                                </div>
                            </td>
                            <td class="table-cell">
                                <div class="text-[11px] font-black text-slate-800 uppercase leading-tight truncate max-w-[250px]" :title="sesion.title">{{ sesion.title }}</div>
                                <div class="text-[9px] text-slate-400 font-bold uppercase mt-1 tracking-tighter truncate">{{ sesion.extendedProps.sucursal }}</div>
                            </td>
                            <td class="table-cell">
                                <div class="text-[11px] font-bold text-slate-500 uppercase">{{ sesion.extendedProps.profesional || '---' }}</div>
                            </td>
                            <td class="table-cell text-center">
                                <span class="status-badge" :class="getStatusClass(sesion.extendedProps.status)">
                                    <i class="fas fa-circle text-[6px] mr-2"></i> {{ sesion.extendedProps.status }}
                                </span>
                            </td>
                            <td class="table-cell text-right">
                                <button @click="router.push({ name: 'DetalleIDCapacitacionesView', params: { id: sesion.id } })" class="text-red-link flex items-center justify-end gap-1 font-black">
                                    GESTIONAR <i class="fas fa-chevron-right text-[8px] ml-1"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filteredSesiones.length === 0">
                            <td colspan="6" class="px-6 py-20 text-center italic text-slate-300 font-black text-[10px] uppercase tracking-widest">
                                No se encontraron registros con los criterios actuales
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const filters = reactive({
    search: '',
    status: 'all'
});

const sesiones = ref([
    { id: '1', title: 'Diana Carolina Méndez Rojas', start: '2026-02-16T12:21:00', extendedProps: { status: 'PROGRAMADA', sucursal: 'Consultorio Satélite CDMX No.7', profesional: 'Angel David Morales Cuenca' } },
    { id: '2', title: 'Plantel Educativo Morelos', start: '2026-02-15T10:18:00', extendedProps: { status: 'CANCELADA', sucursal: 'Centro Cuernavaca', profesional: 'Angel David Morales Cuenca' } },
    { id: '3', title: 'Escuela Técnica Superior', start: '2026-02-16T15:00:00', extendedProps: { status: 'ATENDIDA', sucursal: 'CDMX Norte', profesional: 'Angel David Morales Cuenca' } },
    { id: '4', title: 'Colegio Americano de México', start: '2026-02-17T09:30:00', extendedProps: { status: 'PROGRAMADA', sucursal: 'Lomas de Chapultepec', profesional: 'Angel David Morales Cuenca' } },
    { id: '5', title: 'Instituto Tecnológico Central', start: '2026-02-17T11:00:00', extendedProps: { status: 'ATENDIDA', sucursal: 'Puebla Centro', profesional: 'Angel David Morales Cuenca' } },
    { id: '6', title: 'Escuela Primaria Benito Juárez', start: '2026-02-18T14:20:00', extendedProps: { status: 'PROGRAMADA', sucursal: 'Veracruz Norte', profesional: 'Angel David Morales Cuenca' } }
]);

const hoySesiones = computed(() => {
    const hoy = new Date().toISOString().split('T')[0];
    return sesiones.value.filter(s => s.start.startsWith(hoy)).map(s => ({
        ...s,
        hora: new Date(s.start).toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit", hour12: true }),
        status: s.extendedProps.status
    }));
});

const filteredSesiones = computed(() => {
    return sesiones.value.filter(s => {
        const matchesSearch = !filters.search ||
            s.title.toLowerCase().includes(filters.search.toLowerCase()) ||
            s.extendedProps.sucursal.toLowerCase().includes(filters.search.toLowerCase()) ||
            (s.extendedProps.profesional && s.extendedProps.profesional.toLowerCase().includes(filters.search.toLowerCase()));
        const matchesStatus = filters.status === 'all' || s.extendedProps.status === filters.status;
        return matchesSearch && matchesStatus;
    }).sort((a, b) => new Date(b.start) - new Date(a.start));
});

const getStatusClass = (status) => {
    switch (status?.toUpperCase()) {
        case 'ATENDIDA':   return 'bg-green-100 text-green-700 border border-green-200';
        case 'PROGRAMADA': return 'bg-yellow-100 text-yellow-700 border border-yellow-200';
        case 'CANCELADA':  return 'bg-red-100 text-red-700 border border-red-200';
        default:           return 'bg-slate-100 text-slate-500';
    }
};

const formatDateLong = (date) => new Date(date).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
const formatTime    = (date) => new Date(date).toLocaleTimeString('es-ES', { hour: 'numeric', minute: '2-digit', hour12: true });

const resetFilters  = () => { filters.search = ''; filters.status = 'all'; };
const refreshEvents = () => console.log("Sincronizando agenda técnica...");

const hasFilters = computed(() => filters.search !== '' || filters.status !== 'all');
</script>

<style scoped>
.today-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.today-card {
    background: #f8fafc;
    border: 1.5px solid #f1f5f9;
    border-radius: 1.25rem;
    padding: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 1px 4px -2px rgba(0,0,0,0.05);
}
.today-card:hover {
    border-color: #fecdd3;
    background: white;
    box-shadow: 0 6px 18px -6px rgba(180,40,60,0.1);
    transform: translateY(-2px);
}

.today-time-block {
    background: white;
    border: 1.5px solid #f1f5f9;
    border-radius: 14px;
    padding: 8px 10px;
    text-align: center;
    flex-shrink: 0;
    min-width: 58px;
    transition: border-color 0.2s;
}
.today-hour {
    font-size: 11px;
    font-weight: 900;
    color: #dc2626;
    text-transform: uppercase;
    line-height: 1;
}
.today-ampm {
    font-size: 8px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    margin-top: 3px;
    line-height: 1;
}

.today-info { flex: 1; min-width: 0; }
.today-title {
    font-size: 10px;
    font-weight: 900;
    color: #1e293b;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color 0.15s;
    letter-spacing: 0.02em;
}
.today-sucursal {
    font-size: 8px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
    opacity: 0.8;
}

.bkk { background-color: #fdfdfd; border: 1px solid #e2e8f0; padding: 25px; border-radius: 2rem; }
.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: flex-end; }

.label-header {
    display: block;
    font-size: 10px;
    font-weight: 900;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 6px;
}

.form-input {
    padding: 10px 14px;
    border-radius: 12px;
    border: 2px solid #f1f5f9;
    font-weight: 700;
    color: #334155;
    background: #fafbfc;
    transition: all 0.2s;
    font-size: 0.85rem;
    outline: none;
}
.form-input:focus { border-color: #e4989c; background: white; }

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%);
    color: white;
    border-radius: 16px;
    font-weight: 900;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
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
    transition: all 0.2s;
}

.table-cell { padding: 14px 16px; font-size: 0.85rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    text-transform: uppercase;
    white-space: nowrap;
}

.text-red-link { color: #b91c1c; font-weight: 800; font-size: 0.75rem; border: none; background: transparent; cursor: pointer; text-transform: uppercase; }

.btn-note { padding: 5px 12px; background: #fafbfc; border: 2px solid #f1f5f9; border-radius: 10px; color: #64748b; font-size: 0.65rem; font-weight: 800; cursor: pointer; }

.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    background-color: white;
}
</style>