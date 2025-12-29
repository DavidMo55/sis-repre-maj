<template>
    <aside class="sidenav">
        <div class="sidenav-header">
            <img src="/LOGO-ME-PNG.png" alt="Sistema Representantes Logo" class="sidenav-logo"/>
        </div>

        <ul class="sidenav-menu">
            <li class="nav-item">
                <router-link to="/dashboard" class="nav-link" active-class="active">
                    <i class="nav-icon fas fa-home"></i> Dashboard
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/pedidos" class="nav-link" active-class="active"> 
                    <i class="nav-icon fas fa-shopping-cart"></i> Pedidos
                 </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/visitas" class="nav-link" active-class="active">
                    <i class="nav-icon fas fa-user-friends"></i> Visitas
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/capacitaciones" class="nav-link" active-class="active">
                    <i class="nav-icon fas fa-chalkboard-teacher"></i> Capacitaciones
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/gastos" class="nav-link" active-class="active">
                    <i class="nav-icon fas fa-receipt"></i> Gastos
                </router-link>
            </li>
        </ul>

        <div class="sidenav-footer">
            <button 
                @click="handleLogout" 
                class="nav-link logout-button w-full text-left"
                :disabled="isLoggingOut"
            >
                <i class="nav-icon fas" :class="isLoggingOut ? 'fa-spinner fa-spin' : 'fa-sign-out-alt'"></i>
                {{ isLoggingOut ? 'Cerrando...' : 'Cerrar Sesión' }}
            </button>
        </div>
    </aside>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios'; 

const router = useRouter();
const isLoggingOut = ref(false);

const handleLogout = async () => {
    if (isLoggingOut.value) return;

    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        isLoggingOut.value = true;
        
        try {
            await axios.post('/logout');
        } catch (error) {
            console.error('Error al cerrar sesión en el servidor:', error);
        } finally {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_data');
            
            delete axios.defaults.headers.common['Authorization'];

            router.push('/login');
            isLoggingOut.value = false;
        }
    }
};
</script>

<style scoped>
.sidenav-footer {
    margin-top: auto;
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.logout-button {
    background: transparent;
    border: none;
    color: #fca5a5; 
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    border-radius: 8px;
}

.logout-button:hover {
    background: rgba(239, 68, 68, 0.1); 
    color: #ef4444; 
}

.logout-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.nav-icon {
    width: 20px;
    text-align: center;
}
</style>