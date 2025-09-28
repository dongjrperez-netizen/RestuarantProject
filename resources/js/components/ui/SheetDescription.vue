<script setup lang="ts">
interface Props {
  class?: any
}

defineProps<Props>()
</script>

<template>
  <p class="text-sm text-muted-foreground">
    <slot />
  </p>
</template>