<?php
/**
 * Item model
 */

class Item {
    /**
     * Get all available items (in stock)
     */
    public static function getAvailableItems(mysqli $conn): array {
        $result = $conn->query("
            SELECT itemId, itemName, unitPrice, stockQuantity, itemTax
            FROM Item
            WHERE stockQuantity > 0
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get single item by ID
     */
    public static function getById(mysqli $conn, int $itemId): ?array {
        $stmt = $conn->prepare("
            SELECT itemId, itemName, unitPrice, stockQuantity, itemTax
            FROM Item
            WHERE itemId = ?
        ");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Reduce item stock
     */
    public static function reduceStock(mysqli $conn, int $itemId, int $qty): bool {
        $stmt = $conn->prepare("
            UPDATE Item
            SET stockQuantity = stockQuantity - ?
            WHERE itemId = ? AND stockQuantity >= ?
        ");
        $stmt->bind_param("iii", $qty, $itemId, $qty);
        $result = $stmt->execute();
        $stmt->close();
        return $result && $conn->affected_rows > 0;
    }

    /**
     * Restore item stock (for cancelled orders)
     */
    public static function restoreStock(mysqli $conn, int $itemId, int $qty): bool {
        $stmt = $conn->prepare("
            UPDATE Item
            SET stockQuantity = stockQuantity + ?
            WHERE itemId = ?
        ");
        $stmt->bind_param("ii", $qty, $itemId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
