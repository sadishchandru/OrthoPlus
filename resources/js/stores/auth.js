import { defineStore } from 'pinia';
import axios from 'axios';
import { TOKEN_KEY } from '../bootstrap';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('ortho_user') || 'null'),
        token: localStorage.getItem(TOKEN_KEY) || null,
    }),

    getters: {
        isAuthenticated: (s) => !!s.token,
        roles: (s) => s.user?.roles || [],
        role: (s) => s.user?.role || null,
        isRoot: (s) => (s.user?.roles || []).includes('root'),
    },

    actions: {
        hasRole(...roles) {
            const mine = this.user?.roles || [];
            if (mine.includes('root')) return true;
            const wanted = roles.flat();
            return wanted.some((r) => mine.includes(r));
        },

        async login(username, password) {
            const { data } = await axios.post('/api/auth/login', { username, password });
            this.setSession(data.token, data.user);
            return data.user;
        },

        setSession(token, user) {
            this.token = token;
            this.user = user;
            localStorage.setItem(TOKEN_KEY, token);
            localStorage.setItem('ortho_user', JSON.stringify(user));
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        },

        async fetchMe() {
            try {
                const { data } = await axios.get('/api/auth/me');
                this.user = data;
                localStorage.setItem('ortho_user', JSON.stringify(data));
            } catch {
                this.logout();
            }
        },

        async logout() {
            try { await axios.post('/api/auth/logout'); } catch { /* ignore */ }
            this.token = null;
            this.user = null;
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem('ortho_user');
            delete axios.defaults.headers.common['Authorization'];
        },
    },
});
