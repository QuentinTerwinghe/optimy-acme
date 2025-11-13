<template>
    <div class="w-full">
        <!-- Header with Title and Create Button -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Role Management</h1>
                    <p class="mt-2 text-sm text-gray-700">Manage roles, permissions, and user assignments.</p>
                </div>
                <a href="/admin/roles/create" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Role
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading && roles.length === 0" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p class="mt-2 text-sm text-gray-500">Loading roles...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <p class="text-red-800">{{ error }}</p>
            <button
                @click="fetchRoles"
                class="mt-2 text-sm text-red-600 hover:text-red-800 font-medium"
            >
                Try Again
            </button>
        </div>

        <!-- Roles Table -->
        <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ role.name }}</div>
                            <div class="text-xs text-gray-500">Guard: {{ role.guard_name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">
                                {{ role.permissions?.length || 0 }} permission(s)
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ role.users_count }} user(s)</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ formatDate(role.created_at) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a
                                :href="`/admin/roles/${role.id}/edit`"
                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                            >
                                Edit
                            </a>
                            <button
                                @click="confirmDeleteRole(role)"
                                :disabled="isProtectedRole(role.name)"
                                class="text-red-600 hover:text-red-900 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Empty State -->
            <div v-if="roles.length === 0" class="text-center py-12">
                <p class="text-gray-500">No roles found.</p>
            </div>
        </div>

        <!-- Delete Confirmation Modal (keeping this as it's just a confirmation) -->
        <delete-confirmation-modal
            v-if="showDeleteModal"
            :role="selectedRole"
            @close="showDeleteModal = false"
            @confirm="handleDelete"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import DeleteConfirmationModal from './DeleteConfirmationModal.vue';

const roles = ref([]);
const loading = ref(false);
const error = ref(null);
const showDeleteModal = ref(false);
const selectedRole = ref(null);

const protectedRoles = ['admin', 'user'];

onMounted(() => {
    fetchRoles();
});

const fetchRoles = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/admin/roles');
        roles.value = response.data.data;
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load roles';
    } finally {
        loading.value = false;
    }
};

const confirmDeleteRole = (role) => {
    selectedRole.value = role;
    showDeleteModal.value = true;
};

const handleDelete = async () => {
    try {
        await axios.delete(`/api/admin/roles/${selectedRole.value.id}`);
        showDeleteModal.value = false;
        selectedRole.value = null;
        await fetchRoles();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to delete role';
        showDeleteModal.value = false;
    }
};

const isProtectedRole = (roleName) => {
    return protectedRoles.includes(roleName);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>
