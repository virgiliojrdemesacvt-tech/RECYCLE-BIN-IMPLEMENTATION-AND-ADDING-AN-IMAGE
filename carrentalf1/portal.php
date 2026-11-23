<?php
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/auth.php';
requireCustomerLogin();               // customers only; staff pages stay closed to them
$me = getCustomerByID($pdo, currentCustomerId());
if (!$me) { header("Location: logout.php"); exit; }
$rentals = getRentalsByCustomer($pdo, currentCustomerId());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rentals - Car Rental</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderCustomerBar(); showFlash(); ?>
    <h1>My Rentals</h1>

    <h2>Our fleet</h2>
    <div class="fleet">
        <?php foreach (carFleet() as $name => $icon) { ?>
            <div class="car"><span class="icon"><?php echo e($icon); ?></span><span><?php echo e($name); ?></span></div>
        <?php } ?>
    </div>

    <h2>Book a car</h2>
    <form action="core/handleForms.php" method="POST">
        <p>
            <label for="carModel">Car</label>
            <select name="carModel" id="carModel" required>
                <?php foreach (carFleet() as $name => $icon) { ?>
                    <option value="<?php echo e($name); ?>"><?php echo e($icon . ' ' . $name); ?></option>
                <?php } ?>
            </select>
        </p>
        <p>
            <label for="rental_days">Rental Days</label>
            <input type="number" name="rental_days" id="rental_days" min="1" max="60" value="1" required>
            <input type="submit" name="bookCarBtn" value="Book Now">
        </p>
    </form>

    <h2>My bookings</h2>
    <?php if (count($rentals) === 0) { ?>
        <p>You have no bookings yet. Pick a car above.</p>
    <?php } else { ?>
    <div class="table-wrap">
    <table>
        <tr><th>Booking</th><th>Car</th><th>Days</th><th>Booked on</th><th>Last updated</th><th>Action</th></tr>
        <?php foreach ($rentals as $row) { ?>
        <tr>
            <td>#<?php echo (int)$row['rental_id']; ?></td>
            <td><?php echo e($row['car_model']); ?></td>
            <td><?php echo e($row['rental_days']); ?></td>
            <td><?php echo e($row['date_added']); ?></td>
            <td><?php echo e($row['last_updated']); ?></td>
            <td>
                <form action="core/handleForms.php" method="POST">
                    <input type="hidden" name="rental_id" value="<?php echo (int)$row['rental_id']; ?>">
                    <input type="submit" name="cancelBookingBtn" value="Cancel">
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
    </div>
    <?php } ?>
</body>
</html>
