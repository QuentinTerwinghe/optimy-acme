<template>
    <div class="w-full">
        <!-- Header with Title and Actions -->
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Manage Campaigns</h1>
            <button
                @click="refreshCampaigns"
                :disabled="loading"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
            >
                <svg
                    class="w-4 h-4 mr-2"
                    :class="{ 'animate-spin': loading }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>

        <!-- Filters and Search -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input
                        id="search"
                        v-model="filters.search"
                        type="text"
                        placeholder="Search by title..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select
                        id="status"
                        v-model="filters.status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="waiting_for_validation">Waiting for Validation</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Sort By -->
                <div>
                    <label for="sortBy" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select
                        id="sortBy"
                        v-model="sortBy"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="created_at">Recently Created</option>
                        <option value="title">Title (A-Z)</option>
                        <option value="start_date">Start Date</option>
                        <option value="end_date">End Date</option>
                        <option value="goal_amount">Goal Amount</option>
                        <option value="status">Status</option>
                    </select>
                </div>
            </div>

            <!-- Clear Filters Button -->
            <div class="mt-4 flex justify-end">
                <button
                    @click="clearFilters"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                >
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading && campaigns.length === 0" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p class="mt-2 text-sm text-gray-500">Loading campaigns...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <p class="text-red-800">{{ error }}</p>
            <button
                @click="refreshCampaigns"
                class="mt-2 text-sm text-red-600 hover:text-red-800 font-medium"
            >
                Try Again
            </button>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredCampaigns.length === 0" class="bg-white border border-gray-200 rounded-lg p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No campaigns found</h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ filters.search || filters.status ? 'Try adjusting your filters' : 'Get started by creating a new campaign' }}
            </p>
        </div>

        <!-- Campaigns Table -->
        <div v-else class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                @click="toggleSort('title')"
                            >
                                <div class="flex items-center">
                                    Title
                                    <span v-if="sortBy === 'title'" class="ml-1">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </div>
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                @click="toggleSort('start_date')"
                            >
                                <div class="flex items-center">
                                    Start Date
                                    <span v-if="sortBy === 'start_date'" class="ml-1">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </div>
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                @click="toggleSort('end_date')"
                            >
                                <div class="flex items-center">
                                    End Date
                                    <span v-if="sortBy === 'end_date'" class="ml-1">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </div>
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                @click="toggleSort('goal_amount')"
                            >
                                <div class="flex items-center">
                                    Goal Amount
                                    <span v-if="sortBy === 'goal_amount'" class="ml-1">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Current Amount
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                @click="toggleSort('status')"
                            >
                                <div class="flex items-center">
                                    Status
                                    <span v-if="sortBy === 'status'" class="ml-1">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="campaign in paginatedCampaigns"
                            :key="campaign.id"
                            class="hover:bg-gray-50 transition-colors duration-150"
                        >
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ campaign.title }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ campaign.start_date_formatted || '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ campaign.end_date_formatted || '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ campaign.goal_amount ? formatCurrency(campaign.goal_amount, campaign.currency) : '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ formatCurrency(campaign.current_amount, campaign.currency) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getStatusBadgeClass(campaign.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                    {{ campaign.status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a
                                    :href="`/campaigns/${campaign.id}`"
                                    class="text-blue-600 hover:text-blue-900 mr-4"
                                    title="View campaign details"
                                >
                                    View
                                </a>
                                <a
                                    v-if="canEditCampaign(campaign)"
                                    :href="`/campaigns/${campaign.id}/edit`"
                                    class="text-indigo-600 hover:text-indigo-900 mr-4"
                                    title="Edit campaign"
                                >
                                    Edit
                                </a>
                                <span
                                    v-else
                                    class="text-gray-400 mr-4 cursor-not-allowed"
                                    title="Cannot edit this campaign"
                                >
                                    Edit
                                </span>
                                <button
                                    @click="cancelCampaign(campaign.id)"
                                    class="text-red-600 hover:text-red-900"
                                    title="Cancel campaign"
                                    :disabled="campaign.status === 'cancelled' || campaign.status === 'completed'"
                                    :class="{ 'opacity-50 cursor-not-allowed': campaign.status === 'cancelled' || campaign.status === 'completed' }"
                                >
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium">{{ startIndex + 1 }}</span> to <span class="font-medium">{{ endIndex }}</span> of
                        <span class="font-medium">{{ filteredCampaigns.length }}</span> campaigns
                    </div>
                    <div class="flex space-x-2">
                        <button
                            @click="previousPage"
                            :disabled="currentPage === 1"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        <button
                            v-for="page in displayedPages"
                            :key="page"
                            @click="currentPage = page"
                            :class="[
                                'px-3 py-1 border rounded-md text-sm font-medium',
                                currentPage === page
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'
                            ]"
                        >
                            {{ page }}
                        </button>
                        <button
                            @click="nextPage"
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

