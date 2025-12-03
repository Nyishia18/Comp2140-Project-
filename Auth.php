<?php
/**
 * Authentication endpoints
 * POST action=login: email, password
 * POST action=register: name, email, password, confirm_password, address (optional)
 * GET action=logout
 */

session_start();
header('Content-Type: application/json');

require_once 'config.php';
require_once 'models.php';

// Ensure database connection is available
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection error']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin($conn);
        break;

    case 'register':
        handleRegister($conn);
        break;

    case 'logout':
        handleLogout($conn);
        break;

    case 'status':
        handleStatus();
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

function handleLogin(mysqli $conn): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email and password are required']);
        return;
    }

    $result = Customer::login($conn, $email, $password);

    if ($result) {
        // Set session variables
        $_SESSION['user_id'] = $result['userId'];
        $_SESSION['customer_id'] = $result['customerId'];
        $_SESSION['customer_name'] = $result['customerName'];
        $_SESSION['email'] = $result['email'];

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'customerId' => $result['customerId'],
                'customerName' => $result['customerName'],
                'email' => $result['email']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    }
}

function handleRegister(mysqli $conn): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');

    // Validate required fields
    if (empty($name) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Name, email, and password are required']);
        return;
    }

    // Validate password match
    if ($password !== $confirmPassword) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Passwords do not match']);
        return;
    }

    // Attempt registration
    $result = Customer::register($conn, $name, $email, $password, $address);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful. You can now login.',
            'customerId' => $result['customerId']
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}

function handleLogout(mysqli $conn): void {
    $userId = $_SESSION['user_id'] ?? null;

    // Update login status in database
    if ($userId) {
        User::logout($conn, (int)$userId);
    }

    // Clear session
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

function handleStatus(): void {
    if (isset($_SESSION['customer_id'])) {
        echo json_encode([
            'success' => true,
            'loggedIn' => true,
            'user' => [
                'customerId' => $_SESSION['customer_id'],
                'customerName' => $_SESSION['customer_name'] ?? '',
                'email' => $_SESSION['email'] ?? ''
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'loggedIn' => false
        ]);
    }
}