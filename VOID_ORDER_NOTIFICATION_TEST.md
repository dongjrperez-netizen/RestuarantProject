# Void Order Notification System - Test Plan

## System Overview
The notification system broadcasts real-time updates to the waiter dashboard when a cashier voids an order.

## Components Tested
✅ **Backend Broadcasting** (Laravel Reverb)
- Broadcasting driver: `reverb`
- Reverb server: `localhost:8080`
- Event: `OrderStatusUpdated`
- Channel: `restaurant.{restaurantId}.waiter`

✅ **Frontend Listener** (Laravel Echo + Pusher.js)
- Echo initialization in `resources/js/echo.ts`
- Waiter dashboard listener in `resources/js/pages/Waiter/Dashboard.vue`

✅ **Void Order Flow**
- Manager/Owner authorization required
- Broadcasts to all relevant channels (kitchen, cashier, waiter)
- Restores ingredients to inventory
- Shows toast notification on waiter dashboard

## Prerequisites

### 1. Start Required Services
```bash
# Start Laravel Reverb server (IMPORTANT: Use 0.0.0.0 on Windows, not localhost)
php artisan reverb:start --host=0.0.0.0 --port=8080

# Start Laravel development server (in another terminal)
php artisan serve

# Start Vite development server (in another terminal)
npm run dev
```

**Note:** On Windows, Reverb requires `--host=0.0.0.0` instead of `--host=localhost` to avoid the "invalid host IP" error.

### 2. Ensure Queue Worker is Running (if using queues)
```bash
php artisan queue:work
```

## Quick Test (Using Test Route) ⚡

The fastest way to test the notification system is using the automated test route:

### Step 1: Start Services
Ensure all required services are running:
```bash
# Terminal 1: Start Reverb
php artisan reverb:start --host=0.0.0.0 --port=8080

# Terminal 2: Start Laravel
php artisan serve

# Terminal 3: Start Vite
npm run dev
```

### Step 2: Login as Waiter
1. Open browser and navigate to waiter login
2. Login with waiter credentials
3. Navigate to Waiter Dashboard
4. Keep this browser tab open

### Step 3: Trigger Test Notification
In a new browser tab or using curl, visit:
```
http://localhost:8000/test-void-notification/{restaurantId}
```

Replace `{restaurantId}` with the actual restaurant ID (the user_id of the restaurant owner).

Example:
```
http://localhost:8000/test-void-notification/1
```

### Step 4: Verify Bell Icon Notification 🔔
Switch back to the waiter dashboard tab and verify:

**Bell Icon Changes:**
- ✅ Bell icon shows a red badge with unread count (e.g., "1")
- ✅ Bell icon pulses/animates to draw attention
- ✅ Bell icon changes to primary color (instead of gray)
- ✅ Notification sound plays

**Notification Panel:**
- ✅ Click the bell icon to open the notification panel
- ✅ See notification with yellow warning icon (⚠️)
- ✅ Notification shows: "Order ORD-TEST-{timestamp} has been voided by cashier"
- ✅ Shows table name and table number
- ✅ Shows timestamp (e.g., "Just now")
- ✅ Shows "Click to view order" hint text
- ✅ Notification has yellow background (`bg-yellow-50`)
- ✅ "New" badge appears on unread notification

**Clickable Notification:**
- ✅ Click the notification
- ✅ Notification panel closes
- ✅ You are redirected to the waiter dashboard (if not already there)
- ✅ Table orders modal opens automatically for the specific table
- ✅ Notification is marked as read (badge count decreases)

**Notification Management:**
- ✅ "Mark all as read" button works
- ✅ "Clear all" button removes all notifications
- ✅ X button on individual notifications dismisses them
- ✅ Notifications auto-remove after 60 seconds

## Manual Test Procedure (Full Flow)

### Step 1: Login as Waiter
1. Open browser and navigate to waiter login page
2. Login with waiter credentials
3. Navigate to Waiter Dashboard
4. Open browser console (F12) to monitor events
5. Look for console message: "Laravel Echo initialized with Reverb"
6. Look for console message: "Successfully subscribed to waiter channel"

### Step 2: Create Test Order
1. Login as waiter (if not already)
2. Create a new order for a table
3. Add some items to the order
4. Note the order number and table

### Step 3: Login as Cashier (Different Browser/Incognito)
1. Open a new browser window (incognito mode recommended)
2. Navigate to cashier login page
3. Login with cashier credentials
4. Navigate to Bills page
5. Find the order created in Step 2

### Step 4: Void the Order
1. On the cashier's Bills page, click "Void Order" for the test order
2. Enter the manager access code when prompted
3. Provide a void reason (optional)
4. Submit the void request

### Step 5: Verify Waiter Notification
Switch back to the waiter browser window and verify:

