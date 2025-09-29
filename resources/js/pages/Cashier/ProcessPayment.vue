<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import Alert from '@/components/ui/alert/Alert.vue';
import AlertDescription from '@/components/ui/alert/AlertDescription.vue';
import { ArrowLeft, CreditCard, Banknote, Smartphone, Calculator, Receipt, AlertTriangle, CheckCircle } from 'lucide-vue-next';

interface OrderItem {
  dish: {
    dish_name: string;
  };
  quantity: number;
  unit_price: number;
  total_price: number;
}

interface Order {
  order_number: string;
  customer_name?: string;
  table: {
    table_name: string;
  };
  status: string;
  orderItems: OrderItem[];
  subtotal: number;
  tax_amount: number;
  total_amount: number;
}

// Props
const props = defineProps<{
    employee?: any;
    order?: Order;
}>();

// Mock order data if not provided (for development)
const mockOrder: Order = {
  order_number: 'ORD-001',
  customer_name: 'John Doe',
  table: { table_name: 'Table 5' },
  status: 'ready',
  orderItems: [
    {
      dish: { dish_name: 'Chicken Adobo' },
      quantity: 2,
      unit_price: 350.00,
      total_price: 700.00
    },
    {
      dish: { dish_name: 'Garlic Rice' },
      quantity: 1,
      unit_price: 50.00,
      total_price: 50.00
    }
  ],
  subtotal: 750.00,
  tax_amount: 90.00,
  total_amount: 840.00
};

// Use provided order or mock data
const orderData = computed(() => props.order || mockOrder);

// Form data
const paymentMethod = ref('cash');
const amountPaid = ref('');
const discountAmount = ref('');
const discountReason = ref('');
const discountType = ref('amount'); // 'amount' or 'percentage'
const notes = ref('');
const processing = ref(false);

// Computed values
const discountValue = computed(() => {
  const discount = parseFloat(discountAmount.value || '0');
  if (discountType.value === 'percentage') {
    return Math.min(discount, 100) / 100 * orderData.value.total_amount;
  }
  return Math.min(discount, orderData.value.total_amount);
});

const finalAmount = computed(() => {
  return Math.max(0, orderData.value.total_amount - discountValue.value);
});

const amountPaidNumber = computed(() => parseFloat(amountPaid.value || '0'));

const changeAmount = computed(() => {
  if (paymentMethod.value !== 'cash') return 0;
  return Math.max(0, amountPaidNumber.value - finalAmount.value);
});

const isPaymentValid = computed(() => {
  if (paymentMethod.value === 'cash') {
    return amountPaidNumber.value >= finalAmount.value;
  }
  return true; // For card/digital payments, assume they're always exact
});

const paymentError = computed(() => {
  if (paymentMethod.value === 'cash' && amountPaid.value && amountPaidNumber.value < finalAmount.value) {
    return `Insufficient payment. Need at least ₱${finalAmount.value.toFixed(2)}`;
  }
  return null;
});

// Watch for payment method changes
watch(paymentMethod, (newMethod) => {
  if (newMethod !== 'cash') {
    amountPaid.value = finalAmount.value.toFixed(2);
  } else {
    amountPaid.value = '';
  }
});

// Watch for discount changes to validate
watch([discountAmount, discountType], () => {
  if (discountType.value === 'percentage') {
    const percentage = parseFloat(discountAmount.value || '0');
    if (percentage > 100) {
      discountAmount.value = '100';
    }
  } else {
    const amount = parseFloat(discountAmount.value || '0');
    if (amount > orderData.value.total_amount) {
      discountAmount.value = orderData.value.total_amount.toFixed(2);
    }
  }
});

const processPayment = async () => {
  if (!isPaymentValid.value) {
    return;
  }

  processing.value = true;

  try {
    // This would normally submit to backend
    const paymentData = {
      payment_method: paymentMethod.value,
      amount_paid: paymentMethod.value === 'cash' ? amountPaidNumber.value : finalAmount.value,
      total_amount: orderData.value.total_amount,
      discount_amount: discountValue.value,
      discount_type: discountType.value,
      discount_reason: discountReason.value,
      final_amount: finalAmount.value,
      change_amount: changeAmount.value,
      notes: notes.value
    };

    console.log('Processing payment...', paymentData);

    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500));

    // Navigate to success page
    router.visit('/cashier/payment-success', {
      method: 'get',
      data: paymentData
    });
  } catch (error) {
    console.error('Payment processing failed:', error);
  } finally {
    processing.value = false;
  }
};

