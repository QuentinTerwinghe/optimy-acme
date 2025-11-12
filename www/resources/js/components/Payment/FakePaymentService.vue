<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-center">
                    <div class="text-center">
                        <h1 class="text-3xl font-bold text-gray-900">
                            Acme Corp Fake Payment Service
                        </h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Test Payment Gateway - Development Environment Only
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <!-- Payment Information -->
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Payment Details</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Payment ID:</span>
                            <span class="text-sm font-mono text-gray-900">{{ payment.id }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Amount to Pay:</span>
                            <span class="text-2xl font-bold text-indigo-600">
                                {{ formatCurrency(payment.amount, payment.currency) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Status:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                {{ payment.status }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Status Selection -->
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Select the status you want to apply on this request
                    </h3>

                    <!-- Status Buttons -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <button
                            @click="selectStatus('succeed')"
                            :class="[
                                'py-4 px-6 rounded-lg border-2 transition-all duration-200 font-semibold',
                                selectedStatus === 'succeed'
                                    ? 'border-green-600 bg-green-50 text-green-700'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-green-400 hover:bg-gray-50'
                            ]"
                            type="button"
                        >
                            <svg class="w-6 h-6 mx-auto mb-2" :class="selectedStatus === 'succeed' ? 'text-green-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Succeed
                        </button>

                        <button
                            @click="selectStatus('failed')"
                            :class="[
                                'py-4 px-6 rounded-lg border-2 transition-all duration-200 font-semibold',
                                selectedStatus === 'failed'
                                    ? 'border-red-600 bg-red-50 text-red-700'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-red-400 hover:bg-gray-50'
                            ]"
                            type="button"
                        >
                            <svg class="w-6 h-6 mx-auto mb-2" :class="selectedStatus === 'failed' ? 'text-red-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Failed
                        </button>

                        <button
                            @click="selectStatus('cancelled')"
                            :class="[
                                'py-4 px-6 rounded-lg border-2 transition-all duration-200 font-semibold',
                                selectedStatus === 'cancelled'
                                    ? 'border-gray-600 bg-gray-50 text-gray-700'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50'
                            ]"
                            type="button"
                        >
                            <svg class="w-6 h-6 mx-auto mb-2" :class="selectedStatus === 'cancelled' ? 'text-gray-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Cancelled
                        </button>
                    </div>

                    <!-- Failure Reason Selection (shown only when status is 'failed') -->
                    <div v-if="selectedStatus === 'failed'" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Select a failure reason:
                        </label>
                        <select
                            v-model="selectedFailureReason"
                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                        >
                            <option :value="null">-- Select a reason --</option>
                            <option
                                v-for="reason in failureReasons"
                                :key="reason.value"
                                :value="reason.value"
                            >
                                {{ reason.label }}
                            </option>
                        </select>
                        <p v-if="failureReasonError" class="mt-2 text-sm text-red-600">
                            {{ failureReasonError }}
                        </p>
                    </div>

                    <!-- Error Message -->
                    <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
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

                    <!-- Confirm Button -->
                    <div class="flex justify-end">
                        <button
                            @click="handleConfirm"
                            :disabled="!canConfirm || isProcessing"
                            :class="[
                                'inline-flex items-center px-6 py-3 rounded-lg font-semibold transition-colors duration-200',
                                canConfirm && !isProcessing
                                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer'
                                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                            ]"
                        >
                            <svg v-if="isProcessing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isProcessing ? 'Processing...' : 'Confirm Payment Status' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Info Notice -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-blue-800">
                            This is a test payment gateway for development purposes only.
                            In a real payment system, you would be redirected to an actual payment processor.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    payment: {
        type: Object,
        required: true,
    },
    failureReasons: {
        type: Array,
        required: true,
    },
    callbackUrl: {
        type: String,
        required: true,
    },
});

// State
const selectedStatus = ref(null);
const selectedFailureReason = ref(null);
const failureReasonError = ref('');
const errorMessage = ref('');
const isProcessing = ref(false);

/**
 * Select a payment status
 */
const selectStatus = (status) => {
    selectedStatus.value = status;
    failureReasonError.value = '';

    // Clear failure reason if status is not 'failed'
    if (status !== 'failed') {
        selectedFailureReason.value = null;
    }
};

/**
 * Check if user can confirm
 */
const canConfirm = computed(() => {
    if (!selectedStatus.value) {
        return false;
    }

    // If status is 'failed', a failure reason must be selected
    if (selectedStatus.value === 'failed' && !selectedFailureReason.value) {
        return false;
    }

    return true;
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
 * Generate a transaction ID for successful payments
 */
const generateTransactionId = () => {
    return 'FAKE_TXN_' + Date.now() + '_' + Math.random().toString(36).substring(2, 15);
};

/**
 * Get the failure reason details
 */
const getFailureReasonDetails = (reasonValue) => {
    const reason = props.failureReasons.find(r => r.value === reasonValue);
    return reason || { label: 'Unknown error', value: reasonValue };
};

/**
 * Build the callback payload based on the selected status
 */
const buildCallbackPayload = () => {
    const payload = {
        session_id: props.payment.payload?.session_id || null,
    };

    if (selectedStatus.value === 'succeed') {
        payload.status = 'success';
        payload.transaction_id = generateTransactionId();
    } else if (selectedStatus.value === 'failed') {
        const failureReason = getFailureReasonDetails(selectedFailureReason.value);
        payload.status = 'failed';
        payload.error_message = failureReason.label;
        payload.error_code = failureReason.value;
    } else if (selectedStatus.value === 'cancelled') {
        payload.status = 'failed';
        payload.error_message = 'Payment cancelled by user';
        payload.error_code = 'user_cancelled';
    }

    return payload;
};

/**
 * Navigate to callback URL with query parameters
 */
const navigateToCallback = (payload) => {
    // Build URL with query parameters
    const url = new URL(props.callbackUrl, window.location.origin);

    // Add payload as query parameters
    Object.keys(payload).forEach(key => {
        if (payload[key] !== null && payload[key] !== undefined) {
            url.searchParams.append(key, payload[key]);
        }
    });

    // Check if we're in a popup/modal (opened via window.open)
    if (window.opener && !window.opener.closed) {
        // We're in a popup - navigate the parent window and close this popup
        window.opener.location.href = url.toString();
        window.close();
    } else {
        // We're in the main window - navigate normally
        window.location.href = url.toString();
    }
};

/**
 * Handle confirm button click
 */
const handleConfirm = async () => {
    if (!canConfirm.value || isProcessing.value) {
        return;
    }

    // Validate failure reason if status is 'failed'
    if (selectedStatus.value === 'failed' && !selectedFailureReason.value) {
        failureReasonError.value = 'Please select a failure reason';
        return;
    }

    isProcessing.value = true;
    errorMessage.value = '';
    failureReasonError.value = '';

    try {
        // Build the callback payload
        const payload = buildCallbackPayload();

        console.log('Processing payment with status:', selectedStatus.value);
        console.log('Callback payload:', payload);

        // Small delay to show the loader (improve UX)
        await new Promise(resolve => setTimeout(resolve, 500));

        // Navigate to the callback URL (this will close/replace the current page)
        navigateToCallback(payload);
    } catch (error) {
        console.error('Payment processing error:', error);
        errorMessage.value = 'An error occurred while processing the payment status';
        isProcessing.value = false;
    }
};
</script>
