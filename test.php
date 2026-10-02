<?php

declare(strict_types=1);

require_once __DIR__ . '/basket.php';

use Acme\Basket;

// Ensure browser displays clean plain text with monospace formatting and exact whitespace
if (!headers_sent()) {
    header('Content-Type: text/plain; charset=utf-8');
}

$isCli = (php_sapi_name() === 'cli');

$passTag = $isCli ? "\033[32m[PASS]\033[0m" : "[PASS]";
$failTag = $isCli ? "\033[31m[FAIL]\033[0m" : "[FAIL]";

echo "\n";
echo "======================================================================\n";
echo "              ACME WIDGET CO - SALES SYSTEM TEST SUITE\n";
echo "======================================================================\n\n";

$officialCases = [
    [
        'label' => 'Test Case 1',
        'items' => ['B01', 'G01'],
        'expected' => 37.85,
    ],
    [
        'label' => 'Test Case 2',
        'items' => ['R01', 'R01'],
        'expected' => 54.37,
    ],
    [
        'label' => 'Test Case 3',
        'items' => ['R01', 'G01'],
        'expected' => 60.85,
    ],
    [
        'label' => 'Test Case 4',
        'items' => ['B01', 'B01', 'R01', 'R01', 'R01'],
        'expected' => 98.27,
    ],
];

$allPassed = true;
$passedCount = 0;
$totalTests = 0;

echo "----------------------------------------------------------------------\n";
echo "  1. OFFICIAL SPECIFICATION TEST CASES\n";
echo "----------------------------------------------------------------------\n\n";

foreach ($officialCases as $case) {
    $totalTests++;
    $basket = Basket::createDefault();
    foreach ($case['items'] as $code) {
        $basket->add($code);
    }

    $total = $basket->total();
    $breakdown = $basket->getBreakdown();
    $passed = (abs($total - $case['expected']) < 0.001);

    if ($passed) {
        $passedCount++;
        $status = $passTag;
    } else {
        $allPassed = false;
        $status = $failTag;
    }

    echo "▶ {$case['label']}: Basket [" . implode(', ', $case['items']) . "] {$status}\n";
    echo "\n";
    echo "    Subtotal:      $" . number_format($breakdown['subtotal'], 2) . "\n";
    echo "    Discount:     -$" . number_format($breakdown['discount'], 2) . "\n";
    echo "    Delivery:      $" . number_format($breakdown['delivery'], 2) . "\n";
    echo "    Actual Total:  $" . number_format($total, 2) . "\n";
    echo "    Expected:      $" . number_format($case['expected'], 2) . "\n";
    echo "\n";
    echo "----------------------------------------------------------------------\n\n";
}

echo "----------------------------------------------------------------------\n";
echo "  2. DEFENSIVE EDGE CASES & UNIT VALIDATIONS\n";
echo "----------------------------------------------------------------------\n\n";

// Edge Case 1: Empty Basket
$totalTests++;
$emptyBasket = Basket::createDefault();
$emptyPassed = ($emptyBasket->total() === 0.0 && $emptyBasket->getDeliveryCost() === 0.0 && $emptyBasket->getSubtotal() === 0.0);
if ($emptyPassed) {
    $passedCount++;
    echo "▶ Empty Basket Safety Check {$passTag}\n";
    echo "    Returns $0.00 subtotal, $0.00 delivery, and $0.00 total without errors.\n\n";
} else {
    $allPassed = false;
    echo "▶ Empty Basket Safety Check {$failTag}\n\n";
}
echo "----------------------------------------------------------------------\n\n";

// Edge Case 2: Invalid Product Code
$totalTests++;
$invalidPassed = false;
try {
    $basket = Basket::createDefault();
    $basket->add('UNKNOWN99');
} catch (\InvalidArgumentException) {
    $invalidPassed = true;
}
if ($invalidPassed) {
    $passedCount++;
    echo "▶ Invalid Product Code Exception {$passTag}\n";
    echo "    Correctly throws an InvalidArgumentException when adding an unknown product code.\n\n";
} else {
    $allPassed = false;
    echo "▶ Invalid Product Code Exception {$failTag}\n\n";
}
echo "----------------------------------------------------------------------\n\n";

// Edge Case 3: Three Red Widgets
$totalTests++;
$threeRed = Basket::createDefault();
$threeRed->add('R01')->add('R01')->add('R01');
$expectedThreeRed = round((32.95 * 3) - 16.48 + 2.95, 2);
$threeRedPassed = (abs($threeRed->total() - $expectedThreeRed) < 0.001 && $threeRed->getTotalDiscount() === 16.48);
if ($threeRedPassed) {
    $passedCount++;
    echo "▶ Odd Count Red Widgets (3 x R01) {$passTag}\n";
    echo "    Applies 1 half-price discount ($16.48) to the pair; 3rd widget remains full price ($85.32 total).\n\n";
} else {
    $allPassed = false;
    echo "▶ Odd Count Red Widgets (3 x R01) {$failTag}\n\n";
}
echo "----------------------------------------------------------------------\n\n";

// Edge Case 4: Free Delivery Threshold
$totalTests++;
$freeDeliveryBasket = Basket::createDefault();
$freeDeliveryBasket->add('R01')->add('R01')->add('R01')->add('G01');
$freePassed = ($freeDeliveryBasket->getDeliveryCost() === 0.00);
if ($freePassed) {
    $passedCount++;
    echo "▶ Free Delivery Threshold (>= $90.00) {$passTag}\n";
    echo "    Orders with net subtotal >= $90.00 automatically qualify for free shipping ($0.00 fee).\n\n";
} else {
    $allPassed = false;
    echo "▶ Free Delivery Threshold (>= $90.00) {$failTag}\n\n";
}
echo "----------------------------------------------------------------------\n\n";

echo "======================================================================\n";
if ($allPassed) {
    echo "  ✔ ALL {$passedCount}/{$totalTests} TESTS PASSED WITH 100% ACCURACY!\n";
} else {
    echo "  ✖ {$passedCount}/{$totalTests} TESTS PASSED. PLEASE REVIEW FAILURES.\n";
}
echo "======================================================================\n\n";

exit($allPassed ? 0 : 1);
