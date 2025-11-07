<template>
    <div class="font-sans antialiased bg-gray-50">
        <!-- Navigation -->
        <nav v-if="isAuthenticated" class="bg-white shadow-sm border-b border-gray-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <router-link to="/dashboard" class="text-xl font-bold text-gray-900">
                            {{ appName }}
                        </router-link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden sm:flex sm:space-x-8">
                        <router-link
                            to="/dashboard"
                            class="inline-flex items-center px-1 pt-1 text-sm font-medium"
                            active-class="text-gray-900 border-b-2 border-indigo-500"
                            inactive-class="text-gray-500 hover:text-gray-700 hover:border-gray-300"
                        >
                            Dashboard
                        </router-link>
                        <!-- Add more navigation items here -->
                    </div>

                    <!-- User Dropdown -->
                    <div v-if="user" class="flex items-center space-x-4">
                        <span class="text-sm text-gray-700">{{ user.name }}</span>
                        <button
                            @click="handleLogout"
                            class="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main :class="{ 'py-8': isAuthenticated }">
            <router-view />
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const isAuthenticated = computed(() => authStore.isAuthenticated);
const user = computed(() => authStore.user);
const appName = window.Laravel?.appName || 'ACME Corp';

const handleLogout = async () => {
    try {
        await authStore.logout();
        router.push('/login');
    } catch (error) {
        console.error('Logout failed:', error);
    }
};
</script>
