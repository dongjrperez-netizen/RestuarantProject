<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Building2, Package, LogOut, Home, Plus, Menu, X, ShoppingCart, History } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { ref } from 'vue';

interface Props {
  supplier?: {
    supplier_id: number;
    supplier_name: string;
    email: string;
  };
}

defineProps<Props>();

const mobileMenuOpen = ref(false);

const logout = () => {
  router.post('/supplier/logout');
};

const closeMobileMenu = () => {
  mobileMenuOpen.value = false;
};
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo -->
          <div class="flex items-center space-x-3">
            <!-- Mobile Menu Button -->
            <Button
              variant="ghost"
              size="sm"
              class="md:hidden"
              @click="mobileMenuOpen = !mobileMenuOpen"
            >
              <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
              <X v-else class="h-6 w-6" />
            </Button>

            <Building2 class="h-6 w-6 md:h-8 md:w-8 text-primary" />
            <div>
              <h1 class="text-lg md:text-xl font-semibold text-gray-900">Supplier Portal</h1>
              <p class="text-xs md:text-sm text-gray-500 hidden sm:block" v-if="supplier">{{ supplier.supplier_name }}</p>
            </div>
          </div>

          <!-- Desktop Navigation -->
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
              href="/supplier/purchase-orders"
              class="flex items-center space-x-2 text-gray-600 hover:text-gray-900 transition-colors"
              :class="{ 'text-primary font-medium': $page.url.startsWith('/supplier/purchase-orders') }"
            >
              <ShoppingCart class="h-4 w-4" />
              <span>Purchase Orders</span>
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
              href="/supplier/payments/history"
              class="flex items-center space-x-2 text-gray-600 hover:text-gray-900 transition-colors"
              :class="{ 'text-primary font-medium': $page.url.startsWith('/supplier/payments') }"
            >
              <History class="h-4 w-4" />
              <span>History</span>
            </Link>
          </nav>

          <!-- User Menu -->
          <div class="flex items-center space-x-2 md:space-x-4">
            <span class="text-xs md:text-sm text-gray-600 hidden lg:block" v-if="supplier">{{ supplier.email }}</span>
            <Button @click="logout" variant="outline" size="sm">
              <LogOut class="h-4 w-4 md:mr-2" />
              <span class="hidden md:inline">Logout</span>
            </Button>
          </div>
        </div>
      </div>
    </header>

    <!-- Mobile Navigation Menu -->
    <div
      v-if="mobileMenuOpen"
      class="fixed inset-0 z-30 md:hidden"
      @click="closeMobileMenu"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

      <!-- Sidebar -->
      <nav
        class="absolute left-0 top-16 bottom-0 w-64 bg-white shadow-xl overflow-y-auto"
        @click.stop
      >
        <div class="p-4 space-y-2">
          <!-- User Info -->
          <div class="pb-4 mb-4 border-b">
            <p class="text-sm font-medium text-gray-900" v-if="supplier">{{ supplier.supplier_name }}</p>
            <p class="text-xs text-gray-500" v-if="supplier">{{ supplier.email }}</p>
          </div>

          <!-- Navigation Links -->
          <Link
            href="/supplier/dashboard"
            @click="closeMobileMenu"
            class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors"
            :class="{ 'bg-primary/10 text-primary font-medium': $page.url.startsWith('/supplier/dashboard') }"
          >
            <Home class="h-5 w-5" />
            <span>Dashboard</span>
          </Link>

          <Link
            href="/supplier/purchase-orders"
            @click="closeMobileMenu"
            class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors"
            :class="{ 'bg-primary/10 text-primary font-medium': $page.url.startsWith('/supplier/purchase-orders') }"
          >
            <ShoppingCart class="h-5 w-5" />
            <span>Purchase Orders</span>
          </Link>

          <Link
            href="/supplier/ingredients"
            @click="closeMobileMenu"
            class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors"
            :class="{ 'bg-primary/10 text-primary font-medium': $page.url.startsWith('/supplier/ingredients') }"
          >
            <Package class="h-5 w-5" />
            <span>My Ingredients</span>
          </Link>

          <Link
            href="/supplier/ingredients/create"
            @click="closeMobileMenu"
            class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors"
          >
            <Plus class="h-5 w-5" />
            <span>Add Ingredient</span>
          </Link>

          <Link
            href="/supplier/payments/history"
            @click="closeMobileMenu"
            class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors"
            :class="{ 'bg-primary/10 text-primary font-medium': $page.url.startsWith('/supplier/payments') }"
          >
            <History class="h-5 w-5" />
            <span>Payment History</span>
          </Link>

          <!-- Logout Button -->
          <div class="pt-4 mt-4 border-t">
            <Button @click="logout" variant="outline" class="w-full justify-start">
              <LogOut class="h-5 w-5 mr-3" />
              <span>Logout</span>
            </Button>
          </div>
        </div>
      </nav>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto">
      <slot />
    </main>
  </div>
</template>