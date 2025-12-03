<?php
/**
 * FR05 - Display total cost of selected items in shopping cart
 * Returns: JSON with cart total including subtotal, taxes, discounts, and grand total
 * 
 * This Functional requirement calculates and returns the final total cost of all items
 * in the customer's shopping cart, including item prices, quantities, taxes,
 * and any applicable discounts.
 */

session_start();
header('Content-Type: application/json');

require_once 'config.php';
require_once 'models.php';

// Database connection validation
// Ensuring we have a valid MySQLi connection before proceeding
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database connection error'
    ]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'error' => 'Method not allowed. Use GET or POST.'
    ]);
    exit;
}

// Get customer ID from all possible sources with priority:
// 1. POST parameter (if using POST request)
// 2. GET parameter (if using GET request)
// 3. Session (if user is logged in)
// 4. Default to 0 (not authenticated)
$customerId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
} else {
    $customerId = (int)($_GET['customer_id'] ?? 0);
}

// If not found in request parameters, try session
if ($customerId <= 0) {
    $customerId = (int)($_SESSION['customer_id'] ?? 0);
}

// Authentication check - customer must be identified
if ($customerId <= 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'error' => 'Not authenticated. Please log in or provide customer_id.'
    ]);
    exit;
}

// Accept discount from GET/POST parameters or session
$discountCode = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discountCode = trim($_POST['discount_code'] ?? '');
} else {
    $discountCode = trim($_GET['discount_code'] ?? '');
}

// If no discount code in request, check session
if (empty($discountCode)) {
    $discountCode = trim($_SESSION['cart_discount_code'] ?? '');
}

try {
    // Calculate the cart total using the ShoppingCart model
    // This method should handle:
    // 1. Retrieving all cart items with prices and quantities
    // 2. Calculating subtotal (price × quantity for each item)
    // 3. Applying any applicable discounts
    // 4. Calculating taxes based on subtotal and customer location
    // 5. Returning all components of the total
    
    $cartTotal = ShoppingCart::getCartTotal($conn, $customerId, $discountCode);
    
    // Check that we received a proper cart total structure
    // The getCartTotal method should return an array with specific keys
    if (!is_array($cartTotal) || !isset($cartTotal['success']) || $cartTotal['success'] !== true) {
        // If the cart total calculation failed, return an error
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $cartTotal['error'] ?? 'Failed to calculate cart total',
            'cart' => [
                'subtotal' => 0.00,
                'tax' => 0.00,
                'discount' => 0.00,
                'total' => 0.00,
                'item_count' => 0
            ]
        ]);
        exit;
    }
    
    // Success response with detailed cart breakdown
    // This  response allows the frontend to display:
    // - Individual components (subtotal, tax, discount)
    // - Final grand total
    // - Item count for UI display
    echo json_encode([
        'success' => true,
        'message' => 'Cart total calculated successfully',
        'cart' => [
            'subtotal' => $cartTotal['subtotal'] ?? 0.00,           // Total before tax and discount
            'tax_amount' => $cartTotal['tax_amount'] ?? 0.00,       // Calculated tax amount
            'tax_rate' => $cartTotal['tax_rate'] ?? 0.00,           // Tax rate percentage (for display)
            'discount_amount' => $cartTotal['discount_amount'] ?? 0.00, // Discount amount applied
            'discount_code' => $cartTotal['discount_code'] ?? $discountCode, // Discount code used
            'grand_total' => $cartTotal['grand_total'] ?? 0.00,     // Final amount to be charged
            'item_count' => $cartTotal['item_count'] ?? 0,          // Total number of items
            'currency' => $cartTotal['currency'] ?? 'USD',          // Currency code
            'items' => $cartTotal['items'] ?? []                    // Optional: Detailed item breakdown
        ],
        'timestamp' => date('Y-m-d H:i:s'),  // When the calculation was performed
        'customer_id' => $customerId         // Confirmation of which customer's cart
    ]);
    
} catch (Exception $e) {
    // Catch any unexpected errors during cart total calculation
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while calculating cart total: ' . $e->getMessage(),
        'cart' => [
            'subtotal' => 0.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => 0.00,
            'item_count' => 0
        ]
    ]);
    exit;
}