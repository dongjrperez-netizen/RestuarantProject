<script setup lang="ts">
interface Props {
  class?: any
}

defineProps<Props>()
</script>

<template>
  <h3 class="text-lg font-semibold leading-none tracking-tight">
    <slot />
  </h3>
</template>