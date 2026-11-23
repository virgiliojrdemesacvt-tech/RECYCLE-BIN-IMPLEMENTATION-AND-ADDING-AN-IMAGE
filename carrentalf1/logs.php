<?php
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/auth.php';
requirePermission('logs');            // admin + moderator only (read-only)
$action  = clean($_GET['action'] ?? '');
$logs    = getLogs($pdo, $action, 300);
$actions = getLogActions($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderUserBar(); showFlash(); ?>
    <a href="index.php">Return to dashboard</a>
    <h1>Activity Logs</h1>

    <form class="searchbar" action="logs.php" method="GET">
        <select name="action">
            <option value="">All actions</option>
            <?php foreach ($actions as $a) { ?>
                <option value="<?php echo e($a); ?>" <?php echo $a === $action ? 'selected' : ''; ?>><?php echo e($a); ?></option>
            <?php } ?>
        </select>
        <input type="submit" value="Filter">
        <?php if ($action !== '') { ?><a class="clear" href="logs.php">Clear</a><?php } ?>
    </form>

    <?php if (count($logs) === 0) { ?>
        <p>No activity recorded yet.</p>
    <?php } else { ?>
    <div class="table-wrap">
    <table>
        <tr>
            <th>#</th><th>Date / Time</th><th>Who</th><th>Role</th><th>Action</th><th>Details</th><th>IP</th>
        </tr>
        <?php foreach ($logs as $row) { ?>
        <tr>
            <td><?php echo (int)$row['log_id']; ?></td>
            <td><?php echo e($row['created_at']); ?></td>
            <td><?php echo e($row['actor_name']); ?></td>
            <td><?php echo e($row['role']); ?></td>
            <td><span class="act <?php echo e(actClass($row['action'])); ?>"><?php echo e($row['action']); ?></span></td>
            <td><?php echo e($row['details']); ?></td>
            <td><?php echo e($row['ip_address']); ?></td>
        </tr>
        <?php } ?>
    </table>
    </div>
    <p class="switch">Showing the latest <?php echo count($logs); ?> entries.</p>
    <?php } ?>
</body>
</html>
