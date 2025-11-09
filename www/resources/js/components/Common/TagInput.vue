<template>
    <div class="relative">
        <!-- Tag Display Area with Input -->
        <div
            :class="[
                'appearance-none min-h-[42px] w-full px-3 py-2 border rounded-lg shadow-sm focus-within:outline-none focus-within:ring-indigo-500 focus-within:border-indigo-500 transition-colors cursor-text',
                error ? 'border-red-300' : 'border-gray-300',
                disabled ? 'bg-gray-50 cursor-not-allowed' : 'bg-white'
            ]"
            @click="focusInput"
        >
            <!-- Selected Tags as Chips -->
            <div class="flex flex-wrap gap-2 items-center">
                <span
                    v-for="tag in selectedTags"
                    :key="tag.id || tag.name"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-indigo-100 text-indigo-800"
                >
                    {{ tag.name }}
                    <button
                        type="button"
                        @click.stop="removeTag(tag)"
                        :disabled="disabled"
                        class="hover:text-indigo-600 focus:outline-none"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>

                <!-- Input Field -->
                <input
                    ref="inputRef"
                    v-model="searchQuery"
                    type="text"
                    :disabled="disabled"
                    :placeholder="selectedTags.length === 0 ? placeholder : ''"
                    class="flex-1 min-w-[120px] border-none outline-none focus:ring-0 p-0 text-sm placeholder-gray-400"
                    @input="onInput"
                    @keydown.enter.prevent="onEnter"
                    @keydown.comma.prevent="onComma"
                    @keydown.delete="onBackspace"
                    @focus="showDropdown = true"
                    @blur="onBlur"
                >
            </div>
        </div>

        <!-- Dropdown with Suggestions -->
        <div
            v-if="showDropdown && filteredAvailableTags.length > 0"
            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
            <button
                v-for="tag in filteredAvailableTags"
                :key="tag.id"
                type="button"
                @mousedown.prevent="selectTag(tag)"
                class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none transition-colors"
            >
                {{ tag.name }}
            </button>
        </div>

        <!-- Helper Text -->
        <p v-if="helperText" class="mt-1 text-xs text-gray-500">
            {{ helperText }}
        </p>

        <!-- Error Message -->
        <p v-if="error" class="mt-2 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

// Props
const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    availableTags: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Type to search or add tags...'
    },
    helperText: {
        type: String,
        default: ''
    },
    error: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

// Emits
const emit = defineEmits(['update:modelValue']);

// Refs
const inputRef = ref(null);
const searchQuery = ref('');
const showDropdown = ref(false);

// Computed
const selectedTags = computed(() => props.modelValue);

const filteredAvailableTags = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.availableTags.filter(tag =>
            !selectedTags.value.some(selected => selected.id === tag.id)
        );
    }

    const query = searchQuery.value.toLowerCase().trim();
    return props.availableTags.filter(tag => {
        const isNotSelected = !selectedTags.value.some(selected => selected.id === tag.id);
        const matchesSearch = tag.name.toLowerCase().includes(query);
        return isNotSelected && matchesSearch;
    });
});

// Methods
const focusInput = () => {
    if (!props.disabled) {
        inputRef.value?.focus();
    }
};

const selectTag = (tag) => {
    if (!selectedTags.value.some(selected => selected.id === tag.id)) {
        emit('update:modelValue', [...selectedTags.value, tag]);
    }
    searchQuery.value = '';
    showDropdown.value = false;
    focusInput();
};

const removeTag = (tag) => {
    if (!props.disabled) {
        emit('update:modelValue', selectedTags.value.filter(t =>
            (t.id || t.name) !== (tag.id || tag.name)
        ));
    }
};

const addNewTag = (name) => {
    const trimmedName = name.trim();
    if (!trimmedName) return;

    // Check if tag already exists (by name)
    const existingTag = props.availableTags.find(
        tag => tag.name.toLowerCase() === trimmedName.toLowerCase()
    );

    if (existingTag) {
        selectTag(existingTag);
        return;
    }

    // Check if already selected
    if (selectedTags.value.some(tag => tag.name.toLowerCase() === trimmedName.toLowerCase())) {
        searchQuery.value = '';
        return;
    }

    // Add as new tag (without ID, will be created on save)
    const newTag = {
        name: trimmedName,
        isNew: true
    };

    emit('update:modelValue', [...selectedTags.value, newTag]);
    searchQuery.value = '';
    showDropdown.value = false;
};

const onInput = () => {
    showDropdown.value = true;
};

const onEnter = () => {
    if (searchQuery.value.trim()) {
        if (filteredAvailableTags.value.length > 0) {
            selectTag(filteredAvailableTags.value[0]);
        } else {
            addNewTag(searchQuery.value);
        }
    }
};

const onComma = () => {
    if (searchQuery.value.trim()) {
        addNewTag(searchQuery.value);
    }
};

const onBackspace = () => {
    if (!searchQuery.value && selectedTags.value.length > 0) {
        removeTag(selectedTags.value[selectedTags.value.length - 1]);
    }
};

const onBlur = () => {
    setTimeout(() => {
        showDropdown.value = false;
    }, 200);
};

// Watch for changes
watch(searchQuery, (newValue) => {
    if (newValue) {
        showDropdown.value = true;
    }
});
</script>
