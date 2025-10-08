<script setup lang="ts">

import { Sidebar, SidebarContent,SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import {LayoutGrid,ClipboardList ,    } from 'lucide-vue-next';
import NavAccountUpdate from './NavAccountUpdate.vue';



const NavAccontupdate: NavItem[]  = [

    {
        title: 'Account Request',
        href: '/account/update',
        icon: LayoutGrid ,
    },
];




</script>

<template>
    <Sidebar collapsible="icon" variant="inset">


        <SidebarContent>
            <NavAccountUpdate :items="NavAccontupdate" />
        </SidebarContent>
       
    </Sidebar>
    <slot />
</template>