import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['X-CSRF-TOKEN'] =
    document.querySelector('meta[name="csrf-token"]')?.content ?? '';
window.axios.defaults.baseURL = window.location.origin;

export const TOKEN_KEY = 'ortho_token';

// Attach bearer token to every request
const saved = localStorage.getItem(TOKEN_KEY);
if (saved) window.axios.defaults.headers.common['Authorization'] = `Bearer ${saved}`;

window.axios.interceptors.request.use((config) => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token) config.headers['Authorization'] = `Bearer ${token}`;
    return config;
});

// On 401, drop token and bounce to login (avoid loop on the login call itself)
window.axios.interceptors.response.use(
    (r) => r,
    (error) => {
        if (error.response?.status === 401 && !error.config?.url?.includes('/auth/login')) {
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem('ortho_user');
            localStorage.removeItem('ortho_module');
            localStorage.removeItem('module');
            delete window.axios.defaults.headers.common['Authorization'];
            const loginPath = window.location.pathname.startsWith('/hospital') ? '/hospital/login' : '/clinic/login';
            if (window.location.pathname !== loginPath) {
                window.location.href = loginPath;
            }
        }
        return Promise.reject(error);
    }
);
