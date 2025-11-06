<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import Select from '@/components/ui/select/Select.vue';
import SelectContent from '@/components/ui/select/SelectContent.vue';
import SelectItem from '@/components/ui/select/SelectItem.vue';
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue';
import SelectValue from '@/components/ui/select/SelectValue.vue';

interface RegularEmployee {
  regular_employee_id: number;
  firstname: string;
  lastname: string;
  middle_initial?: string;
  age?: number;
  date_of_birth: string;
  email?: string;
  address?: string;
  status: 'active' | 'inactive';
}

interface Props {
  employee: RegularEmployee;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Regular Employees',
        href: '/regular-employees',
    },
    {
        title: 'Edit Employee',
        href: `/regular-employees/${props.employee.regular_employee_id}/edit`,
    },
];

const form = useForm({
    firstname: props.employee.firstname,
    lastname: props.employee.lastname,
    middle_initial: props.employee.middle_initial || '',
    age: props.employee.age || null as number | null,
    date_of_birth: props.employee.date_of_birth,
    email: props.employee.email || '',
    address: props.employee.address || '',
    status: props.employee.status,
});

const submit = () => {
    form.put(route('regular-employees.update', props.employee.regular_employee_id), {
        onSuccess: () => {
            // Handle success
        }
    });
};
</script>

<template>
    <Head title="Edit Regular Employee" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Edit Regular Employee</h1>
                    <p class="text-muted-foreground">Update employee information</p>
                </div>
                <Link :href="route('regular-employees.index')">
                    <Button variant="outline">Back to List</Button>
                </Link>
            </div>

            <!-- Form -->
            <Card>
                <CardHeader>
                    <CardTitle>Employee Information</CardTitle>
                    <CardDescription>Update the employee's information</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <Label for="firstname">First Name <span class="text-red-500">*</span></Label>
                                <Input
                                    id="firstname"
                                    v-model="form.firstname"
                                    type="text"
                                    placeholder="Juan"
                                    :class="{ 'border-red-500': form.errors.firstname }"
                                    required
                                />
                                <p v-if="form.errors.firstname" class="text-sm text-red-500">{{ form.errors.firstname }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="lastname">Last Name <span class="text-red-500">*</span></Label>
                                <Input
                                    id="lastname"
                                    v-model="form.lastname"
                                    type="text"
                                    placeholder="Dela Cruz"
                                    :class="{ 'border-red-500': form.errors.lastname }"
                                    required
                                />
                                <p v-if="form.errors.lastname" class="text-sm text-red-500">{{ form.errors.lastname }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="middle_initial">Middle Initial</Label>
                                <Input
                                    id="middle_initial"
                                    v-model="form.middle_initial"
                                    type="text"
                                    placeholder="M"
                                    maxlength="10"
                                    :class="{ 'border-red-500': form.errors.middle_initial }"
                                />
                                <p v-if="form.errors.middle_initial" class="text-sm text-red-500">{{ form.errors.middle_initial }}</p>
                            </div>
                        </div>

                        <!-- Date and Age Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="date_of_birth">Date of Birth <span class="text-red-500">*</span></Label>
                                <Input
                                    id="date_of_birth"
                                    v-model="form.date_of_birth"
                                    type="date"
                                    :class="{ 'border-red-500': form.errors.date_of_birth }"
                                    required
                                />
                                <p v-if="form.errors.date_of_birth" class="text-sm text-red-500">{{ form.errors.date_of_birth }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="age">Age</Label>
                                <Input
                                    id="age"
                                    v-model.number="form.age"
                                    type="number"
                                    placeholder="25"
                                    min="15"
                                    max="100"
                                    :class="{ 'border-red-500': form.errors.age }"
                                />
                                <p v-if="form.errors.age" class="text-sm text-red-500">{{ form.errors.age }}</p>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="juan@example.com"
                                :class="{ 'border-red-500': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                        </div>

                        <!-- Address -->
                        <div class="space-y-2">
                            <Label for="address">Address</Label>
                            <Textarea
                                id="address"
                                v-model="form.address"
                                placeholder="Enter full address..."
                                rows="3"
                                :class="{ 'border-red-500': form.errors.address }"
                            />
                            <p v-if="form.errors.address" class="text-sm text-red-500">{{ form.errors.address }}</p>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <Label for="status">Status <span class="text-red-500">*</span></Label>
                            <Select v-model="form.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.status" class="text-sm text-red-500">{{ form.errors.status }}</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-4 pt-4">
                            <Link :href="route('regular-employees.index')">
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
