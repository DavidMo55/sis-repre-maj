<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div class="header-info overflow-hidden">
                    <h1 v-if="visita && visita.cliente" class="text-truncate" :title="visita.cliente.name">
                        Visita a {{ visita.cliente.name }}
                    </h1>
                    <h1 v-else-if="loading">Cargando Visita...</h1>
                    <p class="text-truncate">Gestión de acuerdos y compromisos de bitácora.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary flex-shrink-0">
                    <i class="fas fa-arrow-left mr-2"></i> Volver
                </button>
            </div>

            <div v-if="loading" class="loading-state py-12 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-red-600 mb-3"></i>
                <p class="text-gray-500 font-medium">Recuperando información...</p>
            </div>

            <div v-else-if="visita" class="detail-grid mt-6 animate-fade-in grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div class="info-card border-brand text-break">
                    <h2 class="card-title text-red-700"><i class="fas fa-school"></i> Plantel</h2>
                    <div class="mt-4 space-y-4">
                        <div class="overflow-hidden">
                            <span class="label-xs">Institución:</span>
                            <span class="text-red-700 font-bold text-truncate" :title="visita.cliente?.name">
                                {{ visita.cliente?.name }}
                            </span>
                        </div>
                        <div class="overflow-hidden">
                            <span class="label-xs">Director / Contacto:</span>
                            <span class="text-gray-700 font-medium text-truncate" :title="visita.cliente?.contacto">
                                {{ visita.cliente?.contacto || 'No registrado' }}
                            </span>
                        </div>
                        <div class="overflow-hidden">
                            <span class="label-xs">Dirección:</span>
                            <p class="text-gray-600 text-xs leading-tight line-clamp-3" :title="visita.cliente?.direccion">
                                {{ visita.cliente?.direccion }}
                            </p>
                        </div>
                        <div class="flex gap-4 pt-2 border-t border-gray-50">
                            <div class="flex-1 overflow-hidden">
                                <span class="label-xs">Nivel:</span>
                                <span class="text-gray-500 text-[10px] font-bold uppercase">{{ visita.cliente?.nivel_educativo || '---' }}</span>
                            </div>
                            <div class="flex-1 overflow-hidden">
                                <span class="label-xs">Teléfono:</span>
                                <span class="text-gray-500 text-[10px]">{{ visita.cliente?.telefono || '---' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-card text-break">
                    <h2 class="card-title text-gray-800"><i class="fas fa-user-tie"></i> Entrevista</h2>
                    <div class="mt-4 space-y-4">
                        <div class="visit-data-box overflow-hidden">
                            <span class="label-xs">Atendido por:</span>
                            <p class="text-red-700 font-bold text-truncate" :title="visita.persona_entrevistada">
                                {{ visita.persona_entrevistada }}
                            </p>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest text-truncate">{{ visita.cargo }}</p>
                        </div>
                        <div class="flex justify-between gap-2">
                            <div class="overflow-hidden">
                                <span class="label-xs">Fecha:</span>
                                <span class="text-red-800 font-bold text-sm">{{ formatDate(visita.fecha) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="label-xs">Resultado:</span>
                                <span :class="getOutcomeClass(visita.resultado_visita)" class="status-badge">
                                    {{ (visita.resultado_visita || 'seguimiento').toUpperCase() }}
                                </span>
                            </div>
                        </div>
                        <div v-if="visita.material_entregado" class="bg-red-50 p-2 rounded border border-red-100 mt-2">
                            <span class="label-xs text-red-700">Material de Promoción:</span>
                            <span class="text-xs font-bold text-red-900">{{ visita.material_cantidad }} Libros / Unidades</span>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h2 class="card-title text-gray-800"><i class="fas fa-calendar-check"></i> Acuerdos</h2>
                    <div class="mt-4 space-y-4">
                        <div class="visit-data-box border-l-4 border-l-green-500">
                            <span class="label-xs">Próxima Visita:</span>
                            <p v-if="visita.proxima_visita_estimada" class="text-green-700 font-bold text-lg">
                                {{ formatDate(visita.proxima_visita_estimada) }}
                            </p>
                            <p v-else class="text-gray-400 italic text-xs">Sin fecha agendada</p>
                        </div>
                        <div class="overflow-hidden">
                            <span class="label-xs text-red-700"><i class="fas fa-book mr-1"></i> Libros de Interés:</span>
                            <p class="text-gray-600 text-xs italic line-clamp-2" :title="visita.libros_interes">
                                {{ visita.libros_interes || 'Ninguno registrado' }}
                            </p>
                        </div>
                        <div class="overflow-hidden">
                            <span class="label-xs"><i class="fas fa-comments mr-1"></i> Observaciones:</span>
                            <div class="comment-scroll text-gray-600 text-xs bg-gray-50 p-3 rounded border border-gray-100">
                                {{ visita.comentarios || 'Sin observaciones adicionales.' }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div v-if="visita && visita.resultado_visita !== 'rechazo'" class="flex justify-end mt-8">
               <div class="mt-8 flex justify-end gap-3">
                    <button 
                        v-if="visita && visita.resultado_visita !== 'rechazo'"
                        @click="router.push({ name: 'SeguimientoID', params: { id: visita.id } })" 
                        class="btn-primary flex items-center gap-2 shadow-lg"
                    >
                        <i class="fas fa-history"></i> Registrar Nuevo Seguimiento
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../axios';

const route = useRoute();
const router = useRouter();
const visita = ref(null);
const loading = ref(true);

const fetchVisitaDetail = async () => {
    try {
        const response = await axios.get(`/visitas/${route.params.id}`);
        visita.value = response.data;
    } catch (err) {
        console.error("Error al recuperar detalle:", err);
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '---';
    const parts = dateString.split('T')[0].split('-');
    const date = new Date(parts[0], parts[1] - 1, parts[2]);
    return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getOutcomeClass = (outcome) => {
    if (outcome === 'compra') return 'bg-green-100 text-green-700 border-green-200';
    if (outcome === 'rechazo') return 'bg-red-100 text-red-700 border-red-200';
    return 'bg-orange-100 text-orange-700 border-orange-200';
};

onMounted(fetchVisitaDetail);
</script>

<style scoped>
/* PREVENCIÓN DE DESBORDE CRÍTICA */
.text-truncate {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.max-w-title {
    max-width: 60%;
}

/* CONTENEDOR DE COMENTARIOS CON SCROLL SI EXCEDE ALTURA */
.comment-scroll {
    max-height: 120px;
    overflow-y: auto;
    word-break: break-word;
    line-height: 1.4;
}

/* TARJETAS CON TAMAÑO FIJO EN GRID */
.info-card {
    background: #fff;
    padding: 24px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    min-width: 0; /* Permite que el truncado funcione dentro de flex */
}

.border-brand {
    border-top: 4px solid #b91c1c;
}

.card-title {
    font-size: 1rem;
    font-weight: 800;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 10px;
}

.visit-data-box {
    background: #f8fafc;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #edf2f7;
}

.label-xs {
    font-size: 0.65rem;
    color: #94a3b8;
    text-transform: uppercase;
    font-weight: 800;
    display: block;
    margin-bottom: 3px;
    letter-spacing: 0.025em;
}

.status-badge {
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 800;
    border: 1px solid transparent;
}

.detail-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
}

.flex-shrink-0 {
    flex-shrink: 0;
}

/* Fuerza el salto de línea incluso en palabras sin espacios */
.text-break {
    inline-size: 100%;
    overflow-wrap: break-word;
    word-break: break-all; /* Esto romperá la cadena de texto para que quepa */
}

/* Para el título principal que se ve encimado */
h1.text-truncate {
    white-space: normal; /* Cambia de nowrap a normal para permitir varias líneas si es necesario */
    display: -webkit-box;
    -webkit-line-clamp: 2; /* Máximo 2 líneas en el título */
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.2;
}

/* Asegura que las tarjetas no crezcan más de su contenedor */
.info-card {
    min-width: 0; 
    word-wrap: break-word;
}
</style>