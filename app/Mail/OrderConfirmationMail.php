<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build(): static
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('تأكيد الطلب #' . $this->order->order_number . ' - ' . $this->order->user->name)
            ->markdown('emails.order-confirmation');
    }
}
