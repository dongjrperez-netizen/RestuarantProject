<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const { props } = usePage()
const plans = props.plans
const subscriptions = props.subscriptions || []
const success = props.success || null
const loadingPlanId = ref(null)
const showPaymentMethodModal = ref(false)
const selectedPlan = ref(null)
const processingPayment = ref(false)

// Debug function to check CSRF token
function debugCSRF() {
  console.log('CSRF Token:', props.csrf_token)
  console.log('Page props:', props)
}

// Call debug on component mount
debugCSRF()

// Helper function to open payment method modal
function openPaymentModal(plan) {
  console.log('Opening payment modal for plan:', plan)
  selectedPlan.value = plan
  showPaymentMethodModal.value = true
  console.log('Modal should be visible:', showPaymentMethodModal.value)
}

// Process payment with PayPal
async function processPayPalPayment() {
  if (!selectedPlan.value) return

  processingPayment.value = true
  loadingPlanId.value = selectedPlan.value.plan_id

  // Submit form for PayPal payment
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = '/subscriptions/create'

  const csrfInput = document.createElement('input')
  csrfInput.type = 'hidden'
  csrfInput.name = '_token'
  csrfInput.value = props.csrf_token

  const planInput = document.createElement('input')
  planInput.type = 'hidden'
  planInput.name = 'plan_id'
  planInput.value = selectedPlan.value.plan_id

  form.appendChild(csrfInput)
  form.appendChild(planInput)
  document.body.appendChild(form)
  form.submit()
}

// Process payment with GCash/PayMongo
async function processGCashPayment() {
  if (!selectedPlan.value) return

  processingPayment.value = true

  try {
    const csrfToken = props.csrf_token

    const response = await fetch('/subscriptions/paymongo/create', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        plan_id: selectedPlan.value.plan_id,
        payment_method: 'gcash',
      }),
    })

    if (!response.ok) {
      const errorData = await response.json()
      alert(errorData.message || 'Payment processing failed')
      processingPayment.value = false
      return
    }

    const data = await response.json()

    if (data.checkout_url) {
      // Redirect to PayMongo checkout page
      window.location.href = data.checkout_url
    } else {
      alert('Payment processing failed. Please try again.')
      processingPayment.value = false
    }
  } catch (error) {
    console.error('GCash payment error:', error)
    alert('Payment processing failed. Please try again.')
    processingPayment.value = false
  }
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
            <div v-if="plan.plan_id == 4">
              <!-- Free Trial Form -->
              <form
                method="POST"
                :action="route('subscriptions.free-trial')"
              >
                <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                <input type="hidden" name="plan_id" :value="plan.plan_id" />
                <button
                  type="submit"
                  :disabled="loadingPlanId !== null"
                  class="w-full py-3 px-6 rounded-xl font-semibold text-white transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 shadow-lg focus:ring-orange-300 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Start 30 Days Free Trial
                </button>
              </form>
            </div>
            <div v-else>
              <!-- Paid Plan - Show Payment Method Modal -->
              <button
                @click="openPaymentModal(plan)"
                :disabled="loadingPlanId !== null"
                class="w-full py-3 px-6 rounded-xl font-semibold text-white transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 shadow-lg focus:ring-orange-300 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Subscribe Now
              </button>
            </div>
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

    <!-- Payment Method Selection Modal -->
    <div v-if="showPaymentMethodModal" class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showPaymentMethodModal = false"></div>

      <!-- Modal panel -->
      <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all w-full max-w-lg z-10">
        <div class="bg-white px-6 pt-6 pb-4">
          <div class="sm:flex sm:items-start">
            <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
              <h3 class="text-2xl font-bold text-gray-900 mb-2" style="font-family: 'Playfair Display', serif;" id="modal-title">
                Choose Payment Method
              </h3>
              <p class="text-sm text-gray-600 mb-6">
                Select your preferred payment method for {{ selectedPlan?.plan_name }}
              </p>

              <!-- Payment Method Options -->
              <div class="space-y-3">
                <!-- PayPal Option -->
                <button
                  @click="processPayPalPayment"
                  :disabled="processingPayment"
                  class="w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed group"
                >
                  <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200">
                      <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none">
                        <path d="M20.067 8.478c.492.88.556 2.014.3 3.327-.74 3.806-3.276 5.12-6.514 5.12h-.5a.805.805 0 00-.794.68l-.04.22-.63 3.993-.028.15a.805.805 0 01-.794.679H7.72a.483.483 0 01-.477-.558L8.926 12.5" stroke="#003087" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17.341 5.272c.305-.018.632-.01.96.028 1.993.232 3.317 1.307 3.766 3.178.492.88.556 2.014.3 3.327-.74 3.806-3.276 5.12-6.514 5.12h-.5a.805.805 0 00-.794.68l-.04.22-.63 3.993-.028.15a.805.805 0 01-.794.679H9.72" stroke="#009CDE" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </div>
                    <div class="text-left">
                      <p class="font-semibold text-gray-900">PayPal</p>
                      <p class="text-sm text-gray-500">Pay securely with PayPal</p>
                    </div>
                  </div>
                  <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                  </svg>
                </button>

                <!-- GCash Option -->
                <button
                  @click="processGCashPayment"
                  :disabled="processingPayment"
                  class="w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-blue-600 hover:bg-blue-50 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed group"
                >
                  <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center group-hover:bg-blue-100">
                      <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none">
                        <rect width="24" height="24" rx="4" fill="#007DFF"/>
                        <path d="M12 6v12M6 12h12" stroke="white" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                    </div>
                    <div class="text-left">
                      <p class="font-semibold text-gray-900">GCash</p>
                      <p class="text-sm text-gray-500">Pay with GCash via PayMongo</p>
                    </div>
                  </div>
                  <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                  </svg>
                </button>
              </div>

              <!-- Processing Indicator -->
              <div v-if="processingPayment" class="mt-4 flex items-center justify-center space-x-2 text-sm text-gray-600">
                <svg class="animate-spin h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Processing payment...</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
          <button
            type="button"
            @click="showPaymentMethodModal = false"
            :disabled="processingPayment"
            class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
