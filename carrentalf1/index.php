<?php
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/auth.php';
requireLogin();                       // not logged in -> redirected to login.php
$q = substr(clean($_GET['q'] ?? ''), 0, 50);
$customers = getAllCustomers($pdo, $q);
$totalCustomers = countRows($pdo, 'customers');
$totalRentals   = countRows($pdo, 'rentals');
$totalAccounts  = countRows($pdo, 'customer_accounts');
$totalStaff     = countRows($pdo, 'users');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderUserBar(); showFlash(); ?>
    <h1>Car Rental Dashboard</h1>

    <div class="stats">
        <div class="stat"><span class="num"><?php echo $totalCustomers; ?></span><span class="lbl">Customers</span></div>
        <div class="stat"><span class="num"><?php echo $totalRentals; ?></span><span class="lbl">Rentals</span></div>
        <div class="stat"><span class="num"><?php echo $totalAccounts; ?></span><span class="lbl">Online accounts</span></div>
        <div class="stat"><span class="num"><?php echo $totalStaff; ?></span><span class="lbl">Staff</span></div>
    </div>

    <?php if (can('add')) { ?>
    <h2>Register a new customer</h2>
    <form action="core/handleForms.php" method="POST">
        <p>
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>
        </p>
        <p>
            <label for="firstName">First Name</label>
            <input type="text" name="firstName" id="firstName" required>
        </p>
        <p>
            <label for="lastName">Last Name</label>
            <input type="text" name="lastName" id="lastName" required>
        </p>
        <p>
            <label for="dateOfBirth">Date of Birth</label>
            <input type="date" name="dateOfBirth" id="dateOfBirth" required>
        </p>
        <p>
            <label for="license_number">License Number</label>
            <input type="text" name="license_number" id="license_number" required>
            <input type="submit" name="insertCustomerBtn" value="Add Customer">
        </p>
    </form>
    <?php } ?>

    <h2>Registered Customers</h2>
    <form class="searchbar" action="index.php" method="GET">
        <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search name, username or license...">
        <input type="submit" value="Search">
        <?php if ($q !== '') { ?><a class="clear" href="index.php">Clear</a><?php } ?>
    </form>

    <?php if (count($customers) === 0) { ?>
        <p>No customers found.</p>
    <?php } else { ?>
    <div class="table-wrap">
    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Date of Birth</th>
            <th>License Number</th>
            <th>Date Added</th>
            <th>Added By</th>
            <th>Last Updated By</th>
            <th>Last Updated</th>
            <th>Action</th>
        </tr>
        <?php foreach ($customers as $row) { ?>
        <tr>
            <td><?php echo (int)$row['customer_id']; ?></td>
            <td><?php echo e($row['username']); ?></td>
            <td><?php echo e($row['first_name']); ?></td>
            <td><?php echo e($row['last_name']); ?></td>
            <td><?php echo e($row['date_of_birth']); ?></td>
            <td><?php echo e($row['license_number']); ?></td>
            <td><?php echo e($row['date_added']); ?></td>
            <td><?php echo e(who($row['added_by_name'])); ?></td>
            <td title="last_updated_by (user_id) = <?php echo e($row['last_updated_by'] ?? ''); ?>"><?php echo e(who($row['updated_by_name'])); ?> <small>(#<?php echo e($row['last_updated_by'] ?? '-'); ?>)</small></td>
            <td><?php echo e($row['last_updated']); ?></td>
            <td class="actions">
                <a href="viewrentals.php?customer_id=<?php echo (int)$row['customer_id']; ?>">View Rentals</a>
                <?php if (can('edit')) { ?><a href="editcustomer.php?customer_id=<?php echo (int)$row['customer_id']; ?>">Edit</a><?php } ?>
                <?php if (can('delete')) { ?><a href="deletecustomer.php?customer_id=<?php echo (int)$row['customer_id']; ?>">Delete</a><?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>
    </div>
    <?php } ?>
</body>
</html>
