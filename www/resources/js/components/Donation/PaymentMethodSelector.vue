<template>
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-3">
            Select Payment Method
        </label>
        <div v-if="loading" class="text-center py-8">
            <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent"></div>
            <p class="mt-2 text-sm text-gray-600">Loading payment methods...</p>
        </div>
        <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ error }}</p>
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <button
                v-for="method in paymentMethods"
                :key="method.value"
                @click="selectPaymentMethod(method.value)"
                :class="[
                    'group relative p-6 rounded-lg border-2 transition-all duration-200 text-left',
                    selectedMethod === method.value
                        ? 'border-indigo-600 bg-indigo-50'
                        : 'border-gray-300 bg-white hover:border-indigo-400 hover:bg-gray-50'
                ]"
                type="button"
            >
                <!-- Selection indicator -->
                <div
                    v-if="selectedMethod === method.value"
                    class="absolute top-3 right-3 flex items-center justify-center h-6 w-6 rounded-full bg-indigo-600"
                >
                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <!-- Payment icon -->
                <div
                    :class="[
                        'mb-3 flex items-center justify-center h-12 w-12 rounded-lg transition-colors duration-200',
                        selectedMethod === method.value
                            ? 'bg-indigo-600'
                            : 'bg-gray-200 group-hover:bg-indigo-100'
                    ]"
                >
                    <svg
                        :class="[
                            'h-6 w-6',
                            selectedMethod === method.value
                                ? 'text-white'
                                : 'text-gray-600 group-hover:text-indigo-600'
                        ]"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>

                <!-- Payment method name -->
                <h3
                    :class="[
                        'text-base font-semibold mb-1',
                        selectedMethod === method.value
                            ? 'text-indigo-700'
                            : 'text-gray-900 group-hover:text-indigo-600'
                    ]"
                >
                    {{ method.label }}
                </h3>

                <!-- Payment method description -->
                <p class="text-xs text-gray-500">
                    {{ getPaymentMethodDescription(method.value) }}
                </p>
            </button>
        </div>
        <p v-if="!paymentMethods.length && !loading && !error" class="text-sm text-gray-500 text-center py-4">
            No payment methods available at this time.
        </p>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: null,
    },
    apiUrl: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['update:modelValue']);

// State
const paymentMethods = ref([]);
const selectedMethod = ref(props.modelValue);
const loading = ref(false);
const error = ref('');

/**
 * Fetch enabled payment methods from API
 */
const fetchPaymentMethods = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await fetch(props.apiUrl);

        if (!response.ok) {
            throw new Error('Failed to fetch payment methods');
        }

        const data = await response.json();
        paymentMethods.value = data.data || [];

        // Auto-select first method if only one available
        if (paymentMethods.value.length === 1 && !selectedMethod.value) {
            selectPaymentMethod(paymentMethods.value[0].value);
        }
    } catch (err) {
        error.value = err.message || 'Failed to load payment methods. Please try again.';
        console.error('Error fetching payment methods:', err);
    } finally {
        loading.value = false;
    }
};

/**
 * Select a payment method
 */
const selectPaymentMethod = (value) => {
    selectedMethod.value = value;
    emit('update:modelValue', value);
};

/**
 * Get payment method description
 */
const getPaymentMethodDescription = (value) => {
    const descriptions = {
        'fake': 'Test payment for development',
        'paypal': 'Pay securely with PayPal',
        'credit_card': 'Pay with credit or debit card',
    };
    return descriptions[value] || 'Secure payment method';
};

/**
 * Watch for external changes to modelValue
 */
watch(() => props.modelValue, (newValue) => {
    selectedMethod.value = newValue;
});

/**
 * Fetch payment methods on component mount
 */
onMounted(() => {
    fetchPaymentMethods();
});
</script>
