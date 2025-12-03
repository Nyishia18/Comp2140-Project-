<?php
/**
 * Checkout - Convert cart to order
 * POST: (uses session customer_id)
 */

session_start();
header('Content-Type: application/json');

require_once 'config.php';
require_once 'models.php';

// Ensure database connection is available
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection error']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Validate customer is logged in
$customerId = (int)($_SESSION['customer_id'] ?? 0);
if ($customerId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check cart is not empty
$cartItems = ShoppingCart::viewCartInfo($conn, $customerId);
if (empty($cartItems)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

// Process checkout
$orderId = ShoppingCart::checkOut($conn, $customerId);

if ($orderId === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Checkout failed. Please try again.']);
    exit;
}

// Get order details for response
$order = Order::getById($conn, $orderId);
$orderDetails = Order::getOrderDetails($conn, $orderId);
$orderTotal = Order::getOrderTotal($conn, $orderId);

echo json_encode([
    'success' => true,
    'orderId' => $orderId,
    'order' => $order,
    'items' => $orderDetails,
    'total' => $orderTotal,
    'message' => 'Order created successfully. Please proceed to payment.'
]);