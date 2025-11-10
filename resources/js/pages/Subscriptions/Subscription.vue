<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const { props } = usePage()
const plans = props.plans
const subscriptions = props.subscriptions || []
const success = props.success || null
const loadingPlanId = ref(null)

// Debug function to check CSRF token
function debugCSRF() {
  console.log('CSRF Token:', props.csrf_token)
  console.log('Page props:', props)
}

// Call debug on component mount
debugCSRF()

// Helper function to handle form submission loading state
function handleSubmit(planId) {
  loadingPlanId.value = planId
  // Form will submit normally, loading will reset on page change
}

// Helper function to convert days to minutes for display
function convertDaysToMinutes(days) {
  return days * 24 * 60; // days * 24 hours * 60 minutes
}

// Compute most popular plan (middle one or based on some logic)
const popularPlanIndex = computed(() => Math.floor(plans.length / 2))

// Features for each plan based on plan name
const planFeatures = {
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
}

// Get features for a plan
function getPlanFeatures(planName) {
  return planFeatures[planName] || planFeatures['Free Trial']
}
</script>

<template>
  <Head title="Choose Your Plan - ServeWise">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Kaushan+Script&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet" />
  </Head>

  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='1'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>
    
    <!-- Header with logout button and logo -->
    <header class="relative z-10 px-6 py-6">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <form method="POST" action="/logout">
          <input type="hidden" name="_token" :value="$page.props.csrf_token" />
          <button 
            type="submit" 
            class="inline-flex items-center text-white/70 hover:text-white transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" 
                 fill="none" 
                 viewBox="0 0 24 24" 
                 stroke="currentColor" 
                 class="h-5 w-5 mr-2">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span class="text-sm font-medium">Logout</span>
          </button>
        </form>
        
        <div class="text-3xl font-bold text-orange-500" style="font-family: 'Kaushan Script', cursive;">
          ServeWise
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <div class="relative z-10 text-center px-6 py-16">
      <h1 class="text-3xl md:text-4xl font-bold text-white mb-4" style="font-family: 'Playfair Display', serif;">
        Choose Your Plan
      </h1>
      <p class="text-lg md:text-xl text-gray-300 mb-8 max-w-2xl mx-auto font-light">
        Select the perfect subscription plan for your restaurant management needs
      </p>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 pb-16">
      
      <!-- Success Alert -->
      <div v-if="success" class="mb-8 p-4 bg-green-50/90 backdrop-blur-sm border border-green-200 rounded-xl shadow-sm">
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
          v-for="(plan, index) in plans"
          :key="plan.plan_id"
          class="relative bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 border border-white/20 flex flex-col"
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

            <!-- Features List -->
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

            <!-- Subscribe / Free Trial Button -->
            <form
              method="POST"
              :action="plan.plan_id == 4 ? route('subscriptions.free-trial') : route('subscriptions.create')"
              @submit="handleSubmit(plan.plan_id)"
            >
              <input type="hidden" name="_token" :value="$page.props.csrf_token" />
              <input type="hidden" name="plan_id" :value="plan.plan_id" />
              <button
                type="submit"
                :disabled="loadingPlanId !== null"
                class="w-full py-3 px-6 rounded-xl font-semibold text-white transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 shadow-lg focus:ring-orange-300 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="loadingPlanId !== plan.plan_id">
                  {{ plan.plan_id == 4 ? 'Start 30 Days Free Trial' : 'Subscribe Now' }}
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

      <!-- Current Subscriptions Section -->
      <div v-if="subscriptions.length" class="mt-16">
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl p-8 border border-white/20">
          <div class="flex items-center mb-6">
            <div class="p-2 bg-orange-100 rounded-lg mr-4">
              <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900" style="font-family: 'Playfair Display', serif;">Your Active Subscriptions</h2>
          </div>
          
          <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="sub in subscriptions"
              :key="sub.userSubscription_id"
              class="p-6 border border-gray-200 rounded-xl bg-gradient-to-br from-gray-50 to-white hover:shadow-md transition-all duration-200 hover:-translate-y-1"
            >
              <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-600">Status</span>
                <span 
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-semibold',
                    sub.subscription_status === 'Active' 
                      ? 'bg-green-100 text-green-800' 
                      : sub.subscription_status === 'Pending' 
                      ? 'bg-orange-100 text-orange-800'
                      : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ sub.subscription_status }}
                </span>
              </div>
              <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-600">Expires</span>
                <span class="text-sm font-semibold text-gray-900">{{ sub.subscription_endDate }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Remaining</span>
                <span class="text-sm font-semibold text-orange-600">{{ convertDaysToMinutes(sub.remaining_days) }} minutes</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
