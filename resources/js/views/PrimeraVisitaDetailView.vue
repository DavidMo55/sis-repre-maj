<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div class="header-info">
                    <!-- Mostramos el nombre del plantel directamente desde la visita -->
                    <h1 v-if="visita">Visita a {{ visita.nombre_plantel }}</h1>
                    <h1 v-else>Cargando Visita...</h1>
                    <p>Consulta los compromisos y acuerdos registrados en esta bitácora.</p>
                </div>
                <!-- Botón de Navegación "Volver" -->
                <button @click="router.push('/visitas')" class="btn-secondary flex-row-centered gap-2">
                    <i class="fas fa-arrow-left"></i> Volver al Historial
                </button>
            </div>

            <!-- Estado de Carga -->
            <div v-if="loading" class="loading-state py-12 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-red-600 mb-3"></i>
                <p class="text-gray-500 font-medium">Recuperando información de la bitácora...</p>
            </div>

            <!-- Manejo de Errores -->
            <div v-else-if="error" class="error-message-container mt-6 p-8 text-center bg-red-50 border border-red-200 rounded-xl">
                <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i>
                <h2 class="text-xl font-bold text-red-800">No se encontró la visita</h2>
                <p class="text-red-600 mt-2">{{ error }}</p>
                <div class="flex justify-center gap-4 mt-6">
                    <button @click="fetchVisitaDetail" class="btn-primary">
                        <i class="fas fa-sync-alt"></i> Reintentar Carga
                    </button>
                    <button @click="router.push('/visitas')" class="btn-secondary">
                        Ir al historial general
                    </button>
                </div>
            </div>

            <!-- Contenido de la Visita -->
            <div v-else-if="visita" class="detail-grid mt-6 animate-fade-in grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- COLUMNA IZQUIERDA: DATOS DEL PLANTEL -->
                <div class="lg:col-span-1">
                    <div class="info-card border-red h-full">
                        <h2 class="card-title text-red-700 flex items-center gap-2">
                            <i class="fas fa-school"></i> Datos del Plantel
                        </h2>
                        <div class="mt-4 space-y-4">
                            <div>
                                <span class="label-xs">Nivel Educativo:</span>
                                <span class="text-gray-800 font-semibold">{{ visita.nivel_educativo_plantel }}</span>
                            </div>
                            <div>
                                <span class="label-xs">Director / Coordinador:</span>
                                <span class="text-gray-700 font-bold">{{ visita.director_plantel }}</span>
                            </div>
                            <div>
                                <span class="label-xs">Dirección:</span>
                                <span class="text-gray-600 text-sm leading-tight">{{ visita.direccion_plantel }}</span>
                            </div>
                            <div v-if="visita.latitud" class="bg-blue-50 p-2 rounded border border-blue-100">
                                <span class="label-xs text-blue-700">Geolocalización:</span>
                                <span class="text-[10px] font-mono text-blue-600 block">LAT: {{ visita.latitud }}</span>
                                <span class="text-[10px] font-mono text-blue-600 block">LON: {{ visita.longitud }}</span>
                                <a :href="`https://www.google.com/maps/search/?api=1&query=${visita.latitud},${visita.longitud}`" target="_blank" class="text-[10px] font-bold underline mt-1 block">
                                    <i class="fas fa-external-link-alt"></i> Ver en Google Maps
                                </a>
                            </div>
                            <div>
                                <span class="label-xs">Contacto:</span>
                                <span class="text-gray-700 block"><i class="fas fa-phone-alt mr-1 opacity-40"></i> {{ visita.telefono_plantel }}</span>
                                <span class="text-gray-700 block"><i class="fas fa-envelope mr-1 opacity-40"></i> {{ visita.email_plantel }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: RESUMEN DE LA ENTREVISTA -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="info-card bg-white shadow-sm">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-4">
                            <h2 class="card-title text-gray-800 m-0 border-0 p-0">
                                <i class="fas fa-clipboard-list"></i> Detalle de la Entrevista
                            </h2>
                            <span :class="getOutcomeClass(visita.resultado_visita)" class="status-badge">
                                {{ (visita.resultado_visita || 'seguimiento').toUpperCase() }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="visit-data-box">
                                <p class="label-xs">Fecha de Visita</p>
                                <p class="text-lg font-black text-red-800">{{ formatDate(visita.fecha) }}</p>
                            </div>
                            <div class="visit-data-box">
                                <p class="label-xs">Próxima Acción Agendada</p>
                                <div v-if="visita.proxima_visita_estimada">
                                    <p class="text-lg font-black text-green-700">{{ formatDate(visita.proxima_visita_estimada) }}</p>
                                    <span class="text-[10px] font-bold uppercase text-green-600 bg-green-50 px-2 py-0.5 rounded border border-green-100">
                                        {{ visita.proxima_accion === 'presentacion' ? 'Presentación Académica' : 'Visita de Seguimiento' }}
                                    </span>
                                </div>
                                <p v-else class="text-gray-400 italic">No se agendó fecha próxima</p>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4">
                            <div>
                                <p class="label-xs">Atendido por:</p>
                                <p class="text-gray-800 font-bold">{{ visita.persona_entrevistada }}</p>
                                <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">{{ visita.cargo }}</p>
                            </div>
                            <div>
                                <p class="label-xs">Material de Promoción:</p>
                                <div v-if="visita.material_entregado" class="flex items-center gap-2">
                                    <span class="text-green-600 font-bold"><i class="fas fa-check-circle"></i> Entregado</span>
                                    <span class="text-xs bg-gray-100 px-2 py-0.5 rounded border">{{ visita.material_cantidad }} unidades</span>
                                </div>
                                <span v-else class="text-gray-400 italic">Sin entrega de material</span>
                            </div>
                        </div>

                        <div class="mt-6 bg-gray-50 p-4 rounded-xl border border-dashed border-gray-300">
                            <p class="label-xs mb-2 text-red-700"><i class="fas fa-book mr-1"></i> Libros de Interés:</p>
                            <p class="text-gray-700 italic text-sm leading-relaxed">
                                {{ visita.libros_interes || 'No se registraron títulos específicos en esta visita.' }}
                            </p>
                        </div>

                        <div class="mt-6">
                            <p class="label-xs mb-2"><i class="fas fa-comments mr-1"></i> Comentarios y Acuerdos:</p>
                            <div class="text-gray-600 text-sm whitespace-pre-wrap bg-white p-4 border rounded-xl shadow-inner min-h-[100px]">
                                {{ visita.comentarios || 'Sin observaciones adicionales registradas.' }}
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="mt-8 flex justify-end gap-3">
                            <button 
                                v-if="visita.resultado_visita !== 'rechazo'"
                                @click="router.push({ name: 'VisitaSeguimiento', params: { id: visita.id } })" 
                                class="btn-primary flex items-center gap-2 shadow-lg"
                            >
                                <i class="fas fa-history"></i> Registrar Nuevo Seguimiento
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../axios';

const route = useRoute();
const router = useRouter();

const visita = ref(null);
const loading = ref(false);
const error = ref(null);

const fetchVisitaDetail = async () => {
    const currentId = route.params.id;
    
    if (!currentId) {
        error.value = "ID de visita no detectado.";
        return;
    }

    loading.value = true;
    error.value = null;
    visita.value = null; 
    
    try {
        const response = await axios.get(`/visitas/${currentId}`);
        visita.value = response.data;
    } catch (err) {
        if (err.response) {
            if (err.response.status === 404) {
                error.value = `La visita #${currentId} no existe en tus registros.`;
            } else if (err.response.status === 403) {
                error.value = 'No tienes permisos para acceder a esta bitácora.';
            } else {
                error.value = err.response.data.message || 'Error al recuperar los datos de la bitácora.';
            }
        } else {
            error.value = 'Error de conexión con el servidor.';
        }
        console.error('Error detallado:', err);
    } finally {
        loading.value = false;
    }
};

watch(
    () => route.params.id,
    (newId) => { if (newId) fetchVisitaDetail(); }
);

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const date = new Date(dateString);
    date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
    return date.toLocaleDateString('es-ES', options);
};

const getOutcomeClass = (outcome) => {
    const base = 'status-badge ';
    switch (outcome) {
        case 'compra': return base + 'bg-green-100 text-green-700 border border-green-200';
        case 'rechazo': return base + 'bg-red-100 text-red-800 border border-red-200';
        default: return base + 'bg-orange-100 text-orange-700 border border-orange-200';
    }
};

onMounted(fetchVisitaDetail);
</script>

<style scoped>
/* FIX: Estilos para el encabezado con botón */
.detail-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e2e8f0;
}

@media (max-width: 640px) {
    .detail-header-flex {
        flex-direction: column;
    }
}

.flex-row-centered {
    display: flex;
    align-items: center;
    justify-content: center;
}

.gap-2 {
    gap: 8px;
}

.label-xs { font-size: 0.65rem; color: #94a3b8; text-transform: uppercase; font-weight: 800; display: block; letter-spacing: 0.05em; margin-bottom: 2px; }
.info-card { background-color: #ffffff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; }
.info-card.border-brand { border-top: 5px solid #a93339; }
.card-title { font-size: 1.1rem; font-weight: 800; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; }
.visit-data-box { background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #edf2f7; }
.status-badge { padding: 4px 14px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; letter-spacing: 0.025em; }
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>