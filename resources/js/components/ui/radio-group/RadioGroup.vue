<script setup lang="ts">
import { RadioGroupRoot } from 'reka-ui'
import { cn } from '@/lib/utils'

interface Props {
  class?: string
  defaultValue?: string
  disabled?: boolean
  loop?: boolean
  name?: string
  orientation?: 'horizontal' | 'vertical'
  required?: boolean
  dir?: 'ltr' | 'rtl'
}

const props = withDefaults(defineProps<Props>(), {
  orientation: 'vertical',
})

defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <RadioGroupRoot
    v-bind="props"
    :class="cn('grid gap-2', props.class)"
  >
    <slot />
  </RadioGroupRoot>
</template>