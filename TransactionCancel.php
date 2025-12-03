<?php
/**
 * FR04 - Cancel transaction
 * POST: payment_id
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

// Get payment ID
$paymentId = (int)($_POST['payment_id'] ?? 0);

if ($paymentId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payment ID']);
    exit;
}

// Verify the payment belongs to this customer's order
$stmt = $conn->prepare("
    SELECT P.paymentId, O.customerId
    FROM Payment P
    JOIN Orders O ON O.orderId = P.orderId
    WHERE P.paymentId = ?
");
$stmt->bind_param("i", $paymentId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || (int)$row['customerId'] !== $customerId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Payment not found or access denied']);
    exit;
}

// Cancel the transaction
$result = PaymentService::cancelTransaction($conn, $paymentId);

echo json_encode($result);