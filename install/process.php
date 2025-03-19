<?php
session_start();

try {
    // Validate form data
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';

    if (empty($admin_username) || empty($admin_password)) {
        throw new Exception('Por favor complete todos los campos');
    }

    // Create database directory if it doesn't exist
    $db_dir = __DIR__ . '/../database';
    if (!file_exists($db_dir)) {
        mkdir($db_dir, 0777, true);
    }

    // Initialize SQLite database
    $db_path = $db_dir . '/raffle.db';
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sqlite');
    $pdo->exec($schema);

    // Create admin user
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
    $stmt->execute([$admin_username, password_hash($admin_password, PASSWORD_DEFAULT)]);

    // Create config file
    $config_content = <<<PHP
<?php
session_start();

// Database configuration
define('DB_PATH', __DIR__ . '/../database/raffle.db');

// Initialize PDO connection
try {
    \$pdo = new PDO('sqlite:' . DB_PATH);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException \$e) {
    die('Connection failed: ' . \$e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset(\$_SESSION['admin_user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /admin/index.php');
        exit();
    }
}

// Create .installed file to prevent reinstallation
file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
PHP;

    // Create includes directory if it doesn't exist
    $includes_dir = __DIR__ . '/../includes';
    if (!file_exists($includes_dir)) {
        mkdir($includes_dir, 0777, true);
    }

    // Write config file
    file_put_contents($includes_dir . '/config.php', $config_content);

    // Create .installed file
    file_put_contents($includes_dir . '/.installed', date('Y-m-d H:i:s'));

    // Redirect to admin login with success message
    $_SESSION['success'] = 'Sistema instalado exitosamente';
    header('Location: ../admin/index.php');
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: index.php');
    exit();
}