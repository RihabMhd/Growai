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
        'default_variant'
    ];

    /**
     * Boot the model.
     */
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
    /**
     * Get the shop that owns the product.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get tags as comma-separated string.
     */
    public function getTagsStringAttribute(): string
    {
        return $this->tags ? implode(', ', $this->tags) : '';
    }

    /**
     * Set tags from comma-separated string.
     */
    public function setTagsStringAttribute($value): void
    {
        $this->tags = array_map('trim', explode(',', $value));
    }

    /**
     * Get total stock from all variants.
     */
    public function getTotalStockAttribute(): int
    {
        if (!$this->variants) {
            return 0;
        }
        
        return array_sum(array_column($this->variants, 'stock'));
    }

    /**
     * Get minimum price from all variants.
     */
    public function getMinPriceAttribute(): float
    {
        if (!$this->variants) {
            return 0;
        }
        
        $prices = array_column($this->variants, 'price');
        return min($prices);
    }

    /**
     * Get maximum price from all variants.
     */
    public function getMaxPriceAttribute(): float
    {
        if (!$this->variants) {
            return 0;
        }
        
        $prices = array_column($this->variants, 'price');
        return max($prices);
    }

    /**
     * Get default variant.
     */
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

    /**
     * Check if product is in stock.
     */
    public function getInStockAttribute(): bool
    {
        return $this->total_stock > 0;
    }

    /**
     * Get price range string.
     */
    public function getPriceRangeAttribute(): string
    {
        if ($this->min_price === $this->max_price) {
            return '$' . number_format($this->min_price, 2);
        }
        
        return '$' . number_format($this->min_price, 2) . ' - $' . number_format($this->max_price, 2);
    }
}