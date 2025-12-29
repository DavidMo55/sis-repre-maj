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
</script>


