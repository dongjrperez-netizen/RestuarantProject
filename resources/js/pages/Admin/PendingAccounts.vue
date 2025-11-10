<script setup lang="ts">
import AppLayoutAdministrator from '@/layouts/AppLayoutAdministrator.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Pending Account Requests',
    href: '/request',
  },
];


const props = defineProps({
  pendingApplications: Array as () => Array<{
    restaurant_id: number;
    user_id: number;
    restaurant_name: string;
    user_name: string;
    contact_email: string;
    status: string;
    documents: Array<{
      id: number;
      type: string;
      file_name: string;
      file_path: string;
      uploaded_at: string;
    }>;
  }>
});
</script>

<template>
  <Head title="Pending Account Requests" />

  <AppLayoutAdministrator :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Pending Account Requests</h1>
        <p class="text-gray-600 mt-1">Review and approve or reject new restaurant account applications</p>
      </div>

      <div class="overflow-x-auto bg-white shadow rounded-lg">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>User Name</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Restaurant</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Documents</TableHead>
              <TableHead class="text-center">Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow
              v-for="application in pendingApplications"
              :key="application.restaurant_id"
              class="hover:bg-gray-50"
            >
              <TableCell>{{ application.user_name }}</TableCell>
              <TableCell>{{ application.contact_email }}</TableCell>
              <TableCell>{{ application.restaurant_name }}</TableCell>
              <TableCell>
                <span 
                  :class="{
                    'px-2 py-1 text-xs font-semibold rounded-full': true,
                    'bg-yellow-100 text-yellow-800': application.status === 'Pending',
                    'bg-green-100 text-green-800': application.status === 'Approved',
                    'bg-red-100 text-red-800': application.status === 'Rejected'
                  }"
                >
                  {{ application.status }}
                </span>
              </TableCell>
              <TableCell>
                <ul class="list-disc list-inside space-y-1">
                  <li v-for="doc in application.documents" :key="doc.id">
                    <a
                      :href="doc.file_path"
                      target="_blank"
                      class="text-blue-600 hover:underline"
                      rel="noopener noreferrer"
                    >
                      {{ doc.file_name }} ({{ doc.type }})
                    </a>
                  </li>
                </ul>
              </TableCell>
              <TableCell class="text-center space-x-2">
                <Link
                  v-if="application.status === 'Pending'"
                  as="button"
                  method="post"
                  :href="route('admin.accounts.approve', application.restaurant_id)"
                  class="px-3 py-1 text-xs font-medium text-white bg-green-600 rounded-md hover:bg-green-700"
                >
                  Approve
                </Link>
                <Link
                  v-if="application.status === 'Pending'"
                  as="button"
                  method="post"
                  :href="route('admin.accounts.reject', application.restaurant_id)"
                  class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded-md hover:bg-red-700"
                >
                  Reject
                </Link>
                <span v-if="application.status !== 'Pending'" class="text-gray-500 text-xs">
                  No actions available
                </span>
              </TableCell>
            </TableRow>
            <TableRow v-if="!pendingApplications || pendingApplications.length === 0">
              <TableCell colspan="6" class="text-center py-4 text-gray-500">
                No pending applications found.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayoutAdministrator>
</template>