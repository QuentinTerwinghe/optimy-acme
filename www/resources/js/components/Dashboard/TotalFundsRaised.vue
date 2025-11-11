<template>
    <stats-card
        title="Total Funds Raised"
        :value="displayValue"
        icon="dollar"
        color="green"
    />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import StatsCard from './StatsCard.vue';

const total = ref(null);
const loading = ref(true);
const error = ref(null);

const displayValue = computed(() => {
    if (loading.value) return '...';
    if (error.value) return 'Error';

    // Format as currency
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(total.value);
});

const fetchTotal = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get('/api/campaigns/stats/total-funds-raised');
        total.value = response.data.total;
    } catch (err) {
        console.error('Failed to fetch total funds raised:', err);
        error.value = err.message;
        total.value = 0;
    } finally {
        loading.value = false;
    }
};

// Expose refresh method so parent can call it
const refresh = () => {
    fetchTotal();
};

// Make refresh method available to parent via defineExpose
defineExpose({
    refresh
});

onMounted(() => {
    fetchTotal();
});
</script>
