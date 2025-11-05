<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import WaiterLayout from '@/layouts/WaiterLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import { Plus, Minus, Users, Clock, AlertTriangle, ChefHat, ShoppingCart, ChevronDown, ChevronUp } from 'lucide-vue-next';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';

interface Table {
  id: number;
  table_number: string;
  table_name: string;
  seats: number;
  status: string;
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  unit: string;
}

interface DishIngredient {
  dish_ingredient_id: number;
  quantity_required: number;
  ingredient: Ingredient;
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
  description: string;
  price: number;
  allergens?: string[];
  dietary_tags?: string[];
  preparation_time: number;
  is_available: boolean;
  category: {
    category_id: number;
    category_name: string;
  };
  dish_ingredients?: DishIngredient[];
  variants?: DishVariant[];
}

interface Employee {
  employee_id: number;
  firstname: string;
  lastname: string;
  email: string;
  role: {
    role_name: string;
  };
}

interface OrderItem {
  dish_id: number;
  dish: Dish;
  variant_id?: number;
  quantity: number;
  special_instructions: string;
  excluded_ingredients?: number[];
}

interface OrderFormData {
  dish_id: number;
  variant_id?: number;
  quantity: number;
  special_instructions: string;
}

interface ExistingOrderItem {
  dish_id: number;
  quantity: number;
  unit_price: number;
  special_instructions?: string;
  dish: Dish;
}

interface ExistingOrder {
  order_id: number;
  customer_name?: string;
  notes?: string;
  status: string;
  order_items: ExistingOrderItem[];
}

interface Props {
  table: Table;
  dishes: Dish[];
  employee: Employee;
  existingOrder?: ExistingOrder | null;
}

const props = defineProps<Props>();

// Debug: Log the dishes prop to console
console.log('Dishes received:', props.dishes);
console.log('Number of dishes:', props.dishes?.length || 0);

const orderForm = useForm({
  table_id: props.table.id,
  customer_name: props.existingOrder?.customer_name || '',
  notes: '',
  order_items: [] as any[],
});

const orderItems = ref<OrderItem[]>([]);
const existingOrderItems = ref<ExistingOrderItem[]>(props.existingOrder?.order_items || []);
const searchQuery = ref('');
const selectedCategory = ref<number | null>(null);
const selectedDish = ref<Dish | null>(null);
const selectedVariant = ref<DishVariant | null>(null);
const showQuantityModal = ref(false);
const showCartModal = ref(false);
const modalQuantity = ref(1);
const modalSpecialInstructions = ref('');
const showIngredients = ref(false);
const excludedIngredients = ref<number[]>([]);

const categories = computed(() => {
  if (!props.dishes || !Array.isArray(props.dishes)) {
    return [];
  }

  const categoryMap = new Map();
  props.dishes.forEach(dish => {
    if (dish.category && !categoryMap.has(dish.category.category_id)) {
      categoryMap.set(dish.category.category_id, dish.category);
    }
  });
  return Array.from(categoryMap.values());
});

const filteredDishes = computed(() => {
  if (!props.dishes || !Array.isArray(props.dishes)) {
    console.warn('No dishes available or dishes is not an array:', props.dishes);
    return [];
  }

  return props.dishes.filter(dish => {
    if (!dish.is_available) return false;

    const matchesSearch = dish.dish_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         dish.description.toLowerCase().includes(searchQuery.value.toLowerCase());

    const matchesCategory = selectedCategory.value === null ||
                          dish.category?.category_id === selectedCategory.value;

    return matchesSearch && matchesCategory;
  });
});

const existingOrderTotal = computed(() => {
  return existingOrderItems.value.reduce((total, item) => {
    return total + (item.unit_price * item.quantity);
  }, 0);
});

// Get the price for an order item (considering variants)
const getItemPrice = (item: OrderItem): number => {
  if (item.variant_id && item.dish.variants) {
    const variant = item.dish.variants.find(v => v.variant_id === item.variant_id);
    if (variant) {
      return Number(variant.price_modifier) || 0;
    }
  }
  return Number(item.dish.price) || 0;
};

