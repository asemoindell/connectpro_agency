<?php
// Set admin session for testing
session_start();

// Set admin session
$_SESSION['admin_id'] = 1;
$_SESSION['admin_logged_in'] = true;

echo "<h2>Admin Session Set</h2>";
echo "<p>Admin ID: " . $_SESSION['admin_id'] . "</p>";
echo "<p>Session Status: " . ($_SESSION['admin_logged_in'] ? 'Logged In' : 'Not logged in') . "</p>";

echo "<h3>Now try accessing:</h3>";
echo "<a href='booking-details.php?id=8'>Booking Details (ID: 8)</a><br>";
echo "<a href='booking-details.php?id=1'>Booking Details (ID: 1)</a><br>";
echo "<a href='test-booking-details.php'>Test Booking Details</a><br>";
?>
