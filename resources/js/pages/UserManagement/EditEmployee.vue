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

interface Role {
  id: number;
  role_name: string;
}

interface Employee {
  employee_id: number;
  firstname: string;
  lastname: string;
  middlename?: string;
  email: string;
  date_of_birth: string;
  gender: string;
  status: 'active' | 'inactive';
  role: Role;
  role_id: number;
}

interface Props {
  employee: Employee;
  roles: Role[];
}

const props = defineProps<Props>();

const form = useForm({
  firstname: props.employee.firstname,
  lastname: props.employee.lastname,
  middlename: props.employee.middlename || '',
  email: props.employee.email,
  password: '',
  password_confirmation: '',
  date_of_birth: props.employee.date_of_birth,
  gender: props.employee.gender,
  role_id: props.employee.role_id.toString(),
  status: props.employee.status,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employees',
        href: '/employees',
    },
    {
        title: props.employee.firstname + ' ' + props.employee.lastname,
        href: `/employees/${props.employee.employee_id}`,
    },
    {
        title: 'Edit',
        href: `/employees/${props.employee.employee_id}/edit`,
    },
];

const submit = () => {
  form.put(route('employees.update', props.employee.employee_id));
};
</script>

<template>
    <Head :title="`Edit ${employee.firstname} ${employee.lastname}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Employee</h1>
                    <p class="text-gray-600 mt-1">Update {{ employee.firstname }} {{ employee.lastname }}'s information</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('employees.show', employee.employee_id)">
                        <Button variant="outline">View Details</Button>
                    </Link>
                    <Link :href="route('employees.index')">
                        <Button variant="outline">Back to Employees</Button>
                    </Link>
                </div>
            </div>

            <!-- Form -->
            <Card class="max-w-2xl mx-auto">
                <CardHeader>
                    <CardTitle>Employee Information</CardTitle>
                    <CardDescription>
                        Update the employee's details below
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

                            <div>
                                <Label for="status">Status *</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.status" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.status }}
                                </div>
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Account Information</h3>
                            
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
                                    <Label for="password">New Password (optional)</Label>
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        :class="{ 'border-red-500': form.errors.password }"
                                    />
                                    <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">
                                        {{ form.errors.password }}
                                    </div>
                                    <div class="text-gray-500 text-sm mt-1">
                                        Leave blank to keep current password
                                    </div>
                                </div>

                                <div>
                                    <Label for="password_confirmation">Confirm New Password</Label>
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
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
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Updating...' : 'Update Employee' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>