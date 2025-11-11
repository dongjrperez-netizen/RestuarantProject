<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { Eye, Edit2, Play, Pause, Trash2, MoreHorizontal } from 'lucide-vue-next';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';

interface MenuCategory {
  category_id: number;
  category_name: string;
  sort_order: number;
  is_active: boolean;
}

interface DishPricing {
  pricing_id: number;
  price_type: string;
  base_price: number;
  promotional_price?: number;
  promo_start_date?: string;
  promo_end_date?: string;
}

interface DishVariant {
  variant_id: number;
  size_name: string;
  price_modifier: number;
  quantity_multiplier: number;
  is_default: boolean;
  is_available: boolean;
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  preparation_time?: number;
  serving_size?: number;
  serving_unit?: string;
  image_url?: string;
  calories?: number;
  allergens?: string[];
  dietary_tags?: string[];
  status: 'draft' | 'active' | 'inactive' | 'archived';
  price?: number;
  category?: MenuCategory;
  pricing?: DishPricing[];
  variants?: DishVariant[];
  created_at: string;
  updated_at: string;
}

interface Props {
  dishes: Dish[];
  categories: MenuCategory[];
  filters: {
    category_id?: number;
    status?: string;
    search?: string;
  };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category_id || 'all');
const selectedStatus = ref(props.filters.status || 'all');

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Management', href: '/menu' },
];

const statusOptions = [
  { value: 'all', label: 'All Status' },
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'archived', label: 'Archived' },
];

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'active': return 'default';
    case 'draft': return 'secondary';
    case 'inactive': return 'destructive';
    case 'archived': return 'outline';
    default: return 'secondary';
  }
};

const getDineInPrice = (dish: Dish) => {
  if (dish.price) {
    return `₱${Number(dish.price).toLocaleString()}`;
  }
  return 'N/A';
};

