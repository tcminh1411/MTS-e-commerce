<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductVariantService
{
    public function ensureDefaultForSimpleProduct(Product $product): ProductVariant
    {
        return DB::transaction(function () use ($product): ProductVariant {
            $product = Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $defaultVariant = $product->variants()
                ->where('is_default', true)
                ->first();

            if ($defaultVariant) {
                return $defaultVariant;
            }

            $defaultVariant = $product->variants()->first();

            if ($defaultVariant) {
                $product->variants()->update(['is_default' => false]);
                $defaultVariant->update(['is_default' => true]);

                return $defaultVariant->fresh();
            }

            $sku = $product->sku;
            if (! $sku || ProductVariant::withTrashed()->where('sku', $sku)->exists()) {
                $sku = 'P-'.$product->id.'-DEFAULT';
            }

            while (ProductVariant::withTrashed()->where('sku', $sku)->exists()) {
                $sku = 'P-'.$product->id.'-'.Str::upper(Str::random(8));
            }

            return $product->variants()->create([
                'sku' => $sku,
                'name' => 'Mặc định',
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'cost_price' => $product->cost_price,
                'weight_gram' => $product->weight_gram,
                'is_default' => true,
                'stock' => 0,
                'status' => 'active',
                'sort_order' => 0,
            ]);
        });
    }
}
