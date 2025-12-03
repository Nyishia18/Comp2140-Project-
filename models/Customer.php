<?php
/**
 * Customer model
 */

class Customer {
    public int $customerId;
    public string $customerName;
    public string $address;
    public string $email;
    public string $bankingCardInfo;
    public float $accountBalance;

    /**
     * Login customer
     */
    public static function login(mysqli $conn, string $email, string $password): array|false {
        return User::LoginVerification($conn, $email, $password);
    }

    /**
     * Register new customer
     */
    public static function register(
        mysqli $conn,
        string $customerName,
        string $email,
        string $password,
        string $address = ''
    ): array {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        // Check password length
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }

        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT customerId FROM Customer WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $checkStmt->close();
            return ['success' => false, 'error' => 'Email already exists'];
        }
        $checkStmt->close();

        // Use transaction for two-table insert
        $conn->begin_transaction();

        try {
            // Hash the password securely
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Create User record
            $userStmt = $conn->prepare("INSERT INTO User (password, loginStatus) VALUES (?, 'LOGGED_OUT')");
            $userStmt->bind_param("s", $hashedPassword);
            if (!$userStmt->execute()) {
                throw new Exception("Failed to create user");
            }
            $userId = $userStmt->insert_id;
            $userStmt->close();

            // Create Customer record
            $custStmt = $conn->prepare("
                INSERT INTO Customer (userId, customerName, address, email, accountBalance)
                VALUES (?, ?, ?, ?, 0)
            ");
            $custStmt->bind_param("isss", $userId, $customerName, $address, $email);
            if (!$custStmt->execute()) {
                throw new Exception("Failed to create customer");
            }
            $customerId = $custStmt->insert_id;
            $custStmt->close();

            $conn->commit();

            return [
                'success' => true,
                'userId' => $userId,
                'customerId' => $customerId
            ];

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Update customer profile
     */
    public static function updateProfile(
        mysqli $conn,
        int $customerId,
        string $customerName,
        string $address,
        string $email
    ): bool {
        $stmt = $conn->prepare("
            UPDATE Customer
            SET customerName = ?, address = ?, email = ?
            WHERE customerId = ?
        ");
        $stmt->bind_param("sssi", $customerName, $address, $email, $customerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get customer by ID
     */
    public static function getById(mysqli $conn, int $customerId): ?array {
        $stmt = $conn->prepare("
            SELECT customerId, customerName, address, email, accountBalance
            FROM Customer
            WHERE customerId = ?
        ");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Update account balance
     */
    public static function updateBalance(mysqli $conn, int $customerId, float $amount): bool {
        $stmt = $conn->prepare("
            UPDATE Customer
            SET accountBalance = accountBalance + ?
            WHERE customerId = ?
        ");
        $stmt->bind_param("di", $amount, $customerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
