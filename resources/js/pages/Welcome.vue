<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';

const pricingPlans = [
  {
    name: 'Basic',
    price: '₱1,800',
    period: 'per month',
    description: 'Perfect for small restaurants and cafes',
    features: [
      '5 Employee Accounts',
      '10 Supplier Accounts',
      'Inventory Management',
      'Waiter Dashboard',
      'Order Processing',
      'Kitchen Dashboard',
      'Sales Reports',
      'Email Support'
    ],
    popular: false
  },
  {
    name: 'Premium',
    price: '₱4,500',
    period: 'per month',
    description: 'Ideal for growing restaurants',
    features: [
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
    popular: true
  },
  {
    name: 'Enterprise',
    price: '₱11,300',
    period: 'per month',
    description: 'For large restaurants and chains',
    features: [
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
    popular: false
  }
];

const features = [
  {
    icon: '📊',
    title: 'Inventory Management',
    description: 'Track ingredients, suppliers, and stock levels in real-time. Never run out of essentials again.'
  },
  {
    icon: '🍽️',
    title: 'Order Processing',
    description: 'Streamline your order workflow from kitchen to table. Manage orders efficiently and reduce wait times.'
  },
  {
    icon: '👥',
    title: 'Staff Management',
    description: 'Organize your team, track schedules, and manage roles with our comprehensive employee management system.'
  },
  {
    icon: '💳',
    title: 'Payment Integration',
    description: 'Secure PayPal integration for subscriptions and payments. Handle transactions seamlessly.'
  },
  {
    icon: '📈',
    title: 'Analytics & Reports',
    description: 'Get detailed insights into your restaurant performance with comprehensive reporting tools.'
  },
  {
    icon: '🔐',
    title: 'Multi-Tenant Security',
    description: 'Enterprise-grade security with role-based access control and document verification.'
  }
];
</script>

<template>
  <Head title="Welcome - ServeWise Restaurant Management">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Kaushan+Script&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet" />
  </Head>

  <div class="min-h-screen bg-white scroll-smooth">
    <!-- HERO SECTION -->
    <div class="relative min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
      <!-- Navigation -->
      <nav class="fixed top-0 left-0 right-0 z-50 px-4 sm:px-6 lg:px-8 py-2 sm:py-3 bg-slate-900/90 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
          <!-- Logo Section -->
          <div class="flex items-center gap-2 flex-shrink-0">
            <AppLogoIcon class="w-6 h-6 sm:w-8 md:w-9 sm:h-8 md:h-9" />
            <div class="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold text-orange-500" style="font-family: 'Kaushan Script', cursive;">
              ServeWise
            </div>
          </div>

          <!-- Desktop Navigation Menu -->
          <div class="hidden lg:flex space-x-6 text-white text-sm">
            <a href="#features" class="hover:text-orange-300 transition-colors">Features</a>
            <a href="#contact" class="hover:text-orange-300 transition-colors">Contact Us</a>
            <a href="#pricing" class="hover:text-orange-300 transition-colors">Pricing</a>
          </div>

          <!-- Auth Buttons -->
          <div class="flex gap-2 items-center flex-shrink-0">
            <Link v-if="$page.props.auth.user"
                  :href="route('dashboard')"
                  class="px-4 sm:px-5 py-1.5 sm:py-2 bg-orange-500 text-white text-xs sm:text-sm rounded-full hover:bg-orange-600 transition font-semibold">
              Dashboard
            </Link>
            <template v-else>
              <Link :href="route('login')"
                    class="px-3 sm:px-4 py-1.5 sm:py-2 text-white hover:text-orange-300 transition font-medium text-xs sm:text-sm">
                Sign In
              </Link>
              <Link :href="route('register')"
                    class="px-3 sm:px-4 py-1.5 sm:py-2 bg-orange-500 text-white text-xs sm:text-sm rounded-full hover:bg-orange-600 transition font-semibold whitespace-nowrap">
                Get Started
              </Link>
            </template>
          </div>
        </div>
      </nav>

      <!-- Hero Content -->
      <div class="relative z-10 flex flex-col items-center justify-center min-h-[70vh] sm:min-h-[80vh] text-center px-4 sm:px-6 pt-20 sm:pt-24 md:pt-28">
        <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold text-white mb-4 sm:mb-6 leading-tight" style="font-family: 'Playfair Display', serif;">
          Restaurant Management
          <span class="block text-orange-500">Made Simple</span>
        </h1>
        <p class="text-base sm:text-lg md:text-xl lg:text-2xl text-white mb-6 sm:mb-8 max-w-3xl font-light leading-relaxed px-2">
          From inventory tracking to order management, ServeWise provides everything you need to run a successful restaurant with confidence and style.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 mt-4 sm:mt-8 w-full sm:w-auto px-4 sm:px-0">
          <Link :href="route('register')"
                class="bg-orange-500 text-white px-6 sm:px-8 md:px-10 py-3 sm:py-4 rounded-full text-base sm:text-lg font-semibold hover:bg-orange-600 transition-all transform hover:scale-105 shadow-lg">
            Get Started
          </Link>
          <a href="#features"
             class="border-2 border-white/30 text-white px-6 sm:px-8 md:px-10 py-3 sm:py-4 rounded-full text-base sm:text-lg font-semibold hover:bg-white hover:text-slate-900 transition-all">
            Explore Features
          </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 sm:gap-6 md:gap-8 mt-10 sm:mt-12 md:mt-16 text-center w-full max-w-2xl px-4">
          <div>
            <div class="text-2xl sm:text-3xl md:text-4xl font-bold text-orange-500">500+</div>
            <div class="text-xs sm:text-sm md:text-base text-gray-400 font-medium">Restaurants</div>
          </div>
          <div>
            <div class="text-2xl sm:text-3xl md:text-4xl font-bold text-orange-500">99.9%</div>
            <div class="text-xs sm:text-sm md:text-base text-gray-400 font-medium">Uptime</div>
          </div>
          <div>
            <div class="text-2xl sm:text-3xl md:text-4xl font-bold text-orange-500">24/7</div>
            <div class="text-xs sm:text-sm md:text-base text-gray-400 font-medium">Support</div>
          </div>
        </div>
      </div>
    </div>

    <!-- FEATURES SECTION -->
    <section id="features" class="py-12 sm:py-16 md:py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-10 sm:mb-12 md:mb-16">
          <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4 sm:mb-6 px-2" style="font-family: 'Playfair Display', serif;">
            Everything Your Restaurant Needs
          </h2>
          <p class="text-base sm:text-lg md:text-xl text-gray-600 max-w-3xl mx-auto px-4">
            Comprehensive tools designed specifically for restaurant management, from small bistros to large dining establishments.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 md:gap-8">
          <div v-for="feature in features" :key="feature.title"
               class="bg-gray-50 p-6 sm:p-8 rounded-2xl hover:shadow-lg transition-all hover:bg-orange-50 group">
            <div class="text-3xl sm:text-4xl mb-3 sm:mb-4 group-hover:scale-110 transition-transform">{{ feature.icon }}</div>
            <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 sm:mb-3 group-hover:text-orange-600 transition-colors">
              {{ feature.title }}
            </h3>
            <p class="text-sm sm:text-base text-gray-600 leading-relaxed">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- GALLERY SECTION -->
    <section id="gallery" class="py-12 sm:py-16 md:py-20 bg-slate-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-10 sm:mb-12 md:mb-16">
          <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4 sm:mb-6 px-2" style="font-family: 'Playfair Display', serif;">
            See ServeWise in Action
          </h2>
          <p class="text-base sm:text-lg md:text-xl text-gray-600 max-w-2xl mx-auto px-4">
            Experience the power of modern restaurant management
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 md:gap-8">
          <!-- Dish Food Image -->
          <div class="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
            <img src="/dish food.jpg" alt="Delicious Food" class="w-full h-64 sm:h-72 md:h-80 object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
              <div class="p-6 text-white">
                <h3 class="text-xl font-bold mb-2">Quality Dishes</h3>
                <p class="text-sm text-gray-200">Manage your menu with precision</p>
              </div>
            </div>
          </div>

          <!-- Table Image -->
          <div class="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
            <img src="/Table.jpg" alt="Restaurant Table" class="w-full h-64 sm:h-72 md:h-80 object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
              <div class="p-6 text-white">
                <h3 class="text-xl font-bold mb-2">Table Management</h3>
                <p class="text-sm text-gray-200">Optimize seating and reservations</p>
              </div>
            </div>
          </div>

          <!-- Waiter Image -->
          <div class="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
            <img src="/Waiter.avif" alt="Professional Service" class="w-full h-64 sm:h-72 md:h-80 object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
              <div class="p-6 text-white">
                <h3 class="text-xl font-bold mb-2">Staff Efficiency</h3>
                <p class="text-sm text-gray-200">Streamline operations and service</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA SECTION -->
    <section id="pricing" class="py-12 sm:py-16 md:py-20 bg-gradient-to-r from-orange-500 to-red-500 text-white">
      <div class="max-w-4xl mx-auto text-center px-4 sm:px-6">
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-4 sm:mb-6 px-2" style="font-family: 'Playfair Display', serif;">
          Ready to Transform Your Restaurant?
        </h2>
        <p class="text-base sm:text-lg md:text-xl mb-6 sm:mb-8 opacity-90 max-w-2xl mx-auto px-2">
          Join hundreds of successful restaurants using ServeWise to streamline operations and boost profitability.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center items-center mb-6 sm:mb-8">
          <Link :href="route('register')"
                class="w-full sm:w-auto bg-white text-orange-500 px-6 sm:px-8 md:px-10 py-3 sm:py-4 rounded-full text-base sm:text-lg font-bold hover:bg-gray-100 transition-all transform hover:scale-105 shadow-lg">
            Start Your Free Trial
          </Link>
          <Dialog>
            <DialogTrigger asChild>
              <button class="w-full sm:w-auto border-2 border-white text-white px-6 sm:px-8 md:px-10 py-3 sm:py-4 rounded-full text-base sm:text-lg font-semibold hover:bg-white hover:text-orange-500 transition-all">
                View Pricing Plans
              </button>
            </DialogTrigger>
            <DialogContent class="!max-w-6xl sm:!max-w-6xl max-h-[85vh] overflow-y-auto bg-white/95 backdrop-blur-sm w-[95vw]">
              <DialogHeader>
                <DialogTitle class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 text-center">Subsciption Plan</DialogTitle>
                <DialogDescription class="text-center text-gray-600 mt-2 text-sm sm:text-base">
                  Select the perfect plan for your restaurant's needs
                </DialogDescription>
              </DialogHeader>
              <div class="mt-4 sm:mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <div v-for="plan in pricingPlans" :key="plan.name"
                     :class="[
                       'relative bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 md:p-8 border-2 transition-all hover:shadow-xl',
                       plan.popular ? 'border-orange-500 shadow-lg' : 'border-gray-200'
                     ]">
                  <!-- Popular Badge -->
                  <div v-if="plan.popular" class="absolute -top-3 sm:-top-4 left-1/2 -translate-x-1/2">
                    <span class="bg-orange-500 text-white px-3 sm:px-4 py-0.5 sm:py-1 rounded-full text-xs sm:text-sm font-semibold">
                      Most Popular
                    </span>
                  </div>

                  <!-- Plan Header -->
                  <div class="text-center mb-4 sm:mb-6">
                    <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900 mb-1 sm:mb-2">{{ plan.name }}</h3>
                    <p class="text-gray-600 text-xs sm:text-sm mb-3 sm:mb-4">{{ plan.description }}</p>
                    <div class="mb-3 sm:mb-4">
                      <span class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900">{{ plan.price }}</span>
                      <span class="text-gray-600 ml-1 sm:ml-2 text-xs sm:text-sm">{{ plan.period }}</span>
                    </div>
                  </div>

                  <!-- Features List -->
                  <ul class="space-y-2 sm:space-y-3 mb-6 sm:mb-8">
                    <li v-for="feature in plan.features" :key="feature" class="flex items-start">
                      <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-500 mr-2 sm:mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                      </svg>
                      <span class="text-xs sm:text-sm md:text-base text-gray-700">{{ feature }}</span>
                    </li>
                  </ul>

                  <!-- CTA Button -->
                  <Link :href="route('register')" class="block">
                    <button
                      :class="[
                        'w-full py-2 sm:py-3 px-4 sm:px-6 rounded-full font-semibold transition-all text-sm sm:text-base',
                        plan.popular
                          ? 'bg-orange-500 text-white hover:bg-orange-600'
                          : 'bg-gray-100 text-gray-900 hover:bg-gray-200'
                      ]">
                      Get Started
                    </button>
                  </Link>
                </div>
              </div>
              <div class="mt-4 sm:mt-6 text-center text-xs sm:text-sm text-gray-600 px-2">
                All plans include a 30-day free trial • No credit card required • Cancel anytime
              </div>
            </DialogContent>
          </Dialog>
        </div>

        <!-- Terms & Policies Link -->
        <div class="mb-4">
          <Dialog>
            <DialogTrigger asChild>
              <button class="text-white underline hover:text-gray-200 transition-colors text-sm sm:text-base font-medium">
                Terms & Policies
              </button>
            </DialogTrigger>
            <DialogContent class="max-w-3xl max-h-[80vh] overflow-y-auto bg-white/95 backdrop-blur-sm w-[95vw]">
              <DialogHeader>
                <DialogTitle class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900">Terms and Policies for Restaurant Management System</DialogTitle>
              </DialogHeader>
              <div class="mt-4 space-y-4 sm:space-y-6 text-gray-700 px-2 sm:px-0">
                <div>
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">1. System Usage</h3>
                  <p class="text-sm sm:text-base leading-relaxed">This restaurant management system is designed to help manage and streamline restaurant operations. Users must provide accurate information and ensure proper handling of all data entered into the system. Misuse of the platform, including fraudulent activities, will result in account suspension or termination.</p>
                </div>

                <div>
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">2. Ordering and Customization</h3>
                  <p class="text-sm sm:text-base leading-relaxed">The system allows customers to place orders and request modifications or exclusions of ingredients. All customization requests must be clearly communicated at the time of ordering. The restaurant reserves the right to refuse modifications that compromise food safety or operational efficiency.</p>
                </div>

                <div>
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">3. Inventory and Ingredient Management</h3>
                  <p class="text-sm sm:text-base leading-relaxed">The restaurant commits to maintaining accurate records of inventory and ingredient availability. However, availability is subject to change, and the restaurant is not liable for out-of-stock items or substitutions made in good faith.</p>
                </div>

                <div>
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">4. Sales and Payment</h3>
                  <p class="text-sm sm:text-base leading-relaxed">All transactions processed through this system are subject to applicable taxes and service charges. Payments must be made using the accepted methods listed in the system. The restaurant is not responsible for payment failures due to third-party payment processor errors.</p>
                </div>

                <div>
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">5. Data Privacy</h3>
                  <p class="text-sm sm:text-base leading-relaxed">User data, including personal information, order history, and payment details, will be stored securely and used solely for operational purposes. The restaurant will not share customer data with third parties without explicit consent, except as required by law.</p>
                </div>

                <div>
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">6. Accountability and Security</h3>
                  <p class="text-sm sm:text-base leading-relaxed">Users are responsible for maintaining the confidentiality of their account credentials. Any unauthorized use of accounts must be reported immediately. The restaurant will implement reasonable security measures but is not liable for breaches caused by user negligence.</p>
                </div>

                <div>
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">7. System Maintenance</h3>
                  <p class="text-sm sm:text-base leading-relaxed">The system may undergo scheduled maintenance or experience unplanned downtime. The restaurant will make reasonable efforts to notify users in advance of maintenance periods but is not liable for any loss or inconvenience caused by system unavailability.</p>
                </div>

                <div>
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">8. Liability</h3>
                  <p class="text-sm sm:text-base leading-relaxed">The restaurant is committed to providing quality service but cannot guarantee uninterrupted or error-free operation of the system. Liability for errors, delays, or issues arising from the use of this system is limited to the extent permitted by law. Users agree to use the system at their own risk.</p>
                </div>

                <div class="pt-4 border-t border-gray-200">
                  <p class="text-xs sm:text-sm text-gray-600">By using this restaurant management system, you acknowledge that you have read, understood, and agree to these Terms and Policies. The restaurant reserves the right to update these terms at any time without prior notice.</p>
                </div>
              </div>
            </DialogContent>
          </Dialog>
        </div>

        <div class="text-xs sm:text-sm opacity-75 px-2">
          No credit card required • 30-day free trial • Cancel anytime
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer id="contact" class="bg-slate-900 text-white py-8 sm:py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8">
          <div class="col-span-1 sm:col-span-2">
            <div class="flex items-center gap-2 sm:gap-3 mb-3 sm:mb-4">
              <AppLogoIcon class="w-8 h-8 sm:w-10 sm:h-10" />
              <div class="text-2xl sm:text-3xl font-bold text-orange-500" style="font-family: 'Kaushan Script', cursive;">
                ServeWise
              </div>
            </div>
            <p class="text-gray-400 text-sm sm:text-base mb-4 max-w-md">
              The complete restaurant management platform trusted by restaurants worldwide.
            </p>
          </div>

          <div>
            <h4 class="font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Product</h4>
            <ul class="space-y-2 text-gray-400 text-sm sm:text-base">
              <li><a href="#features" class="hover:text-white transition">Features</a></li>
              <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
              <li><a href="#" class="hover:text-white transition">Security</a></li>
            </ul>
          </div>

          <div>
            <h4 class="font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Contact Us</h4>
            <ul class="space-y-2 text-gray-400 text-sm sm:text-base">
              <li>
                <a href="https://web.facebook.com/roger.perez.308346/" target="_blank" rel="noopener noreferrer" class="hover:text-white transition flex items-center gap-2">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                  </svg>
                  Facebook
                </a>
              </li>
              <li>
                <a href="mailto:rogerjrperez286@gmail.com" class="hover:text-white transition flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                  rogerjrperez286@gmail.com
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div class="border-t border-gray-800 mt-6 sm:mt-8 pt-6 sm:pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
          <p class="text-gray-400 text-xs sm:text-sm text-center md:text-left">
            &copy; {{ new Date().getFullYear() }} ServeWise. All rights reserved.
          </p>
          <div class="flex gap-4 sm:gap-6 text-xs sm:text-sm">
            <Link href="/privacy" target="_blank" class="text-gray-400 hover:text-white transition">Privacy Policy</Link>
            <Link href="/terms" target="_blank" class="text-gray-400 hover:text-white transition">Terms of Service</Link>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style>
html {
  scroll-behavior: smooth;
}
</style>
