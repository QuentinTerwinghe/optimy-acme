<template>
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Active Campaigns</h2>
                <button
                    @click="refreshCampaigns"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Refresh campaigns list"
                >
                    <svg
                        :class="{'animate-spin': loading}"
                        class="h-4 w-4 mr-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>

            <!-- Filters -->
            <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search Filter -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input
                        id="search"
                        v-model="filters.search"
                        type="text"
                        placeholder="Search by title..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        @input="debouncedFetchCampaigns"
                    />
                </div>

                <!-- Category Filter -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select
                        id="category"
                        v-model="filters.category_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        @change="fetchCampaigns"
                    >
                        <option value="">All Categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <!-- Tags Filter -->
                <div>
                    <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                    <div class="relative">
                        <!-- Selected Tags Display and Dropdown Trigger -->
                        <div
                            class="appearance-none min-h-[42px] w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-pointer hover:border-indigo-500 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500"
                            @click="toggleTagDropdown"
                        >
                            <!-- Selected Tags as Chips -->
                            <div v-if="filters.selectedTags.length > 0" class="flex flex-wrap gap-2">
                                <span
                                    v-for="tag in filters.selectedTags"
                                    :key="tag.id"
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-indigo-100 text-indigo-800"
                                >
                                    {{ tag.name }}
                                    <button
                                        type="button"
                                        @click.stop="removeTagFilter(tag)"
                                        class="hover:text-indigo-600 focus:outline-none"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            </div>
                            <!-- Placeholder -->
                            <div v-else class="text-sm text-gray-400">
                                Select tags to filter...
                            </div>
                        </div>

                        <!-- Dropdown with Tag Options -->
                        <div
                            v-if="showTagDropdown"
                            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                        >
                            <div
                                v-for="tag in tags"
                                :key="tag.id"
                                @click="toggleTag(tag)"
                                class="flex items-center px-3 py-2 hover:bg-indigo-50 cursor-pointer transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isTagSelected(tag)"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded pointer-events-none"
                                >
                                <label class="ml-2 text-sm text-gray-900 cursor-pointer">
                                    {{ tag.name }}
                                </label>
                            </div>
                            <div v-if="tags.length === 0" class="px-3 py-2 text-sm text-gray-500">
                                No tags available
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clear Filters Button -->
            <div class="mb-4 flex justify-end" v-if="hasActiveFilters">
                <button
                    @click="clearFilters"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                >
                    Clear Filters
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading && campaigns.length === 0" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-sm text-gray-600">Loading campaigns...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error loading campaigns</h3>
                        <p class="mt-1 text-sm text-red-700">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="campaigns.length === 0" class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No active campaigns</h3>
                <p class="mt-1 text-sm text-gray-500">There are currently no active campaigns.</p>
            </div>

            <!-- Campaigns List -->
            <div v-else class="space-y-4">
                <a
                    v-for="campaign in paginatedCampaigns"
                    :key="campaign.id"
                    :href="`/campaigns/${campaign.id}`"
                    class="block border border-gray-200 rounded-lg p-4 hover:border-indigo-300 hover:shadow-md transition-all cursor-pointer"
                >
                    <!-- Campaign Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-semibold text-gray-900 truncate">
                                {{ campaign.title }}
                            </h3>
                            <p v-if="campaign.description" class="mt-1 text-sm text-gray-600 line-clamp-2">
                                {{ campaign.description }}
                            </p>
                        </div>
                        <span
                            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                        >
                            {{ campaign.status_label }}
                        </span>
                    </div>

                    <!-- Category and Tags -->
                    <div class="mb-3 flex flex-wrap gap-2">
                        <span v-if="campaign.category" class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            {{ campaign.category.name }}
                        </span>
                        <span
                            v-for="tag in campaign.tags"
                            :key="tag.id"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium"
                            :style="{ backgroundColor: tag.color ? tag.color + '20' : '#F3F4F6', color: tag.color || '#374151' }"
                        >
                            {{ tag.name }}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-3">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                            <span class="font-medium">
                                {{ formatCurrency(campaign.current_amount, campaign.currency) }} raised
                            </span>
                            <span>{{ campaign.progress_percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="bg-indigo-600 h-2 rounded-full transition-all"
                                :style="{width: campaign.progress_percentage + '%'}"
                            ></div>
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            Goal: {{ formatCurrency(campaign.goal_amount, campaign.currency) }}
                        </div>
                    </div>

                    <!-- Campaign Footer -->
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center text-gray-500">
                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Ends {{ campaign.end_date_formatted }}</span>
                        </div>
                        <div class="text-indigo-600 font-medium">
                            {{ campaign.days_remaining }} {{ campaign.days_remaining === 1 ? 'day' : 'days' }} left
                        </div>
                    </div>
                </a>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium">{{ startIndex + 1 }}</span> to
                        <span class="font-medium">{{ endIndex }}</span> of
                        <span class="font-medium">{{ campaigns.length }}</span> campaigns
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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// State
const campaigns = ref([]);
const categories = ref([]);
const tags = ref([]);
const loading = ref(false);
const error = ref(null);
const showTagDropdown = ref(false);

// Filters
const filters = ref({
    search: '',
    category_id: '',
    selectedTags: [], // Array of tag objects
});

// Pagination
const currentPage = ref(1);
const itemsPerPage = 5;

/**
 * Fetch active campaigns from API with filters
 */
const fetchCampaigns = async () => {
    loading.value = true;
    error.value = null;

    try {
        const params = {};

        if (filters.value.search) {
            params.search = filters.value.search;
        }
        if (filters.value.category_id) {
            params.category_id = filters.value.category_id;
        }
        if (filters.value.selectedTags.length > 0) {
            // Extract tag IDs from selected tag objects
            const tagIds = filters.value.selectedTags.map(tag => tag.id);
            params.tag_ids = tagIds.join(',');
        }

        console.log('Fetching campaigns with params:', params);
        console.log('Selected tags:', filters.value.selectedTags);

        const response = await axios.get('/api/campaigns/active', { params });

        console.log('API Response:', response.data);
        console.log('Campaigns received:', response.data.data?.length || 0);

        campaigns.value = response.data.data || [];

        // Reset to first page when filters change
        currentPage.value = 1;
    } catch (err) {
        console.error('Error fetching campaigns:', err);
        error.value = err.response?.data?.message || 'Failed to load campaigns. Please try again.';
    } finally {
        loading.value = false;
    }
};

/**
 * Toggle tag dropdown visibility
 */
const toggleTagDropdown = () => {
    showTagDropdown.value = !showTagDropdown.value;
};

/**
 * Check if a tag is currently selected
 */
const isTagSelected = (tag) => {
    return filters.value.selectedTags.some(t => t.id === tag.id);
};

/**
 * Toggle tag selection
 */
const toggleTag = (tag) => {
    if (isTagSelected(tag)) {
        removeTagFilter(tag);
    } else {
        filters.value.selectedTags.push(tag);
        fetchCampaigns();
    }
};

/**
 * Remove a tag from filters
 */
const removeTagFilter = (tag) => {
    filters.value.selectedTags = filters.value.selectedTags.filter(t => t.id !== tag.id);
    fetchCampaigns();
};

/**
 * Close dropdown when clicking outside
 */
const handleClickOutside = (event) => {
    // Check if click is outside the tags filter dropdown
    const tagsFilter = document.querySelector('.relative');
    if (tagsFilter && !tagsFilter.contains(event.target)) {
        showTagDropdown.value = false;
    }
};


/**
 * Fetch categories from API
 */
const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/categories');
        categories.value = response.data.data || [];
    } catch (err) {
        console.error('Error fetching categories:', err);
    }
};

