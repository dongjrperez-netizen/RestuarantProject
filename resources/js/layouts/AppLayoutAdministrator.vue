<script setup lang="ts">
import AppLayoutAdministrator from '@/layouts/app/AppSidebarLayout_ver2.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayoutAdministrator :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayoutAdministrator>
</template>