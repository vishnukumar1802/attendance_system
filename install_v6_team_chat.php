<?php
require_once 'config/db.php';

try {
    echo "Setting up Team Chat...\n";

    $sql = "CREATE TABLE IF NOT EXISTS team_chat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT NOT NULL,
        sender_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES employees(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    $pdo->exec("CREATE INDEX idx_team_chat_fetch ON team_chat(team_id, id)");

    echo "Team Chat Table Created!\n";

} catch (PDOException $e) {
    die("Setup Failed: " . $e->getMessage());
}
?>