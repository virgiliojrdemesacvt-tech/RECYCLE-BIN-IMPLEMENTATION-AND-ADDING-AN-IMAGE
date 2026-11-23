<?php
require_once __DIR__ . '/dbConfig.php';
require_once __DIR__ . '/models.php';
require_once __DIR__ . '/auth.php';

/* ================= STAFF ACCOUNTS (no login needed) ================= */

if (isset($_POST['registerBtn'])) {
    $first   = post('firstName');
    $last    = post('lastName');
    $address = post('address');
    $age     = post('age');
    $contact = post('contact_no');
    $email   = post('email');
    $uname   = post('username');
    $pw      = $_POST['password'] ?? '';
    $pw2     = $_POST['confirm_password'] ?? '';

    $_SESSION['old'] = ['firstName' => $first, 'lastName' => $last, 'address' => $address, 'age' => $age,
                        'contact_no' => $contact, 'email' => $email, 'username' => $uname];

    // The very first staff account becomes the ADMIN; everyone after that starts as an EDITOR.
    $newRole = (countRows($pdo, 'users') === 0) ? 'admin' : 'editor';

    if ($first === '' || $last === '' || $address === '') {
        setFlash('error', 'First name, last name and address are required.');
    } elseif (!ctype_digit($age) || (int)$age < 1 || (int)$age > 120) {
        setFlash('error', 'Please enter a valid age.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Please enter a valid email address.');
    } elseif (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $uname)) {
        setFlash('error', 'Username must be 4-30 characters: letters, numbers or underscore.');
    } elseif (($pwError = validatePasswordStrength($pw)) !== null) {
        setFlash('error', $pwError);   // min 8 chars + number + UPPERCASE + lowercase
    } elseif ($pw !== $pw2) {
        setFlash('error', 'Passwords do not match.');
    } elseif (userExists($pdo, $uname, $email)) {
        setFlash('error', 'That username or email is already taken.');
    } elseif (insertUser($pdo, $first, $last, $address, (int)$age, $contact ?: null, $email, $uname, $pw, $newRole)) {
        unset($_SESSION['old']);
        logActivity($pdo, 'staff', null, $uname, $newRole, 'REGISTER_STAFF', "New {$newRole} account: {$uname}");
        setFlash('ok', 'Registration successful! You may now log in.');
        redirectTo('login.php');
    } else {
        setFlash('error', 'Registration failed. Please try again.');
    }
    redirectTo('register.php');
}

if (isset($_POST['loginBtn'])) {
    $uname = post('username');
    $user  = getUserByUsername($pdo, $uname);

    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);
        // a staff login replaces any customer login in this browser
        unset($_SESSION['customer_account_id'], $_SESSION['customer_id'], $_SESSION['customer_name']);
        $_SESSION['user_id']   = (int)$user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['role']      = $user['role'];
        logActivity($pdo, 'staff', (int)$user['user_id'], $_SESSION['full_name'], $user['role'], 'LOGIN', 'Staff login');
        redirectTo('index.php');
    }
    logActivity($pdo, 'staff', null, $uname, '-', 'LOGIN_FAILED', 'Wrong username or password (staff)');
    setFlash('error', 'Invalid username or password.');
    redirectTo('login.php');
}

/* ================= CUSTOMER ACCOUNTS (separate from staff) ================= */

if (isset($_POST['customerRegisterBtn'])) {
    $first   = post('firstName');
    $last    = post('lastName');
    $email   = post('email');
    $uname   = post('username');
    $dob     = post('dateOfBirth');
    $license = post('license_number');
    $pw      = $_POST['password'] ?? '';
    $pw2     = $_POST['confirm_password'] ?? '';

    $_SESSION['old'] = ['firstName' => $first, 'lastName' => $last, 'email' => $email, 'username' => $uname,
                        'dateOfBirth' => $dob, 'license_number' => $license];

    $d = DateTime::createFromFormat('Y-m-d', $dob);
    if ($first === '' || $last === '') {
        setFlash('error', 'First name and last name are required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Please enter a valid email address.');
    } elseif (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $uname)) {
        setFlash('error', 'Username must be 4-30 characters: letters, numbers or underscore.');
    } elseif (!$d || $d->format('Y-m-d') !== $dob || $d > new DateTime('-18 years')) {
        setFlash('error', 'You must be at least 18 years old to rent a car.');
    } elseif (!XSS_DEMO_MODE && !preg_match('/^[A-Za-z0-9\- ]{5,30}$/', $license)) {
        setFlash('error', 'License number must be 5-30 characters (letters, numbers, dash).');
    } elseif (($pwError = validatePasswordStrength($pw)) !== null) {
        setFlash('error', $pwError);
    } elseif ($pw !== $pw2) {
        setFlash('error', 'Passwords do not match.');
    } elseif (customerAccountExists($pdo, $uname, $email)) {
        setFlash('error', 'That username or email is already taken.');
    } elseif (($newId = insertCustomerAccount($pdo, $first, $last, $dob, $license, $email, $uname, $pw)) !== false) {
        unset($_SESSION['old']);
        logActivity($pdo, 'customer', $newId, "{$first} {$last}", 'customer', 'REGISTER_CUSTOMER', "New customer account: {$uname}");
        setFlash('ok', 'Account created! You may now log in.');
        redirectTo('customer_login.php');
    } else {
        setFlash('error', 'Registration failed. Please try again.');
    }
    redirectTo('customer_register.php');
}

