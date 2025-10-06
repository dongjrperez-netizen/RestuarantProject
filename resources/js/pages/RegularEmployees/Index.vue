<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Badge from '@/components/ui/badge/Badge.vue';
import Select from '@/components/ui/select/Select.vue';
import SelectContent from '@/components/ui/select/SelectContent.vue';
import SelectItem from '@/components/ui/select/SelectItem.vue';
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue';
import SelectValue from '@/components/ui/select/SelectValue.vue';

import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue';
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue';
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue';
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue';
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue';
import AlertDialogFooter from '@/components/ui/alert-dialog/AlertDialogFooter.vue';
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue';
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue';
import AlertDialogTrigger from '@/components/ui/alert-dialog/AlertDialogTrigger.vue';
import { Eye, Edit, UserCheck, UserX, Trash2 } from 'lucide-vue-next';

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

interface EmployeePagination {
  data: RegularEmployee[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

interface Props {
  employees: EmployeePagination;
  filters: {
    search?: string;
    status?: string;
  };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'all');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Regular Employees',
        href: '/regular-employees',
    },
];

const search = () => {
  router.get('/regular-employees', {
    search: searchQuery.value || undefined,
    status: (selectedStatus.value && selectedStatus.value !== 'all') ? selectedStatus.value : undefined,
  }, {
    preserveState: true,
    replace: true,
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  selectedStatus.value = 'all';
  router.get('/regular-employees', {}, {
    preserveState: true,
    replace: true,
  });
};

const deleteEmployee = (employeeId: number) => {
  router.delete(`/regular-employees/${employeeId}`, {
    onSuccess: () => {
      // Handle success
    }
  });
};

const toggleStatus = (employeeId: number) => {
  router.post(`/regular-employees/${employeeId}/toggle-status`, {}, {
    preserveState: true,
  });
};

const getStatusBadgeVariant = (status: string) => {
  return status === 'active' ? 'default' : 'secondary';
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <Head title="Regular Employees" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Regular Employees</h1>
                    <p class="text-muted-foreground">Manage your restaurant staff (non-account employees)</p>
                </div>
                <Link :href="route('regular-employees.create')">
                    <Button>Add Employee</Button>
                </Link>
            </div>

            <!-- Employee Table -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between gap-4">
                        <CardTitle>Employees List</CardTitle>
                        <div class="flex items-center gap-2">
                            <Input
                                v-model="searchQuery"
                                placeholder="Search employees..."
                                @keyup.enter="search"
                                class="w-48"
                            />
                            <Select v-model="selectedStatus">
                                <SelectTrigger class="w-40">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button @click="search" variant="outline" size="sm">Search</Button>
                            <Button @click="clearFilters" variant="ghost" size="sm">Clear</Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Age</TableHead>
                                <TableHead>Date of Birth</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Date Added</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="employees.data.length === 0">
                                <TableCell colspan="7" class="text-center py-8">
                                    <div class="text-muted-foreground">
                                        <div class="text-lg mb-2">No employees found</div>
                                        <div class="text-sm">
                                            Get started by
                                            <Link :href="route('regular-employees.create')" class="text-primary hover:underline">
                                                adding your first employee
                                            </Link>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="employee in employees.data" :key="employee.regular_employee_id">
                                <TableCell class="font-medium">
                                    <div>
                                        <div class="font-semibold">{{ employee.full_name }}</div>
                                        <div class="text-sm text-muted-foreground" v-if="employee.address">
                                            {{ employee.address.substring(0, 30) }}{{ employee.address.length > 30 ? '...' : '' }}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>{{ employee.email || 'N/A' }}</TableCell>
                                <TableCell>{{ employee.age || 'N/A' }}</TableCell>
                                <TableCell>{{ formatDate(employee.date_of_birth) }}</TableCell>
                                <TableCell>
                                    <Badge :variant="getStatusBadgeVariant(employee.status)">
                                        {{ employee.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ formatDate(employee.created_at) }}</TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end items-center gap-1">
                                        <Link :href="route('regular-employees.show', employee.regular_employee_id)">
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0" title="View Details">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link :href="route('regular-employees.edit', employee.regular_employee_id)">
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0" title="Edit Employee">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0"
                                            @click="toggleStatus(employee.regular_employee_id)"
                                            :title="employee.status === 'active' ? 'Deactivate Employee' : 'Activate Employee'"
                                            :class="employee.status === 'active' ? 'text-red-600 hover:text-red-700 hover:bg-red-50' : 'text-green-600 hover:text-green-700 hover:bg-green-50'"
                                        >
                                            <UserX v-if="employee.status === 'active'" class="h-4 w-4" />
                                            <UserCheck v-else class="h-4 w-4" />
                                        </Button>
                                        <AlertDialog>
                                            <AlertDialogTrigger asChild>
                                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50" title="Delete Employee">
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        This action cannot be undone. This will permanently delete
                                                        {{ employee.full_name }} from your employee records.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                    <AlertDialogAction @click="deleteEmployee(employee.regular_employee_id)">
                                                        Delete
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div v-if="employees.last_page > 1" class="flex justify-between items-center pt-4 mt-4 border-t">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ employees.from }} to {{ employees.to }} of {{ employees.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Link
                                v-if="employees.current_page > 1"
                                :href="route('regular-employees.index', { ...filters, page: employees.current_page - 1 })"
                                preserve-scroll
                            >
                                <Button variant="outline" size="sm">Previous</Button>
                            </Link>
                            <Link
                                v-if="employees.current_page < employees.last_page"
                                :href="route('regular-employees.index', { ...filters, page: employees.current_page + 1 })"
                                preserve-scroll
                            >
                                <Button variant="outline" size="sm">Next</Button>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
