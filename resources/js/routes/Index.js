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
import SeguimientoVisita from '../views/SeguimientoVisitaView.vue';
import SeguimientoID from '../views/VisitaIDView.vue';
import GastosCreateView from '../views/GastosCreateView.vue';
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
                path: 'visita-seguimiento',
                name: 'VisitaSeguimiento',
                component: SeguimientoVisita
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
            }

        ]
    },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' } // Captura cualquier ruta inexistente
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(authGuard);

export default router;