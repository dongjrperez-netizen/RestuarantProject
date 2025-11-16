<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { ArrowLeft, Receipt, Printer, CreditCard, Calendar, User, MapPin, Phone, Percent, X, Trash2, Banknote, Smartphone, Wallet, PlusCircle, Ban } from 'lucide-vue-next';
import { ref } from 'vue';

// Props
const props = defineProps<{
    employee?: any;
    order?: any;
    restaurant?: any;
}>();

// Discount modal
const showDiscountModal = ref(false);
const discountPercentage = ref('');
const discountReason = ref('Senior Citizen');
const discountNotes = ref('');

// Quick add-ons
const addonPrice = ref('');
const addonDescription = ref('');
const addonModalOpen = ref(false);
const appliedAddon = ref<null | { amount: number; description: string }>(null);

// Store temporary discount (not persisted to DB)
const tempDiscount = ref<null | { percentage: number; amount: number; reason: string; notes: string }>(null);

// Void single item state
const isVoidItemModalOpen = ref(false);
const voidTargetItem = ref<any | null>(null);
const voidManagerAccessCode = ref('');
const voidItemReason = ref('');
const voidItemProcessing = ref(false);

// Result modal for discount operations
const showResultModal = ref(false);
const resultModalTitle = ref('');
const resultModalMessage = ref('');
const resultModalType = ref<'success' | 'error'>('success');

// Payment modal state
const isPaymentModalOpen = ref(false);
const isCashPaymentModalOpen = ref(false);
const selectedPaymentMethod = ref('');
const amountReceived = ref('');
const paymentNotes = ref('');
const processing = ref(false);

// Status color mapping
const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
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
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Calculate subtotal (before tax)
const calculateSubtotal = (orderItems: any[]) => {
    return orderItems.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
};

// Calculate tax (assuming 12% VAT)
const calculateTax = (subtotal: number) => {
    return subtotal * 0.12;
};

// Helper function to show result modal
const showResult = (title: string, message: string, type: 'success' | 'error' = 'success') => {
    resultModalTitle.value = title;
    resultModalMessage.value = message;
    resultModalType.value = type;
    showResultModal.value = true;
};

// Discount functions
const openDiscountModal = () => {
    discountPercentage.value = '';
    discountReason.value = 'Senior Citizen';
    discountNotes.value = '';
    showDiscountModal.value = true;
};

const closeDiscountModal = () => {
    showDiscountModal.value = false;
};

const applyDiscount = async (order: any) => {
    if (!order || !discountPercentage.value) return;

    const discountAmount = (order.total_amount * parseFloat(discountPercentage.value)) / 100;

    try {
        // Save discount to database immediately
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch(`/cashier/bills/${order.order_id}/discount`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                discount_percentage: parseFloat(discountPercentage.value),
                discount_amount: discountAmount,
                discount_reason: discountReason.value,
                discount_notes: discountNotes.value
            }),
        });

        // Handle 419 CSRF token expiration
        if (response.status === 419) {
            showResult('Session Expired', 'Your session has expired. Please click OK to refresh the page.', 'error');
            return;
        }

        if (response.ok) {
            // Update the order object with the new discount
            if (props.order) {
                props.order.discount_amount = discountAmount;
                props.order.discount_reason = discountReason.value;
                props.order.discount_percentage = parseFloat(discountPercentage.value);
            }

            // Clear temporary discount since it's now saved
            tempDiscount.value = null;

            closeDiscountModal();
            showResult(
                'Discount Applied!',
                `Successfully applied ${discountPercentage.value}% discount (₱${discountAmount.toFixed(2)})`,
                'success'
            );
        } else {
            const responseText = await response.text();
            let errorMessage = 'Unknown error';
            try {
                const result = JSON.parse(responseText);
                errorMessage = result.error || result.message || 'Unknown error';
            } catch (e) {
                if (responseText.includes('<!DOCTYPE') || responseText.includes('<html')) {
                    errorMessage = 'Session expired. Please refresh the page.';
                } else {
                    errorMessage = responseText || 'Server error';
                }
            }
            showResult('Failed to Apply Discount', errorMessage, 'error');
        }
    } catch (error) {
        console.error('Error applying discount:', error);
        showResult('Failed to Apply Discount', 'An unexpected error occurred. Please try again.', 'error');
    }
};

const removeDiscount = async () => {
    if (!props.order) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch(`/cashier/bills/${props.order.order_id}/discount`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
        });

        // Handle 419 CSRF token expiration
        if (response.status === 419) {
            showResult('Session Expired', 'Your session has expired. Please click OK to refresh the page.', 'error');
            return;
        }

        if (response.ok) {
            // Update the order object to remove discount
            if (props.order) {
                props.order.discount_amount = null;
                props.order.discount_reason = null;
                props.order.discount_percentage = null;
            }

            showResult('Discount Removed!', 'The discount has been successfully removed from this order.', 'success');
        } else {
            const responseText = await response.text();
            let errorMessage = 'Unknown error';
            try {
                const result = JSON.parse(responseText);
                errorMessage = result.error || result.message || 'Unknown error';
            } catch (e) {
                if (responseText.includes('<!DOCTYPE') || responseText.includes('<html')) {
                    errorMessage = 'Session expired. Please refresh the page.';
                } else {
                    errorMessage = responseText || 'Server error';
                }
            }
            showResult('Failed to Remove Discount', errorMessage, 'error');
        }
    } catch (error) {
        console.error('Error removing discount:', error);
        showResult('Failed to Remove Discount', 'An unexpected error occurred. Please try again.', 'error');
    }
};

