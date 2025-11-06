# Real-time Restaurant Ordering System Setup Guide

This guide will help you set up the real-time functionality for your restaurant ordering system, allowing orders to be automatically updated across waiter, kitchen, and cashier dashboards without page refreshes.

## Features Added

✅ **Real-time Order Creation**: When a waiter creates an order, it immediately appears on kitchen and cashier dashboards
✅ **Live Order Status Updates**: Kitchen staff can update order status (pending → in_progress → ready) and all dashboards update automatically
✅ **Automatic Order Serving**: When waiters mark items as served, orders automatically move between kitchen/cashier views
✅ **Payment Processing**: When cashier processes payment, order is removed from all active dashboards

## 1. Install Required Dependencies

Run the following command to install the necessary JavaScript packages:

```bash
npm install laravel-echo pusher-js
```

## 2. Configure Broadcasting

### Option A: Using Pusher (Recommended for Production)

1. Sign up for a free account at [https://pusher.com/](https://pusher.com/)
2. Create a new app and get your credentials
3. Add these variables to your `.env` file:

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### Option B: Using Log Driver (Development/Testing)

For testing without Pusher, you can use the log driver:

```env
BROADCAST_DRIVER=log
```

This will log events to `storage/logs/laravel.log` instead of broadcasting them.

## 3. Update Laravel Configuration

Run the following commands to clear configuration cache and ensure broadcasting is enabled:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 4. Set Up Queue Processing

Broadcasting events are queued by default. You need to run a queue worker:

```bash
php artisan queue:work
```

For development, you can run it in the background or use:

```bash
php artisan queue:listen
```

## 5. Build Frontend Assets

After installing the npm packages, rebuild your frontend assets:

```bash
npm run build
```

Or for development:

```bash
npm run dev
```

## 6. Test the Real-time Functionality

### Testing Order Creation:
1. Log in as a waiter
2. Create a new order for a table
3. Check the kitchen dashboard - the order should appear immediately
4. Check the cashier bills page - the order should appear there too

### Testing Order Status Updates:
1. Log in as kitchen staff
2. Change an order status from "pending" to "in_progress"
3. All dashboards (waiter, cashier) should update automatically

### Testing Order Serving:
1. Log in as a waiter
2. Mark order items as served
3. When all items are served, the order should:
   - Disappear from kitchen dashboard
   - Show "Pay" button on cashier dashboard

### Testing Payment Processing:
1. Log in as cashier
2. Process payment for a served order
3. Order should disappear from all active dashboards

## 7. Troubleshooting

### Common Issues:

**Events not broadcasting:**
- Check that `BROADCAST_DRIVER=pusher` is set in `.env`
- Ensure queue worker is running: `php artisan queue:work`
- Check Laravel logs: `tail -f storage/logs/laravel.log`

**Frontend not receiving events:**
- Check browser console for JavaScript errors
- Ensure CSRF token is available in page meta tags
- Verify Pusher credentials are correct

**Authentication errors:**
- Check that broadcasting routes are properly authenticated
- Ensure users are logged in with correct guards (waiter, kitchen, cashier)

**Database errors:**
- Make sure all migrations are run: `php artisan migrate`
- Check that required relationships exist in your models

## 8. Customization

### Adding Custom Events:
You can add more broadcasting events by:

1. Creating new event classes in `app/Events/`
2. Implementing the `ShouldBroadcast` interface
3. Broadcasting the events in your controllers
4. Listening for events in your Vue components

### Changing Channels:
Channel authorization is defined in `routes/channels.php`. You can modify the authentication logic there.

### UI Notifications:
The current implementation logs notifications to the console. You can enhance this by:
- Adding a toast notification library (like vue-toastification)
- Playing sound alerts for new orders
- Adding visual indicators for different event types

## Performance Considerations

- Events are queued by default to prevent blocking the main application
- Consider using Redis for better queue performance in production
- Pusher free tier allows 100 concurrent connections and 200k messages/day
- For high-traffic restaurants, consider upgrading to a paid Pusher plan

## Security Notes

- All broadcast channels are private and require authentication
- Users can only listen to events from their own restaurant
- CSRF protection is enforced on all broadcasting authorization requests

## Support

If you encounter issues with the real-time functionality:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Monitor browser console for JavaScript errors
3. Verify queue workers are running: `php artisan queue:work`
4. Test with log driver first: `BROADCAST_DRIVER=log`

The system is designed to gracefully degrade - if real-time features fail, users can still refresh their browsers to see updates.