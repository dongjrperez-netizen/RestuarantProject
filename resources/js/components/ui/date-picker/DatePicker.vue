<script setup lang="ts">
import { Calendar } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
  modelValue?: string;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  id?: string;
  name?: string;
  autocomplete?: string;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select date'
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const dateInput = ref<HTMLInputElement>();

const formattedValue = computed({
  get: () => props.modelValue || '',
  set: (value: string) => emit('update:modelValue', value)
});

const displayValue = computed(() => {
  if (!props.modelValue) return '';
  
  try {
    const date = new Date(props.modelValue);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  } catch {
    return props.modelValue;
  }
});

const openDatePicker = () => {
  if (dateInput.value && !props.disabled) {
    dateInput.value.showPicker();
  }
};
</script>

<template>
  <div class="relative">
    <!-- Hidden native date input -->
    <input
      ref="dateInput"
      :id="id"
      :name="name"
      type="date"
      :value="formattedValue"
      @input="formattedValue = ($event.target as HTMLInputElement).value"
      :required="required"
      :disabled="disabled"
      :autocomplete="autocomplete"
      class="sr-only"
    />
    
    <!-- Custom styled button -->
    <button
      type="button"
      @click="openDatePicker"
      :disabled="disabled"
      class="relative w-full px-4 py-3 text-left bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors hover:border-gray-400 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed"
    >
      <span v-if="displayValue" class="block text-gray-900">
        {{ displayValue }}
      </span>
      <span v-else class="block text-gray-500">
        {{ placeholder }}
      </span>
      
      <Calendar class="absolute right-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
    </button>
  </div>
</template>