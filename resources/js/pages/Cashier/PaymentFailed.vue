<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { XCircle, AlertTriangle, ArrowLeft, CreditCard } from 'lucide-vue-next';

// Props
defineProps<{
    order?: any;
}>();

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
};
</script>

<template>
    <Head title="Payment Failed" />

    <CashierLayout title="Payment Failed">
        <div class="p-6 max-w-2xl mx-auto space-y-6">

            <!-- Failure Message -->
            <div class="text-center space-y-4">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
                    <XCircle class="w-10 h-10 text-red-600" />
                </div>
                <h1 class="text-3xl font-bold text-red-600">Payment Failed</h1>
                <p class="text-muted-foreground text-lg">
                    Unfortunately, your payment could not be processed.
                </p>
            </div>

            <!-- Failure Details -->
            <Card class="border-red-200">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-red-600">
                        <AlertTriangle class="w-5 h-5" />
                        What happened?
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="bg-red-50 p-4 rounded-lg">
                        <p class="text-red-800 text-sm">
                            The payment was cancelled or could not be completed. This could be due to:
                        </p>
                        <ul class="list-disc list-inside mt-2 text-red-700 text-sm space-y-1">
                            <li>Payment was cancelled by the customer</li>
                            <li>Insufficient funds in the account</li>
                            <li>Network connection issues</li>
                            <li>Payment gateway timeout</li>
                        </ul>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-blue-800 text-sm font-medium mb-2">What you can do:</p>
                        <ul class="list-disc list-inside text-blue-700 text-sm space-y-1">
                            <li>Try the payment again</li>
                            <li>Use a different payment method</li>
                            <li>Check your account balance</li>
                            <li>Contact customer support if the issue persists</li>
                        </ul>
                    </div>
                </CardContent>
            </Card>

            <!-- Order Details -->
            <Card v-if="order">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CreditCard class="w-5 h-5" />
                        Order Information
                    </CardTitle>
                    <CardDescription>
                        Order {{ order.order_number }} - Payment still pending
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
                            <label class="text-sm font-medium text-muted-foreground">Amount Due</label>
                            <p class="font-semibold text-lg text-orange-600">
                                {{ formatCurrency(order.total_amount - (order.discount_amount || 0)) }}
                            </p>
                        </div>
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
                    <Link :href="`/cashier/bills/${order.order_id}`">
                        <CreditCard class="w-4 h-4 mr-2" />
                        Try Payment Again
                    </Link>
                </Button>
            </div>

        </div>
    </CashierLayout>
</template>