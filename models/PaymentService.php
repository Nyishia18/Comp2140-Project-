<?php
/**
 * PaymentService model
 */

class PaymentService {
    /**
     * FR03: Process card payment
     */
    public static function processCardPayment(mysqli $conn, int $orderId, string $cardNumber): array {
        // Strip non-digits
        $digits = preg_replace('/\D/', '', $cardNumber);

        // Validate card number length (most cards are 13-19 digits, 16 is standard)
        if (strlen($digits) < 13 || strlen($digits) > 19) {
            $stmt = $conn->prepare("
                INSERT INTO Payment (orderId, status, message)
                VALUES (?, 'ERROR', 'Invalid card number – please retry')
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $stmt->close();

            return [
                'success' => false,
                'status' => 'ERROR',
                'message' => 'Invalid card number – please retry'
            ];
        }

        // Basic Luhn algorithm check (optional but recommended)
        if (!self::validateLuhn($digits)) {
            $stmt = $conn->prepare("
                INSERT INTO Payment (orderId, status, message)
                VALUES (?, 'ERROR', 'Invalid card number – please retry')
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $stmt->close();

            return [
                'success' => false,
                'status' => 'ERROR',
                'message' => 'Invalid card number – please retry'
            ];
        }

        // Simulate bank authorization (in production, integrate with payment gateway)
        $approved = (random_int(0, 1) === 1);
        $last4 = substr($digits, -4);
        $transactionId = $approved ? uniqid('TXN') : null;

        if ($approved) {
            $msg = "Transaction Approved – to card ending {$last4}";
            $status = 'APPROVED';

            // Update order status
            $orderStmt = $conn->prepare("UPDATE Orders SET status = 'PAID' WHERE orderId = ?");
            $orderStmt->bind_param("i", $orderId);
            $orderStmt->execute();
            $orderStmt->close();
        } else {
            $msg = "Transaction Declined – please try another card";
            $status = 'DECLINED';
        }

        $payStmt = $conn->prepare("
            INSERT INTO Payment (orderId, transactionId, cardLast4, status, message)
            VALUES (?, ?, ?, ?, ?)
        ");
        $payStmt->bind_param("issss", $orderId, $transactionId, $last4, $status, $msg);
        $payStmt->execute();
        $paymentId = $payStmt->insert_id;
        $payStmt->close();

        return [
            'success' => $approved,
            'status' => $status,
            'paymentId' => $paymentId,
            'transactionId' => $transactionId,
            'message' => $msg
        ];
    }

    /**
     * Luhn algorithm for card validation
     */
    private static function validateLuhn(string $digits): bool {
        $sum = 0;
        $length = strlen($digits);
        $parity = $length % 2;

        for ($i = 0; $i < $length; $i++) {
            $digit = (int)$digits[$i];

            if ($i % 2 === $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return ($sum % 10) === 0;
    }

    /**
     * FR04: Cancel transaction
     */
    public static function cancelTransaction(mysqli $conn, int $paymentId): array {
        $stmt = $conn->prepare("
            SELECT P.paymentId, P.orderId, P.status, O.status AS orderStatus
            FROM Payment P
            JOIN Orders O ON O.orderId = P.orderId
            WHERE P.paymentId = ?
        ");
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return [
                'success' => false,
                'message' => 'Payment not found'
            ];
        }

        // Only pending transactions can be cancelled
        if (in_array($row['status'], ['APPROVED', 'DECLINED', 'CANCELLED'], true)) {
            return [
                'success' => false,
                'message' => 'This transaction cannot be cancelled'
            ];
        }

        $conn->begin_transaction();

        try {
            // Update payment to CANCELLED
            $upPay = $conn->prepare("
                UPDATE Payment 
                SET status = 'CANCELLED', message = 'Transaction cancelled by user' 
                WHERE paymentId = ?
            ");
            $upPay->bind_param("i", $paymentId);
            $upPay->execute();
            $upPay->close();

            // Update order to CANCELLED
            $upOrd = $conn->prepare("UPDATE Orders SET status = 'CANCELLED' WHERE orderId = ?");
            $upOrd->bind_param("i", $row['orderId']);
            $upOrd->execute();
            $upOrd->close();

            // Restore stock for cancelled order items
            $itemsStmt = $conn->prepare("
                SELECT itemId, quantity FROM OrderDetails WHERE orderId = ?
            ");
            $itemsStmt->bind_param("i", $row['orderId']);
            $itemsStmt->execute();
            $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $itemsStmt->close();

            foreach ($items as $item) {
                Item::restoreStock($conn, (int)$item['itemId'], (int)$item['quantity']);
            }

            $conn->commit();

            return [
                'success' => true,
                'message' => 'Transaction has been cancelled'
            ];

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Cancel transaction error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Cancellation error – please retry'
            ];
        }
    }

    /**
     * Get payment by order ID
     */
    public static function getByOrderId(mysqli $conn, int $orderId): ?array {
        $stmt = $conn->prepare("
            SELECT paymentId, orderId, transactionId, cardLast4, status, message, createdAt
            FROM Payment
            WHERE orderId = ?
            ORDER BY createdAt DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }
}
