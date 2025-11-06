<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { CreditCard, TrendingUp, ArrowLeft, Check, Star, AlertTriangle } from 'lucide-vue-next';

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
        title: 'Upgrade Subscription',
        href: '/user-management/subscriptions/upgrade',
    },
];

interface SubscriptionPlan {
  plan_id: number;
  plan_name: string;
  plan_price: number;
  plan_duration: number;
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
  isUpgrade: boolean;
}>();

const selectedPlan = ref<number | null>(null);
const isProcessing = ref(false);
const showConfirmModal = ref(false);

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
};

const selectPlan = (planId: number) => {
  selectedPlan.value = planId;
  showConfirmModal.value = true;
};

const confirmUpgrade = () => {
  showConfirmModal.value = false;
  processUpgrade();
};

const cancelUpgrade = () => {
  showConfirmModal.value = false;
  selectedPlan.value = null;
};

const processUpgrade = async () => {
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
  typeInput.value = 'upgrade';
  form.appendChild(typeInput);

  document.body.appendChild(form);
  form.submit();
};

const goBack = () => {
  window.location.href = '/user-management/subscriptions';
};

const calculateUpgradeCost = (newPrice: number) => {
  if (!props.currentSubscription) return newPrice;
  return newPrice - props.currentSubscription.plan_price;
};
</script>

<template>
    <Head title="Upgrade Subscription" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Page Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" @click="goBack">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back
                </Button>
                <div>
                    <h1 class="text-3xl font-bold">Upgrade Subscription</h1>
                    <p class="text-muted-foreground">Choose a higher tier plan to upgrade your subscription</p>
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

            <!-- Available Upgrade Plans -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Available Upgrades</h2>
                <div v-if="props.availablePlans.length === 0" class="text-center py-12">
                    <Star class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                    <h3 class="text-lg font-medium mb-2">You're on the highest tier!</h3>
                    <p class="text-muted-foreground">There are no higher tier plans available for upgrade.</p>
                    <Button class="mt-4" @click="goBack">
                        Back to Subscriptions
                    </Button>
                </div>

                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="plan in props.availablePlans"
                        :key="plan.plan_id"
                        :class="[
                            'cursor-pointer transition-all hover:shadow-lg border-2',
                            selectedPlan === plan.plan_id ? 'ring-2 ring-primary border-primary' : 'border-green-200'
                        ]"
                        @click="selectPlan(plan.plan_id)"
                    >
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center gap-2">
                                    <TrendingUp class="h-5 w-5 text-green-600" />
                                    {{ plan.plan_name }}
                                </CardTitle>
                                <div v-if="selectedPlan === plan.plan_id" class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                    <Check class="h-4 w-4" />
                                </div>
                            </div>
                            <Badge variant="outline" class="text-green-700 border-green-300 w-fit">
                                Upgrade Option
                            </Badge>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div class="text-3xl font-bold text-primary">
                                    {{ formatCurrency(plan.plan_price) }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    per {{ plan.plan_duration }} days
                                </div>
                                <div v-if="props.currentSubscription" class="p-2 bg-green-50 rounded-lg">
                                    <p class="text-sm text-green-800">
                                        Upgrade cost: <span class="font-semibold">{{ formatCurrency(calculateUpgradeCost(plan.plan_price)) }}</span>
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Action Buttons -->
            <div v-if="props.availablePlans.length > 0" class="flex gap-4 pt-4">
                <Button variant="outline" @click="goBack" :disabled="isProcessing">
                    Cancel
                </Button>
                <Button
                    @click="processUpgrade"
                    :disabled="!selectedPlan || isProcessing"
                    class="flex items-center gap-2"
                >
                    <TrendingUp v-if="isProcessing" class="h-4 w-4 animate-spin" />
                    <TrendingUp v-else class="h-4 w-4" />
                    {{ isProcessing ? 'Processing...' : 'Upgrade Subscription' }}
                </Button>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <Dialog v-model:open="showConfirmModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5 text-yellow-600" />
                        Confirm Subscription Upgrade
                    </DialogTitle>
                    <DialogDescription class="pt-4 space-y-3">
                        <p class="text-base">
                            If you upgrade your subscription, your remaining days/time left will be replaced by your new subscription period.
                        </p>
                        <div v-if="props.currentSubscription" class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                            <p class="text-sm font-medium text-yellow-800">
                                Current remaining time: {{ props.currentSubscription.remaining_time }}
                            </p>
                            <p class="text-sm text-yellow-700 mt-1">
                                This time will not be added to your new subscription.
                            </p>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Are you sure you want to proceed with the upgrade?
                        </p>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        @click="cancelUpgrade"
                        variant="outline"
                        type="button"
                    >
                        Cancel
                    </Button>
                    <Button
                        @click="confirmUpgrade"
                        type="button"
                    >
                        Yes, Upgrade Now
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>