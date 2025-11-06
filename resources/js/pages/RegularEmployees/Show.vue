<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Edit, ArrowLeft } from 'lucide-vue-next';

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
  created_at: string;
  updated_at: string;
  full_name: string;
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
        title: 'Employee Details',
        href: `/regular-employees/${props.employee.regular_employee_id}`,
    },
];

const getStatusBadgeVariant = (status: string) => {
  return status === 'active' ? 'default' : 'secondary';
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString();
};
</script>

<template>
    <Head :title="`Employee - ${employee.full_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Employee Details</h1>
                    <p class="text-muted-foreground">View employee information</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('regular-employees.index')">
                        <Button variant="outline">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to List
                        </Button>
                    </Link>
                    <Link :href="route('regular-employees.edit', employee.regular_employee_id)">
                        <Button>
                            <Edit class="h-4 w-4 mr-2" />
                            Edit Employee
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Employee Information Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>{{ employee.full_name }}</CardTitle>
                        <Badge :variant="getStatusBadgeVariant(employee.status)">
                            {{ employee.status }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Personal Information Section -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold border-b pb-2">Personal Information</h3>

                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">First Name</label>
                                    <p class="text-base">{{ employee.firstname }}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Last Name</label>
                                    <p class="text-base">{{ employee.lastname }}</p>
                                </div>

                                <div v-if="employee.middle_initial">
                                    <label class="text-sm font-medium text-muted-foreground">Middle Initial</label>
                                    <p class="text-base">{{ employee.middle_initial }}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Date of Birth</label>
                                    <p class="text-base">{{ formatDate(employee.date_of_birth) }}</p>
                                </div>

                                <div v-if="employee.age">
                                    <label class="text-sm font-medium text-muted-foreground">Age</label>
                                    <p class="text-base">{{ employee.age }} years old</p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Section -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold border-b pb-2">Contact Information</h3>

                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Email</label>
                                    <p class="text-base">{{ employee.email || 'Not provided' }}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Address</label>
                                    <p class="text-base">{{ employee.address || 'Not provided' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- System Information Section -->
                        <div class="space-y-4 md:col-span-2">
                            <h3 class="text-lg font-semibold border-b pb-2">System Information</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Status</label>
                                    <div class="mt-1">
                                        <Badge :variant="getStatusBadgeVariant(employee.status)">
                                            {{ employee.status }}
                                        </Badge>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Date Added</label>
                                    <p class="text-base">{{ formatDateTime(employee.created_at) }}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Last Updated</label>
                                    <p class="text-base">{{ formatDateTime(employee.updated_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
