<?php
session_start();

// Database connection (update with your database credentials)
$servername = "localhost";
$username = "root";
$password = "";
$database = "dashcamera_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Login
$login_error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $email = htmlspecialchars($_POST["email"]);
    $password = htmlspecialchars($_POST["password"]);

    $sql = "SELECT id, email, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row["password"])) {
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["user_email"] = $row["email"];
            header("Location: dashboard.php");
            exit();
        } else {
            $login_error = "Invalid password!";
        }
    } else {
        $login_error = "Email not found!";
    }
    $stmt->close();
}

// Handle Registration
$register_error = "";
$register_success = "";
$show_signup = false;

if (isset($_GET["signup"])) {
    $show_signup = true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $name = htmlspecialchars($_POST["name"]);
    $email = htmlspecialchars($_POST["email"]);
    $password = htmlspecialchars($_POST["password"]);
    $confirm_password = htmlspecialchars($_POST["confirm_password"]);

    if ($password !== $confirm_password) {
        $register_error = "Passwords do not match!";
        $show_signup = true;
    } else if (strlen($password) < 6) {
        $register_error = "Password must be at least 6 characters!";
        $show_signup = true;
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $register_error = "Email already exists!";
            $show_signup = true;
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                $register_success = "Registration successful! You can now login.";
                $show_signup = false;
            } else {
                $register_error = "Error: " . $stmt->error;
                $show_signup = true;
            }
        }
        $stmt->close();
    }
}

// Logout
if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>