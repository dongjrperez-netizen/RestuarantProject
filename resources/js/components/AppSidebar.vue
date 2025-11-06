<script setup lang="ts">
import { ref, watch, onBeforeMount } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  Users, UserRound, Box, ClipboardList,
  UtensilsCrossed, ShoppingCart, Truck, Receipt,
  Folder, BookOpen, LayoutGrid, Package, Warehouse, CreditCard, ChefHat, FileText, Calendar, BarChart3, ChevronRight
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
  SidebarMenuSubItem,
  SidebarHeader
} from "@/components/ui/sidebar"

import { Collapsible, CollapsibleTrigger, CollapsibleContent } from "@/components/ui/collapsible"

// Navigation items
const navItems = [
  { title: "Dashboard", href: "/dashboard", icon: LayoutGrid },
  {
    title: "User Management",
    icon: Users,
    children: [
      { title: "Employees", href: '/employees', icon: UserRound },
      { title: "Regular Employees", href: '/regular-employees', icon: Users },
      { title: "My Subscriptions", href: '/user-management/subscriptions', icon: CreditCard },
    ]
  },
  {
    title: "Procurements",
    icon: Package,
    children: [
      { title: "Suppliers", href: "/suppliers", icon: Truck },
      { title: "Purchase Orders", href: "/purchase-orders", icon: ClipboardList },
      { title: "Bills", href: "/bills", icon: Receipt },
    ]
  },
  {
    title: "Menu Management",
    icon: UtensilsCrossed,
    children: [
      { title: "Menu Items", href: "/menu", icon: ClipboardList },
      { title: "Menu Planning", href: "/menu-planning", icon: Calendar },
    ]
  },
  {
    title: "Inventory",
    icon: Warehouse,
    children: [
      { title: "Ingredients", href: "/inventory", icon: ChefHat },
    ]
  },
  { title: "Tables", href: "/pos/tables", icon: Box },
  { title: "Generate reports", href: "/reports", icon: BarChart3 },
]


// Controlled state for collapsibles
const openCollapsibles = ref<Record<string, boolean>>({})

// Initialize collapsibles
navItems.forEach(item => {
  if (item.children) openCollapsibles.value[item.title] = false
})

// Persist collapsible state across navigation
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
    <SidebarHeader class="border-b border-sidebar-border p-4">
      <div class="flex items-center gap-3">
        <img src="/Logo.png" alt="ServeWise Logo" class="h-8 w-8 object-contain" />
        <span class="font-semibold text-lg">ServeWise</span>
      </div>
    </SidebarHeader>
    <SidebarContent class="flex-1">
      <SidebarGroup>
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
                      <ChevronRight
                        class="ml-auto h-4 w-4 transition-transform duration-200"
                        :class="{ 'rotate-90': openCollapsibles[item.title] }"
                      />
                    </SidebarMenuButton>
                  </CollapsibleTrigger>

                  <CollapsibleContent>
                    <SidebarMenuSub>
                      <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                        <SidebarMenuButton asChild>
                          <Link :href="child.href" :preserve-state="true">
                            <component :is="child.icon" class="mr-2 h-4 w-4" />
                            <span>{{ child.title }}</span>
                          </Link>
                        </SidebarMenuButton>
                      </SidebarMenuSubItem>
                    </SidebarMenuSub>
                  </CollapsibleContent>
                </SidebarMenuItem>
              </Collapsible>

            </template>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>
  </Sidebar>
</template>
