<?php
session_start();

// Check if system is installed
if (!file_exists(__DIR__ . '/.installed')) {
    // If accessing from a subdirectory of install, go up one level
    if (strpos($_SERVER['PHP_SELF'], '/install/') !== false) {
        header('Location: index.php');
    } else {
        header('Location: install/index.php');
    }
    exit();
}

// Database configuration - These will be set during installation
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

// Initialize PDO connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // If we're in the installation process, don't show the error
    if (strpos($_SERVER['PHP_SELF'], '/install/') === false) {
        die('Connection failed: ' . $e->getMessage());
    }
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