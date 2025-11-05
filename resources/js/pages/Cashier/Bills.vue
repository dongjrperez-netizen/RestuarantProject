<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Receipt, Printer, CreditCard, Search, Filter, Ban } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

// Props
const props = defineProps<{
    employee?: any;
    orders?: any;
}>();

// Search and filter
const searchTerm = ref('');
const statusFilter = ref('all');
const orders = ref(props.orders);

// Real-time updates using polling
let pollingInterval = null;

const setupRealTimeUpdates = () => {
    // Poll for updates every 5 seconds
    pollingInterval = setInterval(async () => {
        try {
            const response = await fetch('/cashier/api/orders');
            const data = await response.json();
            
            if (data.success && data.orders) {
                orders.value = data.orders;
                console.log('Cashier orders updated');
            }
        } catch (error) {
            console.error('Error fetching orders:', error);
        }
    }, 5000); // Poll every 5 seconds
    
    console.log('Cashier auto-refresh enabled (every 5 seconds)');
};

const cleanupRealTimeUpdates = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

const showNotification = (message, type = 'info') => {
    // You can implement a toast notification system here
    console.log(`[${type.toUpperCase()}] ${message}`);
};

onMounted(() => {
    setupRealTimeUpdates();
});

onUnmounted(() => {
    cleanupRealTimeUpdates();
});

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
        case 'voided': return 'destructive';
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

// Void Order functionality
const showVoidModal = ref(false);
const selectedOrderForVoid = ref<Order | null>(null);
const isVoidingOrder = ref(false);
const voidError = ref('');

// Create fresh form data each time to prevent any caching
const voidForm = ref({
    manager_access_code: '',
    void_reason: ''
});

// VOID POLICY: Check if order can be voided based on status
// Only allow voiding orders that are 'pending' or 'in_progress'
const canVoidOrder = (order: Order) => {
    const allowedStatuses = ['pending', 'in_progress'];
    return allowedStatuses.includes(order.status.toLowerCase());
};

// Get reason why order cannot be voided
const getVoidRestrictionReason = (order: Order) => {
    const status = order.status.toLowerCase();
    switch (status) {
        case 'voided':
            return 'Order already voided';
        case 'paid':
            return 'Cannot void paid orders. Process refund instead';
        case 'ready':
            return 'Order completed by kitchen. Voiding no longer allowed';
        case 'completed':
            return 'Order already served. Voiding no longer allowed';
        default:
            return `Cannot void order with status: ${order.status}`;
    }
};

const openVoidModal = (order: Order) => {
    selectedOrderForVoid.value = order;
    voidError.value = '';

    // Reset form to ensure blank fields
    voidForm.value = {
        manager_access_code: '',
        void_reason: ''
    };

    showVoidModal.value = true;
};

const closeVoidModal = () => {
    showVoidModal.value = false;
    selectedOrderForVoid.value = null;

    // Clear form after closing
    voidForm.value = {
        manager_access_code: '',
        void_reason: ''
    };
    voidError.value = '';
};

