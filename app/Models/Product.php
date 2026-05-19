<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'shop_id',
        'title',
        'vendor',
        'product_type',
        'handle',
        'status',
        'tags',
        'image',
        'images',
        'description',
        'variants',
        'external_product_id',
        'source_type',
        'cost',
    ];

    protected $casts = [
        'tags' => 'array',
        'images' => 'array',
        'variants' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'total_stock',
        'min_price',
        'max_price',
        'default_variant',
        'type',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->handle)) {
                $product->handle = Str::slug($product->title);
            }
        });
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function getTagsStringAttribute(): string
    {
        return $this->tags ? implode(', ', $this->tags) : '';
    }

    public function setTagsStringAttribute($value): void
    {
        $this->tags = array_map('trim', explode(',', $value));
    }

    public function getTypeAttribute(): ?string
    {
        return $this->product_type;
    }

    public function getTotalStockAttribute(): int
    {
        if (!$this->variants || !is_array($this->variants)) {
            return 0;
        }

        return array_sum(array_map(function ($v) {
            return intval($v['stock'] ?? 0);
        }, $this->variants));
    }

    public function getMinPriceAttribute(): float
    {
        if (!$this->variants || !is_array($this->variants) || empty($this->variants)) {
            return 0;
        }

        $prices = array_filter(array_map(function ($v) {
            return floatval($v['price'] ?? 0);
        }, $this->variants));

        return $prices ? min($prices) : 0;
    }

    public function getMaxPriceAttribute(): float
    {
        if (!$this->variants || !is_array($this->variants) || empty($this->variants)) {
            return 0;
        }

        $prices = array_filter(array_map(function ($v) {
            return floatval($v['price'] ?? 0);
        }, $this->variants));

        return $prices ? max($prices) : 0;
    }

    public function getCostAttribute(): float
    {
        if ($this->attributes['cost'] ?? null) {
            return floatval($this->attributes['cost']);
        }

        if (!$this->variants || !is_array($this->variants) || empty($this->variants)) {
            return 0;
        }

        $costs = array_filter(array_map(function ($v) {
            return floatval($v['cost'] ?? 0);
        }, $this->variants));

        if (empty($costs)) {
            return 0;
        }

        return array_sum($costs) / count($costs);
    }

    public function getDefaultVariantAttribute(): array
    {
        if ($this->variants && count($this->variants) > 0) {
            return $this->variants[0];
        }

        return [
            'title' => 'Default Title',
            'price' => 0,
            'compare_at_price' => null,
            'sku' => null,
            'cost' => null,
            'stock' => 0,
        ];
    }

    public function getInStockAttribute(): bool
    {
        return $this->total_stock > 0;
    }

    public function getPriceRangeAttribute(): string
    {
        if ($this->min_price === $this->max_price) {
            return '$' . number_format($this->min_price, 2);
        }

        return '$' . number_format($this->min_price, 2) . ' - $' . number_format($this->max_price, 2);
    }
}
