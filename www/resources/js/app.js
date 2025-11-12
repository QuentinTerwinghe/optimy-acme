import './bootstrap';
import { createApp } from 'vue';

// Import components
import StatsCard from './components/Dashboard/StatsCard.vue';
import LoginForm from './components/Auth/LoginForm.vue';
import ActiveCampaignsList from './components/Dashboard/ActiveCampaignsList.vue';
import ActiveCampaignsCount from './components/Dashboard/ActiveCampaignsCount.vue';
import DashboardWrapper from './components/Dashboard/DashboardWrapper.vue';
import CampaignCreateForm from './components/Campaign/CampaignCreateForm.vue';
import CampaignEditForm from './components/Campaign/CampaignEditForm.vue';
import CampaignManageList from './components/Campaign/CampaignManageList.vue';
import CampaignShow from './components/Campaign/CampaignShow.vue';
import TagInput from './components/Common/TagInput.vue';
import DonationForm from './components/Donation/DonationForm.vue';
import PaymentMethodSelector from './components/Donation/PaymentMethodSelector.vue';
import FakePaymentService from './components/Payment/FakePaymentService.vue';
import PaymentSuccess from './components/Payment/PaymentSuccess.vue';
import PaymentFailure from './components/Payment/PaymentFailure.vue';

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
app.component('campaign-create-form', CampaignCreateForm);
app.component('campaign-edit-form', CampaignEditForm);
app.component('campaign-manage-list', CampaignManageList);
app.component('campaign-show', CampaignShow);
app.component('tag-input', TagInput);
app.component('donation-form', DonationForm);
app.component('payment-method-selector', PaymentMethodSelector);
app.component('fake-payment-service', FakePaymentService);
app.component('payment-success', PaymentSuccess);
app.component('payment-failure', PaymentFailure);

// Mount the app
app.mount('#app');
