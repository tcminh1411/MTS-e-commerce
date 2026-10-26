<?php

namespace App\Mail;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Gửi email thông báo hàng có lại. */
class StockAvailableMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProductVariant $variant,
    ) {}

    public function envelope(): Envelope
    {
        $productName = $this->variant->product?->name ?? 'Sản phẩm';

        return new Envelope(
            subject: '✅ '.$productName.' đã có hàng trở lại',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.stock-available',
        );
    }
}
