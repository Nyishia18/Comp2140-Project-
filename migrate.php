<?php
/**
 * Database Migration Script
 * Creates all required tables for the DashCam.Ja application
 * 
 * Run this script once to set up the database schema.
 * Can be executed via command line: php migrate.php
 * Or via browser: http://your-domain/migrate.php
 */

require_once __DIR__ . '/config.php';

// Ensure database connection is available
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection error. Please check config.php");
}

$sql = "
    CREATE TABLE IF NOT EXISTS User (
        userId INT AUTO_INCREMENT PRIMARY KEY,
        password VARCHAR(255) NOT NULL,
        loginStatus VARCHAR(20) NOT NULL DEFAULT 'LOGGED_OUT'
    );
    
    CREATE TABLE IF NOT EXISTS Customer (
        customerId INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        customerName VARCHAR(255) NOT NULL,
        address VARCHAR(255),
        email VARCHAR(255) UNIQUE,
        bankingCardInfo VARCHAR(255),
        accountBalance DECIMAL(10,2) DEFAULT 0,
        FOREIGN KEY (userId) REFERENCES User(userId) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS Item (
        itemId INT AUTO_INCREMENT PRIMARY KEY,
        itemName VARCHAR(255) NOT NULL,
        unitPrice DECIMAL(10,2) NOT NULL,
        stockQuantity INT NOT NULL DEFAULT 0,
        itemTax DECIMAL(10,2) NOT NULL DEFAULT 0
    );
    
    CREATE TABLE IF NOT EXISTS ShoppingCart (
        cartId INT AUTO_INCREMENT PRIMARY KEY,
        customerId INT NOT NULL,
        itemId INT NOT NULL,
        quantity INT NOT NULL,
        dateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customerId) REFERENCES Customer(customerId) ON DELETE CASCADE,
        FOREIGN KEY (itemId) REFERENCES Item(itemId)
    );
    
    CREATE TABLE IF NOT EXISTS Orders (
        orderId INT AUTO_INCREMENT PRIMARY KEY,
        dateOrdered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        customerName VARCHAR(255),
        customerId INT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
        FOREIGN KEY (customerId) REFERENCES Customer(customerId)
    );
    
    CREATE TABLE IF NOT EXISTS OrderDetails (
        orderDetailId INT AUTO_INCREMENT PRIMARY KEY,
        orderId INT NOT NULL,
        itemId INT NOT NULL,
        itemName VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        unitPrice DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        itemTax DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (orderId) REFERENCES Orders(orderId) ON DELETE CASCADE,
        FOREIGN KEY (itemId) REFERENCES Item(itemId)
    );
    
    CREATE TABLE IF NOT EXISTS Payment (
        paymentId INT AUTO_INCREMENT PRIMARY KEY,
        orderId INT NOT NULL,
        transactionId VARCHAR(64),
        cardLast4 VARCHAR(4),
        status VARCHAR(20) NOT NULL,
        message VARCHAR(255),
        createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (orderId) REFERENCES Orders(orderId)
    );
";

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());

    if ($conn->errno) {
        echo "Migration error: " . $conn->error;
        $conn->close();
        exit(1);
    } else {
        echo "Migration successful";
    }
} else {
    // Handle multi_query() failure
    echo "Migration error: Failed to execute migration queries. " . $conn->error;
    $conn->close();
    exit(1);
}

$conn->close();
