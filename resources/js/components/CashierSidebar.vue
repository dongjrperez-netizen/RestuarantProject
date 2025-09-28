<script setup lang="ts">
import { ref, watch, onBeforeMount } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  LayoutGrid, Receipt, ShoppingCart, Clock,
  DollarSign, Users, FileText, Calculator
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

// Cashier-specific navigation items
const navItems = [
  { title: "Customer Bills", href: "/cashier/bills", icon: Receipt },
  { title: "Successful Payments", href: "/cashier/successful-payments", icon: DollarSign },
  {
    title: "Orders & Payments",
    icon: ShoppingCart,
    children: [
      { title: "Process Payments", href: '/cashier/process-payment', icon: Calculator },
      { title: "Order History", href: '/cashier/orders', icon: FileText },
      { title: "Pending Orders", href: '/cashier/pending-orders', icon: Clock },
    ]
  },
  {
    title: "Reports",
    icon: Receipt,
    children: [
      { title: "Daily Sales", href: "/cashier/reports/daily", icon: DollarSign },
      { title: "Payment Methods", href: "/cashier/reports/payments", icon: Receipt },
    ]
  },
  { title: "Table Status", href: "/cashier/tables", icon: Users },
]

// Controlled state for collapsibles
const openCollapsibles = ref<Record<string, boolean>>({})

// Initialize collapsibles
navItems.forEach(item => {
  if (item.children) openCollapsibles.value[item.title] = false
})

// Persist collapsible state across navigation
onBeforeMount(() => {
  const saved = sessionStorage.getItem('cashierSidebarState')
  if (saved) openCollapsibles.value = JSON.parse(saved)
})

watch(openCollapsibles, (val) => {
  sessionStorage.setItem('cashierSidebarState', JSON.stringify(val))
}, { deep: true })
</script>

<template>
  <Sidebar class="flex flex-col h-full">
    <SidebarHeader class="border-b border-sidebar-border p-4">
      <div class="flex items-center gap-3">
        <img src="/Logo.png" alt="ServeWise Logo" class="h-8 w-8 object-contain" />
        <div class="flex flex-col">
          <span class="font-semibold text-lg">ServeWise</span>
          <span class="text-xs text-muted-foreground">Cashier Portal</span>
        </div>
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