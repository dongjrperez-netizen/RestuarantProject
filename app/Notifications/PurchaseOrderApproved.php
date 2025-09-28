<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public $purchaseOrder;

    /**
     * Create a new notification instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Purchase Order - Action Required')
            ->greeting('Hello '.$notifiable->supplier_name.'!')
            ->line('Congratulations! You have received a new purchase order from a restaurant.')
            ->line('**Purchase Order:** '.$this->purchaseOrder->po_number)
            ->line('**Restaurant:** '.$this->purchaseOrder->restaurant->restaurant_name)
            ->line('**Total Amount:** ₱'.number_format($this->purchaseOrder->total_amount, 2))
            ->line('**Expected Delivery:** '.($this->purchaseOrder->expected_delivery_date ? date('M d, Y', strtotime($this->purchaseOrder->expected_delivery_date)) : 'Not specified'))
            ->action('View Purchase Order Details', url('/supplier/purchase-orders/'.$this->purchaseOrder->purchase_order_id))
            ->line('Please review the order details and confirm your availability to fulfill this order.')
            ->line('**Next Steps:**')
            ->line('• Review the order items and quantities')
            ->line('• Confirm or update the expected delivery date')
            ->line('• Accept the purchase order to proceed')
            ->salutation('Best regards, '.config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Purchase Order Received',
            'message' => 'You have received a new purchase order '.$this->purchaseOrder->po_number.' from '.$this->purchaseOrder->restaurant->restaurant_name,
            'purchase_order_id' => $this->purchaseOrder->purchase_order_id,
            'po_number' => $this->purchaseOrder->po_number,
            'restaurant_name' => $this->purchaseOrder->restaurant->restaurant_name,
            'total_amount' => $this->purchaseOrder->total_amount,
            'action_url' => '/supplier/purchase-orders/'.$this->purchaseOrder->purchase_order_id,
        ];
    }
}
