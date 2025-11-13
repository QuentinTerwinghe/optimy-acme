<template>
    <div class="mx-auto max-w-4xl">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Edit Role</h1>
                    <p class="mt-2 text-sm text-gray-700">Update role details, permissions, and user assignments.</p>
                </div>
                <a href="/admin/roles" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Roles
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p class="mt-2 text-sm text-gray-500">Loading role...</p>
        </div>

        <div v-else>
            <!-- Error Message -->
            <div v-if="error" class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-red-800">{{ error }}</p>
            </div>

            <!-- Role Form -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <!-- Basic Information -->
                    <div class="border-b border-gray-200 pb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>

                        <!-- Role Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Role Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                :disabled="submitting || isProtectedRole"
                                placeholder="e.g., editor, moderator, viewer"
                                :class="[
                                    'appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
                                    isProtectedRole ? 'bg-gray-100 cursor-not-allowed' : ''
                                ]"
                            />
                            <p v-if="isProtectedRole" class="mt-1 text-xs text-amber-600">
                                This is a protected role and cannot be renamed.
                            </p>
                            <p v-else class="mt-1 text-xs text-gray-500">Use lowercase with underscores (e.g., content_editor)</p>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div class="border-b border-gray-200 pb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Permissions</h2>
                        <p class="text-sm text-gray-600 mb-4">Select which permissions this role should have.</p>

                        <div v-if="loadingPermissions" class="text-sm text-gray-500">Loading permissions...</div>
                        <div v-else-if="availablePermissions.length === 0" class="text-sm text-gray-500">No permissions available.</div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label
                                v-for="permission in availablePermissions"
                                :key="permission.id"
                                class="flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                            >
                                <input
                                    v-model="form.permissions"
                                    :value="permission.name"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-900">{{ permission.name }}</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- User Assignment -->
                    <div class="pb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Assign to Users</h2>
                        <p class="text-sm text-gray-600 mb-4">Select users who should be assigned this role.</p>

                        <div v-if="loadingUsers" class="text-sm text-gray-500">Loading users...</div>
                        <div v-else-if="availableUsers.length === 0" class="text-sm text-gray-500">No users available.</div>
                        <div v-else class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                            <label
                                v-for="user in availableUsers"
                                :key="user.id"
                                class="flex items-center p-3 border-b border-gray-100 last:border-b-0 hover:bg-gray-50 cursor-pointer"
                            >
                                <input
                                    v-model="form.user_ids"
                                    :value="user.id"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-900">{{ user.name }}</span>
                                    <span class="block text-xs text-gray-500">{{ user.email }}</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                        <a
                            href="/admin/roles"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors duration-200"
                        >
                            Cancel
                        </a>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                        >
                            {{ submitting ? 'Updating...' : 'Update Role' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    roleId: {
        type: [Number, String],
        required: true
    }
});

const form = ref({
    name: '',
    permissions: [],
    user_ids: []
});

const role = ref(null);
const availablePermissions = ref([]);
const availableUsers = ref([]);
const loading = ref(true);
const loadingPermissions = ref(false);
const loadingUsers = ref(false);
const submitting = ref(false);
const error = ref(null);

const protectedRoles = ['admin', 'user'];

const isProtectedRole = computed(() => {
    return role.value && protectedRoles.includes(role.value.name);
});

onMounted(async () => {
    await Promise.all([
        fetchRole(),
        fetchPermissions(),
        fetchUsers()
    ]);
    loading.value = false;
});

const fetchRole = async () => {
    try {
        const response = await axios.get(`/api/admin/roles/${props.roleId}`);
        role.value = response.data.data;

        // Populate form
        form.value.name = role.value.name;
        form.value.permissions = role.value.permissions?.map(p => p.name) || [];
        form.value.user_ids = role.value.users?.map(u => u.id) || [];
    } catch (err) {
        error.value = 'Failed to load role';
        console.error('Failed to load role:', err);
    }
};

const fetchPermissions = async () => {
    loadingPermissions.value = true;
    try {
        const response = await axios.get('/api/admin/permissions');
        availablePermissions.value = response.data.data;
    } catch (err) {
        console.error('Failed to load permissions:', err);
    } finally {
        loadingPermissions.value = false;
    }
};

const fetchUsers = async () => {
    loadingUsers.value = true;
    try {
        const response = await axios.get('/api/admin/users');
        availableUsers.value = response.data.data;
    } catch (err) {
        console.error('Failed to load users:', err);
    } finally {
        loadingUsers.value = false;
    }
};

const handleSubmit = async () => {
    error.value = null;
    submitting.value = true;

    try {
        await axios.put(`/api/admin/roles/${props.roleId}`, {
            name: form.value.name,
            permissions: form.value.permissions,
            user_ids: form.value.user_ids
        });

        // Redirect to roles list
        window.location.href = '/admin/roles';
    } catch (err) {
        error.value = err.response?.data?.message || err.response?.data?.error || 'An error occurred';
        submitting.value = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};
</script>
