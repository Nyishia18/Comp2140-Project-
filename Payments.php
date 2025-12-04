<?php
/**
 * FR03 - Process card payment
 * POST: customer_id, order_id, card_number
 */

session_start();
require_once 'config.php';
require_once 'models.php';

/* ============================================================
   GET REQUEST → Show BEAUTIFUL test page
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Test Tool</title>
<style>
    body {
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", Arial, sans-serif;
        background: linear-gradient(135deg, #ff7e5f, #feb47b);
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .container {
        backdrop-filter: blur(12px);
        background: rgba(255, 255, 255, 0.15);
        padding: 30px;
        width: 420px;
        border-radius: 16px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        color: white;
        animation: fadeIn 0.7s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
        letter-spacing: 1px;
        font-size: 26px;
        font-weight: 600;
    }

    label {
        font-size: 14px;
        font-weight: 600;
        margin-top: 10px;
        display: block;
        opacity: 0.9;
    }

    input {
        width: 100%;
        border: none;
        padding: 12px;
        border-radius: 10px;
        margin-top: 5px;
        margin-bottom: 15px;
        font-size: 14px;
        outline: none;
        background: rgba(255, 255, 255, 0.25);
        color: white;
        backdrop-filter: blur(5px);
    }

    button {
        width: 100%;
        padding: 14px;
        font-size: 16px;
        border-radius: 12px;
        cursor: pointer;
        border: none;
        background: #ff6a00;
        color: #fff;
        font-weight: bold;
        letter-spacing: 1px;
        transition: 0.3s ease;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    button:hover {
        background: #ff8c42;
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.3);
    }
</style>
</head>

<body>
<div class="container">
    <h2>💳 Test Payment</h2>
    
    <form method="POST">

        <label>Customer ID</label>
        <input type="number" name="customer_id" placeholder="Enter customer ID" required>

        <label>Order ID</label>
        <input type="number" name="order_id" placeholder="Enter order ID" required>

        <label>Card Number</label>
        <input type="text" name="card_number" placeholder="Enter card number" required>

        <button type="submit">Submit Payment</button>

    </form>
</div>
</body>
</html>

<?php
exit;
}

/* ============================================================
   POST REQUEST → JSON API LOGIC
   ============================================================ */

header('Content-Type: application/json');

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

// Get customer ID (from POST or session)
$customerId = (int)($_POST['customer_id'] ?? $_SESSION['customer_id'] ?? 0);
if ($customerId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Set session for test purposes
$_SESSION['customer_id'] = $customerId;

// Get order ID and card number
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
?>
