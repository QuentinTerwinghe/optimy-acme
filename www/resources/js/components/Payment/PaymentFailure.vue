<template>
    <div class="min-h-screen bg-gradient-to-br from-red-50 to-orange-50 flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full">
            <!-- Failure Icon -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 mb-6">
                    <svg class="w-12 h-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-3">
                    Payment Failed
                </h1>
                <p class="text-xl text-gray-600">
                    We couldn't process your donation
                </p>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden mb-6">
                <!-- Error Details -->
                <div class="p-8 border-b border-gray-200">
                    <div class="mb-6">
                        <div class="flex items-start p-4 bg-red-50 border border-red-200 rounded-lg">
                            <svg class="w-6 h-6 text-red-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div class="ml-4 flex-1">
                                <h3 class="text-sm font-semibold text-red-900 mb-1">Error Details</h3>
                                <p class="text-sm text-red-800">{{ payment.error_message || 'An unknown error occurred during payment processing.' }}</p>
                                <p v-if="payment.error_code" class="text-xs text-red-600 mt-2 font-mono">
                                    Error Code: {{ payment.error_code }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-3 border-t border-gray-100">
                            <span class="text-sm font-medium text-gray-600">Attempted Amount:</span>
                            <span class="text-lg font-semibold text-gray-900">
                                {{ formatCurrency(payment.amount, payment.currency) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-t border-gray-100">
                            <span class="text-sm font-medium text-gray-600">Date:</span>
                            <span class="text-sm text-gray-900">{{ formatDate(payment.failed_at) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-t border-gray-100">
                            <span class="text-sm font-medium text-gray-600">Donor:</span>
                            <span class="text-sm text-gray-900">{{ user.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Campaign Information -->
                <div class="p-8 bg-gradient-to-br from-gray-50 to-blue-50">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        You Were Trying To Support:
                    </h2>
                    <div class="bg-white rounded-lg p-6 shadow-sm mb-6">
                        <h3 class="text-2xl font-bold text-indigo-900 mb-3">
                            {{ campaign.title }}
                        </h3>
                        <p class="text-gray-600 leading-relaxed">
                            {{ campaign.description }}
                        </p>
                    </div>

                    <!-- Retry Button -->
                    <a
                        :href="`/campaigns/${campaign.id}/donate`"
                        class="inline-flex items-center justify-center w-full px-6 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Try Again
                    </a>
                </div>
            </div>

            <!-- What Happened Message -->
            <div class="bg-white rounded-xl p-6 shadow-lg mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">What happened?</h3>
                <div class="space-y-3 text-sm text-gray-700">
                    <p class="leading-relaxed">
                        Your payment couldn't be completed at this time. This could be due to several reasons:
                    </p>
                    <ul class="list-disc list-inside space-y-2 ml-2">
                        <li>Insufficient funds in your account</li>
                        <li>Payment method declined by your bank</li>
                        <li>Incorrect payment details</li>
                        <li>Technical issue with the payment processor</li>
                    </ul>
                    <p class="leading-relaxed mt-4">
                        <span class="font-semibold">Don't worry!</span> No charges were made to your account.
                        You can try again by clicking the "Try Again" button above.
                    </p>
                </div>
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
                    Go to Dashboard
                </a>
                <a
                    href="/campaigns"
                    class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors duration-200"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Browse Other Campaigns
                </a>
            </div>

            <!-- Contact Support -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Need help?
                    <a href="/dashboard" class="text-indigo-600 hover:text-indigo-800 font-medium">Contact Support</a>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
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
