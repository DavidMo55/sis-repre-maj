import axios from 'axios';

/**
 * Configuración base de la instancia de Axios
 */
const instance = axios.create({
    baseURL: '/api', 
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
});

/**
 * INTERCEPTOR DE PETICIÓN
 * Se asegura de obtener el token más reciente del localStorage antes de cada llamada.
 */
instance.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
}, error => {
    return Promise.reject(error);
});

/**
 * INTERCEPTOR DE RESPUESTA
 * Maneja errores globales de autenticación, exceptuando servicios externos específicos.
 */
instance.interceptors.response.use(
    response => response,
    error => {
        // 1. Identificar si el error es 401
        if (error.response && error.response.status === 401) {
            
            // 2. EXCEPCIÓN CRÍTICA: No expulsar al usuario si falla el Proxy de Dipomex
            // Esto permite que el error sea manejado localmente por el componente (silent fail)
            const isProxyUrl = error.config.url.includes('proxy/dipomex');
            
            if (!isProxyUrl) {
                // Solo cerramos sesión si no es una página pública y no es el proxy
                if (window.location.pathname !== '/login') {
                    console.error("Sesión invalidada por el servidor. Redirigiendo...");
                    
                    // Limpiar almacenamiento local
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('session_start_time');
                    localStorage.removeItem('user_data');
                    
                    // Redirección forzada
                    window.location.href = '/login';
                }
            } else {
                console.warn("Se detectó un error 401 en el Proxy externo, pero se mantuvo la sesión activa.");
            }
        }
        return Promise.reject(error);
    }
);

export default instance;