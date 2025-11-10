<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a
                        :href="dashboardUrl"
                        class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-indigo-600"
                    >
                        Dashboard
                    </a>
                </li>
                <li v-if="campaign.category">
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">
                            {{ campaign.category.name }}
                        </span>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 truncate max-w-xs">
                            {{ campaign.title }}
                        </span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Main Content (2/3 width) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Campaign Header -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <!-- Title and Status -->
                        <div class="flex items-start justify-between mb-4">
                            <h1 class="text-3xl font-bold text-gray-900">
                                {{ campaign.title }}
                            </h1>
                            <span
                                :class="getStatusClass(campaign.status)"
                                class="ml-4 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium whitespace-nowrap"
                            >
                                {{ campaign.status_label }}
                            </span>
                        </div>

                        <!-- Category and Tags -->
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            <span v-if="campaign.category" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                {{ campaign.category.name }}
                            </span>

                            <template v-if="campaign.tags && campaign.tags.length > 0">
                                <span
                                    v-for="tag in campaign.tags"
                                    :key="tag.id"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800"
                                >
                                    {{ tag.name }}
                                </span>
                            </template>
                        </div>

                        <!-- Date Range -->
                        <div class="flex items-center text-gray-600 mb-6">
                            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-sm">
                                <strong>Duration:</strong> {{ formatDate(campaign.start_date) }} - {{ formatDate(campaign.end_date) }}
                            </span>
                            <span v-if="campaign.days_remaining !== undefined" class="ml-4 text-sm font-medium text-indigo-600">
                                {{ campaign.days_remaining }} {{ campaign.days_remaining === 1 ? 'day' : 'days' }} remaining
                            </span>
                        </div>

                        <!-- Description -->
                        <div class="prose max-w-none">
                            <h2 class="text-xl font-semibold text-gray-900 mb-3">About this campaign</h2>
                            <p class="text-gray-700 whitespace-pre-line leading-relaxed">
                                {{ campaign.description || 'No description provided.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Campaign Details</h2>
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Campaign ID</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ campaign.id }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Currency</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ campaign.currency }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(campaign.creation_date) }}</dd>
                            </div>
                            <div v-if="campaign.update_date">
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(campaign.update_date) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Right Column - Sidebar (1/3 width) -->
            <div class="lg:col-span-1">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg sticky top-6">
                    <div class="p-6">
                        <!-- Progress Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Funding Progress</h3>

                            <!-- Amount Raised -->
                            <div class="mb-2">
                                <div class="flex items-baseline justify-between">
                                    <span class="text-3xl font-bold text-indigo-600">
                                        {{ formatCurrency(campaign.current_amount, campaign.currency) }}
                                    </span>
                                    <span class="text-sm text-gray-500">raised</span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div
                                        class="bg-indigo-600 h-3 rounded-full transition-all duration-500"
                                        :style="{width: Math.min(progressPercentage, 100) + '%'}"
                                    ></div>
                                </div>
                                <div class="mt-2 text-center">
                                    <span class="text-lg font-semibold text-gray-700">
                                        {{ progressPercentage }}%
                                    </span>
                                    <span class="text-sm text-gray-500">of goal</span>
                                </div>
                            </div>

                            <!-- Goal Amount -->
                            <div class="border-t border-gray-200 pt-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Goal Amount</span>
                                    <span class="text-lg font-bold text-gray-900">
                                        {{ formatCurrency(campaign.goal_amount, campaign.currency) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Creator Information -->
                        <div v-if="campaign.creator" class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Created by</h3>
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center">
                                        <span class="text-lg font-medium text-white">
                                            {{ getInitials(campaign.creator.name) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ campaign.creator.name }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ campaign.creator.email }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="border-t border-gray-200 pt-6 mt-6 space-y-3">
                            <button
                                :disabled="!canContribute"
                                :class="[
                                    'w-full font-semibold py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center',
                                    canContribute
                                        ? 'bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer'
                                        : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                ]"
                            >
                                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ contributeButtonText }}
                            </button>

                            <button
                                class="w-full bg-white hover:bg-gray-50 text-gray-700 font-semibold py-3 px-4 rounded-lg border border-gray-300 transition-colors duration-200 flex items-center justify-center"
                            >
                                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                                Share Campaign
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    campaign: {
        type: Object,
        required: true,
    },
    dashboardUrl: {
        type: String,
        required: true,
    },
});

/**
 * Calculate progress percentage
 */
const progressPercentage = computed(() => {
    if (!props.campaign.goal_amount || props.campaign.goal_amount <= 0) {
        return 0;
    }
    const percentage = (parseFloat(props.campaign.current_amount) / parseFloat(props.campaign.goal_amount)) * 100;
    return Math.round(percentage);
});

/**
 * Calculate days until campaign starts
 */
const daysUntilStart = computed(() => {
    if (!props.campaign.start_date) return 0;
    const startDate = new Date(props.campaign.start_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    startDate.setHours(0, 0, 0, 0);
    const diffTime = startDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return Math.max(0, diffDays);
});

/**
 * Check if campaign has started
 */
const hasStarted = computed(() => {
    if (!props.campaign.start_date) return true;
    const startDate = new Date(props.campaign.start_date);
    const today = new Date();
    return today >= startDate;
});

/**
 * Check if user can contribute to the campaign
 */
const canContribute = computed(() => {
    // Must be active status
    if (props.campaign.status !== 'active') {
        return false;
    }

    // Must have started or be starting today
    return hasStarted.value;
});

/**
 * Get text for contribute button
 */
const contributeButtonText = computed(() => {
    // Handle completed campaigns
    if (props.campaign.status === 'completed') {
        return 'Campaign Has Ended';
    }

    // Handle cancelled campaigns
    if (props.campaign.status === 'cancelled') {
        return 'Campaign Cancelled';
    }

    // Handle non-active campaigns
    if (props.campaign.status !== 'active') {
        return 'Campaign Not Active';
    }

    // Handle future campaigns (active but not yet started)
    if (!hasStarted.value && daysUntilStart.value > 0) {
        const days = daysUntilStart.value;
        return `Starts in ${days} ${days === 1 ? 'day' : 'days'}`;
    }

    return 'Contribute Now';
});

/**
 * Get status badge class based on campaign status
 */
const getStatusClass = (status) => {
    const classes = {
        'draft': 'bg-gray-100 text-gray-800',
        'waiting_for_validation': 'bg-yellow-100 text-yellow-800',
        'active': 'bg-green-100 text-green-800',
        'completed': 'bg-blue-100 text-blue-800',
        'cancelled': 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
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

/**
 * Format date for display
 */
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

/**
 * Format datetime for display
 */
const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

/**
 * Get initials from name
 */
const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length === 1) {
        return parts[0].charAt(0).toUpperCase();
    }
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
};
</script>

<style scoped>
.prose {
    max-width: 65ch;
}
</style>
