<template>
    <div>
        <!-- Botón Hamburguesa (Solo Móvil) -->
        <button v-if="isMobile" class="hamburger-btn" @click="toggleSidenav">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Overlay para cerrar en móvil al hacer clic fuera -->
        <div v-if="open && isMobile" class="sidenav-overlay" @click="toggleSidenav"></div>

        <aside :class="['sidenav', { 'sidenav-collapsed': collapsed, 'sidenav-open': open }]">
            <div class="sidenav-header">
                <!-- Logo: Se oculta o ajusta según el estado -->
                <img v-if="!collapsed" src="/LOGO-ME-PNG.png" alt="Sistema Representantes Logo" class="sidenav-logo"/>
                <i v-else class="fas fa-book-open logo-icon-mini"></i>

                <!-- Botón de Colapso (Solo Desktop) -->
                <button v-if="!isMobile" class="collapse-btn" @click="toggleSidenav">
                    <i class="fas fa-angle-left" :class="{ 'rotated': collapsed }"></i>
                </button>
            </div>

            <ul class="sidenav-menu">
                <li class="nav-item" v-for="item in menuItems" :key="item.label">
                    <router-link :to="item.to" class="nav-link" active-class="active" @click="isMobile ? open = false : null">
                        <i :class="['nav-icon', item.icon]"></i>
                        <span class="nav-text">{{ item.label }}</span>
                    </router-link>
                </li>
            </ul>

            <div class="sidenav-footer">
                <button 
                    @click="handleLogout" 
                    class="logout-button"
                    :disabled="isLoggingOut"
                >
                    <i class="nav-icon fas" :class="isLoggingOut ? 'fa-spinner fa-spin' : 'fa-sign-out-alt'"></i>
                    <span class="nav-text">{{ isLoggingOut ? 'Cerrando...' : 'Cerrar Sesión' }}</span>
                </button>
            </div>
        </aside>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from '../axios'

const router = useRouter()
const isLoggingOut = ref(false)
const collapsed = ref(false)
const open = ref(false)
const windowWidth = ref(window.innerWidth)

const menuItems = [
    { label: 'Dashboard', to: '/dashboard', icon: 'fas fa-home' },
    { label: 'Pedidos', to: '/pedidos', icon: 'fas fa-shopping-cart' },
    { label: 'Visitas', to: '/visitas', icon: 'fas fa-user-friends' },
    { label: 'Capacitaciones', to: '/capacitaciones', icon: 'fas fa-chalkboard-teacher' },
    { label: 'Gastos', to: '/gastos', icon: 'fas fa-receipt' },
]

const isMobile = computed(() => windowWidth.value < 768)

const toggleSidenav = () => {
    if (isMobile.value) {
        open.value = !open.value
    } else {
        collapsed.value = !collapsed.value
    }
}

const handleLogout = async () => {
    if (isLoggingOut.value) return

    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        isLoggingOut.value = true
        try {
            await axios.post('/logout')
        } catch (error) {
            console.error('Error al cerrar sesión:', error)
        } finally {
            localStorage.removeItem('auth_token')
            localStorage.removeItem('user_data')
            delete axios.defaults.headers.common['Authorization']
            router.push('/login')
            isLoggingOut.value = false
        }
    }
}

const updateWidth = () => {
    windowWidth.value = window.innerWidth
}

onMounted(() => {
    window.addEventListener('resize', updateWidth)
})

onUnmounted(() => {
    window.removeEventListener('resize', updateWidth)
})
</script>

<style scoped>
/* BASE SIDENAV */
.sidenav {
    width: 260px;
    height: 100vh;
    background-color: hsl(357, 54%, 43%, 10%);
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    color: #a93339;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease;
    flex-shrink: 0;
    position: sticky;
    top: 0;
    z-index: 1000;
    backdrop-filter: blur(10px);
}

/* ESTADO COLAPSADO (Desktop) */
.sidenav-collapsed {
    width: 80px;
}

.sidenav-collapsed .nav-text,
.sidenav-collapsed .sidenav-logo {
    display: none;
}

.sidenav-collapsed .nav-link {
    justify-content: center;
    padding: 14px 0;
}

.sidenav-collapsed .nav-icon {
    margin-right: 0;
}

/* HEADER */
.sidenav-header {
    padding: 20px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid rgba(51, 65, 85, 0.1);
    position: relative;
}

.sidenav-logo {
    width: 80%;
    max-width: 150px;
    height: auto;
}

.logo-icon-mini {
    font-size: 1.5rem;
    color: #a93339;
}

/* BOTÓN DE COLAPSO (Desktop) */
.collapse-btn {
    position: absolute;
    right: -12px;
    top: 50%;
    transform: translateY(-50%);
    background: #a93339;
    color: white;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.rotated {
    transform: rotate(180deg);
}

/* MENU */
.sidenav-menu {
    flex-grow: 1;
    list-style: none;
    padding: 10px 0;
    margin: 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 14px 20px;
    color: #a93339;
    text-decoration: none;
    font-size: 1rem;
    font-weight: 500;
    transition: background-color 0.2s;
    border-radius: 6px;
    margin: 5px 10px;
    white-space: nowrap;
}

.nav-link:hover {
    background-color: hsl(357, 54%, 43%, 25%);
}

.nav-link.active {
    background-color: hsl(357, 54%, 43%, 55%);
    color: white;
    font-weight: 700;
    box-shadow: 0 4px 8px rgba(169, 51, 57, 0.4);
}

.nav-icon {
    min-width: 24px;
    text-align: center;
    margin-right: 12px;
    font-size: 1.2rem;
}

/* FOOTER / LOGOUT */
.sidenav-footer {
    padding: 16px;
    border-top: 1px solid rgba(0,0,0,0.05);
}

.logout-button {
    width: 100%;
    background: #da8f93;
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
}

.logout-button:hover {
    background-color: #cfabad;
}

.sidenav-collapsed .logout-button {
    padding: 12px 0;
}

/* HAMBURGUER (Solo Móvil) */
.hamburger-btn {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1100;
    background: #a93339;
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

/* OVERLAY MÓVIL */
.sidenav-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    z-index: 999;
}

/* RESPONSIVE MÓVIL */
@media (max-width: 768px) {
    .sidenav {
        position: fixed;
        left: 0;
        top: 0;
        transform: translateX(-100%);
    }

    .sidenav-open {
        transform: translateX(0);
        width: 280px;
    }

    .sidenav-collapsed {
        width: 280px; /* Ignorar colapso mini en móvil */
    }
}
</style>