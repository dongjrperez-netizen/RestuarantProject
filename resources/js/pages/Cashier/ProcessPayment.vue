<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import { ArrowLeft, CreditCard, Banknote, Smartphone, Calculator, Receipt } from 'lucide-vue-next';

// Props
defineProps<{
    employee?: any;
    order?: any;
}>();

// Form data
const paymentMethod = ref('cash');
const amountPaid = ref('');
const discountAmount = ref('');
const discountReason = ref('');
const notes = ref('');

// Computed values
const finalAmount = ref(mockOrder.total_amount - parseFloat(discountAmount.value || '0'));
const changeAmount = ref(0);

const calculateChange = () => {
    const paid = parseFloat(amountPaid.value || '0');
    const final = mockOrder.total_amount - parseFloat(discountAmount.value || '0');
    changeAmount.value = Math.max(0, paid - final);
};

const processPayment = () => {
    // This would normally submit to backend
    console.log('Processing payment...', {
        payment_method: paymentMethod.value,
        amount_paid: amountPaid.value,
        discount_amount: discountAmount.value,
        discount_reason: discountReason.value,
        notes: notes.value
    });
};
</script>

<template>
    <Head title="Process Payment" />

    <CashierLayout title="Process Payment">
        <div class="p-6 max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm">
                    <ArrowLeft class="w-4 h-4 mr-2" />
                    Back to Dashboard
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Process Payment</h1>
                    <p class="text-muted-foreground">{{ mockOrder.order_number }} - {{ mockOrder.table.table_name }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Order Summary -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Receipt class="w-5 h-5" />
                            Order Summary
                        </CardTitle>
                        <CardDescription>
                            Order {{ mockOrder.order_number }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Customer Info -->
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-muted-foreground">Customer:</span>
                            <span class="font-medium">{{ mockOrder.customer_name || 'Walk-in' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-muted-foreground">Table:</span>
                            <span class="font-medium">{{ mockOrder.table.table_name }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-muted-foreground">Status:</span>
                            <Badge>{{ mockOrder.status.toUpperCase() }}</Badge>
                        </div>

                        <Separator />

                        <!-- Order Items -->
                        <div class="space-y-3">
                            <h4 class="font-medium">Order Items</h4>
                            <div v-for="item in mockOrder.orderItems" :key="item.dish.dish_name"
                                 class="flex justify-between items-center text-sm">
                                <div class="flex-1">
                                    <span class="font-medium">{{ item.quantity }}x {{ item.dish.dish_name }}</span>
                                    <div class="text-muted-foreground">₱{{ item.unit_price.toFixed(2) }} each</div>
                                </div>
                                <span class="font-medium">₱{{ item.total_price.toFixed(2) }}</span>
                            </div>
                        </div>

                        <Separator />

                        <!-- Totals -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span>Subtotal:</span>
                                <span>₱{{ mockOrder.subtotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span>Tax (12%):</span>
                                <span>₱{{ mockOrder.tax_amount.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg">
                                <span>Total:</span>
                                <span>₱{{ mockOrder.total_amount.toFixed(2) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Payment Form -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calculator class="w-5 h-5" />
                            Payment Details
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Payment Method -->
                        <div class="space-y-3">
                            <Label class="text-base font-medium">Payment Method</Label>
                            <RadioGroup v-model="paymentMethod" class="grid grid-cols-3 gap-4">
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="cash" id="cash" />
                                    <Label for="cash" class="flex items-center gap-2 cursor-pointer">
                                        <Banknote class="w-4 h-4" />
                                        Cash
                                    </Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="card" id="card" />
                                    <Label for="card" class="flex items-center gap-2 cursor-pointer">
                                        <CreditCard class="w-4 h-4" />
                                        Card
                                    </Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="digital_wallet" id="digital" />
                                    <Label for="digital" class="flex items-center gap-2 cursor-pointer">
                                        <Smartphone class="w-4 h-4" />
                                        Digital
                                    </Label>
                                </div>
                            </RadioGroup>
                        </div>

                        <!-- Discount -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="discount">Discount Amount</Label>
                                <Input
                                    id="discount"
                                    v-model="discountAmount"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    @input="calculateChange"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="discount-reason">Discount Reason</Label>
                                <Input
                                    id="discount-reason"
                                    v-model="discountReason"
                                    placeholder="Senior citizen, etc."
                                />
                            </div>
                        </div>

                        <!-- Amount Paid -->
                        <div class="space-y-2">
                            <Label for="amount-paid">Amount Paid</Label>
                            <Input
                                id="amount-paid"
                                v-model="amountPaid"
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                @input="calculateChange"
                                :class="paymentMethod === 'cash' ? '' : 'opacity-50'"
                                :disabled="paymentMethod !== 'cash'"
                            />
                            <p class="text-xs text-muted-foreground">
                                {{ paymentMethod === 'cash' ? 'Enter cash amount received' : 'Exact amount for card/digital payments' }}
                            </p>
                        </div>

                        <!-- Payment Summary -->
                        <div class="bg-muted p-4 rounded-lg space-y-2">
                            <div class="flex justify-between text-sm">
                                <span>Order Total:</span>
                                <span>₱{{ mockOrder.total_amount.toFixed(2) }}</span>
                            </div>
                            <div v-if="discountAmount" class="flex justify-between text-sm text-red-600">
                                <span>Discount:</span>
                                <span>-₱{{ parseFloat(discountAmount || '0').toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold">
                                <span>Final Amount:</span>
                                <span>₱{{ (mockOrder.total_amount - parseFloat(discountAmount || '0')).toFixed(2) }}</span>
                            </div>
                            <div v-if="paymentMethod === 'cash' && amountPaid" class="flex justify-between text-green-600">
                                <span>Change:</span>
                                <span>₱{{ Math.max(0, parseFloat(amountPaid || '0') - (mockOrder.total_amount - parseFloat(discountAmount || '0'))).toFixed(2) }}</span>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="space-y-2">
                            <Label for="notes">Notes (Optional)</Label>
                            <Textarea
                                id="notes"
                                v-model="notes"
                                placeholder="Additional payment notes..."
                                rows="3"
                            />
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <Button variant="outline" class="flex-1">
                                Cancel
                            </Button>
                            <Button @click="processPayment" class="flex-1">
                                Process Payment
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </CashierLayout>
</template>