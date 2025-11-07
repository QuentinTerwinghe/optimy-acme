<template>
    <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Username/Email Field -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">
                    Email Address
                </label>
                <div class="mt-1">
                    <input
                        id="username"
                        v-model="form.username"
                        type="text"
                        autocomplete="username"
                        required
                        :disabled="isSubmitting"
                        :class="[
                            'appearance-none block w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors',
                            errors.username ? 'border-red-300' : 'border-gray-300',
                            isSubmitting ? 'bg-gray-50 cursor-not-allowed' : ''
                        ]"
                        placeholder="Enter your email"
                        @input="clearError('username')"
                    >
                </div>
                <p v-if="errors.username" class="mt-2 text-sm text-red-600">
                    {{ errors.username }}
                </p>
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                    Password
                </label>
                <div class="mt-1 relative">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        required
                        :disabled="isSubmitting"
                        :class="[
                            'appearance-none block w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors pr-10',
                            errors.password ? 'border-red-300' : 'border-gray-300',
                            isSubmitting ? 'bg-gray-50 cursor-not-allowed' : ''
                        ]"
                        placeholder="Enter your password"
                        @input="clearError('password')"
                    >
                    <!-- Show/Hide Password Toggle -->
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                        :disabled="isSubmitting"
                    >
                        <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
                <p v-if="errors.password" class="mt-2 text-sm text-red-600">
                    {{ errors.password }}
                </p>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input
                        id="remember"
                        v-model="form.remember"
                        type="checkbox"
                        :disabled="isSubmitting"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    >
                    <label for="remember" class="ml-2 block text-sm text-gray-900">
                        Remember me
                    </label>
                </div>

                <div class="text-sm">
                    <a :href="forgotPasswordUrl" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Forgot your password?
                    </a>
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

            <!-- Submit Button -->
            <div>
                <button
                    type="submit"
                    :disabled="isSubmitting || !isFormValid"
                    :class="[
                        'w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white transition-all duration-200',
                        isSubmitting || !isFormValid
                            ? 'bg-indigo-400 cursor-not-allowed'
                            : 'bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                    ]"
                >
                    <span v-if="!isSubmitting">Sign in</span>
                    <span v-else class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Signing in...
                    </span>
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

// Form state
const form = ref({
    username: '',
    password: '',
    remember: false
});

// UI state
const showPassword = ref(false);
const isSubmitting = ref(false);
const errors = ref({
    username: '',
    password: '',
    general: ''
});

// Computed
const isFormValid = computed(() => {
    return form.value.username.trim() !== '' && form.value.password.trim() !== '';
});

const forgotPasswordUrl = '/forgot-password';

// Methods
const clearError = (field) => {
    errors.value[field] = '';
    errors.value.general = '';
};

const handleSubmit = async () => {
    // Reset errors
    errors.value = { username: '', password: '', general: '' };

    // Client-side validation
    if (!form.value.username.trim()) {
        errors.value.username = 'Email address is required';
        return;
    }

    if (!form.value.password.trim()) {
        errors.value.password = 'Password is required';
        return;
    }

    // Basic email format validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(form.value.username)) {
        errors.value.username = 'Please enter a valid email address';
        return;
    }

    // Submit form
    isSubmitting.value = true;

    try {
        const result = await authStore.login({
            username: form.value.username,
            password: form.value.password,
            remember: form.value.remember
        });

        if (result.success) {
            // Success - navigate to dashboard
            router.push('/dashboard');
        } else {
            // Handle validation errors
            if (result.errors) {
                errors.value.username = result.errors.username?.[0] || '';
                errors.value.password = result.errors.password?.[0] || '';
                if (!errors.value.username && !errors.value.password) {
                    errors.value.general = result.errors.email?.[0] || 'Invalid credentials. Please try again.';
                }
            } else {
                errors.value.general = 'Invalid credentials. Please try again.';
            }
        }
    } catch (error) {
        console.error('Login error:', error);
        errors.value.general = 'An error occurred. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>
