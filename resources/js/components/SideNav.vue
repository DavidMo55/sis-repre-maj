<template>
  <div>
    <button v-if="isMobile" class="hamburger-btn" @click="toggleSidenav">
      <MenuIcon v-if="!open" :size="24" />
      <XIcon v-else :size="24" />
    </button>

    <div v-if="open && isMobile" class="sidenav-overlay" @click="toggleSidenav"></div>

    <aside :class="['sidenav', { 'sidenav-collapsed': collapsed, 'sidenav-open': open }]">
      <div class="sidenav-header">
        <div v-if="!collapsed" class="logo-container">
          <img src="/LOGO-ME-PNG.png" alt="Logo" class="sidenav-logo"/>
        </div>
        <BookOpenIcon v-else class="logo-icon-mini" :size="28" />

        <button v-if="!isMobile" class="collapse-btn" @click="toggleSidenav">
          <ChevronLeftIcon :class="{ 'rotated': collapsed }" :size="16" />
        </button>
      </div>

      <ul class="sidenav-menu">
        <li v-for="item in menuItems" :key="item.label">
          <router-link 
            :to="item.to" 
            class="nav-link" 
            active-class="active"
            @click="isMobile ? open = false : null"
          >
            <component :is="item.icon" class="nav-icon" :size="22" />
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
          <Loader2Icon v-if="isLoggingOut" class="animate-spin" :size="20" />
          <LogOutIcon v-else :size="20" />
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
import { 
  LayoutDashboardIcon, ShoppingCartIcon, UsersIcon, 
  GraduationCapIcon, ReceiptIcon, ChevronLeftIcon,
  LogOutIcon, MenuIcon, XIcon, BookOpenIcon, Loader2Icon 
} from 'lucide-vue-next'

const router = useRouter()
const isLoggingOut = ref(false)
const collapsed = ref(false)
const open = ref(false)
const windowWidth = ref(window.innerWidth)

const menuItems = [
  { label: 'Dashboard', to: '/dashboard', icon: LayoutDashboardIcon },
  { label: 'Pedidos', to: '/pedidos', icon: ShoppingCartIcon },
  { label: 'Visitas', to: '/visitas', icon: UsersIcon },
  { label: 'Capacitaciones', to: '/capacitaciones', icon: GraduationCapIcon },
  { label: 'Gastos', to: '/gastos', icon: ReceiptIcon },
]

const isMobile = computed(() => windowWidth.value < 768)

const toggleSidenav = () => {
  isMobile.value ? open.value = !open.value : collapsed.value = !collapsed.value
}

const handleLogout = async () => {
  if (isLoggingOut.value) return
  if (confirm('¿Estás seguro de cerrar sesión?')) {
    isLoggingOut.value = true
    try {
      await axios.post('/logout')
    } catch (e) {
      console.error('Logout error:', e)
    } finally {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      delete axios.defaults.headers.common['Authorization']
      router.push('/login')
      isLoggingOut.value = false
    }
  }
}

const updateWidth = () => windowWidth.value = window.innerWidth
onMounted(() => window.addEventListener('resize', updateWidth))
onUnmounted(() => window.removeEventListener('resize', updateWidth))
</script>

<style scoped>
.sidenav {
  width: 260px;
  height: 100vh;
  background: hsl(357, 54%, 43%, 10%);
  backdrop-filter: blur(12px);
  border-right: 1px solid rgba(169, 51, 57, 0.1);
  display: flex;
  flex-direction: column;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.sidenav-collapsed { width: 80px; }

.sidenav-header {
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 20px;
  position: relative;
  border-bottom: 1px solid rgba(0,0,0,0.05);
}

.sidenav-logo { max-width: 140px; height: auto; }

.collapse-btn {
  position: absolute;
  right: -12px;
  background: #d4b2b4;
  color: white;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.rotated { transform: rotate(180deg); }

.sidenav-menu {
  flex: 1;
  padding: 15px 0;
  list-style: none;
}

.nav-link {
  display: flex;
  align-items: center;
  padding: 12px 18px;
  margin: 4px 12px;
  color: #64748b;
  text-decoration: none;
  border-radius: 8px;
  transition: all 0.2s;
}

.nav-link:hover {
  background: rgba(169, 51, 57, 0.05);
  color: #a93339;
}

.nav-link.active {
  background: #d8a2a4;
  color: white;
  box-shadow: 0 4px 12px rgba(169, 51, 57, 0.25);
}

.nav-icon { flex-shrink: 0; margin-right: 12px; }
.sidenav-collapsed .nav-icon { margin-right: 0; }
.sidenav-collapsed .nav-text { display: none; }

.sidenav-footer { padding: 20px; border-top: 1px solid rgba(0,0,0,0.05); }

.logout-button {
  width: 100%;
  padding: 12px;
  background: #f1f5f9;
  border: none;
  border-radius: 8px;
  color: #a93339;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  cursor: pointer;
  transition: all 0.2s;
}

.logout-button:hover { background: #fee2e2; }
.sidenav-collapsed .logout-button span { display: none; }

.hamburger-btn {
  position: fixed;
  top: 15px;
  left: 15px;
  z-index: 1100;
  background: #d4b3b5;
  color: white;
  border: none;
  padding: 8px;
  border-radius: 8px;
}

.sidenav-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.3);
  z-index: 999;
}

.animate-spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .sidenav { position: fixed; left: 0; transform: translateX(-100%); }
  .sidenav-open { transform: translateX(0); width: 280px; }
}
</style>