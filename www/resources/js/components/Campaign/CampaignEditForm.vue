<template>
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Edit Campaign</h1>
                    <p class="mt-2 text-sm text-gray-700">Update the details below to modify your campaign.</p>
                </div>
                <div class="flex gap-2">
                    <a :href="campaignsUrl" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Campaigns
                    </a>
                </div>
            </div>
        </div>

        <!-- Campaign Form -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                <!-- Basic Information Section -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>

                    <!-- Title -->
                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Campaign Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="title"
                            v-model="form.title"
                            required
                            :disabled="isSubmitting"
                            :class="[
                                'appearance-none block w-full px-3 py-2 border rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors',
                                errors.title ? 'border-red-300' : 'border-gray-300',
                                isSubmitting ? 'bg-gray-50 cursor-not-allowed' : ''
                            ]"
                            placeholder="Enter campaign title"
                            @input="clearError('title')"
                        >
                        <p v-if="errors.title" class="mt-2 text-sm text-red-600">
                            {{ errors.title }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            :disabled="isSubmitting"
                            :class="[
                                'appearance-none block w-full px-3 py-2 border rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors',
                                errors.description ? 'border-red-300' : 'border-gray-300',
                                isSubmitting ? 'bg-gray-50 cursor-not-allowed' : ''
                            ]"
                            placeholder="Describe your campaign goals and details"
                            @input="clearError('description')"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500">Provide a detailed description of your campaign objectives.</p>
                        <p v-if="errors.description" class="mt-2 text-sm text-red-600">
                            {{ errors.description }}
                        </p>
                    </div>

                    <!-- Category -->
                    <div class="mb-4">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Category
                        </label>
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            :disabled="isSubmitting"
                            :class="[
                                'appearance-none block w-full px-3 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors',
                                errors.category_id ? 'border-red-300' : 'border-gray-300',
                                isSubmitting ? 'bg-gray-50 cursor-not-allowed' : ''
                            ]"
                            @change="clearError('category_id')"
                        >
                            <option value="">Select a category (optional)</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <p v-if="errors.category_id" class="mt-2 text-sm text-red-600">
                            {{ errors.category_id }}
                        </p>
                    </div>

                    <!-- Tags -->
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">
                            Tags
                        </label>
                        <TagInput
                            v-model="form.tags"
                            :available-tags="tags"
                            :disabled="isSubmitting"
                            :error="errors.tags"
                            placeholder="Type to search or add tags..."
                            helper-text="Press Enter or comma to add a tag. You can add new tags that don't exist yet."
                            @update:modelValue="clearError('tags')"
                        />
                    </div>
                </div>

                <!-- Financial Details Section -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Financial Details</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Goal Amount -->
                        <div>
                            <label for="goal_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Goal Amount <span class="text-gray-500 text-xs">(required for submission)</span>
                            </label>
                            <input
                                type="number"
                                id="goal_amount"
                                v-model="form.goal_amount"
                                step="0.01"
                                min="0"
                                :disabled="isSubmitting"
                                :class="[
                                    'appearance-none block w-full px-3 py-2 border rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors',
                                    errors.goal_amount ? 'border-red-300' : 'border-gray-300',
                                    isSubmitting ? 'bg-gray-50 cursor-not-allowed' : ''
                                ]"
                                placeholder="0.00"
                                @input="clearError('goal_amount')"
                            >
                            <p v-if="errors.goal_amount" class="mt-2 text-sm text-red-600">
                                {{ errors.goal_amount }}
                            </p>
                        </div>

                        <!-- Currency -->
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                                Currency <span class="text-gray-500 text-xs">(required for submission)</span>
                            </label>
                            <select
                                id="currency"
                                v-model="form.currency"
                                :disabled="isSubmitting"
                                :class="[
                                    'appearance-none block w-full px-3 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors',
                                    errors.currency ? 'border-red-300' : 'border-gray-300',
                                    isSubmitting ? 'bg-gray-50 cursor-not-allowed' : ''
                                ]"
                                @change="clearError('currency')"
                            >
                                <option value="">Select currency</option>
                                <option
                                    v-for="currency in currencies"
                                    :key="currency.value"
                                    :value="currency.value"
                                >
                                    {{ currency.symbol }} - {{ currency.label }}
                                </option>
                            </select>
                            <p v-if="errors.currency" class="mt-2 text-sm text-red-600">
                                {{ errors.currency }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Timeline Section -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Start Date <span class="text-gray-500 text-xs">(required for submission)</span>
                            </label>
                            <input
                                ref="startDateInput"
                                type="date"
                                id="start_date"
                                v-model="form.start_date"
                                :disabled="isSubmitting"
                                :class="[
                                    'appearance-none block w-full px-3 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors cursor-pointer',
                                    errors.start_date ? 'border-red-300' : 'border-gray-300',
                                    isSubmitting ? 'bg-gray-50 cursor-not-allowed' : ''
                                ]"
                                @input="clearError('start_date')"
                                @click="openDatePicker($event)"
                            >
                            <p v-if="errors.start_date" class="mt-2 text-sm text-red-600">
                                {{ errors.start_date }}
                            </p>
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                End Date <span class="text-gray-500 text-xs">(required for submission)</span>
                            </label>
                            <input
                                ref="endDateInput"
                                type="date"
                                id="end_date"
                                v-model="form.end_date"
                                :min="minEndDate"
                                :disabled="isSubmitting"
                                :class="[
                                    'appearance-none block w-full px-3 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors cursor-pointer',
                                    errors.end_date ? 'border-red-300' : 'border-gray-300',
                                    isSubmitting ? 'bg-gray-50 cursor-not-allowed' : ''
                                ]"
                                @input="clearError('end_date')"
                                @click="openDatePicker($event)"
                            >
                            <p v-if="errors.end_date" class="mt-2 text-sm text-red-600">
                                {{ errors.end_date }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- General Error Message -->
                <div v-if="errors.general" class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">
                                {{ errors.general }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
                    <a
                        :href="campaignsUrl"
                        class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200"
                    >
                        Cancel
                    </a>

                    <!-- Save as Draft Button - Only show if status is Draft -->
                    <button
                        v-if="isDraftStatus"
                        type="button"
                        @click="handleSubmit('draft')"
                        :disabled="isSubmitting || !canSaveAsDraft"
                        :class="[
                            'px-6 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 shadow-sm',
                            isSubmitting || !canSaveAsDraft
                                ? 'bg-gray-400 text-white cursor-not-allowed'
                                : 'bg-gray-600 text-white hover:bg-gray-700 hover:shadow-md'
                        ]"
                    >
                        <span v-if="!isSubmitting || submitType !== 'draft'">Save as Draft</span>
                        <span v-else class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>

                    <!-- Save Button - Always show -->
                    <button
                        type="button"
                        @click="handleSubmit('waiting_for_validation')"
                        :disabled="isSubmitting || !canSaveForValidation"
                        :class="[
                            'px-6 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 shadow-sm',
                            isSubmitting || !canSaveForValidation
                                ? 'bg-indigo-400 text-white cursor-not-allowed'
                                : 'bg-indigo-600 text-white hover:bg-indigo-700 hover:shadow-md'
                        ]"
                    >
                        <span v-if="!isSubmitting || submitType !== 'waiting_for_validation'">Save</span>
                        <span v-else class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>

                    <!-- Validate Button - Only show if user has manageAllCampaigns permission AND status is waiting_for_validation -->
                    <button
                        v-if="showValidateButton"
                        type="button"
                        @click="handleSubmit('active')"
                        :disabled="isSubmitting || !canSaveForValidation"
                        :class="[
                            'px-6 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 shadow-sm',
                            isSubmitting || !canSaveForValidation
                                ? 'bg-green-400 text-white cursor-not-allowed'
                                : 'bg-green-600 text-white hover:bg-green-700 hover:shadow-md'
                        ]"
                    >
                        <span v-if="!isSubmitting || submitType !== 'active'">Validate</span>
                        <span v-else class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Validating...
                        </span>
                    </button>

                    <!-- Reject Button - Only show if user has manageAllCampaigns permission AND status is waiting_for_validation -->
                    <button
                        v-if="showRejectButton"
                        type="button"
                        @click="handleSubmit('rejected')"
                        :disabled="isSubmitting"
                        :class="[
                            'px-6 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 shadow-sm',
                            isSubmitting
                                ? 'bg-red-400 text-white cursor-not-allowed'
                                : 'bg-red-600 text-white hover:bg-red-700 hover:shadow-md'
                        ]"
                    >
                        <span v-if="!isSubmitting || submitType !== 'rejected'">Reject</span>
                        <span v-else class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Rejecting...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import TagInput from '../Common/TagInput.vue';

