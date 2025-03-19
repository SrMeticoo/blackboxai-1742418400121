<?php
session_start();

// Database configuration
define('DB_PATH', __DIR__ . '/../database/raffle.db');

// Initialize PDO connection
try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['admin_user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /admin/index.php');
        exit();
    }
}

// Create .installed file to prevent reinstallation
file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));