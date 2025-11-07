import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Import views
import LoginView from '../views/LoginView.vue';
import DashboardView from '../views/DashboardView.vue';
import ForgotPasswordView from '../views/ForgotPasswordView.vue';
import ResetPasswordView from '../views/ResetPasswordView.vue';

const routes = [
    {
        path: '/',
        redirect: '/dashboard'
    },
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { requiresGuest: true }
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: ForgotPasswordView,
        meta: { requiresGuest: true }
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: ResetPasswordView,
        meta: { requiresGuest: true }
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: DashboardView,
        meta: { requiresAuth: true }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Initialize auth state if not already done
    if (!authStore.initialized) {
        await authStore.checkAuth();
    }

    const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
    const requiresGuest = to.matched.some(record => record.meta.requiresGuest);

    if (requiresAuth && !authStore.isAuthenticated) {
        // Redirect to login if route requires auth and user is not authenticated
        next({ name: 'login' });
    } else if (requiresGuest && authStore.isAuthenticated) {
        // Redirect to dashboard if route requires guest and user is authenticated
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
