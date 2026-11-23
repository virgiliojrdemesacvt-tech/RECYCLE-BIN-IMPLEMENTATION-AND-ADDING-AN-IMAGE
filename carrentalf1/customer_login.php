<?php
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/auth.php';
if (isCustomerLoggedIn()) { header("Location: portal.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Car Rental</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Customer Login</h1>
    <?php showFlash(); ?>

    <form action="core/handleForms.php" method="POST">
        <p>
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required autofocus>
        </p>
        <p>
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
            <input type="submit" name="customerLoginBtn" value="Login">
        </p>
    </form>
    <p class="switch">New customer? <a href="customer_register.php">Create an account</a></p>
    <p class="switch">Are you staff? <a href="login.php">Staff login</a></p>
</body>
</html>
