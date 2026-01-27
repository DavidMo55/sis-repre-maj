<template>
  
    <router-view />
</template>

<script setup>
import './assets/base.css';
import './assets/main.css'
import { onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
let interval = null;

// Función para refrescar el tiempo en LocalStorage
const refreshSession = () => {
    if (localStorage.getItem('auth_token')) {
        localStorage.setItem('session_start_time', new Date().getTime().toString());
    }
};

const checkSession = () => {
    const token = localStorage.getItem('auth_token');
    const sessionStart = localStorage.getItem('session_start_time');
    
    if (token && sessionStart) {
        const TWO_HOURS = 2 * 60 * 60 * 1000;
        const now = new Date().getTime();
        
        if (now - parseInt(sessionStart) > TWO_HOURS) {
            localStorage.clear();
            router.push('/login');
            alert("Sesión cerrada por inactividad.");
        }
    }
};

onMounted(() => {
    checkSession(); // Revisar al entrar
    interval = setInterval(checkSession, 60000); // Revisar cada minuto

    // ESCUCHAR ACTIVIDAD: Si mueve el mouse, hace clic o escribe, refrescamos el tiempo
    window.addEventListener('mousemove', refreshSession);
    window.addEventListener('keydown', refreshSession);
    window.addEventListener('click', refreshSession);
});

onUnmounted(() => {
    clearInterval(interval);
    window.removeEventListener('mousemove', refreshSession);
    window.removeEventListener('keydown', refreshSession);
    window.removeEventListener('click', refreshSession);
});
</script>

<style>
</style>