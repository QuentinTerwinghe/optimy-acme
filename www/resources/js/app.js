import './bootstrap';
import { createApp } from 'vue';

// Import components
import StatsCard from './components/Dashboard/StatsCard.vue';
import LoginForm from './components/Auth/LoginForm.vue';
import ActiveCampaignsList from './components/Dashboard/ActiveCampaignsList.vue';
import ActiveCampaignsCount from './components/Dashboard/ActiveCampaignsCount.vue';
import DashboardWrapper from './components/Dashboard/DashboardWrapper.vue';

// Create Vue app
const app = createApp({
    methods: {
        handleLogout(event) {
            // Get the form element
            const form = event.target;

            // Submit the form natively (without Vue interfering)
            form.submit();
        }
    }
});

// Register global components
app.component('stats-card', StatsCard);
app.component('login-form', LoginForm);
app.component('active-campaigns-list', ActiveCampaignsList);
app.component('active-campaigns-count', ActiveCampaignsCount);
app.component('dashboard-wrapper', DashboardWrapper);

// Mount the app
app.mount('#app');
