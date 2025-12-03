<?php
/**
 * FR06 - Display items in the inventory/shopping cart
 * GET: customer_id (optional), include_out_of_stock (optional boolean)
 * Returns: JSON with all items in the customer's cart including details and availability
 * 
 * This Functional requirement retrieves and displays all items currently in the customer's shopping cart,
 * including product details, quantities, prices, and availability status. It also handles
 * automatic removal of out-of-stock items to ensure cart accuracy.
 */

session_start();
header('Content-Type: application/json');

require_once 'config.php';
require_once 'models.php';

// Database connection check
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database connection error',
        'cart_items' => []
    ]);
    exit;
}

// This FR primarily uses GET method since it's retrieving data
// However, we can also accept POST for consistency with other FR
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'error' => 'Method not allowed. Use GET or POST.',
        'cart_items' => []
    ]);
    exit;
}

// Get customer ID from multiple sources with priority order
$customerId = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
} else {
    $customerId = (int)($_GET['customer_id'] ?? 0);
}

// Fall back to session if not provided in request
if ($customerId <= 0) {
    $customerId = (int)($_SESSION['customer_id'] ?? 0);
}

// Authentication check - customer must be identified to view their cart
if ($customerId <= 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'error' => 'Not authenticated. Please log in or provide customer_id.',
        'cart_items' => []
    ]);
    exit;
}

// Dysplaying out of stock items in response
// Default is false (only show available items)
// This can be useful for admin views or debugging
$includeOutOfStock = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $includeOutOfStock = filter_var($_POST['include_out_of_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
} else {
    $includeOutOfStock = filter_var($_GET['include_out_of_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
}

try {
    // Retrieve cart items using the ShoppingCart model
    // This method should:
    // 1. Fetch all items in the customer's cart from database
    // 2. Check current stock levels for each item
    // 3. Automatically remove out-of-stock items (if $removeOutOfStock is true)
    // 4. Return detailed item information
    
    $cartItems = ShoppingCart::getCartItems($conn, $customerId, !$includeOutOfStock);
    
    // Check if the cart items retrieval was successful
    if (!is_array($cartItems) || !isset($cartItems['success'])) {
        // Handle unexpected response format
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Unexpected response format from cart items retrieval',
            'cart_items' => []
        ]);
        exit;
    }
    
    // If retrieval failed, return error
    if ($cartItems['success'] !== true) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $cartItems['error'] ?? 'Failed to retrieve cart items',
            'cart_items' => []
        ]);
        exit;
    }
    
    // The response data structure
    $responseData = [
        'success' => true,
        'message' => 'Cart items retrieved successfully',
        'cart_summary' => [
            'total_items' => $cartItems['total_items'] ?? 0,           // Count of distinct items
            'total_quantity' => $cartItems['total_quantity'] ?? 0,     // Sum of all quantities
            'available_items' => $cartItems['available_items'] ?? 0,   // Items currently in stock
            'out_of_stock_items' => $cartItems['out_of_stock_items'] ?? 0, // Items removed due to stock
            'last_updated' => $cartItems['last_updated'] ?? date('Y-m-d H:i:s')
        ],
        'cart_items' => $cartItems['items'] ?? [],  // Array of item objects
        'customer_id' => $customerId,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Add subtotal calculation if items array exists
    if (isset($cartItems['items']) && is_array($cartItems['items'])) {
        $subtotal = 0;
        foreach ($cartItems['items'] as $item) {
            $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
        }
        $responseData['cart_summary']['subtotal'] = number_format($subtotal, 2, '.', '');
    }
    
    // Success response with cart items
    echo json_encode($responseData);
    
} catch (Exception $e) {
    // Catch any unexpected errors during cart items retrieval
    // This ensures graceful error handling
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while retrieving cart items: ' . $e->getMessage(),
        'cart_items' => [],
        'cart_summary' => [
            'total_items' => 0,
            'total_quantity' => 0,
            'available_items' => 0,
            'out_of_stock_items' => 0,
            'subtotal' => 0.00
        ]
    ]);
    exit;
}

