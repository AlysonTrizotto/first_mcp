import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configurar baseURL dinamicamente
if (typeof window !== 'undefined') {
    window.axios.defaults.baseURL = window.location.origin;
}
