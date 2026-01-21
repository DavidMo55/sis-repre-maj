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
 * Si el servidor responde 401, la sesión es inválida o expiró.
 */
instance.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Evitamos redirecciones si ya estamos en el login
            if (window.location.pathname !== '/login') {
                // Limpiar almacenamiento local por seguridad
                localStorage.removeItem('auth_token');
                localStorage.removeItem('session_start_time');
                localStorage.removeItem('user_data');
                
                // Redirección forzada para limpiar estado de la app
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export default instance;