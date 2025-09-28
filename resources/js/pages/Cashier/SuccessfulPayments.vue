<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { DollarSign, ShoppingCart, Clock, Users, Receipt, TrendingUp } from 'lucide-vue-next';

// Props would come from backend
defineProps<{
    employee?: any;
    paidOrders?: any;
    todayRevenue?: number;
    todayOrderCount?: number;
    pendingOrdersCount?: number;
}>();

// Mock data for design example
const mockPaidOrders = [
    {
        order_id: 1,
        order_number: 'ORD-20250927-0001',
        table: { table_number: 5, table_name: 'Table 5' },
        customer_name: 'John Doe',
        total_amount: 125.50,
        status: 'paid',
        updated_at: '2025-09-27T14:30:00Z',
        orderItems: [
            { dish: { dish_name: 'Adobong Manok' }, quantity: 2, unit_price: 45.00 },
            { dish: { dish_name: 'Garlic Rice' }, quantity: 2, unit_price: 17.75 }
        ]
    },
    {
        order_id: 2,
        order_number: 'ORD-20250927-0002',
        table: { table_number: 3, table_name: 'Table 3' },
        customer_name: 'Jane Smith',
        total_amount: 89.25,
        status: 'paid',
        updated_at: '2025-09-27T14:25:00Z',
        orderItems: [
            { dish: { dish_name: 'Sisig' }, quantity: 1, unit_price: 65.00 },
            { dish: { dish_name: 'San Miguel Beer' }, quantity: 2, unit_price: 12.00 }
        ]
    }
];

const mockStats = {
    todayRevenue: 2450.75,
    todayOrderCount: 18,
    activeTables: 7
};
</script>

<template>
    <Head title="Successful Payments" />

    <CashierLayout title="Successful Payments">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Successful Payments</h1>
                    <p class="text-muted-foreground">View completed transactions and payment history</p>
                </div>
                <div class="flex items-center gap-4">
                    <Badge variant="secondary" class="px-3 py-1">
                        <Clock class="w-4 h-4 mr-1" />
                        {{ new Date().toLocaleTimeString() }}
                    </Badge>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Today's Revenue</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">₱{{ Number(todayRevenue || 0).toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">
                            <TrendingUp class="w-3 h-3 inline mr-1" />
                            +12% from yesterday
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Orders Today</CardTitle>
                        <ShoppingCart class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ todayOrderCount || 0 }}</div>
                        <p class="text-xs text-muted-foreground">
                            +3 new orders
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pending Orders</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ pendingOrdersCount || 0 }}</div>
                        <p class="text-xs text-muted-foreground">
                            Awaiting payment
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Avg. Order Value</CardTitle>
                        <Receipt class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">₱{{ Number(todayOrderCount || 0) > 0 ? Math.round(Number(todayRevenue || 0) / Number(todayOrderCount || 0)) : 0 }}</div>
                        <p class="text-xs text-muted-foreground">
                            Per order today
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Successful Payments -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Receipt class="w-5 h-5" />
                        Recent Successful Payments
                    </CardTitle>
                    <CardDescription>
                        View completed payment transactions
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-for="order in (paidOrders?.data || paidOrders || [])" :key="order.order_id"
                             class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 transition-colors">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <Badge variant="default" class="bg-green-100 text-green-800">
                                        PAID
                                    </Badge>
                                    <span class="font-medium">{{ order.order_number }}</span>
                                    <span class="text-sm text-muted-foreground">{{ order.table?.table_name || 'Unknown Table' }}</span>
                                </div>
                                <div class="text-sm text-muted-foreground mb-1">
                                    Customer: {{ order.customer_name || 'Walk-in' }}
                                </div>
                                <div class="text-sm">
                                    Items: {{ (order.order_items || order.orderItems || []).map((item: any) => `${item.quantity}x ${item.dish?.dish_name || 'Unknown'}`).join(', ') || 'No items' }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold">₱{{ Number(order.total_amount || 0).toFixed(2) }}</div>
                                <div class="flex gap-2 mt-2">
                                    <Button size="sm" variant="outline">
                                        View Receipt
                                    </Button>
                                    <Button size="sm" variant="secondary">
                                        Print Receipt
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-if="(paidOrders?.data || paidOrders || []).length === 0" class="text-center py-8 text-muted-foreground">
                            No successful payments found
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <Button variant="outline" class="h-20 flex-col gap-2">
                            <Receipt class="w-6 h-6" />
                            <span>Daily Report</span>
                        </Button>
                        <Button variant="outline" class="h-20 flex-col gap-2">
                            <DollarSign class="w-6 h-6" />
                            <span>Cash Drawer</span>
                        </Button>
                        <Button variant="outline" class="h-20 flex-col gap-2">
                            <ShoppingCart class="w-6 h-6" />
                            <span>All Orders</span>
                        </Button>
                        <Button variant="outline" class="h-20 flex-col gap-2">
                            <Users class="w-6 h-6" />
                            <span>Table Status</span>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </CashierLayout>
</template>