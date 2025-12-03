<?php
/**
 * Order model
 */

class Order {
    /**
     * Get order by ID
     */
    public static function getById(mysqli $conn, int $orderId): ?array {
        $stmt = $conn->prepare("
            SELECT orderId, dateOrdered, customerName, customerId, status
            FROM Orders
            WHERE orderId = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Get order details
     */
    public static function getOrderDetails(mysqli $conn, int $orderId): array {
        $stmt = $conn->prepare("
            SELECT orderDetailId, orderId, itemId, itemName, quantity, unitPrice, subtotal, itemTax
            FROM OrderDetails
            WHERE orderId = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    /**
     * Get order total
     */
    public static function getOrderTotal(mysqli $conn, int $orderId): array {
        $details = self::getOrderDetails($conn, $orderId);

        $subtotal = 0;
        $tax = 0;

        foreach ($details as $item) {
            $subtotal += (float)$item['subtotal'];
            $tax += (float)$item['itemTax'];
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax
        ];
    }

    /**
     * Get customer's order history
     */
    public static function getCustomerOrders(mysqli $conn, int $customerId): array {
        $stmt = $conn->prepare("
            SELECT orderId, dateOrdered, customerName, status
            FROM Orders
            WHERE customerId = ?
            ORDER BY dateOrdered DESC
        ");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    /**
     * Update order status
     */
    public static function updateStatus(mysqli $conn, int $orderId, string $status): bool {
        $validStatuses = ['PENDING', 'PAID', 'SHIPPED', 'DELIVERED', 'CANCELLED'];

        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        $stmt = $conn->prepare("UPDATE Orders SET status = ? WHERE orderId = ?");
        $stmt->bind_param("si", $status, $orderId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
