<script setup lang="ts">
import { Sidebar, SidebarContent, SidebarHeader } from '@/components/ui/sidebar';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { type NavItem } from '@/types';
import { ClipboardList, Users, CreditCard, Settings, BarChart3 } from 'lucide-vue-next';
import NavAdministrator from './NavAdministrator.vue';

const administrator_side: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
        icon: BarChart3,
    },
    {
        title: 'User Management',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Subscriptions',
        href: '/admin/subscriptions',
        icon: CreditCard,
    },
    {
        title: 'Pending Requests',
        href: '/request',
        icon: ClipboardList,
    },
    {
        title: 'System Settings',
        href: '/admin/settings',
        icon: Settings,
    },
];
</script>

<template>
    <Sidebar class="flex flex-col h-full">
        <!-- PLATFORM HEADER: always ServeWise, never restaurant -->
        <SidebarHeader class="border-b border-sidebar-border p-4">
            <div class="flex items-center gap-3">
                <!-- ServeWise logo (from /Logo.png via AppLogoIcon) -->
                <div class="flex h-8 w-8 items-center justify-center overflow-hidden">
                    <AppLogoIcon class="h-8 w-8 object-contain" />
                </div>

                <!-- ServeWise name -->
                <div class="flex flex-col min-w-0">
                    <span class="font-semibold text-lg truncate">ServeWise</span>
                    <span class="text-xs text-muted-foreground">Admin Portal</span>
                </div>
            </div>
        </SidebarHeader>

        <!-- Admin navigation -->
        <SidebarContent>
            <NavAdministrator :items="administrator_side" />
        </SidebarContent>
    </Sidebar>

    <slot />
</template>
