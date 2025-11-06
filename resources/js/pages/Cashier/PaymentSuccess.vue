<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle, Receipt, ArrowLeft } from 'lucide-vue-next';

// Props
defineProps<{
    order?: any;
    payment?: any;
}>();

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
};

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Payment Successful" />

    <CashierLayout title="Payment Successful">
        <div class="p-6 max-w-2xl mx-auto space-y-6">

            <!-- Success Message -->
            <div class="text-center space-y-4">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <CheckCircle class="w-10 h-10 text-green-600" />
                </div>
                <h1 class="text-3xl font-bold text-green-600">Payment Successful!</h1>
                <p class="text-muted-foreground text-lg">
                    Your payment has been processed successfully.
                </p>
            </div>

            <!-- Order Details -->
            <Card v-if="order">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Receipt class="w-5 h-5" />
                        Order Details
                    </CardTitle>
                    <CardDescription>
                        Payment confirmation for Order {{ order.order_number }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Order Number</label>
                            <p class="font-semibold">{{ order.order_number }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Customer</label>
                            <p class="font-semibold">{{ order.customer_name || 'Walk-in' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Table</label>
                            <p class="font-semibold">{{ order.table?.table_name || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Payment Method</label>
                            <p class="font-semibold capitalize">{{ payment?.payment_method || order.payment_method || 'Cash' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Total Amount</label>
                            <p class="font-semibold text-lg text-green-600">
                                {{ formatCurrency(payment?.final_amount || (order.total_amount - (payment?.discount_amount || order.discount_amount || 0))) }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Payment Date</label>
                            <p class="font-semibold">{{ formatDate(payment?.paid_at || order.paid_at || order.updated_at) }}</p>
                        </div>
                    </div>

                    <!-- Amount Paid & Change (for Cash payments) -->
                    <div v-if="payment?.payment_method === 'cash' && payment?.amount_paid" class="bg-blue-50 p-3 rounded-lg space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-blue-800"><strong>Amount Paid:</strong></span>
                            <span class="font-semibold text-blue-900">{{ formatCurrency(payment.amount_paid) }}</span>
                        </div>
                        <div v-if="payment.change_amount && payment.change_amount > 0" class="flex justify-between text-sm">
                            <span class="text-blue-800"><strong>Change:</strong></span>
                            <span class="font-semibold text-blue-900">{{ formatCurrency(payment.change_amount) }}</span>
                        </div>
                    </div>

                    <div v-if="payment?.discount_amount || order.discount_amount" class="bg-yellow-50 p-3 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <strong>Discount Applied:</strong> {{ formatCurrency(payment?.discount_amount || order.discount_amount) }}
                            <span v-if="payment?.notes || order.discount_reason" class="ml-2">({{ payment?.notes || order.discount_reason }})</span>
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <Button variant="outline" asChild>
                    <Link href="/cashier/bills">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to Bills
                    </Link>
                </Button>

                <Button v-if="order" asChild>
                    <Link :href="`/cashier/bills/${order.order_id}/print`" target="_blank">
                        <Receipt class="w-4 h-4 mr-2" />
                        Print Receipt
                    </Link>
                </Button>
            </div>

        </div>
    </CashierLayout>
</template>