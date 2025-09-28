<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
// Define badgeVariants locally if './index' does not exist
const badgeVariants = ({ variant }: { variant: string }) => {
  switch (variant) {
    case 'secondary':
      return 'bg-gray-200 text-gray-800';
    case 'destructive':
      return 'bg-red-500 text-white';
    case 'outline':
      return 'border border-gray-300 text-gray-800';
    case 'success':
      return 'bg-green-500 text-white';
    case 'warning':
      return 'bg-yellow-500 text-black';
    default:
      return 'bg-blue-500 text-white';
  }
};

interface Props {
  variant?: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning';
  class?: string;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default'
});

const badgeClasses = computed(() => 
  cn(
    badgeVariants({ variant: props.variant }),
    props.class
  )
);
</script>

<template>
  <div :class="badgeClasses">
    <slot />
  </div>
</template>
