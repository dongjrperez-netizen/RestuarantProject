<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Badge from '@/components/ui/badge/Badge.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { 
  TrendingUp, TrendingDown, DollarSign, Calculator, 
  ChefHat, AlertTriangle, BarChart3, PieChart,
  Calendar, Download, Filter
} from 'lucide-vue-next';

interface MenuCategory {
  category_id: number;
  category_name: string;
  dish_count: number;
  total_cost: number;
  total_revenue: number;
  avg_margin: number;
}

interface DishAnalytics {
  dish_id: number;
  dish_name: string;
  category_name: string;
  total_cost: number;
  avg_price: number;
  profit_margin: number;
  status: string;
  popularity_score: number;
  cost_trend: 'up' | 'down' | 'stable';
  orders_count?: number;
  revenue?: number;
}

interface CostAnalytics {
  ingredient_name: string;
  total_usage: number;
  total_cost: number;
  dishes_count: number;
  avg_cost_per_dish: number;
  cost_trend: 'up' | 'down' | 'stable';
}

interface Props {
  categories: MenuCategory[];
  dishAnalytics: DishAnalytics[];
  costAnalytics: CostAnalytics[];
  totalDishes: number;
  activeDishes: number;
  avgProfitMargin: number;
  totalMenuCost: number;
  filters: {
    category_id?: number;
    period?: string;
    status?: string;
  };
}

const props = defineProps<Props>();

const selectedCategory = ref(props.filters.category_id?.toString() || 'all');
const selectedPeriod = ref(props.filters.period || '30');
const selectedStatus = ref(props.filters.status || '');

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Management', href: '/menu' },
  { title: 'Analytics', href: '/menu/analytics' },
];

const periodOptions = [
  { value: '7', label: 'Last 7 days' },
  { value: '30', label: 'Last 30 days' },
  { value: '90', label: 'Last 90 days' },
  { value: '365', label: 'Last year' },
];

const statusOptions = [
  { value: '', label: 'All Status' },
  { value: 'active', label: 'Active' },
  { value: 'draft', label: 'Draft' },
  { value: 'inactive', label: 'Inactive' },
];

const topPerformingDishes = computed(() => {
  return props.dishAnalytics
    .filter(dish => dish.status === 'active')
    .sort((a, b) => b.profit_margin - a.profit_margin)
    .slice(0, 5);
});

const lowMarginDishes = computed(() => {
  return props.dishAnalytics
    .filter(dish => dish.profit_margin < 20)
    .sort((a, b) => a.profit_margin - b.profit_margin)
    .slice(0, 5);
});

const expensiveIngredients = computed(() => {
  return props.costAnalytics
    .sort((a, b) => b.total_cost - a.total_cost)
    .slice(0, 5);
});

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'active': return 'default';
    case 'draft': return 'secondary';
    case 'inactive': return 'destructive';
    default: return 'secondary';
  }
};

const getTrendIcon = (trend: string) => {
  switch (trend) {
    case 'up': return TrendingUp;
    case 'down': return TrendingDown;
    default: return BarChart3;
  }
};

const getTrendColor = (trend: string) => {
  switch (trend) {
    case 'up': return 'text-red-500';
    case 'down': return 'text-green-500';
    default: return 'text-muted-foreground';
  }
};

const getMarginColor = (margin: number) => {
  if (margin >= 30) return 'text-green-600';
  if (margin >= 20) return 'text-yellow-600';
  return 'text-red-600';
};

const applyFilters = () => {
  // This would trigger a new request with filters
  console.log('Applying filters:', {
    category: selectedCategory.value,
    period: selectedPeriod.value,
    status: selectedStatus.value
  });
};

const exportReport = () => {
  // This would generate and download a report
  console.log('Exporting report...');
};
</script>

