<script setup lang="ts">
import { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayoutSubscriptionRenewal from '../layouts/AppLayoutSubscriptionRenewal.vue';

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
const isLoading = ref(false);

function handleSubmit() {
  isLoading.value = true;
}
</script>

<template>
  <Head title="Renew Your Subscription - ServeWise" />
  <AppLayoutSubscriptionRenewal :breadcrumbs="breadcrumbs">

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
        :class="[
          'relative bg-white rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 border border-gray-200',
          plan.plan_id === lastSubscriptionPlanId ? 'ring-2 ring-orange-500 scale-105' : ''
        ]"
      >
          <!-- Last Subscription Badge -->
          <div v-if="plan.plan_id === lastSubscriptionPlanId" 
               class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
            <span class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-4 py-1 rounded-full text-sm font-semibold shadow-lg">
              Previous Plan
            </span>
          </div>

          <div class="p-8">
            <!-- Plan Header -->
            <div class="text-center mb-8">
              <h3 class="text-xl font-bold text-gray-900 mb-3" style="font-family: 'Playfair Display', serif;">{{ plan.plan_name }}</h3>
              <div class="flex items-baseline justify-center">
                <span class="text-3xl font-extrabold text-gray-900">₱{{ plan.plan_price }}</span>
                <span class="text-gray-600 ml-2 text-sm">/ {{ plan.plan_duration }} days</span>
              </div>
            </div>

            <!-- Features -->
            <div class="mb-8">
              <ul class="space-y-4">
                <li class="flex items-center">
                  <svg class="h-5 w-5 text-orange-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-gray-700 font-medium">Full Restaurant Management</span>
                </li>
                <li class="flex items-center">
                  <svg class="h-5 w-5 text-orange-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-gray-700 font-medium">Inventory Tracking</span>
                </li>
                <li class="flex items-center">
                  <svg class="h-5 w-5 text-orange-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-gray-700 font-medium">Order Management</span>
                </li>
                <li class="flex items-center">
                  <svg class="h-5 w-5 text-orange-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-gray-700 font-medium">24/7 Support</span>
                </li>
              </ul>
            </div>

            <!-- Subscribe / Renew Button -->
            <form method="POST" :action="route('subscriptions.renew.process')" @submit="handleSubmit">
              <input type="hidden" name="_token" :value="$page.props.csrf_token" />
              <input type="hidden" name="plan_id" :value="plan.plan_id" />
            <button
              type="submit"
              :disabled="isLoading"
              :class="[ 
                'w-full py-3 px-6 rounded-xl font-semibold text-white transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4',
                plan.plan_id === lastSubscriptionPlanId
                  ? 'bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 shadow-lg focus:ring-orange-300'
                  : 'bg-slate-700 hover:bg-slate-800 shadow-md focus:ring-slate-300'
              ]"
            >
              <span v-if="!isLoading">
                {{ plan.is_trial ? 'Start 7 Days Free Trial' : 'Renew Subscription' }}
              </span>
              <span v-else class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
              </span>
            </button>
        </form>
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
            <span class="text-sm font-semibold text-orange-600">{{ sub.remaining_days }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  </AppLayoutSubscriptionRenewal>
</template>
