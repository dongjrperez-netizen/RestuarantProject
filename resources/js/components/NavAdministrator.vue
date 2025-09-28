<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
     items: Array<any>;
}>();
const openGroups = ref<string[]>([]);

const toggleGroup = (title: string) => {
  const index = openGroups.value.indexOf(title);
  if (index > -1) {
    openGroups.value.splice(index, 1);
  } else {
    openGroups.value.push(title);
  }
};

const page = usePage();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Client Management</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <div v-if="item.isGroup" class="mb-2">
                <button
                  @click="toggleGroup(item.title)"
                  class="flex w-full items-center justify-between px-3 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                  <div class="flex items-center">
                    <component :is="item.icon" />
                    <span>{{ item.title }}</span>
                  </div>
                  <ChevronDownIcon
                    class="h-4 w-4 transition-transform"
                    :class="{ 'rotate-180': openGroups.includes(item.title) }"
                  />
                </button>

                <div
                  v-show="openGroups.includes(item.title)"
                  class="ml-8 mt-1 space-y-1"
                >
                  <Link
                    v-for="child in item.children"
                    :key="child.href"
                    :href="child.href"
                    class="flex items-center px-3 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800"
                    active-class="bg-gray-200 dark:bg-gray-700"
                  >
                    <component :is="child.icon" class="mr-3 h-5 w-5" />
                    <span>{{ child.title }}</span>
                  </Link>
                </div>
              </div>

              <Link
                v-else
                :href="item.href"
                class="flex items-center px-3 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800"
                active-class="bg-gray-200 dark:bg-gray-700"
              >
                <component :is="item.icon" class="mr-3 h-5 w-5" />
                <span>{{ item.title }}</span>
              </Link>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
    
</template>



