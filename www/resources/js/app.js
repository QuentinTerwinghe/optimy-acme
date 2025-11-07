import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';

// Import global components
import StatsCard from './components/Dashboard/StatsCard.vue';
import ActiveCampaignsList from './components/Dashboard/ActiveCampaignsList.vue';
import ActiveCampaignsCount from './components/Dashboard/ActiveCampaignsCount.vue';
import DashboardWrapper from './components/Dashboard/DashboardWrapper.vue';

// Create Pinia store
const pinia = createPinia();

// Create Vue app
const app = createApp(App);

// Use plugins
app.use(pinia);
app.use(router);

// Register global components
app.component('stats-card', StatsCard);
app.component('active-campaigns-list', ActiveCampaignsList);
app.component('active-campaigns-count', ActiveCampaignsCount);
app.component('dashboard-wrapper', DashboardWrapper);

// Mount the app
app.mount('#app');
