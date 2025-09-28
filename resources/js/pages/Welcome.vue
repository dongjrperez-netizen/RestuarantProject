<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

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

const testimonials = [
  {
    name: 'Maria Rodriguez',
    restaurant: 'La Cocina Moderna',
    text: 'ServeWise transformed how we manage our restaurant. Inventory tracking is a game-changer!',
    rating: 5
  },
  {
    name: 'James Chen',
    restaurant: 'Fusion Bistro',
    text: 'The order management system reduced our kitchen chaos by 80%. Highly recommended!',
    rating: 5
  },
  {
    name: 'Sarah Johnson',
    restaurant: 'Garden Grill',
    text: 'Staff scheduling and management became so much easier. Our team loves the platform.',
    rating: 5
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

  <div class="min-h-screen bg-white">
    <!-- HERO SECTION -->
    <div class="relative min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
      <!-- Background Image -->
      <div class="absolute inset-0 bg-[url('/RestoBG.jpg')] bg-cover bg-center bg-no-repeat"></div>
      <!-- Dark Overlay -->
      <div class="absolute inset-0 bg-black/60"></div>

      <!-- Navigation -->
      <nav class="relative z-20 px-6 py-6">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
          <div class="flex items-center gap-3">
            <AppLogoIcon class="w-12 h-12" />
            <div class="text-4xl font-bold text-orange-500" style="font-family: 'Kaushan Script', cursive;">
              ServeWise
            </div>
          </div>
          
          <div class="hidden md:flex space-x-8 text-white/80">
            <a href="#features" class="hover:text-white transition-colors">Features</a>
            <a href="#testimonials" class="hover:text-white transition-colors">Reviews</a>
            <a href="#pricing" class="hover:text-white transition-colors">Pricing</a>
          </div>

          <div class="flex gap-4 items-center">
            <Link v-if="$page.props.auth.user" 
                  :href="route('dashboard')"
                  class="px-6 py-2 bg-orange-500 text-white rounded-full hover:bg-orange-600 transition font-semibold">
              Dashboard
            </Link>
            <template v-else>
              <Link :href="route('login')"
                    class="px-6 py-2 text-white hover:text-orange-300 transition font-medium">
                Sign In
              </Link>
              <Link :href="route('register')"
                    class="px-6 py-2 bg-orange-500 text-white rounded-full hover:bg-orange-600 transition font-semibold">
                Get Started
              </Link>
            </template>
          </div>
        </div>
      </nav>

      <!-- Hero Content -->
      <div class="relative z-10 flex flex-col items-center justify-center min-h-[80vh] text-center px-6">
        <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 leading-tight" style="font-family: 'Playfair Display', serif;">
          Restaurant Management
          <span class="block text-orange-500">Made Simple</span>
        </h1>
        <p class="text-xl md:text-2xl text-gray-300 mb-8 max-w-3xl font-light leading-relaxed">
          From inventory tracking to order management, ServeWise provides everything you need to run a successful restaurant with confidence and style.
        </p>
        <div class="flex flex-col sm:flex-row gap-6 mt-8">
          <Link :href="route('register')" 
                class="bg-orange-500 text-white px-10 py-4 rounded-full text-lg font-semibold hover:bg-orange-600 transition-all transform hover:scale-105 shadow-lg">
            Start Free Trial
          </Link>
          <a href="#features"
             class="border-2 border-white/30 text-white px-10 py-4 rounded-full text-lg font-semibold hover:bg-white hover:text-slate-900 transition-all">
            Explore Features
          </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-8 mt-16 text-center">
          <div>
            <div class="text-3xl md:text-4xl font-bold text-orange-500">500+</div>
            <div class="text-gray-400 font-medium">Restaurants</div>
          </div>
          <div>
            <div class="text-3xl md:text-4xl font-bold text-orange-500">99.9%</div>
            <div class="text-gray-400 font-medium">Uptime</div>
          </div>
          <div>
            <div class="text-3xl md:text-4xl font-bold text-orange-500">24/7</div>
            <div class="text-gray-400 font-medium">Support</div>
          </div>
        </div>
      </div>
    </div>

    <!-- FEATURES SECTION -->
    <section id="features" class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6" style="font-family: 'Playfair Display', serif;">
            Everything Your Restaurant Needs
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Comprehensive tools designed specifically for restaurant management, from small bistros to large dining establishments.
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div v-for="feature in features" :key="feature.title" 
               class="bg-gray-50 p-8 rounded-2xl hover:shadow-lg transition-all hover:bg-orange-50 group">
            <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">{{ feature.icon }}</div>
            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-orange-600 transition-colors">
              {{ feature.title }}
            </h3>
            <p class="text-gray-600 leading-relaxed">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- TESTIMONIALS SECTION -->
    <section id="testimonials" class="py-20 bg-slate-50">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6" style="font-family: 'Playfair Display', serif;">
            Loved by Restaurant Owners
          </h2>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Don't just take our word for it. Here's what real restaurant owners say about ServeWise.
          </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <div v-for="testimonial in testimonials" :key="testimonial.name"
               class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
            <div class="flex mb-4">
              <span v-for="n in testimonial.rating" :key="n" class="text-orange-400 text-xl">★</span>
            </div>
            <p class="text-gray-700 mb-6 italic leading-relaxed">"{{ testimonial.text }}"</p>
            <div>
              <div class="font-semibold text-gray-900">{{ testimonial.name }}</div>
              <div class="text-orange-600 font-medium">{{ testimonial.restaurant }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA SECTION -->
    <section id="pricing" class="py-20 bg-gradient-to-r from-orange-500 to-red-500 text-white">
      <div class="max-w-4xl mx-auto text-center px-6">
        <h2 class="text-4xl md:text-5xl font-bold mb-6" style="font-family: 'Playfair Display', serif;">
          Ready to Transform Your Restaurant?
        </h2>
        <p class="text-xl mb-8 opacity-90 max-w-2xl mx-auto">
          Join hundreds of successful restaurants using ServeWise to streamline operations and boost profitability.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mb-12">
          <Link :href="route('register')" 
                class="bg-white text-orange-500 px-10 py-4 rounded-full text-lg font-bold hover:bg-gray-100 transition-all transform hover:scale-105 shadow-lg">
            Start Your Free Trial
          </Link>
          <Link :href="route('login')"
                class="border-2 border-white text-white px-10 py-4 rounded-full text-lg font-semibold hover:bg-white hover:text-orange-500 transition-all">
            Sign In
          </Link>
        </div>

        <div class="text-sm opacity-75">
          No credit card required • 30-day free trial • Cancel anytime
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-white py-12">
      <div class="max-w-7xl mx-auto px-6">
        <div class="grid md:grid-cols-4 gap-8">
          <div class="col-span-2">
            <div class="flex items-center gap-3 mb-4">
              <AppLogoIcon class="w-10 h-10" />
              <div class="text-3xl font-bold text-orange-500" style="font-family: 'Kaushan Script', cursive;">
                ServeWise
              </div>
            </div>
            <p class="text-gray-400 mb-4 max-w-md">
              The complete restaurant management platform trusted by restaurants worldwide.
            </p>
          </div>
          
          <div>
            <h4 class="font-semibold mb-4">Product</h4>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#features" class="hover:text-white transition">Features</a></li>
              <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
              <li><a href="#" class="hover:text-white transition">Security</a></li>
            </ul>
          </div>
          
          <div>
            <h4 class="font-semibold mb-4">Support</h4>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#" class="hover:text-white transition">Help Center</a></li>
              <li><a href="#" class="hover:text-white transition">Contact Us</a></li>
              <li><a href="#" class="hover:text-white transition">Status</a></li>
            </ul>
          </div>
        </div>
        
        <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
          <p class="text-gray-400 text-sm">
            &copy; {{ new Date().getFullYear() }} ServeWise. All rights reserved.
          </p>
          <div class="flex gap-6 mt-4 md:mt-0">
            <a href="#" class="text-gray-400 hover:text-white transition">Privacy</a>
            <a href="#" class="text-gray-400 hover:text-white transition">Terms</a>
            <a href="#" class="text-gray-400 hover:text-white transition">Cookies</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
