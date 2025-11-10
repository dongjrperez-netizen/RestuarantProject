<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { CreditCard, RefreshCw, ArrowLeft, Check } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Management',
        href: '/user-management',
    },
    {
        title: 'My Subscriptions',
        href: '/user-management/subscriptions',
    },
    {
        title: 'Renew Subscription',
        href: '/user-management/subscriptions/renew',
    },
];

interface SubscriptionPlan {
  plan_id: number;
  plan_name: string;
  plan_price: number;
  plan_duration: number;
  plan_duration_display?: string;
  paypal_plan_id?: string;
}

interface CurrentSubscription {
  userSubscription_id: number;
  plan_name: string;
  plan_price: number;
  plan_duration: number;
  subscription_status: string;
  subscription_startDate: string;
  subscription_endDate: string;
  remaining_days: number;
  remaining_time: string;
  is_expired: boolean;
  is_trial: boolean;
  is_currently_active: boolean;
  created_at: string;
}

const props = defineProps<{
  currentSubscription: CurrentSubscription | null;
  availablePlans: SubscriptionPlan[];
  isRenewal: boolean;
}>();

const selectedPlan = ref<number | null>(null);
const isProcessing = ref(false);

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
};

const selectPlan = (planId: number) => {
  selectedPlan.value = planId;
};

const processRenewal = async () => {
  if (!selectedPlan.value) return;

  isProcessing.value = true;

  // Create form data
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/user-management/subscriptions/process';

  // Add CSRF token
  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = '_token';
  csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  form.appendChild(csrfInput);

  // Add plan ID
  const planInput = document.createElement('input');
  planInput.type = 'hidden';
  planInput.name = 'plan_id';
  planInput.value = selectedPlan.value.toString();
  form.appendChild(planInput);

  // Add type
  const typeInput = document.createElement('input');
  typeInput.type = 'hidden';
  typeInput.name = 'type';
  typeInput.value = 'renewal';
  form.appendChild(typeInput);

  document.body.appendChild(form);
  form.submit();
};

const goBack = () => {
  window.location.href = '/user-management/subscriptions';
};
</script>

<template>
    <Head title="Renew Subscription" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Page Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" @click="goBack">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back
                </Button>
                <div>
                    <h1 class="text-3xl font-bold">Renew Subscription</h1>
                    <p class="text-muted-foreground">Choose a plan to renew your subscription</p>
                </div>
            </div>

            <!-- Current Subscription -->
            <Card v-if="props.currentSubscription" class="border-2 border-blue-200">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CreditCard class="h-5 w-5" />
                        Current Subscription
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Plan</p>
                            <p class="font-semibold">{{ props.currentSubscription.plan_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Price</p>
                            <p class="font-semibold">{{ formatCurrency(props.currentSubscription.plan_price) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Time Remaining</p>
                            <p class="font-semibold text-orange-600">{{ props.currentSubscription.remaining_time }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Available Plans -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Select a Plan to Renew</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="plan in props.availablePlans"
                        :key="plan.plan_id"
                        :class="[
                            'cursor-pointer transition-all hover:shadow-lg',
                            selectedPlan === plan.plan_id ? 'ring-2 ring-primary border-primary' : ''
                        ]"
                        @click="selectPlan(plan.plan_id)"
                    >
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle>{{ plan.plan_name }}</CardTitle>
                                <div v-if="selectedPlan === plan.plan_id" class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                    <Check class="h-4 w-4" />
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div class="text-3xl font-bold text-primary">
                                    {{ formatCurrency(plan.plan_price) }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    per {{ plan.plan_id == 4 ? plan.plan_duration + ' days' : (plan.plan_duration_display || plan.plan_duration + ' days') }}
                                </div>
                                <Badge
                                    v-if="props.currentSubscription && plan.plan_id === props.currentSubscription.plan_name"
                                    variant="secondary"
                                    class="text-xs"
                                >
                                    Current Plan
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4 pt-4">
                <Button variant="outline" @click="goBack" :disabled="isProcessing">
                    Cancel
                </Button>
                <Button
                    @click="processRenewal"
                    :disabled="!selectedPlan || isProcessing"
                    class="flex items-center gap-2"
                >
                    <RefreshCw v-if="isProcessing" class="h-4 w-4 animate-spin" />
                    <RefreshCw v-else class="h-4 w-4" />
                    {{ isProcessing ? 'Processing...' : 'Renew Subscription' }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>