<?php
/**
 * FR04 - Cancel Transaction Test Page
 * POST: payment_id, sets customer_id in session
 */

session_start();
require_once 'config.php';
require_once 'models.php';

// Handle GET → show HTML page
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transaction Cancellation - DashCam.Ja</title>
<style>
body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: linear-gradient(135deg,#36d1dc,#5b86e5);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.container {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(12px);
    padding: 30px;
    border-radius: 16px;
    width: 400px;
    color: white;
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    text-align: center;
}
h2 {
    margin-bottom: 20px;
    font-size: 26px;
}
input {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 10px;
    border: none;
    outline: none;
    background: rgba(255,255,255,0.25);
    color: white;
    font-size: 14px;
}
button {
    width: 100%;
    padding: 14px;
    font-size: 16px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    background: #ff6a00;
    color: white;
    font-weight: bold;
    transition: 0.3s ease;
}
button:hover {
    background: #ff8c42;
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
    <h2>❌ Cancel Transaction!</h2>
    <form id="cancelForm">
        <input type="number" name="customer_id" placeholder="Enter Customer ID" required>
        <input type="number" name="payment_id" placeholder="Enter Payment ID" required>
        <button type="submit">Cancel Transaction</button>
    </form>
    <pre id="response"></pre>
</div>

<script>
document.getElementById('cancelForm').addEventListener('submit', async function(e){
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

// ====================================================
// POST → JSON API logic
// ====================================================
header('Content-Type: application/json');

// Database check
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Database connection error']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);
    exit;
}

// Get Customer ID from POST and set session
$customerId = (int)($_POST['customer_id'] ?? 0);
if ($customerId <= 0) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}
$_SESSION['customer_id'] = $customerId;

// Get Payment ID
$paymentId = (int)($_POST['payment_id'] ?? 0);
if ($paymentId <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid payment ID']);
    exit;
}

// Verify payment belongs to this customer
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
    echo json_encode(['success'=>false,'error'=>'Payment not found or access denied']);
    exit;
}

// Cancel transaction
$result = PaymentService::cancelTransaction($conn, $paymentId);
echo json_encode($result);
?>