const calculateDiscountAmount = (order: any) => {
    if (!order || !discountPercentage.value) return 0;
    return (order.total_amount * parseFloat(discountPercentage.value)) / 100;
};

// Calculate final total after discount
const getFinalTotal = (order: any) => {
    if (tempDiscount.value) {
        return order.total_amount - tempDiscount.value.amount;
    }
    if (order.discount_amount) {
        return parseFloat(order.total_amount) - parseFloat(order.discount_amount);
    }
    return order.total_amount;
};

// Item-level void helpers
const canVoidItem = (order: any, item: any) => {
    if (!order || !item) return false;
    const orderStatus = String(order.status || '').toLowerCase();
    const itemStatus = String(item.status || '').toLowerCase();

    // Match backend rules: order must be pending/in_progress and item pending/preparing with no served qty
    const allowedOrderStatuses = ['pending', 'in_progress'];
    const allowedItemStatuses = ['pending', 'preparing'];

    if (!allowedOrderStatuses.includes(orderStatus)) return false;
    if (!allowedItemStatuses.includes(itemStatus)) return false;
    if ((item.served_quantity ?? 0) > 0) return false;

    return true;
};

const openVoidItemModal = (item: any) => {
    voidTargetItem.value = item;
    voidManagerAccessCode.value = '';
    voidItemReason.value = '';
    voidItemProcessing.value = false;
    isVoidItemModalOpen.value = true;
};

const submitVoidItem = async () => {
    if (!props.order || !voidTargetItem.value || !voidManagerAccessCode.value) return;

    try {
        voidItemProcessing.value = true;

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || '';

        const response = await fetch(`/cashier/bills/${props.order.order_id}/items/void`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                manager_access_code: voidManagerAccessCode.value,
                void_reason: voidItemReason.value || null,
                item_ids: [voidTargetItem.value.item_id],
            }),
        });

        if (response.status === 419) {
            showResult('Session Expired', 'Your session has expired. Please click OK to refresh the page.', 'error');
            return;
        }

        const raw = await response.text();

        if (response.ok) {
            // On success, reload only the order data so UI reflects updated items/amount
            isVoidItemModalOpen.value = false;
            voidTargetItem.value = null;

            try {
                // Try to parse and maybe use updated order if needed
                const data = raw ? JSON.parse(raw) : null;
                if (data && data.order) {
                    // Best-effort local update
                    // eslint-disable-next-line @typescript-eslint/no-explicit-any
                    (props as any).order = data.order;
                } else {
                    router.reload({ only: ['order'] });
                }
            } catch {
                router.reload({ only: ['order'] });
            }
        } else {
            let message = 'Failed to void item.';
            try {
                const data = raw ? JSON.parse(raw) : null;
                message = data?.message || data?.error || message;
            } catch {
                if (raw.includes('<!DOCTYPE') || raw.includes('<html')) {
                    message = 'An error occurred (HTML error response). Please refresh the page and try again.';
                } else if (raw.trim()) {
                    message = raw.trim();
                }
            }
            showResult('Failed to Void Item', message, 'error');
        }
    } catch (error) {
        console.error('Error voiding item:', error);
        showResult('Failed to Void Item', 'An unexpected error occurred while voiding the item. Please try again.', 'error');
    } finally {
        voidItemProcessing.value = false;
    }
};

// Add-on functions (UI only)
const openAddonModal = () => {
    if (appliedAddon.value) {
        addonPrice.value = appliedAddon.value.amount.toFixed(2);
        addonDescription.value = appliedAddon.value.description;
    } else {
        addonPrice.value = '';
        addonDescription.value = '';
    }
    addonModalOpen.value = true;
};

const applyAddon = () => {
    const amount = parseFloat(addonPrice.value || '0');
    if (isNaN(amount) || amount <= 0) {
        alert('Please enter a valid add-on price');
        return;
    }

    appliedAddon.value = {
        amount,
        description: addonDescription.value || 'Add-on'
    };

    addonModalOpen.value = false;
};

const removeAddon = () => {
    appliedAddon.value = null;
    addonPrice.value = '';
    addonDescription.value = '';
};

// Generate print bill URL with discount data
const getPrintBillUrl = () => {
    if (!props.order?.order_id) return '#';

    const baseUrl = `/cashier/bills/${props.order.order_id}/print`;

    if (tempDiscount.value) {
        const params = new URLSearchParams({
            discount_percentage: tempDiscount.value.percentage.toString(),
            discount_reason: tempDiscount.value.reason,
            discount_notes: tempDiscount.value.notes || ''
        });
        return `${baseUrl}?${params.toString()}`;
    }

    return baseUrl;
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
    }
];

// Payment method selection
const openPaymentModal = () => {
    selectedPaymentMethod.value = '';
    isPaymentModalOpen.value = true;
};

const selectPaymentMethod = (methodId: string) => {
    selectedPaymentMethod.value = methodId;
};

