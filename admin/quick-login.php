<?php
// Quick login for testing
session_start();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $stmt = $db->prepare("SELECT * FROM admins WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['admin_id'];
            
            echo "<h2>Login Successful!</h2>";
            echo "<p>Welcome, " . $user['first_name'] . " " . $user['last_name'] . "</p>";
            echo "<p><a href='booking-details.php?id=8'>View Booking Details (ID: 8)</a></p>";
            echo "<p><a href='booking-details.php?id=1'>View Booking Details (ID: 1)</a></p>";
            echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
        } else {
            echo "<h2>Login Failed</h2>";
            echo "<p>Invalid credentials</p>";
        }
    }
} else {
    ?>
    <h2>Quick Admin Login</h2>
    <form method="POST">
        <div>
            <label>Email:</label>
            <input type="email" name="email" value="admin@agency.com" required>
        </div>
        <div>
            <label>Password:</label>
            <input type="password" name="password" value="admin123" required>
        </div>
        <button type="submit">Login</button>
    </form>
    
    <p>Default credentials: admin@agency.com / admin123</p>
    <?php
}
?>
