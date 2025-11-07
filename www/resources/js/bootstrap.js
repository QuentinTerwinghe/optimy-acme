import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Setup CSRF token for all requests
const setupCsrfToken = () => {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    } else {
        console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-token');
    }
};

// Try to setup CSRF token immediately
setupCsrfToken();

// Also setup when DOM is loaded (for SPA)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupCsrfToken);
} else {
    setupCsrfToken();
}
