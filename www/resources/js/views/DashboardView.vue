<template>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-2 text-sm text-gray-700">Welcome back, {{ userName }}!</p>
        </div>

        <!-- Dashboard Content (Vue Component) -->
        <dashboard-wrapper />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import DashboardWrapper from '../components/Dashboard/DashboardWrapper.vue';

const authStore = useAuthStore();
const userName = computed(() => authStore.user?.name || 'User');
</script>
