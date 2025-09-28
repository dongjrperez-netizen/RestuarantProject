<script setup lang="ts">
interface Props {
  class?: any
}

defineProps<Props>()
</script>

<template>
  <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2">
    <slot />
  </div>
</template>