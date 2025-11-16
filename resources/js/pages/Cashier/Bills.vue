<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Receipt, Printer, CreditCard, Search, Filter, Ban, Info } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';

// Props
const props = defineProps<{
    employee?: any;
    orders?: any;
    filters?: {
        search?: string;
        status?: string;
    };
}>();

// Local reactive access to orders prop
const orders = computed(() => props.orders);

// Search and filter (initialized from server-provided filters)
const searchTerm = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? 'all');

// When filters change on the client, sync with server via Inertia
const applyFilters = () => {
    router.get(
        '/cashier/bills',
        {
            search: searchTerm.value || undefined,
            status: statusFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

// Filtered orders based on search and status (client-side for snappy UX on current page)
const filteredOrders = computed(() => {
    const raw = orders.value?.data || orders.value || [];
    const term = searchTerm.value.trim().toLowerCase();
    const status = statusFilter.value;

    return (raw as any[]).filter((order: any) => {
        const orderStatus = String(order.status || '').toLowerCase();
        if (status !== 'all' && orderStatus !== status) return false;

        if (!term) return true;

        const haystack = [
            order.order_number,
            order.customer_name,
            order.table?.table_name,
        ]
            .filter(Boolean)
            .map((v: any) => String(v).toLowerCase())
            .join(' ');

        return haystack.includes(term);
    });
});

// Void order state
const isVoidModalOpen = ref(false);
const voidTargetOrder = ref<any | null>(null);
const managerAccessCode = ref('');
const voidReason = ref('');
const voidProcessing = ref(false);
// Map of item_id -> boolean (selected for void)
const voidItemSelections = ref<Record<number, boolean>>({});

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

// Determine if an order can be voided based on status
const canVoidOrder = (order: any) => {
    if (!order || !order.status) return false;
    const status = String(order.status).toLowerCase();
    // Backend allows voiding only for pending and in_progress
    return ['pending', 'in_progress'].includes(status);
};

// Human-friendly reason why void is not allowed
const getVoidRestrictionReason = (order: any) => {
    if (!order || !order.status) return 'This order cannot be voided.';
    const status = String(order.status).toLowerCase();
    switch (status) {
        case 'ready':
            return 'This order has been completed by the kitchen and is ready to serve. Voiding is no longer allowed.';
        case 'completed':
            return 'This order has already been served to the customer. Voiding is no longer allowed.';
        case 'paid':
            return 'Cannot void a paid order. Please process a refund instead.';
        case 'voided':
            return 'This order has already been voided.';
        default:
            return `This order cannot be voided at its current status: ${order.status}`;
    }
};

const openVoidModal = (order: any) => {
    voidTargetOrder.value = order;
    managerAccessCode.value = '';
    voidReason.value = '';
    voidProcessing.value = false;

    // Initialize item selections: default all items selected
    const selections: Record<number, boolean> = {};
    const items = order?.order_items || [];
    (items as any[]).forEach((item: any) => {
        if (item?.item_id != null) {
            selections[item.item_id] = true;
        }
    });
    voidItemSelections.value = selections;

    isVoidModalOpen.value = true;
};

const closeVoidModal = () => {
    isVoidModalOpen.value = false;
    voidTargetOrder.value = null;
    voidItemSelections.value = {};
};

const submitVoidOrder = async () => {
    if (!voidTargetOrder.value || !managerAccessCode.value) return;

    // Collect selected item IDs
    const selectedItemIds = Object.entries(voidItemSelections.value)
        .filter(([, selected]) => selected)
        .map(([id]) => Number(id));

    if (selectedItemIds.length === 0) {
        alert('Please select at least one dish to void.');
        return;
    }

    try {
        voidProcessing.value = true;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch(`/cashier/bills/${voidTargetOrder.value.order_id}/items/void`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                manager_access_code: managerAccessCode.value,
                void_reason: voidReason.value || null,
                item_ids: selectedItemIds,
            }),
        });

        // Handle 419 CSRF / session expiry
        if (response.status === 419) {
            alert('Your session has expired. Please refresh the page and try again.');
            return;
        }

        if (response.ok) {
            isVoidModalOpen.value = false;
            voidTargetOrder.value = null;
            voidItemSelections.value = {};
            // Reload only orders list via Inertia so UI reflects updated items/void status
            router.reload({ only: ['orders'] });
        } else {
            const raw = await response.text();
            let message = 'Failed to void order.';
            try {
                const data = JSON.parse(raw);
                message = data.message || data.error || message;
            } catch (e) {
                if (raw.includes('<!DOCTYPE') || raw.includes('<html')) {
                    message = 'An error occurred (HTML error response). Please refresh the page and try again.';
                } else if (raw.trim()) {
                    message = raw.trim();
                }
            }
            alert(message);
        }
    } catch (error) {
        console.error('Error voiding order:', error);
        alert('An unexpected error occurred while voiding the order. Please try again.');
    } finally {
        voidProcessing.value = false;
    }
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
                                    @change="applyFilters"
                                    @keyup.enter="applyFilters"
                                    placeholder="Search by order number, customer name, or table..."
                                    class="pl-10"
                                />
                            </div>
                            <!-- Status Filter -->
                            <select
                                v-model="statusFilter"
                                @change="applyFilters"
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
                                <TableRow v-for="order in filteredOrders" :key="order.order_id">
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
                                                v-if="order.status !== 'paid' && order.status !== 'pending' && order.status !== 'voided'"
                                                size="sm"
                                                @click="redirectToPayment(order)"
                                            >
                                                <CreditCard class="w-4 h-4 mr-1" />
                                                Pay
                                            </Button>

                                            <!-- Void Button (only for pending / in_progress orders) -->
                                            <Button
                                                v-if="canVoidOrder(order)"
                                                variant="outline"
                                                size="sm"
                                                class="text-red-600 hover:text-red-700 border-red-200 hover:border-red-400"
                                                @click="openVoidModal(order)"
                                            >
                                                <Ban class="w-4 h-4 mr-1" />
                                                Void
                                            </Button>

                                            <!-- Status indicator for pending orders -->
                                            <span v-if="order.status === 'pending'" class="text-xs text-muted-foreground">
                                                Order in kitchen
                                            </span>

                                            <!-- Cannot-void indicator for restricted statuses -->
                                            <div
                                                v-else-if="['ready', 'completed', 'paid', 'voided'].includes(order.status?.toLowerCase?.() || '')"
                                                class="flex items-center gap-1 text-[11px] text-muted-foreground"
                                                :title="getVoidRestrictionReason(order)"
                                            >
                                                <Info class="w-3 h-3" />
                                                <span>Cannot void ({{ order.status }})</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <!-- Empty State -->
                                <TableRow v-if="!filteredOrders.length">
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
                                @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                            >
                                <span v-html="link.label"></span>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>

        <!-- Void Order Modal -->
        <Dialog v-model:open="isVoidModalOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Ban class="w-5 h-5 text-red-600" />
                        <span>Void Order / Dishes</span>
                    </DialogTitle>
                    <DialogDescription v-if="voidTargetOrder">
                        You are about to void one or more dishes for order
                        <span class="font-semibold">{{ voidTargetOrder.order_number }}</span>.
                        Select the dishes to void below. This action is restricted and requires a
                        valid manager access code.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="voidTargetOrder" class="space-y-4 py-2">
                    <div class="bg-muted p-3 rounded-md text-sm space-y-1">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Order #:</span>
                            <span class="font-medium">{{ voidTargetOrder.order_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Customer:</span>
                            <span class="font-medium">{{ voidTargetOrder.customer_name || 'Walk-in' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Status:</span>
                            <span class="font-medium">{{ voidTargetOrder.status }}</span>
                        </div>
                    </div>

                    <!-- Dish selection -->
                    <div class="space-y-2">
                        <p class="text-sm font-medium">Select dishes to void</p>
                        <div
                            v-if="voidTargetOrder.order_items && voidTargetOrder.order_items.length"
                            class="max-h-48 overflow-auto border rounded-md divide-y bg-background"
                        >
                            <label
                                v-for="item in voidTargetOrder.order_items"
                                :key="item.item_id"
                                class="flex items-center justify-between px-3 py-2 text-sm gap-3 cursor-pointer hover:bg-muted"
                            >
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4"
                                        :value="item.item_id"
                                        v-model="voidItemSelections[item.item_id]"
                                        :disabled="item.served_quantity > 0 || !['pending','preparing'].includes((item.status || '').toLowerCase())"
                                    />
                                    <div>
                                        <p class="font-medium">
                                            {{ item.dish?.dish_name || 'Dish' }}
                                            <span
                                                v-if="item.variant"
                                                class="text-xs text-blue-600 font-semibold"
                                            >
                                                ({{ item.variant.size_name }})
                                            </span>
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            Qty: {{ item.quantity }} ·
                                            {{ formatCurrency(item.unit_price) }} each · Status:
                                            {{ item.status || 'pending' }}
                                            <span v-if="item.served_quantity && item.served_quantity > 0">
                                                · Served: {{ item.served_quantity }}/{{ item.quantity }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs font-medium">
                                    {{ formatCurrency(item.quantity * item.unit_price) }}
                                </span>
                            </label>
                        </div>
                        <p
                            v-else
                            class="text-xs text-muted-foreground border rounded-md px-3 py-2 bg-muted"
                        >
                            No dishes found for this order.
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            Tip: To void the entire order, keep all dishes selected.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="manager-access-code" class="text-sm font-medium">Manager Access Code</label>
                        <Input
                            id="manager-access-code"
                            v-model="managerAccessCode"
                            type="password"
                            inputmode="numeric"
                            maxlength="6"
                            minlength="6"
                            placeholder="Enter 6-digit manager code"
                        />
                        <p class="text-[11px] text-muted-foreground">
                            The manager or restaurant owner must provide this code to authorize the void.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="void-reason" class="text-sm font-medium">Reason for Voiding (optional)</label>
                        <textarea
                            id="void-reason"
                            v-model="voidReason"
                            class="w-full text-sm border rounded-md p-2 min-h-[70px] resize-y"
                            placeholder="Describe why this order is being voided..."
                        />
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button
                            variant="outline"
                            class="flex-1"
                            @click="closeVoidModal"
                            :disabled="voidProcessing"
                        >
                            Cancel
                        </Button>
                        <Button
                            class="flex-1 bg-red-600 hover:bg-red-700"
                            @click="submitVoidOrder"
                            :disabled="!managerAccessCode || managerAccessCode.length !== 6 || voidProcessing"
                        >
                            <span v-if="voidProcessing">Voiding...</span>
                            <span v-else>Confirm Void</span>
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

    </CashierLayout>
</template>
