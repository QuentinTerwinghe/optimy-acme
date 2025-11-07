<template>
    <div class="flex min-h-screen items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                    Forgot Password
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Enter your email address and we'll send you a password reset link.
                </p>
            </div>

            <div v-if="message" class="rounded-md bg-green-50 p-4">
                <p class="text-sm text-green-800">{{ message }}</p>
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email address
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                >
                    {{ loading ? 'Sending...' : 'Send Reset Link' }}
                </button>

                <div class="text-center">
                    <router-link
                        to="/login"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Back to login
                    </router-link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const form = ref({
    email: ''
});

const errors = ref({});
const loading = ref(false);
const message = ref('');

const handleSubmit = async () => {
    loading.value = true;
    errors.value = {};
    message.value = '';

    try {
        await axios.post('/api/forgot-password', form.value);
        message.value = 'Password reset link sent to your email!';
        form.value.email = '';
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            errors.value = { email: 'An error occurred. Please try again.' };
        }
    } finally {
        loading.value = false;
    }
};
</script>
