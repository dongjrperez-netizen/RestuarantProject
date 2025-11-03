<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref, computed, watch } from 'vue';
import { Eye, Edit2, Play, Pause, Trash2, MoreHorizontal } from 'lucide-vue-next';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { type BreadcrumbItem } from '@/types';

interface MenuCategory {
  category_id: number;
  category_name: string;
  sort_order: number;
  is_active: boolean;
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  category?: MenuCategory;
  price?: number;
  status: 'active' | 'inactive' | 'archived';
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
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'archived', label: 'Archived' },
];

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'active': return 'default';
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

// 🔍 Automatically trigger search when typing or changing filters
watch([searchQuery, selectedCategory, selectedStatus], () => {
  applyFilters();
});

const updateDishStatus = (dish: Dish, newStatus: string) => {
  router.post(`/menu/${dish.dish_id}/status`, { status: newStatus }, { preserveState: true });
};

const deleteDish = (dish: Dish) => {
  if (confirm(`Are you sure you want to delete "${dish.dish_name}"? This action cannot be undone.`)) {
    router.delete(`/menu/${dish.dish_id}`, { preserveState: true });
  }
};

const activeDishes = computed(() => (props.dishes || []).filter(dish => dish.status === 'active').length);
</script>

<template>
  <Head title="Menu Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-8 mt-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="text-3xl font-bold tracking-tight">
          <!-- Optional title text -->
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


      <!-- Filters and Search -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Menu Items</CardTitle>
            <div class="flex items-center gap-4">
              <div class="w-[200px]">
                <Input
                  v-model="searchQuery"
                  placeholder="Search dishes..."
                />
              </div>

              <div class="w-[150px]">
                <Select v-model="selectedCategory">
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
                <Select v-model="selectedStatus">
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
                <TableHead>Price</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="dish in (dishes || [])" :key="dish.dish_id">
                <TableCell class="font-medium">{{ dish.dish_name }}</TableCell>
                <TableCell>{{ dish.description || 'No description' }}</TableCell>
                <TableCell>
                  <Badge variant="secondary">{{ dish.category?.category_name || 'Uncategorized' }}</Badge>
                </TableCell>
                <TableCell>{{ getDineInPrice(dish) }}</TableCell>
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
                      <DropdownMenuItem @click="router.visit(`/menu/${dish.dish_id}`)">
                        <Eye class="mr-2 h-4 w-4" /> View
                      </DropdownMenuItem>
                      <DropdownMenuItem @click="router.visit(`/menu/${dish.dish_id}/edit`)">
                        <Edit2 class="mr-2 h-4 w-4" /> Edit
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        v-if="dish.status === 'active'"
                        @click="updateDishStatus(dish, 'inactive')"
                      >
                        <Pause class="mr-2 h-4 w-4" /> Deactivate
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        v-else
                        @click="updateDishStatus(dish, 'active')"
                      >
                        <Play class="mr-2 h-4 w-4" /> Activate
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        class="text-destructive"
                        @click="deleteDish(dish)"
                      >
                        <Trash2 class="mr-2 h-4 w-4" /> Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>

              <TableRow v-if="!dishes || dishes.length === 0">
                <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                  No dishes found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
