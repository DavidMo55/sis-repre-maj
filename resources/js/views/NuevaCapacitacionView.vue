<template>
    <div class="content-wrapper p-2 md:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto">
            
            <!-- Encabezado -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 animate-fade-in">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight leading-tight uppercase">
                        Agendar Capacitación
                    </h1>
                    <p class="text-xs md:text-sm text-red-600 font-bold uppercase tracking-widest mt-1 italic">
                        Programación técnica de sesiones y asignación de capacitadores.
                    </p>
                </div>
                <button @click="router.push('/capacitaciones')" class="btn-secondary-majestic !px-6 !py-3">
                    <i class="fas fa-arrow-left mr-2"></i> VOLVER AL LISTADO
                </button>
            </header>

            <form @submit.prevent="handleSubmit" class="space-y-8 animate-fade-in">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <!-- COLUMNA IZQUIERDA: DATOS DE LA SESIÓN -->
                    <div class="lg:col-span-7 space-y-8">
                        
                        <!-- 1. SELECCIÓN DE INSTITUCIÓN -->
                        <div class="form-section shadow-premium border-t-4 border-t-black !overflow-visible">
                            <div class="section-title">
                                <i class="fas fa-school text-red-700"></i> 1. Selección de Institución
                            </div>
                            <div class="form-group relative">
                                <label class="label-style">Buscar Plantel / Distribuidor *</label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        class="form-input pl-11 font-bold uppercase"  
                                        placeholder="ESCRIBA EL NOMBRE DEL PLANTEL..." 
                                        v-model="sessionForm.clientName"
                                        @input="searchClients"
                                        autocomplete="off"
                                        required
                                    >
                                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                                </div>
                                <ul v-if="clientSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                    <li v-for="client in clientSuggestions" :key="client.id" @click="selectClient(client)" class="p-4 border-b last:border-0 hover:bg-red-50 cursor-pointer transition-colors">
                                        <div class="text-xs font-black text-black uppercase">{{ client.name }}</div>
                                        <div class="text-[9px] text-slate-400 uppercase font-black tracking-widest mt-1">{{ client.direccion }}</div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- 2. AGENDA Y HORARIO -->
                        <div class="form-section shadow-premium border-t-4 border-t-red-700">
                            <div class="section-title">
                                <i class="fas fa-calendar-alt text-red-700"></i> 2. Agenda y Horario
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="label-style">Fecha Programada *</label>
                                    <input v-model="sessionForm.date" type="date" class="form-input font-bold" required>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="label-style">Inicio *</label>
                                        <input v-model="sessionForm.timeStart" type="time" class="form-input font-bold" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="label-style">Fin *</label>
                                        <input v-model="sessionForm.timeEnd" type="time" class="form-input font-bold" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 p-4 bg-blue-50 rounded-2xl border border-blue-100 flex items-center gap-4">
                                <i class="fas fa-info-circle text-blue-500"></i>
                                <p class="text-[10px] font-bold text-blue-700 uppercase tracking-tight">
                                    El sistema validará la disponibilidad del capacitador automáticamente al seleccionar el horario.
                                </p>
                            </div>
                        </div>

                        <!-- 3. OBJETIVO Y NOTAS -->
                        <div class="form-section shadow-premium border-t-4 border-t-slate-300">
                            <div class="section-title">
                                <i class="fas fa-align-left text-slate-400"></i> 3. Detalles Académicos
                            </div>
                            <div class="form-group">
                                <label class="label-style">Objetivo de la capacitación *</label>
                                <textarea v-model="sessionForm.objective" rows="4" class="form-input font-medium uppercase text-xs" placeholder="DESCRIBA LOS TEMAS A TRATAR..." required minlength="10"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ COLUMNA DERECHA: SECCIÓN 4 MEJORADA -->
                    <div class="lg:col-span-5">
                        <div class="form-section shadow-premium border-t-4 border-t-red-700 bg-white sticky top-28">
                            
                            <div class="section-title">
                                <i class="fas fa-user-tie text-red-700"></i> 4. Asignar Capacitador
                            </div>

                            <p class="trainer-section-hint">
                                Selecciona un capacitador disponible para el horario elegido
                            </p>

                            <!-- TARJETAS TIPO RADIO BUTTON -->
                            <div class="trainers-list">
                                <label
                                    v-for="trainer in trainers"
                                    :key="trainer.id"
                                    class="trainer-radio-card"
                                    :class="[
                                        getCardStateClass(trainer),
                                        { 'is-selected': sessionForm.trainerId === trainer.id }
                                    ]"
                                >
                                    <!-- Input radio oculto — accesibilidad -->
                                    <input
                                        type="radio"
                                        name="trainer"
                                        :value="trainer.id"
                                        :disabled="trainer.status !== 'available'"
                                        v-model="sessionForm.trainerId"
                                        class="sr-only"
                                    />

                                    <!-- Avatar con inicial -->
                                    <div class="trainer-avatar" :class="getAvatarClass(trainer)">
                                        {{ trainer.name.charAt(0) }}
                                    </div>

                                    <!-- Info -->
                                    <div class="trainer-info">
                                        <span class="trainer-name">{{ trainer.name }}</span>
                                        <span class="trainer-specialty">{{ trainer.specialty }}</span>
                                        <span v-if="trainer.status === 'busy'" class="trainer-conflict">
                                            <i class="fas fa-lock text-[7px]"></i> Ocupado en: PED-0921
                                        </span>
                                    </div>

                                    <!-- Badge de estado -->
                                    <div class="trainer-right">
                                        <span class="trainer-badge" :class="getBadgeClass(trainer.status)">
                                            <i class="fas" :class="getBadgeIcon(trainer.status)"></i>
                                            {{ getStatusLabel(trainer.status) }}
                                        </span>
                                    </div>

                                    <!-- Indicador de selección (radio visual) -->
                                    <div class="radio-indicator" :class="{ 'radio-checked': sessionForm.trainerId === trainer.id }">
                                        <div class="radio-dot"></div>
                                    </div>
                                </label>
                            </div>

                            <!-- Resumen del seleccionado -->
                            <Transition name="fade-slide">
                                <div v-if="selectedTrainer" class="selected-summary">
                                    <div class="selected-summary-inner">
                                        <i class="fas fa-check-circle text-green-500 text-sm"></i>
                                        <div>
                                            <p class="selected-summary-label">Capacitador asignado</p>
                                            <p class="selected-summary-name">{{ selectedTrainer.name }}</p>
                                        </div>
                                    </div>
                                </div>
                            </Transition>

                            <!-- CTA -->
                            <div class="mt-6 pt-6 border-t border-slate-50">
                                <button
                                    type="submit"
                                    class="btn-primary-majestic w-full py-5 text-sm font-black tracking-[0.2em] shadow-2xl active:scale-95 transition-all"
                                    :disabled="loading || !sessionForm.trainerId"
                                    :class="{ 'opacity-50 cursor-not-allowed': !sessionForm.trainerId }"
                                >
                                    <i class="fas" :class="loading ? 'fa-spinner fa-spin mr-2' : 'fa-paper-plane mr-2'"></i>
                                    CONFIRMAR Y AGENDAR
                                </button>
                                <p v-if="!sessionForm.trainerId" class="text-center text-[9px] font-black text-red-400 uppercase mt-3 tracking-wider">
                                    * Selecciona un capacitador disponible
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- MODAL DE ÉXITO -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="showSuccess" class="modal-overlay-wrapper">
                    <div class="modal-content-majestic animate-scale-in">
                        <div class="success-icon-majestic"><i class="fas fa-check"></i></div>
                        <h2 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tighter">¡Sesión Agendada!</h2>
                        <p class="text-sm text-slate-500 mb-8 font-medium px-4">La capacitación ha sido registrada y el capacitador ha sido notificado.</p>
                        <button @click="router.push('/capacitaciones')" class="btn-primary-majestic w-full !bg-black">Continuar</button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading  = ref(false);