// Props
const props = defineProps({
    campaign: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        default: () => []
    },
    tags: {
        type: Array,
        default: () => []
    },
    currencies: {
        type: Array,
        required: true
    },
    userPermissions: {
        type: Array,
        default: () => []
    },
    csrfToken: {
        type: String,
        required: true
    },
    campaignsUrl: {
        type: String,
        default: '/campaigns'
    }
});

// Helper function to format date for input[type="date"]
const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    // Extract YYYY-MM-DD from ISO string or date object
    return dateString.split('T')[0];
};

// Helper function to convert tags to the format expected by TagInput
const initializeTags = (campaign) => {
    if (!campaign.tags || !Array.isArray(campaign.tags)) {
        return [];
    }
    // Convert tag objects to the format expected by TagInput component
    return campaign.tags.map(tag => ({
        id: tag.id,
        name: tag.name
    }));
};

// Helper function to get currency value (handles both string and enum object)
const getCurrencyValue = (currency) => {
    if (!currency) return 'EUR'; // Default to EUR if null
    if (typeof currency === 'string') return currency;
    if (typeof currency === 'object' && currency.value) return currency.value;
    return 'EUR';
};

// Form state - Initialize with campaign data
const form = ref({
    title: props.campaign.title || '',
    description: props.campaign.description || '',
    category_id: props.campaign.category_id || '',
    tags: initializeTags(props.campaign),
    goal_amount: props.campaign.goal_amount || '',
    currency: getCurrencyValue(props.campaign.currency),
    start_date: formatDateForInput(props.campaign.start_date),
    end_date: formatDateForInput(props.campaign.end_date)
});

