<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Receipt, Printer, CreditCard, Search, Filter } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';

// Props
defineProps<{
    employee?: any;
    orders?: any;
}>();

// Search and filter
const searchTerm = ref('');
const statusFilter = ref('all');

// Payment handling
// Define a type for the order object
type Order = {
    order_id: number;
    order_number: string;
    table?: { table_name?: string };
    customer_name?: string;
    order_items?: any[];
    total_amount: number;
    discount_amount?: number;
    discount_reason?: string;
    status: string;
    updated_at: string;
    // add other fields as needed
};

// Redirect to ViewBills for payment processing
const redirectToPayment = (order: any) => {
    router.visit(`/cashier/bills/${order.order_id}`);
};


// Status color mapping
const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
        case 'pending': return 'outline';
        case 'ready': return 'default';
        case 'completed': return 'secondary';
        case 'paid': return 'success';
        default: return 'secondary';
    }
};

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
};

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};


// Generate print bill URL (no discount data needed since discount is handled in ViewBill)
const getPrintBillUrl = (order: any) => {
    return `/cashier/bills/${order.order_id}/print`;
};

// Auto-refresh functionality
let refreshInterval: ReturnType<typeof setInterval> | null = null;
const REFRESH_INTERVAL = 10000; // Refresh every 10 seconds

const startAutoRefresh = () => {
    // Clear any existing interval
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }

    // Set up new interval to reload orders
    refreshInterval = setInterval(() => {
        router.reload({
            only: ['orders'],
            preserveScroll: true,
            preserveState: true,
        });
    }, REFRESH_INTERVAL);
};

const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

// Start auto-refresh when component mounts
onMounted(() => {
    startAutoRefresh();
});

// Clean up interval when component unmounts
onUnmounted(() => {
    stopAutoRefresh();
});

</script>

<template>
    <Head title="Customer Bills" />

    <CashierLayout title="Customer Bills">
        <div class="p-6 space-y-6">
            <!-- Orders Table -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Receipt class="w-5 h-5" />
                                Customer Orders
                            </CardTitle>
                            <CardDescription>
                                Manage bills for customer orders
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- Search Bar -->
                            <div class="relative w-80">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4" />
                                <Input
                                    v-model="searchTerm"
                                    placeholder="Search by order number, customer name, or table..."
                                    class="pl-10"
                                />
                            </div>
                            <!-- Status Filter -->
                            <select
                                v-model="statusFilter"
                                class="px-3 py-2 border border-border rounded-md bg-background"
                            >
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="ready">Ready</option>
                                <option value="completed">Completed</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Order #</TableHead>
                                    <TableHead>Table</TableHead>
                                    <TableHead>Customer</TableHead>
                                    <TableHead>Items</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="order in orders?.data" :key="order.order_id">
                                    <TableCell class="font-medium">
                                        {{ order.order_number }}
                                    </TableCell>
                                    <TableCell>
                                        {{ order.table?.table_name || 'N/A' }}
                                    </TableCell>
                                    <TableCell>
                                        {{ order.customer_name || 'Walk-in' }}
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-sm text-muted-foreground">
                                            {{ order.order_items?.length || 0 }} items
                                        </span>
                                    </TableCell>
                                    <TableCell class="font-medium">
                                        <div class="flex flex-col">
                                            <span :class="order.discount_amount ? 'line-through text-muted-foreground' : ''">
                                                {{ formatCurrency(order.total_amount) }}
                                            </span>
                                            <span v-if="order.discount_amount" class="text-sm font-bold text-green-600">
                                                {{ formatCurrency(order.total_amount - order.discount_amount) }}
                                            </span>
                                            <span v-if="order.discount_amount" class="text-xs text-red-600">
                                                ({{ order.discount_reason }} -{{ formatCurrency(order.discount_amount) }})
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusColor(order.status)">
                                            {{ order.status?.toUpperCase() }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDate(order.updated_at) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <!-- Print Bill Button -->
                                            <Button variant="outline" size="sm" asChild>
                                                <Link :href="getPrintBillUrl(order)" target="_blank">
                                                    <Printer class="w-4 h-4 mr-1" />
                                                    Print
                                                </Link>
                                            </Button>

                                            <!-- Process Payment Button (only for ready/completed orders) -->
                                            <Button
                                                v-if="order.status !== 'paid' && order.status !== 'pending'"
                                                size="sm"
                                                @click="redirectToPayment(order)"
                                            >
                                                <CreditCard class="w-4 h-4 mr-1" />
                                                Pay
                                            </Button>

                                            <!-- Status indicator for pending orders -->
                                            <span v-if="order.status === 'pending'" class="text-xs text-muted-foreground">
                                                Order in kitchen
                                            </span>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <!-- Empty State -->
                                <TableRow v-if="!orders?.data?.length">
                                    <TableCell colspan="8" class="text-center py-8 text-muted-foreground">
                                        <Receipt class="w-12 h-12 mx-auto mb-4 opacity-50" />
                                        <p class="text-lg font-medium">No orders found</p>
                                        <p class="text-sm">Customer orders will appear here when they are ready for billing.</p>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="orders?.links" class="flex items-center justify-between mt-4">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ orders.from || 0 }} to {{ orders.to || 0 }} of {{ orders.total || 0 }} orders
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                v-for="link in orders.links"
                                :key="link.label"
                                variant="outline"
                                size="sm"
                                :disabled="!link.url"
                                asChild
                            >
                                <Link v-if="link.url" :href="link.url" v-html="link.label"></Link>
                                <span v-else v-html="link.label"></span>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>

    </CashierLayout>
</template>