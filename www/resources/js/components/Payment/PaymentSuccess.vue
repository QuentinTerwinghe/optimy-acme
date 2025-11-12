<template>
    <div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full">
            <!-- Success Icon -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
                    <svg class="w-12 h-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-3">
                    Payment Successful!
                </h1>
                <p class="text-xl text-gray-600">
                    Thank you for your generous donation
                </p>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden mb-6">
                <!-- Payment Details -->
                <div class="p-8 border-b border-gray-200">
                    <div class="text-center mb-6">
                        <p class="text-sm font-medium text-gray-600 mb-2">Donation Amount</p>
                        <p class="text-5xl font-bold text-green-600">
                            {{ formatCurrency(payment.amount, payment.currency) }}
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-3 border-t border-gray-100">
                            <span class="text-sm font-medium text-gray-600">Transaction ID:</span>
                            <span class="text-sm font-mono text-gray-900">{{ payment.transaction_id }}</span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-t border-gray-100">
                            <span class="text-sm font-medium text-gray-600">Date:</span>
                            <span class="text-sm text-gray-900">{{ formatDate(payment.completed_at) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-t border-gray-100">
                            <span class="text-sm font-medium text-gray-600">Donor:</span>
                            <span class="text-sm text-gray-900">{{ user.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Campaign Information -->
                <div class="p-8 bg-gradient-to-br from-indigo-50 to-blue-50">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        Your Contribution To:
                    </h2>
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <h3 class="text-2xl font-bold text-indigo-900 mb-3">
                            {{ campaign.title }}
                        </h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            {{ campaign.description }}
                        </p>
                        <a
                            :href="`/campaigns/${campaign.id}`"
                            class="inline-flex items-center justify-center w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors duration-200"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            View Campaign Details
                        </a>
                    </div>
                </div>
            </div>

            <!-- Thank You Message -->
            <div class="bg-white rounded-xl p-6 shadow-lg text-center mb-6">
                <p class="text-lg text-gray-700 leading-relaxed">
                    <span class="font-semibold text-gray-900">Thank you for making a difference!</span>
                    <br>
                    Your support helps us achieve our mission and creates positive impact in our community.
                    You will receive a confirmation email shortly with the donation receipt.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a
                    href="/dashboard"
                    class="inline-flex items-center justify-center px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-lg border-2 border-gray-300 transition-colors duration-200"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Browse more campaigns
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    payment: {
        type: Object,
        required: true,
    },
    donation: {
        type: Object,
        required: true,
    },
    campaign: {
        type: Object,
        required: true,
    },
    user: {
        type: Object,
        required: true,
    },
});

/**
 * Get currency symbol
 */
const getCurrencySymbol = (currency) => {
    const symbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
    };
    return symbols[currency] || currency;
};

/**
 * Format currency with symbol
 */
const formatCurrency = (amount, currency) => {
    const numAmount = parseFloat(amount);
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numAmount);

    return getCurrencySymbol(currency) + formatted;
};

/**
 * Format date in a readable format
 */
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';

    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};
</script>
