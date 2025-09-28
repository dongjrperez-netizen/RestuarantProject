<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderSubmitted extends Notification implements ShouldQueue
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
            ->subject('Purchase Order Requires Your Approval')
            ->greeting('Hello '.$notifiable->first_name.'!')
            ->line('A new purchase order has been submitted and requires your approval.')
            ->line('**Purchase Order:** '.$this->purchaseOrder->po_number)
            ->line('**Supplier:** '.$this->purchaseOrder->supplier->supplier_name)
            ->line('**Total Amount:** ₱'.number_format($this->purchaseOrder->total_amount, 2))
            ->line('**Submitted by:** '.$this->purchaseOrder->createdBy->first_name.' '.$this->purchaseOrder->createdBy->last_name)
            ->action('Review Purchase Order', url('/purchase-orders/'.$this->purchaseOrder->purchase_order_id))
            ->line('Please review and approve this purchase order to proceed with the order.')
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
            'title' => 'Purchase Order Submitted for Approval',
            'message' => 'Purchase order '.$this->purchaseOrder->po_number.' requires your approval',
            'purchase_order_id' => $this->purchaseOrder->purchase_order_id,
            'po_number' => $this->purchaseOrder->po_number,
            'supplier_name' => $this->purchaseOrder->supplier->supplier_name,
            'total_amount' => $this->purchaseOrder->total_amount,
            'action_url' => '/purchase-orders/'.$this->purchaseOrder->purchase_order_id,
        ];
    }
}
