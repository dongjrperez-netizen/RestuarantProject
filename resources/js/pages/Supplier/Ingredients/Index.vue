<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SupplierLayout from '@/layouts/SupplierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import  Badge  from '@/components/ui/badge/Badge.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Edit, Trash2 } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

interface Restaurant {
  id: number;
  restaurant_name: string;
  address: string;
}

interface IngredientOffer {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
  restaurant: Restaurant;
  pivot: {
    package_unit: string;
    package_quantity: number;
    package_contents_quantity: number;
    package_contents_unit: string;
    package_price: number;
    minimum_order_quantity: number;
    is_active: boolean;
  };
}

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  email: string;
}

interface Props {
  offeredIngredients: IngredientOffer[];
  supplier: Supplier;
}

defineProps<Props>();

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

const deleteOffer = (ingredientId: number) => {
  if (confirm('Are you sure you want to remove this ingredient offer?')) {
    router.delete(`/supplier/ingredients/${ingredientId}`);
  }
};
</script>

<template>
  <Head title="My Ingredients" />

  <SupplierLayout :supplier="supplier">
    <div class="space-y-4 md:space-y-6 p-4 md:p-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold tracking-tight">My Ingredients</h1>
          <p class="text-sm md:text-base text-muted-foreground">Manage your ingredient offers to restaurants</p>
        </div>
        <Link href="/supplier/ingredients/create">
          <Button size="sm" class="md:size-default">
            <Plus class="w-4 h-4 mr-2" />
            Add Ingredient
          </Button>
        </Link>
      </div>

      <!-- Ingredients Table -->
      <Card>
        <CardHeader>
          <CardTitle>Your Ingredient Offers</CardTitle>
          <CardDescription>
            All ingredients you're currently offering to restaurants
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="offeredIngredients.length === 0" class="text-center py-8">
            <p class="text-sm md:text-base text-muted-foreground mb-4">You haven't added any ingredient offers yet.</p>
            <Link href="/supplier/ingredients/create">
              <Button size="sm" class="md:size-default">
                <Plus class="w-4 h-4 mr-2" />
                Add Your First Ingredient
              </Button>
            </Link>
          </div>

          <!-- Desktop Table View (hidden on mobile) -->
          <div v-else class="hidden md:block overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Ingredient</TableHead>
                  <TableHead>Restaurant</TableHead>
                  <TableHead>Package</TableHead>
                  <TableHead>Price</TableHead>
                  <TableHead>Max Order</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="ingredient in offeredIngredients" :key="`${ingredient.ingredient_id}`">
                  <TableCell class="font-medium">
                    <div>
                      <div>{{ ingredient.ingredient_name }}</div>
                      <div class="text-sm text-muted-foreground">Base: {{ ingredient.base_unit }}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ ingredient.restaurant.restaurant_name }}</div>
                      <div class="text-sm text-muted-foreground">{{ ingredient.restaurant.address }}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ ingredient.pivot.package_quantity }} {{ ingredient.pivot.package_unit }}</div>
                      <div class="text-sm text-muted-foreground">
                        Contains: {{ ingredient.pivot.package_contents_quantity }} {{ ingredient.pivot.package_contents_unit }}
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    {{ formatCurrency(ingredient.pivot.package_price) }}
                  </TableCell>
                  <TableCell>
                    {{ ingredient.pivot.minimum_order_quantity }} {{ ingredient.pivot.package_unit }}
                  </TableCell>
                  <TableCell>
                    <Badge :class="ingredient.pivot.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                      {{ ingredient.pivot.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-right">
                    <div class="flex justify-end gap-2">
                      <Link :href="`/supplier/ingredients/${ingredient.ingredient_id}/edit`">
                        <Button variant="outline" size="sm">
                          <Edit class="w-4 h-4" />
                        </Button>
                      </Link>
                      <Button
                        variant="outline"
                        size="sm"
                        @click="deleteOffer(ingredient.ingredient_id)"
                        class="text-red-600 hover:text-red-700"
                      >
                        <Trash2 class="w-4 h-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Mobile Card View (hidden on desktop) -->
          <div v-if="offeredIngredients.length > 0" class="md:hidden space-y-3">
            <Card v-for="ingredient in offeredIngredients" :key="`${ingredient.ingredient_id}`" class="border">
              <CardContent class="p-4">
                <div class="flex items-start justify-between mb-3">
                  <div class="flex-1">
                    <div class="font-semibold text-base">{{ ingredient.ingredient_name }}</div>
                    <div class="text-xs text-muted-foreground mt-1">Base: {{ ingredient.base_unit }}</div>
                    <div class="text-sm text-muted-foreground mt-1">
                      {{ ingredient.restaurant.restaurant_name }}
                    </div>
                  </div>
                  <Badge :class="ingredient.pivot.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="text-xs">
                    {{ ingredient.pivot.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </div>

                <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                  <div>
                    <div class="text-muted-foreground text-xs">Package</div>
                    <div class="font-medium">{{ ingredient.pivot.package_quantity }} {{ ingredient.pivot.package_unit }}</div>
                    <div class="text-xs text-muted-foreground">
                      {{ ingredient.pivot.package_contents_quantity }} {{ ingredient.pivot.package_contents_unit }}
                    </div>
                  </div>
                  <div>
                    <div class="text-muted-foreground text-xs">Price</div>
                    <div class="font-semibold">{{ formatCurrency(ingredient.pivot.package_price) }}</div>
                  </div>
                  <div>
                    <div class="text-muted-foreground text-xs">Max Order</div>
                    <div>{{ ingredient.pivot.minimum_order_quantity }} {{ ingredient.pivot.package_unit }}</div>
                  </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                  <Link :href="`/supplier/ingredients/${ingredient.ingredient_id}/edit`">
                    <Button variant="outline" size="sm">
                      <Edit class="w-4 h-4 mr-1" />
                      Edit
                    </Button>
                  </Link>
                  <Button
                    variant="outline"
                    size="sm"
                    @click="deleteOffer(ingredient.ingredient_id)"
                    class="text-red-600 hover:text-red-700"
                  >
                    <Trash2 class="w-4 h-4 mr-1" />
                    Delete
                  </Button>
                </div>
              </CardContent>
            </Card>
          </div>
        </CardContent>
      </Card>
    </div>
  </SupplierLayout>
</template>