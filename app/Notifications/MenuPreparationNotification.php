<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MenuPreparationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private array $preparationData;

    private string $departmentType;

    public function __construct(array $preparationData, string $departmentType = 'kitchen')
    {
        $this->preparationData = $preparationData;
        $this->departmentType = $departmentType;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->preparationData;
        $subject = $this->getEmailSubject();
        $message = $this->getEmailMessage();

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!")
            ->line($message)
            ->line("Order Reference: {$data['order_reference']}")
            ->line("Preparation Date: {$data['preparation_date']}")
            ->when($data['meal_type'], function ($mail) use ($data) {
                return $mail->line('Meal Type: '.ucfirst($data['meal_type']));
            })
            ->line("Total Dishes: {$data['total_dishes']}")
            ->action('View Preparation Order', url("/menu-preparation/{$data['preparation_order_id']}"))
            ->line('Thank you for your attention to this matter.');
    }

    public function toArray(object $notifiable): array
    {
        $data = $this->preparationData;

        return [
            'type' => 'menu_preparation',
            'department' => $this->departmentType,
            'event' => $data['event'],
            'preparation_order_id' => $data['preparation_order_id'],
            'order_reference' => $data['order_reference'],
            'preparation_date' => $data['preparation_date'],
            'meal_type' => $data['meal_type'],
            'total_dishes' => $data['total_dishes'],
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'action_url' => url("/menu-preparation/{$data['preparation_order_id']}"),
            'created_at' => now()->toISOString(),
        ];
    }

    private function getEmailSubject(): string
    {
        $data = $this->preparationData;
        $event = ucfirst($data['event']);

        if ($this->departmentType === 'kitchen') {
            return "Menu Preparation {$event} - {$data['order_reference']}";
        } else {
            return "Inventory Update Required - {$data['order_reference']}";
        }
    }

    private function getEmailMessage(): string
    {
        $data = $this->preparationData;

        switch ($data['event']) {
            case 'created':
                if ($this->departmentType === 'kitchen') {
                    return 'A new menu preparation order has been created and is ready for preparation.';
                } else {
                    return 'A menu preparation order has been created. Please ensure adequate inventory levels.';
                }
            case 'started':
                return 'Menu preparation has begun. Please coordinate accordingly.';
            case 'completed':
                if ($this->departmentType === 'kitchen') {
                    return 'Menu preparation has been completed successfully.';
                } else {
                    return 'Menu preparation has been completed. Inventory levels have been automatically updated.';
                }
            default:
                return 'Menu preparation order status has been updated.';
        }
    }

    private function getNotificationTitle(): string
    {
        $data = $this->preparationData;
        $event = ucfirst($data['event']);

        if ($this->departmentType === 'kitchen') {
            return "Menu Preparation {$event}";
        } else {
            return 'Inventory Update';
        }
    }

    private function getNotificationMessage(): string
    {
        $data = $this->preparationData;
        $mealInfo = $data['meal_type'] ? ' for '.ucfirst($data['meal_type']) : '';

        switch ($data['event']) {
            case 'created':
                if ($this->departmentType === 'kitchen') {
                    return "New preparation order{$mealInfo} on {$data['preparation_date']} with {$data['total_dishes']} dishes.";
                } else {
                    return "Preparation order{$mealInfo} created. Check inventory levels.";
                }
            case 'started':
                return "Preparation{$mealInfo} has started for {$data['preparation_date']}.";
            case 'completed':
                if ($this->departmentType === 'kitchen') {
                    return "Preparation{$mealInfo} completed for {$data['preparation_date']}.";
                } else {
                    return "Inventory updated for completed preparation{$mealInfo}.";
                }
            default:
                return "Preparation order{$mealInfo} status updated.";
        }
    }
}
