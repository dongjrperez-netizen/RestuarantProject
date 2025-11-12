<script setup lang="ts">
import { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayoutSubscriptionRenewal from '../layouts/AppLayoutSubscriptionRenewal.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Subscription Renewal',
    href: '/subscription/renewal',
  }
];

const { props } = usePage();

interface Plan {
  plan_id: number | string;
  plan_name: string;
  plan_price: number;
  plan_duration: number;
  plan_duration_display?: string;
  is_trial?: boolean;
}
const plans = props.plans as Plan[];

interface Subscription {
  userSubscription_id: number | string;
  subscription_status: string;
  subscription_endDate: string;
  remaining_days: number;
   plan_name: string;
}

const subscriptions = (props.subscriptions as Subscription[]) || [];
const lastSubscriptionPlanId = props.lastSubscriptionPlanId as number | string | null;
const success = props.success || null;
const loadingPlanId = ref<number | string | null>(null);
const showPaymentMethodModal = ref(false);
const selectedPlanForPayment = ref<Plan | null>(null);

function openPaymentModal(plan: Plan) {
  selectedPlanForPayment.value = plan;
  showPaymentMethodModal.value = true;
}

function handleSubmit(planId: number | string) {
  loadingPlanId.value = planId;
}

// Process PayPal payment
function processPayPalPayment() {
  if (!selectedPlanForPayment.value) return;

  showPaymentMethodModal.value = false;
  loadingPlanId.value = selectedPlanForPayment.value.plan_id;

  // Create and submit form for PayPal
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/subscriptions/renew';

  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = '_token';
  csrfInput.value = (props.csrf_token as string) || '';
  form.appendChild(csrfInput);

  const planInput = document.createElement('input');
  planInput.type = 'hidden';
  planInput.name = 'plan_id';
  planInput.value = selectedPlanForPayment.value.plan_id.toString();
  form.appendChild(planInput);

  document.body.appendChild(form);
  form.submit();
}

// Process GCash payment via PayMongo
async function processGCashPayment() {
  if (!selectedPlanForPayment.value) return;

  showPaymentMethodModal.value = false;
  loadingPlanId.value = selectedPlanForPayment.value.plan_id;

  try {
    const csrfToken = (props.csrf_token as string) || '';

    const response = await fetch('/subscriptions/renew/paymongo', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        plan_id: selectedPlanForPayment.value.plan_id,
        payment_method: 'gcash',
      }),
    });

    if (!response.ok) {
      const errorData = await response.json();
      alert(errorData.message || 'Payment processing failed');
      loadingPlanId.value = null;
      return;
    }

    const data = await response.json();

    if (data.checkout_url) {
      window.location.href = data.checkout_url;
    } else {
      alert('Payment processing failed. Please try again.');
      loadingPlanId.value = null;
    }
  } catch (error) {
    console.error('GCash payment error:', error);
    alert('Payment processing failed. Please try again.');
    loadingPlanId.value = null;
  }
}

// Features for each plan based on plan name
const planFeatures: Record<string, string[]> = {
  'Basic': [
    '5 Employee Accounts',
    '10 Supplier Accounts',
    'Inventory Management',
    'Waiter Dashboard',
    'Order Processing',
    'Kitchen Dashboard',
    'Sales Reports',
    'Email Support'
  ],
  'Premium': [
    '10 Employee Accounts',
    '15 Supplier Accounts',
    'Regular Employee CRUD',
    'Inventory Management',
    'Order Processing & Tracking',
    'Kitchen Dashboard',
    'Waiter Dashboard',
    'Advanced Analytics & Reports',
    'Menu Planning',
    'Email Support'
  ],
  'Enterprise': [
    'Unlimited Employee Accounts',
    'Unlimited Supplier Accounts',
    'Full Inventory Management',
    'Advanced Order Processing',
    'Multi-Location Support',
    'Custom Reports & Analytics',
    'Menu Planning & Optimization',
    'Table Reservations',
    'Dedicated Account Manager',
    '24/7 Priority Support'
  ],
  'Free Trial': [
    'Full Restaurant Management',
    'Inventory Tracking',
    'Order Management',
    '30 Days Access'
  ]
};

// Get features for a plan
function getPlanFeatures(planName: string): string[] {
  return planFeatures[planName] || planFeatures['Free Trial'];
}
</script>

<template>
  <Head title="Renew Your Subscription - ServeWise" />
  <AppLayoutSubscriptionRenewal :breadcrumbs="breadcrumbs">
