<template>
    <div>
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <active-campaigns-count ref="campaignsCountRef" />

            <total-funds-raised ref="fundsRaisedRef" />

            <completed-campaigns-count ref="completedCountRef" />

            <fundraising-progress ref="progressRef" />
        </div>

        <!-- Additional Content Area -->
        <div>
            <!-- Active Campaigns - Full Width -->
            <active-campaigns-list @refresh="handleRefresh" />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import ActiveCampaignsCount from './ActiveCampaignsCount.vue';
import ActiveCampaignsList from './ActiveCampaignsList.vue';
import TotalFundsRaised from './TotalFundsRaised.vue';
import CompletedCampaignsCount from './CompletedCampaignsCount.vue';
import FundraisingProgress from './FundraisingProgress.vue';

const campaignsCountRef = ref(null);
const fundsRaisedRef = ref(null);
const completedCountRef = ref(null);
const progressRef = ref(null);

/**
 * Handle refresh event from ActiveCampaignsList
 * Refresh all statistics when the list is refreshed
 */
const handleRefresh = () => {
    if (campaignsCountRef.value) {
        campaignsCountRef.value.refresh();
    }
    if (fundsRaisedRef.value) {
        fundsRaisedRef.value.refresh();
    }
    if (completedCountRef.value) {
        completedCountRef.value.refresh();
    }
    if (progressRef.value) {
        progressRef.value.refresh();
    }
};
</script>