<template>
  <Head title="Menu Analytics" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Menu Analytics</h1>
          <p class="text-muted-foreground">Analyze menu performance, costs, and profitability</p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" @click="exportReport">
            <Download class="w-4 h-4 mr-2" />
            Export Report
          </Button>
          <Link href="/menu">
            <Button variant="outline">Back to Menu</Button>
          </Link>
        </div>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Filter class="w-5 h-5" />
            Filters
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-4">
            <div class="min-w-[150px]">
              <Select v-model="selectedPeriod">
                <SelectTrigger>
                  <SelectValue placeholder="Select period" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem 
                    v-for="option in periodOptions" 
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            
            <div class="min-w-[150px]">
              <Select v-model="selectedCategory">
                <SelectTrigger>
                  <SelectValue placeholder="All Categories" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Categories</SelectItem>
                  <SelectItem 
                    v-for="category in categories" 
                    :key="category.category_id"
                    :value="category.category_id.toString()"
                  >
                    {{ category.category_name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="min-w-[150px]">
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

            <Button @click="applyFilters">Apply Filters</Button>
          </div>
        </CardContent>
      </Card>

      <!-- Key Metrics -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Dishes</CardTitle>
            <ChefHat class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ totalDishes }}</div>
            <p class="text-xs text-muted-foreground">
              {{ activeDishes }} active
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Avg Profit Margin</CardTitle>
            <TrendingUp class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold" :class="getMarginColor(avgProfitMargin)">
              {{ avgProfitMargin.toFixed(1) }}%
            </div>
            <p class="text-xs text-muted-foreground">
              Across all dishes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Menu Cost</CardTitle>
            <Calculator class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">₱{{ totalMenuCost.toLocaleString() }}</div>
            <p class="text-xs text-muted-foreground">
              Total ingredient costs
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Categories</CardTitle>
            <PieChart class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ categories.length }}</div>
            <p class="text-xs text-muted-foreground">
              Menu categories
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Performance Analysis -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Top Performing Dishes -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <TrendingUp class="w-5 h-5" />
              Top Performing Dishes
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="dish in topPerformingDishes" :key="dish.dish_id" class="flex items-center justify-between">
                <div class="space-y-1">
                  <div class="font-medium">{{ dish.dish_name }}</div>
                  <div class="text-sm text-muted-foreground">{{ dish.category_name }}</div>
                </div>
                <div class="text-right">
                  <div class="font-medium text-green-600">{{ dish.profit_margin.toFixed(1) }}%</div>
                  <div class="text-sm text-muted-foreground">₱{{ dish.avg_price.toFixed(2) }}</div>
                </div>
              </div>
              <div v-if="topPerformingDishes.length === 0" class="text-center py-4 text-muted-foreground">
                No active dishes found
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Low Margin Dishes -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <AlertTriangle class="w-5 h-5 text-red-500" />
              Low Margin Dishes
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="dish in lowMarginDishes" :key="dish.dish_id" class="flex items-center justify-between">
                <div class="space-y-1">
                  <div class="font-medium">{{ dish.dish_name }}</div>
                  <div class="text-sm text-muted-foreground">{{ dish.category_name }}</div>
                </div>
                <div class="text-right">
                  <div class="font-medium text-red-600">{{ dish.profit_margin.toFixed(1) }}%</div>
                  <div class="text-sm text-muted-foreground">₱{{ dish.avg_price.toFixed(2) }}</div>
                </div>
              </div>
              <div v-if="lowMarginDishes.length === 0" class="text-center py-4 text-muted-foreground">
                All dishes have healthy margins
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Category Performance -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <BarChart3 class="w-5 h-5" />
            Category Performance
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Category</TableHead>
                <TableHead>Dishes</TableHead>
                <TableHead>Total Cost</TableHead>
                <TableHead>Avg Revenue</TableHead>
                <TableHead>Avg Margin</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="category in categories" :key="category.category_id">
                <TableCell class="font-medium">{{ category.category_name }}</TableCell>
                <TableCell>{{ category.dish_count }}</TableCell>
                <TableCell>₱{{ category.total_cost.toLocaleString() }}</TableCell>
                <TableCell>₱{{ category.total_revenue.toLocaleString() }}</TableCell>
                <TableCell :class="getMarginColor(category.avg_margin)">
                  {{ category.avg_margin.toFixed(1) }}%
                </TableCell>
              </TableRow>
              <TableRow v-if="categories.length === 0">
                <TableCell colspan="5" class="text-center py-8 text-muted-foreground">
                  No categories found
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Cost Analysis -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <DollarSign class="w-5 h-5" />
            Top Cost Ingredients
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Ingredient</TableHead>
                <TableHead>Total Usage</TableHead>
                <TableHead>Total Cost</TableHead>
                <TableHead>Used in Dishes</TableHead>
                <TableHead>Avg Cost/Dish</TableHead>
                <TableHead>Trend</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="ingredient in expensiveIngredients" :key="ingredient.ingredient_name">
                <TableCell class="font-medium">{{ ingredient.ingredient_name }}</TableCell>
                <TableCell>{{ ingredient.total_usage }}</TableCell>
                <TableCell>₱{{ ingredient.total_cost.toFixed(2) }}</TableCell>
                <TableCell>{{ ingredient.dishes_count }}</TableCell>
                <TableCell>₱{{ ingredient.avg_cost_per_dish.toFixed(2) }}</TableCell>
                <TableCell>
                  <component 
                    :is="getTrendIcon(ingredient.cost_trend)" 
                    :class="['w-4 h-4', getTrendColor(ingredient.cost_trend)]"
                  />
                </TableCell>
              </TableRow>
              <TableRow v-if="expensiveIngredients.length === 0">
                <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                  No ingredient data available
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Detailed Dish Analytics -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <ChefHat class="w-5 h-5" />
            Detailed Dish Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Dish Name</TableHead>
                <TableHead>Category</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Cost</TableHead>
                <TableHead>Avg Price</TableHead>
                <TableHead>Margin</TableHead>
                <TableHead>Cost Trend</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="dish in dishAnalytics" :key="dish.dish_id">
                <TableCell class="font-medium">{{ dish.dish_name }}</TableCell>
                <TableCell>{{ dish.category_name }}</TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(dish.status)">
                    {{ dish.status }}
                  </Badge>
                </TableCell>
                <TableCell>₱{{ dish.total_cost.toFixed(2) }}</TableCell>
                <TableCell>₱{{ dish.avg_price.toFixed(2) }}</TableCell>
                <TableCell :class="getMarginColor(dish.profit_margin)">
                  {{ dish.profit_margin.toFixed(1) }}%
                </TableCell>
                <TableCell>
                  <component 
                    :is="getTrendIcon(dish.cost_trend)" 
                    :class="['w-4 h-4', getTrendColor(dish.cost_trend)]"
                  />
                </TableCell>
              </TableRow>
              <TableRow v-if="dishAnalytics.length === 0">
                <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                  No dishes found
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>