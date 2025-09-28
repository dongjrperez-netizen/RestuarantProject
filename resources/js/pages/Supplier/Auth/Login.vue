<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Building2 } from 'lucide-vue-next';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/supplier/login', {
    onFinish: () => {
      form.reset('password');
    },
  });
};
</script>

<template>
  <Head title="Supplier Login" />

  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <Building2 class="mx-auto h-12 w-12 text-primary" />
        <h2 class="mt-6 text-3xl font-bold text-gray-900">Supplier Portal</h2>
        <p class="mt-2 text-sm text-gray-600">Sign in to your supplier account</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Sign in</CardTitle>
          <CardDescription>
            Enter your email and password to access your supplier dashboard
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-4">
              <div class="space-y-2">
                <Label for="email">Email Address</Label>
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  placeholder="your@email.com"
                />
                <div v-if="form.errors.email" class="text-sm text-red-600">
                  {{ form.errors.email }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="password">Password</Label>
                <Input
                  id="password"
                  v-model="form.password"
                  type="password"
                  required
                  placeholder="Enter your password"
                />
                <div v-if="form.errors.password" class="text-sm text-red-600">
                  {{ form.errors.password }}
                </div>
              </div>

              <div class="flex items-center space-x-2">
                <Checkbox id="remember" v-model:checked="form.remember" />
                <Label for="remember" class="text-sm font-normal">Remember me</Label>
              </div>
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
              {{ form.processing ? 'Signing in...' : 'Sign in' }}
            </Button>

            <div class="text-center">
              <p class="text-sm text-gray-600">
                Don't have an account?
                <Link href="/supplier/register" class="font-medium text-primary hover:text-primary/80">
                  Register here
                </Link>
              </p>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </div>
</template>