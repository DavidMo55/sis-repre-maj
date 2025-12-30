<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Seguimiento de Visitas</h1>
                    <p>Gestiona los prospectos y las interacciones con los planteles.</p>
                </div>
                <div class="flex gap-3">
                    <router-link to="/primeras-visitas" class="btn-primary flex-row-centered gap-2">
                        <i class="fas fa-plus-circle"></i> Primera Visita
                    </router-link>
                    <router-link to="/visitas/seguimiento" class="btn-secondary flex-row-centered gap-2">
                        <i class="fas fa-history"></i> Visita Subsecuente
                    </router-link>
                </div>
            </div>

            <div v-if="loading" class="loading-state py-10">
                <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                <p>Cargando historial de visitas...</p>
            </div>

            <div v-else-if="visitas.length === 0" class="cart-empty-message mt-8">
                <i class="fas fa-calendar-day mb-2 text-xl"></i>
                <p>No has registrado visitas recientemente.</p>
            </div>

            <div v-else class="table-responsive table-shadow-lg mt-8">
                <table class="min-width-full divide-y-gray-200">
                    <thead class="bg-light-gray">
                        <tr>
                            <th class="table-header">Fecha</th>
                            <th class="table-header">Plantel</th>
                            <th class="table-header">Entrevistado</th>
                            <th class="table-header">Tipo</th>
                            <th class="table-header">Próxima</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y-gray-200">
                        <tr v-for="visita in visitas" :key="visita.id">
                            <td class="table-cell font-bold">{{ formatDate(visita.fecha) }}</td>
                            <td class="table-cell">
                                <div class="text-dark-gray font-semibold">{{ visita.cliente.name }}</div>
                                <div class="text-xs text-gray-500">{{ visita.cliente.direccion }}</div>
                            </td>
                            <td class="table-cell">
                                <div>{{ visita.persona_entrevistada }}</div>
                                <div class="text-xs text-medium-gray">{{ visita.cargo }}</div>
                            </td>
                            <td class="table-cell">
                                <span :class="visita.es_primera_visita ? 'badge-blue' : 'badge-gray'">
                                    {{ visita.es_primera_visita ? 'Primera' : 'Seguimiento' }}
                                </span>
                            </td>
                            <td class="table-cell text-red-600 font-semibold">
                                {{ formatDate(visita.proxima_visita_estimada) || 'No agendada' }}
                            </td>
                            <button @click="viewDetails(visita)" class="text-red-link font-bold ml-4">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </button>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from '../axios';
import { useRouter } from 'vue-router';

const router = useRouter();

const visitas = ref([]);
const loading = ref(true);

const fetchVisitas = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/visitas');
        const dataReceived = response.data.data || response.data;
        visitas.value = Array.isArray(dataReceived) ? dataReceived : [];
    } catch (err) {
        console.error("Error al cargar visitas", err);
        visitas.value = [];
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return null;
    return new Date(dateString).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
};

const viewDetails = (visita) => {
    router.push({ name: 'VisitaDetalle', params: { id: visita.id } });
};

onMounted(fetchVisitas);
</script>

<style scoped>
.badge-blue {
    background: #ebf8ff;
    color: #2b6cb0;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}
.badge-gray {
    background: #f7fafc;
    color: #4a5568;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}
</style>