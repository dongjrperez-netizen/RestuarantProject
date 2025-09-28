<script setup lang="ts">
import { RadioGroupItem, RadioGroupIndicator } from 'reka-ui'
import { Circle } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

interface Props {
  id?: string
  value: string
  disabled?: boolean
  class?: string
}

const props = defineProps<Props>()
</script>

<template>
  <RadioGroupItem
    :id="props.id"
    v-bind="props"
    :class="cn(
      'aspect-square h-4 w-4 rounded-full border border-primary text-primary shadow focus:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
      props.class,
    )"
  >
    <RadioGroupIndicator class="flex items-center justify-center">
      <Circle class="h-3.5 w-3.5 fill-primary" />
    </RadioGroupIndicator>
  </RadioGroupItem>
</template>