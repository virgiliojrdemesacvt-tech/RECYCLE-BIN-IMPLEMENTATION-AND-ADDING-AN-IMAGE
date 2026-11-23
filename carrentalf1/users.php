<?php
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/auth.php';
requirePermission('users');           // admin only
$users = getAllUsers($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderUserBar(); showFlash(); ?>
    <a href="index.php">Return to dashboard</a>
    <h1>Manage Staff Accounts</h1>

    <div class="roles">
        <div class="rolecard"><?php echo roleBadge('admin'); ?><p>Everything: add, edit, delete, activity logs and changing staff roles.</p></div>
        <div class="rolecard"><?php echo roleBadge('moderator'); ?><p>Add, edit and delete customers and rentals. Can read the activity logs.</p></div>
        <div class="rolecard"><?php echo roleBadge('editor'); ?><p>Add and edit customers and rentals only. No delete, no logs.</p></div>
        <div class="rolecard"><span class="badge role-customer">customer</span><p>Separate login. Can only book and cancel their own rentals.</p></div>
    </div>

    <div class="table-wrap">
    <table>
        <tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Registered</th><th>Change role</th></tr>
        <?php foreach ($users as $u) { ?>
        <tr>
            <td><?php echo (int)$u['user_id']; ?></td>
            <td><?php echo e($u['first_name'] . ' ' . $u['last_name']); ?></td>
            <td><?php echo e($u['username']); ?></td>
            <td><?php echo e($u['email']); ?></td>
            <td><?php echo roleBadge($u['role']); ?></td>
            <td><?php echo e($u['date_registered']); ?></td>
            <td>
                <?php if ((int)$u['user_id'] === currentUserId()) { ?>
                    <em>(you)</em>
                <?php } else { ?>
                <form action="core/handleForms.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                    <select name="role">
                        <?php foreach (staffRoles() as $r) { ?>
                            <option value="<?php echo e($r); ?>" <?php echo $r === $u['role'] ? 'selected' : ''; ?>><?php echo e($r); ?></option>
                        <?php } ?>
                    </select>
                    <input type="submit" name="changeRoleBtn" value="Save">
                </form>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>
    </div>
</body>
</html>
