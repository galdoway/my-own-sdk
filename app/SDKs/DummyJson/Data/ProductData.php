<?php

namespace App\SDKs\DummyJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ProductData extends Data
{
    public function __construct(
        public readonly int $id,

        #[Required]
        public readonly string $title,

        #[Required]
        public readonly string $description,

        public readonly string $category,

        #[Required, Numeric, Min(0)]
        public readonly float $price,

        #[Numeric, Min(0), Max(100)]
        #[MapName('discountPercentage')]
        public readonly float $discountPercentage,

        #[Numeric, Min(0), Max(5)]
        public readonly float $rating,

        #[Numeric, Min(0)]
        public readonly int $stock,

        public readonly array $tags,

        public readonly ?string $brand,

        public readonly string $sku,

        public readonly float $weight,

        public readonly DimensionsData $dimensions,

        #[MapName('warrantyInformation')]
        public readonly string $warrantyInformation,

        #[MapName('shippingInformation')]
        public readonly string $shippingInformation,

        #[MapName('availabilityStatus')]
        public readonly string $availabilityStatus,

        public readonly array $reviews,

        #[MapName('returnPolicy')]
        public readonly string $returnPolicy,

        #[MapName('minimumOrderQuantity')]
        public readonly int $minimumOrderQuantity,

        public readonly MetaData $meta,

        public readonly array $images,

        public readonly string $thumbnail,
    ) {}

    /**
     * Get a formatted price with currency
     */
    public function getFormattedPrice(string $currency = '$'): string
    {
        return $currency . number_format($this->price, 2);
    }

    /**
     * Get discounted price
     */
    public function getDiscountedPrice(): float
    {
        return $this->price - ($this->price * $this->discountPercentage / 100);
    }

    /**
     * Get a formatted discounted price
     */
    public function getFormattedDiscountedPrice(string $currency = '$'): string
    {
        return $currency . number_format($this->getDiscountedPrice(), 2);
    }

    /**
     * Get saving amount
     */
    public function getSavings(): float
    {
        return $this->price * $this->discountPercentage / 100;
    }

    /**
     * Get formatted savings
     */
    public function getFormattedSavings(string $currency = '$'): string
    {
        return $currency . number_format($this->getSavings(), 2);
    }

    /**
     * Check if a product is in stock
     */
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Check if a product has low stock
     */
    public function hasLowStock(int $threshold = 10): bool
    {
        return $this->stock <= $threshold && $this->stock > 0;
    }

    /**
     * Check if a product is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->stock === 0;
    }

    /**
     * Check if a product is discounted
     */
    public function isDiscounted(): bool
    {
        return $this->discountPercentage > 0;
    }

    /**
     * Check if product has high discount
     */
    public function hasHighDiscount(float $threshold = 20.0): bool
    {
        return $this->discountPercentage >= $threshold;
    }

    /**
     * Get rating as percentage
     */
    public function getRatingPercentage(): float
    {
        return ($this->rating / 5) * 100;
    }

    /**
     * Get rating as stars (out of 5)
     */
    public function getStarRating(): string
    {
        $fullStars = floor($this->rating);
        $halfStar = ($this->rating - $fullStars) >= 0.5 ? 1 : 0;
        $emptyStars = 5 - $fullStars - $halfStar;

        return str_repeat('★', $fullStars) .
            str_repeat('☆', $halfStar) .
            str_repeat('☆', $emptyStars);
    }

    /**
     * Check if the product is highly rated
     */
    public function isHighlyRated(float $threshold = 4.0): bool
    {
        return $this->rating >= $threshold;
    }

    /**
     * Get average review rating
     */
    public function getAverageReviewRating(): float
    {
        if (empty($this->reviews)) {
            return 0;
        }

        $total = array_sum(array_column($this->reviews, 'rating'));
        return $total / count($this->reviews);
    }

    /**
     * Get a review count
     */
    public function getReviewCount(): int
    {
        return count($this->reviews);
    }

    /**
     * Check if a product has reviews
     */
    public function hasReviews(): bool
    {
        return !empty($this->reviews);
    }

    /**
     * Get latest reviews
     */
    public function getLatestReviews(int $limit = 5): array
    {
        return array_slice($this->reviews, 0, $limit);
    }

    /**
     * Check if a product can be ordered with a given quantity
     */
    public function canOrder(int $quantity): bool
    {
        return $quantity >= $this->minimumOrderQuantity && $quantity <= $this->stock;
    }

    /**
     * Get maximum orderable quantity
     */
    public function getMaxOrderableQuantity(): int
    {
        return $this->stock;
    }

    /**
     * Check if quantity meets minimum order requirement
     */
    public function meetsMinimumOrder(int $quantity): bool
    {
        return $quantity >= $this->minimumOrderQuantity;
    }

    /**
     * Get product weight in different units
     */
    public function getWeightInKg(): float
    {
        return $this->weight; // Assuming weight is in kg
    }

    public function getWeightInLbs(): float
    {
        return $this->weight * 2.20462;
    }

    public function getWeightInOz(): float
    {
        return $this->weight * 35.274;
    }

    /**
     * Get product volume in cubic units
     */
    public function getVolume(): float
    {
        return $this->dimensions->width *
            $this->dimensions->height *
            $this->dimensions->depth;
    }

