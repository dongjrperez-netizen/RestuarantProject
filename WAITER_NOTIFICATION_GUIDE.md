# Waiter Order Ready Notification System

## Overview
This system provides real-time notifications to waiters when orders are marked as "ready" by kitchen staff. The notifications are displayed in a responsive, mobile-friendly interface with sound alerts.

## Features

### ✅ Real-time Notifications
- Instant notification when kitchen marks an order as "ready"
- Broadcasting using Laravel Reverb/Pusher
- Private channel per restaurant for security

### ✅ Responsive UI
- Bell icon with unread count badge
- Animated pulse effect for new notifications
- Slide-out notification panel
- Mobile and desktop optimized

### ✅ Sound Alerts
- Audio notification plays when new order is ready
- Built-in sound effect (no external file needed)
- Works on most modern browsers

### ✅ Notification Management
- Mark all as read
- Dismiss individual notifications
- Clear all notifications
- Auto-dismiss after 30 seconds
- Timestamp with relative time (e.g., "5m ago")

## Files Created/Modified

### New Files
1. **app/Events/OrderReadyToServe.php** - Broadcasting event when order is ready
2. **resources/js/components/OrderReadyNotification.vue** - Notification component
3. **WAITER_NOTIFICATION_GUIDE.md** - This documentation

### Modified Files
1. **app/Http/Controllers/KitchenController.php** - Added event broadcasting
2. **resources/js/layouts/WaiterLayout.vue** - Integrated notification component

## How It Works

### Flow
1. Kitchen staff marks order status as "ready" in Kitchen Dashboard
2. `KitchenController` broadcasts `OrderReadyToServe` event
3. Event is sent to `restaurant.{restaurantId}.waiter` private channel
4. All logged-in waiters for that restaurant receive notification
5. Notification appears with sound alert
6. Waiter can view details and dismiss notification

### Broadcasting Channels
- Channel: `restaurant.{restaurantId}.waiter` (Private)
- Event: `order.ready.to.serve`
- Data includes:
  - Order ID and number
  - Table information
  - Message
  - Timestamp

## Setup Instructions

### 1. Environment Setup
Ensure Laravel Reverb/Pusher is configured in `.env`:

```env
BROADCAST_CONNECTION=reverb
VITE_REVERB_APP_KEY=your-app-key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

### 2. Start Broadcasting Server
Run Laravel Reverb (or your WebSocket server):

```bash
php artisan reverb:start
```

### 3. Build Frontend Assets
```bash
npm run dev
# or for production
npm run build
```

### 4. Channel Authorization
The broadcasting channels are already configured in `routes/channels.php`. Ensure waiter authentication is working:

```php
Broadcast::channel('restaurant.{restaurantId}.waiter', function ($employee, $restaurantId) {
    return $employee->user_id === (int) $restaurantId;
});
```

## Testing Guide

### Test Scenario 1: Basic Notification
1. **Login as Waiter**
   - Navigate to waiter dashboard
   - Verify bell icon appears in header (both mobile and desktop)

2. **Login as Kitchen Staff** (separate browser/incognito)
   - View the Kitchen Dashboard
   - Find an order in "in_progress" or "confirmed" status

3. **Mark Order as Ready**
   - Click "Mark as Ready" button on the order
   - Order status should change to "ready"

4. **Verify Waiter Notification**
   - Switch to waiter browser tab
   - Bell icon should pulse/animate
   - Badge shows "1" unread notification
   - Sound alert plays
   - Click bell to open notification panel
   - Notification displays:
     - Order number
     - "Order ready to serve!" message
     - Table name and number
     - Timestamp

### Test Scenario 2: Multiple Notifications
1. Mark 2-3 orders as ready from kitchen dashboard
2. Verify waiter receives all notifications
3. Badge shows correct count (2, 3, etc.)
4. All notifications appear in panel

### Test Scenario 3: Notification Management
1. **Mark as Read**
   - Click "Mark all as read" button
   - Badge should disappear
   - Notifications remain visible but marked as read

2. **Dismiss Individual**
   - Click X button on a notification
   - Notification should be removed from list

3. **Clear All**
   - Click "Clear all" button
   - All notifications removed
   - Panel shows "No notifications" message

### Test Scenario 4: Responsive Design
1. **Desktop View**
   - Bell icon in top-right header
   - Panel slides in from right
   - Proper spacing and layout

2. **Mobile View**
   - Bell icon visible in mobile header
   - Full-screen notification panel
   - Touch-friendly buttons
   - Swipe-able/scrollable list

### Test Scenario 5: Auto-dismiss
1. Receive a notification
2. Wait 30 seconds without interacting
3. Notification should auto-remove from list

## Troubleshooting

### No Notifications Received

**Check 1: Broadcasting Server**
```bash
# Verify Reverb/Pusher is running
php artisan reverb:start
```

**Check 2: Browser Console**
- Open Developer Tools → Console
- Look for Echo subscription messages
- Should see: "Subscribed to waiter channel for restaurant: X"

**Check 3: Network Tab**
- Look for WebSocket connection
- Should see ws://localhost:8080 connection
- Status should be "101 Switching Protocols"

**Check 4: Restaurant ID**
- Verify waiter's `user_id` matches restaurant ID
- Check database: `employees` table → `user_id` column

**Check 5: Event Broadcasting**
- Check Laravel logs: `storage/logs/laravel.log`
- Should see broadcast events when kitchen marks order as ready

### Sound Not Playing

**Check 1: Browser Permissions**
- Some browsers block autoplay audio
- User must interact with page first (click anywhere)

**Check 2: Audio Element**
- Check browser console for audio errors
- Verify audio element exists in DOM

**Check 3: Volume**
- Check device volume is not muted
- Browser tab is not muted

### Bell Icon Not Showing

**Check 1: Restaurant ID**
```javascript
// In WaiterLayout.vue, check console:
console.log('Restaurant ID:', restaurantId.value);
```

**Check 2: Component Import**
- Verify OrderReadyNotification component is imported
- Check for TypeScript/build errors

**Check 3: Build Process**
```bash
# Rebuild frontend
npm run build
# Clear cache
php artisan cache:clear
php artisan view:clear
```

## Customization

### Change Notification Sound
Replace the audio source in `OrderReadyNotification.vue`:

```vue
<audio ref="audioRef" preload="auto">
  <source src="/path/to/your/notification.mp3" type="audio/mpeg">