// Open cash payment modal
const openCashPaymentModal = () => {
    amountReceived.value = '';
    paymentNotes.value = '';
    processing.value = false;
    isPaymentModalOpen.value = false;
    isCashPaymentModalOpen.value = true;
};

// Quick amount suggestions
const getQuickAmounts = () => {
    const amounts = [];
    const final = getCurrentTotal();

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
};

const setQuickAmount = (amount: number) => {
    amountReceived.value = amount.toFixed(2);
};

const getChangeAmount = () => {
    const received = parseFloat(amountReceived.value || '0');
    const due = getCurrentTotal();
    return received - due;
};

const isPaymentValid = () => {
    const received = parseFloat(amountReceived.value || '0');
    const due = getCurrentTotal();
    return received >= due;
};

// Process payment based on selected method
const processPayment = async () => {
    if (!selectedPaymentMethod.value) {
        alert('Please select a payment method');
        return;
    }

    if (selectedPaymentMethod.value === 'cash') {
        // Open cash payment modal for amount input
        openCashPaymentModal();
        return;
    }

    if (selectedPaymentMethod.value === 'gcash') {
        // Process GCash payment via PayMongo
        await processGCashPayment();
        return;
    }

    if (selectedPaymentMethod.value === 'paypal') {
        // Process PayPal payment
        await processPayPalPayment();
        return;
    }

    // For other methods, show not implemented message
    isPaymentModalOpen.value = false;
    alert(`${paymentMethods.find(m => m.id === selectedPaymentMethod.value)?.name} payment will be implemented soon!`);
};

// Process GCash payment via PayMongo
const processGCashPayment = async () => {
    if (!props.order) {
        alert('Order information not available');
        return;
    }

    const currentTotal = getCurrentTotal();

    const paymentData = {
        order_id: props.order.order_id,
        amount: currentTotal,
        customer_name: props.order.customer_name || 'Walk-in Customer',
        customer_email: props.order.customer_email || 'customer@restaurant.com',
        method: 'gcash',
        addon_amount: appliedAddon.value?.amount || null,
        addon_description: appliedAddon.value?.description || null,
    };

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!csrfToken) {
            console.error('CSRF token not found');
            alert('Security token not found. Please refresh the page and try again.');
            return;
        }

        console.log('Sending GCash payment data:', paymentData);

        const response = await fetch('/payment/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(paymentData),
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const textResponse = await response.text();
            console.error('Non-JSON response:', textResponse);
            alert('Server error: Expected JSON response but got HTML. Please check server logs.');
            return;
        }

        const result = await response.json();
        console.log('GCash payment response:', result);

        if (response.ok && result.checkout_url) {
            // Redirect to PayMongo checkout page
            window.location.href = result.checkout_url;
        } else {
            console.error('GCash payment error:', result);
            let errorMessage = result.error || 'Unknown error';

            if (result.error === 'Amount mismatch' && result.details) {
                errorMessage += `\\nReceived: ₱${result.details.received_amount}`;
                errorMessage += `\\nExpected: ₱${result.details.expected_amount}`;
                errorMessage += `\\nOrder Total: ₱${result.details.order_total}`;
                errorMessage += `\\nDiscount: ₱${result.details.discount_amount}`;
            }

            alert('Payment processing failed: ' + errorMessage);
        }
    } catch (error) {
        console.error('GCash payment request failed:', error);
        alert('Payment processing failed. Please try again.');
    }
};

// Process PayPal payment
const processPayPalPayment = async () => {
    if (!props.order) {
        alert('Order information not available');
        return;
    }

    const currentTotal = getCurrentTotal();

    const paymentData = {
        order_id: props.order.order_id,
        amount: currentTotal,
        payment_method: 'paypal',
        customer_name: props.order.customer_name || 'Walk-in Customer',
        customer_email: props.order.customer_email || 'customer@restaurant.com',
        discount_amount: props.order.discount_amount || null,
        discount_reason: props.order.discount_reason || null,
        addon_amount: appliedAddon.value?.amount || null,
        addon_description: appliedAddon.value?.description || null,
    };

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!csrfToken) {
            console.error('CSRF token not found');
            alert('Security token not found. Please refresh the page and try again.');
            return;
        }

        console.log('Sending PayPal payment data:', paymentData);
        console.log('CSRF Token:', csrfToken);

        const response = await fetch('/cashier/payment/paypal', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(paymentData),
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const textResponse = await response.text();
            console.error('Non-JSON response:', textResponse);
            alert('Server error: Expected JSON response but got HTML. Please check server logs.');
            return;
        }

        const result = await response.json();
        console.log('PayPal payment response:', result);

        if (response.ok && result.approval_url) {
            // Redirect to PayPal approval page
            window.location.href = result.approval_url;
        } else {
            console.error('PayPal payment error:', result);
            let errorMessage = result.error || 'Unknown error';

            if (result.error === 'Amount mismatch' && result.details) {
                errorMessage += `\\nReceived: ₱${result.details.received_amount}`;
                errorMessage += `\\nExpected: ₱${result.details.expected_amount}`;
                errorMessage += `\\nOrder Total: ₱${result.details.order_total}`;
                errorMessage += `\\nDiscount: ₱${result.details.discount_amount}`;
            }

            alert('PayPal payment processing failed: ' + errorMessage);
        }
    } catch (error) {
        console.error('PayPal payment request failed:', error);
        alert('PayPal payment processing failed. Please try again.');
    }
};

