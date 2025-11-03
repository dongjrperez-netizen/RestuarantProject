<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import CashierSidebar from '@/components/CashierSidebar.vue';
import CashierHeader from '@/components/CashierHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    title?: string;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    title: 'Cashier'
});
</script>

<template>
    <AppShell variant="sidebar">
        <CashierSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <CashierHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>