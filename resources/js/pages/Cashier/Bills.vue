<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Receipt, Eye, Printer, CreditCard, Search, Filter, Banknote, Smartphone, Wallet } from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Props
defineProps<{
    employee?: any;
    orders?: any;
}>();

// Search and filter
const searchTerm = ref('');
const statusFilter = ref('all');

// Payment modal state
const isPaymentModalOpen = ref(false);
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

const selectedOrder = ref<Order | null>(null);
const selectedPaymentMethod = ref('');


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

// Payment methods
const paymentMethods = [
    {
        id: 'cash',
        name: 'Cash',
        icon: Banknote,
        description: 'Pay with cash',
        color: 'text-green-600',
        bgColor: 'hover:bg-green-50'
    },
    {
        id: 'gcash',
        name: 'GCash',
        icon: Smartphone,
        description: 'Digital wallet payment',
        color: 'text-blue-600',
        bgColor: 'hover:bg-blue-50'
    },
    {
        id: 'paypal',
        name: 'PayPal',
        icon: Wallet,
        description: 'Online payment',
        color: 'text-indigo-600',
        bgColor: 'hover:bg-indigo-50'
    },
    {
        id: 'debit_card',
        name: 'Debit Card',
        icon: CreditCard,
        description: 'Card payment',
        color: 'text-purple-600',
        bgColor: 'hover:bg-purple-50'
    }
];

// Payment functions
const openPaymentModal = (order: any) => {
    selectedOrder.value = order;
    selectedPaymentMethod.value = '';
    isPaymentModalOpen.value = true;
};

const selectPaymentMethod = (methodId: string) => {
    selectedPaymentMethod.value = methodId;
};

const processPayment = async () => {
    if (!selectedPaymentMethod.value) {
        alert('Please select a payment method');
        return;
    }

    if (!selectedOrder.value) {
        alert('Order information not available');
        return;
    }

    const paymentData = {
        order_id: selectedOrder.value.order_id,
        amount: selectedOrder.value.total_amount - (selectedOrder.value.discount_amount || 0),
        customer_name: selectedOrder.value.customer_name || 'Walk-in Customer',
        customer_email: selectedOrder.value.customer_email || 'customer@restaurant.com',
        method: selectedPaymentMethod.value === 'debit_card' ? 'card' : selectedPaymentMethod.value,
    };

    console.log('Processing payment:', paymentData);

    // Handle different payment methods
    if (selectedPaymentMethod.value === 'gcash') {
        try {
            // Send request to create PayMongo checkout session
            const response = await fetch('/payment/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(paymentData),
            });

            const result = await response.json();

            if (response.ok && result.checkout_url) {
                // Redirect to PayMongo checkout page
                window.location.href = result.checkout_url;
            } else {
                console.error('Payment error:', result);
                let errorMessage = result.error || 'Unknown error';

                // If it's an amount mismatch, show detailed information
                if (result.error === 'Amount mismatch' && result.details) {
                    errorMessage += `\nReceived: ₱${result.details.received_amount}`;
                    errorMessage += `\nExpected: ₱${result.details.expected_amount}`;
                    errorMessage += `\nOrder Total: ₱${result.details.order_total}`;
                    errorMessage += `\nDiscount: ₱${result.details.discount_amount}`;
                }

                alert('Payment processing failed: ' + errorMessage);
            }
        } catch (error) {
            console.error('Payment request failed:', error);
            alert('Payment processing failed. Please try again.');
        }
    } else if (selectedPaymentMethod.value === 'cash') {
        // Handle cash payment - redirect to detailed payment page or process directly
        window.location.href = `/cashier/bills/${selectedOrder.value.order_id}`;
    } else {
        // For PayPal and other methods, we can implement later
        isPaymentModalOpen.value = false;
        alert(`${paymentMethods.find(m => m.id === selectedPaymentMethod.value)?.name} payment will be implemented soon!`);
    }
};
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
                                            <!-- View Bill Button -->
                                            <Button variant="outline" size="sm" asChild>
                                                <Link :href="`/cashier/bills/${order.order_id}`">
                                                    <Eye class="w-4 h-4 mr-1" />
                                                    View
                                                </Link>
                                            </Button>

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
                                                @click="openPaymentModal(order)"
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

        <!-- Payment Method Modal -->
        <Dialog v-model:open="isPaymentModalOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <CreditCard class="w-5 h-5" />
                        Select Payment Method
                    </DialogTitle>
                    <DialogDescription v-if="selectedOrder">
                        Processing payment for {{ selectedOrder.order_number }}
                        <br />
                        <span class="font-semibold">{{ formatCurrency(selectedOrder.total_amount) }}</span>
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <!-- Payment Methods Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            v-for="method in paymentMethods"
                            :key="method.id"
                            @click="selectPaymentMethod(method.id)"
                            :class="[
                                'p-4 rounded-lg border-2 transition-all duration-200 flex flex-col items-center gap-2 text-center',
                                selectedPaymentMethod === method.id
                                    ? 'border-primary bg-primary/5 shadow-md'
                                    : 'border-border hover:border-primary/50',
                                method.bgColor
                            ]"
                        >
                            <component
                                :is="method.icon"
                                :class="[
                                    'w-8 h-8',
                                    selectedPaymentMethod === method.id ? 'text-primary' : method.color
                                ]"
                            />
                            <div>
                                <div :class="[
                                    'font-medium text-sm',
                                    selectedPaymentMethod === method.id ? 'text-primary' : 'text-foreground'
                                ]">
                                    {{ method.name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ method.description }}
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Selected Payment Method Indicator -->
                    <div v-if="selectedPaymentMethod" class="text-center p-3 bg-muted rounded-lg">
                        <div class="text-sm text-muted-foreground">Selected payment method:</div>
                        <div class="font-semibold text-primary">
                            {{ paymentMethods.find(m => m.id === selectedPaymentMethod)?.name }}
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 pt-4">
                        <Button
                            variant="outline"
                            class="flex-1"
                            @click="isPaymentModalOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            class="flex-1"
                            @click="processPayment"
                            :disabled="!selectedPaymentMethod"
                        >
                            Process Payment
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </CashierLayout>
</template>