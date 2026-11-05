<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductVariantService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('products:ensure-simple-variants')]
#[Description('Tạo biến thể mặc định cho sản phẩm đơn giản chưa có biến thể')]
class EnsureSimpleProductVariants extends Command
{
    public function handle(ProductVariantService $productVariantService): int
    {
        $processed = 0;

        Product::query()
            ->where('product_type', 'simple')
            ->whereDoesntHave('variants', fn ($query) => $query->where('is_default', true))
            ->chunkById(100, function ($products) use ($productVariantService, &$processed): void {
                foreach ($products as $product) {
                    $productVariantService->ensureDefaultForSimpleProduct($product);
                    $processed++;
                }
            });

        $this->info("Đã kiểm tra {$processed} sản phẩm đơn giản.");

        return self::SUCCESS;
    }
}
