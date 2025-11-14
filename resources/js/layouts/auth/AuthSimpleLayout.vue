<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft  } from "lucide-vue-next";

defineProps<{
    title?: string;
    description?: string;
}>();

const backgroundImageUrl = '/RestoBG.jpg';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Kaushan+Script&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet" />

        <!-- Background Image -->
        <div class="fixed inset-0 -z-20" :style="{ backgroundImage: `url(${backgroundImageUrl})`, backgroundSize: 'cover', backgroundPosition: 'center', backgroundRepeat: 'no-repeat' }"></div>
        <!-- Dark Overlay -->
        <div class="fixed inset-0 bg-black/60 -z-10"></div>

        <!-- Header with back button and logo -->
        <header class="relative z-10 px-6 py-6">
            <div class="max-w-4xl mx-auto flex items-center justify-between">
                <Link :href="route('home')" class="inline-flex items-center text-white/70 hover:text-white transition-colors">
                    <ArrowLeft class="w-5 h-5 mr-2" />
                    <span class="text-sm font-medium">Back to Home</span>
                </Link>

                <Link :href="route('home')" class="text-3xl font-bold text-orange-500" style="font-family: 'Kaushan Script', cursive;">
                    ServeWise
                </Link>
            </div>
        </header>

        <!-- Main Content -->
        <div class="relative z-10 flex items-center justify-center min-h-[calc(100vh-120px)] p-6">
            <div class="w-full max-w-md">
                <!-- Header Text -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-white mb-3" style="font-family: 'Playfair Display', serif;">
                        {{ title }}
                    </h1>
                    <p class="text-gray-300 text-lg font-light">
                        {{ description }}
                    </p>
                </div>

                <!-- Form Container -->
                <div class="bg-white/95 backdrop-blur-sm p-8 rounded-2xl shadow-2xl border border-white/20">
                    <slot />
                </div>

                <!-- Footer Text -->
                <div class="text-center mt-6 text-gray-400 text-sm">
                    Manage your restaurant smarter with ServeWise
                </div>
            </div>
        </div>
    </div>
</template>
