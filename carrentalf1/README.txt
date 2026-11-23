CAR RENTAL - One-to-Many (ONE CUSTOMER has MANY RENTALS) with PDO
+ USER MODULE: Register / Login / Logout + "who added / who last updated" tracking

SETUP
1. Open XAMPP Control Panel and START Apache and MySQL.
2. Put this folder named "carrental3sss" inside C:\xampp\htdocs\
   Final path: C:\xampp\htdocs\carrental3sss\index.php
3. Open: http://localhost/carrental3sss/index.php
   (You will be sent to the login page. Click "Register here" to create an account.)

The database (carrental_db) and tables (users, customers, rentals) are created automatically.
If you already had customers / rentals data, the new columns (added_by, last_updated_by,
last_updated) are added automatically and your old data is kept.
Do NOT import any .php file into phpMyAdmin.

FILES ADDED
  register.php, login.php, logout.php, core/auth.php
FILES CHANGED
  core/dbConfig.php, core/models.php, core/handleForms.php, index.php, viewrentals.php,
  editcustomer.php, deletecustomer.php, editrental.php, deleterental.php, styles.css

XSS ASSIGNMENT (7 - Securing against XSS)
  core/models.php      : XSS_DEMO_MODE switch, e() output escaping, clean()/post() input sanitizing, validatePasswordStrength()
  core/auth.php        : HttpOnly session cookie + Content-Security-Policy and other security headers
  core/handleForms.php : all form input goes through post(); password policy enforced on register
  register.php         : HTML pattern + hint for the password rule
  viewrentals.php / styles.css : inline style moved to the stylesheet (CSP-friendly)
  To record the ATTACK screenshots set XSS_DEMO_MODE to true in core/models.php, then set it back to false.

ROLES, LOGS AND CUSTOMER LOGIN
  Staff login    : http://localhost/carrental3sss/login.php           (admin / moderator / editor)
  Customer login : http://localhost/carrental3sss/customer_login.php  (separate accounts, own portal)
  The FIRST staff account registered becomes the ADMIN. Later staff accounts start as EDITOR;
  the admin changes roles in "Manage Users".

  admin     : add, edit, delete, activity logs, manage users
  moderator : add, edit, delete, read activity logs
  editor    : add, edit only
  customer  : portal.php only - book a car, cancel own bookings (cannot open any staff page)

  New files: logs.php, users.php, customer_login.php, customer_register.php, portal.php
  Tables created automatically: activity_logs, customer_accounts (+ users.role column)

RECYCLE BIN + IMAGE PER RENTAL (children entity)
  Deleting a rental (viewrentals.php > Delete) no longer removes the row - it sets
  deleted_at and deleted_by on that row (core/models.php: deleteRental()).
  recyclebin.php (admin/moderator only, same as delete) lists every deleted rental with
  who deleted it and when, and a Restore button (restoreRental()) that brings it back.

  Each rental can have ONE image, uploaded when it's added (viewrentals.php's Add Rental
  form, accepts JPG/PNG/WEBP up to 3 MB). Stored as a random file name under
  /uploads/rentals/ and the relative path is saved in rentals.image_path.
  A thumbnail shows in the rentals table, the recycle bin, and the delete-confirm page.

  New/changed files: recyclebin.php, core/models.php (deleteRental, restoreRental,
  getDeletedRentals, handleRentalImageUpload), core/handleForms.php, viewrentals.php,
  deleterental.php, core/dbConfig.php (deleted_at, deleted_by, image_path columns),
  uploads/rentals/ (new folder, with .htaccess blocking script execution)
