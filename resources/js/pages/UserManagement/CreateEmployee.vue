<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import Alert from '@/components/ui/alert/Alert.vue';
import AlertDescription from '@/components/ui/alert/AlertDescription.vue';
import { AlertCircle } from 'lucide-vue-next';

interface Role {
  id: number;
  role_name: string;
}

interface SubscriptionLimits {
  allowed: boolean;
  message: string;
  current: number;
  limit: number;
}

interface Props {
  roles: Role[];
  subscriptionLimits: SubscriptionLimits;
}

const props = defineProps<Props>();

const form = useForm({
  firstname: '',
  lastname: '',
  middlename: '',
  email: '',
  password: '',
  password_confirmation: '',
  date_of_birth: '',
  gender: '',
  role_id: '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employees',
        href: '/employees',
    },
    {
        title: 'Create Employee',
        href: '/employees/create',
    },
];

const submit = () => {
  form.post(route('employees.store'));
};
</script>

<template>
    <Head title="Create Employee" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Create Employee</h1>
                    <p class="text-muted-foreground mt-1">Add a new employee to your restaurant</p>
                </div>
                <Link :href="route('employees.index')">
                    <Button variant="outline">Back to Employees</Button>
                </Link>
            </div>

            <!-- Subscription Limit Warning -->
            <Alert v-if="!subscriptionLimits.allowed" variant="destructive" class="mb-6 max-w-2xl mx-auto">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    <div class="font-semibold mb-1">Employee Limit Reached</div>
                    <p>{{ subscriptionLimits.message }}</p>
                    <p class="mt-2">
                        You currently have <strong>{{ subscriptionLimits.current }}</strong> employees
                        out of <strong>{{ subscriptionLimits.limit }}</strong> allowed.
                    </p>
                    <p class="mt-2">
                        <Link href="/user-management/subscriptions" class="underline font-medium">
                            Upgrade your subscription
                        </Link> to add more employees.
                    </p>
                </AlertDescription>
            </Alert>

            <!-- Form -->
            <Card class="max-w-2xl mx-auto" :class="{ 'opacity-60 pointer-events-none': !subscriptionLimits.allowed }">
                <CardHeader>
                    <CardTitle>Employee Information</CardTitle>
                    <CardDescription>
                        Fill in the details for the new employee
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Personal Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label for="firstname">First Name *</Label>
                                <Input
                                    id="firstname"
                                    v-model="form.firstname"
                                    type="text"
                                    required
                                    :class="{ 'border-red-500': form.errors.firstname }"
                                />
                                <div v-if="form.errors.firstname" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.firstname }}
                                </div>
                            </div>

                            <div>
                                <Label for="lastname">Last Name *</Label>
                                <Input
                                    id="lastname"
                                    v-model="form.lastname"
                                    type="text"
                                    required
                                    :class="{ 'border-red-500': form.errors.lastname }"
                                />
                                <div v-if="form.errors.lastname" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.lastname }}
                                </div>
                            </div>

                            <div>
                                <Label for="middlename">Middle Name</Label>
                                <Input
                                    id="middlename"
                                    v-model="form.middlename"
                                    type="text"
                                    :class="{ 'border-red-500': form.errors.middlename }"
                                />
                                <div v-if="form.errors.middlename" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.middlename }}
                                </div>
                            </div>

                            <div>
                                <Label for="date_of_birth">Date of Birth *</Label>
                                <Input
                                    id="date_of_birth"
                                    v-model="form.date_of_birth"
                                    type="date"
                                    required
                                    :class="{ 'border-red-500': form.errors.date_of_birth }"
                                />
                                <div v-if="form.errors.date_of_birth" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.date_of_birth }}
                                </div>
                            </div>

                            <div>
                                <Label for="gender">Gender *</Label>
                                <Select v-model="form.gender">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.gender }">
                                        <SelectValue placeholder="Select gender" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="male">Male</SelectItem>
                                        <SelectItem value="female">Female</SelectItem>
                                        <SelectItem value="other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.gender" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.gender }}
                                </div>
                            </div>

                            <div>
                                <Label for="role_id">Role *</Label>
                                <Select v-model="form.role_id">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.role_id }">
                                        <SelectValue placeholder="Select role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem 
                                            v-for="role in roles" 
                                            :key="role.id" 
                                            :value="role.id.toString()"
                                        >
                                            {{ role.role_name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.role_id" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.role_id }}
                                </div>
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-foreground">Account Information</h3>
                            
                            <div>
                                <Label for="email">Email *</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label for="password">Password *</Label>
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        required
                                        :class="{ 'border-red-500': form.errors.password }"
                                    />
                                    <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">
                                        {{ form.errors.password }}
                                    </div>
                                </div>

                                <div>
                                    <Label for="password_confirmation">Confirm Password *</Label>
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        required
                                        :class="{ 'border-red-500': form.errors.password_confirmation }"
                                    />
                                    <div v-if="form.errors.password_confirmation" class="text-red-500 text-sm mt-1">
                                        {{ form.errors.password_confirmation }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end gap-4 pt-4">
                            <Link :href="route('employees.index')">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button
                                type="submit"
                                :disabled="form.processing || !subscriptionLimits.allowed"
                            >
                                {{ form.processing ? 'Creating...' : 'Create Employee' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>