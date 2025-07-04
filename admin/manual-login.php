<?php
session_start();

// Manual admin login for testing
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Super Admin';
$_SESSION['admin_email'] = 'admin@connectpro.com';
$_SESSION['admin_role'] = 'super-admin';

echo "<h2>Manual Admin Login Successful</h2>";
echo "<p>Session created with admin_id: " . $_SESSION['admin_id'] . "</p>";
echo "<p><a href='users.php'>Test User Management Page</a></p>";
echo "<p><a href='debug-session.php'>Check Session</a></p>";
?>
