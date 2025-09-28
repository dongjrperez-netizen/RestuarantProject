<script setup lang="ts">
import AppLayoutSubscriptionRenewal from '@/layouts/app/AppSideBarLayoutSubscriptionRenewal.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayoutSubscriptionRenewal :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayoutSubscriptionRenewal>
</template>
