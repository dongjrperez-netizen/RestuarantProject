<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        src="/Logo.png"
        alt="ServeWise Logo"
        :class="className"
        v-bind="$attrs"
        class="object-contain"
    />
</template>
