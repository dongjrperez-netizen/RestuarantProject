<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItem } from '@/types';
import { 
  Clock, Users, DollarSign, Calculator, 
  ChefHat, AlertTriangle, Leaf, Edit, 
  Trash2, Eye, EyeOff, Archive 
} from 'lucide-vue-next';
import { ref } from 'vue';

interface MenuCategory {
  category_id: number;
  category_name: string;
}

interface DishIngredient {
  ingredient_id: number;
  ingredient_name: string;
  quantity: number;
  unit: string;
  is_optional: boolean;
  preparation_note?: string;
  cost_per_unit: number;
}

interface DishPricing {
  pricing_id: number;
  price_type: string;
  base_price: number;
  promotional_price?: number;
  promo_start_date?: string;
  promo_end_date?: string;
  min_profit_margin: number;
}

interface DishCost {
  cost_id: number;
  ingredient_cost: number;
  labor_cost: number;
  overhead_cost: number;
  total_cost: number;
  calculated_at: string;
}

interface Dish {
  price: any;
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
  category?: MenuCategory;
  ingredients?: DishIngredient[];
  pricing?: DishPricing[];
  costs?: DishCost[];
  created_at: string;
  updated_at: string;
}

interface Props {
  dish: Dish;
}

const props = defineProps<Props>();
const showDeleteConfirmation = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Management', href: '/menu' },
  { title: props.dish.dish_name, href: `/menu/${props.dish.dish_id}` },
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

const getPriceTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    'dine_in': 'Dine In',
    'takeout': 'Takeout',
    'delivery': 'Delivery'
  };
  return labels[type] || type;
};

const getTotalIngredientCost = () => {
  if (!props.dish.ingredients) return 0;
  return props.dish.ingredients.reduce((total, ingredient) => {
    return total + (ingredient.cost_per_unit * ingredient.quantity);
  }, 0);
};

const getLatestCost = () => {
  if (!props.dish.costs || props.dish.costs.length === 0) return null;
  return props.dish.costs[0]; // Assuming costs are sorted by latest first
};

const updateStatus = (newStatus: string) => {
  router.post(`/menu/${props.dish.dish_id}/status`, {
    status: newStatus
  }, {
    preserveState: true,
  });
};