const newOrderTotal = computed(() => {
  return orderItems.value.reduce((total, item) => {
    return total + (getItemPrice(item) * item.quantity);
  }, 0);
});

const totalAmount = computed(() => {
  return existingOrderTotal.value + newOrderTotal.value;
});

const totalItems = computed(() => {
  const existingItems = existingOrderItems.value.reduce((total, item) => total + item.quantity, 0);
  const newItems = orderItems.value.reduce((total, item) => total + item.quantity, 0);
  return existingItems + newItems;
});

// Get current price based on selected variant or base price
const currentPrice = computed(() => {
  if (!selectedDish.value) return 0;
  if (selectedVariant.value) {
    return Number(selectedVariant.value.price_modifier) || 0;
  }
  return Number(selectedDish.value.price) || 0;
});

const openQuantityModal = (dish: Dish) => {
  selectedDish.value = dish;
  modalQuantity.value = 1;
  modalSpecialInstructions.value = '';
  showIngredients.value = false;
  excludedIngredients.value = [];

  // Set default variant if dish has variants
  if (dish.variants && dish.variants.length > 0) {
    const defaultVariant = dish.variants.find(v => v.is_default) || dish.variants[0];
    selectedVariant.value = defaultVariant;
  } else {
    selectedVariant.value = null;
  }

  // Check if this exact dish + variant combo already exists in order to pre-fill quantity and instructions
  const existingItem = orderItems.value.find(item =>
    item.dish_id === dish.dish_id &&
    item.variant_id === selectedVariant.value?.variant_id
  );
  if (existingItem) {
    modalQuantity.value = existingItem.quantity;
    modalSpecialInstructions.value = existingItem.special_instructions;
    excludedIngredients.value = existingItem.excluded_ingredients || [];
    console.log('Opening modal for existing item:', {
      dish_id: dish.dish_id,
      variant_id: selectedVariant.value?.variant_id,
      excluded_ingredients: excludedIngredients.value,
      existing_excluded: existingItem.excluded_ingredients
    });
  } else {
    console.log('Opening modal for new dish:', dish.dish_id, 'variant:', selectedVariant.value?.variant_id);
  }

  showQuantityModal.value = true;
};

const addDishToOrder = () => {
  if (!selectedDish.value || modalQuantity.value < 1) return;

  // Find existing item with same dish_id AND variant_id (size)
  // This allows ordering the same dish in different sizes
  const existingItemIndex = orderItems.value.findIndex(item =>
    item.dish_id === selectedDish.value!.dish_id &&
    item.variant_id === selectedVariant.value?.variant_id
  );
  const isNewItem = existingItemIndex === -1;

  console.log('Adding dish to order:', {
    dish_id: selectedDish.value.dish_id,
    variant_id: selectedVariant.value?.variant_id,
    excluded_ingredients: excludedIngredients.value,
    is_new: isNewItem
  });

  if (existingItemIndex > -1) {
    // Update existing item
    orderItems.value[existingItemIndex].variant_id = selectedVariant.value?.variant_id;
    orderItems.value[existingItemIndex].quantity = modalQuantity.value;
    orderItems.value[existingItemIndex].special_instructions = modalSpecialInstructions.value;
    orderItems.value[existingItemIndex].excluded_ingredients = excludedIngredients.value.length > 0 ? [...excludedIngredients.value] : [];

    console.log('Updated item:', orderItems.value[existingItemIndex]);
  } else {
    // Add new item
    orderItems.value.push({
      dish_id: selectedDish.value.dish_id,
      dish: selectedDish.value,
      variant_id: selectedVariant.value?.variant_id,
      quantity: modalQuantity.value,
      special_instructions: modalSpecialInstructions.value,
      excluded_ingredients: excludedIngredients.value.length > 0 ? [...excludedIngredients.value] : [],
    });

    console.log('Added new item:', orderItems.value[orderItems.value.length - 1]);
  }

  showQuantityModal.value = false;

  // Show a brief cart animation for new items (on mobile)
  if (isNewItem && typeof window !== 'undefined' && window.innerWidth < 1024) {
    // Optional: Could add a subtle animation or toast notification here
  }
};

