<script setup lang="ts">
import { ref, watch, onBeforeMount } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  Users, UserRound, Box, ClipboardList,
  UtensilsCrossed, ShoppingCart, Truck,
  Folder, BookOpen, LayoutGrid
} from "lucide-vue-next"

import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuSub,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSubItem
} from "@/components/ui/sidebar"

import { Collapsible, CollapsibleTrigger, CollapsibleContent } from "@/components/ui/collapsible"
import NavFooter from "@/components/NavFooter.vue"
import NavUser from "@/components/NavUser.vue"

// Navigation items
const navItems = [
  { title: "Subscription", href: "/subscription/renewal", icon: LayoutGrid },

]

// Controlled state for collapsibles
const openCollapsibles = ref<Record<string, boolean>>({})

// Initialize collapsibles
// navItems.forEach(item => {
//   if (item.children) openCollapsibles.value[item.title] = false
// })

onBeforeMount(() => {
  const saved = sessionStorage.getItem('sidebarState')
  if (saved) openCollapsibles.value = JSON.parse(saved)
})

watch(openCollapsibles, (val) => {
  sessionStorage.setItem('sidebarState', JSON.stringify(val))
}, { deep: true })
</script>

<template>
  <Sidebar class="flex flex-col h-full">
    <SidebarContent class="flex-1">
      <SidebarGroup>
        <SidebarGroupLabel>Restaurant</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <template v-for="item in navItems" :key="item.title">
              
              <!-- Single link -->
              <SidebarMenuItem v-if="item.href">
                <SidebarMenuButton asChild>
                  <Link :href="item.href" :preserve-state="true">
                    <component :is="item.icon" class="mr-2 h-4 w-4"/>
                    <span>{{ item.title }}</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>

              <!-- Collapsible group -->
              <Collapsible
                v-else
                v-model:open="openCollapsibles[item.title]"
              >
                <SidebarMenuItem>
                  <CollapsibleTrigger asChild>
                    <SidebarMenuButton>
                      <component :is="item.icon" class="mr-2 h-4 w-4" />
                      <span>{{ item.title }}</span>
                    </SidebarMenuButton>
                  </CollapsibleTrigger>

                </SidebarMenuItem>
              </Collapsible>

            </template>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>
  </Sidebar>
</template>
