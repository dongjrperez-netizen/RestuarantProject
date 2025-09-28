<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click.self="emitClose"
  >
    <div class="bg-white rounded-lg p-6 shadow-lg w-full max-w-md">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ show: boolean }>();
const emit = defineEmits(['close']);

const emitClose = () => {
  emit('close');
};
</script>
