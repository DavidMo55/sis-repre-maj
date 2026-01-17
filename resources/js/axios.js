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


instance.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
}, error => {
    return Promise.reject(error);
});


instance.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {

            localStorage.removeItem('auth_token');
            localStorage.removeItem('session_start_time');
            localStorage.removeItem('user_data');
            
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default instance;