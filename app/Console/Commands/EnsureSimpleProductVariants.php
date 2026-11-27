<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductVariantService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('products:ensure-simple-variants')]
#[Description('Tạo biến thể mặc định cho sản phẩm đơn giản hoặc không có thuộc tính biến thể')]
class EnsureSimpleProductVariants extends Command
{
    public function handle(ProductVariantService $productVariantService): int
    {
        $processed = 0;

        Product::query()
            ->where(function ($query): void {
                $query->where(function ($simpleQuery): void {
                    $simpleQuery->where('product_type', 'simple')
                        ->whereDoesntHave('variants', fn ($variantQuery) => $variantQuery
                            ->where('is_default', true));
                })->orWhere(function ($variableQuery): void {
                    $variableQuery->where('product_type', 'variable')
                        ->whereDoesntHave('productAttributes', fn ($attributeQuery) => $attributeQuery
                            ->where('is_variation', true))
                        ->whereDoesntHave('variants', fn ($variantQuery) => $variantQuery
                            ->where('is_default', true));
                });
            })
            ->chunkById(100, function ($products) use ($productVariantService, &$processed): void {
                foreach ($products as $product) {
                    if ($product->product_type === 'simple' || $product->variants()->count() <= 1) {
                        $product->update(['product_type' => 'simple']);
                    }

                    $productVariantService->ensureDefaultForSimpleProduct($product);
                    $processed++;
                }
            });

        $this->info("Đã kiểm tra {$processed} sản phẩm đơn giản.");

        return self::SUCCESS;
    }
}