<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1 v-if="visita">Visita a {{ visita.cliente.name }}</h1>
                    <h1 v-else>Cargando Visita...</h1>
                    <p>Consulta el historial y los compromisos adquiridos con este prospecto.</p>
                </div>
                <button @click="$router.push('/visitas')" class="btn-secondary flex-row-centered gap-2">
                    <i class="fas fa-arrow-left"></i> Volver al Historial
                </button>
            </div>

            <div v-if="loading" class="loading-state py-10">
                <i class="fas fa-spinner fa-spin text-3xl text-red-600 mb-3"></i>
                <p>Recuperando información de la bitácora...</p>
            </div>

            <div v-else-if="error" class="error-message p-6 text-center bg-red-50 rounded-lg">
                <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                <p>{{ error }}</p>
            </div>

            <div v-else-if="visita" class="detail-container mt-6">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- COLUMNA IZQUIERDA: INFORMACIÓN DEL PLANTEL -->
                    <div class="lg:col-span-1">
                        <div class="info-card border-brand shadow-sm">
                            <h2 class="card-title text-red-800">
                                <i class="fas fa-school"></i> Datos del Plantel
                            </h2>
                            
                            <div class="data-list mt-4">
                                <div class="data-item">
                                    <span class="label">Nivel Educativo:</span>
                                    <span class="value">{{ visita.cliente.nivel_educativo || 'No especificado' }}</span>
                                </div>
                                <div class="data-item">
                                    <span class="label">Director / Coordinador:</span>
                                    <span class="value font-bold">{{ visita.cliente.contacto }}</span>
                                </div>
                                <div class="data-item">
                                    <span class="label">Dirección:</span>
                                    <span class="value text-sm text-gray-600">{{ visita.cliente.direccion }}</span>
                                </div>
                                <div class="data-item">
                                    <span class="label">Ubicación:</span>
                                    <span class="value">{{ visita.cliente.estado?.estado || 'Estado no registrado' }}</span>
                                </div>
                                <div class="data-item">
                                    <span class="label">Contacto Directo:</span>
                                    <span class="value">
                                        <i class="fas fa-phone-alt text-xs text-gray-400"></i> {{ visita.cliente.telefono }}<br>
                                        <i class="fas fa-envelope text-xs text-gray-400"></i> {{ visita.cliente.email }}
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="label">Estatus del Registro:</span>
                                    <span class="status-badge bg-blue-100 text-blue-700">
                                        {{ visita.cliente.tipo }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: DETALLE DE LA ENTREVISTA -->
                    <div class="lg:col-span-2">
                        <div class="info-card bg-white shadow-sm">
                            <h2 class="card-title text-gray-800 border-gray-300">
                                <i class="fas fa-clipboard-list"></i> Resumen de la Entrevista
                            </h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div class="visit-data-box">
                                    <p class="label">Fecha de Visita</p>
                                    <p class="value-large text-red-700">{{ formatDate(visita.fecha) }}</p>
                                </div>
                                <div class="visit-data-box">
                                    <p class="label">Próxima Visita Estimada</p>
                                    <p class="value-large text-green-700">
                                        {{ formatDate(visita.proxima_visita_estimada) || 'No agendada' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6 border-t pt-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="label">Atendido por:</p>
                                        <p class="value font-bold text-gray-800">{{ visita.persona_entrevistada }}</p>
                                        <p class="text-xs text-gray-500 uppercase font-bold">{{ visita.cargo }}</p>
                                    </div>
                                    <div>
                                        <p class="label">Material de promoción:</p>
                                        <p class="value">
                                            <span v-if="visita.material_entregado" class="text-green-600 font-bold">
                                                <i class="fas fa-check-circle"></i> Entregado
                                            </span>
                                            <span v-else class="text-gray-400">No se entregó material</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
                                <p class="label mb-2"><i class="fas fa-book"></i> Libros de Interés:</p>
                                <p class="value text-gray-700 leading-relaxed">
                                    {{ visita.libros_interes || 'No se registraron títulos específicos.' }}
                                </p>
                            </div>

                            <div class="mt-6">
                                <p class="label mb-2"><i class="fas fa-comments"></i> Comentarios y Acuerdos:</p>
                                <div class="value text-gray-600 italic whitespace-pre-wrap bg-white p-3 border rounded">
                                    {{ visita.comentarios || 'Sin observaciones adicionales.' }}
                                </div>
                            </div>
                        </div>

                        <!-- Botón de acción rápida -->
                        <div class="mt-6 flex justify-end">
                            <button @click="$router.push('/visitas/seguimiento')" class="btn-primary">
                                <i class="fas fa-history"></i> Registrar Visita Subsecuente
                            </button>
                        </div>
                    </div>
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
const visitaId = route.params.id;

const visita = ref(null);
const loading = ref(true);
const error = ref(null);

const fetchVisitaDetail = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/visitas/${visitaId}`);
        visita.value = response.data;
    } catch (err) {
        error.value = 'No se pudo cargar la información de la visita. El registro podría no existir o no tener permisos.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return null;
    return new Date(dateString).toLocaleDateString('es-ES', { 
        year: 'numeric', month: 'long', day: 'numeric' 
    });
};

onMounted(fetchVisitaDetail);
</script>

<style scoped>
.info-card {
    padding: 24px;
    background: #fdfdfd;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.info-card.border-brand {
    border-top: 5px solid var(--brand-red-dark);
}

.card-title {
    font-size: 1.25rem;
    font-weight: 800;
    padding-bottom: 12px;
    border-bottom: 2px solid #f1f5f9;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.data-item {
    margin-bottom: 15px;
}

.label {
    font-size: 0.75rem;
    color: #94a3b8;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.05em;
    display: block;
}

.value {
    font-size: 1rem;
    color: #1e293b;
    margin-top: 2px;
}

.value-large {
    font-size: 1.4rem;
    font-weight: 800;
}

.visit-data-box {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-block;
    margin-top: 5px;
}
</style>