// Process cash payment
const processCashPayment = async () => {
    if (!props.order || !isPaymentValid()) {
        return;
    }

    processing.value = true;

    try {
        const paymentData = {
            payment_method: 'cash',
            amount_paid: parseFloat(amountReceived.value),
            discount_amount: props.order.discount_amount || null,
            discount_reason: props.order.discount_reason || null,
            addon_amount: appliedAddon.value?.amount || null,
            addon_description: appliedAddon.value?.description || null,
            notes: paymentNotes.value || null
        };

        console.log('Processing cash payment:', paymentData);

        // Get fresh CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch(`/cashier/payment/${props.order.order_id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(paymentData),
        });

        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);

        // Handle 419 CSRF token expiration
        if (response.status === 419) {
            showResult('Session Expired', 'Your session has expired. Please click OK to refresh the page.', 'error');
            return;
        }

        if (response.ok) {
            const result = await response.json();
            console.log('Payment successful:', result);

            isCashPaymentModalOpen.value = false;

            // Redirect to payment success page with order data
            router.visit('/cashier/payment-success', {
                method: 'get',
                data: {
                    order_id: props.order.order_id
                }
            });
        } else {
            console.error('Response not ok. Status:', response.status);
            const responseText = await response.text();
            console.error('Raw response:', responseText);

            let errorMessage = 'Unknown error';
            try {
                const errorData = JSON.parse(responseText);
                console.error('Parsed error data:', errorData);
                errorMessage = errorData.message || errorData.error || 'Unknown error';
            } catch (e) {
                console.error('Failed to parse error response as JSON');
                // Check if it's an HTML error page (like 419)
                if (responseText.includes('<!DOCTYPE') || responseText.includes('<html')) {
                    errorMessage = 'Session expired. Please refresh the page and try again.';
                } else {
                    errorMessage = responseText || 'Server error';
                }
            }

            alert('Failed to process payment: ' + errorMessage);
        }
    } catch (error) {
        console.error('Payment processing error:', error);
        alert('Payment processing failed. Please try again.');
    } finally {
        processing.value = false;
    }
};

// Get current total (including discount and add-ons)
const getCurrentTotal = () => {
    if (!props.order) return 0;

    const baseTotal = parseFloat(props.order.total_amount);
    const discount = props.order.discount_amount ? parseFloat(props.order.discount_amount) : 0;
    const addon = appliedAddon.value ? appliedAddon.value.amount : 0;
    const result = baseTotal - discount + addon;

    console.log('getCurrentTotal debug:', {
        total_amount: props.order.total_amount,
        discount_amount: props.order.discount_amount,
        addon_amount: addon,
        result,
    });

    return result;
};
</script>

