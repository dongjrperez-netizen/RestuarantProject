<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue';
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue';
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue';
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue';
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue';
import AlertDialogFooter from '@/components/ui/alert-dialog/AlertDialogFooter.vue';
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue';
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue';
import AlertDialogTrigger from '@/components/ui/alert-dialog/AlertDialogTrigger.vue';

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
  created_at: string;
  updated_at: string;
  full_name: string;
}

interface Props {
  employee: Employee;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employees',
        href: '/employees',
    },
    {
        title: props.employee.full_name,
        href: `/employees/${props.employee.employee_id}`,
    },
];

const deleteEmployee = () => {
  router.delete(route('employees.destroy', props.employee.employee_id));
};

const toggleStatus = () => {
  router.post(route('employees.toggle-status', props.employee.employee_id), {}, {
    preserveState: true,
  });
};

const getStatusBadgeVariant = (status: string) => {
  return status === 'active' ? 'default' : 'secondary';
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const calculateAge = (birthDate: string) => {
  const today = new Date();
  const birth = new Date(birthDate);
  let age = today.getFullYear() - birth.getFullYear();
  const monthDiff = today.getMonth() - birth.getMonth();
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--;
  }
  
  return age;
};
</script>

<template>
    <Head :title="employee.full_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-start mb-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ employee.full_name }}</h1>
                        <Badge :variant="getStatusBadgeVariant(employee.status)">
                            {{ employee.status }}
                        </Badge>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300">{{ employee.role.role_name }}</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('employees.edit', employee.employee_id)">
                        <Button variant="outline">Edit</Button>
                    </Link>
                    <Button 
                        @click="toggleStatus"
                        :variant="employee.status === 'active' ? 'secondary' : 'default'"
                    >
                        {{ employee.status === 'active' ? 'Deactivate' : 'Activate' }}
                    </Button>
                    <Link :href="route('employees.index')">
                        <Button variant="outline">Back to Employees</Button>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Personal Information -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Personal Information</CardTitle>
                        <CardDescription>Employee's personal details</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Full Name</h4>
                                <p class="text-gray-600 dark:text-gray-300">{{ employee.full_name }}</p>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Email</h4>
                                <p class="text-gray-600 dark:text-gray-300">{{ employee.email }}</p>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Date of Birth</h4>
                                <p class="text-gray-600 dark:text-gray-300">
                                    {{ formatDate(employee.date_of_birth) }}
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                        ({{ calculateAge(employee.date_of_birth) }} years old)
                                    </span>
                                </p>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Gender</h4>
                                <p class="text-gray-600 dark:text-gray-300 capitalize">{{ employee.gender }}</p>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Role</h4>
                                <p class="text-gray-600 dark:text-gray-300">{{ employee.role.role_name }}</p>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Status</h4>
                                <Badge :variant="getStatusBadgeVariant(employee.status)">
                                    {{ employee.status }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Account Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Account Information</CardTitle>
                        <CardDescription>System and account details</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Employee ID</h4>
                            <p class="text-gray-600 dark:text-gray-300">#{{ employee.employee_id }}</p>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Date Joined</h4>
                            <p class="text-gray-600 dark:text-gray-300">{{ formatDateTime(employee.created_at) }}</p>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Last Updated</h4>
                            <p class="text-gray-600 dark:text-gray-300">{{ formatDateTime(employee.updated_at) }}</p>
                        </div>

                        <!-- Quick Actions -->
                        <div class="pt-4 space-y-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Quick Actions</h4>
                            
                            <Link :href="route('employees.edit', employee.employee_id)" class="block">
                                <Button variant="outline" class="w-full justify-start">
                                    Edit Information
                                </Button>
                            </Link>
                            
                            <Button 
                                @click="toggleStatus"
                                :variant="employee.status === 'active' ? 'secondary' : 'default'"
                                class="w-full justify-start"
                            >
                                {{ employee.status === 'active' ? 'Deactivate Employee' : 'Activate Employee' }}
                            </Button>
                            
                            <AlertDialog>
                                <AlertDialogTrigger asChild>
                                    <Button variant="destructive" class="w-full justify-start">
                                        Delete Employee
                                    </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            This action cannot be undone. This will permanently delete 
                                            <strong>{{ employee.full_name }}</strong> from your employee records.
                                            All associated data will be removed from the system.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                        <AlertDialogAction @click="deleteEmployee">
                                            Delete Employee
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>