if (isset($_POST['customerLoginBtn'])) {
    $uname = post('username');
    $acct  = getCustomerAccountByUsername($pdo, $uname);

    if ($acct && password_verify($_POST['password'] ?? '', $acct['password_hash'])) {
        session_regenerate_id(true);
        // a customer login replaces any staff login in this browser
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['full_name'], $_SESSION['role']);
        $_SESSION['customer_account_id'] = (int)$acct['account_id'];
        $_SESSION['customer_id']         = (int)$acct['customer_id'];
        $_SESSION['customer_name']       = $acct['first_name'] . ' ' . $acct['last_name'];
        logActivity($pdo, 'customer', (int)$acct['account_id'], $_SESSION['customer_name'], 'customer', 'LOGIN', 'Customer login');
        redirectTo('portal.php');
    }
    logActivity($pdo, 'customer', null, $uname, '-', 'LOGIN_FAILED', 'Wrong username or password (customer)');
    setFlash('error', 'Invalid username or password.');
    redirectTo('customer_login.php');
}

if (isset($_POST['bookCarBtn'])) {
    requireCustomerLogin();
    $car  = post('carModel');
    $days = post('rental_days');

    if (!array_key_exists($car, carFleet())) {
        setFlash('error', 'Please choose a car from the list.');
    } elseif (!ctype_digit($days) || (int)$days < 1 || (int)$days > 60) {
        setFlash('error', 'Rental days must be a number from 1 to 60.');
    } elseif (insertRental($pdo, $car, $days, currentCustomerId(), null)) {
        logAsCurrent($pdo, 'BOOK_CAR', "Booked {$car} for {$days} day(s)");
        setFlash('ok', "Booked! {$car} for {$days} day(s).");
    } else {
        setFlash('error', 'Booking failed. Please try again.');
    }
    redirectTo('portal.php');
}

if (isset($_POST['cancelBookingBtn'])) {
    requireCustomerLogin();
    $rid = ctype_digit($_POST['rental_id'] ?? '') ? (int)$_POST['rental_id'] : 0;
    // a customer can only cancel THEIR OWN bookings
    if ($rid > 0 && rentalBelongsTo($pdo, $rid, currentCustomerId()) && deleteRental($pdo, $rid)) {
        logAsCurrent($pdo, 'CANCEL_BOOKING', "Cancelled booking #{$rid}");
        setFlash('ok', 'Booking cancelled.');
    } else {
        setFlash('error', 'That booking could not be cancelled.');
    }
    redirectTo('portal.php');
}

/* ======= EVERYTHING BELOW NEEDS A LOGGED-IN STAFF MEMBER ======= */
requireLogin();
$userId = currentUserId();   // the user who is doing the insert / update

/* ---------- STAFF ROLES (admin only) ---------- */

if (isset($_POST['changeRoleBtn'])) {
    requirePermission('users');
    $targetId = ctype_digit($_POST['user_id'] ?? '') ? (int)$_POST['user_id'] : 0;
    $newRole  = $_POST['role'] ?? '';
    $target   = $targetId ? getUserById($pdo, $targetId) : false;

    if (!$target || !in_array($newRole, staffRoles(), true)) {
        setFlash('error', 'Invalid user or role.');
    } elseif ($targetId === $userId) {
        setFlash('error', 'You cannot change your own role.');
    } elseif (updateUserRole($pdo, $targetId, $newRole)) {
        logAsCurrent($pdo, 'CHANGE_ROLE', "@{$target['username']}: {$target['role']} -> {$newRole}");
        setFlash('ok', "Role updated: @{$target['username']} is now {$newRole}.");
    } else {
        setFlash('error', 'Could not change the role.');
    }
    redirectTo('users.php');
}

/* ---------- CUSTOMERS (the "one" side) ---------- */