const submitVoidOrder = () => {
    if (!selectedOrderForVoid.value) return;

    // Validate form
    if (!voidForm.value.manager_access_code || voidForm.value.manager_access_code.length !== 6) {
        voidError.value = 'Please enter a valid 6-digit manager access code';
        return;
    }

    // Void reason is now optional - no validation needed

    isVoidingOrder.value = true;
    voidError.value = '';

    // Use Inertia router for automatic CSRF handling
    router.post(`/cashier/bills/${selectedOrderForVoid.value.order_id}/void`, voidForm.value, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            showNotification('Order voided successfully', 'success');
            closeVoidModal();

            // Reload the orders list
            router.reload({ only: ['orders'] });
        },
        onError: (errors) => {
            console.error('Errors:', errors);
            // Handle validation errors
            if (errors.manager_access_code) {
                voidError.value = errors.manager_access_code;
            } else if (errors.void_reason) {
                voidError.value = errors.void_reason;
            } else if (errors.error) {
                voidError.value = errors.error;
            } else if (typeof errors === 'string') {
                voidError.value = errors;
            } else if (errors.message) {
                voidError.value = errors.message;
            } else {
                voidError.value = 'Invalid manager access code or failed to void order.';
            }
            isVoidingOrder.value = false;
        },
        onFinish: () => {
            isVoidingOrder.value = false;
        },
    });
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

                                            <!-- Void Order Button (only for pending and in_progress orders) -->
                                            <Button
                                                v-if="canVoidOrder(order)"
                                                variant="destructive"
                                                size="sm"
                                                @click="openVoidModal(order)"
                                                :title="`Void this ${order.status} order`"
                                            >
                                                <Ban class="w-4 h-4 mr-1" />
                                                Void
                                            </Button>

                                            <!-- Show reason when void is not allowed -->
                                            <span
                                                v-if="!canVoidOrder(order) && order.status !== 'paid'"
                                                class="text-xs text-muted-foreground italic"
                                                :title="getVoidRestrictionReason(order)"
                                            >
                                                {{ order.status === 'voided' ? 'Voided' : 'Cannot void' }}
                                            </span>

                                            <!-- Process Payment Button (only for ready/completed orders) -->
                                            <Button
                                                v-if="order.status !== 'paid' && order.status !== 'pending' && order.status !== 'voided'"
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

        <!-- Void Order Modal -->
        <Dialog :open="showVoidModal" @update:open="closeVoidModal">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-destructive">
                        <Ban class="w-5 h-5" />
                        Void Order - Manager Authorization Required
                    </DialogTitle>
                    <DialogDescription>
                        Voiding order <strong>{{ selectedOrderForVoid?.order_number }}</strong> requires manager approval.
                        This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <!-- Manager Access Code -->
                    <div class="space-y-2">
                        <Label for="manager_code">Manager Access Code</Label>
                        <Input
                            id="manager_code"
                            v-model="voidForm.manager_access_code"
                            type="password"
                            placeholder="Enter 6-digit code"
                            maxlength="6"
                            :disabled="isVoidingOrder"
                            autocomplete="one-time-code"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                            data-lpignore="true"
                            data-form-type="other"
                        />
                        <p class="text-xs text-muted-foreground">
                            Ask your manager for their access code to authorize this void.
                        </p>
                    </div>

                    <!-- Void Reason -->
                    <div class="space-y-2">
                        <Label for="void_reason">Reason for Voiding (Optional)</Label>
                        <Textarea
                            id="void_reason"
                            v-model="voidForm.void_reason"
                            placeholder="Optionally provide a reason for voiding this order"
                            rows="4"
                            :disabled="isVoidingOrder"
                            autocomplete="off"
                        />
                    </div>

                    <!-- Error Message -->
                    <div v-if="voidError" class="p-3 text-sm text-destructive bg-destructive/10 border border-destructive/20 rounded-md">
                        {{ voidError }}
                    </div>

                    <!-- Order Details -->
                    <div class="p-3 bg-muted rounded-md space-y-1 text-sm">
                        <p><strong>Table:</strong> {{ selectedOrderForVoid?.table?.table_name || 'N/A' }}</p>
                        <p><strong>Customer:</strong> {{ selectedOrderForVoid?.customer_name || 'Walk-in' }}</p>
                        <p><strong>Total:</strong> {{ selectedOrderForVoid ? formatCurrency(selectedOrderForVoid.total_amount) : '' }}</p>
                        <p><strong>Items:</strong> {{ selectedOrderForVoid?.order_items?.length || 0 }}</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="closeVoidModal"
                        :disabled="isVoidingOrder"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="submitVoidOrder"
                        :disabled="isVoidingOrder"
                    >
                        <Ban class="w-4 h-4 mr-2" />
                        {{ isVoidingOrder ? 'Voiding...' : 'Void Order' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </CashierLayout>
</template>