<template>
    <Head :title="`Bill - ${order?.order_number}`" />

    <CashierLayout :title="`Bill - ${order?.order_number}`">
        <div class="p-6 max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/cashier/bills">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to Bills
                    </Link>
                </Button>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold">Bill Details</h1>
                    <p class="text-muted-foreground">{{ order?.order_number }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Bill Details -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Receipt class="w-5 h-5" />
                                Bill Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Restaurant Header -->
                            <div class="text-center border-b pb-4">
                                <h2 class="text-xl font-bold">{{ restaurant?.restaurant_name || 'Restaurant Name' }}</h2>
                                <p class="text-sm text-muted-foreground">{{ restaurant?.restaurant_address || 'Restaurant Address' }}</p>
                                <p class="text-sm text-muted-foreground">{{ restaurant?.restaurant_phone || 'Phone Number' }}</p>
                            </div>

                            <!-- Order Info -->
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-muted-foreground">Order Number:</span>
                                    <p class="font-medium">{{ order?.order_number }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Date & Time:</span>
                                    <p class="font-medium">{{ formatDate(order?.created_at) }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Table:</span>
                                    <p class="font-medium">{{ order?.table?.table_name || 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Customer:</span>
                                    <p class="font-medium">{{ order?.customer_name || 'Walk-in Customer' }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Served by:</span>
                                    <p class="font-medium">{{ order?.employee?.name || 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Status:</span>
                                    <Badge :variant="getStatusColor(order?.status)">
                                        {{ order?.status?.toUpperCase() }}
                                    </Badge>
                                </div>
                            </div>

                            <Separator />

                            <!-- Order Items -->
                            <div>
                                <h3 class="font-medium mb-3">Order Items</h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="item in order?.order_items"
                                        :key="item.item_id"
                                        class="flex justify-between items-center gap-3"
                                    >
                                        <div class="flex-1">
                                            <p class="font-medium flex items-center gap-2">
                                                <span>
                                                    {{ item.dish?.dish_name }}
                                                    <span
                                                        v-if="item.variant"
                                                        class="text-sm text-blue-600 font-semibold"
                                                    >
                                                        ({{ item.variant.size_name }})
                                                    </span>
                                                </span>
                                                <Badge
                                                    v-if="item.status"
                                                    variant="outline"
                                                    class="text-[10px] uppercase"
                                                >
                                                    {{ item.status }}
                                                </Badge>
                                            </p>
                                            <p class="text-sm text-muted-foreground">
                                                {{ formatCurrency(item.unit_price) }} × {{ item.quantity }}
                                            </p>
                                            <p
                                                v-if="item.served_quantity && item.served_quantity > 0"
                                                class="text-xs text-muted-foreground"
                                            >
                                                Served: {{ item.served_quantity }} / {{ item.quantity }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-medium mb-1">
                                                {{ formatCurrency(item.quantity * item.unit_price) }}
                                            </p>
                                            <Button
                                                v-if="canVoidItem(order, item)"
                                                variant="outline"
                                                size="sm"
                                                class="text-red-600 hover:text-red-700 border-red-200 hover:border-red-400"
                                                @click="openVoidItemModal(item)"
                                            >
                                                <Ban class="w-3 h-3 mr-1" />
                                                Void
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <!-- Bill Totals -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Subtotal:</span>
                                    <span>{{ formatCurrency(calculateSubtotal(order?.order_items || [])) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>Tax (12%):</span>
                                    <span>{{ formatCurrency(calculateTax(calculateSubtotal(order?.order_items || []))) }}</span>
                                </div>
                                <div v-if="order?.reservation_fee && order?.reservation_fee > 0" class="flex justify-between text-sm text-blue-600">
                                    <span>Reservation Fee:</span>
                                    <span>{{ formatCurrency(order.reservation_fee) }}</span>
                                </div>
                                <div v-if="tempDiscount" class="flex justify-between text-sm text-red-600">
                                    <span>Discount ({{ tempDiscount.reason }}):</span>
                                    <span>-{{ formatCurrency(tempDiscount.amount) }}</span>
                                </div>
                                <div v-else-if="order?.discount_amount && order.discount_amount > 0" class="flex justify-between text-sm text-red-600">
                                    <span>Discount:</span>
                                    <span>-{{ formatCurrency(order.discount_amount) }}</span>
                                </div>
                                <div v-if="appliedAddon" class="flex justify-between text-sm text-blue-600">
                                    <span>
                                        Add-ons
                                        <span v-if="appliedAddon.description" class="text-xs text-muted-foreground">
                                            ({{ appliedAddon.description }})
                                        </span>
                                    </span>
                                    <span>{{ formatCurrency(appliedAddon.amount) }}</span>
                                </div>
                                <Separator />
                                <div class="flex justify-between font-bold text-lg">
                                    <span>Total Amount:</span>
                                    <span :class="(tempDiscount || (order?.discount_amount && order.discount_amount > 0) || appliedAddon) ? 'line-through text-muted-foreground' : ''">
                                        {{ formatCurrency(order?.total_amount || 0) }}
                                    </span>
                                </div>
                                <div v-if="tempDiscount || (order?.discount_amount && order.discount_amount > 0) || appliedAddon" class="flex justify-between font-bold text-lg text-green-600">
                                    <span>Final Amount:</span>
                                    <span>{{ formatCurrency(getCurrentTotal()) }}</span>
                                </div>
                            </div>

                            <!-- Payment Info (if paid) -->
                            <div v-if="order?.status === 'paid'" class="bg-muted p-4 rounded-lg">
                                <h4 class="font-medium mb-2">Payment Information</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <span class="text-muted-foreground">Payment Method:</span>
                                        <p class="font-medium capitalize">{{ order.payment_method }}</p>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Amount Paid:</span>
                                        <p class="font-medium">{{ formatCurrency(order.amount_paid || 0) }}</p>
                                    </div>
                                    <div v-if="order.payment_method === 'cash' && order.amount_paid > order.total_amount">
                                        <span class="text-muted-foreground">Change:</span>
                                        <p class="font-medium">{{ formatCurrency(order.amount_paid - order.total_amount) }}</p>
                                    </div>
                                    <div v-if="order.paid_at">
                                        <span class="text-muted-foreground">Paid At:</span>
                                        <p class="font-medium">{{ formatDate(order.paid_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="order.payment_notes" class="mt-2">
                                    <span class="text-muted-foreground">Notes:</span>
                                    <p class="text-sm">{{ order.payment_notes }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Summary Sidebar -->
                <div>
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="text-center">
                                <div v-if="tempDiscount || (order?.discount_amount && order.discount_amount > 0) || appliedAddon">
                                    <p class="text-lg line-through text-muted-foreground">{{ formatCurrency(order?.total_amount || 0) }}</p>
                                    <p class="text-2xl font-bold text-green-600">{{ formatCurrency(getCurrentTotal()) }}</p>
                                    <p class="text-sm text-muted-foreground">Final Amount</p>
                                    <p v-if="tempDiscount || (order?.discount_amount && order.discount_amount > 0)" class="text-xs text-red-600">Discount Applied</p>
                                    <p v-if="appliedAddon" class="text-xs text-blue-600">Add-ons Included</p>
                                </div>
                                <div v-else>
                                    <p class="text-2xl font-bold">{{ formatCurrency(order?.total_amount || 0) }}</p>
                                    <p class="text-sm text-muted-foreground">Total Amount</p>
                                </div>
                            </div>

                            <Separator />

                            <div class="space-y-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <Calendar class="w-4 h-4 text-muted-foreground" />
                                    <span>{{ formatDate(order?.created_at) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <User class="w-4 h-4 text-muted-foreground" />
                                    <span>{{ order?.customer_name || 'Walk-in Customer' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <MapPin class="w-4 h-4 text-muted-foreground" />
                                    <span>{{ order?.table?.table_name || 'No table assigned' }}</span>
                                </div>
                            </div>

                            <Separator />

                            <div class="text-center">
                                <Badge :variant="getStatusColor(order?.status)" class="text-sm">
                                    {{ order?.status?.toUpperCase() }}
                                </Badge>
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-2">
                                <Button variant="outline" class="w-full" asChild>
                                    <Link :href="getPrintBillUrl()" target="_blank">
                                        <Printer class="w-4 h-4 mr-2" />
                                        Print Bill
                                    </Link>
                                </Button>

                                <!-- Discount Button -->
                                <Button
                                    v-if="order?.status !== 'paid' && !(order?.discount_amount && order.discount_amount > 0) && !tempDiscount"
                                    variant="outline"
                                    class="w-full"
                                    @click="openDiscountModal"
                                >
                                    <Percent class="w-4 h-4 mr-2" />
                                    Apply Discount
                                </Button>

                                <!-- Remove Discount Button -->
                                <Button
                                    v-if="tempDiscount"
                                    variant="outline"
                                    class="w-full text-red-600 hover:text-red-700"
                                    @click="removeDiscount"
                                >
                                    <Trash2 class="w-4 h-4 mr-2" />
                                    Remove Discount
                                </Button>

                                <!-- Add-ons Button -->
                                <Button
                                    v-if="order?.status !== 'paid'"
                                    variant="outline"
                                    class="w-full"
                                    @click="openAddonModal"
                                >
                                    <PlusCircle class="w-4 h-4 mr-2" />
                                    {{ appliedAddon ? 'Edit Add-ons' : 'Add Add-ons' }}
                                </Button>

                                <!-- Remove Add-ons Button -->
                                <Button
                                    v-if="appliedAddon"
                                    variant="outline"
                                    class="w-full text-red-600 hover:text-red-700"
                                    @click="removeAddon"
                                >
                                    <Trash2 class="w-4 h-4 mr-2" />
                                    Remove Add-ons
                                </Button>

                                <Button v-if="order?.status !== 'paid'" class="w-full" @click="openPaymentModal">
                                    <CreditCard class="w-4 h-4 mr-2" />
                                    Process Payment
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Discount Modal -->
            <div v-if="showDiscountModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Apply Discount</h3>
                        <Button variant="ghost" size="sm" @click="closeDiscountModal">
                            <X class="w-4 h-4" />
                        </Button>
                    </div>

                    <div v-if="order" class="space-y-4">
                        <!-- Order Info -->
                        <div class="bg-muted p-3 rounded">
                            <p class="font-medium">{{ order.order_number }}</p>
                            <p class="text-sm text-muted-foreground">{{ order.table?.table_name }} - {{ order.customer_name || 'Walk-in' }}</p>
                            <p class="text-lg font-bold">Original Total: {{ formatCurrency(order.total_amount) }}</p>
                        </div>

                        <!-- Discount Type -->
                        <div class="space-y-2">
                            <Label for="discount-reason">Discount Type</Label>
                            <select
                                id="discount-reason"
                                v-model="discountReason"
                                class="w-full px-3 py-2 border border-border rounded-md bg-background"
                            >
                                <option value="Senior Citizen">Senior Citizen (20%)</option>
                                <option value="PWD">PWD (20%)</option>
                                <option value="Employee">Employee Discount (10%)</option>
                                <option value="Manager">Manager Approval</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Discount Percentage -->
                        <div class="space-y-2">
                            <Label for="discount-percentage">Discount Percentage (%)</Label>
                            <Input
                                id="discount-percentage"
                                v-model="discountPercentage"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                placeholder="Enter percentage (e.g., 20)"
                            />
                        </div>

                        <!-- Discount Amount Preview -->
                        <div v-if="discountPercentage" class="bg-blue-50 p-3 rounded border">
                            <p class="text-sm text-blue-800">
                                <strong>Discount Amount:</strong> {{ formatCurrency(calculateDiscountAmount(order)) }}
                            </p>
                            <p class="text-sm text-blue-800">
                                <strong>New Total:</strong> {{ formatCurrency(order.total_amount - calculateDiscountAmount(order)) }}
                            </p>
                        </div>

                        <!-- Notes -->
                        <div class="space-y-2">
                            <Label for="discount-notes">Notes (Optional)</Label>
                            <Textarea
                                id="discount-notes"
                                v-model="discountNotes"
                                placeholder="Additional notes about this discount..."
                                rows="2"
                            />
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <Button variant="outline" class="flex-1" @click="closeDiscountModal">
                                Cancel
                            </Button>
                            <Button
                                class="flex-1"
                                @click="applyDiscount(order)"
                                :disabled="!discountPercentage || parseFloat(discountPercentage) <= 0"
                            >
                                Apply Discount
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add-ons Modal -->
        <Dialog v-model:open="addonModalOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <PlusCircle class="w-5 h-5" />
                        Add-ons
                    </DialogTitle>
                    <DialogDescription v-if="order">
                        Add extra items (e.g. soup) to {{ order.order_number }}.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <div v-if="order" class="bg-muted p-3 rounded">
                        <p class="font-medium">{{ order.order_number }}</p>
                        <p class="text-sm text-muted-foreground">{{ order.table?.table_name }} - {{ order.customer_name || 'Walk-in' }}</p>
                        <p class="text-sm">Current Amount Due: {{ formatCurrency(getCurrentTotal()) }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="addon-price-modal">Add-on Price</Label>
                        <Input
                            id="addon-price-modal"
                            v-model="addonPrice"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="addon-desc-modal">Description</Label>
                        <Input
                            id="addon-desc-modal"
                            v-model="addonDescription"
                            placeholder="e.g. Soup"
                        />
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button
                            variant="outline"
                            class="flex-1"
                            @click="addonModalOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            class="flex-1"
                            @click="applyAddon"
                            :disabled="!addonPrice || parseFloat(addonPrice) <= 0"
                        >
                            Save Add-ons
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Payment Method Selection Modal -->
        <Dialog v-model:open="isPaymentModalOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <CreditCard class="w-5 h-5" />
                        Select Payment Method
                    </DialogTitle>
                    <DialogDescription v-if="order">
                        Choose payment method for {{ order.order_number }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <!-- Order Summary -->
                    <div class="bg-muted p-4 rounded-lg space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Order Total:</span>
                            <span>{{ formatCurrency(order?.total_amount || 0) }}</span>
                        </div>
                        <div v-if="order?.discount_amount && order.discount_amount > 0" class="flex justify-between text-sm text-red-600">
                            <span>Discount:</span>
                            <span>-{{ formatCurrency(order.discount_amount) }}</span>
                        </div>
                        <div v-if="appliedAddon" class="flex justify-between text-sm text-blue-600">
                            <span>
                                Add-ons
                                <span v-if="appliedAddon.description" class="text-xs text-muted-foreground">
                                    ({{ appliedAddon.description }})
                                </span>
                            </span>
                            <span>{{ formatCurrency(appliedAddon.amount) }}</span>
                        </div>
                        <Separator />
                        <div class="flex justify-between font-bold text-lg">
                            <span>Amount Due:</span>
                            <span class="text-blue-600">{{ formatCurrency(getCurrentTotal()) }}</span>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="space-y-3">
                        <Label class="text-base font-medium">Payment Methods</Label>
                        <div class="grid gap-3">
                            <div
                                v-for="method in paymentMethods"
                                :key="method.id"
                                @click="selectPaymentMethod(method.id)"
                                :class="[
                                    'p-4 border rounded-lg cursor-pointer transition-all',
                                    selectedPaymentMethod === method.id
                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                                        : 'border-border hover:border-primary/50',
                                    method.bgColor
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <component :is="method.icon" :class="['w-6 h-6', method.color]" />
                                    <div class="flex-1">
                                        <h3 class="font-medium">{{ method.name }}</h3>
                                        <p class="text-sm text-muted-foreground">{{ method.description }}</p>
                                    </div>
                                    <div v-if="selectedPaymentMethod === method.id" class="w-5 h-5 rounded-full bg-primary flex items-center justify-center">
                                        <div class="w-2 h-2 bg-white rounded-full"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3">
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
                            Continue
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Cash Payment Modal -->
        <Dialog v-model:open="isCashPaymentModalOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Banknote class="w-5 h-5" />
                        Cash Payment
                    </DialogTitle>
                    <DialogDescription v-if="order">
                        Processing cash payment for {{ order.order_number }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-3 py-4">
                    <!-- Order Summary -->
                    <div class="bg-muted p-2 rounded-lg space-y-1">
                        <div class="flex justify-between text-sm">
                            <span>Order Total:</span>
                            <span>{{ formatCurrency(order?.total_amount || 0) }}</span>
                        </div>
                        <div v-if="order?.discount_amount && order.discount_amount > 0" class="flex justify-between text-sm text-red-600">
                            <span>Discount:</span>
                            <span>-{{ formatCurrency(order.discount_amount) }}</span>
                        </div>
                        <div v-if="appliedAddon" class="flex justify-between text-sm text-blue-600">
                            <span>
                                Add-ons
                                <span v-if="appliedAddon.description" class="text-xs text-muted-foreground">
                                    ({{ appliedAddon.description }})
                                </span>
                            </span>
                            <span>{{ formatCurrency(appliedAddon.amount) }}</span>
                        </div>
                        <Separator />
                        <div class="flex justify-between font-bold">
                            <span>Amount Due:</span>
                            <span class="text-blue-600">{{ formatCurrency(getCurrentTotal()) }}</span>
                        </div>
                    </div>

                    <!-- Amount Received Input -->
                    <div class="space-y-2">
                        <Label for="amount-received" class="text-base font-medium">Amount Received from Customer</Label>

                        <!-- Quick Amount Buttons -->
                        <div class="space-y-1">
                            <Label class="text-sm">Quick Amounts</Label>
                            <div class="grid grid-cols-2 gap-2">
                                <Button
                                    v-for="amount in getQuickAmounts()"
                                    :key="amount"
                                    @click="setQuickAmount(amount)"
                                    variant="outline"
                                    size="sm"
                                    class="text-xs py-1"
                                >
                                    ₱{{ amount.toFixed(2) }}
                                </Button>
                            </div>
                        </div>

                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground">₱</span>
                            <Input
                                id="amount-received"
                                v-model="amountReceived"
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                class="pl-8 text-lg"
                                min="0"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Enter the cash amount received from the customer
                        </p>
                    </div>

                    <!-- Payment Calculation -->
                    <div v-if="amountReceived" class="bg-green-50 p-3 rounded-lg space-y-2">
                        <h4 class="font-medium">Payment Summary</h4>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span>Amount Due:</span>
                                <span>₱{{ getCurrentTotal().toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Amount Received:</span>
                                <span>₱{{ parseFloat(amountReceived || '0').toFixed(2) }}</span>
                            </div>
                            <Separator />
                            <div class="flex justify-between font-bold text-lg" :class="getChangeAmount() >= 0 ? 'text-green-600' : 'text-red-600'">
                                <span>Change:</span>
                                <span>₱{{ getChangeAmount().toFixed(2) }}</span>
                            </div>
                        </div>

                        <!-- Payment Status -->
                        <div class="pt-1">
                            <div v-if="isPaymentValid()" class="flex items-center gap-2 text-green-600 text-sm">
                                <CreditCard class="h-4 w-4" />
                                <span>Payment Valid</span>
                            </div>
                            <div v-else class="flex items-center gap-2 text-red-600 text-sm">
                                <X class="h-4 w-4" />
                                <span>Insufficient Payment - Need ₱{{ (getCurrentTotal() - parseFloat(amountReceived || '0')).toFixed(2) }} more</span>
                            </div>
                        </div>
                    </div>


                    <!-- Action Buttons -->
                    <div class="flex gap-3 pt-2">
                        <Button
                            variant="outline"
                            class="flex-1"
                            @click="isCashPaymentModalOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            class="flex-1 bg-green-600 hover:bg-green-700"
                            @click="processCashPayment"
                            :disabled="!isPaymentValid() || processing"
                        >
                            <span v-if="processing">Processing...</span>
                            <span v-else>Complete Payment</span>
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Void Single Item Modal -->
        <Dialog v-model:open="isVoidItemModalOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Ban class="w-5 h-5 text-red-600" />
                        <span>Void Dish</span>
                    </DialogTitle>
                    <DialogDescription v-if="voidTargetItem && order">
                        You are about to void
                        <span class="font-semibold">{{ voidTargetItem.dish?.dish_name }}</span>
                        from order
                        <span class="font-semibold">{{ order.order_number }}</span>.
                        This action restores the ingredients for this dish back to inventory
                        (if already deducted) and requires a valid manager access code.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="voidTargetItem && order" class="space-y-4 py-2">
                    <div class="bg-muted p-3 rounded-md text-sm space-y-1">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Dish:</span>
                            <span class="font-medium">
                                {{ voidTargetItem.dish?.dish_name }}
                                <span
                                    v-if="voidTargetItem.variant"
                                    class="text-xs text-blue-600 font-semibold"
                                >
                                    ({{ voidTargetItem.variant.size_name }})
                                </span>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Quantity:</span>
                            <span class="font-medium">{{ voidTargetItem.quantity }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Status:</span>
                            <span class="font-medium">{{ voidTargetItem.status }}</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="manager-access-code-item" class="text-sm font-medium">
                            Manager Access Code
                        </Label>
                        <Input
                            id="manager-access-code-item"
                            v-model="voidManagerAccessCode"
                            type="password"
                            inputmode="numeric"
                            maxlength="6"
                            minlength="6"
                            placeholder="Enter 6-digit manager code"
                        />
                        <p class="text-[11px] text-muted-foreground">
                            The manager or restaurant owner must provide this code to authorize the
                            void.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="void-item-reason" class="text-sm font-medium">
                            Reason for Voiding (optional)
                        </Label>
                        <Textarea
                            id="void-item-reason"
                            v-model="voidItemReason"
                            class="w-full text-sm border rounded-md p-2 min-h-[70px] resize-y"
                            placeholder="Describe why this dish is being voided..."
                        />
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button
                            variant="outline"
                            class="flex-1"
                            @click="() => { isVoidItemModalOpen = false; voidTargetItem = null; }"
                            :disabled="voidItemProcessing"
                        >
                            Cancel
                        </Button>
                        <Button
                            class="flex-1 bg-red-600 hover:bg-red-700"
                            @click="submitVoidItem"
                            :disabled="!voidManagerAccessCode || voidManagerAccessCode.length !== 6 || voidItemProcessing"
                        >
                            <span v-if="voidItemProcessing">Voiding...</span>
                            <span v-else>Confirm Void</span>
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Result Modal for Discount Operations -->
        <Dialog :open="showResultModal" @update:open="(open) => showResultModal = open">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <div v-if="resultModalType === 'success'" class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div v-else class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <span>{{ resultModalTitle }}</span>
                    </DialogTitle>
                    <DialogDescription>
                        {{ resultModalMessage }}
                    </DialogDescription>
                </DialogHeader>
                <div class="flex justify-end mt-4">
                    <Button
                        @click="() => {
                            showResultModal = false;
                            // Reload page if it was a session expired error
                            if (resultModalTitle === 'Session Expired') {
                                window.location.reload();
                            }
                        }"
                        :class="resultModalType === 'success' ? 'bg-green-600 hover:bg-green-700' : ''"
                    >
                        OK
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </CashierLayout>
</template>