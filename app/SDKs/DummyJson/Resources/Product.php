<?php


namespace App\SDKs\DummyJson\Resources;

use App\SDKs\DummyJson\Data\ProductData;
use App\SDKs\DummyJson\Http\Client;
use App\SDKs\DummyJson\Http\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;

class Product
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get all products
     *
     * @param array $params Additional query parameters
     * @return Collection<ProductData>
     * @throws ConnectionException
     */
    public function all(array $params = []): Collection
    {
        $response = $this->client->get('/products', $params);
        return $response->items()->map(fn($item) => ProductData::from($item));
    }

    /**
     * Find a product by ID
     *
     * @param int $id Product ID
     * @return ProductData|null
     * @throws ConnectionException
     */
    public function find(int $id): ?ProductData
    {
        $response = $this->client->get("/products/$id");

        if (!$response->successful()) {
            return null;
        }

        return ProductData::from($response->item());
    }

    /**
     * Get paginated products
     *
     * @param int $limit Number of products per page
     * @param int $skip Number of products to skip
     * @return Response
     * @throws ConnectionException
     */
    public function paginate(int $limit = 30, int $skip = 0): Response
    {
        return $this->client->get('/products', [
            'limit' => $limit,
            'skip' => $skip,
        ]);
    }

    /**
     * Search products by query
     *
     * @param string $query Search term
     * @param int $limit Number of results
     * @param int $skip Number of results to skip
     * @return Response
     * @throws ConnectionException
     */
    public function search(string $query, int $limit = 30, int $skip = 0): Response
    {
        return $this->client->get('/products/search', [
            'q' => $query,
            'limit' => $limit,
            'skip' => $skip,
        ]);
    }

    /**
     * Get all available categories
     *
     * @return Collection<string>
     * @throws ConnectionException
     */
    public function categories(): Collection
    {
        $response = $this->client->get('/products/categories');
        return collect($response->data());
    }

    /**
     * Get products by category
     *
     * @param string $category Category name
     * @param int $limit Number of products
     * @param int $skip Number of products to skip
     * @return Response
     * @throws ConnectionException
     */
    public function byCategory(string $category, int $limit = 30, int $skip = 0): Response
    {
        return $this->client->get("/products/category/$category", [
            'limit' => $limit,
            'skip' => $skip,
        ]);
    }

    /**
     * Sort products by field
     *
     * @param string $sortBy Field to sort by (title, price, rating, etc.)
     * @param string $order Sort order (asc, desc)
     * @param int $limit Number of products
     * @param int $skip Number of products to skip
     * @return Response
     * @throws ConnectionException
     */
    public function sortBy(string $sortBy, string $order = 'asc', int $limit = 30, int $skip = 0): Response
    {
        return $this->client->get('/products', [
            'sortBy' => $sortBy,
            'order' => $order,
            'limit' => $limit,
            'skip' => $skip,
        ]);
    }

    /**
     * Select specific fields from products
     *
     * @param array $fields Fields to select
     * @param int $limit Number of products
     * @param int $skip Number of products to skip
     * @return Response
     * @throws ConnectionException
     */
    public function select(array $fields, int $limit = 30, int $skip = 0): Response
    {
        return $this->client->get('/products', [
            'limit' => $limit,
            'skip' => $skip,
            'select' => implode(',', $fields),
        ]);
    }

    /**
     * Create a new product
     *
     * @param array $data Product data
     * @return Response
     * @throws ConnectionException
     */
    public function create(array $data): Response
    {
        return $this->client->post('/products/add', $data);
    }

    /**
     * Update product
     *
     * @param int $id Product ID
     * @param array $data Updated product data
     * @return Response
     * @throws ConnectionException
     */
    public function update(int $id, array $data): Response
    {
        return $this->client->put("/products/$id", $data);
    }

    /**
     * Delete product
     *
     * @param int $id Product ID
     * @return Response
     * @throws ConnectionException
     */
    public function delete(int $id): Response
    {
        return $this->client->delete("/products/$id");
    }

    /**
     * Filter products with advanced options
     *
     * @param array $filters Filter options
     * @return Response
     * @throws ConnectionException
     */
    public function filter(array $filters = []): Response
    {
        $params = $this->buildFilterParams($filters);
        return $this->client->get('/products', $params);
    }

    /**
     * Get discounted products
     *
     * @param int $limit Number of products
     * @return Collection<ProductData>
     * @throws ConnectionException
     */
    public function discounted(int $limit = 30): Collection
    {
        return $this->all(['limit' => $limit])
            ->filter(fn(ProductData $product) => $product->discountPercentage > 0);
    }

    /**
     * Get products by price range
     *
     * @param float $min Minimum price
     * @param float $max Maximum price
     * @param int $limit Number of products
     * @return Collection<ProductData>
     * @throws ConnectionException
     */
    public function byPriceRange(float $min, float $max, int $limit = 30): Collection
    {
        return $this->all(['limit' => $limit])
            ->filter(fn(ProductData $product) => $product->price >= $min && $product->price <= $max
            );
    }

    /**
     * Get high-rated products
     *
     * @param float $minRating Minimum rating
     * @param int $limit Number of products
     * @return Collection<ProductData>
     * @throws ConnectionException
     */
    public function highRated(float $minRating = 4.0, int $limit = 30): Collection
    {
        return $this->all(['limit' => $limit])
            ->filter(fn(ProductData $product) => $product->rating >= $minRating);
    }

    /**
     * Get products with low stock
     *
     * @param int $threshold Stock threshold
     * @param int $limit Number of products
     * @return Collection<ProductData>
     * @throws ConnectionException
     */
    public function lowStock(int $threshold = 10, int $limit = 30): Collection
    {
        return $this->all(['limit' => $limit])
            ->filter(fn(ProductData $product) => $product->stock <= $threshold);
    }

    /**
     * Get featured products (high-rating and discounted)
     *
     * @param int $limit Number of products
     * @return Collection<ProductData>
     * @throws ConnectionException
     */
    public function featured(int $limit = 20): Collection
    {
        return $this->all(['limit' => 100])
            ->filter(fn(ProductData $product) => $product->rating >= 4.0 && $product->discountPercentage > 0
            )
            ->take($limit);
    }

    /**
     * Get trending products (highest rated)
     *
     * @param int $limit Number of products
     * @return Collection<ProductData>
     * @throws ConnectionException
     */
    public function trending(int $limit = 20): Collection
    {
        return $this->all(['limit' => 100])
            ->sortByDesc(fn(ProductData $product) => $product->rating)
            ->take($limit);
    }

    /**
     * Get products on sale
     *
     * @param float $minDiscount Minimum discount percentage
     * @param int $limit Number of products
     * @return Collection<ProductData>
     * @throws ConnectionException
     */
    public function onSale(float $minDiscount = 5.0, int $limit = 30): Collection
    {
        return $this->all(['limit' => $limit])
            ->filter(fn(ProductData $product) => $product->discountPercentage >= $minDiscount);
    }

    /**
     * Get products by brand
     *
     * @param string $brand Brand name
     * @param int $limit Number of products
     * @return Collection<ProductData>
     * @throws ConnectionException
     */
    public function byBrand(string $brand, int $limit = 30): Collection
    {
        return $this->all(['limit' => $limit])
            ->filter(fn(ProductData $product) => strtolower($product->brand ?? '') === strtolower($brand)
            );
    }

    /**
     * Get products by tags
     *
     * @param array $tags Array of tags
     * @param bool $matchAll Whether to match all tags or any tag
     * @param int $limit Number of products
     * @return Collection<ProductData>
     * @throws ConnectionException
     */
    public function byTags(array $tags, bool $matchAll = false, int $limit = 30): Collection
    {
        return $this->all(['limit' => $limit])
            ->filter(function (ProductData $product) use ($tags, $matchAll) {
                $productTags = collect($product->tags);

                if ($matchAll) {
                    return collect($tags)->every(fn($tag) => $productTags->contains($tag));
                }

                return collect($tags)->some(fn($tag) => $productTags->contains($tag));
            });
    }

    /**
     * Get random products
     *
     * @param int $count Number of random products
     * @return Collection<ProductData>
     * @throws ConnectionException
     */
    public function random(int $count = 10): Collection
    {
        return $this->all(['limit' => 100])->random($count);
    }

    /**
     * Build filter parameters
     *
     * @param array $filters
     * @return array
     */
    protected function buildFilterParams(array $filters): array
    {
        return array_filter([
            'limit' => $filters['limit'] ?? 30,
            'skip' => $filters['skip'] ?? 0,
            'select' => isset($filters['select']) ? implode(',', $filters['select']) : null,
            'sortBy' => $filters['sort_by'] ?? null,
            'order' => $filters['order'] ?? 'asc',
        ], fn($value) => $value !== null && $value !== '');
    }
}
