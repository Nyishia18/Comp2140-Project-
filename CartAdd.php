<?php
/**
 * FR01 - Add selected items to cart
 * POST: customer_id, items (JSON array)
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
<title>Add to Cart – Test Tool</title>
<style>
    body {
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", Arial, sans-serif;
        background: linear-gradient(135deg, #4b79a1, #283e51);
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

    input, textarea {
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

    textarea {
        height: 130px;
        resize: none;
    }

    button {
        width: 100%;
        padding: 14px;
        font-size: 16px;
        border-radius: 12px;
        cursor: pointer;
        border: none;
        background: #00c6ff;
        color: #003c57;
        font-weight: bold;
        letter-spacing: 1px;
        transition: 0.3s ease;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    button:hover {
        background: #00e1ff;
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.3);
    }
</style>
</head>

<body>
<div class="container">
    <h2>🛒 Test Add to Cart</h2>
    
    <form method="POST">

        <label>Customer ID</label>
        <input type="number" name="customer_id" placeholder="Enter customer ID" required>

        <label>Items (JSON Format)</label>
        <textarea name="items">[
    { "itemId": 1, "quantity": 2 },
    { "itemId": 3, "quantity": 1 }
]</textarea>

        <button type="submit">Submit POST Request</button>

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
?>
