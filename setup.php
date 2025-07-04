<?php
// Database setup script
// This script will create the database and tables if they don't exist

echo "<h2>ConnectPro Agency Database Setup</h2>";

try {
    // First, connect without specifying database to create it
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute the schema file
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<div style='color: green; font-weight: bold; margin: 20px 0;'>";
    echo "✅ Database setup completed successfully!<br>";
    echo "✅ Tables created<br>";
    echo "✅ Default admin users added<br>";
    echo "✅ Sample services and content added<br>";
    echo "</div>";
    
    echo "<h3>Default Admin Credentials:</h3>";
    echo "<div style='background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>Super Admin:</strong><br>";
    echo "Email: admin@connectpro.com<br>";
    echo "Password: password<br><br>";
    echo "<strong>Content Admin:</strong><br>";
    echo "Email: content@connectpro.com<br>";
    echo "Password: password<br>";
    echo "</div>";
    
    echo "<p><a href='/Agency/admin/login.php' style='color: #007bff; text-decoration: none;'>→ Go to Admin Login</a></p>";
    echo "<p><a href='/Agency/index.php' style='color: #007bff; text-decoration: none;'>→ Go to Main Website</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-weight: bold; margin: 20px 0;'>";
    echo "❌ Database setup failed: " . $e->getMessage();
    echo "</div>";
    
    echo "<h3>Common Issues:</h3>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL service is running</li>";
    echo "<li>Check if MySQL root password is empty (default XAMPP setting)</li>";
    echo "<li>Verify database connection settings in config/database.php</li>";
    echo "</ul>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>ConnectPro Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h3 { color: #555; margin-top: 30px; }
    </style>
</head>
<body>
    <!-- PHP output will appear here -->
</body>
</html>
