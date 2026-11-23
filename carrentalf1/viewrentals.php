<?php
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/auth.php';
requireLogin();
$id  = requireId('customer_id');
$dev = getCustomerByID($pdo, $id);
if (!$dev) { header("Location: index.php"); exit; }
$q = substr(clean($_GET['q'] ?? ''), 0, 50);
$rentals = getRentalsByCustomer($pdo, $id, $q);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Rentals</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderUserBar(); showFlash(); ?>
    <a href="index.php">Return to home</a>
    <h1>Rentals of <?php echo e($dev['first_name'] . ' ' . $dev['last_name']); ?></h1>
    <?php if (can('restore')) { ?><p class="switch"><a href="recyclebin.php">View Recycle Bin</a></p><?php } ?>

    <?php if (can('add')) { ?>
    <form action="core/handleForms.php?customer_id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
        <p>
            <label for="carModel">Car Model</label>
            <input type="text" name="carModel" id="carModel" required>
        </p>
        <p>
            <label for="rental_days">Rental Days</label>
            <input type="text" name="rental_days" id="rental_days" required>
        </p>
        <p>
            <label for="car_image">Image (optional)</label>
            <input type="file" name="car_image" id="car_image" accept="image/jpeg,image/png,image/webp">
            <input type="submit" name="insertNewRentalBtn" value="Add Rental">
        </p>
        <p><small class="hint">One image per rental, showing the car or the issue reported. JPG, PNG or WEBP, 3 MB max.</small></p>
    </form>
    <?php } ?>

    <form class="searchbar" action="viewrentals.php" method="GET">
        <input type="hidden" name="customer_id" value="<?php echo $id; ?>">
        <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search car model or rental days...">
        <input type="submit" value="Search">
        <?php if ($q !== '') { ?><a class="clear" href="viewrentals.php?customer_id=<?php echo $id; ?>">Clear</a><?php } ?>
    </form>

    <?php if (count($rentals) === 0) { ?>
        <p>No rentals found<?php echo $q !== '' ? ' for "' . e($q) . '"' : ''; ?>.</p>
    <?php } else { ?>
    <div class="table-wrap">
    <table class="rentals-table">
        <tr>
            <th>Image</th>
            <th>Rental ID</th>
            <th>Car Model</th>
            <th>Rental Days</th>
            <th>Customer</th>
            <th>Date Added</th>
            <th>Added By</th>
            <th>Last Updated By</th>
            <th>Last Updated</th>
            <th>Action</th>
        </tr>
        <?php foreach ($rentals as $row) { ?>
        <tr>
            <td>
                <?php if (!empty($row['image_path'])) { ?>
                    <img class="thumb" src="<?php echo e($row['image_path']); ?>" alt="Rental image">
                <?php } else { ?>
                    <span class="switch">No image</span>
                <?php } ?>
            </td>
            <td><?php echo (int)$row['rental_id']; ?></td>
            <td><?php echo e($row['car_model']); ?></td>
            <td><?php echo e($row['rental_days']); ?></td>
            <td><?php echo e($row['customer_name']); ?></td>
            <td><?php echo e($row['date_added']); ?></td>
            <td><?php echo e(who($row['added_by_name'])); ?></td>
            <td title="last_updated_by (user_id) = <?php echo e($row['last_updated_by'] ?? ''); ?>"><?php echo e(who($row['updated_by_name'])); ?> <small>(#<?php echo e($row['last_updated_by'] ?? '-'); ?>)</small></td>
            <td><?php echo e($row['last_updated']); ?></td>
            <td>
                <?php if (can('edit')) { ?><a href="editrental.php?rental_id=<?php echo (int)$row['rental_id']; ?>&customer_id=<?php echo $id; ?>">Edit</a><?php } ?>
                <?php if (can('delete')) { ?><a href="deleterental.php?rental_id=<?php echo (int)$row['rental_id']; ?>&customer_id=<?php echo $id; ?>">Delete</a><?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>
    </div>
    <?php } ?>
</body>
</html>