const showSuccess = ref(false);

const sessionForm = reactive({
    clientId:   null,
    clientName: '',
    date:       '',
    timeStart:  '',
    timeEnd:    '',
    trainerId:  null,
    objective:  ''
});

const clientSuggestions = ref([]);
const trainers = ref([
    { id: 1, name: 'Angel David Morales',   specialty: 'Soporte Técnico / Digital',    status: 'available'    },
    { id: 2, name: 'Diana Carolina Méndez', specialty: 'Pedagogía Infantil',            status: 'busy'         },
    { id: 3, name: 'Roberto J. Esquivel',   specialty: 'Capacitación Editorial',        status: 'off-schedule' },
    { id: 4, name: 'Ximena Cervantes',      specialty: 'Especialista en Licencias',     status: 'available'    },
]);

const selectedTrainer = computed(() =>
    trainers.value.find(t => t.id === sessionForm.trainerId) || null
);

const searchClients = () => {
    if (sessionForm.clientName.length > 2) {
        clientSuggestions.value = [
            { id: 1, name: 'COLEGIO MÉXICO AMERICANO',      direccion: 'CALZADA DE LAS LOMAS #123' },
            { id: 2, name: 'INSTITUTO PEDAGÓGICO CENTRAL',  direccion: 'AV. JUÁREZ #88' }
        ];
    } else {
        clientSuggestions.value = [];
    }
};

