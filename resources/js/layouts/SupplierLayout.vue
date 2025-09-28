<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Building2, Package, LogOut, Home, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

interface Props {
  supplier?: {
    supplier_id: number;
    supplier_name: string;
    email: string;
  };
}

defineProps<Props>();

const logout = () => {
  router.post('/supplier/logout');
};
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo -->
          <div class="flex items-center space-x-4">
            <Building2 class="h-8 w-8 text-primary" />
            <div>
              <h1 class="text-xl font-semibold text-gray-900">Supplier Portal</h1>
              <p class="text-sm text-gray-500" v-if="supplier">{{ supplier.supplier_name }}</p>
            </div>
          </div>

          <!-- Navigation -->
          <nav class="hidden md:flex items-center space-x-6">
            <Link
              href="/supplier/dashboard"
              class="flex items-center space-x-2 text-gray-600 hover:text-gray-900 transition-colors"
              :class="{ 'text-primary font-medium': $page.url.startsWith('/supplier/dashboard') }"
            >
              <Home class="h-4 w-4" />
              <span>Dashboard</span>
            </Link>
            
            <Link
              href="/supplier/ingredients"
              class="flex items-center space-x-2 text-gray-600 hover:text-gray-900 transition-colors"
              :class="{ 'text-primary font-medium': $page.url.startsWith('/supplier/ingredients') }"
            >
              <Package class="h-4 w-4" />
              <span>My Ingredients</span>
            </Link>

            <Link
              href="/supplier/ingredients/create"
              class="flex items-center space-x-2 text-gray-600 hover:text-gray-900 transition-colors"
            >
              <Plus class="h-4 w-4" />
              <span>Add Ingredient</span>
            </Link>
          </nav>

          <!-- User Menu -->
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600" v-if="supplier">{{ supplier.email }}</span>
            <Button @click="logout" variant="outline" size="sm">
              <LogOut class="h-4 w-4 mr-2" />
              Logout
            </Button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <slot />
      </div>
    </main>
  </div>
</template>