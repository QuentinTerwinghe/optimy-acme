import './bootstrap';
import { createApp } from 'vue';

// Import components
import StatsCard from './components/Dashboard/StatsCard.vue';

// Create Vue app
const app = createApp({});

// Register global components
app.component('stats-card', StatsCard);

// Mount the app
app.mount('#app');