<div class="mx-6 space-y-6">

    <!-- Hero Section -->
    <div class="text-center mb-12">
      <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4" style="font-family: 'Playfair Display', serif;">
        Renew Your Subscription
      </h1>
      <p class="text-lg md:text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
        Your subscription has expired. Choose a plan to continue managing your restaurant.
      </p>
    </div>
    <!-- Success Alert -->
    <div v-if="success" class="mb-8 p-4 bg-green-50 border border-green-200 rounded-xl shadow-sm">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-green-800 font-medium">{{ success }}</p>
        </div>
      </div>
    </div>

    <!-- Pricing Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div
        v-for="plan in plans"
        :key="plan.plan_id"
        class="relative bg-white rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 border border-gray-200 flex flex-col"
      >
          <div class="p-8 flex flex-col flex-grow">
            <!-- Plan Header -->
            <div class="text-center mb-8">
              <h3 class="text-xl font-bold text-gray-900 mb-3" style="font-family: 'Playfair Display', serif;">{{ plan.plan_name }}</h3>
              <div class="flex items-baseline justify-center">
                <span class="text-3xl font-extrabold text-gray-900">₱{{ plan.plan_price }}</span>
                <span class="text-gray-600 ml-2 text-sm">
                  / {{ plan.plan_id == 4 ? plan.plan_duration + ' days' : (plan.plan_duration_display || plan.plan_duration + ' days') }}
                </span>
              </div>
            </div>

            <!-- Features -->
            <div class="mb-8 flex-grow">
              <ul class="space-y-3">
                <li v-for="feature in getPlanFeatures(plan.plan_name)" :key="feature" class="flex items-center">
                  <svg class="h-5 w-5 text-orange-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-gray-700 text-sm">{{ feature }}</span>
                </li>
              </ul>
            </div>

            <!-- Subscribe / Renew Button -->
            <button
              type="button"
              @click="openPaymentModal(plan)"
              :disabled="loadingPlanId !== null"
              class="w-full py-3 px-6 rounded-xl font-semibold text-white transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 shadow-lg focus:ring-orange-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loadingPlanId !== plan.plan_id">
                {{ plan.is_trial ? 'Start 30 Days Free Trial' : 'Renew Subscription' }}
              </span>
              <span v-else class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
              </span>
            </button>
      </div>
    </div>
  </div>

  <!-- Subscriptions History Section -->
  <div v-if="subscriptions.length" class="mt-16">
    <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-200">
      <div class="flex items-center mb-6">
        <div class="p-2 bg-orange-100 rounded-lg mr-4">
          <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900" style="font-family: 'Playfair Display', serif;">Your Subscription History</h2>
      </div>
      
      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="sub in subscriptions"
          :key="sub.userSubscription_id"
          class="p-6 border border-gray-200 rounded-xl bg-gradient-to-br from-gray-50 to-white hover:shadow-md transition-all duration-200 hover:-translate-y-1"
        >
          <!-- Plan Name -->
          <h3 class="text-lg font-bold text-gray-900 mb-4">
            {{ sub.plan_name }}
          </h3>

          <!-- Status -->
          <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-600">Status</span>
            <span 
              :class="[ 
                'px-3 py-1 rounded-full text-xs font-semibold',
                sub.subscription_status === 'active' 
                  ? 'bg-green-100 text-green-800' 
                  : sub.subscription_status === 'pending' 
                  ? 'bg-orange-100 text-orange-800'
                  : 'bg-red-100 text-red-800'
              ]"
            >
              {{ sub.subscription_status }}
            </span>
          </div>

          <!-- Expiry -->
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-gray-600">Expires</span>
            <span class="text-sm font-semibold text-gray-900">{{ sub.subscription_endDate }}</span>
          </div>

          <!-- Remaining Days -->
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-600">Days Remaining</span>
            <span class="text-sm font-semibold text-orange-600">0</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

  <!-- Payment Method Selection Modal -->
  <Dialog v-model:open="showPaymentMethodModal">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>Choose Payment Method</DialogTitle>
        <DialogDescription>
          Select your preferred payment method to renew your subscription
        </DialogDescription>
      </DialogHeader>
      <div class="grid gap-4 py-4">
        <!-- PayPal Option -->
        <button
          @click="processPayPalPayment"
          class="flex items-center gap-4 rounded-lg border-2 border-gray-200 p-4 transition-all hover:border-blue-500 hover:bg-blue-50"
        >
          <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
          </div>
          <div class="flex-1 text-left">
            <div class="font-semibold text-gray-900">PayPal</div>
            <div class="text-sm text-gray-500">Pay securely with PayPal</div>
          </div>
        </button>

        <!-- GCash Option -->
        <button
          @click="processGCashPayment"
          class="flex items-center gap-4 rounded-lg border-2 border-gray-200 p-4 transition-all hover:border-blue-500 hover:bg-blue-50"
        >
          <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
          </div>
          <div class="flex-1 text-left">
            <div class="font-semibold text-gray-900">GCash</div>
            <div class="text-sm text-gray-500">Pay with GCash via PayMongo</div>
          </div>
        </button>
      </div>
      <DialogFooter>
        <Button
          variant="outline"
          @click="showPaymentMethodModal = false"
        >
          Cancel
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  </AppLayoutSubscriptionRenewal>
</template>
