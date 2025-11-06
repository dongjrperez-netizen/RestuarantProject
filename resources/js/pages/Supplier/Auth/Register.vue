<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Building2 } from 'lucide-vue-next';

interface Props {
  restaurant_id: number;
  supplier?: any;
}

const props = defineProps<Props>();

const form = useForm({
  restaurant_id: props.restaurant_id,
  supplier_id: props.supplier?.supplier_id || null,
  supplier_name: props.supplier?.supplier_name || '',
  email: props.supplier?.email || '',
  password: '',
  password_confirmation: '',
  contact_number: props.supplier?.contact_number || '',
  address: props.supplier?.address || '',
  business_registration: props.supplier?.business_registration || '',
  tax_id: props.supplier?.tax_id || '',
});

const submit = () => {
  form.post('/supplier/register');
};
</script>

<template>
  <Head title="Supplier Registration" />

  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full space-y-8">
      <div class="text-center">
        <Building2 class="mx-auto h-12 w-12 text-primary" />
        <h2 class="mt-6 text-3xl font-bold text-gray-900">
          {{ props.supplier ? 'Complete Supplier Setup' : 'Supplier Registration' }}
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          {{ props.supplier ? 'Complete your account setup with login credentials' : 'Create your supplier account' }}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>
            {{ props.supplier ? 'Complete Account Setup' : 'Register as Supplier' }}
          </CardTitle>
          <CardDescription>
            {{ props.supplier ? 'Set your login credentials and verify your business details' : 'Fill out the form below to create your supplier account' }}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid gap-6 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="supplier_name">Business Name *</Label>
                <Input
                  id="supplier_name"
                  v-model="form.supplier_name"
                  required
                  placeholder="Your business name"
                />
                <div v-if="form.errors.supplier_name" class="text-sm text-red-600">
                  {{ form.errors.supplier_name }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="email">Email Address *</Label>
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  placeholder="business@email.com"
                />
                <div v-if="form.errors.email" class="text-sm text-red-600">
                  {{ form.errors.email }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="password">Password *</Label>
                <Input
                  id="password"
                  v-model="form.password"
                  type="password"
                  required
                  placeholder="Minimum 8 characters"
                />
                <div v-if="form.errors.password" class="text-sm text-red-600">
                  {{ form.errors.password }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="password_confirmation">Confirm Password *</Label>
                <Input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  type="password"
                  required
                  placeholder="Confirm your password"
                />
              </div>

              <div class="space-y-2">
                <Label for="contact_number">Contact Number</Label>
                <Input
                  id="contact_number"
                  v-model="form.contact_number"
                  placeholder="Your contact number"
                />
                <div v-if="form.errors.contact_number" class="text-sm text-red-600">
                  {{ form.errors.contact_number }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="business_registration">Business Registration</Label>
                <Input
                  id="business_registration"
                  v-model="form.business_registration"
                  placeholder="Business registration number"
                />
                <div v-if="form.errors.business_registration" class="text-sm text-red-600">
                  {{ form.errors.business_registration }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="tax_id">Tax ID</Label>
                <Input
                  id="tax_id"
                  v-model="form.tax_id"
                  placeholder="Tax identification number"
                />
                <div v-if="form.errors.tax_id" class="text-sm text-red-600">
                  {{ form.errors.tax_id }}
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="address">Address</Label>
              <Textarea
                id="address"
                v-model="form.address"
                placeholder="Your business address"
                rows="3"
              />
              <div v-if="form.errors.address" class="text-sm text-red-600">
                {{ form.errors.address }}
              </div>
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
              {{ form.processing 
                ? (props.supplier ? 'Completing Setup...' : 'Creating Account...') 
                : (props.supplier ? 'Complete Setup' : 'Create Account') 
              }}
            </Button>

            <div class="text-center">
              <p class="text-sm text-gray-600">
                Already have an account?
                <Link href="/login" class="font-medium text-primary hover:text-primary/80">
                  Sign in here
                </Link>
              </p>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </div>
</template>