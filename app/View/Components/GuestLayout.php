<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/** Component bố cục cho khách chưa đăng nhập dùng trong Blade. */
class GuestLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
