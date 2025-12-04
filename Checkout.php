<?php
/**
 * Checkout - Convert cart to order
 * POST: uses session customer_id
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
<title>Checkout Test Tool</title>
<style>
    body {
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", Arial, sans-serif;
        background: linear-gradient(135deg, #36d1dc, #5b86e5);
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .container {
        backdrop-filter: blur(12px);
        background: rgba(255, 255, 255, 0.15);
        padding: 30px;
        width: 400px;
        border-radius: 16px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        color: white;
        animation: fadeIn 0.7s ease;
        text-align: center;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    h2 {
        margin-bottom: 20px;
        letter-spacing: 1px;
        font-size: 26px;
        font-weight: 600;
    }

    input {
        width: 100%;
        border: none;
        padding: 12px;
        border-radius: 10px;
        margin-bottom: 15px;
        font-size: 14px;
        outline: none;
        background: rgba(255,255,255,0.25);
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

    pre {
        background: rgba(255,255,255,0.2);
        padding: 12px;
        border-radius: 10px;
        text-align: left;
        overflow-x: auto;
        max-height: 300px;
    }
</style>
</head>
<body>
<div class="container">
    <h2>🛒 Test Checkout</h2>
    <form method="POST" id="checkoutForm">
        <input type="number" name="customer_id" placeholder="Enter Customer ID" required>
        <button type="submit">Process Checkout</button>
    </form>
    <pre id="response"></pre>
</div>

<script>
document.getElementById('checkoutForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const responseBox = document.getElementById('response');

    const res = await fetch('', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    responseBox.textContent = JSON.stringify(data, null, 2);
});
</script>
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
    echo json_encode(['success'=>false,'error'=>'Database connection error']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);
    exit;
}

// Get customer ID (from POST or session)
$customerId = (int)($_POST['customer_id'] ?? $_SESSION['customer_id'] ?? 0);
if ($customerId <= 0) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}

// Set session for test purposes
$_SESSION['customer_id'] = $customerId;

// Check cart is not empty
$cartItems = ShoppingCart::viewCartInfo($conn, $customerId);
if (empty($cartItems)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Cart is empty']);
    exit;
}

// Process checkout
$orderId = ShoppingCart::checkOut($conn, $customerId);
if ($orderId === null) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Checkout failed. Please try again.']);
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
?>
