<template>
    <div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-100 py-8 px-4">
        <div class="max-w-2xl mx-auto">
            <!-- Success Header -->
            <div class="text-center mb-8">
                <div class="bg-green-100 border border-green-300 rounded-lg p-6 mb-6">
                    <h1 class="text-3xl font-bold text-green-800 mb-2">
                        🎉 Employee Login Redirect Success!
                    </h1>
                    <p class="text-green-700">
                        You have successfully logged in as an employee and been redirected to the mobile view.
                    </p>
                </div>
            </div>

            <!-- Employee Info -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Employee Information
                </h2>

                <div v-if="$page.props.auth?.user" class="space-y-3">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-600">Name:</span>
                        <span class="text-gray-800">{{ $page.props.auth.user.firstname }} {{ $page.props.auth.user.lastname }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-600">Email:</span>
                        <span class="text-gray-800">{{ $page.props.auth.user.email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-600">Role:</span>
                        <span class="text-gray-800">{{ $page.props.auth.user.role_id?.label || 'Employee' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-600">Status:</span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            {{ $page.props.auth.user.status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Menu Plan Info -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Current Menu Plan
                </h2>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-600">Menu Plan ID:</span>
                        <span class="text-gray-800">{{ $page.props.menuPlan?.menu_plan_id || 'Not specified' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-600">Date:</span>
                        <span class="text-gray-800">{{ $page.props.date || new Date().toLocaleDateString() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-600">URL Parameters:</span>
                        <span class="text-gray-800 text-sm font-mono bg-gray-100 px-2 py-1 rounded">
                            {{ $page.url }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Redirect Test Results -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Redirect Test Results
                </h2>

                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                        <span class="text-green-700 font-medium">Employee authentication successful</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                        <span class="text-green-700 font-medium">User type detection working</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                        <span class="text-green-700 font-medium">Role-based redirect functioning</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                        <span class="text-green-700 font-medium">Employee guard routes accessible</span>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Navigation
                </h2>

                <div class="grid md:grid-cols-3 gap-4">
                    <a href="/debug/employee-test" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg text-center transition-colors">
                        🧪 Test Dashboard
                    </a>

                    <a href="/debug/login-logs" target="_blank" class="bg-purple-500 hover:bg-purple-600 text-white font-medium py-3 px-4 rounded-lg text-center transition-colors">
                        📋 Debug Logs
                    </a>

                    <button @click="logout" class="bg-red-500 hover:bg-red-600 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                        🚪 Logout
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'

// Props from the backend
defineProps<{
    menuPlan?: any;
    date?: string;
}>()

// Logout function
const logout = () => {
    router.post('/employee/logout')
}
</script>