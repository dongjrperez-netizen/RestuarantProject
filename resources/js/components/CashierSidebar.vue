<script setup lang="ts">
import { ref, watch, onBeforeMount, computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import {
  Receipt, DollarSign, LogOut, Plus
} from "lucide-vue-next"
import type { FunctionalComponent } from 'vue'
import type { LucideProps } from 'lucide-vue-next'

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
import Button from '@/components/ui/button/Button.vue'

// Define navigation item types
interface NavItem {
  title: string
  href?: string
  icon: FunctionalComponent<LucideProps, {}, any, {}>
  children?: NavItem[]
}

// Page props (Inertia)
const page = usePage()

// Derive restaurant information shared from backend
const restaurant = computed(() => {
  const auth: any = page.props.auth || {}
  const user = auth.user || null

  if (auth.restaurant) {
    return auth.restaurant
  }

  // Fallbacks if restaurant was attached directly to the cashier or owner
  return user?.restaurantData || user?.restaurant_data || null
})

const restaurantName = computed(() => {
  if (restaurant.value?.restaurant_name) {
    return restaurant.value.restaurant_name
  }

  const auth: any = page.props.auth || {}
  const user = auth.user || null

  if (user?.name) {
    return user.name
  }

  return 'Your Restaurant'
})

const restaurantLogoUrl = computed(() => {
  if (!restaurant.value) return null

  // Check for logo field and prepend storage path if it exists
  if (restaurant.value.logo) {
    return `/storage/${restaurant.value.logo}`
  }

  // Fallback to other possible fields
  return restaurant.value.logo_url || restaurant.value.logo_path || null
})

// Cashier-specific navigation items
const navItems: NavItem[] = [
  { title: "Customer Bills", href: "/cashier/bills", icon: Receipt },
  { title: "Payments History", href: "/cashier/successful-payments", icon: DollarSign },
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

// Logout functionality
const logoutForm = useForm({})
const logout = () => {
  logoutForm.post(route('logout'))
}
</script>

<template>
  <Sidebar class="flex flex-col h-full">
    <SidebarHeader class="border-b border-sidebar-border p-4">
      <div class="flex items-center gap-3">
        <!-- Logo or placeholder (no click behavior) -->
        <div
          class="flex h-8 w-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground overflow-hidden border border-sidebar-border/60"
        >
          <img
            v-if="restaurantLogoUrl"
            :src="restaurantLogoUrl"
            :alt="`${restaurantName} logo`"
            class="h-8 w-8 object-cover"
          />
          <Plus
            v-else
            class="h-4 w-4 text-sidebar-primary-foreground/80"
          />
        </div>
        <div class="flex flex-col min-w-0">
          <span
            class="font-semibold text-lg truncate"
            :title="restaurantName"
          >
            {{ restaurantName }}
          </span>
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
                  <Link :href="item.href!" :preserve-state="true">
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
                          <Link :href="child.href!" :preserve-state="true">
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

    <SidebarFooter class="border-t border-sidebar-border p-3">
      <Button
        @click="logout"
        variant="ghost"
        class="w-full justify-start gap-2 px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50"
        :disabled="logoutForm.processing"
      >
        <LogOut class="h-4 w-4" />
        <span class="text-sm">{{ logoutForm.processing ? 'Logging out...' : 'Logout' }}</span>
      </Button>
    </SidebarFooter>
  </Sidebar>
</template>