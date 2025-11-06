<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItem } from '@/types';
import { AlertTriangle, ArrowLeft, Edit, Calendar, Package, User, DollarSign, FileText, Clock, Restaurant } from 'lucide-vue-next';

interface Restaurant {
  id: number;
  restaurant_name: string;
}

interface Ingredient {
  ingredient_name: string;
}

interface User {
  id: number;
  name: string;
  email: string;
}

interface DamageSpoilageLog {
  id: number;
  type: string;
  quantity: number;
  unit: string;
  reason: string | null;
  notes: string | null;
  incident_date: string;
  estimated_cost: number | null;
  created_at: string;
  updated_at: string;
  ingredient: Ingredient;
  user: User;
  restaurant: Restaurant;
}

interface Props {
  log: DamageSpoilageLog;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Damage & Spoilage', href: '/damage-spoilage' },
  { title: 'View Details', href: `/damage-spoilage/${props.log.id}` },
];

const getTypeVariant = (type: string) => {
  return type === 'damage' ? 'destructive' : 'secondary';
};

const getTypeIcon = (type: string) => {
  return type === 'damage' ? AlertTriangle : Package;
};

const formatCurrency = (amount: number | null) => {
  return amount ? `$${amount.toFixed(2)}` : 'N/A';
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getTypeLabel = (type: string) => {
  return type === 'damage' ? 'Damage' : 'Spoilage';
};
</script>

<template>
  <Head :title="`${getTypeLabel(log.type)} Report #${log.id}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl mx-auto space-y-6 px-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="sm" @click="$inertia.visit('/damage-spoilage')">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to List
          </Button>
          <div>
            <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
              <component :is="getTypeIcon(log.type)" class="w-8 h-8" :class="log.type === 'damage' ? 'text-red-600' : 'text-orange-600'" />
              {{ getTypeLabel(log.type) }} Report #{{ log.id }}
            </h1>
            <p class="text-muted-foreground">Detailed view of damage/spoilage incident</p>
          </div>
        </div>
        <Link :href="`/damage-spoilage/${log.id}/edit`">
          <Button>
            <Edit class="w-4 h-4 mr-2" />
            Edit Report
          </Button>
        </Link>
      </div>

      <!-- Main Details Card -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <component :is="getTypeIcon(log.type)" class="w-5 h-5" :class="log.type === 'damage' ? 'text-red-600' : 'text-orange-600'" />
            Incident Details
            <Badge :variant="getTypeVariant(log.type)" class="ml-2">
              {{ getTypeLabel(log.type) }}
            </Badge>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Ingredient Information -->
            <div class="space-y-4">
              <div class="flex items-center gap-2 text-lg font-semibold">
                <Package class="w-5 h-5 text-blue-600" />
                Ingredient Information
              </div>
              <div class="space-y-3 pl-7">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Ingredient</p>
                  <p class="text-lg font-semibold">{{ log.ingredient.ingredient_name }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Quantity Lost</p>
                  <p class="text-lg font-semibold">{{ log.quantity }} {{ log.unit }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Estimated Cost</p>
                  <p class="text-lg font-semibold">{{ formatCurrency(log.estimated_cost) }}</p>
                </div>
              </div>
            </div>

            <!-- Incident Information -->
            <div class="space-y-4">
              <div class="flex items-center gap-2 text-lg font-semibold">
                <Calendar class="w-5 h-5 text-green-600" />
                Incident Information
              </div>
              <div class="space-y-3 pl-7">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Incident Date</p>
                  <p class="text-lg font-semibold">{{ formatDate(log.incident_date) }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Reported By</p>
                  <p class="text-lg font-semibold">{{ log.user.name }}</p>
                  <p class="text-sm text-muted-foreground">{{ log.user.email }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Restaurant</p>
                  <p class="text-lg font-semibold">{{ log.restaurant.restaurant_name }}</p>
                </div>
              </div>
            </div>
          </div>

          <Separator />

          <!-- Reason and Notes -->
          <div class="space-y-4">
            <div v-if="log.reason">
              <div class="flex items-center gap-2 text-lg font-semibold mb-3">
                <FileText class="w-5 h-5 text-orange-600" />
                Reason for {{ getTypeLabel(log.type) }}
              </div>
              <div class="pl-7">
                <p class="text-base">{{ log.reason }}</p>
              </div>
            </div>

            <div v-if="log.notes">
              <div class="flex items-center gap-2 text-lg font-semibold mb-3">
                <FileText class="w-5 h-5 text-purple-600" />
                Additional Notes
              </div>
              <div class="pl-7">
                <p class="text-base whitespace-pre-wrap">{{ log.notes }}</p>
              </div>
            </div>

            <div v-if="!log.reason && !log.notes">
              <div class="text-center py-6">
                <FileText class="w-8 h-8 text-muted-foreground mx-auto mb-2" />
                <p class="text-muted-foreground">No additional details provided</p>
              </div>
            </div>
          </div>

          <Separator />

          <!-- Timestamps -->
          <div class="space-y-4">
            <div class="flex items-center gap-2 text-lg font-semibold">
              <Clock class="w-5 h-5 text-gray-600" />
              Record Information
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-7">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Reported On</p>
                <p class="text-base">{{ formatDateTime(log.created_at) }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Last Updated</p>
                <p class="text-base">{{ formatDateTime(log.updated_at) }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Actions -->
      <div class="flex justify-between">
        <Button variant="outline" @click="$inertia.visit('/damage-spoilage')">
          <ArrowLeft class="w-4 h-4 mr-2" />
          Back to List
        </Button>
        <Link :href="`/damage-spoilage/${log.id}/edit`">
          <Button>
            <Edit class="w-4 h-4 mr-2" />
            Edit Report
          </Button>
        </Link>
      </div>
    </div>
  </AppLayout>
</template>