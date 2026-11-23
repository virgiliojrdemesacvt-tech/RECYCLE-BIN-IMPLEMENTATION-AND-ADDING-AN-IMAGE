<?php
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/auth.php';
if (isCustomerLoggedIn()) { header("Location: portal.php"); exit; }
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Sign Up - Car Rental</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Customer Sign Up</h1>
    <?php showFlash(); ?>

    <form action="core/handleForms.php" method="POST">
        <p>
            <label for="firstName">First Name</label>
            <input type="text" name="firstName" id="firstName" value="<?php echo e($old['firstName'] ?? ''); ?>" required>
        </p>
        <p>
            <label for="lastName">Last Name</label>
            <input type="text" name="lastName" id="lastName" value="<?php echo e($old['lastName'] ?? ''); ?>" required>
        </p>
        <p>
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo e($old['email'] ?? ''); ?>" required>
        </p>
        <p>
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?php echo e($old['username'] ?? ''); ?>" required>
        </p>
        <p>
            <label for="dateOfBirth">Date of Birth</label>
            <input type="date" name="dateOfBirth" id="dateOfBirth" value="<?php echo e($old['dateOfBirth'] ?? ''); ?>" required>
        </p>
        <p>
            <label for="license_number">License Number</label>
            <input type="text" name="license_number" id="license_number" value="<?php echo e($old['license_number'] ?? ''); ?>" required>
        </p>
        <p>
            <label for="password">Password</label>
            <input type="password" name="password" id="password" minlength="8"
                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                   title="At least 8 characters with a number, an uppercase and a lowercase letter" required>
            <small class="hint">Min. 8 characters, with a number, an UPPERCASE and a lowercase letter.</small>
        </p>
        <p>
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" minlength="8" required>
            <input type="submit" name="customerRegisterBtn" value="Sign Up">
        </p>
    </form>
    <p class="switch">Already have an account? <a href="customer_login.php">Log in</a></p>
</body>
</html>
