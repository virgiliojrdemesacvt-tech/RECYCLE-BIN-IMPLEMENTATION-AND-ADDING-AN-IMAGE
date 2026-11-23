<?php
// Login / session / role helpers. Load AFTER dbConfig.php and models.php.
require_once __DIR__ . '/models.php';

// XSS DEFENSE 3 - HttpOnly session cookie: JavaScript (document.cookie) can no longer read the session ID,
// so even if a script gets injected it cannot steal the login session.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}

// XSS DEFENSE 4 - Security headers. The Content-Security-Policy tells the browser to run scripts and
// styles ONLY from this site's own files, so an injected <script> or inline handler is blocked.
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    if (!XSS_DEMO_MODE) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; "
             . "img-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'");
    }
}

// Redirect helper that works from the root folder AND from the core/ folder
function redirectTo($page) {
    $inCore = basename(dirname($_SERVER['SCRIPT_NAME'])) === 'core';
    header("Location: " . ($inCore ? "../" : "") . $page);
    exit;
}

/* ================= STAFF (admin / moderator / editor) ================= */

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

// Put this at the top of every STAFF page: not logged in -> login.php.
// The role is re-read from the database each time, so a role change by the admin applies immediately.
function requireLogin() {
    global $pdo;
    if (!isLoggedIn()) {
        redirectTo('login.php');
    }
    $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();
    if ($role === false) {                 // account was removed
        $_SESSION = [];
        redirectTo('login.php');
    }
    $_SESSION['role'] = $role;
}

function currentUserId() {
    return (int)($_SESSION['user_id'] ?? 0);
}

function currentRole() {
    return $_SESSION['role'] ?? '';
}

// WHO CAN DO WHAT
//   add / edit  : admin, moderator, editor
//   delete      : admin, moderator
//   restore     : admin, moderator (recycle bin - same people who can delete)
//   logs        : admin, moderator (read-only)
//   users       : admin only (change staff roles)
function can($action) {
    $matrix = [
        'add'     => ['admin', 'moderator', 'editor'],
        'edit'    => ['admin', 'moderator', 'editor'],
        'delete'  => ['admin', 'moderator'],
        'restore' => ['admin', 'moderator'],
        'logs'    => ['admin', 'moderator'],
        'users'   => ['admin'],
    ];
    return in_array(currentRole(), $matrix[$action] ?? [], true);
}

// Server-side check: hiding a button is not enough, the action itself is blocked too.
function requirePermission($action) {
    requireLogin();
    if (!can($action)) {
        global $pdo;
        logAsCurrent($pdo, 'ACCESS_DENIED', 'Tried to use: ' . $action);
        setFlash('error', 'Your role (' . currentRole() . ') is not allowed to do that.');
        redirectTo('index.php');
    }
}

function roleBadge($role) {
    return '<span class="badge role-' . e($role) . '">' . e($role) . '</span>';
}

// The "Logged in as ... [nav] [Logout]" bar shown on every staff page
function renderUserBar() {
    echo '<div class="userbar"><span class="who">Logged in as <b>' . e($_SESSION['full_name'] ?? '') . '</b> (@'
         . e($_SESSION['username'] ?? '') . ') ' . roleBadge(currentRole()) . '</span><nav class="nav">'
         . '<a href="index.php">Dashboard</a>'
         . (can('logs')    ? '<a href="logs.php">Activity Logs</a>' : '')
         . (can('restore') ? '<a href="recyclebin.php">Recycle Bin</a>' : '')
         . (can('users')   ? '<a href="users.php">Manage Users</a>' : '')
         . '<a class="logout" href="logout.php">Logout</a></nav></div>';
}

/* ================= CUSTOMERS (separate login) ================= */

function isCustomerLoggedIn() {
    return !empty($_SESSION['customer_account_id']);
}

function requireCustomerLogin() {
    if (!isCustomerLoggedIn()) {
        redirectTo('customer_login.php');
    }
}

function currentCustomerId() {
    return (int)($_SESSION['customer_id'] ?? 0);
}

function renderCustomerBar() {
    echo '<div class="userbar"><span class="who">Welcome, <b>' . e($_SESSION['customer_name'] ?? '') . '</b> '
         . '<span class="badge role-customer">customer</span></span><nav class="nav">'
         . '<a href="portal.php">My Rentals</a><a class="logout" href="logout.php">Logout</a></nav></div>';
}

/* ================= ACTIVITY LOG (uses the session to know who is acting) ================= */

function logAsCurrent($pdo, $action, $details = '') {
    if (isLoggedIn()) {
        logActivity($pdo, 'staff', currentUserId(), $_SESSION['full_name'] ?? '', currentRole(), $action, $details);
    } elseif (isCustomerLoggedIn()) {
        logActivity($pdo, 'customer', (int)$_SESSION['customer_account_id'], $_SESSION['customer_name'] ?? '', 'customer', $action, $details);
    }
}

/* ================= One-time messages (errors / success) ================= */

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function showFlash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert ' . e($f['type']) . '">' . e($f['message']) . '</div>';
    }
}