const selectClient = (client) => {
    sessionForm.clientId   = client.id;
    sessionForm.clientName = client.name;
    clientSuggestions.value = [];
};

/* ── Clases dinámicas ── */
const getCardStateClass = (trainer) => {
    if (trainer.status !== 'available') return 'trainer-disabled';
    return 'trainer-available';
};

const getAvatarClass = (trainer) => {
    if (trainer.status === 'available') return 'avatar-available';
    if (trainer.status === 'busy')      return 'avatar-busy';
    return 'avatar-off';
};

const getBadgeClass = (status) => {
    if (status === 'available')    return 'badge-available';
    if (status === 'busy')         return 'badge-busy';
    return 'badge-off';
};

const getBadgeIcon = (status) => {
    if (status === 'available')    return 'fa-circle-check';
    if (status === 'busy')         return 'fa-circle-xmark';
    return 'fa-clock';
};

const getStatusLabel = (status) => {
    const labels = { available: 'DISPONIBLE', busy: 'EN SESIÓN', 'off-schedule': 'FUERA DE HORARIO' };
    return labels[status];
};

const handleSubmit = () => {
    loading.value = true;
    setTimeout(() => { loading.value = false; showSuccess.value = true; }, 1500);
};
</script>

<style scoped>

/* =============================================
   SECCIÓN 4 — TARJETAS RADIO BUTTON
   ============================================= */

.trainer-section-hint {
    font-size: 9px;
    font-weight: 900;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    margin-bottom: 16px;
}

.trainers-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Tarjeta base */
.trainer-radio-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 18px;
    border: 2px solid #f1f5f9;
    background: white;
    transition: all 0.2s ease;
    /* El padding-right deja espacio para el radio indicator */
    padding-right: 48px;
}

/* Disponible — interactivo */
.trainer-available {
    cursor: pointer;
}
.trainer-available:hover {
    border-color: #fda4af;
    background: #fff8f8;
    box-shadow: 0 4px 16px -4px rgba(220, 38, 38, 0.1);
    transform: translateY(-1px);
}

/* Seleccionada */
.trainer-radio-card.is-selected {
    border-color: #1e293b !important;
    background: #f8fafc !important;
    box-shadow: 0 6px 24px -6px rgba(30, 41, 59, 0.18) !important;
    transform: translateY(-2px) !important;
}

/* Deshabilitada */
.trainer-disabled {
    cursor: not-allowed;
    opacity: 0.55;
    background: #fafafa;
}

