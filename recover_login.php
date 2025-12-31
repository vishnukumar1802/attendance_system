<?php
require_once 'config/db.php';

try {
    echo "This script manages Login Credentials recovery.\n\n";

    // 1. Reset Admin Password
    $admin_user = 'admin';
    $admin_pass = 'admin123';
    $hash = password_hash($admin_pass, PASSWORD_DEFAULT);

    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$admin_user]);

    if ($stmt->rowCount() > 0) {
        $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?")->execute([$hash, $admin_user]);
        echo "✅ Admin password reset.\n";
        echo "   Username: $admin_user\n";
        echo "   Password: $admin_pass\n";
    } else {
        // Create admin
        $pdo->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, 'super_admin')")->execute([$admin_user, $hash]);
        echo "✅ Admin account created.\n";
        echo "   Username: $admin_user\n";
        echo "   Password: $admin_pass\n";
    }

    echo "\n--------------------------------------\n\n";

    // 2. Check Employees
    $emps = $pdo->query("SELECT id, employee_id, first_name, last_name FROM employees LIMIT 5")->fetchAll();

    if (count($emps) > 0) {
        echo "✅ Existing Employees (Use these ID to login):\n";
        foreach ($emps as $e) {
            echo "   - " . $e['employee_id'] . " (" . $e['first_name'] . " " . $e['last_name'] . ")\n";
        }

        // Reset the first employee's password for testing
        $first_emp = $emps[0]['employee_id'];
        $emp_pass = 'password123';
        $emp_hash = password_hash($emp_pass, PASSWORD_DEFAULT);

        $pdo->prepare("UPDATE employees SET password = ? WHERE employee_id = ?")->execute([$emp_hash, $first_emp]);
        echo "\n✅ Reset password for first employee identified above:\n";
        echo "   ID: $first_emp\n";
        echo "   Password: $emp_pass\n";

    } else {
        echo "⚠️ No employees found. Use Admin panel to create one.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>