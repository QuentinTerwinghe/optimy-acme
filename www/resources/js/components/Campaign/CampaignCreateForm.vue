<template>
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Create New Campaign</h1>
                    <p class="mt-2 text-sm text-gray-700">Fill in the details below to create a new fundraising campaign.</p>
                </div>
                <a :href="dashboardUrl" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Dashboard
                </a>
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
                                Goal Amount <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                id="goal_amount"
                                v-model="form.goal_amount"
                                required
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
                                Currency <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="currency"
                                v-model="form.currency"
                                required
                                :disabled="isSubmitting"
                                :class="[
                                    'appearance-none block w-full px-3 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors',
                                    errors.currency ? 'border-red-300' : 'border-gray-300',
                                    isSubmitting ? 'bg-gray-50 cursor-not-allowed' : ''
                                ]"
                                @change="clearError('currency')"
                            >
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
                                Start Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                ref="startDateInput"
                                type="date"
                                id="start_date"
                                v-model="form.start_date"
                                required
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
                                End Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                ref="endDateInput"
                                type="date"
                                id="end_date"
                                v-model="form.end_date"
                                required
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
                        :href="dashboardUrl"
                        class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200"
                    >
                        Cancel
                    </a>
                    <button
                        type="button"
                        @click="handleSubmit('draft')"
                        :disabled="isSubmitting || !isFormValid"
                        :class="[
                            'px-6 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 shadow-sm',
                            isSubmitting || !isFormValid
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
                    <button
                        type="button"
                        @click="handleSubmit('waiting_for_validation')"
                        :disabled="isSubmitting || !isFormValid"
                        :class="[
                            'px-6 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 shadow-sm',
                            isSubmitting || !isFormValid
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
    csrfToken: {
        type: String,
        required: true
    },
    dashboardUrl: {
        type: String,
        default: '/dashboard'
    }
});

// Form state
const form = ref({
    title: '',
    description: '',
    category_id: '',
    tags: [],
    goal_amount: '',
    currency: props.currencies.find(c => c.value === 'EUR')?.value || props.currencies[0]?.value || '',
    start_date: '',
    end_date: ''
});

// UI state
const isSubmitting = ref(false);
const submitType = ref(null); // 'draft' or 'active'
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
const isFormValid = computed(() => {
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
    // Set submit type for loading state
    submitType.value = status;
    isSubmitting.value = true;

    // Reset errors
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

    try {
        // Prepare form data with status
        const formData = {
            ...form.value,
            status: status,
            // Ensure empty category_id is sent as null
            category_id: form.value.category_id || null,
            // Convert tags from objects to array of strings (tag names)
            tags: form.value.tags.map(tag => tag.name)
        };

        // Submit to the server
        const response = await fetch('/campaigns', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (!response.ok) {
            // Handle validation errors
            if (response.status === 422 && data.errors) {
                // Laravel validation errors
                Object.keys(data.errors).forEach(key => {
                    if (errors.value.hasOwnProperty(key)) {
                        errors.value[key] = data.errors[key][0];
                    }
                });
            } else {
                // General error
                errors.value.general = data.message || 'An error occurred while saving the campaign.';
            }
            return;
        }

        // Success! Redirect to dashboard or show success message
        console.log('Campaign created successfully:', data);

        // Redirect to dashboard
        window.location.href = props.dashboardUrl;
    } catch (error) {
        console.error('Submission error:', error);
        errors.value.general = 'An error occurred while saving the campaign. Please try again.';
    } finally {
        isSubmitting.value = false;
        submitType.value = null;
    }
};
</script>
