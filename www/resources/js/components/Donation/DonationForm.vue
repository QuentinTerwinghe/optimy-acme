<template>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                <li>
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                        <a
                            :href="campaignUrl"
                            class="ml-1 text-sm font-medium text-gray-700 hover:text-indigo-600 md:ml-2"
                        >
                            {{ campaign.title }}
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Make a Donation</span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <!-- Campaign Info Section -->
            <div class="p-6 border-b border-gray-200 bg-gray-50">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">
                            Support: {{ campaign.title }}
                        </h1>
                        <p class="text-gray-600 text-sm leading-relaxed" v-if="campaign.description">
                            {{ campaign.description }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-3">
                            <div class="text-center">
                                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Goal</div>
                                <div class="text-lg font-bold text-indigo-600">
                                    {{ formatCurrency(campaign.goal_amount, campaign.currency) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Donation Form Section -->
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Choose Your Donation Amount</h2>

                <!-- Quick Amount Buttons -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Quick Select
                    </label>
                    <div class="grid grid-cols-5 gap-3">
                        <button
                            v-for="amount in quickAmounts"
                            :key="amount"
                            @click="selectQuickAmount(amount)"
                            :class="[
                                'py-3 px-4 rounded-lg border-2 transition-all duration-200 font-semibold',
                                selectedQuickAmount === amount
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-indigo-400 hover:bg-gray-50'
                            ]"
                            type="button"
                        >
                            {{ formatCurrencySimple(amount, campaign.currency) }}
                        </button>
                    </div>
                </div>

                <!-- Custom Amount Input -->
                <div class="mb-6">
                    <label for="customAmount" class="block text-sm font-medium text-gray-700 mb-2">
                        Or Enter Custom Amount
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-lg">
                                {{ getCurrencySymbol(campaign.currency) }}
                            </span>
                        </div>
                        <input
                            id="customAmount"
                            v-model="customAmount"
                            @input="onCustomAmountInput"
                            @focus="clearQuickSelection"
                            type="text"
                            inputmode="decimal"
                            placeholder="0.00"
                            class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-lg"
                        />
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Enter any amount you wish to donate
                    </p>
                    <p v-if="amountError" class="mt-2 text-sm text-red-600">
                        {{ amountError }}
                    </p>
                </div>

                <!-- Selected Amount Display -->
                <div v-if="finalAmount > 0" class="mb-6 p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Your donation amount:</span>
                        <span class="text-2xl font-bold text-indigo-600">
                            {{ formatCurrency(finalAmount, campaign.currency) }}
                        </span>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <payment-method-selector
                    v-model="selectedPaymentMethod"
                    :api-url="paymentMethodsApiUrl"
                />

                <!-- Error Message -->
                <div v-if="errorMessage" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-red-800">{{ errorMessage }}</p>
                        </div>
                        <button @click="errorMessage = ''" class="ml-auto">
                            <svg class="h-5 w-5 text-red-400 hover:text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <a
                        :href="campaignUrl"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                    >
                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Campaign
                    </a>

                    <button
                        type="button"
                        @click="handleContinueToPayment"
                        :disabled="!canProceed || isProcessing"
                        :class="[
                            'inline-flex items-center px-6 py-3 rounded-lg font-semibold transition-colors duration-200',
                            canProceed && !isProcessing
                                ? 'bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer'
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                        ]"
                    >
                        <svg v-if="isProcessing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isProcessing ? 'Processing...' : 'Continue to Payment' }}
                        <svg v-if="!isProcessing" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import PaymentMethodSelector from './PaymentMethodSelector.vue';

const props = defineProps({
    campaign: {
        type: Object,
        required: true,
    },
    quickAmounts: {
        type: Array,
        required: true,
        default: () => [5, 10, 20, 50, 100],
    },
    dashboardUrl: {
        type: String,
        required: true,
    },
    campaignUrl: {
        type: String,
        required: true,
    },
    paymentMethodsApiUrl: {
        type: String,
        default: '/api/payment-methods',
    },
});

// State
const selectedQuickAmount = ref(null);
const customAmount = ref('');
const amountError = ref('');
const selectedPaymentMethod = ref(null);
const isProcessing = ref(false);
const errorMessage = ref('');

/**
 * Select a quick amount button
 */
const selectQuickAmount = (amount) => {
    selectedQuickAmount.value = amount;
    customAmount.value = '';
    amountError.value = '';
};

/**
 * Clear quick selection when user focuses on custom amount input
 */
const clearQuickSelection = () => {
    selectedQuickAmount.value = null;
};

/**
 * Handle custom amount input
 */
const onCustomAmountInput = (event) => {
    let value = event.target.value;

    // Remove any non-numeric characters except decimal point
    value = value.replace(/[^\d.]/g, '');

    // Ensure only one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }

    // Limit to 2 decimal places
    if (parts.length === 2 && parts[1].length > 2) {
        value = parts[0] + '.' + parts[1].substring(0, 2);
    }

    customAmount.value = value;

    // Validate amount
    const numValue = parseFloat(value);
    if (value && (isNaN(numValue) || numValue <= 0)) {
        amountError.value = 'Please enter a valid amount greater than 0';
    } else {
        amountError.value = '';
    }
};

/**
 * Get the final donation amount
 */
const finalAmount = computed(() => {
    if (selectedQuickAmount.value) {
        return selectedQuickAmount.value;
    }

    if (customAmount.value) {
        const amount = parseFloat(customAmount.value);
        return isNaN(amount) ? 0 : amount;
    }

    return 0;
});

/**
 * Check if user can proceed to payment
 */
const canProceed = computed(() => {
    return finalAmount.value > 0 && !amountError.value && selectedPaymentMethod.value !== null;
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
 * Format currency for buttons (no decimals)
 */
const formatCurrencySimple = (amount, currency) => {
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);

    return getCurrencySymbol(currency) + formatted;
};

/**
 * Handle continue to payment button click
 */
const handleContinueToPayment = async () => {
    if (!canProceed.value || isProcessing.value) {
        return;
    }

    isProcessing.value = true;
    errorMessage.value = '';

    try {
        const response = await window.axios.post('/api/payments/initialize', {
            campaign_id: props.campaign.id,
            amount: finalAmount.value,
            payment_method: selectedPaymentMethod.value,
            metadata: {
                source: 'web',
                campaign_title: props.campaign.title,
            },
        });

        if (response.data.success) {
            const { donation, payment } = response.data.data;

            // Log success for debugging
            console.log('Payment initialized successfully:', { donation, payment });

            // TODO: Redirect to next step of payment processing
            // For now, show success message
            alert(`Payment initialized! Donation ID: ${donation.id}, Payment ID: ${payment.id}`);

            // In the future, you would redirect to a payment processing page:
            // window.location.href = `/payments/${payment.id}/process`;
        } else {
            errorMessage.value = response.data.message || 'Failed to initialize payment';
        }
    } catch (error) {
        console.error('Payment initialization error:', error);

        if (error.response) {
            // Server responded with an error status
            if (error.response.status === 422) {
                // Validation errors
                const errors = error.response.data.errors;
                if (errors) {
                    const errorMessages = Object.values(errors).flat();
                    errorMessage.value = errorMessages.join(', ');
                } else {
                    errorMessage.value = error.response.data.message || 'Validation failed';
                }
            } else if (error.response.status === 401) {
                errorMessage.value = 'Please log in to continue with your donation';
            } else {
                errorMessage.value = error.response.data.message || 'An error occurred while processing your request';
            }
        } else if (error.request) {
            // Request was made but no response received
            errorMessage.value = 'No response from server. Please check your internet connection';
        } else {
            // Something else happened
            errorMessage.value = 'An unexpected error occurred';
        }
    } finally {
        isProcessing.value = false;
    }
};
</script>
