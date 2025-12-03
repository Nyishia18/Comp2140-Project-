<?php
/**
 * ShoppingCart model
 */

class ShoppingCart {
    /**
     * Add item to cart (or increase quantity if already exists)
     */
    public static function addCartItem(mysqli $conn, int $customerId, int $itemId, int $quantity): bool {
        // Check if item exists and has stock
        $item = Item::getById($conn, $itemId);
        if (!$item || $item['stockQuantity'] < $quantity) {
            return false;
        }

        // Check if item already in cart
        $stmt = $conn->prepare("
            SELECT cartId, quantity FROM ShoppingCart
            WHERE customerId = ? AND itemId = ?
        ");
        $stmt->bind_param("ii", $customerId, $itemId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if ($row) {
            $newQty = $row['quantity'] + $quantity;
            return self::updateQuantity($conn, (int)$row['cartId'], $newQty);
        }

        $insert = $conn->prepare("
            INSERT INTO ShoppingCart (customerId, itemId, quantity)
            VALUES (?, ?, ?)
        ");
        $insert->bind_param("iii", $customerId, $itemId, $quantity);
        $result = $insert->execute();
        $insert->close();
        return $result;
    }

    /**
     * Update cart item quantity
     */
    public static function updateQuantity(mysqli $conn, int $cartId, int $quantity): bool {
        if ($quantity <= 0) {
            $del = $conn->prepare("DELETE FROM ShoppingCart WHERE cartId = ?");
            $del->bind_param("i", $cartId);
            $result = $del->execute();
            $del->close();
            return $result;
        }

        $stmt = $conn->prepare("UPDATE ShoppingCart SET quantity = ? WHERE cartId = ?");
        $stmt->bind_param("ii", $quantity, $cartId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * View cart contents (FR06: removes out-of-stock items)
     */
    public static function viewCartInfo(mysqli $conn, int $customerId): array {
        // Clean out any out-of-stock items first (using prepared statement)
        $cleanStmt = $conn->prepare("
            DELETE c FROM ShoppingCart c
            JOIN Item i ON c.itemId = i.itemId
            WHERE c.customerId = ? AND i.stockQuantity <= 0
        ");
        $cleanStmt->bind_param("i", $customerId);
        $cleanStmt->execute();
        $cleanStmt->close();

        $stmt = $conn->prepare("
            SELECT c.cartId, c.itemId, i.itemName, c.quantity,
                   i.unitPrice, i.itemTax,
                   (i.unitPrice * c.quantity) AS lineSubtotal,
                   (i.itemTax * c.quantity) AS lineTax,
                   i.stockQuantity
            FROM ShoppingCart c
            JOIN Item i ON c.itemId = i.itemId
            WHERE c.customerId = ?
        ");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $res = $stmt->get_result();
        $items = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $items;
    }

    /**
     * Get cart total
     */
    public static function getCartTotal(mysqli $conn, int $customerId): array {
        $items = self::viewCartInfo($conn, $customerId);

        $subtotal = 0;
        $tax = 0;

        foreach ($items as $item) {
            $subtotal += (float)$item['lineSubtotal'];
            $tax += (float)$item['lineTax'];
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
            'itemCount' => count($items)
        ];
    }

    /**
     * Checkout - creates order from cart
     */
    public static function checkOut(mysqli $conn, int $customerId): ?int {
        $cartItems = self::viewCartInfo($conn, $customerId);
        if (empty($cartItems)) {
            return null;
        }

        // Get customer info
        $customer = Customer::getById($conn, $customerId);
        if (!$customer) {
            return null;
        }

        $conn->begin_transaction();

        try {
            // Create order
            $orderStmt = $conn->prepare("
                INSERT INTO Orders (customerName, customerId, status)
                VALUES (?, ?, 'PENDING')
            ");
            $orderStmt->bind_param("si", $customer['customerName'], $customerId);
            if (!$orderStmt->execute()) {
                throw new Exception("Failed to create order");
            }
            $orderId = $orderStmt->insert_id;
            $orderStmt->close();

            // Insert order details and reduce stock
            $detailStmt = $conn->prepare("
                INSERT INTO OrderDetails
                (orderId, itemId, itemName, quantity, unitPrice, subtotal, itemTax)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($cartItems as $item) {
                $itemId = (int)$item['itemId'];
                $name = $item['itemName'];
                $qty = (int)$item['quantity'];
                $unit = (float)$item['unitPrice'];
                $subtotal = (float)$item['lineSubtotal'];
                $tax = (float)$item['itemTax'] * $qty;

                // Verify and reduce stock
                if (!Item::reduceStock($conn, $itemId, $qty)) {
                    throw new Exception("Insufficient stock for item: " . $name);
                }

                $detailStmt->bind_param(
                    "iisiddd",
                    $orderId,
                    $itemId,
                    $name,
                    $qty,
                    $unit,
                    $subtotal,
                    $tax
                );

                if (!$detailStmt->execute()) {
                    throw new Exception("Failed to add order detail");
                }
            }
            $detailStmt->close();

            // Clear the cart
            $clearStmt = $conn->prepare("DELETE FROM ShoppingCart WHERE customerId = ?");
            $clearStmt->bind_param("i", $customerId);
            $clearStmt->execute();
            $clearStmt->close();

            $conn->commit();
            return $orderId;

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Checkout error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Remove item from cart
     */
    public static function removeItem(mysqli $conn, int $cartId): bool {
        $stmt = $conn->prepare("DELETE FROM ShoppingCart WHERE cartId = ?");
        $stmt->bind_param("i", $cartId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Clear entire cart
     */
    public static function clearCart(mysqli $conn, int $customerId): bool {
        $stmt = $conn->prepare("DELETE FROM ShoppingCart WHERE customerId = ?");
        $stmt->bind_param("i", $customerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