/**
 * Fetch tags from API
 */
const fetchTags = async () => {
    try {
        const response = await axios.get('/api/tags');
        tags.value = response.data.data || [];
    } catch (err) {
        console.error('Error fetching tags:', err);
    }
};

// Define emits
const emit = defineEmits(['refresh']);

/**
 * Debounce helper for search
 */
let debounceTimeout = null;
const debouncedFetchCampaigns = () => {
    if (debounceTimeout) {
        clearTimeout(debounceTimeout);
    }
    debounceTimeout = setTimeout(() => {
        fetchCampaigns();
    }, 300);
};

/**
 * Refresh campaigns list
 */
const refreshCampaigns = () => {
    fetchCampaigns();
    // Emit refresh event to parent so it can refresh other components
    emit('refresh');
};

/**
 * Clear all filters
 */
const clearFilters = () => {
    filters.value = {
        search: '',
        category_id: '',
        selectedTags: [],
    };
    fetchCampaigns();
};

/**
 * Check if any filters are active
 */
const hasActiveFilters = computed(() => {
    return filters.value.search !== '' ||
           filters.value.category_id !== '' ||
           filters.value.selectedTags.length > 0;
});

/**
 * Pagination computed properties
 */
const totalPages = computed(() => Math.ceil(campaigns.value.length / itemsPerPage));
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage);
const endIndex = computed(() => Math.min(startIndex.value + itemsPerPage, campaigns.value.length));

const paginatedCampaigns = computed(() => {
    return campaigns.value.slice(startIndex.value, endIndex.value);
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

/**
 * Pagination functions
 */
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

/**
 * Format currency value
 */
const formatCurrency = (amount, currency) => {
    const numAmount = parseFloat(amount);
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numAmount);

    const symbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
    };

    return (symbols[currency] || currency) + formatted;
};

// Load data on component mount
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    fetchCampaigns();
    fetchCategories();
    fetchTags();
});

// Clean up listener on unmount
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
