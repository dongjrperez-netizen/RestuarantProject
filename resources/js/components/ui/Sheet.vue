<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  open?: boolean
  defaultOpen?: boolean
}

interface Emits {
  (e: 'update:open', value: boolean): void
}

const props = withDefaults(defineProps<Props>(), {
  open: undefined,
  defaultOpen: false
})

const emits = defineEmits<Emits>()

const isOpen = computed({
  get: () => props.open ?? props.defaultOpen,
  set: (value) => emits('update:open', value)
})
</script>

<template>
  <div>
    <slot :open="isOpen" :onOpenChange="(value: boolean) => isOpen = value" />
  </div>
</template>