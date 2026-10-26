<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Xử lý nghiệp vụ giỏ hàng dùng chung. */
class CartService
{
    public function current(): Cart
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(
                ['user_id' => Auth::id()],
                ['currency' => 'VND']
            );
        }

        $token = session('guest_cart_token');
        if (! $token) {
            $token = (string) Str::uuid();
            session(['guest_cart_token' => $token]);
        }

        return Cart::firstOrCreate(
            ['session_token' => $token],
            ['currency' => 'VND', 'expires_at' => now()->addDays(30)]
        );
    }

    public function add(Product $product, ?ProductVariant $variant, int $qty = 1): CartItem
    {
        $cart = $this->current();
        $price = $variant?->final_price ?? $product->final_price;

        return DB::transaction(function () use ($cart, $product, $variant, $qty, $price) {
            $existing = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('variant_id', $variant?->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->quantity += $qty;
                $existing->unit_price = $price;
                $existing->save();

                return $existing;
            }

            return CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity' => $qty,
                'unit_price' => $price,
            ]);
        });
    }

    public function updateQty(CartItem $item, int $qty): void
    {
        if ($qty <= 0) {
            $item->delete();

            return;
        }
        $item->quantity = $qty;
        $item->save();
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(): void
    {
        $this->current()->items()->delete();
    }

    public function mergeGuestIntoUser(): void
    {
        if (! Auth::check()) {
            return;
        }

        $guestToken = session('guest_cart_token');
        if (! $guestToken) {
            return;
        }

        $guestCart = Cart::where('session_token', $guestToken)
            ->whereNull('user_id')
            ->with('items')
            ->first();

        if (! $guestCart || $guestCart->items->isEmpty()) {
            return;
        }

        // Gộp sản phẩm trùng, rồi xóa giỏ khách để không giữ lại dữ liệu đã chuyển.
        DB::transaction(function () use ($guestCart) {
            $userCart = Cart::firstOrCreate(
                ['user_id' => Auth::id()],
                ['currency' => 'VND']
            );

            foreach ($guestCart->items as $gItem) {
                $existing = CartItem::where('cart_id', $userCart->id)
                    ->where('product_id', $gItem->product_id)
                    ->where('variant_id', $gItem->variant_id)
                    ->first();

                if ($existing) {
                    $existing->quantity += $gItem->quantity;
                    $existing->unit_price = $gItem->unit_price;
                    $existing->save();
                } else {
                    $gItem->cart_id = $userCart->id;
                    $gItem->save();
                }
            }

            $guestCart->items()->delete();
            $guestCart->delete();
        });

        session()->forget('guest_cart_token');
    }

    public function refreshPrices(Cart $cart): void
    {
        $cart->load('items.product', 'items.variant');
        foreach ($cart->items as $item) {
            $newPrice = $item->variant?->final_price ?? $item->product?->final_price;
            if ($newPrice !== null && (float) $item->unit_price !== (float) $newPrice) {
                $item->unit_price = $newPrice;
                $item->save();
            }
        }
    }
}
