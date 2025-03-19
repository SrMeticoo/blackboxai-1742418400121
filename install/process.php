<?php
session_start();

try {
    // Validate form data
    $db_host = $_POST['db_host'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';

    if (empty($db_host) || empty($db_name) || empty($db_user) || empty($admin_username) || empty($admin_password)) {
        throw new Exception('Por favor complete todos los campos requeridos');
    }

    // Test database connection
    try {
        $pdo = new PDO(
            "mysql:host=$db_host;charset=utf8mb4",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");

        // Execute schema
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($schema);

        // Create admin user
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
        $stmt->execute([$admin_username, password_hash($admin_password, PASSWORD_DEFAULT)]);

        // Create config file content
        $config_content = <<<PHP
<?php
session_start();

// Database configuration
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

// Initialize PDO connection
try {
    \$pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
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

    } catch (PDOException $e) {
        throw new Exception('Error de conexión a la base de datos: ' . $e->getMessage());
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: index.php');
    exit();
}