    /**
     * Get product volume in different units
     */
    public function getVolumeInCubicCm(): float
    {
        return $this->getVolume(); // Assuming dimensions are in cm
    }

    public function getVolumeInCubicInches(): float
    {
        return $this->getVolume() * 0.0610237;
    }

    /**
     * Check if product is available
     */
    public function isAvailable(): bool
    {
        return strtolower($this->availabilityStatus) === 'in stock';
    }

    /**
     * Get availability status display
     */
    public function getAvailabilityDisplay(): string
    {
        return match (strtolower($this->availabilityStatus)) {
            'in stock' => 'Available',
            'low stock' => 'Limited Stock',
            'out of stock' => 'Out of Stock',
            default => ucfirst($this->availabilityStatus),
        };
    }

    /**
     * Check if product has warranty
     */
    public function hasWarranty(): bool
    {
        return !empty($this->warrantyInformation) &&
            strtolower($this->warrantyInformation) !== 'no warranty';
    }

    /**
     * Check if product is returnable
     */
    public function isReturnable(): bool
    {
        return !empty($this->returnPolicy) &&
            !str_contains(strtolower($this->returnPolicy), 'no returns');
    }

    /**
     * Get product age in days (from creation date)
     */
    public function getAgeInDays(): int
    {
        $createdAt = Carbon::parse($this->meta->createdAt);
        return $createdAt->diffInDays(now());
    }

    /**
     * Check if product is new (created within last 30 days)
     */
    public function isNew(int $daysThreshold = 30): bool
    {
        return $this->getAgeInDays() <= $daysThreshold;
    }

    /**
     * Get product URL slug
     */
    public function getSlug(): string
    {
        return str($this->title)
            ->lower()
            ->replace(' ', '-')
            ->replace('/', '-')
            ->replaceMatches('/[^a-z0-9\-]/', '')
            ->replaceMatches('/-+/', '-')
            ->trim('-')
            ->toString();
    }

    /**
     * Get SEO-friendly title
     */
    public function getSeoTitle(): string
    {
        $baseTitle = $this->title;

        if ($this->brand) {
            $baseTitle = "{$this->brand} {$baseTitle}";
        }

        if ($this->isDiscounted()) {
            $baseTitle .= " - {$this->discountPercentage}% Off";
        }

        return $baseTitle;
    }

    /**
     * Get product summary for display
     */
    public function getSummary(int $length = 150): string
    {
        return str($this->description)->limit($length)->toString();
    }

    /**
     * Get product tags as string
     */
    public function getTagsAsString(string $separator = ', '): string
    {
        return implode($separator, $this->tags);
    }

    /**
     * Check if product has specific tag
     */
    public function hasTag(string $tag): bool
    {
        return in_array(strtolower($tag), array_map('strtolower', $this->tags));
    }

    /**
     * Get first image URL
     */
    public function getFirstImage(): string
    {
        return !empty($this->images) ? $this->images[0] : $this->thumbnail;
    }

    /**
     * Get all images including thumbnail
     */
    public function getAllImages(): array
    {
        $images = $this->images;

        if (!in_array($this->thumbnail, $images)) {
            array_unshift($images, $this->thumbnail);
        }

        return $images;
    }

    /**
     * Convert to array for API responses
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'price' => $this->price,
            'formatted_price' => $this->getFormattedPrice(),
            'discount_percentage' => $this->discountPercentage,
            'discounted_price' => $this->getDiscountedPrice(),
            'formatted_discounted_price' => $this->getFormattedDiscountedPrice(),
            'savings' => $this->getSavings(),
            'rating' => $this->rating,
            'rating_percentage' => $this->getRatingPercentage(),
            'star_rating' => $this->getStarRating(),
            'stock' => $this->stock,
            'is_in_stock' => $this->isInStock(),
            'availability_status' => $this->getAvailabilityDisplay(),
            'brand' => $this->brand,
            'tags' => $this->tags,
            'images' => $this->getAllImages(),
            'thumbnail' => $this->thumbnail,
            'is_new' => $this->isNew(),
            'slug' => $this->getSlug(),
        ];
    }
}

class DimensionsData extends Data
{
    public function __construct(
        public readonly float $width,
        public readonly float $height,
        public readonly float $depth,
    ) {}

    /**
     * Get dimensions as string
     */
    public function toString(string $unit = 'cm'): string
    {
        return "{$this->width} x {$this->height} x {$this->depth} {$unit}";
    }

    /**
     * Get volume
     */
    public function getVolume(): float
    {
        return $this->width * $this->height * $this->depth;
    }
}

class MetaData extends Data
{
    public function __construct(
        #[MapName('createdAt')]
        public readonly string $createdAt,

        #[MapName('updatedAt')]
        public readonly string $updatedAt,

        public readonly string $barcode,

        #[MapName('qrCode')]
        public readonly string $qrCode,
    ) {}

    /**
     * Get created date as a Carbon instance
     */
    public function getCreatedAt(): Carbon
    {
        return Carbon::parse($this->createdAt);
    }

    /**
     * Get updated date as Carbon instance
     */
    public function getUpdatedAt(): Carbon
    {
        return Carbon::parse($this->updatedAt);
    }

    /**
     * Check if the product was recently updated
     */
    public function wasRecentlyUpdated(int $daysThreshold = 7): bool
    {
        return $this->getUpdatedAt()->diffInDays(now()) <= $daysThreshold;
    }
}
