<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { ArrowDown, AlertTriangle, Check } from 'lucide-vue-next';

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
        title: 'Downgrade Subscription',
        href: '/user-management/subscriptions/downgrade',
    },
];

interface SubscriptionData {
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

interface Plan {
  plan_id: number;
  plan_name: string;
  plan_price: number;
  plan_duration: number;
  employee_limit: number | null;
  supplier_limit: number | null;
}

const props = defineProps<{
  currentSubscription: SubscriptionData | null;
  availablePlans: Plan[];
}>();

const selectedPlanId = ref<number | null>(null);
const processing = ref(false);

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const selectPlan = (planId: number) => {
  selectedPlanId.value = planId;
};

const processDowngrade = () => {
  if (!selectedPlanId.value) return;
  
  processing.value = true;
  
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/user-management/subscriptions/process';
  
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
  }
  
  const planInput = document.createElement('input');
  planInput.type = 'hidden';
  planInput.name = 'plan_id';
  planInput.value = selectedPlanId.value.toString();
  form.appendChild(planInput);
  
  const typeInput = document.createElement('input');
  typeInput.type = 'hidden';
  typeInput.name = 'type';
  typeInput.value = 'downgrade';
  form.appendChild(typeInput);
  
  document.body.appendChild(form);
  form.submit();
};
</script>

<template>
    <Head title="Downgrade Subscription" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-2">
                <h1 class="text-3xl font-bold">Downgrade Subscription</h1>
                <p class="text-muted-foreground">Choose a lower-tier plan that suits your needs</p>
            </div>

            <!-- Current Subscription Info -->
            <Card v-if="props.currentSubscription" class="border-2 border-orange-500/20 bg-orange-50/50">
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <AlertTriangle class="h-6 w-6 text-orange-600" />
                        <div>
                            <CardTitle class="text-lg">Current Plan</CardTitle>
                            <CardDescription>Your active subscription</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-xl">{{ props.currentSubscription.plan_name }}</h3>
                            <p class="text-2xl font-bold text-orange-600">{{ formatCurrency(props.currentSubscription.plan_price) }}</p>
                            <p class="text-sm text-muted-foreground">per {{ props.currentSubscription.plan_duration }} days</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-muted-foreground">Expires on</p>
                            <p class="font-medium">{{ formatDate(props.currentSubscription.subscription_endDate) }}</p>
                            <p class="text-sm text-orange-600">{{ props.currentSubscription.remaining_time }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Warning Alert -->
            <Card class="border-2 border-yellow-500/30 bg-yellow-50/50">
                <CardContent class="pt-6">
                    <div class="flex items-start gap-3">
                        <AlertTriangle class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" />
                        <div class="space-y-2">
                            <h3 class="font-semibold text-yellow-900">Important Information</h3>
                            <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                                <li>Downgrading will start a new subscription period</li>
                                <li>Your current subscription will be expired immediately</li>
                                <li>Remaining days from your current plan will NOT be transferred</li>
                                <li>Some features may become limited or unavailable</li>
                            </ul>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Available Plans -->
            <div class="space-y-4">
                <h2 class="text-2xl font-semibold">Available Lower-Tier Plans</h2>
                <p class="text-muted-foreground" v-if="props.availablePlans.length === 0">
                    No lower-tier plans available. You are already on the lowest paid plan.
                </p>
                
                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card 
                        v-for="plan in props.availablePlans" 
                        :key="plan.plan_id"
                        :class="[
                            'cursor-pointer transition-all hover:shadow-lg',
                            selectedPlanId === plan.plan_id ? 'border-2 border-primary ring-2 ring-primary/20' : 'border-2 border-transparent'
                        ]"
                        @click="selectPlan(plan.plan_id)"
                    >
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-xl">{{ plan.plan_name }}</CardTitle>
                                <div 
                                    v-if="selectedPlanId === plan.plan_id"
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-primary"
                                >
                                    <Check class="h-4 w-4 text-white" />
                                </div>
                            </div>
                            <div class="pt-2">
                                <p class="text-3xl font-bold">{{ formatCurrency(plan.plan_price) }}</p>
                                <p class="text-sm text-muted-foreground">per {{ plan.plan_duration }} days</p>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground">Employees:</span>
                                    <Badge variant="secondary">
                                        {{ plan.employee_limit ?? 'Unlimited' }}
                                    </Badge>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground">Suppliers:</span>
                                    <Badge variant="secondary">
                                        {{ plan.supplier_limit ?? 'Unlimited' }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Action Buttons -->
            <div v-if="props.availablePlans.length > 0" class="flex gap-4 justify-end pt-4">
                <Button 
                    variant="outline" 
                    @click="() => window.location.href = '/user-management/subscriptions'"
                >
                    Cancel
                </Button>
                <Button 
                    variant="default"
                    :disabled="!selectedPlanId || processing"
                    @click="processDowngrade"
                >
                    <ArrowDown class="h-4 w-4 mr-2" />
                    {{ processing ? 'Processing...' : 'Proceed to Downgrade' }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
