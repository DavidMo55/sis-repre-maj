<template>
    <div class="app-container"> 
        
        <SideNav />

        <div class="content-area">
            
            <main class="content-wrapper">
                <router-view /> 
            </main>

            <footer class="main-footer">
                &copy; 2025 Sistema para Representantes. Desarrollado por David Morales.
            </footer>
        </div>
    </div>
</template>

<script setup>
import SideNav from './SideNav.vue'; 
import { onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import '../assets/base.css';
import '../assets/main.css';

const router = useRouter();

const checkSession = () => {
    const token = localStorage.getItem('auth_token');
    const sessionStart = localStorage.getItem('session_start_time');

    if (token && sessionStart) {
        const twoHours = 2 * 60 * 60 * 1000;
        const now = new Date().getTime();

        if (now - parseInt(sessionStart) > twoHours) {
            // Limpiamos todo
            localStorage.removeItem('auth_token');
            localStorage.removeItem('session_start_time');
            localStorage.removeItem('user_data');
            
            // Redirigimos al login
            router.push('/login');
            alert("Tu sesión ha expirado por seguridad.");
        }
    }
};

let interval = null;

onMounted(() => {
    // 1. Verificar inmediatamente al cargar la página
    checkSession();

    // 2. Verificar cada minuto por si el usuario deja la página abierta
    interval = setInterval(checkSession, 60000); 
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
});
</script>


