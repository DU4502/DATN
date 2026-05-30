<?php

namespace App\Models;

use App\Support\ProductCatalog;
use App\Support\ProductImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'image',
        'gallery_images',
        'price',
        'description',
        'stock',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'gallery_images' => 'array',
        'price' => 'decimal:2',
        'status' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Product $product) {
            $product->loadMissing('category');
            $codes = ProductCatalog::codesFor(
                $product->name,
                $product->category?->name,
            );

            if (Schema::hasColumn('products', 'sku')) {
                $product->sku ??= $codes['sku'];
            }

            if (Schema::hasColumn('products', 'slug')) {
                $product->slug ??= $codes['slug'];
            }

            if (empty($product->description) || ProductCatalog::isPlaceholderDescription($product->description)) {
                $product->description = $codes['description'];
            }
        });
    }

    public function getDisplayDescriptionAttribute(): string
    {
        if (! empty($this->description) && ! ProductCatalog::isPlaceholderDescription($this->description)) {
            return $this->description;
        }

        return ProductCatalog::descriptionFor($this->name, $this->category?->name);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the category that owns the product
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all order items for the product
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all reviews for the product
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get average rating for the product
     */
    public function averageRating()
    {
        return $this->reviews()->avg('rating');
    }

    public function getImageUrlAttribute(): string
    {
        return ProductImage::resolve(
            $this->image,
            $this->category?->name,
            $this->id,
        );
    }

    public function getGalleryImagesAttribute(): array
    {
        $rawGallery = $this->attributes['gallery_images'] ?? null;
        $storedGallery = is_string($rawGallery) ? json_decode($rawGallery, true) : $rawGallery;
        $storedGallery = is_array($storedGallery) ? $storedGallery : [];

        $manualImages = collect($storedGallery)
            ->filter()
            ->map(fn ($image) => ProductImage::resolve(
                (string) $image,
                $this->category?->name,
                $this->id,
                1000,
            ))
            ->all();

        $generatedImages = ProductImage::gallery(
            $this->image,
            $this->category?->name,
            $this->id,
            1000,
        );

        $mainImage = $generatedImages[0] ?? $this->image_url;

        return collect([$mainImage])
            ->merge($manualImages)
            ->merge($generatedImages)
            ->unique()
            ->values()
            ->all();
    }
}
