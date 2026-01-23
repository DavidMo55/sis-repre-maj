<template>
  <div class="login-page-wrapper"> 
    <form @submit.prevent="handleLogin" class="login-form">
      
      <div class="logo-container">
        <img src="/LOGO-ME-PNG.png" alt="Logo SIS" class="login-logo">
      </div>

      <div class="form-header">
        <h2>SISTEMA DE REPRESENTANTES</h2>
        <p>Acceso de Representantes</p>
      </div>

      <div class="form-group">
        <label for="username">Usuario</label>
        <input 
          v-model="form.username" 
          type="text" 
          id="username" 
          required 
          autocomplete="off" 
          class="form-input"
        />
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <input 
          v-model="form.password" 
          type="password" 
          id="password" 
          required 
          autocomplete="new-password" 
          class="form-input"
        />
      </div>

      <button type="submit" :disabled="loading" class="login-button">
        {{ loading ? 'Ingresando...' : 'Iniciar Sesión' }}
      </button>
      
      <p class="forgot-password-link">
        <a href="#" @click.prevent="showRecoveryModal = true">
          ¿Olvidaste tu contraseña?
        </a>
      </p>

      <p v-if="error" class="error-message">{{ error }}</p>
      
    </form>

    <Teleport to="body">
        <RecoveryModal :visible="showRecoveryModal" @close="showRecoveryModal = false" />
    </Teleport>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router'; 
import axios from '../../axios'; 
import RecoveryModal from './RecoveryModal.vue';

const router = useRouter(); 

const form = reactive({
  username: '', 
  password: '',
});

const loading = ref(false);
const error = ref(null);
const showRecoveryModal = ref(false);

const handleLogin = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await axios.post('/login', {
        username: form.username,
        password: form.password,
    });

    // --- LÓGICA DE SESIÓN ---
    // 1. Guardamos el token
    localStorage.setItem('auth_token', response.data.token);
    
    // 2. Guardamos el momento exacto del inicio (Timestamp en milisegundos)
    // Esto es lo que usará el router para calcular las 2 horas
    localStorage.setItem('session_start_time', new Date().getTime().toString());

    // 3. Opcional: Guardar datos del usuario si tu API los retorna
    if (response.data.user) {
        localStorage.setItem('user_data', JSON.stringify(response.data.user));
    }

    // Redirigir al sistema
    router.push('/dashboard'); 

    // Limpiar formulario
    form.username = '';
    form.password = '';

  } catch (err) {
    if (err.response && err.response.status === 401) {
      error.value = 'Credenciales inválidas. Por favor, verifica tu usuario y contraseña.';
    } else {
      error.value = 'Error de conexión con el servidor. Verifica que Laravel esté corriendo.';
      console.error("Error de Login:", err); 
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Aquí irían tus estilos existentes */
.error-message {
  color: #ff4d4d;
  margin-top: 10px;
  font-size: 0.9rem;
  text-align: center;
}
</style>