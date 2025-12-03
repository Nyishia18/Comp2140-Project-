<?php
/**
 * User model
 */

class User {
    public int $userId;
    public string $password;
    public string $loginStatus;

    /**
     * Verify login credentials and update login status
     * Returns user data on success, false on failure
     */
    public static function LoginVerification(mysqli $conn, string $email, string $password): array|false {
        $stmt = $conn->prepare("
            SELECT U.userId, U.password, C.customerId, C.customerName, C.email
            FROM User U
            JOIN Customer C ON C.userId = U.userId
            WHERE C.email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return false;
        }

        // Use password_verify for secure password checking
        if (password_verify($password, $row['password'])) {
            $update = $conn->prepare("UPDATE User SET loginStatus = 'LOGGED_IN' WHERE userId = ?");
            $update->bind_param("i", $row['userId']);
            $update->execute();
            $update->close();

            return [
                'userId' => $row['userId'],
                'customerId' => $row['customerId'],
                'customerName' => $row['customerName'],
                'email' => $row['email']
            ];
        }

        return false;
    }

    /**
     * Log out user
     */
    public static function logout(mysqli $conn, int $userId): bool {
        $stmt = $conn->prepare("UPDATE User SET loginStatus = 'LOGGED_OUT' WHERE userId = ?");
        $stmt->bind_param("i", $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
