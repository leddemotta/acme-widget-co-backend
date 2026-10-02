<?php

declare(strict_types=1);

namespace Acme;

use InvalidArgumentException;

/**
 * -----------------------------------------------------------------------------
 * 1. Product Model (Value Object)
 * -----------------------------------------------------------------------------
 * Represents an immutable product in the Acme Widget Co catalog.
 * Leverages native PHP 8.2+ features (readonly class and constructor property promotion).
 */
final readonly class Product
{
    public function __construct(
        public string $code,
        public string $name,
        public float $price
    ) {
    }

    /**
     * @return array{code: string, name: string, price: float}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'price' => $this->price,
        ];
    }
}

/**
 * -----------------------------------------------------------------------------
 * 2. Product Catalog
 * -----------------------------------------------------------------------------
 * Manages in-memory product inventory.
 */
class ProductCatalog
{
    /** @var array<string, Product> */
    private array $products = [];

    /**
     * @param array<Product> $initialProducts
     */
    public function __construct(array $initialProducts = [])
    {
        foreach ($initialProducts as $product) {
            $this->add($product);
        }
    }

    /**
     * Default instance pre-loaded with the 3 official Acme Widget Co products.
     */
    public static function createDefault(): self
    {
        return new self([
            new Product('R01', 'Red Widget', 32.95),
            new Product('G01', 'Green Widget', 24.95),
            new Product('B01', 'Blue Widget', 7.95),
        ]);
    }

    public function add(Product $product): void
    {
        $this->products[$product->code] = $product;
    }

    public function get(string $code): ?Product
    {
        return $this->products[strtoupper(trim($code))] ?? null;
    }

    public function has(string $code): bool
    {
        return isset($this->products[strtoupper(trim($code))]);
    }

    /**
     * @return array<string, Product>
     */
    public function getAll(): array
    {
        return $this->products;
    }
}

/**
 * -----------------------------------------------------------------------------
 * 3. Delivery Fee Calculator
 * -----------------------------------------------------------------------------
 * Calculates tiered delivery charges based on net subtotal:
 * - Under $50: $4.95
 * - Under $90 ($50.00 to $89.99): $2.95
 * - $90 or more: Free shipping ($0.00)
 */
class DeliveryCalculator
{
    /**
     * @param float $netSubtotal Subtotal after applying discounts
     */
    public static function calculate(float $netSubtotal): float
    {
        if ($netSubtotal <= 0.0) {
            return 0.0;
        }

        return match (true) {
            $netSubtotal >= 90.00 => 0.00,
            $netSubtotal >= 50.00 => 2.95,
            default               => 4.95,
        };
    }
}

/**
 * -----------------------------------------------------------------------------
 * 4. Offer Calculator
 * -----------------------------------------------------------------------------
 * Applies official promotional rule:
 * "Buy one red widget, get the second half price"
 * For every pair of Red Widgets (R01), the 2nd receives a 50% discount ($16.48 commercially rounded).
 */
class OfferCalculator
{
    /**
     * @param Product[] $items
     */
    public static function calculate(array $items): float
    {
        $r01Count = 0;
        $r01Price = 0.0;

        foreach ($items as $product) {
            if ($product->code === 'R01') {
                $r01Count++;
                $r01Price = $product->price;
            }
        }

        $discountPairs = intdiv($r01Count, 2);
        if ($discountPairs <= 0) {
            return 0.0;
        }

        // Commercial rounding: 32.95 / 2 = 16.475 -> 16.48
        $discountPerSecondWidget = round($r01Price / 2.0, 2);
        return round($discountPairs * $discountPerSecondWidget, 2);
    }
}

/**
 * -----------------------------------------------------------------------------
 * 5. Shopping Basket Domain Model
 * -----------------------------------------------------------------------------
 * Primary shopping basket, managing items and computing totals
 * using Dependency Injection (Callables).
 */
class Basket
{
    /** @var Product[] */
    private array $items = [];

    /**
     * @param ProductCatalog $catalog
     * @param callable(float): float $deliveryCalculator
     * @param callable(Product[]): float $offerCalculator
     */
    public function __construct(
        private readonly ProductCatalog $catalog,
        private readonly mixed $deliveryCalculator = [DeliveryCalculator::class, 'calculate'],
        private readonly mixed $offerCalculator = [OfferCalculator::class, 'calculate']
    ) {
    }

    /**
     * Creates default Acme Widget Co basket configured with official catalog and rules.
     */
    public static function createDefault(): self
    {
        return new self(ProductCatalog::createDefault());
    }

    /**
     * Adds a product to the basket by product reference code.
     *
     * @throws InvalidArgumentException if the product code does not exist in the catalog
     */
    public function add(string $productCode): self
    {
        $product = $this->catalog->get($productCode);
        if ($product === null) {
            throw new InvalidArgumentException("Product with code '{$productCode}' does not exist in catalog.");
        }

        $this->items[] = $product;
        return $this;
    }

    /**
     * @return Product[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Calculates gross subtotal (sum of standard prices).
     */
    public function getSubtotal(): float
    {
        $subtotal = 0.0;
        foreach ($this->items as $product) {
            $subtotal += $product->price;
        }
        return round($subtotal, 2);
    }

    /**
     * Calculates total promotional discount.
     */
    public function getTotalDiscount(): float
    {
        if (empty($this->items)) {
            return 0.0;
        }

        return round(($this->offerCalculator)($this->items), 2);
    }

    /**
     * Calculates delivery charge based on net subtotal (post-discount).
     */
    public function getDeliveryCost(): float
    {
        if (empty($this->items)) {
            return 0.0;
        }

        $netSubtotal = max(0.0, $this->getSubtotal() - $this->getTotalDiscount());
        return round(($this->deliveryCalculator)($netSubtotal), 2);
    }

    /**
     * Returns final order total to be charged.
     */
    public function total(): float
    {
        if (empty($this->items)) {
            return 0.0;
        }

        $netSubtotal = max(0.0, $this->getSubtotal() - $this->getTotalDiscount());
        $delivery = $this->getDeliveryCost();

        return round($netSubtotal + $delivery, 2);
    }

    /**
     * Returns a comprehensive breakdown for receipts, UI, and APIs.
     *
     * @return array{
     *     items: list<array{code: string, name: string, price: float}>,
     *     subtotal: float,
     *     discount: float,
     *     delivery: float,
     *     total: float
     * }
     */
    public function getBreakdown(): array
    {
        if (empty($this->items)) {
            return [
                'items' => [],
                'subtotal' => 0.0,
                'discount' => 0.0,
                'delivery' => 0.0,
                'total' => 0.0,
            ];
        }

        $subtotal = $this->getSubtotal();
        $discount = $this->getTotalDiscount();
        $delivery = $this->getDeliveryCost();
        $total = $this->total();

        return [
            'items' => array_map(fn(Product $p) => $p->toArray(), $this->items),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'delivery' => $delivery,
            'total' => $total,
        ];
    }

    /**
     * Clears all items from the basket.
     */
    public function clear(): void
    {
        $this->items = [];
    }
}
