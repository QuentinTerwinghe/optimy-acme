<template>
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Active Campaigns</h2>
                <button
                    @click="refreshCampaigns"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Refresh campaigns list"
                >
                    <svg
                        :class="{'animate-spin': loading}"
                        class="h-4 w-4 mr-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading && campaigns.length === 0" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-sm text-gray-600">Loading campaigns...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error loading campaigns</h3>
                        <p class="mt-1 text-sm text-red-700">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="campaigns.length === 0" class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No active campaigns</h3>
                <p class="mt-1 text-sm text-gray-500">There are currently no active campaigns.</p>
            </div>

            <!-- Campaigns List -->
            <div v-else class="space-y-4">
                <div
                    v-for="campaign in campaigns"
                    :key="campaign.id"
                    class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition-colors"
                >
                    <!-- Campaign Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-semibold text-gray-900 truncate">
                                {{ campaign.title }}
                            </h3>
                            <p v-if="campaign.description" class="mt-1 text-sm text-gray-600 line-clamp-2">
                                {{ campaign.description }}
                            </p>
                        </div>
                        <span
                            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                        >
                            {{ campaign.status_label }}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-3">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                            <span class="font-medium">
                                {{ formatCurrency(campaign.current_amount, campaign.currency) }} raised
                            </span>
                            <span>{{ campaign.progress_percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="bg-indigo-600 h-2 rounded-full transition-all"
                                :style="{width: campaign.progress_percentage + '%'}"
                            ></div>
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            Goal: {{ formatCurrency(campaign.goal_amount, campaign.currency) }}
                        </div>
                    </div>

                    <!-- Campaign Footer -->
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center text-gray-500">
                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Ends {{ campaign.end_date_formatted }}</span>
                        </div>
                        <div class="text-indigo-600 font-medium">
                            {{ campaign.days_remaining }} {{ campaign.days_remaining === 1 ? 'day' : 'days' }} left
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

// State
const campaigns = ref([]);
const loading = ref(false);
const error = ref(null);

/**
 * Fetch active campaigns from API
 */
const fetchCampaigns = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get('/api/campaigns/active');
        campaigns.value = response.data.data || [];
    } catch (err) {
        console.error('Error fetching campaigns:', err);
        error.value = err.response?.data?.message || 'Failed to load campaigns. Please try again.';
    } finally {
        loading.value = false;
    }
};

/**
 * Refresh campaigns list
 */
const refreshCampaigns = () => {
    fetchCampaigns();
};

/**
 * Format currency value
 */
const formatCurrency = (amount, currency) => {
    const numAmount = parseFloat(amount);
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numAmount);

    const symbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
    };

    return (symbols[currency] || currency) + formatted;
};

// Load campaigns on component mount
onMounted(() => {
    fetchCampaigns();
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
