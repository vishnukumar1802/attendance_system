<?php
require_once 'config/db.php';

try {
    echo "Updating database schema...\n";

    // 1. Update Attendance Status ENUM
    // Note: Changing ENUM in MySQL can be tricky if data exists, but adding values is usually fine.
    // We execute specific ALTER TABLE.
    $pdo->exec("ALTER TABLE attendance MODIFY COLUMN status ENUM('present', 'absent', 'pending', 'rejected', 'half_day', 'leave', 'holiday') DEFAULT 'pending'");
    echo "Updated attendance status enum.\n";

    // 2. Create Holidays Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS holidays (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created holidays table.\n";

    // 3. Create Leaves Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS leaves (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        type ENUM('leave', 'wfh') NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )");
    echo "Created leaves table.\n";

    // 4. Create Teams Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created teams table.\n";

    // 5. Add team_id column to employees if it doesn't exist
    $colCheck = $pdo->query("SHOW COLUMNS FROM employees LIKE 'team_id'");
    if ($colCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE employees ADD COLUMN team_id INT NULL");
        $pdo->exec("ALTER TABLE employees ADD FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL");
        echo "Added team_id to employees.\n";
    }

    // 6. Create Tasks Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        assigned_to INT NOT NULL,
        assigned_by INT NOT NULL,
        category ENUM('development', 'testing', 'design', 'meeting') NOT NULL,
        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
        due_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (assigned_to) REFERENCES employees(id) ON DELETE CASCADE
    )");
    echo "Created tasks table.\n";

    // 7. Create Notifications Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
    )");
    echo "Created notifications table.\n";

    // 8. Create Settings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value VARCHAR(255)
    )");
    // Insert default lock date if not exists
    $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('attendance_lock_date', '2000-01-01')");
    echo "Created settings table.\n";

    echo "Database update completed successfully!";

} catch (PDOException $e) {
    die("DB Update Failed: " . $e->getMessage());
}
?>