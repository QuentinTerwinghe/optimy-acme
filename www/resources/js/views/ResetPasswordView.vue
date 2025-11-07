<template>
    <div class="flex min-h-screen items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                    Reset Password
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Enter your new password below.
                </p>
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
                <input type="hidden" v-model="form.token" />

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

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        New Password
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        Confirm Password
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                >
                    {{ loading ? 'Resetting...' : 'Reset Password' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const form = ref({
    token: '',
    email: '',
    password: '',
    password_confirmation: ''
});

const errors = ref({});
const loading = ref(false);

onMounted(() => {
    form.value.token = route.query.token || '';
    form.value.email = route.query.email || '';
});

const handleSubmit = async () => {
    loading.value = true;
    errors.value = {};

    try {
        await axios.post('/api/reset-password', form.value);
        alert('Password reset successful! Please login with your new password.');
        router.push('/login');
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