// UI state
const isSubmitting = ref(false);
const submitType = ref(null); // 'draft' or 'waiting_for_validation'
const errors = ref({
    title: '',
    description: '',
    category_id: '',
    tags: '',
    goal_amount: '',
    currency: '',
    start_date: '',
    end_date: '',
    general: ''
});

// Computed
const canSaveAsDraft = computed(() => {
    // For draft, only title is required
    return form.value.title.trim() !== '';
});

const canSaveForValidation = computed(() => {
    // For validation, all fields are required
    return (
        form.value.title.trim() !== '' &&
        form.value.goal_amount !== '' &&
        form.value.currency !== '' &&
        form.value.start_date !== '' &&
        form.value.end_date !== ''
    );
});

const minEndDate = computed(() => {
    if (!form.value.start_date) {
        return '';
    }

    // Add 1 day to start date
    const startDate = new Date(form.value.start_date);
    startDate.setDate(startDate.getDate() + 1);

    // Format as YYYY-MM-DD
    return startDate.toISOString().split('T')[0];
});

// Check if user has specific permission
const hasPermission = (permission) => {
    return props.userPermissions.includes(permission);
};

// Check if current campaign status is Draft
const isDraftStatus = computed(() => {
    return props.campaign.status === 'draft';
});

// Check if current campaign status is Waiting for Validation
const isWaitingForValidationStatus = computed(() => {
    return props.campaign.status === 'waiting_for_validation';
});

// Check if user can validate campaigns
const canValidateCampaigns = computed(() => {
    return hasPermission('manageAllCampaigns');
});

// Show validate button only if user has permission AND status is waiting_for_validation
const showValidateButton = computed(() => {
    return canValidateCampaigns.value && isWaitingForValidationStatus.value;
});

// Show reject button only if user has permission AND status is waiting_for_validation
const showRejectButton = computed(() => {
    return canValidateCampaigns.value && isWaitingForValidationStatus.value;
});

// Methods
const clearError = (field) => {
    errors.value[field] = '';
    errors.value.general = '';
};

const openDatePicker = (event) => {
    // Trigger the native date picker when clicking anywhere on the input
    if (event.target && !isSubmitting.value) {
        try {
            event.target.showPicker();
        } catch (error) {
            // showPicker() might not be supported in all browsers, fail silently
            console.debug('showPicker not supported');
        }
    }
};

const handleSubmit = async (status) => {
    // Clear previous errors
    errors.value = {
        title: '',
        description: '',
        category_id: '',
        tags: '',
        goal_amount: '',
        currency: '',
        start_date: '',
        end_date: '',
        general: ''
    };

    // Set submitting state
    isSubmitting.value = true;
    submitType.value = status;

    try {
        // Prepare form data based on action type
        let endpoint = '';
        let method = '';
        let payload = {};

        if (status === 'active') {
            // Validate action - only change status, don't send form modifications
            endpoint = `/campaigns/${props.campaign.id}/validate`;
            method = 'POST';
            payload = {}; // Empty payload - endpoint handles status change
        } else if (status === 'rejected') {
            // Reject action - only change status, don't send form modifications
            endpoint = `/campaigns/${props.campaign.id}/reject`;
            method = 'POST';
            payload = {}; // Empty payload - endpoint handles status change
        } else {
            // Update action (draft or waiting_for_validation)
            endpoint = `/campaigns/${props.campaign.id}`;
            method = 'PUT';

            // Build payload with form data and status
            payload = {
                title: form.value.title,
                description: form.value.description || null,
                category_id: form.value.category_id || null,
                tags: form.value.tags.map(tag => tag.name),
                goal_amount: form.value.goal_amount || null,
                currency: form.value.currency || null,
                start_date: form.value.start_date || null,
                end_date: form.value.end_date || null,
                status: status
            };
        }

        // Make API call
        const response = await fetch(endpoint, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            // Success! Redirect to campaigns list
            window.location.href = props.campaignsUrl;
        } else {
            // Handle validation errors
            if (result.errors) {
                Object.keys(result.errors).forEach(key => {
                    if (errors.value.hasOwnProperty(key)) {
                        errors.value[key] = Array.isArray(result.errors[key])
                            ? result.errors[key][0]
                            : result.errors[key];
                    }
                });
            } else {
                errors.value.general = result.message || 'An error occurred while saving the campaign.';
            }
        }
    } catch (error) {
        console.error('Error submitting form:', error);
        errors.value.general = 'A network error occurred. Please try again.';
    } finally {
        isSubmitting.value = false;
        submitType.value = null;
    }
};
</script>
