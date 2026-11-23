<?php
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/auth.php';
requirePermission('restore');          // admin + moderator only (same as delete)
$deleted = getDeletedRentals($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycle Bin - Car Rental</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderUserBar(); showFlash(); ?>
    <a href="index.php">Return to dashboard</a>
    <h1>Recycle Bin - Deleted Rentals</h1>
    <p class="switch">Deleted rentals are kept here with a record of who deleted them and when. Restoring a rental puts it back on the customer's View Rentals page.</p>

    <?php if (count($deleted) === 0) { ?>
        <p>The recycle bin is empty.</p>
    <?php } else { ?>
    <div class="table-wrap">
    <table>
        <tr>
            <th>Image</th><th>Rental ID</th><th>Car Model</th><th>Rental Days</th><th>Customer</th>
            <th>Date Added</th><th>Deleted At</th><th>Deleted By</th><th>Action</th>
        </tr>
        <?php foreach ($deleted as $row) { ?>
        <tr>
            <td>
                <?php if (!empty($row['image_path'])) { ?>
                    <img class="thumb" src="<?php echo e($row['image_path']); ?>" alt="Rental image">
                <?php } else { ?>
                    <span class="switch">No image</span>
                <?php } ?>
            </td>
            <td>#<?php echo (int)$row['rental_id']; ?></td>
            <td><?php echo e($row['car_model']); ?></td>
            <td><?php echo e($row['rental_days']); ?></td>
            <td><?php echo e($row['customer_name']); ?></td>
            <td><?php echo e($row['date_added']); ?></td>
            <td><?php echo e($row['deleted_at']); ?></td>
            <td title="deleted_by (user_id) = <?php echo e($row['deleted_by_id'] ?? ''); ?>">
                <?php echo e(who($row['deleted_by_name'])); ?> <small>(#<?php echo e($row['deleted_by_id'] ?? '-'); ?>)</small>
            </td>
            <td>
                <form action="core/handleForms.php" method="POST">
                    <input type="hidden" name="rental_id" value="<?php echo (int)$row['rental_id']; ?>">
                    <input type="submit" name="restoreRentalBtn" value="Restore">
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
    </div>
    <?php } ?>
</body>
</html>
