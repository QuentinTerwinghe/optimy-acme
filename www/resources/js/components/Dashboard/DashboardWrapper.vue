<template>
    <div>
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <active-campaigns-count ref="campaignsCountRef" />

            <stats-card
                title="Active Projects"
                value="56"
                change="+8%"
                icon="document"
                color="green"
            />

            <stats-card
                title="Completed Tasks"
                value="892"
                change="+23%"
                icon="check"
                color="blue"
            />

            <stats-card
                title="Revenue"
                value="$45.2k"
                change="-2%"
                icon="chart"
                color="yellow"
            />
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
import StatsCard from './StatsCard.vue';

const campaignsCountRef = ref(null);

/**
 * Handle refresh event from ActiveCampaignsList
 * Refresh the campaigns count when the list is refreshed
 */
const handleRefresh = () => {
    if (campaignsCountRef.value) {
        campaignsCountRef.value.refresh();
    }
};
</script>
