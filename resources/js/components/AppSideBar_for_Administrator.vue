<script setup lang="ts">

import { Sidebar, SidebarContent} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import {LayoutGrid, ClipboardList, Users, CreditCard, Settings, BarChart3} from 'lucide-vue-next';
import NavAdministrator from './NavAdministrator.vue';

const administrator_side: NavItem[]  = [
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
    <Sidebar>

        <SidebarContent>
            <NavAdministrator :items="administrator_side" />
        </SidebarContent>
       
    </Sidebar>
    <slot />
</template>