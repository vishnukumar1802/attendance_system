<?php
require_once 'config/db.php';

echo "<h2>Database Update Script</h2>";

try {
    // 1. Add 'role' to admins table if not exists
    echo "Checking 'admins' table for 'role' column... ";
    $cols = $pdo->query("SHOW COLUMNS FROM admins LIKE 'role'")->fetchAll();
    if (count($cols) == 0) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN role ENUM('super_admin', 'sub_admin') DEFAULT 'sub_admin' AFTER password");
        echo "ADDED.<br>";

        // Make 'admin' user super_admin
        $pdo->exec("UPDATE admins SET role = 'super_admin' WHERE username = 'admin'");
        echo "Updated 'admin' user to 'super_admin'.<br>";
    } else {
        echo "ALREADY EXISTS.<br>";
    }

    // 2. Add 'monthly_salary' to employees table if not exists
    echo "Checking 'employees' table for 'monthly_salary' column... ";
    $cols = $pdo->query("SHOW COLUMNS FROM employees LIKE 'monthly_salary'")->fetchAll();
    if (count($cols) == 0) {
        $pdo->exec("ALTER TABLE employees ADD COLUMN monthly_salary DECIMAL(10, 2) DEFAULT 0.00 AFTER designation");
        echo "ADDED.<br>";

        // Update existing records: monthly = daily * 30
        $pdo->exec("UPDATE employees SET monthly_salary = salary_per_day * 30 WHERE monthly_salary = 0 AND salary_per_day > 0");
        echo "Updated existing employee monthly salaries.<br>";
    } else {
        echo "ALREADY EXISTS.<br>";
    }

    echo "<h3 style='color:green;'>Database updated successfully!</h3>";
    echo "<p><a href='admin/dashboard.php'>Go to Dashboard</a></p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>Error updating database: " . $e->getMessage() . "</h3>";
}
?>