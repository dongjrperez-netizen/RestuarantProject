<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Badge from '@/components/ui/badge/Badge.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { Plus, Edit2, Trash2, MoreHorizontal, ArrowUp, ArrowDown, Eye, EyeOff } from 'lucide-vue-next';

interface MenuCategory {
  category_id: number;
  category_name: string;
  description?: string;
  sort_order: number;
  is_active: boolean;
  dishes_count: number;
  created_at: string;
  updated_at: string;
}

interface Props {
  categories: MenuCategory[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Management', href: '/menu' },
  { title: 'Manage Categories', href: '/menu-categories' },
];

// Create form
const createForm = useForm({
  category_name: '',
  description: '',
  sort_order: undefined as number | undefined,
});

// Edit form
const editForm = useForm({
  category_id: null as number | null,
  category_name: '',
  description: '',
  sort_order: undefined as number | undefined,
  is_active: true as boolean,
});

const showCreateDialog = ref(false);
const showEditDialog = ref(false);

const activeCategories = computed(() => props.categories.filter(cat => cat.is_active).length);
const totalDishes = computed(() => props.categories.reduce((sum, cat) => sum + cat.dishes_count, 0));

const createCategory = () => {
  createForm.post('/menu-categories', {
    onSuccess: () => {
      createForm.reset();
      showCreateDialog.value = false;
    },
  });
};

const openEditDialog = (category: MenuCategory) => {
  editForm.reset();
  editForm.category_id = category.category_id;
  editForm.category_name = category.category_name;
  editForm.description = category.description || '';
  editForm.sort_order = category.sort_order;
  editForm.is_active = category.is_active;
  showEditDialog.value = true;
};

const updateCategory = () => {
  editForm.put(`/menu-categories/${editForm.category_id}`, {
    onSuccess: () => {
      editForm.reset();
      showEditDialog.value = false;
    },
  });
};

const toggleCategoryStatus = (category: MenuCategory) => {
  router.post(`/menu-categories/${category.category_id}/status`, {
    is_active: !category.is_active
  }, {
    preserveState: true,
  });
};

const deleteCategory = (category: MenuCategory) => {
  if (category.dishes_count > 0) {
    alert(`Cannot delete "${category.category_name}" because it contains ${category.dishes_count} dishes. Please reassign or delete the dishes first.`);
    return;
  }

  if (confirm(`Are you sure you want to delete "${category.category_name}"? This action cannot be undone.`)) {
    router.delete(`/menu-categories/${category.category_id}`, {
      preserveState: true,
    });
  }
};

const moveCategory = (category: MenuCategory, direction: 'up' | 'down') => {
  const sortedCategories = [...props.categories].sort((a, b) => a.sort_order - b.sort_order);
  const currentIndex = sortedCategories.findIndex(c => c.category_id === category.category_id);

  if ((direction === 'up' && currentIndex === 0) ||
      (direction === 'down' && currentIndex === sortedCategories.length - 1)) {
    return;
  }

  const newIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;
  const targetCategory = sortedCategories[newIndex];

  // Swap sort orders
  const reorderData = [
    { category_id: category.category_id, sort_order: targetCategory.sort_order },
    { category_id: targetCategory.category_id, sort_order: category.sort_order }
  ];

  router.post('/menu-categories/reorder', {
    categories: reorderData
  }, {
    preserveState: true,
  });
};

const getSortedCategories = computed(() => {
  return [...props.categories].sort((a, b) => a.sort_order - b.sort_order);
});
</script>

<template>
  <Head title="Manage Categories" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-8">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Manage Categories</h1>
          <p class="text-muted-foreground">Organize your menu items into categories</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child>
            <Button>
              <Plus class="w-4 h-4 mr-2" />
              Add Category
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create New Category</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="createCategory" class="space-y-4">
              <div class="space-y-2">
                <Label for="category_name">Category Name *</Label>
                <Input
                  id="category_name"
                  v-model="createForm.category_name"
                  placeholder="Enter category name"
                  :class="{ 'border-red-500': createForm.errors.category_name }"
                />
                <p v-if="createForm.errors.category_name" class="text-sm text-red-500">
                  {{ createForm.errors.category_name }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="description">Description</Label>
                <Textarea
                  id="description"
                  v-model="createForm.description"
                  placeholder="Optional description"
                  rows="3"
                />
              </div>

              <div class="space-y-2">
                <Label for="sort_order">Sort Order</Label>
                <Input
                  id="sort_order"
                  v-model="createForm.sort_order"
                  type="number"
                  placeholder="Leave empty for auto"
                />
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" @click="showCreateDialog = false">
                  Cancel
                </Button>
                <Button type="submit" :disabled="createForm.processing">
                  {{ createForm.processing ? 'Creating...' : 'Create Category' }}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>


      <!-- Categories Table -->
      <Card>
        <CardHeader>
          <CardTitle>Categories</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Order</TableHead>
                <TableHead>Category Name</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Dishes</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right pr-8">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(category, index) in getSortedCategories" :key="category.category_id">
                <TableCell>
                  <div class="flex items-center space-x-1">
                    <span class="text-sm font-mono">{{ category.sort_order }}</span>
                    <div class="flex flex-col">
                      <Button
                        variant="ghost"
                        size="sm"
                        :disabled="index === 0"
                        @click="moveCategory(category, 'up')"
                        class="h-4 w-4 p-0"
                      >
                        <ArrowUp class="h-3 w-3" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        :disabled="index === getSortedCategories.length - 1"
                        @click="moveCategory(category, 'down')"
                        class="h-4 w-4 p-0"
                      >
                        <ArrowDown class="h-3 w-3" />
                      </Button>
                    </div>
                  </div>
                </TableCell>
                <TableCell class="font-medium">
                  {{ category.category_name }}
                </TableCell>
                <TableCell>
                  <div v-if="category.description" class="text-sm text-muted-foreground max-w-xs">
                    {{ category.description.substring(0, 100) }}{{ category.description.length > 100 ? '...' : '' }}
                  </div>
                  <div v-else class="text-xs text-muted-foreground italic">
                    No description
                  </div>
                </TableCell>
                <TableCell>
                  <Badge variant="outline">
                    {{ category.dishes_count }} dishes
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="category.is_active ? 'default' : 'secondary'">
                    {{ category.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right pr-8">
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                        <MoreHorizontal class="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem @click="openEditDialog(category)" class="flex items-center">
                        <Edit2 class="mr-2 h-4 w-4" />
                        Edit Category
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        @click="toggleCategoryStatus(category)"
                        class="flex items-center"
                      >
                        <Eye v-if="!category.is_active" class="mr-2 h-4 w-4" />
                        <EyeOff v-else class="mr-2 h-4 w-4" />
                        {{ category.is_active ? 'Deactivate' : 'Activate' }}
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        @click="deleteCategory(category)"
                        class="flex items-center text-destructive focus:text-destructive"
                        :disabled="category.dishes_count > 0"
                      >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="categories.length === 0">
                <TableCell colspan="6" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <div class="text-lg mb-2">No categories found</div>
                    <div class="text-sm">Create your first category to organize your menu items.</div>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Edit Dialog -->
      <Dialog v-model:open="showEditDialog">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Category</DialogTitle>
          </DialogHeader>
          <form @submit.prevent="updateCategory" class="space-y-4">
            <div class="space-y-2">
              <Label for="edit_category_name">Category Name *</Label>
              <Input
                id="edit_category_name"
                v-model="editForm.category_name"
                placeholder="Enter category name"
                :class="{ 'border-red-500': editForm.errors.category_name }"
              />
              <p v-if="editForm.errors.category_name" class="text-sm text-red-500">
                {{ editForm.errors.category_name }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="edit_description">Description</Label>
              <Textarea
                id="edit_description"
                v-model="editForm.description"
                placeholder="Optional description"
                rows="3"
              />
            </div>

            <div class="space-y-2">
              <Label for="edit_sort_order">Sort Order</Label>
              <Input
                id="edit_sort_order"
                v-model="editForm.sort_order"
                type="number"
              />
            </div>

            <div class="flex items-center space-x-2">
              <input
                type="checkbox"
                id="edit_is_active"
                v-model="editForm.is_active"
                class="rounded"
              />
              <Label for="edit_is_active">Category is active</Label>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" @click="showEditDialog = false">
                Cancel
              </Button>
              <Button type="submit" :disabled="editForm.processing">
                {{ editForm.processing ? 'Updating...' : 'Update Category' }}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>