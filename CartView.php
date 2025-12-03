<?php
/**
 * FR06 - View items in cart
 * GET: customer_id (optional if using session)
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

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get customer ID (from session or GET param)
$customerId = (int)($_GET['customer_id'] ?? $_SESSION['customer_id'] ?? 0);

if ($customerId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get cart items (out-of-stock items are automatically removed)
$items = ShoppingCart::viewCartInfo($conn, $customerId);
$totals = ShoppingCart::getCartTotal($conn, $customerId);

echo json_encode([
    'success' => true,
    'items' => $items,
    'summary' => $totals
]);