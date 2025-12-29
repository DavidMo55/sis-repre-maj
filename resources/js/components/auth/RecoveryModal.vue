<template>
  <div v-if="visible" class="modal-overlay">
    <div class="modal-card">
      
      <h3 class="modal-title">
        Recuperación de Contraseña
      </h3>

      <p v-if="!successMessage" class="modal-text">
        Ingresa tus datos para crear un ticket y permitir que un administrador te ayude a recuperar tu acceso.
      </p>
      
      <p v-if="successMessage" class="success-message is-large">
          <i class="fas fa-check-circle mr-2"></i> {{ successMessage }}
      </p>
      <p v-if="errorMessage" class="error-message modal-error">
          <i class="fas fa-exclamation-triangle mr-2"></i> {{ errorMessage }}
      </p>


      <form @submit.prevent="submitTicket" v-if="!successMessage">

        <div class="form-group">
          <label for="recovery_username">Nombre de Usuario</label>
          <input
            v-model="form.username"
            type="text"
            id="recovery_username"
            required
            class="form-input"
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="recovery_email">Correo Electrónico</label>
          <input
            v-model="form.email"
            type="email"
            id="recovery_email"
            required
            class="form-input"
            :disabled="loading"
          />
        </div>

        <div class="btn-row">
          <button type="button" class="btn-cancel" @click="$emit('close')" :disabled="loading">
            Cancelar
          </button>

          <button type="submit" class="btn-submit" :disabled="loading">
            {{ loading ? 'Enviando...' : 'Enviar Solicitud' }}
          </button>
        </div>

      </form>

    </div>
  </div>
</template>


<script setup>
import { reactive, ref, defineProps, defineEmits } from 'vue';
import axios from '../../axios'; 

const props = defineProps({
  visible: Boolean,
});

const emit = defineEmits(['close']);

const form = reactive({
  username: '',
  email: '',
});

const successMessage = ref('');
const errorMessage = ref('');
const loading = ref(false); // Estado de carga

const submitTicket = async () => {
  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    await axios.get('http://127.0.0.1:8000/sanctum/csrf-cookie'); 
    
    const response = await axios.post('/password/request-ticket', {
        username: form.username,
        email: form.email,
    });
    
    successMessage.value = response.data.message;

    setTimeout(() => {
      emit('close');
      form.username = '';
      form.email = '';
      successMessage.value = '';
    }, 3000);

  } catch (err) {
    if (err.response && err.response.status === 422) {
      errorMessage.value = 'Por favor, verifica el formato de tu email o ingresa todos los campos.';
    } else {
      errorMessage.value = 'Error de conexión con el servidor. Inténtalo de nuevo más tarde.';
    }
    console.error("Error al enviar ticket:", err);
  } finally {
    loading.value = false;
  }
};
</script>