// Quick amount buttons for cash payments
const quickAmounts = computed(() => {
  const amounts = [];
  const final = finalAmount.value;

  // Add exact amount
  amounts.push(final);

  // Add convenient amounts above the final amount
  const roundedUp = Math.ceil(final / 100) * 100;
  if (roundedUp > final) amounts.push(roundedUp);

  const nextHundred = Math.ceil(final / 100) * 100 + 100;
  if (nextHundred !== roundedUp) amounts.push(nextHundred);

  const nextFiveHundred = Math.ceil(final / 500) * 500;
  if (nextFiveHundred > nextHundred && nextFiveHundred - final <= 1000) {
    amounts.push(nextFiveHundred);
  }

  return [...new Set(amounts)].sort((a, b) => a - b);
});

const setQuickAmount = (amount: number) => {
  amountPaid.value = amount.toFixed(2);
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
                    <p class="text-muted-foreground">{{ orderData.order_number }} - {{ orderData.table.table_name }}</p>
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
                            Order {{ orderData.order_number }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Customer Info -->
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-muted-foreground">Customer:</span>
                            <span class="font-medium">{{ orderData.customer_name || 'Walk-in' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-muted-foreground">Table:</span>
                            <span class="font-medium">{{ orderData.table.table_name }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-muted-foreground">Status:</span>
                            <Badge>{{ orderData.status.toUpperCase() }}</Badge>
                        </div>

                        <Separator />

                        <!-- Order Items -->
                        <div class="space-y-3">
                            <h4 class="font-medium">Order Items</h4>
                            <div v-for="item in orderData.orderItems" :key="item.dish.dish_name"
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
                                <span>₱{{ orderData.subtotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span>Tax (12%):</span>
                                <span>₱{{ orderData.tax_amount.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg">
                                <span>Total:</span>
                                <span>₱{{ orderData.total_amount.toFixed(2) }}</span>
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
                        <div class="space-y-4">
                            <Label class="text-base font-medium">Discount (Optional)</Label>

                            <!-- Discount Type -->
                            <RadioGroup v-model="discountType" class="flex gap-4">
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="amount" id="discount-amount" />
                                    <Label for="discount-amount" class="cursor-pointer">₱ Amount</Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="percentage" id="discount-percentage" />
                                    <Label for="discount-percentage" class="cursor-pointer">% Percentage</Label>
                                </div>
                            </RadioGroup>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="discount">
                                        {{ discountType === 'percentage' ? 'Discount Percentage' : 'Discount Amount' }}
                                    </Label>
                                    <div class="relative">
                                        <Input
                                            id="discount"
                                            v-model="discountAmount"
                                            type="number"
                                            :step="discountType === 'percentage' ? '1' : '0.01'"
                                            :placeholder="discountType === 'percentage' ? '0' : '0.00'"
                                            :max="discountType === 'percentage' ? '100' : orderData.total_amount"
                                            min="0"
                                        />
                                        <span v-if="discountType === 'percentage'" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground">%</span>
                                        <span v-else class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground">₱</span>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        {{ discountType === 'percentage' ? 'Max 100%' : `Max ₱${orderData.total_amount.toFixed(2)}` }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="discount-reason">Discount Reason</Label>
                                    <Input
                                        id="discount-reason"
                                        v-model="discountReason"
                                        placeholder="Senior citizen, PWD, etc."
                                    />
                                </div>
                            </div>

                            <!-- Discount Preview -->
                            <div v-if="discountValue > 0" class="p-3 bg-orange-50 border border-orange-200 rounded-lg">
                                <div class="flex justify-between text-sm">
                                    <span>Discount Applied:</span>
                                    <span class="font-medium text-orange-700">
                                        {{ discountType === 'percentage' ? `${discountAmount}% ` : '' }}
                                        (-₱{{ discountValue.toFixed(2) }})
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Amount Paid -->
                        <div class="space-y-3">
                            <Label for="amount-paid" class="text-base font-medium">Amount Received</Label>

                            <!-- Quick Amount Buttons for Cash -->
                            <div v-if="paymentMethod === 'cash'" class="space-y-2">
                                <Label class="text-sm">Quick Amounts</Label>
                                <div class="grid grid-cols-2 gap-2">
                                    <Button
                                        v-for="amount in quickAmounts"
                                        :key="amount"
                                        @click="setQuickAmount(amount)"
                                        variant="outline"
                                        size="sm"
                                        class="text-xs"
                                    >
                                        ₱{{ amount.toFixed(2) }}
                                    </Button>
                                </div>
                            </div>

                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground">₱</span>
                                <Input
                                    id="amount-paid"
                                    v-model="amountPaid"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    :class="[
                                        'pl-8',
                                        paymentMethod === 'cash' ? '' : 'opacity-50',
                                        paymentError ? 'border-red-500 focus:border-red-500' : ''
                                    ]"
                                    :disabled="paymentMethod !== 'cash'"
                                    min="0"
                                />
                            </div>

                            <p class="text-xs text-muted-foreground">
                                {{ paymentMethod === 'cash' ? 'Enter cash amount received from customer' : 'Exact amount for card/digital payments' }}
                            </p>

                            <!-- Payment Error -->
                            <Alert v-if="paymentError" variant="destructive">
                                <AlertTriangle class="h-4 w-4" />
                                <AlertDescription>{{ paymentError }}</AlertDescription>
                            </Alert>
                        </div>

                        <!-- Payment Summary -->
                        <div class="bg-muted p-4 rounded-lg space-y-3">
                            <h4 class="font-medium">Payment Summary</h4>

                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Order Total:</span>
                                    <span>₱{{ orderData.total_amount.toFixed(2) }}</span>
                                </div>

                                <div v-if="discountValue > 0" class="flex justify-between text-sm text-red-600">
                                    <span>Discount {{ discountType === 'percentage' ? `(${discountAmount}%)` : '' }}:</span>
                                    <span>-₱{{ discountValue.toFixed(2) }}</span>
                                </div>

                                <Separator />

                                <div class="flex justify-between font-bold text-lg">
                                    <span>Amount Due:</span>
                                    <span class="text-blue-600">₱{{ finalAmount.toFixed(2) }}</span>
                                </div>

                                <div v-if="paymentMethod === 'cash' && amountPaid" class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span>Amount Received:</span>
                                        <span>₱{{ amountPaidNumber.toFixed(2) }}</span>
                                    </div>

                                    <div class="flex justify-between font-bold" :class="changeAmount >= 0 ? 'text-green-600' : 'text-red-600'">
                                        <span>Change:</span>
                                        <span>₱{{ changeAmount.toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Status Indicator -->
                            <div v-if="paymentMethod === 'cash' && amountPaid" class="pt-2">
                                <div v-if="isPaymentValid" class="flex items-center gap-2 text-green-600 text-sm">
                                    <CheckCircle class="h-4 w-4" />
                                    <span>Payment Valid</span>
                                </div>
                                <div v-else class="flex items-center gap-2 text-red-600 text-sm">
                                    <AlertTriangle class="h-4 w-4" />
                                    <span>Insufficient Payment</span>
                                </div>
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
                            <Button
                                variant="outline"
                                class="flex-1"
                                @click="router.visit('/cashier/dashboard')"
                                :disabled="processing"
                            >
                                Cancel
                            </Button>
                            <Button
                                @click="processPayment"
                                class="flex-1"
                                :disabled="!isPaymentValid || processing"
                            >
                                <span v-if="processing">Processing...</span>
                                <span v-else-if="paymentMethod === 'cash'">Process Cash Payment</span>
                                <span v-else>Process {{ paymentMethod === 'card' ? 'Card' : 'Digital' }} Payment</span>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </CashierLayout>
</template>