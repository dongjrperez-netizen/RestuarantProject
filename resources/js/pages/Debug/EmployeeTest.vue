<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    🧪 Employee Login Test Dashboard
                </h1>
                <p class="text-lg text-gray-600">
                    Test and verify employee authentication and redirect functionality
                </p>
            </div>

            <!-- Current Auth Status -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Current Authentication Status
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Web Guard Status -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-800 mb-2">Restaurant Owner (Web Guard)</h3>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <span class="w-3 h-3 rounded-full mr-2" :class="webAuthStatus.authenticated ? 'bg-green-500' : 'bg-red-500'"></span>
                                <span :class="webAuthStatus.authenticated ? 'text-green-700' : 'text-red-700'">
                                    {{ webAuthStatus.authenticated ? 'Authenticated' : 'Not Authenticated' }}
                                </span>
                            </div>
                            <div v-if="webAuthStatus.user" class="text-sm text-blue-700">
                                <p><strong>Email:</strong> {{ webAuthStatus.user.email }}</p>
                                <p><strong>Name:</strong> {{ webAuthStatus.user.first_name }} {{ webAuthStatus.user.last_name }}</p>
                                <p><strong>Role:</strong> Restaurant Owner</p>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Guard Status -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h3 class="font-semibold text-green-800 mb-2">Employee (Employee Guard)</h3>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <span class="w-3 h-3 rounded-full mr-2" :class="employeeAuthStatus.authenticated ? 'bg-green-500' : 'bg-red-500'"></span>
                                <span :class="employeeAuthStatus.authenticated ? 'text-green-700' : 'text-red-700'">
                                    {{ employeeAuthStatus.authenticated ? 'Authenticated' : 'Not Authenticated' }}
                                </span>
                            </div>
                            <div v-if="employeeAuthStatus.user" class="text-sm text-green-700">
                                <p><strong>Email:</strong> {{ employeeAuthStatus.user.email }}</p>
                                <p><strong>Name:</strong> {{ employeeAuthStatus.user.firstname }} {{ employeeAuthStatus.user.lastname }}</p>
                                <p><strong>Role:</strong> {{ employeeAuthStatus.user.role_id?.label || 'Unknown' }}</p>
                                <p><strong>Status:</strong> {{ employeeAuthStatus.user.status }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Credentials -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-3a1 1 0 011-1h2.586l6.414-6.414A6 6 0 0119 9z"></path>
                    </svg>
                    Test Credentials
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-800 mb-2">Restaurant Owner Login</h3>
                        <div class="space-y-1 text-sm text-blue-700">
                            <p><strong>Email:</strong> <code class="bg-blue-100 px-1 rounded">owner@test.com</code></p>
                            <p><strong>Password:</strong> <code class="bg-blue-100 px-1 rounded">password123</code></p>
                            <p><strong>Expected Redirect:</strong> Dashboard or Subscription page</p>
                        </div>
                    </div>

                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h3 class="font-semibold text-green-800 mb-2">Employee Login (Waiter)</h3>
                        <div class="space-y-1 text-sm text-green-700">
                            <p><strong>Email:</strong> <code class="bg-green-100 px-1 rounded">dongjrperez@gmail.com</code></p>
                            <p><strong>Password:</strong> <code class="bg-green-100 px-1 rounded">perez123</code></p>
                            <p><strong>Expected Redirect:</strong> Mobile Menu View or Menu Planning Index</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Quick Actions
                </h2>

                <div class="grid md:grid-cols-4 gap-4">
                    <a href="/login" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg text-center transition-colors">
                        🔑 Go to Login
                    </a>

                    <a href="/debug/login-logs" target="_blank" class="bg-purple-500 hover:bg-purple-600 text-white font-medium py-3 px-4 rounded-lg text-center transition-colors">
                        📋 View Debug Logs
                    </a>

                    <button @click="refreshStatus" class="bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                        🔄 Refresh Status
                    </button>

                    <button @click="logout" v-if="webAuthStatus.authenticated || employeeAuthStatus.authenticated" class="bg-red-500 hover:bg-red-600 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                        🚪 Logout
                    </button>
                </div>
            </div>

            <!-- Expected Redirect URLs -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 10m0 0V7m0 3v10"></path>
                    </svg>
                    Expected Redirect Behavior
                </h2>

                <div class="space-y-4">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="font-semibold text-yellow-800 mb-2">🎯 Employee (Waiter) Login Success</h3>
                        <div class="text-sm text-yellow-700 space-y-1">
                            <p><strong>Expected URL:</strong> <code class="bg-yellow-100 px-1 rounded">/menu-planning/8/mobile-view/2025-09-22</code></p>
                            <p><strong>Fallback URL:</strong> <code class="bg-yellow-100 px-1 rounded">/menu-planning</code> (employee accessible)</p>
                            <p><strong>Guard Used:</strong> employee</p>
                            <p><strong>Role:</strong> Waiter (ID: 3)</p>
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-800 mb-2">🏢 Restaurant Owner Login Success</h3>
                        <div class="text-sm text-blue-700 space-y-1">
                            <p><strong>Expected URL:</strong> <code class="bg-blue-100 px-1 rounded">/dashboard</code> or subscription page</p>
                            <p><strong>Guard Used:</strong> web</p>
                            <p><strong>Role:</strong> Restaurant Owner (ID: 1)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

// Reactive data
const webAuthStatus = ref({
    authenticated: false,
    user: null
})

const employeeAuthStatus = ref({
    authenticated: false,
    user: null
})

// Check authentication status
const checkAuthStatus = async () => {
    try {
        // Check web guard (restaurant owners)
        const webResponse = await fetch('/api/auth-status/web', {
            credentials: 'include'
        })
        if (webResponse.ok) {
            webAuthStatus.value = await webResponse.json()
        }

        // Check employee guard
        const employeeResponse = await fetch('/api/auth-status/employee', {
            credentials: 'include'
        })
        if (employeeResponse.ok) {
            employeeAuthStatus.value = await employeeResponse.json()
        }
    } catch (error) {
        console.log('Auth status check failed:', error)
    }
}

// Refresh status
const refreshStatus = () => {
    checkAuthStatus()
}

// Logout function
const logout = () => {
    if (employeeAuthStatus.value.authenticated) {
        router.post('/employee/logout')
    } else if (webAuthStatus.value.authenticated) {
        router.post('/logout')
    }
}

// Check status on component mount
onMounted(() => {
    checkAuthStatus()
})
</script>