<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { AlertCircle, Users, ArrowLeft } from 'lucide-vue-next';

interface Props {
    canResetPassword?: boolean;
    status?: string;
    ownerLoginUrl: string;
}

const props = defineProps<Props>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('employee.login.submit'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Employee Login" />

    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <Users class="mx-auto h-12 w-12 text-orange-600" />
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                    Employee Login
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Sign in to your employee account
                </p>
            </div>

            <!-- Login Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Welcome Back</CardTitle>
                    <CardDescription>
                        Enter your employee credentials to access the system
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Status Message -->
                    <div v-if="status" class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md">
                        <p class="text-sm text-green-600 dark:text-green-400">{{ status }}</p>
                    </div>

                    <!-- Login Form -->
                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Email -->
                        <div class="space-y-2">
                            <Label for="email">Email Address</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Enter your employee email"
                                :class="{ 'border-red-500': form.errors.email }"
                            />
                            <div v-if="form.errors.email" class="flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <AlertCircle class="w-4 h-4" />
                                {{ form.errors.email }}
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="password">Password</Label>
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('employee.password.request')"
                                    class="text-sm text-orange-600 hover:text-orange-500"
                                >
                                    Forgot password?
                                </Link>
                            </div>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                :class="{ 'border-red-500': form.errors.password }"
                            />
                            <div v-if="form.errors.password" class="flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <AlertCircle class="w-4 h-4" />
                                {{ form.errors.password }}
                            </div>
                        </div>

                        <!-- Remember me -->
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="remember"
                                v-model:checked="form.remember"
                                name="remember"
                            />
                            <Label for="remember" class="text-sm">Remember me</Label>
                        </div>

                        <!-- Submit button -->
                        <Button
                            type="submit"
                            class="w-full bg-orange-600 hover:bg-orange-700"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Signing in...</span>
                            <span v-else>Sign in as Employee</span>
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Navigation Links -->
            <div class="text-center space-y-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Restaurant owner or manager?
                    <Link :href="ownerLoginUrl" class="font-medium text-orange-600 hover:text-orange-500">
                        Sign in here
                    </Link>
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <Link :href="route('home')" class="font-medium text-orange-600 hover:text-orange-500 inline-flex items-center gap-1">
                        <ArrowLeft class="w-4 h-4" />
                        Back to home
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>