<?php
/**
 * FR01 - Add selected items to cart
 * POST: customer_id, items (JSON array)
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

// Get customer ID (from session or POST)
$customerId = (int)($_POST['customer_id'] ?? $_SESSION['customer_id'] ?? 0);

if ($customerId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Parse items JSON
$itemsJson = $_POST['items'] ?? '[]';
$items = json_decode($itemsJson, true);

if (!is_array($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid items format']);
    exit;
}

$results = [];
$allSuccess = true;

foreach ($items as $it) {
    $itemId = (int)($it['itemId'] ?? 0);
    $qty = (int)($it['quantity'] ?? 1);

    if ($itemId <= 0 || $qty <= 0) {
        $results[] = ['itemId' => $itemId, 'success' => false, 'error' => 'Invalid item or quantity'];
        $allSuccess = false;
        continue;
    }

    $success = ShoppingCart::addCartItem($conn, $customerId, $itemId, $qty);
    $results[] = [
        'itemId' => $itemId,
        'quantity' => $qty,
        'success' => $success,
        'error' => $success ? null : 'Failed to add item (may be out of stock)'
    ];

    if (!$success) {
        $allSuccess = false;
    }
}

echo json_encode([
    'success' => $allSuccess,
    'results' => $results,
    'cart' => ShoppingCart::getCartTotal($conn, $customerId)
]);