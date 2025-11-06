# Void Order Policy

## Overview
This document outlines the policy for voiding orders in the restaurant management system.

## Policy Statement
Orders can only be voided **before and during kitchen preparation**. Once an order is completed by the kitchen or served to the customer, voiding is no longer allowed.

## Allowed Statuses for Void

### ✅ Allowed (Before & During Preparation)

| Status | Description | Can Void? |
|--------|-------------|-----------|
| `pending` | Order placed but not yet started by kitchen | ✅ Yes |
| `in_progress` | Order is currently being prepared by kitchen | ✅ Yes |

**Rationale:** Orders in these stages have not been completed yet, so voiding them prevents food waste and allows for order corrections without significant cost to the restaurant.

## Disallowed Statuses for Void

### ❌ Not Allowed (After Preparation)

| Status | Description | Can Void? | Alternative Action |
|--------|-------------|-----------|-------------------|
| `ready` | Order completed by kitchen, ready to serve | ❌ No | Contact manager |
| `completed` | Order served to customer | ❌ No | Contact manager |
| `paid` | Order has been paid | ❌ No | Process refund |
| `voided` | Order already voided | ❌ No | N/A |

**Rationale:** Once the kitchen has completed an order, food has already been prepared. Voiding at this stage would result in food waste and lost revenue. If an issue occurs after preparation, alternative solutions (comps, discounts, refunds) should be considered instead.

## Implementation Details

### Backend Validation
**Location:** `app/Http/Controllers/CashierController.php:790-820`

```php
// VOID POLICY: Only allow voiding orders before and during preparation
$allowedStatuses = ['pending', 'in_progress'];

if (!in_array($order->status, $allowedStatuses)) {
    // Return appropriate error message based on order status
}
```

### Error Messages

**Order Status: `ready`**
> "This order has been completed by the kitchen and is ready to serve. Voiding is no longer allowed."

**Order Status: `completed`**
> "This order has already been served to the customer. Voiding is no longer allowed."

**Order Status: `paid`**
> "Cannot void a paid order. Please process a refund instead."

**Order Status: `voided`**
> "This order has already been voided."

## Authorization Required

All void requests require **manager authorization** via a 6-digit access code, regardless of order status. This applies to:
- Restaurant owners
- Manager-level employees

**Note:** Even with manager authorization, the order status policy still applies.

## Order Lifecycle

```
┌──────────┐
│ PENDING  │ ← Can Void ✅
└────┬─────┘
     │
     ▼
┌─────────────┐
│ IN_PROGRESS │ ← Can Void ✅
└──────┬──────┘
       │
       ▼
   ┌────────┐
   │ READY  │ ← Cannot Void ❌ (Kitchen completed)
   └───┬────┘
       │
       ▼
 ┌───────────┐
 │ COMPLETED │ ← Cannot Void ❌ (Served to customer)
 └─────┬─────┘
       │
       ▼
  ┌────────┐
  │  PAID  │ ← Cannot Void ❌ (Process refund)
  └────────┘
```

## Use Cases

### ✅ Valid Void Scenarios

1. **Customer Changes Mind (Order Pending)**
   - Status: `pending`
   - Action: Void order
   - Result: Order cancelled before kitchen starts

2. **Wrong Order Entered (Order In Progress)**
   - Status: `in_progress`
   - Action: Void order
   - Result: Kitchen stops preparation, ingredients restored

3. **Kitchen Just Started Preparation**
   - Status: `in_progress` (within first few minutes)
   - Action: Void order
   - Result: Minimal food waste

### ❌ Invalid Void Scenarios

1. **Food Already Prepared**
   - Status: `ready`
   - Void: ❌ Not allowed
   - Alternative: Offer discount, comp meal, or keep as house meal

2. **Food Already Served**
   - Status: `completed`
   - Void: ❌ Not allowed
   - Alternative: Manager comp, discount on next visit

