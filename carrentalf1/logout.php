<?php
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/auth.php';   // also starts the session

$wasCustomer = isCustomerLoggedIn() && !isLoggedIn();
logAsCurrent($pdo, 'LOGOUT', $wasCustomer ? 'Customer logout' : 'Staff logout');

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Start a fresh session just to show the "logged out" message
session_start();
$_SESSION['flash'] = ['type' => 'ok', 'message' => 'You have been logged out.'];
header("Location: " . ($wasCustomer ? 'customer_login.php' : 'login.php'));
exit;
