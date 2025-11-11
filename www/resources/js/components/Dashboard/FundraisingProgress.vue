<template>
    <stats-card
        title="Fundraising Progress"
        :value="displayValue"
        icon="scale"
        color="indigo"
    />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import StatsCard from './StatsCard.vue';

const progress = ref(null);
const loading = ref(true);
const error = ref(null);

const displayValue = computed(() => {
    if (loading.value) return '...';
    if (error.value) return 'Error';
    if (!progress.value) return '0%';

    // Display only the percentage
    return `${progress.value.percentage}%`;
});

const fetchProgress = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get('/api/campaigns/stats/fundraising-progress');
        progress.value = response.data;
    } catch (err) {
        console.error('Failed to fetch fundraising progress:', err);
        error.value = err.message;
        progress.value = {
            total_goal: 0,
            total_raised: 0,
            percentage: 0
        };
    } finally {
        loading.value = false;
    }
};

// Expose refresh method so parent can call it
const refresh = () => {
    fetchProgress();
};

// Make refresh method available to parent via defineExpose
defineExpose({
    refresh
});

onMounted(() => {
    fetchProgress();
});
</script>
