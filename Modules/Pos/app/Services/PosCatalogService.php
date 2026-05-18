<?php

namespace Modules\Pos\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Business\Models\Business;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCategory;
use Modules\Product\Models\ProductStockLayer;
use Modules\Product\Services\ProductStockLayerService;

class PosCatalogService
{
    public function __construct(
        private readonly ProductStockLayerService $stockLayers,
    ) {
    }

    /**
     * @return EloquentCollection<int, Product>
     */
    public function sellableProducts(Business $business, ?string $search = null, ?int $categoryId = null): EloquentCollection
    {
        $query = $business->products()
            ->where('is_active', true)
            ->where('is_bundle', false)
            ->with(['productUnit', 'imageFile', 'categories'])
            ->orderBy('name');

        if ($categoryId !== null && $categoryId > 0) {
            $query->whereHas('categories', fn ($builder) => $builder->whereKey($categoryId));
        }

        $term = trim((string) $search);
        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            });
        }

        return $query->get();
    }

    /**
     * @return Collection<int, ProductCategory>
     */
    public function posCategories(Business $business): Collection
    {
        return $business->productCategories()
            ->where('is_active', true)
            ->whereHas('products', fn ($query) => $query
                ->where('is_active', true)
                ->where('is_bundle', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function productCardsForPos(Business $business, ?string $search = null, ?int $categoryId = null): array
    {
        return $this->sellableProducts($business, $search, $categoryId)
            ->map(function (Product $product) {
                $meta = $this->posMetaForProduct($product);

                return [
                    'id' => (int) $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->productUnit?->name ?: $product->unit,
                    'image_url' => $product->imageUrl(),
                    'unit_sell_price' => $meta['unit_sell_price'],
                    'stock_quantity' => $meta['stock_quantity'],
                    'has_layers' => $meta['has_layers'],
                    'category_ids' => $product->categories->pluck('id')->map(fn ($id) => (int) $id)->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function productCardForProduct(Product $product): array
    {
        $product->loadMissing(['productUnit', 'imageFile', 'categories']);
        $meta = $this->posMetaForProduct($product);

        return [
            'id' => (int) $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'unit' => $product->productUnit?->name ?: $product->unit,
            'image_url' => $product->imageUrl(),
            'unit_sell_price' => $meta['unit_sell_price'],
            'stock_quantity' => $meta['stock_quantity'],
            'has_layers' => $meta['has_layers'],
            'category_ids' => $product->categories->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    public function findSellableProductBySku(Business $business, string $sku): ?Product
    {
        $term = trim($sku);
        if ($term === '') {
            return null;
        }

        return $business->products()
            ->where('is_active', true)
            ->where('is_bundle', false)
            ->where('sku', $term)
            ->first();
    }

    /**
     * @return array{
     *     unit_sell_price: ?float,
     *     stock_quantity: float,
     *     has_layers: bool,
     * }
     */
    public function posMetaForProduct(Product $product): array
    {
        $product->loadMissing('business');
        $layer = $this->nextFifoLayer($product);

        $unitSell = $layer !== null
            ? ($layer->selling_unit_price !== null ? (float) $layer->selling_unit_price : null)
            : ($product->unit_price !== null ? (float) $product->unit_price : null);

        if ($unitSell === null && $layer !== null) {
            $unitSell = $this->stockLayers->defaultSellingUnitPrice(
                $product->business,
                $product,
                (float) $layer->unit_cost,
            );
        }

        return [
            'unit_sell_price' => $unitSell,
            'stock_quantity' => round((float) $product->stock_quantity, 3),
            'has_layers' => $layer !== null,
        ];
    }

    public function nextFifoLayer(Product $product): ?ProductStockLayer
    {
        return ProductStockLayer::query()
            ->where('product_id', $product->id)
            ->where('business_id', $product->business_id)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->first();
    }
}