const closeQuantityModal = () => {
  showQuantityModal.value = false;
  selectedDish.value = null;
  modalQuantity.value = 1;
  modalSpecialInstructions.value = '';
  showIngredients.value = false;
  excludedIngredients.value = [];
};

const toggleIngredientExclusion = (ingredientId: number) => {
  const index = excludedIngredients.value.indexOf(ingredientId);
  if (index > -1) {
    excludedIngredients.value.splice(index, 1);
    console.log('Removed ingredient:', ingredientId, 'Current excluded:', excludedIngredients.value);
  } else {
    excludedIngredients.value.push(ingredientId);
    console.log('Added ingredient:', ingredientId, 'Current excluded:', excludedIngredients.value);
  }
};

const removeDishFromOrder = (dishId: number, variantId?: number) => {
  const itemIndex = orderItems.value.findIndex(item =>
    item.dish_id === dishId && item.variant_id === variantId
  );
  if (itemIndex > -1) {
    if (orderItems.value[itemIndex].quantity > 1) {
      orderItems.value[itemIndex].quantity--;
    } else {
      orderItems.value.splice(itemIndex, 1);
    }
  }
};

const updateQuantity = (dishId: number, quantity: number, variantId?: number) => {
  const item = orderItems.value.find(item =>
    item.dish_id === dishId && item.variant_id === variantId
  );
  if (item) {
    if (quantity > 0) {
      item.quantity = quantity;
    } else {
      removeDishFromOrder(dishId, variantId);
    }
  }
};

const submitOrder = () => {
  // Transform order items to form-compatible format
  orderForm.order_items = orderItems.value.map(item => ({
    dish_id: item.dish_id,
    variant_id: item.variant_id,
    quantity: item.quantity,
    special_instructions: item.special_instructions,
    excluded_ingredients: item.excluded_ingredients || [],
  }));

  console.log('Submitting order with items:', orderForm.order_items);

  orderForm.post(route('waiter.orders.store'), {
    onSuccess: () => {
      console.log('Order submitted successfully!');
      showCartModal.value = false;
      // Clear the cart after successful submission
      orderItems.value = [];
      // Redirect back to dashboard or show success message
    },
    onError: (errors) => {
      console.error('Order submission failed:', errors);
      // Keep cart modal open to show any validation errors
    },
  });
};

