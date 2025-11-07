import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: window.Laravel?.user || null,
        initialized: false
    }),

    getters: {
        isAuthenticated: (state) => state.user !== null
    },

    actions: {
        async checkAuth() {
            try {
                const response = await axios.get('/api/user');
                this.user = response.data;
            } catch (error) {
                this.user = null;
            } finally {
                this.initialized = true;
            }
        },

        async login(credentials) {
            try {
                const response = await axios.post('/api/login', credentials);
                this.user = response.data.user;
                this.initialized = true; // Mark as initialized to prevent checkAuth from running
                return { success: true };
            } catch (error) {
                console.error('Login error:', error);
                console.error('Error response:', error.response);

                if (error.response?.status === 419) {
                    // CSRF token mismatch - reload to get fresh token
                    console.error('CSRF token mismatch - page will reload');
                    window.location.reload();
                    return {
                        success: false,
                        errors: { general: ['Session expired. Please try again.'] }
                    };
                }

                if (error.response?.data?.errors) {
                    return {
                        success: false,
                        errors: error.response.data.errors
                    };
                }

                return {
                    success: false,
                    errors: { email: ['Invalid credentials'] }
                };
            }
        },

        async logout() {
            try {
                await axios.post('/api/logout');
                this.user = null;
            } catch (error) {
                console.error('Logout error:', error);
                // Still clear user on error
                this.user = null;
            }
        },

        setUser(user) {
            this.user = user;
        }
    }
});
