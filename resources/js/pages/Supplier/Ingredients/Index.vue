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
    lead_time_days: number;
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
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">My Ingredients</h1>
          <p class="text-muted-foreground">Manage your ingredient offers to restaurants</p>
        </div>
        <Link href="/supplier/ingredients/create">
          <Button>
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
            <p class="text-muted-foreground mb-4">You haven't added any ingredient offers yet.</p>
            <Link href="/supplier/ingredients/create">
              <Button>
                <Plus class="w-4 h-4 mr-2" />
                Add Your First Ingredient
              </Button>
            </Link>
          </div>
          
          <div v-else>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Ingredient</TableHead>
                  <TableHead>Restaurant</TableHead>
                  <TableHead>Package</TableHead>
                  <TableHead>Price</TableHead>
                  <TableHead>Lead Time</TableHead>
                  <TableHead>Min Order</TableHead>
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
                    {{ ingredient.pivot.lead_time_days }} {{ ingredient.pivot.lead_time_days === 1 ? 'day' : 'days' }}
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
        </CardContent>
      </Card>
    </div>
  </SupplierLayout>
</template>