<script setup lang="ts">
import { computed } from 'vue'
import { X } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { sheetVariants, type SheetProps } from './sheet'

interface Props extends /* @vue-ignore */ SheetProps {
  class?: any
}

const props = withDefaults(defineProps<Props>(), {
  side: 'right'
})

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props
  return delegated
})
</script>

<template>
  <div class="fixed inset-0 z-50 bg-black/50" v-if="true">
    <div :class="cn(sheetVariants({ side }), props.class)" v-bind="delegatedProps">
      <slot />
    </div>
  </div>
</template>