const applyFilters = () => {
  router.get('/menu', {
    search: searchQuery.value || undefined,
    category_id: (selectedCategory.value && selectedCategory.value !== 'all') ? selectedCategory.value : undefined,
    status: (selectedStatus.value && selectedStatus.value !== 'all') ? selectedStatus.value : undefined,
  }, {
    preserveState: true,
    replace: true,
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  selectedCategory.value = 'all';
  selectedStatus.value = 'all';
  applyFilters();
};

const updateDishStatus = (dish: Dish, newStatus: string) => {
  router.post(`/menu/${dish.dish_id}/status`, {
    status: newStatus
  }, {
    preserveState: true,
  });
};

const deleteDish = (dish: Dish) => {
  if (confirm(`Are you sure you want to delete "${dish.dish_name}"? This action cannot be undone.`)) {
    router.delete(`/menu/${dish.dish_id}`, {
      preserveState: true,
      onSuccess: () => {
        // Refresh the page or show success message
      }
    });
  }
};

const activeDishes = computed(() => (props.dishes || []).filter(dish => dish.status === 'active').length);
const draftDishes = computed(() => (props.dishes || []).filter(dish => dish.status === 'draft').length);
</script>

<template>
  <Head title="Menu Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-8">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Menu Management</h1>
          <p class="text-muted-foreground">Manage your restaurant menu items and categories</p>
        </div>
        <div class="flex space-x-2">
          <Link href="/menu-categories">
            <Button variant="outline">Manage Categories</Button>
          </Link>
          <Link :href="route('menu.create')">
            <Button>Add New Dish</Button>
          </Link>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-3 md:grid-cols-4">
      <Card class="h-24 flex flex-col justify-between p-3">
        <div class="flex items-center justify-between">
          <p class="text-xs font-medium text-muted-foreground">Total Dishes</p>
        </div>
        <div class="text-xl font-bold leading-tight">
          {{ (dishes || []).length }}
        </div>
      </Card>

      <Card class="h-24 flex flex-col justify-between p-3">
        <div class="flex items-center justify-between">
          <p class="text-xs font-medium text-muted-foreground">Active Dishes</p>
        </div>
        <div class="text-xl font-bold leading-tight">
          {{ activeDishes }}
        </div>
      </Card>

      <Card class="h-24 flex flex-col justify-between p-3">
        <div class="flex items-center justify-between">
          <p class="text-xs font-medium text-muted-foreground">Draft Dishes</p>
        </div>
        <div class="text-xl font-bold leading-tight">
          {{ draftDishes }}
        </div>
      </Card>

      <Card class="h-24 flex flex-col justify-between p-3">
        <div class="flex items-center justify-between">
          <p class="text-xs font-medium text-muted-foreground">Categories</p>
        </div>
        <div class="text-xl font-bold leading-tight">
          {{ (categories || []).length }}
        </div>
      </Card>
    </div>


      <!-- Dishes Table -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Menu Items</CardTitle>
            <div class="flex items-center gap-4">
              <div class="w-[200px]">
                <Input
                  v-model="searchQuery"
                  placeholder="Search dishes..."
                  @keyup.enter="applyFilters"
                />
              </div>

              <div class="w-[150px]">
                <Select v-model="selectedCategory" @update:model-value="applyFilters">
                  <SelectTrigger>
                    <SelectValue placeholder="All Categories" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Categories</SelectItem>
                    <SelectItem
                      v-for="category in (categories || [])"
                      :key="category.category_id"
                      :value="category.category_id.toString()"
                    >
                      {{ category.category_name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="w-[150px]">
                <Select v-model="selectedStatus" @update:model-value="applyFilters">
                  <SelectTrigger>
                    <SelectValue placeholder="All Status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="option in statusOptions"
                      :key="option.value"
                      :value="option.value"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <Button variant="outline" size="sm" @click="clearFilters">Clear</Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Dish Name</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Category</TableHead>
                <TableHead>Variants</TableHead>
                <TableHead>Price</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="dish in (dishes || [])" :key="dish.dish_id">
                <TableCell class="font-medium">
                  <div class="font-semibold">{{ dish.dish_name }}</div>
                </TableCell>
                <TableCell>
                  <div v-if="dish.description" class="text-sm text-muted-foreground max-w-xs">
                    {{ dish.description?.substring(0, 120) }}{{ dish.description?.length > 120 ? '...' : '' }}
                  </div>
                  <div v-else class="text-xs text-muted-foreground italic">
                    No description
                  </div>
                </TableCell>
                <TableCell>
                  <Badge variant="secondary">
                    {{ dish.category?.category_name || 'Uncategorized' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div v-if="dish.variants && dish.variants.length > 0" class="space-y-1">
                    <div v-for="variant in dish.variants" :key="variant.variant_id" class="text-xs">
                      <span class="font-medium">{{ variant.size_name }}</span>
                      <span v-if="variant.is_default" class="ml-1 text-green-600 text-[10px]">(Default)</span>
                    </div>
                  </div>
                  <div v-else class="text-xs text-muted-foreground italic">
                    No variants
                  </div>
                </TableCell>
                <TableCell>
                  <div v-if="dish.variants && dish.variants.length > 0" class="space-y-1">
                    <div v-for="variant in dish.variants" :key="variant.variant_id" class="text-xs">
                      ₱{{ Number(variant.price_modifier).toFixed(2) }}
                    </div>
                  </div>
                  <div v-else>
                    {{ getDineInPrice(dish) }}
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(dish.status)">
                    {{ dish.status }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right">
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                        <MoreHorizontal class="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem @click="router.visit(`/menu/${dish.dish_id}`)" class="flex items-center cursor-pointer">
                        <Eye class="mr-2 h-4 w-4" />
                        View Details
                      </DropdownMenuItem>
                      <DropdownMenuItem @click="router.visit(`/menu/${dish.dish_id}/edit`)" class="flex items-center cursor-pointer">
                        <Edit2 class="mr-2 h-4 w-4" />
                        Edit Dish
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        v-if="dish.status === 'draft'"
                        @click="updateDishStatus(dish, 'active')"
                        class="flex items-center"
                      >
                        <Play class="mr-2 h-4 w-4" />
                        Activate
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        v-else-if="dish.status === 'active'"
                        @click="updateDishStatus(dish, 'inactive')"
                        class="flex items-center"
                      >
                        <Pause class="mr-2 h-4 w-4" />
                        Deactivate
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        @click="deleteDish(dish)"
                        class="flex items-center text-destructive focus:text-destructive"
                      >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="!dishes || dishes.length === 0">
                <TableCell colspan="7" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <div class="text-lg mb-2">No dishes found</div>
                    <div class="text-sm">Get started by adding your first dish.</div>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>