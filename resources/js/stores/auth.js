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
        pageAccess: (s) => s.user?.page_access || [],
    },

    actions: {
        hasRole(...roles) {
            const mine = this.user?.roles || [];
            if (mine.includes('root')) return true;
            const wanted = roles.flat();
            return wanted.some((r) => mine.includes(r));
        },

        // Page-level access from role page_access. '*' = everything (root).
        canAccess(page) {
            const pa = this.user?.page_access;
            // Legacy/unrefreshed session (no page_access yet) — don't lock out; fetchMe() will refresh.
            if (pa == null) return true;
            return pa.includes('*') || pa.includes(page);
        },

        // First page this user may land on (post-login), based on page_access.
        firstAccessiblePath() {
            const order = [
                ['dashboard', '/'], ['patients', '/patients'], ['appointments', '/appointments'],
                ['pharmacy', '/pharmacy'], ['inventory', '/inventory'],
                ['direct-doctor', '/doctor-direct'], ['settings', '/settings'],
            ];
            const hit = order.find(([p]) => this.canAccess(p));
            return hit ? hit[1] : '/unauthorized';
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
