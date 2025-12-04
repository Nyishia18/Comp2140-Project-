<?php
/**
 * Cart View - Visual Page
 * GET: customer_id (optional if using session)
 */

session_start();
require_once 'config.php';
require_once 'models.php';

// Get customer ID (from GET or session)
$customerId = (int)($_GET['customer_id'] ?? $_SESSION['customer_id'] ?? 0);
if ($customerId <= 0) {
    echo "<h2 style='color:red;text-align:center;margin-top:50px;'>Not authenticated. Provide a valid Customer ID in the URL (?customer_id=1)</h2>";
    exit;
}

// Save in session for demo purposes
$_SESSION['customer_id'] = $customerId;

// Get cart items and totals
$items = ShoppingCart::viewCartInfo($conn, $customerId);
$totals = ShoppingCart::getCartTotal($conn, $customerId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Cart - DashCam.Ja</title>
<style>
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f3f4f6;
    margin: 0;
    padding: 0;
}
.header {
    background: linear-gradient(90deg,#36d1dc,#5b86e5);
    color: white;
    padding: 20px;
    text-align: center;
}
.container {
    width: 90%;
    max-width: 1000px;
    margin: 30px auto;
}
.cart-item {
    background: white;
    display: flex;
    align-items: center;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.cart-item img {
    width: 120px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 20px;
}
.cart-item-details {
    flex: 1;
}
.cart-item-details h3 {
    margin: 0 0 5px 0;
}
.cart-item-details p {
    margin: 3px 0;
    color: #555;
}
.cart-summary {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    text-align: right;
}
.cart-summary h3 {
    margin-bottom: 10px;
}
button {
    padding: 10px 18px;
    background: #ff6a00;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}
button:hover {
    background: #ff8c42;
}
.empty-cart {
    text-align: center;
    color: #777;
    margin-top: 50px;
    font-size: 20px;
}
</style>
</head>
<body>
<div class="header">
    <h1>🛒DashCam.Ja Cart</h1>
</div>
<div class="container">
<?php if(empty($items)): ?>
    <div class="empty-cart">Your cart is empty 😢</div>
<?php else: ?>
    <?php foreach($items as $item): ?>
        <div class="cart-item">
            <!-- Placeholder image for dashcam -->
            <img src="https://cdn.pixabay.com/photo/2017/08/30/12/11/dashcam-2695451_1280.jpg" alt="<?= htmlspecialchars($item['itemName']) ?>">
            <div class="cart-item-details">
                <h3><?= htmlspecialchars($item['itemName']) ?></h3>
                <p>Quantity: <?= $item['quantity'] ?></p>
                <p>Unit Price: $<?= number_format($item['unitPrice'],2) ?></p>
                <p>Item Tax: $<?= number_format($item['itemTax'],2) ?></p>
                <p>Subtotal: $<?= number_format($item['subtotal'],2) ?></p>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="cart-summary">
        <h3>Cart Summary</h3>
        <p>Subtotal: $<?= number_format($totals['subtotal'],2) ?></p>
        <p>Tax: $<?= number_format($totals['tax'],2) ?></p>
        <p><strong>Total: $<?= number_format($totals['total'],2) ?></strong></p>
        <p>Items: <?= $totals['itemCount'] ?></p>
        <button onclick="alert('Proceed to Checkout clicked!')">Proceed to Checkout</button>
    </div>
<?php endif; ?>
</div>
</body>
</html>
