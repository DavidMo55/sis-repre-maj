<template>
    <div class="content-wrapper px-2 sm:px-4">
        <div class="module-page max-w-full overflow-x-hidden">
            <div class="module-header detail-header-flex flex-col sm:flex-row justify-between items-start gap-4 mb-6">
                <div class="header-info w-full sm:w-auto">
                    <h1 v-if="visita" class="text-xl md:text-2xl font-black text-slate-800 break-words line-clamp-2">
                        Bitácora: {{ visita.nombre_plantel || visita.cliente?.name || 'Sin nombre' }}
                    </h1>
                    <h1 v-else-if="loading" class="text-xl font-black">Cargando...</h1>
                    <p class="text-xs md:text-sm text-slate-500 truncate">Resumen técnico de acuerdos y materiales.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary w-full sm:w-auto flex justify-center items-center gap-2 px-6 py-2 rounded-xl text-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>

            <div v-if="loading" class="text-center py-12">
                <i class="fas fa-spinner fa-spin text-3xl text-red-600"></i>
            </div>

            <div v-else-if="visita" class="space-y-6 animate-fade-in">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    
                    <div class="form-section shadow-premium border-t-4 border-t-red-700 w-full">
                        <div class="section-title text-[11px] font-black uppercase mb-4 flex items-center gap-2 text-red-700">
                            <i class="fas fa-school"></i> 1. Plantel
                        </div>
                        <div class="space-y-4">
                            <div class="data-block">
                                <label class="label-mini">Nombre Oficial</label>
                                <p class="text-sm font-black text-slate-800 uppercase leading-tight break-words">{{ visita.nombre_plantel || visita.cliente?.name }}</p>
                            </div>
                            <div class="data-block">
                                <label class="label-mini">Niveles</label>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="n in formatLevels(visita.nivel_educativo_plantel)" :key="n" class="status-badge bg-slate-100 text-slate-600 text-[9px]">
                                        {{ n }}
                                    </span>
                                </div>
                            </div>
                            <div class="data-block">
                                <label class="label-mini">Ubicación</label>
                                <p class="text-[11px] text-slate-500 italic break-words leading-snug">
                                    {{ visita.direccion_plantel || 'Sin dirección' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="form-section shadow-premium border-t-4 border-t-slate-800 w-full">
                        <div class="section-title text-[11px] font-black uppercase mb-4 flex items-center gap-2 text-slate-800">
                            <i class="fas fa-user-tie"></i> 2. Entrevista
                        </div>
                        <div class="space-y-4">
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <label class="label-mini">Atendido por</label>
                                <p class="text-sm font-black text-red-800 uppercase break-words">{{ visita.persona_entrevistada || 'N/A' }}</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ visita.cargo || 'Responsable' }}</p>
                            </div>
                            <div class="flex justify-between items-end border-t border-slate-100 pt-3">
                                <div>
                                    <label class="label-mini">Fecha</label>
                                    <p class="text-xs font-black text-slate-700">{{ formatDate(visita.fecha) }}</p>
                                </div>
                                <div class="text-right">
                                    <label class="label-mini">Resultado</label>
                                    <span :class="getOutcomeClass(visita.resultado_visita)" class="status-badge text-[9px]">
                                        {{ (visita.resultado_visita || 'seguimiento').toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section shadow-premium border-t-4 border-t-red-800 bg-slate-50/30 w-full">
                        <div class="section-title text-[11px] font-black uppercase mb-4 flex items-center gap-2 text-red-800">
                            <i class="fas fa-calendar-alt"></i> 3. Seguimiento
                        </div>
                        <div class="space-y-4">
                            <div class="p-4 rounded-2xl border-2 border-slate-100 bg-white shadow-sm">
                                <label class="label-mini">Próximo Compromiso</label>
                                <div v-if="visita.proxima_visita_estimada">
                                    <p class="text-xl font-black text-green-700 tracking-tighter">{{ formatDate(visita.proxima_visita_estimada) }}</p>
                                </div>
                                <p v-else class="text-slate-400 italic text-[10px] font-bold uppercase">Sin fecha agendada</p>
                            </div>
                            <button v-if="visita.resultado_visita !== 'rechazo'" @click="router.push({ name: 'Visitas' })" 
                                class="btn-primary w-full py-3.5 rounded-2xl font-black uppercase tracking-widest text-[10px] flex justify-center items-center gap-2 shadow-md">
                                <i class="fas fa-plus-circle"></i> Nuevo Seguimiento
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-8 w-full">
                    <div class="section-title text-sm font-black uppercase flex items-center gap-2 mb-4 text-slate-700">
                        <i class="fas fa-book-open"></i> Material de Interés
                    </div>
                    
                    <div v-if="librosParsed.length" class="overflow-hidden border rounded-2xl bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="hidden md:table-header-group bg-slate-50 border-b">
                                    <tr>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-500 tracking-widest">Material</th>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-500 tracking-widest">Formato</th>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-500 tracking-widest text-center">Promo</th>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-500 tracking-widest text-center">Cant.</th>
                                    </tr>
                                </thead>
                                <tbody class="block md:table-row-group divide-y divide-slate-100">
                                    <tr v-for="(libro, index) in librosParsed" :key="index" 
                                        class="block md:table-row p-4 md:p-0 relative hover:bg-slate-50 transition-colors">
                                        
                                        <td class="block md:table-cell px-0 md:px-6 py-2 md:py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="hidden md:flex w-7 h-7 bg-red-50 text-red-700 rounded-lg items-center justify-center font-black text-[10px] shrink-0">
                                                    {{ index + 1 }}
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="md:hidden block text-[9px] font-black text-slate-400 uppercase mb-0.5">Material</span>
                                                    <p class="font-black text-slate-800 text-xs md:text-sm uppercase leading-tight break-words">{{ libro.titulo }}</p>
                                                    <p class="text-[9px] font-bold text-slate-400 uppercase">{{ libro.serie_nombre }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="block md:table-cell px-0 md:px-6 py-2 md:py-4">
                                            <span class="md:hidden inline-block text-[9px] font-black text-slate-400 uppercase mr-2">Formato:</span>
                                            <span class="status-badge bg-red-50 text-red-700 text-[9px] uppercase">{{ libro.formato || 'Físico' }}</span>
                                        </td>
                                        
                                        <td class="block md:table-cell px-0 md:px-6 py-2 md:py-4 md:text-center">
                                            <span class="md:hidden inline-block text-[9px] font-black text-slate-400 uppercase mr-2">Promo:</span>
                                            <span v-if="libro.entrega_promo" class="text-green-700 font-black text-[9px] uppercase">
                                                <i class="fas fa-check-circle"></i> Entregado
                                            </span>
                                            <span v-else class="text-slate-300 italic text-[9px] font-bold uppercase tracking-tighter">Sin entrega</span>
                                        </td>
                                        
                                        <td class="block md:table-cell px-0 md:px-6 py-2 md:py-4 md:text-center">
                                            <span class="md:hidden inline-block text-[9px] font-black text-slate-400 uppercase mr-2">Cantidad:</span>
                                            <span class="text-sm font-black text-slate-800">{{ libro.cantidad_promo || '--' }}</span>
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

<style scoped>
.form-section { background: white; padding: 20px; border-radius: 20px; border: 1px solid #f1f5f9; box-sizing: border-box; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-widest; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; display: inline-block; }
.shadow-premium { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.04); }

/* AJUSTES PARA EVITAR DESBORDE */
@media (max-width: 640px) {
    .content-wrapper { padding: 8px !important; }
    .form-section { 
        padding: 16px !important; 
        border-radius: 16px !important;
        width: 100% !important;
    }
    .table-responsive td { padding: 8px 0 !important; }
}

/* Forzar que las palabras largas no rompan el layout */
.break-words {
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-word;
}

.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

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
        error.value = err.response?.data?.message || "Error al conectar con el servidor.";
    } finally {
        loading.value = false;
    }
};

const librosParsed = computed(() => {
    if (!visita.value || !visita.value.libros_interes) return [];
    if (Array.isArray(visita.value.libros_interes)) return visita.value.libros_interes;
    try {
        const parsed = JSON.parse(visita.value.libros_interes);
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [{ 
            titulo: visita.value.libros_interes, 
            serie_nombre: 'Dato Histórico', 
            formato: 'No especificado',
            entrega_promo: visita.value.material_entregado,
            cantidad_promo: visita.value.material_cantidad
        }];
    }
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

