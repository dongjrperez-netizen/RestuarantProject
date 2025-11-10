<script setup lang="ts">
import { ref, watch, onBeforeMount } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  LayoutGrid
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

const navItems = [
  { title: "Subscription", icon: LayoutGrid },

]

const openCollapsibles = ref<Record<string, boolean>>({})

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
              <SidebarMenuItem>
                <SidebarMenuButton asChild>
                  <div>
                    <component :is="item.icon" class="mr-2 h-4 w-4"/>
                    <span>{{ item.title }}</span>
                  </div>
                </SidebarMenuButton>
              </SidebarMenuItem>

             

            </template>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>
  </Sidebar>
</template>
