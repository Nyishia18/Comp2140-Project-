<?php
/**
 * FR03 - Process card payment
 * POST: order_id, card_number
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

// Get parameters
$orderId = (int)($_POST['order_id'] ?? 0);
$cardNumber = $_POST['card_number'] ?? '';

// Validate inputs
if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

if (empty($cardNumber)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Card number is required']);
    exit;
}

// Verify the order belongs to this customer
$order = Order::getById($conn, $orderId);
if (!$order || (int)$order['customerId'] !== $customerId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Order not found or access denied']);
    exit;
}

// Check order isn't already paid or cancelled
if ($order['status'] !== 'PENDING') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Order cannot be paid (status: ' . $order['status'] . ')'
    ]);
    exit;
}

// Process the payment
$result = PaymentService::processCardPayment($conn, $orderId, $cardNumber);

// Include order total in response
if ($result['success']) {
    $result['orderTotal'] = Order::getOrderTotal($conn, $orderId);
}

echo json_encode($result);