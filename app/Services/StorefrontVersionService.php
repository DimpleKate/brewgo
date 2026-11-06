<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\CarouselSlide;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Promotion;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;

class StorefrontVersionService
{
    /**
     * Build a lightweight fingerprint of storefront content so open pages can
     * detect administrator changes without reloading the whole catalogue.
     */
    public function current(): string
    {
        $sources = [
            'settings' => $this->fingerprint(SiteSetting::class),
            'products' => $this->fingerprint(Product::class),
            'options' => $this->fingerprint(ProductOption::class),
            'addons' => $this->fingerprint(Addon::class),
            'categories' => $this->fingerprint(Category::class),
            'carousel' => $this->fingerprint(CarouselSlide::class),
            'promotions' => $this->fingerprint(Promotion::class),
        ];

        return substr(hash('sha256', (string) json_encode($sources)), 0, 20);
    }

    /**
     * @param  class-string<Model>  $model
     * @return array{count:int, latest:string|null}
     */
    private function fingerprint(string $model): array
    {
        return [
            'count' => (int) $model::query()->count(),
            'latest' => $model::query()->max('updated_at'),
        ];
    }
}
