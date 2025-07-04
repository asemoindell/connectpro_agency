<?php
// Create sample data for testing
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed!");
}

echo "<h1>Creating Sample Data</h1>";

// Create admin user
try {
    $stmt = $db->prepare("INSERT IGNORE INTO admins (admin_id, first_name, last_name, email, password, role, status) VALUES (1, 'Super', 'Admin', 'admin@agency.com', ?, 'super-admin', 'active')");
    $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
    echo "✓ Admin user created (admin@agency.com / admin123)<br>";
} catch (Exception $e) {
    echo "❌ Admin user error: " . $e->getMessage() . "<br>";
}

// Create a test user
try {
    $stmt = $db->prepare("INSERT IGNORE INTO users (user_id, first_name, last_name, email, phone, password, status) VALUES (1, 'John', 'Doe', 'john@example.com', '123-456-7890', ?, 'active')");
    $stmt->execute([password_hash('password123', PASSWORD_DEFAULT)]);
    echo "✓ Test user created (john@example.com)<br>";
} catch (Exception $e) {
    echo "❌ Test user error: " . $e->getMessage() . "<br>";
}

// Create a test service
try {
    $stmt = $db->prepare("INSERT IGNORE INTO services (id, title, category, description, base_price, status) VALUES (1, 'Website Development', 'Web Development', 'Custom website development service', 999.99, 'active')");
    $stmt->execute();
    echo "✓ Test service created<br>";
} catch (Exception $e) {
    echo "❌ Test service error: " . $e->getMessage() . "<br>";
}

// Create test bookings
for ($i = 1; $i <= 10; $i++) {
    try {
        $stmt = $db->prepare("INSERT IGNORE INTO service_bookings (
            id, booking_reference, user_id, service_id, client_name, client_email, client_phone, 
            service_details, urgency_level, status, booking_date, quoted_price, agent_fee, total_amount, 
            assigned_admin_id, admin_notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)");
        
        $booking_ref = 'BK-' . str_pad($i, 6, '0', STR_PAD_LEFT);
        $statuses = ['pending', 'confirmed', 'approved', 'in_progress', 'completed'];
        $urgency = ['low', 'medium', 'high'];
        
        $stmt->execute([
            $i,
            $booking_ref,
            1, // user_id
            1, // service_id
            "Client Name $i",
            "client$i@example.com",
            "123-456-78" . str_pad($i, 2, '0', STR_PAD_LEFT),
            "Test booking details for booking $i",
            $urgency[($i - 1) % 3],
            $statuses[($i - 1) % 5],
            500.00 + ($i * 100),
            50.00 + ($i * 10),
            550.00 + ($i * 110),
            1, // assigned_admin_id
            "Test admin notes for booking $i"
        ]);
        
        echo "✓ Booking $i created ($booking_ref)<br>";
    } catch (Exception $e) {
        echo "❌ Booking $i error: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>Sample Data Created!</h2>";
echo "<p>You can now access:</p>";
echo "<ul>";
for ($i = 1; $i <= 10; $i++) {
    echo "<li><a href='booking-details.php?id=$i'>Booking Details (ID: $i)</a></li>";
}
echo "</ul>";

echo "<p>Admin login: admin@agency.com / admin123</p>";
echo "<p><a href='login.php'>Login to Admin Panel</a></p>";
?>