/* ── Avatar ── */
.trainer-avatar {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 900;
    flex-shrink: 0;
    letter-spacing: -0.02em;
    transition: all 0.2s;
}
.avatar-available {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #15803d;
    border: 2px solid #bbf7d0;
}
.avatar-busy {
    background: #fee2e2;
    color: #b91c1c;
    border: 2px solid #fecaca;
}
.avatar-off {
    background: #f1f5f9;
    color: #94a3b8;
    border: 2px solid #e2e8f0;
}
.is-selected .trainer-avatar {
    background: linear-gradient(135deg, #1e293b, #334155) !important;
    color: white !important;
    border-color: #1e293b !important;
}

/* ── Info del trainer ── */
.trainer-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.trainer-name {
    font-size: 11px;
    font-weight: 900;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.trainer-disabled .trainer-name { color: #94a3b8; }

.trainer-specialty {
    font-size: 9px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trainer-conflict {
    font-size: 8px;
    font-weight: 900;
    color: #f87171;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ── Lado derecho: badge ── */
.trainer-right {
    flex-shrink: 0;
}

.trainer-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 8px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1.5px solid transparent;
    white-space: nowrap;
}
.badge-available {
    background: #f0fdf4;
    color: #16a34a;
    border-color: #bbf7d0;
}
.badge-busy {
    background: #fff1f2;
    color: #e11d48;
    border-color: #fecdd3;
}
.badge-off {
    background: #f8fafc;
    color: #94a3b8;
    border-color: #e2e8f0;
}

/* ── Radio indicator (círculo a la derecha) ── */
.radio-indicator {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
}
.trainer-available:hover .radio-indicator {
    border-color: #fda4af;
}
.radio-checked {
    background: #1e293b;
    border-color: #1e293b;
    box-shadow: 0 0 0 3px rgba(30,41,59,0.12);
}
.radio-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: transparent;
    transition: all 0.15s ease;
}
.radio-checked .radio-dot {
    background: white;
    transform: scale(1);
}

/* ── Resumen del seleccionado ── */
.selected-summary {
    margin-top: 14px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1.5px solid #bbf7d0;
    border-radius: 14px;
}
.selected-summary-inner {
    display: flex;
    align-items: center;
    gap: 10px;
}
.selected-summary-label {
    font-size: 8px;
    font-weight: 900;
    color: #16a34a;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}
.selected-summary-name {
    font-size: 11px;
    font-weight: 900;
    color: #14532d;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

/* Transición del resumen */
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.25s ease; }
.fade-slide-enter-from { opacity: 0; transform: translateY(-6px); }
.fade-slide-leave-to   { opacity: 0; transform: translateY(-6px); }

/* =============================================
   ESTILOS GENERALES (sin cambios)
   ============================================= */
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.form-section { background: white; padding: 35px; border-radius: 2.5rem; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #1e293b; margin-bottom: 25px; border-bottom: 2px solid #f8fafc; padding-bottom: 15px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 2px; }

.label-style { display: block; font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; margin-left: 4px; }
.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #f1f5f9; font-weight: 700; color: #334155; background: #fafbfc; transition: 0.2s; font-size: 0.9rem; }
.form-input:focus { border-color: #a93339; background: white; outline: none; }

.btn-primary-majestic {
    background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%);
    color: white; border-radius: 20px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em;
    border: none; cursor: pointer;
}
.btn-primary-majestic:disabled { filter: grayscale(0.3); }

.btn-secondary-majestic {
    background: white; border: 2px solid #f1f5f9; color: #64748b;
    border-radius: 12px; font-weight: 900; font-size: 10px;
    text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer; transition: 0.2s;
}
.btn-secondary-majestic:hover { background: #f8fafc; }

.autocomplete-list { position: absolute; z-index: 1000; width: 100%; background: white; border-radius: 20px; max-height: 250px; overflow-y: auto; list-style: none; padding: 8px; margin-top: 8px; }

/* MODAL */
.modal-overlay-wrapper { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; background: rgba(15,23,42,0.85); backdrop-filter: blur(8px); }
.modal-content-majestic { background: white; padding: 50px; border-radius: 3rem; text-align: center; width: 90%; max-width: 420px; border: 1px solid #f1f5f9; }
.success-icon-majestic { width: 80px; height: 80px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 30px; }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* sr-only — accesibilidad */
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
</style>