<script setup lang="ts">
interface Props {
  class?: any
}

defineProps<Props>()
</script>

<template>
  <div class="flex flex-col space-y-2 text-center sm:text-left">
    <slot />
  </div>
</template>