const getAllergenBadgeColor = (allergen: string) => {
  const colors: { [key: string]: string } = {
    'dairy': 'bg-blue-100 text-blue-800',
    'gluten': 'bg-yellow-100 text-yellow-800',
    'nuts': 'bg-red-100 text-red-800',
    'shellfish': 'bg-orange-100 text-orange-800',
    'soy': 'bg-green-100 text-green-800',
    'eggs': 'bg-purple-100 text-purple-800',
  };
  return colors[allergen.toLowerCase()] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
  <Head title="Create Order" />

  <WaiterLayout :employee="employee">
    <template #title>{{ existingOrderItems.length > 0 ? 'Add to Order' : 'Create Order' }} - {{ table.table_name }}</template>

    <div class="flex flex-col lg:flex-row gap-2 sm:gap-4 p-2 sm:p-4 min-h-[calc(100vh-3rem)] lg:overflow-hidden">
      <!-- Menu Section -->
      <div class="flex-1 space-y-3 sm:space-y-4 min-w-0 lg:order-1 lg:overflow-hidden">
        <!-- Search and Filter -->
        <Card>
          <CardHeader class="pb-3">
            <div class="flex flex-col space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <CardTitle class="text-lg">Menu Items</CardTitle>
                  <Badge v-if="existingOrderItems.length > 0" variant="default" class="text-xs bg-blue-600">
                    Extending Order
                  </Badge>
                </div>
                <Badge variant="outline" class="text-xs">
                  {{ filteredDishes.length }} items
                </Badge>
              </div>

              <!-- Search -->
              <Input
                v-model="searchQuery"
                placeholder="Search dishes..."
                class="w-full"
              />

              <!-- Category Filter -->
              <div class="flex flex-wrap gap-2">
                <Button
                  @click="selectedCategory = null"
                  :variant="selectedCategory === null ? 'default' : 'outline'"
                  size="sm"
                >
                  All
                </Button>
                <Button
                  v-for="category in categories"
                  :key="category.category_id"
                  @click="selectedCategory = category.category_id"
                  :variant="selectedCategory === category.category_id ? 'default' : 'outline'"
                  size="sm"
                >
                  {{ category.category_name }}
                </Button>
              </div>
            </div>
          </CardHeader>
        </Card>

        <!-- No Dishes Available Message -->
        <div v-if="!props.dishes || props.dishes.length === 0" class="text-center py-12">
          <AlertTriangle class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-muted-foreground mb-2">No Menu Items Available</h3>
          <p class="text-muted-foreground mb-4">
            There are no dishes available for this restaurant. Please contact the manager to add menu items.
          </p>
          <Button @click="() => router.visit('/waiter/dashboard')" variant="outline">
            Back to Tables
          </Button>
        </div>

        <!-- Empty Search Results -->
        <div v-else-if="filteredDishes.length === 0" class="text-center py-12">
          <AlertTriangle class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-muted-foreground mb-2">No Dishes Found</h3>
          <p class="text-muted-foreground mb-4">
            No dishes match your search criteria. Try adjusting your search or filter settings.
          </p>
          <Button @click="() => { searchQuery = ''; selectedCategory = null; }" variant="outline">
            Clear Filters
          </Button>
        </div>

        <!-- Dishes Grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4 lg:overflow-y-auto lg:max-h-[calc(100vh-300px)]">
          <Card
            v-for="dish in filteredDishes"
            :key="dish.dish_id"
            class="cursor-pointer transition-all hover:shadow-md"
            @click="openQuantityModal(dish)"
          >
            <CardContent class="p-3 sm:p-4">
              <div class="space-y-3">
                <!-- Dish Header -->
                <div class="space-y-2">
                  <div class="flex justify-between items-start">
                    <h3 class="font-semibold text-base sm:text-lg flex-1 min-w-0 pr-2">{{ dish.dish_name }}</h3>
                    <p class="font-bold text-lg sm:text-xl text-green-600 flex-shrink-0">
                      ₱{{ dish.variants && dish.variants.length > 0
                        ? Number((dish.variants.find(v => v.is_default) || dish.variants[0]).price_modifier).toFixed(2)
                        : Number(dish.price || 0).toFixed(2) }}
                    </p>
                  </div>
                  <p class="text-sm sm:text-base text-muted-foreground">{{ dish.description || 'No description available' }}</p>
                </div>

                <!-- Allergens -->
                <div v-if="dish.allergens && Array.isArray(dish.allergens) && dish.allergens.length > 0" class="flex flex-wrap gap-1">
                  <Badge
                    v-for="allergen in dish.allergens"
                    :key="allergen"
                    :class="getAllergenBadgeColor(allergen)"
                    class="text-xs"
                  >
                    ⚠️ {{ allergen }}
                  </Badge>
                </div>

                <!-- Preparation Time -->
                <div class="flex items-center justify-between text-sm text-muted-foreground">
                  <div class="flex items-center gap-2">
                    <Clock class="h-4 w-4" />
                    <span>{{ dish.preparation_time }} min</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <ChefHat class="h-4 w-4" />
                    <span class="hidden sm:inline">{{ dish.category?.category_name }}</span>
                  </div>
                </div>

                <!-- Add Button -->
                <Button class="w-full" size="sm">
                  <Plus class="h-4 w-4 mr-2" />
                  <span class="hidden sm:inline">Select Quantity</span>
                  <span class="sm:hidden">Add</span>
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Desktop Cart Button -->
      <div class="hidden lg:block lg:w-72 lg:min-w-72 flex-shrink-0 lg:order-2">
        <Card>
          <CardHeader class="pb-2 sm:pb-3">
            <CardTitle class="flex items-center gap-2 text-base sm:text-lg">
              <Users class="h-5 w-5" />
              {{ table.table_name }}
            </CardTitle>
            <CardDescription>
              Table #{{ table.table_number }} • {{ table.seats }} seats
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Cart Button -->
            <Button
              @click="showCartModal = true"
              class="w-full bg-green-600 hover:bg-green-700"
              size="lg"
            >
              <ShoppingCart class="h-5 w-5 mr-2" />
              View Cart
              <span v-if="totalItems > 0" class="ml-2 bg-green-800 text-white px-2 py-1 rounded-full text-xs">
                {{ totalItems }}
              </span>
            </Button>

            <!-- Quick Summary -->
            <div v-if="existingOrderItems.length > 0 || orderItems.length > 0" class="space-y-2">
              <!-- Existing Order Info -->
              <div v-if="existingOrderItems.length > 0" class="text-center p-3 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-xs text-blue-600 font-medium">Existing Order</p>
                <p class="text-sm text-blue-800">{{ existingOrderItems.reduce((total, item) => total + item.quantity, 0) }} item{{ existingOrderItems.reduce((total, item) => total + item.quantity, 0) !== 1 ? 's' : '' }}</p>
                <p class="font-bold text-blue-600">₱{{ existingOrderTotal.toFixed(2) }}</p>
              </div>

              <!-- New Items Summary -->
              <div v-if="orderItems.length > 0" class="text-center p-3 bg-green-50 rounded-lg border border-green-200">
                <p class="text-xs text-green-600 font-medium">{{ existingOrderItems.length > 0 ? 'Additional Items' : 'New Order' }}</p>
                <p class="text-sm text-green-800">{{ orderItems.reduce((total, item) => total + item.quantity, 0) }} item{{ orderItems.reduce((total, item) => total + item.quantity, 0) !== 1 ? 's' : '' }}</p>
                <p class="font-bold text-green-600">₱{{ newOrderTotal.toFixed(2) }}</p>
              </div>

              <!-- Combined Total -->
              <div v-if="existingOrderItems.length > 0 && orderItems.length > 0" class="text-center p-2 bg-gray-100 rounded-lg border">
                <p class="text-xs text-gray-600 font-medium">Total</p>
                <p class="font-bold text-gray-800">₱{{ totalAmount.toFixed(2) }}</p>
              </div>
            </div>

            <!-- Cancel Button -->
            <Button
              @click="() => router.visit('/waiter/dashboard')"
              variant="outline"
              class="w-full"
            >
              Back to Tables
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Floating Cart Icon (Mobile) -->
    <div class="lg:hidden fixed bottom-6 right-6 z-50">
      <Button
        @click="showCartModal = true"
        class="relative h-14 w-14 rounded-full bg-green-600 hover:bg-green-700 shadow-lg border-2 border-white"
        size="icon"
      >
        <ShoppingCart class="h-6 w-6 text-white" />
        <!-- Cart Badge -->
        <div
          v-if="totalItems > 0"
          class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center min-w-6"
        >
          {{ totalItems > 99 ? '99+' : totalItems }}
        </div>
      </Button>
    </div>

    <!-- Quantity Selection Modal -->
    <Dialog :open="showQuantityModal" @update:open="closeQuantityModal">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Add to Order</DialogTitle>
          <DialogDescription v-if="selectedDish">
            {{ selectedDish.dish_name }}
          </DialogDescription>
        </DialogHeader>

        <div v-if="selectedDish" class="space-y-4">
          <!-- Dish Details -->
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <h4 class="font-semibold">{{ selectedDish.dish_name }}</h4>
              <span class="font-bold text-lg">₱{{ currentPrice.toFixed(2) }}</span>
            </div>
            <p class="text-sm text-muted-foreground">{{ selectedDish.description || 'No description available' }}</p>

            <!-- Allergens -->
            <div v-if="selectedDish.allergens && Array.isArray(selectedDish.allergens) && selectedDish.allergens.length > 0" class="flex flex-wrap gap-1">
              <Badge
                v-for="allergen in selectedDish.allergens"
                :key="allergen"
                :class="getAllergenBadgeColor(allergen)"
                class="text-xs"
              >
                ⚠️ {{ allergen }}
              </Badge>
            </div>

            <!-- Preparation Time -->
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <Clock class="h-4 w-4" />
              <span>{{ selectedDish.preparation_time }} min</span>
              <ChefHat class="h-4 w-4 ml-2" />
              <span>{{ selectedDish.category?.category_name }}</span>
            </div>
          </div>

          <!-- Variant/Size Selection -->
          <div v-if="selectedDish.variants && selectedDish.variants.length > 0" class="space-y-2">
            <Label>Select Size</Label>
            <div class="grid grid-cols-1 gap-2">
              <button
                v-for="variant in selectedDish.variants"
                :key="variant.variant_id"
                @click="selectedVariant = variant"
                :class="[
                  'p-3 border rounded-lg text-left transition-all',
                  selectedVariant?.variant_id === variant.variant_id
                    ? 'border-primary bg-primary/10 ring-2 ring-primary'
                    : 'border-gray-300 hover:border-primary hover:bg-gray-50'
                ]"
              >
                <div class="flex justify-between items-center">
                  <span class="font-semibold">{{ variant.size_name }}</span>
                  <span class="font-bold text-primary">₱{{ Number(variant.price_modifier).toFixed(2) }}</span>
                </div>
                <div class="text-xs text-muted-foreground mt-1">
                  {{ Number(variant.quantity_multiplier).toFixed(1) }}x ingredients
                </div>
              </button>
            </div>
          </div>

          <!-- Quantity Selection -->
          <div class="space-y-2">
            <Label for="quantity">Quantity</Label>
            <div class="flex items-center gap-2">
              <Button
                @click="modalQuantity = Math.max(1, modalQuantity - 1)"
                variant="outline"
                size="icon"
                class="h-10 w-10"
              >
                <Minus class="h-4 w-4" />
              </Button>
              <Input
                v-model.number="modalQuantity"
                type="number"
                min="1"
                class="w-20 text-center"
                id="quantity"
              />
              <Button
                @click="modalQuantity++"
                variant="outline"
                size="icon"
                class="h-10 w-10"
              >
                <Plus class="h-4 w-4" />
              </Button>
            </div>
          </div>

          <!-- Ingredients Section -->
          <div v-if="selectedDish.dish_ingredients && selectedDish.dish_ingredients.length > 0" class="space-y-2">
            <div class="flex items-center justify-between">
              <Label class="text-sm font-medium">Ingredients</Label>
              <Button
                @click="showIngredients = !showIngredients"
                variant="ghost"
                size="sm"
                type="button"
                class="h-8"
              >
                <component :is="showIngredients ? ChevronUp : ChevronDown" class="h-4 w-4 mr-1" />
                {{ showIngredients ? 'Hide' : 'Show' }}
              </Button>
            </div>

            <div v-if="showIngredients" class="space-y-2 p-3 bg-muted/50 rounded-lg border max-h-48 overflow-y-auto">
              <p class="text-xs text-muted-foreground mb-2">
                Select ingredients to exclude (for allergies or preferences):
              </p>
              <div
                v-for="dishIngredient in selectedDish.dish_ingredients"
                :key="dishIngredient.dish_ingredient_id"
                class="flex items-center space-x-2 p-2 hover:bg-muted rounded cursor-pointer"
                @click="toggleIngredientExclusion(dishIngredient.ingredient.ingredient_id)"
              >
                <input
                  type="checkbox"
                  :id="`ingredient-${dishIngredient.ingredient.ingredient_id}`"
                  :checked="excludedIngredients.includes(dishIngredient.ingredient.ingredient_id)"
                  class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                  @click.stop
                  @change="toggleIngredientExclusion(dishIngredient.ingredient.ingredient_id)"
                />
                <label
                  :for="`ingredient-${dishIngredient.ingredient.ingredient_id}`"
                  class="flex-1 text-sm cursor-pointer"
                >
                  {{ dishIngredient.ingredient.ingredient_name }}
                  <span class="text-xs text-muted-foreground ml-1">
                    ({{ dishIngredient.quantity_required }} {{ dishIngredient.ingredient.unit }})
                  </span>
                </label>
              </div>

              <div v-if="excludedIngredients.length > 0" class="mt-2 pt-2 border-t">
                <p class="text-xs font-medium text-amber-600">
                  ⚠️ {{ excludedIngredients.length }} ingredient(s) will be excluded
                </p>
              </div>
            </div>
          </div>

          <!-- Special Instructions -->
          <div class="space-y-2">
            <Label for="special-instructions">Special Instructions (Optional)</Label>
            <Textarea
              v-model="modalSpecialInstructions"
              id="special-instructions"
              placeholder="Any special requests or modifications..."
              rows="3"
            />
          </div>

          <!-- Total Price -->
          <div class="flex justify-between items-center p-3 bg-muted rounded-lg">
            <span class="font-medium">Total:</span>
            <span class="font-bold text-lg">₱{{ (currentPrice * modalQuantity).toFixed(2) }}</span>
          </div>
        </div>

        <DialogFooter class="gap-2">
          <Button variant="outline" @click="closeQuantityModal">
            Cancel
          </Button>
          <Button @click="addDishToOrder">
            Add to Order
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Cart Modal -->
    <Dialog :open="showCartModal" @update:open="(open) => showCartModal = open">
      <DialogContent class="sm:max-w-lg max-h-[80vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <ShoppingCart class="h-5 w-5" />
            Order Cart
          </DialogTitle>
          <DialogDescription class="flex items-center gap-2">
            <Users class="h-4 w-4" />
            {{ table.table_name }} • Table #{{ table.table_number }} • {{ table.seats }} seats
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <!-- Customer Info -->
          <div class="space-y-3 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-semibold text-sm">Customer Information</h3>
            <div class="space-y-2">
              <div>
                <Label for="cart-customer-name" class="text-xs">Customer Name</Label>
                <Input
                  id="cart-customer-name"
                  v-model="orderForm.customer_name"
                  placeholder="Enter customer name"
                  class="mt-1"
                />
              </div>
              <div>
                <Label for="cart-notes" class="text-xs">Order Notes</Label>
                <Textarea
                  id="cart-notes"
                  v-model="orderForm.notes"
                  placeholder="Special requests..."
                  rows="2"
                  class="mt-1"
                />
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div class="space-y-3">
            <div v-if="existingOrderItems.length === 0 && orderItems.length === 0" class="text-center py-8 text-muted-foreground">
              <ShoppingCart class="h-12 w-12 mx-auto mb-3 opacity-50" />
              <p class="text-sm">Your cart is empty</p>
              <p class="text-xs">Add some dishes to get started</p>
            </div>

            <div v-else>
              <h3 class="font-semibold text-sm mb-3">Order Items ({{ totalItems }})</h3>

              <div class="space-y-2 max-h-64 overflow-y-auto">
                <!-- Existing Order Items -->
                <div v-if="existingOrderItems.length > 0">
                  <div class="text-xs font-medium text-blue-600 mb-2 px-2">Previous Order:</div>
                  <div
                    v-for="item in existingOrderItems"
                    :key="`existing-${item.dish_id}`"
                    class="flex items-start gap-3 p-3 border rounded-lg bg-blue-50"
                  >
                    <div class="flex-1 min-w-0">
                      <p class="font-medium text-sm">{{ item.dish.dish_name }}</p>
                      <p class="text-xs text-muted-foreground">₱{{ item.unit_price }} each</p>
                      <p v-if="item.special_instructions" class="text-xs text-muted-foreground mt-1 italic">
                        Special: {{ item.special_instructions }}
                      </p>
                    </div>
                    <div class="flex items-center">
                      <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">
                        {{ item.quantity }}
                      </span>
                    </div>
                    <div class="text-right">
                      <p class="font-semibold text-sm">₱{{ (item.unit_price * item.quantity).toFixed(2) }}</p>
                    </div>
                  </div>
                </div>

                <!-- New Order Items -->
                <div v-if="orderItems.length > 0">
                  <div v-if="existingOrderItems.length > 0" class="text-xs font-medium text-green-600 mb-2 px-2 mt-4">Additional Items:</div>
                  <div
                    v-for="item in orderItems"
                    :key="`${item.dish_id}-${item.variant_id || 'no-variant'}`"
                    class="flex items-start gap-3 p-3 border rounded-lg bg-white"
                  >
                    <div class="flex-1 min-w-0">
                      <p class="font-medium text-sm">{{ item.dish.dish_name }}</p>
                      <p class="text-xs text-muted-foreground">
                        ₱{{ getItemPrice(item).toFixed(2) }} each
                        <span v-if="item.variant_id && item.dish.variants" class="text-blue-600">
                          ({{ item.dish.variants.find(v => v.variant_id === item.variant_id)?.size_name }})
                        </span>
                      </p>
                      <p v-if="item.special_instructions" class="text-xs text-muted-foreground mt-1 italic">
                        Special: {{ item.special_instructions }}
                      </p>
                      <p v-if="item.excluded_ingredients && item.excluded_ingredients.length > 0" class="text-xs text-amber-600 mt-1 font-medium">
                        ⚠️ {{ item.excluded_ingredients.length }} ingredient(s) excluded
                      </p>
                    </div>

                    <div class="flex items-center gap-2">
                      <Button
                        @click="removeDishFromOrder(item.dish_id, item.variant_id)"
                        variant="outline"
                        size="icon"
                        class="h-8 w-8"
                      >
                        <Minus class="h-3 w-3" />
                      </Button>

                      <Input
                        :model-value="item.quantity"
                        @update:model-value="(value) => updateQuantity(item.dish_id, parseInt(String(value)) || 0, item.variant_id)"
                        type="number"
                        min="0"
                        class="w-14 h-8 text-center text-xs"
                      />

                      <Button
                        @click="openQuantityModal(item.dish); showCartModal = false"
                        variant="outline"
                        size="icon"
                        class="h-8 w-8"
                      >
                        <Plus class="h-3 w-3" />
                      </Button>
                    </div>

                    <div class="text-right">
                      <p class="font-semibold text-sm">₱{{ (getItemPrice(item) * item.quantity).toFixed(2) }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Order Total -->
              <div class="border-t pt-3 mt-3 bg-green-50 p-3 rounded-lg">
                <div v-if="existingOrderItems.length > 0" class="space-y-2 mb-2">
                  <div class="flex justify-between items-center text-sm">
                    <span>Previous Order:</span>
                    <span>₱{{ existingOrderTotal.toFixed(2) }}</span>
                  </div>
                  <div v-if="newOrderTotal > 0" class="flex justify-between items-center text-sm">
                    <span>Additional Items:</span>
                    <span>₱{{ newOrderTotal.toFixed(2) }}</span>
                  </div>
                  <hr class="border-gray-300">
                </div>
                <div class="flex justify-between items-center">
                  <span class="font-semibold">Total Amount:</span>
                  <span class="font-bold text-xl text-green-600">₱{{ totalAmount.toFixed(2) }}</span>
                </div>
                <div class="text-xs text-muted-foreground mt-1">
                  {{ totalItems }} item{{ totalItems !== 1 ? 's' : '' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <DialogFooter class="gap-2 mt-4">
          <Button
            variant="outline"
            @click="showCartModal = false"
            class="flex-1"
          >
            Continue Shopping
          </Button>
          <Button
            @click="submitOrder"
            :disabled="orderItems.length === 0 || orderForm.processing"
            class="flex-1 bg-green-600 hover:bg-green-700"
          >
            {{ orderForm.processing ? 'Submitting...' : (existingOrderItems.length > 0 ? 'Add to Order' : 'Submit Order') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </WaiterLayout>
</template>