// State
const campaigns = ref([]);
const loading = ref(false);
const error = ref(null);

// Filters
const filters = ref({
    search: '',
    status: '',
});

// Sorting
const sortBy = ref('created_at');
const sortDirection = ref('desc');

// Pagination
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Fetch campaigns
const fetchCampaigns = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get('/api/campaigns/manage');
        campaigns.value = response.data.data || [];
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load campaigns.';
        console.error('Error fetching campaigns:', err);
    } finally {
        loading.value = false;
    }
};

// Refresh campaigns
const refreshCampaigns = () => {
    fetchCampaigns();
};

// Filter campaigns
const filteredCampaigns = computed(() => {
    let result = [...campaigns.value];

    // Apply search filter
    if (filters.value.search) {
        const searchLower = filters.value.search.toLowerCase();
        result = result.filter(campaign =>
            campaign.title.toLowerCase().includes(searchLower)
        );
    }

    // Apply status filter
    if (filters.value.status) {
        result = result.filter(campaign => {
            const matches = campaign.status === filters.value.status;
            return matches;
        });
    }

    // Apply sorting
    result.sort((a, b) => {
        let aValue = a[sortBy.value];
        let bValue = b[sortBy.value];

        // Handle null values
        if (aValue === null || aValue === undefined) aValue = '';
        if (bValue === null || bValue === undefined) bValue = '';

        // Convert to comparable values
        if (sortBy.value === 'goal_amount' || sortBy.value === 'current_amount') {
            aValue = parseFloat(aValue) || 0;
            bValue = parseFloat(bValue) || 0;
        } else if (typeof aValue === 'string') {
            aValue = aValue.toLowerCase();
            bValue = bValue.toLowerCase();
        }

        if (aValue < bValue) return sortDirection.value === 'asc' ? -1 : 1;
        if (aValue > bValue) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });

    return result;
});

// Pagination computed properties
const totalPages = computed(() => Math.ceil(filteredCampaigns.value.length / itemsPerPage.value));
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value);
const endIndex = computed(() => Math.min(startIndex.value + itemsPerPage.value, filteredCampaigns.value.length));

const paginatedCampaigns = computed(() => {
    return filteredCampaigns.value.slice(startIndex.value, endIndex.value);
});

const displayedPages = computed(() => {
    const pages = [];
    const maxDisplayed = 5;
    let start = Math.max(1, currentPage.value - Math.floor(maxDisplayed / 2));
    let end = Math.min(totalPages.value, start + maxDisplayed - 1);

    if (end - start < maxDisplayed - 1) {
        start = Math.max(1, end - maxDisplayed + 1);
    }

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

// Sorting functions
const toggleSort = (field) => {
    if (sortBy.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDirection.value = 'asc';
    }
    currentPage.value = 1; // Reset to first page when sorting changes
};

// Pagination functions
const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const previousPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

// Clear filters
const clearFilters = () => {
    filters.value = {
        search: '',
        status: '',
    };
    currentPage.value = 1;
};

// Format currency
const formatCurrency = (amount, currency) => {
    const currencySymbols = {
        USD: '$',
        EUR: '€',
        GBP: '£',
    };

    const symbol = currencySymbols[currency] || currency || '$';
    return `${symbol}${parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

// Get status badge class
const getStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800',
        waiting_for_validation: 'bg-yellow-100 text-yellow-800',
        active: 'bg-green-100 text-green-800',
        completed: 'bg-blue-100 text-blue-800',
        cancelled: 'bg-red-100 text-red-800',
    };

    return classes[status] || 'bg-gray-100 text-gray-800';
};

// Check if campaign can be edited (only draft, waiting_for_validation and rejectd)
const canEditCampaign = (campaign) => {
    return campaign.status === 'draft' || campaign.status === 'waiting_for_validation' || campaign.status === 'rejected';
};

// Action handlers
const cancelCampaign = (id) => {
    console.log('Cancel campaign:', id);
    // TODO: Implement cancel functionality
};

// Lifecycle
onMounted(() => {
    fetchCampaigns();
});
</script>
