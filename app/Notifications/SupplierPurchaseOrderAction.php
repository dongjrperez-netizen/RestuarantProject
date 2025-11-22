<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;

class SupplierPurchaseOrderAction extends Notification // implements ShouldQueue
{
    use Queueable;

    public PurchaseOrder $purchaseOrder;

    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable)
    {
        $confirmUrl = URL::temporarySignedRoute(
            'supplier.purchase-orders.respond',
            now()->addDays(7),
            ['id' => $this->purchaseOrder->purchase_order_id, 'action' => 'confirm']
        );

        $rejectUrl = URL::temporarySignedRoute(
            'supplier.purchase-orders.respond',
            now()->addDays(7),
            ['id' => $this->purchaseOrder->purchase_order_id, 'action' => 'reject']
        );

        \Log::info('Generated supplier PO action URLs', [
            'po_id' => $this->purchaseOrder->purchase_order_id,
            'po_number' => $this->purchaseOrder->po_number,
            'confirmUrl' => $confirmUrl,
            'rejectUrl' => $rejectUrl,
            'supplier_email' => $notifiable->email ?? 'no email',
        ]);

        // Use a custom Blade email template with view data
        return (new MailMessage)
            ->subject('Purchase Order – Action Required: ' . $this->purchaseOrder->po_number)
            ->view(
                'emails.supplier_purchase_order_action',
                [
                    'purchaseOrder' => $this->purchaseOrder,
                    'confirmUrl' => $confirmUrl,
                    'rejectUrl' => $rejectUrl,
                    'notifiableName' => $notifiable->supplier_name ?? ($notifiable->name ?? 'Supplier'),
                ]
            );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Purchase Order',
            'message' => 'Purchase order '.$this->purchaseOrder->po_number.' sent to you',
            'purchase_order_id' => $this->purchaseOrder->purchase_order_id,
            'po_number' => $this->purchaseOrder->po_number,
            'restaurant_name' => $this->purchaseOrder->restaurant->restaurant_name,
        ];
    }
}