if (isset($_POST['insertCustomerBtn'])) {
    requirePermission('add');
    $first = post('firstName');
    $last  = post('lastName');
    $query = insertCustomer($pdo, post('username'), $first,
        $last, post('dateOfBirth'), post('license_number'), $userId);

    if ($query) {
        logAsCurrent($pdo, 'ADD_CUSTOMER', "Added customer: {$first} {$last}");
        setFlash('ok', 'Customer added.');
        header("Location: ../index.php"); exit;
    }
    echo "Insertion failed";
}

if (isset($_POST['editCustomerBtn'])) {
    requirePermission('edit');
    $cid   = requireId('customer_id');
    $first = post('firstName');
    $last  = post('lastName');
    $query = updateCustomer($pdo, $first, $last,
        post('dateOfBirth'), post('license_number'), $cid, $userId);

    if ($query) {
        logAsCurrent($pdo, 'EDIT_CUSTOMER', "Edited customer #{$cid}: {$first} {$last}");
        setFlash('ok', 'Customer updated.');
        header("Location: ../index.php"); exit;
    }
    echo "Edit failed";
}

if (isset($_POST['deleteCustomerBtn'])) {
    requirePermission('delete');
    $cid  = requireId('customer_id');
    $old  = getCustomerByID($pdo, $cid);
    $query = deleteCustomer($pdo, $cid);

    if ($query) {
        $label = $old ? $old['first_name'] . ' ' . $old['last_name'] : '';
        logAsCurrent($pdo, 'DELETE_CUSTOMER', "Deleted customer #{$cid} {$label}");
        setFlash('ok', 'Customer deleted.');
        header("Location: ../index.php"); exit;
    }
    echo "Deletion failed";
}

/* ---------- RENTALS ---------- */

if (isset($_POST['insertNewRentalBtn'])) {
    requirePermission('add');
    $customerId = requireId('customer_id');
    $car  = post('carModel');
    $days = post('rental_days');

    $imagePath = handleRentalImageUpload($_FILES['car_image'] ?? null);
    if ($imagePath === false) {
        setFlash('error', 'Image must be JPG, PNG or WEBP and 3 MB or smaller.');
        header("Location: ../viewrentals.php?customer_id=" . $customerId); exit;
    }

    $query = insertRental($pdo, $car, $days, $customerId, $userId, $imagePath);

    if ($query) {
        $withImg = $imagePath ? ' with image' : '';
        logAsCurrent($pdo, 'ADD_RENTAL', "Added rental: {$car} ({$days} day/s) for customer #{$customerId}{$withImg}");
        setFlash('ok', 'Rental added.');
        header("Location: ../viewrentals.php?customer_id=" . $customerId); exit;
    }
    echo "Insertion failed";
}

if (isset($_POST['editRentalBtn'])) {
    requirePermission('edit');
    $customerId = requireId('customer_id');
    $rentalId   = requireId('rental_id');
    $car  = post('carModel');
    $days = post('rental_days');
    $query = updateRental($pdo, $car, $days, $rentalId, $userId);

    if ($query) {
        logAsCurrent($pdo, 'EDIT_RENTAL', "Edited rental #{$rentalId}: {$car} ({$days} day/s)");
        setFlash('ok', 'Rental updated.');
        header("Location: ../viewrentals.php?customer_id=" . $customerId); exit;
    }
    echo "Update failed";
}

if (isset($_POST['deleteRentalBtn'])) {
    requirePermission('delete');
    $customerId = requireId('customer_id');
    $rentalId   = requireId('rental_id');
    $query = deleteRental($pdo, $rentalId, $userId);   // soft delete: moves it to the recycle bin

    if ($query) {
        logAsCurrent($pdo, 'DELETE_RENTAL', "Deleted rental #{$rentalId} of customer #{$customerId} (moved to recycle bin)");
        setFlash('ok', 'Rental moved to the recycle bin.');
        header("Location: ../viewrentals.php?customer_id=" . $customerId); exit;
    }
    echo "Deletion failed";
}

/* ---------- RECYCLE BIN ---------- */

if (isset($_POST['restoreRentalBtn'])) {
    requirePermission('restore');
    $rentalId = ctype_digit($_POST['rental_id'] ?? '') ? (int)$_POST['rental_id'] : 0;
    $old = $rentalId ? getDeletedRentalById($pdo, $rentalId) : false;

    if ($old && restoreRental($pdo, $rentalId)) {
        logAsCurrent($pdo, 'RESTORE_RENTAL', "Restored rental #{$rentalId} (customer #{$old['customer_id']})");
        setFlash('ok', 'Rental restored.');
    } else {
        setFlash('error', 'Could not restore that rental.');
    }
    redirectTo('recyclebin.php');
}
