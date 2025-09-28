<script setup lang="ts">
import AdminUserInfo from '@/components/AdminUserInfo.vue';
import AdminUserMenuContent from './AdminUserMenuContent.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';

interface Administrator {
    id: number;
    email: string;
}

const page = usePage();
const admin = page.props.auth.user as Administrator | null;
const { isMobile, state } = useSidebar();
</script>

<template>
    <SidebarMenu v-if="admin">
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground hover:bg-sidebar-accent/50 transition-colors duration-200 min-w-fit px-3"
                    >
                        <AdminUserInfo :admin="admin" :show-email="false" />
                        <ChevronsUpDown class="ml-auto size-4 opacity-60" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    :side="isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'"
                    align="end"
                    :side-offset="4"
                >
                    <AdminUserMenuContent :admin="admin" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
    <div v-else class="p-2 text-sm text-muted-foreground">
        No admin user
    </div>
</template>