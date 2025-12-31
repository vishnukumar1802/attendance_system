<?php
require_once 'config/db.php';

try {
    echo "Upgrading Team Module...\n";

    // 1. Create team_members table
    // added_by is INT. If Admin added, we might use a special ID or just 0? 
    // Employees have IDs usually starting at 1. Admins table has IDs too.
    // Let's assume positive INT is Emp ID. 0 or negative could be Admin? 
    // Or we just store the ID and don't enforce FK to employees strictly if it can be Admin.
    // Actually, Prompt says "added_by". 
    // Let's just use '0' for System/Admin to allow Foreign Key we would need a Users table combined.
    // Since we have separate `admins` and `employees` tables, we can't have a single FK.
    // So we won't put a strict FK on `added_by`.

    $sql = "CREATE TABLE IF NOT EXISTS team_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT NOT NULL,
        emp_id INT NOT NULL,
        role ENUM('member', 'sub_admin') DEFAULT 'member',
        added_by INT DEFAULT 0, 
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
        FOREIGN KEY (emp_id) REFERENCES employees(id) ON DELETE CASCADE,
        UNIQUE(team_id, emp_id)
    )";
    $pdo->exec($sql);
    echo "Created team_members table.\n";

    // 2. Migrate existing data from employees.team_id
    echo "Migrating existing team memberships...\n";

    // Check if team_id column exists in employees (it should)
    $stmt = $pdo->query("SELECT id, team_id FROM employees WHERE team_id IS NOT NULL AND team_id > 0");
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Insert into team_members if not exists
        // Use INSERT IGNORE or ON DUPLICATE KEY UPDATE to avoid errors on multiple runs
        $ins = $pdo->prepare("INSERT IGNORE INTO team_members (team_id, emp_id, role, added_by) VALUES (?, ?, 'member', 0)");
        $ins->execute([$row['team_id'], $row['id']]);
        $count++;
    }
    echo "Migrated $count records.\n";

    // 3. Optional: Add created_by_admin to teams if needed (Prompt mentioned it)
    // Check if exists
    $colCheck = $pdo->query("SHOW COLUMNS FROM teams LIKE 'created_by_admin'");
    if ($colCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE teams ADD COLUMN created_by_admin INT DEFAULT 1");
    }

    echo "Team Upgrade Complete!\n";

} catch (PDOException $e) {
    die("Setup Failed: " . $e->getMessage());
}
?>