<?php

declare(strict_types=1);

require_once __DIR__ . '/../basket.php';

use Acme\Basket;
use Acme\Product;
use Acme\ProductCatalog;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Configure CORS headers
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');
}

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

try {
    // GET Endpoint: Product catalog and business rules
    if ($method === 'GET') {
        $catalog = ProductCatalog::createDefault();
        $products = array_values(array_map(
            fn(Product $p) => $p->toArray(),
            $catalog->getAll()
        ));

        echo json_encode([
            'status' => 'success',
            'version' => '1.1.8',
            'products' => $products,
            'deliveryRules' => [
                ['description' => 'Orders under $50.00', 'cost' => 4.95, 'threshold' => 50.00],
                ['description' => 'Orders under $90.00', 'cost' => 2.95, 'threshold' => 90.00],
                ['description' => 'Orders of $90.00 or more', 'cost' => 0.00, 'threshold' => null],
            ],
            'specialOffers' => [
                [
                    'code' => 'BUY_ONE_RED_GET_SECOND_HALF_PRICE',
                    'title' => 'Red Widget Special Offer',
                    'description' => 'Buy one red widget (R01), get the second half price!',
                    'targetProduct' => 'R01',
                    'discountRate' => 0.50,
                ],
            ],
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // POST Endpoint: Official basket calculation
    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput ?: '{}', true);

        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid payload. Expected JSON object with an "items" array of product codes.',
            ]);
            exit;
        }

        $basket = Basket::createDefault();
        foreach ($data['items'] as $productCode) {
            if (!is_string($productCode)) {
                continue;
            }
            $basket->add($productCode);
        }

        $breakdown = $basket->getBreakdown();

        echo json_encode([
            'status' => 'success',
            'data' => [
                'items' => $breakdown['items'],
                'subtotal' => $breakdown['subtotal'],
                'discount' => $breakdown['discount'],
                'delivery' => $breakdown['delivery'],
                'total' => $breakdown['total'],
            ],
        ], JSON_PRETTY_PRINT);
        exit;
    }

    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed',
    ]);
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