3. **Customer Unhappy After Eating**
   - Status: `paid`
   - Void: ❌ Not allowed
   - Alternative: Process refund with manager approval

4. **Order Already Voided**
   - Status: `voided`
   - Void: ❌ Not allowed (already done)
   - Alternative: N/A

## Business Benefits

### 1. **Reduces Food Waste**
By preventing voids after preparation, the policy ensures food isn't wasted unnecessarily.

### 2. **Protects Revenue**
Completed orders represent sunk costs (ingredients, labor). The policy prevents revenue loss from voiding completed work.

### 3. **Encourages Accuracy**
Staff are incentivized to verify orders before they enter the kitchen.

### 4. **Clear Accountability**
The policy establishes clear cutoff points for order modifications.

### 5. **Inventory Accuracy**
Prevents inventory discrepancies from voiding orders after ingredients have been used.

## Manager Override

While managers can authorize voids during the allowed stages, they **cannot override the status policy**. This ensures:
- Consistent application of the rule
- Protection against manager pressure
- Audit trail of attempted voids

All void attempts (successful or denied) are logged with:
- Order ID and number
- Current order status
- Cashier ID
- Manager/Owner who authorized (if applicable)
- Timestamp
- Void reason

## Exception Handling

For orders that need to be voided after preparation:

1. **Contact Manager/Owner**
   - Explain the situation
   - Manager assesses the circumstances

2. **Alternative Solutions**
   - Offer discount on current order
   - Provide complimentary item
   - Process refund (if already paid)
   - Note in customer account for future visit

3. **Documentation**
   - Record incident in notes
   - Track reason for future training
   - Update policy if pattern emerges

## Testing

### Test Cases

1. **Void Pending Order** ✅
   - Create order (status: pending)
   - Attempt void with manager code
   - Expected: Success

2. **Void In-Progress Order** ✅
   - Create order and start kitchen prep (status: in_progress)
   - Attempt void with manager code
   - Expected: Success

3. **Void Ready Order** ❌
   - Complete order in kitchen (status: ready)
   - Attempt void with manager code
   - Expected: Error - "completed by kitchen, voiding not allowed"

4. **Void Completed Order** ❌
   - Mark order as served (status: completed)
   - Attempt void with manager code
   - Expected: Error - "already served, voiding not allowed"

5. **Void Paid Order** ❌
   - Process payment (status: paid)
   - Attempt void with manager code
   - Expected: Error - "process refund instead"

## Logging

All void attempts are logged with:

```php
Log::warning('Void order denied - order past preparation stage', [
    'order_id' => $orderId,
    'order_number' => $order->order_number,
    'current_status' => $order->status,
    'attempted_by_cashier' => $cashier->employee_id,
]);
```

**Log Location:** `storage/logs/laravel.log`

## Future Considerations

### Potential Enhancements

1. **Grace Period**
   - Allow 2-minute grace period after order reaches "ready" status
   - Useful for immediate corrections

2. **Partial Voids**
   - Allow voiding individual items instead of entire order
   - Useful when only some items have issues

3. **Manager Override Option**
   - Add special "force void" permission for extreme circumstances
   - Requires additional authorization level
   - Creates special audit log entry

4. **Customer Facing**
   - Show customers when orders can no longer be modified
   - Set expectations in ordering interface

## Related Documentation

- **BELL_NOTIFICATION_IMPLEMENTATION.md** - Void order notifications
- **VOID_ORDER_NOTIFICATION_TEST.md** - Testing void notifications
- **CashierController.php** - Implementation details

## Policy Version

**Version:** 1.0
**Last Updated:** 2025-11-04
**Next Review:** 2026-02-04 (Quarterly)

## Summary

✅ **Can void:** Pending and In-Progress orders
❌ **Cannot void:** Ready, Completed, and Paid orders
🔐 **Always requires:** Manager authorization (6-digit code)
📝 **Always logged:** All void attempts (success or failure)

This policy balances operational flexibility with revenue protection and food waste reduction.