</audio>
```

### Adjust Auto-dismiss Time
Change timeout in `addNotification` method:

```javascript
// Default: 30000ms (30 seconds)
setTimeout(() => {
  removeNotification(notification.id);
}, 60000); // 60 seconds
```

### Styling
Modify colors, sizes, and animations in the component's `<style>` section or using Tailwind classes.

### Notification Position
Change panel position in the Teleport section:

```vue
<!-- Right side (default) -->
<div class="fixed inset-0 z-50 flex items-start justify-end">

<!-- Left side -->
<div class="fixed inset-0 z-50 flex items-start justify-start">

<!-- Center -->
<div class="fixed inset-0 z-50 flex items-start justify-center">
```

## API Reference

### OrderReadyToServe Event

```php
use App\Events\OrderReadyToServe;

// Trigger event
broadcast(new OrderReadyToServe($order))->toOthers();
```

**Event Data:**
```json
{
  "order": {
    "order_id": 123,
    "order_number": "ORD-20250110-001",
    "status": "ready",
    "table": {...},
    "orderItems": [...]
  },
  "message": "Order ORD-20250110-001 is ready to serve!",
  "table": {
    "id": 5,
    "number": "5",
    "name": "Table 5"
  },
  "timestamp": "2025-01-10T15:30:00.000000Z"
}
```

## Security Considerations

1. **Private Channels** - Only authenticated waiters of the same restaurant receive notifications
2. **CSRF Protection** - Broadcasting auth uses Laravel's CSRF tokens
3. **User Verification** - Channel authorization checks employee's user_id matches restaurant_id

## Performance

- **Lightweight** - No polling, uses WebSocket connection
- **Efficient** - Only broadcasts to relevant waiters
- **Scalable** - Laravel Reverb handles multiple concurrent connections
- **Low Latency** - Real-time with <100ms delay typically

## Browser Compatibility

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile Safari (iOS 12+)
- ✅ Chrome Mobile (Android)

## Known Limitations

1. **Audio Autoplay** - Some browsers require user interaction before playing sounds
2. **WebSocket** - Requires persistent connection; may disconnect on poor network
3. **Notifications** - Limited to current browser tab; no system notifications (can be added)

## Future Enhancements

- [ ] Browser notification API integration
- [ ] Vibration API for mobile devices
- [ ] Notification history/archive
- [ ] Custom sound selection
- [ ] Notification preferences (sound on/off, auto-dismiss time)
- [ ] Desktop notification support
- [ ] Push notifications for offline waiters

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console for errors
3. Verify broadcasting server is running
4. Review this guide's troubleshooting section

---

**Version:** 1.0.0
**Last Updated:** 2025-10-28
**Compatibility:** Laravel 11.x, Vue 3.x, Inertia.js 1.x
