<template>
    <div class="content-wrapper p-2 md:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto">
            
            <!-- Encabezado -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 animate-fade-in">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight leading-tight uppercase">
                        Modificar Capacitación
                    </h1>
                    <p class="text-xs md:text-sm text-red-600 font-bold uppercase tracking-widest mt-1 italic">
                        Edición de expediente ID: #CP-{{ route.params.id || '000' }}
                    </p>
                </div>
                <button @click="router.push('/capacitaciones')" class="btn-secondary-majestic !px-6 !py-3">
                    <i class="fas fa-arrow-left mr-2"></i> CANCELAR Y VOLVER
                </button>
            </header>

            <div v-if="loadingInitial" class="py-20 text-center animate-pulse">
                <i class="fas fa-circle-notch fa-spin text-5xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Cargando expediente técnico...</p>
            </div>

            <form v-else @submit.prevent="handleSubmit" class="space-y-8 animate-fade-in">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <!-- COLUMNA IZQUIERDA: DATOS DEL EXPEDIENTE -->
                    <div class="lg:col-span-7 space-y-8">
                        
                        <!-- 1. IDENTIDAD DEL PLANTEL (Lectura preferente) -->
                        <div class="form-section shadow-premium border-t-4 border-t-black !overflow-visible">
                            <div class="section-title">
                                <i class="fas fa-school text-red-700"></i> 1. Institución Vinculada
                            </div>
                            <div class="form-group relative">
                                <label class="label-style">Plantel / Distribuidor</label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        class="form-input pl-11 font-bold uppercase bg-slate-50 cursor-not-allowed"  
                                        v-model="sessionForm.clientName"
                                        readonly
                                    >
                                    <i class="fas fa-university absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                </div>
                                <p class="text-[8px] text-slate-400 mt-2 italic">* La institución no puede cambiarse una vez agendada. De ser un error, cancele la sesión y genere una nueva.</p>
                            </div>
                        </div>

                        <!-- 2. RE-AGENDAR HORARIO -->
                        <div class="form-section shadow-premium border-t-4 border-t-red-700">
                            <div class="section-title">
                                <i class="fas fa-calendar-alt text-red-700"></i> 2. Ajuste de Horario
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="label-style">Nueva Fecha *</label>
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
                        </div>

                        <!-- 3. OBJETIVO Y BITÁCORA DE CAMBIO -->
                        <div class="form-section shadow-premium border-t-4 border-t-slate-300">
                            <div class="section-title">
                                <i class="fas fa-align-left text-slate-400"></i> 3. Detalles y Auditoría
                            </div>
                            <div class="space-y-6">
                                <div class="form-group">
                                    <label class="label-style">Objetivo de la capacitación *</label>
                                    <textarea v-model="sessionForm.objective" rows="3" class="form-input font-medium uppercase text-xs" required></textarea>
                                </div>

                                <!-- CAMPO EXTRA: MOTIVO DE MODIFICACIÓN -->
                                <div class="form-group p-6 bg-red-50 rounded-[2rem] border-2 border-red-100 shadow-inner">
                                    <label class="label-style !text-red-800">Motivo del Ajuste (Log de Auditoría) *</label>
                                    <textarea 
                                        v-model="sessionForm.modificationReason" 
                                        rows="3" 
                                        class="form-input border-red-200 font-bold uppercase text-xs" 
                                        placeholder="EXPLIQUE BREVEMENTE POR QUÉ SE MODIFICA ESTA CITA..."
                                        required 
                                        minlength="10"
                                    ></textarea>
                                    <p class="text-[8px] text-red-400 mt-2 italic font-bold">Esta justificación quedará registrada en el historial técnico del plantel.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: RE-ASIGNAR CAPACITADOR -->
                    <div class="lg:col-span-5">
                        <div class="form-section shadow-premium border-t-4 border-t-red-700 bg-white sticky top-28">
                            <div class="section-title">
                                <i class="fas fa-user-tie text-red-700"></i> 4. Capacitador Asignado
                            </div>
                            
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Verificar disponibilidad para el nuevo horario:</p>

                            <div class="space-y-4">
                                <div v-for="trainer in trainers" :key="trainer.id" 
                                    @click="selectTrainer(trainer)"
                                    class="trainer-card p-5 rounded-[2rem] border-2 transition-all flex items-center justify-between group"
                                    :class="getTrainerCardClass(trainer)">
                                    
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-sm transition-all"
                                            :class="trainer.status === 'available' ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-400'">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black uppercase tracking-tight" :class="trainer.status === 'available' ? 'text-slate-800' : 'text-slate-400'">
                                                {{ trainer.name }}
                                            </h4>
                                            <p class="text-[9px] font-bold uppercase mt-1" :class="trainer.status === 'available' ? 'text-slate-400' : 'text-slate-300'">
                                                {{ trainer.specialty }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <span class="status-badge-majestic" :class="trainer.status">
                                            {{ getStatusLabel(trainer.status) }}
                                        </span>
                                    </div>

                                    <!-- Indicador de selección -->
                                    <div v-if="sessionForm.trainerId === trainer.id" class="absolute -right-2 -top-2 w-8 h-8 bg-black text-white rounded-full flex items-center justify-center shadow-lg animate-scale-in">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-10 pt-8 border-t border-slate-50">
                                <button type="submit" class="btn-primary-majestic w-full py-5 text-sm font-black tracking-[0.2em] shadow-2xl active:scale-95 transition-all" 
                                    :disabled="loading || !sessionForm.trainerId || !sessionForm.modificationReason">
                                    <i class="fas" :class="loading ? 'fa-spinner fa-spin mr-2' : 'fa-save mr-2'"></i>
                                    ACTUALIZAR Y NOTIFICAR
                                </button>
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
                        <div class="success-icon-majestic"><i class="fas fa-check-double"></i></div>
                        <h2 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tighter">¡Cambios Guardados!</h2>
                        <p class="text-sm text-slate-500 mb-8 font-medium px-4">El expediente ha sido actualizado con el motivo proporcionado y se ha notificado al capacitador.</p>
                        <button @click="router.push('/capacitaciones')" class="btn-primary-majestic w-full !bg-black">Finalizar Edición</button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();
const loading = ref(false);
const loadingInitial = ref(true);
const showSuccess = ref(false);

const sessionForm = reactive({
    clientId: null,
    clientName: '',
    date: '',
    timeStart: '',
    timeEnd: '',
    trainerId: null,
    objective: '',
    modificationReason: '' // Campo extra para auditoría
});

// Catálogos simulados
const trainers = ref([
    { id: 1, name: 'Angel David Morales', specialty: 'Soporte Técnico / Digital', status: 'available' },
    { id: 2, name: 'Diana Carolina Méndez', specialty: 'Pedagogía Infantil', status: 'available' },
    { id: 3, name: 'Roberto J. Esquivel', specialty: 'Capacitación Editorial', status: 'off-schedule' },
    { id: 4, name: 'Ximena Cervantes', specialty: 'Especialista en Licencias', status: 'available' }
]);

const fetchSessionData = () => {
    // Simulación de hidratación de datos
    setTimeout(() => {
        sessionForm.clientId = 1;
        sessionForm.clientName = 'COLEGIO MÉXICO AMERICANO';
        sessionForm.date = '2026-02-25';
        sessionForm.timeStart = '10:00';
        sessionForm.timeEnd = '11:30';
        sessionForm.trainerId = 1; // Angel David
        sessionForm.objective = 'CAPACITACIÓN SOBRE EL USO DE MAJESTIC DIGITAL APP PARA DOCENTES DE INGLÉS.';
        
        loadingInitial.value = false;
    }, 1000);
};

const selectTrainer = (trainer) => {
    if (trainer.status === 'available') {
        sessionForm.trainerId = trainer.id;
    }
};

const getTrainerCardClass = (trainer) => {
    if (sessionForm.trainerId === trainer.id) return 'border-black bg-slate-50 scale-[1.02] shadow-md relative';
    if (trainer.status === 'available') return 'border-slate-100 bg-white cursor-pointer hover:border-red-200 hover:shadow-sm';
    return 'border-slate-50 bg-slate-50/50 opacity-60 cursor-not-allowed';
};

const getStatusLabel = (status) => {
    const labels = {
        'available': 'DISPONIBLE',
        'busy': 'EN SESIÓN',
        'off-schedule': 'FUERA DE HORARIO'
    };
    return labels[status];
};

const handleSubmit = () => {
    loading.value = true;
    setTimeout(() => {
        loading.value = false;
        showSuccess.value = true;
    }, 1500);
};

onMounted(() => {
    fetchSessionData();
});

</script>

<style scoped>
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.form-section { background: white; padding: 35px; border-radius: 2.5rem; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #1e293b; margin-bottom: 25px; border-bottom: 2px solid #f8fafc; padding-bottom: 15px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 2px; }

.label-style { @apply text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1; }
.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #f1f5f9; font-weight: 700; color: #334155; background: #fafbfc; transition: 0.2s; font-size: 0.9rem; }
.form-input:focus { border-color: #a93339; background: white; outline: none; }

.btn-primary-majestic {
    background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%);
    color: white; border-radius: 20px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em;
}

.btn-secondary-majestic {
    @apply bg-white border-2 border-slate-100 text-slate-500 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-50 transition-all;
}

/* BADGES DE ESTADO */
.status-badge-majestic {
    @apply text-[8px] font-black px-2.5 py-1 rounded-full border tracking-widest uppercase;
}
.status-badge-majestic.available { @apply bg-green-50 text-green-600 border-green-100; }
.status-badge-majestic.busy { @apply bg-red-50 text-red-600 border-red-100; }
.status-badge-majestic.off-schedule { @apply bg-slate-100 text-slate-400 border-slate-200; }

/* MODAL */
.modal-overlay-wrapper { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; background: rgba(15,23,42,0.85); backdrop-filter: blur(8px); }
.modal-content-majestic { background: white; padding: 50px; border-radius: 3rem; text-align: center; width: 90%; max-width: 420px; border: 1px solid #f1f5f9; }
.success-icon-majestic { width: 80px; height: 80px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 30px; border: 4px solid white; }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>