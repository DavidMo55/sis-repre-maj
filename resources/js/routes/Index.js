import { createRouter, createWebHistory } from 'vue-router';
import MainLayout from '@/components/MainLayout.vue';
import DashboardView from '@/views/DashboardView.vue'; 
import LoginForm from '@/components/auth/Login.vue';
import PedidosIndexView from '@/views/PedidosIndexView.vue';
import DetallePedido from '@/views/PedidoDeatilView.vue';
import GenerarPedido from '@/views/PedidosView.vue'
import GastosIndexView from '@/views/GastosIndexView.vue';
import GastosDeailView from '@/views/GastosDeailView.vue';
import VisistasIndexView from '@/views/VisitasIndesView.vue';
import PrimerasVisitasView from '@/views/PrimeraVisitaView.vue';
import DetallesPrimerVisita from '@/views/PrimeraVisitaDetailView.vue'
import SeguimientoID from '../views/VisitaIDView.vue';
import GastosCreateView from '../views/GastosCreateView.vue';
import CapacitacionesIndexView from '../views/CapacitacionesIndexView.vue';
import ProfileView from '../views/profile/ProfileView.vue';

const authGuard = (to, from, next) => {
    const token = localStorage.getItem('auth_token');

    if (to.meta.requiresAuth && !token) {
        next('/login');
    } else if (to.path === '/login' && token) {
        next('/dashboard');
    } else {
        next();
    }
};

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: LoginForm,
        meta: { requiresAuth: false }
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'dashboard',
                name: 'Dashboard',
                component: DashboardView,
            },
            {
                path: 'pedidos',
                name: 'Pedidos',
                component: PedidosIndexView,
            },
            {
                path: 'pedidos/:id',
                name: 'DetallePedido',
                component: DetallePedido
            },
            {
                path: 'GenerarPedido',
                name: 'GenerarPedido',
                component: GenerarPedido
            },
            {
                path: 'gastos',
                name: 'Gastos',
                component: GastosIndexView,
            },
            {
                path: 'gastos/:id',
                name: 'GastosDetail',
                component: GastosDeailView
            },
            {
                path: 'visitas',
                name: 'Visitas',
                component: VisistasIndexView,
            },
            {
                path: 'primeras-visitas',
                name: 'PrimerasVisitas',
                component: PrimerasVisitasView
            },
            {
                path: 'visita-detalle/:id',
                name: 'VisitaDetalle',
                component: DetallesPrimerVisita
            },
            
            {
                path: 'seguimiento/:id',
                name: 'SeguimientoID',
                component: SeguimientoID
            },
            {
                path: 'gastos-nuevo',
                name: 'GastosCreate',
                component: GastosCreateView
            },
            {
                path: 'capacitaciones', 
                name: 'Capacitaciones',
                component: CapacitacionesIndexView
            },
            {
                path: 'profile',
                name: 'Profile',
                component: ProfileView
            }

        ]
    },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' } 
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return { top: 0, left: 0 }
  }
})

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('auth_token');
    const sessionStart = localStorage.getItem('session_start_time');
    
    if (token && sessionStart) {
        const twoHours = 2 * 60 * 60 * 1000;
        const now = new Date().getTime();

        if (now - parseInt(sessionStart) > twoHours) {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('session_start_time');
            localStorage.removeItem('user_data');
            return next('/login');
        }
    }

    if (to.meta.requiresAuth && !token) {
        next('/login');
    } else if (to.path === '/login' && token) {
        next('/dashboard');
    } else {
        next();
    }
});

export default router;