**Expected Results:**
1. ✅ A toast notification appears in the top-right corner
2. ✅ Notification shows: "Order {order_number} ({table_name}) has been voided by cashier"
3. ✅ Notification has a warning/yellow styling
4. ✅ If the order modal was open, the voided order is removed from the list
5. ✅ Notification auto-dismisses after 5 seconds
6. ✅ Console shows: "Order status updated event received"
7. ✅ Console shows: "Processing voided order {order_id}"

## Browser Console Monitoring

### Expected Console Output (Waiter Dashboard)
```javascript
// On page load
"Laravel Echo initialized with Reverb"
"Setting up real-time listener for restaurant: {restaurantId}"
"Successfully subscribed to waiter channel"
"Waiter dashboard listening for order updates on channel: restaurant.{restaurantId}.waiter"

// When order is voided
"Order status updated event received:" {event object}
"Event new_status: voided"
"Event order:" {order object}
"Processing voided order {order_id}"
"Order {order_id} was voided and removed from view"
"Showing notification for: {orderNumber} {tableName}"
```

## Troubleshooting

### Issue: No notification received
**Check:**
1. Is Reverb server running? (`php artisan reverb:start`)
2. Check browser console for connection errors
3. Verify waiter is logged in correctly
4. Check Network tab for WebSocket connection (ws://localhost:8080)
5. Verify both users belong to the same restaurant

### Issue: WebSocket connection fails
**Check:**
1. .env file has correct REVERB configuration
2. VITE_REVERB_APP_KEY matches REVERB_APP_KEY
3. Firewall not blocking port 8080
4. Run `npm run dev` to rebuild frontend with correct env vars

### Issue: Authorization fails
**Check:**
1. Custom broadcasting auth endpoint is working (`/broadcasting-custom-auth`)
2. Check Laravel logs for authorization errors
3. Verify waiter has correct `user_id` that matches restaurant

### Issue: Event not broadcasting
**Check:**
1. Broadcasting driver in .env is set to `reverb`
2. Check Laravel logs for broadcasting errors
3. Verify `OrderStatusUpdated` event implements `ShouldBroadcast`
4. Verify event is being fired in CashierController line 868

## Test Checklist

- [ ] Reverb server is running on port 8080
- [ ] Laravel app is running
- [ ] Vite is running (npm run dev)
- [ ] Waiter is logged in and on dashboard
- [ ] Browser console shows successful Echo initialization
- [ ] Browser console shows successful channel subscription
- [ ] Test order has been created
- [ ] Cashier is logged in (different browser/incognito)
- [ ] Void order with manager code
- [ ] Toast notification appears on waiter dashboard
- [ ] Notification shows correct order number and table
- [ ] Notification has warning styling
- [ ] Notification auto-dismisses after 5 seconds
- [ ] Console shows correct event messages
- [ ] Order is removed from tableOrders if modal was open

## Network Tab Verification

### WebSocket Connection
1. Open browser DevTools (F12)
2. Go to Network tab
3. Filter by "WS" (WebSocket)
4. Look for connection to `ws://localhost:8080/app/{appKey}`
5. Status should be "101 Switching Protocols" (successful)
6. Connection should show as "pending" (maintaining connection)

### WebSocket Messages
Click on the WebSocket connection and view "Messages" tab:
- You should see subscription messages
- When order is voided, you should see the event message

## Configuration Summary

### Backend (.env)
```
BROADCAST_DRIVER=reverb
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=576873
REVERB_APP_KEY=ttzw8bnntzh4wywxj47z
REVERB_APP_SECRET=7dyqy28hwf5jpettel5g
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Frontend (Vite env vars)
```
VITE_REVERB_APP_KEY=ttzw8bnntzh4wywxj47z
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

## Code References

### Event Broadcasting
- **CashierController**: Line 868 - `broadcast(new OrderStatusUpdated($order, $previousStatus))->toOthers();`
- **OrderStatusUpdated Event**: `app/Events/OrderStatusUpdated.php:38-40` - Broadcasts to waiter channel
- **Broadcasting Auth**: `routes/web.php:44-73` - Custom multi-guard authentication

### Frontend Listener
- **Echo Setup**: `resources/js/echo.ts:21-55` - Echo initialization with Reverb
- **Waiter Dashboard**: `resources/js/pages/Waiter/Dashboard.vue:297-326` - Event listener and notification handler
- **Channel Subscription**: Line 295 - `window.Echo.private('restaurant.${restaurantId}.waiter')`

### Channel Authorization
- **Channels Definition**: `routes/channels.php:50-80` - Waiter channel authorization logic

## Success Criteria
✅ Notification system is working correctly when:
1. Waiter receives real-time notification within 1-2 seconds of void
2. Notification displays correct information
3. No JavaScript errors in console
4. WebSocket connection remains stable
5. Multiple notifications can be shown and dismissed properly

## Additional Notes
- Notifications are displayed using a toast notification system
- Each notification has a unique ID for dismissal
- Notifications stack vertically in the top-right corner
- The system uses Teleport to render notifications outside the main component tree
- Notifications include icons based on type (warning, success, error, info)
