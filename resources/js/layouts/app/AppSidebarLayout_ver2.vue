<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSideBar_for_Administrator from '@/components/AppSideBar_for_Administrator.vue';
import AdminAppSidebarHeader from '@/components/AdminAppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSideBar_for_Administrator />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AdminAppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>

    
</template>
