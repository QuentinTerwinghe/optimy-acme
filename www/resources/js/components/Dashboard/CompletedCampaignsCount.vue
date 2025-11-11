<template>
    <stats-card
        title="Completed Campaigns"
        :value="displayValue"
        icon="check"
        color="blue"
    />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import StatsCard from './StatsCard.vue';

const count = ref(null);
const loading = ref(true);
const error = ref(null);

const displayValue = computed(() => {
    if (loading.value) return '...';
    if (error.value) return 'Error';
    return count.value;
});

const fetchCount = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get('/api/campaigns/stats/completed-count');
        count.value = response.data.count;
    } catch (err) {
        console.error('Failed to fetch completed campaigns count:', err);
        error.value = err.message;
        count.value = 0;
    } finally {
        loading.value = false;
    }
};

// Expose refresh method so parent can call it
const refresh = () => {
    fetchCount();
};

// Make refresh method available to parent via defineExpose
defineExpose({
    refresh
});

onMounted(() => {
    fetchCount();
});
</script>
