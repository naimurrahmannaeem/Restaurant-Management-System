<?php
// config/db.php
// ── Database connection ──────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // change to your MySQL username
define('DB_PASS', '');           // change to your MySQL password
define('DB_NAME', 'restaurant_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');

// ── Session helper ───────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Helper: is customer logged in? ──────────────────────────
function isCustomerLoggedIn() {
    return isset($_SESSION['customer_id']);
}

// ── Helper: is admin logged in? ─────────────────────────────
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// ── Helper: redirect ────────────────────────────────────────
function redirect($url) {
    header("Location: $url");
    exit;
}

// ── Helper: sanitize input ───────────────────────────────────
function clean($conn, $data) {
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}
?>