const deleteDish = () => {
  router.delete(`/menu/${props.dish.dish_id}`, {
    onSuccess: () => {
      router.visit('/menu');
    }
  });
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const handleImageError = (event: Event) => {
  const target = event.target as HTMLImageElement;
  if (target) {
    target.style.display = 'none';
    console.warn('Failed to load dish image:', target.src);
  }
};
</script>

<template>
  <Head :title="dish.dish_name" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-6xl mx-auto space-y-6 pl-8">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold tracking-tight">{{ dish.dish_name }}</h1>
            <Badge :variant="getStatusBadgeVariant(dish.status)">
              {{ dish.status }}
            </Badge>
          </div>
          <p v-if="dish.description" class="text-muted-foreground max-w-2xl">
            {{ dish.description }}
          </p>
          <div v-if="dish.category" class="flex items-center gap-2">
            <span class="text-sm text-muted-foreground">Category:</span>
            <Badge variant="outline">{{ dish.category?.category_name }}</Badge>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Link :href="`/menu/${dish.dish_id}/edit`">
            <Button variant="outline">
              <Edit class="w-4 h-4 mr-2" />
              Edit
            </Button>
          </Link>
          
          <Button 
            v-if="dish.status === 'draft'"
            @click="updateStatus('active')"
          >
            <Eye class="w-4 h-4 mr-2" />
            Activate
          </Button>
          
          <Button 
            v-else-if="dish.status === 'active'"
            variant="secondary"
            @click="updateStatus('inactive')"
          >
            <EyeOff class="w-4 h-4 mr-2" />
            Deactivate
          </Button>

          <Button 
            v-if="dish.status !== 'archived'"
            variant="outline"
            @click="updateStatus('archived')"
          >
            <Archive class="w-4 h-4 mr-2" />
            Archive
          </Button>

          <Button 
            variant="destructive"
            @click="showDeleteConfirmation = true"
          >
            <Trash2 class="w-4 h-4 mr-2" />
            Delete
          </Button>
        </div>
      </div>

      <!-- First Row: Dish Details and Price side by side -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Dish Details -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <ChefHat class="w-5 h-5" />
                Dish Details
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-if="dish.preparation_time">
                  <div class="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                    <Clock class="w-4 h-4" />
                    Prep Time
                  </div>
                  <div class="font-medium">{{ dish.preparation_time }} minutes</div>
                </div>

                <div v-if="dish.serving_size">
                  <div class="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                    <Users class="w-4 h-4" />
                    Serving Size
                  </div>
                  <div class="font-medium">{{ dish.serving_size }} {{ dish.serving_unit || 'serving' }}</div>
                </div>

                <div v-if="dish.calories">
                  <div class="text-sm text-muted-foreground mb-1">Calories</div>
                  <div class="font-medium">{{ dish.calories }} kcal</div>
                </div>

                <div>
                  <div class="text-sm text-muted-foreground mb-1">Created</div>
                  <div class="font-medium text-sm">{{ formatDate(dish.created_at) }}</div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Price -->
        <div>
          <Card v-if="dish.price">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Calculator class="w-5 h-5" />
                Price
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold text-green-600">
                ₱{{ Number(dish.price).toLocaleString() }}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Second Row: Main Content and Sidebar -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">

          <!-- Allergens & Dietary Tags -->
          <Card v-if="dish.allergens?.length || dish.dietary_tags?.length">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <AlertTriangle class="w-5 h-5" />
                Allergens & Dietary Information
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div v-if="dish.allergens?.length">
                <h4 class="font-medium mb-2">Allergens</h4>
                <div class="flex flex-wrap gap-2">
                  <Badge v-for="allergen in dish.allergens" :key="allergen" variant="destructive">
                    {{ allergen }}
                  </Badge>
                </div>
              </div>

              <div v-if="dish.dietary_tags?.length">
                <h4 class="font-medium mb-2">Dietary Tags</h4>
                <div class="flex flex-wrap gap-2">
                  <Badge v-for="tag in dish.dietary_tags" :key="tag" variant="outline">
                    <Leaf class="w-3 h-3 mr-1" />
                    {{ tag }}
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Dish Image -->
          <Card v-if="dish.image_url">
            <CardHeader>
              <CardTitle>Dish Image</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="relative aspect-video rounded-lg overflow-hidden bg-muted">
                <img
                  :src="dish.image_url"
                  :alt="dish.dish_name"
                  class="w-full h-full object-cover"
                  @error="handleImageError"
                />
              </div>
            </CardContent>
          </Card>

          <!-- Recipe -->
          <Card v-if="dish.ingredients?.length">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Calculator class="w-5 h-5" />
                Recipe
                <Badge variant="outline">
                  Total Cost: ₱{{ getTotalIngredientCost().toFixed(2) }}
                </Badge>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Ingredient</TableHead>
                    <TableHead>Quantity</TableHead>
                    <TableHead>Unit Cost</TableHead>
                    <TableHead>Total Cost</TableHead>
                    <TableHead>Notes</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="ingredient in (dish.ingredients || [])" :key="ingredient.ingredient_id">
                    <TableCell class="font-medium">
                      <div class="flex items-center gap-2">
                        {{ ingredient.ingredient_name }}
                        <Badge v-if="ingredient.is_optional" variant="secondary" class="text-xs">
                          Optional
                        </Badge>
                      </div>
                    </TableCell>
                    <TableCell>
                      {{ ingredient.quantity }} {{ ingredient.unit }}
                    </TableCell>
                    <TableCell>
                      ₱{{ (ingredient.cost_per_unit || 0).toFixed(2) }}
                    </TableCell>
                    <TableCell>
                      ₱{{ ((ingredient.cost_per_unit || 0) * (ingredient.quantity || 0)).toFixed(2) }}
                    </TableCell>
                    <TableCell class="text-sm text-muted-foreground">
                      {{ ingredient.preparation_note || '-' }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div>
          <!-- Quick Actions -->
          <Card>
            <CardHeader>
              <CardTitle>Quick Actions</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
              <Button
                v-if="dish.status === 'draft'"
                @click="updateStatus('active')"
                class="w-full"
              >
                <Eye class="w-4 h-4 mr-2" />
                Activate Dish
              </Button>

              <Button
                v-else-if="dish.status === 'active'"
                variant="secondary"
                @click="updateStatus('inactive')"
                class="w-full"
              >
                <EyeOff class="w-4 h-4 mr-2" />
                Deactivate Dish
              </Button>

              <Button
                v-if="dish.status !== 'archived'"
                variant="outline"
                @click="updateStatus('archived')"
                class="w-full"
              >
                <Archive class="w-4 h-4 mr-2" />
                Archive Dish
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Delete Confirmation Dialog -->
      <div v-if="showDeleteConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <Card class="w-full max-w-md">
          <CardHeader>
            <CardTitle class="text-red-600">Delete Dish</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="mb-4">Are you sure you want to delete "{{ dish.dish_name }}"? This action cannot be undone.</p>
            <div class="flex justify-end space-x-2">
              <Button variant="outline" @click="showDeleteConfirmation = false">
                Cancel
              </Button>
              <Button variant="destructive" @click="deleteDish">
                Delete
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>