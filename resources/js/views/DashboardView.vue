<template>
    <div class="dashboard-page">
        <h2>👋 Bienvenido(a) al Sistema de Representantes</h2>
        <p class="summary-text">Resumen de tu actividad reciente.</p>

        <div class="kpi-grid">
            <div class="kpi-card">
                <i class="kpi-icon fas fa-user-tag red-dark-icon"></i>
                <p class="kpi-title">Prospectos Activos</p>
                <p class="kpi-value">24</p> </div>

            <div class="kpi-card">
                <i class="kpi-icon fas fa-handshake green-icon"></i>
                <p class="kpi-title">Clientes Registrados</p>
                <p class="kpi-value">8</p> </div>

            <div class="kpi-card">
                <i class="kpi-icon fas fa-truck orange-icon"></i>
                <p class="kpi-title">Pedidos Pendientes</p>
                <p class="kpi-value">3</p> </div>

            <div class="kpi-card">
                <i class="kpi-icon fas fa-calendar-alt purple-icon"></i>
                <p class="kpi-title">Próxima Visita</p>
                <p class="kpi-value-small">Colegio Siglo XXI - 10 Ene</p> </div>
        </div>

        <h3>Accesos Rápidos</h3>
        <div class="quick-links">
            <router-link to="/visitas/primera" class="quick-link-btn primary-btn">
                <i class="fas fa-plus-circle"></i> Nueva Primera Visita
            </router-link>
            <router-link to="/pedidos/nuevo" class="quick-link-btn success-btn">
                <i class="fas fa-cart-plus"></i> Ingresar Pedido
            </router-link>
            <router-link to="/gastos" class="quick-link-btn warning-btn">
                <i class="fas fa-cloud-upload-alt"></i> Subir Comprobante de Gasto
            </router-link>
        </div>
        
        </div>
</template>

<script>
export default {
    name: 'DashboardView',
};
</script>

