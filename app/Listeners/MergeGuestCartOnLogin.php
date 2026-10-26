<?php

namespace App\Listeners;

use App\Services\CartService;
use Illuminate\Auth\Events\Login;

/** Gộp giỏ hàng khách vào tài khoản sau khi đăng nhập. */
class MergeGuestCartOnLogin
{
    public function __construct(private CartService $cart) {}

    public function handle(Login $event): void
    {
        $this->cart->mergeGuestIntoUser();
    }
}
